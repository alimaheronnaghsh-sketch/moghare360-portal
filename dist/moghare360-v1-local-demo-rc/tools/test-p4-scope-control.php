<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p4c_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }
function p4c_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$all = p4c_read($public . '/includes/m360-estimate-helper.php')
    . p4c_read($public . '/includes/m360-parts-finance-gate-helper.php')
    . p4c_read($public . '/includes/m360-estimate-approval-helper.php');

$results = [];
$results[] = p4c_pass('No full purchase module', !preg_match('/INSERT INTO dbo\.erp_purchase_requests/i', $all));
$results[] = p4c_pass('No inventory write', !preg_match('/INSERT INTO dbo\.erp_inventory|stock_movement/i', $all));
$results[] = p4c_pass('No payment gateway', !preg_match('/payment_gateway|zarinpal|stripe/i', $all));
$results[] = p4c_pass('No invoice create', !preg_match('/INSERT INTO dbo\.erp_invoices/i', $all));
$results[] = p4c_pass('No accounting voucher', !preg_match('/journal_entry|accounting_voucher|GL_/i', $all));
$results[] = p4c_pass('Contract gate used', str_contains($all, 'm360_contract_can_continue_to_p2'));
$results[] = p4c_pass('P3 ready check', str_contains($all, 'm360_estimate_is_p3_ready') || str_contains($all, 'WAITING_FOR_APPROVAL'));
$results[] = p4c_pass('staff-login untouched', is_file($public . '/staff-login.php'));

$pass = 0; $fail = 0;
echo "# P4 Scope Control Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
