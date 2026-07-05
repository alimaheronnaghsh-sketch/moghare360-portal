<?php
declare(strict_types=1);

/**
 * PR-01 — Canonical OTP diagnostic (CLI, masked, no secrets/OTP codes).
 *
 * Usage:
 *   php tools/diagnose-pr-01-otp-canonical.php --mobile=09128166648
 *   php tools/diagnose-pr-01-otp-canonical.php --mobile=09128166648 --probe-send
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';

$mobile = '09128166648';
$probeSend = false;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--mobile=')) {
        $mobile = trim(substr($arg, 9));
    }
    if ($arg === '--probe-send') {
        $probeSend = true;
    }
}

$sendEndpoint = 'api/customer/send-otp.php';
$verifyEndpoint = 'api/customer/verify-otp.php';
$configReport = m360_otp_config_diagnostics_report();
$smsSettings = m360_otp_sms_settings();
$tokenCheck = m360_otp_ippanel_check_token();

echo "MOGHARE360 PR-01 OTP Canonical Diagnostic\n";
echo str_repeat('-', 60) . "\n";
echo 'endpoint_send: ' . $sendEndpoint . "\n";
echo 'endpoint_verify: ' . $verifyEndpoint . "\n";
echo 'config_loaded: ' . (!empty($configReport['sources']['private_otp_config_found']) ? 'yes' : 'no') . "\n";
echo 'config_source_labels: ' . json_encode($configReport['sources']['loaded_private_labels'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
echo 'htdocs_private_found: ' . (!empty($configReport['sources']['htdocs_private_found']) ? 'yes' : 'no') . "\n";
echo 'project_private_found: ' . (!empty($configReport['sources']['project_private_found']) ? 'yes' : 'no') . "\n";
echo 'provider_mode: ' . (string)($smsSettings['provider'] ?? 'empty') . "\n";
echo 'helper_m360_otp_send: ' . (function_exists('m360_otp_send') ? 'yes' : 'no') . "\n";
echo 'helper_m360_otp_verify: ' . (function_exists('m360_otp_verify') ? 'yes' : 'no') . "\n";
echo 'sms_configured: ' . (m360_otp_sms_configured() ? 'yes' : 'no') . "\n";
echo 'auth_header_mode: ' . (string)($tokenCheck['auth_header_mode'] ?? 'unknown') . "\n";
echo 'check_token_http_status: ' . (string)($tokenCheck['http_status'] ?? '0') . "\n";
echo 'check_token_valid: ' . (!empty($tokenCheck['token_valid']) ? 'yes' : 'no') . "\n";
echo 'check_token_error_category: ' . (string)($tokenCheck['error'] ?? '') . "\n";

$sendResultType = 'not_run';
$jsonShape = ['ok' => 'bool', 'message' => 'string', 'data' => 'object_optional'];

if ($probeSend) {
    $sendResult = m360_otp_send($mobile);
    $sendResultType = !empty($sendResult['ok']) ? 'success' : 'failure';
    echo 'probe_send_mobile: ' . $mobile . "\n";
    echo 'probe_send_result_type: ' . $sendResultType . "\n";
    echo 'probe_send_message: ' . (string)($sendResult['message'] ?? '') . "\n";
    if (!empty($sendResult['test_mode'])) {
        echo "probe_send_test_mode: yes\n";
    }
} else {
    echo "probe_send: skipped (use --probe-send to test live send)\n";
}

echo 'json_response_shape: ' . json_encode($jsonShape, JSON_UNESCAPED_UNICODE) . "\n";
echo str_repeat('-', 60) . "\n";

$pass = function_exists('m360_otp_send')
    && function_exists('m360_otp_verify')
    && !empty($configReport['sms_configured'])
    && ($probeSend ? $sendResultType === 'success' : !empty($tokenCheck['token_valid']));

echo 'RESULT: ' . ($pass ? 'PASS' : 'FAIL') . "\n";
echo "No token, OTP code, or API secret printed.\n";

exit($pass ? 0 : 1);
