<?php
declare(strict_types=1);

/**
 * PR-02B — Shared customer online submit (profile + vehicle + online request).
 * Used by customer-request.php (direct) and api/customer/request.php (API).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-customer-v2-write-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-reception-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-calendar-1405-helper.php';

const M360_PR02B_PROFILE_EXT_MARKER = 'PR02B_EXT:';

/** @return list<string> */
function m360_pr02b_approved_vehicle_brands(): array
{
    return ['بنز', 'ب ام و', 'پورشه', 'ولوو', 'فولکس واگن', 'سایر'];
}

/** @return array<string, string> */
function m360_pr02b_schema_capability(): array
{
    return [
        'SCHEMA_SUPPORTS_CUSTOMER_PROFILE' => 'partial',
        'SCHEMA_SUPPORTS_MULTI_VEHICLE' => 'yes',
        'SCHEMA_SUPPORTS_ONLINE_REQUEST_CUSTOMER_ID' => 'yes',
        'SCHEMA_SUPPORTS_ONLINE_REQUEST_VEHICLE_ID' => 'yes',
        'SQL_PROPOSAL_NEEDED' => 'yes',
        'DB_SCHEMA_UNTOUCHED' => 'yes',
    ];
}

function m360_pr02b_compose_full_name(string $firstName, string $lastName): string
{
    return trim($firstName . ' ' . $lastName);
}

/** @param array<string, string> $ext */
function m360_pr02b_encode_profile_ext_notes(array $ext): string
{
    $filtered = array_filter($ext, static fn($v) => trim((string)$v) !== '');
    if ($filtered === []) {
        return '';
    }
    $json = json_encode($filtered, JSON_UNESCAPED_UNICODE);
    return $json !== false ? M360_PR02B_PROFILE_EXT_MARKER . $json : '';
}

/** @return array<string, string> */
function m360_pr02b_decode_profile_ext_notes(?string $notes): array
{
    if ($notes === null || !str_starts_with($notes, M360_PR02B_PROFILE_EXT_MARKER)) {
        return [];
    }
    $decoded = json_decode(substr($notes, strlen(M360_PR02B_PROFILE_EXT_MARKER)), true);
    return is_array($decoded) ? $decoded : [];
}

/** @return array{ok:bool,message:string} */
function m360_pr02b_assert_otp_verified(string $mobile, string $tokenBody = ''): array
{
    m360_otp_session_start();
    if (!m360_otp_is_verified($mobile)) {
        return ['ok' => false, 'message' => 'برای ادامه، ابتدا شماره موبایل را با کد پیامکی تأیید کنید.'];
    }
    $tokenSession = m360_otp_verified_token();
    if ($tokenBody !== '' && $tokenSession !== '' && !hash_equals($tokenSession, $tokenBody)) {
        return ['ok' => false, 'message' => 'نشست تأیید موبایل منقضی شده است. لطفاً دوباره کد پیامکی را وارد کنید.'];
    }

    return ['ok' => true, 'message' => ''];
}

/** @return array<string, mixed>|null */
function m360_pr02b_fetch_customer_row($conn, int $companyId, string $mobile): ?array
{
    $customerId = m360_online_req_resolve_customer_id($conn, $companyId, $mobile);
    if ($customerId === null || $customerId < 1) {
        return null;
    }
    $sql = 'SELECT TOP 1 customer_id, full_name, national_id, primary_mobile, secondary_mobile, address, city, notes
            FROM dbo.erp_customers WHERE customer_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$customerId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    $normalized = [];
    foreach ($row as $k => $v) {
        $normalized[strtolower((string)$k)] = $v === null ? '' : (string)$v;
    }
    $normalized['customer_id'] = (string)$customerId;

    return $normalized;
}

/**
 * @param array<string, string> $profile
 * @return array{ok:bool,customer_id:int,created:bool,message:string}
 */
