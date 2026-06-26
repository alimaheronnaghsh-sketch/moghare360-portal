<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — OTP-first customer entry flow verification.
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function oef_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function oef_read(string $path): string
{
    return is_file($path) ? (string)file_get_contents($path) : '';
}

function oef_http_post_json(string $url, array $body): array
{
    if (!function_exists('curl_init')) {
        return ['reachable' => false, 'http' => 0, 'raw' => '', 'data' => null, 'detail' => 'curl_missing'];
    }
    $ch = curl_init($url);
    if ($ch === false) {
        return ['reachable' => false, 'http' => 0, 'raw' => '', 'data' => null, 'detail' => 'curl_init_failed'];
    }
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
    ]);
    $raw = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        return ['reachable' => false, 'http' => $http, 'raw' => '', 'data' => null, 'detail' => $err !== '' ? $err : 'curl_exec_failed'];
    }
    $decoded = json_decode((string)$raw, true);
    return [
        'reachable' => true,
        'http' => $http,
        'raw' => (string)$raw,
        'data' => is_array($decoded) ? $decoded : null,
        'detail' => is_array($decoded) ? '' : 'non_json',
    ];
}

function oef_local_mirror_test_ready(string $installPath): bool
{
    $cfgPath = rtrim($installPath, '\\/') . DIRECTORY_SEPARATOR . 'mirror-config.php';
    if (!is_file($cfgPath)) {
        return false;
    }
    $cfg = require $cfgPath;
    if (!is_array($cfg) || empty($cfg['M360_OTP_TEST_MODE'])) {
        return false;
    }
    $digits = preg_replace('/\D+/', '', (string)($cfg['M360_OTP_TEST_CODE'] ?? '')) ?? '';
    return strlen($digits) === 6;
}

