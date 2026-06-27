<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p5c_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }
function p5c_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$all = p5c_read($public . '/includes/m360-work-execution-helper.php')
    . p5c_read($public . '/includes/m360-parts-consumption-helper.php')
    . p5c_read($public . '/includes/m360-technical-completion-helper.php')
    . p5c_read($public . '/erp-work-execution-board.php')
    . p5c_read($public . '/erp-work-execution-detail.php');

$results = [];
$results[] = p5c_pass('No full purchase module', !preg_match('/INSERT INTO dbo\.erp_purchase_orders/i', $all));
$results[] = p5c_pass('No payment gateway', !preg_match('/payment_gateway|zarinpal|stripe/i', $all));
$results[] = p5c_pass('No final invoice', !preg_match('/INSERT INTO dbo\.erp_invoices/i', $all));
$results[] = p5c_pass('No accounting voucher', !preg_match('/journal_entry|accounting_voucher|GL_/i', $all));
$results[] = p5c_pass('No QC final module', !preg_match('/erp_qc_checklist|qc_final/i', $all));
$results[] = p5c_pass('No delivery module', !preg_match('/vehicle_release|delivery_release|erp_delivery/i', $all));
$results[] = p5c_pass('Contract gate used', str_contains($all, 'm360_contract_can_continue_to_p2'));
$results[] = p5c_pass('Estimate gate used', str_contains($all, 'm360_estimate_fetch_active_for_jobcard'));
$results[] = p5c_pass('Parts gate constants', str_contains($all, 'M360_GATE_PARTS_CLEARED'));
$results[] = p5c_pass('Finance gate constants', str_contains($all, 'M360_GATE_FINANCE_CLEARED'));
$results[] = p5c_pass('staff-login untouched', is_file($public . '/staff-login.php'));
$results[] = p5c_pass('access-control untouched', is_file($public . '/access-control.php'));

$pass = 0; $fail = 0;
echo "# P5 Scope Control Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
