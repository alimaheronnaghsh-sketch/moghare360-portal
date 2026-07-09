<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1.5 — Contract OTP + digital signature helper.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';

const M360_CONTRACT_OTP_TTL = 120;
const M360_CONTRACT_OTP_RESEND = 60;
const M360_CONTRACT_CONSENT_TEXT_FA = 'متن قرارداد را به‌طور کامل مطالعه کردم و مفاد آن را می‌پذیرم.';
const M360_CONTRACT_OTP_PURPOSE = 'CONTRACT_CONFIRMATION';

function m360_contract_workflow_review_completed(array $contractRow): bool
{
    $workflow = m360_intake_contract_get_workflow_meta($contractRow);

    return trim((string)($workflow['review_completed_at'] ?? '')) !== '';
}

function m360_contract_workflow_consent_accepted(array $contractRow): bool
{
    $workflow = m360_intake_contract_get_workflow_meta($contractRow);

    return trim((string)($workflow['consent_at'] ?? '')) !== '';
}

function m360_contract_workflow_signature_draft_ready(array $contractRow): bool
{
    $workflow = m360_intake_contract_get_workflow_meta($contractRow);

    return trim((string)($workflow['signature_draft_hash'] ?? '')) !== '';
}

/** @return array{ok:bool,message:string} */
function m360_contract_mark_review_completed(array $contractRow): array
{
    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'قرارداد قبلاً تأیید شده است.'];
    }
    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'خطا در اتصال به سامانه.'];
    }
    $contractId = (int)($contractRow['contract_id'] ?? 0);
    $patch = ['review_completed_at' => gmdate('c')];
    $result = m360_intake_contract_patch_workflow_meta($conn, $contractId, $patch);
    if ($result['ok']) {
        m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_REVIEW_COMPLETED', null, null);
        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET viewed_at = COALESCE(viewed_at, SYSUTCDATETIME()), contract_status = ?, updated_at = SYSUTCDATETIME() WHERE contract_id = ? AND contract_status IN (?, ?)',
            [M360_CONTRACT_STATUS_VIEWED, $contractId, M360_CONTRACT_STATUS_SENT, M360_CONTRACT_STATUS_GENERATED]
        );
    }

    return $result;
}

/** @return array{ok:bool,message:string} */
function m360_contract_mark_consent_accepted(array $contractRow): array
{
    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'قرارداد قبلاً تأیید شده است.'];
    }
    if (!m360_contract_workflow_review_completed($contractRow)) {
        return ['ok' => false, 'message' => 'ابتدا باید متن قرارداد را تا انتها مطالعه کنید.'];
    }
    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'خطا در اتصال به سامانه.'];
    }
    $contractId = (int)($contractRow['contract_id'] ?? 0);
    $patch = [
        'consent_at' => gmdate('c'),
        'consent_text' => M360_CONTRACT_CONSENT_TEXT_FA,
    ];
    $result = m360_intake_contract_patch_workflow_meta($conn, $contractId, $patch);
    if ($result['ok']) {
        m360_intake_contract_record_event($conn, $contractId, 'CUSTOMER_CONSENT_ACCEPTED', M360_CONTRACT_CONSENT_TEXT_FA, null);
    }

    return $result;
}

/** @return array{ok:bool,message:string} */
function m360_contract_save_signature_draft(array $contractRow, string $signatureImageData): array
{
    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'قرارداد قبلاً تأیید شده است.'];
    }
    if (!m360_contract_workflow_consent_accepted($contractRow)) {
        return ['ok' => false, 'message' => 'ابتدا باید پذیرش صریح متن قرارداد را ثبت کنید.'];
    }
    if ($signatureImageData === '' || strlen($signatureImageData) < 100) {
        return ['ok' => false, 'message' => 'امضای مستقیم روی صفحه الزامی است.'];
    }
    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'خطا در اتصال به سامانه.'];
    }
    $contractId = (int)($contractRow['contract_id'] ?? 0);
    $sigHash = m360_intake_contract_hash($signatureImageData);
    $patch = [
        'signature_draft_hash' => $sigHash,
        'signature_draft_at' => gmdate('c'),
    ];
    $result = m360_intake_contract_patch_workflow_meta($conn, $contractId, $patch);
    if ($result['ok']) {
        m360_contract_sig_session_start();
        $_SESSION['m360_contract_sig_draft_' . $contractId] = $signatureImageData;
        m360_intake_contract_record_event($conn, $contractId, 'DIRECT_SIGNATURE_CAPTURED', 'hash=' . substr($sigHash, 0, 16), null);
    }

    return $result;
}

