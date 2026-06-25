<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Local public site runtime verification (public_html + HTTP).
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$pub = $root . DIRECTORY_SEPARATOR . 'public_html';
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

$results = [];

$required = [
    'customer-request.php',
    'staff-login.php',
    'owner-login.php',
    'assets/js/customer-form.js',
    'includes/mirror-api-client.php',
    'includes/mirror-layout.php',
];

foreach ($required as $rel) {
    $path = $pub . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $results[] = rt_pass('Exists: ' . $rel, is_file($path));
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
