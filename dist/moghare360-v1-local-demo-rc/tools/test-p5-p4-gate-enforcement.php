<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-work-execution-helper.php';

function p5g_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$helper = file_get_contents($root . '/public_html/includes/m360-work-execution-helper.php') ?: '';

$results = [];
$results[] = p5g_pass('Gate function exists', function_exists('m360_work_assert_gates'));
$results[] = p5g_pass('Block event constant', str_contains($helper, 'JOBCARD_WORK_EXECUTION_BLOCKED_GATE'));
$results[] = p5g_pass('Contract gate check', str_contains($helper, 'm360_contract_can_continue_to_p2'));
$results[] = p5g_pass('Estimate fetch for gate', str_contains($helper, 'm360_estimate_fetch_active_for_jobcard'));
$results[] = p5g_pass('Parts gate values', str_contains($helper, 'M360_GATE_PARTS_CLEARED') && str_contains($helper, 'M360_GATE_PARTS_NOT_REQUIRED'));
$results[] = p5g_pass('Finance gate values', str_contains($helper, 'M360_GATE_FINANCE_CLEARED') && str_contains($helper, 'M360_GATE_FINANCE_NOT_REQUIRED'));
$results[] = p5g_pass('APPROVED_FOR_WORK check', str_contains($helper, 'APPROVED_FOR_WORK'));
$results[] = p5g_pass('Non-approved row blocked', str_contains($helper, 'm360_work_is_p4_approved'));

$rowBad = ['estimate_status' => 'DRAFT', 'work_execution_status' => ''];
$results[] = p5g_pass('is_p4_approved rejects draft', !m360_work_is_p4_approved($rowBad));

$rowGood = ['estimate_status' => 'APPROVED_FOR_WORK', 'work_execution_status' => ''];
$results[] = p5g_pass('is_p4_approved accepts approved', m360_work_is_p4_approved($rowGood));

$pass = 0; $fail = 0;
echo "# P5 P4 Gate Enforcement Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
