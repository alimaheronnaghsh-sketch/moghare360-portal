<?php
declare(strict_types=1);

/**
 * MOGHARE360 — OTP provider config loader (private file + env + mirror-config).
 * Never commits or prints real secrets.
 */

function m360_otp_config_repo_root(): string
{
    return dirname(__DIR__, 2);
}

/**
 * @return list<string>
 */
function m360_otp_config_placeholder_markers(): array
{
    return [
        'your_real_ippanel_api_key',
        'your_approved_sender_number',
        'your_real_pattern_code',
        'my-api',
        'my-pattern',
        'changeme',
        'replace_me',
        'placeholder',
        'xxx',
        'todo',
    ];
}

function m360_otp_is_placeholder_value(string $value): bool
{
    $v = strtolower(trim($value));
    if ($v === '') {
        return true;
    }
    if (str_starts_with($v, 'your_')) {
        return true;
    }
    foreach (m360_otp_config_placeholder_markers() as $marker) {
        if ($v === $marker || str_contains($v, $marker)) {
            return true;
        }
    }

    return false;
}

/**
 * @return array<string, string>
 */
function m360_otp_config_env_map(): array
{
    return [
        'M360_OTP_USE_FAKE' => 'useFakeOtp',
        'M360_OTP_EXPIRE_MINUTES' => 'otpExpireMinutes',
        'M360_IPPANEL_API_KEY' => 'ippanelApiKey',
        'M360_IPPANEL_SENDER' => 'ippanelSender',
        'M360_IPPANEL_PATTERN_CODE' => 'ippanelPatternCode',
        'M360_IPPANEL_OTP_VARIABLE_NAME' => 'ippanelOtpVariableName',
        'M360_OTP_LINE' => 'otpLine',
        'M360_RECEPTION_LINE' => 'receptionLine',
        'M360_PARTS_APPROVAL_LINE' => 'partsApprovalLine',
        'M360_SURVEY_LINE' => 'surveyLine',
        'M360_SMS_PROVIDER' => 'M360_SMS_PROVIDER',
        'M360_SMS_API_KEY' => 'M360_SMS_API_KEY',
        'M360_SMS_SENDER' => 'M360_SMS_SENDER',
        'M360_SMS_PATTERN_CODE' => 'M360_SMS_PATTERN_CODE',
        'M360_SMS_PATTERN_VARIABLE' => 'M360_SMS_PATTERN_VARIABLE',
        'IPPANEL_API_KEY' => 'IPPANEL_API_KEY',
        'IPPANEL_SENDER' => 'IPPANEL_SENDER',
        'IPPANEL_PATTERN_CODE' => 'IPPANEL_PATTERN_CODE',
        'IPPANEL_OTP_VARIABLE' => 'IPPANEL_OTP_VARIABLE',
        'M360_SMS_PATTERN_ID' => 'M360_SMS_PATTERN_ID',
    ];
}

/**
 * @param array<string, mixed> $base
 * @return array<string, mixed>
 */
function m360_otp_config_apply_env(array $base): array
{
    foreach (m360_otp_config_env_map() as $envKey => $configKey) {
        $raw = getenv($envKey);
        if ($raw === false) {
            continue;
        }
        $val = trim((string)$raw);
        if ($val === '') {
            continue;
        }
        if ($configKey === 'useFakeOtp') {
            $base[$configKey] = in_array(strtolower($val), ['1', 'true', 'yes', 'on'], true);
            continue;
        }
        if ($configKey === 'otpExpireMinutes') {
            $base[$configKey] = max(1, (int)$val);
            continue;
        }
        $base[$configKey] = $val;
    }

    return $base;
}

/**
 * @return array<string, mixed>
 */
function m360_otp_config_load_file(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $loaded = require $path;

    return is_array($loaded) ? $loaded : [];
}

function m360_otp_config_mask_secret(string $value, int $showTail = 4): string
{
    $v = trim($value);
    if ($v === '') {
        return '(empty)';
    }
    if (m360_otp_is_placeholder_value($v)) {
        return '(placeholder)';
    }
    $len = strlen($v);
    if ($len <= $showTail + 2) {
        return str_repeat('*', $len);
    }

    return str_repeat('*', max(8, $len - $showTail)) . substr($v, -$showTail);
}

/**
 * @return list<string>
 */
