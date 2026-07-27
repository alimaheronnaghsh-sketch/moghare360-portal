<?php
declare(strict_types=1);

/**
 * MOGHARE360 P4 / Wave 1C-B4.1-B — Customer estimate approval + immutable version OTP.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-customer-cartable-helper.php';

const M360_EST_APPROVAL_OTP_TTL = 120;
const M360_EST_APPROVAL_OTP_RESEND = 60;
const M360_EST_APPROVAL_CSRF = 'estimate_customer_approval';
const M360_EST_APPROVAL_CHANNEL_SESSION = 'SESSION_DASHBOARD';
const M360_EST_APPROVAL_CHANNEL_TOKEN = 'SECURE_TOKEN';

function m360_estimate_approval_session_key(int $estimateVersionId): string
{
    return 'm360_estimate_otp_v_' . $estimateVersionId;
}

function m360_estimate_approval_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        m360_otp_session_start();
    }
}

/**
 * @return array{ok:bool,mobile:string,message:string}
 */
function m360_estimate_require_verified_session_for_owner(?int $customerId, string $ownerMobile): array
{
    if (!function_exists('m360_rw_customer_profile_resolve_verified_session_mobile')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';
    }
    $resolved = m360_rw_customer_profile_resolve_verified_session_mobile();
    if (!$resolved['ok']) {
        return ['ok' => false, 'mobile' => '', 'message' => 'ابتدا احراز هویت موبایل مشتری باید تکمیل شود.'];
    }
    $sessionMobile = m360_cartable_normalize_mobile($resolved['mobile']);
    $expectedMobile = m360_cartable_normalize_mobile($ownerMobile);
    if ($sessionMobile === '' || $expectedMobile === '' || !hash_equals($sessionMobile, $expectedMobile)) {
        return ['ok' => false, 'mobile' => '', 'message' => 'هویت موبایل تأییدشده با پرونده مطابقت ندارد.'];
    }

    return ['ok' => true, 'mobile' => $sessionMobile, 'message' => ''];
}

/**
 * @return array{ok:bool,message:string,estimate:?array,version:?array}
 */
function m360_estimate_resolve_token(string $rawToken): array
{
    $rawToken = trim($rawToken);
    if ($rawToken === '') {
        return ['ok' => false, 'message' => 'لینک نامعتبر است.', 'estimate' => null, 'version' => null];
    }
    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'سرویس در دسترس نیست.', 'estimate' => null, 'version' => null];
    }

    $hash = m360_estimate_hash($rawToken);
    $version = m360_estimate_fetch_version_by_token_hash($conn, $hash);
    if ($version !== null) {
        $estimateId = (int)($version['estimate_id'] ?? 0);
        $est = $estimateId > 0 ? m360_estimate_fetch($conn, $estimateId) : null;
        if ($est === null) {
            return ['ok' => false, 'message' => 'برآورد یافت نشد.', 'estimate' => null, 'version' => null];
        }
        $exp = strtotime((string)($version['secure_token_expires_at'] ?? ''));
        if ($exp > 0 && $exp < time()) {
            return ['ok' => false, 'message' => 'مهلت مشاهده برآورد به پایان رسیده است.', 'estimate' => null, 'version' => null];
        }
        $versionStatus = strtoupper((string)($version['version_status'] ?? ''));
        if (in_array($versionStatus, [M360_EST_VERSION_STATUS_SUPERSEDED, M360_EST_VERSION_STATUS_CANCELLED], true)) {
            return ['ok' => false, 'message' => 'این نسخه برآورد دیگر معتبر نیست.', 'estimate' => null, 'version' => null];
        }
        if ($versionStatus === M360_EST_VERSION_STATUS_ACCEPTED) {
            return ['ok' => true, 'message' => 'این برآورد قبلاً تأیید شده است.', 'estimate' => $est, 'version' => $version];
        }

        return ['ok' => true, 'message' => '', 'estimate' => $est, 'version' => $version];
    }

    if (!customer_core_table_exists($conn, M360_ESTIMATE_TABLE)) {
        return ['ok' => false, 'message' => 'سرویس در دسترس نیست.', 'estimate' => null, 'version' => null];
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_ESTIMATE_TABLE . ' WHERE secure_token_hash = ?',
        [$hash]
    );
    if ($rows === []) {
        return ['ok' => false, 'message' => 'لینک منقضی یا نامعتبر است.', 'estimate' => null, 'version' => null];
    }
    $est = $rows[0];
    $exp = strtotime((string)($est['secure_token_expires_at'] ?? ''));
    if ($exp > 0 && $exp < time()) {
        return ['ok' => false, 'message' => 'مهلت مشاهده برآورد به پایان رسیده است.', 'estimate' => null, 'version' => null];
    }
    if (strtoupper((string)($est['estimate_status'] ?? '')) === M360_EST_STATUS_APPROVED) {
        return ['ok' => true, 'message' => 'این برآورد قبلاً تأیید شده است.', 'estimate' => $est, 'version' => null];
    }

    return ['ok' => true, 'message' => '', 'estimate' => $est, 'version' => null];
}

