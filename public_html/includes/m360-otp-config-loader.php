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
