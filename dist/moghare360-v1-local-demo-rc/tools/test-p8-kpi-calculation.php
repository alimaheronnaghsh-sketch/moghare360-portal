<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-management-kpi-helper.php';

function p8k_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p8k_pass('KPI helper loaded', function_exists('m360_mgmt_safe_div'));
$results[] = p8k_pass('safe_div zero denominator', m360_mgmt_safe_div(10.0, 0.0) === 0.0);
$results[] = p8k_pass('safe_div normal', m360_mgmt_safe_div(10.0, 4.0) === 2.5);
$results[] = p8k_pass('safe_div negative denominator', m360_mgmt_safe_div(5.0, -2.0) === 0.0);

$allWindow = m360_mgmt_period_window('all');
$results[] = p8k_pass(
    'period all window',
    array_key_exists('from', $allWindow) && $allWindow['from'] === null && ($allWindow['sql_from'] ?? 'x') === ''
);

$todayWindow = m360_mgmt_period_window('today');
$results[] = p8k_pass('period today window', ($todayWindow['from'] ?? '') !== '' && str_contains((string)($todayWindow['sql_from'] ?? ''), 'created_at'));
$results[] = p8k_pass('period 7d window', str_contains((string)(m360_mgmt_period_window('7d')['sql_from'] ?? ''), 'created_at'));
$results[] = p8k_pass('period 30d window', str_contains((string)(m360_mgmt_period_window('30d')['sql_from'] ?? ''), 'created_at'));

$closedRow = ['jobcard_status' => 'CLOSED'];
$results[] = p8k_pass('resolve stage closed', m360_mgmt_resolve_stage($closedRow)['stage'] === M360_MGMT_STAGE_CLOSED);

$receptionRow = ['jobcard_status' => 'OPEN', 'estimate_status' => '', 'technical_status' => ''];
$results[] = p8k_pass('resolve stage reception', m360_mgmt_resolve_stage($receptionRow)['stage'] === M360_MGMT_STAGE_RECEPTION);

$settleRow = [
    'jobcard_status' => 'OPEN',
    'final_invoice_status' => 'FINALIZED',
    'settlement_status' => 'PAYMENT_PENDING',
];
$results[] = p8k_pass('resolve stage settlement', m360_mgmt_resolve_stage($settleRow)['stage'] === M360_MGMT_STAGE_SETTLEMENT);

$results[] = p8k_pass('age_hours null-safe', m360_mgmt_age_hours(null) === 0.0);
$results[] = p8k_pass('age_hours empty-safe', m360_mgmt_age_hours('') === 0.0);
$results[] = p8k_pass('scalar no conn', m360_mgmt_scalar(false, 'SELECT 1') === 0.0);

$intake = m360_mgmt_kpi_intake(false, m360_mgmt_period_window('today'));
$results[] = p8k_pass('intake null-safe conn', ($intake['conversion_rate'] ?? -1) === 0.0 && ($intake['today'] ?? -1) === 0);

$pass = 0; $fail = 0;
echo "# P8 KPI Calculation Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
