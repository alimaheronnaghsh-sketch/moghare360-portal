<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-saas-config-loader.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-saas-tenant-context.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-saas-storage-adapter.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

$tenant = mogh_tenant_resolve_from_request();
$dbStatus = 'unknown';
$tablesStatus = 'unknown';

try {
    $conn = mogh_tenant_db_connect();
    $dbStatus = is_resource($conn) ? 'connected' : 'failed';
    if (is_resource($conn)) {
        $check = @odbc_exec($conn, 'SELECT TOP 1 company_id FROM dbo.erp_companies');
        $tablesStatus = $check !== false ? 'ready' : 'pending_migration';
        @odbc_close($conn);
    }
} catch (Throwable) {
    $dbStatus = 'failed';
}

echo json_encode([
    'ok' => $dbStatus === 'connected',
    'service' => 'MOGHARE360 SaaS Health',
    'version' => mogh_saas_api_version(),
    'saas_enabled' => mogh_saas_is_enabled(),
    'company' => $tenant,
    'database' => $dbStatus,
    'saas_tables' => $tablesStatus,
    'storage_mode' => mogh_storage_mode(),
    'timestamp' => gmdate('c'),
], JSON_UNESCAPED_UNICODE);