function m360_pr02b_upsert_customer($conn, int $companyId, string $mobile, array $profile): array
{
    $existing = m360_pr02b_fetch_customer_row($conn, $companyId, $mobile);
    $fullName = m360_pr02b_compose_full_name(
        trim((string)($profile['first_name'] ?? '')),
        trim((string)($profile['last_name'] ?? ''))
    );
    if ($fullName === '' && $existing !== null) {
        $fullName = trim((string)($profile['full_name'] ?? $existing['full_name'] ?? ''));
    }
    if ($fullName === '') {
        $fullName = trim((string)($profile['full_name'] ?? ''));
    }
    if ($fullName === '') {
        return ['ok' => false, 'customer_id' => 0, 'created' => false, 'message' => 'نام و نام خانوادگی الزامی است.'];
    }

    $nationalId = trim((string)($profile['national_id'] ?? ''));
    $secondPhone = trim((string)($profile['second_phone'] ?? ''));
    $address = trim((string)($profile['residence_address'] ?? $profile['address'] ?? ''));
    $city = trim((string)($profile['city'] ?? ''));
    $extNotes = m360_pr02b_encode_profile_ext_notes([
        'vehicle_delivery_address' => trim((string)($profile['vehicle_delivery_address'] ?? '')),
        'authorized_receiver_name' => trim((string)($profile['authorized_receiver_name'] ?? '')),
        'authorized_receiver_phone' => trim((string)($profile['authorized_receiver_phone'] ?? '')),
    ]);

    if ($existing !== null) {
        $customerId = (int)$existing['customer_id'];
        $sets = ['full_name = ?', 'updated_at = SYSUTCDATETIME()'];
        $params = [$fullName];
        if ($nationalId !== '') {
            $sets[] = 'national_id = ?';
            $params[] = $nationalId;
        }
        if ($secondPhone !== '') {
            $sets[] = 'secondary_mobile = ?';
            $params[] = $secondPhone;
        }
        if ($address !== '') {
            $sets[] = 'address = ?';
            $params[] = $address;
        }
        if ($city !== '') {
            $sets[] = 'city = ?';
            $params[] = $city;
        }
        if ($extNotes !== '') {
            $sets[] = 'notes = ?';
            $params[] = $extNotes;
        }
        $params[] = $customerId;
        $sql = 'UPDATE dbo.erp_customers SET ' . implode(', ', $sets) . ' WHERE customer_id = ?';
        $stmt = @odbc_prepare($conn, $sql);
        if ($stmt === false || !@odbc_execute($stmt, $params)) {
            return ['ok' => false, 'customer_id' => 0, 'created' => false, 'message' => 'به‌روزرسانی پروفایل مشتری ناموفق بود.'];
        }

        return ['ok' => true, 'customer_id' => $customerId, 'created' => false, 'message' => ''];
    }

    $write = moghare360_customer_v2_write([
        'customer_name' => $fullName,
        'mobile' => $mobile,
        'national_id' => $nationalId,
        'customer_channel' => 'PUBLIC_ONLINE',
        'customer_class' => 'ONLINE',
        'notes' => $extNotes,
    ]);
    if (!$write['ok'] || (int)($write['customer_id'] ?? 0) < 1) {
        return ['ok' => false, 'customer_id' => 0, 'created' => true, 'message' => (string)($write['error'] ?? 'ایجاد مشتری ناموفق بود.')];
    }
    $customerId = (int)$write['customer_id'];
    if ($secondPhone !== '' && customer_core_column_exists($conn, 'erp_customers', 'secondary_mobile')) {
        $u = @odbc_prepare($conn, 'UPDATE dbo.erp_customers SET secondary_mobile = ?, address = ?, city = ? WHERE customer_id = ?');
        if ($u !== false) {
            @odbc_execute($u, [$secondPhone, $address, $city, $customerId]);
        }
    }

    return ['ok' => true, 'customer_id' => $customerId, 'created' => true, 'message' => ''];
}

/** @return bool */
function m360_pr02b_is_supported_vehicle_brand(string $brand): bool
{
    $brand = trim($brand);
    if ($brand === '') {
        return false;
    }
    $blocked = ['Toyota', 'تویوتا', 'Lexus', 'لکسوس', 'Camry', 'کمری', 'Hyundai', 'هیوندای', 'Kia', 'کیا'];
    if (in_array($brand, $blocked, true)) {
        return false;
    }

    return in_array($brand, m360_pr02b_approved_vehicle_brands(), true);
}

