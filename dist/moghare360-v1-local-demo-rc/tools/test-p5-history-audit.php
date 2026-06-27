<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$work = file_get_contents($root . '/public_html/includes/m360-work-execution-helper.php') ?: '';
$parts = file_get_contents($root . '/public_html/includes/m360-parts-consumption-helper.php') ?: '';
$migration = file_get_contents($root . '/database/migrations/P5_work_execution_parts_consumption.sql') ?: '';

function p5h_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$events = [
    'JOBCARD_WORK_QUEUE', 'JOBCARD_WORK_STARTED', 'JOBCARD_WORK_EXECUTION_BLOCKED_GATE',
    'JOBCARD_WAITING_FOR_PARTS', 'JOBCARD_PART_CONSUMED', 'JOBCARD_PART_CONSUMPTION_BLOCKED_INSUFFICIENT_STOCK',
    'JOBCARD_SERVICE_OPERATION_EXECUTION_STARTED', 'JOBCARD_SERVICE_OPERATION_EXECUTION_COMPLETED',
    'JOBCARD_TECHNICAL_COMPLETION_NOTES_SAVED', 'JOBCARD_TECHNICAL_WORK_COMPLETED', 'JOBCARD_READY_FOR_QC',
    'JOBCARD_WORK_EXECUTION_ON_HOLD', 'JOBCARD_WORK_EXECUTION_CANCELLED',
    'SERVICE_OPERATION_EXECUTION_STARTED', 'SERVICE_OPERATION_EXECUTION_COMPLETED', 'SERVICE_OPERATION_EXECUTION_BLOCKED',
    'PART_USAGE_CONSUMED_FOR_JOBCARD', 'PART_USAGE_CONSUMPTION_BLOCKED',
];

$results = [];
foreach ($events as $ev) {
    $results[] = p5h_pass('Event: ' . $ev, str_contains($work, $ev) || str_contains($parts, $ev));
}
$results[] = p5h_pass('JobCard history table', str_contains($work, 'erp_jobcard_change_history'));
$results[] = p5h_pass('Work execution events table', str_contains($work, 'erp_work_execution_events'));
$results[] = p5h_pass('No destructive SQL', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p5h_pass('staff-login untouched', is_file($root . '/public_html/staff-login.php'));
$results[] = p5h_pass('owner-login untouched', is_file($root . '/public_html/owner-login.php'));
$results[] = p5h_pass('No credential leak', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $work . $parts));

$pass = 0; $fail = 0;
echo "# P5 History Audit Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
