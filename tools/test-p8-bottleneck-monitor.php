<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-bottleneck-helper.php';

function p8b_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$stages = m360_bottleneck_stages();
$summary = m360_bottleneck_summary(false);
$stageData = $summary['stages'][M360_MGMT_STAGE_QC] ?? [];

$overdueRow = [
    'jobcard_status' => 'OPEN',
    'updated_at' => gmdate('Y-m-d H:i:s', time() - (50 * 3600)),
    'created_at' => gmdate('Y-m-d H:i:s', time() - (50 * 3600)),
];
$enriched = m360_mgmt_enrich_pipeline_row($overdueRow);
$conflictRow = [
    'jobcard_status' => 'CLOSED',
    'customer_delivery_status' => 'PENDING',
    'vehicle_released_at' => null,
];
$conflicts = m360_mgmt_status_conflicts($conflictRow);

$results = [];
$results[] = p8b_pass('Bottleneck helper loaded', function_exists('m360_bottleneck_stages'));
$results[] = p8b_pass('Stages non-empty', count($stages) >= 10);
$results[] = p8b_pass('Stages include QC', in_array(M360_MGMT_STAGE_QC, $stages, true));
$results[] = p8b_pass('Summary stages key', isset($summary['stages']) && is_array($summary['stages']));
$results[] = p8b_pass('Summary pressure keys', array_key_exists('highest_pressure_stage', $summary) && array_key_exists('highest_pressure_count', $summary));
$results[] = p8b_pass('Stage structure count', array_key_exists('count', $stageData) && array_key_exists('avg_age_hours', $stageData));
$results[] = p8b_pass('Stage structure oldest', array_key_exists('oldest_jobcard_id', $stageData) && array_key_exists('stuck_rows', $stageData));
$results[] = p8b_pass('Overdue flag 48h', !empty($enriched['is_overdue_48']));
$results[] = p8b_pass('Overdue flag 24h', !empty($enriched['is_overdue_24']));
$results[] = p8b_pass('Status conflict CLOSED_WITHOUT_RELEASE', in_array('CLOSED_WITHOUT_RELEASE', $conflicts, true));
$results[] = p8b_pass('Risk flags include conflict', str_contains(implode(',', m360_mgmt_risk_flags($conflictRow)), 'STATUS_CONFLICT'));
$results[] = p8b_pass('Stuck list null-safe', is_array(m360_bottleneck_stuck_list(false, 5)));

$pass = 0; $fail = 0;
echo "# P8 Bottleneck Monitor Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
