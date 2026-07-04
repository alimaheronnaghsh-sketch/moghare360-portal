<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';

function fixc_scope_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$saveSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-save.php');
$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');

$results[] = fixc_scope_pass('no automatic jobcard', !str_contains($saveSrc, 'convert_to_jobcard'));
$results[] = fixc_scope_pass('no otp bypass', !preg_match('/otp_verified\s*=\s*1/i', $saveSrc));
$results[] = fixc_scope_pass('staff-auth untouched', !str_contains($saveSrc, 'staff-auth.php'));
$results[] = fixc_scope_pass('scope report exists', is_file($root . '/docs/audit/MOGHARE360_P11_9_C_2C_FIX_C_SCROLL_PHOTO_SCOPE_REPORT.md'));

$childTests = [
    'test-p11-9-c-2c-fix-c-scroll-anchor.php',
    'test-p11-9-c-2c-fix-c-section-lock.php',
    'test-p11-9-c-2c-fix-c-photo-six.php',
    'test-p11-9-c-2c-fix-a-save-validation.php',
    'test-p11-9-c-2c-fix-b-operational-uat.php',
    'test-p11-9-c-2c-intake-write-runtime.php',
    'test-p11-9-c-2c-intake-write-security.php',
    'test-p11-9-c-2c-intake-write-process.php',
    'test-p11-9-c-2c-intake-write-payload.php',
    'test-p11-9-c-2c-scope-security.php',
    'test-v1-production-signoff.php',
];
foreach ($childTests as $test) {
    exec('"' . $phpBin . '" ' . escapeshellarg($root . '/tools/' . $test) . ' 2>&1', $out, $code);
    $results[] = fixc_scope_pass($test, $code === 0, $code !== 0 ? implode("\n", array_slice($out, -4)) : '');
}

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C-FIX-C Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
