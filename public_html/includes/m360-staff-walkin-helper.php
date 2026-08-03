<?php
declare(strict_types=1);

/**
 * MOGHARE360 staff-assisted walk-in intake.
 * Uses the online request queue as canonical workflow input while preserving
 * customer OTP/signature gates for legal decisions.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-customer-online-submit-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-staff-home-helper.php';

const M360_WALKIN_IDEMPOTENCY_SESSION_KEY = 'm360_walkin_idempotency_keys';
const M360_WALKIN_FLASH_SESSION_KEY = 'm360_walkin_flash';
const M360_WALKIN_HISTORY_CREATED = 'STAFF_ASSISTED_WALKIN_CREATED';

/** Map walk-in request_type to intake service_route taxonomy key. */
function m360_walkin_map_service_route(string $requestType): string
{
    return match (strtolower(trim($requestType))) {
        'diagnostic_inspection' => 'diag',
        'buy_sell_inspection' => 'trade',
        'periodic_service' => 'periodic',
        'option_add' => 'options',
        'other' => 'other',
        default => '',
    };
}

/**
 * Initialize pending 6-slot reception photo metadata (no fake data_key).
 *
 * @return array{required_count:int,completed_count:int,is_complete:bool,slots:array<string,array<string,string>>}
 */
function m360_walkin_pending_photo_slots(): array
{
    $slots = [];
    foreach (m360_rw_intake_reception_photo_slots() as $key => $label) {
        $slots[$key] = [
            'label' => (string)$label,
            'status' => 'pending',
            'data_key' => '',
            'file' => '',
            'captured_at' => '',
            'captured_by' => '',
        ];
    }

    return [
        'required_count' => count($slots),
        'completed_count' => 0,
        'is_complete' => false,
        'slots' => $slots,
    ];
}

/**
 * Refresh nested reception_intake.vehicle after vehicle_id is resolved.
 *
 * @param array<string, mixed> $payload
 * @return array<string, mixed>
 */
function m360_walkin_hydrate_reception_intake_vehicle(array $payload, int $vehicleId): array
{
    if (!isset($payload['reception_intake']) || !is_array($payload['reception_intake'])) {
        $payload['reception_intake'] = [];
    }
    $vehicle = is_array($payload['reception_intake']['vehicle'] ?? null)
        ? $payload['reception_intake']['vehicle']
        : [];
    $plate = trim((string)($payload['plate_display'] ?? $payload['plate_number'] ?? $payload['vehicle_plate'] ?? $vehicle['plate'] ?? ''));
    $vehicle['vehicle_id'] = $vehicleId;
    $vehicle['selected_vehicle_id'] = (int)($payload['selected_vehicle_id'] ?? $vehicle['selected_vehicle_id'] ?? 0);
    if ($vehicle['selected_vehicle_id'] < 1 && ($vehicle['vehicle_mode'] ?? '') === 'existing') {
        $vehicle['selected_vehicle_id'] = $vehicleId;
    }
    $vehicle['vehicle_mode'] = (string)($payload['vehicle_mode'] ?? $vehicle['vehicle_mode'] ?? 'new');
    $vehicle['brand'] = trim((string)($payload['brand'] ?? $payload['vehicle_brand'] ?? $vehicle['brand'] ?? ''));
    $vehicle['model'] = trim((string)($payload['model'] ?? $payload['vehicle_model'] ?? $payload['vehicle_class'] ?? $vehicle['model'] ?? ''));
    $vehicle['vehicle_class'] = trim((string)($payload['vehicle_class'] ?? $vehicle['model'] ?? ''));
    $vehicle['plate'] = $plate;
    $vehicle['plate_number'] = $plate;
    $vehicle['plate_display'] = $plate;
    $vehicle['vin'] = trim((string)($payload['vin'] ?? $vehicle['vin'] ?? ''));
    $vehicle['chassis_number'] = trim((string)($payload['chassis_number'] ?? $vehicle['chassis_number'] ?? ''));
    $vehicle['vehicle_year_pair'] = trim((string)($payload['vehicle_year_pair'] ?? $vehicle['vehicle_year_pair'] ?? ''));
    $vehicle['production_year'] = trim((string)($payload['production_year'] ?? $vehicle['production_year'] ?? ''));
    $vehicle['visit_date'] = trim((string)($payload['visit_date'] ?? $vehicle['visit_date'] ?? ''));
    $vehicle['odometer_km'] = trim((string)($payload['odometer_km'] ?? $payload['mileage'] ?? $vehicle['odometer_km'] ?? ''));
    $vehicle['mileage'] = $vehicle['odometer_km'];
    $vehicle['fuel_level'] = trim((string)($payload['fuel_level'] ?? $vehicle['fuel_level'] ?? ''));
    $vehicle['color'] = trim((string)($payload['color'] ?? $vehicle['color'] ?? ''));
    $payload['reception_intake']['vehicle'] = $vehicle;
    if (!isset($payload['reception_intake']['photos']) || !is_array($payload['reception_intake']['photos'])) {
        $payload['reception_intake']['photos'] = m360_walkin_pending_photo_slots();
    }

    return $payload;
}

/** @return array{0:string,1:string} */
function m360_walkin_split_full_name(string $fullName): array
{
    $parts = preg_split('/\s+/u', trim($fullName), 2) ?: [];

    return [(string)($parts[0] ?? ''), (string)($parts[1] ?? '')];
}

