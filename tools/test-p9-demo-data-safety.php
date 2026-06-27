<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';
$migration = is_file($root . '/database/migrations/P9_end_to_end_soft_run.sql')
    ? (string)file_get_contents($root . '/database/migrations/P9_end_to_end_soft_run.sql')
    : '';

$p9Files = [
    'includes/m360-soft-run-helper.php',
    'includes/m360-demo-scenario-helper.php',
    'includes/m360-demo-readiness-helper.php',
    'includes/m360-e2e-validation-helper.php',
    'erp-soft-run-control-center.php',
    'erp-end-to-end-demo-scenario.php',
    'erp-soft-run-checklist.php',
    'erp-demo-flow-map.php',
    'erp-demo-readiness-report.php',
    'api/soft-run/readiness-summary.php',
    'api/soft-run/demo-scenario-status.php',
];

function p9d_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p9d_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$all = '';
foreach ($p9Files as $rel) {
    $all .= p9d_read($public . '/' . $rel);
}
$readiness = p9d_read($public . '/includes/m360-demo-readiness-helper.php');
$softRun = p9d_read($public . '/includes/m360-soft-run-helper.php');

$results = [];
$results[] = p9d_pass('M360-DEMO prefix constant', str_contains($softRun, "M360_SOFT_RUN_DEMO_PREFIX = 'M360-DEMO'"));
$results[] = p9d_pass('Demo marker helper', str_contains($softRun, 'm360_soft_run_is_demo_marker'));
$results[] = p9d_pass('Find demo jobcard by prefix', str_contains($softRun, "LIKE N'M360-DEMO%'"));
$results[] = p9d_pass('Scenario code M360-DEMO-E2E-V1', str_contains($softRun, 'M360-DEMO-E2E-V1'));
$results[] = p9d_pass('Migration no operational mutation', !preg_match('/\b(INSERT|UPDATE)\s+(INTO|dbo\.)/i', $migration));
$results[] = p9d_pass('No DELETE operational in P9 PHP', !preg_match('/\bDELETE\s+FROM\s+dbo\.(erp_jobcards|erp_estimates|erp_final_invoices)/i', $all));
$results[] = p9d_pass('Checklist mutation scoped to soft_run_checklist', str_contains($readiness, 'M360_SOFT_RUN_TABLE_CHECKLIST') && str_contains($readiness, 'm360_readiness_update_checklist_item'));
$results[] = p9d_pass('No operational table INSERT in P9', !preg_match('/INSERT\s+INTO\s+dbo\.erp_(jobcards|estimates|final_invoices|settlement|qc_|intake)/i', $all));
$results[] = p9d_pass('No operational table UPDATE in P9', !preg_match('/UPDATE\s+dbo\.erp_(jobcards|estimates|final_invoices|settlement|qc_|intake)/i', $all));
$results[] = p9d_pass('E2E helper read-only', !preg_match('/\b(INSERT|UPDATE|DELETE)\s+/i', p9d_read($public . '/includes/m360-e2e-validation-helper.php')));
$results[] = p9d_pass('Soft run helper read-only', !preg_match('/\b(INSERT|UPDATE|DELETE)\s+/i', $softRun));
$results[] = p9d_pass('Scenario helper read-only', !preg_match('/\b(INSERT|UPDATE|DELETE)\s+/i', p9d_read($public . '/includes/m360-demo-scenario-helper.php')));
$results[] = p9d_pass('Checklist page documents scope', str_contains(p9d_read($public . '/erp-soft-run-checklist.php'), 'erp_soft_run_checklist'));
$results[] = p9d_pass('No fake production OTP in P9', str_contains($readiness, 'no_fake_production_otp'));
$results[] = p9d_pass('Demo data category in readiness', str_contains($softRun, 'demo_data'));
$results[] = p9d_pass('No workflow mutation flag', str_contains($readiness, 'no_workflow_mutation'));

$pass = 0; $fail = 0;
echo "# P9 Demo Data Safety Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