/** @return list<array{vehicle_id:int,label:string,plate:string,brand:string,model:string,supported:bool}> */
function m360_pr02b_list_customer_vehicles($conn, int $customerId, bool $supportedOnly = false): array
{
    if (!is_resource($conn) || $customerId < 1) {
        return [];
    }
    if (!customer_core_table_exists($conn, 'erp_customer_vehicle_relations')) {
        return [];
    }
    $sql = 'SELECT v.vehicle_id, v.brand, v.model, v.plate_number
            FROM dbo.erp_customer_vehicle_relations r
            INNER JOIN dbo.erp_vehicles v ON v.vehicle_id = r.vehicle_id
            WHERE r.customer_id = ? AND r.lifecycle_state = N\'ACTIVE\'
            ORDER BY r.is_primary_owner DESC, r.relation_id DESC';
    $rows = customer_core_fetch_rows($conn, $sql, [$customerId]);
    $out = [];
    foreach ($rows as $row) {
        $vid = (int)($row['vehicle_id'] ?? 0);
        if ($vid < 1) {
            continue;
        }
        $brand = trim((string)($row['brand'] ?? ''));
        $model = trim((string)($row['model'] ?? ''));
        $plate = trim((string)($row['plate_number'] ?? ''));
        $out[] = [
            'vehicle_id' => $vid,
            'brand' => $brand,
            'model' => $model,
            'plate' => $plate,
            'label' => trim($brand . ' ' . $model . ($plate !== '' ? ' — ' . $plate : '')),
            'supported' => m360_pr02b_is_supported_vehicle_brand($brand),
        ];
    }

    if ($supportedOnly) {
        return array_values(array_filter($out, static fn(array $v): bool => !empty($v['supported'])));
    }

    return $out;
}

/**
 * @param array<string, string> $vehicleData
 * @return array{ok:bool,vehicle_id:int,created:bool,message:string}
 */
function m360_pr02b_resolve_vehicle($conn, int $customerId, array $vehicleData): array
{
    $selectedId = (int)($vehicleData['selected_vehicle_id'] ?? 0);
    if ($selectedId > 0) {
        $vehicles = m360_pr02b_list_customer_vehicles($conn, $customerId);
        foreach ($vehicles as $v) {
            if ((int)$v['vehicle_id'] === $selectedId) {
                return ['ok' => true, 'vehicle_id' => $selectedId, 'created' => false, 'message' => ''];
            }
        }

        return ['ok' => false, 'vehicle_id' => 0, 'created' => false, 'message' => 'خودرو انتخاب‌شده متعلق به این مشتری نیست.'];
    }

    $brand = trim((string)($vehicleData['vehicle_brand'] ?? ''));
    $model = trim((string)($vehicleData['vehicle_class'] ?? $vehicleData['model'] ?? ''));
    if ($brand === '' || !m360_pr02b_is_supported_vehicle_brand($brand)) {
        return ['ok' => false, 'vehicle_id' => 0, 'created' => false, 'message' => 'برند خودرو باید از فهرست تأییدشده انتخاب شود.'];
    }
    if ($model === '') {
        return ['ok' => false, 'vehicle_id' => 0, 'created' => false, 'message' => 'مدل خودرو الزامی است.'];
    }

    $plate = trim((string)($vehicleData['plate_display'] ?? $vehicleData['vehicle_plate'] ?? ''));
    if ($plate === '') {
        return ['ok' => false, 'vehicle_id' => 0, 'created' => false, 'message' => 'پلاک خودرو الزامی است.'];
    }
    $normalizedPlate = m360_online_req_normalize_plate($plate);
    $existingVid = m360_online_req_resolve_vehicle_id($conn, $customerId, $normalizedPlate);
    if ($existingVid !== null && $existingVid > 0) {
        m360_reception_ensure_relation($conn, $customerId, $existingVid);

        return ['ok' => true, 'vehicle_id' => $existingVid, 'created' => false, 'message' => ''];
    }

    erp_auth_context_start();
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
    $vehicleCode = 'P1V-' . date('Ymd-His') . '-' . random_int(1000, 9999);
    $insertOk = customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_vehicles (vehicle_code, plate_number, brand, model, vin, mileage, lifecycle_state, created_by_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $vehicleCode,
            $normalizedPlate,
            $brand,
            $model,
            trim((string)($vehicleData['vin'] ?? '')) ?: null,
            trim((string)($vehicleData['odometer_km'] ?? '')) ?: null,
            'ACTIVE',
            $userId,
        ]
    );
    if ($insertOk === false) {
        return ['ok' => false, 'vehicle_id' => 0, 'created' => true, 'message' => 'ایجاد خودرو ناموفق بود.'];
    }
    $newVehicleId = (int)(customer_core_scope_identity($conn) ?? 0);
    if ($newVehicleId < 1) {
        $newVehicleId = (int)(customer_core_scalar(
            $conn,
            'SELECT vehicle_id FROM dbo.erp_vehicles WHERE vehicle_code = ?',
            [$vehicleCode]
        ) ?? 0);
    }
    if ($newVehicleId < 1) {
        return ['ok' => false, 'vehicle_id' => 0, 'created' => true, 'message' => 'شناسه خودرو دریافت نشد.'];
    }
    m360_reception_ensure_relation($conn, $customerId, $newVehicleId);

    return ['ok' => true, 'vehicle_id' => $newVehicleId, 'created' => true, 'message' => ''];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok:bool,message:string,online_request_id:int,customer_id:int,vehicle_id:int}
 */
