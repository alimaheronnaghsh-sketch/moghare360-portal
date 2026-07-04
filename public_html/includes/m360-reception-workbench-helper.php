<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.9-C-2B — Reception workbench + intake completion shell helper.
 * Read-only aggregation; no schema changes; reuses P1/P2/P1.5 foundations.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-reception-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';

function m360_rw_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_rw_table_exists($conn, string $table): bool
{
    if (!is_resource($conn)) {
        return false;
    }
    $sql = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = ?";
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$table])) {
        return false;
    }
    $row = odbc_fetch_array($stmt);

    return $row !== false && (int)($row['c'] ?? 0) > 0;
}

/** @return array<string, string> */
function m360_rw_payload_label_map(): array
{
    return [
        'customer_name' => 'نام مشتری',
        'mobile' => 'موبایل',
        'vehicle_plate' => 'پلاک',
        'service_note' => 'شرح درخواست',
        'national_id' => 'کد ملی',
        'province' => 'استان',
        'city' => 'شهر',
        'brand' => 'برند',
        'vehicle_brand' => 'برند خودرو',
        'model' => 'مدل',
        'vehicle_model' => 'مدل خودرو',
        'vehicle_class' => 'کلاس خودرو',
        'vehicle_year_pair' => 'سال خودرو',
        'vin' => 'VIN / شماره شاسی',
        'chassis' => 'شماره شاسی',
        'odometer_km' => 'کیلومتر',
        'mileage' => 'کیلومتر',
        'fuel_level' => 'سطح سوخت',
        'belongings' => 'لوازم داخل خودرو',
        'visible_damage' => 'آسیب ظاهری',
        'complaint' => 'شرح شکایت مشتری',
        'defect_category' => 'دسته‌بندی عیب',
        'defect_code' => 'کد عیب',
        'photos' => 'عکس‌ها',
        'diagnostic' => 'دیاگ اولیه',
        'diag' => 'دیاگ',
        'cost_agreement' => 'توافق هزینه',
        'contract' => 'قرارداد',
        'terms' => 'شرایط / توافق',
        'customer_confirmation' => 'تأیید مشتری',
        'reception_final_confirmation' => 'تأیید نهایی پذیرشگر',
        'notes' => 'یادداشت',
        'request_type' => 'نوع درخواست',
        'visit_date' => 'تاریخ مراجعه',
        'otp_verified' => 'تأیید OTP',
        'address' => 'آدرس',
        'plate_display' => 'نمایش پلاک',
        'customer_flow' => 'جریان مشتری',
        'source' => 'منبع',
    ];
}

/**
 * @return array{valid:bool,items:array<string,mixed>,raw_warning:string}
 */
function m360_rw_decode_payload(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return ['valid' => true, 'items' => [], 'raw_warning' => ''];
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [
            'valid' => false,
            'items' => [],
            'raw_warning' => 'ساختار JSON فرم آنلاین نامعتبر است. اطلاعات پایه از ستون‌های درخواست نمایش داده می‌شود.',
        ];
    }

    return ['valid' => true, 'items' => $decoded, 'raw_warning' => ''];
}

/** @return list<array{key:string,label_fa:string,value:string,is_nested:bool}> */
function m360_rw_payload_display_rows(array $payload): array
{
    $labels = m360_rw_payload_label_map();
    $knownKeys = array_keys($labels);
    $rows = [];
    $used = [];

    foreach ($knownKeys as $key) {
        if (!array_key_exists($key, $payload)) {
            continue;
        }
        $val = m360_rw_format_payload_value($payload[$key]);
        if ($val === '') {
            continue;
        }
        $rows[] = ['key' => $key, 'label_fa' => $labels[$key], 'value' => $val, 'is_nested' => false];
        $used[$key] = true;
    }

    foreach ($payload as $key => $value) {
        if (isset($used[$key]) || $key === 'plate_parts' || $key === 'otp_verified') {
            continue;
        }
        $val = m360_rw_format_payload_value($value);
        if ($val === '') {
            continue;
        }
        $label = $labels[$key] ?? (string)$key;
        $rows[] = [
            'key' => (string)$key,
            'label_fa' => $label,
            'value' => $val,
            'is_nested' => is_array($value),
        ];
    }

    if (isset($payload['plate_parts']) && is_array($payload['plate_parts'])) {
        $parts = [];
        foreach ($payload['plate_parts'] as $pk => $pv) {
            if ((string)$pv !== '') {
                $parts[] = (string)$pk . ': ' . (string)$pv;
            }
        }
        if ($parts !== []) {
            $rows[] = ['key' => 'plate_parts', 'label_fa' => 'اجزای پلاک', 'value' => implode(' | ', $parts), 'is_nested' => true];
        }
    }

    return $rows;
}

function m360_rw_format_payload_value(mixed $value): string
{
    if ($value === null) {
        return '';
    }
    if (is_bool($value)) {
        return $value ? 'بله' : 'خیر';
    }
    if (is_array($value)) {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
        return $encoded !== false ? $encoded : '';
    }

    return trim((string)$value);
}

function m360_rw_pick(array $sources, string ...$keys): string
{
    foreach ($sources as $src) {
        foreach ($keys as $key) {
            if (isset($src[$key]) && trim((string)$src[$key]) !== '') {
                return trim((string)$src[$key]);
            }
        }
    }

    return '';
}

/**
 * @param array<string, array<string, mixed>|null> $sourceMap
 * @return array{value:string,source_key:string}
 */
function m360_rw_pick_meta(array $sourceMap, array $keys): array
{
    foreach ($sourceMap as $sourceKey => $src) {
        if (!is_array($src)) {
            continue;
        }
        foreach ($keys as $key) {
            if (array_key_exists($key, $src) && trim((string)$src[$key]) !== '') {
                return ['value' => trim((string)$src[$key]), 'source_key' => (string)$sourceKey];
            }
        }
    }

    return ['value' => '', 'source_key' => ''];
}

function m360_rw_source_label_fa(string $sourceKey): string
{
    return match ($sourceKey) {
        'online_request' => 'از درخواست آنلاین خوانده شد',
        'payload' => 'در Payload موجود است',
        'erp_vehicle' => 'از پرونده خودرو ERP خوانده شد',
        'erp_customer' => 'از پرونده مشتری ERP خوانده شد',
        'erp_jobcard' => 'از کارت کار خوانده شد',
        'erp_intake' => 'از intake ERP خوانده شد',
        default => '',
    };
}

/**
 * @return array{value:string,source_key:string,source_label:string,missing_label:string,present:bool,partial:bool,detail:string}
 */
function m360_rw_field_result(string $value, string $sourceKey, string $missingLabel, bool $partial = false, string $detail = ''): array
{
    $present = $value !== '';

    return [
        'value' => $value,
        'source_key' => $sourceKey,
        'source_label' => $present ? m360_rw_source_label_fa($sourceKey) : '',
        'missing_label' => $present ? '' : $missingLabel,
        'present' => $present,
        'partial' => $partial && $present,
        'detail' => $detail,
    ];
}

