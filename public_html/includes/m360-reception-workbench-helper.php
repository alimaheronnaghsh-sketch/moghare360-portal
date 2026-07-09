<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.9-C-2B — Reception workbench + intake completion shell helper.
 * Read-only aggregation; no schema changes; reuses P1/P2/P1.5 foundations.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-reception-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-customer-cartable-helper.php';

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
 * @return array{
 *   valid:bool,
 *   items:array<string,mixed>,
 *   raw_warning:string,
 *   payload_status:string,
 *   sql_len:int,
 *   read_len:int,
 *   recovery_source:string
 * }
 */
function m360_rw_decode_payload(?string $json, array $readMeta = []): array
{
    $sqlLen = (int)($readMeta['sql_len'] ?? 0);
    $readLen = (int)($readMeta['read_len'] ?? 0);
    $readOk = array_key_exists('ok', $readMeta) ? (bool)$readMeta['ok'] : true;
    $readError = trim((string)($readMeta['error'] ?? ''));
    $runtimeReadFailed = !$readOk
        || $readError === 'RUNTIME_PAYLOAD_READ_FAILED'
        || ($sqlLen > 0 && $readLen > 0 && $readLen < $sqlLen);
    $runtimeWarning = 'خواندن کامل اطلاعات درخواست با خطای فنی مواجه شد؛ داده اصلی در پایگاه داده حذف یا نامعتبر تشخیص داده نشده است.';

    if ($json === null || trim($json) === '') {
        if ($sqlLen > 0 && $runtimeReadFailed) {
            return [
                'valid' => false,
                'items' => [],
                'raw_warning' => $runtimeWarning,
                'payload_status' => 'RUNTIME_PAYLOAD_READ_FAILED',
                'sql_len' => $sqlLen,
                'read_len' => $readLen,
                'recovery_source' => '',
            ];
        }

        return [
            'valid' => true,
            'items' => [],
            'raw_warning' => '',
            'payload_status' => 'PAYLOAD_VALID',
            'sql_len' => $sqlLen,
            'read_len' => $readLen,
            'recovery_source' => '',
        ];
    }

    $decoded = json_decode($json, true);

    if ($runtimeReadFailed) {
        if (is_array($decoded)) {
            return [
                'valid' => false,
                'items' => $decoded,
                'raw_warning' => $runtimeWarning,
                'payload_status' => 'RUNTIME_PAYLOAD_READ_FAILED',
                'sql_len' => $sqlLen,
                'read_len' => $readLen,
                'recovery_source' => 'residual-valid',
            ];
        }

        return [
            'valid' => false,
            'items' => [],
            'raw_warning' => $runtimeWarning,
            'payload_status' => 'RUNTIME_PAYLOAD_READ_FAILED',
            'sql_len' => $sqlLen,
            'read_len' => $readLen,
            'recovery_source' => '',
        ];
    }

    if (!is_array($decoded)) {
        return [
            'valid' => false,
            'items' => [],
            'raw_warning' => 'ساختار JSON فرم آنلاین نامعتبر است. اطلاعات پایه از ستون‌های درخواست نمایش داده می‌شود.',
            'payload_status' => 'STORED_JSON_INVALID',
            'sql_len' => $sqlLen,
            'read_len' => $readLen,
            'recovery_source' => '',
        ];
    }

    return [
        'valid' => true,
        'items' => $decoded,
        'raw_warning' => '',
        'payload_status' => 'PAYLOAD_VALID',
        'sql_len' => $sqlLen,
        'read_len' => $readLen,
        'recovery_source' => '',
    ];
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
        'reception_intake' => 'از پیش‌نویس پذیرش خوانده شد',
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

    if ($left === '') {
        $d1 = trim((string)($pp['first_digit_1'] ?? ''));
        $d2 = trim((string)($pp['first_digit_2'] ?? ''));
        if ($d1 !== '' && $d2 !== '') {
            $left = $d1 . $d2;
        }
    }
    if ($mid === '') {
        $m1 = trim((string)($pp['middle_digit_1'] ?? ''));
        $m2 = trim((string)($pp['middle_digit_2'] ?? ''));
        $m3 = trim((string)($pp['middle_digit_3'] ?? ''));
        if ($m1 !== '' && $m2 !== '' && $m3 !== '') {
            $mid = $m1 . $m2 . $m3;
        }
    }
    if ($region === '') {
        $r1 = trim((string)($pp['region_digit_1'] ?? ''));
        $r2 = trim((string)($pp['region_digit_2'] ?? ''));
        if ($r1 !== '' && $r2 !== '') {
            $region = $r1 . $r2;
        }
    }

    if ($left !== '' && $letter !== '' && $mid !== '' && $region !== '') {
        return $left . $letter . $mid . '-' . $region;
    }

    return '';
}

/**
 * Display-only mojibake guard for Persian legacy strings. Never persists conversions.
 */
function m360_rw_display_text_is_mojibake(string $value): bool
{
    $value = trim($value);
    if ($value === '') {
        return false;
    }
    $markers = ['Ø', 'Ù', 'ط§', 'ط¨', 'ظ†', 'ظ…'];
    foreach ($markers as $marker) {
        if (str_contains($value, $marker)) {
            return true;
        }
    }

    return false;
}

function m360_rw_mojibake_field_message(string $fieldLabelFa): string
{
    $label = trim($fieldLabelFa);
    if ($label === '') {
        return 'داده قدیمی دارای اشکال کدگذاری است و باید توسط مدیر سیستم اصلاح شود.';
    }

    return 'فیلد «' . $label . '»: داده قدیمی دارای اشکال کدگذاری است و باید توسط مدیر سیستم اصلاح شود.';
}

/**
 * Prefer clean text; skip empty and obvious mojibake (display-only).
 *
 * @param list<array{value:string,source_key:string}> $candidates
 * @return array{value:string,source_key:string,mojibake:bool,warning:string}
 */
function m360_rw_pick_clean_display_text(array $candidates, string $fieldLabelFa, bool $guardMojibake = true): array
{
    $mojibakeOnly = '';
    $mojibakeSource = '';
    foreach ($candidates as $candidate) {
        $value = trim((string)($candidate['value'] ?? ''));
        if ($value === '') {
            continue;
        }
        $source = (string)($candidate['source_key'] ?? '');
        if ($guardMojibake && m360_rw_display_text_is_mojibake($value)) {
            if ($mojibakeOnly === '') {
                $mojibakeOnly = $value;
                $mojibakeSource = $source;
            }
            continue;
        }

        return [
            'value' => $value,
            'source_key' => $source,
            'mojibake' => false,
            'warning' => '',
        ];
    }

    if ($mojibakeOnly !== '') {
        return [
            'value' => '',
            'source_key' => $mojibakeSource,
            'mojibake' => true,
            'warning' => m360_rw_mojibake_field_message($fieldLabelFa),
        ];
    }

    return ['value' => '', 'source_key' => '', 'mojibake' => false, 'warning' => ''];
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

    $plateMeta = m360_rw_pick_meta($map, ['plate_display', 'vehicle_plate', 'plate', 'plate_number', 'license_plate', 'car_plate']);
    if ($plateMeta['value'] === '' || ($plateMeta['value'] !== '' && m360_rw_display_text_is_mojibake($plateMeta['value']))) {
        $fromParts = m360_rw_recover_plate_from_parts($payload);
        if ($fromParts !== '') {
            $plateMeta = ['value' => $fromParts, 'source_key' => 'payload'];
        }
    }
    if ($plateMeta['value'] === '' && $intake !== null) {
        $plateMeta = m360_rw_pick_meta(['erp_intake' => $intake], ['license_plate']);
    }
    if ($plateMeta['value'] !== '' && m360_rw_display_text_is_mojibake($plateMeta['value'])) {
        $plateMeta = ['value' => '', 'source_key' => $plateMeta['source_key']];
    }

    $vinMeta = m360_rw_pick_meta($map, ['vin', 'vehicle_vin', 'chassis_vin']);
    $brandMeta = m360_rw_pick_meta($map, ['brand', 'vehicle_brand']);
    $modelMeta = m360_rw_pick_meta($map, ['model', 'vehicle_model']);
    if ($brandMeta['value'] !== '' && m360_rw_display_text_is_mojibake($brandMeta['value'])) {
        $brandMeta = ['value' => '', 'source_key' => $brandMeta['source_key']];
    }
    if ($modelMeta['value'] !== '' && m360_rw_display_text_is_mojibake($modelMeta['value'])) {
        $modelMeta = ['value' => '', 'source_key' => $modelMeta['source_key']];
    }
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
        'plate' => ($plateMeta['value'] === '' && $vehicle !== null && trim((string)($vehicle['plate_number'] ?? '')) !== '' && m360_rw_display_text_is_mojibake((string)$vehicle['plate_number']))
            ? array_merge(m360_rw_field_result('', 'erp_vehicle', m360_rw_mojibake_field_message('پلاک')), ['warning' => m360_rw_mojibake_field_message('پلاک'), 'mojibake' => true])
            : m360_rw_field_result(
                $plateMeta['value'] !== '' ? $plateMeta['value'] : (
                    ($vehicle !== null && trim((string)($vehicle['plate_number'] ?? '')) !== '' && !m360_rw_display_text_is_mojibake((string)$vehicle['plate_number']))
                        ? trim((string)$vehicle['plate_number'])
                        : ''
                ),
                $plateMeta['value'] !== '' ? $plateMeta['source_key'] : (($vehicle !== null && trim((string)($vehicle['plate_number'] ?? '')) !== '') ? 'erp_vehicle' : ''),
                'پلاک ثبت نشده است'
            ),
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
        'periodic' => [
            'label' => 'سرویس‌های دوره‌ای',
            'subs' => [
                'oil_filter' => 'سرویس روغن و فیلتر',
                'mileage_service' => 'سرویس کیلومتری',
                'periodic_inspection' => 'بازدید دوره‌ای',
            ],
        ],
        'trade' => [
            'label' => 'کارشناسی خرید و فروش',
            'subs' => [
                'technical_inspection' => 'کارشناسی فنی',
                'body_inspection' => 'کارشناسی بدنه',
                'full_inspection' => 'کارشناسی کامل',
            ],
        ],
    ];
}

/** @return array<string, string> */
function m360_rw_customer_request_type_labels(): array
{
    return [
        'diagnostic_inspection' => 'کارشناسی و عیب‌یابی',
        'buy_sell_inspection' => 'کارشناسی خرید/فروش',
        'periodic_service' => 'سرویس‌های دوره‌ای',
        'option_add' => 'افزودن آپشن',
        'other' => 'سایر',
    ];
}

function m360_rw_request_service_policy_group(string $requestType): string
{
    $requestType = strtolower(trim($requestType));

    return match ($requestType) {
        'buy_sell_inspection' => 'DEFINED_INSPECTION',
        'periodic_service' => 'PERIODIC_SERVICE',
        'diagnostic_inspection' => 'TECHNICAL_DIAGNOSIS',
        'option_add' => 'OTHER_DEFINED_SERVICE',
        'other' => 'UNKNOWN_FAULT',
        default => (str_contains($requestType, 'diagn') || str_contains($requestType, 'troubleshoot') || str_contains($requestType, 'fault'))
            ? 'TECHNICAL_DIAGNOSIS'
            : (str_contains($requestType, 'inspect') ? 'DEFINED_INSPECTION' : 'OTHER_DEFINED_SERVICE'),
    };
}

function m360_rw_request_service_policy_requires_diagnosis(string $policyGroup): bool
{
    return $policyGroup === 'TECHNICAL_DIAGNOSIS';
}

/**
 * @param array<string, string> $post
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $request
 */
