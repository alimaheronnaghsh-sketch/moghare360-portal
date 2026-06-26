<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p3h_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p3h_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$helper = p3h_read($public . '/includes/m360-technical-operation-helper.php');
$wf = p3h_read($public . '/includes/m360-technician-workflow-helper.php');
$migration = p3h_read($root . '/database/migrations/P3_technical_operation_workflow.sql');

$events = [
    'JOBCARD_MOVED_TO_TECHNICAL_QUEUE',
    'JOBCARD_TECHNICIAN_ASSIGNED',
    'JOBCARD_DIAGNOSIS_STARTED',
    'JOBCARD_TECHNICIAN_NOTES_SAVED',
    'JOBCARD_DIAGNOSIS_COMPLETED',
    'JOBCARD_SERVICE_OPERATION_CREATED',
    'JOBCARD_SERVICE_OPERATION_STARTED',
    'JOBCARD_SERVICE_OPERATION_COMPLETED',
    'JOBCARD_TECHNICAL_REVIEW',
    'JOBCARD_WAITING_FOR_APPROVAL',
    'JOBCARD_TECHNICAL_DONE',
    'JOBCARD_TECHNICAL_ON_HOLD',
    'JOBCARD_TECHNICAL_ACTION_BLOCKED_NOT_READY',
    'JOBCARD_TECHNICAL_ACTION_BLOCKED_CONTRACT_GATE',
];

$soEvents = ['SERVICE_OPERATION_CREATED', 'SERVICE_OPERATION_STARTED', 'SERVICE_OPERATION_COMPLETED'];

$results = [];
$results[] = p3h_pass('Jobcard history write', str_contains($helper, 'm360_technical_write_jobcard_history'));
$results[] = p3h_pass('Service history write', str_contains($helper, 'm360_technical_write_service_history'));
foreach ($events as $ev) {
    $results[] = p3h_pass('Event: ' . $ev, str_contains($wf, $ev) || str_contains($helper, $ev));
}
foreach ($soEvents as $ev) {
    $results[] = p3h_pass('SO Event: ' . $ev, str_contains($helper, $ev));
}
$results[] = p3h_pass('No destructive SQL', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p3h_pass('Auth core unchanged', !preg_match('/function\s+erp_auth_/', $helper));
$results[] = p3h_pass('No credential leakage', !preg_match('/api_key|password\s*=/i', $helper . $wf));

$pass = 0; $fail = 0;
echo "# MOGHARE360 P3 History Audit Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