/**
 * @param array{token?:string,task_id?:int} $input
 * @return array{
 *   ok:bool,
 *   message:string,
 *   estimate:?array,
 *   version:?array,
 *   items:list<array<string,mixed>>,
 *   task:?array,
 *   entry_mode:string,
 *   raw_token:string,
 *   jobcard:?array
 * }
 */
function m360_estimate_resolve_customer_context($conn, array $input): array
{
    $empty = [
        'ok' => false,
        'message' => 'دسترسی مجاز نیست.',
        'estimate' => null,
        'version' => null,
        'items' => [],
        'task' => null,
        'entry_mode' => '',
        'raw_token' => '',
        'jobcard' => null,
    ];
    if (!is_resource($conn)) {
        return array_merge($empty, ['message' => 'سرویس در دسترس نیست.']);
    }

    $taskId = (int)($input['task_id'] ?? 0);
    $rawToken = trim((string)($input['token'] ?? ''));

    if ($taskId > 0) {
        $task = m360_cartable_fetch_task_by_id($conn, $taskId);
        if ($task === null || (string)($task['task_type'] ?? '') !== M360_CARTABLE_TASK_TYPE_ESTIMATE_APPROVAL) {
            return array_merge($empty, ['message' => 'وظیفه معتبر یافت نشد.']);
        }
        if ((int)($task['is_active'] ?? 0) !== 1) {
            return array_merge($empty, ['message' => 'این وظیفه دیگر فعال نیست.']);
        }
        $versionId = (int)($task['source_entity_id'] ?? 0);
        $version = $versionId > 0 ? m360_estimate_fetch_version($conn, $versionId) : null;
        if ($version === null) {
            return array_merge($empty, ['message' => 'نسخه برآورد یافت نشد.']);
        }
        $estimateId = (int)($version['estimate_id'] ?? 0);
        $est = $estimateId > 0 ? m360_estimate_fetch($conn, $estimateId) : null;
        if ($est === null) {
            return array_merge($empty, ['message' => 'برآورد یافت نشد.']);
        }
        $jc = m360_estimate_fetch_jobcard($conn, (int)($version['jobcard_id'] ?? 0));
        if ($jc === null) {
            return array_merge($empty, ['message' => 'پرونده یافت نشد.']);
        }
        $binding = m360_estimate_require_verified_session_for_owner(
            (int)($task['customer_id'] ?? 0) > 0 ? (int)$task['customer_id'] : (int)($est['customer_id'] ?? 0),
            (string)($jc['customer_mobile'] ?? '')
        );
        if (!$binding['ok']) {
            return array_merge($empty, ['message' => $binding['message']]);
        }
        if (!m360_cartable_task_belongs_to_customer($task, (int)($task['customer_id'] ?? 0), $binding['mobile'])) {
            return array_merge($empty, ['message' => 'دسترسی به این وظیفه مجاز نیست.']);
        }
        if ($rawToken !== '') {
            $tokenResolved = m360_estimate_resolve_token($rawToken);
            if (!$tokenResolved['ok'] || !is_array($tokenResolved['version'])) {
                return array_merge($empty, ['message' => 'توکن با وظیفه مطابقت ندارد.']);
            }
            if ((int)($tokenResolved['version']['estimate_version_id'] ?? 0) !== $versionId) {
                return array_merge($empty, ['message' => 'توکن با وظیفه مطابقت ندارد.']);
            }
        }
        $versionStatus = strtoupper((string)($version['version_status'] ?? ''));
        if (in_array($versionStatus, [M360_EST_VERSION_STATUS_SUPERSEDED, M360_EST_VERSION_STATUS_CANCELLED], true)) {
            return array_merge($empty, ['message' => 'این نسخه برآورد دیگر معتبر نیست.']);
        }

        return [
            'ok' => true,
            'message' => '',
            'estimate' => $est,
            'version' => $version,
            'items' => m360_estimate_list_version_items($conn, $versionId),
            'task' => $task,
            'entry_mode' => M360_EST_APPROVAL_CHANNEL_SESSION,
            'raw_token' => $rawToken,
            'jobcard' => $jc,
        ];
    }

    if ($rawToken === '') {
        return array_merge($empty, ['message' => 'لینک نامعتبر است.']);
    }

    $resolved = m360_estimate_resolve_token($rawToken);
    if (!$resolved['ok'] || !is_array($resolved['estimate'])) {
        return array_merge($empty, ['message' => $resolved['message']]);
    }
    $est = $resolved['estimate'];
    $version = is_array($resolved['version'] ?? null) ? $resolved['version'] : null;
    if ($version === null && m360_estimate_version_tables_available($conn)) {
        $estimateId = (int)($est['estimate_id'] ?? 0);
        $activeRows = customer_core_fetch_rows(
            $conn,
            'SELECT TOP 1 * FROM dbo.' . M360_ESTIMATE_VERSION_TABLE . '
             WHERE estimate_id = ? AND version_status IN (?, ?)
             ORDER BY version_number DESC, estimate_version_id DESC',
            [$estimateId, M360_EST_VERSION_STATUS_ISSUED, M360_EST_VERSION_STATUS_VIEWED]
        );
        $version = $activeRows[0] ?? null;
    }
    if ($version === null) {
        return array_merge($empty, ['message' => 'نسخه برآورد یافت نشد.']);
    }
    $jc = m360_estimate_fetch_jobcard($conn, (int)($version['jobcard_id'] ?? ($est['jobcard_id'] ?? 0)));
    if ($jc === null) {
        return array_merge($empty, ['message' => 'پرونده یافت نشد.']);
    }
    if (!function_exists('m360_rw_customer_profile_resolve_verified_session_mobile')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';
    }
    $sessionResolved = m360_rw_customer_profile_resolve_verified_session_mobile();
    if ($sessionResolved['ok']) {
        $sessionMobile = m360_cartable_normalize_mobile($sessionResolved['mobile']);
        $ownerMobile = m360_cartable_normalize_mobile((string)($jc['customer_mobile'] ?? ''));
        if ($sessionMobile !== '' && $ownerMobile !== '' && !hash_equals($sessionMobile, $ownerMobile)) {
            return array_merge($empty, ['message' => 'هویت موبایل تأییدشده با برآورد مطابقت ندارد.']);
        }
    }

    $versionId = (int)($version['estimate_version_id'] ?? 0);

    return [
        'ok' => true,
        'message' => $resolved['message'],
        'estimate' => $est,
        'version' => $version,
        'items' => m360_estimate_list_version_items($conn, $versionId),
        'task' => null,
        'entry_mode' => M360_EST_APPROVAL_CHANNEL_TOKEN,
        'raw_token' => $rawToken,
        'jobcard' => $jc,
    ];
}

