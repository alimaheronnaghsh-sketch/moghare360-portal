<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-api-bootstrap.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-customer-online-submit-helper.php';

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

/** @return array<string, string> */
function m360_profile_split_name(string $fullName): array
{
    $fullName = trim($fullName);
    if ($fullName === '') {
        return ['first_name' => '', 'last_name' => ''];
    }
    $parts = preg_split('/\s+/u', $fullName, 2) ?: [];
    return [
        'first_name' => (string)($parts[0] ?? ''),
        'last_name' => (string)($parts[1] ?? ''),
    ];
}

$tenant = mogh_tenant_resolve_from_request();
$conn = mogh_tenant_db_connect();
$customerRow = null;
$vehicles = [];
$vehiclesOutOfScope = [];
$customerId = null;
try {
    $customerId = m360_online_req_resolve_customer_id($conn, $tenant['company_id'], $normalized);
    if ($customerId !== null && $customerId > 0) {
        $customerRow = m360_pr02b_fetch_customer_row($conn, $tenant['company_id'], $normalized);
        $allVehicles = m360_pr02b_list_customer_vehicles($conn, $customerId);
        $vehicles = array_values(array_filter($allVehicles, static fn(array $v): bool => !empty($v['supported'])));
        $vehiclesOutOfScope = array_values(array_filter($allVehicles, static fn(array $v): bool => empty($v['supported'])));
    }
} finally {
    @odbc_close($conn);
}

$customerExists = $customerRow !== null;
$ext = $customerRow !== null ? m360_pr02b_decode_profile_ext_notes($customerRow['notes'] ?? '') : [];
$nameParts = $customerRow !== null
    ? m360_profile_split_name((string)($customerRow['full_name'] ?? ''))
    : ['first_name' => '', 'last_name' => ''];

$profile = [
    'first_name' => $nameParts['first_name'],
    'last_name' => $nameParts['last_name'],
    'full_name' => $customerRow !== null ? trim((string)($customerRow['full_name'] ?? '')) : '',
    'national_id' => $customerRow !== null ? trim((string)($customerRow['national_id'] ?? '')) : '',
    'primary_mobile' => $normalized,
    'second_phone' => $customerRow !== null ? trim((string)($customerRow['secondary_mobile'] ?? '')) : '',
    'residence_address' => $customerRow !== null ? trim((string)($customerRow['address'] ?? '')) : '',
    'city' => $customerRow !== null ? trim((string)($customerRow['city'] ?? '')) : '',
    'vehicle_delivery_address' => trim((string)($ext['vehicle_delivery_address'] ?? '')),
    'authorized_receiver_name' => trim((string)($ext['authorized_receiver_name'] ?? '')),
    'authorized_receiver_phone' => trim((string)($ext['authorized_receiver_phone'] ?? '')),
];

$lastVehicle = '';
if ($vehicles !== []) {
    $lastVehicle = (string)$vehicles[0]['label'];
}

mogh_api_ok('وضعیت مشتری دریافت شد.', [
    'verified' => true,
    'customer_exists' => $customerExists,
    'profile_required' => !$customerExists,
    'customer_id' => $customerId,
    'profile' => $profile,
    'vehicles' => $vehicles,
    'vehicles_out_of_scope' => $vehiclesOutOfScope ?? [],
    'customer' => [
        'full_name' => $profile['full_name'] !== '' ? $profile['full_name'] : 'مشتری گرامی',
        'mobile' => $normalized,
        'last_vehicle' => $lastVehicle,
        'masked' => false,
    ],
], 200);
