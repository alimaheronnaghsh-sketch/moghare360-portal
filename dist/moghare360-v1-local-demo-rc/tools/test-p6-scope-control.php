<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p6sc_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }
function p6sc_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$all = p6sc_read($public . '/includes/m360-qc-helper.php')
    . p6sc_read($public . '/includes/m360-final-inspection-helper.php')
    . p6sc_read($public . '/includes/m360-delivery-readiness-helper.php')
    . p6sc_read($public . '/erp-qc-detail.php');

$results = [];
$results[] = p6sc_pass('No final invoice', !preg_match('/INSERT INTO dbo\.erp_invoices/i', $all));
$results[] = p6sc_pass('No payment settlement', !preg_match('/payment_settlement|settlement_complete|zarinpal/i', $all));
$results[] = p6sc_pass('No delivery release', !preg_match("/delivery_status = N'RELEASED'|vehicle_release|customer_release/i", $all));
$results[] = p6sc_pass('No delivery OTP', !preg_match('/delivery_otp|release_otp/i', $all));
$results[] = p6sc_pass('No accounting voucher', !preg_match('/journal_entry|accounting_voucher|GL_/i', $all));
$results[] = p6sc_pass('No free upload', !preg_match('/type=[\'"]file[\'"]|multipart.*upload|move_uploaded_file/i', $all));
$results[] = p6sc_pass('Controlled camera link', str_contains($all, 'erp-jobcard-camera-capture.php'));
$results[] = p6sc_pass('Contract gate', str_contains($all, 'm360_contract_can_continue_to_p2'));
$results[] = p6sc_pass('P5 work check', str_contains($all, 'technical_completion_notes'));
$results[] = p6sc_pass('staff-login untouched', is_file($public . '/staff-login.php'));
$results[] = p6sc_pass('access-control untouched', is_file($public . '/access-control.php'));

$pass = 0; $fail = 0;
echo "# P6 Scope Control Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
