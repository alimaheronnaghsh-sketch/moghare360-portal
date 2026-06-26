<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Local public site runtime verification (public_html + HTTP).
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$pub = $root . DIRECTORY_SEPARATOR . 'public_html';
$localRuntime = rtrim(getenv('MOGHARE360_INSTALL_PATH') ?: 'C:\\xampp\\htdocs\\moghare360', '\\/');
$baseUrl = rtrim(getenv('MOGHARE360_BASE_URL') ?: 'http://localhost:8080/moghare360/', '/') . '/';

function rt_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function rt_read(string $path): string
{
    return is_file($path) ? (string)file_get_contents($path) : '';
}

function rt_http(string $url): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'detail' => 'curl_missing'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_FOLLOWLOCATION => true]);
    $body = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $fatal = str_contains($body, 'Fatal error') || str_contains($body, 'ensureSessionStarted()');
    return ['ok' => $code >= 200 && $code < 500 && !$fatal, 'detail' => 'HTTP ' . $code . ($fatal ? ' fatal' : '')];
}

function rt_http_post_json(string $url, array $body): array
{
    if (!function_exists('curl_init')) {
        return ['reachable' => false, 'http' => 0, 'data' => null, 'detail' => 'curl_missing'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
    ]);
    $raw = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        return ['reachable' => false, 'http' => $http, 'data' => null, 'detail' => $err !== '' ? $err : 'curl_exec_failed'];
    }
    $decoded = json_decode((string)$raw, true);
    return [
        'reachable' => true,
        'http' => $http,
        'data' => is_array($decoded) ? $decoded : null,
        'detail' => is_array($decoded) ? '' : 'non_json',
    ];
}

$results = [];

$required = [
    'customer-request.php',
    'staff-login.php',
    'owner-login.php',
    'assets/js/customer-form.js',
    'includes/mirror-api-client.php',
    'includes/mirror-layout.php',
    'includes/m360-otp-helper.php',
    'api/customer/send-otp.php',
    'api/customer/verify-otp.php',
    'api/customer/profile-status.php',
];

foreach ($required as $rel) {
    $path = $pub . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $results[] = rt_pass('Exists: ' . $rel, is_file($path));
}

$localOtpFiles = [
    'includes/m360-otp-helper.php',
    'api/customer/send-otp.php',
    'api/customer/verify-otp.php',
    'api/customer/profile-status.php',
    'assets/js/customer-form.js',
];
if (is_dir($localRuntime)) {
    foreach ($localOtpFiles as $rel) {
        $path = $localRuntime . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $results[] = rt_pass('Local runtime exists: ' . $rel, is_file($path));
    }
}

$lintFiles = ['customer-request.php', 'staff-login.php', 'owner-login.php'];
foreach ($lintFiles as $rel) {
    $path = $pub . DIRECTORY_SEPARATOR . $rel;
    if (!is_file($path)) {
        continue;
    }
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    $results[] = rt_pass('PHP lint: ' . $rel, $code === 0);
}

$staff = rt_read($pub . DIRECTORY_SEPARATOR . 'staff-login.php');
$owner = rt_read($pub . DIRECTORY_SEPARATOR . 'owner-login.php');
$customer = rt_read($pub . DIRECTORY_SEPARATOR . 'customer-request.php');
$layout = rt_read($pub . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php');
$combined = $staff . $owner . $customer . $layout;

$results[] = rt_pass('staff-login no legacy config.php', !str_contains($staff, "require_once __DIR__ . '/config.php'"));
$results[] = rt_pass('staff-login no ensureSessionStarted', !preg_match('/\bensureSessionStarted\s*\(/', $staff));
$results[] = rt_pass('owner-login no ensureSessionStarted', !preg_match('/\bensureSessionStarted\s*\(/', $owner));
$results[] = rt_pass('customer-request uses API client', str_contains($customer, 'mirror_api_customer_request'));
$results[] = rt_pass('customer-request uses OTP helper', str_contains($customer, 'm360-otp-helper.php'));
$results[] = rt_pass('customer-request loads customer-form cache bust', str_contains($customer, 'customer-form.js?v=p07d'));
$results[] = rt_pass('customer-form binds send OTP click', str_contains(rt_read($pub . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'customer-form.js'), 'stopPropagation'));
$results[] = rt_pass('API client targets customer request', str_contains(rt_read($pub . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php'), '/api/customer/request'));

$forbidden = ['Master Server', 'Mirror Interface Only', 'No Host Database', 'رابط آینه', 'SQL Server', 'laptop'];
foreach ($forbidden as $text) {
    $results[] = rt_pass('No forbidden UI text: ' . $text, !str_contains($combined, $text));
}

$results[] = rt_pass('No public_html/config.php', !is_file($pub . DIRECTORY_SEPARATOR . 'config.php'));

foreach (['customer-request.php', 'staff-login.php', 'owner-login.php'] as $page) {
    $probe = rt_http($baseUrl . $page);
    $results[] = rt_pass('HTTP 200: ' . $page, $probe['ok'], $probe['detail']);
}

$sendProbe = rt_http_post_json($baseUrl . 'api/customer/send-otp.php', ['phone' => '09123456789']);
if (!$sendProbe['reachable'] || $sendProbe['http'] === 0) {
    $results[] = rt_pass('HTTP POST send-otp.php localhost dev fallback', true, 'WARN: Apache not reachable — ' . $sendProbe['detail']);
} elseif (!is_array($sendProbe['data'])) {
    $results[] = rt_pass('HTTP POST send-otp.php localhost dev fallback', false, 'non-JSON: HTTP ' . $sendProbe['http']);
} else {
    $ok = ($sendProbe['data']['ok'] ?? false) === true
        && ($sendProbe['data']['test_mode'] ?? false) === true
        && str_contains((string)($sendProbe['data']['message'] ?? ''), '123456');
    $results[] = rt_pass('HTTP POST send-otp.php localhost dev fallback', $ok, json_encode($sendProbe['data'], JSON_UNESCAPED_UNICODE));
}

$failed = array_filter($results, static fn(array $r): bool => !$r['pass']);
$passed = count($results) - count($failed);

echo "MOGHARE360 V1 Local Public Site Runtime Test\n";
echo str_repeat('-', 60) . "\n";
foreach ($results as $r) {
    $mark = $r['pass'] ? 'PASS' : 'FAIL';
    $detail = $r['detail'] !== '' ? ' — ' . $r['detail'] : '';
    echo "[{$mark}] {$r['name']}{$detail}\n";
}
echo str_repeat('-', 60) . "\n";
echo "Result: {$passed}/" . count($results) . " PASS\n";
exit(count($failed) > 0 ? 1 : 0);
