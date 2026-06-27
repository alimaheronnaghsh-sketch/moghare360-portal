<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$migrationPath = $root . '/database/migrations/P8_management_dashboard_owner_control.sql';
$migration = is_file($migrationPath) ? (string)file_get_contents($migrationPath) : '';
$kpi = is_file($root . '/public_html/includes/m360-management-kpi-helper.php')
    ? (string)file_get_contents($root . '/public_html/includes/m360-management-kpi-helper.php')
    : '';
$fin = is_file($root . '/public_html/includes/m360-financial-control-helper.php')
    ? (string)file_get_contents($root . '/public_html/includes/m360-financial-control-helper.php')
    : '';

function p8s_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p8s_pass('P8 migration exists', is_file($migrationPath));
$results[] = p8s_pass('Non-destructive IF OBJECT_ID', str_contains($migration, 'IF OBJECT_ID') && str_contains($migration, 'CREATE VIEW'));
$results[] = p8s_pass('No DROP/DELETE/TRUNCATE', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p8s_pass('vw_m360_owner_jobcard_pipeline', str_contains($migration, 'vw_m360_owner_jobcard_pipeline'));
$results[] = p8s_pass('vw_m360_owner_financial_control', str_contains($migration, 'vw_m360_owner_financial_control'));
$results[] = p8s_pass('vw_m360_owner_qc_control', str_contains($migration, 'vw_m360_owner_qc_control'));
$qcViewStart = (int)strpos($migration, 'vw_m360_owner_qc_control');
$qcViewBlock = $qcViewStart >= 0 ? substr($migration, $qcViewStart, 2500) : '';
$results[] = p8s_pass('QC view CREATE OR ALTER', str_contains($migration, 'CREATE OR ALTER VIEW dbo.vw_m360_owner_qc_control'));
$results[] = p8s_pass('QC view no is_active', !str_contains($qcViewBlock, 'is_active'));
$results[] = p8s_pass('QC view latest item ROW_NUMBER', str_contains($qcViewBlock, 'ROW_NUMBER()') && str_contains($qcViewBlock, 'PARTITION BY qi.jobcard_id, qi.item_key'));
$results[] = p8s_pass('No operational INSERT/UPDATE', !preg_match('/\b(INSERT|UPDATE)\s+(INTO|dbo\.)/i', $migration));
$results[] = p8s_pass('Management index only', str_contains($migration, 'IX_erp_jobcards_mgmt_open'));
$results[] = p8s_pass('Migration read-only comment', str_contains($migration, 'read-only') || str_contains($migration, 'non-destructive'));
$results[] = p8s_pass('Helper view_exists fallback', str_contains($kpi, 'm360_mgmt_view_exists') && str_contains($kpi, 'M360_MGMT_VIEW_PIPELINE'));
$results[] = p8s_pass('Pipeline fallback to erp_jobcards', str_contains($kpi, 'erp_jobcards') && str_contains($kpi, 'else'));
$results[] = p8s_pass('Financial helper view fallback', str_contains($fin, 'm360_mgmt_view_exists') && str_contains($fin, 'm360_mgmt_fetch_pipeline'));

$pass = 0; $fail = 0;
echo "# P8 Management Schema Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
