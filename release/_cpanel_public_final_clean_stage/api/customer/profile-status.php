<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';

mogh_api_json_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mogh_api_fail('فقط POST مجاز است.', 405);
}

$body = mogh_api_read_json_body();
$mobile = trim((string)($body['mobile'] ?? $body['phone'] ?? ''));
$normalized = m360_otp_normalize_phone($mobile);
if ($normalized === null) {
    mogh_api_fail('شماره موبایل معتبر نیست.', 422);
}

m360_otp_session_start();
if (!m360_otp_is_verified($normalized)) {
    mogh_api_fail('شماره موبایل تأیید نشده است.', 403);
}

function m360_profile_table_has_column($conn, string $table, string $column): bool
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

function m360_profile_mask_name(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return 'مشتری گرامی';
    }
    $parts = preg_split('/\s+/u', $name) ?: [];
    $first = $parts[0] ?? $name;
    if (mb_strlen($first) <= 1) {
        return $first . '***';
    }
    return mb_substr($first, 0, 1) . '***';
}

function m360_profile_mask_mobile(string $mobile): string
{
    if (strlen($mobile) < 7) {
        return $mobile;
    }
    return substr($mobile, 0, 4) . '***' . substr($mobile, -3);
}

/** @return array{full_name:string,last_vehicle:string,source:string}|null */
function m360_profile_lookup_customer($conn, int $companyId, string $mobile): ?array
{
    if (!is_resource($conn)) {
        return null;
    }

    if (m360_profile_table_has_column($conn, 'erp_customers', 'primary_mobile')) {
        $hasCompany = m360_profile_table_has_column($conn, 'erp_customers', 'company_id');
        $sql = $hasCompany
            ? 'SELECT TOP 1 customer_id, full_name FROM dbo.erp_customers WHERE company_id = ? AND primary_mobile = ?'
            : 'SELECT TOP 1 customer_id, full_name FROM dbo.erp_customers WHERE primary_mobile = ?';
        $params = $hasCompany ? [$companyId, $mobile] : [$mobile];
        $stmt = @odbc_prepare($conn, $sql);
        if ($stmt !== false && @odbc_execute($stmt, $params)) {
            $row = odbc_fetch_array($stmt);
            if ($row !== false) {
                $customerId = (int)($row['customer_id'] ?? 0);
                $fullName = trim((string)($row['full_name'] ?? ''));
                $lastVehicle = '';
                if ($customerId > 0 && m360_profile_table_has_column($conn, 'erp_customer_vehicle_relations', 'customer_id')) {
                    $vSql = 'SELECT TOP 1 v.brand, v.model, v.plate_number
                             FROM dbo.erp_customer_vehicle_relations r
                             JOIN dbo.erp_vehicles v ON v.vehicle_id = r.vehicle_id
                             WHERE r.customer_id = ?
                             ORDER BY r.is_primary_owner DESC, r.relation_id DESC';
                    $vStmt = @odbc_prepare($conn, $vSql);
                    if ($vStmt !== false && @odbc_execute($vStmt, [$customerId])) {
                        $vRow = odbc_fetch_array($vStmt);
                        if ($vRow !== false) {
                            $brand = trim((string)($vRow['brand'] ?? ''));
                            $model = trim((string)($vRow['model'] ?? ''));
                            $plate = trim((string)($vRow['plate_number'] ?? ''));
                            $lastVehicle = trim($brand . ' ' . $model . ($plate !== '' ? ' — ' . $plate : ''));
                        }
                    }
                }
                return ['full_name' => $fullName, 'last_vehicle' => $lastVehicle, 'source' => 'erp_customers'];
            }
        }
    }

    if (m360_profile_table_has_column($conn, 'erp_customer_phones', 'phone_number')) {
        $sql = 'SELECT TOP 1 c.full_name, v.brand, v.model, v.plate_number
                FROM dbo.erp_customer_phones p
                JOIN dbo.erp_customers c ON c.customer_id = p.customer_id
                LEFT JOIN dbo.erp_customer_vehicle_relations r ON r.customer_id = c.customer_id
                LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = r.vehicle_id
                WHERE p.phone_number = ? AND c.company_id = ?
                ORDER BY p.is_primary DESC, r.is_primary_owner DESC, r.relation_id DESC';
        $stmt = @odbc_prepare($conn, $sql);
        if ($stmt !== false && @odbc_execute($stmt, [$mobile, $companyId])) {
            $row = odbc_fetch_array($stmt);
            if ($row !== false) {
                $brand = trim((string)($row['brand'] ?? ''));
                $model = trim((string)($row['model'] ?? ''));
                $plate = trim((string)($row['plate_number'] ?? ''));
                $lastVehicle = trim($brand . ' ' . $model . ($plate !== '' ? ' — ' . $plate : ''));
                return [
                    'full_name' => trim((string)($row['full_name'] ?? '')),
                    'last_vehicle' => $lastVehicle,
                    'source' => 'erp_customer_phones',
                ];
            }
        }
    }

    if (m360_profile_table_has_column($conn, 'erp_customer_online_requests', 'mobile')) {
        $sql = 'SELECT TOP 1 customer_name, vehicle_plate
                FROM dbo.erp_customer_online_requests
                WHERE company_id = ? AND mobile = ?
                ORDER BY online_request_id DESC';
        $stmt = @odbc_prepare($conn, $sql);
        if ($stmt !== false && @odbc_execute($stmt, [$companyId, $mobile])) {
            $row = odbc_fetch_array($stmt);
            if ($row !== false) {
                return [
                    'full_name' => trim((string)($row['customer_name'] ?? '')),
                    'last_vehicle' => trim((string)($row['vehicle_plate'] ?? '')),
                    'source' => 'erp_customer_online_requests',
                ];
            }
        }
    }

    return null;
}

$tenant = mogh_tenant_resolve_from_request();
$conn = mogh_tenant_db_connect();
$profile = null;
try {
    $profile = m360_profile_lookup_customer($conn, $tenant['company_id'], $normalized);
} finally {
    @odbc_close($conn);
}

$customerExists = $profile !== null && ($profile['full_name'] !== '' || $profile['last_vehicle'] !== '');
$displayName = $customerExists ? m360_profile_mask_name($profile['full_name']) : '';

mogh_api_ok('وضعیت مشتری دریافت شد.', [
    'verified' => true,
    'customer_exists' => $customerExists,
    'profile_required' => !$customerExists,
    'customer' => [
        'full_name' => $displayName,
        'mobile' => m360_profile_mask_mobile($normalized),
        'last_vehicle' => $customerExists ? ($profile['last_vehicle'] !== '' ? $profile['last_vehicle'] : '—') : '',
        'masked' => true,
    ],
], 200);
