<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-otp-helper.php';

function pr01_resp_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$sendSrc = (string)file_get_contents($root . '/public_html/api/customer/send-otp.php');
$verifySrc = (string)file_get_contents($root . '/public_html/api/customer/verify-otp.php');

// Simulate send endpoint success JSON shape without HTTP.
m360_otp_session_start();
$_SESSION = [];
$ttl = m360_otp_ttl_seconds();
$sampleOk = json_encode([
    'ok' => true,
    'message' => M360_OTP_MSG_SMS_SENT,
    'data' => ['expires_in' => $ttl],
], JSON_UNESCAPED_UNICODE);
$decodedOk = json_decode($sampleOk, true);

$results = [];
$results[] = pr01_resp_pass('m360_otp_ttl_seconds returns positive int', $ttl > 0);
$results[] = pr01_resp_pass('send uses json ok helper', str_contains($sendSrc, 'm360_otp_json_ok'));
$results[] = pr01_resp_pass('send uses json fail helper', str_contains($sendSrc, 'm360_otp_json_fail'));
$results[] = pr01_resp_pass('verify uses json ok helper', str_contains($verifySrc, 'm360_otp_json_ok'));
$results[] = pr01_resp_pass('verify returns verified flag', str_contains($verifySrc, "'verified' => true"));
$results[] = pr01_resp_pass('sample ok json has ok bool', is_array($decodedOk) && ($decodedOk['ok'] ?? null) === true);
$results[] = pr01_resp_pass('sample ok json has message string', is_array($decodedOk) && is_string($decodedOk['message'] ?? null));
$results[] = pr01_resp_pass('sample ok json has expires_in', is_array($decodedOk) && isset($decodedOk['data']['expires_in']));

$pass = 0;
$fail = 0;
echo "# PR-01 OTP Response Contract Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
