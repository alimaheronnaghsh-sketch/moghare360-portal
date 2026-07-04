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
    $photoStatusInfo = m360_rw_intake_reception_photo_status($payload);
    $erpPhotoCount = count($vehiclePhotos) + count($jobcardMedia);
    $hasPhoto = $photoStatusInfo['complete'];
    $photoDisplay = $hasPhoto
        ? ($photoStatusInfo['count'] . '/' . $photoStatusInfo['min_required'] . ' ثبت شده')
        : ($photoStatusInfo['count'] > 0
            ? ($photoStatusInfo['count'] . '/' . $photoStatusInfo['min_required'] . ' ثبت شده')
            : '');
    $photoMissing = m360_rw_intake_reception_photo_missing_message($photoStatusInfo);
    if ($photoMissing === '' && !$hasPhoto) {
        $photoMissing = 'عکس پذیرش کامل نیست؛ حداقل ۶ عکس الزامی است.';
    }
    $photoSource = $hasPhoto ? 'payload' : ($photoStatusInfo['count'] > 0 || $photoStatusInfo['legacy_only'] ? 'payload' : ($erpPhotoCount > 0 ? 'erp_vehicle' : ''));

    $contractStatus = '';
    if ($contracts !== []) {
        $contractStatus = (string)($contracts[0]['contract_status'] ?? '');
    } elseif ($jobcard !== null) {
        $contractStatus = (string)($jobcard['contract_status'] ?? '');
    }
    if ($contractStatus === '') {
        $contractStatus = m360_rw_pick_meta($map, ['contract_status', 'reception_contract_status'])['value'];
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
        'belongings' => m360_rw_field_result($belongingsMeta['value'], $belongingsMeta['source_key'], 'لوازم داخل خودرو ثبت نشده است'),
        'damage' => m360_rw_field_result($damageMeta['value'], $damageMeta['source_key'], 'آسیب ظاهری ثبت نشده است'),
        'vehicle' => m360_rw_field_result(
            $vehicleOk ? ($hasErpVehicle ? 'ERP #' . (string)$vehicleId : 'Payload') : '',
            $hasErpVehicle ? 'erp_vehicle' : ($hasPayloadVehicle ? 'payload' : ''),
            'خودرو شناسایی نشده است',
            $vehiclePartial,
            $vehicleDetail
        ),
        'photo' => m360_rw_field_result(
            $photoDisplay,
            $photoSource,
            $photoMissing,
            !$hasPhoto && ($photoStatusInfo['legacy_only'] || $photoStatusInfo['count'] > 0),
            !$hasPhoto && $photoStatusInfo['legacy_only'] ? 'عکس قدیمی موجود است، اما چک‌لیست ۶ عکس هنوز کامل نیست.' : ''
        ),
        'diag' => m360_rw_field_result(
            $diagMeta['value'],
            $diagMeta['source_key'],
            'دیاگ اولیه یا وضعیت عیب‌یابی ثبت نشده است'
        ),
        'contract' => m360_rw_field_result(
            $contractStatus,
            $contractStatus !== '' ? ($contracts !== [] ? 'erp_vehicle' : 'payload') : '',
            'قرارداد پذیرش ثبت نشده است'
        ),
        'cost' => m360_rw_field_result($costMeta['value'], $costMeta['source_key'], 'توافق هزینه ثبت نشده است'),
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
        'photo' => 'عکس پذیرش کامل نیست؛ حداقل ۶ عکس الزامی است.',
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
    $pathRaw = m360_rw_pick([$payload], 'fault_service_path_clear', 'service_fault_path_clear', 'service_path_clear');
    if ($pathRaw === '' && isset($payload['reception_intake']['service_classification']) && is_array($payload['reception_intake']['service_classification'])) {
        $nestedPath = $payload['reception_intake']['service_classification']['service_path_clear'] ?? null;
        if (is_bool($nestedPath)) {
            $pathRaw = $nestedPath ? '1' : '0';
        } elseif ($nestedPath !== null && $nestedPath !== '') {
            $pathRaw = in_array(strtolower((string)$nestedPath), ['1', 'yes', 'true'], true) ? '1' : '0';
        }
    }
    $faultPathClear = in_array(strtolower($pathRaw), ['1', 'yes', 'true'], true);
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
    $payload = m360_rw_intake_payload_for_recovery($payloadMeta['items']);
    $payloadRows = m360_rw_payload_display_rows($payloadMeta['items']);

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

    $photoStatusInfo = m360_rw_intake_reception_photo_status($payload);
    $hasPhoto = $photoStatusInfo['complete'];
    $photoCheckLabel = 'عکس پذیرش: ' . $photoStatusInfo['count'] . '/' . $photoStatusInfo['min_required'];
    $photoMissingLabel = m360_rw_intake_reception_photo_missing_message($photoStatusInfo);
    if ($photoMissingLabel === '') {
        $photoMissingLabel = $fields['photo']['missing_label'];
    }
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
        ['id' => 'photo', 'label' => $photoCheckLabel, 'ok' => $hasPhoto, 'missing_label' => $photoMissingLabel, 'source' => $fields['photo']['source_label'], 'phase_next' => true, 'c2c_only' => true],
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

// --- P11.9-C-2C: Reception intake write actions (payload merge, no schema change) ---

const M360_RW_INTAKE_HISTORY_PREFIX = 'RECEPTION_INTAKE_SAVE_';

/** @return list<string> */
function m360_rw_intake_allowed_actions(): array
{
    return [
        'save_mobile_correction',
        'send_customer_otp',
        'save_vehicle_identity',
        'save_condition_notes',
        'save_service_classification',
        'save_temporary_reception',
        'save_referral_team',
        'save_documents_and_cost',
        'save_camera_photo',
        'save_diagnostic_pdf',
        'run_intake_contract',
        'approve_intake_contract',
        'save_reception_confirmation',
    ];
}

/** @return list<string> */
function m360_rw_intake_fuel_levels(): array
{
    return ['خالی', 'یک‌چهارم', 'نصف', 'سه‌چهارم', 'پر', 'نامشخص'];
}

/** @return array<string, string> */
function m360_rw_intake_temp_statuses(): array
{
    return [
        'pending_info' => 'در انتظار تکمیل اطلاعات',
        'expert_review' => 'نیازمند بررسی کارشناسی',
        'rejected' => 'رد شده',
        'held_temporary' => 'نگهداری موقت',
    ];
}

function m360_rw_intake_status_is_pending(string $status): bool
{
    $s = strtolower(trim($status));
    return in_array($s, ['pending', 'منتظر', 'در انتظار', 'waiting', ''], true);
}

/**
 * @param array<string, mixed> $payload
 * @return array<string, mixed>
 */
function m360_rw_intake_payload_for_recovery(array $payload): array
{
    if (!isset($payload['reception_intake']) || !is_array($payload['reception_intake'])) {
        return $payload;
    }
    $ri = $payload['reception_intake'];
    if (isset($ri['vehicle']) && is_array($ri['vehicle'])) {
        $v = $ri['vehicle'];
        $map = [
            'plate' => 'vehicle_plate',
            'vin' => 'vin',
            'brand' => 'brand',
            'model' => 'model',
            'mileage' => 'odometer_km',
            'fuel_level' => 'fuel_level',
        ];
        foreach ($map as $from => $to) {
            if ((!isset($payload[$to]) || trim((string)$payload[$to]) === '') && isset($v[$from]) && trim((string)$v[$from]) !== '') {
                $payload[$to] = $v[$from];
            }
        }
    }
    if (isset($ri['condition']) && is_array($ri['condition'])) {
        $c = $ri['condition'];
        foreach (['vehicle_items' => 'belongings', 'visible_damage' => 'visible_damage', 'initial_vehicle_condition' => 'initial_vehicle_condition'] as $from => $to) {
            if ((!isset($payload[$to]) || trim((string)$payload[$to]) === '') && isset($c[$from]) && trim((string)$c[$from]) !== '') {
                $payload[$to] = $c[$from];
            }
        }
    }
    if (isset($ri['documents']) && is_array($ri['documents'])) {
        $d = $ri['documents'];
        foreach (['photo_status', 'diagnostic_status', 'contract_status', 'cost_agreement', 'cost_agreement_note'] as $key) {
            if ((!isset($payload[$key]) || trim((string)$payload[$key]) === '') && isset($d[$key]) && trim((string)$d[$key]) !== '') {
                $payload[$key] = $d[$key];
            }
        }
    }
    if (isset($ri['reception_confirmation']) && is_array($ri['reception_confirmation'])) {
        $rc = $ri['reception_confirmation'];
        if (!empty($rc['confirmed_by_receptionist']) && empty($payload['reception_final_confirmation'])) {
            $payload['reception_final_confirmation'] = '1';
        }
        if (!empty($rc['confirmation_note']) && empty($payload['reception_confirmation_note'])) {
            $payload['reception_confirmation_note'] = $rc['confirmation_note'];
        }
    }

    return $payload;
}

/** @return array<string, mixed> */
function m360_rw_intake_ensure_nested(array $payload): array
{
    if (!isset($payload['reception_intake']) || !is_array($payload['reception_intake'])) {
        $payload['reception_intake'] = [];
    }

    return $payload;
}

/** @return array{ok:bool,error:string} */
function m360_rw_intake_validate_plate(mixed $plate): array
{
    $normalized = m360_rw_intake_normalize_scalar_text($plate);
    if (!$normalized['ok']) {
        return ['ok' => false, 'error' => 'پلاک نامعتبر است.'];
    }
    $plate = $normalized['value'];
    if ($plate === '') {
        return ['ok' => false, 'error' => 'پلاک الزامی است.'];
    }
    if (mb_strlen($plate) > 50) {
        return ['ok' => false, 'error' => 'پلاک بیش از حد مجاز طولانی است.'];
    }

    return ['ok' => true, 'error' => ''];
}

/** @return array{ok:bool,error:string} */
function m360_rw_intake_validate_vin(mixed $vin): array
{
    $normalized = m360_rw_intake_normalize_scalar_text($vin);
    if (!$normalized['ok']) {
        return ['ok' => false, 'error' => 'VIN/شاسی نامعتبر است.'];
    }
    $vin = $normalized['value'];
    if ($vin === '') {
        return ['ok' => true, 'error' => ''];
    }
    if (mb_strlen($vin) > 40) {
        return ['ok' => false, 'error' => 'VIN/شاسی بیش از حد مجاز طولانی است.'];
    }

    return ['ok' => true, 'error' => ''];
}

/** @return array{ok:bool,error:string} */
function m360_rw_intake_normalize_scalar_text(mixed $value): array
{
    if ($value === null) {
        return ['ok' => true, 'error' => '', 'value' => ''];
    }
    if (is_bool($value)) {
        return ['ok' => true, 'error' => '', 'value' => $value ? '1' : '0'];
    }
    if (is_int($value) || is_float($value)) {
        return ['ok' => true, 'error' => '', 'value' => trim((string)$value)];
    }
    if (is_string($value)) {
        return ['ok' => true, 'error' => '', 'value' => trim($value)];
    }

    return ['ok' => false, 'error' => 'مقدار واردشده معتبر نیست.', 'value' => ''];
}

/** @return array{ok:bool,error:string,value:string} */
function m360_rw_intake_post_scalar(array $post, string $key): array
{
    if (!array_key_exists($key, $post)) {
        return ['ok' => true, 'error' => '', 'value' => ''];
    }

    return m360_rw_intake_normalize_scalar_text($post[$key]);
}

/** @return array{ok:bool,error:string} */
function m360_rw_intake_validate_text(mixed $value, int $maxLen, string $label): array
{
    $normalized = m360_rw_intake_normalize_scalar_text($value);
    if (!$normalized['ok']) {
        return ['ok' => false, 'error' => $label . ': ' . $normalized['error']];
    }
    $text = $normalized['value'];
    if (mb_strlen($text) > $maxLen) {
        return ['ok' => false, 'error' => $label . ' بیش از حد مجاز طولانی است.'];
    }

    return ['ok' => true, 'error' => ''];
}

/** @return array{ok:bool,error:string,value:string} */
function m360_rw_intake_validate_mileage(mixed $mileage): array
{
    $normalized = m360_rw_intake_normalize_scalar_text($mileage);
    if (!$normalized['ok']) {
        return ['ok' => false, 'error' => 'کیلومتر نامعتبر است.', 'value' => ''];
    }
    $mileage = $normalized['value'];
    if ($mileage === '') {
        return ['ok' => true, 'error' => '', 'value' => ''];
    }
    if (!preg_match('/^\d+$/', $mileage)) {
        return ['ok' => false, 'error' => 'کیلومتر باید عدد صحیح باشد.', 'value' => ''];
    }
    $n = (int)$mileage;
    if ($n < 0) {
        return ['ok' => false, 'error' => 'کیلومتر نمی‌تواند منفی باشد.', 'value' => ''];
    }

    return ['ok' => true, 'error' => '', 'value' => (string)$n];
}

/** @return array{ok:bool,error:string} */
function m360_rw_intake_validate_fuel(mixed $fuel): array
{
    $normalized = m360_rw_intake_normalize_scalar_text($fuel);
    if (!$normalized['ok']) {
        return ['ok' => false, 'error' => 'سطح سوخت انتخاب‌شده نامعتبر است.'];
    }
    $fuel = $normalized['value'];
    if ($fuel === '') {
        return ['ok' => true, 'error' => ''];
    }
    if (!in_array($fuel, m360_rw_intake_fuel_levels(), true)) {
        return ['ok' => false, 'error' => 'سطح سوخت انتخاب‌شده نامعتبر است.'];
    }

    return ['ok' => true, 'error' => ''];
}

const M360_RW_INTAKE_SAVE_GENERIC_ERROR_FA = 'خطا در ذخیره اطلاعات پذیرش. مقدار واردشده معتبر نیست یا نیاز به بررسی دارد.';

/**
 * @param list<array{value:string,label:string,max:int}> $fields
 * @return array{ok:bool,error:string}
 */
function m360_rw_intake_validate_text_fields(array $fields): array
{
    foreach ($fields as $field) {
        $vt = m360_rw_intake_validate_text($field['value'], $field['max'], $field['label']);
        if (!$vt['ok']) {
            return $vt;
        }
    }

    return ['ok' => true, 'error' => ''];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, string> $post
 * @return array{ok:bool,error:string,payload:array<string,mixed>,column_updates:array<string,string>}
 */
function m360_rw_intake_apply_action(array $payload, string $actionType, array $post): array
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $columnUpdates = [];

    switch ($actionType) {
        case 'save_vehicle_identity':
            $plateBuilt = m360_rw_intake_build_plate_from_post($post);
            if (!$plateBuilt['ok']) {
                return ['ok' => false, 'error' => $plateBuilt['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $plate = $plateBuilt['plate'];
            $vinNorm = m360_rw_intake_post_scalar($post, 'vin');
            $brandNorm = m360_rw_intake_post_scalar($post, 'brand');
            $modelNorm = m360_rw_intake_post_scalar($post, 'model');
            $mileageNorm = m360_rw_intake_post_scalar($post, 'mileage');
            $fuelNorm = m360_rw_intake_post_scalar($post, 'fuel_level');
            foreach ([$vinNorm, $brandNorm, $modelNorm, $mileageNorm, $fuelNorm] as $norm) {
                if (!$norm['ok']) {
                    return ['ok' => false, 'error' => $norm['error'], 'payload' => $payload, 'column_updates' => []];
                }
            }
            $vin = $vinNorm['value'];
            $brand = $brandNorm['value'];
            $model = $modelNorm['value'];
            $fuel = $fuelNorm['value'];

            $vPlate = m360_rw_intake_validate_plate($plate);
            if (!$vPlate['ok']) {
                return ['ok' => false, 'error' => $vPlate['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $vVin = m360_rw_intake_validate_vin($vin);
            if (!$vVin['ok']) {
                return ['ok' => false, 'error' => $vVin['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $vtBrandModel = m360_rw_intake_validate_text_fields([
                ['value' => $brand, 'label' => 'برند', 'max' => 120],
                ['value' => $model, 'label' => 'مدل', 'max' => 120],
            ]);
            if (!$vtBrandModel['ok']) {
                return ['ok' => false, 'error' => $vtBrandModel['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $vMileage = m360_rw_intake_validate_mileage($mileageNorm['value']);
            if (!$vMileage['ok']) {
                return ['ok' => false, 'error' => $vMileage['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $vFuel = m360_rw_intake_validate_fuel($fuel);
            if (!$vFuel['ok']) {
                return ['ok' => false, 'error' => $vFuel['error'], 'payload' => $payload, 'column_updates' => []];
            }

            $payload['vehicle_plate'] = $plate;
            $payload['plate'] = $plate;
            if ($plateBuilt['parts'] !== []) {
                $payload['plate_parts'] = $plateBuilt['parts'];
            }
            if ($vin !== '') {
                $payload['vin'] = $vin;
            }
            if ($brand !== '') {
                $payload['brand'] = $brand;
                $payload['vehicle_brand'] = $brand;
            }
            if ($model !== '') {
                $payload['model'] = $model;
                $payload['vehicle_model'] = $model;
            }
            if ($vMileage['value'] !== '') {
                $payload['odometer_km'] = $vMileage['value'];
                $payload['mileage'] = $vMileage['value'];
            }
            if ($fuel !== '') {
                $payload['fuel_level'] = $fuel;
                $payload['intake_fuel_level'] = $fuel;
            }
            $payload['reception_intake']['vehicle'] = [
                'plate' => $plate,
                'vin' => $vin,
                'brand' => $brand,
                'model' => $model,
                'mileage' => $vMileage['value'],
                'fuel_level' => $fuel,
            ];
            $columnUpdates['vehicle_plate'] = $plate;
            m360_rw_intake_mark_section_saved($payload, 'vehicle_identity');
            break;

        case 'save_mobile_correction':
            $mobileNorm = m360_rw_intake_post_scalar($post, 'mobile_corrected');
            if (!$mobileNorm['ok']) {
                return ['ok' => false, 'error' => $mobileNorm['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $mobile = $mobileNorm['value'];
            if ($mobile === '' || !preg_match('/^09\d{9}$/', $mobile)) {
                return ['ok' => false, 'error' => 'شماره موبایل معتبر نیست (فرمت 09xxxxxxxxx).', 'payload' => $payload, 'column_updates' => []];
            }
            $payload['mobile'] = $mobile;
            $payload['otp_verified'] = 0;
            $payload['reception_intake']['mobile_correction'] = [
                'mobile' => $mobile,
                'corrected_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ];
            $columnUpdates['mobile'] = $mobile;
            m360_rw_intake_mark_section_saved($payload, 'mobile_otp');
            break;

        case 'save_condition_notes':
            $itemsNorm = m360_rw_intake_post_scalar($post, 'vehicle_items');
            $damageNorm = m360_rw_intake_post_scalar($post, 'visible_damage');
            $initialNorm = m360_rw_intake_post_scalar($post, 'initial_vehicle_condition');
            foreach ([$itemsNorm, $damageNorm, $initialNorm] as $norm) {
                if (!$norm['ok']) {
                    return ['ok' => false, 'error' => $norm['error'], 'payload' => $payload, 'column_updates' => []];
                }
            }
            $items = $itemsNorm['value'];
            $damage = $damageNorm['value'];
            $initial = $initialNorm['value'];
            $vt = m360_rw_intake_validate_text_fields([
                ['value' => $items, 'label' => 'لوازم داخل خودرو', 'max' => 2000],
                ['value' => $damage, 'label' => 'آسیب ظاهری', 'max' => 2000],
                ['value' => $initial, 'label' => 'وضعیت اولیه', 'max' => 2000],
            ]);
            if (!$vt['ok']) {
                return ['ok' => false, 'error' => $vt['error'], 'payload' => $payload, 'column_updates' => []];
            }
            if ($items !== '') {
                $payload['belongings'] = $items;
                $payload['vehicle_items'] = $items;
            }
            if ($damage !== '') {
                $payload['visible_damage'] = $damage;
                $payload['body_damage'] = $damage;
            }
            if ($initial !== '') {
                $payload['initial_vehicle_condition'] = $initial;
            }
            $payload['reception_intake']['condition'] = [
                'vehicle_items' => $items,
                'visible_damage' => $damage,
                'initial_vehicle_condition' => $initial,
            ];
            m360_rw_intake_mark_section_saved($payload, 'condition_notes');
            break;

        case 'save_service_classification':
            $primary = trim((string)($post['service_primary'] ?? ''));
            $allowedPrimary = array_keys(m360_rw_service_classification_taxonomy());
            if ($primary === '' || !in_array($primary, $allowedPrimary, true)) {
                return ['ok' => false, 'error' => 'دسته‌بندی اصلی خدمات الزامی است.', 'payload' => $payload, 'column_updates' => []];
            }
            $diagSubs = [];
            if ($primary === 'diag') {
                $rawSubs = $post['service_diag_sub'] ?? [];
                if (!is_array($rawSubs)) {
                    $rawSubs = $rawSubs !== '' ? [(string)$rawSubs] : [];
                }
                $allowedSubs = array_keys(m360_rw_service_classification_taxonomy()['diag']['subs']);
                foreach ($rawSubs as $sub) {
                    $sub = trim((string)$sub);
                    if ($sub !== '' && in_array($sub, $allowedSubs, true)) {
                        $diagSubs[] = $sub;
                    }
                }
                if ($diagSubs === []) {
                    return ['ok' => false, 'error' => 'حداقل یک زیردسته عیب‌یابی انتخاب کنید.', 'payload' => $payload, 'column_updates' => []];
                }
            }
            $pathClearRaw = trim((string)($post['service_path_clear'] ?? ''));
            if (!in_array($pathClearRaw, ['0', '1'], true)) {
                return ['ok' => false, 'error' => 'وضعیت روشن بودن مسیر عیب/خدمت را مشخص کنید.', 'payload' => $payload, 'column_updates' => []];
            }
            $pathNoteNorm = m360_rw_intake_post_scalar($post, 'service_path_note');
            if (!$pathNoteNorm['ok']) {
                return ['ok' => false, 'error' => $pathNoteNorm['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $pathNote = $pathNoteNorm['value'];
            $vt = m360_rw_intake_validate_text($pathNote, 2000, 'یادداشت مسیر خدمت');
            if (!$vt['ok']) {
                return ['ok' => false, 'error' => $vt['error'], 'payload' => $payload, 'column_updates' => []];
            }

            $payload['reception_service_primary'] = $primary;
            unset($payload['reception_service_periodic'], $payload['reception_service_trade']);
            if ($primary === 'diag') {
                $payload['reception_service_diag_sub'] = $diagSubs;
            } else {
                unset($payload['reception_service_diag_sub']);
                if ($primary === 'periodic') {
                    $payload['reception_service_periodic'] = '1';
                } elseif ($primary === 'trade') {
                    $payload['reception_service_trade'] = '1';
                }
            }
            $payload['fault_service_path_clear'] = $pathClearRaw;
            $payload['service_path_clear'] = $pathClearRaw;
            if ($pathNote !== '') {
                $payload['service_path_note'] = $pathNote;
            }
            $payload['reception_intake']['service_classification'] = [
                'main' => $primary,
                'diagnostic_categories' => $diagSubs,
                'service_path_clear' => $pathClearRaw === '1',
                'service_path_note' => $pathNote,
            ];
            m360_rw_intake_mark_section_saved($payload, 'service_classification');
            break;

        case 'save_temporary_reception':
            $tempStatus = trim((string)($post['temporary_status'] ?? ''));
            $statuses = m360_rw_intake_temp_statuses();
            if ($tempStatus === '' || !isset($statuses[$tempStatus])) {
                return ['ok' => false, 'error' => 'وضعیت پذیرش موقت نامعتبر است.', 'payload' => $payload, 'column_updates' => []];
            }
            $reasonNorm = m360_rw_intake_post_scalar($post, 'temporary_reason');
            $moreInfoNorm = m360_rw_intake_post_scalar($post, 'request_more_info_note');
            $expertNoteNorm = m360_rw_intake_post_scalar($post, 'expert_review_note');
            foreach ([$reasonNorm, $moreInfoNorm, $expertNoteNorm] as $norm) {
                if (!$norm['ok']) {
                    return ['ok' => false, 'error' => $norm['error'], 'payload' => $payload, 'column_updates' => []];
                }
            }
            $reason = $reasonNorm['value'];
            $moreInfo = $moreInfoNorm['value'];
            $expertNote = $expertNoteNorm['value'];
            $vt = m360_rw_intake_validate_text_fields([
                ['value' => $reason, 'label' => 'دلیل', 'max' => 2000],
                ['value' => $moreInfo, 'label' => 'درخواست اطلاعات', 'max' => 2000],
                ['value' => $expertNote, 'label' => 'یادداشت کارشناسی', 'max' => 2000],
            ]);
            if (!$vt['ok']) {
                return ['ok' => false, 'error' => $vt['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $payload['reception_intake']['temporary_reception'] = [
                'status' => $tempStatus,
                'reason' => $reason,
                'request_more_info_note' => $moreInfo,
                'expert_review_required' => $tempStatus === 'expert_review',
                'expert_review_note' => $expertNote,
            ];
            $payload['temporary_reception_status'] = $tempStatus;
            if ($reason !== '') {
                $payload['temporary_reception_reason'] = $reason;
            }
            if ($moreInfo !== '') {
                $payload['request_more_info_note'] = $moreInfo;
            }
            if ($expertNote !== '') {
                $payload['expert_review_note'] = $expertNote;
            }
            m360_rw_intake_mark_section_saved($payload, 'temporary_reception');
            break;

        case 'save_referral_team':
            $teamId = trim((string)($post['referral_team_id'] ?? ''));
            $teams = m360_rw_intake_referral_teams();
            if ($teamId === '' || !isset($teams[$teamId])) {
                return ['ok' => false, 'error' => 'تیم ارجاع انتخاب نشده است.', 'payload' => $payload, 'column_updates' => []];
            }
            $refType = trim((string)($post['referral_type'] ?? 'service_team'));
            $noteNorm = m360_rw_intake_post_scalar($post, 'referral_note');
            if (!$noteNorm['ok']) {
                return ['ok' => false, 'error' => $noteNorm['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $vt = m360_rw_intake_validate_text($noteNorm['value'], 2000, 'یادداشت ارجاع');
            if (!$vt['ok']) {
                return ['ok' => false, 'error' => $vt['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $payload['reception_intake']['referral'] = [
                'referral_team_id' => $teamId,
                'referral_team_label' => $teams[$teamId],
                'referral_type' => $refType,
                'referral_note' => $noteNorm['value'],
                'referred_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ];
            m360_rw_intake_mark_section_saved($payload, 'referral');
            break;

        case 'save_documents_and_cost':
            $photoNorm = m360_rw_intake_post_scalar($post, 'photo_status');
            $diagNorm = m360_rw_intake_post_scalar($post, 'diagnostic_status');
            $contractNorm = m360_rw_intake_post_scalar($post, 'contract_status');
            $costNorm = m360_rw_intake_post_scalar($post, 'cost_agreement');
            $costNoteNorm = m360_rw_intake_post_scalar($post, 'cost_agreement_note');
            foreach ([$photoNorm, $diagNorm, $contractNorm, $costNorm, $costNoteNorm] as $norm) {
                if (!$norm['ok']) {
                    return ['ok' => false, 'error' => $norm['error'], 'payload' => $payload, 'column_updates' => []];
                }
            }
            $photoStatus = $photoNorm['value'];
            $diagStatus = $diagNorm['value'];
            $contractStatus = $contractNorm['value'];
            $costAgreement = $costNorm['value'];
            $costNote = $costNoteNorm['value'];
            $vt = m360_rw_intake_validate_text_fields([
                ['value' => $photoStatus, 'label' => 'وضعیت عکس', 'max' => 500],
                ['value' => $diagStatus, 'label' => 'وضعیت دیاگ', 'max' => 500],
                ['value' => $contractStatus, 'label' => 'وضعیت قرارداد', 'max' => 500],
                ['value' => $costAgreement, 'label' => 'توافق هزینه', 'max' => 500],
                ['value' => $costNote, 'label' => 'یادداشت هزینه', 'max' => 500],
            ]);
            if (!$vt['ok']) {
                return ['ok' => false, 'error' => $vt['error'], 'payload' => $payload, 'column_updates' => []];
            }
            if ($photoStatus !== '') {
                $payload['photo_status'] = $photoStatus;
            }
            if ($diagStatus !== '') {
                $payload['diagnostic_status'] = $diagStatus;
                $payload['diag_status'] = $diagStatus;
            }
            if ($contractStatus !== '') {
                $payload['contract_status'] = $contractStatus;
            }
            if ($costAgreement !== '') {
                $payload['cost_agreement'] = $costAgreement;
            }
            if ($costNote !== '') {
                $payload['cost_agreement_note'] = $costNote;
            }
            $payload['reception_intake']['documents'] = [
                'photo_status' => $photoStatus,
                'diagnostic_status' => $diagStatus,
                'contract_status' => $contractStatus,
                'cost_agreement' => $costAgreement,
                'cost_agreement_note' => $costNote,
            ];
            m360_rw_intake_mark_section_saved($payload, 'documents_cost');
            break;

        case 'save_camera_photo':
            $slot = trim((string)($post['photo_slot'] ?? ''));
            $slotDefs = m360_rw_intake_reception_photo_slots();
            if ($slot === '' || !isset($slotDefs[$slot])) {
                return ['ok' => false, 'error' => 'اسلات عکس پذیرش انتخاب نشده یا نامعتبر است.', 'payload' => $payload, 'column_updates' => []];
            }
            $reqId = (int)($post['online_request_id'] ?? 0);
            $img = trim((string)($post['camera_image_base64'] ?? ''));
            $saved = m360_rw_intake_save_base64_image($reqId, $img, 'photo_' . $slot);
            if (!$saved['ok']) {
                return ['ok' => false, 'error' => $saved['error'], 'payload' => $payload, 'column_updates' => []];
            }
            if (!isset($payload['reception_intake']['documents']) || !is_array($payload['reception_intake']['documents'])) {
                $payload['reception_intake']['documents'] = [];
            }
            if (!isset($payload['reception_intake']['documents']['reception_photos']) || !is_array($payload['reception_intake']['documents']['reception_photos'])) {
                $payload['reception_intake']['documents']['reception_photos'] = [];
            }
            $payload['reception_intake']['documents']['reception_photos'][$slot] = [
                'label' => $slotDefs[$slot],
                'file' => $saved['relative_path'],
                'saved_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ];
            $photoStatus = m360_rw_intake_reception_photo_status($payload);
            $payload['reception_intake']['documents']['photo_count'] = $photoStatus['count'];
            $payload['reception_intake']['documents']['photo_min_required'] = M360_RW_INTAKE_PHOTO_MIN_REQUIRED;
            $payload['reception_intake']['documents']['photo_status'] = $photoStatus['complete']
                ? 'ثبت شد'
                : ($photoStatus['count'] . '/' . $photoStatus['min_required']);
            $payload['photo_status'] = $payload['reception_intake']['documents']['photo_status'];
            if ($photoStatus['complete']) {
                m360_rw_intake_mark_section_saved($payload, 'camera_photo');
            }
            break;

        case 'run_intake_contract':
            $payload['reception_intake']['contract'] = [
                'status' => 'DRAFT',
                'contract_text' => m360_rw_intake_contract_template_text([], $payload),
                'run_at' => gmdate('Y-m-d\TH:i:s\Z'),
                'customer_approved' => false,
            ];
            $payload['contract_status'] = 'DRAFT';
            m360_rw_intake_mark_section_saved($payload, 'contract');
            break;

        case 'approve_intake_contract':
            $approved = isset($post['customer_contract_approved']) && (string)$post['customer_contract_approved'] === '1';
            if (!$approved) {
                return ['ok' => false, 'error' => 'تأیید صریح مشتری برای قرارداد الزامی است.', 'payload' => $payload, 'column_updates' => []];
            }
            if (empty($payload['reception_intake']['contract']['run_at'])) {
                return ['ok' => false, 'error' => 'ابتدا قرارداد پذیرش را اجرا کنید.', 'payload' => $payload, 'column_updates' => []];
            }
            $payload['reception_intake']['contract']['customer_approved'] = true;
            $payload['reception_intake']['contract']['approved_at'] = gmdate('Y-m-d\TH:i:s\Z');
            $payload['reception_intake']['contract']['status'] = 'CUSTOMER_APPROVED_PAYLOAD';
            $payload['contract_status'] = 'CUSTOMER_APPROVED_PAYLOAD';
            m360_rw_intake_mark_section_saved($payload, 'contract');
            break;

        case 'save_reception_confirmation':
            $confirmed = isset($post['confirmed_by_receptionist']) && (string)$post['confirmed_by_receptionist'] === '1';
            $noteNorm = m360_rw_intake_post_scalar($post, 'confirmation_note');
            if (!$noteNorm['ok']) {
                return ['ok' => false, 'error' => $noteNorm['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $note = $noteNorm['value'];
            $vt = m360_rw_intake_validate_text($note, 2000, 'یادداشت تأیید');
            if (!$vt['ok']) {
                return ['ok' => false, 'error' => $vt['error'], 'payload' => $payload, 'column_updates' => []];
            }
            if (!$confirmed) {
                return ['ok' => false, 'error' => 'برای ثبت تأیید، گزینه تأیید نهایی پذیرشگر را علامت بزنید.', 'payload' => $payload, 'column_updates' => []];
            }
            $payload['reception_final_confirmation'] = '1';
            if ($note !== '') {
                $payload['reception_confirmation_note'] = $note;
            }
            $payload['reception_intake']['reception_confirmation'] = [
                'confirmed_by_receptionist' => true,
                'confirmation_note' => $note,
                'confirmed_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ];
            m360_rw_intake_mark_section_saved($payload, 'reception_confirmation');
            break;

        default:
            return ['ok' => false, 'error' => 'نوع عملیات نامعتبر است.', 'payload' => $payload, 'column_updates' => []];
    }

    return ['ok' => true, 'error' => '', 'payload' => $payload, 'column_updates' => $columnUpdates];
}

/**
 * @param array<string, string> $columnUpdates
 * @return array{ok:bool,message:string}
 */
function m360_rw_intake_persist_payload($conn, int $requestId, array $payload, array $columnUpdates): array
{
    if (!is_resource($conn) || $requestId < 1) {
        return ['ok' => false, 'message' => 'اتصال یا شناسه درخواست نامعتبر است.'];
    }

    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        return ['ok' => false, 'message' => 'خطا در آماده‌سازی داده پرونده.'];
    }

    $sets = ['request_payload_json = ?'];
    $params = [$encoded];

    if (m360_online_req_has_column($conn, 'updated_at')) {
        $sets[] = 'updated_at = SYSUTCDATETIME()';
    }
    if (isset($columnUpdates['vehicle_plate']) && m360_online_req_has_column($conn, 'vehicle_plate')) {
        $sets[] = 'vehicle_plate = ?';
        $params[] = $columnUpdates['vehicle_plate'];
    }
    if (isset($columnUpdates['mobile']) && m360_online_req_has_column($conn, 'mobile')) {
        $sets[] = 'mobile = ?';
        $params[] = $columnUpdates['mobile'];
    }

    $params[] = $requestId;
    $sql = 'UPDATE dbo.' . m360_online_req_table() . ' SET ' . implode(', ', $sets) . ' WHERE online_request_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return ['ok' => false, 'message' => 'ذخیره پرونده در پایگاه داده انجام نشد.'];
    }

    return ['ok' => true, 'message' => ''];
}

/**
 * @param array<string, string> $post
 * @return array{ok:bool,message:string,history_written:bool}
 */
function m360_rw_intake_process_save($conn, int $requestId, string $actionType, array $post, array $files = []): array
{
    try {
        return m360_rw_intake_process_save_inner($conn, $requestId, $actionType, $post, $files);
    } catch (Throwable) {
        return ['ok' => false, 'message' => M360_RW_INTAKE_SAVE_GENERIC_ERROR_FA, 'history_written' => false];
    }
}

/**
 * @param array<string, string> $post
 * @param array<string, mixed> $files
 * @return array{ok:bool,message:string,history_written:bool}
 */
function m360_rw_intake_process_save_inner($conn, int $requestId, string $actionType, array $post, array $files = []): array
{
    if (!in_array($actionType, m360_rw_intake_allowed_actions(), true)) {
        return ['ok' => false, 'message' => 'نوع عملیات نامعتبر است.', 'history_written' => false];
    }

    $request = m360_online_req_fetch_by_id($conn, $requestId);
    if ($request === null) {
        return ['ok' => false, 'message' => 'درخواست یافت نشد.', 'history_written' => false];
    }
    if (m360_online_req_is_converted($request)) {
        return ['ok' => false, 'message' => 'این درخواست قبلاً تبدیل شده و قابل ویرایش نیست.', 'history_written' => false];
    }
    if (strtoupper(trim((string)($request['request_status'] ?? ''))) === M360_ONLINE_REQ_STATUS_REJECTED) {
        return ['ok' => false, 'message' => 'درخواست رد شده و قابل ویرایش نیست.', 'history_written' => false];
    }

    if ($actionType === 'send_customer_otp') {
        return m360_rw_intake_process_send_otp($conn, $requestId, $request);
    }

    $existing = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
    $otpPreserved = $existing['otp_verified'] ?? null;
    $resetOtp = ($actionType === 'save_mobile_correction');

    if ($actionType === 'save_diagnostic_pdf') {
        $pdfFile = $files['diagnostic_pdf'] ?? null;
        if (!is_array($pdfFile)) {
            return ['ok' => false, 'message' => 'فایل PDF دریافت نشد.', 'history_written' => false];
        }
        $saved = m360_rw_intake_save_pdf_upload($requestId, $pdfFile);
        if (!$saved['ok']) {
            return ['ok' => false, 'message' => $saved['error'], 'history_written' => false];
        }
        $existing = m360_rw_intake_ensure_nested($existing);
        if (!isset($existing['reception_intake']['documents']) || !is_array($existing['reception_intake']['documents'])) {
            $existing['reception_intake']['documents'] = [];
        }
        $existing['reception_intake']['documents']['diagnostic_pdf'] = $saved['relative_path'];
        $existing['reception_intake']['documents']['diagnostic_status'] = 'ثبت شد';
        $existing['diagnostic_status'] = 'ثبت شد';
        m360_rw_intake_mark_section_saved($existing, 'diagnostic_pdf');
        $persist = m360_rw_intake_persist_payload($conn, $requestId, $existing, []);
        if (!$persist['ok']) {
            return ['ok' => false, 'message' => $persist['message'], 'history_written' => false];
        }
        $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
        $historyWritten = m360_online_req_write_history($conn, $requestId, M360_RW_INTAKE_HISTORY_PREFIX . 'SAVE_DIAGNOSTIC_PDF', (string)($request['request_status'] ?? ''), (string)($request['request_status'] ?? ''), 'Diagnostic PDF saved', $userId);

        return ['ok' => true, 'message' => 'فایل دیاگ ذخیره شد.', 'history_written' => $historyWritten];
    }

    $post['online_request_id'] = (string)$requestId;
    $applied = m360_rw_intake_apply_action($existing, $actionType, $post);
    if (!$applied['ok']) {
        return ['ok' => false, 'message' => $applied['error'], 'history_written' => false];
    }

    $newPayload = $applied['payload'];
    if ($resetOtp) {
        $newPayload['otp_verified'] = 0;
    } elseif ($otpPreserved !== null) {
        $newPayload['otp_verified'] = $otpPreserved;
    }

    if ($actionType === 'run_intake_contract') {
        $newPayload['reception_intake']['contract']['contract_text'] = m360_rw_intake_contract_template_text($request, $newPayload);
    }

    $persist = m360_rw_intake_persist_payload($conn, $requestId, $newPayload, $applied['column_updates']);
    if (!$persist['ok']) {
        return ['ok' => false, 'message' => $persist['message'], 'history_written' => false];
    }

    if ($actionType === 'save_temporary_reception') {
        $tempStatus = trim((string)($post['temporary_status'] ?? ''));
        if ($tempStatus === 'rejected') {
            m360_reception_update_status(
                $conn,
                $requestId,
                M360_ONLINE_REQ_STATUS_REJECTED,
                M360_ONLINE_REQ_HISTORY_REJECTED,
                'Rejected via temporary reception save'
            );
        } elseif ($tempStatus === 'pending_info') {
            m360_reception_update_status(
                $conn,
                $requestId,
                M360_ONLINE_REQ_STATUS_UNDER_REVIEW,
                M360_ONLINE_REQ_HISTORY_UNDER_REVIEW,
                'More information requested via intake'
            );
        }
    }

    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
    $eventType = M360_RW_INTAKE_HISTORY_PREFIX . strtoupper($actionType);
    $historyWritten = m360_online_req_write_history(
        $conn,
        $requestId,
        $eventType,
        (string)($request['request_status'] ?? ''),
        (string)($request['request_status'] ?? ''),
        'Reception intake save: ' . $actionType,
        $userId
    );

    $messages = [
        'save_vehicle_identity' => 'اطلاعات خودرو ذخیره شد.',
        'save_condition_notes' => 'یادداشت‌های وضعیت خودرو ذخیره شد.',
        'save_service_classification' => 'دسته‌بندی خدمات پذیرشگر ذخیره شد.',
        'save_temporary_reception' => 'وضعیت پذیرش موقت ذخیره شد.',
        'save_mobile_correction' => 'شماره موبایل اصلاح شد. OTP همچنان نیازمند تأیید مشتری است.',
        'save_referral_team' => 'ارجاع تیم ذخیره شد.',
        'save_camera_photo' => 'عکس پذیرش ذخیره شد.',
        'run_intake_contract' => 'قرارداد پذیرش آماده نمایش شد.',
        'approve_intake_contract' => 'تأیید مشتری برای قرارداد ثبت شد.',
        'save_documents_and_cost' => 'مستندات و توافق هزینه ذخیره شد.',
        'save_reception_confirmation' => 'تأیید نهایی پذیرشگر ثبت شد.',
    ];

    return [
        'ok' => true,
        'message' => $messages[$actionType] ?? 'ذخیره انجام شد.',
        'history_written' => $historyWritten,
    ];
}

/**
 * @return array<string, string>
 */
function m360_rw_intake_form_values(array $payload, array $request): array
{
    $payload = m360_rw_intake_payload_for_recovery($payload);
    $ri = isset($payload['reception_intake']) && is_array($payload['reception_intake']) ? $payload['reception_intake'] : [];
    $vehicle = is_array($ri['vehicle'] ?? null) ? $ri['vehicle'] : [];
    $condition = is_array($ri['condition'] ?? null) ? $ri['condition'] : [];
    $service = is_array($ri['service_classification'] ?? null) ? $ri['service_classification'] : [];
    $temp = is_array($ri['temporary_reception'] ?? null) ? $ri['temporary_reception'] : [];
    $docs = is_array($ri['documents'] ?? null) ? $ri['documents'] : [];
    $confirm = is_array($ri['reception_confirmation'] ?? null) ? $ri['reception_confirmation'] : [];

    $pathClear = m360_rw_pick([$payload], 'fault_service_path_clear', 'service_path_clear');
    if ($pathClear === '' && isset($service['service_path_clear'])) {
        $pathClear = !empty($service['service_path_clear']) ? '1' : '0';
    }

    $diagSubs = $payload['reception_service_diag_sub'] ?? ($service['diagnostic_categories'] ?? []);
    if (!is_array($diagSubs)) {
        $diagSubs = $diagSubs !== '' ? explode(',', (string)$diagSubs) : [];
    }

    $plate = m360_rw_pick([$vehicle, $payload, $request], 'plate', 'vehicle_plate');
    $plateLeft = (string)($payload['plate_parts']['left_2'] ?? '');
    $plateLetter = (string)($payload['plate_parts']['letter'] ?? '');
    $plateMid = (string)($payload['plate_parts']['middle_3'] ?? '');
    $plateIran = (string)($payload['plate_parts']['region_2'] ?? '');
    if ($plateLeft === '' && $plateLetter === '' && $plateMid === '' && $plateIran === '' && $plate !== '') {
        if (preg_match('/^(\d{2})([' . implode('', m360_rw_intake_plate_letters()) . '])(\d{3})-(\d{2})$/u', $plate, $pm)) {
            $plateLeft = $pm[1];
            $plateLetter = $pm[2];
            $plateMid = $pm[3];
            $plateIran = $pm[4];
        }
    }

    return [
        'plate' => $plate,
        'vin' => m360_rw_pick([$vehicle, $payload], 'vin', 'chassis'),
        'brand' => m360_rw_pick([$vehicle, $payload], 'brand', 'vehicle_brand'),
        'model' => m360_rw_pick([$vehicle, $payload], 'model', 'vehicle_model'),
        'mileage' => m360_rw_pick([$vehicle, $payload], 'mileage', 'odometer_km', 'mileage'),
        'fuel_level' => m360_rw_pick([$vehicle, $payload], 'fuel_level', 'intake_fuel_level', 'fuel'),
        'vehicle_items' => m360_rw_pick([$condition, $payload], 'vehicle_items', 'belongings'),
        'visible_damage' => m360_rw_pick([$condition, $payload], 'visible_damage', 'body_damage'),
        'initial_vehicle_condition' => m360_rw_pick([$condition, $payload], 'initial_vehicle_condition'),
        'service_primary' => m360_rw_pick([$payload, $service], 'reception_service_primary', 'main'),
        'service_path_clear' => $pathClear,
        'service_path_note' => m360_rw_pick([$payload, $service], 'service_path_note'),
        'temporary_status' => m360_rw_pick([$temp, $payload], 'status', 'temporary_reception_status'),
        'temporary_reason' => m360_rw_pick([$temp, $payload], 'reason', 'temporary_reception_reason'),
        'request_more_info_note' => m360_rw_pick([$temp, $payload], 'request_more_info_note'),
        'expert_review_note' => m360_rw_pick([$temp, $payload], 'expert_review_note'),
        'photo_status' => m360_rw_pick([$docs, $payload], 'photo_status'),
        'diagnostic_status' => m360_rw_pick([$docs, $payload], 'diagnostic_status', 'diag_status'),
        'contract_status' => m360_rw_pick([$docs, $payload], 'contract_status'),
        'cost_agreement' => m360_rw_pick([$docs, $payload], 'cost_agreement'),
        'cost_agreement_note' => m360_rw_pick([$docs, $payload], 'cost_agreement_note'),
        'confirmation_note' => m360_rw_pick([$confirm, $payload], 'confirmation_note', 'reception_confirmation_note'),
        'confirmed_by_receptionist' => m360_rw_pick([$payload, $confirm], 'reception_final_confirmation') === '1' || !empty($confirm['confirmed_by_receptionist']) ? '1' : '0',
        'service_diag_sub_codes' => $diagSubs,
        'plate_left_2_digits' => $plateLeft,
        'plate_letter' => $plateLetter,
        'plate_middle_3_digits' => $plateMid,
        'plate_iran_2_digits' => $plateIran,
        'mobile_corrected' => m360_rw_pick([$payload, $request], 'mobile'),
        'referral_team_id' => (string)($payload['reception_intake']['referral']['referral_team_id'] ?? ''),
        'referral_type' => (string)($payload['reception_intake']['referral']['referral_type'] ?? 'service_team'),
        'referral_note' => (string)($payload['reception_intake']['referral']['referral_note'] ?? ''),
        'contract_text' => (string)($payload['reception_intake']['contract']['contract_text'] ?? ''),
        'contract_approved' => !empty($payload['reception_intake']['contract']['customer_approved']) ? '1' : '0',
        'photo_file' => (string)($payload['reception_intake']['documents']['photo_file'] ?? ''),
        'diagnostic_pdf' => (string)($payload['reception_intake']['documents']['diagnostic_pdf'] ?? ''),
        'photo_count' => (string)($payload['reception_intake']['documents']['photo_count'] ?? ''),
        'photo_min_required' => (string)($payload['reception_intake']['documents']['photo_min_required'] ?? (string)M360_RW_INTAKE_PHOTO_MIN_REQUIRED),
        'reception_photos' => is_array($payload['reception_intake']['documents']['reception_photos'] ?? null)
            ? $payload['reception_intake']['documents']['reception_photos']
            : [],
    ];
}

function m360_rw_intake_save_redirect_url(int $requestId, string $message, bool $ok, array $post = []): string
{
    $returnSection = trim((string)($post['return_section'] ?? ''));
    if ($returnSection === '') {
        $returnSection = m360_rw_intake_action_default_anchor(trim((string)($post['action_type'] ?? '')));
    }
    $sectionKey = m360_rw_intake_anchor_to_section_key($returnSection);

    $url = 'erp-reception-intake-file.php?online_request_id=' . $requestId
        . '&msg=' . rawurlencode($message)
        . '&ok=' . ($ok ? '1' : '0');

    if (!$ok && $sectionKey !== '') {
        $url .= '&edit_section=' . rawurlencode($sectionKey);
    }

    if ($returnSection !== '') {
        $url .= '#' . $returnSection;
    }

    return $url;
}

/** @return array<string, string> */
function m360_rw_intake_section_anchors(): array
{
    return [
        'mobile_otp' => 'section-mobile-otp',
        'vehicle_identity' => 'section-vehicle-identity',
        'condition_notes' => 'section-condition-notes',
        'service_classification' => 'section-service-classification',
        'temporary_reception' => 'section-temporary-reception',
        'referral' => 'section-referral-team',
        'camera_photo' => 'section-camera-photo',
        'diagnostic_pdf' => 'section-diagnostic-pdf',
        'contract' => 'section-contract',
        'documents_cost' => 'section-documents-cost',
        'reception_confirmation' => 'section-reception-confirmation',
        'gate_checklist' => 'section-gate-checklist',
    ];
}

function m360_rw_intake_section_key_to_anchor(string $sectionKey): string
{
    $map = m360_rw_intake_section_anchors();

    return $map[$sectionKey] ?? '';
}

function m360_rw_intake_anchor_to_section_key(string $anchor): string
{
    $anchor = ltrim(trim($anchor), '#');
    foreach (m360_rw_intake_section_anchors() as $key => $id) {
        if ($id === $anchor) {
            return $key;
        }
    }

    return '';
}

function m360_rw_intake_action_default_anchor(string $actionType): string
{
    return match ($actionType) {
        'save_mobile_correction', 'send_customer_otp' => 'section-mobile-otp',
        'save_vehicle_identity' => 'section-vehicle-identity',
        'save_condition_notes' => 'section-condition-notes',
        'save_service_classification' => 'section-service-classification',
        'save_temporary_reception' => 'section-temporary-reception',
        'save_referral_team' => 'section-referral-team',
        'save_camera_photo' => 'section-camera-photo',
        'save_diagnostic_pdf' => 'section-diagnostic-pdf',
        'run_intake_contract', 'approve_intake_contract' => 'section-contract',
        'save_documents_and_cost' => 'section-documents-cost',
        'save_reception_confirmation' => 'section-reception-confirmation',
        default => '',
    };
}

function m360_rw_intake_return_section_hidden(string $sectionKey): void
{
    $anchor = m360_rw_intake_section_key_to_anchor($sectionKey);
    if ($anchor === '') {
        return;
    }
    echo '<input type="hidden" name="return_section" value="' . m360_rw_h($anchor) . '">';
}

const M360_RW_INTAKE_PHOTO_MIN_REQUIRED = 6;

/**
 * Six reception photo slots — aligned with MOGHARE360_INPUT_PHOTO_6_RULE (Persian labels for intake UI).
 *
 * @return array<string, string>
 */
function m360_rw_intake_reception_photo_slots(): array
{
    return [
        'front' => 'نمای جلو خودرو',
        'rear' => 'نمای عقب خودرو',
        'right' => 'سمت راست خودرو',
        'left' => 'سمت چپ خودرو',
        'interior' => 'داخل کابین',
        'dashboard' => 'کیلومتر / داشبورد',
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{count:int,min_required:int,complete:bool,missing_labels:list<string>,slots:array<string,array{label:string,file:string,saved:bool,saved_at:string}>,legacy_file:string,legacy_only:bool}
 */
function m360_rw_intake_reception_photo_status(array $payload): array
{
    $slotDefs = m360_rw_intake_reception_photo_slots();
    $min = M360_RW_INTAKE_PHOTO_MIN_REQUIRED;
    $payload = m360_rw_intake_ensure_nested($payload);
    $docs = is_array($payload['reception_intake']['documents'] ?? null) ? $payload['reception_intake']['documents'] : [];
    $stored = is_array($docs['reception_photos'] ?? null) ? $docs['reception_photos'] : [];
    $legacyFile = trim((string)($docs['photo_file'] ?? ''));
    $count = 0;
    $missing = [];
    $slots = [];

    foreach ($slotDefs as $key => $label) {
        $entry = is_array($stored[$key] ?? null) ? $stored[$key] : [];
        $file = trim((string)($entry['file'] ?? ''));
        $saved = $file !== '';
        if ($saved) {
            $count++;
        } else {
            $missing[] = $label;
        }
        $slots[$key] = [
            'label' => $label,
            'file' => $file,
            'saved' => $saved,
            'saved_at' => (string)($entry['saved_at'] ?? ''),
        ];
    }

    return [
        'count' => $count,
        'min_required' => $min,
        'complete' => $count >= $min && $missing === [],
        'missing_labels' => $missing,
        'slots' => $slots,
        'legacy_file' => $legacyFile,
        'legacy_only' => $legacyFile !== '' && $count === 0,
    ];
}

function m360_rw_intake_reception_photo_missing_message(array $photoStatus): string
{
    if (!empty($photoStatus['complete'])) {
        return '';
    }
    if (!empty($photoStatus['legacy_only'])) {
        return 'عکس قدیمی موجود است، اما چک‌لیست ۶ عکس هنوز کامل نیست.';
    }
    if (($photoStatus['count'] ?? 0) > 0 && !empty($photoStatus['missing_labels'])) {
        return 'عکس پذیرش ناقص است: ' . implode('، ', $photoStatus['missing_labels']);
    }

    return 'عکس پذیرش کامل نیست؛ حداقل ۶ عکس الزامی است.';
}

// --- P11.9-C-2C-FIX-B: Operational UAT (mobile, plate, sections, camera, contract, referral) ---

/** @return list<string> */
function m360_rw_intake_plate_letters(): array
{
    return ['ب', 'ج', 'د', 'س', 'ص', 'ط', 'ق', 'ل', 'م', 'ن', 'و', 'ه', 'ی', 'ع', 'پ', 'ت', 'ک', 'گ'];
}

/** @return array<string, string> */
function m360_rw_intake_referral_teams(): array
{
    return [
        'team_1' => 'تیم ۱',
        'team_2' => 'تیم ۲',
        'team_3' => 'تیم ۳',
        'team_electrical' => 'تیم برق',
        'team_mechanical' => 'تیم مکانیک',
    ];
}

/** @return array<string, string> */
function m360_rw_intake_section_titles(): array
{
    return [
        'mobile_otp' => 'شماره موبایل و تأیید مشتری',
        'vehicle_identity' => 'اطلاعات خودرو',
        'condition_notes' => 'وضعیت خودرو (لوازم / آسیب)',
        'service_classification' => 'دسته‌بندی خدمات پذیرشگر',
        'temporary_reception' => 'وضعیت پذیرش موقت',
        'referral' => 'ارجاع کارشناسی / تیم مسئول',
        'documents_cost' => 'مستندات و توافق هزینه',
        'camera_photo' => 'عکس پذیرش خودرو',
        'diagnostic_pdf' => 'فایل دیاگ اولیه',
        'contract' => 'قرارداد پذیرش و تأیید مشتری',
        'reception_confirmation' => 'تأیید نهایی پذیرشگر',
    ];
}

function m360_rw_intake_load_otp_helper(): bool
{
    static $loaded = false;
    if (!$loaded && is_file(__DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
        $loaded = true;
    }

    return function_exists('m360_otp_send');
}

/** @return array{available:bool,reason_fa:string} */
function m360_rw_intake_otp_send_available(): array
{
    if (!m360_rw_intake_load_otp_helper()) {
        return ['available' => false, 'reason_fa' => 'ارسال OTP نیازمند اتصال پیامک فعال است.'];
    }
    if (function_exists('m360_otp_sms_configured') && m360_otp_sms_configured()) {
        return ['available' => true, 'reason_fa' => ''];
    }
    if (function_exists('m360_otp_can_use_dev_code') && m360_otp_can_use_dev_code()) {
        return ['available' => true, 'reason_fa' => 'حالت توسعه محلی — کد OTP در لاگ/محیط dev'];
    }

    return ['available' => false, 'reason_fa' => 'ارسال OTP نیازمند اتصال پیامک فعال است.'];
}

function m360_rw_intake_storage_root(int $onlineRequestId): string
{
    $root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'reception-intake'
        . DIRECTORY_SEPARATOR . max(1, $onlineRequestId);
    if (!is_dir($root)) {
        @mkdir($root, 0755, true);
    }

    return $root;
}

/**
 * @param array<string, mixed> $payload
 */
function m360_rw_intake_mark_section_saved(array &$payload, string $sectionId): void
{
    $payload = m360_rw_intake_ensure_nested($payload);
    if (!isset($payload['reception_intake']['section_status']) || !is_array($payload['reception_intake']['section_status'])) {
        $payload['reception_intake']['section_status'] = [];
    }
    $payload['reception_intake']['section_status'][$sectionId] = [
        'completed' => true,
        'saved_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, string> $request
 */
function m360_rw_intake_section_is_complete(string $sectionId, array $payload, array $request, array $formValues): bool
{
    $status = $payload['reception_intake']['section_status'][$sectionId]['completed'] ?? false;
    if ($status) {
        return true;
    }

    return match ($sectionId) {
        'mobile_otp' => trim((string)($request['mobile'] ?? '')) !== '',
        'vehicle_identity' => trim((string)($formValues['plate'] ?? '')) !== '',
        'condition_notes' => trim((string)($formValues['vehicle_items'] ?? '')) !== '' || trim((string)($formValues['visible_damage'] ?? '')) !== '',
        'service_classification' => trim((string)($formValues['service_primary'] ?? '')) !== '',
        'temporary_reception' => trim((string)($formValues['temporary_status'] ?? '')) !== '',
        'referral' => trim((string)($formValues['referral_team_id'] ?? '')) !== '',
        'documents_cost' => trim((string)($formValues['cost_agreement'] ?? '')) !== '' || trim((string)($formValues['photo_status'] ?? '')) !== '',
        'camera_photo' => m360_rw_intake_reception_photo_status($payload)['complete'],
        'diagnostic_pdf' => !empty($payload['reception_intake']['documents']['diagnostic_pdf']),
        'contract' => !empty($payload['reception_intake']['contract']['run_at']),
        'reception_confirmation' => ($formValues['confirmed_by_receptionist'] ?? '') === '1',
        default => false,
    };
}

/**
 * @return array{show_summary:bool,show_form:bool,status_label:string}
 */
function m360_rw_intake_section_ui_state(string $sectionId, array $payload, array $request, array $formValues, string $editSection): array
{
    $complete = m360_rw_intake_section_is_complete($sectionId, $payload, $request, $formValues);
    $editing = $editSection === $sectionId;
    $showForm = !$complete || $editing;
    $showSummary = $complete && !$editing;
    $statusLabel = $editing ? 'در حال ویرایش' : ($complete ? 'تکمیل شده' : 'نیازمند تکمیل');

    return ['show_summary' => $showSummary, 'show_form' => $showForm, 'status_label' => $statusLabel, 'complete' => $complete];
}

/**
 * @param array<string, string> $post
 * @return array{ok:bool,error:string,plate:string,parts:array<string,string>}
 */
function m360_rw_intake_build_plate_from_post(array $post): array
{
    $left = trim((string)($post['plate_left_2_digits'] ?? ''));
    $letter = trim((string)($post['plate_letter'] ?? ''));
    $mid = trim((string)($post['plate_middle_3_digits'] ?? ''));
    $iran = trim((string)($post['plate_iran_2_digits'] ?? ''));
    $fallback = trim((string)($post['plate'] ?? ''));

    if ($left === '' && $letter === '' && $mid === '' && $iran === '') {
        if ($fallback === '') {
            return ['ok' => false, 'error' => 'پلاک الزامی است.', 'plate' => '', 'parts' => []];
        }

        return ['ok' => true, 'error' => '', 'plate' => $fallback, 'parts' => []];
    }

    if (!preg_match('/^\d{2}$/', $left)) {
        return ['ok' => false, 'error' => 'دو رقم اول پلاک نامعتبر است.', 'plate' => '', 'parts' => []];
    }
    if (!preg_match('/^\d{3}$/', $mid)) {
        return ['ok' => false, 'error' => 'سه رقم وسط پلاک نامعتبر است.', 'plate' => '', 'parts' => []];
    }
    if (!preg_match('/^\d{2}$/', $iran)) {
        return ['ok' => false, 'error' => 'کد ایران پلاک نامعتبر است.', 'plate' => '', 'parts' => []];
    }
    if ($letter === '' || !in_array($letter, m360_rw_intake_plate_letters(), true)) {
        return ['ok' => false, 'error' => 'حرف پلاک نامعتبر است.', 'plate' => '', 'parts' => []];
    }

    $plate = $left . $letter . $mid . '-' . $iran;
    $parts = ['left_2' => $left, 'letter' => $letter, 'middle_3' => $mid, 'region_2' => $iran];

    return ['ok' => true, 'error' => '', 'plate' => $plate, 'parts' => $parts];
}

/** @return array{ok:bool,error:string,relative_path:string} */
function m360_rw_intake_save_base64_image(int $onlineRequestId, string $base64, string $prefix): array
{
    $raw = trim($base64);
    if ($raw === '') {
        return ['ok' => false, 'error' => 'تصویر دریافت نشد.', 'relative_path' => ''];
    }
    if (preg_match('/^data:image\/(jpeg|jpg|png);base64,(.+)$/i', $raw, $m)) {
        $ext = strtolower($m[1]) === 'png' ? 'png' : 'jpg';
        $data = base64_decode($m[2], true);
    } else {
        $data = base64_decode($raw, true);
        $ext = 'jpg';
    }
    if ($data === false || strlen($data) < 100) {
        return ['ok' => false, 'error' => 'فرمت تصویر نامعتبر است.', 'relative_path' => ''];
    }
    if (strlen($data) > 2 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'حجم تصویر بیش از حد مجاز است.', 'relative_path' => ''];
    }

    $fname = $prefix . '_' . gmdate('YmdHis') . '.' . $ext;
    $dir = m360_rw_intake_storage_root($onlineRequestId);
    $full = $dir . DIRECTORY_SEPARATOR . $fname;
    if (@file_put_contents($full, $data) === false) {
        return ['ok' => false, 'error' => 'ذخیره تصویر انجام نشد.', 'relative_path' => ''];
    }

    return ['ok' => true, 'error' => '', 'relative_path' => 'reception-intake/' . $onlineRequestId . '/' . $fname];
}

/** @return array{ok:bool,error:string,relative_path:string} */
function m360_rw_intake_save_pdf_upload(int $onlineRequestId, array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'فایل PDF دریافت نشد.', 'relative_path' => ''];
    }
    $name = (string)($file['name'] ?? '');
    $tmp = (string)($file['tmp_name'] ?? '');
    $size = (int)($file['size'] ?? 0);
    if ($size < 1 || $size > 5 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'حجم PDF بیش از حد مجاز است.', 'relative_path' => ''];
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        return ['ok' => false, 'error' => 'فقط فایل PDF مجاز است.', 'relative_path' => ''];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo !== false ? (string)finfo_file($finfo, $tmp) : '';
    if ($finfo !== false) {
        finfo_close($finfo);
    }
    if ($mime !== '' && $mime !== 'application/pdf') {
        return ['ok' => false, 'error' => 'نوع فایل PDF معتبر نیست.', 'relative_path' => ''];
    }

    $dir = m360_rw_intake_storage_root($onlineRequestId) . DIRECTORY_SEPARATOR . 'diagnostic';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $fname = 'diag_' . gmdate('YmdHis') . '.pdf';
    $dest = $dir . DIRECTORY_SEPARATOR . $fname;
    if (!@move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => 'ذخیره PDF انجام نشد.', 'relative_path' => ''];
    }

    return ['ok' => true, 'error' => '', 'relative_path' => 'reception-intake/' . $onlineRequestId . '/diagnostic/' . $fname];
}

function m360_rw_intake_contract_template_text(array $request, array $payload): string
{
    $name = m360_rw_pick([$request, $payload], 'customer_name');
    $mobile = m360_rw_pick([$request, $payload], 'mobile');
    $plate = m360_rw_pick([$payload, $request], 'vehicle_plate', 'plate');

    return "قرارداد پذیرش خودرو\n\n"
        . "نام مشتری: {$name}\n"
        . "موبایل: {$mobile}\n"
        . "پلاک: {$plate}\n\n"
        . "با پذیرش این قرارداد، مشتری شرایط پذیرش و نگهداری خودرو در مرکز را مطالعه و تأیید می‌کند.";
}

/**
 * @return array{ok:bool,message:string,history_written:bool}
 */
function m360_rw_intake_process_send_otp($conn, int $requestId, array $request): array
{
    $avail = m360_rw_intake_otp_send_available();
    if (!$avail['available']) {
        return ['ok' => false, 'message' => $avail['reason_fa'], 'history_written' => false];
    }
    $mobile = trim((string)($request['mobile'] ?? ''));
    if ($mobile === '') {
        return ['ok' => false, 'message' => 'موبایل برای ارسال OTP ثبت نشده است.', 'history_written' => false];
    }
    $result = m360_otp_send($mobile);
    if (empty($result['ok'])) {
        return ['ok' => false, 'message' => (string)($result['message'] ?? 'ارسال OTP انجام نشد.'), 'history_written' => false];
    }

    return ['ok' => true, 'message' => 'درخواست ارسال OTP ثبت شد. مشتری باید کد را از طریق مسیر تأیید مشتری وارد کند.', 'history_written' => false];
}

function m360_rw_intake_edit_url(int $onlineRequestId, string $sectionId): string
{
    $anchor = m360_rw_intake_section_key_to_anchor($sectionId);

    return 'erp-reception-intake-file.php?online_request_id=' . $onlineRequestId
        . '&edit_section=' . rawurlencode($sectionId)
        . ($anchor !== '' ? '#' . $anchor : '');
}

function m360_rw_intake_render_section_header(string $title, array $uiState, int $onlineRequestId, string $sectionId, bool $canAct): void
{
    echo '<div class="m360-rw-sec-head">';
    echo '<h3 class="m360-rw-sec-title">' . m360_rw_h($title) . '</h3>';
    echo '<span class="m360-rw-sec-status is-' . ($uiState['complete'] ? 'done' : 'pending') . '">' . m360_rw_h((string)$uiState['status_label']) . '</span>';
    if ($canAct && !empty($uiState['show_summary'])) {
        echo ' <a class="m360-rw-btn m360-rw-btn-secondary m360-rw-sec-edit" href="' . m360_rw_h(m360_rw_intake_edit_url($onlineRequestId, $sectionId)) . '">ویرایش</a>';
    }
    echo '</div>';
}

/** @param array<string, string> $formValues */
function m360_rw_intake_render_plate_widget(array $formValues): void
{
    $left = $formValues['plate_left_2_digits'] ?? '';
    $letter = $formValues['plate_letter'] ?? '';
    $mid = $formValues['plate_middle_3_digits'] ?? '';
    $iran = $formValues['plate_iran_2_digits'] ?? '';
    echo '<div class="m360-rw-plate-wrap">';
    echo '<p class="m360-rw-muted">پلاک ایران — از چپ: ۲ رقم، حرف، ۳ رقم، کد ایران</p>';
    echo '<div class="iran-plate-widget m360-rw-plate-widget" dir="rtl">';
    echo '<div class="iran-plate-ir-band"><span class="iran-plate-ir-band__ir">IR</span><span class="iran-plate-ir-band__flag">🇮🇷</span></div>';
    echo '<div class="iran-plate-body">';
    echo '<input class="m360-rw-form-input m360-rw-plate-part" type="text" name="plate_left_2_digits" maxlength="2" inputmode="numeric" pattern="[0-9]{2}" value="' . m360_rw_h($left) . '" placeholder="۱۲">';
    echo '<select class="m360-rw-form-input m360-rw-plate-letter" name="plate_letter"><option value="">حرف</option>';
    foreach (m360_rw_intake_plate_letters() as $pl) {
        $sel = ($letter === $pl) ? ' selected' : '';
        echo '<option value="' . m360_rw_h($pl) . '"' . $sel . '>' . m360_rw_h($pl) . '</option>';
    }
    echo '</select>';
    echo '<input class="m360-rw-form-input m360-rw-plate-part" type="text" name="plate_middle_3_digits" maxlength="3" inputmode="numeric" pattern="[0-9]{3}" value="' . m360_rw_h($mid) . '" placeholder="۳۴۵">';
    echo '</div>';
    echo '<div class="iran-plate-region-box"><span class="iran-plate-region-box__label">ایران</span>';
    echo '<input class="m360-rw-form-input m360-rw-plate-part" type="text" name="plate_iran_2_digits" maxlength="2" inputmode="numeric" pattern="[0-9]{2}" value="' . m360_rw_h($iran) . '" placeholder="۶۷">';
    echo '</div></div>';
    echo '<p id="m360_rw_plate_preview" class="iran-plate-preview m360-rw-plate-preview"></p>';
    echo '</div>';
}

/**
 * @param array<string, mixed> $payloadData
 * @param array<string, string> $request
 * @param array<string, mixed> $formValues
 */
function m360_rw_intake_render_reception_photos_section(
    int $onlineRequestId,
    array $payloadData,
    array $request,
    array $formValues,
    string $editSection,
    bool $canAct,
    string $csrfInputHtml,
    string $saveUrl
): void {
    $secCam = m360_rw_intake_section_ui_state('camera_photo', $payloadData, $request, $formValues, $editSection);
    $photoStatus = m360_rw_intake_reception_photo_status($payloadData);
    echo '<section class="m360-rw-section-block" id="section-camera-photo">';
    echo '<h2 class="m360-rw-section-title">عکس‌های پذیرش خودرو</h2>';
    echo '<div class="m360-rw-panel">';
    m360_rw_intake_render_section_header('عکس‌های پذیرش', $secCam, $onlineRequestId, 'camera_photo', $canAct);
    echo '<p class="m360-rw-muted">حداقل ۶ عکس الزامی است. — <strong>' . m360_rw_h((string)$photoStatus['count']) . '/' . m360_rw_h((string)$photoStatus['min_required']) . '</strong></p>';
    if ($photoStatus['legacy_only']) {
        echo '<p class="m360-rw-warn">عکس قدیمی موجود است، اما چک‌لیست ۶ عکس هنوز کامل نیست.</p>';
    }
    if ($secCam['show_summary']) {
        echo '<div class="m360-rw-sec-summary m360-rw-photo-slot-grid">';
        foreach ($photoStatus['slots'] as $slotKey => $slot) {
            $st = !empty($slot['saved']) ? 'ثبت شده' : 'ثبت نشده';
            echo '<div class="m360-rw-photo-card is-done"><span class="m360-rw-photo-card__label">' . m360_rw_h((string)$slot['label']) . '</span>';
            echo '<span class="m360-rw-photo-card__status">' . m360_rw_h($st) . '</span></div>';
        }
        echo '</div>';
    }
    if ($canAct && $secCam['show_form']) {
        echo '<div class="m360-rw-camera-shared">';
        echo '<video id="m360_rw_camera_video" autoplay playsinline muted></video>';
        echo '<canvas id="m360_rw_camera_canvas" style="display:none;"></canvas>';
        echo '<div class="m360-rw-actions">';
        echo '<button type="button" id="m360_rw_camera_start" class="m360-rw-btn m360-rw-btn-secondary">فعال‌سازی دوربین</button>';
        echo '</div></div>';
        echo '<div class="m360-rw-photo-slot-grid">';
        foreach ($photoStatus['slots'] as $slotKey => $slot) {
            $st = !empty($slot['saved']) ? 'ثبت شده' : 'ثبت نشده';
            $cardClass = !empty($slot['saved']) ? 'is-done' : 'is-pending';
            echo '<div class="m360-rw-photo-card ' . $cardClass . '" data-slot="' . m360_rw_h($slotKey) . '">';
            echo '<span class="m360-rw-photo-card__label">' . m360_rw_h((string)$slot['label']) . '</span>';
            echo '<span class="m360-rw-photo-card__status">' . m360_rw_h($st) . '</span>';
            if (!empty($slot['saved'])) {
                echo '<img class="m360-rw-photo-card__thumb" src="storage/' . m360_rw_h($slot['file']) . '" alt="">';
            } else {
                echo '<img class="m360-rw-photo-card__thumb" id="m360_rw_preview_' . m360_rw_h($slotKey) . '" alt="" style="display:none;">';
            }
            echo '<form class="m360-rw-form m360-rw-photo-slot-form" method="post" action="' . m360_rw_h($saveUrl) . '">';
            echo $csrfInputHtml;
            echo '<input type="hidden" name="online_request_id" value="' . $onlineRequestId . '">';
            echo '<input type="hidden" name="action_type" value="save_camera_photo">';
            echo '<input type="hidden" name="photo_slot" value="' . m360_rw_h($slotKey) . '">';
            m360_rw_intake_return_section_hidden('camera_photo');
            echo '<input type="hidden" name="camera_image_base64" class="m360-rw-slot-base64" value="">';
            echo '<button type="button" class="m360-rw-btn m360-rw-btn-secondary m360-rw-slot-capture" data-slot="' . m360_rw_h($slotKey) . '">ثبت عکس</button>';
            echo '<button type="submit" class="m360-rw-btn m360-rw-slot-save" data-slot="' . m360_rw_h($slotKey) . '" disabled>ذخیره</button>';
            echo '</form></div>';
        }
        echo '</div>';
    }
    echo '</div></section>';
}

