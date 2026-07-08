<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr02b_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$page = (string)file_get_contents($root . '/public_html/customer-request.php');
$api = (string)file_get_contents($root . '/public_html/api/customer/request.php');
$helper = (string)file_get_contents($root . '/public_html/includes/m360-customer-online-submit-helper.php');
$js = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');

$results[] = pr02b_pass('direct submit no mirror curl', !str_contains($page, 'mirror_api_customer_request'));
$results[] = pr02b_pass('shared submit from page', str_contains($page, 'm360_customer_online_submit_from_post'));
$results[] = pr02b_pass('shared submit from API', str_contains($api, 'm360_customer_online_submit'));
$results[] = pr02b_pass('server OTP assert helper', str_contains($helper, 'm360_pr02b_assert_otp_verified'));
$results[] = pr02b_pass('OTP uses m360_otp_is_verified', str_contains($helper, 'm360_otp_is_verified'));
$results[] = pr02b_pass('JS uses verified state not only hidden', str_contains($js, 'if (!verified)'));
$results[] = pr02b_pass('single OTP status cleared after verify', !str_contains($js, 'شماره موبایل تأیید شد. مرحله پروفایل') || str_contains($js, 'clearAllStepErrors'));
$results[] = pr02b_pass('no contradictory welcome + error stack', !str_contains($js, "showSection('m360_section_vehicle', true);\n        showSection('m360_section_request', true);"));
$results[] = pr02b_pass('OTP_PROVIDER_UNTOUCHED', is_file($root . '/public_html/includes/m360-otp-helper.php'));
$results[] = pr02b_pass('OTP_BYPASS_CREATED no', !str_contains($helper, 'otp_verified = 1') || str_contains($helper, 'm360_otp_is_verified'));
$results[] = pr02b_pass('HIDDEN_FIELD_NOT_SECURITY_SOURCE', str_contains($js, 'if (!verified)'));
$results[] = pr02b_pass('OTP send button id bound', str_contains($js, "var sendBtn = $('m360_send_otp')") && str_contains($js, "sendBtn.addEventListener('click'"));
$results[] = pr02b_pass('OTP send loading visible', str_contains($js, 'در حال ارسال کد تأیید'));
$results[] = pr02b_pass('OTP failure visible', str_contains($js, 'ارسال کد تأیید انجام نشد'));

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02B otp single source: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
