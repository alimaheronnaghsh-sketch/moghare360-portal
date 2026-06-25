<?php
declare(strict_types=1);

/**
 * MOGHARE360 — Legacy Codex site review verification
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$mirrorRoot = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-mirror-site-package' . DIRECTORY_SEPARATOR . 'public_html';
$legacyReview = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . '_legacy_codex_review';
$reviewDoc = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'MOGHARE360_LEGACY_CODEX_SITE_REVIEW.md';

function leg_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];

$results[] = leg_pass('ZIP review doc exists', is_file($reviewDoc));
$results[] = leg_pass('Legacy extract folder exists (local review)', is_dir($legacyReview));

$forbiddenMirror = [
    $mirrorRoot . DIRECTORY_SEPARATOR . 'submit-customer.php',
    $mirrorRoot . DIRECTORY_SEPARATOR . 'submit-service-request.php',
    $mirrorRoot . DIRECTORY_SEPARATOR . 'admin-pending.php',
    $mirrorRoot . DIRECTORY_SEPARATOR . 'config.php',
    $mirrorRoot . DIRECTORY_SEPARATOR . 'customer.php',
    $mirrorRoot . DIRECTORY_SEPARATOR . 'service-request.php',
];
foreach ($forbiddenMirror as $path) {
    $results[] = leg_pass('Legacy Codex file not in mirror package: ' . basename($path), !is_file($path));
}

$customerRequest = $mirrorRoot . DIRECTORY_SEPARATOR . 'customer-request.php';
$results[] = leg_pass('Mirror customer-request.php exists', is_file($customerRequest));
if (is_file($customerRequest)) {
    $cr = (string)file_get_contents($customerRequest);
    $results[] = leg_pass('customer-request uses mirror_api_customer_request', str_contains($cr, 'mirror_api_customer_request'));
    $results[] = leg_pass('customer-request no submit-customer.php action', !str_contains($cr, 'submit-customer.php'));
    $results[] = leg_pass('customer-request has odometer_km (legacy field)', str_contains($cr, 'odometer_km'));
    $results[] = leg_pass('customer-request has legacy success message', str_contains($cr, 'پس از بررسی با شما تماس گرفته می‌شود'));
    exec('"' . $phpBin . '" -l ' . escapeshellarg($customerRequest) . ' 2>&1', $out, $code);
    $results[] = leg_pass('PHP lint customer-request.php', $code === 0);
}

$mirrorIndex = $mirrorRoot . DIRECTORY_SEPARATOR . 'index.php';
if (is_file($mirrorIndex)) {
    $idx = (string)file_get_contents($mirrorIndex);
    $results[] = leg_pass('Mirror index has legacy tagline', str_contains($idx, 'پورتال یکپارچه خدمات خودرو'));
    exec('"' . $phpBin . '" -l ' . escapeshellarg($mirrorIndex) . ' 2>&1', $out2, $code2);
    $results[] = leg_pass('PHP lint mirror index.php', $code2 === 0);
}

$apiPath = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'request.php';
$results[] = leg_pass('V1 API customer/request.php exists', is_file($apiPath));
if (is_file($apiPath)) {
    $api = (string)file_get_contents($apiPath);
    $results[] = leg_pass('API maps full_name alias', str_contains($api, 'full_name'));
    $results[] = leg_pass('API maps odometer_km', str_contains($api, 'odometer_km'));
    $results[] = leg_pass('API maps service_description', str_contains($api, 'service_description'));
    exec('"' . $phpBin . '" -l ' . escapeshellarg($apiPath) . ' 2>&1', $out3, $code3);
    $results[] = leg_pass('PHP lint api/customer/request.php', $code3 === 0);
}

$mirrorHealth = $mirrorRoot . DIRECTORY_SEPARATOR . 'mirror-health.php';
if (is_file($mirrorHealth)) {
    exec('"' . $phpBin . '" -l ' . escapeshellarg($mirrorHealth) . ' 2>&1', $out4, $code4);
    $results[] = leg_pass('PHP lint mirror-health.php', $code4 === 0);
}

$legacyConfig = $legacyReview . DIRECTORY_SEPARATOR . 'config.php';
$results[] = leg_pass('Legacy config stays in review folder only', is_file($legacyConfig));
$legacyCfg = is_file($legacyConfig) ? (string)file_get_contents($legacyConfig) : '';
$results[] = leg_pass('Legacy MySQL placeholder not in mirror package', !is_file($mirrorRoot . DIRECTORY_SEPARATOR . 'config.php'));

$gitignore = $root . DIRECTORY_SEPARATOR . '.gitignore';
if (is_file($gitignore)) {
    $results[] = leg_pass('.gitignore excludes legacy review folder', str_contains((string)file_get_contents($gitignore), '_legacy_codex_review'));
}

$passed = $failed = 0;
foreach ($results as $row) {
    $line = ($row['pass'] ? 'PASS' : 'FAIL') . ' — ' . $row['name'];
    if ($row['detail'] !== '') {
        $line .= ' (' . $row['detail'] . ')';
    }
    echo $line . PHP_EOL;
    $row['pass'] ? $passed++ : $failed++;
}

echo PHP_EOL . 'Passed: ' . $passed . ' / ' . count($results) . PHP_EOL;

if ($failed > 0) {
    fwrite(STDERR, 'LEGACY CODEX SITE REVIEW TEST FAILED' . PHP_EOL);
    exit(1);
}
echo 'LEGACY CODEX SITE REVIEW TEST PASSED' . PHP_EOL;
exit(0);