function m360_rw_recover_plate_from_parts(array $payload): string
{
    if (!isset($payload['plate_parts']) || !is_array($payload['plate_parts'])) {
        return '';
    }
    $pp = $payload['plate_parts'];
    $left = trim((string)($pp['left_2'] ?? ''));
    $letter = trim((string)($pp['letter'] ?? ''));
    $mid = trim((string)($pp['middle_3'] ?? ''));
    $region = trim((string)($pp['region_2'] ?? ''));
    if ($left !== '' && $letter !== '' && $mid !== '' && $region !== '') {
        return $left . $letter . $mid . '-' . $region;
    }

    return '';
}

/**
 * @param array<string, mixed> $request
 * @param array<string, mixed> $payload
 * @return array<string, array<string, mixed>|null>
 */
function m360_rw_intake_source_map(array $request, array $payload, ?array $customer, ?array $vehicle, ?array $intake, ?array $jobcard): array
{
    return [
        'online_request' => $request,
        'payload' => $payload,
        'erp_customer' => $customer,
        'erp_vehicle' => $vehicle,
        'erp_intake' => $intake,
        'erp_jobcard' => $jobcard,
    ];
}

/**
 * @return array<string, array{value:string,source_key:string,source_label:string,missing_label:string,present:bool,partial:bool,detail:string}>
 */
function m360_rw_recover_intake_fields(
    array $request,
    array $payload,
    ?array $customer,
    ?array $vehicle,
    ?array $intake,
    ?array $jobcard,
    array $vehiclePhotos,
    array $jobcardMedia,
    array $contracts
): array {
    $map = m360_rw_intake_source_map($request, $payload, $customer, $vehicle, $intake, $jobcard);

    $plateMeta = m360_rw_pick_meta($map, ['vehicle_plate', 'plate', 'plate_number', 'license_plate', 'plate_display', 'car_plate']);
    if ($plateMeta['value'] === '') {
        $fromParts = m360_rw_recover_plate_from_parts($payload);
        if ($fromParts !== '') {
            $plateMeta = ['value' => $fromParts, 'source_key' => 'payload'];
        }
    }
    if ($plateMeta['value'] === '' && $intake !== null) {
        $plateMeta = m360_rw_pick_meta(['erp_intake' => $intake], ['license_plate']);
    }

    $vinMeta = m360_rw_pick_meta($map, ['vin', 'chassis', 'chassis_no', 'chassis_number', 'vehicle_vin']);
    $brandMeta = m360_rw_pick_meta($map, ['brand', 'vehicle_brand']);
    $modelMeta = m360_rw_pick_meta($map, ['model', 'vehicle_model']);
    $mileageMeta = m360_rw_pick_meta($map, ['odometer_km', 'mileage', 'odometer', 'kilometer', 'km', 'intake_mileage']);
    $fuelMeta = m360_rw_pick_meta($map, ['fuel_level', 'intake_fuel_level', 'fuel']);
    $belongingsMeta = m360_rw_pick_meta($map, ['belongings', 'vehicle_items', 'internal_items']);
    $damageMeta = m360_rw_pick_meta($map, ['visible_damage', 'body_damage', 'damage_notes']);
    $costMeta = m360_rw_pick_meta($map, ['cost_agreement', 'agreed_cost', 'terms']);
    $diagMeta = m360_rw_pick_meta($map, ['diagnostic', 'diag', 'diagnostic_status', 'diag_status']);
    $customerNameMeta = m360_rw_pick_meta($map, ['customer_name', 'full_name']);
    $mobileMeta = m360_rw_pick_meta($map, ['mobile', 'phone']);

    $vehicleId = (int)($request['vehicle_id'] ?? 0);
    $hasErpVehicle = $vehicleId > 0 && $vehicle !== null;
    $hasPayloadVehicle = $plateMeta['value'] !== '' || $vinMeta['value'] !== '' || $brandMeta['value'] !== '' || $modelMeta['value'] !== '';
    $vehiclePartial = !$hasErpVehicle && $hasPayloadVehicle;
    $vehicleDetail = $vehiclePartial
        ? 'اطلاعات خودرو از فرم/Payload موجود است؛ اتصال ERP خودرو در C-2C تکمیل می‌شود.'
        : '';
    $vehicleOk = $hasErpVehicle || ($plateMeta['value'] !== '' && ($brandMeta['value'] !== '' || $modelMeta['value'] !== '' || $vinMeta['value'] !== ''));

    $otpOk = m360_online_req_payload_otp_verified($request);
    $photoCount = count($vehiclePhotos) + count($jobcardMedia);
    $hasPhotoPayload = m360_rw_pick_meta($map, ['photos'])['value'] !== '';
    $hasPhoto = $photoCount > 0 || $hasPhotoPayload;

    $contractStatus = '';
    if ($contracts !== []) {
        $contractStatus = (string)($contracts[0]['contract_status'] ?? '');
    } elseif ($jobcard !== null) {
        $contractStatus = (string)($jobcard['contract_status'] ?? '');
    }

    $brandModelOk = ($brandMeta['value'] !== '' && $modelMeta['value'] !== '')
        || ($hasErpVehicle && ($brandMeta['value'] !== '' || $modelMeta['value'] !== ''));

    return [
        'customer_name' => m360_rw_field_result($customerNameMeta['value'], $customerNameMeta['source_key'], 'نام مشتری ثبت نشده است'),
        'mobile' => m360_rw_field_result($mobileMeta['value'], $mobileMeta['source_key'], 'موبایل ثبت نشده است'),
        'otp' => [
            'value' => $otpOk ? 'تأیید شده' : 'تأیید نشده',
            'source_key' => $otpOk ? 'payload' : '',
            'source_label' => $otpOk ? 'بر اساس داده واقعی OTP' : '',
            'missing_label' => $otpOk ? '' : 'نیازمند تأیید مشتری / OTP — این وضعیت Gate را عبور نمی‌دهد',
            'present' => $otpOk,
            'partial' => false,
            'detail' => '',
            'verified' => $otpOk,
        ],
        'plate' => m360_rw_field_result($plateMeta['value'], $plateMeta['source_key'], 'پلاک ثبت نشده است'),
        'vin' => m360_rw_field_result($vinMeta['value'], $vinMeta['source_key'], 'VIN/شاسی ثبت نشده است'),
        'brand' => m360_rw_field_result($brandMeta['value'], $brandMeta['source_key'], 'برند ثبت نشده است'),
        'model' => m360_rw_field_result($modelMeta['value'], $modelMeta['source_key'], 'مدل ثبت نشده است'),
        'brand_model' => m360_rw_field_result(
            $brandModelOk ? trim($brandMeta['value'] . ' / ' . $modelMeta['value'], ' /') : '',
            $brandMeta['source_key'] !== '' ? $brandMeta['source_key'] : $modelMeta['source_key'],
            'برند/مدل ثبت نشده است',
            !$brandModelOk && ($brandMeta['value'] !== '' || $modelMeta['value'] !== ''),
            (!$brandModelOk && ($brandMeta['value'] !== '' xor $modelMeta['value'] !== '')) ? 'برند یا مدل به‌صورت جزئی موجود است' : ''
        ),
        'mileage' => m360_rw_field_result($mileageMeta['value'], $mileageMeta['source_key'], 'کیلومتر ورود ثبت نشده است'),
        'fuel' => m360_rw_field_result($fuelMeta['value'], $fuelMeta['source_key'], 'سطح سوخت ثبت نشده است'),
        'belongings' => m360_rw_field_result($belongingsMeta['value'], $belongingsMeta['source_key'], 'لوازم داخل خودرو هنوز در پرونده ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود'),
        'damage' => m360_rw_field_result($damageMeta['value'], $damageMeta['source_key'], 'آسیب ظاهری هنوز در پرونده ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود'),
        'vehicle' => m360_rw_field_result(
            $vehicleOk ? ($hasErpVehicle ? 'ERP #' . (string)$vehicleId : 'Payload') : '',
            $hasErpVehicle ? 'erp_vehicle' : ($hasPayloadVehicle ? 'payload' : ''),
            'خودرو شناسایی نشده است',
            $vehiclePartial,
            $vehicleDetail
        ),
        'photo' => m360_rw_field_result(
            $hasPhoto ? (string)$photoCount : '',
            $hasPhoto ? ($photoCount > 0 ? 'erp_vehicle' : 'payload') : '',
            'عکس پذیرش ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود'
        ),
        'diag' => m360_rw_field_result(
            $diagMeta['value'],
            $diagMeta['source_key'],
            'دیاگ اولیه یا وضعیت عیب‌یابی ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود'
        ),
        'contract' => m360_rw_field_result(
            $contractStatus,
            $contractStatus !== '' ? 'erp_vehicle' : '',
            'قرارداد پذیرش ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود'
        ),
        'cost' => m360_rw_field_result($costMeta['value'], $costMeta['source_key'], 'توافق هزینه ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود'),
    ];
}

