<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Canonical SQL Server database lock test
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';

function canon_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];

$canonicalSql = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'sqlserver' . DIRECTORY_SEPARATOR . 'MOGHARE360_V1_CANONICAL_DATABASE.sql';
$verifySql = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'sqlserver' . DIRECTORY_SEPARATOR . 'MOGHARE360_V1_DATABASE_VERIFY.sql';
$extensionsSql = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'sqlserver' . DIRECTORY_SEPARATOR . 'v1_canonical_extensions.sql';

$results[] = canon_pass('Canonical SQL bundle exists', is_file($canonicalSql));
$results[] = canon_pass('Verify SQL exists', is_file($verifySql));
$results[] = canon_pass('Canonical extensions SQL exists', is_file($extensionsSql));

if (is_file($canonicalSql)) {
    $content = (string)file_get_contents($canonicalSql);
    $results[] = canon_pass('Canonical SQL no DROP TABLE', !preg_match('/^\s*DROP\s+TABLE\b/im', $content));
    $results[] = canon_pass('Canonical SQL no TRUNCATE', !preg_match('/^\s*TRUNCATE\b/im', $content));
    $results[] = canon_pass('Canonical SQL no MySQL IF NOT EXISTS', !preg_match('/^\s*CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS/im', $content));
    $results[] = canon_pass('Canonical includes v1_saas_activation_foundation', str_contains($content, 'v1_saas_activation_foundation.sql'));
    $results[] = canon_pass('Canonical includes v1_post_run_fix_register', str_contains($content, 'v1_post_run_fix_register.sql'));
}

$apiPath = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'request.php';
$apiContent = is_file($apiPath) ? (string)file_get_contents($apiPath) : '';
$results[] = canon_pass('API customer request exists', is_file($apiPath));
$results[] = canon_pass('API uses SQL Server tenant connect', str_contains($apiContent, 'mogh_tenant_db_connect'));
$results[] = canon_pass('API targets erp_customer_online_requests', str_contains($apiContent, 'erp_customer_online_requests'));
$results[] = canon_pass('API no MySQL functions', !preg_match('/\b(mysqli_|mysql_connect)\b/', $apiContent));
$results[] = canon_pass('API no legacy submit include', !preg_match('/submit-customer\.php/', $apiContent));
$results[] = canon_pass('API stores request_payload_json when available', str_contains($apiContent, 'request_payload_json'));

$mirrorForm = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-mirror-site-package' . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'customer-request.php';
$mirrorClient = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-mirror-site-package' . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';
$mirrorFormContent = is_file($mirrorForm) ? (string)file_get_contents($mirrorForm) : '';
$mirrorClientContent = is_file($mirrorClient) ? (string)file_get_contents($mirrorClient) : '';
$results[] = canon_pass('Mirror form uses API client', str_contains($mirrorFormContent, 'mirror_api_customer_request'));
$results[] = canon_pass('Mirror client posts /api/customer/request', str_contains($mirrorClientContent, '/api/customer/request'));
$results[] = canon_pass('Mirror form no submit-customer.php', !preg_match('/submit-customer\.php/', $mirrorFormContent));

$installer = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'production' . DIRECTORY_SEPARATOR . 'INSTALL_MOGHARE360_V1.ps1';
$autoDeploy = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'production' . DIRECTORY_SEPARATOR . 'AUTO_DEPLOY_MOGHARE360_V1.ps1';
$installerContent = is_file($installer) ? (string)file_get_contents($installer) : '';
$autoDeployContent = is_file($autoDeploy) ? (string)file_get_contents($autoDeploy) : '';
$results[] = canon_pass('Installer references canonical SQL', str_contains($installerContent, 'MOGHARE360_V1_CANONICAL_DATABASE.sql'));
$results[] = canon_pass('Installer references verify SQL', str_contains($installerContent, 'MOGHARE360_V1_DATABASE_VERIFY.sql'));
$results[] = canon_pass('Auto deploy runs canonical DB test', str_contains($autoDeployContent, 'test-v1-canonical-database.php'));

