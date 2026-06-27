<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . '/public_html';

function p9u_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p9u_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }
function p9u_lint(string $rel): array {
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $out = []; $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p9u_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$pages = [
    'erp-soft-run-control-center.php',
    'erp-end-to-end-demo-scenario.php',
    'erp-soft-run-checklist.php',
    'erp-demo-flow-map.php',
    'erp-demo-readiness-report.php',
];
$helpers = [
    'includes/m360-soft-run-helper.php',
    'includes/m360-demo-scenario-helper.php',
    'includes/m360-demo-readiness-helper.php',
    'includes/m360-e2e-validation-helper.php',
];
$apis = [
    'api/soft-run/readiness-summary.php',
    'api/soft-run/demo-scenario-status.php',
];

$ui = '';
foreach ($pages as $page) {
    $ui .= p9u_read($public . '/' . $page);
}

$results = [];
foreach ($pages as $page) {
    $results[] = p9u_pass('UI page: ' . $page, is_file($public . '/' . $page));
}
foreach ($helpers as $helper) {
    $results[] = p9u_pass('Helper: ' . basename($helper), is_file($public . '/' . $helper));
}
foreach ($apis as $api) {
    $results[] = p9u_pass('API: ' . basename($api), is_file($public . '/' . $api));
}
$results[] = p9u_pass('CSS exists', is_file($public . '/assets/css/m360-soft-run.css'));
$results[] = p9u_pass('JS exists', is_file($public . '/assets/js/m360-soft-run.js'));
$results[] = p9u_pass('Release CSS exists', is_file($public . '/assets/moghare360-ui/moghare360-soft-run-release.css'));
$results[] = p9u_pass('RTL on soft run pages', str_contains($ui, 'dir="rtl"'));
$results[] = p9u_pass('Persian control center title', str_contains($ui, 'کنترل‌سنتر Soft Run') || str_contains($ui, 'چک‌لیست Soft Run'));
$results[] = p9u_pass('Persian demo scenario', str_contains($ui, 'سناریوی End-to-End') || str_contains($ui, 'نقشه Demo Flow'));
$results[] = p9u_pass('M360-DEMO prefix shown', str_contains($ui, 'M360-DEMO') || str_contains(p9u_read($public . '/includes/m360-soft-run-helper.php'), 'M360-DEMO'));
$results[] = p9u_pass('No credentials in UI', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $ui));
$results[] = p9u_pass('Staff gate on control center', str_contains(p9u_read($public . '/erp-soft-run-control-center.php'), 'm360_soft_run_require_staff'));
$results[] = p9u_pass('Nav links to P8 dashboard', str_contains(p9u_read($public . '/includes/m360-soft-run-helper.php'), 'erp-management-dashboard.php'));
$readOnlyUi = '';
foreach (['erp-soft-run-control-center.php', 'erp-end-to-end-demo-scenario.php', 'erp-demo-flow-map.php', 'erp-demo-readiness-report.php'] as $page) {
    $readOnlyUi .= p9u_read($public . '/' . $page);
}
$results[] = p9u_pass('No POST on read-only pages', !preg_match('/method\s*=\s*[\'"]post[\'"]/i', $readOnlyUi));
$results[] = p9u_pass('No UI SQL mutation on read-only pages', !preg_match('/\b(INSERT INTO|UPDATE dbo|DELETE FROM)\b/i', $readOnlyUi));

foreach ($pages as $page) {
    $results[] = p9u_lint($page);
}
foreach ($helpers as $helper) {
    $results[] = p9u_lint($helper);
}
foreach ($apis as $api) {
    $results[] = p9u_lint($api);
}

$pass = 0; $fail = 0;
echo "# P9 Soft Run UI Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