function m360_walkin_digits_to_ascii(string $value): string
{
    return strtr($value, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}

function m360_walkin_normalize_mobile(string $mobile): string
{
    $mobile = m360_walkin_digits_to_ascii($mobile);
    $mobile = preg_replace('/[^\d+]/', '', trim($mobile)) ?? trim($mobile);
    if (str_starts_with($mobile, '+98')) {
        $mobile = '0' . substr($mobile, 3);
    } elseif (str_starts_with($mobile, '0098')) {
        $mobile = '0' . substr($mobile, 4);
    } elseif (str_starts_with($mobile, '98') && strlen($mobile) === 12) {
        $mobile = '0' . substr($mobile, 2);
    }

    return $mobile;
}

function m360_walkin_post_string(array $post, string $key, int $max = 2000): string
{
    $value = trim((string)($post[$key] ?? ''));
    if ($value === '') {
        return '';
    }
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }

    return substr($value, 0, $max);
}

function m360_walkin_compose_plate(array $post): string
{
    $display = m360_walkin_post_string($post, 'plate_display', 100);
    if ($display !== '') {
        return $display;
    }

    $left = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_left_2_digits', 2));
    $letter = m360_walkin_post_string($post, 'plate_letter', 4);
    $middle = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_middle_3_digits', 3));
    $region = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_region_2_digits', 2));
    if ($left === '') {
        $left = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_first_digit_1', 1))
            . m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_first_digit_2', 1));
    }
    if ($middle === '') {
        $middle = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_middle_digit_1', 1))
            . m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_middle_digit_2', 1))
            . m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_middle_digit_3', 1));
    }
    if ($region === '') {
        $region = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_region_digit_1', 1))
            . m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_region_digit_2', 1));
    }
    if ($left !== '' && $letter !== '' && $middle !== '' && $region !== '') {
        return $left . ' ' . $letter . ' ' . $middle . ' ایران ' . $region;
    }

    return '';
}

/** @return array{ok:bool,user_id:int,company_id:int,role_code:string,message:string,status:int} */
function m360_walkin_require_actor($conn): array
{
    erp_auth_context_start();
    $userId = (int)(erp_auth_context_session_user_id() ?? 0);
    if ($userId < 1) {
        return ['ok' => false, 'user_id' => 0, 'company_id' => 0, 'role_code' => '', 'message' => 'نیاز به ورود پرسنل است.', 'status' => 401];
    }

    $companyId = (int)($_SESSION['erp_company_id'] ?? 0);
    if ($companyId < 1) {
        $companyId = m360_reception_default_company_id($conn);
    }
    $roleCode = m360_staff_home_resolve_role_code($conn, $userId, $companyId);
    if (!in_array($roleCode, ['OWNER', 'SYSTEM_ADMIN', 'RECEPTION'], true)) {
        return ['ok' => false, 'user_id' => $userId, 'company_id' => $companyId, 'role_code' => $roleCode, 'message' => 'دسترسی ثبت پذیرش حضوری فقط برای پذیرش یا مدیر سیستم مجاز است.', 'status' => 403];
    }

    return ['ok' => true, 'user_id' => $userId, 'company_id' => $companyId, 'role_code' => $roleCode, 'message' => '', 'status' => 200];
}

function m360_walkin_generate_idempotency_key(): string
{
    erp_auth_context_start();
    $key = 'walkin-' . bin2hex(random_bytes(16));
    $_SESSION[M360_WALKIN_IDEMPOTENCY_SESSION_KEY][$key] = [
        'created_at' => time(),
        'online_request_id' => 0,
    ];

    return $key;
}

function m360_walkin_idempotency_key_from_session(): string
{
    erp_auth_context_start();
    $keys = $_SESSION[M360_WALKIN_IDEMPOTENCY_SESSION_KEY] ?? [];
    if (is_array($keys)) {
        foreach ($keys as $key => $meta) {
            if (is_string($key) && $key !== '' && is_array($meta) && (int)($meta['online_request_id'] ?? 0) < 1) {
                return $key;
            }
        }
    }

    return m360_walkin_generate_idempotency_key();
}

function m360_walkin_remember_idempotency(string $key, int $requestId): void
{
    if ($key === '' || $requestId < 1) {
        return;
    }
    erp_auth_context_start();
    $_SESSION[M360_WALKIN_IDEMPOTENCY_SESSION_KEY][$key] = [
        'created_at' => time(),
        'online_request_id' => $requestId,
    ];
}

function m360_walkin_find_existing_by_idempotency($conn, string $key): int
{
    if (!is_resource($conn) || $key === '') {
        return 0;
    }
    $needle = '%"staff_idempotency_key":"' . str_replace(['%', '_', '['], ['[%]', '[_]', '[[]'], $key) . '"%';
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 online_request_id
         FROM dbo.' . m360_online_req_table() . '
         WHERE source_channel = ? AND request_payload_json LIKE ?
         ORDER BY online_request_id DESC',
        [M360_ONLINE_REQ_SOURCE_STAFF_WALKIN, $needle]
    );

    return (int)($rows[0]['online_request_id'] ?? 0);
}

/**
 * Load an ACTIVE customer-owned vehicle for walk-in hydration.
 *
 * @return array{ok:bool,message:string,vehicle:array<string,string>}
 */