function m360_rw_intake_resolve_customer_request_type(array $post, array $payload, array $request): string
{
    $fromPost = trim((string)($post['customer_request_type'] ?? ''));
    if ($fromPost !== '') {
        return $fromPost;
    }

    return trim((string)m360_rw_pick([$request, $payload], 'request_type'));
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $requestRow
 * @param array<string, mixed>|null $erpVehicle
 * @return array{
 *   vehicle_type:array{value:string,source_key:string,source_label:string,missing_label:string,present:bool,partial:bool,detail:string},
 *   vehicle_class:array{value:string,source_key:string,source_label:string,missing_label:string,present:bool,partial:bool,detail:string},
 *   vin:array{value:string,source_key:string,source_label:string,missing_label:string,present:bool,partial:bool,detail:string},
 *   chassis_number:array{value:string,source_key:string,source_label:string,missing_label:string,present:bool,partial:bool,detail:string}
 * }
 */
/**
 * Canonical display/prefill vehicle field with source precedence and mojibake guard.
 *
 * @return array{value:string,source_key:string,source_label:string,missing_label:string,present:bool,partial:bool,detail:string,warning:string,mojibake:bool}
 */
function m360_rw_intake_resolve_vehicle_field_display(
    array $payload,
    array $requestRow,
    ?array $erpVehicle,
    array $keys,
    string $fieldLabelFa,
    string $missingFa = 'در درخواست اولیه ذخیره نشده است',
    bool $guardMojibake = true
): array {
    $payload = m360_rw_intake_payload_for_recovery($payload);
    $draft = is_array($payload['reception_intake']['vehicle'] ?? null)
        ? $payload['reception_intake']['vehicle']
        : [];

    $candidates = [];
    foreach ($keys as $key) {
        $candidates[] = ['value' => trim((string)($draft[$key] ?? '')), 'source_key' => 'reception_intake'];
    }
    foreach ($keys as $key) {
        $candidates[] = ['value' => trim((string)($payload[$key] ?? '')), 'source_key' => 'payload'];
    }
    foreach ($keys as $key) {
        $candidates[] = ['value' => trim((string)($requestRow[$key] ?? '')), 'source_key' => 'online_request'];
    }
    if (is_array($erpVehicle)) {
        $erpKeyMap = [
            'plate' => ['plate_number', 'plate', 'vehicle_plate'],
            'vehicle_plate' => ['plate_number', 'plate', 'vehicle_plate'],
            'plate_display' => ['plate_number', 'plate', 'vehicle_plate'],
            'plate_number' => ['plate_number'],
            'vin' => ['vin', 'vehicle_vin'],
            'vehicle_vin' => ['vin', 'vehicle_vin'],
            'brand' => ['brand', 'vehicle_brand'],
            'vehicle_brand' => ['brand', 'vehicle_brand'],
            'model' => ['model', 'vehicle_model'],
            'vehicle_model' => ['model', 'vehicle_model'],
            'vehicle_class' => ['model', 'vehicle_model', 'vehicle_class'],
            'mileage' => ['mileage', 'odometer_km', 'odometer'],
            'odometer_km' => ['mileage', 'odometer_km', 'odometer'],
            'production_year' => ['model_year', 'production_year', 'year'],
            'year' => ['model_year', 'production_year', 'year'],
            'color' => ['color'],
            'fuel_type' => ['fuel_type'],
            'fuel_level' => ['fuel_level'],
            'transmission_type' => ['transmission_type', 'transmission'],
            'engine_number' => ['engine_number', 'engine_no'],
            'chassis_number' => ['chassis_number', 'chassis_no'],
        ];
        foreach ($keys as $key) {
            $erpKeys = $erpKeyMap[$key] ?? [$key];
            foreach ($erpKeys as $ek) {
                $candidates[] = [
                    'value' => trim((string)($erpVehicle[$ek] ?? '')),
                    'source_key' => 'erp_vehicle',
                ];
            }
        }
    }

    $picked = m360_rw_pick_clean_display_text($candidates, $fieldLabelFa, $guardMojibake);
    $result = m360_rw_field_result($picked['value'], $picked['source_key'], $missingFa);
    $result['warning'] = (string)($picked['warning'] ?? '');
    $result['mojibake'] = !empty($picked['mojibake']);
    if ($result['value'] === '' && $result['warning'] !== '') {
        $result['missing_label'] = $result['warning'];
    }

    return $result;
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $requestRow
 * @param array<string, mixed>|null $erpVehicle
 * @return array<string, array{value:string,source_key:string,source_label:string,missing_label:string,present:bool,partial:bool,detail:string,warning?:string,mojibake?:bool}>
 */
function m360_rw_intake_resolve_vehicle_dossier_fields(array $payload, array $requestRow = [], ?array $erpVehicle = null): array
{
    $missingFa = 'در درخواست اولیه ذخیره نشده است';

    $plate = m360_rw_intake_resolve_vehicle_field_display(
        $payload,
        $requestRow,
        $erpVehicle,
        ['plate_display', 'plate', 'vehicle_plate', 'plate_number'],
        'پلاک',
        'پلاک ثبت نشده است',
        true
    );
    if ($plate['value'] === '') {
        $fromParts = m360_rw_recover_plate_from_parts(m360_rw_intake_payload_for_recovery($payload));
        if ($fromParts !== '') {
            $plate = m360_rw_field_result($fromParts, 'payload', 'پلاک ثبت نشده است');
            $plate['warning'] = '';
            $plate['mojibake'] = false;
        } else {
            $plate = m360_rw_intake_resolve_vehicle_field_display(
                $payload,
                $requestRow,
                $erpVehicle,
                ['plate_number'],
                'پلاک',
                'پلاک ثبت نشده است',
                true
            );
        }
    }

    $vin = m360_rw_intake_resolve_vehicle_field_display(
        $payload,
        $requestRow,
        $erpVehicle,
        ['vin', 'vehicle_vin', 'chassis_vin'],
        'VIN',
        $missingFa,
        false
    );
    $chassis = m360_rw_intake_resolve_vehicle_field_display(
        $payload,
        $requestRow,
        $erpVehicle,
        ['chassis_number', 'chassis_no'],
        'شماره شاسی',
        $missingFa,
        false
    );
    if ($chassis['value'] === '') {
        $chassisOnly = m360_rw_intake_resolve_vehicle_field_display(
            $payload,
            $requestRow,
            $erpVehicle,
            ['chassis'],
            'شماره شاسی',
            $missingFa,
            false
        );
        if ($chassisOnly['value'] !== '' && strcasecmp($chassisOnly['value'], $vin['value']) !== 0) {
            $chassis = $chassisOnly;
        }
    }

    return [
        'plate' => $plate,
        'vin' => $vin,
        'chassis_number' => $chassis,
        'engine_number' => m360_rw_intake_resolve_vehicle_field_display(
            $payload, $requestRow, $erpVehicle, ['engine_number', 'engine_no'], 'شماره موتور', $missingFa, false
        ),
        'brand' => m360_rw_intake_resolve_vehicle_field_display(
            $payload, $requestRow, $erpVehicle, ['brand', 'vehicle_brand'], 'برند', $missingFa, true
        ),
        'model' => m360_rw_intake_resolve_vehicle_field_display(
            $payload, $requestRow, $erpVehicle, ['model', 'vehicle_model'], 'مدل', $missingFa, true
        ),
        'vehicle_class' => m360_rw_intake_resolve_vehicle_field_display(
            $payload, $requestRow, $erpVehicle, ['vehicle_class', 'class_code', 'class_name', 'vehicle_category', 'model_class', 'model', 'vehicle_model'], 'کلاس خودرو', $missingFa, true
        ),
        'vehicle_type' => m360_rw_intake_resolve_vehicle_field_display(
            $payload, $requestRow, $erpVehicle, ['vehicle_type', 'body_type', 'vehicle_body_type', 'car_type'], 'نوع خودرو', $missingFa, true
        ),
        'production_year' => m360_rw_intake_resolve_vehicle_field_display(
            $payload, $requestRow, $erpVehicle, ['production_year', 'year', 'model_year', 'vehicle_year_pair'], 'سال ساخت', $missingFa, false
        ),
        'color' => m360_rw_intake_resolve_vehicle_field_display(
            $payload, $requestRow, $erpVehicle, ['color', 'vehicle_color'], 'رنگ', $missingFa, true
        ),
        'mileage' => m360_rw_intake_resolve_vehicle_field_display(
            $payload, $requestRow, $erpVehicle, ['mileage', 'odometer_km', 'odometer', 'kilometer', 'km'], 'کیلومتر', $missingFa, false
        ),
        'fuel_type' => m360_rw_intake_resolve_vehicle_field_display(
            $payload, $requestRow, $erpVehicle, ['fuel_type'], 'نوع سوخت', $missingFa, true
        ),
        'fuel_level' => m360_rw_intake_resolve_vehicle_field_display(
            $payload, $requestRow, $erpVehicle, ['fuel_level', 'intake_fuel_level', 'fuel'], 'سطح سوخت', $missingFa, true
        ),
        'transmission_type' => m360_rw_intake_resolve_vehicle_field_display(
            $payload, $requestRow, $erpVehicle, ['transmission_type', 'transmission'], 'گیربکس', $missingFa, true
        ),
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{route:string,diagnostic_subcategories:list<string>,service_path_clear:string,service_path_note:string}
 */
function m360_rw_intake_service_classification_canonical(array $payload): array
{
    $payload = m360_rw_intake_payload_for_recovery($payload);
    $service = is_array($payload['reception_intake']['service_classification'] ?? null)
        ? $payload['reception_intake']['service_classification']
        : [];

    $route = trim((string)($service['route'] ?? $service['main'] ?? ''));
    if ($route === '') {
        $route = trim((string)m360_rw_pick([$payload], 'reception_service_primary', 'service_classification_primary'));
    }

    $diagSubs = [];
    $diagRaw = $service['diagnostic_subcategories'] ?? $service['diagnostic_categories'] ?? null;
    if (!is_array($diagRaw) || $diagRaw === []) {
        $diagRaw = $payload['reception_service_diag_sub'] ?? [];
    }
    if (is_array($diagRaw)) {
        foreach ($diagRaw as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $diagSubs[] = $item;
            }
        }
    }

    $pathClear = '';
    if (array_key_exists('service_path_clear', $service)) {
        $nested = $service['service_path_clear'];
        if (is_bool($nested)) {
            $pathClear = $nested ? '1' : '0';
        } else {
            $pathClear = in_array(strtolower(trim((string)$nested)), ['1', 'yes', 'true'], true) ? '1' : '0';
        }
    }
    if ($pathClear === '') {
        $pathClear = m360_rw_pick([$payload], 'fault_service_path_clear', 'service_path_clear');
    }

    return [
        'route' => $route,
        'diagnostic_subcategories' => $diagSubs,
        'service_path_clear' => $pathClear,
        'service_path_note' => trim((string)($service['service_path_note'] ?? m360_rw_pick([$payload], 'service_path_note'))),
    ];
}

/**
 * @param array<string, string> $formValues
 */
function m360_rw_intake_service_wizard_step_complete(array $formValues, string $policyGroup = ''): bool
{
    if ($policyGroup === '') {
        $policyGroup = m360_rw_request_service_policy_group(
            trim((string)($formValues['customer_request_type'] ?? ''))
        );
    }

    $route = trim((string)($formValues['service_route'] ?? $formValues['service_primary'] ?? ''));
    $path = (string)($formValues['service_path_clear'] ?? '');

    if ($policyGroup === 'TECHNICAL_DIAGNOSIS') {
        if ($route === '') {
            return false;
        }
        if ($path !== '1') {
            return false;
        }
        if ($route === 'diag') {
            $subs = $formValues['service_diag_sub_codes'] ?? [];
            if (!is_array($subs) || $subs === []) {
                return false;
            }
        }

        return true;
    }

    if ($policyGroup === 'UNKNOWN_FAULT') {
        if ($path === '1' && $route !== '') {
            return true;
        }
        $tempStatus = trim((string)($formValues['temporary_status'] ?? ''));
        if ($tempStatus === 'reception_temporary_diagnosis') {
            return true;
        }

        return trim((string)($formValues['service_path_note'] ?? '')) !== '' && $path === '0';
    }

    if (in_array($policyGroup, ['DEFINED_INSPECTION', 'PERIODIC_SERVICE', 'OTHER_DEFINED_SERVICE'], true)) {
        if ($route === '') {
            return false;
        }

        return $path === '1';
    }

    return false;
}

/**
 * @param array<string, string> $post
 * @return array{ok:bool,error:string,route:string,diag_subs:list<string>,path_clear:string,path_note:string,inspection_subs:list<string>,policy_group:string}
 */
function m360_rw_intake_validate_service_classification_post(array $post, string $policyGroup = ''): array
{
    if ($policyGroup === '') {
        $policyGroup = m360_rw_request_service_policy_group(
            trim((string)($post['customer_request_type'] ?? ''))
        );
    }

    $fail = static function (
        string $error,
        string $route = '',
        array $diagSubs = [],
        string $pathClear = '',
        string $pathNote = '',
        array $inspectionSubs = []
    ) use ($policyGroup): array {
        return [
            'ok' => false,
            'error' => $error,
            'route' => $route,
            'diag_subs' => $diagSubs,
            'path_clear' => $pathClear,
            'path_note' => $pathNote,
            'inspection_subs' => $inspectionSubs,
            'policy_group' => $policyGroup,
        ];
    };

    $pathNoteNorm = m360_rw_intake_post_scalar($post, 'service_path_note');
    if (!$pathNoteNorm['ok']) {
        return $fail($pathNoteNorm['error']);
    }
    $pathNote = $pathNoteNorm['value'];
    $vt = m360_rw_intake_validate_text($pathNote, 2000, 'یادداشت پذیرش');
    if (!$vt['ok']) {
        return $fail($vt['error']);
    }

    if ($policyGroup === 'DEFINED_INSPECTION') {
        $confirm = trim((string)($post['reception_inspection_confirm'] ?? $post['service_reception_confirmed'] ?? ''));
        if (!in_array($confirm, ['1', 'yes', 'on'], true)) {
            return $fail('تأیید درخواست کارشناسی الزامی است.');
        }
        $inspectionSubs = [];
        $rawInspection = $post['inspection_scope'] ?? $post['trade_inspection_scope'] ?? [];
        if (!is_array($rawInspection)) {
            $rawInspection = $rawInspection !== '' ? [(string)$rawInspection] : [];
        }
        $allowedInspection = array_keys(m360_rw_service_classification_taxonomy()['trade']['subs']);
        foreach ($rawInspection as $sub) {
            $sub = trim((string)$sub);
            if ($sub !== '' && in_array($sub, $allowedInspection, true)) {
                $inspectionSubs[] = $sub;
            }
        }

        return [
            'ok' => true,
            'error' => '',
            'route' => 'trade',
            'diag_subs' => [],
            'path_clear' => '1',
            'path_note' => $pathNote,
            'inspection_subs' => $inspectionSubs,
            'policy_group' => $policyGroup,
        ];
    }

    if ($policyGroup === 'PERIODIC_SERVICE') {
        $confirm = trim((string)($post['service_reception_confirmed'] ?? ''));
        if (!in_array($confirm, ['1', 'yes', 'on'], true)) {
            return $fail('تأیید درخواست سرویس دوره‌ای الزامی است.');
        }

        return [
            'ok' => true,
            'error' => '',
            'route' => 'periodic',
            'diag_subs' => [],
            'path_clear' => '1',
            'path_note' => $pathNote,
            'inspection_subs' => [],
            'policy_group' => $policyGroup,
        ];
    }

    if ($policyGroup === 'OTHER_DEFINED_SERVICE') {
        $confirm = trim((string)($post['service_reception_confirmed'] ?? ''));
        if (!in_array($confirm, ['1', 'yes', 'on'], true)) {
            return $fail('تأیید درخواست خدمت الزامی است.');
        }
        $route = trim((string)($post['service_route'] ?? ''));
        if ($route === '') {
            $route = 'diag';
        }

        return [
            'ok' => true,
            'error' => '',
            'route' => $route,
            'diag_subs' => $route === 'diag' ? ['options'] : [],
            'path_clear' => '1',
            'path_note' => $pathNote,
            'inspection_subs' => [],
            'policy_group' => $policyGroup,
        ];
    }

    if ($policyGroup === 'UNKNOWN_FAULT') {
        $pathClearRaw = trim((string)($post['service_path_clear'] ?? '0'));
        if (!in_array($pathClearRaw, ['0', '1'], true)) {
            $pathClearRaw = '0';
        }
        $symptomsNorm = m360_rw_intake_post_scalar($post, 'initial_symptoms');
        if (!$symptomsNorm['ok']) {
            return $fail($symptomsNorm['error']);
        }
        $symptoms = $symptomsNorm['value'];
        $symVt = m360_rw_intake_validate_text($symptoms, 2000, 'علائم اولیه');
        if (!$symVt['ok']) {
            return $fail($symVt['error']);
        }
        if ($symptoms === '' && $pathClearRaw === '0') {
            return $fail('علائم اولیه یا یادداشت پذیرش برای پذیرش موقت الزامی است.');
        }
        $route = trim((string)($post['service_route'] ?? ''));
        if ($route === '') {
            $route = 'diag';
        }

        return [
            'ok' => true,
            'error' => '',
            'route' => $route,
            'diag_subs' => [],
            'path_clear' => $pathClearRaw,
            'path_note' => $pathNote !== '' ? $pathNote : $symptoms,
            'inspection_subs' => [],
            'policy_group' => $policyGroup,
        ];
    }

    $route = trim((string)($post['service_route'] ?? $post['service_primary'] ?? ''));
    $allowedPrimary = array_keys(m360_rw_service_classification_taxonomy());
    if ($route === '' || !in_array($route, $allowedPrimary, true)) {
        return $fail('مسیر اصلی خدمات الزامی است.');
    }

    $diagSubs = [];
    if ($route === 'diag') {
        $rawSubs = $post['diagnostic_subcategories'] ?? $post['service_diag_sub'] ?? [];
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
            return $fail('حداقل یک زیردسته عیب‌یابی انتخاب کنید.', $route);
        }
    }

    $pathClearRaw = trim((string)($post['service_path_clear'] ?? ''));
    if (!in_array($pathClearRaw, ['0', '1'], true)) {
        return $fail('وضعیت روشن بودن مسیر عیب/خدمت را مشخص کنید.', $route, $diagSubs);
    }

    return [
        'ok' => true,
        'error' => '',
        'route' => $route,
        'diag_subs' => $diagSubs,
        'path_clear' => $pathClearRaw,
        'path_note' => $pathNote,
        'inspection_subs' => [],
        'policy_group' => $policyGroup,
    ];
}

/**
 * @param array<string, string> $formValues
 * @param list<string> $diagSubCodes
 * @param array{registered:bool,fault_path_clear:bool,primary:string,diag_sub:list<string>,periodic:string,trade:string,customer_request_type:string,customer_mismatch:bool,taxonomy:array<string,array{label:string,subs:array<string,string>}>,write_placeholder:string,selected_labels:list<string>} $serviceClass
 * @param array<string, mixed> $payload
 */
function m360_rw_intake_render_service_wizard_block(
    int $onlineRequestId,
    array $formValues,
    array $diagSubCodes,
    array $serviceClass,
    bool $canShowStepForm,
    bool $canShowTempActions,
    string $csrfInputHtml,
    string $saveUrl,
    string $customerRequestType = '',
    array $payload = []
): void {
    $requestType = trim($customerRequestType);
    if ($requestType === '') {
        $requestType = trim((string)($serviceClass['customer_request_type'] ?? ''));
    }
    $policyGroup = m360_rw_request_service_policy_group($requestType);
    $requestLabels = m360_rw_customer_request_type_labels();
    $requestLabel = $requestLabels[$requestType] ?? $requestType;
    $route = trim((string)($formValues['service_route'] ?? $formValues['service_primary'] ?? ''));
    $pathClear = (string)($formValues['service_path_clear'] ?? '');
    $description = trim((string)m360_rw_pick([$payload], 'request_description', 'service_description', 'service_note'));

    if ($pathClear === '0' && $policyGroup === 'TECHNICAL_DIAGNOSIS') {
        echo '<p class="m360-rw-warn">مسیر انتخابی قطع است؛ پرونده در پذیرش موقت باقی می‌ماند تا مسیر عیب/خدمت روشن شود.</p>';
    }
    if ($pathClear === '0' && $policyGroup === 'UNKNOWN_FAULT') {
        echo '<p class="m360-rw-warn">پذیرش موقت — عیب‌یابی: پرونده تا روشن شدن مسیر در این وضعیت باقی می‌ماند.</p>';
    }

    if (!$canShowStepForm) {
        echo '<div class="m360-rw-field-grid">';
        if ($requestLabel !== '') {
            echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">نوع درخواست مشتری</span><span class="m360-rw-field-val">' . m360_rw_h($requestLabel) . '</span></div>';
        }
        if ($route !== '') {
            $label = $serviceClass['taxonomy'][$route]['label'] ?? $route;
            echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">مسیر خدمت</span><span class="m360-rw-field-val">' . m360_rw_h($label) . '</span></div>';
            if ($route === 'diag' && $diagSubCodes !== []) {
                $subLabels = [];
                foreach ($diagSubCodes as $code) {
                    $subLabels[] = $serviceClass['taxonomy']['diag']['subs'][$code] ?? $code;
                }
                echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">زیردسته‌ها</span><span class="m360-rw-field-val">' . m360_rw_h(implode('، ', $subLabels)) . '</span></div>';
            }
        }
        echo '</div>';

        return;
    }

    echo '<form class="m360-rw-form m360-rw-service-form" method="post" action="' . m360_rw_h($saveUrl) . '">';
    echo $csrfInputHtml;
    echo '<input type="hidden" name="online_request_id" value="' . $onlineRequestId . '">';
    echo '<input type="hidden" name="action_type" value="save_service_classification">';
    echo '<input type="hidden" name="customer_request_type" value="' . m360_rw_h($requestType) . '">';
    m360_rw_intake_return_step_hidden('service');

    if ($policyGroup === 'DEFINED_INSPECTION') {
        echo '<div class="m360-rw-field-grid">';
        echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">نوع درخواست</span><span class="m360-rw-field-val">' . m360_rw_h($requestLabel) . '</span></div>';
        if ($description !== '') {
            echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">شرح درخواست مشتری</span><span class="m360-rw-field-val">' . m360_rw_h($description) . '</span></div>';
        }
        echo '</div>';

        $savedInspection = [];
        $sc = is_array($payload['reception_intake']['service_classification'] ?? null)
            ? $payload['reception_intake']['service_classification']
            : [];
        $rawInspection = $sc['inspection_scope'] ?? $sc['trade_inspection_scope'] ?? [];
        if (is_array($rawInspection)) {
            $savedInspection = $rawInspection;
        }
        echo '<div class="m360-rw-form-field"><span class="m360-rw-form-label">حوزه کارشناسی (اختیاری)</span>';
        echo '<div class="m360-rw-checkbox-grid">';
        foreach ($serviceClass['taxonomy']['trade']['subs'] as $subCode => $subLabel) {
            $checked = in_array($subCode, $savedInspection, true) ? ' checked' : '';
            echo '<label class="m360-rw-check-label"><input type="checkbox" name="inspection_scope[]" value="' . m360_rw_h($subCode) . '"' . $checked . '> ' . m360_rw_h($subLabel) . '</label>';
        }
        echo '</div></div>';

        echo '<div class="m360-rw-form-field"><label class="m360-rw-form-label" for="service_path_note">یادداشت پذیرش</label>';
        echo '<textarea class="m360-rw-form-input m360-rw-form-textarea" id="service_path_note" name="service_path_note" rows="3">' . m360_rw_h($formValues['service_path_note'] ?? '') . '</textarea></div>';

        echo '<label class="m360-rw-check-label m360-rw-confirm-check"><input type="checkbox" name="reception_inspection_confirm" value="1" required> تأیید درخواست کارشناسی</label>';
        echo '<button type="submit" class="m360-rw-btn">ذخیره و ادامه</button>';
        echo '</form>';

        return;
    }

    if ($policyGroup === 'PERIODIC_SERVICE') {
        $mileage = trim((string)m360_rw_pick([$payload], 'odometer_km', 'mileage'));
        echo '<div class="m360-rw-field-grid">';
        echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">نوع درخواست</span><span class="m360-rw-field-val">' . m360_rw_h($requestLabel) . '</span></div>';
        if ($description !== '') {
            echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">خدمت درخواستی</span><span class="m360-rw-field-val">' . m360_rw_h($description) . '</span></div>';
        }
        if ($mileage !== '') {
            echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">کیلومتر فعلی</span><span class="m360-rw-field-val">' . m360_rw_h($mileage) . '</span></div>';
        }
        echo '</div>';

        echo '<div class="m360-rw-form-field"><label class="m360-rw-form-label" for="service_path_note">یادداشت پذیرش / اقلام سرویس</label>';
        echo '<textarea class="m360-rw-form-input m360-rw-form-textarea" id="service_path_note" name="service_path_note" rows="3">' . m360_rw_h($formValues['service_path_note'] ?? '') . '</textarea></div>';
        echo '<label class="m360-rw-check-label m360-rw-confirm-check"><input type="checkbox" name="service_reception_confirmed" value="1" required> تأیید درخواست سرویس دوره‌ای</label>';
        echo '<button type="submit" class="m360-rw-btn">ذخیره و ادامه</button>';
        echo '</form>';

        return;
    }

    if ($policyGroup === 'OTHER_DEFINED_SERVICE') {
        echo '<div class="m360-rw-field-grid">';
        echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">نوع درخواست</span><span class="m360-rw-field-val">' . m360_rw_h($requestLabel) . '</span></div>';
        if ($description !== '') {
            echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">خلاصه درخواست</span><span class="m360-rw-field-val">' . m360_rw_h($description) . '</span></div>';
        }
        echo '</div>';
        echo '<div class="m360-rw-form-field"><label class="m360-rw-form-label" for="service_path_note">یادداشت پذیرش</label>';
        echo '<textarea class="m360-rw-form-input m360-rw-form-textarea" id="service_path_note" name="service_path_note" rows="3">' . m360_rw_h($formValues['service_path_note'] ?? '') . '</textarea></div>';
        echo '<label class="m360-rw-check-label m360-rw-confirm-check"><input type="checkbox" name="service_reception_confirmed" value="1" required> تأیید درخواست خدمت</label>';
        echo '<button type="submit" class="m360-rw-btn">ذخیره و ادامه</button>';
        echo '</form>';

        return;
    }

    if ($policyGroup === 'UNKNOWN_FAULT') {
        echo '<div class="m360-rw-field-grid">';
        echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">نوع درخواست</span><span class="m360-rw-field-val">' . m360_rw_h($requestLabel) . '</span></div>';
        if ($description !== '') {
            echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">شرح اولیه مشتری</span><span class="m360-rw-field-val">' . m360_rw_h($description) . '</span></div>';
        }
        echo '</div>';
        echo '<div class="m360-rw-form-field"><label class="m360-rw-form-label" for="initial_symptoms">علائم / شرح اولیه پذیرش</label>';
        echo '<textarea class="m360-rw-form-input m360-rw-form-textarea" id="initial_symptoms" name="initial_symptoms" rows="3">' . m360_rw_h($formValues['initial_symptoms'] ?? $description) . '</textarea></div>';
        echo '<div class="m360-rw-form-field"><label class="m360-rw-form-label" for="service_path_note">یادداشت پذیرش</label>';
        echo '<textarea class="m360-rw-form-input m360-rw-form-textarea" id="service_path_note" name="service_path_note" rows="3">' . m360_rw_h($formValues['service_path_note'] ?? '') . '</textarea></div>';
        echo '<input type="hidden" name="service_path_clear" value="0">';
        echo '<input type="hidden" name="temporary_status" value="reception_temporary_diagnosis">';
        echo '<button type="submit" class="m360-rw-btn">ذخیره پذیرش موقت</button>';
        echo '</form>';

        return;
    }

    $primaryOpts = [];
    foreach ($serviceClass['taxonomy'] as $code => $group) {
        $primaryOpts[$code] = $group['label'];
    }
    echo '<div class="m360-rw-form-field"><label class="m360-rw-form-label" for="service_route">مسیر اصلی خدمات <span class="m360-rw-req">*</span></label>';
    echo '<select class="m360-rw-form-input" id="service_route" name="service_route" required>';
    echo '<option value="">— انتخاب —</option>';
    foreach ($primaryOpts as $optVal => $optLabel) {
        $sel = ((string)$optVal === $route) ? ' selected' : '';
        echo '<option value="' . m360_rw_h((string)$optVal) . '"' . $sel . '>' . m360_rw_h((string)$optLabel) . '</option>';
    }
    echo '</select></div>';

    $showDiagSubs = ($route === 'diag' || $route === '');
    echo '<div class="m360-rw-form-field m360-rw-service-diag-subs" data-service-diag-panel' . ($showDiagSubs ? '' : ' hidden') . '>';
    echo '<span class="m360-rw-form-label">زیردسته‌های عیب‌یابی <span class="m360-rw-req">*</span></span>';
    echo '<div class="m360-rw-checkbox-grid">';
    foreach ($serviceClass['taxonomy']['diag']['subs'] as $subCode => $subLabel) {
        $checked = in_array($subCode, $diagSubCodes, true) ? ' checked' : '';
        echo '<label class="m360-rw-check-label"><input type="checkbox" name="diagnostic_subcategories[]" value="' . m360_rw_h($subCode) . '"' . $checked . '> ' . m360_rw_h($subLabel) . '</label>';
    }
    echo '</div></div>';

    echo '<div class="m360-rw-form-field"><label class="m360-rw-form-label" for="service_path_clear">آیا مسیر عیب/خدمت برای ادامه پذیرش روشن است؟ <span class="m360-rw-req">*</span></label>';
    echo '<select class="m360-rw-form-input" id="service_path_clear" name="service_path_clear" required>';
    echo '<option value="">— انتخاب —</option>';
    echo '<option value="1"' . ($pathClear === '1' ? ' selected' : '') . '>بله، مسیر روشن است</option>';
    echo '<option value="0"' . ($pathClear === '0' ? ' selected' : '') . '>خیر، پذیرش موقت بماند</option>';
    echo '</select></div>';

    echo '<div class="m360-rw-form-field"><label class="m360-rw-form-label" for="service_path_note">یادداشت مسیر خدمت</label>';
    echo '<textarea class="m360-rw-form-input m360-rw-form-textarea" id="service_path_note" name="service_path_note" rows="3">' . m360_rw_h($formValues['service_path_note'] ?? '') . '</textarea></div>';

    echo '<button type="submit" class="m360-rw-btn">ذخیره و ادامه</button>';
    echo '</form>';

    if ($canShowTempActions && $policyGroup === 'TECHNICAL_DIAGNOSIS') {
        echo '<form class="m360-rw-form" method="post" action="' . m360_rw_h($saveUrl) . '">';
        echo $csrfInputHtml;
        echo '<input type="hidden" name="online_request_id" value="' . $onlineRequestId . '">';
        echo '<input type="hidden" name="action_type" value="save_temporary_reception">';
        m360_rw_intake_return_step_hidden('service');
        echo '<div class="m360-rw-form-field"><label class="m360-rw-form-label" for="temporary_status">وضعیت موقت</label>';
        echo '<select class="m360-rw-form-input" id="temporary_status" name="temporary_status">';
        echo '<option value="">— انتخاب —</option>';
        foreach (m360_rw_intake_temp_statuses() as $optVal => $optLabel) {
            $sel = ((string)$optVal === ($formValues['temporary_status'] ?? '')) ? ' selected' : '';
            echo '<option value="' . m360_rw_h((string)$optVal) . '"' . $sel . '>' . m360_rw_h((string)$optLabel) . '</option>';
        }
        echo '</select></div>';
        echo '<button type="submit" class="m360-rw-btn m360-rw-btn-secondary">ذخیره وضعیت موقت</button>';
        echo '</form>';
    }
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
    $canonical = m360_rw_intake_service_classification_canonical($payload);
    $primary = $canonical['route'];
    $diagSubs = $canonical['diagnostic_subcategories'];
    $periodic = m360_rw_pick([$payload], 'reception_service_periodic', 'service_classification_periodic');
    $trade = m360_rw_pick([$payload], 'reception_service_trade', 'service_classification_trade');
    $registered = $primary !== '' && ($primary !== 'diag' || $diagSubs !== []);
    $pathRaw = $canonical['service_path_clear'];
    $faultPathClear = $pathRaw === '1';
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
    $sql = 'SELECT TOP 1 * FROM dbo.erp_vehicles WHERE vehicle_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false) {
        return null;
    }
    if (!@odbc_execute($stmt, [$vehicleId])) {
        if (is_resource($stmt)) {
            @odbc_free_result($stmt);
        }

        return null;
    }
    $row = odbc_fetch_array($stmt);
    if (is_resource($stmt)) {
        @odbc_free_result($stmt);
    }
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

    $payloadMeta = m360_rw_decode_payload($request['request_payload_json'] ?? null, [
        'ok' => !array_key_exists('_payload_read_ok', $request) || !empty($request['_payload_read_ok']),
        'sql_len' => (int)($request['_payload_sql_len'] ?? 0),
        'read_len' => (int)($request['_payload_read_len'] ?? 0),
        'error' => (string)($request['_payload_read_error'] ?? ''),
    ]);
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
        if (in_array((string)($c['id'] ?? ''), ['contract', 'final_confirm', 'cost', 'diag'], true)
            && !empty($c['c2c_only'])) {
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

// --- PR-02A: Calendar 1405 + vehicle standards + hall manager gate ---

const M360_RW_CALENDAR_1405_SOURCE_DOC = 'docs/source/calendar/iran_calendar_1405_source.xlsx';
const M360_RW_HALL_MANAGER_STATUS_READY_FA = 'آماده بررسی مسئول سالن';
const M360_RW_TOP_LEVEL_OTHER_BRAND = 'سایر';
const M360_RW_MODEL_LIST_GAP_VALUE = 'سایر';

/** @return list<string> */
function m360_rw_intake_approved_vehicle_brands(): array
{
    return ['بنز', 'ب ام و', 'پورشه', 'ولوو', 'فولکس واگن', M360_RW_TOP_LEVEL_OTHER_BRAND];
}

/** @return list<int> PR-02A diagnostic requests — not valid for product sign-off. */
function m360_rw_intake_diagnostic_request_ids(): array
{
    return [18, 20];
}

function m360_rw_intake_is_diagnostic_only_request(int $onlineRequestId): bool
{
    return in_array($onlineRequestId, m360_rw_intake_diagnostic_request_ids(), true);
}

/** @return list<string> Legacy test brands outside current MOGHARE360 scope — never add to approved list. */
function m360_rw_intake_legacy_unsupported_brands(): array
{
    return ['Toyota', 'تویوتا', 'Lexus', 'لکسوس', 'Hyundai', 'هیوندای', 'Kia', 'کیا'];
}

function m360_rw_intake_brand_is_legacy_unsupported(string $brand): bool
{
    $brand = trim($brand);
    if ($brand === '') {
        return false;
    }

    return in_array($brand, m360_rw_intake_legacy_unsupported_brands(), true);
}

/** @return array{blocked:bool,reason_fa:string} */
function m360_rw_intake_vehicle_signoff_status(array $payload, array $requestRow, int $onlineRequestId): array
{
    if (m360_rw_intake_is_diagnostic_only_request($onlineRequestId)) {
        return [
            'blocked' => true,
            'reason_fa' => 'درخواست #' . $onlineRequestId . ' فقط برای عیب‌یابی PR-02A است و برای امضای محصول معتبر نیست.',
        ];
    }
    $brand = trim(m360_rw_intake_vehicle_canonical($payload, $requestRow)['brand']);
    if ($brand !== '' && m360_rw_intake_brand_is_legacy_unsupported($brand)) {
        return [
            'blocked' => true,
            'reason_fa' => 'برند «' . $brand . '» خارج از محدوده فعلی پذیرش است (فقط بنز، ب ام و، پورشه، ولوو، فولکس واگن، سایر).',
        ];
    }
    if ($brand !== '' && !in_array($brand, m360_rw_intake_approved_vehicle_brands(), true)) {
        return [
            'blocked' => true,
            'reason_fa' => 'برند خودرو در فهرست تأییدشده فعلی نیست.',
        ];
    }

    return ['blocked' => false, 'reason_fa' => ''];
}

/** @return list<string> */
function m360_rw_intake_actions_requiring_vehicle_complete(): array
{
    return [
        'save_condition_notes',
        'save_service_classification',
        'save_temporary_reception',
        'save_camera_photo',
        'save_documents_and_cost',
        'save_diagnostic_pdf',
        'prepare_customer_contract_review',
        'complete_reception_intake',
        'save_reception_confirmation',
        'sign_and_lock_intake',
        'send_to_hall_manager',
    ];
}

function m360_rw_intake_action_requires_vehicle_complete(string $actionType): bool
{
    return in_array($actionType, m360_rw_intake_actions_requiring_vehicle_complete(), true);
}

/**
 * Bidirectional sync of vehicle canonical fields between top-level, nested, and form_values.
 *
 * @param array<string, mixed> $payload
 * @return array<string, mixed>
 */
function m360_rw_intake_sync_vehicle_canonical_fields(array $payload): array
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $vehicle = is_array($payload['reception_intake']['vehicle'] ?? null)
        ? $payload['reception_intake']['vehicle']
        : [];

    $pick = static function (array $sources, string ...$keys): string {
        foreach ($sources as $src) {
            foreach ($keys as $key) {
                if (isset($src[$key]) && trim((string)$src[$key]) !== '') {
                    return trim((string)$src[$key]);
                }
            }
        }

        return '';
    };

    $sources = [$vehicle, $payload];
    $fv = is_array($payload['form_values'] ?? null) ? $payload['form_values'] : [];
    if ($fv !== []) {
        $sources[] = $fv;
    }

    $plate = $pick($sources, 'plate', 'vehicle_plate');
    $vin = $pick($sources, 'vin', 'chassis');
    $brand = $pick($sources, 'brand', 'vehicle_brand');
    $model = $pick($sources, 'model', 'vehicle_model', 'vehicle_class');
    $vehicleClass = $pick($sources, 'vehicle_class', 'model', 'vehicle_model');
    $vehicleType = $pick($sources, 'vehicle_type', 'body_type', 'vehicle_body_type', 'car_type');
    $mileage = $pick($sources, 'mileage', 'odometer_km');
    $fuel = $pick($sources, 'fuel_level', 'intake_fuel_level', 'fuel');
    $year = $pick($sources, 'vehicle_year_pair', 'vehicle_year');
    $visitDate = $pick($sources, 'visit_date');

    if ($plate !== '') {
        $vehicle['plate'] = $plate;
        $payload['plate'] = $plate;
        $payload['vehicle_plate'] = $plate;
    }
    if ($vin !== '') {
        $vehicle['vin'] = $vin;
        $payload['vin'] = $vin;
    }
    if ($brand !== '') {
        $vehicle['brand'] = $brand;
        $payload['brand'] = $brand;
        $payload['vehicle_brand'] = $brand;
    }
    if ($model !== '') {
        $vehicle['model'] = $model;
        $payload['model'] = $model;
        $payload['vehicle_model'] = $model;
    }
    if ($vehicleClass !== '') {
        $vehicle['vehicle_class'] = $vehicleClass;
        $payload['vehicle_class'] = $vehicleClass;
        if (trim((string)($payload['model'] ?? '')) === '') {
            $payload['model'] = $vehicleClass;
            $payload['vehicle_model'] = $vehicleClass;
            $vehicle['model'] = $vehicleClass;
        }
    }
    if ($vehicleType !== '') {
        $vehicle['vehicle_type'] = $vehicleType;
        $payload['vehicle_type'] = $vehicleType;
    }
    if ($mileage !== '') {
        $vehicle['mileage'] = $mileage;
        $payload['mileage'] = $mileage;
        $payload['odometer_km'] = $mileage;
    }
    if ($fuel !== '') {
        $vehicle['fuel_level'] = $fuel;
        $payload['fuel_level'] = $fuel;
        $payload['intake_fuel_level'] = $fuel;
    }
    if ($year !== '') {
        $vehicle['vehicle_year_pair'] = $year;
        $payload['vehicle_year_pair'] = $year;
    }
    if ($visitDate !== '') {
        $vehicle['visit_date'] = $visitDate;
        $payload['visit_date'] = $visitDate;
    }

    $payload['reception_intake']['vehicle'] = $vehicle;

    if ($fv !== []) {
        $fvMap = [
            'plate' => $plate,
            'vin' => $vin,
            'brand' => $brand,
            'model' => $model !== '' ? $model : $vehicleClass,
            'vehicle_class' => $vehicleClass !== '' ? $vehicleClass : $model,
            'vehicle_type' => $vehicleType,
            'mileage' => $mileage,
            'fuel_level' => $fuel,
            'vehicle_year_pair' => $year,
            'visit_date' => $visitDate,
        ];
        foreach ($fvMap as $key => $val) {
            if ($val !== '') {
                $payload['form_values'][$key] = $val;
            }
        }
    }

    return $payload;
}

function m360_rw_intake_render_diagnostic_request_notice(int $onlineRequestId): void
{
    if (!m360_rw_intake_is_diagnostic_only_request($onlineRequestId)) {
        return;
    }
    echo '<div class="m360-rw-flash is-warn" role="status">';
    echo m360_rw_h('درخواست #' . $onlineRequestId . ' — فقط عیب‌یابی PR-02A. برای امضای محصول از درخواست جدید با برند تأییدشده (مثلاً بنز C200 یا پورشه Macan) استفاده کنید.');
    echo '</div>';
}

function m360_rw_intake_render_payload_invalid_notice(array $payloadMeta): void
{
    if (($payloadMeta['valid'] ?? true) === true) {
        return;
    }
    $warning = trim((string)($payloadMeta['raw_warning'] ?? ''));
    if ($warning === '') {
        $warning = 'ساختار JSON پرونده نامعتبر است. ذخیره‌سازی متوقف شده تا از بازگشت ناخواسته به مرحله خودرو جلوگیری شود.';
    }
    echo '<div class="m360-rw-flash is-err" role="alert">' . m360_rw_h($warning) . '</div>';
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-calendar-1405-helper.php';

/** @return array<string, list<string>> */
function m360_rw_intake_vehicle_brand_model_map(): array
{
    return [
        'بنز' => ['C200', 'C250', 'C300', 'E200', 'E250', 'E300', 'E350', 'S350', 'S500', 'S560', 'GLC', 'GLE', 'GLS', 'G-Class', 'سایر'],
        'ب ام و' => ['320', '325', '328', '330', '520', '523', '525', '528', '530', '730', '740', 'X1', 'X3', 'X4', 'X5', 'X6', 'سایر'],
        'پورشه' => ['Cayenne', 'Macan', 'Panamera', '911', 'Boxster', 'Cayman', 'سایر'],
        'ولوو' => ['XC60', 'XC90', 'S60', 'S80', 'V40', 'V60', 'سایر'],
        'فولکس واگن' => ['Passat', 'Tiguan', 'Touareg', 'Golf', 'Beetle', 'Jetta', 'سایر'],
        'سایر' => [],
    ];
}

const M360_RW_RECEPTION_OTP_CUSTOMER_ONLY_MESSAGE_FA = 'تأیید OTP فقط از طریق فرم آنلاین مشتری انجام می‌شود. پذیرش مجاز به ارسال OTP برای مشتری نیست.';
const M360_RW_RECEPTION_UNVERIFIED_ACCESS_MESSAGE_FA = 'این درخواست هنوز توسط مشتری تأیید OTP نشده و قابل مشاهده در پذیرش نیست.';

function m360_rw_intake_staff_otp_action_blocked(): bool
{
    return true;
}

/** @param array<string, mixed> $requestRow */
function m360_rw_intake_reception_otp_verified(array $requestRow): bool
{
    return m360_online_req_payload_otp_verified($requestRow);
}

function m360_rw_intake_vehicle_year_options(): array
{
    $todayGy = (int)gmdate('Y');
    $options = [];
    for ($i = 0; $i <= 20; $i++) {
        $gy = $todayGy - $i;
        $jy = $gy - 621;
        $options[] = [
            'value' => $jy . ' - ' . $gy,
            'label' => $jy . ' شمسی / ' . $gy . ' میلادی',
        ];
    }

    return $options;
}

/**
 * @param array<string, string> $post
 * @return array{ok:bool,error:string,brand:string,model:string,year:string,brand_status:string,model_status:string,brand_other_explanation:string,model_other_explanation:string}
 */
function m360_rw_intake_validate_vehicle_selection(array $post): array
{
    $brand = trim((string)($post['vehicle_brand'] ?? $post['brand'] ?? ''));
    $model = trim((string)($post['vehicle_class'] ?? $post['model'] ?? ''));
    $year = trim((string)($post['vehicle_year_pair'] ?? $post['vehicle_year'] ?? ''));
    $brandOther = trim((string)($post['brand_other_explanation'] ?? ''));
    $modelOther = trim((string)($post['model_other_explanation'] ?? ''));
    if ($brand === '' || $brand === 'انتخاب برند' || !in_array($brand, m360_rw_intake_approved_vehicle_brands(), true)) {
        return ['ok' => false, 'error' => 'برند خودرو باید از فهرست تأییدشده انتخاب شود.', 'brand' => '', 'model' => '', 'year' => '', 'brand_status' => '', 'model_status' => '', 'brand_other_explanation' => '', 'model_other_explanation' => ''];
    }
    if (m360_rw_intake_brand_is_legacy_unsupported($brand)) {
        return ['ok' => false, 'error' => 'برند انتخاب‌شده خارج از محدوده فعلی پذیرش است. از برندهای تأییدشده یا «سایر» با توضیح مدیر استفاده کنید.', 'brand' => '', 'model' => '', 'year' => '', 'brand_status' => '', 'model_status' => '', 'brand_other_explanation' => '', 'model_other_explanation' => ''];
    }
    if ($year === '' || $year === 'انتخاب سال') {
        return ['ok' => false, 'error' => 'سال ساخت خودرو الزامی است.', 'brand' => $brand, 'model' => '', 'year' => '', 'brand_status' => '', 'model_status' => '', 'brand_other_explanation' => '', 'model_other_explanation' => ''];
    }
    $brandStatus = 'approved';
    $modelStatus = 'approved';
    if ($brand === M360_RW_TOP_LEVEL_OTHER_BRAND) {
        $brandStatus = 'MANAGER_EXCEPTION_REVIEW';
        if ($brandOther === '') {
            return ['ok' => false, 'error' => 'برای برند سایر، توضیح الزامی است.', 'brand' => $brand, 'model' => '', 'year' => $year, 'brand_status' => $brandStatus, 'model_status' => '', 'brand_other_explanation' => '', 'model_other_explanation' => ''];
        }
        $vt = m360_rw_intake_validate_text($brandOther, 500, 'توضیح برند سایر');
        if (!$vt['ok']) {
            return ['ok' => false, 'error' => $vt['error'], 'brand' => $brand, 'model' => '', 'year' => $year, 'brand_status' => $brandStatus, 'model_status' => '', 'brand_other_explanation' => '', 'model_other_explanation' => ''];
        }
        $brandOther = trim($brandOther);
        $model = '';
        $modelStatus = '';
    } else {
        if ($model === '') {
            return ['ok' => false, 'error' => 'کلاس / مدل خودرو الزامی است.', 'brand' => $brand, 'model' => '', 'year' => $year, 'brand_status' => $brandStatus, 'model_status' => '', 'brand_other_explanation' => '', 'model_other_explanation' => ''];
        }
        if ($model === 'انتخاب کلاس / مدل') {
            return ['ok' => false, 'error' => 'کلاس / مدل خودرو را از فهرست انتخاب کنید.', 'brand' => $brand, 'model' => '', 'year' => $year, 'brand_status' => $brandStatus, 'model_status' => '', 'brand_other_explanation' => '', 'model_other_explanation' => ''];
        }
        if ($model === M360_RW_MODEL_LIST_GAP_VALUE) {
            $modelStatus = 'MODEL_LIST_GAP';
            if ($modelOther === '') {
                return ['ok' => false, 'error' => 'برای مدل سایر، توضیح الزامی است.', 'brand' => $brand, 'model' => $model, 'year' => $year, 'brand_status' => $brandStatus, 'model_status' => $modelStatus, 'brand_other_explanation' => '', 'model_other_explanation' => ''];
            }
            $vt = m360_rw_intake_validate_text($modelOther, 500, 'توضیح مدل سایر');
            if (!$vt['ok']) {
                return ['ok' => false, 'error' => $vt['error'], 'brand' => $brand, 'model' => $model, 'year' => $year, 'brand_status' => $brandStatus, 'model_status' => $modelStatus, 'brand_other_explanation' => '', 'model_other_explanation' => ''];
            }
            $modelOther = trim($modelOther);
        }
    }

    return [
        'ok' => true,
        'error' => '',
        'brand' => $brand,
        'model' => $model,
        'year' => $year,
        'brand_status' => $brandStatus,
        'model_status' => $modelStatus,
        'brand_other_explanation' => $brandOther,
        'model_other_explanation' => $modelOther,
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{status:string,sent_at:string,note:string}
 */
function m360_rw_intake_hall_manager_canonical(array $payload): array
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $hm = is_array($payload['reception_intake']['hall_manager'] ?? null)
        ? $payload['reception_intake']['hall_manager']
        : [];

    return [
        'status' => trim((string)($hm['status'] ?? '')),
        'sent_at' => trim((string)($hm['sent_at'] ?? '')),
        'note' => trim((string)($hm['note'] ?? '')),
    ];
}

/** @param array<string, mixed> $payload */
function m360_rw_intake_hall_manager_step_complete(array $payload): bool
{
    $hm = m360_rw_intake_hall_manager_canonical($payload);

    return $hm['status'] === M360_RW_HALL_MANAGER_STATUS_READY_FA && $hm['sent_at'] !== '';
}

/** @param array<string, string> $formValues */
function m360_rw_intake_render_visit_calendar(array $formValues): void
{
    $days = m360_rw_calendar_next_30_day_window();
    $selected = trim((string)($formValues['visit_date'] ?? ''));
    $display = trim((string)($formValues['visit_date_display'] ?? ''));
    echo '<label for="m360_rw_visit_date_display">تاریخ مراجعه <span class="m360-req">*</span></label>';
    echo '<div class="m360-date-field">';
    echo '<input type="text" id="m360_rw_visit_date_display" class="m360-date-display' . ($display !== '' ? ' m360-date-display--filled' : '') . '" readonly placeholder="روز مراجعه را از تقویم انتخاب کنید" value="' . m360_rw_h($display) . '" aria-describedby="m360_rw_visit_date_hint">';
    echo '<input type="hidden" id="m360_rw_visit_date" name="visit_date" value="' . m360_rw_h($selected) . '">';
    echo '<p id="m360_rw_visit_date_hint" class="m360-jalali-datepicker__hint">انتخاب مراجعه در بازه ۳۰ روز آینده تقویم شمسی — فقط روزهای کاری (جمعه و تعطیلات رسمی غیرفعال)</p>';
    echo '<div class="m360-server-calendar m360-rw-server-calendar" id="m360_rw_server_calendar" role="group" aria-label="تقویم مراجعه">';
    foreach ($days as $day) {
        m360_rw_calendar_render_day_button($day, $selected, 'm360_rw_h');
    }
    echo '</div></div>';
}

/** @param array<string, string> $formValues */
function m360_rw_intake_render_vehicle_selector(array $formValues): void
{
    $brand = trim((string)($formValues['brand'] ?? ''));
    $model = trim((string)($formValues['model'] ?? ''));
    $year = trim((string)($formValues['vehicle_year_pair'] ?? ''));
    $brandOther = trim((string)($formValues['brand_other_explanation'] ?? ''));
    $modelOther = trim((string)($formValues['model_other_explanation'] ?? ''));
    echo '<div class="m360-rw-vehicle-selector">';
    echo '<label for="m360_rw_vehicle_brand">برند <span class="m360-req">*</span></label>';
    echo '<select id="m360_rw_vehicle_brand" name="vehicle_brand" class="m360-rw-form-input" data-required-both="1" required>';
    echo '<option value="">انتخاب برند</option>';
    foreach (m360_rw_intake_approved_vehicle_brands() as $brandOpt) {
        $sel = ($brand === $brandOpt) ? ' selected' : '';
        echo '<option value="' . m360_rw_h($brandOpt) . '"' . $sel . '>' . m360_rw_h($brandOpt) . '</option>';
    }
    echo '</select>';
    echo '<label for="m360_rw_vehicle_class">کلاس / مدل <span class="m360-req">*</span></label>';
    echo '<select id="m360_rw_vehicle_class" name="vehicle_class" class="m360-rw-form-input" data-required-both="1">';
    echo '<option value="">انتخاب کلاس / مدل</option>';
    $modelMap = m360_rw_intake_vehicle_brand_model_map();
    if ($brand !== '' && isset($modelMap[$brand]) && $brand !== 'سایر') {
        foreach ($modelMap[$brand] as $modelOpt) {
            $sel = ($model === $modelOpt) ? ' selected' : '';
            echo '<option value="' . m360_rw_h($modelOpt) . '"' . $sel . '>' . m360_rw_h($modelOpt) . '</option>';
        }
    }
    echo '</select>';
    echo '<div id="m360_rw_top_other_panel" class="m360-rw-other-panel" style="display:none">';
    echo '<p class="m360-rw-warn">خارج از محدوده استاندارد — نیازمند بررسی / تأیید مدیر</p>';
    m360_rw_intake_form_field('توضیح برند سایر', 'brand_other_explanation', $brandOther, 'textarea', true);
    echo '</div>';
    echo '<div id="m360_rw_model_gap_panel" class="m360-rw-other-panel" style="display:none">';
    echo '<p class="m360-rw-muted">مدل در فهرست موجود نیست (MODEL_LIST_GAP)</p>';
    m360_rw_intake_form_field('توضیح مدل سایر', 'model_other_explanation', $modelOther, 'textarea', true);
    echo '</div>';
    echo '<label for="m360_rw_vehicle_year">سال ساخت <span class="m360-req">*</span></label>';
    echo '<select id="m360_rw_vehicle_year" name="vehicle_year_pair" class="m360-rw-form-input" required>';
    echo '<option value="">انتخاب سال</option>';
    foreach (m360_rw_intake_vehicle_year_options() as $opt) {
        $sel = ($year === $opt['value']) ? ' selected' : '';
        echo '<option value="' . m360_rw_h($opt['value']) . '"' . $sel . '>' . m360_rw_h($opt['label']) . '</option>';
    }
    echo '</select></div>';
    echo '<script>window.m360RwVehicleInit=' . json_encode(['brand' => $brand, 'model' => $model], JSON_UNESCAPED_UNICODE) . ';</script>';
}

// --- P11.9-C-2C: Reception intake write actions (payload merge, no schema change) ---

const M360_RW_INTAKE_HISTORY_PREFIX = 'RECEPTION_INTAKE_SAVE_';
const M360_RW_INTAKE_LOCK_MESSAGE_FA = 'پرونده پذیرش پس از امضای مشتری قفل شده است. اصلاح فقط از مسیر اصلاحیه مجاز است.';

/** @return list<string> */
function m360_rw_intake_allowed_actions(): array
{
    return [
        'save_mobile_correction',
        'save_vehicle_identity',
        'save_condition_notes',
        'save_service_classification',
        'save_temporary_reception',
        'send_to_hall_manager',
        'save_documents_and_cost',
        'save_camera_photo',
        'save_diagnostic_pdf',
        'prepare_customer_contract_review',
        'save_reception_confirmation',
        'complete_reception_intake',
        'sign_and_lock_intake',
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
        'reception_temporary_diagnosis' => 'پذیرش موقت — عیب‌یابی',
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
            'vehicle_class' => 'vehicle_class',
            'vehicle_type' => 'vehicle_type',
            'mileage' => 'odometer_km',
            'fuel_level' => 'fuel_level',
        ];
        foreach ($map as $from => $to) {
            if ((!isset($payload[$to]) || trim((string)$payload[$to]) === '') && isset($v[$from]) && trim((string)$v[$from]) !== '') {
                $payload[$to] = $v[$from];
            }
        }
        if ((!isset($payload['model']) || trim((string)$payload['model']) === '')
            && isset($payload['vehicle_class']) && trim((string)$payload['vehicle_class']) !== '') {
            $payload['model'] = $payload['vehicle_class'];
            $payload['vehicle_model'] = $payload['vehicle_class'];
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
    if (isset($ri['service_classification']) && is_array($ri['service_classification'])) {
        $sc = $ri['service_classification'];
        $route = trim((string)($sc['route'] ?? $sc['main'] ?? ''));
        if ($route !== '' && (!isset($payload['reception_service_primary']) || trim((string)$payload['reception_service_primary']) === '')) {
            $payload['reception_service_primary'] = $route;
        }
        $subs = $sc['diagnostic_subcategories'] ?? $sc['diagnostic_categories'] ?? null;
        if (is_array($subs) && $subs !== [] && (!isset($payload['reception_service_diag_sub']) || $payload['reception_service_diag_sub'] === [])) {
            $payload['reception_service_diag_sub'] = $subs;
        }
        if (array_key_exists('service_path_clear', $sc) && !isset($payload['fault_service_path_clear']) && !isset($payload['service_path_clear'])) {
            $payload['fault_service_path_clear'] = !empty($sc['service_path_clear']) ? '1' : '0';
            $payload['service_path_clear'] = $payload['fault_service_path_clear'];
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
    $canonicalPhotos = m360_rw_intake_photos_canonical($payload);
    $payload = m360_rw_intake_photos_sync_to_payload($payload, $canonicalPhotos);

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
            $vehicleSel = m360_rw_intake_validate_vehicle_selection($post);
            if (!$vehicleSel['ok']) {
                return ['ok' => false, 'error' => $vehicleSel['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $visitDate = trim((string)($post['visit_date'] ?? ''));
            $visitCheck = m360_rw_calendar_validate_visit_date($visitDate);
            if (!$visitCheck['ok']) {
                return ['ok' => false, 'error' => $visitCheck['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $mileageNorm = m360_rw_intake_post_scalar($post, 'mileage');
            $fuelNorm = m360_rw_intake_post_scalar($post, 'fuel_level');
            foreach ([$vinNorm, $mileageNorm, $fuelNorm] as $norm) {
                if (!$norm['ok']) {
                    return ['ok' => false, 'error' => $norm['error'], 'payload' => $payload, 'column_updates' => []];
                }
            }
            $vin = $vinNorm['value'];
            $brand = $vehicleSel['brand'];
            $model = $vehicleSel['model'];
            $fuel = $fuelNorm['value'];

            $vPlate = m360_rw_intake_validate_plate($plate);
            if (!$vPlate['ok']) {
                return ['ok' => false, 'error' => $vPlate['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $vVin = m360_rw_intake_validate_vin($vin);
            if (!$vVin['ok']) {
                return ['ok' => false, 'error' => $vVin['error'], 'payload' => $payload, 'column_updates' => []];
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
            if (($plateBuilt['display'] ?? '') !== '') {
                $payload['plate_display'] = $plateBuilt['display'];
            }
            if ($vin !== '') {
                $payload['vin'] = $vin;
            }
            $payload['brand'] = $brand;
            $payload['vehicle_brand'] = $brand;
            if ($model !== '') {
                $payload['model'] = $model;
                $payload['vehicle_model'] = $model;
                $payload['vehicle_class'] = $model;
            }
            $payload['vehicle_year_pair'] = $vehicleSel['year'];
            $payload['visit_date'] = $visitDate;
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
                'plate_display' => (string)($plateBuilt['display'] ?? ''),
                'vin' => $vin,
                'brand' => $brand,
                'model' => $model,
                'vehicle_class' => $model,
                'vehicle_year_pair' => $vehicleSel['year'],
                'visit_date' => $visitDate,
                'brand_status' => $vehicleSel['brand_status'],
                'model_status' => $vehicleSel['model_status'],
                'brand_other_explanation' => $vehicleSel['brand_other_explanation'],
                'model_other_explanation' => $vehicleSel['model_other_explanation'],
                'mileage' => $vMileage['value'],
                'fuel_level' => $fuel,
            ];
            $columnUpdates['vehicle_plate'] = $plate;
            if ($visitDate !== '') {
                $columnUpdates['visit_date'] = $visitDate;
            }
            $payload = m360_rw_intake_sync_vehicle_canonical_fields($payload);
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
            unset($payload['otp_verified_at'], $payload['otp_verified_mobile']);
            $payload['reception_intake']['otp'] = [
                'status' => 'unverified',
                'mobile' => $mobile,
                'updated_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ];
            if (m360_rw_intake_load_otp_helper()) {
                m360_otp_clear_pending();
                m360_otp_reset_verified();
            }
            $payload['reception_intake']['mobile_correction'] = [
                'mobile' => $mobile,
                'corrected_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ];
            $columnUpdates['mobile'] = $mobile;
            $columnUpdates['otp_verified'] = 0;
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
            $requestType = m360_rw_intake_resolve_customer_request_type($post, $payload, []);
            $policyGroup = m360_rw_request_service_policy_group($requestType);
            $validated = m360_rw_intake_validate_service_classification_post($post, $policyGroup);
            if (!$validated['ok']) {
                return ['ok' => false, 'error' => $validated['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $primary = $validated['route'];
            $diagSubs = $validated['diag_subs'];
            $pathClearRaw = $validated['path_clear'];
            $pathNote = $validated['path_note'];
            $inspectionSubs = $validated['inspection_subs'] ?? [];
            $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
            $now = gmdate('Y-m-d\TH:i:s\Z');

            $payload['reception_service_primary'] = $primary;
            $payload['customer_request_type'] = $requestType;
            $payload['service_policy_group'] = $policyGroup;
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
            if ($inspectionSubs !== []) {
                $payload['inspection_scope'] = $inspectionSubs;
            }
            $payload['reception_intake']['service_classification'] = [
                'route' => $primary,
                'diagnostic_subcategories' => $diagSubs,
                'inspection_scope' => $inspectionSubs,
                'service_path_clear' => $pathClearRaw === '1',
                'service_path_note' => $pathNote,
                'policy_group' => $policyGroup,
                'customer_request_type' => $requestType,
                'saved_at' => $now,
                'saved_by' => (string)$userId,
            ];
            if ($policyGroup === 'UNKNOWN_FAULT') {
                $tempStatus = trim((string)($post['temporary_status'] ?? 'reception_temporary_diagnosis'));
                $symptoms = trim((string)($post['initial_symptoms'] ?? ''));
                $payload['reception_intake']['temporary_reception'] = [
                    'status' => $tempStatus !== '' ? $tempStatus : 'reception_temporary_diagnosis',
                    'reason' => $symptoms !== '' ? $symptoms : $pathNote,
                    'request_more_info_note' => '',
                    'expert_review_required' => false,
                    'expert_review_note' => '',
                    'saved_at' => $now,
                    'saved_by' => (string)$userId,
                ];
                $payload['temporary_reception_status'] = $payload['reception_intake']['temporary_reception']['status'];
            }
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

        case 'send_to_hall_manager':
            if (!m360_rw_intake_operation_gate_hall_manager_allowed($payload)) {
                return [
                    'ok' => false,
                    'error' => m360_rw_intake_operation_gate_message_fa($payload),
                    'payload' => $payload,
                    'column_updates' => [],
                ];
            }
            $noteNorm = m360_rw_intake_post_scalar($post, 'hall_manager_note');
            if (!$noteNorm['ok']) {
                return ['ok' => false, 'error' => $noteNorm['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $vt = m360_rw_intake_validate_text($noteNorm['value'], 2000, 'یادداشت ارسال به مسئول سالن');
            if (!$vt['ok']) {
                return ['ok' => false, 'error' => $vt['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
            $now = gmdate('Y-m-d\TH:i:s\Z');
            $payload['reception_intake']['hall_manager'] = [
                'status' => M360_RW_HALL_MANAGER_STATUS_READY_FA,
                'status_code' => 'ready_for_hall_manager_review',
                'sent_at' => $now,
                'sent_by' => (string)$userId,
                'note' => $vt['value'],
            ];
            $payload['reception_intake']['referral'] = [
                'hall_manager_gate' => true,
                'legacy_referral_team_removed_pr02a' => true,
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
            $payload = m360_rw_intake_ensure_nested($payload);
            if (!isset($payload['reception_intake']['documents']) || !is_array($payload['reception_intake']['documents'])) {
                $payload['reception_intake']['documents'] = [];
            }
            $docs = &$payload['reception_intake']['documents'];
            if ($photoStatus !== '') {
                $docs['photo_status'] = $photoStatus;
                $payload['photo_status'] = $photoStatus;
            }
            if ($diagStatus !== '') {
                $docs['diagnostic_status'] = $diagStatus;
                $payload['diagnostic_status'] = $diagStatus;
                $payload['diag_status'] = $diagStatus;
            }
            if ($contractStatus !== '') {
                $docs['contract_status'] = $contractStatus;
                $payload['contract_status'] = $contractStatus;
            }
            if ($costAgreement !== '') {
                $docs['cost_agreement'] = $costAgreement;
                $payload['cost_agreement'] = $costAgreement;
            }
            if ($costNote !== '') {
                $docs['cost_agreement_note'] = $costNote;
                $payload['cost_agreement_note'] = $costNote;
            }
            $canonicalPhotos = m360_rw_intake_photos_canonical($payload);
            $payload = m360_rw_intake_photos_sync_to_payload($payload, $canonicalPhotos);
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
            $payload = m360_rw_intake_ensure_nested($payload);
            $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
            $canonical = m360_rw_intake_photos_canonical($payload);
            $now = gmdate('Y-m-d\TH:i:s\Z');
            $canonical['slots'][$slot] = [
                'label' => $slotDefs[$slot],
                'status' => 'captured',
                'captured_at' => $now,
                'captured_by' => (string)$userId,
                'data_key' => $saved['relative_path'],
            ];
            $canonical = m360_rw_intake_photos_recalculate($canonical);
            $payload = m360_rw_intake_photos_sync_to_payload($payload, $canonical);
            if (!empty($canonical['is_complete'])) {
                m360_rw_intake_mark_section_saved($payload, 'camera_photo');
            }
            break;

        case 'prepare_customer_contract_review':
            $requestId = (int)($post['online_request_id'] ?? 0);
            if ($requestId < 1) {
                return ['ok' => false, 'error' => 'شناسه درخواست نامعتبر است.', 'payload' => $payload, 'column_updates' => []];
            }
            $requestRow = [
                'online_request_id' => (string)$requestId,
                'customer_id' => (string)($post['_rw_request_customer_id'] ?? ''),
                'vehicle_id' => (string)($post['_rw_request_vehicle_id'] ?? ''),
                'customer_name' => trim((string)($post['_rw_request_customer_name'] ?? '')),
                'vehicle_plate' => trim((string)($post['_rw_request_vehicle_plate'] ?? '')),
                'visit_date' => trim((string)($post['_rw_request_visit_date'] ?? '')),
                'mobile' => trim((string)($post['_rw_request_mobile'] ?? '')) !== ''
                    ? trim((string)$post['_rw_request_mobile'])
                    : m360_rw_intake_resolve_mobile_for_otp([], $payload),
                'otp_verified' => (string)($post['_rw_request_otp_verified'] ?? ($payload['otp_verified'] ?? '0')),
                'request_payload_json' => json_encode($payload),
            ];
            $prereq = m360_rw_intake_contract_cartable_prerequisites($payload, $requestRow);
            if (!$prereq['ready']) {
                return [
                    'ok' => false,
                    'error' => 'پرونده هنوز آماده ارسال به کارتابل مشتری نیست.',
                    'payload' => $payload,
                    'column_updates' => [],
                ];
            }
            $conn = customer_core_db();
            $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
            $boot = m360_rw_intake_bootstrap_contract_cartable_task(
                $conn !== false ? $conn : null,
                $requestId,
                $requestRow,
                $payload,
                (int)$userId
            );
            if (!$boot['ok']) {
                return ['ok' => false, 'error' => $boot['error'], 'payload' => $payload, 'column_updates' => []];
            }
            $payload = $boot['payload'];
            break;

        case 'complete_reception_intake':
            $requestRow = [
                'mobile' => trim((string)($post['_rw_request_mobile'] ?? '')) !== ''
                    ? trim((string)$post['_rw_request_mobile'])
                    : m360_rw_intake_resolve_mobile_for_otp([], $payload),
                'otp_verified' => (string)($post['_rw_request_otp_verified'] ?? ($payload['otp_verified'] ?? '0')),
                'request_payload_json' => json_encode($payload),
            ];
            $wizard = m360_rw_intake_get_wizard_step_state($payload, $requestRow);
            foreach (m360_rw_intake_reception_completion_keys() as $stepKey) {
                if (empty($wizard['steps'][$stepKey]['complete'])) {
                    return [
                        'ok' => false,
                        'error' => 'تکمیل پذیرش هنوز کامل نیست: ' . m360_rw_intake_cartable_blocker_label_fa($stepKey, 'item'),
                        'payload' => $payload,
                        'column_updates' => [],
                    ];
                }
            }
            if (m360_rw_intake_reception_is_completed($payload)) {
                return [
                    'ok' => false,
                    'error' => 'پذیرش این پرونده قبلاً تکمیل شده است.',
                    'payload' => $payload,
                    'column_updates' => [],
                ];
            }
            $requestId = (int)($post['online_request_id'] ?? 0);
            $now = gmdate('Y-m-d\TH:i:s\Z');
            $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
            $payload = m360_rw_intake_ensure_nested($payload);
            $payload['reception_intake']['reception_completed'] = [
                'status' => 'completed',
                'completed_at' => $now,
                'completed_by' => (string)$userId,
            ];
            $payload['reception_intake']['operation_gate'] = [
                'status' => 'pending_contract_and_financial',
                'message_fa' => 'عملیات هنوز مجاز نیست',
                'hall_manager_allowed' => false,
                'contract_pending_customer_review' => true,
            ];
            m360_rw_intake_mark_section_saved($payload, 'reception_completed');

            if (!m360_rw_intake_contract_customer_accepted($payload) && !m360_rw_intake_contract_cartable_pending($payload)) {
                $prereq = m360_rw_intake_contract_cartable_prerequisites($payload, $requestRow);
                if ($prereq['ready'] && $requestId > 0) {
                    $connBoot = customer_core_db();
                    $boot = m360_rw_intake_bootstrap_contract_cartable_task(
                        $connBoot !== false ? $connBoot : null,
                        $requestId,
                        array_merge($requestRow, [
                            'online_request_id' => (string)$requestId,
                            'customer_id' => (string)($post['_rw_request_customer_id'] ?? ''),
                            'vehicle_id' => (string)($post['_rw_request_vehicle_id'] ?? ''),
                            'customer_name' => trim((string)($post['_rw_request_customer_name'] ?? '')),
                            'vehicle_plate' => trim((string)($post['_rw_request_vehicle_plate'] ?? '')),
                            'visit_date' => trim((string)($post['_rw_request_visit_date'] ?? '')),
                        ]),
                        $payload,
                        (int)$userId
                    );
                    if ($boot['ok']) {
                        $payload = $boot['payload'];
                    } else {
                        if (!isset($payload['reception_intake']['contract']) || !is_array($payload['reception_intake']['contract'])) {
                            $payload['reception_intake']['contract'] = [];
                        }
                        $payload['reception_intake']['contract']['status'] = M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW;
                        $payload['contract_status'] = M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW;
                    }
                } else {
                    if (!isset($payload['reception_intake']['contract']) || !is_array($payload['reception_intake']['contract'])) {
                        $payload['reception_intake']['contract'] = [];
                    }
                    $payload['reception_intake']['contract']['status'] = M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW;
                    $payload['contract_status'] = M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW;
                }
            }
            break;

        case 'run_intake_contract':
        case 'approve_intake_contract':
            return ['ok' => false, 'error' => 'تأیید قرارداد فقط از طریق صفحه مطالعه مشتری امکان‌پذیر است.', 'payload' => $payload, 'column_updates' => []];

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
    if (isset($columnUpdates['visit_date']) && m360_online_req_has_column($conn, 'visit_date')) {
        $sets[] = 'visit_date = ?';
        $params[] = $columnUpdates['visit_date'];
    }
    if (isset($columnUpdates['mobile']) && m360_online_req_has_column($conn, 'mobile')) {
        $sets[] = 'mobile = ?';
        $params[] = $columnUpdates['mobile'];
    }
    if (array_key_exists('otp_verified', $columnUpdates) && m360_online_req_has_column($conn, 'otp_verified')) {
        $sets[] = 'otp_verified = ?';
        $params[] = (int)$columnUpdates['otp_verified'];
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

    if (in_array($actionType, ['send_customer_otp', 'verify_customer_otp'], true)) {
        return ['ok' => false, 'message' => M360_RW_RECEPTION_OTP_CUSTOMER_ONLY_MESSAGE_FA, 'history_written' => false];
    }

    if (!m360_rw_intake_reception_otp_verified($request)) {
        return ['ok' => false, 'message' => M360_RW_RECEPTION_UNVERIFIED_ACCESS_MESSAGE_FA, 'history_written' => false];
    }

    $rawPayloadJson = (string)($request['request_payload_json'] ?? '');
    $payloadMeta = m360_rw_decode_payload($rawPayloadJson !== '' ? $rawPayloadJson : null);
    if ($rawPayloadJson !== '' && ($payloadMeta['valid'] ?? true) !== true) {
        return [
            'ok' => false,
            'message' => trim((string)($payloadMeta['raw_warning'] ?? '')) !== ''
                ? (string)$payloadMeta['raw_warning']
                : 'ساختار JSON پرونده نامعتبر است. لطفاً پشتیبانی فنی را مطلع کنید.',
            'history_written' => false,
        ];
    }

    $existingForLock = m360_rw_intake_payload_for_recovery($payloadMeta['items']);
    if ($actionType === 'sign_and_lock_intake') {
        return m360_rw_intake_process_sign_and_lock($conn, $requestId, $request, $post);
    }

    $lockGuard = m360_rw_intake_assert_not_locked($existingForLock, $actionType);
    if (!$lockGuard['ok']) {
        $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
        m360_online_req_write_history(
            $conn,
            $requestId,
            M360_RW_INTAKE_HISTORY_PREFIX . 'LOCK_BLOCKED',
            (string)($request['request_status'] ?? ''),
            (string)($request['request_status'] ?? ''),
            'Blocked intake save after lock: ' . $actionType,
            $userId
        );

        return ['ok' => false, 'message' => $lockGuard['message'], 'history_written' => true];
    }

    $existing = m360_rw_intake_payload_for_recovery($payloadMeta['items']);
    $existing = m360_rw_intake_sync_vehicle_canonical_fields($existing);
    $otpPreserved = $existing['otp_verified'] ?? null;
    $resetOtp = ($actionType === 'save_mobile_correction');

    if (m360_rw_intake_action_requires_vehicle_complete($actionType)
        && !m360_rw_intake_vehicle_step_complete($existing, $request)) {
        return [
            'ok' => false,
            'message' => 'ابتدا مرحله خودرو و پلاک را با برند، مدل، کیلومتر و سوخت تکمیل و ذخیره کنید.',
            'history_written' => false,
        ];
    }

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
    $post['_rw_request_mobile'] = trim((string)($request['mobile'] ?? ''));
    $post['_rw_request_otp_verified'] = (string)($request['otp_verified'] ?? '');
    $applied = m360_rw_intake_apply_action($existing, $actionType, $post);
    if (!$applied['ok']) {
        return ['ok' => false, 'message' => $applied['error'], 'history_written' => false];
    }

    $newPayload = $applied['payload'];
    $onceContractToken = trim((string)($newPayload['_contract_review_token_once'] ?? ''));
    unset($newPayload['_contract_review_token_once']);
    if ($resetOtp) {
        $newPayload['otp_verified'] = 0;
        unset($newPayload['otp_verified_at'], $newPayload['otp_verified_mobile']);
    } elseif ($otpPreserved !== null) {
        $newPayload['otp_verified'] = $otpPreserved;
    }

    if ($actionType === 'prepare_customer_contract_review') {
        $newPayload['reception_intake']['contract']['contract_text'] = m360_rw_intake_contract_template_text($request, $newPayload);
        if ($onceContractToken !== '') {
            m360_rw_intake_store_contract_review_token_once($requestId, $onceContractToken);
        }
    }

    $contractSmsAfterSave = !empty($newPayload['_contract_sms_after_save']);
    unset($newPayload['_contract_sms_after_save']);

    if ($actionType === 'complete_reception_intake' && $onceContractToken !== '') {
        m360_rw_intake_store_contract_review_token_once($requestId, $onceContractToken);
    }

    $newPayload = m360_rw_intake_sync_vehicle_canonical_fields($newPayload);

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
        'send_to_hall_manager' => 'پرونده به مسئول سالن ارسال شد.',
        'save_camera_photo' => 'عکس پذیرش ذخیره شد.',
        'prepare_customer_contract_review' => 'مأموریت قرارداد در کارتابل مشتری ایجاد شد.',
        'complete_reception_intake' => 'پذیرش ثبت شد. قرارداد در انتظار تأیید مشتری است. عملیات هنوز مجاز نیست.',
        'save_documents_and_cost' => 'مستندات و توافق هزینه ذخیره شد.',
        'save_reception_confirmation' => 'تأیید نهایی پذیرشگر ثبت شد.',
    ];
    $message = $messages[$actionType] ?? 'ذخیره انجام شد.';
    if ($actionType === 'save_service_classification') {
        $canonical = m360_rw_intake_service_classification_canonical($newPayload);
        if ($canonical['service_path_clear'] !== '1') {
            $message = 'مسیر انتخابی قطع است؛ پرونده در پذیرش موقت باقی می‌ماند تا مسیر عیب/خدمت روشن شود.';
        }
    }
    if ($contractSmsAfterSave) {
        $mobile = m360_rw_intake_resolve_mobile_for_otp($request, $newPayload);
        $reviewPath = '';
        $task = m360_rw_intake_contract_cartable_task($newPayload);
        if (trim((string)($task['review_url_path'] ?? '')) !== '') {
            $reviewPath = (string)$task['review_url_path'];
        } elseif (trim((string)($newPayload['_contract_review_token_once'] ?? '')) !== '') {
            $reviewPath = m360_rw_intake_contract_review_url((string)$newPayload['_contract_review_token_once']);
        }
        $sms = m360_rw_intake_send_contract_notification_sms($mobile, true, $reviewPath);
        if (!isset($newPayload['reception_intake']['operation_gate']) || !is_array($newPayload['reception_intake']['operation_gate'])) {
            $newPayload['reception_intake']['operation_gate'] = [];
        }
        $newPayload['reception_intake']['operation_gate']['contract_sms'] = [
            'sent' => !empty($sms['sent']),
            'message' => (string)($sms['message'] ?? ''),
            'skipped_reason' => (string)($sms['skipped_reason'] ?? ''),
            'at' => gmdate('Y-m-d\TH:i:s\Z'),
        ];
        if (!empty($sms['sent'])) {
            $message .= ' ' . (string)$sms['message'];
        } elseif ($actionType === 'complete_reception_intake' && ($sms['skipped_reason'] ?? '') === 'sms_not_configured') {
            $message .= ' ارسال پیامک قرارداد در این محیط پیکربندی نشده است.';
        }
        $persistSms = m360_rw_intake_persist_payload($conn, $requestId, $newPayload, []);
        if (!$persistSms['ok']) {
            return ['ok' => false, 'message' => $persistSms['message'], 'history_written' => $historyWritten];
        }
    }

    return [
        'ok' => true,
        'message' => $message,
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
    $canonical = m360_rw_intake_service_classification_canonical($payload);

    $pathClear = $canonical['service_path_clear'];
    $diagSubs = $canonical['diagnostic_subcategories'];
    $serviceRoute = $canonical['route'];

    $temp = is_array($ri['temporary_reception'] ?? null) ? $ri['temporary_reception'] : [];
    $docs = is_array($ri['documents'] ?? null) ? $ri['documents'] : [];
    $confirm = is_array($ri['reception_confirmation'] ?? null) ? $ri['reception_confirmation'] : [];

    $plate = m360_rw_pick([$vehicle, $payload, $request], 'plate', 'vehicle_plate', 'plate_display');
    if ($plate === '') {
        $plate = m360_rw_recover_plate_from_parts($payload);
    }
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

    $vehicleMeta = is_array($ri['vehicle'] ?? null) ? $ri['vehicle'] : [];
    $visitDate = m360_rw_pick([$vehicleMeta, $payload, $request], 'visit_date');
    $visitDisplay = '';
    if ($visitDate !== '') {
        foreach (m360_rw_calendar_next_30_day_window() as $day) {
            if ($day['gregorian'] === $visitDate) {
                $visitDisplay = $day['label'];
                break;
            }
        }
        if ($visitDisplay === '') {
            $visitDisplay = $visitDate;
        }
    }

    return [
        'plate' => $plate,
        'vin' => m360_rw_pick([$vehicle, $payload], 'vin', 'vehicle_vin', 'chassis_vin'),
        'brand' => m360_rw_pick([$vehicle, $payload], 'brand', 'vehicle_brand'),
        'model' => m360_rw_pick([$vehicle, $payload], 'model', 'vehicle_model', 'vehicle_class'),
        'vehicle_class' => m360_rw_pick([$vehicle, $payload], 'vehicle_class', 'model', 'vehicle_model'),
        'vehicle_type' => m360_rw_pick([$vehicle, $payload], 'vehicle_type', 'body_type', 'vehicle_body_type', 'car_type'),
        'mileage' => m360_rw_pick([$vehicle, $payload], 'mileage', 'odometer_km', 'mileage'),
        'fuel_level' => m360_rw_pick([$vehicle, $payload], 'fuel_level', 'intake_fuel_level', 'fuel'),
        'vehicle_items' => m360_rw_pick([$condition, $payload], 'vehicle_items', 'belongings'),
        'visible_damage' => m360_rw_pick([$condition, $payload], 'visible_damage', 'body_damage'),
        'initial_vehicle_condition' => m360_rw_pick([$condition, $payload], 'initial_vehicle_condition'),
        'service_route' => $serviceRoute,
        'service_primary' => $serviceRoute,
        'service_path_clear' => $pathClear,
        'service_path_note' => $canonical['service_path_note'],
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
        'customer_request_type' => trim((string)m360_rw_pick([$request, $payload], 'request_type', 'customer_request_type')),
        'initial_symptoms' => trim((string)m360_rw_pick([$payload], 'initial_symptoms', 'service_path_note')),
        'service_reception_confirmed' => !empty($service['policy_group']) || $serviceRoute !== '' ? '1' : '',
        'service_diag_sub_codes' => $diagSubs,
        'plate_left_2_digits' => $plateLeft,
        'plate_letter' => $plateLetter,
        'plate_middle_3_digits' => $plateMid,
        'plate_iran_2_digits' => $plateIran,
        'plate_region_2_digits' => $plateIran,
        'vehicle_year_pair' => m360_rw_pick([$vehicleMeta, $payload], 'vehicle_year_pair'),
        'visit_date' => $visitDate,
        'visit_date_display' => $visitDisplay,
        'brand_other_explanation' => (string)($vehicleMeta['brand_other_explanation'] ?? ''),
        'model_other_explanation' => (string)($vehicleMeta['model_other_explanation'] ?? ''),
        'brand_status' => (string)($vehicleMeta['brand_status'] ?? ''),
        'model_status' => (string)($vehicleMeta['model_status'] ?? ''),
        'hall_manager_status' => (string)($payload['reception_intake']['hall_manager']['status'] ?? ''),
        'hall_manager_note' => (string)($payload['reception_intake']['hall_manager']['note'] ?? ''),
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

function m360_rw_intake_save_redirect_url(int $requestId, string $message, bool $ok, array $post = [], $conn = false): string
{
    $redirect = m360_rw_intake_redirect_active_step(is_resource($conn) ? $conn : false, $requestId, trim((string)($post['action_type'] ?? '')), $ok, $post);
    $activeStep = (string)($redirect['active_step'] ?? 'otp');
    $hash = (string)($redirect['hash'] ?? m360_rw_intake_step_hash($activeStep));
    $extra = is_array($redirect['extra'] ?? null) ? $redirect['extra'] : [];

    $url = 'erp-reception-intake-file.php?online_request_id=' . $requestId
        . '&active_step=' . rawurlencode($activeStep)
        . '&msg=' . rawurlencode($message)
        . '&ok=' . ($ok ? '1' : '0');

    foreach ($extra as $key => $val) {
        if ($val === '' || $val === null) {
            continue;
        }
        $url .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((string)$val);
    }

    if (!$ok && !isset($extra['edit_section'])) {
        $sectionKey = m360_rw_intake_step_primary_section($activeStep);
        if ($sectionKey !== '') {
            $url .= '&edit_section=' . rawurlencode($sectionKey);
        }
    }

    if ($hash !== '') {
        $url .= '#' . $hash;
    }

    return $url;
}

/**
 * @param array<string, mixed> $payload
 */
function m360_rw_intake_is_locked(array $payload): bool
{
    $lock = $payload['reception_intake']['intake_lock'] ?? null;

    return is_array($lock) && strtolower(trim((string)($lock['status'] ?? ''))) === 'locked';
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string}
 */
function m360_rw_intake_assert_not_locked(array $payload, string $actionType): array
{
    if (!m360_rw_intake_is_locked($payload)) {
        return ['ok' => true, 'message' => ''];
    }

    return ['ok' => false, 'message' => M360_RW_INTAKE_LOCK_MESSAGE_FA];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $request
 * @param array<string, string> $formValues
 * @return array<string, mixed>
 */
function m360_rw_intake_build_locked_snapshot(array $payload, array $request, array $formValues): array
{
    return [
        'customer' => [
            'name' => trim((string)($request['customer_name'] ?? m360_rw_pick([$payload], 'customer_name'))),
            'request_type' => trim((string)($request['request_type'] ?? '')),
        ],
        'mobile' => m360_rw_intake_resolve_mobile_for_otp($request, $payload),
        'vehicle' => [
            'plate' => (string)($formValues['plate'] ?? ''),
            'brand' => (string)($formValues['brand'] ?? ''),
            'model' => (string)($formValues['model'] ?? ''),
            'mileage' => (string)($formValues['mileage'] ?? ''),
            'fuel_level' => (string)($formValues['fuel_level'] ?? ''),
            'vin' => (string)($formValues['vin'] ?? ''),
        ],
        'condition' => [
            'belongings' => (string)($formValues['vehicle_items'] ?? ''),
            'visible_damage' => (string)($formValues['visible_damage'] ?? ''),
            'initial_condition' => (string)($formValues['initial_vehicle_condition'] ?? ''),
        ],
        'service_classification' => [
            'primary' => (string)($formValues['service_primary'] ?? ''),
            'service_path_clear' => (string)($formValues['service_path_clear'] ?? ''),
            'service_path_note' => (string)($formValues['service_path_note'] ?? ''),
        ],
        'referral' => [
            'team_id' => (string)($formValues['referral_team_id'] ?? ''),
            'referral_type' => (string)($formValues['referral_type'] ?? ''),
            'note' => (string)($formValues['referral_note'] ?? ''),
        ],
        'documents' => [
            'diagnostic_pdf' => !empty($payload['reception_intake']['documents']['diagnostic_pdf']),
            'contract_status' => (string)($formValues['contract_status'] ?? ''),
            'cost_agreement' => (string)($formValues['cost_agreement'] ?? ''),
        ],
        'photos' => m360_rw_intake_photos_canonical($payload),
        'signature' => $payload['reception_intake']['customer_signature'] ?? [],
    ];
}

/**
 * @param array<string, string> $post
 * @return array{ok:bool,message:string,history_written:bool}
 */
function m360_rw_intake_process_sign_and_lock($conn, int $requestId, array $request, array $post): array
{
    if (!is_resource($conn) || $requestId < 1) {
        return ['ok' => false, 'message' => 'درخواست نامعتبر است.', 'history_written' => false];
    }

    $existing = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
    $existing = m360_rw_intake_ensure_nested($existing);
    if (m360_rw_intake_is_locked($existing)) {
        return ['ok' => false, 'message' => M360_RW_INTAKE_LOCK_MESSAGE_FA, 'history_written' => false];
    }

    $formValues = m360_rw_intake_form_values($existing, $request);
    $customerApproved = isset($post['customer_intake_approved']) && (string)$post['customer_intake_approved'] === '1';
    $receptionConfirmed = isset($post['confirmed_by_receptionist']) && (string)$post['confirmed_by_receptionist'] === '1';
    if (!$customerApproved) {
        return ['ok' => false, 'message' => 'تأیید صریح مشتری برای اطلاعات پذیرش الزامی است.', 'history_written' => false];
    }
    if (!$receptionConfirmed) {
        return ['ok' => false, 'message' => 'تأیید نهایی پذیرشگر الزامی است.', 'history_written' => false];
    }

    foreach (m360_rw_intake_reception_completion_keys() as $stepKey) {
        if (!m360_rw_intake_wizard_step_is_complete($stepKey, $existing, $request, $formValues)) {
            return ['ok' => false, 'message' => 'تکمیل مراحل پذیرش قبل از امضا و قفل الزامی است.', 'history_written' => false];
        }
    }

    if (($formValues['service_path_clear'] ?? '') !== '1') {
        return [
            'ok' => false,
            'message' => 'پرونده در پذیرش موقت باقی می‌ماند تا مسیر عیب/خدمت روشن شود.',
            'history_written' => false,
        ];
    }

    $now = gmdate('Y-m-d\TH:i:s\Z');
    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
    $customerName = trim((string)($post['customer_signature_name'] ?? ''));
    if ($customerName === '') {
        $customerName = trim((string)($request['customer_name'] ?? ''));
    }

    $existing['reception_intake']['customer_signature'] = [
        'status' => 'signed',
        'signed_at' => $now,
        'customer_name' => $customerName,
        'mobile' => m360_rw_intake_resolve_mobile_for_otp($request, $existing),
        'approved_intake_summary' => true,
    ];
    $existing['reception_intake']['receptionist_final_confirmation'] = [
        'confirmed' => true,
        'confirmed_at' => $now,
        'confirmed_by' => (string)$userId,
    ];
    $existing['reception_final_confirmation'] = '1';
    $existing['reception_intake']['reception_confirmation'] = [
        'confirmed_by_receptionist' => true,
        'confirmation_note' => trim((string)($post['confirmation_note'] ?? '')),
        'confirmed_at' => $now,
    ];
    m360_rw_intake_mark_section_saved($existing, 'reception_confirmation');

    $existing['reception_intake']['intake_lock'] = [
        'status' => 'locked',
        'locked_at' => $now,
        'locked_by' => (string)$userId,
        'reason' => 'customer_signed_and_reception_confirmed',
    ];
    $existing['reception_intake']['locked_snapshot'] = m360_rw_intake_build_locked_snapshot($existing, $request, $formValues);

    $persist = m360_rw_intake_persist_payload($conn, $requestId, $existing, []);
    if (!$persist['ok']) {
        return ['ok' => false, 'message' => $persist['message'], 'history_written' => false];
    }

    $historyWritten = m360_online_req_write_history(
        $conn,
        $requestId,
        M360_RW_INTAKE_HISTORY_PREFIX . 'SIGN_AND_LOCK',
        (string)($request['request_status'] ?? ''),
        (string)($request['request_status'] ?? ''),
        'Reception intake signed and locked',
        $userId
    );

    return ['ok' => true, 'message' => 'پرونده پذیرش با امضای مشتری قفل شد.', 'history_written' => $historyWritten];
}

/** @return list<string> */
function m360_rw_intake_reception_completion_keys(): array
{
    return ['otp', 'vehicle', 'condition', 'service', 'photos'];
}

/** @return list<string> */
function m360_rw_intake_post_reception_operation_keys(): array
{
    return ['documents', 'signature', 'referral'];
}

/** @return list<string> */
function m360_rw_intake_wizard_operational_keys(): array
{
    return array_merge(
        m360_rw_intake_reception_completion_keys(),
        m360_rw_intake_post_reception_operation_keys()
    );
}

const M360_RW_INTAKE_CONTRACT_SMS_TEXT_FA = 'لطفاً قرارداد خودروی خود را در سامانه مقاره موتورز امضا فرمایید.';
const M360_RW_INTAKE_CARTABLE_TASK_TITLE_FA = 'قرارداد نیازمند امضا';
const M360_RW_INTAKE_CARTABLE_TASK_MESSAGE_FA = 'قرارداد پذیرش خودروی شما آماده بررسی و امضا است.';
const M360_RW_INTAKE_CARTABLE_TASK_STATUS_ACTION_FA = 'نیازمند اقدام';
const M360_RW_INTAKE_CARTABLE_TASK_STATUS_COMPLETED_FA = 'امضاشده';
const M360_RW_INTAKE_CARTABLE_TASK_ACTION_LABEL_FA = 'بررسی و امضای قرارداد';

const M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_SIGNED_CANONICAL = 'SIGNED_CANONICAL';
const M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_PENDING_SIGNATURE = 'PENDING_SIGNATURE';
const M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_LEGACY_ACCEPTED_UNVERIFIED = 'LEGACY_ACCEPTED_UNVERIFIED';
const M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_NO_CONTRACT = 'NO_CONTRACT';

const M360_RW_CUSTOMER_PROFILE_CONTRACT_HISTORY_TITLE_FA = 'سوابق قرارداد پذیرش';
const M360_RW_CUSTOMER_PROFILE_CONTRACT_LEGACY_STATUS_FA = 'تأیید قدیمی فاقد امضای دیجیتال';
const M360_RW_CUSTOMER_PROFILE_CONTRACT_LEGACY_MESSAGE_FA = 'این تأیید از مسیر قدیمی ثبت شده و دارای قرارداد قفل‌شده، امضای دیجیتال و OTP قرارداد نیست.';
const M360_RW_CUSTOMER_PROFILE_CONTRACT_SIGNED_STATUS_FA = 'امضاشده و قفل‌شده';
const M360_RW_CUSTOMER_PROFILE_CONTRACT_SIGNED_MESSAGE_FA = 'قرارداد با امضای دیجیتال و تأیید OTP ثبت و قفل شده است.';

/** @var array<string, int> */
$GLOBALS['m360_rw_customer_dashboard_perf'] = [];

function m360_rw_customer_dashboard_perf_reset(): void
{
    $GLOBALS['m360_rw_customer_dashboard_perf'] = [
        'sql_statements' => 0,
        'payload_hydrations' => 0,
        'task_queries' => 0,
        'request_fetches' => 0,
        'vehicle_queries' => 0,
    ];
}

function m360_rw_customer_dashboard_perf_inc(string $key, int $amount = 1): void
{
    if (!isset($GLOBALS['m360_rw_customer_dashboard_perf'][$key])) {
        $GLOBALS['m360_rw_customer_dashboard_perf'][$key] = 0;
    }
    $GLOBALS['m360_rw_customer_dashboard_perf'][$key] += $amount;
}

/** @return array<string, int|float> */
function m360_rw_customer_dashboard_perf_snapshot(float $startedAt): array
{
    $perf = is_array($GLOBALS['m360_rw_customer_dashboard_perf'] ?? null)
        ? $GLOBALS['m360_rw_customer_dashboard_perf']
        : [];

    return array_merge($perf, [
        'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
    ]);
}

function m360_rw_customer_portal_app_root_web_path(): string
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_contains($script, '/api/customer/')) {
        $root = dirname($script, 3);
    } else {
        $root = dirname($script);
    }
    if ($root === '/' || $root === '.' || $root === '') {
        return '';
    }

    return rtrim($root, '/');
}

function m360_rw_customer_portal_app_root_url(string $pathAndQuery): string
{
    $path = str_starts_with($pathAndQuery, '/') ? $pathAndQuery : '/' . ltrim($pathAndQuery, '/');
    $root = m360_rw_customer_portal_app_root_web_path();

    return $root . $path;
}

/** @return list<string> */
function m360_rw_customer_dashboard_terminal_statuses(): array
{
    return ['DELIVERED', 'CLOSED', 'DONE', 'CANCELLED', 'REJECTED'];
}

function m360_rw_customer_dashboard_safe_text(string $value, string $fallback = '—'): string
{
    $value = trim($value);
    if ($value === '') {
        return $fallback;
    }
    if (str_contains($value, 'Ã') || str_contains($value, 'Ø') || str_contains($value, 'Ù') || str_contains($value, 'â')) {
        return $fallback;
    }

    return $value;
}

/**
 * @param array<string, mixed> $requestRow
 * @param array<string, mixed> $payload
 * @return array{display_name:string,plate:string,service_type:string}
 */
function m360_rw_customer_dashboard_extract_vehicle_summary(array $requestRow, array $payload): array
{
    $payload = m360_rw_intake_payload_for_recovery($payload);
    $vehicle = is_array($payload['reception_intake']['vehicle'] ?? null) ? $payload['reception_intake']['vehicle'] : [];
    $brand = m360_rw_customer_dashboard_safe_text((string)($vehicle['brand'] ?? $payload['vehicle_brand'] ?? $payload['brand'] ?? ''), '');
    $model = m360_rw_customer_dashboard_safe_text((string)($vehicle['model'] ?? $vehicle['class'] ?? $payload['vehicle_class'] ?? $payload['model'] ?? ''), '');
    $plate = m360_rw_customer_dashboard_safe_text((string)($vehicle['plate'] ?? $payload['vehicle_plate'] ?? $payload['plate_display'] ?? $requestRow['vehicle_plate'] ?? ''), '');
    $display = trim($brand . ' ' . $model);
    if ($display === '') {
        $display = 'خودرو';
    }
    $requestType = trim((string)($payload['request_type'] ?? ''));
    $serviceMap = [
        'diagnostic_inspection' => 'کارشناسی و عیب‌یابی',
        'buy_sell_inspection' => 'کارشناسی خرید/فروش',
        'periodic_service' => 'سرویس دوره‌ای',
        'option_add' => 'افزودن آپشن',
        'other' => 'سایر',
    ];

    return [
        'display_name' => $display,
        'plate' => $plate !== '—' ? $plate : '',
        'service_type' => $serviceMap[$requestType] ?? m360_rw_customer_dashboard_safe_text((string)($payload['service_description'] ?? ''), 'درخواست خدمت'),
    ];
}

/**
 * @param array<string, mixed> $requestRow
 * @param array<string, mixed> $payload
 * @return array{stage:string,stage_label:string,status_label:string,next_actor:string,next_action:string}
 */
function m360_rw_customer_dashboard_resolve_stage($conn, array $requestRow, array $payload, string $customerMobile): array
{
    $requestId = (int)($requestRow['online_request_id'] ?? 0);
    $status = strtoupper(trim((string)($requestRow['request_status'] ?? 'NEW')));
    $normalized = m360_rw_customer_profile_normalize_mobile($customerMobile);
    $dbContract = is_resource($conn) && $requestId > 0
        ? m360_intake_contract_find_active_for_online_request($conn, $requestId)
        : null;

    if (is_resource($conn) && m360_rw_customer_profile_contract_is_pending_signature($conn, $requestRow, $payload, $dbContract, $normalized)) {
        return [
            'stage' => 'CONTRACT_PENDING',
            'stage_label' => 'در انتظار بررسی و امضای قرارداد',
            'status_label' => 'نیازمند امضای قرارداد',
            'next_actor' => 'شما',
            'next_action' => 'بررسی و امضای قرارداد',
        ];
    }

    if (is_resource($conn) && m360_rw_customer_profile_contract_is_canonically_signed($conn, $requestRow, $dbContract, $normalized)) {
        return [
            'stage' => 'IN_SERVICE',
            'stage_label' => 'در حال انجام خدمات',
            'status_label' => 'قرارداد امضاشده — ادامه فرایند',
            'next_actor' => 'واحد پذیرش',
            'next_action' => 'پیگیری پرونده',
        ];
    }

    $map = [
        'NEW' => ['stage' => 'NEW', 'stage_label' => 'در انتظار بررسی پذیرش', 'status_label' => 'در انتظار بررسی پذیرش', 'next_actor' => 'واحد پذیرش', 'next_action' => 'بررسی درخواست'],
        'PENDING' => ['stage' => 'NEW', 'stage_label' => 'در انتظار بررسی پذیرش', 'status_label' => 'در انتظار بررسی پذیرش', 'next_actor' => 'واحد پذیرش', 'next_action' => 'بررسی درخواست'],
        'UNDER_REVIEW' => ['stage' => 'UNDER_REVIEW', 'stage_label' => 'در حال بررسی پذیرش', 'status_label' => 'در حال بررسی پذیرش', 'next_actor' => 'واحد پذیرش', 'next_action' => 'تکمیل بررسی'],
        'ACCEPTED' => ['stage' => 'RECEPTION_IN_PROGRESS', 'stage_label' => 'در حال تکمیل پذیرش', 'status_label' => 'پذیرفته‌شده', 'next_actor' => 'واحد پذیرش', 'next_action' => 'تکمیل پذیرش'],
        'CONVERTED_TO_JOBCARD' => ['stage' => 'IN_SERVICE', 'stage_label' => 'در حال انجام خدمات', 'status_label' => 'تبدیل به کارت کار', 'next_actor' => 'واحد فنی', 'next_action' => 'پیگیری خدمات'],
    ];
    if (isset($map[$status])) {
        return $map[$status];
    }

    return [
        'stage' => 'UNKNOWN',
        'stage_label' => 'در حال بررسی',
        'status_label' => m360_online_req_status_label_fa($status !== '' ? $status : 'NEW'),
        'next_actor' => 'واحد پذیرش',
        'next_action' => 'پیگیری پرونده',
    ];
}

/**
 * @param array<string, mixed> $requestRow
 * @return array<string, mixed>
 */
function m360_rw_customer_dashboard_format_request_card($conn, array $requestRow, string $customerMobile): array
{
    m360_rw_customer_dashboard_perf_inc('payload_hydrations');
    $requestId = (int)($requestRow['online_request_id'] ?? 0);
    $requestMobile = m360_rw_customer_profile_normalize_mobile((string)($requestRow['mobile'] ?? ''));
    $normalized = m360_rw_customer_profile_normalize_mobile($customerMobile);
    if ($requestId < 1 || $normalized === '' || $requestMobile !== $normalized) {
        return [];
    }

    $payload = m360_online_req_parse_payload($requestRow['request_payload_json'] ?? null);
    $payload = m360_rw_intake_payload_for_recovery($payload);
    $vehicle = m360_rw_customer_dashboard_extract_vehicle_summary($requestRow, $payload);
    $stage = m360_rw_customer_dashboard_resolve_stage($conn, $requestRow, $payload, $customerMobile);
    $status = strtoupper(trim((string)($requestRow['request_status'] ?? 'NEW')));
    $terminal = m360_rw_customer_dashboard_terminal_statuses();
    $contractClass = m360_rw_customer_profile_classify_contract_request($conn, $requestRow, $customerMobile);
    $updatedAt = trim((string)($requestRow['updated_at'] ?? $requestRow['created_at'] ?? ''));

    $actionUrl = '';
    $actionLabel = '';
    if ($contractClass['classification'] === M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_PENDING_SIGNATURE) {
        $readonlyUrl = m360_rw_customer_profile_contract_review_url_readonly($payload);
        if ($readonlyUrl !== '') {
            $actionUrl = $readonlyUrl;
            $actionLabel = M360_RW_INTAKE_CARTABLE_TASK_ACTION_LABEL_FA;
        }
    }

    $legacyLabel = '';
    if ($contractClass['classification'] === M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_LEGACY_ACCEPTED_UNVERIFIED) {
        $legacyLabel = M360_RW_CUSTOMER_PROFILE_CONTRACT_LEGACY_STATUS_FA;
    } elseif ($contractClass['classification'] === M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_SIGNED_CANONICAL) {
        $legacyLabel = M360_RW_CUSTOMER_PROFILE_CONTRACT_SIGNED_STATUS_FA;
    }

    return [
        'online_request_id' => $requestId,
        'reference' => m360_rw_customer_profile_contract_jobcard_code($requestId),
        'vehicle_display' => $vehicle['display_name'],
        'vehicle_plate' => $vehicle['plate'],
        'service_type' => $vehicle['service_type'],
        'stage' => $stage['stage'],
        'stage_label' => $stage['stage_label'],
        'status_label' => $stage['status_label'],
        'next_actor' => $stage['next_actor'],
        'next_action' => $stage['next_action'],
        'last_update' => $updatedAt !== '' ? $updatedAt : '—',
        'is_terminal' => in_array($status, $terminal, true) || $status === M360_ONLINE_REQ_STATUS_REJECTED,
        'terminal_status' => in_array($status, $terminal, true) ? $status : '',
        'contract_legacy_label' => $legacyLabel,
        'contract_classification' => $contractClass['classification'],
        'action_url' => $actionUrl,
        'action_label' => $actionLabel,
    ];
}

/**
 * @param list<array<string, mixed>> $activeCases
 * @return list<array<string, mixed>>
 */
function m360_rw_customer_dashboard_list_vehicles_enriched($conn, int $customerId, string $customerMobile, array $activeCases): array
{
    m360_rw_customer_dashboard_perf_inc('vehicle_queries');
    $vehicles = $customerId > 0 ? m360_rw_customer_profile_list_vehicles($conn, $customerId) : [];
    $counts = [];
    foreach ($activeCases as $case) {
        $plate = trim((string)($case['vehicle_plate'] ?? ''));
        $key = $plate !== '' ? $plate : (string)($case['vehicle_display'] ?? 'unknown');
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }
    $out = [];
    foreach ($vehicles as $vehicle) {
        $label = m360_rw_customer_dashboard_safe_text((string)($vehicle['label'] ?? ''), 'خودرو');
        $plate = m360_rw_customer_dashboard_safe_text((string)($vehicle['plate'] ?? ''), '');
        $key = $plate !== '' && $plate !== '—' ? $plate : $label;
        $out[] = [
            'label' => $label,
            'plate' => $plate !== '—' ? $plate : '',
            'active_case_count' => (int)($counts[$key] ?? 0),
            'last_service' => '—',
            'status_label' => ((int)($counts[$key] ?? 0)) > 0 ? 'دارای پرونده فعال' : 'بدون پرونده فعال',
        ];
    }
    if ($out === [] && $activeCases !== []) {
        foreach ($activeCases as $case) {
            $out[] = [
                'label' => (string)($case['vehicle_display'] ?? 'خودرو'),
                'plate' => (string)($case['vehicle_plate'] ?? ''),
                'active_case_count' => 1,
                'last_service' => (string)($case['service_type'] ?? '—'),
                'status_label' => (string)($case['stage_label'] ?? 'در حال بررسی'),
            ];
        }
    }

    return $out;
}

/**
 * @param array<string, string> $query
 * @return array<string, mixed>
 */
function m360_rw_customer_dashboard_build($conn, string $verifiedMobile, array $query = []): array
{
    $started = microtime(true);
    m360_rw_customer_dashboard_perf_reset();
    m360_rw_customer_dashboard_perf_inc('sql_statements');

    $identity = m360_rw_customer_profile_resolve_display_identity($conn, $verifiedMobile);
    $customerId = (int)($identity['customer_id'] ?? 0);
    $customerRow = m360_rw_customer_profile_fetch_customer_row($conn, $verifiedMobile);
    $customer = m360_rw_customer_profile_normalize_customer_row($customerRow);

    m360_rw_customer_dashboard_perf_inc('request_fetches');
    $requests = m360_rw_customer_profile_fetch_online_requests_by_mobile($conn, $verifiedMobile);
    $activeCases = [];
    $historyCases = [];
    foreach ($requests as $row) {
        $card = m360_rw_customer_dashboard_format_request_card($conn, $row, $verifiedMobile);
        if ($card === []) {
            continue;
        }
        if (!empty($card['is_terminal'])) {
            $historyCases[] = $card;
        } else {
            $activeCases[] = $card;
        }
    }

    m360_rw_customer_dashboard_perf_inc('task_queries');
    $inbox = [];
    if (function_exists('m360_cartable_list_dashboard_inbox')) {
        $inbox = m360_cartable_list_dashboard_inbox($conn, $customerId, $verifiedMobile);
    } else {
        $tasks = m360_rw_customer_profile_canonical_active_contract_tasks($conn, $customerId, $verifiedMobile);
        foreach ($tasks as $taskRow) {
            $formatted = m360_rw_customer_profile_format_canonical_active_contract_task($conn, $customerId, $verifiedMobile, $taskRow);
            if ($formatted === null) {
                continue;
            }
            $inbox[] = [
                'task_id' => (int)($taskRow['task_id'] ?? 0),
                'title' => (string)($formatted['title'] ?? ''),
                'message' => (string)($formatted['message'] ?? ''),
                'priority' => (int)($taskRow['priority'] ?? 50),
                'status_label' => (string)($formatted['status_label'] ?? ''),
                'context' => (string)($formatted['jobcard_code'] ?? ''),
                'action_label' => (string)($formatted['action_label'] ?? ''),
                'action_url' => (string)($formatted['review_url'] ?? ''),
            ];
        }
    }

    $historyPage = max(1, (int)($query['history_page'] ?? 1));
    $perPage = 20;
    $historyTotal = count($historyCases);
    $historyPages = max(1, (int)ceil($historyTotal / $perPage));
    if ($historyPage > $historyPages) {
        $historyPage = $historyPages;
    }
    $historySlice = array_slice($historyCases, ($historyPage - 1) * $perPage, $perPage);

    $vehicles = m360_rw_customer_dashboard_list_vehicles_enriched($conn, $customerId, $verifiedMobile, $activeCases);

    $banners = [];
    if ((string)($query['contract_signed'] ?? '') === '1') {
        $banners[] = [
            'id' => 'contract_signed',
            'title' => 'قرارداد با موفقیت امضا و تأیید شد.',
            'message' => 'پرونده شما برای ادامه فرایند پذیرش ارسال شده است.',
        ];
    }
    if ((string)($query['request_created'] ?? '') === '1') {
        $banners[] = [
            'id' => 'request_created',
            'title' => 'درخواست خدمت با موفقیت ثبت شد.',
            'message' => 'پرونده جدید شما در بخش پرونده‌های در جریان قابل پیگیری است.',
        ];
    }

    return [
        'identity' => $identity,
        'customer' => $customer,
        'banners' => $banners,
        'inbox' => $inbox,
        'active_task_count' => count($inbox),
        'active_cases' => $activeCases,
        'history_cases' => $historySlice,
        'history_page' => $historyPage,
        'history_total' => $historyTotal,
        'history_pages' => $historyPages,
        'vehicles' => $vehicles,
        'financial' => [
            'available' => false,
            'open_invoice_count' => 0,
            'outstanding_balance' => null,
            'message' => 'صورتحساب باز یا پرداخت معوقی برای شما ثبت نشده است.',
        ],
        'notifications' => [
            'available' => false,
            'message' => 'پیام یا اعلان جدیدی ثبت نشده است.',
        ],
        'metrics' => m360_rw_customer_dashboard_perf_snapshot($started),
    ];
}

function m360_rw_customer_profile_contract_jobcard_code(int $requestId): string
{
    return 'REQ-' . (string)$requestId;
}

function m360_rw_customer_profile_contract_has_signature_proof($conn, int $contractId): bool
{
    if (!is_resource($conn) || $contractId < 1) {
        return false;
    }
    if (!m360_rw_table_exists($conn, M360_CONTRACT_SIG_TABLE)) {
        return false;
    }
    $sql = 'SELECT TOP 1 contract_id FROM dbo.' . M360_CONTRACT_SIG_TABLE . ' WHERE contract_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$contractId])) {
        return false;
    }

    return odbc_fetch_array($stmt) !== false;
}

/**
 * @param array<string, mixed> $requestRow
 * @param array<string, mixed>|null $dbContract
 */
function m360_rw_customer_profile_contract_binding_valid(array $requestRow, ?array $dbContract, string $normalizedMobile): bool
{
    if ($dbContract === null) {
        return false;
    }
    $requestId = (int)($requestRow['online_request_id'] ?? 0);
    $contractRequestId = (int)($dbContract['online_request_id'] ?? 0);
    if ($requestId < 1 || $contractRequestId !== $requestId) {
        return false;
    }
    $requestMobile = m360_rw_customer_profile_normalize_mobile((string)($requestRow['mobile'] ?? ''));
    if ($requestMobile !== $normalizedMobile) {
        return false;
    }
    $contractMobile = m360_rw_customer_profile_normalize_mobile((string)($dbContract['mobile'] ?? ''));
    if ($contractMobile !== '' && $contractMobile !== $normalizedMobile) {
        return false;
    }

    return true;
}

/**
 * @param array<string, mixed> $requestRow
 * @param array<string, mixed>|null $dbContract
 */
function m360_rw_customer_profile_contract_is_canonically_signed($conn, array $requestRow, ?array $dbContract, string $normalizedMobile): bool
{
    if ($dbContract === null) {
        return false;
    }
    $contractId = (int)($dbContract['contract_id'] ?? 0);
    if ($contractId < 1) {
        return false;
    }
    if (!m360_rw_customer_profile_contract_binding_valid($requestRow, $dbContract, $normalizedMobile)) {
        return false;
    }
    if (!m360_intake_contract_is_signed($dbContract)) {
        return false;
    }

    return m360_rw_customer_profile_contract_has_signature_proof($conn, $contractId);
}

/**
 * @param array<string, mixed> $payload
 */
function m360_rw_customer_profile_contract_review_url_readonly(array $payload): string
{
    $task = m360_rw_intake_contract_cartable_task($payload);
    $reviewPath = trim((string)($task['review_url_path'] ?? ''));
    if ($reviewPath !== '' && str_contains($reviewPath, 'customer-intake-contract-review.php')) {
        return $reviewPath;
    }

    return '';
}

/**
 * @param array<string, mixed> $requestRow
 * @param array<string, mixed> $payload
 * @param array<string, mixed>|null $dbContract
 */
function m360_rw_customer_profile_contract_is_pending_signature($conn, array $requestRow, array $payload, ?array $dbContract, string $normalizedMobile): bool
{
    if ($dbContract === null) {
        return false;
    }
    if ((int)($dbContract['contract_id'] ?? 0) < 1 || m360_intake_contract_is_signed($dbContract)) {
        return false;
    }
    if (!m360_rw_customer_profile_contract_binding_valid($requestRow, $dbContract, $normalizedMobile)) {
        return false;
    }
    $task = m360_rw_intake_contract_cartable_task($payload);
    $taskMobile = m360_rw_customer_profile_normalize_mobile((string)($task['customer_mobile'] ?? ''));
    if ($taskMobile !== '' && $taskMobile !== $normalizedMobile) {
        return false;
    }
    if (m360_rw_intake_contract_customer_accepted($payload)) {
        return false;
    }
    if (!m360_rw_intake_contract_cartable_pending($payload)) {
        $taskStatus = trim((string)($task['status'] ?? ''));
        if ($taskStatus !== M360_RW_INTAKE_CARTABLE_STATUS_PENDING) {
            return false;
        }
    }

    return m360_rw_customer_profile_contract_review_url_readonly($payload) !== '';
}

/**
 * @param array<string, mixed> $requestRow
 * @return array{
 *   classification:string,
 *   online_request_id:int,
 *   jobcard_code:string,
 *   title:string,
 *   message:string,
 *   status_label:string,
 *   action_label:string,
 *   review_url:string,
 *   is_active:bool,
 *   is_completed:bool,
 *   is_legacy:bool,
 *   counts_toward_badge:bool
 * }
 */
function m360_rw_customer_profile_classify_contract_request($conn, array $requestRow, string $customerMobile): array
{
    $empty = [
        'classification' => M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_NO_CONTRACT,
        'online_request_id' => 0,
        'jobcard_code' => '',
        'title' => '',
        'message' => '',
        'status_label' => '',
        'action_label' => '',
        'review_url' => '',
        'is_active' => false,
        'is_completed' => false,
        'is_legacy' => false,
        'counts_toward_badge' => false,
    ];
    $requestId = (int)($requestRow['online_request_id'] ?? 0);
    if ($requestId < 1) {
        return $empty;
    }
    $normalizedCustomer = m360_rw_customer_profile_normalize_mobile($customerMobile);
    $requestMobile = m360_rw_customer_profile_normalize_mobile((string)($requestRow['mobile'] ?? ''));
    if ($normalizedCustomer === '' || $requestMobile !== $normalizedCustomer) {
        return $empty;
    }

    $payload = m360_online_req_parse_payload($requestRow['request_payload_json'] ?? null);
    $payload = m360_rw_intake_payload_for_recovery($payload);
    $dbContract = is_resource($conn) ? m360_intake_contract_find_active_for_online_request($conn, $requestId) : null;
    $jobcardCode = m360_rw_customer_profile_contract_jobcard_code($requestId);

    if (is_resource($conn) && m360_rw_customer_profile_contract_is_canonically_signed($conn, $requestRow, $dbContract, $normalizedCustomer)) {
        return [
            'classification' => M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_SIGNED_CANONICAL,
            'online_request_id' => $requestId,
            'jobcard_code' => $jobcardCode,
            'title' => M360_RW_CUSTOMER_PROFILE_CONTRACT_HISTORY_TITLE_FA,
            'message' => M360_RW_CUSTOMER_PROFILE_CONTRACT_SIGNED_MESSAGE_FA,
            'status_label' => M360_RW_CUSTOMER_PROFILE_CONTRACT_SIGNED_STATUS_FA,
            'action_label' => '',
            'review_url' => '',
            'is_active' => false,
            'is_completed' => true,
            'is_legacy' => false,
            'counts_toward_badge' => false,
        ];
    }

    if (m360_rw_intake_contract_customer_accepted($payload)) {
        return [
            'classification' => M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_LEGACY_ACCEPTED_UNVERIFIED,
            'online_request_id' => $requestId,
            'jobcard_code' => $jobcardCode,
            'title' => M360_RW_CUSTOMER_PROFILE_CONTRACT_HISTORY_TITLE_FA,
            'message' => M360_RW_CUSTOMER_PROFILE_CONTRACT_LEGACY_MESSAGE_FA,
            'status_label' => M360_RW_CUSTOMER_PROFILE_CONTRACT_LEGACY_STATUS_FA,
            'action_label' => '',
            'review_url' => '',
            'is_active' => false,
            'is_completed' => true,
            'is_legacy' => true,
            'counts_toward_badge' => false,
        ];
    }

    if (is_resource($conn) && m360_rw_customer_profile_contract_is_pending_signature($conn, $requestRow, $payload, $dbContract, $normalizedCustomer)) {
        $task = m360_rw_intake_contract_cartable_task($payload);

        return [
            'classification' => M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_PENDING_SIGNATURE,
            'online_request_id' => $requestId,
            'jobcard_code' => $jobcardCode,
            'title' => trim((string)($task['title'] ?? '')) !== '' ? (string)$task['title'] : M360_RW_INTAKE_CARTABLE_TASK_TITLE_FA,
            'message' => trim((string)($task['message'] ?? '')) !== '' ? (string)$task['message'] : M360_RW_INTAKE_CARTABLE_TASK_MESSAGE_FA,
            'status_label' => M360_RW_INTAKE_CARTABLE_TASK_STATUS_ACTION_FA,
            'action_label' => M360_RW_INTAKE_CARTABLE_TASK_ACTION_LABEL_FA,
            'review_url' => m360_rw_customer_profile_contract_review_url_readonly($payload),
            'is_active' => true,
            'is_completed' => false,
            'is_legacy' => false,
            'counts_toward_badge' => true,
        ];
    }

    return array_merge($empty, [
        'online_request_id' => $requestId,
        'jobcard_code' => $jobcardCode,
    ]);
}

function m360_rw_customer_profile_normalize_mobile(string $mobile): string
{
    $mobile = trim($mobile);
    if ($mobile === '') {
        return '';
    }
    $otpHelper = __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
    if (is_file($otpHelper)) {
        require_once $otpHelper;
        if (function_exists('m360_otp_normalize_phone')) {
            $normalized = m360_otp_normalize_phone($mobile);
            if ($normalized !== null && $normalized !== '') {
                return $normalized;
            }
        }
    }

    return preg_replace('/\D+/', '', $mobile) ?? '';
}

/**
 * @return list<array<string, mixed>>
 */
function m360_rw_customer_profile_fetch_online_requests_by_mobile($conn, string $customerMobile): array
{
    if (!is_resource($conn) || $customerMobile === '') {
        return [];
    }
    if (!m360_rw_table_exists($conn, m360_online_req_table())) {
        return [];
    }
    $normalized = m360_rw_customer_profile_normalize_mobile($customerMobile);
    if ($normalized === '') {
        return [];
    }
    $sql = 'SELECT online_request_id, mobile
            FROM dbo.' . m360_online_req_table() . '
            WHERE mobile IS NOT NULL AND LTRIM(RTRIM(mobile)) <> \'\'
            ORDER BY online_request_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt)) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $rowMobile = m360_rw_customer_profile_normalize_mobile((string)($row['mobile'] ?? $row['MOBILE'] ?? ''));
        if ($rowMobile === '' || $rowMobile !== $normalized) {
            continue;
        }
        $requestId = (int)($row['online_request_id'] ?? $row['ONLINE_REQUEST_ID'] ?? 0);
        if ($requestId < 1) {
            continue;
        }
        $fullRow = m360_online_req_fetch_by_id($conn, $requestId);
        if ($fullRow === null) {
            continue;
        }
        $fullMobile = m360_rw_customer_profile_normalize_mobile((string)($fullRow['mobile'] ?? ''));
        if ($fullMobile !== $normalized) {
            continue;
        }
        $rows[] = $fullRow;
    }
    if (is_resource($stmt)) {
        @odbc_free_result($stmt);
    }

    return $rows;
}

/**
 * @param array<string, mixed> $requestRow
 * @param array<string, mixed> $payload
 * @return array{ok:bool,payload:array<string,mixed>,review_url_path:string,error:string}
 */
function m360_rw_customer_profile_refresh_task_review_access($conn, int $requestId, array $requestRow, array $payload): array
{
    if (!is_resource($conn) || $requestId < 1) {
        return ['ok' => false, 'payload' => $payload, 'review_url_path' => '', 'error' => 'invalid_request'];
    }
    if (m360_rw_intake_contract_customer_accepted($payload)) {
        return ['ok' => false, 'payload' => $payload, 'review_url_path' => '', 'error' => 'already_accepted'];
    }
    $task = m360_rw_intake_contract_cartable_task($payload);
    $existingPath = trim((string)($task['review_url_path'] ?? ''));
    if ($existingPath !== '' && str_contains($existingPath, 'customer-intake-contract-review.php')) {
        return ['ok' => true, 'payload' => $payload, 'review_url_path' => $existingPath, 'error' => ''];
    }

    $rawToken = m360_rw_intake_contract_generate_review_token($requestId);
    $tokenHash = m360_rw_intake_contract_token_hash($rawToken);
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $expires = gmdate('Y-m-d\TH:i:s\Z', time() + M360_RW_INTAKE_CONTRACT_REVIEW_TTL_SECONDS);
    $payload = m360_rw_intake_ensure_nested($payload);
    if (!isset($payload['reception_intake']['customer_cartable']) || !is_array($payload['reception_intake']['customer_cartable'])) {
        $payload['reception_intake']['customer_cartable'] = [];
    }
    $mobile = m360_rw_intake_resolve_mobile_for_otp($requestRow, $payload);
    $payload['reception_intake']['customer_cartable']['contract_task'] = array_merge($task, [
        'status' => M360_RW_INTAKE_CARTABLE_STATUS_PENDING,
        'title' => M360_RW_INTAKE_CARTABLE_TASK_TITLE_FA,
        'message' => M360_RW_INTAKE_CARTABLE_TASK_MESSAGE_FA,
        'customer_mobile' => $mobile,
        'access_token_hash' => $tokenHash,
        'access_token_created_at' => $now,
        'access_token_expires_at' => $expires,
        'review_url_path' => m360_rw_intake_contract_review_url($rawToken),
    ]);
    if (!isset($payload['reception_intake']['contract']) || !is_array($payload['reception_intake']['contract'])) {
        $payload['reception_intake']['contract'] = [];
    }
    $payload['reception_intake']['contract'] = array_merge($payload['reception_intake']['contract'], [
        'status' => M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW,
        'review_token_hash' => $tokenHash,
        'review_token_created_at' => $now,
        'review_token_expires_at' => $expires,
    ]);
    $persistToken = m360_rw_intake_persist_payload($conn, $requestId, $payload, []);
    if (!$persistToken['ok']) {
        return ['ok' => false, 'payload' => $payload, 'review_url_path' => '', 'error' => 'persist_failed'];
    }
    m360_rw_intake_ensure_db_contract_for_cartable($conn, $requestId, $requestRow, $payload, $rawToken);
    $requestFresh = m360_online_req_fetch_by_id($conn, $requestId);
    if ($requestFresh !== null) {
        $payload = m360_rw_intake_payload_for_recovery(m360_online_req_parse_payload($requestFresh['request_payload_json'] ?? null));
        $taskFresh = m360_rw_intake_contract_cartable_task($payload);
        if (trim((string)($taskFresh['review_url_path'] ?? '')) === '') {
            $payload['reception_intake']['customer_cartable']['contract_task']['review_url_path'] = m360_rw_intake_contract_review_url($rawToken);
            m360_rw_intake_persist_payload($conn, $requestId, $payload, []);
        }
    }
    $task = m360_rw_intake_contract_cartable_task($payload);
    $reviewPath = trim((string)($task['review_url_path'] ?? ''));

    return [
        'ok' => $reviewPath !== '',
        'payload' => $payload,
        'review_url_path' => $reviewPath,
        'error' => $reviewPath !== '' ? '' : 'missing_review_path',
    ];
}

/**
 * @return list<array{
 *   classification:string,
 *   online_request_id:int,
 *   jobcard_code:string,
 *   title:string,
 *   message:string,
 *   status_label:string,
 *   action_label:string,
 *   review_url:string,
 *   is_active:bool,
 *   is_completed:bool,
 *   is_legacy:bool,
 *   counts_toward_badge:bool
 * }>
 */
/**
 * @return list<array<string, mixed>>
 */
function m360_rw_customer_profile_canonical_active_contract_tasks($conn, int $customerId, string $customerMobile): array
{
    if (!is_resource($conn) || !m360_cartable_tables_available($conn)) {
        return [];
    }

    return m360_cartable_list_active_for_customer(
        $conn,
        $customerId > 0 ? $customerId : null,
        $customerMobile,
        M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE
    );
}

function m360_rw_customer_profile_canonical_contract_badge_count($conn, int $customerId, string $customerMobile): int
{
    if (!is_resource($conn) || !m360_cartable_tables_available($conn)) {
        return 0;
    }

    return m360_cartable_count_active_for_customer(
        $conn,
        $customerId > 0 ? $customerId : null,
        $customerMobile
    );
}

/**
 * @param array<string, string>|null $cartableTask
 * @return ?array<string, mixed>
 */
function m360_rw_customer_profile_format_canonical_active_contract_task(
    $conn,
    int $customerId,
    string $customerMobile,
    ?array $cartableTask
): ?array {
    if (!is_resource($conn) || $cartableTask === null || $cartableTask === []) {
        return null;
    }
    if (!m360_cartable_task_belongs_to_customer($cartableTask, $customerId > 0 ? $customerId : null, $customerMobile)) {
        return null;
    }

    $requestId = (int)($cartableTask['online_request_id'] ?? 0);
    $requestRow = $requestId > 0 ? m360_online_req_fetch_by_id($conn, $requestId) : null;
    $payload = $requestRow !== null
        ? m360_rw_intake_payload_for_recovery(m360_online_req_parse_payload($requestRow['request_payload_json'] ?? null))
        : [];

    $action = m360_cartable_resolve_task_action($conn, $cartableTask, $requestRow, $payload);
    if (!$action['ok'] || trim((string)$action['review_url']) === '') {
        return null;
    }

    return [
        'classification' => M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_PENDING_SIGNATURE,
        'online_request_id' => $requestId,
        'jobcard_code' => m360_rw_customer_profile_contract_jobcard_code($requestId),
        'title' => trim((string)($cartableTask['title'] ?? '')) !== ''
            ? (string)$cartableTask['title']
            : M360_RW_INTAKE_CARTABLE_TASK_TITLE_FA,
        'message' => trim((string)($cartableTask['message'] ?? '')) !== ''
            ? (string)$cartableTask['message']
            : M360_RW_INTAKE_CARTABLE_TASK_MESSAGE_FA,
        'status_label' => M360_RW_INTAKE_CARTABLE_TASK_STATUS_ACTION_FA,
        'action_label' => M360_RW_INTAKE_CARTABLE_TASK_ACTION_LABEL_FA,
        'review_url' => (string)$action['review_url'],
        'is_active' => true,
        'is_completed' => false,
        'is_legacy' => false,
        'counts_toward_badge' => true,
        'canonical_task_id' => (int)($cartableTask['task_id'] ?? 0),
    ];
}

/**
 * Historical contract cards only (signed canonical + legacy accepted). Active inbox uses canonical table.
 *
 * @return list<array<string, mixed>>
 */
function m360_rw_customer_profile_contract_cartable_tasks($conn, string $customerMobile): array
{
    if (!is_resource($conn)) {
        return [];
    }
    $normalizedCustomer = m360_rw_customer_profile_normalize_mobile($customerMobile);
    if ($normalizedCustomer === '') {
        return [];
    }

    $requests = m360_rw_customer_profile_fetch_online_requests_by_mobile($conn, $customerMobile);
    $historical = [];

    foreach ($requests as $requestRow) {
        $classified = m360_rw_customer_profile_classify_contract_request($conn, $requestRow, $customerMobile);
        $classification = (string)($classified['classification'] ?? '');
        if ($classification === M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_NO_CONTRACT) {
            continue;
        }
        if ($classification === M360_RW_CUSTOMER_PROFILE_CONTRACT_CLASS_PENDING_SIGNATURE) {
            continue;
        }
        $historical[] = $classified;
    }

    usort($historical, static function (array $a, array $b): int {
        return (int)($b['online_request_id'] ?? 0) <=> (int)($a['online_request_id'] ?? 0);
    });

    return $historical;
}

/**
 * @param list<array<string, mixed>> $tasks
 */
function m360_rw_customer_profile_contract_badge_count(array $tasks): int
{
    $count = 0;
    foreach ($tasks as $task) {
        if (!empty($task['counts_toward_badge'])) {
            $count++;
        }
    }

    return $count;
}

/**
 * @param list<array<string, mixed>> $tasks
 * @return ?array<string, mixed>
 */
function m360_rw_customer_profile_active_contract_task(array $tasks): ?array
{
    foreach ($tasks as $task) {
        if (!empty($task['is_active'])) {
            return $task;
        }
    }

    return null;
}

function m360_rw_customer_profile_unauthenticated_redirect_url(): string
{
    return 'customer-request.php';
}

/**
 * @return array{ok:bool,mobile:string,error:string}
 */
function m360_rw_customer_profile_resolve_verified_session_mobile(): array
{
    $otpHelper = __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
    if (!is_file($otpHelper)) {
        return ['ok' => false, 'mobile' => '', 'error' => 'otp_helper_missing'];
    }
    require_once $otpHelper;
    m360_otp_session_start();
    $verified = trim((string)($_SESSION['otp_verified_phone'] ?? ''));
    if ($verified === '') {
        return ['ok' => false, 'mobile' => '', 'error' => 'not_verified'];
    }
    if (!function_exists('m360_otp_normalize_phone')) {
        return ['ok' => false, 'mobile' => '', 'error' => 'otp_helper_incomplete'];
    }
    $normalized = m360_otp_normalize_phone($verified);
    if ($normalized === null || $normalized === '') {
        return ['ok' => false, 'mobile' => '', 'error' => 'invalid_mobile'];
    }
    if (!m360_otp_is_verified($normalized)) {
        return ['ok' => false, 'mobile' => '', 'error' => 'session_expired'];
    }

    return ['ok' => true, 'mobile' => $normalized, 'error' => ''];
}

function m360_rw_customer_profile_require_verified_session(): string
{
    $resolved = m360_rw_customer_profile_resolve_verified_session_mobile();
    if (!$resolved['ok']) {
        header('Location: ' . m360_rw_customer_profile_unauthenticated_redirect_url(), true, 302);
        exit;
    }

    return $resolved['mobile'];
}

function m360_rw_customer_profile_render_error_page(string $message, int $status = 500): never
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
    $layout = __DIR__ . DIRECTORY_SEPARATOR . 'mirror-layout.php';
    if (is_file($layout)) {
        require_once $layout;
        mirror_render_head('خطا — پروفایل مشتری', 'customer');
        echo '<section class="m360-card"><h2 class="m360-step-title">خطا</h2>';
        echo '<p class="m360-alert m360-alert-error">' . m360_rw_h($message) . '</p>';
        echo '<p class="m360-action-row"><a class="m360-btn m360-btn-primary" href="customer-request.php">بازگشت</a></p></section>';
        mirror_render_foot();
        exit;
    }
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><title>خطا</title></head><body><p>';
    echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '</p></body></html>';
    exit;
}

function m360_rw_customer_profile_initial_letter(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '؟';
    }
    if (function_exists('mb_substr')) {
        return (string)mb_substr($value, 0, 1);
    }

    return substr($value, 0, 1) ?: '؟';
}

/**
 * @param ?array<string, mixed> $row
 * @return array<string, string>
 */
function m360_rw_customer_profile_normalize_customer_row(?array $row): array
{
    if ($row === null) {
        return [
            'full_name' => '',
            'first_name' => '',
            'last_name' => '',
            'national_id' => '',
            'primary_mobile' => '',
            'address' => '',
            'city' => '',
            'customer_id' => '',
        ];
    }
    $fullName = trim((string)($row['full_name'] ?? ''));
    $parts = preg_split('/\s+/u', $fullName, 2) ?: [];

    return [
        'full_name' => $fullName,
        'first_name' => trim((string)($parts[0] ?? '')),
        'last_name' => trim((string)($parts[1] ?? '')),
        'national_id' => trim((string)($row['national_id'] ?? '')),
        'primary_mobile' => trim((string)($row['primary_mobile'] ?? '')),
        'address' => trim((string)($row['address'] ?? '')),
        'city' => trim((string)($row['city'] ?? '')),
        'customer_id' => trim((string)($row['customer_id'] ?? '')),
    ];
}

/**
 * @return array{
 *   full_name:string,
 *   display_name:string,
 *   primary_mobile:string,
 *   address:string,
 *   city:string,
 *   customer_id:int,
 *   source:string,
 *   profile_complete:bool,
 *   needs_completion:bool
 * }
 */
function m360_rw_customer_profile_resolve_display_identity($conn, string $verifiedMobile): array
{
    $normalizedMobile = m360_rw_customer_profile_normalize_mobile($verifiedMobile);
    $empty = [
        'full_name' => '',
        'display_name' => '',
        'primary_mobile' => $normalizedMobile,
        'address' => '',
        'city' => '',
        'customer_id' => 0,
        'source' => 'verified_mobile_only',
        'profile_complete' => false,
        'needs_completion' => true,
    ];
    if (!is_resource($conn) || $normalizedMobile === '') {
        return $empty;
    }

    $customerRow = m360_rw_customer_profile_fetch_customer_row($conn, $normalizedMobile);
    $customer = m360_rw_customer_profile_normalize_customer_row($customerRow);
    $customerId = (int)($customer['customer_id'] ?? 0);
    $fullName = trim((string)($customer['full_name'] ?? ''));
    $address = trim((string)($customer['address'] ?? ''));
    $city = trim((string)($customer['city'] ?? ''));
    $nationalId = trim((string)($customer['national_id'] ?? ''));
    $source = 'canonical_customer';

    if ($fullName === '') {
        $requests = m360_rw_customer_profile_fetch_online_requests_by_mobile($conn, $normalizedMobile);
        foreach ($requests as $requestRow) {
            $requestMobile = m360_rw_customer_profile_normalize_mobile((string)($requestRow['mobile'] ?? ''));
            if ($requestMobile !== $normalizedMobile) {
                continue;
            }
            $name = trim((string)($requestRow['customer_name'] ?? ''));
            if ($name === '') {
                $payload = m360_online_req_parse_payload($requestRow['request_payload_json'] ?? null);
                $payload = m360_rw_intake_payload_for_recovery($payload);
                $summary = m360_rw_intake_contract_review_summary($payload, $requestRow);
                $name = trim((string)($summary['customer_name'] ?? ''));
            }
            if ($name !== '') {
                $fullName = $name;
                $source = 'request_snapshot';
                break;
            }
        }
    }

    if ($fullName === '' && m360_cartable_tables_available($conn)) {
        $tasks = m360_cartable_list_active_for_customer($conn, $customerId > 0 ? $customerId : null, $normalizedMobile);
        foreach ($tasks as $taskRow) {
            $requestId = (int)($taskRow['online_request_id'] ?? 0);
            if ($requestId < 1) {
                continue;
            }
            $requestRow = m360_online_req_fetch_by_id($conn, $requestId);
            if ($requestRow === null) {
                continue;
            }
            $requestMobile = m360_rw_customer_profile_normalize_mobile((string)($requestRow['mobile'] ?? ''));
            if ($requestMobile !== $normalizedMobile) {
                continue;
            }
            $name = trim((string)($requestRow['customer_name'] ?? ''));
            if ($name !== '') {
                $fullName = $name;
                $source = 'contract_task_snapshot';
                break;
            }
        }
    }

    $profileComplete = $fullName !== ''
        && preg_match('/^[0-9]{10}$/', $nationalId) === 1
        && $address !== '';

    return [
        'full_name' => $fullName,
        'display_name' => $fullName !== '' ? $fullName : 'مشتری',
        'primary_mobile' => $normalizedMobile,
        'address' => $address,
        'city' => $city,
        'customer_id' => $customerId,
        'source' => $source,
        'profile_complete' => $profileComplete,
        'needs_completion' => !$profileComplete,
    ];
}

/**
 * @return ?array<string, mixed>
 */
function m360_rw_customer_profile_fetch_customer_row($conn, string $mobile): ?array
{
    if (!is_resource($conn) || $mobile === '') {
        return null;
    }
    $submitHelper = __DIR__ . DIRECTORY_SEPARATOR . 'm360-customer-online-submit-helper.php';
    if (!is_file($submitHelper)) {
        return null;
    }
    require_once $submitHelper;
    if (!function_exists('m360_pr02b_fetch_customer_row') || !function_exists('m360_reception_default_company_id')) {
        return null;
    }
    $companyId = m360_reception_default_company_id($conn);
    if ($companyId < 1) {
        return null;
    }

    return m360_pr02b_fetch_customer_row($conn, $companyId, $mobile);
}

/**
 * @return list<array<string, mixed>>
 */
function m360_rw_customer_profile_list_vehicles($conn, int $customerId): array
{
    if (!is_resource($conn) || $customerId < 1) {
        return [];
    }
    $submitHelper = __DIR__ . DIRECTORY_SEPARATOR . 'm360-customer-online-submit-helper.php';
    if (!is_file($submitHelper)) {
        return [];
    }
    require_once $submitHelper;
    if (!function_exists('m360_pr02b_list_customer_vehicles')) {
        return [];
    }

    return m360_pr02b_list_customer_vehicles($conn, $customerId);
}

/**
 * @return ?array<string, mixed>
 */
function m360_rw_customer_profile_detect_active_online_request($conn, string $mobile): ?array
{
    $requests = m360_rw_customer_profile_fetch_online_requests_by_mobile($conn, $mobile);
    $terminal = ['DELIVERED', 'CANCELLED', 'CLOSED', 'DONE', 'REJECTED'];
    foreach ($requests as $row) {
        $status = strtoupper(trim((string)($row['request_status'] ?? '')));
        if ($status !== '' && in_array($status, $terminal, true)) {
            continue;
        }
        $requestId = (int)($row['online_request_id'] ?? 0);
        if ($requestId < 1) {
            continue;
        }
        $payload = m360_online_req_parse_payload($row['request_payload_json'] ?? null);
        $vehiclePlate = '';
        if (is_array($payload)) {
            $vehicle = $payload['reception_intake']['vehicle'] ?? null;
            if (is_array($vehicle)) {
                $vehiclePlate = trim((string)($vehicle['plate'] ?? ''));
            }
            if ($vehiclePlate === '') {
                $vehiclePlate = trim((string)($payload['vehicle_plate'] ?? ''));
            }
        }
        $statusLabel = function_exists('m360_online_req_status_label_fa')
            ? m360_online_req_status_label_fa($status !== '' ? $status : 'NEW')
            : 'در حال بررسی';

        return [
            'online_request_id' => $requestId,
            'request_status' => $status !== '' ? $status : 'NEW',
            'request_status_label' => $statusLabel,
            'vehicle_plate' => $vehiclePlate,
            'jobcard_code' => 'REQ-' . (string)$requestId,
        ];
    }

    return null;
}

/**
 * @param array<string, mixed> $payload
 */
function m360_rw_intake_reception_is_completed(array $payload): bool
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $rc = is_array($payload['reception_intake']['reception_completed'] ?? null)
        ? $payload['reception_intake']['reception_completed']
        : [];

    return trim((string)($rc['status'] ?? '')) === 'completed';
}

/**
 * @param array<string, mixed> $payload
 */
function m360_rw_intake_operation_gate_hall_manager_allowed(array $payload): bool
{
    if (!m360_rw_intake_reception_is_completed($payload)) {
        return false;
    }
    if (!m360_rw_intake_contract_customer_accepted($payload)) {
        return false;
    }

    return true;
}

/**
 * @param array<string, mixed> $payload
 */
function m360_rw_intake_operation_gate_message_fa(array $payload): string
{
    if (!m360_rw_intake_reception_is_completed($payload)) {
        return 'پرونده پذیرش هنوز تکمیل نشده است.';
    }
    if (!m360_rw_intake_contract_customer_accepted($payload)) {
        return 'پرونده پذیرش شده است اما شروع عملیات منوط به تأیید قرارداد و مجوز مالی است.';
    }

    return 'عملیات هنوز مجاز نیست تا مجوز مالی تأیید شود.';
}

/**
 * @return array{ok:bool,sent:bool,message:string,skipped_reason:string}
 */
function m360_rw_intake_send_contract_notification_sms(string $mobile, bool $allowLiveSend = true, string $reviewUrl = ''): array
{
    $phone = trim($mobile);
    if ($phone === '') {
        return ['ok' => false, 'sent' => false, 'message' => 'شماره موبایل مشتری موجود نیست.', 'skipped_reason' => 'missing_mobile'];
    }
    if (!$allowLiveSend || PHP_SAPI === 'cli') {
        return ['ok' => true, 'sent' => false, 'message' => 'ارسال پیامک در محیط تست/خودکار غیرفعال است.', 'skipped_reason' => 'automated_or_cli'];
    }

    $otpHelper = __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
    if (!is_file($otpHelper)) {
        return ['ok' => false, 'sent' => false, 'message' => 'زیرساخت پیامک در دسترس نیست.', 'skipped_reason' => 'otp_helper_missing'];
    }
    require_once $otpHelper;
    if (!function_exists('m360_otp_sms_configured') || !m360_otp_sms_configured()) {
        return ['ok' => false, 'sent' => false, 'message' => 'ارسال پیامک قرارداد نیازمند پیکربندی IPPanel است.', 'skipped_reason' => 'sms_not_configured'];
    }
    if (!function_exists('m360_otp_normalize_phone')) {
        return ['ok' => false, 'sent' => false, 'message' => 'زیرساخت پیامک در دسترس نیست.', 'skipped_reason' => 'otp_helper_incomplete'];
    }
    $normalized = m360_otp_normalize_phone($phone);
    if ($normalized === null) {
        return ['ok' => false, 'sent' => false, 'message' => 'شماره موبایل مشتری نامعتبر است.', 'skipped_reason' => 'invalid_mobile'];
    }
    $settings = m360_otp_sms_settings();
    if (($settings['provider'] ?? '') !== 'ippanel') {
        return ['ok' => false, 'sent' => false, 'message' => 'ارسال پیامک قرارداد فقط از مسیر IPPanel پشتیبانی می‌شود.', 'skipped_reason' => 'unsupported_provider'];
    }
    $message = M360_RW_INTAKE_CONTRACT_SMS_TEXT_FA;
    $reviewUrl = trim($reviewUrl);
    if ($reviewUrl !== '') {
        $base = m360_intake_contract_public_base_url();
        $fullUrl = str_starts_with($reviewUrl, 'http') ? $reviewUrl : rtrim($base, '/') . '/' . ltrim($reviewUrl, '/');
        $message .= ' ' . $fullUrl;
    }
    $payload = m360_otp_ippanel_webservice_payload($normalized, $message, $settings);
    $send = m360_otp_ippanel_send($payload, (string)($settings['api_key'] ?? ''));
    if (empty($send['ok'])) {
        return [
            'ok' => false,
            'sent' => false,
            'message' => (string)($send['message'] ?? 'ارسال پیامک قرارداد انجام نشد.'),
            'skipped_reason' => 'provider_error',
        ];
    }

    return ['ok' => true, 'sent' => true, 'message' => 'پیامک اطلاع‌رسانی قرارداد ارسال شد.', 'skipped_reason' => ''];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $requestRow
 * @return array{plate:string,vin:string,brand:string,model:string,mileage:string,fuel_level:string}
 */
function m360_rw_intake_vehicle_canonical(array $payload, array $requestRow = [], ?array $erpVehicle = null): array
{
    $resolved = m360_rw_intake_resolve_vehicle_dossier_fields($payload, $requestRow, $erpVehicle);

    return [
        'plate' => (string)($resolved['plate']['value'] ?? ''),
        'vin' => (string)($resolved['vin']['value'] ?? ''),
        'brand' => (string)($resolved['brand']['value'] ?? ''),
        'model' => (string)($resolved['model']['value'] ?? ''),
        'vehicle_class' => (string)($resolved['vehicle_class']['value'] ?? ''),
        'vehicle_type' => (string)($resolved['vehicle_type']['value'] ?? ''),
        'mileage' => (string)($resolved['mileage']['value'] ?? ''),
        'fuel_level' => (string)($resolved['fuel_level']['value'] ?? ''),
        'fields' => $resolved,
    ];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $requestRow
 */
function m360_rw_intake_vehicle_step_complete(array $payload, array $requestRow = []): bool
{
    $vehicle = m360_rw_intake_vehicle_canonical($payload, $requestRow);
    $riVehicle = is_array($payload['reception_intake']['vehicle'] ?? null) ? $payload['reception_intake']['vehicle'] : [];
    foreach (['plate', 'brand', 'mileage', 'fuel_level'] as $field) {
        if (trim($vehicle[$field]) === '') {
            return false;
        }
    }
    if (trim((string)($riVehicle['vehicle_year_pair'] ?? $payload['vehicle_year_pair'] ?? '')) === '') {
        return false;
    }
    if (trim((string)($riVehicle['visit_date'] ?? $payload['visit_date'] ?? '')) === '') {
        return false;
    }
    $brand = trim($vehicle['brand']);
    if (!in_array($brand, m360_rw_intake_approved_vehicle_brands(), true)) {
        return false;
    }
    if ($brand === M360_RW_TOP_LEVEL_OTHER_BRAND) {
        return trim((string)($riVehicle['brand_other_explanation'] ?? '')) !== '';
    }

    return trim($vehicle['model']) !== '';
}

/**
 * @param array<string, mixed> $payload
 * @return array{vehicle_items:string,visible_damage:string,initial_vehicle_condition:string}
 */
function m360_rw_intake_condition_canonical(array $payload): array
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $condition = is_array($payload['reception_intake']['condition'] ?? null) ? $payload['reception_intake']['condition'] : [];

    return [
        'vehicle_items' => m360_rw_pick([$condition, $payload], 'vehicle_items', 'belongings'),
        'visible_damage' => m360_rw_pick([$condition, $payload], 'visible_damage', 'body_damage'),
        'initial_vehicle_condition' => m360_rw_pick([$condition, $payload], 'initial_vehicle_condition'),
    ];
}

/**
 * @param array<string, mixed> $payload
 */
function m360_rw_intake_condition_step_complete(array $payload): bool
{
    $condition = m360_rw_intake_condition_canonical($payload);
    foreach (['vehicle_items', 'visible_damage', 'initial_vehicle_condition'] as $field) {
        if (trim($condition[$field]) === '') {
            return false;
        }
    }

    return true;
}

/**
 * @param array<string, mixed> $payload
 * @return array{referral_team_id:string,referral_type:string}
 */
function m360_rw_intake_referral_canonical(array $payload): array
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $referral = is_array($payload['reception_intake']['referral'] ?? null) ? $payload['reception_intake']['referral'] : [];

    return [
        'referral_team_id' => trim((string)($referral['referral_team_id'] ?? '')),
        'referral_type' => trim((string)($referral['referral_type'] ?? 'service_team')),
    ];
}

/**
 * @param array<string, mixed> $payload
 */
function m360_rw_intake_referral_step_complete(array $payload): bool
{
    return m360_rw_intake_hall_manager_step_complete($payload);
}

const M360_RW_INTAKE_CONTRACT_STATUS_PREPARED = 'prepared';
const M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW = 'pending_customer_review';
const M360_RW_INTAKE_CONTRACT_STATUS_CUSTOMER_ACCEPTED = 'customer_accepted';
const M360_RW_INTAKE_DOC_CONTRACT_STATUS_CUSTOMER_PENDING = 'customer_pending_review';
const M360_RW_INTAKE_DOC_CONTRACT_STATUS_CUSTOMER_ACCEPTED = 'customer_accepted';
const M360_RW_INTAKE_CARTABLE_STATUS_NOT_READY = 'not_ready';
const M360_RW_INTAKE_CARTABLE_STATUS_PENDING = 'pending_customer_review';
const M360_RW_INTAKE_CARTABLE_STATUS_CUSTOMER_ACCEPTED = 'customer_accepted';
const M360_RW_INTAKE_CONTRACT_REVIEW_TTL_SECONDS = 172800;

/**
 * @param array<string, mixed> $payload
 * @return array<string, mixed>
 */
function m360_rw_intake_contract_cartable_task(array $payload): array
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $cartable = is_array($payload['reception_intake']['customer_cartable'] ?? null)
        ? $payload['reception_intake']['customer_cartable']
        : [];
    $task = is_array($cartable['contract_task'] ?? null) ? $cartable['contract_task'] : [];

    return $task;
}

/**
 * @param array<string, mixed> $payload
 */
function m360_rw_intake_contract_cartable_pending(array $payload): bool
{
    if (m360_rw_intake_contract_customer_accepted($payload)) {
        return false;
    }
    $task = m360_rw_intake_contract_cartable_task($payload);
    $taskStatus = trim((string)($task['status'] ?? ''));
    if ($taskStatus === M360_RW_INTAKE_CARTABLE_STATUS_PENDING) {
        return trim((string)($task['access_token_hash'] ?? '')) !== '';
    }
    $contract = is_array($payload['reception_intake']['contract'] ?? null) ? $payload['reception_intake']['contract'] : [];
    $contractStatus = trim((string)($contract['status'] ?? ''));
    $hasHash = trim((string)($contract['review_token_hash'] ?? '')) !== ''
        || trim((string)($task['access_token_hash'] ?? '')) !== '';

    return $hasHash && in_array($contractStatus, [
        M360_RW_INTAKE_CONTRACT_STATUS_PREPARED,
        M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW,
    ], true);
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, string> $formValues
 * @return array{diagnostic_ready:bool,cost_ready:bool,documents_cost_ready:bool}
 */
function m360_rw_intake_documents_cost_diagnostic_ready(array $payload, array $formValues): array
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $docs = is_array($payload['reception_intake']['documents'] ?? null) ? $payload['reception_intake']['documents'] : [];
    $diagnosticReady = trim((string)($formValues['diagnostic_status'] ?? '')) !== ''
        || trim((string)($docs['diagnostic_status'] ?? '')) !== ''
        || trim((string)($docs['diagnostic_pdf'] ?? '')) !== ''
        || trim((string)($formValues['diagnostic_pdf'] ?? '')) !== '';
    $costReady = trim((string)($formValues['cost_agreement'] ?? '')) !== ''
        || trim((string)($docs['cost_agreement'] ?? '')) !== ''
        || trim((string)($payload['cost_agreement'] ?? '')) !== '';

    return [
        'diagnostic_ready' => $diagnosticReady,
        'cost_ready' => $costReady,
        'documents_cost_ready' => $diagnosticReady && $costReady,
    ];
}

function m360_rw_intake_cartable_blocker_label_fa(string $stepKey, string $kind = 'item'): string
{
    $items = [
        'otp' => 'تکمیل احراز هویت OTP مشتری',
        'vehicle' => 'تکمیل اطلاعات خودرو',
        'condition' => 'تکمیل وضعیت خودرو',
        'service' => 'تکمیل مسیر خدمات',
        'referral' => 'ارسال به مسئول سالن',
        'photos' => 'تکمیل عکس‌های پذیرش',
        'diagnostic' => 'ثبت دیاگ',
        'cost' => 'ثبت توافق هزینه',
        'documents' => 'تکمیل مستندات دیاگ و هزینه',
    ];
    $actions = [
        'otp' => 'بازگشت برای تکمیل احراز هویت مشتری',
        'vehicle' => 'بازگشت برای تکمیل اطلاعات خودرو',
        'condition' => 'بازگشت برای تکمیل وضعیت خودرو',
        'service' => 'بازگشت برای تکمیل مسیر خدمات',
        'referral' => 'بازگشت برای ارسال به مسئول سالن',
        'photos' => 'بازگشت برای تکمیل عکس‌ها',
        'diagnostic' => 'بازگشت برای تکمیل مستندات',
        'cost' => 'بازگشت برای تکمیل مستندات',
        'documents' => 'بازگشت برای تکمیل مستندات',
    ];

    return $kind === 'action'
        ? ($actions[$stepKey] ?? 'بازگشت برای تکمیل اطلاعات')
        : ($items[$stepKey] ?? 'تکمیل مرحله ' . $stepKey);
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $requestRow
 * @return array{
 *   ready:bool,
 *   blockers:list<array{step:string,label_fa:string,action_fa:string}>,
 *   completed:list<string>,
 *   first_blocker_step:string
 * }
 */
function m360_rw_intake_contract_cartable_prerequisites(array $payload, array $requestRow): array
{
    $payload = m360_rw_intake_payload_for_recovery($payload);
    $formValues = m360_rw_intake_form_values($payload, $requestRow);
    $wizard = m360_rw_intake_get_wizard_step_state($payload, $requestRow);
    $blockers = [];
    $completed = [];

    foreach (m360_rw_intake_reception_completion_keys() as $stepKey) {
        if (!empty($wizard['steps'][$stepKey]['complete'])) {
            $completed[] = $stepKey;
            continue;
        }
        $blockers[] = [
            'step' => $stepKey,
            'label_fa' => m360_rw_intake_cartable_blocker_label_fa($stepKey, 'item'),
            'action_fa' => m360_rw_intake_cartable_blocker_label_fa($stepKey, 'action'),
        ];
    }

    $docReady = m360_rw_intake_documents_cost_diagnostic_ready($payload, $formValues);
    if (!$docReady['diagnostic_ready']) {
        $blockers[] = [
            'step' => 'documents',
            'label_fa' => m360_rw_intake_cartable_blocker_label_fa('diagnostic', 'item'),
            'action_fa' => m360_rw_intake_cartable_blocker_label_fa('documents', 'action'),
        ];
    } else {
        $completed[] = 'diagnostic';
    }
    if (!$docReady['cost_ready']) {
        $blockers[] = [
            'step' => 'documents',
            'label_fa' => m360_rw_intake_cartable_blocker_label_fa('cost', 'item'),
            'action_fa' => m360_rw_intake_cartable_blocker_label_fa('documents', 'action'),
        ];
    } else {
        $completed[] = 'cost';
    }

    $firstBlocker = $blockers[0]['step'] ?? '';

    return [
        'ready' => $blockers === [],
        'blockers' => $blockers,
        'completed' => $completed,
        'first_blocker_step' => $firstBlocker,
    ];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $requestRow
 * @return string not_ready|ready|pending|accepted
 */
function m360_rw_intake_documents_cartable_ui_state(array $payload, array $requestRow): string
{
    if (m360_rw_intake_contract_customer_accepted($payload)) {
        return 'accepted';
    }
    if (m360_rw_intake_contract_cartable_pending($payload)) {
        return 'pending';
    }
    $prereq = m360_rw_intake_contract_cartable_prerequisites($payload, $requestRow);
    if (!$prereq['ready']) {
        return 'not_ready';
    }

    return 'ready';
}

/**
 * @param array<string, mixed> $payload
 */
function m360_rw_intake_contract_customer_accepted(array $payload): bool
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $contract = is_array($payload['reception_intake']['contract'] ?? null) ? $payload['reception_intake']['contract'] : [];
    $docs = is_array($payload['reception_intake']['documents'] ?? null) ? $payload['reception_intake']['documents'] : [];
    $task = m360_rw_intake_contract_cartable_task($payload);
    $status = trim((string)($contract['status'] ?? ''));
    $docStatus = trim((string)($docs['contract_status'] ?? $payload['contract_status'] ?? ''));
    $taskStatus = trim((string)($task['status'] ?? ''));

    return $status === M360_RW_INTAKE_CONTRACT_STATUS_CUSTOMER_ACCEPTED
        || $docStatus === M360_RW_INTAKE_CONTRACT_STATUS_CUSTOMER_ACCEPTED
        || $docStatus === M360_RW_INTAKE_DOC_CONTRACT_STATUS_CUSTOMER_ACCEPTED
        || $taskStatus === M360_RW_INTAKE_CARTABLE_STATUS_CUSTOMER_ACCEPTED;
}

function m360_rw_intake_contract_generate_review_token(int $requestId): string
{
    return rtrim(strtr(base64_encode($requestId . ':' . bin2hex(random_bytes(24))), '+/', '-_'), '=');
}

function m360_rw_intake_contract_parse_review_token(string $rawToken): int
{
    $rawToken = trim($rawToken);
    if ($rawToken === '') {
        return 0;
    }
    $decoded = base64_decode(strtr($rawToken, '-_', '+/'), true);
    if ($decoded === false || !str_contains($decoded, ':')) {
        return 0;
    }
    $parts = explode(':', $decoded, 2);
    $requestId = (int)($parts[0] ?? 0);

    return $requestId > 0 ? $requestId : 0;
}

function m360_rw_intake_contract_token_hash(string $rawToken): string
{
    return hash('sha256', trim($rawToken));
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,error:string,request_id:int,expired:bool}
 */
function m360_rw_intake_contract_validate_review_token(array $payload, int $requestId, string $rawToken): array
{
    $invalidMessage = 'مأموریت قرارداد مشتری یافت نشد یا منقضی شده است.';
    if ($requestId < 1 || trim($rawToken) === '') {
        return ['ok' => false, 'error' => $invalidMessage, 'request_id' => 0, 'expired' => false];
    }

    $payload = m360_rw_intake_ensure_nested($payload);
    $contract = is_array($payload['reception_intake']['contract'] ?? null) ? $payload['reception_intake']['contract'] : [];
    $task = m360_rw_intake_contract_cartable_task($payload);
    $storedHash = trim((string)($contract['review_token_hash'] ?? ''));
    if ($storedHash === '') {
        $storedHash = trim((string)($task['access_token_hash'] ?? ''));
    }
    if ($storedHash === '' || !hash_equals($storedHash, m360_rw_intake_contract_token_hash($rawToken))) {
        return ['ok' => false, 'error' => $invalidMessage, 'request_id' => $requestId, 'expired' => false];
    }

    $expiresAt = trim((string)($task['access_token_expires_at'] ?? ''));
    if ($expiresAt === '') {
        $expiresAt = trim((string)($contract['review_token_expires_at'] ?? ''));
    }
    if ($expiresAt !== '') {
        $expiresTs = strtotime($expiresAt);
        if ($expiresTs !== false && time() > $expiresTs) {
            return ['ok' => false, 'error' => $invalidMessage, 'request_id' => $requestId, 'expired' => true];
        }
    }

    return ['ok' => true, 'error' => '', 'request_id' => $requestId, 'expired' => false];
}

function m360_rw_intake_contract_review_url(string $rawToken): string
{
    return 'customer-intake-contract-review.php?t=' . rawurlencode($rawToken);
}

/**
 * @param resource $conn
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $requestRow
 * @return array{ok:bool,error:string,payload:array<string,mixed>,raw_token:string}
 */
function m360_rw_intake_bootstrap_contract_cartable_task($conn, int $requestId, array $requestRow, array $payload, int $userId): array
{
    if ($requestId < 1) {
        return ['ok' => false, 'error' => 'شناسه درخواست نامعتبر است.', 'payload' => $payload, 'raw_token' => ''];
    }

    $payload = m360_rw_intake_ensure_nested($payload);
    $rawToken = m360_rw_intake_contract_generate_review_token($requestId);
    $tokenHash = m360_rw_intake_contract_token_hash($rawToken);
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $expires = gmdate('Y-m-d\TH:i:s\Z', time() + M360_RW_INTAKE_CONTRACT_REVIEW_TTL_SECONDS);
    $mobile = m360_rw_intake_resolve_mobile_for_otp($requestRow, $payload);
    $vehicle = m360_rw_intake_vehicle_canonical($payload, $requestRow);
    $summary = m360_rw_intake_contract_review_summary($payload, $requestRow);
    $snapshot = [
        'customer_name' => $summary['customer_name'] !== '' ? $summary['customer_name'] : (string)($requestRow['customer_name'] ?? '-'),
        'mobile' => $mobile !== '' ? $mobile : '-',
        'vehicle' => $summary['brand_model'] !== '' ? $summary['brand_model'] : '-',
        'plate' => $vehicle['plate'] !== '' ? $vehicle['plate'] : (string)($requestRow['vehicle_plate'] ?? '-'),
        'vin' => $vehicle['vin'] !== '' ? $vehicle['vin'] : '-',
        'odometer' => $vehicle['mileage'] !== '' ? $vehicle['mileage'] : '-',
        'service_type' => $summary['service_route'] !== '' ? $summary['service_route'] : '-',
        'cost_range' => $summary['cost_agreement'] !== '' ? $summary['cost_agreement'] : '-',
        'visit_date' => trim((string)($requestRow['visit_date'] ?? ($payload['visit_date'] ?? date('Y-m-d')))),
        'reception_date' => date('Y-m-d'),
    ];

    $contractId = 0;
    if (!is_resource($conn)) {
        return ['ok' => false, 'error' => 'اتصال به پایگاه داده برقرار نشد.', 'payload' => $payload, 'raw_token' => ''];
    }
    $generated = m360_intake_contract_generate_for_online_request(
        $conn,
        $requestId,
        $rawToken,
        gmdate('Y-m-d H:i:s', time() + M360_RW_INTAKE_CONTRACT_REVIEW_TTL_SECONDS),
        $mobile,
        (int)($requestRow['customer_id'] ?? 0) ?: null,
        (int)($requestRow['vehicle_id'] ?? 0) ?: null,
        $snapshot
    );
    if (!$generated['ok'] && empty($generated['reused'])) {
        return ['ok' => false, 'error' => (string)($generated['message'] ?? 'ثبت قرارداد ناموفق بود.'), 'payload' => $payload, 'raw_token' => ''];
    }
    $contractId = (int)($generated['contract_id'] ?? 0);

    if ($contractId > 0) {
        m360_rw_intake_ensure_canonical_contract_cartable_task(
            $conn,
            $requestId,
            $requestRow,
            $payload,
            $contractId,
            $tokenHash,
            $expires,
            'STAFF',
            (string)$userId
        );
    }

    if (!isset($payload['reception_intake']['customer_cartable']) || !is_array($payload['reception_intake']['customer_cartable'])) {
        $payload['reception_intake']['customer_cartable'] = [];
    }
    $payload['reception_intake']['customer_cartable']['contract_task'] = [
        'status' => M360_RW_INTAKE_CARTABLE_STATUS_PENDING,
        'title' => M360_RW_INTAKE_CARTABLE_TASK_TITLE_FA,
        'message' => M360_RW_INTAKE_CARTABLE_TASK_MESSAGE_FA,
        'assigned_at' => gmdate('Y-m-d H:i:s'),
        'assigned_by_user_id' => (string)$userId,
        'customer_mobile' => $mobile,
        'contract_id' => $contractId > 0 ? (string)$contractId : '',
        'access_token_hash' => $tokenHash,
        'access_token_created_at' => $now,
        'access_token_expires_at' => $expires,
        'review_url_path' => m360_rw_intake_contract_review_url($rawToken),
    ];
    if (!isset($payload['reception_intake']['contract']) || !is_array($payload['reception_intake']['contract'])) {
        $payload['reception_intake']['contract'] = [];
    }
    $payload['reception_intake']['contract'] = array_merge($payload['reception_intake']['contract'], [
        'status' => M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW,
        'contract_id' => $contractId > 0 ? (string)$contractId : '',
        'contract_version' => M360_CONTRACT_VERSION,
        'review_token_hash' => $tokenHash,
        'review_token_created_at' => $now,
        'review_token_expires_at' => $expires,
        'prepared_at' => $now,
    ]);
    if (!isset($payload['reception_intake']['documents']) || !is_array($payload['reception_intake']['documents'])) {
        $payload['reception_intake']['documents'] = [];
    }
    $payload['reception_intake']['documents']['contract_status'] = M360_RW_INTAKE_DOC_CONTRACT_STATUS_CUSTOMER_PENDING;
    $payload['contract_status'] = M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW;

    $validate = m360_rw_intake_contract_validate_review_token($payload, $requestId, $rawToken);
    if (!$validate['ok']) {
        return ['ok' => false, 'error' => 'خطای داخلی: ذخیره توکن کارتابل مشتری تأیید نشد.', 'payload' => $payload, 'raw_token' => ''];
    }

    $payload['_contract_review_token_once'] = $rawToken;
    $payload['_contract_sms_after_save'] = '1';
    m360_rw_intake_mark_section_saved($payload, 'contract');

    return ['ok' => true, 'error' => '', 'payload' => $payload, 'raw_token' => $rawToken];
}

/**
 * @param resource $conn
 * @param array<string, mixed> $requestRow
 * @param array<string, mixed> $payload
 * @return array{ok:bool,contract_id:int,message:string}
 */
function m360_rw_intake_ensure_db_contract_for_cartable($conn, int $requestId, array $requestRow, array $payload, string $rawToken): array
{
    if (!is_resource($conn) || $requestId < 1 || trim($rawToken) === '') {
        return ['ok' => false, 'contract_id' => 0, 'message' => 'اطلاعات ناقص است.'];
    }
    $existing = m360_intake_contract_find_active_for_online_request($conn, $requestId);
    if ($existing !== null) {
        $contractId = (int)($existing['contract_id'] ?? 0);
        if ($contractId > 0) {
            $payload = m360_rw_intake_ensure_nested($payload);
            $task = m360_rw_intake_contract_cartable_task($payload);
            $tokenHash = trim((string)($task['access_token_hash'] ?? ($existing['secure_token_hash'] ?? '')));
            $expires = trim((string)($task['access_token_expires_at'] ?? ($existing['secure_token_expires_at'] ?? '')));
            m360_rw_intake_ensure_canonical_contract_cartable_task(
                $conn,
                $requestId,
                $requestRow,
                $payload,
                $contractId,
                $tokenHash,
                $expires,
                'SYSTEM',
                'ensure_db_contract_existing'
            );
        }

        return ['ok' => true, 'contract_id' => $contractId, 'message' => ''];
    }
    $payload = m360_rw_intake_ensure_nested($payload);
    $task = m360_rw_intake_contract_cartable_task($payload);
    $expires = trim((string)($task['access_token_expires_at'] ?? ''));
    if ($expires === '') {
        $expires = gmdate('Y-m-d H:i:s', time() + M360_RW_INTAKE_CONTRACT_REVIEW_TTL_SECONDS);
    } else {
        $expires = str_replace('T', ' ', substr($expires, 0, 19));
    }
    $mobile = m360_rw_intake_resolve_mobile_for_otp($requestRow, $payload);
    $vehicle = m360_rw_intake_vehicle_canonical($payload, $requestRow);
    $summary = m360_rw_intake_contract_review_summary($payload, $requestRow);
    $snapshot = [
        'customer_name' => $summary['customer_name'] !== '' ? $summary['customer_name'] : (string)($requestRow['customer_name'] ?? '-'),
        'mobile' => $mobile !== '' ? $mobile : '-',
        'vehicle' => $summary['brand_model'] !== '' ? $summary['brand_model'] : '-',
        'plate' => $vehicle['plate'] !== '' ? $vehicle['plate'] : (string)($requestRow['vehicle_plate'] ?? '-'),
        'vin' => $vehicle['vin'] !== '' ? $vehicle['vin'] : '-',
        'odometer' => $vehicle['mileage'] !== '' ? $vehicle['mileage'] : '-',
        'service_type' => $summary['service_route'] !== '' ? $summary['service_route'] : '-',
        'cost_range' => $summary['cost_agreement'] !== '' ? $summary['cost_agreement'] : '-',
        'visit_date' => trim((string)($requestRow['visit_date'] ?? ($payload['visit_date'] ?? date('Y-m-d')))),
        'reception_date' => date('Y-m-d'),
    ];
    $generated = m360_intake_contract_generate_for_online_request(
        $conn,
        $requestId,
        $rawToken,
        $expires,
        $mobile,
        (int)($requestRow['customer_id'] ?? 0) ?: null,
        (int)($requestRow['vehicle_id'] ?? 0) ?: null,
        $snapshot
    );
    if (!$generated['ok'] && empty($generated['reused'])) {
        return ['ok' => false, 'contract_id' => 0, 'message' => (string)($generated['message'] ?? '')];
    }
    $contractId = (int)($generated['contract_id'] ?? 0);
    if ($contractId > 0) {
        $task = m360_rw_intake_contract_cartable_task($payload);
        $tokenHash = trim((string)($task['access_token_hash'] ?? ''));
        if ($tokenHash === '' && function_exists('m360_rw_intake_contract_token_hash')) {
            $tokenHash = m360_rw_intake_contract_token_hash($rawToken);
        }
        m360_rw_intake_ensure_canonical_contract_cartable_task(
            $conn,
            $requestId,
            $requestRow,
            $payload,
            $contractId,
            $tokenHash,
            $expires,
            'SYSTEM',
            'ensure_db_contract'
        );
        $payload['reception_intake']['contract']['contract_id'] = (string)$contractId;
        $payload['reception_intake']['customer_cartable']['contract_task']['contract_id'] = (string)$contractId;
        m360_rw_intake_persist_payload($conn, $requestId, $payload, []);
    }

    return ['ok' => $contractId > 0, 'contract_id' => $contractId, 'message' => ''];
}

/**
 * @param array<string, mixed> $requestRow
 * @param array<string, mixed> $payload
 * @return array{ok:bool,task_id:int,created_new:bool,message:string}
 */
function m360_rw_intake_ensure_canonical_contract_cartable_task(
    $conn,
    int $requestId,
    array $requestRow,
    array $payload,
    int $contractId,
    string $tokenHash,
    string $tokenExpiresAt,
    string $actorType,
    ?string $actorId
): array {
    $empty = ['ok' => false, 'task_id' => 0, 'created_new' => false, 'message' => ''];
    if (!is_resource($conn) || $requestId < 1 || $contractId < 1 || !m360_cartable_tables_available($conn)) {
        return array_merge($empty, ['message' => 'unavailable']);
    }

    $mobile = m360_rw_intake_resolve_mobile_for_otp($requestRow, $payload);
    $mobile = m360_cartable_normalize_mobile($mobile);
    if ($mobile === '') {
        return array_merge($empty, ['message' => 'missing_mobile']);
    }

    $customerId = (int)($requestRow['customer_id'] ?? 0);
    if ($customerId < 1) {
        $customerRow = m360_rw_customer_profile_fetch_customer_row($conn, $mobile);
        $customerId = (int)($customerRow['customer_id'] ?? 0);
    }

    $expiresAt = trim($tokenExpiresAt) !== '' ? str_replace('T', ' ', substr($tokenExpiresAt, 0, 19)) : null;
    $created = m360_cartable_create_or_get_active($conn, [
        'customer_id' => $customerId > 0 ? $customerId : null,
        'customer_mobile_normalized' => $mobile,
        'task_type' => M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE,
        'title' => M360_CARTABLE_CONTRACT_TITLE_FA,
        'message' => M360_CARTABLE_CONTRACT_MESSAGE_FA,
        'priority' => M360_CARTABLE_CONTRACT_PRIORITY,
        'status' => M360_CARTABLE_STATUS_PENDING,
        'source_module' => M360_CARTABLE_SOURCE_MODULE_INTAKE_CONTRACT,
        'source_entity_type' => M360_CARTABLE_SOURCE_ENTITY_TYPE_INTAKE_CONTRACT,
        'source_entity_id' => (string)$contractId,
        'online_request_id' => $requestId,
        'contract_id' => $contractId,
        'action_route' => M360_CARTABLE_CONTRACT_ACTION_ROUTE,
        'action_token_hash' => trim($tokenHash) !== '' ? trim($tokenHash) : null,
        'action_expires_at' => $expiresAt,
        'created_by_actor_type' => $actorType,
        'created_by_actor_id' => $actorId,
        'event_type' => M360_CARTABLE_EVENT_SYNCED_FROM_MODULE,
        'event_metadata' => ['online_request_id' => $requestId, 'contract_id' => $contractId],
    ]);

    if (!$created['ok']) {
        return array_merge($empty, ['message' => (string)$created['message']]);
    }

    if ($created['created_new']) {
        m360_cartable_append_event(
            $conn,
            (int)$created['task_id'],
            M360_CARTABLE_EVENT_SYNCED_TO_COMPATIBILITY_PAYLOAD,
            $actorType,
            $actorId,
            null,
            null,
            ['online_request_id' => $requestId, 'contract_id' => $contractId]
        );
    }

    return [
        'ok' => true,
        'task_id' => (int)$created['task_id'],
        'created_new' => (bool)$created['created_new'],
        'message' => '',
    ];
}

/**
 * @param array<string, mixed> $contractRow
 * @return array{ok:bool,message:string,changed:bool,compatibility_only:bool}
 */
function m360_rw_intake_complete_canonical_contract_cartable_task(
    $conn,
    array $contractRow,
    string $completedChannel = 'CUSTOMER_PORTAL'
): array {
    $empty = ['ok' => false, 'message' => 'unavailable', 'changed' => false, 'compatibility_only' => false];
    if (!is_resource($conn) || !m360_cartable_tables_available($conn)) {
        return $empty;
    }

    $contractId = (int)($contractRow['contract_id'] ?? 0);
    if ($contractId < 1) {
        return array_merge($empty, ['message' => 'missing_contract']);
    }

    $task = m360_cartable_find_active_by_source(
        $conn,
        M360_CARTABLE_SOURCE_MODULE_INTAKE_CONTRACT,
        M360_CARTABLE_SOURCE_ENTITY_TYPE_INTAKE_CONTRACT,
        (string)$contractId,
        M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE
    );
    if ($task === null) {
        return ['ok' => true, 'message' => 'no_canonical_task', 'changed' => false, 'compatibility_only' => true];
    }

    $result = m360_cartable_complete_task(
        $conn,
        (int)$task['task_id'],
        'CUSTOMER',
        m360_cartable_normalize_mobile((string)($contractRow['mobile'] ?? '')),
        $completedChannel,
        ['contract_id' => $contractId, 'online_request_id' => (int)($contractRow['online_request_id'] ?? 0)]
    );

    return [
        'ok' => $result['ok'],
        'message' => (string)$result['message'],
        'changed' => (bool)$result['changed'],
        'compatibility_only' => false,
    ];
}

/**
 * @param resource $conn
 * @param array<string, mixed> $contractRow
 */
function m360_rw_intake_sync_cartable_from_signed_contract($conn, array $contractRow): void
{
    if (!is_resource($conn)) {
        return;
    }
    $requestId = (int)($contractRow['online_request_id'] ?? 0);
    $contractId = (int)($contractRow['contract_id'] ?? 0);
    if ($requestId < 1 || $contractId < 1) {
        return;
    }
    $request = m360_online_req_fetch_by_id($conn, $requestId);
    if ($request === null) {
        return;
    }
    $payload = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
    $payload = m360_rw_intake_payload_for_recovery($payload);
    if (m360_rw_intake_contract_customer_accepted($payload)) {
        return;
    }

    $payload = m360_rw_intake_ensure_nested($payload);
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $mobile = trim((string)($contractRow['mobile'] ?? ''));
    $task = m360_rw_intake_contract_cartable_task($payload);
    $payload['reception_intake']['customer_cartable']['contract_task'] = array_merge($task, [
        'status' => M360_RW_INTAKE_CARTABLE_STATUS_CUSTOMER_ACCEPTED,
        'title' => M360_RW_INTAKE_CARTABLE_TASK_TITLE_FA,
        'contract_id' => (string)$contractId,
        'accepted_at' => gmdate('Y-m-d H:i:s'),
        'completed_at' => gmdate('Y-m-d H:i:s'),
        'accepted_mobile' => $mobile,
        'acceptance_method' => 'contract_signature_otp_confirmed',
        'review_url_path' => '',
    ]);
    $payload['reception_intake']['contract'] = array_merge(
        is_array($payload['reception_intake']['contract'] ?? null) ? $payload['reception_intake']['contract'] : [],
        [
            'status' => M360_RW_INTAKE_CONTRACT_STATUS_CUSTOMER_ACCEPTED,
            'contract_id' => (string)$contractId,
            'customer_accepted_at' => $now,
            'acceptance_method' => 'contract_signature_otp_confirmed',
            'contract_body_hash' => (string)($contractRow['contract_body_hash'] ?? ''),
        ]
    );
    $payload['reception_intake']['documents']['contract_status'] = M360_RW_INTAKE_DOC_CONTRACT_STATUS_CUSTOMER_ACCEPTED;
    $payload['contract_status'] = M360_RW_INTAKE_CONTRACT_STATUS_CUSTOMER_ACCEPTED;
    if (!isset($payload['reception_intake']['operation_gate']) || !is_array($payload['reception_intake']['operation_gate'])) {
        $payload['reception_intake']['operation_gate'] = [];
    }
    $payload['reception_intake']['operation_gate']['contract_customer_confirmed'] = true;
    $payload['reception_intake']['operation_gate']['contract_confirmed_at'] = $now;
    $payload['reception_intake']['operation_gate']['contract_pending_customer_review'] = false;
    if (m360_rw_intake_reception_is_completed($payload)) {
        $payload['reception_intake']['operation_gate']['hall_manager_allowed'] = true;
        $payload['reception_intake']['operation_gate']['message_fa'] = '';
    }

    m360_rw_intake_persist_payload($conn, $requestId, $payload, []);
    m360_online_req_write_history(
        $conn,
        $requestId,
        'CUSTOMER_CONTRACT_SIGNATURE_CONFIRMED',
        (string)($request['request_status'] ?? ''),
        (string)($request['request_status'] ?? ''),
        'contract_id=' . $contractId,
        null
    );
}

function m360_rw_intake_store_contract_review_token_once(int $requestId, string $rawToken): void
{
    if ($requestId < 1 || trim($rawToken) === '') {
        return;
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['m360_rw_contract_token_' . $requestId] = $rawToken;
}

function m360_rw_intake_consume_contract_review_token_once(int $requestId): string
{
    if ($requestId < 1) {
        return '';
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $key = 'm360_rw_contract_token_' . $requestId;
    $token = trim((string)($_SESSION[$key] ?? ''));
    unset($_SESSION[$key]);

    return $token;
}

/**
 * @param array<string, mixed> $payloadData
 * @param array<string, mixed> $request
 */
function m360_rw_intake_render_documents_contract_staff_block(
    int $onlineRequestId,
    array $payloadData,
    array $request,
    string $csrfInputHtml,
    string $saveUrl,
    bool $canShowStepForm
): void {
    $uiState = m360_rw_intake_documents_cartable_ui_state($payloadData, $request);
    $prereq = m360_rw_intake_contract_cartable_prerequisites($payloadData, $request);
    $contractStatus = m360_rw_intake_contract_status_label_fa($payloadData);

    echo '<div class="m360-rw-contract-staff-block">';
    echo '<p class="m360-rw-muted">وضعیت قرارداد: <strong>' . m360_rw_h($contractStatus) . '</strong></p>';

    if ($uiState === 'not_ready') {
        echo '<div class="m360-rw-alert m360-rw-cartable-blockers">';
        echo '<p><strong>پرونده هنوز آماده ارسال به کارتابل مشتری نیست.</strong></p>';
        echo '<p>موارد ناقص:</p><ul>';
        foreach ($prereq['blockers'] as $blocker) {
            echo '<li>' . m360_rw_h((string)($blocker['label_fa'] ?? '')) . '</li>';
        }
        echo '</ul>';
        $firstStep = trim((string)($prereq['first_blocker_step'] ?? ''));
        if ($firstStep !== '' && $firstStep !== 'documents') {
            $actionLabel = m360_rw_intake_cartable_blocker_label_fa($firstStep, 'action');
            $goUrl = m360_rw_intake_step_go_url($onlineRequestId, $firstStep);
            echo '<a class="m360-rw-btn m360-rw-btn-secondary" href="' . m360_rw_h($goUrl) . '">' . m360_rw_h($actionLabel) . '</a>';
        } elseif ($firstStep === 'documents') {
            echo '<p class="m360-rw-muted">لطفاً دیاگ و توافق هزینه را در همین مرحله تکمیل کنید.</p>';
        }
        echo '</div>';
    } elseif ($uiState === 'ready') {
        if ($canShowStepForm) {
            echo '<form class="m360-rw-form" method="post" action="' . m360_rw_h($saveUrl) . '">';
            echo $csrfInputHtml;
            echo '<input type="hidden" name="online_request_id" value="' . $onlineRequestId . '">';
            echo '<input type="hidden" name="action_type" value="prepare_customer_contract_review">';
            m360_rw_intake_return_step_hidden('documents');
            echo '<input type="hidden" name="return_section" value="section-contract">';
            echo '<button type="submit" class="m360-rw-btn m360-rw-btn-secondary">ایجاد مأموریت قرارداد در کارتابل مشتری</button>';
            echo '</form>';
            echo '<p class="m360-rw-muted">پس از تکمیل پذیرش و تأیید قرارداد توسط مشتری، ارسال به مسئول سالن فعال می‌شود.</p>';
        }
    } elseif ($uiState === 'pending') {
        echo '<div class="m360-rw-flash is-info">';
        echo '<p><strong>مأموریت قرارداد در کارتابل مشتری فعال است.</strong></p>';
        echo '<p>وضعیت: در انتظار تأیید مشتری</p>';
        echo '</div>';
        $onceToken = m360_rw_intake_consume_contract_review_token_once($onlineRequestId);
        if ($onceToken !== '') {
            $reviewUrl = m360_rw_intake_contract_review_url($onceToken);
            echo '<div class="m360-rw-contract-link-box">';
            echo '<p class="m360-rw-muted">لینک موقت دسترسی به کارتابل مشتری برای V1 RC:</p>';
            echo '<a class="m360-rw-btn" href="' . m360_rw_h($reviewUrl) . '" target="_blank" rel="noopener">باز کردن کارتابل مشتری</a>';
            echo '</div>';
        }
    } elseif ($uiState === 'accepted') {
        echo '<p class="m360-rw-flash is-ok">قرارداد توسط مشتری تأیید شده است.</p>';
    }

    $contractText = trim((string)($payloadData['reception_intake']['contract']['contract_text'] ?? ''));
    if ($contractText !== '') {
        echo '<div class="m360-rw-contract-text m360-rw-contract-compact">' . m360_rw_h(mb_substr($contractText, 0, 400)) . (mb_strlen($contractText) > 400 ? '…' : '') . '</div>';
    }
    echo '</div>';
}

/**
 * @param resource $conn
 * @return array{ok:bool,message:string}
 */
function m360_rw_intake_process_customer_contract_accept($conn, string $rawToken, array $post, array $server = []): array
{
    if (!is_resource($conn)) {
        return ['ok' => false, 'message' => 'اتصال به پایگاه داده برقرار نشد.'];
    }
    $requestId = m360_rw_intake_contract_parse_review_token($rawToken);
    if ($requestId < 1) {
        return ['ok' => false, 'message' => 'مأموریت قرارداد مشتری یافت نشد یا منقضی شده است.'];
    }
    $request = m360_online_req_fetch_by_id($conn, $requestId);
    if ($request === null) {
        return ['ok' => false, 'message' => 'درخواست یافت نشد.'];
    }
    if (m360_online_req_is_converted($request)) {
        return ['ok' => false, 'message' => 'این درخواست قبلاً تبدیل شده است.'];
    }
    $payload = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
    $payload = m360_rw_intake_payload_for_recovery($payload);
    if (m360_rw_intake_is_locked($payload)) {
        return ['ok' => false, 'message' => M360_RW_INTAKE_LOCK_MESSAGE_FA];
    }
    $tokenCheck = m360_rw_intake_contract_validate_review_token($payload, $requestId, $rawToken);
    if (!$tokenCheck['ok']) {
        return ['ok' => false, 'message' => $tokenCheck['error']];
    }
    if (m360_rw_intake_contract_customer_accepted($payload)) {
        return ['ok' => true, 'message' => 'قرارداد پذیرش قبلاً تأیید شده است.'];
    }
    $accepted = isset($post['customer_accepts_contract']) && (string)$post['customer_accepts_contract'] === '1';
    if ($accepted) {
        return ['ok' => false, 'message' => 'تأیید قرارداد فقط پس از مطالعه کامل، پذیرش صریح، امضای مستقیم و OTP قرارداد امکان‌پذیر است.'];
    }
    return ['ok' => false, 'message' => 'عملیات نامعتبر است.'];
}

function m360_rw_intake_contract_status_label_fa(array $payload): string
{
    if (m360_rw_intake_contract_customer_accepted($payload)) {
        return 'تأیید شده توسط مشتری';
    }
    if (m360_rw_intake_contract_cartable_pending($payload)) {
        return 'در انتظار تأیید مشتری در کارتابل';
    }
    $contract = is_array($payload['reception_intake']['contract'] ?? null) ? $payload['reception_intake']['contract'] : [];
    $status = trim((string)($contract['status'] ?? ''));
    if (in_array($status, [
        M360_RW_INTAKE_CONTRACT_STATUS_PREPARED,
        M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW,
    ], true)) {
        return 'در انتظار تأیید مشتری در کارتابل';
    }

    return 'ثبت نشده';
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $requestRow
 * @return array<string, string>
 */
function m360_rw_intake_contract_review_summary(array $payload, array $requestRow): array
{
    $vehicle = m360_rw_intake_vehicle_canonical($payload, $requestRow);
    $photos = m360_rw_intake_photos_canonical($payload);
    $formValues = m360_rw_intake_form_values($payload, $requestRow);

    return [
        'customer_name' => trim((string)($requestRow['customer_name'] ?? '')),
        'mobile' => m360_rw_intake_resolve_mobile_for_otp($requestRow, $payload),
        'plate' => $vehicle['plate'],
        'brand_model' => trim($vehicle['brand'] . ' / ' . $vehicle['model'], ' /'),
        'service_route' => (string)($formValues['service_primary'] ?? ''),
        'service_subcategories' => is_array($formValues['service_diag_sub_codes'] ?? null)
            ? implode('، ', $formValues['service_diag_sub_codes'])
            : '',
        'photos' => (string)($photos['completed_count'] ?? 0) . '/' . (string)($photos['required_count'] ?? 6),
        'diagnostic' => trim((string)($formValues['diagnostic_status'] ?? '')),
        'cost_agreement' => trim((string)($formValues['cost_agreement'] ?? '')),
    ];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $requestRow
 * @param array<string, string> $server
 * @return array{ok:bool,error:string,payload:array<string,mixed>}
 */
function m360_rw_intake_apply_customer_contract_acceptance(array $payload, array $requestRow, array $server = []): array
{
    if (!m360_online_req_payload_otp_verified($requestRow)) {
        return ['ok' => false, 'error' => 'ابتدا احراز هویت موبایل مشتری در پذیرش باید با OTP انجام شود.', 'payload' => $payload];
    }

    $payload = m360_rw_intake_ensure_nested($payload);
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $mobile = m360_rw_intake_resolve_mobile_for_otp($requestRow, $payload);
    if (!isset($payload['reception_intake']['customer_cartable']) || !is_array($payload['reception_intake']['customer_cartable'])) {
        $payload['reception_intake']['customer_cartable'] = [];
    }
    $task = m360_rw_intake_contract_cartable_task($payload);
    $payload['reception_intake']['customer_cartable']['contract_task'] = array_merge($task, [
        'status' => M360_RW_INTAKE_CARTABLE_STATUS_CUSTOMER_ACCEPTED,
        'accepted_at' => gmdate('Y-m-d H:i:s'),
        'accepted_mobile' => $mobile,
        'acceptance_method' => 'otp_verified_identity_and_customer_cartable_confirmation',
        'acceptance_ip' => trim((string)($server['REMOTE_ADDR'] ?? '')),
        'acceptance_user_agent' => trim(substr((string)($server['HTTP_USER_AGENT'] ?? ''), 0, 500)),
    ]);
    if (!isset($payload['reception_intake']['contract']) || !is_array($payload['reception_intake']['contract'])) {
        $payload['reception_intake']['contract'] = [];
    }
    $payload['reception_intake']['contract'] = array_merge($payload['reception_intake']['contract'], [
        'status' => M360_RW_INTAKE_CONTRACT_STATUS_CUSTOMER_ACCEPTED,
        'customer_accepted_at' => $now,
        'customer_accepted_mobile' => $mobile,
        'acceptance_method' => 'otp_verified_identity_and_customer_cartable_confirmation',
        'acceptance_ip' => trim((string)($server['REMOTE_ADDR'] ?? '')),
        'acceptance_user_agent' => trim(substr((string)($server['HTTP_USER_AGENT'] ?? ''), 0, 500)),
    ]);
    if (!isset($payload['reception_intake']['documents']) || !is_array($payload['reception_intake']['documents'])) {
        $payload['reception_intake']['documents'] = [];
    }
    $payload['reception_intake']['documents']['contract_status'] = M360_RW_INTAKE_DOC_CONTRACT_STATUS_CUSTOMER_ACCEPTED;
    $payload['contract_status'] = M360_RW_INTAKE_CONTRACT_STATUS_CUSTOMER_ACCEPTED;
    m360_rw_intake_mark_section_saved($payload, 'contract');

    return ['ok' => true, 'error' => '', 'payload' => $payload];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, string> $formValues
 * @return array{complete:bool,reason:string,missing_fields:list<string>}
 */
function m360_rw_intake_documents_step_state(array $payload, array $formValues): array
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $docs = is_array($payload['reception_intake']['documents'] ?? null) ? $payload['reception_intake']['documents'] : [];
    $contract = is_array($payload['reception_intake']['contract'] ?? null) ? $payload['reception_intake']['contract'] : [];
    $missing = [];

    $diag = trim((string)($formValues['diagnostic_status'] ?? '')) !== ''
        || trim((string)($docs['diagnostic_status'] ?? '')) !== ''
        || trim((string)($docs['diagnostic_pdf'] ?? '')) !== ''
        || trim((string)($formValues['diagnostic_pdf'] ?? '')) !== '';
    if (!$diag) {
        $missing[] = 'diagnostic_status';
    }

    $cost = trim((string)($formValues['cost_agreement'] ?? '')) !== ''
        || trim((string)($docs['cost_agreement'] ?? '')) !== ''
        || trim((string)($payload['cost_agreement'] ?? '')) !== '';
    if (!$cost) {
        $missing[] = 'cost_agreement';
    }

    $complete = $missing === [];

    return [
        'complete' => $complete,
        'reason' => $complete ? 'documents complete' : 'documents incomplete',
        'missing_fields' => $missing,
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{complete:bool,reason:string,missing_fields:list<string>}
 */
function m360_rw_intake_signature_step_state(array $payload): array
{
    if (m360_rw_intake_is_locked($payload)) {
        return ['complete' => true, 'reason' => 'intake locked', 'missing_fields' => []];
    }

    $missing = [];
    $sig = is_array($payload['reception_intake']['customer_signature'] ?? null)
        ? $payload['reception_intake']['customer_signature']
        : [];
    if (trim((string)($sig['status'] ?? '')) !== 'signed' && trim((string)($sig['signed_at'] ?? '')) === '') {
        $missing[] = 'customer_signature';
    }
    $confirm = is_array($payload['reception_intake']['reception_confirmation'] ?? null)
        ? $payload['reception_intake']['reception_confirmation']
        : [];
    if (empty($confirm['confirmed_by_receptionist']) && (string)($payload['reception_final_confirmation'] ?? '') !== '1') {
        $missing[] = 'receptionist_confirmation';
    }

    return [
        'complete' => false,
        'reason' => $missing === [] ? 'ready for signature and lock' : 'signature incomplete',
        'missing_fields' => $missing,
    ];
}

/**
 * @param array<string, array{complete:bool,reason:string,missing_fields:list<string>}> $steps
 */
function m360_rw_intake_wizard_blocker_message(array $steps, string $firstIncomplete): string
{
    if ($firstIncomplete === 'documents') {
        $missing = $steps['documents']['missing_fields'] ?? [];
        if ($missing !== []) {
            return 'مستندات ناقص است: ' . implode('، ', $missing);
        }
    }
    if ($firstIncomplete === 'signature') {
        return '';
    }
    if ($firstIncomplete === 'referral') {
        return '';
    }
    if (in_array($firstIncomplete, m360_rw_intake_reception_completion_keys(), true)) {
        $missing = $steps[$firstIncomplete]['missing_fields'] ?? [];
        if ($missing !== []) {
            return 'تکمیل پذیرش ناقص است: ' . implode('، ', $missing);
        }
    }

    return '';
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $requestRow
 * @return array{
 *   steps:array<string,array{complete:bool,reason:string,missing_fields:list<string>}>,
 *   first_incomplete:string,
 *   resolved_active_step:string,
 *   blocker_message:string
 * }
 */
function m360_rw_intake_get_wizard_step_state(array $payload, array $requestRow): array
{
    $payload = m360_rw_intake_payload_for_recovery($payload);
    $formValues = m360_rw_intake_form_values($payload, $requestRow);
    $steps = [];

    $otpComplete = trim((string)($requestRow['mobile'] ?? '')) !== '' && m360_online_req_payload_otp_verified($requestRow);
    $steps['otp'] = [
        'complete' => $otpComplete,
        'reason' => $otpComplete ? 'otp_verified=1' : 'OTP not verified',
        'missing_fields' => $otpComplete ? [] : ['otp_verified'],
    ];

    $vehicleComplete = m360_rw_intake_vehicle_step_complete($payload, $requestRow);
    $vehicleMissing = [];
    if (!$vehicleComplete) {
        foreach (m360_rw_intake_vehicle_canonical($payload, $requestRow) as $field => $value) {
            if (in_array($field, ['plate', 'brand', 'model', 'mileage', 'fuel_level'], true) && trim($value) === '') {
                $vehicleMissing[] = $field;
            }
        }
    }
    $steps['vehicle'] = [
        'complete' => $vehicleComplete,
        'reason' => $vehicleComplete ? 'canonical vehicle complete' : 'vehicle fields missing',
        'missing_fields' => $vehicleMissing,
    ];

    $conditionComplete = m360_rw_intake_condition_step_complete($payload);
    $conditionMissing = [];
    if (!$conditionComplete) {
        foreach (m360_rw_intake_condition_canonical($payload) as $field => $value) {
            if (trim($value) === '') {
                $conditionMissing[] = $field;
            }
        }
    }
    $steps['condition'] = [
        'complete' => $conditionComplete,
        'reason' => $conditionComplete ? 'canonical condition complete' : 'condition fields missing',
        'missing_fields' => $conditionMissing,
    ];

    $serviceComplete = m360_rw_intake_service_wizard_step_complete(
        $formValues,
        m360_rw_request_service_policy_group(trim((string)m360_rw_pick([$requestRow, $payload], 'request_type')))
    );
    $serviceMissing = [];
    $policyGroup = m360_rw_request_service_policy_group(trim((string)m360_rw_pick([$requestRow, $payload], 'request_type')));
    if (!$serviceComplete) {
        if ($policyGroup === 'TECHNICAL_DIAGNOSIS') {
            if (trim((string)($formValues['service_route'] ?? $formValues['service_primary'] ?? '')) === '') {
                $serviceMissing[] = 'service_route';
            }
            if ((string)($formValues['service_path_clear'] ?? '') !== '1') {
                $serviceMissing[] = 'service_path_clear';
            }
            $route = trim((string)($formValues['service_route'] ?? $formValues['service_primary'] ?? ''));
            if ($route === 'diag') {
                $subs = $formValues['service_diag_sub_codes'] ?? [];
                if (!is_array($subs) || $subs === []) {
                    $serviceMissing[] = 'diagnostic_subcategories';
                }
            }
        } elseif ($policyGroup === 'UNKNOWN_FAULT') {
            $serviceMissing[] = 'temporary_reception_or_path';
        } else {
            $serviceMissing[] = 'service_reception_confirmation';
        }
    }
    $steps['service'] = [
        'complete' => $serviceComplete,
        'reason' => $serviceComplete ? 'canonical service complete' : 'service classification incomplete',
        'missing_fields' => $serviceMissing,
    ];

    $referralComplete = m360_rw_intake_referral_step_complete($payload);
    $referralMissing = [];
    if (!$referralComplete && m360_rw_intake_operation_gate_hall_manager_allowed($payload)) {
        $referralMissing[] = 'hall_manager_send';
    }
    $steps['referral'] = [
        'complete' => $referralComplete,
        'reason' => $referralComplete ? 'hall manager gate complete' : 'post-reception operational gate',
        'missing_fields' => $referralMissing,
    ];

    $photosComplete = m360_rw_intake_photos_complete($payload);
    $steps['photos'] = [
        'complete' => $photosComplete,
        'reason' => $photosComplete ? 'canonical photos 6/6 complete' : 'photos incomplete',
        'missing_fields' => $photosComplete ? [] : ['photos'],
    ];

    $steps['documents'] = m360_rw_intake_documents_step_state($payload, $formValues);
    $steps['signature'] = m360_rw_intake_signature_step_state($payload);
    $steps['locked_summary'] = [
        'complete' => m360_rw_intake_is_locked($payload) && m360_rw_intake_hall_manager_step_complete($payload),
        'reason' => m360_rw_intake_is_locked($payload) ? 'locked summary available' : 'not locked',
        'missing_fields' => (m360_rw_intake_is_locked($payload) && m360_rw_intake_hall_manager_step_complete($payload)) ? [] : ['intake_lock'],
    ];

    $firstIncomplete = 'documents';
    if (!m360_online_req_payload_otp_verified($requestRow)) {
        $firstIncomplete = 'otp';
    } else {
        foreach (m360_rw_intake_reception_completion_keys() as $stepKey) {
            if (empty($steps[$stepKey]['complete'])) {
                $firstIncomplete = $stepKey;
                break;
            }
        }
    }

    return [
        'steps' => $steps,
        'first_incomplete' => $firstIncomplete,
        'resolved_active_step' => $firstIncomplete,
        'blocker_message' => m360_rw_intake_wizard_blocker_message($steps, $firstIncomplete),
    ];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $requestRow
 */
function m360_rw_intake_render_wizard_blocker_notice(array $payload, array $requestRow, string $activeStep): void
{
    $state = m360_rw_intake_get_wizard_step_state($payload, $requestRow);
    $message = trim((string)($state['blocker_message'] ?? ''));
    if ($message === '') {
        return;
    }
    if ($activeStep !== ($state['first_incomplete'] ?? '') && $activeStep !== 'signature' && $activeStep !== 'documents') {
        return;
    }
    echo '<div class="m360-rw-flash is-err m360-rw-wizard-blocker">' . m360_rw_h($message) . '</div>';
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $requestRow
 */
function m360_rw_intake_render_signature_checklist(array $payload, array $requestRow): void
{
    $sig = m360_rw_intake_get_wizard_step_state($payload, $requestRow)['steps']['signature'] ?? [];
    echo '<ul class="m360-rw-signature-checklist">';
    echo '<li class="' . (m360_rw_intake_contract_customer_accepted($payload) ? 'is-done' : 'is-info') . '">قرارداد (پس از تکمیل پذیرش)</li>';
    echo '<li class="' . (!in_array('customer_signature', $sig['missing_fields'] ?? [], true) ? 'is-done' : 'is-info') . '">امضای مشتری (عملیات)</li>';
    echo '<li class="' . (!in_array('receptionist_confirmation', $sig['missing_fields'] ?? [], true) ? 'is-done' : 'is-info') . '">تأیید نهایی پذیرشگر (عملیات)</li>';
    echo '</ul>';
    if (m360_rw_intake_reception_is_completed($payload)) {
        echo '<p class="m360-rw-muted">پذیرش ثبت شده است؛ موارد بالا مانع تکمیل پذیرش نیستند.</p>';
    }
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $request
 * @param array<string, string> $formValues
 */
function m360_rw_intake_wizard_furthest_operational_step(?array $request, array $payload, array $formValues): string
{
    if ($request === null) {
        return 'otp';
    }

    return m360_rw_intake_get_wizard_step_state($payload, $request)['first_incomplete'];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $request
 * @param array<string, string> $formValues
 */
function m360_rw_intake_wizard_prev_amendable_step(string $activeStep, array $payload, array $request, array $formValues): ?string
{
    if (m360_rw_intake_is_locked($payload) || $activeStep === 'signature' || $activeStep === 'locked_summary') {
        return null;
    }

    $keys = m360_rw_intake_wizard_operational_keys();
    $idx = array_search($activeStep, $keys, true);
    if ($idx === false || $idx < 1) {
        return null;
    }

    for ($i = $idx - 1; $i >= 0; $i--) {
        if (m360_rw_intake_wizard_step_is_complete($keys[$i], $payload, $request, $formValues)) {
            return $keys[$i];
        }
    }

    return null;
}

function m360_rw_intake_wizard_amend_url(int $onlineRequestId, string $stepKey): string
{
    return 'erp-reception-intake-file.php?online_request_id=' . $onlineRequestId
        . '&active_step=' . rawurlencode($stepKey)
        . '&wizard_edit=1';
}

/**
 * @return array<string, array{hash:string,label:string,num:int,sections:list<string>}>
 */
function m360_rw_intake_stepper_definition(): array
{
    return [
        'otp' => ['hash' => 'step-otp', 'label' => 'موبایل و OTP', 'num' => 1, 'sections' => ['mobile_otp']],
        'vehicle' => ['hash' => 'step-vehicle', 'label' => 'خودرو و پلاک', 'num' => 2, 'sections' => ['vehicle_identity']],
        'condition' => ['hash' => 'step-condition', 'label' => 'وضعیت خودرو', 'num' => 3, 'sections' => ['condition_notes']],
        'service' => ['hash' => 'step-service', 'label' => 'خدمات و مسیر عیب', 'num' => 4, 'sections' => ['service_classification', 'temporary_reception']],
        'photos' => ['hash' => 'step-photos', 'label' => 'عکس‌های پذیرش', 'num' => 5, 'sections' => ['camera_photo']],
        'documents' => ['hash' => 'step-documents', 'label' => 'دیاگ / قرارداد / توافق هزینه', 'num' => 6, 'sections' => ['diagnostic_pdf', 'contract', 'documents_cost']],
        'signature' => ['hash' => 'step-signature', 'label' => 'امضای مشتری و تأیید نهایی', 'num' => 7, 'sections' => ['reception_confirmation']],
        'referral' => ['hash' => 'step-referral', 'label' => 'ارسال به مسئول سالن', 'num' => 8, 'sections' => ['referral']],
        'locked_summary' => ['hash' => 'step-locked', 'label' => 'پرونده قفل‌شده پذیرش', 'num' => 9, 'sections' => []],
    ];
}

/** @return list<string> */
function m360_rw_intake_stepper_keys(): array
{
    return array_keys(m360_rw_intake_stepper_definition());
}

function m360_rw_intake_step_hash(string $stepKey): string
{
    $def = m360_rw_intake_stepper_definition();

    return (string)($def[$stepKey]['hash'] ?? '');
}

function m360_rw_intake_step_primary_section(string $stepKey): string
{
    $def = m360_rw_intake_stepper_definition();
    $sections = $def[$stepKey]['sections'] ?? [];

    return $sections !== [] ? (string)$sections[0] : '';
}

function m360_rw_intake_section_to_step(string $sectionKey): string
{
    foreach (m360_rw_intake_stepper_definition() as $stepKey => $meta) {
        if (in_array($sectionKey, $meta['sections'], true)) {
            return (string)$stepKey;
        }
    }

    return '';
}

function m360_rw_intake_action_to_step(string $actionType): string
{
    return match ($actionType) {
        'save_mobile_correction', 'send_customer_otp', 'verify_customer_otp' => 'otp',
        'save_vehicle_identity' => 'vehicle',
        'save_condition_notes' => 'condition',
        'save_service_classification', 'save_temporary_reception' => 'service',
        'save_referral_team' => 'referral',
        'send_to_hall_manager' => 'referral',
        'save_camera_photo' => 'photos',
        'save_diagnostic_pdf', 'prepare_customer_contract_review', 'save_documents_and_cost', 'complete_reception_intake' => 'documents',
        'save_reception_confirmation', 'sign_and_lock_intake' => 'signature',
        default => 'otp',
    };
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $request
 * @param array<string, string> $formValues
 */
function m360_rw_intake_wizard_step_is_complete(string $stepKey, array $payload, array $request, array $formValues): bool
{
    $state = m360_rw_intake_get_wizard_step_state($payload, $request);

    return !empty($state['steps'][$stepKey]['complete']);
}

/**
 * @param array<string, string> $formValues
 */
function m360_rw_intake_step_is_complete(string $stepKey, array $payload, array $request, array $formValues): bool
{
    if ($stepKey === 'final') {
        $stepKey = 'signature';
    }

    return m360_rw_intake_wizard_step_is_complete($stepKey, $payload, $request, $formValues);
}

/**
 * @param array<string, mixed> $query
 * @param array<string, string> $formValues
 */
function m360_rw_intake_resolve_active_step(array $query, ?array $request, array $payload, array $formValues): string
{
    if (m360_rw_intake_is_locked($payload)) {
        if (!m360_rw_intake_hall_manager_step_complete($payload)) {
            return 'referral';
        }

        return 'locked_summary';
    }

    $requestRow = $request ?? [];
    $state = m360_rw_intake_get_wizard_step_state($payload, $requestRow);
    $furthest = (string)($state['first_incomplete'] ?? 'otp');

    $operational = m360_rw_intake_wizard_operational_keys();
    $requested = trim((string)($query['active_step'] ?? ''));
    if ($requested === 'final') {
        $requested = 'signature';
    }

    $wizardEdit = isset($query['wizard_edit']) && (string)$query['wizard_edit'] === '1';

    if ($requested === 'documents' && !$wizardEdit) {
        return 'documents';
    }

    if ($requested !== '' && in_array($requested, array_merge($operational, ['locked_summary']), true)) {
        if ($requested === 'locked_summary') {
            return $furthest;
        }

        $reqIdx = array_search($requested, $operational, true);
        $furIdx = array_search($furthest, $operational, true);
        if ($reqIdx !== false && $furIdx !== false) {
            if ($wizardEdit
                && $requested !== 'signature'
                && !empty($state['steps'][$requested]['complete'])) {
                return $requested;
            }

            $priorComplete = true;
            for ($i = 0; $i < $reqIdx; $i++) {
                $priorKey = $operational[$i];
                if (empty($state['steps'][$priorKey]['complete'])) {
                    $priorComplete = false;
                    break;
                }
            }

            if ($priorComplete && $reqIdx >= $furIdx) {
                return $requested;
            }

            if ($reqIdx === $furIdx) {
                return $requested;
            }

            if ($reqIdx < $furIdx && !empty($state['steps'][$requested]['complete']) && !$wizardEdit) {
                return $furthest;
            }
        }
    }

    return $furthest;
}

/**
 * @param array<string, string> $post
 * @return array{active_step:string,hash:string,extra:array<string,string>}
 */
function m360_rw_intake_redirect_active_step($conn, int $requestId, string $actionType, bool $ok, array $post): array
{
    $stayStep = trim((string)($post['return_active_step'] ?? ''));
    if ($stayStep === '') {
        $returnSection = trim((string)($post['return_section'] ?? ''));
        if ($returnSection !== '') {
            $sectionKey = m360_rw_intake_anchor_to_section_key(ltrim($returnSection, '#'));
            if ($sectionKey !== '') {
                $stayStep = m360_rw_intake_section_to_step($sectionKey);
            }
        }
    }
    if ($stayStep === '') {
        $stayStep = m360_rw_intake_action_to_step($actionType);
    }

    if (!$ok) {
        return [
            'active_step' => $stayStep,
            'hash' => m360_rw_intake_step_hash($stayStep),
            'extra' => [],
        ];
    }

    $extra = [];
    $nextStep = match ($actionType) {
        'save_mobile_correction' => 'otp',
        'send_customer_otp' => 'otp',
        'verify_customer_otp' => 'vehicle',
        'save_vehicle_identity' => 'condition',
        'save_condition_notes' => 'service',
        'save_service_classification', 'save_temporary_reception' => 'photos',
        'save_diagnostic_pdf', 'prepare_customer_contract_review', 'save_documents_and_cost', 'complete_reception_intake' => 'documents',
        'save_reception_confirmation' => 'signature',
        'sign_and_lock_intake' => 'referral',
        'send_to_hall_manager' => 'locked_summary',
        'save_camera_photo' => 'photos',
        default => $stayStep,
    };
    if ($actionType === 'send_customer_otp') {
        $extra['otp_sent'] = '1';
    }

    if ($actionType === 'save_camera_photo' && is_resource($conn) && $requestId > 0) {
        $request = m360_online_req_fetch_by_id($conn, $requestId);
        if ($request !== null) {
            $payload = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
            if (m360_rw_intake_photos_complete($payload)) {
                $nextStep = 'documents';
            }
        }
    }

    if ($actionType === 'save_service_classification' && is_resource($conn) && $requestId > 0) {
        $request = m360_online_req_fetch_by_id($conn, $requestId);
        if ($request !== null) {
            $payload = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
            $canonical = m360_rw_intake_service_classification_canonical($payload);
            $nextStep = $canonical['service_path_clear'] === '1' ? 'photos' : 'service';
        }
    }

    return [
        'active_step' => $nextStep,
        'hash' => m360_rw_intake_step_hash($nextStep),
        'extra' => $extra,
    ];
}

function m360_rw_intake_step_is_active(string $stepKey, string $activeStep): bool
{
    return $stepKey === $activeStep;
}

function m360_rw_intake_step_go_url(int $onlineRequestId, string $stepKey): string
{
    return 'erp-reception-intake-file.php?online_request_id=' . $onlineRequestId
        . '&active_step=' . rawurlencode($stepKey)
        . '#' . rawurlencode(m360_rw_intake_step_hash($stepKey));
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, string> $request
 */
function m360_rw_intake_resolve_mobile_for_otp(array $request, array $payload): string
{
    m360_rw_intake_load_otp_helper();

    $candidates = [];
    $correction = trim((string)($payload['reception_intake']['mobile_correction']['mobile'] ?? ''));
    if ($correction !== '') {
        $candidates[] = $correction;
    }
    foreach (['mobile_corrected', 'mobile'] as $payloadKey) {
        $val = trim((string)($payload[$payloadKey] ?? ''));
        if ($val !== '') {
            $candidates[] = $val;
        }
    }
    $nestedMobile = trim((string)($payload['reception_intake']['mobile']['corrected'] ?? ''));
    if ($nestedMobile !== '') {
        $candidates[] = $nestedMobile;
    }
    $rowMobile = trim((string)($request['mobile'] ?? ''));
    if ($rowMobile !== '') {
        $candidates[] = $rowMobile;
    }

    foreach ($candidates as $raw) {
        if (function_exists('m360_otp_normalize_phone')) {
            $normalized = m360_otp_normalize_phone($raw);
            if ($normalized !== null) {
                return $normalized;
            }
        }
        if (preg_match('/^09\d{9}$/', $raw)) {
            return $raw;
        }
    }

    return '';
}

function m360_rw_intake_flash_indicates_otp_sent(string $flashMsg): bool
{
    $msg = trim($flashMsg);
    if ($msg === '') {
        return false;
    }

    return str_contains($msg, 'پیامک OTP ارسال شد')
        || str_contains($msg, 'کد تأیید برای شما ارسال شد');
}

function m360_rw_intake_otp_show_verify_form(?array $request, bool $otpVerified, array $payload = []): bool
{
    if ($otpVerified || $request === null) {
        return false;
    }
    $ui = m360_rw_intake_otp_ui_status($payload, $request, false, false);

    return !empty($ui['show_verify_form']);
}

/**
 * @param array<string, string> $formValues
 */
function m360_rw_intake_step_nav_state(string $stepKey, string $activeStep, array $payload, array $request, array $formValues): array
{
    $complete = m360_rw_intake_step_is_complete($stepKey, $payload, $request, $formValues);
    $isActive = $stepKey === $activeStep;
    if ($isActive) {
        $label = 'در حال تکمیل';
    } elseif ($complete) {
        $label = 'تکمیل شده';
    } else {
        $label = 'نیازمند تکمیل';
    }

    return ['complete' => $complete, 'active' => $isActive, 'status_label' => $label];
}

/**
 * @param array<string, string> $formValues
 */
function m360_rw_intake_render_stepper_nav(int $onlineRequestId, string $activeStep, array $payload, array $request, array $formValues): void
{
    m360_rw_intake_render_wizard_progress($onlineRequestId, $activeStep, $payload, $request, $formValues);
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $request
 * @param array<string, string> $formValues
 */
function m360_rw_intake_render_wizard_progress(int $onlineRequestId, string $activeStep, array $payload, array $request, array $formValues): void
{
    $furthest = m360_rw_intake_is_locked($payload)
        ? 'locked_summary'
        : m360_rw_intake_wizard_furthest_operational_step($request, $payload, $formValues);
    $operational = m360_rw_intake_wizard_operational_keys();
    $furIdx = array_search($furthest, $operational, true);
    if ($furIdx === false) {
        $furIdx = count($operational) - 1;
    }

    echo '<nav class="m360-rw-wizard-progress" aria-label="پیشرفت مراحل پذیرش">';
    echo '<ol class="m360-rw-wizard-progress-track">';
    foreach (m360_rw_intake_stepper_definition() as $stepKey => $meta) {
        if ($stepKey === 'locked_summary' && !m360_rw_intake_is_locked($payload)) {
            continue;
        }
        $complete = m360_rw_intake_wizard_step_is_complete($stepKey, $payload, $request, $formValues);
        $isActive = $stepKey === $activeStep;
        $opIdx = array_search($stepKey, $operational, true);
        $isFuture = $opIdx !== false && $opIdx > $furIdx;
        $cls = 'm360-rw-wizard-progress-item';
        if ($isActive) {
            $cls .= ' is-active';
        } elseif ($complete) {
            $cls .= ' is-done';
        } elseif ($isFuture) {
            $cls .= ' is-future';
        } else {
            $cls .= ' is-pending';
        }
        echo '<li class="' . m360_rw_h($cls) . '">';
        echo '<span class="m360-rw-wizard-progress-num">' . m360_rw_h((string)$meta['num']) . '</span>';
        echo '<span class="m360-rw-wizard-progress-title">' . m360_rw_h((string)$meta['label']) . '</span>';
        if ($complete) {
            $status = 'تکمیل';
        } elseif ($isFuture) {
            $status = 'بعدی';
        } elseif ($isActive) {
            $status = 'فعال';
        } else {
            $status = 'نیازمند';
        }
        echo '<span class="m360-rw-wizard-progress-status">' . m360_rw_h($status) . '</span>';
        echo '</li>';
    }
    echo '</ol></nav>';
}

/**
 * @param array<string, string> $formValues
 */
function m360_rw_intake_step_panel_open(string $stepKey, string $activeStep, array $payload, array $request, array $formValues, string $title): void
{
    $def = m360_rw_intake_stepper_definition();
    $hash = (string)($def[$stepKey]['hash'] ?? '');
    $state = m360_rw_intake_step_nav_state($stepKey, $activeStep, $payload, $request, $formValues);
    $cls = 'm360-rw-step-panel';
    if ($state['active']) {
        $cls .= ' is-active';
    }
    if ($state['complete']) {
        $cls .= ' is-complete';
    }
    echo '<section class="' . m360_rw_h($cls) . '" id="' . m360_rw_h($hash) . '" data-step="' . m360_rw_h($stepKey) . '">';
    echo '<header class="m360-rw-step-head"><h2 class="m360-rw-step-title">' . m360_rw_h($title) . '</h2>';
    echo '<span class="m360-rw-step-badge">' . m360_rw_h((string)$state['status_label']) . '</span></header>';
    if ($state['active']) {
        echo '<div class="m360-rw-step-body">';
    } else {
        echo '<div class="m360-rw-step-compact-card">';
    }
}

function m360_rw_intake_step_panel_close(string $stepKey, string $activeStep, int $onlineRequestId): void
{
    $section = m360_rw_intake_step_primary_section($stepKey);
    echo '</div>';
    if ($section !== '' && $stepKey !== $activeStep) {
        echo '<p class="m360-rw-step-edit"><a class="m360-rw-btn m360-rw-btn-secondary" href="'
            . m360_rw_h(m360_rw_intake_edit_url($onlineRequestId, $section)) . '">ویرایش این مرحله</a></p>';
    }
    echo '</section>';
}

function m360_rw_intake_render_stepper_footer_nav(int $onlineRequestId, string $activeStep): void
{
    $steps = m360_rw_intake_stepper_keys();
    $idx = array_search($activeStep, $steps, true);
    if ($idx === false) {
        $idx = 0;
    }
    echo '<nav class="m360-rw-stepper-foot" aria-label="پیمایش مراحل">';
    if ($idx > 0) {
        $prev = $steps[$idx - 1];
        $href = 'erp-reception-intake-file.php?online_request_id=' . $onlineRequestId
            . '&active_step=' . rawurlencode($prev)
            . '#' . rawurlencode(m360_rw_intake_step_hash($prev));
        echo '<a class="m360-rw-btn m360-rw-btn-secondary m360-rw-step-prev" href="' . m360_rw_h($href) . '">مرحله قبل</a>';
    } else {
        echo '<span class="m360-rw-step-spacer"></span>';
    }
    if ($idx < count($steps) - 1) {
        $next = $steps[$idx + 1];
        $href = 'erp-reception-intake-file.php?online_request_id=' . $onlineRequestId
            . '&active_step=' . rawurlencode($next)
            . '#' . rawurlencode(m360_rw_intake_step_hash($next));
        echo '<a class="m360-rw-btn m360-rw-step-next" href="' . m360_rw_h($href) . '">مرحله بعد</a>';
    }
    echo '</nav>';
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, string> $request
 * @param array<string, string> $formValues
 */
function m360_rw_intake_render_step_compact_summary(
    string $stepKey,
    int $onlineRequestId,
    array $payload,
    array $request,
    array $formValues,
    array $otpStatusUi = []
): void {
    $state = m360_rw_intake_step_nav_state($stepKey, '---', $payload, $request, $formValues);
    $goUrl = m360_rw_intake_step_go_url($onlineRequestId, $stepKey);
    echo '<div class="m360-rw-step-compact-inner">';
    echo '<p class="m360-rw-step-compact-status">' . m360_rw_h((string)$state['status_label']) . '</p>';
    echo '<div class="m360-rw-step-compact-lines">';
    switch ($stepKey) {
        case 'otp':
            echo '<span>موبایل: ' . m360_rw_h(m360_rw_intake_resolve_mobile_for_otp($request, $payload) ?: '—') . '</span>';
            echo '<span>OTP: ' . m360_rw_h((string)($otpStatusUi['label_fa'] ?? '—')) . '</span>';
            break;
        case 'vehicle':
            echo '<span>پلاک: ' . m360_rw_h($formValues['plate'] ?? '—') . '</span>';
            echo '<span>خودرو: ' . m360_rw_h(trim(($formValues['brand'] ?? '') . ' ' . ($formValues['model'] ?? ''), ' ') ?: '—') . '</span>';
            break;
        case 'condition':
            $items = trim((string)($formValues['vehicle_items'] ?? ''));
            echo '<span>لوازم: ' . m360_rw_h($items !== '' ? mb_substr($items, 0, 40) : '—') . '</span>';
            break;
        case 'service':
            echo '<span>دسته: ' . m360_rw_h($formValues['service_primary'] ?? '—') . '</span>';
            break;
        case 'referral':
            echo '<span>مسئول سالن: ' . m360_rw_h($formValues['hall_manager_status'] ?? '—') . '</span>';
            break;
        case 'photos':
            $photoStatus = m360_rw_intake_reception_photo_status($payload);
            echo '<span>عکس: ' . m360_rw_h((string)$photoStatus['count']) . '/' . m360_rw_h((string)$photoStatus['min_required']) . '</span>';
            break;
        case 'documents':
            echo '<span>توافق: ' . m360_rw_h($formValues['cost_agreement'] ?? '—') . '</span>';
            break;
        case 'final':
            echo '<span>تأیید: ' . m360_rw_h(($formValues['confirmed_by_receptionist'] ?? '') === '1' ? 'بله' : 'خیر') . '</span>';
            break;
    }
    echo '</div>';
    echo '<a class="m360-rw-btn m360-rw-btn-secondary m360-rw-step-go" href="' . m360_rw_h($goUrl) . '">رفتن به این مرحله</a>';
    echo '</div>';
}

/**
 * @param list<array{label_fa:string,value:string}> $payloadRows
 * @param array{valid:bool,raw_warning:string} $payloadMeta
 */
function m360_rw_intake_render_payload_collapsed(array $payloadRows, array $payloadMeta): void
{
    if ($payloadRows === []) {
        return;
    }
    echo '<details class="m360-rw-tech-details">';
    echo '<summary>جزئیات فنی / فقط برای بررسی</summary>';
    echo '<div class="m360-rw-tech-details-body">';
    if (!$payloadMeta['valid']) {
        echo '<p class="m360-rw-warn">' . m360_rw_h($payloadMeta['raw_warning']) . '</p>';
    }
    echo '<div class="m360-rw-field-grid">';
    foreach ($payloadRows as $prow) {
        echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">' . m360_rw_h($prow['label_fa']) . '</span>';
        echo '<span class="m360-rw-field-val">' . m360_rw_h($prow['value'] !== '' ? $prow['value'] : '—') . '</span></div>';
    }
    echo '</div></div></details>';
}

function m360_rw_intake_return_step_hidden(string $stepKey): void
{
    if ($stepKey === '') {
        return;
    }
    echo '<input type="hidden" name="active_step" value="' . m360_rw_h($stepKey) . '">';
    echo '<input type="hidden" name="return_step" value="' . m360_rw_h($stepKey) . '">';
    echo '<input type="hidden" name="return_active_step" value="' . m360_rw_h($stepKey) . '">';
    $section = m360_rw_intake_step_primary_section($stepKey);
    if ($section !== '') {
        m360_rw_intake_return_section_hidden($section);
    }
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
        'save_mobile_correction', 'send_customer_otp', 'verify_customer_otp' => 'section-mobile-otp',
        'save_vehicle_identity' => 'section-vehicle-identity',
        'save_condition_notes' => 'section-condition-notes',
        'save_service_classification' => 'section-service-classification',
        'save_temporary_reception' => 'section-temporary-reception',
        'save_referral_team' => 'section-referral-team',
        'save_camera_photo' => 'section-camera-photo',
        'save_diagnostic_pdf' => 'section-diagnostic-pdf',
        'prepare_customer_contract_review' => 'section-contract',
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
    $step = m360_rw_intake_section_to_step($sectionKey);
    if ($step !== '') {
        echo '<input type="hidden" name="return_active_step" value="' . m360_rw_h($step) . '">';
    }
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, string> $request
 * @param array<string, string> $formValues
 * @return array{show_summary:bool,show_form:bool,status_label:string,complete:bool}
 */
function m360_rw_intake_stepper_section_ui_state(
    string $sectionId,
    string $activeStep,
    array $payload,
    array $request,
    array $formValues,
    string $editSection
): array {
    $ui = m360_rw_intake_section_ui_state($sectionId, $payload, $request, $formValues, $editSection);
    $step = m360_rw_intake_section_to_step($sectionId);
    if ($step === $activeStep) {
        $ui['show_form'] = true;
        $ui['show_summary'] = $ui['complete'] && $editSection !== $sectionId && $editSection !== '';
        $ui['status_label'] = 'در حال تکمیل';
    } else {
        $ui['show_form'] = false;
        $ui['show_summary'] = true;
    }

    return $ui;
}

const M360_RW_INTAKE_PHOTO_MIN_REQUIRED = 6;

/**
 * Six reception photo slots — canonical keys for P11.9-C-2C-FIX-E5.
 *
 * @return array<string, string>
 */
function m360_rw_intake_reception_photo_slots(): array
{
    return [
        'front' => 'جلو',
        'rear' => 'عقب',
        'right' => 'راست',
        'left' => 'چپ',
        'cabin' => 'کابین',
        'dashboard' => 'داشبورد',
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{required_count:int,completed_count:int,is_complete:bool,slots:array<string,array{label:string,status:string,captured_at:string,captured_by:string,data_key:string}>,missing_labels:list<string>}
 */
function m360_rw_intake_photos_canonical(array $payload): array
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $slotDefs = m360_rw_intake_reception_photo_slots();
    $required = M360_RW_INTAKE_PHOTO_MIN_REQUIRED;
    $storedCanonical = is_array($payload['reception_intake']['photos'] ?? null)
        ? $payload['reception_intake']['photos']
        : [];
    $slots = is_array($storedCanonical['slots'] ?? null) ? $storedCanonical['slots'] : [];
    $legacyStored = is_array($payload['reception_intake']['documents']['reception_photos'] ?? null)
        ? $payload['reception_intake']['documents']['reception_photos']
        : [];

    foreach ($slotDefs as $key => $label) {
        $entry = is_array($slots[$key] ?? null) ? $slots[$key] : [];
        $legacyEntry = is_array($legacyStored[$key] ?? null) ? $legacyStored[$key] : [];
        if ($key === 'cabin' && $legacyEntry === [] && is_array($legacyStored['interior'] ?? null)) {
            $legacyEntry = $legacyStored['interior'];
        }

        $dataKey = trim((string)($entry['data_key'] ?? $entry['file'] ?? ''));
        if ($dataKey === '' && $legacyEntry !== []) {
            $dataKey = trim((string)($legacyEntry['file'] ?? ''));
        }

        $status = 'missing';
        if (($entry['status'] ?? '') === 'captured' || $dataKey !== '') {
            $status = 'captured';
        }

        $slots[$key] = [
            'label' => $label,
            'status' => $status,
            'captured_at' => (string)($entry['captured_at'] ?? $legacyEntry['saved_at'] ?? ''),
            'captured_by' => (string)($entry['captured_by'] ?? $legacyEntry['saved_by'] ?? ''),
            'data_key' => $dataKey,
        ];
    }

    return m360_rw_intake_photos_recalculate([
        'required_count' => $required,
        'completed_count' => 0,
        'is_complete' => false,
        'slots' => $slots,
        'missing_labels' => [],
    ]);
}

/**
 * @param array{required_count?:int,completed_count?:int,is_complete?:bool,slots?:array<string,array<string,string>>,missing_labels?:list<string>} $canonical
 * @return array{required_count:int,completed_count:int,is_complete:bool,slots:array<string,array{label:string,status:string,captured_at:string,captured_by:string,data_key:string}>,missing_labels:list<string>}
 */
function m360_rw_intake_photos_recalculate(array $canonical): array
{
    $slotDefs = m360_rw_intake_reception_photo_slots();
    $required = M360_RW_INTAKE_PHOTO_MIN_REQUIRED;
    $slots = is_array($canonical['slots'] ?? null) ? $canonical['slots'] : [];
    $completed = 0;
    $missing = [];

    foreach ($slotDefs as $key => $label) {
        $entry = is_array($slots[$key] ?? null) ? $slots[$key] : [
            'label' => $label,
            'status' => 'missing',
            'captured_at' => '',
            'captured_by' => '',
            'data_key' => '',
        ];
        $dataKey = trim((string)($entry['data_key'] ?? ''));
        if (($entry['status'] ?? '') === 'captured' && $dataKey !== '') {
            $completed++;
        } else {
            $missing[] = $label;
            $entry['status'] = 'missing';
        }
        $slots[$key] = [
            'label' => (string)($entry['label'] ?? $label),
            'status' => (string)$entry['status'],
            'captured_at' => (string)($entry['captured_at'] ?? ''),
            'captured_by' => (string)($entry['captured_by'] ?? ''),
            'data_key' => $dataKey,
        ];
    }

    $isComplete = $completed >= $required && $missing === [];

    return [
        'required_count' => $required,
        'completed_count' => $completed,
        'is_complete' => $isComplete,
        'slots' => $slots,
        'missing_labels' => $missing,
    ];
}

/**
 * @param array<string, mixed> $payload
 */
function m360_rw_intake_photos_complete(array $payload): bool
{
    $canonical = m360_rw_intake_photos_canonical($payload);

    return !empty($canonical['is_complete'])
        && ($canonical['completed_count'] ?? 0) >= M360_RW_INTAKE_PHOTO_MIN_REQUIRED
        && ($canonical['missing_labels'] ?? []) === [];
}

/**
 * @param array<string, mixed> $payload
 * @param array{required_count:int,completed_count:int,is_complete:bool,slots:array<string,array<string,string>>,missing_labels:list<string>} $canonical
 * @return array<string, mixed>
 */
function m360_rw_intake_photos_sync_to_payload(array $payload, array $canonical): array
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $payload['reception_intake']['photos'] = [
        'required_count' => $canonical['required_count'],
        'completed_count' => $canonical['completed_count'],
        'is_complete' => $canonical['is_complete'],
        'slots' => $canonical['slots'],
    ];

    if (!isset($payload['reception_intake']['documents']) || !is_array($payload['reception_intake']['documents'])) {
        $payload['reception_intake']['documents'] = [];
    }
    $docs = &$payload['reception_intake']['documents'];
    $docs['photo_count'] = $canonical['completed_count'];
    $docs['photo_min_required'] = M360_RW_INTAKE_PHOTO_MIN_REQUIRED;
    $docs['photo_status'] = $canonical['is_complete']
        ? 'ثبت شد'
        : ($canonical['completed_count'] . '/' . $canonical['required_count']);
    $payload['photo_status'] = $docs['photo_status'];

    $legacyPhotos = [];
    foreach ($canonical['slots'] as $key => $slot) {
        if (($slot['status'] ?? '') === 'captured' && trim((string)($slot['data_key'] ?? '')) !== '') {
            $legacyPhotos[$key] = [
                'label' => $slot['label'],
                'file' => $slot['data_key'],
                'saved_at' => $slot['captured_at'],
                'saved_by' => $slot['captured_by'],
            ];
        }
    }
    $docs['reception_photos'] = $legacyPhotos;

    return $payload;
}

/**
 * @param array<string, mixed> $payload
 * @return array{count:int,min_required:int,complete:bool,missing_labels:list<string>,slots:array<string,array{label:string,file:string,saved:bool,saved_at:string}>,legacy_file:string,legacy_only:bool,canonical:array<string,mixed>}
 */
function m360_rw_intake_reception_photo_status(array $payload): array
{
    $canonical = m360_rw_intake_photos_canonical($payload);
    $slotDefs = m360_rw_intake_reception_photo_slots();
    $docs = is_array($payload['reception_intake']['documents'] ?? null) ? $payload['reception_intake']['documents'] : [];
    $legacyFile = trim((string)($docs['photo_file'] ?? ''));
    $slots = [];

    foreach ($slotDefs as $key => $label) {
        $cs = is_array($canonical['slots'][$key] ?? null) ? $canonical['slots'][$key] : [];
        $file = trim((string)($cs['data_key'] ?? ''));
        $saved = ($cs['status'] ?? '') === 'captured' && $file !== '';
        $slots[$key] = [
            'label' => $label,
            'file' => $file,
            'saved' => $saved,
            'saved_at' => (string)($cs['captured_at'] ?? ''),
        ];
    }

    return [
        'count' => $canonical['completed_count'],
        'min_required' => $canonical['required_count'],
        'complete' => m360_rw_intake_photos_complete($payload),
        'missing_labels' => $canonical['missing_labels'],
        'slots' => $slots,
        'legacy_file' => $legacyFile,
        'legacy_only' => $legacyFile !== '' && $canonical['completed_count'] === 0,
        'canonical' => $canonical,
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

/** @return array{active:bool,available:bool,reason_fa:string,config_missing:bool,dev_mode?:bool} */
function m360_rw_intake_otp_config_status(): array
{
    if (!m360_rw_intake_load_otp_helper()) {
        return [
            'active' => false,
            'available' => false,
            'reason_fa' => 'تنظیمات پیامک فعال نیست؛ فایل خصوصی OTP تنظیم نشده است.',
            'config_missing' => true,
        ];
    }
    if (function_exists('m360_otp_sms_configured') && m360_otp_sms_configured()) {
        return ['active' => true, 'available' => true, 'reason_fa' => '', 'config_missing' => false];
    }
    if (function_exists('m360_otp_can_use_dev_code') && m360_otp_can_use_dev_code()) {
        return [
            'active' => true,
            'available' => true,
            'reason_fa' => '',
            'config_missing' => false,
            'dev_mode' => true,
        ];
    }

    return [
        'active' => false,
        'available' => false,
        'reason_fa' => 'تنظیمات پیامک فعال نیست؛ فایل خصوصی OTP تنظیم نشده است.',
        'config_missing' => true,
    ];
}

/** @return array{available:bool,reason_fa:string} */
function m360_rw_intake_otp_send_available(): array
{
    $status = m360_rw_intake_otp_config_status();

    return ['available' => $status['available'], 'reason_fa' => $status['reason_fa']];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, string>|null $request
 * @return array{label_fa:string,show_verify_form:bool,verified:bool,status_code:string}
 */
function m360_rw_intake_otp_ui_status(array $payload, ?array $request, bool $otpSentFlash = false, bool $flashOk = false, string $flashMsg = ''): array
{
    $verified = $request !== null && m360_online_req_payload_otp_verified($request);
    if ($verified) {
        return [
            'label_fa' => 'تأیید شده',
            'show_verify_form' => false,
            'verified' => true,
            'status_code' => 'verified',
        ];
    }

    if ($flashOk && ($otpSentFlash || m360_rw_intake_flash_indicates_otp_sent($flashMsg))) {
        return [
            'label_fa' => 'ارسال شده، در انتظار تأیید',
            'show_verify_form' => true,
            'verified' => false,
            'status_code' => 'sent',
        ];
    }

    $otpMeta = $payload['reception_intake']['otp'] ?? [];
    if (!is_array($otpMeta)) {
        $otpMeta = [];
    }
    $code = trim((string)($otpMeta['status'] ?? 'unverified'));
    $labels = [
        'unverified' => 'تأیید نشده',
        'sent' => 'ارسال شده، در انتظار تأیید',
        'expired' => 'منقضی شده',
        'verified' => 'تأیید شده',
        'failed' => 'ارسال ناموفق',
        'config_missing' => 'تنظیمات پیامک فعال نیست',
    ];
    $label = $labels[$code] ?? 'تأیید نشده';
    $mobileReady = $request !== null && m360_rw_intake_resolve_mobile_for_otp($request, $payload) !== '';
    $showVerify = $mobileReady && in_array($code, ['sent', 'expired', 'failed', 'unverified'], true);

    return [
        'label_fa' => $label,
        'show_verify_form' => $showVerify,
        'verified' => false,
        'status_code' => $code,
    ];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $request
 * @param array<string, string> $formValues
 * @param array{label_fa:string,show_verify_form:bool,verified:bool,status_code:string} $otpStatusUi
 * @param array{available:bool,reason_fa:string} $otpSend
 */
function m360_rw_intake_render_otp_wizard_block(
    int $onlineRequestId,
    array $payload,
    array $request,
    array $formValues,
    array $otpStatusUi,
    array $otpSend,
    bool $canShowStepForm,
    bool $otpVerified,
    string $otpMobile,
    bool $otpSentFlash,
    bool $flashOk,
    string $csrfInputHtml,
    string $saveUrl
): void {
    echo '<div class="m360-rw-otp-step-block" id="step-otp">';
    echo '<div class="m360-rw-field-grid m360-rw-step-compact">';
    echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">موبایل فعلی</span>';
    echo '<span class="m360-rw-field-val">' . m360_rw_h($otpMobile !== '' ? $otpMobile : '—') . '</span></div>';
    echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">وضعیت OTP</span>';
    echo '<span class="m360-rw-field-val">' . m360_rw_h($otpStatusUi['label_fa']) . '</span></div>';
    echo '</div>';

    if ($otpVerified) {
        echo '<p class="m360-rw-flash is-ok">OTP تأیید شده — با تکمیل این مرحله به مرحله خودرو می‌روید.</p>';
        echo '</div>';

        return;
    }

    echo '<p class="m360-rw-warn">' . m360_rw_h(M360_RW_RECEPTION_OTP_CUSTOMER_ONLY_MESSAGE_FA) . '</p>';
    echo '<p class="m360-rw-muted">مشتری باید OTP را از صفحه «ثبت درخواست آنلاین» تکمیل کند. پس از تأیید، درخواست در فهرست پذیرش نمایش داده می‌شود.</p>';

    if (!$canShowStepForm) {
        echo '</div>';

        return;
    }

    echo '<form class="m360-rw-form" method="post" action="' . m360_rw_h($saveUrl) . '">';
    echo $csrfInputHtml;
    echo '<input type="hidden" name="online_request_id" value="' . $onlineRequestId . '">';
    echo '<input type="hidden" name="action_type" value="save_mobile_correction">';
    m360_rw_intake_return_step_hidden('otp');
    echo '<div class="m360-rw-form-field"><label class="m360-rw-form-label" for="mobile_corrected">موبایل اصلاح‌شده <span class="m360-rw-req">*</span></label>';
    echo '<input class="m360-rw-form-input" type="tel" id="mobile_corrected" name="mobile_corrected" value="' . m360_rw_h($formValues['mobile_corrected'] ?? $otpMobile) . '" required></div>';
    echo '<button type="submit" class="m360-rw-btn m360-rw-btn-secondary">ذخیره موبایل</button>';
    echo '</form>';

    echo '</div>';
}

/**
 * @param array<string, mixed> $result
 */
function m360_rw_intake_classify_otp_send_failure(string $failMsg, array $result): string
{
    if (str_contains($failMsg, 'توکن iPPanel') || str_contains($failMsg, '401')) {
        return 'invalid_token';
    }
    if (str_contains($failMsg, 'فعال نیست') || str_contains($failMsg, 'تنظیمات پیامک')) {
        return 'config_missing';
    }
    if (str_contains($failMsg, 'ثانیه دیگر')) {
        return 'rate_limited';
    }
    if ($failMsg === '' && empty($result['ok'])) {
        return 'provider_fail';
    }

    return 'provider_fail';
}

/** @return array{ok:bool,message:string,history_written:bool,category?:string} */
function m360_rw_intake_process_send_otp($conn, int $requestId, array $request): array
{
    if (!m360_rw_intake_load_otp_helper()) {
        return ['ok' => false, 'message' => 'تنظیمات پیامک فعال نیست.', 'history_written' => false, 'category' => 'config_missing'];
    }

    $avail = m360_rw_intake_otp_send_available();
    $existing = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
    $existing = m360_rw_intake_ensure_nested($existing);
    $mobile = m360_rw_intake_resolve_mobile_for_otp($request, $existing);
    if ($mobile === '' || !preg_match('/^09\d{9}$/', $mobile)) {
        return ['ok' => false, 'message' => 'موبایل برای ارسال OTP ثبت نشده است.', 'history_written' => false, 'category' => 'invalid_mobile'];
    }

    $now = gmdate('Y-m-d\TH:i:s\Z');

    if (!$avail['available']) {
        $existing['reception_intake']['otp'] = [
            'status' => 'config_missing',
            'mobile' => $mobile,
            'updated_at' => $now,
        ];
        m360_rw_intake_persist_payload($conn, $requestId, $existing, []);

        return ['ok' => false, 'message' => 'تنظیمات پیامک فعال نیست.', 'history_written' => false, 'category' => 'config_missing'];
    }

    $result = m360_otp_send($mobile);
    $provider = (function_exists('m360_otp_sms_configured') && m360_otp_sms_configured()) ? 'ippanel' : 'dev';

    if (empty($result['ok'])) {
        $existing['reception_intake']['otp'] = [
            'status' => 'failed',
            'mobile' => $mobile,
            'provider' => $provider,
            'updated_at' => $now,
        ];
        m360_rw_intake_persist_payload($conn, $requestId, $existing, []);
        $failMsg = trim((string)($result['message'] ?? ''));
        $category = m360_rw_intake_classify_otp_send_failure($failMsg, $result);

        return ['ok' => false, 'message' => $failMsg !== '' ? $failMsg : 'ارسال OTP ناموفق بود.', 'history_written' => false, 'category' => $category];
    }

    $existing['reception_intake']['otp'] = [
        'status' => 'sent',
        'mobile' => $mobile,
        'sent_at' => $now,
        'provider' => $provider,
        'updated_at' => $now,
    ];
    $persist = m360_rw_intake_persist_payload($conn, $requestId, $existing, []);
    if (!$persist['ok']) {
        return ['ok' => false, 'message' => 'پیامک ارسال شد اما ذخیره وضعیت انجام نشد.', 'history_written' => false, 'category' => 'persist_fail'];
    }

    return ['ok' => true, 'message' => 'پیامک OTP ارسال شد.', 'history_written' => false, 'category' => 'sent'];
}

/**
 * @param array<string, string> $post
 * @return array{ok:bool,message:string,history_written:bool}
 */
function m360_rw_intake_process_verify_otp($conn, int $requestId, array $request, array $post): array
{
    if (!m360_rw_intake_load_otp_helper()) {
        return ['ok' => false, 'message' => 'تنظیمات پیامک فعال نیست.', 'history_written' => false];
    }

    $codeNorm = m360_rw_intake_post_scalar($post, 'otp_code');
    if (!$codeNorm['ok']) {
        return ['ok' => false, 'message' => $codeNorm['error'], 'history_written' => false];
    }
    $code = $codeNorm['value'];
    if ($code === '') {
        return ['ok' => false, 'message' => 'کد تأیید الزامی است.', 'history_written' => false];
    }

    $existing = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
    $existing = m360_rw_intake_ensure_nested($existing);
    $mobile = m360_rw_intake_resolve_mobile_for_otp($request, $existing);
    if ($mobile === '' || !preg_match('/^09\d{9}$/', $mobile)) {
        return ['ok' => false, 'message' => 'موبایل برای تأیید OTP ثبت نشده است.', 'history_written' => false];
    }

    $now = gmdate('Y-m-d\TH:i:s\Z');
    $provider = m360_otp_sms_configured() ? 'ippanel' : 'dev';

    $result = m360_otp_verify($mobile, $code);
    if (empty($result['ok'])) {
        $msg = (string)($result['message'] ?? 'کد تأیید نادرست است.');
        $otpMeta = $existing['reception_intake']['otp'] ?? [];
        if (!is_array($otpMeta)) {
            $otpMeta = [];
        }
        if (str_contains($msg, 'منقضی')) {
            $otpMeta['status'] = 'expired';
        } elseif (($otpMeta['status'] ?? '') === 'sent') {
            $otpMeta['failed_attempt'] = true;
        }
        $otpMeta['updated_at'] = $now;
        $existing['reception_intake']['otp'] = $otpMeta;
        m360_rw_intake_persist_payload($conn, $requestId, $existing, []);

        return ['ok' => false, 'message' => $msg, 'history_written' => false];
    }

    $existing['otp_verified'] = 1;
    $existing['otp_verified_at'] = $now;
    $existing['otp_verified_mobile'] = $mobile;
    $existing['reception_intake']['otp'] = [
        'status' => 'verified',
        'mobile' => $mobile,
        'verified_at' => $now,
        'provider' => $provider,
        'updated_at' => $now,
    ];
    m360_rw_intake_mark_section_saved($existing, 'mobile_otp');

    $columnUpdates = [];
    if (m360_online_req_has_column($conn, 'otp_verified')) {
        $columnUpdates['otp_verified'] = 1;
    }

    $persist = m360_rw_intake_persist_payload($conn, $requestId, $existing, $columnUpdates);
    if (!$persist['ok']) {
        return ['ok' => false, 'message' => $persist['message'], 'history_written' => false];
    }

    $userId = erp_auth_current_user_id() ?? ERP_PHASE1_PLATFORM_OWNER_ID;
    $historyWritten = m360_online_req_write_history(
        $conn,
        $requestId,
        M360_RW_INTAKE_HISTORY_PREFIX . 'VERIFY_CUSTOMER_OTP',
        (string)($request['request_status'] ?? ''),
        (string)($request['request_status'] ?? ''),
        'Customer OTP verified via reception intake',
        $userId
    );

    return ['ok' => true, 'message' => 'شماره موبایل مشتری تأیید شد.', 'history_written' => $historyWritten];
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
    $sectionToStep = [
        'mobile_otp' => 'otp',
        'vehicle_identity' => 'vehicle',
        'condition_notes' => 'condition',
        'service_classification' => 'service',
        'temporary_reception' => 'service',
        'referral' => 'referral',
        'camera_photo' => 'photos',
        'diagnostic_pdf' => 'documents',
        'contract' => 'documents',
        'documents_cost' => 'documents',
        'reception_confirmation' => 'signature',
    ];
    if (isset($sectionToStep[$sectionId])) {
        $state = m360_rw_intake_get_wizard_step_state($payload, $request);
        $stepKey = $sectionToStep[$sectionId];
        if (!empty($state['steps'][$stepKey]['complete'])) {
            return true;
        }
        if (in_array($sectionId, ['diagnostic_pdf', 'contract', 'documents_cost'], true)) {
            $docs = $state['steps']['documents'] ?? ['complete' => false, 'missing_fields' => []];
            if ($sectionId === 'diagnostic_pdf') {
                return !in_array('diagnostic_status', $docs['missing_fields'] ?? [], true);
            }
            if ($sectionId === 'contract') {
                return m360_rw_intake_contract_customer_accepted($payload);
            }
            if ($sectionId === 'documents_cost') {
                return !in_array('cost_agreement', $docs['missing_fields'] ?? [], true);
            }
        }
        if ($sectionId === 'camera_photo') {
            return m360_rw_intake_photos_complete($payload);
        }
        if ($sectionId === 'reception_confirmation') {
            return m360_rw_intake_is_locked($payload);
        }
    }

    return match ($sectionId) {
        'mobile_otp' => trim((string)($request['mobile'] ?? '')) !== '' && m360_online_req_payload_otp_verified($request),
        'vehicle_identity' => m360_rw_intake_vehicle_step_complete($payload, $request),
        'condition_notes' => m360_rw_intake_condition_step_complete($payload),
        'service_classification' => m360_rw_intake_service_wizard_step_complete(
            $formValues,
            m360_rw_request_service_policy_group(trim((string)m360_rw_pick([$request, $payload], 'request_type')))
        ),
        'temporary_reception' => trim((string)($formValues['temporary_status'] ?? '')) !== '',
        'referral' => m360_rw_intake_referral_step_complete($payload),
        'documents_cost' => trim((string)($formValues['cost_agreement'] ?? '')) !== '',
        'camera_photo' => m360_rw_intake_photos_complete($payload),
        'diagnostic_pdf' => m360_rw_intake_documents_step_state($payload, $formValues)['complete']
            || trim((string)($formValues['diagnostic_status'] ?? '')) !== ''
            || !empty($payload['reception_intake']['documents']['diagnostic_pdf']),
        'contract' => m360_rw_intake_contract_customer_accepted($payload),
        'reception_confirmation' => m360_rw_intake_is_locked($payload),
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
 * @return array{ok:bool,error:string,plate:string,display:string,parts:array<string,string>}
 */
function m360_rw_intake_build_plate_from_post(array $post): array
{
    $left = trim((string)($post['plate_left_2_digits'] ?? ''));
    $letter = trim((string)($post['plate_letter'] ?? ''));
    $mid = trim((string)($post['plate_middle_3_digits'] ?? ''));
    $iran = trim((string)($post['plate_region_2_digits'] ?? $post['plate_iran_2_digits'] ?? ''));

    if ($left === '' && isset($post['plate_first_digit_1'], $post['plate_first_digit_2'])) {
        $left = trim((string)$post['plate_first_digit_1']) . trim((string)$post['plate_first_digit_2']);
    }
    if ($mid === '' && isset($post['plate_middle_digit_1'], $post['plate_middle_digit_2'], $post['plate_middle_digit_3'])) {
        $mid = trim((string)$post['plate_middle_digit_1']) . trim((string)$post['plate_middle_digit_2']) . trim((string)$post['plate_middle_digit_3']);
    }
    if ($iran === '' && isset($post['plate_region_digit_1'], $post['plate_region_digit_2'])) {
        $iran = trim((string)$post['plate_region_digit_1']) . trim((string)$post['plate_region_digit_2']);
    }

    if ($left === '' || $letter === '' || $mid === '' || $iran === '') {
        return ['ok' => false, 'error' => 'پلاک باید با انتخاب رقم و حرف تکمیل شود.', 'plate' => '', 'display' => '', 'parts' => []];
    }

    if (!preg_match('/^\d{2}$/', $left)) {
        return ['ok' => false, 'error' => 'دو رقم اول پلاک نامعتبر است.', 'plate' => '', 'display' => '', 'parts' => []];
    }
    if (!preg_match('/^\d{3}$/', $mid)) {
        return ['ok' => false, 'error' => 'سه رقم وسط پلاک نامعتبر است.', 'plate' => '', 'display' => '', 'parts' => []];
    }
    if (!preg_match('/^\d{2}$/', $iran)) {
        return ['ok' => false, 'error' => 'کد ایران پلاک نامعتبر است.', 'plate' => '', 'display' => '', 'parts' => []];
    }
    if ($letter === '' || !in_array($letter, m360_rw_intake_plate_letters(), true)) {
        return ['ok' => false, 'error' => 'حرف پلاک نامعتبر است.', 'plate' => '', 'display' => '', 'parts' => []];
    }

    $plate = $left . $letter . $mid . '-' . $iran;
    $display = $left . ' ' . $letter . ' ' . $mid . ' ایران ' . $iran;
    $parts = ['left_2' => $left, 'letter' => $letter, 'middle_3' => $mid, 'region_2' => $iran];

    return ['ok' => true, 'error' => '', 'plate' => $plate, 'display' => $display, 'parts' => $parts];
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

function m360_rw_intake_edit_url(int $onlineRequestId, string $sectionId): string
{
    $step = m360_rw_intake_section_to_step($sectionId);
    $anchor = $step !== '' ? m360_rw_intake_step_hash($step) : m360_rw_intake_section_key_to_anchor($sectionId);

    return 'erp-reception-intake-file.php?online_request_id=' . $onlineRequestId
        . '&active_step=' . rawurlencode($step !== '' ? $step : 'otp')
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
    $iran = $formValues['plate_region_2_digits'] ?? ($formValues['plate_iran_2_digits'] ?? '');
    $d1 = $left !== '' ? substr($left, 0, 1) : '';
    $d2 = $left !== '' && strlen($left) > 1 ? substr($left, 1, 1) : '';
    $m1 = $mid !== '' ? substr($mid, 0, 1) : '';
    $m2 = $mid !== '' && strlen($mid) > 1 ? substr($mid, 1, 1) : '';
    $m3 = $mid !== '' && strlen($mid) > 2 ? substr($mid, 2, 1) : '';
    $r1 = $iran !== '' ? substr($iran, 0, 1) : '';
    $r2 = $iran !== '' && strlen($iran) > 1 ? substr($iran, 1, 1) : '';
    $display = trim((string)($formValues['plate_display'] ?? ''));
    echo '<label class="iran-plate-field-label">پلاک خودرو <span class="m360-req">*</span></label>';
    echo '<p class="iran-plate-field-hint">از چپ به راست: دو رقم، حرف، سه رقم، سپس کد ایران</p>';
    echo '<div class="iran-plate-widget m360-rw-plate-widget" aria-label="پلاک خودرو">';
    echo '<div class="iran-plate-ir-band" aria-hidden="true"><span class="iran-plate-ir-band__ir">IR</span><span class="iran-plate-ir-band__flag" aria-hidden="true">🇮🇷</span></div>';
    echo '<div class="iran-plate-body">';
    echo '<div class="iran-plate-group iran-plate-group--series" aria-label="دو رقم اول"><span class="iran-plate-group__label">۲ رقم</span><div class="iran-plate-group__inputs">';
    for ($i = 1; $i <= 2; $i++) {
        $sel = ($i === 1 ? $d1 : $d2);
        echo '<select class="plate-digit-select" id="plate_first_digit_' . $i . '" name="plate_first_digit_' . $i . '" data-required-both="1" aria-label="رقم پلاک"><option value="">-</option>';
        for ($d = 0; $d <= 9; $d++) {
            echo '<option value="' . $d . '"' . ((string)$d === (string)$sel ? ' selected' : '') . '>' . $d . '</option>';
        }
        echo '</select>';
    }
    echo '</div></div><span class="iran-plate-sep" aria-hidden="true"></span>';
    echo '<div class="iran-plate-group iran-plate-group--letter" aria-label="حرف پلاک"><span class="iran-plate-group__label">حرف</span>';
    echo '<select class="plate-letter-select" id="plate_letter" name="plate_letter" data-required-both="1" aria-label="حرف پلاک"><option value="">حرف</option>';
    foreach (m360_rw_intake_plate_letters() as $pl) {
        echo '<option value="' . m360_rw_h($pl) . '"' . ($letter === $pl ? ' selected' : '') . '>' . m360_rw_h($pl) . '</option>';
    }
    echo '</select></div><span class="iran-plate-sep" aria-hidden="true"></span>';
    echo '<div class="iran-plate-group iran-plate-group--middle" aria-label="سه رقم وسط"><span class="iran-plate-group__label">۳ رقم</span><div class="iran-plate-group__inputs">';
    foreach ([1 => $m1, 2 => $m2, 3 => $m3] as $idx => $sel) {
        echo '<select class="plate-digit-select" id="plate_middle_digit_' . $idx . '" name="plate_middle_digit_' . $idx . '" data-required-both="1" aria-label="رقم سه‌رقمی"><option value="">-</option>';
        for ($d = 0; $d <= 9; $d++) {
            echo '<option value="' . $d . '"' . ((string)$d === (string)$sel ? ' selected' : '') . '>' . $d . '</option>';
        }
        echo '</select>';
    }
    echo '</div></div></div>';
    echo '<div class="iran-plate-region-box" aria-label="کد ایران"><span class="iran-plate-region-box__label">ایران</span><div class="iran-plate-region-box__digits">';
    foreach ([1 => $r1, 2 => $r2] as $idx => $sel) {
        echo '<select class="plate-digit-select" id="plate_region_digit_' . $idx . '" name="plate_region_digit_' . $idx . '" data-required-both="1" aria-label="رقم کد ایران"><option value="">-</option>';
        for ($d = 0; $d <= 9; $d++) {
            echo '<option value="' . $d . '"' . ((string)$d === (string)$sel ? ' selected' : '') . '>' . $d . '</option>';
        }
        echo '</select>';
    }
    echo '</div></div></div>';
    echo '<p id="m360_rw_plate_preview" class="iran-plate-preview" aria-live="polite">' . ($display !== '' ? m360_rw_h($display) : 'پس از تکمیل، پلاک اینجا نمایش داده می‌شود') . '</p>';
    echo '<input type="hidden" id="plate_left_2_digits" name="plate_left_2_digits" value="' . m360_rw_h($left) . '">';
    echo '<input type="hidden" id="plate_middle_3_digits" name="plate_middle_3_digits" value="' . m360_rw_h($mid) . '">';
    echo '<input type="hidden" id="plate_region_2_digits" name="plate_region_2_digits" value="' . m360_rw_h($iran) . '">';
    echo '<input type="hidden" id="plate_display" name="plate_display" value="' . m360_rw_h($display) . '">';
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
    string $saveUrl,
    string $activeStep = '',
    bool $stepperMode = false
): void {
    $secCam = $activeStep !== '' && $stepperMode
        ? m360_rw_intake_stepper_section_ui_state('camera_photo', $activeStep, $payloadData, $request, $formValues, $editSection)
        : m360_rw_intake_section_ui_state('camera_photo', $payloadData, $request, $formValues, $editSection);
    $photoStatus = m360_rw_intake_reception_photo_status($payloadData);
    if (!$stepperMode) {
        echo '<section class="m360-rw-section-block" id="section-camera-photo">';
        echo '<h2 class="m360-rw-section-title">عکس‌های پذیرش خودرو</h2>';
    } else {
        echo '<div class="m360-rw-panel" id="section-camera-photo">';
    }
    echo '<div class="m360-rw-panel-inner">';
    m360_rw_intake_render_section_header('عکس‌های پذیرش', $secCam, $onlineRequestId, 'camera_photo', $canAct);
    echo '<p class="m360-rw-muted">عکس‌های پذیرش: <strong>' . m360_rw_h((string)$photoStatus['count']) . '/' . m360_rw_h((string)$photoStatus['min_required']) . '</strong></p>';
    if (!empty($photoStatus['complete'])) {
        echo '<p class="m360-rw-flash is-ok">عکس‌های پذیرش کامل است.</p>';
    } elseif ($photoStatus['legacy_only']) {
        echo '<p class="m360-rw-warn">عکس قدیمی موجود است، اما چک‌لیست ۶ عکس هنوز کامل نیست.</p>';
    } elseif (!empty($photoStatus['missing_labels'])) {
        echo '<p class="m360-rw-warn">اسلات‌های باقی‌مانده: ' . m360_rw_h(implode('، ', $photoStatus['missing_labels'])) . '</p>';
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
            if ($stepperMode) {
                m360_rw_intake_return_step_hidden('photos');
            } else {
                m360_rw_intake_return_section_hidden('camera_photo');
            }
            echo '<input type="hidden" name="camera_image_base64" class="m360-rw-slot-base64" value="">';
            $captureLabel = !empty($slot['saved']) ? 'عکس مجدد' : 'ثبت عکس';
            echo '<button type="button" class="m360-rw-btn m360-rw-btn-secondary m360-rw-slot-capture" data-slot="' . m360_rw_h($slotKey) . '">' . m360_rw_h($captureLabel) . '</button>';
            echo '<button type="submit" class="m360-rw-btn m360-rw-slot-save" data-slot="' . m360_rw_h($slotKey) . '" disabled>ذخیره</button>';
            echo '</form></div>';
        }
        echo '</div>';
        if ($stepperMode && !empty($photoStatus['complete'])) {
            echo '<div class="m360-rw-actions m360-rw-photo-continue">';
            echo '<a class="m360-rw-btn" href="' . m360_rw_h(m360_rw_intake_step_go_url($onlineRequestId, 'documents')) . '">ادامه به مستندات</a>';
            echo '</div>';
        }
    }
    echo '</div>';
    if ($stepperMode) {
        echo '</div>';
    } else {
        echo '</div></section>';
    }
}