function m360_contract_sig_session_key(int $contractId): string
{
    return 'm360_contract_otp_' . $contractId;
}

function m360_contract_sig_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        m360_otp_session_start();
    }
}

/** @return array{ok:bool,message:string,test_mode?:bool} */
function m360_contract_send_otp(array $contractRow): array
{
    m360_contract_sig_session_start();
    $contractId = (int)($contractRow['contract_id'] ?? 0);
    $mobile = trim((string)($contractRow['mobile'] ?? ''));
    if ($contractId < 1 || $mobile === '') {
        return ['ok' => false, 'message' => 'اطلاعات قرارداد معتبر نیست.'];
    }
    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'این قرارداد قبلاً امضا شده است.'];
    }
    if (!m360_contract_workflow_review_completed($contractRow)) {
        return ['ok' => false, 'message' => 'ابتدا باید مطالعه کامل قرارداد ثبت شود.'];
    }
    if (!m360_contract_workflow_consent_accepted($contractRow)) {
        return ['ok' => false, 'message' => 'ابتدا باید پذیرش صریح متن قرارداد ثبت شود.'];
    }
    if (!m360_contract_workflow_signature_draft_ready($contractRow)) {
        return ['ok' => false, 'message' => 'ابتدا باید امضای مستقیم ثبت شود.'];
    }

    $key = m360_contract_sig_session_key($contractId);
    $last = (int)($_SESSION[$key]['last_sent_at'] ?? 0);
    if ($last > 0 && (time() - $last) < M360_CONTRACT_OTP_RESEND) {
        $wait = M360_CONTRACT_OTP_RESEND - (time() - $last);
        return ['ok' => false, 'message' => 'لطفاً ' . $wait . ' ثانیه دیگر تلاش کنید.'];
    }

    if (m360_otp_sms_configured()) {
        $code = (string)random_int(100000, 999999);
        $sms = m360_otp_send_sms($mobile, $code);
        if (!$sms['ok']) {
            return $sms;
        }
        $_SESSION[$key] = [
            'mobile' => $mobile,
            'hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + M360_CONTRACT_OTP_TTL,
            'last_sent_at' => time(),
            'verified' => false,
        ];
        $conn = customer_core_db();
        if ($conn !== false) {
            customer_core_execute(
                $conn,
                'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_status = ?, updated_at = SYSUTCDATETIME() WHERE contract_id = ? AND contract_status <> ?',
                [M360_CONTRACT_STATUS_OTP_SENT, $contractId, M360_CONTRACT_STATUS_SIGNED]
            );
            m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_OTP_SENT', M360_CONTRACT_OTP_PURPOSE, null);
        }
        return ['ok' => true, 'message' => 'کد تأیید برای شما ارسال شد.'];
    }

    if (m360_otp_can_use_dev_code()) {
        $code = m360_otp_get_dev_code();
        $_SESSION[$key] = [
            'mobile' => $mobile,
            'hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + M360_CONTRACT_OTP_TTL,
            'last_sent_at' => time(),
            'verified' => false,
        ];
        return ['ok' => true, 'message' => m360_otp_dev_fallback_message(), 'test_mode' => true];
    }

    return ['ok' => false, 'message' => M360_OTP_MSG_SMS_INACTIVE];
}

function m360_contract_otp_verified(int $contractId, string $mobile): bool
{
    m360_contract_sig_session_start();
    $key = m360_contract_sig_session_key($contractId);
    $bag = $_SESSION[$key] ?? null;
    if (!is_array($bag)) {
        return false;
    }
    if (trim((string)($bag['mobile'] ?? '')) !== $mobile) {
        return false;
    }
    if (empty($bag['verified'])) {
        return false;
    }
    if ((int)($bag['expires_at'] ?? 0) < time()) {
        return false;
    }
    return true;
}

