<?php
/**
 * MOGHARE360 ERP — Phase 11 Stabilization Sprint CLI Test
 */

declare(strict_types=1);

const P11ST_PHASE_PAGES = [
    'erp-soft-run-home.php', 'erp-moghare-ready.php', 'erp-business-command-center.php',
    'erp-module-navigation.php', 'erp-blueprint-map.php', 'erp-product-status.php',
    'erp-operational-command-center.php', 'erp-role-demo-navigation.php',
    'erp-management-dashboard.php', 'erp-kpi-report.php', 'erp-operation-performance-report.php',
    'erp-financial-preview-report.php', 'erp-crm-report.php', 'erp-inventory-pressure-report.php',
    'erp-staff-performance-preview.php', 'erp-soft-run-audit.php',
    'moghare360-commercial-demo.php', 'moghare360-sales-showcase.php', 'moghare360-product-packages.php',
    'moghare360-license-preview.php', 'moghare360-commercial-checklist.php', 'moghare360-final-release-report.php',
];

const P11ST_BUILT = [
    'public_html/includes/moghare360-stabilization-helper.php',
    'public_html/erp-stabilization-dashboard.php',
    'public_html/erp-broken-link-report.php',
    'public_html/erp-ui-polish-report.php',
    'public_html/erp-db-consistency-check.php',
    'public_html/erp-local-release-candidate.php',
    'public_html/assets/moghare360-ui/moghare360-stabilization.css',
];

const P11ST_PHP_SYNTAX = [
    'public_html/includes/moghare360-stabilization-helper.php',
    'public_html/erp-stabilization-dashboard.php',
    'public_html/erp-broken-link-report.php',
    'public_html/erp-ui-polish-report.php',
    'public_html/erp-db-consistency-check.php',
    'public_html/erp-local-release-candidate.php',
];

const P11ST_MISSION_DOCS = [
    'docs/missions/phase_11_stabilization_sprint/PHASE_11_00_INDEX.md',
    'docs/missions/phase_11_stabilization_sprint/PHASE_11_01_SCOPE.md',
    'docs/missions/phase_11_stabilization_sprint/PHASE_11_02_BROKEN_LINK_REPORT.md',
    'docs/missions/phase_11_stabilization_sprint/PHASE_11_03_UI_POLISH_REPORT.md',
    'docs/missions/phase_11_stabilization_sprint/PHASE_11_04_DATABASE_CONSISTENCY_CHECK.md',
    'docs/missions/phase_11_stabilization_sprint/PHASE_11_05_FORBIDDEN_FILE_CHECK.md',
    'docs/missions/phase_11_stabilization_sprint/PHASE_11_06_LOCAL_RELEASE_CANDIDATE.md',
    'docs/missions/phase_11_stabilization_sprint/PHASE_11_90_TEST_RESULT.md',
    'docs/missions/phase_11_stabilization_sprint/PHASE_11_99_SIGNOFF.md',
];

const P11ST_FORBIDDEN = [
    'staff-auth.php', 'access-control.php', 'staff-login.php', 'config.php', 'config.example.php',
    'private/erp-config.php', 'private/erp-config.example.php',
];

function p11st_root(): string { return dirname(__DIR__); }
function p11st_line(string $l, string $s): void { echo str_pad($l, 52, '.') . ' ' . $s . PHP_EOL; }
function p11st_php(): string {
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '') continue;
        if ($c === 'php' || is_file($c)) return $c;
    }
    return 'php';
}

require_once p11st_root() . '/public_html/includes/moghare360-stabilization-helper.php';

echo 'PHASE 11 STABILIZATION SPRINT TEST' . PHP_EOL . str_repeat('=', 52) . PHP_EOL;
$ok = true; $fail = [];

$sqlPath = p11st_root() . '/public_html/sql/sqlserver/phase_11_stabilization_sprint.sql';
p11st_line('SQL phase_11 file', is_file($sqlPath) ? 'FOUND (optional)' : 'NOT REQUIRED');

foreach (P11ST_BUILT as $rel) {
    $fp = p11st_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p11st_line('Built ' . basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'Phase 1-10 main pages:' . PHP_EOL;
foreach (P11ST_PHASE_PAGES as $page) {
    $fp = p11st_root() . '/public_html/' . $page;
    $pass = is_file($fp);
    p11st_line('Page ' . $page, $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $page; }
}

echo 'Mission docs:' . PHP_EOL;
foreach (P11ST_MISSION_DOCS as $rel) {
    $fp = p11st_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p11st_line(basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

foreach (P11ST_PHP_SYNTAX as $rel) {
    $fp = p11st_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { $ok = false; $fail[] = $rel; p11st_line('Syntax ' . basename($rel), 'FAILED'); continue; }
    $out = []; $ec = 0;
    exec(p11st_php() . ' -l ' . escapeshellarg($fp) . ' 2>&1', $out, $ec);
    p11st_line('PHP syntax ' . basename($rel), $ec === 0 ? 'PASSED' : 'FAILED');
    if ($ec !== 0) { $ok = false; $fail[] = 'syntax ' . $rel; }
}

foreach (P11ST_FORBIDDEN as $rel) {
    $fp = p11st_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { p11st_line('Forbidden ' . $rel, 'SKIP'); continue; }
    $gc = [];
    exec('git -C ' . escapeshellarg(p11st_root()) . ' status --short -- ' . escapeshellarg($rel) . ' 2>&1', $gc);
    $mod = $gc !== [] && trim(implode('', $gc)) !== '';
    p11st_line('Forbidden ' . $rel, $mod ? 'FAILED (modified)' : 'OK');
    if ($mod) { $ok = false; $fail[] = 'forbidden ' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL;
echo $ok ? 'RESULT: PASSED' . PHP_EOL : 'RESULT: FAILED' . PHP_EOL;
if (!$ok) echo 'Failures: ' . implode(', ', $fail) . PHP_EOL;
echo 'PHASE 11 STABILIZATION TEST COMPLETE' . PHP_EOL;
exit($ok ? 0 : 1);
