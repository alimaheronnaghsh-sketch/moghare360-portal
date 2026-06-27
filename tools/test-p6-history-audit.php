<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$qc = file_get_contents($root . '/public_html/includes/m360-qc-helper.php') ?: '';
$migration = file_get_contents($root . '/database/migrations/P6_qc_final_inspection_delivery_readiness.sql') ?: '';

function p6h_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$events = [
    'JOBCARD_QC_STARTED', 'JOBCARD_QC_CHECKLIST_ITEM_SAVED', 'JOBCARD_FINAL_INSPECTION_NOTES_SAVED',
    'JOBCARD_QC_FAILED', 'JOBCARD_REWORK_REQUIRED', 'JOBCARD_REWORK_COMPLETED', 'JOBCARD_QC_PASSED',
    'JOBCARD_DELIVERY_READY', 'JOBCARD_QC_BLOCKED_GATE', 'JOBCARD_QC_ON_HOLD', 'JOBCARD_QC_CANCELLED',
    'QC_STARTED', 'QC_CHECKLIST_ITEM_SAVED', 'QC_FINAL_INSPECTION_NOTES_SAVED', 'QC_FAILED',
    'QC_REWORK_REQUIRED', 'QC_REWORK_COMPLETED', 'QC_PASSED', 'QC_DELIVERY_READY', 'QC_BLOCKED_GATE',
    'QC_ON_HOLD', 'QC_CANCELLED',
];

$results = [];
foreach ($events as $ev) {
    $results[] = p6h_pass('Event: ' . $ev, str_contains($qc, $ev));
}
$results[] = p6h_pass('JobCard history path', str_contains($qc, 'erp_jobcard_change_history'));
$results[] = p6h_pass('QC events table', str_contains($qc, 'erp_qc_events'));
$results[] = p6h_pass('No destructive SQL', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p6h_pass('staff-login untouched', is_file($root . '/public_html/staff-login.php'));
$results[] = p6h_pass('No credential leak', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $qc));

$pass = 0; $fail = 0;
echo "# P6 History Audit Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
