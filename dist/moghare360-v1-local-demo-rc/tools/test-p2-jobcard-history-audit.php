<?php
declare(strict_types=1);

/**
 * MOGHARE360 P2 — JobCard history / audit tests.
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p2h_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function p2h_read(string $path): string
{
    return is_file($path) ? (string)file_get_contents($path) : '';
}

$helper = p2h_read($public . '/includes/m360-reception-jobcard-helper.php');
$workflow = p2h_read($public . '/includes/m360-jobcard-workflow-helper.php');
$migration = p2h_read($root . '/database/migrations/P2_reception_jobcard_workflow.sql');
$staffLogin = p2h_read($public . '/staff-login.php');
$accessControl = p2h_read($public . '/access-control.php');

$events = [
    'JOBCARD_ARRIVED',
    'JOBCARD_CHECKED_IN',
    'JOBCARD_CUSTOMER_COMPLAINT_SAVED',
    'JOBCARD_RECEPTION_NOTES_SAVED',
    'JOBCARD_INITIAL_INSPECTION_SAVED',
    'JOBCARD_READY_FOR_TECHNICAL',
    'JOBCARD_READY_FOR_TECHNICAL_BLOCKED_CONTRACT_UNSIGNED',
    'JOBCARD_CONTRACT_GATE_MANAGER_OVERRIDE',
    'JOBCARD_ON_HOLD',
    'JOBCARD_CANCELLED',
];

$results = [];
$results[] = p2h_pass('History write helper', str_contains($helper, 'm360_reception_jobcard_write_history'));
$results[] = p2h_pass('Uses erp_jobcard_change_history', str_contains($helper, 'erp_jobcard_change_history'));
foreach ($events as $ev) {
    $results[] = p2h_pass('Event mapped: ' . $ev, str_contains($workflow, $ev) || str_contains($helper, $ev));
}
$results[] = p2h_pass('No destructive SQL in migration', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p2h_pass('Migration uses IF COL_LENGTH', str_contains($migration, 'COL_LENGTH'));
$results[] = p2h_pass('Auth core unchanged in helper', !preg_match('/function\s+erp_auth_/', $helper));
$results[] = p2h_pass('staff-login file exists (auth core untouched)', is_file($public . '/staff-login.php') && !preg_match('/function\s+erp_auth_/', $staffLogin . $accessControl));
$results[] = p2h_pass('No credential leakage in P2 files', !preg_match('/api_key|password\s*=/i', $helper . $workflow));

$pass = 0;
$fail = 0;
echo "# MOGHARE360 P2 JobCard History Audit Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'];
    if ($r['detail'] !== '') {
        echo ' — ' . $r['detail'];
    }
    echo "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
