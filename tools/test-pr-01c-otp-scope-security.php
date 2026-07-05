<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$helper = (string)file_get_contents($root . '/public_html/includes/m360-otp-helper.php');
$send = (string)file_get_contents($root . '/public_html/api/customer/send-otp.php');

function pr01c_sec_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$results[] = pr01c_sec_pass('no fake success in send', !preg_match('/function m360_otp_send\\([\\s\\S]{0,200}return \\[\'ok\' => true/', $helper));
$results[] = pr01c_sec_pass('error_code in send endpoint', str_contains($send, 'error_code'));
$results[] = pr01c_sec_pass('no staff-auth', !str_contains($helper, 'staff-auth.php'));
$results[] = pr01c_sec_pass('no access-control', !str_contains($helper, 'access-control.php'));
$results[] = pr01c_sec_pass('no jobcard', !str_contains(strtolower($helper), 'jobcard'));
$results[] = pr01c_sec_pass('no sql ddl', !preg_match('/\\bCREATE TABLE\\b/i', $helper));
$results[] = pr01c_sec_pass('no ssl verify disabled permanently', !str_contains($helper, 'CURLOPT_SSL_VERIFYPEER') || !preg_match('/CURLOPT_SSL_VERIFYPEER\\s*=>\\s*false/', $helper));

$pass = 0;
$fail = 0;
echo "# PR-01C OTP Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
