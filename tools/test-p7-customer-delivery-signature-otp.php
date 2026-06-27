<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p7cd_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p7cd_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$review = p7cd_read($public . '/customer-delivery-review.php');
$sign = p7cd_read($public . '/customer-delivery-sign.php');
$helper = p7cd_read($public . '/includes/m360-customer-delivery-helper.php');
$sendApi = p7cd_read($public . '/api/customer/delivery-send-otp.php');
$confirmApi = p7cd_read($public . '/api/customer/delivery-confirm.php');
$js = p7cd_read($public . '/assets/js/m360-customer-delivery-sign.js');
$migration = p7cd_read($root . '/database/migrations/P7_final_invoice_settlement_customer_delivery.sql');

$results = [];
$results[] = p7cd_pass('Review page exists', is_file($public . '/customer-delivery-review.php'));
$results[] = p7cd_pass('Sign page exists', is_file($public . '/customer-delivery-sign.php'));
$results[] = p7cd_pass('Send OTP API', is_file($public . '/api/customer/delivery-send-otp.php'));
$results[] = p7cd_pass('Confirm API', is_file($public . '/api/customer/delivery-confirm.php'));
$results[] = p7cd_pass('RTL review', str_contains($review, 'dir="rtl"'));
$results[] = p7cd_pass('RTL sign', str_contains($sign, 'dir="rtl"'));
$results[] = p7cd_pass('OTP verify fn', str_contains($helper, 'm360_delivery_verify_otp'));
$results[] = p7cd_pass('OTP required on sign', str_contains($sign, 'otp_code') && str_contains($sign, 'required'));
$results[] = p7cd_pass('OTP required on confirm', str_contains($helper, 'm360_delivery_verify_otp($invoiceId, trim($otpCode))'));
$results[] = p7cd_pass('Signature hash stored', str_contains($helper, 'signature_hash') && str_contains($helper, "hash('sha256', \$signatureData)"));
$results[] = p7cd_pass('Signature required', str_contains($helper, 'امضای دیجیتال الزامی'));
$results[] = p7cd_pass('Checkboxes c1-c4', str_contains($sign, 'confirm_vehicle') && str_contains($sign, 'confirm_services') && str_contains($sign, 'confirm_finance') && str_contains($sign, 'confirm_terms'));
$results[] = p7cd_pass('All checkboxes required', str_contains($helper, 'if (!$c1 || !$c2 || !$c3 || !$c4)'));
$results[] = p7cd_pass('Token hash lookup', str_contains($helper, 'delivery_token_hash = ?') && str_contains($helper, 'm360_fi_hash($rawToken)'));
$results[] = p7cd_pass('No raw token column in migration', !preg_match('/\bdelivery_token\b(?!_hash)/i', $migration));
$results[] = p7cd_pass('No production OTP hardcode in helper', !preg_match("/['\"]123456['\"]/", $helper));
$results[] = p7cd_pass('Dev OTP via otp helper', str_contains($helper, 'm360_otp_get_dev_code') && str_contains($helper, 'm360_otp_can_use_dev_code'));
$results[] = p7cd_pass('IP stored', str_contains($helper, 'confirmation_ip') && str_contains($helper, 'customer_core_client_ip()'));
$results[] = p7cd_pass('User agent stored', str_contains($helper, 'confirmation_user_agent') && str_contains($helper, 'customer_core_user_agent()'));
$results[] = p7cd_pass('API POST only send', str_contains($sendApi, "!== 'POST'"));
$results[] = p7cd_pass('API POST only confirm', str_contains($confirmApi, "!== 'POST'"));
$results[] = p7cd_pass('JS calls OTP API', str_contains($js, 'delivery-send-otp.php') && str_contains($js, 'delivery-confirm.php'));

$pass = 0; $fail = 0;
echo "# P7 Customer Delivery Signature OTP Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
