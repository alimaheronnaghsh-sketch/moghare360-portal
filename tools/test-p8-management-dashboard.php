<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . '/public_html';

function p8d_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p8d_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }
function p8d_lint(string $rel): array {
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $out = []; $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p8d_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$pages = [
    'erp-management-dashboard.php',
    'erp-owner-control-center.php',
    'erp-operational-kpi.php',
    'erp-jobcard-timeline.php',
    'erp-bottleneck-monitor.php',
    'erp-financial-control-summary.php',
];
$helpers = [
    'includes/m360-management-kpi-helper.php',
    'includes/m360-owner-control-helper.php',
    'includes/m360-bottleneck-helper.php',
    'includes/m360-jobcard-timeline-helper.php',
    'includes/m360-financial-control-helper.php',
];

$ui = '';
foreach ($pages as $page) {
    $ui .= p8d_read($public . '/' . $page);
}

$results = [];
foreach ($pages as $page) {
    $results[] = p8d_pass('UI page: ' . $page, is_file($public . '/' . $page));
}
foreach ($helpers as $helper) {
    $results[] = p8d_pass('Helper: ' . basename($helper), is_file($public . '/' . $helper));
}
$results[] = p8d_pass('CSS exists', is_file($public . '/assets/css/m360-management-dashboard.css'));
$results[] = p8d_pass('RTL on dashboard', str_contains($ui, 'dir="rtl"'));
$results[] = p8d_pass('Persian dashboard title', str_contains($ui, 'داشبورد مدیریت') || str_contains($ui, 'مرکز کنترل مالک'));
$results[] = p8d_pass('Persian KPI page', str_contains($ui, 'KPI عملیاتی') || str_contains($ui, 'کنترل مالی'));
$results[] = p8d_pass('No credentials in UI', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $ui));
$results[] = p8d_pass('No POST forms on UI pages', !preg_match('/method\s*=\s*[\'"]post[\'"]/i', $ui));
$results[] = p8d_pass('No UI SQL mutation', !preg_match('/\b(INSERT INTO|UPDATE dbo|DELETE FROM)\b/i', $ui));
$results[] = p8d_pass('Staff gate on dashboard', str_contains(p8d_read($public . '/erp-management-dashboard.php'), 'm360_mgmt_require_staff'));

foreach ($pages as $page) {
    $results[] = p8d_lint($page);
}
foreach ($helpers as $helper) {
    $results[] = p8d_lint($helper);
}

$pass = 0; $fail = 0;
echo "# P8 Management Dashboard Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
