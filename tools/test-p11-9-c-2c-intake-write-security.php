<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function c2c_sec_pass(string $name, bool $ok): array
{
    return ['name' => $name, 'pass' => $ok];
}

$helper = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$save = (string)file_get_contents($root . '/public_html/erp-reception-intake-save.php');
$intake = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');

$results = [];
$results[] = c2c_sec_pass('save requires staff', str_contains($save, 'm360_reception_require_staff'));
$results[] = c2c_sec_pass('save validates CSRF', str_contains($save, 'm360_reception_csrf_is_valid'));
$results[] = c2c_sec_pass('save POST only', str_contains($save, "!== 'POST'"));
$results[] = c2c_sec_pass('intake forms include CSRF', str_contains($intake, 'm360_reception_csrf_input_html'));
$results[] = c2c_sec_pass('request id numeric cast', str_contains($save, '(int)$_POST'));
$results[] = c2c_sec_pass('no otp_verified write in apply', !preg_match('/\$payload\s*\[\s*[\'"]otp_verified[\'"]\s*\]\s*=\s*1/', $helper));
$results[] = c2c_sec_pass('process save preserves otp', str_contains($helper, '$otpPreserved'));
$results[] = c2c_sec_pass('no automatic jobcard in save', !str_contains($save, 'convert_to_jobcard') && !str_contains($save, 'm360_reception_convert_to_jobcard'));
$results[] = c2c_sec_pass('no GET write in save', !str_contains($save, '$_GET'));
$results[] = c2c_sec_pass('parameterized payload update', str_contains($helper, 'request_payload_json = ?'));
$results[] = c2c_sec_pass('intake no auto convert on load', !preg_match('/m360_reception_convert_to_jobcard\s*\(/', $intake));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C Intake Write Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
