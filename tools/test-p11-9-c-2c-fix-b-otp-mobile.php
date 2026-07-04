<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function fixb_otp_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$intakeSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');
$saveSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-save.php');

$results[] = fixb_otp_pass('mobile correction action allowed', in_array('save_mobile_correction', m360_rw_intake_allowed_actions(), true));
$results[] = fixb_otp_pass('send_customer_otp action allowed', in_array('send_customer_otp', m360_rw_intake_allowed_actions(), true));
$results[] = fixb_otp_pass('intake mobile section exists', str_contains($intakeSrc, 'شماره موبایل و تأیید مشتری'));
$results[] = fixb_otp_pass('save_mobile_correction in intake form', str_contains($intakeSrc, 'save_mobile_correction'));

$applied = m360_rw_intake_apply_action(['otp_verified' => 1], 'save_mobile_correction', ['mobile_corrected' => '09123456789']);
$results[] = fixb_otp_pass('mobile correction saves mobile', $applied['ok'] && ($applied['payload']['mobile'] ?? '') === '09123456789');
$results[] = fixb_otp_pass('mobile correction resets otp_verified', ($applied['payload']['otp_verified'] ?? 1) === 0);
$results[] = fixb_otp_pass('mobile_correction nested payload', !empty($applied['payload']['reception_intake']['mobile_correction']['mobile']));
$results[] = fixb_otp_pass('column_updates mobile', ($applied['column_updates']['mobile'] ?? '') === '09123456789');

$bad = m360_rw_intake_apply_action([], 'save_mobile_correction', ['mobile_corrected' => '1234']);
$results[] = fixb_otp_pass('invalid mobile rejected', !$bad['ok']);

$results[] = fixb_otp_pass('no otp_verified=1 write in apply', !preg_match('/\$payload\s*\[\s*[\'"]otp_verified[\'"]\s*\]\s*=\s*1/', $helperSrc));
$results[] = fixb_otp_pass('otp send availability helper', function_exists('m360_rw_intake_otp_send_available'));
$avail = m360_rw_intake_otp_send_available();
$results[] = fixb_otp_pass('OTP not faked in UI when unavailable', str_contains($intakeSrc, 'ارسال OTP') && (str_contains($intakeSrc, 'disabled') || str_contains($intakeSrc, '$otpSend')));
$results[] = fixb_otp_pass('no fake otp success in save', !str_contains($saveSrc, 'otp_verified = 1') && !str_contains($saveSrc, 'otp_verified=1'));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C-FIX-B OTP Mobile Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
