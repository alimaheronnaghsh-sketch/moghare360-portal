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
require_once $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';

$configPath = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'mirror-config.php';
$privateOtpPath = $root . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'm360-otp-config.php';
$configFound = is_file($configPath);
$privateOtpFound = is_file($privateOtpPath);
$settings = m360_otp_sms_settings();

echo "MOGHARE360 OTP Config Diagnostics\n";
echo str_repeat('-', 40) . "\n";
echo 'mirror_config_found: ' . ($configFound ? 'yes' : 'no') . "\n";
echo 'private_otp_config_found: ' . ($privateOtpFound ? 'yes' : 'no') . "\n";
echo 'config_path: ' . ($configFound ? 'public_html/mirror-config.php' : 'missing') . "\n";
echo 'private_otp_path: ' . ($privateOtpFound ? 'private/m360-otp-config.php' : 'missing') . "\n";
echo 'provider: ' . (($settings['provider'] ?? '') !== '' ? (string)$settings['provider'] : 'empty') . "\n";
echo 'api_key_present: ' . (($settings['api_key'] ?? '') !== '' ? 'yes' : 'no') . "\n";
echo 'api_key_length: ' . strlen((string)($settings['api_key'] ?? '')) . "\n";
echo 'sender_present: ' . (($settings['sender'] ?? '') !== '' ? 'yes' : 'no') . "\n";
echo 'pattern_present: ' . (($settings['pattern_id'] ?? '') !== '' ? 'yes' : 'no') . "\n";
echo 'sms_configured: ' . (m360_otp_sms_configured() ? 'yes' : 'no') . "\n";
echo 'localhost_dev_enabled: ' . (m360_otp_can_use_dev_code() ? 'yes' : 'no') . "\n";
echo str_repeat('-', 40) . "\n";
echo "No secrets are printed. Rotate SMS API key if it was ever exposed.\n";

exit(0);
