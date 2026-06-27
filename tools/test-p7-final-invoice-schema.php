<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$migration = file_get_contents($root . '/database/migrations/P7_final_invoice_settlement_customer_delivery.sql') ?: '';
$fi = file_get_contents($root . '/public_html/includes/m360-final-invoice-helper.php') ?: '';

function p7s_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p7s_pass('Migration exists', is_file($root . '/database/migrations/P7_final_invoice_settlement_customer_delivery.sql'));
$results[] = p7s_pass('No DROP/DELETE/TRUNCATE', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p7s_pass('Non-destructive pattern', str_contains($migration, 'IF OBJECT_ID') && str_contains($migration, 'IF COL_LENGTH'));
$results[] = p7s_pass('erp_final_invoices', str_contains($migration, 'erp_final_invoices'));
$results[] = p7s_pass('erp_final_invoice_items', str_contains($migration, 'erp_final_invoice_items'));
$results[] = p7s_pass('erp_settlement_controls', str_contains($migration, 'erp_settlement_controls'));
$results[] = p7s_pass('erp_customer_delivery_confirmations', str_contains($migration, 'erp_customer_delivery_confirmations'));
$results[] = p7s_pass('erp_delivery_events', str_contains($migration, 'erp_delivery_events'));
$results[] = p7s_pass('delivery_token_hash column', str_contains($migration, 'delivery_token_hash'));
$results[] = p7s_pass('No raw delivery_token column', !preg_match('/\bdelivery_token\b(?!_hash)/i', $migration));
$results[] = p7s_pass('signature_hash metadata', str_contains($migration, 'signature_hash'));
$results[] = p7s_pass('Jobcard P7 columns', str_contains($migration, 'final_invoice_status') && str_contains($migration, 'settlement_status'));
$results[] = p7s_pass('Helper uses token hash only', str_contains($fi, 'delivery_token_hash') && !preg_match('/delivery_token\s*=\s*\?/i', $fi));

$pass = 0; $fail = 0;
echo "# P7 Final Invoice Schema Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