function m360_estimate_mark_version_viewed($conn, array $versionRow): void
{
    if (!is_resource($conn) || !m360_estimate_version_tables_available($conn)) {
        return;
    }
    $versionId = (int)($versionRow['estimate_version_id'] ?? 0);
    $estimateId = (int)($versionRow['estimate_id'] ?? 0);
    $jobcardId = (int)($versionRow['jobcard_id'] ?? 0);
    if ($versionId < 1) {
        return;
    }
    $status = strtoupper((string)($versionRow['version_status'] ?? ''));
    if ($status === M360_EST_VERSION_STATUS_ISSUED) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_ESTIMATE_VERSION_TABLE . ' SET version_status = ?, viewed_at = SYSUTCDATETIME() WHERE estimate_version_id = ? AND version_status = ?',
            [M360_EST_VERSION_STATUS_VIEWED, $versionId, M360_EST_VERSION_STATUS_ISSUED]
        );
    }
    if ($estimateId > 0) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET viewed_at = COALESCE(viewed_at, SYSUTCDATETIME()), estimate_status = ? WHERE estimate_id = ? AND estimate_status = ?',
            [M360_EST_STATUS_VIEWED, $estimateId, M360_EST_STATUS_SENT]
        );
    }
    m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_CUSTOMER_VIEWED', $estimateId, null, null, $versionId);
}

