<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$helper = (string)file_get_contents($root . '/public_html/includes/m360-otp-helper.php');

function pr01c_curl_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$results[] = pr01c_curl_pass('apply curl options helper exists', str_contains($helper, 'function m360_otp_ippanel_apply_curl_options'));
$results[] = pr01c_curl_pass('CURLOPT_CONNECTTIMEOUT set', str_contains($helper, 'CURLOPT_CONNECTTIMEOUT'));
$results[] = pr01c_curl_pass('CURLOPT_TIMEOUT set', str_contains($helper, 'CURLOPT_TIMEOUT'));
$results[] = pr01c_curl_pass('CURLOPT_HTTP_VERSION_1_1 set', str_contains($helper, 'CURL_HTTP_VERSION_1_1'));
$results[] = pr01c_curl_pass('CURLOPT_IPRESOLVE_V4 set', str_contains($helper, 'CURL_IPRESOLVE_V4'));
$results[] = pr01c_curl_pass('CURLOPT_NOSIGNAL set', str_contains($helper, 'CURLOPT_NOSIGNAL'));
$results[] = pr01c_curl_pass('CURLOPT_SSL_VERIFYPEER true', str_contains($helper, 'CURLOPT_SSL_VERIFYPEER') && str_contains($helper, 'true'));
$results[] = pr01c_curl_pass('CURLOPT_SSL_VERIFYHOST 2', str_contains($helper, 'CURLOPT_SSL_VERIFYHOST') && str_contains($helper, '2'));
$results[] = pr01c_curl_pass('CURLOPT_PROXY cleared', str_contains($helper, "CURLOPT_PROXY => ''"));
$results[] = pr01c_curl_pass('send uses apply curl options', str_contains($helper, 'm360_otp_ippanel_apply_curl_options($ch, $bodyJson)'));
$results[] = pr01c_curl_pass('check_token uses apply curl options', str_contains($helper, "m360_otp_ippanel_apply_curl_options(\$ch, '{}')"));
$results[] = pr01c_curl_pass('safe error code helper exists', str_contains($helper, 'function m360_otp_ippanel_safe_error_code'));
$results[] = pr01c_curl_pass('check_token not used in m360_otp_send', !preg_match('/function m360_otp_send\\([\\s\\S]*m360_otp_ippanel_check_token/', $helper));

$pass = 0;
$fail = 0;
echo "# PR-01C OTP cURL Options Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
