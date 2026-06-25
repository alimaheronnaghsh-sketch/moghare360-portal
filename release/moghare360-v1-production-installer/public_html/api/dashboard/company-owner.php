<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';

mogh_api_json_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mogh_api_fail('فقط POST مجاز است.', 405);
}

$tenant = mogh_tenant_resolve_from_request();
$endpoint = '/api/dashboard/company-owner';
$conn = mogh_tenant_db_connect();
$companyId = $tenant['company_id'];

try {
    $stats = [
        'customers' => 0,
        'jobcards' => 0,
        'online_requests_pending' => 0,
        'access_requests_pending' => 0,
        'payments_pending' => 0,
    ];

    foreach ([
        'customers' => "SELECT COUNT(*) AS c FROM dbo.erp_customers",
        'jobcards' => "SELECT COUNT(*) AS c FROM dbo.erp_jobcards",
        'online_requests_pending' => "SELECT COUNT(*) AS c FROM dbo.erp_customer_online_requests WHERE company_id = {$companyId} AND request_status = N'PENDING'",
        'access_requests_pending' => "SELECT COUNT(*) AS c FROM dbo.erp_user_access_requests WHERE company_id = {$companyId} AND request_status = N'PENDING'",
        'payments_pending' => "SELECT COUNT(*) AS c FROM dbo.erp_payments WHERE payment_status IN (N'PENDING', N'PARTIAL')",
    ] as $key => $sql) {
        $res = @odbc_exec($conn, $sql);
        if ($res !== false && ($row = odbc_fetch_array($res))) {
            $stats[$key] = (int)($row['c'] ?? 0);
        }
    }

    mogh_api_log_request($conn, $companyId, $endpoint, 'POST', 200, 'owner_dashboard');
    mogh_api_ok('خلاصه داشبورد مالک.', [
        'company' => $tenant,
        'summary' => $stats,
        'read_only' => true,
    ]);
} catch (Throwable) {
    mogh_api_fail('بارگذاری داشبورد ناموفق بود.', 500);
} finally {
    @odbc_close($conn);
}
