<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';

mogh_api_json_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mogh_api_fail('فقط POST مجاز است.', 405);
}

$body = mogh_api_read_json_body();
mogh_api_require_csrf_if_present($body, 'customer_request');

$tenant = mogh_tenant_resolve_from_request();
$name = mogh_api_sanitize_string(
    $body['customer_name'] ?? $body['full_name'] ?? $body['name'] ?? '',
    200
);
$mobile = mogh_api_sanitize_string($body['mobile'] ?? $body['phone'] ?? '', 30);

m360_otp_session_start();
if (!m360_otp_is_verified($mobile)) {
    mogh_api_fail('شماره موبایل تأیید نشده است.', 403);
}
$tokenBody = trim((string)($body['otp_verified_token'] ?? ''));
$tokenSession = m360_otp_verified_token();
if ($tokenBody !== '' && $tokenSession !== '' && !hash_equals($tokenSession, $tokenBody)) {
    mogh_api_fail('شماره موبایل تأیید نشده است.', 403);
}

$customerFlow = mogh_api_sanitize_string($body['customer_flow'] ?? 'new', 20);
$isReturningCustomer = $customerFlow === 'returning';
$verifiedCustomerName = mogh_api_sanitize_string($body['verified_customer_name'] ?? '', 200);

if (!$isReturningCustomer && $name === '') {
    mogh_api_fail('نام مشتری الزامی است.', 422);
}
if ($isReturningCustomer && $name === '') {
    $name = $verifiedCustomerName !== '' ? $verifiedCustomerName : 'مشتری گرامی';
}

$province = mogh_api_sanitize_string($body['province'] ?? '', 80);
$city = mogh_api_sanitize_string($body['city'] ?? '', 80);
if (!$isReturningCustomer && ($province === '' || $city === '')) {
    mogh_api_fail('استان و شهر الزامی است.', 422);
}

$plate = mogh_api_sanitize_string(
    $body['vehicle_plate'] ?? $body['plate_display'] ?? $body['plate_number'] ?? $body['plate'] ?? '',
    50
);

$description = mogh_api_sanitize_string(
    $body['request_description'] ?? $body['service_description'] ?? $body['service_note'] ?? $body['note'] ?? '',
    2000
);

$plateParts = $body['plate_parts'] ?? null;
if (!is_array($plateParts)) {
    $plateParts = array_filter([
        'left_2' => mogh_api_sanitize_string($body['plate_left_2_digits'] ?? '', 4),
        'letter' => mogh_api_sanitize_string($body['plate_letter'] ?? '', 4),
        'middle_3' => mogh_api_sanitize_string($body['plate_middle_3_digits'] ?? '', 4),
        'region_2' => mogh_api_sanitize_string($body['plate_region_2_digits'] ?? '', 4),
    ], static fn($v) => $v !== '');
}

$extra = [
    'national_id' => mogh_api_sanitize_string($body['national_id'] ?? $body['national_code'] ?? '', 20),
    'province' => mogh_api_sanitize_string($body['province'] ?? '', 80),
    'city' => mogh_api_sanitize_string($body['city'] ?? '', 80),
    'brand' => mogh_api_sanitize_string($body['brand'] ?? $body['vehicle_brand'] ?? '', 80),
    'vehicle_brand' => mogh_api_sanitize_string($body['vehicle_brand'] ?? $body['brand'] ?? '', 80),
    'model' => mogh_api_sanitize_string($body['model'] ?? $body['vehicle_model'] ?? '', 80),
    'vehicle_class' => mogh_api_sanitize_string($body['vehicle_class'] ?? '', 80),
    'vehicle_year_pair' => mogh_api_sanitize_string($body['vehicle_year_pair'] ?? '', 20),
    'vin' => mogh_api_sanitize_string($body['vin'] ?? '', 30),
    'odometer_km' => mogh_api_sanitize_string((string)($body['odometer_km'] ?? ''), 20),
    'request_type' => mogh_api_sanitize_string($body['request_type'] ?? '', 80),
    'visit_date' => mogh_api_sanitize_string($body['visit_date'] ?? '', 20),
    'plate_display' => mogh_api_sanitize_string($body['plate_display'] ?? $plate, 50),
    'address' => mogh_api_sanitize_string($body['address'] ?? $body['postal_address'] ?? '', 300),
    'extra_contact_info' => mogh_api_sanitize_string($body['extra_contact_info'] ?? '', 500),
    'job_title' => mogh_api_sanitize_string($body['job_title'] ?? '', 100),
    'birth_date' => mogh_api_sanitize_string($body['birth_date'] ?? '', 20),
    'source' => mogh_api_sanitize_string($body['source'] ?? 'MIRROR', 80),
];