/** @return array{ok:bool,message:string} */
function m360_contract_verify_otp(int $contractId, string $mobile, string $otp): array
{
    m360_contract_sig_session_start();
    $key = m360_contract_sig_session_key($contractId);
    $bag = $_SESSION[$key] ?? null;
    if (!is_array($bag)) {
        return ['ok' => false, 'message' => 'ابتدا کد تأیید را درخواست کنید.'];
    }
    if (trim((string)($bag['mobile'] ?? '')) !== $mobile) {
        return ['ok' => false, 'message' => 'شماره موبایل مطابقت ندارد.'];
    }
    if ((int)($bag['expires_at'] ?? 0) < time()) {
        return ['ok' => false, 'message' => 'کد تأیید منقضی شده است.'];
    }
    $digits = preg_replace('/\D+/', '', $otp) ?? '';
    if (strlen($digits) !== 6) {
        return ['ok' => false, 'message' => 'کد تأیید باید ۶ رقم باشد.'];
    }
    if (!password_verify($digits, (string)($bag['hash'] ?? ''))) {
        return ['ok' => false, 'message' => 'کد تأیید نادرست است.'];
    }
    $_SESSION[$key]['verified'] = true;
    return ['ok' => true, 'message' => 'کد تأیید شد.'];
}

/** @return array{ok:bool,message:string,test_mode?:bool} */
function m360_contract_send_link_sms(string $mobile, string $url): array
{
    if (!m360_otp_sms_configured()) {
        if (m360_otp_can_use_dev_code()) {
            return ['ok' => true, 'message' => 'پیامک در محیط توسعه ارسال نشد. لینک را دستی ارسال کنید.', 'test_mode' => true];
        }
        return ['ok' => false, 'message' => M360_OTP_MSG_SMS_INACTIVE];
    }
    $message = 'مقاره۳۶۰: لینک قرارداد پذیرش خودرو: ' . $url;
    return m360_contract_send_plain_sms($mobile, $message);
}

/** @return array{ok:bool,message:string} */
function m360_contract_send_plain_sms(string $mobile, string $message): array
{
    if (!m360_otp_sms_configured()) {
        return ['ok' => false, 'message' => M360_OTP_MSG_SMS_INACTIVE];
    }
    $s = m360_otp_sms_settings();
    if ($s['provider'] !== 'ippanel') {
        return ['ok' => false, 'message' => M360_OTP_MSG_SMS_FAILED];
    }
    $payload = [
        'sending_type' => 'webservice',
        'from_number' => m360_otp_ippanel_from_number((string)$s['sender']),
        'params' => [
            'recipients' => [m360_otp_ippanel_recipient($mobile)],
            'message' => $message,
        ],
    ];
    return m360_otp_ippanel_send($payload, (string)$s['api_key']);
}

/**
 * @param array<string, mixed> $contractRow
 * @return array{ok:bool,message:string}
 */
