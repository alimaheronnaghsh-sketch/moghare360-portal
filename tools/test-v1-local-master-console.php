<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Local master entry + console smoke test.
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$baseUrl = rtrim(getenv('MOGHARE360_BASE_URL') ?: 'http://localhost:8080/moghare360/', '/') . '/';

function lmc_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function lmc_http_ok(string $url): array
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

$indexPath = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'index.php';
$masterPath = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'erp-v1-master-console.php';
$unitPath = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'erp-v1-unit-access-console.php';

$results[] = lmc_pass('index.php exists', is_file($indexPath));
$results[] = lmc_pass('erp-v1-master-console.php exists', is_file($masterPath));
$results[] = lmc_pass('erp-v1-unit-access-console.php exists', is_file($unitPath));

foreach ([$indexPath => 'index.php', $masterPath => 'erp-v1-master-console.php', $unitPath => 'erp-v1-unit-access-console.php'] as $path => $label) {
    if (!is_file($path)) {
        continue;
    }
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    $results[] = lmc_pass('PHP lint: ' . $label, $code === 0);
}

if (is_file($indexPath)) {
    $indexContent = (string)file_get_contents($indexPath);
    $results[] = lmc_pass('index.php no legacy config.php require', !str_contains($indexContent, "require_once __DIR__ . '/config.php'"));
    $results[] = lmc_pass('index.php no legacy session bootstrap call', !preg_match('/\bensureSessionStarted\s*\(/', $indexContent));
    $results[] = lmc_pass('index.php links master console', str_contains($indexContent, 'erp-v1-master-console.php'));
}

if (is_file($masterPath)) {
    $masterContent = (string)file_get_contents($masterPath);
    foreach ([
        'erp-v1-unit-access-console.php',
        'erp-v1-production-signoff.php',
        'erp-v1-fix-register.php',
        'erp-moghare-ready.php',
        'erp-soft-run-home.php',
        'erp-operational-command-center.php',
        'erp-product-status.php',
    ] as $needle) {
        $results[] = lmc_pass('Master console link: ' . $needle, str_contains($masterContent, $needle));
    }
}

if (is_file($unitPath)) {
    $unitContent = (string)file_get_contents($unitPath);
    foreach (['OWNER', 'SYSTEM_ADMIN', 'RECEPTION', 'TECHNICIAN', 'INVENTORY', 'FINANCE', 'QC', 'CRM', 'COMPANY_OWNER_VIEWER'] as $role) {
        $results[] = lmc_pass('Unit access role: ' . $role, str_contains($unitContent, $role));
    }
    $results[] = lmc_pass('Unit console documents production-users template', str_contains($unitContent, 'production-users.template.json'));
    $results[] = lmc_pass('Unit console documents import script', str_contains($unitContent, 'CREATE_PRODUCTION_USERS_FROM_PRIVATE_JSON.ps1'));
    $results[] = lmc_pass('Unit console no password input', !preg_match('/type=["\']password["\']/', $unitContent));
}

$results[] = lmc_pass('public_html/config.php not created', !is_file($root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'config.php'));

$erpConfig = $root . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'erp-config.php';
$gitignore = is_file($root . DIRECTORY_SEPARATOR . '.gitignore') ? (string)file_get_contents($root . DIRECTORY_SEPARATOR . '.gitignore') : '';
$results[] = lmc_pass('private/erp-config.php still gitignored', str_contains($gitignore, 'private/erp-config.php'));

$httpPaths = ['', 'erp-v1-master-console.php', 'erp-v1-unit-access-console.php'];
foreach ($httpPaths as $rel) {
    $probe = lmc_http_ok($baseUrl . $rel);
    $label = $rel === '' ? '/' : $rel;
    $results[] = lmc_pass('HTTP 200: ' . $label, $probe['ok'], $probe['detail']);
}

$failed = array_filter($results, static fn(array $r): bool => !$r['pass']);
$passed = count($results) - count($failed);

echo "MOGHARE360 V1 Local Master Console Test\n";
echo str_repeat('-', 60) . "\n";
foreach ($results as $r) {
    $mark = $r['pass'] ? 'PASS' : 'FAIL';
    $detail = $r['detail'] !== '' ? ' — ' . $r['detail'] : '';
    echo "[{$mark}] {$r['name']}{$detail}\n";
}
echo str_repeat('-', 60) . "\n";
echo "Result: {$passed}/" . count($results) . " PASS\n";
exit(count($failed) > 0 ? 1 : 0);