function m360_rw_gate_missing_message(array $check): string
{
    $id = (string)($check['id'] ?? '');
    if (!empty($check['missing_label'])) {
        return (string)$check['missing_label'];
    }

    return match ($id) {
        'otp' => 'نیازمند تأیید مشتری / OTP',
        'vehicle' => 'خودرو شناسایی نشده است',
        'plate' => 'پلاک ثبت نشده است',
        'vin' => 'VIN/شاسی ثبت نشده است',
        'brand_model' => 'برند/مدل ثبت نشده است',
        'mileage' => 'کیلومتر ورود ثبت نشده است',
        'fuel' => 'سطح سوخت ثبت نشده است',
        'belongings' => 'لوازم داخل خودرو هنوز در پرونده ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود',
        'damage' => 'آسیب ظاهری هنوز در پرونده ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود',
        'service_classification' => 'دسته‌بندی خدمات توسط پذیرشگر ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود',
        'fault_path' => 'مسیر عیب/خدمت هنوز مشخص نیست — پرونده در پذیرش موقت باقی می‌ماند.',
        'photo' => 'عکس پذیرش ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود',
        'diag' => 'دیاگ اولیه یا وضعیت عیب‌یابی ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود',
        'contract' => 'قرارداد پذیرش ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود',
        'cost' => 'توافق هزینه ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود',
        'final_confirm' => 'تأیید نهایی پذیرشگر ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود',
        default => (string)($check['label'] ?? 'ثبت نشده است'),
    };
}

const M360_RW_SERVICE_CLASS_WRITE_PLACEHOLDER = 'ثبت دسته‌بندی خدمات در فاز تکمیل عملیات پذیرش فعال می‌شود.';
const M360_RW_SERVICE_CLASS_BUSINESS_PURPOSE_FA = 'این بخش توسط پذیرشگر تکمیل می‌شود و برای گزارش‌گیری مدیریتی، تحلیل درآمد خدمات، تحلیل نوع مراجعات و برنامه‌ریزی عملیاتی استفاده می‌شود.';
const M360_RW_CUSTOMER_REQUEST_TYPE_NOTE_FA = 'نوع درخواست مشتری جایگزین دسته‌بندی خدمات پذیرشگر نیست.';
const M360_RW_WALKIN_PLACEHOLDER_FA = 'فرم عملیاتی پذیرش حضوری در فاز تکمیل عملیات پذیرش فعال می‌شود.';
const M360_RW_MOCK_UX_LABEL_FA = 'راهنمای نمایشی / غیرعملیاتی';

/** @return array<string, mixed> */
function m360_rw_build_empty_gate(): array
{
    return m360_rw_build_gate([], [], null, null, null, [], [], [], null);
}

/** @return array<string, array{label:string,subs:array<string,string>}> */
function m360_rw_service_classification_taxonomy(): array
{
    return [
        'diag' => [
            'label' => 'کارشناسی و عیب‌یابی',
            'subs' => [
                'engine_transmission' => 'موتور و گیربکس',
                'electrical_battery' => 'برق و باتری',
                'underbody_suspension' => 'زیروبند و تعلیق',
                'interior_trim' => 'مبلمان داخلی',
                'body_services' => 'خدمات بدنه',
                'options' => 'آپشن',
            ],
        ],
        'periodic' => ['label' => 'سرویس‌های دوره‌ای', 'subs' => []],
        'trade' => ['label' => 'کارشناسی خرید و فروش', 'subs' => []],
    ];
}

/**
 * Reception-only service classification (not customer-facing).
 *
 * @return array{
 *   registered:bool,
 *   fault_path_clear:bool,
 *   primary:string,
 *   diag_sub:list<string>,
 *   periodic:string,
 *   trade:string,
 *   customer_request_type:string,
 *   customer_mismatch:bool,
 *   taxonomy:array<string,array{label:string,subs:array<string,string>}>,
 *   write_placeholder:string,
 *   selected_labels:list<string>
 * }
 */