function m360_customer_online_submit($conn, int $companyId, array $body): array
{
    $mobile = trim((string)($body['mobile'] ?? $body['phone'] ?? ''));
    $otpCheck = m360_pr02b_assert_otp_verified($mobile, trim((string)($body['otp_verified_token'] ?? '')));
    if (!$otpCheck['ok']) {
        return ['ok' => false, 'message' => $otpCheck['message'], 'online_request_id' => 0, 'customer_id' => 0, 'vehicle_id' => 0];
    }

    $customerFlow = trim((string)($body['customer_flow'] ?? 'new'));
    $isReturning = $customerFlow === 'returning';

    $profile = [
        'first_name' => trim((string)($body['first_name'] ?? '')),
        'last_name' => trim((string)($body['last_name'] ?? '')),
        'full_name' => trim((string)($body['full_name'] ?? $body['customer_name'] ?? '')),
        'national_id' => trim((string)($body['national_id'] ?? '')),
        'second_phone' => trim((string)($body['second_phone'] ?? '')),
        'residence_address' => trim((string)($body['residence_address'] ?? $body['address'] ?? '')),
        'city' => trim((string)($body['city'] ?? '')),
        'vehicle_delivery_address' => trim((string)($body['vehicle_delivery_address'] ?? '')),
        'authorized_receiver_name' => trim((string)($body['authorized_receiver_name'] ?? '')),
        'authorized_receiver_phone' => trim((string)($body['authorized_receiver_phone'] ?? '')),
    ];
    if ($isReturning && $profile['first_name'] === '' && $profile['last_name'] === '' && $profile['full_name'] !== '') {
        $parts = preg_split('/\s+/u', $profile['full_name'], 2) ?: [];
        $profile['first_name'] = (string)($parts[0] ?? '');
        $profile['last_name'] = (string)($parts[1] ?? '');
    }
    if ($isReturning && ($profile['first_name'] === '' || $profile['last_name'] === '')) {
        $existingRow = m360_pr02b_fetch_customer_row($conn, $companyId, $mobile);
        if ($existingRow !== null) {
            $parts = preg_split('/\s+/u', trim((string)($existingRow['full_name'] ?? '')), 2) ?: [];
            if ($profile['first_name'] === '') {
                $profile['first_name'] = (string)($parts[0] ?? '');
            }
            if ($profile['last_name'] === '') {
                $profile['last_name'] = (string)($parts[1] ?? '');
            }
        }
    }
    if (!$isReturning && ($profile['first_name'] === '' || $profile['last_name'] === '')) {
        return ['ok' => false, 'message' => 'نام و نام خانوادگی الزامی است.', 'online_request_id' => 0, 'customer_id' => 0, 'vehicle_id' => 0];
    }

    $custResult = m360_pr02b_upsert_customer($conn, $companyId, $mobile, $profile);
    if (!$custResult['ok']) {
        return ['ok' => false, 'message' => $custResult['message'], 'online_request_id' => 0, 'customer_id' => 0, 'vehicle_id' => 0];
    }
    $customerId = (int)$custResult['customer_id'];

    $vehicleResult = m360_pr02b_resolve_vehicle($conn, $customerId, $body);
    if (!$vehicleResult['ok']) {
        return ['ok' => false, 'message' => $vehicleResult['message'], 'online_request_id' => 0, 'customer_id' => $customerId, 'vehicle_id' => 0];
    }
    $vehicleId = (int)$vehicleResult['vehicle_id'];

    $visitDate = trim((string)($body['visit_date'] ?? ''));
    $visitCheck = m360_rw_calendar_validate_visit_date($visitDate);
    if (!$visitCheck['ok']) {
        return ['ok' => false, 'message' => $visitCheck['error'], 'online_request_id' => 0, 'customer_id' => $customerId, 'vehicle_id' => $vehicleId];
    }

    $fullName = m360_pr02b_compose_full_name($profile['first_name'], $profile['last_name']);
    if ($fullName === '') {
        $fullName = $profile['full_name'];
    }
    $plate = trim((string)($body['plate_display'] ?? $body['vehicle_plate'] ?? ''));
    $vehicleSnap = null;
    if ($vehicleId > 0) {
        $vehicleSnap = customer_core_fetch_rows(
            $conn,
            'SELECT TOP 1 vehicle_id, plate_number, brand, model, vin, mileage, model_year FROM dbo.erp_vehicles WHERE vehicle_id = ?',
            [$vehicleId]
        );
        $vehicleSnap = is_array($vehicleSnap[0] ?? null) ? $vehicleSnap[0] : null;
    }
    if ($plate === '' && is_array($vehicleSnap)) {
        $plate = trim((string)($vehicleSnap['plate_number'] ?? ''));
    }
    $description = trim((string)($body['request_description'] ?? $body['service_note'] ?? ''));

    $payloadData = array_merge($body, [
        'customer_name' => $fullName,
        'full_name' => $fullName,
        'mobile' => $mobile,
        'vehicle_plate' => $plate,
        'service_note' => $description,
        'customer_id' => $customerId,
        'vehicle_id' => $vehicleId,
        'otp_verified' => 1,
        'profile_required' => false,
        'contract_ack_placeholder' => 'PR02B_PENDING_PR02C',
        'contract_ack_status' => trim((string)($body['contract_ack_status'] ?? 'pending_pr02c')),
    ]);

    // Operational snapshot for future requests: fill empty fields from linked canonical vehicle only.
    if (is_array($vehicleSnap)) {
        $snapMap = [
            'plate' => trim((string)($vehicleSnap['plate_number'] ?? '')),
            'plate_display' => trim((string)($vehicleSnap['plate_number'] ?? '')),
            'vehicle_plate' => trim((string)($vehicleSnap['plate_number'] ?? '')),
            'vin' => trim((string)($vehicleSnap['vin'] ?? '')),
            'brand' => trim((string)($vehicleSnap['brand'] ?? '')),
            'vehicle_brand' => trim((string)($vehicleSnap['brand'] ?? '')),
            'model' => trim((string)($vehicleSnap['model'] ?? '')),
            'vehicle_model' => trim((string)($vehicleSnap['model'] ?? '')),
            'production_year' => trim((string)($vehicleSnap['model_year'] ?? '')),
            'mileage' => trim((string)($vehicleSnap['mileage'] ?? '')),
            'odometer_km' => trim((string)($vehicleSnap['mileage'] ?? '')),
        ];
        foreach ($snapMap as $key => $snapValue) {
            if ($snapValue === '') {
                continue;
            }
            if (trim((string)($payloadData[$key] ?? '')) === '') {
                $payloadData[$key] = $snapValue;
            }
        }
        if (trim((string)($payloadData['color'] ?? '')) === '' && trim((string)($body['color'] ?? '')) !== '') {
            $payloadData['color'] = trim((string)$body['color']);
        }
        if (trim((string)($payloadData['fuel_type'] ?? '')) === '' && trim((string)($body['fuel_type'] ?? '')) !== '') {
            $payloadData['fuel_type'] = trim((string)$body['fuel_type']);
        }
        if (trim((string)($payloadData['transmission_type'] ?? '')) === '' && trim((string)($body['transmission_type'] ?? '')) !== '') {
            $payloadData['transmission_type'] = trim((string)$body['transmission_type']);
        }
    }

    $vehicleClass = trim((string)($body['vehicle_class'] ?? ''));
    if ($vehicleClass !== '') {
        $payloadData['vehicle_class'] = $vehicleClass;
        if (trim((string)($payloadData['model'] ?? '')) === '') {
            $payloadData['model'] = $vehicleClass;
        }
        if (trim((string)($payloadData['vehicle_model'] ?? '')) === '') {
            $payloadData['vehicle_model'] = $vehicleClass;
        }
    }
    $vinValue = trim((string)($body['vin'] ?? $payloadData['vin'] ?? ''));
    if ($vinValue !== '') {
        $payloadData['vin'] = $vinValue;
    }
    unset($payloadData['otp_verified_token']);
    $payloadJson = json_encode($payloadData, JSON_UNESCAPED_UNICODE);
    if ($payloadJson === false) {
        $payloadJson = '{}';
    }

    $insert = m360_online_req_insert($conn, $companyId, [
        'customer_name' => $fullName,
        'mobile' => $mobile,
        'vehicle_plate' => $plate,
        'service_note' => $description,
        'request_type' => trim((string)($body['request_type'] ?? '')),
        'source_channel' => trim((string)($body['source_channel'] ?? M360_ONLINE_REQ_SOURCE_PUBLIC)),
        'request_payload_json' => $payloadJson,
        'visit_date' => $visitDate,
    ]);

    if (!$insert['ok'] || (int)($insert['online_request_id'] ?? 0) < 1) {
        $errorCode = trim((string)($insert['error_code'] ?? 'online_request_insert_failed'));
        $detail = trim((string)($insert['insert_detail'] ?? ''));
        $message = 'ثبت درخواست آنلاین ناموفق بود.';
        if ($errorCode === 'online_request_identity_missing') {
            $message = 'درخواست ثبت شد اما شماره پیگیری دریافت نشد. لطفاً با پذیرش تماس بگیرید.';
        } elseif ($detail === 'odbc_execute_failed') {
            $message = 'ذخیره درخواست آنلاین در پایگاه داده ناموفق بود. لطفاً دوباره تلاش کنید یا با پذیرش تماس بگیرید.';
        }

        return [
            'ok' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'step' => 'm360_section_request',
            'online_request_id' => 0,
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
        ];
    }

    $requestId = (int)$insert['online_request_id'];
    m360_reception_bind_request_entities($conn, $requestId, $customerId, $vehicleId);

    return [
        'ok' => true,
        'message' => 'درخواست شما با موفقیت ثبت شد.',
        'error_code' => '',
        'step' => 'm360_customer_success_panel',
        'online_request_id' => $requestId,
        'customer_id' => $customerId,
        'vehicle_id' => $vehicleId,
    ];
}

