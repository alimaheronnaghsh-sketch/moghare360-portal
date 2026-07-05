<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr01_scope_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$allowed = [
    'public_html/customer-request.php',
    'public_html/assets/js/customer-form.js',
    'public_html/api/customer/send-otp.php',
    'public_html/api/customer/verify-otp.php',
    'public_html/includes/m360-otp-helper.php',
    'public_html/includes/m360-otp-config-loader.php',
    'public_html/includes/m360-reception-helper.php',
    'public_html/includes/m360-legacy-otp-deprecation-stub.php',
];

$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-otp-helper.php');
$sendSrc = (string)file_get_contents($root . '/public_html/api/customer/send-otp.php');

$results = [];
$results[] = pr01_scope_pass('no fake otp bypass in helper', !str_contains($helperSrc, 'return [\'ok\' => true') || str_contains($helperSrc, 'm360_otp_can_use_dev_code'));
$results[] = pr01_scope_pass('no hardcoded always-success send', !preg_match('/function m360_otp_send[\\s\\S]{0,200}return \\[\'ok\' => true/', $helperSrc));
$results[] = pr01_scope_pass('send endpoint requires POST', str_contains($sendSrc, "REQUEST_METHOD"));
$results[] = pr01_scope_pass('no staff-auth in send endpoint', !str_contains($sendSrc, 'staff-auth.php'));
$results[] = pr01_scope_pass('no access-control in send endpoint', !str_contains($sendSrc, 'access-control.php'));
$results[] = pr01_scope_pass('no jobcard in send endpoint', !str_contains(strtolower($sendSrc), 'jobcard'));
$results[] = pr01_scope_pass('no sql schema in otp helper', !preg_match('/\\bALTER TABLE\\b/i', $helperSrc));
$results[] = pr01_scope_pass('no CREATE TABLE in otp helper', !preg_match('/\\bCREATE TABLE\\b/i', $helperSrc));
$results[] = pr01_scope_pass('allowed file list documented count', count($allowed) === 8);

$pass = 0;
$fail = 0;
echo "# PR-01 OTP Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
