<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$migrationPath = $root . '/database/migrations/P9_end_to_end_soft_run.sql';
$migration = is_file($migrationPath) ? (string)file_get_contents($migrationPath) : '';
$helper = is_file($root . '/public_html/includes/m360-soft-run-helper.php')
    ? (string)file_get_contents($root . '/public_html/includes/m360-soft-run-helper.php')
    : '';

function p9s_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p9s_pass('P9 migration exists', is_file($migrationPath));
$results[] = p9s_pass('Non-destructive IF OBJECT_ID', str_contains($migration, 'IF OBJECT_ID') && str_contains($migration, 'CREATE TABLE'));
$results[] = p9s_pass('No DROP/DELETE/TRUNCATE', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p9s_pass('erp_soft_run_scenarios table', str_contains($migration, 'erp_soft_run_scenarios'));
$results[] = p9s_pass('erp_soft_run_events table', str_contains($migration, 'erp_soft_run_events'));
$results[] = p9s_pass('erp_soft_run_checklist table', str_contains($migration, 'erp_soft_run_checklist'));
$results[] = p9s_pass('Scenario index IX_erp_soft_run_scenarios_code', str_contains($migration, 'IX_erp_soft_run_scenarios_code'));
$results[] = p9s_pass('Checklist index IX_erp_soft_run_checklist_key', str_contains($migration, 'IX_erp_soft_run_checklist_key'));
$results[] = p9s_pass('No operational INSERT/UPDATE', !preg_match('/\b(INSERT|UPDATE)\s+(INTO|dbo\.)/i', $migration));
$results[] = p9s_pass('Migration read-only comment', str_contains($migration, 'read-only') || str_contains($migration, 'non-destructive') || str_contains($migration, 'tracking tables only'));
$results[] = p9s_pass('Helper table constants', str_contains($helper, 'M360_SOFT_RUN_TABLE_SCENARIOS') && str_contains($helper, 'M360_SOFT_RUN_TABLE_EVENTS') && str_contains($helper, 'M360_SOFT_RUN_TABLE_CHECKLIST'));
$results[] = p9s_pass('Helper demo prefix constant', str_contains($helper, "M360_SOFT_RUN_DEMO_PREFIX = 'M360-DEMO'"));

$pass = 0; $fail = 0;
echo "# P9 Soft Run Schema Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
