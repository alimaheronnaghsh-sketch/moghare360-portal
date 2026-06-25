<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';

mogh_api_json_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mogh_api_fail('فقط POST مجاز است.', 405);
}

$body = mogh_api_read_json_body();
mogh_api_require_csrf_if_present($body, 'customer_request');

$tenant = mogh_tenant_resolve_from_request();
$name = mogh_api_sanitize_string($body['customer_name'] ?? $body['name'] ?? '', 200);
$mobile = mogh_api_sanitize_string($body['mobile'] ?? $body['phone'] ?? '', 30);
$plate = mogh_api_sanitize_string($body['vehicle_plate'] ?? $body['plate'] ?? '', 50);
$note = mogh_api_sanitize_string($body['service_note'] ?? $body['note'] ?? '', 2000);

if ($name === '') {
    mogh_api_fail('نام مشتری الزامی است.', 422);
}

$endpoint = '/api/customer/request';
$conn = mogh_tenant_db_connect();

try {
    $sql = 'INSERT INTO dbo.erp_customer_online_requests
        (company_id, customer_name, mobile, vehicle_plate, service_note, request_status, source_channel)
        VALUES (?, ?, ?, ?, ?, N\'PENDING\', N\'MIRROR\')';
    $stmt = odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [
        $tenant['company_id'], $name, $mobile, $plate, $note,
    ])) {
        throw new RuntimeException('ثبت درخواست ناموفق بود.');
    }

    $idSql = 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT) AS new_id';
    $idRes = @odbc_exec($conn, $idSql);
    $newId = 0;
    if ($idRes !== false && ($row = odbc_fetch_array($idRes))) {
        $newId = (int)($row['new_id'] ?? 0);
    }

    $mirrorSql = 'INSERT INTO dbo.erp_mirror_requests (company_id, request_type, payload_json, response_status)
                  VALUES (?, N\'CUSTOMER_REQUEST\', ?, 201)';
    $payload = json_encode([
        'online_request_id' => $newId,
        'customer_name' => $name,
    ], JSON_UNESCAPED_UNICODE);
    $mStmt = odbc_prepare($conn, $mirrorSql);
    if ($mStmt !== false) {
        @odbc_execute($mStmt, [$tenant['company_id'], $payload]);
    }

    mogh_api_log_request($conn, $tenant['company_id'], $endpoint, 'POST', 201, 'customer_request');
    mogh_api_ok('درخواست مشتری ثبت شد.', [
        'online_request_id' => $newId,
        'company_id' => $tenant['company_id'],
        'status' => 'PENDING',
    ], 201);
} catch (Throwable) {
    mogh_api_log_request($conn, $tenant['company_id'], $endpoint, 'POST', 500, 'customer_request_failed');
    mogh_api_fail('ثبت درخواست مشتری ناموفق بود.', 500);
} finally {
    @odbc_close($conn);
}
