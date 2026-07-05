<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr01b_sec_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-otp-helper.php');
$loaderSrc = (string)file_get_contents($root . '/public_html/includes/m360-otp-config-loader.php');
$diagSrc = is_file($root . '/tools/diagnose-pr-01b-otp-reference-reconcile.php')
    ? (string)file_get_contents($root . '/tools/diagnose-pr-01b-otp-reference-reconcile.php')
    : '';

$results = [];
$results[] = pr01b_sec_pass('diagnose does not echo api_key value', !preg_match('/echo\s+.*api_key/i', $diagSrc));
$results[] = pr01b_sec_pass('diagnose stop if reference missing', str_contains($diagSrc, 'KNOWN_WORKING_REFERENCE_NOT_FOUND'));
$results[] = pr01b_sec_pass('no fake always-success in helper send', !preg_match('/function m360_otp_send_sms[\\s\\S]{0,120}return \\[\'ok\' => true/', $helperSrc));
$results[] = pr01b_sec_pass('no staff-auth in otp helper', !str_contains($helperSrc, 'staff-auth.php'));
$results[] = pr01b_sec_pass('no access-control in otp helper', !str_contains($helperSrc, 'access-control.php'));
$results[] = pr01b_sec_pass('no jobcard in otp helper', !str_contains(strtolower($helperSrc), 'jobcard'));
$results[] = pr01b_sec_pass('no sql ddl in otp helper', !preg_match('/\\bCREATE TABLE\\b/i', $helperSrc));
$results[] = pr01b_sec_pass('example config not merged at runtime', !str_contains($loaderSrc, 'm360-otp-config.example.php') || str_contains($loaderSrc, 'not used in production'));
$results[] = pr01b_sec_pass('private real config gitignored path only in candidates', str_contains($loaderSrc, 'm360-otp-config.php'));

$pass = 0;
$fail = 0;
echo "# PR-01B OTP Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