function m360_walkin_load_owned_vehicle($conn, int $customerId, int $vehicleId): array
{
    if (!is_resource($conn) || $customerId < 1 || $vehicleId < 1) {
        return ['ok' => false, 'message' => 'خودرو انتخاب‌شده متعلق به این مشتری نیست.', 'vehicle' => []];
    }
    if (!customer_core_table_exists($conn, 'erp_vehicles') || !customer_core_table_exists($conn, 'erp_customer_vehicle_relations')) {
        return ['ok' => false, 'message' => 'خودرو انتخاب‌شده متعلق به این مشتری نیست.', 'vehicle' => []];
    }

    $columns = ['v.vehicle_id', 'v.brand', 'v.model', 'v.plate_number'];
    foreach (['vin', 'mileage', 'color', 'chassis_number', 'production_year', 'model_year'] as $column) {
        if (customer_core_column_exists($conn, 'erp_vehicles', $column)) {
            $columns[] = 'v.' . $column;
        }
    }

    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 ' . implode(', ', $columns) . '
         FROM dbo.erp_vehicles v
         INNER JOIN dbo.erp_customer_vehicle_relations r ON r.vehicle_id = v.vehicle_id
         WHERE v.vehicle_id = ?
           AND r.customer_id = ?
           AND r.lifecycle_state = N\'ACTIVE\'
         ORDER BY r.is_primary_owner DESC, r.relation_id DESC',
        [$vehicleId, $customerId]
    );
    $row = is_array($rows[0] ?? null) ? $rows[0] : null;
    if ($row === null) {
        return ['ok' => false, 'message' => 'خودرو انتخاب‌شده متعلق به این مشتری نیست.', 'vehicle' => []];
    }

    $normalized = [];
    foreach ($row as $key => $value) {
        $normalized[strtolower((string)$key)] = $value === null ? '' : trim((string)$value);
    }

    return ['ok' => true, 'message' => '', 'vehicle' => $normalized];
}

/**
 * Resolve customer_id for existing-vehicle ownership checks without writing.
 */
function m360_walkin_resolve_customer_id_for_vehicle($conn, array $actor, string $mobile, int $selectedExistingCustomerId): int
{
    if ($selectedExistingCustomerId > 0) {
        return $selectedExistingCustomerId;
    }
    if (!is_resource($conn) || $mobile === '' || !function_exists('m360_online_req_resolve_customer_id')) {
        return 0;
    }
    $resolved = m360_online_req_resolve_customer_id($conn, (int)($actor['company_id'] ?? 0), $mobile);

    return $resolved === null ? 0 : (int)$resolved;
}

