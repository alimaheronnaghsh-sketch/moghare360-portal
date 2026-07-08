<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-customer-online-submit-helper.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-helper.php';

mogh_api_json_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mogh_api_fail('فقط POST مجاز است.', 405);
}

$body = mogh_api_read_json_body();
mogh_api_require_csrf_if_present($body, 'customer_request');

$tenant = mogh_tenant_resolve_from_request();
$conn = customer_core_db();
$endpoint = '/api/customer/request';

try {
    if (!is_resource($conn)) {
        mogh_api_fail('اتصال به پایگاه داده برقرار نشد.', 503, ['error_code' => 'db_connection_failed', 'step' => 'm360_section_submit']);
    }
    if (!isset($body['source_channel']) || trim((string)$body['source_channel']) === '' || trim((string)$body['source_channel']) === 'PUBLIC_WEB') {
        $body['source_channel'] = M360_ONLINE_REQ_SOURCE_PUBLIC;
    }
    $companyId = m360_reception_default_company_id($conn);
    $result = m360_customer_online_submit($conn, $companyId, $body);
    if (!$result['ok']) {
        mogh_api_log_request($conn, $tenant['company_id'], $endpoint, 'POST', 403, 'customer_request_otp_or_validation');
        mogh_api_fail($result['message'], 403, [
            'error_code' => (string)($result['error_code'] ?? 'submit_failed'),
            'step' => (string)($result['step'] ?? 'm360_section_submit'),
        ]);
    }

    $newId = (int)$result['online_request_id'];
    $mirrorSql = 'INSERT INTO dbo.erp_mirror_requests (company_id, request_type, payload_json, response_status)
                  VALUES (?, N\'CUSTOMER_REQUEST\', ?, 201)';
    $mirrorPayload = json_encode([
        'online_request_id' => $newId,
        'customer_id' => $result['customer_id'],
        'vehicle_id' => $result['vehicle_id'],
        'otp_verified' => true,
    ], JSON_UNESCAPED_UNICODE);
    $mStmt = odbc_prepare($conn, $mirrorSql);
    if ($mStmt !== false) {
        @odbc_execute($mStmt, [$tenant['company_id'], $mirrorPayload]);
    }

    mogh_api_log_request($conn, $tenant['company_id'], $endpoint, 'POST', 201, 'customer_request');
    mogh_api_ok($result['message'], [
        'online_request_id' => $newId,
        'company_id' => $tenant['company_id'],
        'customer_id' => $result['customer_id'],
        'vehicle_id' => $result['vehicle_id'],
        'otp_verified' => true,
        'profile_required' => false,
    ], 201);
} catch (Throwable) {
    mogh_api_log_request($conn, $tenant['company_id'], $endpoint, 'POST', 500, 'customer_request_failed');
    mogh_api_fail('ثبت درخواست مشتری ناموفق بود.', 500);
} finally {
    @odbc_close($conn);
}
