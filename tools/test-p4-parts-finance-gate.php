<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

require_once $public . '/includes/m360-parts-finance-gate-helper.php';

function p4g_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p4g_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$helper = p4g_read($public . '/includes/m360-estimate-helper.php');
$gate = p4g_read($public . '/includes/m360-parts-finance-gate-helper.php');

$results = [];
$results[] = p4g_pass('Parts gate evaluate', str_contains($gate, 'm360_parts_gate_evaluate'));
$results[] = p4g_pass('Finance gate evaluate', str_contains($gate, 'm360_finance_gate_evaluate'));
$results[] = p4g_pass('Approve for work gate', str_contains($gate, 'm360_gates_can_approve_for_work'));
$results[] = p4g_pass('Blocked gate event', str_contains($helper, 'ESTIMATE_APPROVED_FOR_WORK_BLOCKED_GATE'));
$results[] = p4g_pass('PART item triggers parts', str_contains($helper, "item_type = N'PART'") || str_contains($helper, "=== 'PART'"));
$results[] = p4g_pass('50% advance', str_contains($gate, '0.5') || str_contains($gate, 'M360_FINANCE_ADVANCE_MIN_PERCENT'));
$results[] = p4g_pass('10M rounding', str_contains($gate, '10000000'));
$adv = m360_finance_calculate_advance(100000000);
$results[] = p4g_pass('Runtime advance calc', $adv === 50000000.0);
$results[] = p4g_pass('No payment gateway', !preg_match('/stripe|zarinpal|gateway/i', $helper . $gate));
$results[] = p4g_pass('No invoice module', !preg_match('/create_invoice|erp_invoices.*INSERT/i', $helper));

$pass = 0; $fail = 0;
echo "# P4 Parts Finance Gate Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
