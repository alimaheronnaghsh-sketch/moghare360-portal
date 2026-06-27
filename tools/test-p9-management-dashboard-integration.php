<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p9i_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p9i_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$softRun = p9i_read($public . '/includes/m360-soft-run-helper.php');
$controlCenter = p9i_read($public . '/erp-soft-run-control-center.php');
$readinessPage = p9i_read($public . '/erp-demo-readiness-report.php');
$e2e = p9i_read($public . '/includes/m360-e2e-validation-helper.php');

$p8Pages = [
    'erp-management-dashboard.php',
    'erp-owner-control-center.php',
    'erp-operational-kpi.php',
    'erp-jobcard-timeline.php',
    'erp-bottleneck-monitor.php',
    'erp-financial-control-summary.php',
];

$results = [];
$results[] = p9i_pass('Nav links to P8 dashboard', str_contains($softRun, 'erp-management-dashboard.php'));
$results[] = p9i_pass('Nav label داشبورد P8', str_contains($softRun, 'داشبورد P8'));
$results[] = p9i_pass('Phase map includes P8', str_contains($softRun, "'P8' => ['MANAGEMENT_DASHBOARD']") || str_contains($softRun, "'P8'"));
$results[] = p9i_pass('P8 phase href management dashboard', str_contains($softRun, "'P8' => 'erp-management-dashboard.php'"));
foreach ($p8Pages as $page) {
    $results[] = p9i_pass('P8 page exists: ' . $page, is_file($public . '/' . $page));
}
$results[] = p9i_pass('Control center shows readiness categories', str_contains($controlCenter, 'management') && str_contains($controlCenter, 'Management P8'));
$results[] = p9i_pass('Readiness report checks P8 views', str_contains(p9i_read($public . '/includes/m360-demo-readiness-helper.php'), 'M360_MGMT_VIEW_PIPELINE'));
$results[] = p9i_pass('Readiness page lists P9/P8 migrations', str_contains($readinessPage, 'P9') && str_contains($readinessPage, 'p8'));
$results[] = p9i_pass('E2E management stage uses pipeline view', str_contains($e2e, 'vw_m360_owner_jobcard_pipeline'));
$results[] = p9i_pass('E2E management checks timeline page', str_contains($e2e, 'erp-jobcard-timeline.php'));
$results[] = p9i_pass('Scenario MANAGEMENT_DASHBOARD stage', str_contains(p9i_read($public . '/includes/m360-demo-scenario-helper.php'), 'MANAGEMENT_DASHBOARD'));
$results[] = p9i_pass('Checklist item p8_dashboard', str_contains(p9i_read($public . '/includes/m360-demo-readiness-helper.php'), 'p8_dashboard'));
$results[] = p9i_pass('No P8 mutation from P9', !preg_match('/m360_mgmt_(approve|override|payment)/i', $softRun));

$pass = 0; $fail = 0;
echo "# P9 Management Dashboard Integration Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
