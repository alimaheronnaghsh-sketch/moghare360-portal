<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Customer mobile OTP (session-backed, no fake pass).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-config-loader.php';

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

const M360_OTP_MAX_ATTEMPTS = 5;
const M360_OTP_RESEND_SECONDS = 60;
const M360_OTP_MSG_SMS_INACTIVE = 'امکان ارسال پیامک در حال حاضر فعال نیست.';
const M360_OTP_MSG_SMS_FAILED = 'ارسال کد تأیید انجام نشد. لطفاً دوباره تلاش کنید.';
const M360_OTP_MSG_SMS_SENT = 'کد تأیید برای شما ارسال شد.';

/** @return array<string, mixed> */
function m360_otp_load_config(): array
{
    return m360_otp_config_merged();
}

function m360_otp_ttl_seconds(): int
{
    $cfg = m360_otp_load_config();
    $minutes = m360_otp_config_int($cfg, 5, 'otpExpireMinutes', 'M360_OTP_EXPIRE_MINUTES');
    if ($minutes < 1) {
        $minutes = 5;
    }
    if ($minutes > 30) {
        $minutes = 30;
    }

    return $minutes * 60;
}

function m360_otp_is_production_host(): bool
{
    $host = m360_otp_request_host();
    if ($host === '') {
        return false;
    }

    return str_contains($host, 'moghareh360.ir');
}

