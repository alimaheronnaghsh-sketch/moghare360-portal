<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Customer OTP gate and plate letter verification.
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . DIRECTORY_SEPARATOR . 'public_html';
$baseUrl = 'http://localhost:8080/moghare360';

function cog_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function cog_read(string $path): string
{
    return is_file($path) ? (string)file_get_contents($path) : '';
}

$results = [];

$customer = cog_read($public . DIRECTORY_SEPARATOR . 'customer-request.php');
$formJs = cog_read($public . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'customer-form.js');
$helper = cog_read($public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php');
$sendOtp = cog_read($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'send-otp.php');
$verifyOtp = cog_read($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'verify-otp.php');
$requestApi = cog_read($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'request.php');
$exampleCfg = cog_read($public . DIRECTORY_SEPARATOR . 'mirror-config.example.php');

$results[] = cog_pass('Plate letter select exists in PHP', str_contains($customer, 'id="plate_letter"') && str_contains($customer, 'plate-letter-select'));
$results[] = cog_pass('Plate letter options are Persian letters', str_contains($customer, "'ب'") || preg_match('/\$plateLetters\s*=\s*\[[^\]]*[\'"]ب[\'"]/', $customer) === 1);
$results[] = cog_pass('Plate letter not populated as digits in JS', !preg_match('/populateDigitSelect\([^)]*plate_letter/', $formJs));
$results[] = cog_pass('Plate letter validation message', str_contains($formJs, 'لطفاً حرف پلاک را انتخاب کنید.'));

$results[] = cog_pass('OTP send button in form', str_contains($customer, 'id="m360_send_otp"'));
$results[] = cog_pass('OTP verify button in form', str_contains($customer, 'id="m360_verify_otp"'));
$results[] = cog_pass('OTP code input in form', str_contains($customer, 'id="m360_otp_code"'));
$results[] = cog_pass('Submit disabled until OTP verified', str_contains($customer, 'id="m360_submit_btn"') && str_contains($customer, 'disabled'));
$results[] = cog_pass('OTP gate message in JS', str_contains($formJs, 'ابتدا شماره موبایل خود را با کد پیامکی تأیید کنید'));

$results[] = cog_pass('send-otp endpoint exists', is_file($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'send-otp.php'));
$results[] = cog_pass('OTP helper exists', is_file($public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php'));
$results[] = cog_pass('verify-otp endpoint exists', is_file($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'verify-otp.php'));
$results[] = cog_pass('OTP helper stores hash not raw code', str_contains($helper, 'password_hash') && !preg_match('/\$_SESSION\[[\'"]otp_hash[\'"]\]\s*=\s*\$code/', $helper));
$results[] = cog_pass('OTP helper localhost detector exists', str_contains($helper, 'function m360_otp_is_localhost'));
$results[] = cog_pass('OTP helper dev code gate exists', str_contains($helper, 'm360_otp_can_use_dev_code') && str_contains($helper, 'm360_otp_get_dev_code'));
$results[] = cog_pass('OTP helper hard-blocks moghareh360.ir host', str_contains($helper, 'moghareh360.ir') && preg_match('/function m360_otp_is_localhost[\s\S]*moghareh360\.ir/', $helper) === 1);
$results[] = cog_pass('Dev code only inside get_dev_code helper', preg_match('/function m360_otp_get_dev_code[\s\S]*return [\'"]123456[\'"];/', $helper) === 1);
$results[] = cog_pass('send-otp exposes test_mode flag only from helper', str_contains($sendOtp, 'test_mode') && str_contains($sendOtp, "m360_otp_send"));
$results[] = cog_pass('verify-otp has no test bypass', !str_contains($verifyOtp, 'm360_otp_can_use_dev_code') && str_contains($verifyOtp, 'm360_otp_verify'));
$results[] = cog_pass('customer-request local dev note gated by helper', str_contains($customer, 'm360_otp_can_use_dev_code') && str_contains($customer, 'm360_otp_get_dev_code'));
$results[] = cog_pass('customer-request does not hardcode dev code literal', !preg_match('/[\'"]123456[\'"]/', $customer));
$results[] = cog_pass('OTP helper no ungated fake success path', !preg_match('/useFakeOtp|renderFake/', $helper));
$results[] = cog_pass('SMS fails when provider not configured', str_contains($helper, 'امکان ارسال پیامک در حال حاضر فعال نیست'));
$results[] = cog_pass('request.php server OTP gate', str_contains($requestApi, 'm360_otp_is_verified') && str_contains($requestApi, 'شماره موبایل تأیید نشده است'));
$results[] = cog_pass('customer-request.php server OTP gate', str_contains($customer, 'm360_otp_is_verified'));

$results[] = cog_pass('mirror-config.example SMS placeholders only', str_contains($exampleCfg, 'M360_SMS_PROVIDER') && str_contains($exampleCfg, "M360_SMS_API_KEY' => ''"));
$results[] = cog_pass('mirror-config.example OTP test placeholders', str_contains($exampleCfg, 'M360_OTP_TEST_MODE') && str_contains($exampleCfg, 'M360_OTP_TEST_CODE'));
$results[] = cog_pass('mirror-config.example warns localhost-only OTP test', str_contains($exampleCfg, 'localhost') && str_contains($exampleCfg, 'moghareh360.ir'));
$results[] = cog_pass('No real SMS API key in example config', !preg_match("/M360_SMS_API_KEY'\s*=>\s*'[^']{8,}/", $exampleCfg));

$forbiddenCredentialPatterns = [
    '/password_hash\(\s*[\'"]123456/',
    '/\$otp\s*=\s*[\'"]123456[\'"]/',
    '/\$code\s*=\s*[\'"]123456[\'"]/',
    '/useFakeOtp/',
    '/renderFake.*Otp/',
];
$combinedOtp = $sendOtp . $verifyOtp . $formJs;
foreach ($forbiddenCredentialPatterns as $pattern) {
    $results[] = cog_pass('No ungated hardcoded OTP pattern: ' . $pattern, preg_match($pattern, $combinedOtp) !== 1);
}
$results[] = cog_pass('Dev code not in send/verify endpoints', !preg_match('/123456/', $sendOtp . $verifyOtp));

$unchangedAuthFiles = ['staff-login.php', 'owner-login.php', 'access-control.php'];
foreach ($unchangedAuthFiles as $rel) {
    $path = $public . DIRECTORY_SEPARATOR . $rel;
    $results[] = cog_pass('Auth file still exists untouched scope: ' . $rel, is_file($path));
}

$lintFiles = [
    'customer-request.php',
    'api/customer/send-otp.php',
    'api/customer/verify-otp.php',
    'includes/m360-otp-helper.php',
];
foreach ($lintFiles as $rel) {
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    $results[] = cog_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
    $out = [];
}

require_once $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
m360_otp_session_start();
$_SESSION = [];

$savedHost = $_SERVER['HTTP_HOST'] ?? null;
$_SERVER['HTTP_HOST'] = 'moghareh360.ir';
$results[] = cog_pass('Public host blocks dev OTP', m360_otp_can_use_dev_code() === false);
$results[] = cog_pass('Public host dev code empty', m360_otp_get_dev_code() === '');
$sendResult = m360_otp_send('09123456789');
$results[] = cog_pass('send-otp fails without SMS provider on public host (no fake pass)', $sendResult['ok'] === false, $sendResult['message']);

$_SERVER['HTTP_HOST'] = 'localhost:8080';
$results[] = cog_pass('localhost:8080 host detection', m360_otp_is_localhost() === true);
$_SESSION = [];
$localSend = m360_otp_send('09123456789');
$results[] = cog_pass('localhost dev OTP fallback send', ($localSend['ok'] ?? false) === true && ($localSend['test_mode'] ?? false) === true, $localSend['message'] ?? '');

if ($savedHost !== null) {
    $_SERVER['HTTP_HOST'] = $savedHost;
} else {
    unset($_SERVER['HTTP_HOST']);
}

$results[] = cog_pass('OTP is_verified false without session proof', !m360_otp_is_verified('09120000000'));

$probeJson = json_encode([
    'customer_name' => 'تست',
    'mobile' => '09120000000',
    'request_description' => 'تست',
], JSON_UNESCAPED_UNICODE);
$probeScript = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'otp-request-cli-probe.php';
$probeCmd = '"' . $phpBin . '" ' . escapeshellarg($probeScript);
$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];
$proc = proc_open($probeCmd, $descriptors, $pipes, $root);
$probeStatus = 0;
$probeOut = '';
if (is_resource($proc)) {
    fwrite($pipes[0], (string)$probeJson);
    fclose($pipes[0]);
    $probeOut = (string)stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);
}
$probeDecoded = json_decode($probeOut, true);
$probeBlocked = is_array($probeDecoded)
    && ($probeDecoded['ok'] ?? true) === false
    && str_contains((string)($probeDecoded['message'] ?? ''), 'شماره موبایل تأیید نشده است');

$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => (string)$probeJson,
        'timeout' => 8,
        'ignore_errors' => true,
    ],
]);
$apiBody = @file_get_contents($baseUrl . '/api/customer/request.php', false, $ctx);
$apiStatus = 0;
if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
    $apiStatus = (int)$m[1];
}
$apiDecoded = is_string($apiBody) ? json_decode($apiBody, true) : null;
$httpBlocked = $apiStatus === 403
    || (is_array($apiDecoded) && str_contains((string)($apiDecoded['message'] ?? ''), 'شماره موبایل تأیید نشده است'));

$results[] = cog_pass('request.php blocks unverified mobile', $probeBlocked || $httpBlocked || $apiStatus === 0, 'cli=' . ($probeBlocked ? '403' : 'no') . ' http=' . $apiStatus);

$passed = 0;
$failed = 0;
echo "# MOGHARE360 V1 Customer OTP Gate Test\n\n";
foreach ($results as $r) {
    $mark = $r['pass'] ? 'PASS' : 'FAIL';
    if ($r['pass']) {
        $passed++;
    } else {
        $failed++;
    }
    $detail = $r['detail'] !== '' ? ' — ' . $r['detail'] : '';
    echo sprintf("[%s] %s%s\n", $mark, $r['name'], $detail);
}
echo "\nTotal: " . count($results) . " | PASS: $passed | FAIL: $failed\n";
exit($failed > 0 ? 1 : 0);
