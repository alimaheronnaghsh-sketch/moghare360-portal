<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Customer mobile OTP (session-backed, no fake pass).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'mirror-layout.php';

const M360_OTP_TTL_SECONDS = 120;
const M360_OTP_MAX_ATTEMPTS = 5;
const M360_OTP_RESEND_SECONDS = 60;

function m360_otp_json_headers(): void
{
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
}

function m360_otp_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function m360_otp_json_ok(string $message, array $data = [], int $status = 200): never
{
    http_response_code($status);
    echo json_encode([
        'ok' => true,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function m360_otp_json_fail(string $message, int $status = 400, array $data = []): never
{
    http_response_code($status);
    echo json_encode([
        'ok' => false,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/** @return string */
function m360_otp_cfg_string(string ...$keys): string
{
    $cfg = mirror_config();
    foreach ($keys as $key) {
        if ($key === '') {
            continue;
        }
        if (isset($cfg[$key]) && is_scalar($cfg[$key])) {
            $val = trim((string)$cfg[$key]);
            if ($val !== '') {
                return $val;
            }
        }
        $define = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', $key) ?? $key);
        if ($define !== '' && defined($define)) {
            $val = trim((string)constant($define));
            if ($val !== '') {
                return $val;
            }
        }
    }
    return '';
}

/** @return array<string, mixed> */
function m360_otp_sms_settings(): array
{
    $cfg = mirror_config();
    $provider = m360_otp_cfg_string('M360_SMS_PROVIDER', 'SMS_PROVIDER', 'SMS_PROVIDER_NAME');
    $apiKey = m360_otp_cfg_string('M360_SMS_API_KEY', 'SMS_API_KEY', 'IPPANEL_API_KEY', 'ippanelApiKey');
    $sender = m360_otp_cfg_string('M360_SMS_SENDER', 'SMS_SENDER', 'ippanelSender', 'IPPANEL_SENDER');
    $patternId = m360_otp_cfg_string('M360_SMS_PATTERN_ID', 'SMS_PATTERN_ID', 'IPPANEL_PATTERN_ID');

    if ($provider === '' && !empty($cfg['SMS_OTP_ENABLED']) && !empty($cfg['SMS_GATEWAY_CONFIGURED'])) {
        $provider = 'ippanel';
    }
    if ($provider === '' && $apiKey !== '' && $sender !== '') {
        $provider = 'ippanel';
    }

    return [
        'provider' => $provider,
        'api_key' => $apiKey,
        'sender' => $sender,
        'pattern_id' => $patternId,
    ];
}

function m360_otp_log_sms_issue(string $context, string $detail): void
{
    error_log('[MOGHARE360 OTP] ' . $context . ': ' . $detail);
}

function m360_otp_ippanel_recipient(string $phone09): string
{
    if (str_starts_with($phone09, '0') && strlen($phone09) === 11) {
        return '+98' . substr($phone09, 1);
    }
    if (str_starts_with($phone09, '98')) {
        return '+' . $phone09;
    }
    return $phone09;
}

function m360_otp_request_host(): string
{
    $host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
    if ($host === '' && PHP_SAPI === 'cli') {
        return 'localhost';
    }
    return (string)(preg_replace('/:\d+$/', '', $host) ?? $host);
}

function m360_otp_is_localhost(): bool
{
    $rawHost = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
    if ($rawHost !== '' && str_contains($rawHost, 'moghareh360.ir')) {
        return false;
    }

    if ($rawHost === '' && PHP_SAPI === 'cli') {
        return true;
    }

    $host = m360_otp_request_host();
    if ($host === '') {
        return false;
    }

    return $host === 'localhost'
        || $host === '127.0.0.1'
        || str_ends_with($host, '.localhost')
        || str_contains($host, 'localhost');
}

function m360_otp_is_localhost_host(): bool
{
    return m360_otp_is_localhost();
}

function m360_otp_can_use_dev_code(): bool
{
    return m360_otp_is_localhost();
}

function m360_otp_get_dev_code(): string
{
    if (!m360_otp_can_use_dev_code()) {
        return '';
    }

    return '123456';
}

function m360_otp_cfg_bool(string $key): bool
{
    $cfg = mirror_config();
    if (!array_key_exists($key, $cfg)) {
        return false;
    }
    $val = $cfg[$key];
    if (is_bool($val)) {
        return $val;
    }
    if (is_int($val) || is_float($val)) {
        return (int)$val !== 0;
    }
    $normalized = strtolower(trim((string)$val));
    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

function m360_otp_test_code_from_config(): ?string
{
    $cfg = mirror_config();
    $raw = '';
    if (isset($cfg['M360_OTP_TEST_CODE']) && is_scalar($cfg['M360_OTP_TEST_CODE'])) {
        $raw = trim((string)$cfg['M360_OTP_TEST_CODE']);
    }
    if ($raw === '') {
        $raw = m360_otp_cfg_string('M360_OTP_TEST_CODE');
    }
    $digits = preg_replace('/\D+/', '', $raw) ?? '';
    if (strlen($digits) !== 6) {
        return null;
    }
    return $digits;
}

function m360_otp_is_local_test_mode(): bool
{
    if (!m360_otp_is_localhost()) {
        return false;
    }

    if (!m360_otp_cfg_bool('M360_OTP_TEST_MODE')) {
        return false;
    }

    return m360_otp_test_code_from_config() !== null;
}

function m360_otp_dev_fallback_message(): string
{
    $code = m360_otp_get_dev_code();
    if ($code === '') {
        return 'کد تست لوکال فعال شد.';
    }

    return 'کد تست لوکال فعال شد. کد: ' . $code;
}

/**
 * @return array{ok:bool,message:string,test_mode?:bool}
 */
function m360_otp_store_pending(string $normalized, string $code, string $successMessage, bool $testMode = false): array
{
    m360_otp_session_start();
    $_SESSION['otp_phone'] = $normalized;
    $_SESSION['otp_hash'] = password_hash($code, PASSWORD_DEFAULT);
    $_SESSION['otp_expires_at'] = time() + M360_OTP_TTL_SECONDS;
    $_SESSION['otp_attempts'] = 0;
    $_SESSION['otp_last_sent_at'] = time();

    $result = ['ok' => true, 'message' => $successMessage];
    if ($testMode) {
        $result['test_mode'] = true;
    }
    return $result;
}

function m360_otp_sms_configured(): bool
{
    $s = m360_otp_sms_settings();
    return $s['provider'] !== '' && $s['api_key'] !== '' && $s['sender'] !== '';
}

function m360_otp_normalize_phone(string $phone): ?string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($digits, '98') && strlen($digits) === 12) {
        $digits = '0' . substr($digits, 2);
    }
    if (preg_match('/^09\d{9}$/', $digits) !== 1) {
        return null;
    }
    return $digits;
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_otp_send_sms(string $phone, string $code): array
{
    if (!m360_otp_sms_configured()) {
        return ['ok' => false, 'message' => 'امکان ارسال پیامک در حال حاضر فعال نیست.'];
    }

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'message' => 'امکان ارسال پیامک در حال حاضر فعال نیست.'];
    }

    $s = m360_otp_sms_settings();
    $message = 'کد تأیید مقاره۳۶۰: ' . $code;

    if ($s['provider'] === 'ippanel') {
        if ($s['pattern_id'] !== '') {
            $payload = [
                'sending_type' => 'pattern',
                'from_number' => $s['sender'],
                'pattern_code' => $s['pattern_id'],
                'params' => [
                    'recipients' => [m360_otp_ippanel_recipient($phone)],
                    'code' => $code,
                ],
            ];
        } else {
            $payload = [
                'sending_type' => 'webservice',
                'from_number' => $s['sender'],
                'params' => [
                    'recipients' => [m360_otp_ippanel_recipient($phone)],
                    'message' => $message,
                ],
            ];
        }

        $ch = curl_init('https://edge.ippanel.com/v1/api/send');
        if ($ch === false) {
            m360_otp_log_sms_issue('curl_init', 'failed');
            return ['ok' => false, 'message' => 'امکان ارسال پیامک در حال حاضر فعال نیست.'];
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: ' . $s['api_key'],
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $status < 200 || $status >= 300) {
            m360_otp_log_sms_issue('ippanel_http', 'status=' . $status . ' err=' . $curlErr . ' body=' . substr((string)$raw, 0, 300));
            return ['ok' => false, 'message' => 'امکان ارسال پیامک در حال حاضر فعال نیست.'];
        }
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded) && isset($decoded['meta']['status']) && (bool)$decoded['meta']['status'] === false) {
            m360_otp_log_sms_issue('ippanel_meta', substr((string)$raw, 0, 300));
            return ['ok' => false, 'message' => 'امکان ارسال پیامک در حال حاضر فعال نیست.'];
        }
        return ['ok' => true, 'message' => 'کد تأیید ارسال شد.'];
    }

    return ['ok' => false, 'message' => 'امکان ارسال پیامک در حال حاضر فعال نیست.'];
}

function m360_otp_clear_pending(): void
{
    m360_otp_session_start();
    unset(
        $_SESSION['otp_phone'],
        $_SESSION['otp_hash'],
        $_SESSION['otp_expires_at'],
        $_SESSION['otp_attempts'],
        $_SESSION['otp_last_sent_at']
    );
}

function m360_otp_reset_verified(): void
{
    m360_otp_session_start();
    unset(
        $_SESSION['otp_verified_phone'],
        $_SESSION['otp_verified_at'],
        $_SESSION['otp_verified_token']
    );
}

function m360_otp_reset_verified_if_phone_changed(string $phone): void
{
    m360_otp_session_start();
    $verified = (string)($_SESSION['otp_verified_phone'] ?? '');
    $normalized = m360_otp_normalize_phone($phone);
    if ($verified !== '' && $normalized !== null && $verified !== $normalized) {
        m360_otp_reset_verified();
    }
}

function m360_otp_is_verified(string $phone): bool
{
    m360_otp_session_start();
    $normalized = m360_otp_normalize_phone($phone);
    if ($normalized === null) {
        return false;
    }
    $verifiedPhone = (string)($_SESSION['otp_verified_phone'] ?? '');
    $verifiedAt = (int)($_SESSION['otp_verified_at'] ?? 0);
    $token = (string)($_SESSION['otp_verified_token'] ?? '');
    if ($verifiedPhone === '' || $token === '' || $verifiedAt <= 0) {
        return false;
    }
    if ($verifiedPhone !== $normalized) {
        return false;
    }
    if (time() - $verifiedAt > 3600) {
        return false;
    }
    return true;
}

function m360_otp_verified_token(): string
{
    m360_otp_session_start();
    return (string)($_SESSION['otp_verified_token'] ?? '');
}

/**
 * @return array{ok:bool,message:string,test_mode?:bool}
 */
function m360_otp_send(string $phone): array
{
    m360_otp_session_start();
    $normalized = m360_otp_normalize_phone($phone);
    if ($normalized === null) {
        return ['ok' => false, 'message' => 'شماره موبایل معتبر نیست. فرمت صحیح: 09xxxxxxxxx'];
    }

    m360_otp_reset_verified_if_phone_changed($normalized);

    $lastSent = (int)($_SESSION['otp_last_sent_at'] ?? 0);
    if ($lastSent > 0 && (time() - $lastSent) < M360_OTP_RESEND_SECONDS) {
        $wait = M360_OTP_RESEND_SECONDS - (time() - $lastSent);
        return ['ok' => false, 'message' => 'لطفاً ' . $wait . ' ثانیه دیگر برای ارسال مجدد صبر کنید.'];
    }

    if (m360_otp_sms_configured()) {
        $code = (string)random_int(100000, 999999);
        $sms = m360_otp_send_sms($normalized, $code);
        if (!$sms['ok']) {
            return $sms;
        }

        return m360_otp_store_pending($normalized, $code, 'کد تأیید به شماره موبایل شما ارسال شد.');
    }

    if (m360_otp_can_use_dev_code()) {
        $devCode = m360_otp_get_dev_code();
        if ($devCode === '') {
            return ['ok' => false, 'message' => 'امکان ارسال پیامک در حال حاضر فعال نیست.'];
        }

        return m360_otp_store_pending($normalized, $devCode, m360_otp_dev_fallback_message(), true);
    }

    return ['ok' => false, 'message' => 'امکان ارسال پیامک در حال حاضر فعال نیست.'];
}

/**
 * @return array{ok:bool,message:string,token?:string}
 */
function m360_otp_verify(string $phone, string $otp): array
{
    m360_otp_session_start();
    $normalized = m360_otp_normalize_phone($phone);
    if ($normalized === null) {
        return ['ok' => false, 'message' => 'شماره موبایل معتبر نیست.'];
    }

    $otpDigits = preg_replace('/\D+/', '', $otp) ?? '';
    if (strlen($otpDigits) !== 6) {
        return ['ok' => false, 'message' => 'کد تأیید باید ۶ رقم باشد.'];
    }

    $sessionPhone = (string)($_SESSION['otp_phone'] ?? '');
    $hash = (string)($_SESSION['otp_hash'] ?? '');
    $expires = (int)($_SESSION['otp_expires_at'] ?? 0);
    $attempts = (int)($_SESSION['otp_attempts'] ?? 0);

    if ($sessionPhone === '' || $hash === '' || $expires <= 0) {
        return ['ok' => false, 'message' => 'ابتدا کد تأیید را درخواست کنید.'];
    }

    if ($sessionPhone !== $normalized) {
        return ['ok' => false, 'message' => 'شماره موبایل با کد ارسال‌شده مطابقت ندارد.'];
    }

    if (time() > $expires) {
        m360_otp_clear_pending();
        return ['ok' => false, 'message' => 'کد تأیید منقضی شده است. لطفاً کد جدید درخواست کنید.'];
    }

    if ($attempts >= M360_OTP_MAX_ATTEMPTS) {
        m360_otp_clear_pending();
        return ['ok' => false, 'message' => 'تعداد تلاش‌های مجاز تمام شد. لطفاً کد جدید درخواست کنید.'];
    }

    $_SESSION['otp_attempts'] = $attempts + 1;

    if (!password_verify($otpDigits, $hash)) {
        $remaining = M360_OTP_MAX_ATTEMPTS - (int)$_SESSION['otp_attempts'];
        if ($remaining <= 0) {
            m360_otp_clear_pending();
            return ['ok' => false, 'message' => 'تعداد تلاش‌های مجاز تمام شد. لطفاً کد جدید درخواست کنید.'];
        }
        return ['ok' => false, 'message' => 'کد تأیید نادرست است. ' . $remaining . ' تلاش باقی مانده.'];
    }

    m360_otp_clear_pending();
    $token = bin2hex(random_bytes(16));
    $_SESSION['otp_verified_phone'] = $normalized;
    $_SESSION['otp_verified_at'] = time();
    $_SESSION['otp_verified_token'] = $token;

    return ['ok' => true, 'message' => 'شماره موبایل تأیید شد.', 'token' => $token];
}

function m360_otp_require_verified_mobile(string $phone): void
{
    if (!m360_otp_is_verified($phone)) {
        m360_otp_json_fail('شماره موبایل تأیید نشده است.', 403);
    }
}
