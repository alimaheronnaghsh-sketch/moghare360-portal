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

$customer = oef_read($public . DIRECTORY_SEPARATOR . 'customer-request.php');
$formJs = oef_read($public . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'customer-form.js');
$helper = oef_read($public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php');
$profile = oef_read($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'profile-status.php');
$request = oef_read($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'request.php');

$results = [];

$results[] = oef_pass('Send OTP button type=button', str_contains($customer, 'id="m360_send_otp"') && preg_match('/id="m360_send_otp"[^>]*type="button"|type="button"[^>]*id="m360_send_otp"/', $customer) === 1);
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
$results[] = oef_pass('No fake OTP', !str_contains($helper, 'useFakeOtp') && !str_contains($formJs, '123456'));
$results[] = oef_pass('No hardcoded OTP success', !preg_match('/\$otp\s*=\s*[\'"]\d{6}[\'"]/', $helper));

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
