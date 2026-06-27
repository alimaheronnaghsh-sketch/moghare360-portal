<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-final-invoice-helper.php';

function p7g_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$helper = file_get_contents($root . '/public_html/includes/m360-final-invoice-helper.php') ?: '';

$results = [];
$results[] = p7g_pass('Helper required', is_file($root . '/public_html/includes/m360-final-invoice-helper.php'));
$results[] = p7g_pass('Delivery ready fn', function_exists('m360_p7_is_delivery_ready'));
$results[] = p7g_pass('Assert gates fn', function_exists('m360_p7_assert_gates'));
$results[] = p7g_pass(
    'Block event constant',
    str_contains($helper, 'JOBCARD_DELIVERY_BLOCKED_GATE') || str_contains($helper, 'FINAL_INVOICE_FINALIZE_BLOCKED_GATE')
);
$results[] = p7g_pass('Contract gate', str_contains($helper, 'm360_contract_can_continue_to_p2'));
$results[] = p7g_pass('P5 ready check', str_contains($helper, 'technical_completion_notes'));
$results[] = p7g_pass('QC pass check', str_contains($helper, 'M360_QC_PASSED') && str_contains($helper, 'M360_QC_DELIVERY_READY'));

$rowReady = ['qc_status' => 'DELIVERY_READY', 'delivery_readiness_status' => '', 'jobcard_status' => ''];
$results[] = p7g_pass('Delivery ready accepted', m360_p7_is_delivery_ready($rowReady));

$rowNotReady = ['qc_status' => 'QC_IN_PROGRESS', 'delivery_readiness_status' => 'PENDING', 'jobcard_status' => 'IN_WORK'];
$results[] = p7g_pass('Non-ready rejected', !m360_p7_is_delivery_ready($rowNotReady));

$rowReadiness = ['qc_status' => '', 'delivery_readiness_status' => 'READY', 'jobcard_status' => ''];
$results[] = p7g_pass('Readiness READY accepted', m360_p7_is_delivery_ready($rowReadiness));

$rowNoNotes = [
    'qc_status' => 'DELIVERY_READY',
    'delivery_readiness_status' => 'READY',
    'jobcard_status' => 'DELIVERY_READY',
    'estimate_status' => 'APPROVED_FOR_WORK',
    'technical_completion_notes' => '',
    'work_execution_status' => 'READY_FOR_QC',
];
$gateMsg = m360_p7_assert_gates(null, 1, $rowNoNotes);
$results[] = p7g_pass('Missing notes blocked', !$gateMsg['ok']);
$results[] = p7g_pass('Blocked gate event returned', ($gateMsg['block_event'] ?? '') === 'FINAL_INVOICE_FINALIZE_BLOCKED_GATE');

$pass = 0; $fail = 0;
echo "# P7 Delivery Gate Enforcement Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