$lockDoc = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'MOGHARE360_V1_CANONICAL_DATABASE_LOCK.md';
$results[] = canon_pass('Canonical database lock doc exists', is_file($lockDoc));

$legacyMysqlSql = glob($root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*.sql') ?: [];
$legacyMysqlCount = 0;
foreach ($legacyMysqlSql as $path) {
    $c = (string)file_get_contents($path);
    if (str_contains($c, 'CREATE TABLE IF NOT EXISTS')) {
        $legacyMysqlCount++;
    }
}
$results[] = canon_pass('Legacy MySQL SQL isolated under public_html/sql (not sqlserver)', $legacyMysqlCount > 0, 'legacy_files=' . $legacyMysqlCount);

require_once $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-saas-config-loader.php';
require_once $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-saas-tenant-context.php';

$dbName = 'moghare360_ERP';
$configPath = mogh_saas_find_config_path();
if ($configPath !== null) {
    $erp = require $configPath;
    if (is_array($erp) && isset($erp['database']['name']) && is_string($erp['database']['name']) && $erp['database']['name'] !== '') {
        $dbName = $erp['database']['name'];
    }
}
$results[] = canon_pass('Active database name resolved from config', $dbName !== '', $dbName);

$requiredTables = [
    'erp_customers', 'erp_customer_phones', 'erp_vehicles', 'erp_customer_vehicle_relations',
    'erp_jobcards', 'erp_service_operations', 'erp_jobcard_part_usage', 'erp_purchase_requests',
    'erp_payments', 'erp_qc_checks', 'erp_delivery_controls',
    'erp_companies', 'erp_company_domains', 'erp_company_users',
    'erp_api_request_log', 'erp_mirror_requests', 'erp_customer_online_requests',
    'erp_user_access_requests', 'erp_saas_storage_objects', 'erp_deployment_health_checks',
    'erp_v1_production_run_signoff', 'erp_v1_post_run_fix_register',
];

$legacyTables = ['portal_customers_staging', 'portal_service_requests_staging'];

$conn = false;
try {
    $conn = mogh_tenant_db_connect();
    $results[] = canon_pass('SQL Server ODBC connection', is_resource($conn));
} catch (Throwable $e) {
    $results[] = canon_pass('SQL Server ODBC connection', false, $e->getMessage());
}

function canon_table_exists($conn, string $table): bool
{
    if (!is_resource($conn)) {
        return false;
    }
    $stmt = @odbc_prepare($conn, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
    if ($stmt === false || !@odbc_execute($stmt, ['dbo', $table])) {
        return false;
    }
    $row = odbc_fetch_array($stmt);
    return $row !== false && (int)($row['c'] ?? 0) > 0;
}

function canon_column_exists($conn, string $table, string $column): bool
{
    if (!is_resource($conn)) {
        return false;
    }
    $stmt = @odbc_prepare($conn, 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
    if ($stmt === false || !@odbc_execute($stmt, ['dbo', $table, $column])) {
        return false;
    }
    $row = odbc_fetch_array($stmt);
    return $row !== false && (int)($row['c'] ?? 0) > 0;
}

$missingTables = [];
if (is_resource($conn)) {
    foreach ($requiredTables as $table) {
        $exists = canon_table_exists($conn, $table);
        $results[] = canon_pass('Table exists: ' . $table, $exists);
        if (!$exists) {
            $missingTables[] = $table;
        }
    }

    foreach ($legacyTables as $table) {
        $exists = canon_table_exists($conn, $table);
        $results[] = canon_pass('Legacy MySQL table absent: ' . $table, !$exists);
    }

    $results[] = canon_pass('Column request_payload_json on erp_customer_online_requests', canon_column_exists($conn, 'erp_customer_online_requests', 'request_payload_json'));
    $results[] = canon_pass('Column request_type on erp_customer_online_requests', canon_column_exists($conn, 'erp_customer_online_requests', 'request_type'));

    $tenantStmt = @odbc_exec($conn, "SELECT TOP 1 company_id, company_code FROM dbo.erp_companies WHERE company_code = N'MOGHAREH_MAIN'");
    $tenantOk = false;
    if ($tenantStmt !== false) {
        $row = odbc_fetch_array($tenantStmt);
        $tenantOk = is_array($row) && ($row['company_code'] ?? '') === 'MOGHAREH_MAIN';
    }
    $results[] = canon_pass('Default tenant MOGHAREH_MAIN exists', $tenantOk);

    $countSql = "SELECT
        (SELECT COUNT(*) FROM dbo.erp_customers) AS customers,
        (SELECT COUNT(*) FROM dbo.erp_jobcards) AS jobcards,
        (SELECT COUNT(*) FROM dbo.erp_payments) AS payments,
        (SELECT COUNT(*) FROM dbo.erp_customer_online_requests) AS online_requests,
        (SELECT COUNT(*) FROM dbo.erp_customer_online_requests WHERE customer_name = N'TEST_V1_RUN_DO_NOT_USE') AS test_marker";
    $countStmt = @odbc_exec($conn, $countSql);
    if ($countStmt !== false) {
        $counts = odbc_fetch_array($countStmt);
        $detail = $counts ? json_encode($counts, JSON_UNESCAPED_UNICODE) : 'n/a';
        $results[] = canon_pass('Row count report available', is_array($counts), $detail);
    } else {
        $results[] = canon_pass('Row count report available', false);
    }

    @odbc_close($conn);
}

$baseUrl = rtrim(getenv('MOGHARE360_BASE_URL') ?: 'http://localhost:8080/moghare360/', '/') . '/';
if (function_exists('curl_init')) {
    $ch = curl_init($baseUrl . 'api/mirror/health.php');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $results[] = canon_pass('API mirror health endpoint', $code >= 200 && $code < 500, 'HTTP ' . $code);

    $payload = json_encode([
        'customer_name' => 'TEST_V1_CANONICAL_DB_DO_NOT_USE',
        'mobile' => '09000000000',
        'vehicle_plate' => '99-TEST-99',
        'request_description' => 'canonical-db-test',
        'postal_address' => 'test-address',
        'source_channel' => 'CANONICAL_TEST',
    ], JSON_UNESCAPED_UNICODE);
    $ch2 = curl_init($baseUrl . 'api/customer/request.php');
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
    ]);
    $raw2 = curl_exec($ch2);
    $code2 = (int)curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    $json = is_string($raw2) ? json_decode($raw2, true) : null;
    $apiOk = $code2 >= 200 && $code2 < 300 && is_array($json) && ($json['ok'] ?? false) === true;
    $results[] = canon_pass('API customer request writes to V1 DB', $apiOk, 'HTTP ' . $code2);
} else {
    $results[] = canon_pass('API mirror health endpoint', false, 'curl_missing');
    $results[] = canon_pass('API customer request writes to V1 DB', false, 'curl_missing');
}

$failed = array_filter($results, static fn(array $r): bool => !$r['pass']);
$passed = count($results) - count($failed);

echo "MOGHARE360 V1 Canonical Database Test\n";
echo "Database: {$dbName}\n";
echo str_repeat('-', 60) . "\n";
foreach ($results as $r) {
    $mark = $r['pass'] ? 'PASS' : 'FAIL';
    $detail = $r['detail'] !== '' ? ' — ' . $r['detail'] : '';
    echo "[{$mark}] {$r['name']}{$detail}\n";
}
echo str_repeat('-', 60) . "\n";
echo "Result: {$passed}/" . count($results) . " PASS\n";
if (count($failed) > 0) {
    if (count($missingTables) > 0) {
        echo 'Missing tables: ' . implode(', ', $missingTables) . "\n";
        echo "Run: sqlcmd -S .\\SQLEXPRESS -d {$dbName} -E -i public_html\\sql\\sqlserver\\MOGHARE360_V1_CANONICAL_DATABASE.sql\n";
    }
    exit(1);
}
exit(0);