function m360_rw_parse_service_classification(array $payload, array $request): array
{
    $taxonomy = m360_rw_service_classification_taxonomy();
    $primary = m360_rw_pick([$payload], 'reception_service_primary', 'service_classification_primary', 'service_category_primary');
    $diagRaw = $payload['reception_service_diag_sub'] ?? $payload['service_classification_diag_sub'] ?? null;
    $diagSubs = [];
    if (is_array($diagRaw)) {
        foreach ($diagRaw as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $diagSubs[] = $item;
            }
        }
    } elseif (is_string($diagRaw) && trim($diagRaw) !== '') {
        foreach (explode(',', $diagRaw) as $part) {
            $part = trim($part);
            if ($part !== '') {
                $diagSubs[] = $part;
            }
        }
    }
    $periodic = m360_rw_pick([$payload], 'reception_service_periodic', 'service_classification_periodic');
    $trade = m360_rw_pick([$payload], 'reception_service_trade', 'service_classification_trade');
    $registered = $primary !== '' || $diagSubs !== [] || $periodic !== '' || $trade !== '';
    $faultPathClear = $registered
        || m360_rw_pick([$payload], 'fault_service_path_clear', 'service_fault_path_clear') === '1';
    $customerRequestType = m360_rw_pick([$request, $payload], 'request_type');

    $selectedLabels = [];
    if ($primary !== '' && isset($taxonomy[$primary])) {
        $selectedLabels[] = $taxonomy[$primary]['label'];
    }
    foreach ($diagSubs as $code) {
        $label = $taxonomy['diag']['subs'][$code] ?? $code;
        $selectedLabels[] = $label;
    }
    if ($periodic !== '') {
        $selectedLabels[] = $taxonomy['periodic']['label'];
    }
    if ($trade !== '') {
        $selectedLabels[] = $taxonomy['trade']['label'];
    }

    return [
        'registered' => $registered,
        'fault_path_clear' => $faultPathClear,
        'primary' => $primary,
        'diag_sub' => $diagSubs,
        'periodic' => $periodic,
        'trade' => $trade,
        'customer_request_type' => $customerRequestType,
        'customer_mismatch' => $customerRequestType !== '' && !$registered,
        'taxonomy' => $taxonomy,
        'write_placeholder' => M360_RW_SERVICE_CLASS_WRITE_PLACEHOLDER,
        'selected_labels' => $selectedLabels,
    ];
}

/** @return ?array<string, string> */
function m360_rw_fetch_customer_row($conn, int $customerId): ?array
{
    if (!is_resource($conn) || $customerId < 1 || !m360_rw_table_exists($conn, 'erp_customers')) {
        return null;
    }
    $sql = 'SELECT TOP 1 customer_id, full_name, mobile, national_code, customer_code
            FROM dbo.erp_customers WHERE customer_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$customerId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    $out = [];
    foreach ($row as $k => $v) {
        $out[strtolower((string)$k)] = $v === null ? '' : (string)$v;
    }

    return $out;
}

/** @return ?array<string, string> */
function m360_rw_fetch_vehicle_row($conn, int $vehicleId): ?array
{
    if (!is_resource($conn) || $vehicleId < 1 || !m360_rw_table_exists($conn, 'erp_vehicles')) {
        return null;
    }
    $sql = 'SELECT TOP 1 vehicle_id, brand, model, plate_number, vin, vehicle_code, model_year
            FROM dbo.erp_vehicles WHERE vehicle_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$vehicleId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    $out = [];
    foreach ($row as $k => $v) {
        $out[strtolower((string)$k)] = $v === null ? '' : (string)$v;
    }

    return $out;
}

/** @return ?array<string, string> */
function m360_rw_fetch_intake_row($conn, string $mobile, int $customerId): ?array
{
    if (!is_resource($conn) || !m360_rw_table_exists($conn, 'erp_customer_intakes')) {
        return null;
    }
    if ($mobile === '') {
        return null;
    }
    $sql = 'SELECT TOP 1 intake_id, full_name, mobile, national_code, license_plate, created_at
            FROM dbo.erp_customer_intakes WHERE mobile = ? ORDER BY intake_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$mobile])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    $out = [];
    foreach ($row as $k => $v) {
        $out[strtolower((string)$k)] = $v === null ? '' : (string)$v;
    }

    return $out;
}

/** @return list<array<string, string>> */
function m360_rw_fetch_contracts_for_request($conn, int $onlineRequestId, int $jobcardId): array
{
    if (!is_resource($conn) || !m360_intake_contract_table_exists($conn, 'erp_intake_contracts')) {
        return [];
    }
    $rows = [];
    if ($onlineRequestId > 0) {
        $sql = 'SELECT TOP 5 contract_id, contract_status, jobcard_id, online_request_id, mobile, signed_at, created_at
                FROM dbo.erp_intake_contracts WHERE online_request_id = ? ORDER BY contract_id DESC';
        $stmt = @odbc_prepare($conn, $sql);
        if ($stmt !== false && @odbc_execute($stmt, [$onlineRequestId])) {
            while (($row = odbc_fetch_array($stmt)) !== false) {
                $norm = [];
                foreach ($row as $k => $v) {
                    $norm[strtolower((string)$k)] = $v === null ? '' : (string)$v;
                }
                $rows[] = $norm;
            }
        }
    }
    if ($rows === [] && $jobcardId > 0) {
        $active = m360_intake_contract_find_active_for_jobcard($conn, $jobcardId);
        if ($active !== null) {
            $rows[] = $active;
        }
    }

    return $rows;
}

/** @return list<array<string, string>> */
function m360_rw_fetch_vehicle_photos($conn, int $vehicleId, int $limit = 10): array
{
    if (!is_resource($conn) || $vehicleId < 1 || !m360_rw_table_exists($conn, 'erp_vehicle_photo_records')) {
        return [];
    }
    $sql = 'SELECT TOP ' . max(1, min(20, $limit)) . ' photo_record_id, vehicle_id, photo_type, file_path, created_at
            FROM dbo.erp_vehicle_photo_records WHERE vehicle_id = ? ORDER BY photo_record_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$vehicleId])) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $norm = [];
        foreach ($row as $k => $v) {
            $norm[strtolower((string)$k)] = $v === null ? '' : (string)$v;
        }
        $rows[] = $norm;
    }

    return $rows;
}

/** @return list<array<string, string>> */
function m360_rw_fetch_jobcard_media($conn, int $jobcardId, int $limit = 10): array
{
    if (!is_resource($conn) || $jobcardId < 1 || !m360_rw_table_exists($conn, 'erp_jobcard_media')) {
        return [];
    }
    $sql = 'SELECT TOP ' . max(1, min(20, $limit)) . ' media_id, jobcard_id, media_type, capture_stage, file_path, created_at
            FROM dbo.erp_jobcard_media WHERE jobcard_id = ? ORDER BY media_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $norm = [];
        foreach ($row as $k => $v) {
            $norm[strtolower((string)$k)] = $v === null ? '' : (string)$v;
        }
        $rows[] = $norm;
    }

    return $rows;
}