/** @return array{ok:bool,message:string,test_mode?:bool} */
function m360_estimate_send_otp(array $context): array
{
    m360_estimate_approval_session_start();
    $version = is_array($context['version'] ?? null) ? $context['version'] : null;
    $estimate = is_array($context['estimate'] ?? null) ? $context['estimate'] : null;
    if ($version === null || $estimate === null) {
        return ['ok' => false, 'message' => 'نسخه برآورد معتبر نیست.'];
    }
    $versionId = (int)($version['estimate_version_id'] ?? 0);
    $jobcardId = (int)($version['jobcard_id'] ?? ($estimate['jobcard_id'] ?? 0));
    if ($versionId < 1) {
        return ['ok' => false, 'message' => 'نسخه برآورد معتبر نیست.'];
    }
    $versionStatus = strtoupper((string)($version['version_status'] ?? ''));
    if ($versionStatus === M360_EST_VERSION_STATUS_ACCEPTED) {
        return ['ok' => false, 'message' => 'این برآورد قبلاً تأیید شده است.'];
    }
    if (in_array($versionStatus, [M360_EST_VERSION_STATUS_SUPERSEDED, M360_EST_VERSION_STATUS_CANCELLED, M360_EST_VERSION_STATUS_REJECTED], true)) {
        return ['ok' => false, 'message' => 'این نسخه برآورد دیگر قابل تأیید نیست.'];
    }

    $conn = customer_core_db();
    $jc = $conn !== false ? m360_estimate_fetch_jobcard($conn, $jobcardId) : null;
    $mobile = trim((string)($jc['customer_mobile'] ?? ''));
    if ($mobile === '') {
        return ['ok' => false, 'message' => 'شماره موبایل مشتری یافت نشد.'];
    }
    $entryMode = (string)($context['entry_mode'] ?? M360_EST_APPROVAL_CHANNEL_TOKEN);
    if ($entryMode === M360_EST_APPROVAL_CHANNEL_SESSION) {
        $binding = m360_estimate_require_verified_session_for_owner((int)($estimate['customer_id'] ?? 0), $mobile);
        if (!$binding['ok']) {
            return ['ok' => false, 'message' => $binding['message']];
        }
        $mobile = $binding['mobile'];
    }

    $key = m360_estimate_approval_session_key($versionId);
    $last = (int)($_SESSION[$key]['last_sent_at'] ?? 0);
    if ($last > 0 && (time() - $last) < M360_EST_APPROVAL_OTP_RESEND) {
        return ['ok' => false, 'message' => 'لطفاً چند ثانیه دیگر تلاش کنید.'];
    }

    $contentHash = (string)($version['content_hash'] ?? '');
    $otpBag = [
        'mobile' => $mobile,
        'estimate_version_id' => $versionId,
        'content_hash' => $contentHash,
        'verified' => false,
        'viewed' => false,
    ];

    if (m360_otp_sms_configured()) {
        $code = (string)random_int(100000, 999999);
        $sms = m360_otp_send_sms($mobile, $code);
        if (!$sms['ok']) {
            return $sms;
        }
        $otpBag['hash'] = password_hash($code, PASSWORD_DEFAULT);
        $otpBag['expires_at'] = time() + M360_EST_APPROVAL_OTP_TTL;
        $otpBag['last_sent_at'] = time();
        $_SESSION[$key] = $otpBag;
        if ($conn !== false) {
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_CUSTOMER_OTP_SENT', (int)($estimate['estimate_id'] ?? 0), null, null, $versionId);
        }

        return ['ok' => true, 'message' => 'کد تأیید ارسال شد.'];
    }

    if (m360_otp_can_use_dev_code()) {
        $code = m360_otp_get_dev_code();
        $otpBag['hash'] = password_hash($code, PASSWORD_DEFAULT);
        $otpBag['expires_at'] = time() + M360_EST_APPROVAL_OTP_TTL;
        $otpBag['last_sent_at'] = time();
        $_SESSION[$key] = $otpBag;

        return ['ok' => true, 'message' => m360_otp_dev_fallback_message(), 'test_mode' => true];
    }

    return ['ok' => false, 'message' => M360_OTP_MSG_SMS_INACTIVE];
}

