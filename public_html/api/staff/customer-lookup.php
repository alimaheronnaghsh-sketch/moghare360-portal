<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-staff-walkin-helper.php';

mogh_api_json_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mogh_api_fail('فقط POST مجاز است.', 405);
}

$body = mogh_api_read_json_body();
$csrfToken = isset($body['erp_csrf_token']) ? (string)$body['erp_csrf_token'] : null;
if (!m360_reception_csrf_is_valid($csrfToken)) {
    mogh_api_fail('نشست امنیتی معتبر نیست.', 403);
}

$conn = customer_core_db();
if (!is_resource($conn)) {
    mogh_api_fail('اتصال به پایگاه داده برقرار نشد.', 503);
}

$actor = m360_walkin_require_actor($conn);
if (!$actor['ok']) {
    mogh_api_fail((string)$actor['message'], (int)$actor['status']);
}

/** @return array<string, string>|null */
function m360_staff_lookup_customer_row($conn, int $companyId, string $mobile, string $nationalId): ?array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, 'erp_customers')) {
        return null;
    }

    $columns = ['customer_id', 'full_name'];
    foreach (['national_id', 'primary_mobile', 'secondary_mobile', 'address', 'city', 'notes'] as $column) {
        if (customer_core_column_exists($conn, 'erp_customers', $column)) {
            $columns[] = $column;
        }
    }

    $where = [];
    $params = [];
    if ($mobile !== '') {
        if (customer_core_column_exists($conn, 'erp_customers', 'primary_mobile')) {
            $where[] = 'primary_mobile = ?';
            $params[] = $mobile;
        }
        if (customer_core_column_exists($conn, 'erp_customers', 'secondary_mobile')) {
            $where[] = 'secondary_mobile = ?';
            $params[] = $mobile;
        }
    }
    if ($nationalId !== '' && customer_core_column_exists($conn, 'erp_customers', 'national_id')) {
        $where[] = 'national_id = ?';
        $params[] = $nationalId;
    }
    if ($where === []) {
        return null;
    }

    $companyClause = '';
    $companyParams = [];
    if ($companyId > 0 && customer_core_column_exists($conn, 'erp_customers', 'company_id')) {
        $companyClause = 'company_id = ? AND ';
        $companyParams[] = $companyId;
    }

    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 ' . implode(', ', $columns) . ' FROM dbo.erp_customers WHERE ' . $companyClause . '(' . implode(' OR ', $where) . ') ORDER BY customer_id DESC',
        array_merge($companyParams, $params)
    );
    $row = is_array($rows[0] ?? null) ? $rows[0] : null;
    if ($row === null && $mobile !== '') {
        $customerId = m360_online_req_resolve_customer_id($conn, $companyId, $mobile);
        if ($customerId !== null && $customerId > 0) {
            $rows = customer_core_fetch_rows(
                $conn,
                'SELECT TOP 1 ' . implode(', ', $columns) . ' FROM dbo.erp_customers WHERE customer_id = ?',
                [$customerId]
            );
            $row = is_array($rows[0] ?? null) ? $rows[0] : null;
        }
    }
    if ($row === null) {
        return null;
    }

    $normalized = [];
    foreach ($row as $key => $value) {
        $normalized[strtolower((string)$key)] = $value === null ? '' : (string)$value;
    }

    return $normalized;
}

$mobile = m360_walkin_normalize_mobile(trim((string)($body['mobile'] ?? $body['phone'] ?? '')));
$nationalId = preg_replace('/\D+/', '', m360_walkin_digits_to_ascii(trim((string)($body['national_id'] ?? '')))) ?? '';
if ($mobile === '' && $nationalId === '') {
    mogh_api_fail('شماره موبایل یا کد ملی مشتری را وارد کنید.', 422);
}
if ($mobile !== '' && !preg_match('/^09\d{9}$/', $mobile)) {
    mogh_api_fail('شماره موبایل معتبر نیست.', 422);
}

$customerRow = m360_staff_lookup_customer_row($conn, (int)$actor['company_id'], $mobile, $nationalId);
if ($customerRow === null) {
    mogh_api_ok('مشتری پیدا نشد.', [
        'customer_exists' => false,
        'profile_required' => true,
        'profile' => [
            'primary_mobile' => $mobile,
            'national_id' => $nationalId,
        ],
        'vehicles' => [],
        'vehicles_out_of_scope' => [],
    ], 200);
}

$customerId = (int)($customerRow['customer_id'] ?? 0);
$nameParts = m360_walkin_split_full_name((string)($customerRow['full_name'] ?? ''));
$ext = m360_pr02b_decode_profile_ext_notes($customerRow['notes'] ?? '');
$allVehicles = m360_pr02b_list_customer_vehicles($conn, $customerId);
$vehicles = array_values(array_filter($allVehicles, static fn(array $v): bool => !empty($v['supported'])));
$vehiclesOutOfScope = array_values(array_filter($allVehicles, static fn(array $v): bool => empty($v['supported'])));
$primaryMobile = trim((string)($customerRow['primary_mobile'] ?? ''));
if ($primaryMobile === '') {
    $primaryMobile = $mobile;
}

mogh_api_ok('مشتری پیدا شد.', [
    'customer_exists' => true,
    'profile_required' => false,
    'customer_id' => $customerId,
    'profile' => [
        'customer_id' => $customerId,
        'first_name' => $nameParts[0],
        'last_name' => $nameParts[1],
        'full_name' => trim((string)($customerRow['full_name'] ?? '')),
        'national_id' => trim((string)($customerRow['national_id'] ?? $nationalId)),
        'primary_mobile' => $primaryMobile,
        'second_phone' => trim((string)($customerRow['secondary_mobile'] ?? '')),
        'residence_address' => trim((string)($customerRow['address'] ?? '')),
        'city' => trim((string)($customerRow['city'] ?? '')),
        'vehicle_delivery_address' => trim((string)($ext['vehicle_delivery_address'] ?? '')),
        'authorized_receiver_name' => trim((string)($ext['authorized_receiver_name'] ?? '')),
        'authorized_receiver_phone' => trim((string)($ext['authorized_receiver_phone'] ?? '')),
    ],
    'vehicles' => $vehicles,
    'vehicles_out_of_scope' => $vehiclesOutOfScope,
], 200);
