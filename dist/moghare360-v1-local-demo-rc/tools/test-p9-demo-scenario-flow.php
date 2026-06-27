<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';
$scenarioHelper = $public . '/includes/m360-demo-scenario-helper.php';
$demoPage = $public . '/erp-end-to-end-demo-scenario.php';
$flowMap = $public . '/erp-demo-flow-map.php';
$api = $public . '/api/soft-run/demo-scenario-status.php';

require_once $scenarioHelper;

function p9f_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p9f_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$stages = m360_demo_scenario_stages();
$codes = array_column($stages, 'stage_code');
$phases = array_unique(array_column($stages, 'phase'));

$results = [];
$results[] = p9f_pass('Scenario helper exists', is_file($scenarioHelper));
$results[] = p9f_pass('Demo scenario page exists', is_file($demoPage));
$results[] = p9f_pass('Demo flow map exists', is_file($flowMap));
$results[] = p9f_pass('Demo scenario API exists', is_file($api));
$results[] = p9f_pass('Stage count P1-P8', count($stages) >= 18);
$results[] = p9f_pass('ONLINE_REQUEST stage', in_array('ONLINE_REQUEST', $codes, true));
$results[] = p9f_pass('CONTRACT stage', in_array('CONTRACT', $codes, true));
$results[] = p9f_pass('MANAGEMENT_DASHBOARD stage', in_array('MANAGEMENT_DASHBOARD', $codes, true));
$results[] = p9f_pass('JOBCARD_CLOSED stage', in_array('JOBCARD_CLOSED', $codes, true));
$results[] = p9f_pass('P1 through P8 phases', in_array('P1', $phases, true) && in_array('P8', $phases, true));
$results[] = p9f_pass('m360_demo_scenario_status exists', function_exists('m360_demo_scenario_status'));
$results[] = p9f_pass('m360_demo_stage_page_link exists', function_exists('m360_demo_stage_page_link'));
$offline = m360_demo_scenario_status(false, 0);
$results[] = p9f_pass('Offline status returns stages', count($offline) === count($stages));
$results[] = p9f_pass('Demo page renders stage table', str_contains(p9f_read($demoPage), 'stage_status') || str_contains(p9f_read($demoPage), 'm360_demo_scenario_status'));
$results[] = p9f_pass('Flow map links operational pages', str_contains(p9f_read($flowMap), 'm360_demo_stage_page_link'));
$results[] = p9f_pass('API GET only', str_contains(p9f_read($api), "!== 'GET'"));
$results[] = p9f_pass('API uses demo prefix', str_contains(p9f_read($api), 'M360_SOFT_RUN_DEMO_PREFIX'));
$results[] = p9f_pass('No bypass in scenario helper', !preg_match('/skip.*gate|bypass.*gate/i', p9f_read($scenarioHelper)));

$pass = 0; $fail = 0;
echo "# P9 Demo Scenario Flow Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
