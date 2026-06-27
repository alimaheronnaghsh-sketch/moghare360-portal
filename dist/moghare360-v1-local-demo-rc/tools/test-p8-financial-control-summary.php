<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-financial-control-helper.php';

$fin = is_file($root . '/public_html/includes/m360-financial-control-helper.php')
    ? (string)file_get_contents($root . '/public_html/includes/m360-financial-control-helper.php')
    : '';

function p8f_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$expectedKeys = [
    'final_invoice_total',
    'paid_total',
    'remaining_total',
    'settlement_pending_count',
    'partial_settled_count',
    'settled_count',
    'manager_release_count',
    'released_with_balance_count',
    'delivery_ready_unpaid_count',
    'variance_cases_count',
    'balance_case_count',
    'read_only',
];

$summary = m360_financial_control_summary(false);
$empty = m360_financial_control_empty();

$results = [];
$results[] = p8f_pass('Financial helper loaded', function_exists('m360_financial_control_summary'));
foreach ($expectedKeys as $key) {
    $results[] = p8f_pass('Summary key: ' . $key, array_key_exists($key, $summary));
}
$results[] = p8f_pass('Empty helper keys', array_key_exists('final_invoice_total', $empty) && array_key_exists('read_only', $empty));
$results[] = p8f_pass('Summary read_only true', ($summary['read_only'] ?? false) === true);
$results[] = p8f_pass('No payment gateway integration', !preg_match('/zarinpal|stripe|paypal|saman|mellat|payment_gateway_api/i', $fin));
$results[] = p8f_pass('No accounting voucher write', !preg_match('/INSERT INTO dbo\.(erp_ledger|erp_vouchers|journal_entries)|journal_entry_create|accounting_voucher_post/i', $fin));
$results[] = p8f_pass('No erp_payments INSERT', !preg_match('/INSERT INTO dbo\.erp_payments/i', $fin));
$results[] = p8f_pass('Explicit no gateway flag', str_contains($fin, 'no_payment_gateway'));
$results[] = p8f_pass('Explicit no voucher flag', str_contains($fin, 'no_accounting_voucher'));
$results[] = p8f_pass('Financial page exists', is_file($root . '/public_html/erp-financial-control-summary.php'));

$pass = 0; $fail = 0;
echo "# P8 Financial Control Summary Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
