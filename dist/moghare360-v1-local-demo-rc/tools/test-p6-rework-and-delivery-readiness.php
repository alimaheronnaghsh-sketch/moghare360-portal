<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-delivery-readiness-helper.php';
$qc = file_get_contents($root . '/public_html/includes/m360-qc-helper.php') ?: '';

function p6r_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p6r_pass('Failure reason required', str_contains($qc, 'failure_reason') && str_contains($qc, 'دلیل رد'));
$results[] = p6r_pass('REWORK_REQUIRED status', str_contains($qc, 'REWORK_REQUIRED'));
$results[] = p6r_pass('rework_completed action', str_contains($qc, 'rework_completed'));
$results[] = p6r_pass('JOBCARD_REWORK_REQUIRED event', str_contains($qc, 'JOBCARD_REWORK_REQUIRED'));
$results[] = p6r_pass('delivery_ready after pass', str_contains($qc, "'delivery_ready'"));
$results[] = p6r_pass('Delivery validate fn', function_exists('m360_delivery_readiness_validate'));
$results[] = p6r_pass('QC_PASSED required for delivery', str_contains(file_get_contents($root . '/public_html/includes/m360-delivery-readiness-helper.php') ?: '', 'QC_PASSED'));
$results[] = p6r_pass('No vehicle release', !preg_match('/vehicle_release|RELEASED.*delivery|delivery_otp/i', $qc));
$results[] = p6r_pass('No final invoice', !preg_match('/INSERT INTO dbo\.erp_invoices/i', $qc));
$results[] = p6r_pass('DELIVERY_READY status', str_contains($qc, 'DELIVERY_READY'));

$pass = 0; $fail = 0;
echo "# P6 Rework And Delivery Readiness Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
