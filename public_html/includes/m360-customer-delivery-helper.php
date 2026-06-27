<?php
declare(strict_types=1);

/**
 * MOGHARE360 P7 — Customer delivery OTP + signature confirmation.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-final-invoice-helper.php';

const M360_DEL_CONF_TABLE = 'erp_customer_delivery_confirmations';
const M360_DEL_CONF_SIGNED = 'DELIVERY_SIGNED';
const M360_DEL_OTP_TTL = 120;
const M360_DEL_OTP_RESEND = 60;

function m360_delivery_session_key(int $finalInvoiceId): string
{
    return 'm360_delivery_otp_' . $finalInvoiceId;
}

function m360_delivery_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        m360_otp_session_start();
    }
}

/** @return array{ok:bool,message:string,invoice:?array} */
function m360_delivery_resolve_token(string $rawToken): array
{
    $rawToken = trim($rawToken);
    if ($rawToken === '') {
        return ['ok' => false, 'message' => 'لینک نامعتبر است.', 'invoice' => null];
    }
    $conn = customer_core_db();
    if ($conn === false || !customer_core_table_exists($conn, M360_FI_TABLE)) {
        return ['ok' => false, 'message' => 'سرویس در دسترس نیست.', 'invoice' => null];
    }
    $hash = m360_fi_hash($rawToken);
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_FI_TABLE . ' WHERE delivery_token_hash = ?',
        [$hash]
    );
    if ($rows === []) {
        return ['ok' => false, 'message' => 'لینک منقضی یا نامعتبر است.', 'invoice' => null];
    }
    $inv = $rows[0];
    $exp = strtotime((string)($inv['delivery_token_expires_at'] ?? ''));
    if ($exp > 0 && $exp < time()) {
        return ['ok' => false, 'message' => 'مهلت لینک تحویل به پایان رسیده است.', 'invoice' => null];
    }
    if (strtoupper((string)($inv['invoice_status'] ?? '')) !== M360_FI_FINALIZED) {
        return ['ok' => false, 'message' => 'فاکتور نهایی هنوز آماده نیست.', 'invoice' => null];
    }
    return ['ok' => true, 'message' => '', 'invoice' => $inv];
}

function m360_delivery_is_confirmed($conn, int $jobcardId): bool
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_DEL_CONF_TABLE)) {
        return false;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 confirmation_status FROM dbo." . M360_DEL_CONF_TABLE . " WHERE jobcard_id = ? AND confirmation_status = N'DELIVERY_SIGNED' ORDER BY delivery_confirmation_id DESC",
        [$jobcardId]
    );
    return $rows !== [];
}

/** @return array{ok:bool,message:string} */
function m360_delivery_send_otp(array $invoiceRow): array
{
    m360_delivery_session_start();
    $invoiceId = (int)($invoiceRow['final_invoice_id'] ?? 0);
    $jobcardId = (int)($invoiceRow['jobcard_id'] ?? 0);
    if ($invoiceId < 1) {
        return ['ok' => false, 'message' => 'فاکتور معتبر نیست.'];
    }
    $conn = customer_core_db();
    if ($conn !== false && m360_delivery_is_confirmed($conn, $jobcardId)) {
        return ['ok' => false, 'message' => 'تحویل قبلاً تأیید شده است.'];
    }
    $jc = $conn !== false ? m360_fi_fetch_jobcard($conn, $jobcardId) : null;
    $mobile = trim((string)($jc['customer_mobile'] ?? ''));
    if ($mobile === '') {
        return ['ok' => false, 'message' => 'شماره موبایل مشتری یافت نشد.'];
    }
    $key = m360_delivery_session_key($invoiceId);
    $last = (int)($_SESSION[$key]['last_sent_at'] ?? 0);
    if ($last > 0 && (time() - $last) < M360_DEL_OTP_RESEND) {
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
            'expires_at' => time() + M360_DEL_OTP_TTL,
            'last_sent_at' => time(),
            'verified' => false,
        ];
    } else {
        if (!m360_otp_can_use_dev_code()) {
            return ['ok' => false, 'message' => 'سرویس OTP پیکربندی نشده است.'];
        }
        $code = m360_otp_get_dev_code();
        $_SESSION[$key] = [
            'mobile' => $mobile,
            'hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + M360_DEL_OTP_TTL,
            'last_sent_at' => time(),
            'verified' => false,
        ];
    }
    if ($conn !== false) {
        m360_fi_write_event($conn, $jobcardId, 'CUSTOMER_DELIVERY_OTP_SENT', null, 0, $invoiceId);
        m360_fi_write_history($conn, $jobcardId, 'JOBCARD_DELIVERY_OTP_SENT', null, null, 'Delivery OTP sent', 0);
    }
    if (!m360_otp_sms_configured() && m360_otp_can_use_dev_code()) {
        return ['ok' => true, 'message' => m360_otp_dev_fallback_message(), 'test_mode' => true];
    }
    return ['ok' => true, 'message' => 'کد تأیید ارسال شد.'];
}