/**
 * @return array{step:string,is_otp_error:bool}
 */
function m360_pr02b_submit_error_meta(string $message, bool $otpVerified, string $errorCode = ''): array
{
    if ($errorCode === 'online_request_insert_failed' || $errorCode === 'online_request_identity_missing') {
        return ['step' => 'm360_section_request', 'is_otp_error' => false];
    }
    if (!$otpVerified || str_contains($message, 'کد پیامکی') || str_contains($message, 'نشست تأیید')) {
        return ['step' => 'm360_step_otp', 'is_otp_error' => true];
    }
    if (str_contains($message, 'نام')) {
        return ['step' => 'm360_section_profile', 'is_otp_error' => false];
    }
    if (str_contains($message, 'خودرو') || str_contains($message, 'پلاک') || str_contains($message, 'برند') || str_contains($message, 'مدل')) {
        return ['step' => 'm360_section_vehicle', 'is_otp_error' => false];
    }
    if (str_contains($message, 'مراجعه') || str_contains($message, 'تاریخ')) {
        return ['step' => 'm360_section_request', 'is_otp_error' => false];
    }
    if (str_contains($message, 'شرح') || str_contains($message, 'نوع درخواست')) {
        return ['step' => 'm360_section_request', 'is_otp_error' => false];
    }

    return ['step' => 'm360_section_request', 'is_otp_error' => false];
}

/**
 * @param array<string, string> $post
 * @return array{ok:bool,message:string,error_code:string,step:string,online_request_id:int,customer_id:int,vehicle_id:int}
 */
function m360_customer_online_submit_from_post(array $post): array
{
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-reception-helper.php';

    $conn = customer_core_db();
    if (!is_resource($conn)) {
        return [
            'ok' => false,
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
            'error_code' => 'db_connection_failed',
            'step' => 'm360_section_request',
            'online_request_id' => 0,
            'customer_id' => 0,
            'vehicle_id' => 0,
        ];
    }

    if (!isset($post['source_channel']) || trim((string)$post['source_channel']) === '' || trim((string)$post['source_channel']) === 'PUBLIC_WEB') {
        $post['source_channel'] = M360_ONLINE_REQ_SOURCE_PUBLIC;
    }

    try {
        $companyId = m360_reception_default_company_id($conn);
        return m360_customer_online_submit($conn, $companyId, $post);
    } finally {
        @odbc_close($conn);
    }
}
