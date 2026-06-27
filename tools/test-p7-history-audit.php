<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$fi = file_get_contents($root . '/public_html/includes/m360-final-invoice-helper.php') ?: '';
$settle = file_get_contents($root . '/public_html/includes/m360-settlement-helper.php') ?: '';
$del = file_get_contents($root . '/public_html/includes/m360-customer-delivery-helper.php') ?: '';
$close = file_get_contents($root . '/public_html/includes/m360-jobcard-close-helper.php') ?: '';
$migration = file_get_contents($root . '/database/migrations/P7_final_invoice_settlement_customer_delivery.sql') ?: '';
$all = $fi . $settle . $del . $close;

function p7h_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$historyEvents = [
    'JOBCARD_FINAL_INVOICE_CREATED',
    'JOBCARD_FINAL_INVOICE_CALCULATED',
    'JOBCARD_FINAL_INVOICE_FINALIZED',
    'JOBCARD_FINAL_INVOICE_FINALIZE_BLOCKED_GATE',
    'JOBCARD_FINAL_INVOICE_CANCELLED',
    'JOBCARD_SETTLEMENT_RECALCULATED',
    'JOBCARD_DELIVERY_REVIEWED_BY_CUSTOMER',
    'JOBCARD_DELIVERY_OTP_SENT',
    'JOBCARD_DELIVERY_SIGNED',
    'JOBCARD_VEHICLE_RELEASED',
    'JOBCARD_CLOSED',
];

$deliveryEvents = [
    'FINAL_INVOICE_CREATED',
    'FINAL_INVOICE_CALCULATED',
    'FINAL_INVOICE_MANUAL_ITEM_ADDED',
    'FINAL_INVOICE_DISCOUNT_APPLIED',
    'FINAL_INVOICE_FINALIZED',
    'FINAL_INVOICE_FINALIZE_BLOCKED_GATE',
    'FINAL_INVOICE_CANCELLED',
    'CUSTOMER_DELIVERY_REVIEWED',
    'CUSTOMER_DELIVERY_OTP_SENT',
    'CUSTOMER_DELIVERY_SIGNED',
    'SETTLEMENT_RECALCULATED',
    'SETTLEMENT_MANAGER_RELEASE_APPROVED',
    'SETTLEMENT_SETTLED',
    'SETTLEMENT_DELIVERY_BLOCKED',
    'VEHICLE_RELEASED_TO_CUSTOMER',
    'JOBCARD_CLOSED',
];

$results = [];
foreach ($historyEvents as $ev) {
    $results[] = p7h_pass('History: ' . $ev, str_contains($all, $ev));
}
foreach ($deliveryEvents as $ev) {
    $results[] = p7h_pass('Event: ' . $ev, str_contains($all, $ev));
}
$results[] = p7h_pass('JobCard history path', str_contains($fi, 'erp_jobcard_change_history'));
$results[] = p7h_pass('Delivery events table', str_contains($fi, 'erp_delivery_events'));
$results[] = p7h_pass('No destructive SQL in migration', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p7h_pass('staff-login untouched', is_file($root . '/public_html/staff-login.php'));
$results[] = p7h_pass('No credential leak', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $all));

$pass = 0; $fail = 0;
echo "# P7 History Audit Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
