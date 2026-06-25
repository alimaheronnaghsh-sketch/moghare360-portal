<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';

mogh_api_json_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mogh_api_fail('فقط POST مجاز است.', 405);
}

$body = mogh_api_read_json_body();
mogh_api_require_csrf_if_present($body, 'access_request');

$tenant = mogh_tenant_resolve_from_request();
$name = mogh_api_sanitize_string($body['requester_name'] ?? $body['name'] ?? '', 200);
$mobile = mogh_api_sanitize_string($body['requester_mobile'] ?? $body['mobile'] ?? '', 30);
$role = mogh_api_sanitize_string($body['requested_role'] ?? $body['role'] ?? 'STAFF', 80);
$note = mogh_api_sanitize_string($body['request_note'] ?? $body['note'] ?? '', 2000);
$endpoint = '/api/access/request';

if ($name === '') {
    mogh_api_fail('نام درخواست‌دهنده الزامی است.', 422);
}

$conn = mogh_tenant_db_connect();

try {
    $sql = 'INSERT INTO dbo.erp_user_access_requests
        (company_id, requester_name, requester_mobile, requested_role, request_status, request_note)
        VALUES (?, ?, ?, ?, N\'PENDING\', ?)';
    $stmt = odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [
        $tenant['company_id'], $name, $mobile, $role, $note,
    ])) {
        throw new RuntimeException('access_request_failed');
    }

    mogh_api_log_request($conn, $tenant['company_id'], $endpoint, 'POST', 201, 'access_request');
    mogh_api_ok('درخواست دسترسی ثبت شد — کاربر بدون تأیید مالک ایجاد نمی‌شود.', [
        'company_id' => $tenant['company_id'],
        'requested_role' => $role,
        'status' => 'PENDING',
    ], 201);
} catch (Throwable) {
    mogh_api_fail('ثبت درخواست دسترسی ناموفق بود.', 500);
} finally {
    @odbc_close($conn);
}