function m360_delivery_verify_otp(int $finalInvoiceId, string $otpCode): bool
{
    m360_delivery_session_start();
    $key = m360_delivery_session_key($finalInvoiceId);
    $sess = $_SESSION[$key] ?? null;
    if (!is_array($sess)) {
        return false;
    }
    if ((int)($sess['expires_at'] ?? 0) < time()) {
        return false;
    }
    if (!password_verify($otpCode, (string)($sess['hash'] ?? ''))) {
        return false;
    }
    $_SESSION[$key]['verified'] = true;
    return true;
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_delivery_confirm(
    array $invoiceRow,
    string $rawToken,
    string $otpCode,
    string $signatureData,
    bool $c1,
    bool $c2,
    bool $c3,
    bool $c4
): array {
    if (!$c1 || !$c2 || !$c3 || !$c4) {
        return ['ok' => false, 'message' => 'همه تأییدها الزامی است.'];
    }
    if (trim($signatureData) === '' || strlen($signatureData) < 100) {
        return ['ok' => false, 'message' => 'امضای دیجیتال الزامی است.'];
    }
    $resolved = m360_delivery_resolve_token($rawToken);
    if (!$resolved['ok'] || !is_array($resolved['invoice'])) {
        return ['ok' => false, 'message' => $resolved['message']];
    }
    $invoiceId = (int)$resolved['invoice']['final_invoice_id'];
    $jobcardId = (int)$resolved['invoice']['jobcard_id'];
    if (!m360_delivery_verify_otp($invoiceId, trim($otpCode))) {
        return ['ok' => false, 'message' => 'کد تأیید نامعتبر یا منقضی است.'];
    }
    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'اتصال برقرار نشد.'];
    }
    if (m360_delivery_is_confirmed($conn, $jobcardId)) {
        return ['ok' => false, 'message' => 'تحویل قبلاً تأیید شده است.'];
    }
    $jc = m360_fi_fetch_jobcard($conn, $jobcardId);
    $mobile = trim((string)($jc['customer_mobile'] ?? ''));
    $ip = customer_core_client_ip();
    $ua = customer_core_user_agent();
    $sigHash = hash('sha256', $signatureData);
    $confHash = hash('sha256', $rawToken . $sigHash . $mobile . gmdate('c'));
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_DEL_CONF_TABLE . ' (jobcard_id, final_invoice_id, mobile, confirmation_status, otp_verified, otp_verified_at, signature_hash, confirmation_hash, confirmation_ip, confirmation_user_agent, confirmed_at) VALUES (?, ?, ?, ?, 1, SYSUTCDATETIME(), ?, ?, ?, ?, SYSUTCDATETIME())',
        [$jobcardId, $invoiceId, $mobile, M360_DEL_CONF_SIGNED, $sigHash, $confHash, $ip, $ua]
    );
    $sets = ["customer_delivery_status = N'DELIVERY_SIGNED'", 'customer_delivery_signed_at = SYSUTCDATETIME()'];
    $params = [];
    if (customer_core_column_exists($conn, 'erp_jobcards', 'updated_at')) {
        $sets[] = 'updated_at = SYSUTCDATETIME()';
    }
    $params[] = $jobcardId;
    customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET ' . implode(', ', $sets) . ' WHERE jobcard_id = ?', $params);
    m360_fi_write_history($conn, $jobcardId, 'JOBCARD_DELIVERY_SIGNED', null, M360_DEL_CONF_SIGNED, 'Customer delivery signed', 0);
    m360_fi_write_event($conn, $jobcardId, 'CUSTOMER_DELIVERY_SIGNED', null, 0, $invoiceId, $ip, $ua);
    return ['ok' => true, 'message' => 'تحویل با موفقیت تأیید شد.'];
}