/** @return ?array<string, string> */
function m360_rw_fetch_jobcard_row($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1 || !m360_rw_table_exists($conn, 'erp_jobcards')) {
        return null;
    }
    $sql = 'SELECT TOP 1 jobcard_id, jobcard_number, jobcard_status, customer_id, vehicle_id, online_request_id,
                   reception_notes, initial_inspection_notes, contract_status, intake_contract_id,
                   fuel_level, odometer, intake_mileage, complaint_text, created_at
            FROM dbo.erp_jobcards WHERE jobcard_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    $out = [];
    foreach ($row as $k => $v) {
        $out[strtolower((string)$k)] = $v === null ? '' : (string)$v;
    }

    return $out;
}

/**
 * @return array<string, int>
 */
function m360_rw_workbench_kpis($conn): array
{
    $kpi = [
        'online_active' => 0,
        'incomplete' => 0,
        'ready_convert' => 0,
        'jobcards_today' => 0,
        'contracts_pending' => 0,
        'rejected_closed' => 0,
    ];
    if (!is_resource($conn)) {
        return $kpi;
    }

    if (m360_rw_table_exists($conn, m360_online_req_table())) {
        $sql = "SELECT request_status, COUNT(*) AS cnt FROM dbo." . m360_online_req_table() . "
                WHERE request_status NOT IN (N'" . M360_ONLINE_REQ_STATUS_CONVERTED . "', N'" . M360_ONLINE_REQ_STATUS_REJECTED . "')
                GROUP BY request_status";
        $stmt = @odbc_exec($conn, $sql);
        if ($stmt !== false) {
            while (($row = odbc_fetch_array($stmt)) !== false) {
                $kpi['online_active'] += (int)($row['cnt'] ?? 0);
            }
        }

        $sqlReady = "SELECT COUNT(*) AS cnt FROM dbo." . m360_online_req_table() . "
                     WHERE request_status IN (N'" . M360_ONLINE_REQ_STATUS_ACCEPTED . "', N'" . M360_ONLINE_REQ_STATUS_UNDER_REVIEW . "')
                     AND (converted_jobcard_id IS NULL OR converted_jobcard_id = 0)";
        $rStmt = @odbc_exec($conn, $sqlReady);
        if ($rStmt !== false && ($rRow = odbc_fetch_array($rStmt))) {
            $kpi['ready_convert'] = (int)($rRow['cnt'] ?? 0);
        }

        $sqlRej = "SELECT COUNT(*) AS cnt FROM dbo." . m360_online_req_table() . "
                   WHERE request_status = N'" . M360_ONLINE_REQ_STATUS_REJECTED . "'";
        $jStmt = @odbc_exec($conn, $sqlRej);
        if ($jStmt !== false && ($jRow = odbc_fetch_array($jStmt))) {
            $kpi['rejected_closed'] = (int)($jRow['cnt'] ?? 0);
        }

        $kpi['incomplete'] = max(0, $kpi['online_active'] - $kpi['ready_convert']);
    }

    if (m360_rw_table_exists($conn, 'erp_jobcards')) {
        $sqlJc = "SELECT COUNT(*) AS cnt FROM dbo.erp_jobcards
                  WHERE CONVERT(date, created_at) = CONVERT(date, SYSUTCDATETIME())";
        $jcStmt = @odbc_exec($conn, $sqlJc);
        if ($jcStmt !== false && ($jcRow = odbc_fetch_array($jcStmt))) {
            $kpi['jobcards_today'] = (int)($jcRow['cnt'] ?? 0);
        }
    }

    if (m360_intake_contract_table_exists($conn, 'erp_intake_contracts')) {
        $sqlC = "SELECT COUNT(*) AS cnt FROM dbo.erp_intake_contracts
                 WHERE contract_status IN (N'DRAFT', N'SENT', N'VIEWED')";
        $cStmt = @odbc_exec($conn, $sqlC);
        if ($cStmt !== false && ($cRow = odbc_fetch_array($cStmt))) {
            $kpi['contracts_pending'] = (int)($cRow['cnt'] ?? 0);
        }
    }

    return $kpi;
}

/**
 * @return array{
 *   request:?array<string,string>,
 *   payload:array<string,mixed>,
 *   payload_meta:array{valid:bool,raw_warning:string},
 *   payload_rows:list<array{key:string,label_fa:string,value:string,is_nested:bool}>,
 *   customer:?array<string,string>,
 *   vehicle:?array<string,string>,
 *   intake:?array<string,string>,
 *   contracts:list<array<string,string>>,
 *   vehicle_photos:list<array<string,string>>,
 *   jobcard_media:list<array<string,string>>,
 *   jobcard:?array<string,string>,
 *   gate:array<string,mixed>
 * }
 */
function m360_rw_build_intake_file($conn, int $onlineRequestId): array
{
    $emptyGate = m360_rw_build_empty_gate();
    if (!is_resource($conn) || $onlineRequestId < 1) {
        return [
            'request' => null,
            'payload' => [],
            'payload_meta' => ['valid' => true, 'raw_warning' => ''],
            'payload_rows' => [],
            'customer' => null,
            'vehicle' => null,
            'intake' => null,
            'contracts' => [],
            'vehicle_photos' => [],
            'jobcard_media' => [],
            'jobcard' => null,
            'gate' => $emptyGate,
        ];
    }

    $request = m360_online_req_fetch_by_id($conn, $onlineRequestId);
    if ($request === null) {
        return [
            'request' => null,
            'payload' => [],
            'payload_meta' => ['valid' => true, 'raw_warning' => ''],
            'payload_rows' => [],
            'customer' => null,
            'vehicle' => null,
            'intake' => null,
            'contracts' => [],
            'vehicle_photos' => [],
            'jobcard_media' => [],
            'jobcard' => null,
            'gate' => $emptyGate,
        ];
    }

    $payloadMeta = m360_rw_decode_payload($request['request_payload_json'] ?? null);
    $payload = $payloadMeta['items'];
    $payloadRows = m360_rw_payload_display_rows($payload);

    $customerId = (int)($request['customer_id'] ?? 0);
    $vehicleId = (int)($request['vehicle_id'] ?? 0);
    $jobcardId = m360_online_req_converted_jobcard_id($request);
    if ($jobcardId < 1 && $customerId > 0) {
        $jobcardId = 0;
    }

    $customer = $customerId > 0 ? m360_rw_fetch_customer_row($conn, $customerId) : null;
    $vehicle = $vehicleId > 0 ? m360_rw_fetch_vehicle_row($conn, $vehicleId) : null;
    $mobile = trim((string)($request['mobile'] ?? ''));
    $intake = m360_rw_fetch_intake_row($conn, $mobile, $customerId);
    $jobcard = $jobcardId > 0 ? m360_rw_fetch_jobcard_row($conn, $jobcardId) : null;
    $contracts = m360_rw_fetch_contracts_for_request($conn, $onlineRequestId, $jobcardId);
    $vehiclePhotos = $vehicleId > 0 ? m360_rw_fetch_vehicle_photos($conn, $vehicleId) : [];
    $jobcardMedia = $jobcardId > 0 ? m360_rw_fetch_jobcard_media($conn, $jobcardId) : [];

    $gate = m360_rw_build_gate($request, $payload, $customer, $vehicle, $intake, $contracts, $vehiclePhotos, $jobcardMedia, $jobcard);
    $serviceClass = m360_rw_parse_service_classification($payload, $request);
    $fieldRecovery = m360_rw_recover_intake_fields($request, $payload, $customer, $vehicle, $intake, $jobcard, $vehiclePhotos, $jobcardMedia, $contracts);

    return [
        'request' => $request,
        'payload' => $payload,
        'payload_meta' => $payloadMeta,
        'payload_rows' => $payloadRows,
        'customer' => $customer,
        'vehicle' => $vehicle,
        'intake' => $intake,
        'contracts' => $contracts,
        'vehicle_photos' => $vehiclePhotos,
        'jobcard_media' => $jobcardMedia,
        'jobcard' => $jobcard,
        'gate' => $gate,
        'service_classification' => $serviceClass,
        'field_recovery' => $fieldRecovery,
    ];
}

