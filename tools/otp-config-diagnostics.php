<?php
declare(strict_types=1);

/**
 * MOGHARE360 — OTP/SMS config diagnostics (CLI only, masked output).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-config-loader.php';

$report = m360_otp_config_diagnostics_report();

echo "MOGHARE360 OTP Config Diagnostics\n";
echo str_repeat('-', 60) . "\n";
echo 'provider: ' . (string)($report['provider'] ?? 'empty') . "\n";
echo 'mirror_config_found: ' . (!empty($report['sources']['mirror_config_found']) ? 'yes' : 'no') . "\n";
echo 'private_otp_config_found: ' . (!empty($report['sources']['private_otp_config_found']) ? 'yes' : 'no') . "\n";
echo 'primary_private_path: ' . (string)($report['sources']['primary_private_path'] ?? 'private/m360-otp-config.php') . "\n";
echo 'api_key_status: ' . (string)($report['api_key']['status'] ?? 'unknown') . "\n";
echo 'api_key_masked: ' . (string)($report['api_key']['masked'] ?? '') . "\n";
echo 'api_key_length: ' . (string)($report['api_key']['length'] ?? '0') . "\n";
echo 'sender_status: ' . (string)($report['sender']['status'] ?? 'unknown') . "\n";
echo 'sender_masked: ' . (string)($report['sender']['masked'] ?? '') . "\n";
echo 'pattern_code_status: ' . (string)($report['pattern_code']['status'] ?? 'unknown') . "\n";
echo 'pattern_code_masked: ' . (string)($report['pattern_code']['masked'] ?? '') . "\n";
echo 'pattern_variable_status: ' . (string)($report['pattern_variable']['status'] ?? 'unknown') . "\n";
echo 'pattern_variable_value: ' . (string)($report['pattern_variable']['value'] ?? 'OTP') . "\n";
echo 'sms_configured: ' . (!empty($report['sms_configured']) ? 'yes' : 'no') . "\n";

$warnings = is_array($report['warnings'] ?? null) ? $report['warnings'] : [];
if ($warnings === []) {
    echo "warnings: (none)\n";
} else {
    echo "warnings:\n";
    foreach ($warnings as $warning) {
        echo '  - ' . $warning . "\n";
    }
}

echo "payload_preview:\n";
echo json_encode($report['payload_preview'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

echo str_repeat('-', 60) . "\n";
$pass = !empty($report['pass']);
echo 'RESULT: ' . ($pass ? 'PASS' : 'FAIL') . "\n";
if (!$pass) {
    echo 'reason: ' . (string)($report['fail_reason'] ?? 'unknown') . "\n";
}
echo "No secrets are printed. Rotate SMS API key if it was ever exposed.\n";

exit($pass ? 0 : 1);
