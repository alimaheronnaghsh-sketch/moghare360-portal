<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';

mogh_api_json_headers();

$tenant = mogh_tenant_resolve_from_request();
$endpoint = '/api/mirror/health';

try {
    $conn = mogh_tenant_db_connect();
    $dbOk = is_resource($conn);
    $tablesOk = false;

    if ($dbOk) {
        $check = @odbc_exec($conn, "SELECT TOP 1 company_id FROM dbo.erp_companies");
        $tablesOk = $check !== false;
        mogh_api_log_request($conn, $tenant['company_id'], $endpoint, $_SERVER['REQUEST_METHOD'] ?? 'GET', 200, 'health');
        @odbc_close($conn);
    }

    mogh_api_ok('Mirror health OK', [
        'company' => $tenant,
        'database' => $dbOk ? 'connected' : 'failed',
        'saas_tables' => $tablesOk ? 'ready' : 'pending_migration',
        'storage_mode' => mogh_storage_mode(),
        'saas_enabled' => mogh_saas_is_enabled(),
        'api_status' => 'active',
    ]);
} catch (Throwable $e) {
    mogh_api_fail('بررسی سلامت ناموفق بود.', 503, [
        'company' => $tenant,
        'storage_mode' => mogh_storage_mode(),
    ]);
}