/** @return array{ok:bool,message:string,fields:array<string,mixed>,payload:array<string,mixed>} */
function m360_walkin_validate_payload(array $post, array $actor, $conn = null): array
{
    $mobile = m360_walkin_normalize_mobile(m360_walkin_post_string($post, 'mobile', 60));
    $customerFlow = m360_walkin_post_string($post, 'customer_flow', 40);
    $selectedExistingCustomerId = (int)m360_walkin_post_string($post, 'selected_existing_customer_id', 20);
    $selectedVehicleId = (int)m360_walkin_post_string($post, 'selected_vehicle_id', 20);
    $vehicleMode = m360_walkin_post_string($post, 'vehicle_mode', 40);
    $isExistingVehicle = $selectedVehicleId > 0 && ($vehicleMode === '' || $vehicleMode === 'existing');
    $firstName = m360_walkin_post_string($post, 'first_name', 80);
    $lastName = m360_walkin_post_string($post, 'last_name', 120);
    $fullName = m360_walkin_post_string($post, 'full_name', 200);
    if ($fullName === '') {
        $fullName = m360_pr02b_compose_full_name($firstName, $lastName);
    }
    if (($firstName === '' || $lastName === '') && $fullName !== '') {
        [$splitFirst, $splitLast] = m360_walkin_split_full_name($fullName);
        if ($firstName === '') {
            $firstName = $splitFirst;
        }
        if ($lastName === '') {
            $lastName = $splitLast;
        }
    }

    $plate = m360_walkin_compose_plate($post);
    $vehicleBrand = m360_walkin_post_string($post, 'vehicle_brand', 200);
    $vehicleClass = m360_walkin_post_string($post, 'vehicle_class', 200);
    $vehicleYearPair = m360_walkin_post_string($post, 'vehicle_year_pair', 40);
    $vin = m360_walkin_post_string($post, 'vin', 80);
    $chassisNumber = m360_walkin_post_string($post, 'chassis_number', 160);
    $color = m360_walkin_post_string($post, 'color', 160);
    $odometerKm = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'odometer_km', 20));
    $fuelLevel = m360_walkin_post_string($post, 'fuel_level', 60);
    $productionYear = m360_walkin_digits_to_ascii(
        m360_walkin_post_string($post, 'production_year', 10) !== ''
            ? m360_walkin_post_string($post, 'production_year', 10)
            : $vehicleYearPair
    );
    $requestType = m360_walkin_post_string($post, 'request_type', 160);
    $description = m360_walkin_post_string($post, 'request_description', 1500);
    $serviceRoute = m360_walkin_map_service_route($requestType);
    $servicePathClear = in_array((string)($post['service_path_clear'] ?? ''), ['0', '1'], true)
        ? (string)$post['service_path_clear']
        : '';
    // Staff walk-in request_type selection is authoritative seed; non-diag paths default clear.
    if ($servicePathClear === '' && $serviceRoute !== '' && $serviceRoute !== 'diag') {
        $servicePathClear = '1';
    }
    $diagnosticSubcategories = [];
    $rawDiagnosticSubs = $post['diagnostic_subcategories'] ?? [];
    if (is_array($rawDiagnosticSubs)) {
        $allowedDiagnosticSubs = array_keys(m360_rw_service_classification_taxonomy()['diag']['subs'] ?? []);
        foreach ($rawDiagnosticSubs as $diagSub) {
            $diagSub = trim((string)$diagSub);
            if ($diagSub !== '' && in_array($diagSub, $allowedDiagnosticSubs, true)) {
                $diagnosticSubcategories[] = $diagSub;
            }
        }
    }
    $visitDate = m360_walkin_post_string($post, 'visit_date', 40);
    if ($visitDate === '') {
        $visitDate = date('Y-m-d');
    }

    if (!preg_match('/^09\d{9}$/', $mobile)) {
        return ['ok' => false, 'message' => 'شماره موبایل معتبر ۱۱ رقمی وارد کنید.', 'fields' => [], 'payload' => []];
    }
    if ($fullName === '') {
        return ['ok' => false, 'message' => 'نام کامل مشتری الزامی است.', 'fields' => [], 'payload' => []];
    }
    if ($isExistingVehicle) {
        $customerIdForVehicle = m360_walkin_resolve_customer_id_for_vehicle(
            $conn,
            $actor,
            $mobile,
            $selectedExistingCustomerId
        );
        if ($customerIdForVehicle < 1) {
            return ['ok' => false, 'message' => 'خودرو انتخاب‌شده متعلق به این مشتری نیست.', 'fields' => [], 'payload' => []];
        }
        $owned = m360_walkin_load_owned_vehicle($conn, $customerIdForVehicle, $selectedVehicleId);
        if (!$owned['ok']) {
            return ['ok' => false, 'message' => $owned['message'], 'fields' => [], 'payload' => []];
        }
        $dbVehicle = $owned['vehicle'];
        $dbBrand = trim((string)($dbVehicle['brand'] ?? ''));
        if ($dbBrand === '') {
            return ['ok' => false, 'message' => 'اطلاعات خودروی قبلی ناقص است؛ برند خودرو را اصلاح کنید', 'fields' => [], 'payload' => []];
        }
        if ($vehicleBrand === '') {
            $vehicleBrand = $dbBrand;
        }
        if ($vehicleClass === '') {
            $vehicleClass = trim((string)($dbVehicle['model'] ?? ''));
        }
        if ($plate === '') {
            $plate = trim((string)($dbVehicle['plate_number'] ?? ''));
        }
        if ($vin === '') {
            $vin = trim((string)($dbVehicle['vin'] ?? ''));
        }
        if ($chassisNumber === '') {
            $chassisNumber = trim((string)($dbVehicle['chassis_number'] ?? ''));
        }
        if ($color === '') {
            $color = trim((string)($dbVehicle['color'] ?? ''));
        }
        $dbYear = trim((string)($dbVehicle['production_year'] ?? $dbVehicle['model_year'] ?? ''));
        if ($vehicleYearPair === '' && $dbYear !== '') {
            $vehicleYearPair = $dbYear;
        }
        if ($productionYear === '' && $dbYear !== '') {
            $productionYear = m360_walkin_digits_to_ascii($dbYear);
        }
        // Historical DB mileage is fallback only when current visit mileage was not entered.
        if ($odometerKm === '') {
            $odometerKm = m360_walkin_digits_to_ascii(trim((string)($dbVehicle['mileage'] ?? '')));
        }
        $vehicleMode = 'existing';
        if ($vehicleClass === '') {
            return ['ok' => false, 'message' => 'اطلاعات خودروی قبلی ناقص است؛ برند خودرو را اصلاح کنید', 'fields' => [], 'payload' => []];
        }
        if ($plate === '') {
            return ['ok' => false, 'message' => 'اطلاعات خودروی قبلی ناقص است؛ برند خودرو را اصلاح کنید', 'fields' => [], 'payload' => []];
        }
    } else {
        $selectedVehicleId = 0;
        $vehicleMode = 'new';
        if ($vehicleBrand === '') {
            return ['ok' => false, 'message' => 'برند خودرو الزامی است.', 'fields' => [], 'payload' => []];
        }
        if (function_exists('m360_pr02b_is_supported_vehicle_brand') && !m360_pr02b_is_supported_vehicle_brand($vehicleBrand)) {
            return ['ok' => false, 'message' => 'برند خودرو باید از فهرست تأییدشده انتخاب شود.', 'fields' => [], 'payload' => []];
        }
        if ($vehicleClass === '') {
            return ['ok' => false, 'message' => 'مدل خودرو الزامی است.', 'fields' => [], 'payload' => []];
        }
        if ($vehicleYearPair === '') {
            return ['ok' => false, 'message' => 'سال تولید خودرو الزامی است.', 'fields' => [], 'payload' => []];
        }
        if ($plate === '') {
            return ['ok' => false, 'message' => 'پلاک خودرو الزامی است.', 'fields' => [], 'payload' => []];
        }
    }
    if ($odometerKm === '') {
        return ['ok' => false, 'message' => 'کیلومتر فعلی خودرو الزامی است.', 'fields' => [], 'payload' => []];
    }
    if ($fuelLevel === '') {
        return ['ok' => false, 'message' => 'سطح بنزین الزامی است.', 'fields' => [], 'payload' => []];
    }
    if ($requestType === '') {
        return ['ok' => false, 'message' => 'نوع خدمت/درخواست الزامی است.', 'fields' => [], 'payload' => []];
    }
    if ($description === '') {
        return ['ok' => false, 'message' => 'شرح درخواست یا گفته مشتری الزامی است.', 'fields' => [], 'payload' => []];
    }
    // Entry-only walk-in: condition/photos/documents/agreements belong in erp-reception-intake-file.php.
    $conditionStructured = m360_rw_intake_validate_condition_structured_post($post);
    if (!$conditionStructured['ok']) {
        $conditionStructured = [
            'ok' => true,
            'error' => '',
            'trunk' => [],
            'zones' => [],
            'note' => '',
        ];
    }

    $nationalId = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'national_id', 40));
    $plateFirstDigit1 = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_first_digit_1', 1));
    $plateFirstDigit2 = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_first_digit_2', 1));
    $plateMiddleDigit1 = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_middle_digit_1', 1));
    $plateMiddleDigit2 = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_middle_digit_2', 1));
    $plateMiddleDigit3 = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_middle_digit_3', 1));
    $plateRegionDigit1 = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_region_digit_1', 1));
    $plateRegionDigit2 = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_region_digit_2', 1));
    $plateLeft = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_left_2_digits', 2));
    $plateMiddle = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_middle_3_digits', 3));
    $plateRegion = m360_walkin_digits_to_ascii(m360_walkin_post_string($post, 'plate_region_2_digits', 2));
    if ($plateLeft === '') {
        $plateLeft = $plateFirstDigit1 . $plateFirstDigit2;
    }
    if ($plateMiddle === '') {
        $plateMiddle = $plateMiddleDigit1 . $plateMiddleDigit2 . $plateMiddleDigit3;
    }
    if ($plateRegion === '') {
        $plateRegion = $plateRegionDigit1 . $plateRegionDigit2;
    }
    $idempotencyKey = m360_walkin_post_string($post, 'staff_idempotency_key', 80);
    $now = gmdate('Y-m-d H:i:s');
    $payload = [
        'source' => M360_ONLINE_REQ_SOURCE_STAFF_WALKIN,
        'source_channel' => M360_ONLINE_REQ_SOURCE_STAFF_WALKIN,
        'staff_assisted_walkin' => true,
        'staff_idempotency_key' => $idempotencyKey,
        'created_by_staff_user_id' => (string)$actor['user_id'],
        'created_by_staff_role_code' => (string)$actor['role_code'],
        'created_by_staff_at' => $now,
        'mobile_otp_policy' => 'NOT_REQUIRED_FOR_INITIAL_STAFF_ASSISTED_CREATE',
        'otp_verified' => 0,
        'customer_name' => $fullName,
        'customer_flow' => $customerFlow !== '' ? $customerFlow : 'new',
        'selected_existing_customer_id' => $selectedExistingCustomerId,
        'full_name' => $fullName,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'mobile' => $mobile,
        'normalized_mobile' => $mobile,
        'national_id' => $nationalId,
        'second_phone' => m360_walkin_normalize_mobile(m360_walkin_post_string($post, 'second_phone', 60)),
        'residence_address' => m360_walkin_post_string($post, 'residence_address', 300),
        'vehicle_delivery_address' => m360_walkin_post_string($post, 'vehicle_delivery_address', 300),
        'authorized_receiver_name' => m360_walkin_post_string($post, 'authorized_receiver_name', 120),
        'authorized_receiver_phone' => m360_walkin_normalize_mobile(m360_walkin_post_string($post, 'authorized_receiver_phone', 60)),
        'province' => m360_walkin_post_string($post, 'province', 100),
        'city' => m360_walkin_post_string($post, 'city', 100),
        'customer_notes' => m360_walkin_post_string($post, 'customer_notes', 1000),
        'selected_vehicle_id' => $selectedVehicleId,
        'vehicle_mode' => $vehicleMode,
        'vehicle_brand' => $vehicleBrand,
        'brand' => $vehicleBrand,
        'vehicle_class' => $vehicleClass,
        'vehicle_model' => $vehicleClass,
        'model' => $vehicleClass,
        'vehicle_year_pair' => $vehicleYearPair,
        'brand_other_explanation' => m360_walkin_post_string($post, 'brand_other_explanation', 500),
        'model_other_explanation' => m360_walkin_post_string($post, 'model_other_explanation', 500),
        'production_year' => $productionYear,
        'plate_left_2_digits' => $plateLeft,
        'plate_letter' => m360_walkin_post_string($post, 'plate_letter', 4),
        'plate_middle_3_digits' => $plateMiddle,
        'plate_region_2_digits' => $plateRegion,
        'plate_first_digit_1' => $plateFirstDigit1,
        'plate_first_digit_2' => $plateFirstDigit2,
        'plate_middle_digit_1' => $plateMiddleDigit1,
        'plate_middle_digit_2' => $plateMiddleDigit2,
        'plate_middle_digit_3' => $plateMiddleDigit3,
        'plate_region_digit_1' => $plateRegionDigit1,
        'plate_region_digit_2' => $plateRegionDigit2,
        'plate_display' => $plate,
        'plate_number' => $plate,
        'vehicle_plate' => $plate,
        'plate_parts' => [
            'left_2' => $plateLeft,
            'letter' => m360_walkin_post_string($post, 'plate_letter', 4),
            'middle_3' => $plateMiddle,
            'region_2' => $plateRegion,
            'first_digit_1' => $plateFirstDigit1,
            'first_digit_2' => $plateFirstDigit2,
            'middle_digit_1' => $plateMiddleDigit1,
            'middle_digit_2' => $plateMiddleDigit2,
            'middle_digit_3' => $plateMiddleDigit3,
            'region_digit_1' => $plateRegionDigit1,
            'region_digit_2' => $plateRegionDigit2,
        ],
        'vin' => $vin,
        'chassis_number' => $chassisNumber,
        'odometer_km' => $odometerKm,
        'mileage' => $odometerKm,
        'color' => $color,
        'fuel_level' => $fuelLevel,
        'request_type' => $requestType,
        'request_description' => $description,
        'service_description' => $description,
        'service_note' => $description,
        'fault_path' => m360_walkin_post_string($post, 'fault_path', 500),
        'diagnostic_options' => m360_walkin_post_string($post, 'diagnostic_options', 500),
        'service_route' => $serviceRoute,
        'service_path_clear' => $servicePathClear,
        'diagnostic_subcategories' => $diagnosticSubcategories,
        'visit_date' => $visitDate,
        'trunk_belongings_note' => m360_rw_intake_trunk_summary_fa($conditionStructured['trunk']),
        'damage_zones_note' => m360_rw_intake_damage_summary_fa($conditionStructured['zones']),
        'vehicle_condition_note' => m360_walkin_post_string($post, 'vehicle_condition_note', 1000),
        'cost_agreement' => m360_walkin_post_string($post, 'cost_agreement', 500),
        'cost_agreement_note' => m360_walkin_post_string($post, 'cost_agreement_note', 500),
        'reception_intake' => [
            'source_channel' => M360_ONLINE_REQ_SOURCE_STAFF_WALKIN,
            'vehicle' => [
                'vehicle_id' => 0,
                'selected_vehicle_id' => $selectedVehicleId,
                'vehicle_mode' => $vehicleMode,
                'plate' => $plate,
                'plate_number' => $plate,
                'plate_display' => $plate,
                'brand' => $vehicleBrand,
                'model' => $vehicleClass,
                'vehicle_class' => $vehicleClass,
                'brand_other_explanation' => m360_walkin_post_string($post, 'brand_other_explanation', 500),
                'model_other_explanation' => m360_walkin_post_string($post, 'model_other_explanation', 500),
                'vin' => $vin,
                'chassis_number' => $chassisNumber,
                'vehicle_year_pair' => $vehicleYearPair,
                'production_year' => $productionYear,
                'visit_date' => $visitDate,
                'odometer_km' => $odometerKm,
                'mileage' => $odometerKm,
                'color' => $color,
                'fuel_level' => $fuelLevel,
            ],
            'condition' => [
                'trunk_belongings' => $conditionStructured['trunk'],
                'damage_zones' => $conditionStructured['zones'],
                'damage_general_note' => $conditionStructured['note'],
                'trunk_belongings_note' => m360_rw_intake_trunk_summary_fa($conditionStructured['trunk']),
                'damage_zones_note' => m360_rw_intake_damage_summary_fa($conditionStructured['zones']),
                'vehicle_condition_note' => m360_walkin_post_string($post, 'vehicle_condition_note', 1000),
                'initial_vehicle_condition' => m360_walkin_post_string($post, 'vehicle_condition_note', 1000),
                'photos_complete' => false,
            ],
            'service_classification' => [
                'request_type' => $requestType,
                'description' => $description,
                'customer_complaint' => $description,
                'fault_path' => m360_walkin_post_string($post, 'fault_path', 500),
                'diagnostic_options' => m360_walkin_post_string($post, 'diagnostic_options', 500),
                'route' => $serviceRoute,
                'main' => $serviceRoute,
                'service_path_clear' => $servicePathClear,
                'diagnostic_subcategories' => $diagnosticSubcategories,
            ],
            'photos' => m360_walkin_pending_photo_slots(),
            'documents' => [
                'photo_gate_status' => 'PENDING_RECEPTION_CAPTURE',
                'contract_status' => 'NOT_PREPARED',
                'cost_agreement' => m360_walkin_post_string($post, 'cost_agreement', 500),
                'cost_agreement_note' => m360_walkin_post_string($post, 'cost_agreement_note', 500),
                'diagnostic_status' => m360_walkin_post_string($post, 'diagnostic_options', 500) !== '' ? 'SEEDED' : '',
            ],
            'reception_notes' => m360_walkin_post_string($post, 'reception_notes', 1500),
        ],
        'contract_ack_placeholder' => 'STAFF_ASSISTED_CONTRACT_REVIEW_REQUIRED',
        'contract_ack_status' => 'pending_customer_contract_review',
    ];

    $fields = [
        'customer_name' => $fullName,
        'mobile' => $mobile,
        'vehicle_plate' => $plate,
        'service_note' => $description,
        'request_type' => $requestType,
        'source_channel' => M360_ONLINE_REQ_SOURCE_STAFF_WALKIN,
        'visit_date' => $visitDate,
        'otp_verified' => 0,
    ];

    return ['ok' => true, 'message' => '', 'fields' => $fields, 'payload' => $payload];
}

