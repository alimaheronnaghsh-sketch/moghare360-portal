<?php
declare(strict_types=1);

/**
 * MOGHARE360 P4 — Customer estimate approval + OTP.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';

const M360_EST_APPROVAL_OTP_TTL = 120;
const M360_EST_APPROVAL_OTP_RESEND = 60;

function m360_estimate_approval_session_key(int $estimateId): string
{
    return 'm360_estimate_otp_' . $estimateId;
}

function m360_estimate_approval_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        m360_otp_session_start();
    }
}

/** @return array{ok:bool,message:string,estimate:?array} */
function m360_estimate_resolve_token(string $rawToken): array
{
    $rawToken = trim($rawToken);
    if ($rawToken === '') {
        return ['ok' => false, 'message' => 'لینک نامعتبر است.', 'estimate' => null];
    }
    $conn = customer_core_db();
    if ($conn === false || !customer_core_table_exists($conn, M360_ESTIMATE_TABLE)) {
        return ['ok' => false, 'message' => 'سرویس در دسترس نیست.', 'estimate' => null];
    }
    $hash = m360_estimate_hash($rawToken);
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_ESTIMATE_TABLE . ' WHERE secure_token_hash = ?',
        [$hash]
    );
    if ($rows === []) {
        return ['ok' => false, 'message' => 'لینک منقضی یا نامعتبر است.', 'estimate' => null];
    }
    $est = $rows[0];
    $exp = strtotime((string)($est['secure_token_expires_at'] ?? ''));
    if ($exp > 0 && $exp < time()) {
        return ['ok' => false, 'message' => 'مهلت مشاهده برآورد به پایان رسیده است.', 'estimate' => null];
    }
    if (strtoupper((string)($est['estimate_status'] ?? '')) === M360_EST_STATUS_APPROVED) {
        return ['ok' => true, 'message' => 'این برآورد قبلاً تأیید شده است.', 'estimate' => $est];
    }
    return ['ok' => true, 'message' => '', 'estimate' => $est];
}

function m360_estimate_mark_viewed($conn, int $estimateId): void
{
    if (!is_resource($conn) || $estimateId < 1) {
        return;
    }
    $est = m360_estimate_fetch($conn, $estimateId);
    if ($est === null || !empty($est['viewed_at'])) {
        return;
    }
    $jobcardId = (int)($est['jobcard_id'] ?? 0);
    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET viewed_at = SYSUTCDATETIME(), estimate_status = ? WHERE estimate_id = ? AND estimate_status = ?',
        [M360_EST_STATUS_VIEWED, $estimateId, M360_EST_STATUS_SENT]
    );
    m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_CUSTOMER_VIEWED', $estimateId);
}

/** @return array{ok:bool,message:string,test_mode?:bool} */
function m360_estimate_send_otp(array $estimateRow): array
{
    m360_estimate_approval_session_start();
    $estimateId = (int)($estimateRow['estimate_id'] ?? 0);
    $jobcardId = (int)($estimateRow['jobcard_id'] ?? 0);
    if ($estimateId < 1) {
        return ['ok' => false, 'message' => 'برآورد معتبر نیست.'];
    }
    if (strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_APPROVED) {
        return ['ok' => false, 'message' => 'این برآورد قبلاً تأیید شده است.'];
    }

    $conn = customer_core_db();
    $jc = $conn !== false ? m360_estimate_fetch_jobcard($conn, $jobcardId) : null;
    $mobile = trim((string)($jc['customer_mobile'] ?? ''));
    if ($mobile === '') {
        return ['ok' => false, 'message' => 'شماره موبایل مشتری یافت نشد.'];
    }

    $key = m360_estimate_approval_session_key($estimateId);
    $last = (int)($_SESSION[$key]['last_sent_at'] ?? 0);
    if ($last > 0 && (time() - $last) < M360_EST_APPROVAL_OTP_RESEND) {
        return ['ok' => false, 'message' => 'لطفاً چند ثانیه دیگر تلاش کنید.'];
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
            'expires_at' => time() + M360_EST_APPROVAL_OTP_TTL,
            'last_sent_at' => time(),
            'verified' => false,
            'viewed' => false,
        ];
        if ($conn !== false) {
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_CUSTOMER_OTP_SENT', $estimateId);
        }
        return ['ok' => true, 'message' => 'کد تأیید ارسال شد.'];
    }

    if (m360_otp_can_use_dev_code()) {
        $code = m360_otp_get_dev_code();
        $_SESSION[$key] = [
            'mobile' => $mobile,
            'hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + M360_EST_APPROVAL_OTP_TTL,
            'last_sent_at' => time(),
            'verified' => false,
            'viewed' => false,
        ];
        return ['ok' => true, 'message' => m360_otp_dev_fallback_message(), 'test_mode' => true];
    }

    return ['ok' => false, 'message' => M360_OTP_MSG_SMS_INACTIVE];
}

