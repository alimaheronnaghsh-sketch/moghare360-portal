<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-qc-helper.php';

function p6g_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$helper = file_get_contents($root . '/public_html/includes/m360-qc-helper.php') ?: '';

$results = [];
$results[] = p6g_pass('Gate function', function_exists('m360_qc_assert_gates'));
$results[] = p6g_pass('Block event', str_contains($helper, 'JOBCARD_QC_BLOCKED_GATE'));
$results[] = p6g_pass('Contract gate', str_contains($helper, 'm360_contract_can_continue_to_p2'));
$results[] = p6g_pass('P5 ready check', str_contains($helper, 'm360_qc_is_p5_ready'));
$results[] = p6g_pass('Technical notes check', str_contains($helper, 'technical_completion_notes'));
$results[] = p6g_pass('READY_FOR_QC', str_contains($helper, 'READY_FOR_QC'));

$rowBad = ['work_execution_status' => 'WORK_STARTED', 'jobcard_status' => 'RECEIVED', 'qc_status' => ''];
$results[] = p6g_pass('Non-ready blocked', !m360_qc_is_p5_ready($rowBad));

$rowGood = ['work_execution_status' => 'READY_FOR_QC', 'jobcard_status' => 'READY_FOR_QC', 'qc_status' => ''];
$results[] = p6g_pass('Ready accepted', m360_qc_is_p5_ready($rowGood));

$rowNoNotes = ['work_execution_status' => 'READY_FOR_QC', 'technical_completion_notes' => '', 'estimate_status' => 'APPROVED_FOR_WORK'];
$gateMsg = m360_qc_assert_gates(false, 1, $rowNoNotes);
$results[] = p6g_pass('Missing notes blocked', !$gateMsg['ok']);

$pass = 0; $fail = 0;
echo "# P6 QC Gate Enforcement Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