/**
 * @param list<array<string,string>> $contracts
 * @param list<array<string,string>> $vehiclePhotos
 * @param list<array<string,string>> $jobcardMedia
 * @return array<string, mixed>
 */
function m360_rw_build_gate(
    array $request,
    array $payload,
    ?array $customer,
    ?array $vehicle,
    ?array $intake,
    array $contracts,
    array $vehiclePhotos,
    array $jobcardMedia,
    ?array $jobcard
): array {
    $fields = m360_rw_recover_intake_fields($request, $payload, $customer, $vehicle, $intake, $jobcard, $vehiclePhotos, $jobcardMedia, $contracts);

    $customerName = $fields['customer_name']['value'];
    $mobile = $fields['mobile']['value'];
    $plate = $fields['plate']['value'];
    $vin = $fields['vin']['value'];
    $brand = $fields['brand']['value'];
    $model = $fields['model']['value'];
    $mileage = $fields['mileage']['value'];
    $fuel = $fields['fuel']['value'];
    $belongings = $fields['belongings']['value'];
    $damage = $fields['damage']['value'];
    $complaint = m360_rw_pick([$request, $payload], 'service_note', 'complaint', 'request_description');
    $serviceClass = m360_rw_parse_service_classification($payload, $request);
    $costAgreement = $fields['cost']['value'];
    $finalConfirm = m360_rw_pick([$payload, $request], 'reception_final_confirmation', 'customer_confirmation');
    $status = strtoupper(trim((string)($request['request_status'] ?? '')));
    $convertedId = m360_online_req_converted_jobcard_id($request);
    $otpOk = !empty($fields['otp']['verified']);

    $hasPhoto = !empty($fields['photo']['present']);
    $hasDiagMedia = false;
    foreach ($jobcardMedia as $m) {
        if (str_contains(strtolower((string)($m['media_type'] ?? '')), 'diag')) {
            $hasDiagMedia = true;
            break;
        }
    }
    $diagPayload = $fields['diag']['value'];
    $diagPending = m360_rw_pick([$payload], 'diag_pending', 'diagnostic_pending') === '1'
        || str_contains(strtolower($diagPayload), 'pending')
        || str_contains(strtolower($diagPayload), 'انتظار');
    $hasDiag = $hasDiagMedia || ($diagPayload !== '' && !$diagPending);

    $contractStatus = $fields['contract']['value'];
    $vehicleOk = !empty($fields['vehicle']['present']);
    $vehiclePartial = !empty($fields['vehicle']['partial']);
    $brandModelOk = !empty($fields['brand_model']['present']);

    $checks = [
        ['id' => 'customer', 'label' => 'مشتری کامل است؟', 'ok' => $customerName !== '', 'missing_label' => $fields['customer_name']['missing_label'], 'source' => $fields['customer_name']['source_label'], 'phase_next' => false, 'c2c_only' => false],
        ['id' => 'mobile', 'label' => 'موبایل ثبت شده؟', 'ok' => $mobile !== '', 'missing_label' => $fields['mobile']['missing_label'], 'source' => $fields['mobile']['source_label'], 'phase_next' => false, 'c2c_only' => false],
        ['id' => 'otp', 'label' => 'موبایل/OTP روشن است؟', 'ok' => $otpOk, 'missing_label' => $fields['otp']['missing_label'], 'source' => $fields['otp']['source_label'], 'phase_next' => false, 'c2c_only' => false, 'gate_blocker' => true],
        ['id' => 'vehicle', 'label' => 'خودرو شناسایی شده؟', 'ok' => $vehicleOk, 'missing_label' => $fields['vehicle']['missing_label'], 'source' => $fields['vehicle']['source_label'], 'detail' => $fields['vehicle']['detail'], 'partial' => $vehiclePartial, 'phase_next' => false, 'c2c_only' => false],
        ['id' => 'plate', 'label' => 'پلاک مشخص است؟', 'ok' => $plate !== '', 'missing_label' => $fields['plate']['missing_label'], 'source' => $fields['plate']['source_label'], 'phase_next' => false, 'c2c_only' => false],
        ['id' => 'vin', 'label' => 'VIN/شاسی', 'ok' => $vin !== '', 'missing_label' => $fields['vin']['missing_label'], 'source' => $fields['vin']['source_label'], 'phase_next' => true, 'c2c_only' => false],
        ['id' => 'brand_model', 'label' => 'برند/مدل', 'ok' => $brandModelOk, 'missing_label' => $fields['brand_model']['missing_label'], 'source' => $fields['brand_model']['source_label'], 'detail' => $fields['brand_model']['detail'], 'phase_next' => false, 'c2c_only' => false],
        ['id' => 'mileage', 'label' => 'کیلومتر', 'ok' => $mileage !== '', 'missing_label' => $fields['mileage']['missing_label'], 'source' => $fields['mileage']['source_label'], 'phase_next' => true, 'c2c_only' => false],
        ['id' => 'fuel', 'label' => 'سطح سوخت', 'ok' => $fuel !== '', 'missing_label' => $fields['fuel']['missing_label'], 'source' => $fields['fuel']['source_label'], 'phase_next' => true, 'c2c_only' => false],
        ['id' => 'belongings', 'label' => 'لوازم داخل خودرو', 'ok' => $belongings !== '', 'missing_label' => $fields['belongings']['missing_label'], 'source' => $fields['belongings']['source_label'], 'phase_next' => true, 'c2c_only' => true],
        ['id' => 'damage', 'label' => 'آسیب ظاهری', 'ok' => $damage !== '', 'missing_label' => $fields['damage']['missing_label'], 'source' => $fields['damage']['source_label'], 'phase_next' => true, 'c2c_only' => true],
        ['id' => 'complaint', 'label' => 'شرح شکایت مشتری', 'ok' => $complaint !== '', 'missing_label' => 'شرح درخواست/شکایت ثبت نشده است', 'source' => '', 'phase_next' => false, 'c2c_only' => false],
        ['id' => 'service_classification', 'label' => 'دسته‌بندی خدمات توسط پذیرشگر', 'ok' => $serviceClass['registered'], 'missing_label' => 'دسته‌بندی خدمات توسط پذیرشگر ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود', 'source' => '', 'phase_next' => true, 'c2c_only' => true],
        ['id' => 'fault_path', 'label' => 'مسیر عیب/خدمت مشخص است؟', 'ok' => $serviceClass['fault_path_clear'], 'missing_label' => 'مسیر عیب/خدمت هنوز مشخص نیست — پرونده در پذیرش موقت باقی می‌ماند.', 'source' => '', 'phase_next' => true, 'c2c_only' => true],
        ['id' => 'photo', 'label' => 'عکس پذیرش', 'ok' => $hasPhoto, 'missing_label' => $fields['photo']['missing_label'], 'source' => $fields['photo']['source_label'], 'phase_next' => true, 'c2c_only' => true],
        ['id' => 'diag', 'label' => 'دیاگ اولیه یا وضعیت مشخص', 'ok' => $hasDiag || $diagPending, 'missing_label' => $fields['diag']['missing_label'], 'source' => $fields['diag']['source_label'], 'phase_next' => true, 'c2c_only' => true],
        ['id' => 'contract', 'label' => 'قرارداد پذیرش', 'ok' => $contractStatus !== '', 'missing_label' => $fields['contract']['missing_label'], 'source' => $fields['contract']['source_label'], 'phase_next' => true, 'c2c_only' => true],
        ['id' => 'cost', 'label' => 'توافق هزینه', 'ok' => $costAgreement !== '', 'missing_label' => $fields['cost']['missing_label'], 'source' => $fields['cost']['source_label'], 'phase_next' => true, 'c2c_only' => true],
        ['id' => 'final_confirm', 'label' => 'تأیید نهایی پذیرشگر', 'ok' => $finalConfirm !== '' || $status === M360_ONLINE_REQ_STATUS_ACCEPTED, 'missing_label' => 'تأیید نهایی پذیرشگر ثبت نشده است؛ ثبت عملیاتی در C-2C فعال می‌شود', 'source' => '', 'phase_next' => true, 'c2c_only' => true],
        ['id' => 'converted', 'label' => 'تبدیل به کارت کار', 'ok' => $convertedId > 0 || m360_online_req_is_converted($request), 'source' => 'converted_jobcard_id', 'phase_next' => false, 'c2c_only' => false],
    ];

    $missing = [];
    $missingHard = [];
    $missingService = [];
    $partialNotes = [];
    foreach ($checks as $c) {
        if ($c['ok']) {
            if (!empty($c['partial']) && !empty($c['detail'])) {
                $partialNotes[] = (string)$c['detail'];
            }
            continue;
        }
        if (($c['id'] ?? '') === 'converted') {
            continue;
        }
        $msg = m360_rw_gate_missing_message($c);
        if (in_array($c['id'], ['service_classification', 'fault_path'], true)) {
            $missingService[] = $msg;
        }
        $missing[] = $msg;
        if (in_array($c['id'], ['customer', 'mobile', 'otp', 'plate', 'complaint'], true)) {
            $missingHard[] = $c['label'];
        }
    }
    foreach ($fields as $f) {
        if (!empty($f['partial']) && !empty($f['detail']) && !in_array($f['detail'], $partialNotes, true)) {
            $partialNotes[] = $f['detail'];
        }
    }

    $rejected = $status === M360_ONLINE_REQ_STATUS_REJECTED;
    $converted = m360_online_req_is_converted($request);

    if ($converted) {
        $gateStatus = 'converted';
        $gateLabel = 'قبلاً تبدیل شده به کارت کار';
        $receptionMode = 'closed';
    } elseif ($rejected) {
        $gateStatus = 'rejected';
        $gateLabel = 'رد شده / قابل پذیرش نیست';
        $receptionMode = 'closed';
    } elseif (!$otpOk) {
        $gateStatus = 'otp_required';
        $gateLabel = 'نیازمند تأیید مشتری / OTP';
        $receptionMode = 'incomplete';
    } elseif ($missingHard !== []) {
        $gateStatus = 'incomplete_file';
        $gateLabel = 'پرونده ناقص';
        $receptionMode = 'incomplete';
    } elseif (!$serviceClass['registered']) {
        $gateStatus = 'complete_unclear_fault';
        $gateLabel = 'پرونده کامل ولی عیب/خدمت نامشخص — پذیرش موقت';
        $receptionMode = 'temporary';
    } elseif (!$serviceClass['fault_path_clear']) {
        $gateStatus = 'temporary_reception';
        $gateLabel = 'پذیرش موقت — مسیر عیب/خدمت نامشخص';
        $receptionMode = 'temporary';
    } else {
        $softMissing = false;
        foreach ($checks as $c) {
            if (($c['id'] ?? '') === 'converted' || !empty($c['ok'])) {
                continue;
            }
            if (!empty($c['phase_next'])) {
                $softMissing = true;
                break;
            }
        }
        if ($softMissing) {
            $gateStatus = 'ready_full_reception';
            $gateLabel = 'آماده پذیرش کامل';
            $receptionMode = 'full';
        } else {
            $gateStatus = 'ready_convert';
            $gateLabel = 'آماده تبدیل به کارت کار';
            $receptionMode = 'full';
        }
    }

    $canShowConvert = $gateStatus === 'ready_convert';
    $canShowTempActions = !$converted && !$rejected;

    return [
        'status' => $gateStatus,
        'label_fa' => $gateLabel,
        'reception_mode' => $receptionMode,
        'checks' => $checks,
        'missing' => $missing,
        'missing_hard' => $missingHard,
        'missing_service' => $missingService,
        'partial_notes' => $partialNotes,
        'field_recovery' => $fields,
        'can_show_convert' => $canShowConvert,
        'can_show_temp_actions' => $canShowTempActions,
        'converted_jobcard_id' => $convertedId,
        'contract_status' => $contractStatus,
        'service_classification_registered' => $serviceClass['registered'],
        'fault_path_clear' => $serviceClass['fault_path_clear'],
    ];
}