function m360_estimate_verify_otp(int $estimateId, string $mobile, string $code): bool
{
    m360_estimate_approval_session_start();
    $key = m360_estimate_approval_session_key($estimateId);
    $bag = $_SESSION[$key] ?? null;
    if (!is_array($bag) || trim($mobile) !== trim((string)($bag['mobile'] ?? ''))) {
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

function m360_estimate_otp_was_verified(int $estimateId, string $mobile): bool
{
    m360_estimate_approval_session_start();
    $bag = $_SESSION[m360_estimate_approval_session_key($estimateId)] ?? null;
    return is_array($bag) && !empty($bag['verified']) && trim($mobile) === trim((string)($bag['mobile'] ?? ''));
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
function m360_estimate_customer_decision(
    array $estimateRow,
    string $rawToken,
    string $decision,
    bool $c1,
    bool $c2,
    bool $c3,
    string $otpCode,
    string $rejectReason = ''
): array {
    $resolved = m360_estimate_resolve_token($rawToken);
    if (!$resolved['ok'] || !is_array($resolved['estimate'])) {
        return ['ok' => false, 'message' => $resolved['message']];
    }
    $est = $resolved['estimate'];
    $estimateId = (int)($est['estimate_id'] ?? 0);
    $jobcardId = (int)($est['jobcard_id'] ?? 0);
    $status = strtoupper((string)($est['estimate_status'] ?? ''));

    if ($status === M360_EST_STATUS_APPROVED) {
        return ['ok' => false, 'message' => 'این برآورد قبلاً تأیید شده و قابل تغییر نیست.'];
    }
    if (!in_array($status, [M360_EST_STATUS_SENT, M360_EST_STATUS_VIEWED], true)) {
        return ['ok' => false, 'message' => 'وضعیت برآورد برای تأیید مشتری مناسب نیست.'];
    }

    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'سرویس در دسترس نیست.'];
    }
    $jc = m360_estimate_fetch_jobcard($conn, $jobcardId);
    $mobile = trim((string)($jc['customer_mobile'] ?? ''));
    if ($mobile === '') {
        return ['ok' => false, 'message' => 'موبایل مشتری یافت نشد.'];
    }

    $decision = strtolower(trim($decision));
    if ($decision === 'reject') {
        $reason = trim($rejectReason);
        if ($reason === '') {
            return ['ok' => false, 'message' => 'دلیل رد الزامی است.'];
        }
        customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET estimate_status = ?, rejected_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_EST_STATUS_REJECTED, $estimateId]);
        customer_core_execute(
            $conn,
            'INSERT INTO dbo.erp_estimate_approvals (estimate_id, jobcard_id, mobile, approval_status, customer_note, rejected_at, approval_ip, approval_user_agent) VALUES (?, ?, ?, ?, ?, SYSUTCDATETIME(), ?, ?)',
            [$estimateId, $jobcardId, $mobile, M360_EST_STATUS_REJECTED, $reason, m360_estimate_client_ip(), m360_estimate_client_ua()]
        );
        m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_CUSTOMER_REJECTED', $estimateId, $reason);
        return ['ok' => true, 'message' => 'برآورد رد شد.'];
    }

    if (!$c1 || !$c2 || !$c3) {
        return ['ok' => false, 'message' => 'پذیرش همه شرایط الزامی است.'];
    }
    if (!m360_estimate_otp_was_verified($estimateId, $mobile) && !m360_estimate_verify_otp($estimateId, $mobile, trim($otpCode))) {
        return ['ok' => false, 'message' => 'کد تأیید معتبر نیست.'];
    }

    $ip = m360_estimate_client_ip();
    $ua = m360_estimate_client_ua();
    if ($ip === '' || $ua === '') {
        return ['ok' => false, 'message' => 'اطلاعات تأیید ناقص است.'];
    }

    $total = (float)($est['total_amount'] ?? 0);
    $approvalHash = hash('sha256', $estimateId . '|' . $total . '|' . $mobile . '|' . gmdate('c'));

    customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET estimate_status = ?, approved_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_EST_STATUS_APPROVED, $estimateId]);
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_estimate_approvals (estimate_id, jobcard_id, mobile, approval_status, otp_verified, otp_verified_at, approved_total_amount, approval_ip, approval_user_agent, approval_hash, approved_at) VALUES (?, ?, ?, ?, 1, SYSUTCDATETIME(), ?, ?, ?, ?, SYSUTCDATETIME())',
        [$estimateId, $jobcardId, $mobile, M360_EST_STATUS_APPROVED, $total, $ip, $ua, $approvalHash]
    );

    $partsEval = m360_parts_gate_evaluate($conn, $estimateId);
    $finEval = m360_finance_gate_evaluate($conn, $estimateId, $total, (float)($est['advance_required_amount'] ?? m360_finance_calculate_advance($total)));
    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET parts_gate_status = ?, finance_gate_status = ? WHERE estimate_id = ?',
        [$partsEval['parts_gate_status'], $finEval['finance_gate_status'], $estimateId]
    );

    if (customer_core_column_exists($conn, 'erp_jobcards', 'estimate_status')) {
        customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET estimate_status = N\'ESTIMATE_APPROVED\', estimate_approved_at = SYSUTCDATETIME() WHERE jobcard_id = ?', [$jobcardId]);
    }

    m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_CUSTOMER_APPROVED', $estimateId, null, null);
    m360_estimate_jobcard_history($conn, $jobcardId, 'JOBCARD_ESTIMATE_APPROVED', null, M360_EST_STATUS_APPROVED, 'Customer OTP approval', 0);

    return ['ok' => true, 'message' => 'برآورد با موفقیت تأیید شد.'];
}

function m360_estimate_is_customer_approved(array $estimateRow): bool
{
    return strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_APPROVED
        || strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_PARTS_PENDING
        || strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_FIN_PENDING
        || strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_PARTS_CLEARED
        || strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_FIN_CLEARED
        || strtoupper((string)($estimateRow['estimate_status'] ?? '')) === M360_EST_STATUS_APPROVED_WORK;
}
