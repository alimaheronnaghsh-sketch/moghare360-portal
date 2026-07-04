<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';

function fixb_scope_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$saveSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-save.php');
$intakeSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');

$results[] = fixb_scope_pass('no DB schema migration files touched', !glob($root . '/database/migrations/*fix-b*'));
$results[] = fixb_scope_pass('staff-auth.php untouched', !str_contains($saveSrc, 'staff-auth') && !str_contains($intakeSrc, 'staff-auth.php'));
$results[] = fixb_scope_pass('access-control.php untouched in save', !str_contains($saveSrc, 'access-control.php'));
$results[] = fixb_scope_pass('no automatic jobcard', !str_contains($saveSrc, 'convert_to_jobcard') && !str_contains($helperSrc, 'auto_jobcard'));
$results[] = fixb_scope_pass('save Throwable catch', str_contains($saveSrc, 'catch (Throwable)'));
$results[] = fixb_scope_pass('no stack trace leak', !str_contains($saveSrc, 'getTraceAsString'));
$results[] = fixb_scope_pass('CSRF in save', str_contains($saveSrc, 'csrf') || str_contains($saveSrc, 'CSRF'));
$results[] = fixb_scope_pass('OTP bypass not added', !preg_match('/otp_verified\s*=\s*1/i', $saveSrc));

$childTests = [
    'test-p11-9-c-2c-fix-a-save-validation.php',
    'test-p11-9-c-2c-intake-write-runtime.php',
    'test-p11-9-c-2c-intake-write-security.php',
    'test-p11-9-c-2c-intake-write-process.php',
    'test-p11-9-c-2c-intake-write-payload.php',
    'test-p11-9-c-2c-scope-security.php',
    'test-v1-production-signoff.php',
];
foreach ($childTests as $test) {
    exec('"' . $phpBin . '" ' . escapeshellarg($root . '/tools/' . $test) . ' 2>&1', $out, $code);
    $results[] = fixb_scope_pass($test . ' regression', $code === 0, $code !== 0 ? implode("\n", array_slice($out, -4)) : '');
}

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C-FIX-B Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