function m360_estimate_verify_otp(int $estimateVersionId, string $mobile, string $code, string $contentHash): bool
{
    m360_estimate_approval_session_start();
    $key = m360_estimate_approval_session_key($estimateVersionId);
    $bag = $_SESSION[$key] ?? null;
    if (!is_array($bag) || trim($mobile) !== trim((string)($bag['mobile'] ?? ''))) {
        return false;
    }
    if ((int)($bag['estimate_version_id'] ?? 0) !== $estimateVersionId) {
        return false;
    }
    if ($contentHash !== '' && (string)($bag['content_hash'] ?? '') !== $contentHash) {
        return false;
    }
    if ((int)($bag['expires_at'] ?? 0) < time()) {
        return false;
    }
    if (!password_verify($code, (string)($bag['hash'] ?? ''))) {
        return false;
    }
    $_SESSION[$key]['verified'] = true;

    return true;
}

function m360_estimate_otp_was_verified(int $estimateVersionId, string $mobile, string $contentHash): bool
{
    m360_estimate_approval_session_start();
    $bag = $_SESSION[m360_estimate_approval_session_key($estimateVersionId)] ?? null;

    return is_array($bag)
        && !empty($bag['verified'])
        && trim($mobile) === trim((string)($bag['mobile'] ?? ''))
        && (int)($bag['estimate_version_id'] ?? 0) === $estimateVersionId
        && ($contentHash === '' || (string)($bag['content_hash'] ?? '') === $contentHash);
}