function m360_otp_config_collect_warnings(array $config): array
{
    $warnings = [];

    if (array_key_exists('M360_SMS_PATTERN_Code', $config)) {
        $warnings[] = 'Wrong-case key M360_SMS_PATTERN_Code detected; use M360_SMS_PATTERN_CODE.';
    }

    $hasPatternCode = m360_otp_config_has_pattern_code_key($config);
    if (array_key_exists('IPPANEL_PATTERN_ID', $config) && !$hasPatternCode) {
        $warnings[] = 'IPPANEL_PATTERN_ID is unsupported as sole pattern key; use IPPANEL_PATTERN_CODE or M360_SMS_PATTERN_CODE.';
    }

    if (array_key_exists('M360_SMS_PATTERN_ID', $config) && !$hasPatternCode) {
        $warnings[] = 'M360_SMS_PATTERN_ID is legacy; prefer M360_SMS_PATTERN_CODE.';
    }

    return $warnings;
}

function m360_otp_config_has_pattern_code_key(array $config): bool
{
    foreach ([
        'M360_SMS_PATTERN_CODE',
        'M360_SMS_PATTERN_Code',
        'M360_IPPANEL_PATTERN_CODE',
        'IPPANEL_PATTERN_CODE',
        'ippanelPatternCode',
    ] as $key) {
        if (!array_key_exists($key, $config)) {
            continue;
        }
        $val = trim((string)$config[$key]);
        if ($val !== '') {
            return true;
        }
    }

    return false;
}

/**
 * @return array<string, mixed>
 */
function m360_otp_config_normalize(array $config): array
{
    if (array_key_exists('M360_SMS_PATTERN_Code', $config) && !array_key_exists('M360_SMS_PATTERN_CODE', $config)) {
        $config['M360_SMS_PATTERN_CODE'] = $config['M360_SMS_PATTERN_Code'];
    }

    if (!array_key_exists('M360_SMS_PATTERN_VARIABLE', $config)) {
        foreach (['IPPANEL_OTP_VARIABLE', 'IPPANEL_OTP_VARIABLE_NAME', 'M360_IPPANEL_OTP_VARIABLE_NAME', 'ippanelOtpVariableName'] as $alias) {
            if (isset($config[$alias]) && trim((string)$config[$alias]) !== '') {
                $config['M360_SMS_PATTERN_VARIABLE'] = trim((string)$config[$alias]);
                break;
            }
        }
    }

    if (!array_key_exists('M360_SMS_API_KEY', $config)) {
        foreach (['M360_IPPANEL_API_KEY', 'IPPANEL_API_KEY', 'ippanelApiKey'] as $alias) {
            if (isset($config[$alias]) && trim((string)$config[$alias]) !== '') {
                $config['M360_SMS_API_KEY'] = trim((string)$config[$alias]);
                break;
            }
        }
    }

    if (!array_key_exists('M360_SMS_SENDER', $config)) {
        foreach (['M360_IPPANEL_SENDER', 'IPPANEL_SENDER', 'ippanelSender'] as $alias) {
            if (isset($config[$alias]) && trim((string)$config[$alias]) !== '') {
                $config['M360_SMS_SENDER'] = trim((string)$config[$alias]);
                break;
            }
        }
    }

    if (!array_key_exists('M360_SMS_PATTERN_CODE', $config)) {
        foreach (['M360_IPPANEL_PATTERN_CODE', 'IPPANEL_PATTERN_CODE', 'ippanelPatternCode'] as $alias) {
            if (isset($config[$alias]) && trim((string)$config[$alias]) !== '') {
                $config['M360_SMS_PATTERN_CODE'] = trim((string)$config[$alias]);
                break;
            }
        }
    }

    return $config;
}

/**
 * @return array<string, mixed>
 */
function m360_otp_config_merged(): array
{
    static $merged = null;
    if ($merged !== null) {
        return $merged;
    }

    $root = m360_otp_config_repo_root();
    $public = dirname(__DIR__);

    $config = [];

    $mirror = $public . DIRECTORY_SEPARATOR . 'mirror-config.php';
    if (is_file($mirror)) {
        $config = array_merge($config, m360_otp_config_load_file($mirror));
    }

    $private = $root . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'm360-otp-config.php';
    if (is_file($private)) {
        $config = array_merge($config, m360_otp_config_load_file($private));
    }

    $config = m360_otp_config_apply_env($config);
    $config = m360_otp_config_normalize($config);
    $merged = $config;

    return $merged;
}

/**
 * Example config for docs/tests only — not used in production runtime merge.
 *
 * @return array<string, mixed>
 */
function m360_otp_config_example(): array
{
    $root = m360_otp_config_repo_root();
    $example = $root . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'm360-otp-config.example.php';
    if (!is_file($example)) {
        return [];
    }

    return m360_otp_config_load_file($example);
}

/**
 * @param list<string> $keys
 */
