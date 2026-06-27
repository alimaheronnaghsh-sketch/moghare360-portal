<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p4o_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p4o_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$approval = p4o_read($public . '/includes/m360-estimate-approval-helper.php');
$sign = p4o_read($public . '/customer-estimate-approval-sign.php');
$otpApi = p4o_read($public . '/api/customer/estimate-send-otp.php');

$results = [];
$results[] = p4o_pass('Customer approval page', is_file($public . '/customer-estimate-approval.php'));
$results[] = p4o_pass('Customer sign page', is_file($public . '/customer-estimate-approval-sign.php'));
$results[] = p4o_pass('OTP API', is_file($public . '/api/customer/estimate-send-otp.php'));
$results[] = p4o_pass('OTP required for approve', str_contains($approval, 'm360_estimate_verify_otp') || str_contains($approval, 'm360_estimate_otp_was_verified'));
$results[] = p4o_pass('Checkboxes required', str_contains($sign, 'confirm_viewed') && str_contains($approval, '!$c1 || !$c2 || !$c3'));
$results[] = p4o_pass('Uses m360_otp_send_sms', str_contains($approval, 'm360_otp_send_sms'));
$results[] = p4o_pass('No fake production OTP', !preg_match('/fake.*otp|otp.*=.*1234/i', $approval) || str_contains($approval, 'm360_otp_can_use_dev_code'));
$results[] = p4o_pass('IP stored', str_contains($approval, 'approval_ip'));
$results[] = p4o_pass('User-Agent stored', str_contains($approval, 'approval_user_agent'));
$results[] = p4o_pass('approval_hash stored', str_contains($approval, 'approval_hash'));
$results[] = p4o_pass('Re-approval blocked', str_contains($approval, 'قبلاً تأیید شده'));

$pass = 0; $fail = 0;
echo "# P4 Customer Approval OTP Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