function m360_otp_cfg_value(array $keys, string $default = ''): string
{
    $cfg = m360_otp_load_config();
    foreach ($keys as $key) {
        if (!is_string($key) || $key === '') {
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

    return $default;
}

/** @return string */
function m360_otp_cfg_string(string ...$keys): string
{
    return m360_otp_cfg_value($keys);
}

function m360_otp_normalize_provider(string $provider): string
{
    $normalized = strtolower(trim($provider));
    if ($normalized === '') {
        return '';
    }
    if (str_contains($normalized, 'ippanel') || str_contains($normalized, 'ip_panel')) {
        return 'ippanel';
    }

    return $normalized;
}

/** @return array<string, mixed> */
function m360_otp_sms_settings(): array
{
    $cfg = m360_otp_load_config();
    $provider = m360_otp_normalize_provider(m360_otp_cfg_value([
        'M360_SMS_PROVIDER',
        'SMS_PROVIDER',
        'SMS_PROVIDER_NAME',
    ]));
    $apiKey = m360_otp_cfg_value([
        'M360_IPPANEL_API_KEY',
        'M360_SMS_API_KEY',
        'SMS_API_KEY',
        'IPPANEL_API_KEY',
        'ippanelApiKey',
    ]);
    $sender = m360_otp_cfg_value([
        'M360_IPPANEL_SENDER',
        'M360_SMS_SENDER',
        'SMS_SENDER',
        'IPPANEL_SENDER',
        'ippanelSender',
    ]);
    $patternId = m360_otp_cfg_value([
        'ippanelPatternCode',
        'M360_IPPANEL_PATTERN_CODE',
        'IPPANEL_PATTERN_CODE',
        'M360_SMS_PATTERN_ID',
        'SMS_PATTERN_ID',
        'IPPANEL_PATTERN_ID',
        'ippanelPatternId',
    ]);
    $otpVariable = m360_otp_ippanel_otp_variable_name();

    if ($provider === '' && !empty($cfg['SMS_OTP_ENABLED']) && !empty($cfg['SMS_GATEWAY_CONFIGURED'])) {
        $provider = 'ippanel';
    }
    if ($provider === '' && $apiKey !== '' && !m360_otp_is_placeholder_value($apiKey)) {
        $provider = 'ippanel';
    }

    return [
        'provider' => $provider,
        'api_key' => $apiKey,
        'sender' => $sender,
        'pattern_id' => $patternId,
        'otp_variable' => $otpVariable,
    ];
}

function m360_otp_ippanel_otp_variable_name(): string
{
    $name = m360_otp_cfg_value([
        'ippanelOtpVariableName',
        'M360_IPPANEL_OTP_VARIABLE_NAME',
        'IPPANEL_OTP_VARIABLE_NAME',
    ], 'OTP');
    $name = trim($name);
    if ($name === '') {
        return 'OTP';
    }
    $name = trim($name, '%');
    if ($name === '') {
        return 'OTP';
    }

    return $name;
}

function m360_otp_log_sms_not_configured(): void
{
    $s = m360_otp_sms_settings();
    m360_otp_log_sms_issue(
        'sms_not_configured',
        sprintf(
            'provider_present=%s api_key_present=%s api_key_length=%d sender_present=%s pattern_present=%s provider=%s',
            $s['provider'] !== '' ? 'yes' : 'no',
            $s['api_key'] !== '' ? 'yes' : 'no',
            strlen($s['api_key']),
            $s['sender'] !== '' ? 'yes' : 'no',
            $s['pattern_id'] !== '' ? 'yes' : 'no',
            $s['provider'] !== '' ? $s['provider'] : 'empty'
        )
    );
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
    if (m360_otp_is_production_host()) {
        return false;
    }
    if (!m360_otp_is_localhost()) {
        return false;
    }

    $cfg = m360_otp_load_config();
    if (m360_otp_config_bool($cfg, 'useFakeOtp', 'M360_OTP_USE_FAKE')) {
        return true;
    }

    return m360_otp_is_local_test_mode();
}

function m360_otp_get_dev_code(): string
{
    if (!m360_otp_can_use_dev_code()) {
        return '';
    }

    $fromConfig = m360_otp_test_code_from_config();
    if ($fromConfig !== null) {
        return $fromConfig;
    }

    return '123456';
}

function m360_otp_should_display_dev_code(): bool
{
    return m360_otp_can_use_dev_code() && m360_otp_is_localhost() && !m360_otp_is_production_host();
}

function m360_otp_cfg_bool(string $key): bool
{
    $cfg = m360_otp_load_config();
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
    $cfg = m360_otp_load_config();
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
    if (!m360_otp_should_display_dev_code()) {
        return 'کد تست لوکال فعال شد.';
    }

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
    $_SESSION['otp_expires_at'] = time() + m360_otp_ttl_seconds();
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
    if ($s['provider'] !== 'ippanel') {
        if ($s['provider'] === '') {
            m360_otp_log_sms_not_configured();
            return false;
        }

        $ok = $s['api_key'] !== ''
            && $s['sender'] !== ''
            && !m360_otp_is_placeholder_value((string)$s['api_key'])
            && !m360_otp_is_placeholder_value((string)$s['sender']);
        if (!$ok) {
            m360_otp_log_sms_not_configured();
        }
        return $ok;
    }

    $ok = $s['api_key'] !== ''
        && $s['sender'] !== ''
        && $s['pattern_id'] !== ''
        && !m360_otp_is_placeholder_value((string)$s['api_key'])
        && !m360_otp_is_placeholder_value((string)$s['sender'])
        && !m360_otp_is_placeholder_value((string)$s['pattern_id']);
    if (!$ok) {
        m360_otp_log_sms_not_configured();
    }

    return $ok;
}

function m360_otp_ippanel_auth_header(string $apiKey): string
{
    $key = trim($apiKey);
    if ($key === '') {
        return '';
    }
    if (stripos($key, 'accesskey') === 0 || stripos($key, 'bearer ') === 0) {
        return $key;
    }

    return 'AccessKey ' . $key;
}

function m360_otp_ippanel_from_number(string $sender): string
{
    $sender = trim($sender);
    if ($sender === '') {
        return '';
    }
    if (str_starts_with($sender, '+')) {
        return $sender;
    }
    if (preg_match('/^09\d{9}$/', $sender) === 1) {
        return m360_otp_ippanel_recipient($sender);
    }

    return $sender;
}

/**
 * @return array<string, mixed>
 */
function m360_otp_ippanel_pattern_payload(string $phone, string $code, array $settings): array
{
    $varName = trim((string)($settings['otp_variable'] ?? 'OTP'));
    if ($varName === '' || str_starts_with($varName, '%')) {
        $varName = 'OTP';
    }
    $varName = trim($varName, '%');

    return [
        'sending_type' => 'pattern',
        'from_number' => m360_otp_ippanel_from_number((string)$settings['sender']),
        'code' => (string)$settings['pattern_id'],
        'recipients' => [m360_otp_ippanel_recipient($phone)],
        'params' => [
            $varName => $code,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function m360_otp_ippanel_webservice_payload(string $phone, string $message, array $settings): array
{
    return [
        'sending_type' => 'webservice',
        'from_number' => m360_otp_ippanel_from_number((string)$settings['sender']),
        'params' => [
            'recipients' => [m360_otp_ippanel_recipient($phone)],
            'message' => $message,
        ],
    ];
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_otp_ippanel_send(array $payload, string $apiKey): array
{
    if (!function_exists('curl_init')) {
        m360_otp_log_sms_issue('curl_missing', 'curl_init unavailable');
        return ['ok' => false, 'message' => M360_OTP_MSG_SMS_FAILED];
    }

    $ch = curl_init('https://edge.ippanel.com/v1/api/send');
    if ($ch === false) {
        m360_otp_log_sms_issue('curl_init', 'failed');
        return ['ok' => false, 'message' => M360_OTP_MSG_SMS_FAILED];
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: ' . m360_otp_ippanel_auth_header($apiKey),
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $status < 200 || $status >= 300) {
        m360_otp_log_sms_issue('ippanel_http', 'status=' . $status . ' err=' . $curlErr . ' body=' . substr((string)$raw, 0, 300));
        return ['ok' => false, 'message' => M360_OTP_MSG_SMS_FAILED];
    }

    $decoded = json_decode((string)$raw, true);
    if (is_array($decoded) && isset($decoded['meta']['status']) && (bool)$decoded['meta']['status'] === false) {
        m360_otp_log_sms_issue('ippanel_meta', 'status=' . $status . ' body=' . substr((string)$raw, 0, 300));
        return ['ok' => false, 'message' => M360_OTP_MSG_SMS_FAILED];
    }

    return ['ok' => true, 'message' => M360_OTP_MSG_SMS_SENT];
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
        return ['ok' => false, 'message' => M360_OTP_MSG_SMS_INACTIVE];
    }

    $s = m360_otp_sms_settings();
    if ($s['provider'] !== 'ippanel') {
        m360_otp_log_sms_issue('unsupported_provider', 'provider=' . $s['provider']);
        return ['ok' => false, 'message' => M360_OTP_MSG_SMS_FAILED];
    }

    if ($s['pattern_id'] !== '') {
        $payload = m360_otp_ippanel_pattern_payload($phone, $code, $s);
    } else {
        $payload = m360_otp_ippanel_webservice_payload($phone, 'کد تأیید مقاره۳۶۰: ' . $code, $s);
    }

    return m360_otp_ippanel_send($payload, (string)$s['api_key']);
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

        return m360_otp_store_pending($normalized, $code, M360_OTP_MSG_SMS_SENT);
    }

    if (m360_otp_can_use_dev_code()) {
        $devCode = m360_otp_get_dev_code();
        if ($devCode === '') {
            return ['ok' => false, 'message' => M360_OTP_MSG_SMS_INACTIVE];
        }

        return m360_otp_store_pending($normalized, $devCode, m360_otp_dev_fallback_message(), true);
    }

    return ['ok' => false, 'message' => M360_OTP_MSG_SMS_INACTIVE];
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
