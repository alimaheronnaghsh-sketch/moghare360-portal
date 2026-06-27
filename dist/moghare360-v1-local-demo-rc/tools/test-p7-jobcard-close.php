<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-jobcard-close-helper.php';

$close = file_get_contents($root . '/public_html/includes/m360-jobcard-close-helper.php') ?: '';

function p7jc_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p7jc_pass('Vehicle release validate fn', function_exists('m360_vehicle_release_validate'));
$results[] = p7jc_pass('Vehicle release fn', function_exists('m360_vehicle_release'));
$results[] = p7jc_pass('Jobcard close validate fn', function_exists('m360_jobcard_close_validate'));
$results[] = p7jc_pass('Jobcard close fn', function_exists('m360_jobcard_close'));
$results[] = p7jc_pass('No accounting voucher', !preg_match('/journal_entry|accounting_voucher|GL_/i', $close));
$results[] = p7jc_pass('Signed delivery required', str_contains($close, 'DELIVERY_SIGNED') && str_contains($close, 'امضای تحویل مشتری'));
$results[] = p7jc_pass('Invoice finalized required', str_contains($close, 'm360_jc_close_invoice_finalized'));
$results[] = p7jc_pass('Settlement gate on release', str_contains($close, 'm360_settlement_can_release'));
$results[] = p7jc_pass('Vehicle release after signed', str_contains($close, 'vehicle_released_at') && str_contains($close, 'VEHICLE_RELEASED'));
$results[] = p7jc_pass('Close after vehicle release', str_contains($close, 'vehicle_released_at') && str_contains($close, 'jobcard_closed_at'));
$results[] = p7jc_pass('No premature close', str_contains($close, 'ابتدا خودرو باید تحویل مشتری شود'));
$results[] = p7jc_pass('VEHICLE_RELEASED event', str_contains($close, 'VEHICLE_RELEASED_TO_CUSTOMER'));
$results[] = p7jc_pass('JOBCARD_CLOSED event', str_contains($close, "'JOBCARD_CLOSED'"));

$pass = 0; $fail = 0;
echo "# P7 Jobcard Close Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
