<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 Production Run Smoke Test
 */

$root = dirname(__DIR__);
$installPath = getenv('MOGHARE360_INSTALL_PATH') ?: 'C:\\xampp\\htdocs\\moghare360';
$baseUrl = rtrim(getenv('MOGHARE360_BASE_URL') ?: 'http://localhost:8080/moghare360/', '/') . '/';

function smoke_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function smoke_file_has_forbidden_wording(string $path): bool
{
    if (!is_file($path)) {
        return false;
    }
    $content = (string)file_get_contents($path);
    $forbidden = ['Not Production Installer', 'Demo only', 'Local only', 'Not SaaS'];
    foreach ($forbidden as $needle) {
        if (str_contains($content, $needle)) {
            return true;
        }
    }
    return false;
}

function smoke_zip_forbidden(string $zipPath): bool
{
    if (!is_file($zipPath) || !class_exists('ZipArchive')) {
        return false;
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return true;
    }
    $forbiddenNames = ['erp-config.php', 'config.php', 'mirror-config.php'];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = (string)$zip->getNameIndex($i);
        $leaf = basename(str_replace('\\', '/', $name));
        if (in_array($leaf, $forbiddenNames, true)) {
            $zip->close();
            return true;
        }
        if (preg_match('#(^|/)(private|uploads|backups|logs)(/|$)#i', $name)) {
            $zip->close();
            return true;
        }
    }
    $zip->close();
    return false;
}

function smoke_http_get(string $url): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'message' => 'curl_missing'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['ok' => $code >= 200 && $code < 500, 'code' => $code];
}

function smoke_http_json(string $url): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'message' => 'curl_missing'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    return ['ok' => $code >= 200 && $code < 500, 'code' => $code, 'json' => $decoded];
}

$results = [];

$configCandidates = [
    $installPath . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'erp-config.php',
    $root . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'erp-config.php',
];
$configExists = false;
foreach ($configCandidates as $cfg) {
    if (is_file($cfg)) {
        $configExists = true;
        break;
    }
}
$cfgGen = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'desktop-run-templates' . DIRECTORY_SEPARATOR . 'CREATE_LOCAL_CONFIG.ps1';
$results[] = smoke_pass(
    'Config exists or generator ready',
    $configExists || is_file($cfgGen)
);

$dbOk = false;
$saasTables = false;
try {
    require_once $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-saas-tenant-context.php';
    $conn = mogh_tenant_db_connect();
    $dbOk = is_resource($conn);
    if ($dbOk) {
        $res = @odbc_exec($conn, 'SELECT TOP 1 company_id FROM dbo.erp_companies');
        $saasTables = $res !== false;
        @odbc_close($conn);
    }
} catch (Throwable $e) {
    $results[] = smoke_pass('DB connection', false, $e->getMessage());
}
$results[] = smoke_pass('DB connection', $dbOk);
$results[] = smoke_pass('SaaS tenant table', $saasTables);

$apiHealth = smoke_http_json($baseUrl . 'api/mirror/health.php');
$results[] = smoke_pass('Mirror health API responds', $apiHealth['ok'], 'HTTP ' . ($apiHealth['code'] ?? 0));

$saasHealth = smoke_http_get($baseUrl . 'saas-health.php');
$results[] = smoke_pass('SaaS health page responds', $saasHealth['ok']);

$customerApi = smoke_http_json($baseUrl . 'api/customer/request.php');
$results[] = smoke_pass('Customer request API reachable', ($customerApi['code'] ?? 0) > 0);

$ownerApi = smoke_http_json($baseUrl . 'api/dashboard/company-owner.php');
$results[] = smoke_pass('Owner dashboard API reachable', ($ownerApi['code'] ?? 0) > 0);

$accessApi = smoke_http_json($baseUrl . 'api/access/request.php');
$results[] = smoke_pass('Access request API reachable', ($accessApi['code'] ?? 0) > 0);

$downloadPage = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'moghare360-release-download.php';
$results[] = smoke_pass('Release download page exists', is_file($downloadPage));
$results[] = smoke_pass('No forbidden wording in release page', !smoke_file_has_forbidden_wording($downloadPage));

$zips = [
    'moghare360-v1-production-installer.zip',
    'moghare360-v1-auto-deploy.zip',
    'moghare360-v1-saas-deploy.zip',
    'moghare360-mirror-site-package.zip',
    'moghare360-v1-production-final-delivery.zip',
];
foreach ($zips as $zipName) {
    $zipPath = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . $zipName;
    $exists = is_file($zipPath);
    $forbidden = $exists && smoke_zip_forbidden($zipPath);
    $results[] = smoke_pass("ZIP $zipName exists", $exists);
    $results[] = smoke_pass("ZIP $zipName no forbidden content", !$forbidden);
}

$lintFiles = [
    'public_html/includes/moghare360-release-package-helper.php',
    'public_html/moghare360-release-download.php',
    'public_html/saas-health.php',
];
foreach ($lintFiles as $rel) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($path)) {
        $results[] = smoke_pass("PHP lint $rel", false, 'missing');
        continue;
    }
    exec('"' . (getenv('PHP_BIN') ?: 'php') . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    $results[] = smoke_pass("PHP lint $rel", $code === 0);
}

// Optional safe write test
if ($dbOk && $saasTables) {
    try {
        $conn = mogh_tenant_db_connect();
        $tenant = mogh_tenant_resolve_from_request();
        $sql = "INSERT INTO dbo.erp_customer_online_requests
            (company_id, customer_name, mobile, vehicle_plate, service_note, request_status, source_channel)
            VALUES (?, N'TEST_V1_RUN_DO_NOT_USE', N'0000000000', N'TEST', N'Smoke test', N'PENDING', N'SMOKE_TEST')";
        $stmt = @odbc_prepare($conn, $sql);
        $writeOk = $stmt !== false && @odbc_execute($stmt, [$tenant['company_id']]);
        $results[] = smoke_pass('Safe write test record', $writeOk, 'TEST_V1_RUN_DO_NOT_USE');
        @odbc_close($conn);
    } catch (Throwable) {
        $results[] = smoke_pass('Safe write test record', false);
    }
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
    fwrite(STDERR, 'V1 PRODUCTION RUN SMOKE TEST FAILED' . PHP_EOL);
    exit(1);
}
echo 'V1 PRODUCTION RUN SMOKE TEST PASSED' . PHP_EOL;
exit(0);