function m360_walkin_create_intake_row($conn, array $payload, int $customerId, int $userId): int
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, 'erp_customer_intakes')) {
        return 0;
    }
    $duplicate = customer_core_duplicate_check_intake(
        $conn,
        (string)($payload['mobile'] ?? ''),
        (string)($payload['national_id'] ?? ''),
        (string)($payload['vehicle_plate'] ?? '')
    );
    $createdBy = 'staff_user_id:' . $userId;
    $statement = customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_customer_intakes
            (customer_id, full_name, mobile, national_code, license_plate, intake_channel, intake_type, source_description, notes, duplicate_status, duplicate_reason, status, created_by)
         OUTPUT INSERTED.intake_id
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $customerId > 0 ? $customerId : null,
            (string)($payload['full_name'] ?? ''),
            (string)($payload['mobile'] ?? ''),
            (string)($payload['national_id'] ?? '') !== '' ? (string)$payload['national_id'] : null,
            (string)($payload['vehicle_plate'] ?? '') !== '' ? (string)$payload['vehicle_plate'] : null,
            M360_ONLINE_REQ_SOURCE_STAFF_WALKIN,
            'CUSTOMER',
            'Staff-assisted walk-in intake',
            (string)($payload['reception_intake']['reception_notes'] ?? ''),
            (string)($duplicate['status'] ?? 'NEW'),
            (string)($duplicate['reason'] ?? ''),
            'OPEN',
            $createdBy,
        ]
    );
    if ($statement === false || @odbc_fetch_row($statement) !== true) {
        return 0;
    }

    $intakeId = @odbc_result($statement, 1);

    return $intakeId === false || $intakeId === null ? 0 : (int)$intakeId;
}