function m360_contract_complete_signature(
    array $contractRow,
    string $rawToken,
    string $signatureImageData,
    bool $readConfirmed,
    bool $infoConfirmed,
    bool $otpTermsConfirmed,
    string $otpCode
): array {
    $contractId = (int)($contractRow['contract_id'] ?? 0);
    $mobile = trim((string)($contractRow['mobile'] ?? ''));

    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'این قرارداد قبلاً امضا شده است.'];
    }
    if (!m360_contract_workflow_review_completed($contractRow)) {
        return ['ok' => false, 'message' => 'مطالعه کامل قرارداد ثبت نشده است.'];
    }
    if (!m360_contract_workflow_consent_accepted($contractRow)) {
        return ['ok' => false, 'message' => 'پذیرش صریح متن قرارداد ثبت نشده است.'];
    }
    if (!$readConfirmed) {
        return ['ok' => false, 'message' => 'تأیید مطالعه قرارداد الزامی است.'];
    }
    if ($signatureImageData === '' || strlen($signatureImageData) < 100) {
        m360_contract_sig_session_start();
        $sessionDraft = (string)($_SESSION['m360_contract_sig_draft_' . $contractId] ?? '');
        if ($sessionDraft !== '' && strlen($sessionDraft) >= 100) {
            $signatureImageData = $sessionDraft;
        }
    }
    if ($signatureImageData === '' || strlen($signatureImageData) < 100) {
        return ['ok' => false, 'message' => 'امضا روی صفحه الزامی است.'];
    }
    $workflow = m360_intake_contract_get_workflow_meta($contractRow);
    $draftHash = trim((string)($workflow['signature_draft_hash'] ?? ''));
    $sigHash = m360_intake_contract_hash($signatureImageData);
    if ($draftHash === '' || !hash_equals($draftHash, $sigHash)) {
        return ['ok' => false, 'message' => 'امضای ثبت‌شده با امضای تأییدشده مطابقت ندارد.'];
    }

    $otpCheck = m360_contract_verify_otp($contractId, $mobile, $otpCode);
    if (!$otpCheck['ok']) {
        return $otpCheck;
    }

    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'خطا در اتصال به سامانه.'];
    }

    $snapshot = m360_intake_contract_snapshot_from_row($contractRow);
    $html = m360_contract_render_html($snapshot, true);
    $signedHash = m360_intake_contract_hash($html . $signatureImageData . $mobile . gmdate('c'));
    $sigHash = m360_intake_contract_hash($signatureImageData);
    $ip = customer_core_client_ip();
    $ua = customer_core_user_agent();

    $sigOk = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_CONTRACT_SIG_TABLE . ' (
            contract_id, mobile, otp_verified, otp_verified_at, signature_image_data,
            signature_hash, signer_ip, signer_user_agent, signed_contract_hash, signed_at
        ) VALUES (?, ?, 1, SYSUTCDATETIME(), ?, ?, ?, ?, ?, SYSUTCDATETIME())',
        [$contractId, $mobile, $signatureImageData, $sigHash, $ip, $ua, $signedHash]
    );
    if ($sigOk === false) {
        return ['ok' => false, 'message' => 'ثبت امضا ناموفق بود.'];
    }

    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_status = ?, signed_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME(), contract_body_hash = ? WHERE contract_id = ?',
        [M360_CONTRACT_STATUS_SIGNED, $signedHash, $contractId]
    );

    $jobcardId = (int)($contractRow['jobcard_id'] ?? 0);
    if ($jobcardId > 0) {
        if (customer_core_column_exists($conn, 'erp_jobcards', 'contract_status')) {
            customer_core_execute(
                $conn,
                'UPDATE dbo.erp_jobcards SET contract_status = ?, intake_contract_id = ?, contract_signed_at = SYSUTCDATETIME() WHERE jobcard_id = ?',
                ['SIGNED', $contractId, $jobcardId]
            );
        }
        if (customer_core_table_exists($conn, 'erp_jobcard_change_history')) {
            customer_core_execute(
                $conn,
                'INSERT INTO dbo.erp_jobcard_change_history (jobcard_id, change_type, previous_status, new_status, change_summary, changed_by_user_id)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$jobcardId, 'JOBCARD_INTAKE_CONTRACT_SIGNED', null, 'SIGNED', 'P1.5 intake contract signed', ERP_PHASE1_PLATFORM_OWNER_ID]
            );
        }
    }

    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_OTP_VERIFIED', M360_CONTRACT_OTP_PURPOSE, null);
    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_CONFIRMED', null, null);
    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_LOCKED', null, null);
    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_SIGNED', 'signed_hash=' . substr($signedHash, 0, 16), null);
    if (function_exists('m360_rw_intake_sync_cartable_from_signed_contract')) {
        m360_rw_intake_sync_cartable_from_signed_contract($conn, $contractRow);
    }
    unset($_SESSION[m360_contract_sig_session_key($contractId)]);
    unset($_SESSION['m360_contract_sig_draft_' . $contractId]);

    return ['ok' => true, 'message' => 'قرارداد با موفقیت امضا و تأیید شد.'];
}

/** @return array{ok:bool,message:string,contract:?array} */
function m360_contract_resolve_token(string $rawToken): array
{
    $rawToken = trim($rawToken);
    if ($rawToken === '' || strlen($rawToken) < 32) {
        return ['ok' => false, 'message' => 'لینک قرارداد معتبر نیست.', 'contract' => null];
    }
    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'خطا در اتصال به سامانه.', 'contract' => null];
    }
    $row = m360_intake_contract_fetch_by_token_hash($conn, m360_intake_contract_hash($rawToken));
    if ($row === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.', 'contract' => null];
    }
    if (!m360_intake_contract_token_valid($row)) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_status = ?, updated_at = SYSUTCDATETIME() WHERE contract_id = ? AND contract_status NOT IN (?, ?)',
            [M360_CONTRACT_STATUS_EXPIRED, (int)$row['contract_id'], M360_CONTRACT_STATUS_SIGNED, M360_CONTRACT_STATUS_OVERRIDDEN]
        );
        return ['ok' => false, 'message' => 'لینک قرارداد منقضی شده است. لطفاً با پذیرش تماس بگیرید.', 'contract' => null];
    }
    return ['ok' => true, 'message' => '', 'contract' => $row];
}