/** @return list<array{title:string,desc:string,href:string,count:?int,placeholder:bool,placeholder_text:string}> */
function m360_rw_workbench_hr_shortcuts(): array
{
    $root = dirname(__DIR__);
    $cards = [
        ['title' => 'پروفایل پرسنلی من', 'desc' => 'مشاهده پروفایل کارمند', 'file' => 'erp-employee-profile.php'],
        ['title' => 'مرخصی / اضافه‌کاری', 'desc' => 'مدیریت مرخصی و اضافه‌کاری', 'file' => 'erp-hr-dashboard.php'],
        ['title' => 'مدارک پرسنلی', 'desc' => 'مدارک و آموزش', 'file' => 'erp-hr-training-discipline.php'],
        ['title' => 'فیش حقوقی', 'desc' => 'پیش‌نمایش حقوق', 'file' => 'erp-payroll-preview.php'],
    ];
    $out = [];
    foreach ($cards as $c) {
        $exists = is_file($root . DIRECTORY_SEPARATOR . $c['file']);
        $out[] = [
            'title' => $c['title'],
            'desc' => $c['desc'],
            'href' => $exists ? $c['file'] : '',
            'count' => null,
            'placeholder' => !$exists,
            'placeholder_text' => 'میانبر پرسنلی در فاز HR Self-Service فعال می‌شود.',
        ];
    }

    return $out;
}