function m360_walkin_update_vehicle_metadata($conn, int $vehicleId, array $payload, int $userId): void
{
    if (!is_resource($conn) || $vehicleId < 1) {
        return;
    }
    $sets = ['updated_at = SYSUTCDATETIME()', 'updated_by_user_id = ?'];
    $params = [$userId];
    $yearRaw = m360_walkin_digits_to_ascii((string)($payload['production_year'] ?? $payload['vehicle_year_pair'] ?? ''));
    if (preg_match('/(13|14|19|20)\d{2}/', $yearRaw, $m) === 1) {
        $year = (int)$m[0];
        if ($year >= 1300 && $year < 1700) {
            $year += 621;
        }
        if ($year >= 1980 && $year <= ((int)date('Y') + 1)) {
            $sets[] = 'production_year = COALESCE(production_year, ?)';
            $params[] = $year;
        }
    }
    foreach ([
        'color' => 'color',
        'chassis_number' => 'chassis_number',
        'vin' => 'vin',
    ] as $payloadKey => $column) {
        $value = trim((string)($payload[$payloadKey] ?? ''));
        if ($value !== '' && customer_core_column_exists($conn, 'erp_vehicles', $column)) {
            $sets[] = $column . ' = COALESCE(NULLIF(' . $column . ", N''), ?)";
            $params[] = $value;
        }
    }
    $params[] = $vehicleId;
    customer_core_execute($conn, 'UPDATE dbo.erp_vehicles SET ' . implode(', ', $sets) . ' WHERE vehicle_id = ?', $params);
}