function m360_otp_config_bool(array $config, string ...$keys): bool
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $config)) {
            continue;
        }
        $val = $config[$key];
        if (is_bool($val)) {
            return $val;
        }
        if (is_int($val) || is_float($val)) {
            return (int)$val !== 0;
        }
        $normalized = strtolower(trim((string)$val));
        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }
    }

    return false;
}

/**
 * @param list<string> $keys
 */
function m360_otp_config_int(array $config, int $default, string ...$keys): int
{
    foreach ($keys as $key) {
        if (!isset($config[$key]) || !is_scalar($config[$key])) {
            continue;
        }
        $n = (int)$config[$key];
        if ($n > 0) {
            return $n;
        }
    }

    return $default;
}

/**
 * CLI diagnostics report (masked secrets).
 *
 * @return array<string, mixed>
 */
function m360_otp_config_diagnostics_report(): array
{
    $root = m360_otp_config_repo_root();
    $public = dirname(__DIR__);
    $mirrorPath = $public . DIRECTORY_SEPARATOR . 'mirror-config.php';
    $privatePath = $root . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'm360-otp-config.php';

    $rawMirror = is_file($mirrorPath) ? m360_otp_config_load_file($mirrorPath) : [];
    $rawPrivate = is_file($privatePath) ? m360_otp_config_load_file($privatePath) : [];

    $warnings = array_values(array_unique(array_merge(
        m360_otp_config_collect_warnings($rawMirror),
        m360_otp_config_collect_warnings($rawPrivate)
    )));

    if (!function_exists('m360_otp_sms_settings')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
    }

    $settings = m360_otp_sms_settings();
    $provider = (string)($settings['provider'] ?? '');
    $apiKey = (string)($settings['api_key'] ?? '');
    $sender = (string)($settings['sender'] ?? '');
    $pattern = (string)($settings['pattern_id'] ?? '');
    $otpVar = (string)($settings['otp_variable'] ?? 'OTP');

    $apiStatus = $apiKey === '' ? 'missing' : (m360_otp_is_placeholder_value($apiKey) ? 'placeholder' : 'present');
    $senderStatus = $sender === '' ? 'missing' : (m360_otp_is_placeholder_value($sender) ? 'placeholder' : 'present');
    $patternStatus = $pattern === '' ? 'missing' : (m360_otp_is_placeholder_value($pattern) ? 'placeholder' : 'present');
    $varStatus = $otpVar === '' ? 'defaulted' : ($otpVar === 'OTP' ? 'present' : 'configured');

    if (str_starts_with($otpVar, '%') || str_contains($otpVar, '%OTP%')) {
        $warnings[] = 'Pattern variable must be OTP (without % wrappers) in API params.';
    }

    $payloadPreview = m360_otp_ippanel_pattern_payload('09120000000', '******', $settings);
    if (isset($payloadPreview['params']) && is_array($payloadPreview['params'])) {
        foreach (array_keys($payloadPreview['params']) as $paramKey) {
            if (str_starts_with((string)$paramKey, '%') || (string)$paramKey === '%OTP%') {
                $warnings[] = 'Pattern params must not use %OTP% as key.';
            }
        }
    }

    $pass = m360_otp_sms_configured() && $warnings === [];
    $failReason = '';
    if (!$pass) {
        if ($apiStatus !== 'present') {
            $failReason = 'API key missing or placeholder.';
        } elseif ($senderStatus !== 'present') {
            $failReason = 'Sender missing or placeholder.';
        } elseif ($patternStatus !== 'present') {
            $failReason = 'Pattern code missing or placeholder.';
        } elseif ($provider !== 'ippanel') {
            $failReason = 'Provider is not ippanel.';
        } elseif ($warnings !== []) {
            $failReason = implode(' ', $warnings);
        } else {
            $failReason = 'SMS not configured.';
        }
    }

    return [
        'provider' => $provider !== '' ? $provider : 'empty',
        'warnings' => $warnings,
        'sources' => [
            'mirror_config_found' => is_file($mirrorPath),
            'private_otp_config_found' => is_file($privatePath),
            'primary_private_path' => 'private/m360-otp-config.php',
        ],
        'api_key' => [
            'status' => $apiStatus,
            'masked' => m360_otp_config_mask_secret($apiKey),
            'length' => strlen($apiKey),
        ],
        'sender' => [
            'status' => $senderStatus,
            'masked' => m360_otp_config_mask_secret($sender),
        ],
        'pattern_code' => [
            'status' => $patternStatus,
            'masked' => m360_otp_config_mask_secret($pattern),
        ],
        'pattern_variable' => [
            'status' => $varStatus,
            'value' => $otpVar,
        ],
        'payload_preview' => $payloadPreview,
        'sms_configured' => m360_otp_sms_configured(),
        'pass' => $pass,
        'fail_reason' => $failReason,
    ];
}
