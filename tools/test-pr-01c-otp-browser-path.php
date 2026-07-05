<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr01c_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$page = (string)file_get_contents($root . '/public_html/customer-request.php');
$js = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');
$send = (string)file_get_contents($root . '/public_html/api/customer/send-otp.php');

$results = [];
$results[] = pr01c_pass('customer-request loads customer-form.js', str_contains($page, 'customer-form.js'));
$results[] = pr01c_pass('customer-request uses filemtime cache bust', str_contains($page, 'filemtime($m360CustomerFormJs)'));
$results[] = pr01c_pass('js uses canonical send endpoint', str_contains($js, "api/customer/send-otp.php"));
$results[] = pr01c_pass('js uses canonical verify endpoint', str_contains($js, "api/customer/verify-otp.php"));
$results[] = pr01c_pass('js payload uses phone key', str_contains($js, '{ phone: phone }'));
$results[] = pr01c_pass('js logs safe error_code', str_contains($js, 'error_code'));
$results[] = pr01c_pass('send endpoint returns error_code', str_contains($send, "'error_code'"));
$results[] = pr01c_pass('no legacy root otp in js', !preg_match("/fetchJson\\(['\"]send-otp\\.php/", $js));
$results[] = pr01c_pass('no contract otp in js', !str_contains($js, 'contract-otp'));

$pass = 0;
$fail = 0;
echo "# PR-01C OTP Browser Path Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
