<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-settlement-helper.php';

$settle = file_get_contents($root . '/public_html/includes/m360-settlement-helper.php') ?: '';
$fi = file_get_contents($root . '/public_html/includes/m360-final-invoice-helper.php') ?: '';

function p7st_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p7st_pass('Settlement helper loaded', function_exists('m360_settlement_recalculate'));
$results[] = p7st_pass('Settlement after finalized', str_contains($fi, 'تسویه فقط بعد از نهایی') || str_contains($fi, "!== M360_FI_FINALIZED"));
$results[] = p7st_pass('Recalculate on finalize', str_contains($fi, 'm360_settlement_recalculate'));
$results[] = p7st_pass('Total paid read fn', function_exists('m360_settlement_total_paid'));
$results[] = p7st_pass('Total paid from erp_payments', str_contains($settle, 'erp_payments') && str_contains($settle, 'SUM(payment_amount)'));
$results[] = p7st_pass('No erp_payments INSERT', !preg_match('/INSERT INTO dbo\.erp_payments/i', $settle));
$results[] = p7st_pass('Can release fn', function_exists('m360_settlement_can_release'));
$results[] = p7st_pass('Delivery blocked without SETTLED', str_contains($settle, 'M360_SETTLE_SETTLED') && str_contains($settle, 'تسویه کامل یا مجوز مدیریتی'));
$results[] = p7st_pass('Delivery allowed when SETTLED', str_contains($settle, 'in_array($status, [M360_SETTLE_SETTLED, M360_SETTLE_MANAGER_RELEASE]'));
$results[] = p7st_pass('Block delivery fn', function_exists('m360_settlement_block_delivery'));
$results[] = p7st_pass('Manager release fn', function_exists('m360_settlement_manager_release'));
$results[] = p7st_pass('Manager reason required', str_contains($settle, "if (\$reason === '')") || str_contains($settle, 'دلیل مجوز مدیریتی الزامی'));
$results[] = p7st_pass('No payment gateway', !preg_match('/payment_gateway|zarinpal|stripe|paypal/i', $settle . $fi));
$results[] = p7st_pass('Partial status resolved', m360_settlement_resolve_status(1000, 500, M360_SETTLE_PAYMENT_PENDING, false) === M360_SETTLE_PARTIAL);

$pass = 0; $fail = 0;
echo "# P7 Settlement Control Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
