<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p11otp_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p11otp_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$otpHelper = $public . '/includes/m360-otp-helper.php';
$otpLoader = $public . '/includes/m360-otp-config-loader.php';
$exampleConfig = $root . '/private/m360-otp-config.example.php';

$results = [];
$results[] = p11otp_pass('OTP helper exists', is_file($otpHelper));
$results[] = p11otp_pass('OTP config loader exists', is_file($otpLoader));
$results[] = p11otp_pass('Example config exists', is_file($exampleConfig));

$scanRoots = [
    $public . '/includes/m360-otp-helper.php',
    $public . '/includes/m360-otp-config-loader.php',
    $exampleConfig,
    $public . '/mirror-config.example.php',
];
$blob = '';
foreach ($scanRoots as $f) {
    $blob .= p11otp_read($f);
}
$results[] = p11otp_pass('No real API key in committed OTP files', !preg_match('/api[_-]?key\s*=\s*[\'"][a-zA-Z0-9]{20,}[\'"]/i', $blob));

require_once $otpLoader;
require_once $otpHelper;

$example = m360_otp_config_example();
$results[] = p11otp_pass('Example config readable', $example !== []);
$results[] = p11otp_pass('Example useFakeOtp default false', ($example['useFakeOtp'] ?? true) === false);
$results[] = p11otp_pass('Example ippanelOtpVariableName is OTP', (string)($example['ippanelOtpVariableName'] ?? '') === 'OTP');
$results[] = p11otp_pass('Placeholder API key in example', m360_otp_is_placeholder_value((string)($example['ippanelApiKey'] ?? '')));
$results[] = p11otp_pass('Placeholder not valid production config', !m360_otp_sms_configured());

$settings = m360_otp_sms_settings();
$results[] = p11otp_pass('Pattern variable name is OTP', (string)($settings['otp_variable'] ?? '') === 'OTP');

$payload = m360_otp_ippanel_pattern_payload('09120000000', '123456', $settings);
$paramKeys = array_keys($payload['params'] ?? []);
$results[] = p11otp_pass('Pattern params use OTP key', in_array('OTP', $paramKeys, true));
$results[] = p11otp_pass('%OTP% not used as variable key', !in_array('%OTP%', $paramKeys, true) && !in_array('%otp%', $paramKeys, true));

$_SERVER['HTTP_HOST'] = 'moghareh360.ir';
$results[] = p11otp_pass('Fake OTP blocked on production host', !m360_otp_can_use_dev_code());
$results[] = p11otp_pass('Production does not display OTP in message', !str_contains(m360_otp_dev_fallback_message(), '123456'));

$_SERVER['HTTP_HOST'] = 'localhost';
$results[] = p11otp_pass('Fake OTP blocked on localhost without explicit allow', !m360_otp_can_use_dev_code());

$withFake = m360_otp_config_apply_env(['useFakeOtp' => false]);
putenv('M360_OTP_USE_FAKE=true');
$withFake = m360_otp_config_apply_env($withFake);
$results[] = p11otp_pass('Env can enable useFakeOtp', m360_otp_config_bool($withFake, 'useFakeOtp'));

$missing = m360_otp_send_sms('09120000000', '123456');
$results[] = p11otp_pass('Missing IPPanel config returns controlled error', ($missing['ok'] ?? true) === false);
$results[] = p11otp_pass('Missing config message is safe', str_contains((string)($missing['message'] ?? ''), 'فعال نیست') || str_contains((string)($missing['message'] ?? ''), 'انجام نشد'));

$contractApi = p11otp_read($public . '/api/customer/contract-send-otp.php');
$estimateApi = p11otp_read($public . '/api/customer/estimate-send-otp.php');
$deliveryApi = p11otp_read($public . '/api/customer/delivery-send-otp.php');
$results[] = p11otp_pass('P1.5 contract OTP uses central helper', str_contains($contractApi, 'm360_contract_send_otp') && str_contains(p11otp_read($public . '/includes/m360-contract-signature-helper.php'), 'm360_otp_send_sms'));
$results[] = p11otp_pass('P4 estimate OTP uses central helper', str_contains($estimateApi, 'm360_estimate_send_otp') && str_contains(p11otp_read($public . '/includes/m360-estimate-approval-helper.php'), 'm360_otp_send_sms'));
$results[] = p11otp_pass('P7 delivery OTP uses central helper', str_contains($deliveryApi, 'm360_delivery_send_otp') && str_contains(p11otp_read($public . '/includes/m360-customer-delivery-helper.php'), 'm360_otp_send_sms'));

$authFiles = ['staff-login.php', 'owner-login.php', 'access-control.php'];
foreach ($authFiles as $af) {
    $results[] = p11otp_pass('Auth file unchanged exists: ' . $af, is_file($public . '/' . $af));
}

$results[] = p11otp_pass('OTP TTL default 5 minutes', m360_otp_ttl_seconds() === 300);

putenv('M360_OTP_USE_FAKE');
putenv('M360_OTP_EXPIRE_MINUTES');
$_SERVER['HTTP_HOST'] = '';

$pass = 0; $fail = 0;
echo "# P11 OTP Provider Config Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
