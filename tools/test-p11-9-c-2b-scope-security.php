<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function c2b_sec_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$allowedPatterns = [
    'public_html/erp-reception-workbench.php',
    'public_html/erp-reception-intake-file.php',
    'public_html/includes/m360-reception-workbench-helper.php',
    'public_html/erp-staff-home.php',
    'public_html/erp-reception-online-requests.php',
    'public_html/erp-reception-online-request-detail.php',
    'public_html/assets/css/moghare360-v1-luxury-ui.css',
    'docs/audit/MOGHARE360_P11_9_C_2B',
    'tools/test-p11-9-c-2b',
];

$forbidden = [
    'public_html/includes/staff-auth.php',
    'public_html/includes/access-control.php',
    'includes/staff-auth.php',
    'includes/access-control.php',
];

foreach ($forbidden as $f) {
    $path = $root . '/' . $f;
    if (is_file($path)) {
        $results[] = c2b_sec_pass($f . ' not modified recently', filemtime($path) <= time() - 120);
    }
}

$authFiles = ['staff-login.php', 'owner-login.php'];
foreach ($authFiles as $af) {
    $path = $root . '/public_html/' . $af;
    if (is_file($path)) {
        $results[] = c2b_sec_pass($af . ' not modified recently', filemtime($path) <= time() - 120);
    }
}

$otpHelper = $root . '/public_html/includes/m360-otp-helper.php';
$results[] = c2b_sec_pass('OTP helper not modified recently', !(is_file($otpHelper) && filemtime($otpHelper) > time() - 120));

$sqlRecent = false;
foreach (glob($root . '/database/migrations/*.sql') ?: [] as $sql) {
    if (filemtime($sql) > time() - 120) {
        $sqlRecent = true;
        break;
    }
}
$results[] = c2b_sec_pass('no SQL migration modified recently', !$sqlRecent);

$privateRecent = false;
if (is_dir($root . '/private')) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/private', FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->isFile() && $file->getMTime() > time() - 120) {
            $privateRecent = true;
            break;
        }
    }
}
$results[] = c2b_sec_pass('no private files modified recently', !$privateRecent);

$results[] = c2b_sec_pass('scope report exists', is_file($root . '/docs/audit/MOGHARE360_P11_9_C_2B_RECEPTION_WORKBENCH_SCOPE_REPORT.md'));
$results[] = c2b_sec_pass('workbench page exists', is_file($root . '/public_html/erp-reception-workbench.php'));
$results[] = c2b_sec_pass('intake shell exists', is_file($root . '/public_html/erp-reception-intake-file.php'));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2B Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
