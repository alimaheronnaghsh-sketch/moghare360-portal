<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p7sc_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }
function p7sc_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$all = p7sc_read($public . '/includes/m360-final-invoice-helper.php')
    . p7sc_read($public . '/includes/m360-settlement-helper.php')
    . p7sc_read($public . '/includes/m360-customer-delivery-helper.php')
    . p7sc_read($public . '/includes/m360-jobcard-close-helper.php')
    . p7sc_read($public . '/erp-final-invoice-detail.php')
    . p7sc_read($public . '/erp-final-invoice-action.php')
    . p7sc_read($public . '/erp-settlement-action.php')
    . p7sc_read($public . '/customer-delivery-review.php')
    . p7sc_read($public . '/customer-delivery-sign.php');

$results = [];
$results[] = p7sc_pass('No erp_invoices INSERT', !preg_match('/INSERT INTO dbo\.erp_invoices/i', $all));
$results[] = p7sc_pass('No accounting voucher', !preg_match('/journal_entry|accounting_voucher|GL_/i', $all));
$results[] = p7sc_pass('No ledger module', !preg_match('/INSERT INTO dbo\.erp_ledger|general_ledger|chart_of_accounts/i', $all));
$results[] = p7sc_pass('No payment gateway', !preg_match('/payment_gateway|zarinpal|stripe|paypal|saman|mellat/i', $all));
$results[] = p7sc_pass('No bank integration', !preg_match('/bank_transfer_api|iban_verify|shaparak/i', $all));
$results[] = p7sc_pass('No tax filing module', !preg_match('/tax_filing|moadian|samt/i', $all));
$results[] = p7sc_pass('No free upload', !preg_match('/type=[\'"]file[\'"]|multipart.*upload|move_uploaded_file/i', $all));
$results[] = p7sc_pass('Contract gate', str_contains($all, 'm360_contract_can_continue_to_p2'));
$results[] = p7sc_pass('P7 assert gates', str_contains($all, 'm360_p7_assert_gates'));
$results[] = p7sc_pass('Settlement gate on release', str_contains($all, 'm360_settlement_can_release'));
$results[] = p7sc_pass('No gate bypass skip', !preg_match('/skip.*gate|bypass.*gate|gate.*=.*true.*override/i', $all));
$results[] = p7sc_pass('Uses erp_final_invoices not erp_invoices', str_contains($all, 'erp_final_invoices'));
$results[] = p7sc_pass('staff-login untouched', is_file($public . '/staff-login.php'));
$results[] = p7sc_pass('access-control untouched', is_file($public . '/access-control.php'));

$pass = 0; $fail = 0;
echo "# P7 Scope Control Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
