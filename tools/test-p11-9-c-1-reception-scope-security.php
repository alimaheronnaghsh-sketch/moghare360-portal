<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function p119c1_sec_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$allowed = [
    'public_html/erp-reception-online-requests.php',
    'public_html/erp-reception-online-request-detail.php',
    'public_html/erp-reception-online-request-accept.php',
    'public_html/includes/m360-reception-helper.php',
];

$gitOut = shell_exec('git -C ' . escapeshellarg($root) . ' status --porcelain 2>nul');
$onlyAllowed = true;
if (is_string($gitOut) && trim($gitOut) !== '') {
    foreach (array_filter(array_map('trim', explode("\n", trim($gitOut)))) as $line) {
        $ok = false;
        foreach ($allowed as $a) {
            if (str_contains(str_replace('\\', '/', $line), $a) || str_contains($line, 'docs/audit/MOGHARE360_P11_9_C_1') || str_contains($line, 'tools/test-p11-9-c-1')) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            $onlyAllowed = false;
            break;
        }
    }
}
$results[] = p119c1_sec_pass('only C-1 allowed code changes', $onlyAllowed);

foreach (['staff-auth.php', 'access-control.php'] as $f) {
    foreach ([$root . '/public_html/includes/' . $f, $root . '/includes/' . $f] as $path) {
        if (is_file($path)) {
            $results[] = p119c1_sec_pass($f . ' not modified recently', filemtime($path) <= time() - 120);
        }
    }
}

$csrfCore = $root . '/includes/erp-csrf.php';
$results[] = p119c1_sec_pass('shared erp-csrf.php not modified recently', !(is_file($csrfCore) && filemtime($csrfCore) > time() - 120));

$sqlRecent = false;
foreach (glob($root . '/database/**/*.sql') ?: [] as $sql) {
    if (filemtime($sql) > time() - 120) {
        $sqlRecent = true;
        break;
    }
}
$results[] = p119c1_sec_pass('no SQL migration added recently', !$sqlRecent);

$otpHelper = $root . '/public_html/includes/m360-otp-helper.php';
$results[] = p119c1_sec_pass('OTP helper not modified recently', !(is_file($otpHelper) && filemtime($otpHelper) > time() - 120));

$privateRecent = false;
if (is_dir($root . '/private')) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/private'));
    foreach ($it as $file) {
        if ($file->isFile() && $file->getMTime() > time() - 120) {
            $privateRecent = true;
            break;
        }
    }
}
$results[] = p119c1_sec_pass('no private files modified recently', !$privateRecent);

$results[] = p119c1_sec_pass('implementation report exists', is_file($root . '/docs/audit/MOGHARE360_P11_9_C_1_RECEPTION_ACTION_STABILIZATION_REPORT.md'));

$pass = 0;
$fail = 0;
echo "# P11.9-C-1 Reception Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