$payloadData = array_merge(
    ['customer_name' => $name, 'mobile' => $mobile, 'vehicle_plate' => $plate, 'service_note' => $description, 'customer_flow' => $customerFlow],
    array_filter($extra, static fn($v) => $v !== '')
);
if ($plateParts !== []) {
    $payloadData['plate_parts'] = $plateParts;
}

$plateDigitKeys = [
    'plate_first_digit_1', 'plate_first_digit_2',
    'plate_middle_digit_1', 'plate_middle_digit_2', 'plate_middle_digit_3',
    'plate_region_digit_1', 'plate_region_digit_2',
];
foreach ($plateDigitKeys as $digitKey) {
    $val = mogh_api_sanitize_string($body[$digitKey] ?? '', 4);
    if ($val !== '') {
        $extra[$digitKey] = $val;
        $payloadData[$digitKey] = $val;
    }
}

$noteParts = [];
if ($description !== '') {
    $noteParts[] = $description;
}
$extraLines = [];
foreach ($extra as $key => $val) {
    if ($val !== '') {
        $extraLines[] = $key . ': ' . $val;
    }
}
if ($extraLines !== []) {
    $noteParts[] = implode("\n", $extraLines);
}
$note = mogh_api_sanitize_string(implode("\n\n", $noteParts), 2000);
$requestType = mogh_api_sanitize_string($body['request_type'] ?? '', 80);
$sourceChannel = mogh_api_sanitize_string($body['source_channel'] ?? $extra['source'] ?? 'MIRROR', 80);
$payloadJson = json_encode($payloadData, JSON_UNESCAPED_UNICODE);
if ($payloadJson === false) {
    $payloadJson = '{}';
}

$endpoint = '/api/customer/request';
$conn = mogh_tenant_db_connect();

function mogh_api_table_has_column($conn, string $table, string $column): bool
{
    if (!is_resource($conn)) {
        return false;
    }
    $sql = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=? AND COLUMN_NAME=?";
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$table, $column])) {
        return false;
    }
    $row = odbc_fetch_array($stmt);
    return $row !== false && (int)($row['c'] ?? 0) > 0;
}

try {
    $hasPayload = mogh_api_table_has_column($conn, 'erp_customer_online_requests', 'request_payload_json');
    $hasReqType = mogh_api_table_has_column($conn, 'erp_customer_online_requests', 'request_type');

    if ($hasPayload && $hasReqType) {
        $sql = 'INSERT INTO dbo.erp_customer_online_requests
            (company_id, customer_name, mobile, vehicle_plate, service_note, request_status, source_channel, request_type, request_payload_json)
            VALUES (?, ?, ?, ?, ?, N\'PENDING\', ?, ?, ?)';
        $params = [$tenant['company_id'], $name, $mobile, $plate, $note, $sourceChannel, $requestType, $payloadJson];
    } elseif ($hasPayload) {
        $sql = 'INSERT INTO dbo.erp_customer_online_requests
            (company_id, customer_name, mobile, vehicle_plate, service_note, request_status, source_channel, request_payload_json)
            VALUES (?, ?, ?, ?, ?, N\'PENDING\', ?, ?)';
        $params = [$tenant['company_id'], $name, $mobile, $plate, $note, $sourceChannel, $payloadJson];
    } else {
        $sql = 'INSERT INTO dbo.erp_customer_online_requests
            (company_id, customer_name, mobile, vehicle_plate, service_note, request_status, source_channel)
            VALUES (?, ?, ?, ?, ?, N\'PENDING\', ?)';
        $params = [$tenant['company_id'], $name, $mobile, $plate, $note, $sourceChannel];
    }

    $stmt = odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
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
        'request_payload_json' => $hasPayload ? json_decode($payloadJson, true) : null,
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
