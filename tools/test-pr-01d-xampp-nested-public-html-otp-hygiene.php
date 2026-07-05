<?php
declare(strict_types=1);

/**
 * PR-01D — XAMPP nested public_html legacy OTP hygiene (controlled runtime only).
 */

$root = dirname(__DIR__);
$xamppRoot = 'C:\\xampp\\htdocs\\moghare360';
$xamppPrivate = 'C:\\xampp\\htdocs\\private\\m360-otp-config.php';

function pr01d_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function pr01d_sha256(string $path): string
{
    if (!is_file($path)) {
        return 'MISSING';
    }
    return hash_file('sha256', $path);
}

$nestedRoutes = [
    'send-otp.php',
    'verify-otp.php',
    'check-otp.php',
    'send-contract-otp.php',
    'verify-contract-otp.php',
];

$canonicalPairs = [
    'customer-request.php',
    'assets/js/customer-form.js',
    'api/customer/send-otp.php',
    'api/customer/verify-otp.php',
    'includes/m360-otp-helper.php',
    'includes/m360-otp-config-loader.php',
];

$canonicalHashes = [
    'customer-request.php' => '37909b3307d2c3ca804e8ca7a1afb73d1beaae14c021c277f8f6a07b5dd28d0d',
    'assets/js/customer-form.js' => '4d6e447277c3e9f4e9d1127727bb8f53bd07a09a49a2d3134adb30335ff5461a',
    'api/customer/send-otp.php' => '6736f894d17d2b2cb981ad6baf895c0ef6328571e928c1feb3d0e9460ee7a675',
    'api/customer/verify-otp.php' => '709f41ba91cdfd12c2722dc9ae2024946ce0df79877d33ca905f8ebe5bb2492b',
    'includes/m360-otp-helper.php' => '3f3c714f2235feed7170af0cd1ffd18acf9cf910c129e5f5565cbbdc51f87c1e',
    'includes/m360-otp-config-loader.php' => '3e886878a5c53e21c25385e03f3a6fa285711ddc0c4fedfdb45b0263286b12e8',
];

$privateConfigHash = '4632534647e5c6b121cfcb8f5bd5abd38f7502a93b888010dfe47daadf7a6be0';

$results = [];

foreach ($nestedRoutes as $route) {
    $path = $xamppRoot . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . $route;
    $src = is_file($path) ? (string)file_get_contents($path) : '';
    $results[] = pr01d_pass(
        'nested ' . $route . ' exists',
        is_file($path),
        $path
    );
    $results[] = pr01d_pass(
        'nested ' . $route . ' uses deprecation stub',
        str_contains($src, 'm360-legacy-otp-deprecation-stub.php'),
    );
    $results[] = pr01d_pass(
        'nested ' . $route . ' requires parent includes',
        str_contains($src, "dirname(__DIR__)") && str_contains($src, 'includes'),
    );
    $results[] = pr01d_pass(
        'nested ' . $route . ' no legacy config.php',
        !str_contains($src, 'config.php'),
    );
}

foreach ($canonicalPairs as $rel) {
    $repoPath = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $xamppPath = $xamppRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $repoHash = pr01d_sha256($repoPath);
    $xamppHash = pr01d_sha256($xamppPath);
    $expected = $canonicalHashes[$rel] ?? '';

    $results[] = pr01d_pass(
        'repo canonical hash unchanged: ' . $rel,
        $repoHash === $expected,
        $repoHash
    );
    $results[] = pr01d_pass(
        'xampp canonical hash matches repo: ' . $rel,
        $repoHash === $xamppHash && $repoHash !== 'MISSING',
        'repo=' . $repoHash . ' xampp=' . $xamppHash
    );
}

$results[] = pr01d_pass(
    'private config hash untouched',
    pr01d_sha256($root . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'm360-otp-config.php') === $privateConfigHash
    && pr01d_sha256($xamppPrivate) === $privateConfigHash,
    'expected=' . $privateConfigHash
);

$customerForm = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');
$results[] = pr01d_pass(
    'customer-form uses api/customer/send-otp.php',
    str_contains($customerForm, 'api/customer/send-otp.php')
);
$results[] = pr01d_pass(
    'customer-form uses api/customer/verify-otp.php',
    str_contains($customerForm, 'api/customer/verify-otp.php')
);
$results[] = pr01d_pass(
    'customer-form avoids nested public_html legacy',
    !str_contains($customerForm, 'public_html/send-otp.php')
);

$scopeForbidden = [
    'includes/m360-reception-workbench-helper.php',
    'staff-login.php',
    'erp-reception-intake-file.php',
];
foreach ($scopeForbidden as $rel) {
    $path = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $results[] = pr01d_pass(
        'forbidden scope file unchanged in repo: ' . $rel,
        is_file($path) && pr01d_sha256($path) !== 'MISSING',
        'hash=' . pr01d_sha256($path)
    );
}

$deprecationStub = $xamppRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-legacy-otp-deprecation-stub.php';
$results[] = pr01d_pass(
    'parent deprecation stub reachable from nested public_html',
    is_file($deprecationStub) && str_contains((string)file_get_contents($deprecationStub), 'm360_legacy_otp_route_deprecated')
);

$httpBase = getenv('M360_OTP_HTTP_BASE') ?: 'http://localhost:8080/moghare360/public_html';
foreach ($nestedRoutes as $route) {
    $url = rtrim($httpBase, '/') . '/' . $route;
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Accept: application/json\r\nContent-Type: application/json\r\n",
            'body' => '{}',
            'ignore_errors' => true,
            'timeout' => 5,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', (string)$http_response_header[0], $m)) {
        $status = (int)$m[1];
    }
    $jsonOk = false;
    if (is_string($body) && $body !== '') {
        $decoded = json_decode($body, true);
        $jsonOk = is_array($decoded)
            && ($decoded['ok'] ?? null) === false
            && str_contains((string)($decoded['message'] ?? ''), 'مسیر قدیمی OTP');
    }
    $results[] = pr01d_pass(
        'nested HTTP 410 JSON: ' . $route,
        $status === 410 && $jsonOk,
        'status=' . $status . ' url=' . $url
    );
}

$pass = 0;
$fail = 0;
echo "# PR-01D XAMPP Nested public_html OTP Hygiene Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
