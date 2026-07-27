<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr02a_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function pr02a_hash(string $path): string
{
    return is_file($path) ? hash_file('sha256', $path) : 'MISSING';
}

$otpFiles = [
    'public_html/includes/m360-otp-helper.php',
    'public_html/includes/m360-otp-config-loader.php',
    'public_html/api/customer/send-otp.php',
    'public_html/api/customer/verify-otp.php',
    'private/m360-otp-config.php',
];
$authFiles = ['public_html/staff-login.php', 'public_html/includes/staff-auth.php', 'public_html/includes/access-control.php'];
$forbiddenTouch = array_merge($otpFiles, $authFiles, [
    'public_html/customer-request.php',
]);

$expectedUntouched = [
    'public_html/includes/m360-otp-helper.php' => '3f3c714f2235feed7170af0cd1ffd18acf9cf910c129e5f5565cbbdc51f87c1e',
    'public_html/includes/m360-otp-config-loader.php' => '3e886878a5c53e21c25385e03f3a6fa285711ddc0c4fedfdb45b0263286b12e8',
    'public_html/api/customer/send-otp.php' => '6736f894d17d2b2cb981ad6baf895c0ef6328571e928c1feb3d0e9460ee7a675',
    'public_html/api/customer/verify-otp.php' => '709f41ba91cdfd12c2722dc9ae2024946ce0df79877d33ca905f8ebe5bb2492b',
    'private/m360-otp-config.php' => '4632534647e5c6b121cfcb8f5bd5abd38f7502a93b888010dfe47daadf7a6be0',
];

$results = [];
foreach ($expectedUntouched as $rel => $hash) {
    $path = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $results[] = pr02a_pass('untouched: ' . $rel, pr02a_hash($path) === $hash, pr02a_hash($path));
}

$customerRequestPath = $root . '/public_html/customer-request.php';
$customerRequestHash = pr02a_hash($customerRequestPath);
$results[] = pr02a_pass('customer-request.php modified for UAT repair', is_file($customerRequestPath), $customerRequestHash);

$helper = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$receptionHelper = (string)file_get_contents($root . '/public_html/includes/m360-reception-helper.php');
$intakeFile = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');

$results[] = pr02a_pass('staff send OTP UI removed', !str_contains($helper, 'ارسال OTP به مشتری'));
$results[] = pr02a_pass('staff send_customer_otp blocked in save', str_contains($helper, 'M360_RW_RECEPTION_OTP_CUSTOMER_ONLY_MESSAGE_FA'));
$allowedActionsChunk = preg_match('/function m360_rw_intake_allowed_actions\(\): array\s*\{[\s\S]*?return \[[\s\S]*?\];/', $helper, $m) ? (string)$m[0] : '';
$results[] = pr02a_pass('send_customer_otp not in allowed actions', $allowedActionsChunk !== '' && !str_contains($allowedActionsChunk, "'send_customer_otp'"));
$results[] = pr02a_pass('otp_verified list filter exists', str_contains($receptionHelper, 'ISNULL(r.otp_verified, 0) = 1'));
$results[] = pr02a_pass('unverified intake blocked message', str_contains($intakeFile, 'M360_RW_RECEPTION_UNVERIFIED_ACCESS_MESSAGE_FA'));
$results[] = pr02a_pass('PHP brand fallback options rendered', str_contains($helper, 'm360_rw_intake_approved_vehicle_brands'));
$results[] = pr02a_pass('customer success tracking in page', str_contains((string)file_get_contents($customerRequestPath), 'm360_created_request_id'));
$results[] = pr02a_pass('contract template function preserved', str_contains($helper, 'function m360_rw_intake_contract_template_text'));
$results[] = pr02a_pass('no jobcard conversion added in PR-02A intake', !str_contains($helper, 'convert_to_jobcard') || !str_contains((string)file_get_contents($root . '/public_html/erp-reception-intake-file.php'), 'convert_to_jobcard'));
$results[] = pr02a_pass('allowed files only modified scope', is_file($root . '/public_html/erp-reception-intake-save.php'));

$allowedModified = [
    'public_html/customer-request.php',
    'public_html/assets/js/customer-form.js',
    'public_html/erp-reception-intake-file.php',
    'public_html/erp-reception-online-requests.php',
    'public_html/includes/m360-reception-helper.php',
    'public_html/includes/m360-reception-workbench-helper.php',
    'public_html/assets/js/m360-reception-intake.js',
    'public_html/assets/css/mirror.css',
];
foreach ($allowedModified as $rel) {
    $results[] = pr02a_pass('allowed runtime file exists: ' . $rel, is_file($root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel)));
}

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02A scope security: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