function m360_estimate_client_ip(): string
{
    return substr(trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown')), 0, 100);
}

function m360_estimate_client_ua(): string
{
    return substr(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')), 0, 1000);
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_estimate_customer_decision(array $context, array $input): array
{
    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'سرویس در دسترس نیست.'];
    }

    $version = is_array($context['version'] ?? null) ? $context['version'] : null;
    $estimate = is_array($context['estimate'] ?? null) ? $context['estimate'] : null;
    if ($version === null || $estimate === null) {
        return ['ok' => false, 'message' => 'نسخه برآورد معتبر نیست.'];
    }

    $versionId = (int)($version['estimate_version_id'] ?? 0);
    $estimateId = (int)($estimate['estimate_id'] ?? 0);
    $jobcardId = (int)($version['jobcard_id'] ?? ($estimate['jobcard_id'] ?? 0));
    $contentHash = trim((string)($input['content_hash'] ?? ($version['content_hash'] ?? '')));
    $postedHash = trim((string)($input['content_hash'] ?? ''));
    if ($postedHash === '' || !hash_equals((string)($version['content_hash'] ?? ''), $postedHash)) {
        return ['ok' => false, 'message' => 'محتوای برآورد تغییر کرده است. لطفاً صفحه را تازه‌سازی کنید.'];
    }

    $entryMode = (string)($context['entry_mode'] ?? M360_EST_APPROVAL_CHANNEL_TOKEN);
    if ($entryMode === M360_EST_APPROVAL_CHANNEL_SESSION) {
        $jc = m360_estimate_fetch_jobcard($conn, $jobcardId);
        $binding = m360_estimate_require_verified_session_for_owner((int)($estimate['customer_id'] ?? 0), (string)($jc['customer_mobile'] ?? ''));
        if (!$binding['ok']) {
            return ['ok' => false, 'message' => $binding['message']];
        }
        if (!function_exists('erp_csrf_validate_token')) {
            customer_core_require_helper('erp-csrf.php');
        }
        $csrfToken = isset($input['erp_csrf_token']) ? (string)$input['erp_csrf_token'] : '';
        if (!erp_csrf_validate_token(M360_EST_APPROVAL_CSRF, $csrfToken)) {
            return ['ok' => false, 'message' => 'درخواست نامعتبر است. لطفاً دوباره تلاش کنید.'];
        }
    }

    $versionStatus = strtoupper((string)($version['version_status'] ?? ''));
    if (in_array($versionStatus, [M360_EST_VERSION_STATUS_SUPERSEDED, M360_EST_VERSION_STATUS_CANCELLED], true)) {
        return ['ok' => false, 'message' => 'این نسخه برآورد دیگر معتبر نیست.'];
    }

    $decision = strtolower(trim((string)($input['decision'] ?? 'approve')));
    $channel = $entryMode === M360_EST_APPROVAL_CHANNEL_SESSION
        ? M360_EST_APPROVAL_CHANNEL_SESSION
        : M360_EST_APPROVAL_CHANNEL_TOKEN;

    $existingApproval = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_estimate_approvals WHERE estimate_version_id = ? ORDER BY approval_id DESC',
        [$versionId]
    );
    $existing = $existingApproval[0] ?? null;
    if (is_array($existing)) {
        $existingStatus = strtoupper((string)($existing['approval_status'] ?? ''));
        if ($decision === 'reject' && $existingStatus === M360_EST_STATUS_REJECTED) {
            return ['ok' => true, 'message' => 'برآورد قبلاً رد شده است.'];
        }
        if ($decision !== 'reject' && $existingStatus === M360_EST_STATUS_APPROVED) {
            return ['ok' => true, 'message' => 'برآورد قبلاً تأیید شده است.'];
        }
        if ($decision === 'reject' && $existingStatus === M360_EST_STATUS_APPROVED) {
            return ['ok' => false, 'message' => 'برآورد قبلاً تأیید شده و قابل رد نیست.'];
        }
        if ($decision !== 'reject' && $existingStatus === M360_EST_STATUS_REJECTED) {
            return ['ok' => false, 'message' => 'برآورد قبلاً رد شده و قابل تأیید نیست.'];
        }
    }

    if ($versionStatus === M360_EST_VERSION_STATUS_ACCEPTED) {
        return $decision === 'reject'
            ? ['ok' => false, 'message' => 'برآورد قبلاً تأیید شده و قابل رد نیست.']
            : ['ok' => true, 'message' => 'برآورد قبلاً تأیید شده است.'];
    }
    if ($versionStatus === M360_EST_VERSION_STATUS_REJECTED) {
        return $decision === 'reject'
            ? ['ok' => true, 'message' => 'برآورد قبلاً رد شده است.']
            : ['ok' => false, 'message' => 'برآورد قبلاً رد شده و قابل تأیید نیست.'];
    }

    $jc = m360_estimate_fetch_jobcard($conn, $jobcardId);
    $mobile = trim((string)($jc['customer_mobile'] ?? ''));
    if ($mobile === '') {
        return ['ok' => false, 'message' => 'موبایل مشتری یافت نشد.'];
    }

    if ($decision === 'reject') {
        $note = trim((string)($input['reject_reason'] ?? ($input['customer_note'] ?? '')));
        if (mb_strlen($note) > 1000) {
            return ['ok' => false, 'message' => 'یادداشت مشتری بیش از حد مجاز است.'];
        }

        return m360_estimate_apply_customer_decision_tx(
            $conn,
            $version,
            $estimate,
            $mobile,
            M360_EST_STATUS_REJECTED,
            M360_EST_VERSION_STATUS_REJECTED,
            $channel,
            $contentHash,
            $note,
            false,
            ''
        );
    }

    $c1 = !empty($input['confirm_viewed']);
    $c2 = !empty($input['confirm_amount']);
    $c3 = !empty($input['confirm_hidden']);
    $otpCode = trim((string)($input['otp_code'] ?? ''));
    if (!$c1 || !$c2 || !$c3) {
        return ['ok' => false, 'message' => 'پذیرش همه شرایط الزامی است.'];
    }
    if (!m360_estimate_otp_was_verified($versionId, $mobile, $contentHash)
        && !m360_estimate_verify_otp($versionId, $mobile, $otpCode, $contentHash)) {
        return ['ok' => false, 'message' => 'کد تأیید معتبر نیست.'];
    }

    return m360_estimate_apply_customer_decision_tx(
        $conn,
        $version,
        $estimate,
        $mobile,
        M360_EST_STATUS_APPROVED,
        M360_EST_VERSION_STATUS_ACCEPTED,
        $channel,
        $contentHash,
        '',
        true,
        $otpCode
    );
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_estimate_apply_customer_decision_tx(
    $conn,
    array $version,
    array $estimate,
    string $mobile,
    string $estimateStatus,
    string $versionStatus,
    string $channel,
    string $contentHash,
    string $customerNote,
    bool $otpVerified,
    string $otpCode
): array {
    $versionId = (int)($version['estimate_version_id'] ?? 0);
    $estimateId = (int)($estimate['estimate_id'] ?? 0);
    $jobcardId = (int)($version['jobcard_id'] ?? ($estimate['jobcard_id'] ?? 0));
    if ($versionId < 1 || $estimateId < 1) {
        return ['ok' => false, 'message' => 'نسخه برآورد معتبر نیست.'];
    }

    if (!m360_estimate_tx_begin($conn)) {
        return ['ok' => false, 'message' => 'شروع تراکنش ناموفق بود.'];
    }

    try {
        $freshRows = customer_core_fetch_rows(
            $conn,
            'SELECT TOP 1 * FROM dbo.' . M360_ESTIMATE_VERSION_TABLE . ' WHERE estimate_version_id = ?',
            [$versionId]
        );
        $fresh = $freshRows[0] ?? null;
        if ($fresh === null) {
            throw new RuntimeException('version_missing');
        }
        $freshStatus = strtoupper((string)($fresh['version_status'] ?? ''));
        if (in_array($freshStatus, [M360_EST_VERSION_STATUS_SUPERSEDED, M360_EST_VERSION_STATUS_CANCELLED], true)) {
            throw new RuntimeException('version_stale');
        }
        if (!hash_equals((string)($fresh['content_hash'] ?? ''), $contentHash)) {
            throw new RuntimeException('hash_mismatch');
        }
        if ($freshStatus === M360_EST_VERSION_STATUS_ACCEPTED && $versionStatus === M360_EST_VERSION_STATUS_ACCEPTED) {
            m360_estimate_tx_commit($conn);

            return ['ok' => true, 'message' => 'برآورد قبلاً تأیید شده است.'];
        }
        if ($freshStatus === M360_EST_VERSION_STATUS_REJECTED && $versionStatus === M360_EST_VERSION_STATUS_REJECTED) {
            m360_estimate_tx_commit($conn);

            return ['ok' => true, 'message' => 'برآورد قبلاً رد شده است.'];
        }
        if ($freshStatus === M360_EST_VERSION_STATUS_ACCEPTED || $freshStatus === M360_EST_VERSION_STATUS_REJECTED) {
            throw new RuntimeException('opposite_decision_blocked');
        }

        $ip = m360_estimate_client_ip();
        $ua = m360_estimate_client_ua();
        $total = (float)($fresh['total_amount'] ?? 0);
        $approvalHash = hash('sha256', $versionId . '|' . $contentHash . '|' . $mobile . '|' . gmdate('c'));

        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_ESTIMATE_VERSION_TABLE . '
             SET version_status = ?, decided_at = SYSUTCDATETIME(), decision_channel = ?, customer_note = ?
             WHERE estimate_version_id = ? AND version_status IN (?, ?)',
            [
                $versionStatus,
                $channel,
                $customerNote !== '' ? mb_substr($customerNote, 0, 1000) : null,
                $versionId,
                M360_EST_VERSION_STATUS_ISSUED,
                M360_EST_VERSION_STATUS_VIEWED,
            ]
        );

        if ($estimateStatus === M360_EST_STATUS_APPROVED) {
            customer_core_execute(
                $conn,
                'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET estimate_status = ?, approved_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE estimate_id = ?',
                [M360_EST_STATUS_APPROVED, $estimateId]
            );
        } else {
            customer_core_execute(
                $conn,
                'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET estimate_status = ?, rejected_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE estimate_id = ?',
                [M360_EST_STATUS_REJECTED, $estimateId]
            );
        }

        $approvalExists = customer_core_fetch_rows(
            $conn,
            'SELECT TOP 1 approval_id FROM dbo.erp_estimate_approvals WHERE estimate_version_id = ?',
            [$versionId]
        );
        if ($approvalExists === []) {
            if ($estimateStatus === M360_EST_STATUS_APPROVED) {
                customer_core_execute(
                    $conn,
                    'INSERT INTO dbo.erp_estimate_approvals (
                        estimate_id, estimate_version_id, jobcard_id, mobile, approval_status, otp_verified, otp_verified_at,
                        approved_total_amount, approval_ip, approval_user_agent, approval_hash, content_hash, decision_channel,
                        customer_note, approved_at
                    ) VALUES (?, ?, ?, ?, ?, 1, SYSUTCDATETIME(), ?, ?, ?, ?, ?, ?, ?, SYSUTCDATETIME())',
                    [
                        $estimateId,
                        $versionId,
                        $jobcardId,
                        $mobile,
                        M360_EST_STATUS_APPROVED,
                        $total,
                        $ip,
                        $ua,
                        $approvalHash,
                        $contentHash,
                        $channel,
                        null,
                    ]
                );
            } else {
                customer_core_execute(
                    $conn,
                    'INSERT INTO dbo.erp_estimate_approvals (
                        estimate_id, estimate_version_id, jobcard_id, mobile, approval_status, otp_verified,
                        approval_ip, approval_user_agent, approval_hash, content_hash, decision_channel, customer_note, rejected_at
                    ) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, SYSUTCDATETIME())',
                    [
                        $estimateId,
                        $versionId,
                        $jobcardId,
                        $mobile,
                        M360_EST_STATUS_REJECTED,
                        $ip,
                        $ua,
                        $approvalHash,
                        $contentHash,
                        $channel,
                        $customerNote !== '' ? mb_substr($customerNote, 0, 1000) : null,
                    ]
                );
            }
        }

        if ($estimateStatus === M360_EST_STATUS_APPROVED) {
            $partsEval = m360_parts_gate_evaluate($conn, $estimateId);
            $finEval = m360_finance_gate_evaluate(
                $conn,
                $estimateId,
                $total,
                (float)($estimate['advance_required_amount'] ?? m360_finance_calculate_advance($total))
            );
            customer_core_execute(
                $conn,
                'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET parts_gate_status = ?, finance_gate_status = ? WHERE estimate_id = ?',
                [$partsEval['parts_gate_status'], $finEval['finance_gate_status'], $estimateId]
            );
            if (customer_core_column_exists($conn, 'erp_jobcards', 'estimate_status')) {
                customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET estimate_status = N\'ESTIMATE_APPROVED\', estimate_approved_at = SYSUTCDATETIME() WHERE jobcard_id = ?', [$jobcardId]);
            }
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_CUSTOMER_APPROVED', $estimateId, null, null, $versionId);
            m360_estimate_jobcard_history($conn, $jobcardId, 'JOBCARD_ESTIMATE_APPROVED', null, M360_EST_STATUS_APPROVED, 'Customer OTP approval', 0);
        } else {
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_CUSTOMER_REJECTED', $estimateId, $customerNote !== '' ? $customerNote : null, null, $versionId);
        }

        m360_cartable_complete_estimate_approval_task(
            $conn,
            $versionId,
            $channel,
            ['decision' => $estimateStatus, 'estimate_version_id' => $versionId]
        );

        m360_estimate_tx_commit($conn);

        return [
            'ok' => true,
            'message' => $estimateStatus === M360_EST_STATUS_APPROVED ? 'برآورد با موفقیت تأیید شد.' : 'برآورد رد شد.',
        ];
    } catch (Throwable $e) {
        m360_estimate_tx_rollback($conn);
        $code = $e->getMessage();
        if ($code === 'version_stale') {
            return ['ok' => false, 'message' => 'این نسخه برآورد دیگر معتبر نیست.'];
        }
        if ($code === 'hash_mismatch') {
            return ['ok' => false, 'message' => 'محتوای برآورد تغییر کرده است. لطفاً صفحه را تازه‌سازی کنید.'];
        }
        if ($code === 'opposite_decision_blocked') {
            return ['ok' => false, 'message' => 'تصمیم قبلی قابل تغییر نیست.'];
        }

        return ['ok' => false, 'message' => 'ثبت تصمیم ناموفق بود.'];
    }
}

function m360_estimate_is_customer_approved(array $estimateRow, ?array $versionRow = null): bool
{
    if ($versionRow !== null) {
        $versionStatus = strtoupper((string)($versionRow['version_status'] ?? ''));
        if ($versionStatus === M360_EST_VERSION_STATUS_ACCEPTED) {
            return true;
        }
        if ($versionStatus === M360_EST_VERSION_STATUS_REJECTED) {
            return false;
        }
    }

    return strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_APPROVED
        || strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_PARTS_PENDING
        || strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_FIN_PENDING
        || strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_PARTS_CLEARED
        || strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_FIN_CLEARED
        || strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_APPROVED_WORK;
}

function m360_estimate_is_version_decided(array $versionRow): bool
{
    $status = strtoupper((string)($versionRow['version_status'] ?? ''));

    return in_array($status, [M360_EST_VERSION_STATUS_ACCEPTED, M360_EST_VERSION_STATUS_REJECTED], true);
}

function m360_estimate_approval_csrf_input(): string
{
    if (!function_exists('erp_csrf_input')) {
        customer_core_require_helper('erp-csrf.php');
    }

    return erp_csrf_input(M360_EST_APPROVAL_CSRF);
}