/** @return list<array{title:string,desc:string,href:string,count:?int,placeholder:bool,placeholder_text:string}> */
function m360_rw_workbench_operational_cards(array $kpi): array
{
    return [
        [
            'title' => 'پذیرش خودرو حضوری',
            'desc' => 'ورود خودرو بدون درخواست آنلاین — راهنمای کنترل‌شده',
            'href' => 'erp-reception-workbench.php?section=walkin',
            'count' => null,
            'placeholder' => false,
            'placeholder_text' => '',
        ],
        [
            'title' => 'درخواست‌های آنلاین مشتری',
            'desc' => 'فهرست و بررسی درخواست‌های ثبت‌شده از سایت',
            'href' => 'erp-reception-online-requests.php',
            'count' => (int)($kpi['online_active'] ?? 0),
            'placeholder' => false,
            'placeholder_text' => '',
        ],
        [
            'title' => 'تکمیل پرونده پذیرش',
            'desc' => 'باز کردن پرونده تکمیلی از فهرست درخواست آنلاین',
            'href' => 'erp-reception-online-requests.php',
            'count' => null,
            'placeholder' => false,
            'placeholder_text' => '',
        ],
        [
            'title' => 'پرونده‌های ناقص',
            'desc' => 'درخواست‌های فعال نیازمند تکمیل اطلاعات',
            'href' => 'erp-reception-online-requests.php?status=' . M360_ONLINE_REQ_STATUS_NEW,
            'count' => (int)($kpi['incomplete'] ?? 0),
            'placeholder' => false,
            'placeholder_text' => '',
        ],
        [
            'title' => 'آماده تبدیل به کارت کار',
            'desc' => 'درخواست‌های پذیرفته‌شده آماده تبدیل',
            'href' => 'erp-reception-online-requests.php?status=' . M360_ONLINE_REQ_STATUS_ACCEPTED,
            'count' => (int)($kpi['ready_convert'] ?? 0),
            'placeholder' => false,
            'placeholder_text' => '',
        ],
        [
            'title' => 'کارت‌های کار پذیرش‌شده امروز',
            'desc' => 'JobCardهای ثبت‌شده در روز جاری',
            'href' => 'erp-reception-jobcards.php',
            'count' => (int)($kpi['jobcards_today'] ?? 0),
            'placeholder' => false,
            'placeholder_text' => '',
        ],
        [
            'title' => 'پروفایل خودرو / پاسخ‌گویی مشتری',
            'desc' => 'جستجوی مشتری و خودرو',
            'href' => 'erp-customer-vehicle-workbench.php?role=reception',
            'count' => null,
            'placeholder' => false,
            'placeholder_text' => '',
        ],
        [
            'title' => 'قراردادهای پذیرش',
            'desc' => 'گیت امضای قرارداد P1.5',
            'href' => 'erp-intake-contracts.php',
            'count' => (int)($kpi['contracts_pending'] ?? 0),
            'placeholder' => false,
            'placeholder_text' => '',
        ],
        [
            'title' => 'عکس‌ها و مستندات پذیرش',
            'desc' => 'دوربین و فایل دیاگ — از پرونده یا JobCard',
            'href' => 'erp-reception-online-requests.php',
            'count' => null,
            'placeholder' => false,
            'placeholder_text' => 'از پرونده پذیرش یا JobCard مرتبط باز کنید.',
        ],
        [
            'title' => 'گزارش روزانه پذیرش',
            'desc' => 'خلاصه عملیات روز — فاز بعد',
            'href' => '',
            'count' => null,
            'placeholder' => true,
            'placeholder_text' => 'گزارش روزانه پذیرش در فاز بعدی فعال می‌شود.',
        ],
    ];
}

function m360_rw_gate_status_chip_class(string $status): string
{
    return match ($status) {
        'ready_convert', 'ready_full_reception' => 'is-ready',
        'converted' => 'is-done',
        'otp_required', 'temporary_reception', 'complete_unclear_fault' => 'is-warn',
        'rejected' => 'is-danger',
        'incomplete_file' => 'is-pending',
        default => 'is-pending',
    };
}

/** @return list<array{title:string,desc:string,href:string,placeholder:bool,placeholder_text:string,subs?:list<array{title:string,href:string}>}> */
function m360_rw_reception_process_hub_cards(array $kpi): array
{
    return [
        [
            'title' => 'پذیرش موقت',
            'desc' => 'پرونده‌های با عیب/خدمت نامشخص — تکمیل داده، رد، درخواست اطلاعات، ارجاع کارشناسی',
            'href' => 'erp-reception-online-requests.php?status=' . M360_ONLINE_REQ_STATUS_UNDER_REVIEW,
            'placeholder' => false,
            'placeholder_text' => '',
        ],
        [
            'title' => 'پذیرش',
            'desc' => 'پذیرش حضوری، آنلاین/تکمیل پرونده، پیگیری پرونده‌های در جریان',
            'href' => '',
            'placeholder' => false,
            'placeholder_text' => '',
            'subs' => [
                ['title' => 'پذیرش حضوری', 'href' => 'erp-reception-workbench.php?section=walkin'],
                ['title' => 'پذیرش آنلاین / تکمیل پرونده', 'href' => 'erp-reception-online-requests.php'],
                ['title' => 'پیگیری پرونده‌های در جریان', 'href' => 'erp-reception-jobcards.php'],
            ],
        ],
        [
            'title' => 'کنترل کیفی',
            'desc' => 'QC پذیرش — فاز بعد',
            'href' => '',
            'placeholder' => true,
            'placeholder_text' => 'کنترل کیفی پذیرش در فاز عملیاتی بعد متصل می‌شود.',
        ],
        [
            'title' => 'ترخیص',
            'desc' => 'تحویل خودرو — فاز بعد',
            'href' => '',
            'placeholder' => true,
            'placeholder_text' => 'ترخیص خودرو در فاز عملیاتی بعد متصل می‌شود.',
        ],
    ];
}
