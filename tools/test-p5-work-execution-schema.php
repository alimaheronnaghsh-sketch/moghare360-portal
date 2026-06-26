<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$migration = file_get_contents($root . '/database/migrations/P5_work_execution_parts_consumption.sql') ?: '';
$helper = file_get_contents($root . '/public_html/includes/m360-work-execution-helper.php') ?: '';
$parts = file_get_contents($root . '/public_html/includes/m360-parts-consumption-helper.php') ?: '';

function p5s_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p5s_pass('Migration file exists', is_file($root . '/database/migrations/P5_work_execution_parts_consumption.sql'));
$results[] = p5s_pass('No DROP/DELETE/TRUNCATE', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p5s_pass('work_execution_status column', str_contains($migration, 'work_execution_status'));
$results[] = p5s_pass('work_started_at column', str_contains($migration, 'work_started_at'));
$results[] = p5s_pass('ready_for_qc_at column', str_contains($migration, 'ready_for_qc_at'));
$results[] = p5s_pass('technical_completion_notes column', str_contains($migration, 'technical_completion_notes'));
$results[] = p5s_pass('erp_work_execution_events table', str_contains($migration, 'erp_work_execution_events'));
$results[] = p5s_pass('Non-destructive IF OBJECT_ID', str_contains($migration, 'IF OBJECT_ID') && str_contains($migration, 'IF COL_LENGTH'));
$results[] = p5s_pass('No duplicate part usage table', !str_contains($migration, 'CREATE TABLE dbo.erp_jobcard_part_usage'));
$results[] = p5s_pass('Uses existing part usage', str_contains($parts, 'erp_jobcard_part_usage'));
$results[] = p5s_pass('Work execution statuses defined', str_contains($helper, 'WORK_STARTED') && str_contains($helper, 'READY_FOR_QC'));

$pass = 0; $fail = 0;
echo "# P5 Work Execution Schema Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