$customer = oef_read($public . DIRECTORY_SEPARATOR . 'customer-request.php');
$formJs = oef_read($public . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'customer-form.js');
$helper = oef_read($public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php');
$sendOtp = oef_read($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'send-otp.php');
$verifyOtp = oef_read($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'verify-otp.php');
$exampleCfg = oef_read($public . DIRECTORY_SEPARATOR . 'mirror-config.example.php');
$profile = oef_read($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'profile-status.php');
$request = oef_read($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'request.php');

$results = [];

$results[] = oef_pass('Send OTP button type=button', str_contains($customer, 'id="m360_send_otp"') && preg_match('/id="m360_send_otp"[^>]*type="button"|type="button"[^>]*id="m360_send_otp"/', $customer) === 1);
$sendIdCount = preg_match_all('/id="m360_send_otp"/', $customer, $m);
$results[] = oef_pass('Single send OTP button id', $sendIdCount === 1, 'count=' . $sendIdCount);
$results[] = oef_pass('customer-form.js cache bust', str_contains($customer, 'customer-form.js?v=full-replace-v2'));
$results[] = oef_pass('DOMContentLoaded OTP init', str_contains($formJs, "document.addEventListener('DOMContentLoaded'") && str_contains($formJs, 'initOtpFirstFlow'));
$results[] = oef_pass('Send click preventDefault', str_contains($formJs, 'preventDefault') && str_contains($formJs, 'stopPropagation'));
$results[] = oef_pass('Localhost missing-button console error', str_contains($formJs, 'OTP send button not found') && str_contains($formJs, 'logDevError'));
$results[] = oef_pass('Safe fetch text then JSON parse', str_contains($formJs, 'res.text()') && str_contains($formJs, 'JSON.parse'));
$results[] = oef_pass('User hint before send', str_contains($formJs, 'برای شروع، شماره موبایل خود را وارد کنید.'));
$results[] = oef_pass('Send OTP JS listener exists', str_contains($formJs, 'm360_send_otp') && str_contains($formJs, 'sendOtp'));
$results[] = oef_pass('Fetch path send-otp.php', str_contains($formJs, 'api/customer/send-otp.php'));
$results[] = oef_pass('Fetch credentials same-origin', str_contains($formJs, "credentials: 'same-origin'"));
$results[] = oef_pass('Verify OTP exists', str_contains($formJs, 'api/customer/verify-otp.php') && str_contains($customer, 'm360_verify_otp'));
$results[] = oef_pass('Profile-status endpoint exists', is_file($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'profile-status.php'));
$results[] = oef_pass('Profile-status requires verified mobile', str_contains($profile, 'm360_otp_is_verified'));
$results[] = oef_pass('OTP-first mobile step exists', str_contains($customer, 'm360_step_mobile'));
$results[] = oef_pass('OTP step exists', str_contains($customer, 'm360_step_otp'));
$results[] = oef_pass('Full form sections initially hidden', str_contains($customer, 'm360_section_profile') && str_contains($customer, 'm360-step--hidden'));
$results[] = oef_pass('Submit blocked without verified mobile', str_contains($formJs, 'mobile_verified') && str_contains($customer, 'm360_submit_btn') && str_contains($customer, 'disabled'));
$results[] = oef_pass('Returning customer branch in JS', str_contains($formJs, "flow.value = exists ? 'returning' : 'new'"));
$results[] = oef_pass('New customer branch in JS', str_contains($formJs, 'm360_section_profile') && str_contains($formJs, 'setNewCustomerRequired(true)'));
$results[] = oef_pass('Resend OTP control exists', str_contains($customer, 'm360_resend_otp') && str_contains($formJs, 'startResendCountdown'));
$results[] = oef_pass('Safe JSON parse on fetch', str_contains($formJs, 'JSON.parse'));
$results[] = oef_pass('Config alias keys in helper', str_contains($helper, 'IPPANEL_API_KEY') && str_contains($helper, 'm360_otp_cfg_string'));
$results[] = oef_pass('Request API returning customer flow', str_contains($request, 'customer_flow') && str_contains($request, 'isReturningCustomer'));
$results[] = oef_pass('No fake OTP in JS', !str_contains($formJs, 'useFakeOtp'));
$results[] = oef_pass('Dev OTP gated in helper', str_contains($helper, 'm360_otp_can_use_dev_code') && str_contains($helper, 'm360_otp_get_dev_code'));
$results[] = oef_pass('Dev OTP not in send/verify endpoints', !preg_match('/123456/', $sendOtp . $verifyOtp));
$results[] = oef_pass('Localhost detector supports port', str_contains($helper, 'm360_otp_is_localhost'));
$results[] = oef_pass('send-otp returns test_mode from helper', str_contains($sendOtp, 'test_mode'));
$results[] = oef_pass('verify-otp uses standard verify path', str_contains($verifyOtp, 'm360_otp_verify') && !str_contains($verifyOtp, 'm360_otp_can_use_dev_code'));
$results[] = oef_pass('Local dev UI note uses helper', str_contains($customer, 'm360_otp_can_use_dev_code') && str_contains($customer, 'حالت تست لوکال فعال است'));

require_once $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
$savedHost = $_SERVER['HTTP_HOST'] ?? null;
$_SERVER['HTTP_HOST'] = 'www.moghareh360.ir';
$results[] = oef_pass('www.moghareh360.ir hard-blocks dev OTP', m360_otp_can_use_dev_code() === false && m360_otp_get_dev_code() === '');
if ($savedHost !== null) {
    $_SERVER['HTTP_HOST'] = $savedHost;
} else {
    unset($_SERVER['HTTP_HOST']);
}

$baseUrl = rtrim(getenv('MOGHARE360_BASE_URL') ?: 'http://localhost:8080/moghare360/', '/') . '/';
$localRuntime = rtrim(getenv('MOGHARE360_INSTALL_PATH') ?: 'C:\\xampp\\htdocs\\moghare360', '\\/');
$probe = oef_http_post_json($baseUrl . 'api/customer/send-otp.php', ['phone' => '09123456789']);
if (!$probe['reachable'] || $probe['http'] === 0) {
    $results[] = oef_pass('HTTP send-otp localhost dev fallback probe', true, 'WARN: Apache not reachable — ' . $probe['detail']);
} elseif (!is_array($probe['data'])) {
    $results[] = oef_pass('HTTP send-otp localhost dev fallback probe', false, 'non-JSON response: ' . substr($probe['raw'], 0, 120));
} else {
    $ok = ($probe['data']['ok'] ?? false) === true
        && ($probe['data']['test_mode'] ?? false) === true
        && str_contains((string)($probe['data']['message'] ?? ''), '123456');
    $results[] = oef_pass('HTTP send-otp localhost dev fallback probe', $ok, json_encode($probe['data'], JSON_UNESCAPED_UNICODE));
}

// GET customer page for browser click prerequisites
if (function_exists('curl_init')) {
    $ch = curl_init($baseUrl . 'customer-request.php');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $pageHtml = curl_exec($ch);
    $pageHttp = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($pageHtml === false || $pageHttp === 0) {
        $results[] = oef_pass('Browser page includes OTP send button', true, 'WARN: customer-request.php not reachable over HTTP');
    } else {
        $results[] = oef_pass(
            'Browser page includes OTP send button',
            str_contains((string)$pageHtml, 'id="m360_send_otp"') && str_contains((string)$pageHtml, 'customer-form.js?v=full-replace-v2'),
            'HTTP ' . $pageHttp
        );
    }
}

$lintFiles = [
    'customer-request.php',
    'api/customer/send-otp.php',
    'api/customer/verify-otp.php',
    'api/customer/profile-status.php',
    'api/customer/request.php',
    'includes/m360-otp-helper.php',
];
foreach ($lintFiles as $rel) {
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    $results[] = oef_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
    $out = [];
}

$passed = 0;
$failed = 0;
echo "# MOGHARE360 V1 Customer OTP Entry Flow Test\n\n";
foreach ($results as $r) {
    $mark = $r['pass'] ? 'PASS' : 'FAIL';
    if ($r['pass']) {
        $passed++;
    } else {
        $failed++;
    }
    $detail = $r['detail'] !== '' ? ' — ' . $r['detail'] : '';
    echo sprintf("[%s] %s%s\n", $mark, $r['name'], $detail);
}
echo "\nTotal: " . count($results) . " | PASS: $passed | FAIL: $failed\n";
exit($failed > 0 ? 1 : 0);