function m360_walkin_update_request_payload($conn, int $requestId, array $payload): void
{
    if (!is_resource($conn) || $requestId < 1 || !m360_online_req_has_column($conn, 'request_payload_json')) {
        return;
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return;
    }
    customer_core_execute(
        $conn,
        'UPDATE dbo.' . m360_online_req_table() . ' SET request_payload_json = ?, updated_at = SYSUTCDATETIME() WHERE online_request_id = ?',
        [$json, $requestId]
    );
}

/** @return array{ok:bool,message:string,online_request_id:int,customer_id:int,vehicle_id:int,intake_id:int,contract_id:int,reused:bool} */
function m360_walkin_create($conn, array $post): array
{
    $actor = m360_walkin_require_actor($conn);
    if (!$actor['ok']) {
        return ['ok' => false, 'message' => $actor['message'], 'online_request_id' => 0, 'customer_id' => 0, 'vehicle_id' => 0, 'intake_id' => 0, 'contract_id' => 0, 'reused' => false];
    }

    $validated = m360_walkin_validate_payload($post, $actor, $conn);
    if (!$validated['ok']) {
        return ['ok' => false, 'message' => $validated['message'], 'online_request_id' => 0, 'customer_id' => 0, 'vehicle_id' => 0, 'intake_id' => 0, 'contract_id' => 0, 'reused' => false];
    }
    $payload = $validated['payload'];
    $idempotencyKey = (string)($payload['staff_idempotency_key'] ?? '');
    $existingRequestId = m360_walkin_find_existing_by_idempotency($conn, $idempotencyKey);
    if ($existingRequestId > 0) {
        m360_walkin_remember_idempotency($idempotencyKey, $existingRequestId);
        $row = m360_online_req_fetch_by_id($conn, $existingRequestId);
        $rowPayload = m360_online_req_parse_payload($row['request_payload_json'] ?? null);
        return [
            'ok' => true,
            'message' => 'این پذیرش حضوری قبلاً ثبت شده بود و همان پرونده باز شد.',
            'online_request_id' => $existingRequestId,
            'customer_id' => (int)($row['customer_id'] ?? 0),
            'vehicle_id' => (int)($row['vehicle_id'] ?? 0),
            'intake_id' => (int)($rowPayload['intake_id'] ?? 0),
            'contract_id' => 0,
            'reused' => true,
        ];
    }

    $profile = [
        'first_name' => (string)$payload['first_name'],
        'last_name' => (string)$payload['last_name'],
        'full_name' => (string)$payload['full_name'],
        'national_id' => (string)$payload['national_id'],
        'second_phone' => (string)$payload['second_phone'],
        'residence_address' => (string)$payload['residence_address'],
        'city' => (string)$payload['city'],
        'vehicle_delivery_address' => (string)$payload['vehicle_delivery_address'],
        'authorized_receiver_name' => (string)$payload['authorized_receiver_name'],
        'authorized_receiver_phone' => (string)$payload['authorized_receiver_phone'],
    ];
    $customerResult = m360_pr02b_upsert_customer($conn, (int)$actor['company_id'], (string)$payload['mobile'], $profile);
    if (!$customerResult['ok'] || (int)$customerResult['customer_id'] < 1) {
        return ['ok' => false, 'message' => (string)$customerResult['message'], 'online_request_id' => 0, 'customer_id' => 0, 'vehicle_id' => 0, 'intake_id' => 0, 'contract_id' => 0, 'reused' => false];
    }
    $customerId = (int)$customerResult['customer_id'];
    $payload['customer_id'] = $customerId;

    $vehicleResult = m360_pr02b_resolve_vehicle($conn, $customerId, $payload);
    if (!$vehicleResult['ok'] || (int)$vehicleResult['vehicle_id'] < 1) {
        return ['ok' => false, 'message' => (string)$vehicleResult['message'], 'online_request_id' => 0, 'customer_id' => $customerId, 'vehicle_id' => 0, 'intake_id' => 0, 'contract_id' => 0, 'reused' => false];
    }
    $vehicleId = (int)$vehicleResult['vehicle_id'];
    $payload['vehicle_id'] = $vehicleId;
    $payload = m360_walkin_hydrate_reception_intake_vehicle($payload, $vehicleId);
    m360_walkin_update_vehicle_metadata($conn, $vehicleId, $payload, (int)$actor['user_id']);
    $relation = m360_reception_ensure_relation($conn, $customerId, $vehicleId);
    if (!$relation['ok']) {
        return ['ok' => false, 'message' => (string)($relation['error'] ?? 'ثبت رابطه مشتری و خودرو ناموفق بود.'), 'online_request_id' => 0, 'customer_id' => $customerId, 'vehicle_id' => $vehicleId, 'intake_id' => 0, 'contract_id' => 0, 'reused' => false];
    }

    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($payloadJson === false) {
        $payloadJson = '{}';
    }
    $fields = $validated['fields'];
    $fields['request_payload_json'] = $payloadJson;
    $insert = m360_online_req_insert($conn, (int)$actor['company_id'], $fields);
    if (!$insert['ok'] || (int)$insert['online_request_id'] < 1) {
        return ['ok' => false, 'message' => 'ثبت پرونده پذیرش حضوری ناموفق بود.', 'online_request_id' => 0, 'customer_id' => $customerId, 'vehicle_id' => $vehicleId, 'intake_id' => 0, 'contract_id' => 0, 'reused' => false];
    }

    $requestId = (int)$insert['online_request_id'];
    m360_reception_bind_request_entities($conn, $requestId, $customerId, $vehicleId);
    $intakeId = m360_walkin_create_intake_row($conn, $payload, $customerId, (int)$actor['user_id']);
    if ($intakeId > 0) {
        $payload['intake_id'] = $intakeId;
        $payload['reception_intake']['intake_id'] = $intakeId;
    }
    // Always re-persist nested reception_intake (vehicle_id + pending photos) after bind.
    m360_walkin_update_request_payload($conn, $requestId, $payload);

    m360_online_req_write_history(
        $conn,
        $requestId,
        M360_WALKIN_HISTORY_CREATED,
        '',
        M360_ONLINE_REQ_STATUS_NEW,
        'Staff-assisted walk-in created by user #' . (int)$actor['user_id'] . '; mobile OTP intentionally not required at creation.',
        (int)$actor['user_id']
    );
    customer_core_insert_history(
        $conn,
        'erp_customer_online_requests',
        $requestId,
        M360_WALKIN_HISTORY_CREATED,
        'Staff-assisted walk-in request created',
        null,
        json_encode([
            'source_channel' => M360_ONLINE_REQ_SOURCE_STAFF_WALKIN,
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'intake_id' => $intakeId,
            'staff_user_id' => (int)$actor['user_id'],
        ], JSON_UNESCAPED_UNICODE)
    );
    m360_walkin_remember_idempotency($idempotencyKey, $requestId);

    return [
        'ok' => true,
        'message' => 'پذیرش حضوری با موفقیت ثبت شد.',
        'online_request_id' => $requestId,
        'customer_id' => $customerId,
        'vehicle_id' => $vehicleId,
        'intake_id' => $intakeId,
        'contract_id' => 0,
        'reused' => false,
    ];
}

function m360_walkin_set_flash(array $flash): void
{
    erp_auth_context_start();
    $_SESSION[M360_WALKIN_FLASH_SESSION_KEY] = $flash;
}

function m360_walkin_consume_flash(): array
{
    erp_auth_context_start();
    $flash = $_SESSION[M360_WALKIN_FLASH_SESSION_KEY] ?? [];
    unset($_SESSION[M360_WALKIN_FLASH_SESSION_KEY]);

    return is_array($flash) ? $flash : [];
}
