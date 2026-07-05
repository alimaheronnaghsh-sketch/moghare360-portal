<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr01_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$js = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');
$sendSrc = (string)file_get_contents($root . '/public_html/api/customer/send-otp.php');
$verifySrc = (string)file_get_contents($root . '/public_html/api/customer/verify-otp.php');
$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-otp-helper.php');

$results = [];
$results[] = pr01_pass('customer-form send endpoint canonical', str_contains($js, "fetchJson('api/customer/send-otp.php'"));
$results[] = pr01_pass('customer-form verify endpoint canonical', str_contains($js, "fetchJson('api/customer/verify-otp.php'"));
$results[] = pr01_pass('customer-form payload uses phone key', str_contains($js, '{ phone: phone }'));
$results[] = pr01_pass('send-otp calls m360_otp_send', str_contains($sendSrc, 'm360_otp_send($phone)'));
$results[] = pr01_pass('verify-otp calls m360_otp_verify', str_contains($verifySrc, 'm360_otp_verify($phone, $otp)'));
$results[] = pr01_pass('send-otp no undefined TTL constant', !str_contains($sendSrc, 'M360_OTP_TTL_SECONDS'));
$results[] = pr01_pass('send-otp uses m360_otp_ttl_seconds', str_contains($sendSrc, 'm360_otp_ttl_seconds()'));
$results[] = pr01_pass('helper defines m360_otp_send', str_contains($helperSrc, 'function m360_otp_send('));
$results[] = pr01_pass('helper defines m360_otp_verify', str_contains($helperSrc, 'function m360_otp_verify('));
$results[] = pr01_pass('no legacy send-otp in customer-form', !preg_match("/fetchJson\\(['\"]send-otp\\.php/", $js));

$pass = 0;
$fail = 0;
echo "# PR-01 OTP Canonical Route Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
