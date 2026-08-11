<?php
declare(strict_types=1);

const M360_CONTRACT_VERSION = 'MOGHARE360-INTAKE-V1';
const M360_CONTRACT_TITLE = 'قرارداد پذیرش، بررسی، کارشناسی، تعمیر، سرویس، تأمین قطعه و تحویل خودرو';
// LEGAL_ENTITY_REVIEW_REQUIRED — legal contracting party name; not commercial product brand
const M360_CONTRACT_COMPANY = 'مجموعه خدمات فنی مهندسی مقاره موتورز';

function m360_contract_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Display-only thousand separator for MONEY / mileage amounts only.
 * NEVER use for mobile, national code, VIN, plate, request/contract IDs.
 */
function m360_format_number($value): string
{
    return m360_format_money_digits_only($value);
}

/** @param mixed $value */
function m360_format_money_digits_only($value): string
{
    if ($value === null) {
        return '';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_int($value) || is_float($value)) {
        if (!is_finite((float)$value)) {
            return '';
        }
        if ((float)$value == floor((float)$value)) {
            return number_format((int)$value, 0, '.', ',');
        }

        return number_format((float)$value, 2, '.', ',');
    }

    $raw = trim((string)$value);
    if ($raw === '' || $raw === '-' || $raw === '—') {
        return $raw;
    }

    // Identity-like values must never receive money commas.
    $digitsOnly = preg_replace('/\D+/', '', $raw) ?? '';
    if ($digitsOnly !== '' && m360_format_is_identity_digit_string($digitsOnly, $raw)) {
        return m360_format_plain_digits($raw);
    }

    $compact = str_replace([',', ' ', '٬', '،'], '', $raw);
    if (preg_match('/^-?\d+(\.\d+)?$/', $compact) === 1) {
        if (str_contains($compact, '.')) {
            return number_format((float)$compact, 2, '.', ',');
        }

        return number_format((int)$compact, 0, '.', ',');
    }

    // Mixed money text: format digit groups that are not identity-shaped.
    $formatted = preg_replace_callback(
        '/\d{4,}/',
        static function (array $m): string {
            $g = $m[0];
            if (m360_format_is_identity_digit_string($g, $g)) {
                return $g;
            }

            return number_format((int)$g, 0, '.', ',');
        },
        $raw
    );

    return is_string($formatted) ? $formatted : $raw;
}

function m360_format_is_identity_digit_string(string $digits, string $raw = ''): bool
{
    $len = strlen($digits);
    // Iranian mobile — never money-format.
    if ($len === 11 && str_starts_with($digits, '09')) {
        return true;
    }
    // VIN / alphanumeric identity tokens.
    $compact = strtoupper(str_replace([' ', '-'], '', $raw));
    if ($compact !== '' && preg_match('/^[A-HJ-NPR-Z0-9]{11,17}$/', $compact) === 1 && preg_match('/[A-Z]/', $compact) === 1) {
        return true;
    }

    return false;
}

/** Exact digits / controlled plain display — no thousand separators. */
function m360_format_plain_digits($value): string
{
    $raw = trim((string)($value ?? ''));
    if ($raw === '' || $raw === '-' || $raw === '—') {
        return $raw === '' ? '' : $raw;
    }

    return $raw;
}

function m360_format_masked_mobile(string $mobile): string
{
    $digits = preg_replace('/\D+/', '', $mobile) ?? '';
    if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
        return substr($digits, 0, 4) . '***' . substr($digits, -4);
    }
    if (strlen($digits) >= 8) {
        return substr($digits, 0, 4) . '***' . substr($digits, -4);
    }

    return trim($mobile) !== '' ? trim($mobile) : '-';
}

function m360_format_national_code($code): string
{
    $raw = trim((string)($code ?? ''));
    if ($raw === '' || $raw === '-' || $raw === '—') {
        return $raw === '' ? '' : $raw;
    }
    $digits = preg_replace('/\D+/', '', $raw) ?? '';

    return $digits !== '' ? $digits : $raw;
}

function m360_format_vin($vin): string
{
    $raw = strtoupper(trim((string)($vin ?? '')));
    if ($raw === '' || $raw === '-' || $raw === '—') {
        return $raw === '' ? '' : $raw;
    }

    return preg_replace('/\s+/', '', $raw) ?? $raw;
}

/** @param array<string, mixed>|string $plateParts */
function m360_format_plate($plateParts): string
{
    if (is_array($plateParts)) {
        $parts = array_filter(array_map(static fn($v) => trim((string)$v), $plateParts), static fn($v) => $v !== '');

        return $parts !== [] ? implode(' ', $parts) : '-';
    }
    $raw = trim((string)$plateParts);

    return $raw !== '' ? $raw : '-';
}

function m360_format_mileage($km): string
{
    $raw = trim((string)($km ?? ''));
    if ($raw === '' || $raw === '-' || $raw === '—') {
        return $raw === '' ? '' : $raw;
    }
    $digits = preg_replace('/[^\d]/', '', $raw) ?? '';
    if ($digits === '') {
        return $raw;
    }

    return number_format((int)$digits, 0, '.', ',') . ' کیلومتر';
}

/**
 * True when a contract display field is an empty placeholder (dash / blank / generic).
 */
function m360_contract_is_blank_display($value): bool
{
    $raw = trim((string)($value ?? ''));
    if ($raw === '' || $raw === '-' || $raw === '—' || $raw === 'ثبت نشده') {
        return true;
    }

    return m360_contract_is_generic_placeholder($raw);
}

/**
 * Generic intake placeholders that must not appear in contract/PDF once structured data exists.
 */
function m360_contract_is_generic_placeholder(string $value): bool
{
    $raw = trim($value);
    if ($raw === '') {
        return false;
    }
    $generics = [
        'مطابق انتخاب پرونده',
        'مطابق انتخاب پذیرش',
        'مطابق اعلام پذیرش',
        'پس از کارشناسی اعلام می‌شود',
        'ثبت‌شده در پرونده پذیرش',
    ];

    return in_array($raw, $generics, true);
}

/**
 * Normalize agreement/display value for contract/PDF: generics and dashes → empty.
 */
function m360_contract_normalize_agreement_display($value): string
{
    $raw = trim((string)($value ?? ''));
    if (m360_contract_is_blank_display($raw)) {
        return '';
    }

    return $raw;
}

/**
 * Canonical yes/no Persian for contract fields (دارد/ندارد/empty).
 */
function m360_contract_agreement_yes_no_display(string $value): string
{
    $value = strtolower(trim($value));
    if ($value === 'yes' || $value === '1' || $value === 'true' || $value === 'دارد') {
        return 'دارد';
    }
    if ($value === 'no' || $value === '0' || $value === 'false' || $value === 'ندارد') {
        return 'ندارد';
    }
    if (function_exists('m360_rw_intake_agreement_yes_no_fa')) {
        $fa = trim(m360_rw_intake_agreement_yes_no_fa($value));
        if ($fa === 'دارد' || $fa === 'ندارد') {
            return $fa;
        }
    }

    return m360_contract_normalize_agreement_display($value);
}

/**
 * Canonical part-purchase Persian label for contract/PDF.
 */
function m360_contract_part_purchase_display(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (function_exists('m360_rw_intake_part_purchase_authorization_fa')) {
        $fa = trim(m360_rw_intake_part_purchase_authorization_fa($value));
        if ($fa !== '' && $fa !== '—') {
            return m360_contract_normalize_agreement_display($fa);
        }
    }
    $aliases = [
        'under_500m' => 'زیر 500,000,000 ریال',
        'up_to_1b' => 'تا 1,000,000,000 ریال',
        'up_to_2b' => 'تا 2,000,000,000 ریال',
        'no_limit' => 'بدون سقف',
        'owner_sms_coord' => 'با هماهنگی مالک پیامکی',
    ];
    if (isset($aliases[$value])) {
        return $aliases[$value];
    }

    return m360_contract_normalize_agreement_display($value);
}

/**
 * Normalize money input to raw digit string (strips commas/spaces). Empty if none.
 */
function m360_contract_normalize_money_digits($value): string
{
    $digits = preg_replace('/[^\d]/', '', (string)($value ?? '')) ?? '';

    return $digits;
}

/**
 * Parse «از X تا Y» (optional ریال/تومان) into digit min/max.
 *
 * @return array{min:string,max:string}
 */
function m360_contract_parse_cost_range_bounds(string $text): array
{
    $text = trim($text);
    if ($text === '') {
        return ['min' => '', 'max' => ''];
    }
    if (preg_match('/از\s*([\d,٬،\s]+)\s*تا\s*([\d,٬،\s]+)/u', $text, $m) === 1) {
        return [
            'min' => m360_contract_normalize_money_digits($m[1]),
            'max' => m360_contract_normalize_money_digits($m[2]),
        ];
    }

    return ['min' => '', 'max' => ''];
}

/**
 * Display-only IRR money label. Raw stored value must remain unformatted.
 * Missing values render as «ثبت نشده» (not a meaningless dash).
 */
function m360_format_money_irr($value): string
{
    $raw = trim((string)($value ?? ''));
    if (m360_contract_is_blank_display($raw)) {
        return 'ثبت نشده';
    }
    if (mb_strpos($raw, 'ریال') !== false || mb_strpos($raw, 'تومان') !== false) {
        return m360_format_number($raw);
    }
    $num = m360_format_number($raw);
    if (m360_contract_is_blank_display($num)) {
        return 'ثبت نشده';
    }

    return $num . ' ریال';
}

function m360_contract_display_mobile(string $mobile): string
{
    $masked = m360_format_masked_mobile($mobile);

    return $masked !== '' ? $masked : '-';
}

/**
 * @param array<string, mixed> $data
 */
function m360_contract_render_html(array $data, bool $wrapDocument = true): string
{
    $customerName = m360_contract_h((string)($data['customer_name'] ?? '-'));
    $mobile = m360_contract_h(m360_contract_display_mobile((string)($data['mobile'] ?? '-')));
    $odometerRaw = trim((string)($data['odometer'] ?? '-'));
    if (m360_contract_is_blank_display($odometerRaw)) {
        $odometer = m360_contract_h('-');
    } else {
        $odometer = m360_contract_h(m360_format_mileage($odometerRaw));
    }
    $fuelLevel = m360_contract_h((string)($data['fuel_level'] ?? '-'));
    $serviceType = m360_contract_h((string)($data['service_type'] ?? '-'));
    $requestDescription = m360_contract_h((string)($data['request_description'] ?? '-'));
    $costRangeRaw = trim((string)($data['cost_range'] ?? ''));
    if (m360_contract_is_blank_display($costRangeRaw)) {
        $costRangeDisplay = 'ثبت نشده';
    } elseif (preg_match('/^-?[\d,\s٬،]+$/', $costRangeRaw) === 1) {
        $costRangeDisplay = m360_format_money_irr($costRangeRaw);
    } else {
        $costRangeDisplay = m360_format_money_irr($costRangeRaw);
    }
    $costRange = m360_contract_h($costRangeDisplay);
    $prepaymentRaw = trim((string)($data['prepayment'] ?? ''));
    $prepayment = m360_contract_h(m360_contract_is_blank_display($prepaymentRaw) ? 'ثبت نشده' : m360_format_money_irr($prepaymentRaw));
    $purchaseLimitRaw = m360_contract_normalize_agreement_display($data['purchase_limit'] ?? '');
    $purchaseLimit = m360_contract_h($purchaseLimitRaw !== '' ? m360_contract_part_purchase_display($purchaseLimitRaw) : 'ثبت نشده');
    if ($purchaseLimit === m360_contract_h('') || trim(html_entity_decode(strip_tags($purchaseLimit))) === '') {
        $purchaseLimit = m360_contract_h('ثبت نشده');
    }
    $testDriveAllowed = m360_contract_h(m360_contract_normalize_agreement_display($data['test_drive_allowed'] ?? '') !== ''
        ? m360_contract_normalize_agreement_display($data['test_drive_allowed'] ?? '')
        : 'ثبت نشده');
    $bodyInsuranceStatus = m360_contract_h(m360_contract_normalize_agreement_display($data['body_insurance_status'] ?? '') !== ''
        ? m360_contract_normalize_agreement_display($data['body_insurance_status'] ?? '')
        : 'ثبت نشده');
    $thirdPartyInsurance = m360_contract_h(m360_contract_normalize_agreement_display($data['third_party_insurance'] ?? '') !== ''
        ? m360_contract_normalize_agreement_display($data['third_party_insurance'] ?? '')
        : 'ثبت نشده');
    $otherAgreementsNoteRaw = m360_contract_normalize_agreement_display($data['other_agreements_note'] ?? '');
    $otherAgreementsNote = m360_contract_h($otherAgreementsNoteRaw !== '' ? $otherAgreementsNoteRaw : 'ثبت نشده');
    $serviceCostMin = m360_contract_h(m360_format_money_irr($data['service_cost_min'] ?? ''));
    $serviceCostMax = m360_contract_h(m360_format_money_irr($data['service_cost_max'] ?? ''));
    $checklistRaw = m360_contract_normalize_agreement_display($data['checklist_summary'] ?? '');
    $checklistSummary = m360_contract_h($checklistRaw !== '' ? $checklistRaw : 'ثبت نشده');
    $jobcardId = trim((string)($data['jobcard_id'] ?? ''));
    $onlineRequestId = trim((string)($data['online_request_id'] ?? ''));
    $caseCode = $onlineRequestId !== '' && $onlineRequestId !== '-' && $onlineRequestId !== '0'
        ? $onlineRequestId
        : (($jobcardId !== '' && $jobcardId !== '-' && $jobcardId !== '0') ? $jobcardId : '—');
    $caseCode = m360_contract_h(m360_format_plain_digits($caseCode));
    $vin = m360_contract_h(m360_format_vin((string)($data['vin'] ?? '-')));
    $plate = m360_contract_h(m360_format_plate((string)($data['plate'] ?? '-')));
    $visitDate = m360_contract_h((string)($data['visit_date'] ?? '-'));
    $receptionDate = m360_contract_h((string)($data['reception_date'] ?? '-'));
    $contractHash = m360_contract_h((string)($data['contract_hash'] ?? '-'));
    $brandLine = trim((string)($data['brand'] ?? ''));
    $modelLine = trim((string)($data['model'] ?? $data['vehicle_class'] ?? ''));
    $vehicleTypeLine = trim((string)($data['vehicle_type'] ?? ''));
    $vehicleSummary = trim((string)($data['vehicle'] ?? ''));
    if ($vehicleSummary === '' || $vehicleSummary === '-') {
        $vehicleSummary = trim($brandLine . ' ' . $modelLine);
    }
    $vehicle = m360_contract_h($vehicleSummary !== '' ? $vehicleSummary : '-');

    $title = m360_contract_h(M360_CONTRACT_TITLE);
    $version = m360_contract_h(M360_CONTRACT_VERSION);
    $company = m360_contract_h(M360_CONTRACT_COMPANY);
    $vehicleTypeHtml = '';
    if ($vehicleTypeLine !== '' && $vehicleTypeLine !== '-' && !m360_contract_is_blank_display($vehicleTypeLine)) {
        $vehicleTypeHtml = '<div class="m360-contract-item"><span>نوع خودرو</span><strong>'
            . m360_contract_h($vehicleTypeLine) . '</strong></div>';
    }

    $body = <<<HTML
<article class="m360-contract-sheet">
  <h1>{$title}</h1>

  <p class="m360-contract-meta">
    نسخه قرارداد:
    <strong>{$version}</strong>
    |
    مجموعه:
    <strong>{$company}</strong>
    |
    کد پرونده:
    <strong>{$caseCode}</strong>
    |
    تاریخ مشاهده:
    <strong>{$visitDate}</strong>
  </p>

  <section class="m360-contract-block m360-contract-summary">
    <h2>خلاصه اطلاعات پذیرش</h2>

    <div class="m360-contract-grid">
      <div class="m360-contract-item">
        <span>نام مشتری</span>
        <strong>{$customerName}</strong>
      </div>

      <div class="m360-contract-item">
        <span>شماره موبایل</span>
        <strong>{$mobile}</strong>
      </div>

      <div class="m360-contract-item">
        <span>خودرو (برند / مدل)</span>
        <strong>{$vehicle}</strong>
      </div>
      {$vehicleTypeHtml}

      <div class="m360-contract-item">
        <span>پلاک</span>
        <strong>{$plate}</strong>
      </div>

      <div class="m360-contract-item">
        <span>VIN</span>
        <strong>{$vin}</strong>
      </div>

      <div class="m360-contract-item">
        <span>کیلومتر</span>
        <strong>{$odometer}</strong>
      </div>

      <div class="m360-contract-item">
        <span>سطح بنزین</span>
        <strong>{$fuelLevel}</strong>
      </div>

      <div class="m360-contract-item">
        <span>نوع خدمت</span>
        <strong>{$serviceType}</strong>
      </div>

      <div class="m360-contract-item">
        <span>شرح درخواست</span>
        <strong>{$requestDescription}</strong>
      </div>

      <div class="m360-contract-item">
        <span>توافق / محدوده هزینه</span>
        <strong>{$costRange}</strong>
      </div>

      <div class="m360-contract-item">
        <span>حداقل هزینه خدمات</span>
        <strong>{$serviceCostMin}</strong>
      </div>

      <div class="m360-contract-item">
        <span>حداکثر هزینه خدمات</span>
        <strong>{$serviceCostMax}</strong>
      </div>

      <div class="m360-contract-item">
        <span>علی‌الحساب</span>
        <strong>{$prepayment}</strong>
      </div>

      <div class="m360-contract-item">
        <span>سقف اختیار خرید قطعه</span>
        <strong>{$purchaseLimit}</strong>
      </div>

      <div class="m360-contract-item">
        <span>اجازه تست درایو</span>
        <strong>{$testDriveAllowed}</strong>
      </div>

      <div class="m360-contract-item">
        <span>بیمه شخص ثالث</span>
        <strong>{$thirdPartyInsurance}</strong>
      </div>

      <div class="m360-contract-item">
        <span>بیمه بدنه</span>
        <strong>{$bodyInsuranceStatus}</strong>
      </div>

      <div class="m360-contract-item">
        <span>سایر توافقات</span>
        <strong>{$otherAgreementsNote}</strong>
      </div>

      <div class="m360-contract-item">
        <span>چک‌لیست پذیرش</span>
        <strong>{$checklistSummary}</strong>
      </div>

      <div class="m360-contract-item">
        <span>شناسه درخواست آنلاین</span>
        <strong>{$onlineRequestId}</strong>
      </div>

      <div class="m360-contract-item">
        <span>تاریخ پذیرش</span>
        <strong>{$receptionDate}</strong>
      </div>

      <div class="m360-contract-item">
        <span>هش قرارداد</span>
        <strong>{$contractHash}</strong>
      </div>
    </div>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۱: مبنا، اعتبار و اجزای قرارداد</h2>
    <p>
      این قرارداد به استناد مواد ۱۰، ۱۸۳ و ۶۵۶ قانون مدنی و بر اساس توافق طرفین، جهت پذیرش، بررسی، کارشناسی، عیب‌یابی، سرویس، تعمیر، تأمین قطعه، انجام خدمات جانبی، تست، نگهداری، ترخیص، تحویل و تسویه خودرو تنظیم می‌گردد.
    </p>
    <p>
      مشتری، مالک، نماینده مالک یا تحویل‌دهنده خودرو با امضای فیزیکی یا تأیید آنلاین این قرارداد اعلام می‌نماید که اطلاعات ثبت‌شده در پرونده پذیرش، اطلاعات خودرو، تصاویر خودرو، چک‌لیست پذیرش، وضعیت ظاهری، موضوع خدمت، محدوده هزینه، مبلغ علی‌الحساب، سقف اختیار خرید قطعه، خدمات انتخاب‌شده، شرایط تحویل و سایر اطلاعات ثبت‌شده در سامانه را مشاهده کرده و می‌پذیرد.
    </p>
    <p>
      اطلاعات هویتی مشتری، مالک، تحویل‌دهنده، خودرو، تصاویر، موضوع خدمت، مبالغ، تاریخ‌ها، زمان‌ها، گزینه‌های انتخاب‌شده، سوابق پیامکی، امضای آنلاین، IP، اطلاعات مرورگر و سایر داده‌های ثبت‌شده در نرم‌افزار، جزء لاینفک این قرارداد بوده و در تفسیر مفاد قرارداد ملاک عمل خواهد بود.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۲: موضوع قرارداد</h2>
    <p>
      موضوع قرارداد، همان خدمت یا خدماتی است که پیش از مرحله تأیید قرارداد، توسط مشتری، پذیرش‌گر یا نماینده مجاز مجموعه در نرم‌افزار انتخاب، ثبت و به این قرارداد لینک شده است.
    </p>
    <p>
      موضوع قرارداد می‌تواند شامل کارشناسی و عیب‌یابی، کارشناسی خرید یا فروش، سرویس‌های دوره‌ای، ارتقا و آپشن، خدمات تکمیلی، خدمات جانبی، خدمات بیرونی یا خدمات تخصصی مرتبط با خودرو باشد که در پرونده نرم‌افزاری ثبت شده است.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۳: وضعیت خودرو، تصاویر و چک‌لیست پذیرش</h2>
    <p>
      مشتری تأیید می‌کند که وضعیت خودرو در زمان پذیرش، شامل وضعیت ظاهری، سطح سوخت، کیلومتر، تجهیزات همراه، اقلام داخل خودرو، عکس‌های پذیرش، چک‌لیست ورود و توضیحات پذیرش‌گر، توسط مجموعه ثبت شده و برای مشاهده مشتری در نرم‌افزار یا نسخه قرارداد قابل مشاهده است.
    </p>
    <p>
      عکس‌ها، چک‌لیست پذیرش، توضیحات ثبت‌شده و اطلاعات نرم‌افزاری، ملاک وضعیت خودرو در زمان ورود به مجموعه خواهد بود.
    </p>
    <p>
      مجموعه نسبت به اشیای شخصی، وجه نقد، اسناد، مدارک، کارت‌ها، لوازم الکترونیکی، ابزار، قطعات، اقلام همراه یا هرگونه وسیله‌ای که در چک‌لیست پذیرش ثبت نشده باشد، مسئولیتی نخواهد داشت.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۴: خرابی‌های پنهان، کامپیوتری و غیرقابل مشاهده</h2>
    <p>
      مشتری آگاه است و می‌پذیرد که برخی خرابی‌ها، ایرادات، خطاهای کامپیوتری، ایرادات ECU، سنسورها، ماژول‌ها، سیستم‌های برقی، نرم‌افزاری، گیربکس، موتور، توربو، تعلیق، ترمز، سیستم کولر، سیستم سوخت‌رسانی، نشتی‌ها، خطاهای مقطعی، ضعف قطعات، خرابی‌های پنهان و ایراداتی که در لحظه پذیرش قابل مشاهده یا قابل تشخیص قطعی نیستند، ممکن است پس از دیاگ، تست، باز و بست، گرم شدن خودرو، درایو تست، ادامه کار خودرو یا بررسی تخصصی مشخص شوند.
    </p>
    <p>
      مشتری صراحتاً می‌پذیرد که مسئولیت مالی و هزینه‌های ناشی از بررسی، عیب‌یابی، تعمیر، تعویض، تهیه قطعه، تأمین خدمات یا رفع ایرادات پنهان، کامپیوتری، برقی، نرم‌افزاری و غیرقابل مشاهده، پس از اعلام و ثبت در پرونده، بر عهده مشتری خواهد بود.
    </p>
    <p>
      پذیرش این ماده برای ادامه فرآیند پذیرش، بررسی، کارشناسی، تعمیر یا سرویس خودرو الزامی است.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۵: اختیار بررسی، بازدید، دیاگ، تست، جابه‌جایی و انجام خدمات</h2>
    <p>
      مشتری به مجموعه اجازه می‌دهد در حدود نیاز فنی و عملیاتی، خودرو را جهت بررسی، عیب‌یابی، دیاگ، تست، جابه‌جایی در محوطه کارگاه، تست عملکردی، بررسی بین واحدهای داخلی، کنترل کیفیت، ارجاع به خدمات تخصصی مرتبط یا انتقال به واحد خدماتی مورد تأیید مجموعه حرکت داده یا بررسی نماید.
    </p>
    <p>
      تشخیص ضرورت بررسی، تست، باز و بست، دیاگ، جابه‌جایی، تست عملکردی یا ارجاع به خدمات مرتبط، با مجموعه خواهد بود، مگر آنکه مشتری در گزینه‌های قرارداد محدودیتی را صریحاً انتخاب کرده باشد.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۶: تست رانندگی، حادثه احتمالی، بیمه بدنه و محدودیت‌های انتخاب مشتری</h2>
    <p>
      مشتری آگاه است که در برخی خدمات، انجام تست رانندگی یا جابه‌جایی خودرو برای تشخیص عیب، کنترل کیفیت، بررسی صدا، لرزش، عملکرد موتور، گیربکس، ترمز، تعلیق، کولر، آپشن‌ها یا تأیید نهایی خدمات، ضروری یا مفید است.
    </p>
    <p>
      وضعیت اجازه تست رانندگی، جابه‌جایی خارج از محوطه، حادثه احتمالی و استفاده از بیمه بدنه، طبق گزینه‌ای است که مشتری در فرم تأیید قرارداد انتخاب می‌کند.
    </p>
    <p>
      در صورتی که مشتری اجازه تست رانندگی یا استفاده از بیمه بدنه را تأیید نکند، یا خودرو فاقد بیمه بدنه باشد، مجموعه می‌تواند بنا به تشخیص خود خودرو را بدون تست رانندگی نهایی تحویل دهد، یا از مشتری بخواهد پیش از اتمام کار در محل مجموعه حاضر شود تا تست رانندگی با حضور، اطلاع یا مسئولیت مشتری انجام شود.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۷: وکالت، اختیار خرید قطعات و خدمات مرتبط</h2>
    <p>
      مشتری با تأیید این قرارداد، در حدود سقف انتخاب‌شده در نرم‌افزار، به مجموعه وکالت و اجازه می‌دهد قطعات یدکی، مواد مصرفی، خدمات جانبی، خدمات بیرونی، سرویس‌های مرتبط، اقلام فنی، لوازم مصرفی، قطعات الکترونیکی، قطعات وارداتی، قطعات سفارشی و سایر اقلام مورد نیاز خودرو را تهیه، خریداری، سفارش‌گذاری یا هماهنگ نماید.
    </p>
    <p>
      سقف اختیار خرید قطعه و خدمات مرتبط توسط مشتری در بخش گزینه‌های قرارداد انتخاب می‌شود و ملاک اقدام مجموعه خواهد بود.
    </p>
    <p>
      مشتری آگاه است که قیمت قطعات، به‌ویژه قطعات خودروهای وارداتی، اروپایی، کمیاب، سفارشی، الکترونیکی یا فوری، ممکن است به علت نوسان بازار، نرخ ارز، موجودی محدود، فوریت، حمل، زمان تأمین، شرایط تأمین‌کننده، تغییر قیمت روزانه یا عدم ثبات بازار تغییر کند.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۸: قطعات الکترونیکی، قطعات حساس و محدودیت ضمانت</h2>
    <p>
      مشتری آگاه است که برخی قطعات الکترونیکی، یونیت‌ها، ECU، سنسورها، ماژول‌ها، قطعات برقی، قطعات کدینگ‌دار، قطعات وارداتی، قطعات استوک، قطعات سفارشی و قطعات خاص، به علت ماهیت فنی، شرایط بازار، وضعیت کارکرد خودرو، نصب، کدینگ، برنامه‌ریزی، شرایط تأمین یا سیاست تأمین‌کننده، ممکن است دارای محدودیت ضمانت، عدم امکان برگشت، شرایط خاص تست یا عدم پذیرش مرجوعی باشند.
    </p>
    <p>
      شرایط ضمانت، تست، تعویض، نصب، عدم ضمانت یا عدم امکان مرجوعی هر قطعه، حسب مورد، مطابق اعلام تأمین‌کننده، وضعیت قطعه، شرایط بازار و توضیحات ثبت‌شده در فاکتور یا پرونده اعمال خواهد شد.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۹: علی‌الحساب، محدوده هزینه و ماهیت برآورد اولیه</h2>
    <p>
      مبلغ علی‌الحساب بر اساس مبلغ برآوردی اعلام‌شده توسط پذیرش محاسبه می‌شود و حداقل معادل ۵۰ درصد مبلغ برآوردی خواهد بود.
    </p>
    <p>
      مبلغ علی‌الحساب توسط نرم‌افزار محاسبه و به بالاترین مضرب ۱۰,۰۰۰,۰۰۰ تومان گرد می‌شود.
    </p>
    <p>
      محدوده هزینه اعلام‌شده در زمان پذیرش، صرفاً برآورد اولیه بوده و مبلغ نهایی محسوب نمی‌شود. این مبلغ ممکن است پس از کارشناسی، دیاگ، باز شدن قطعات، تست، مشخص شدن خرابی‌های پنهان، تغییر قیمت قطعات، تغییر شرایط تأمین یا تأیید خدمات تکمیلی افزایش یا کاهش یابد.
    </p>
    <p>
      شروع یا ادامه کار می‌تواند منوط به پرداخت علی‌الحساب باشد. در صورت عدم پرداخت علی‌الحساب، مجموعه مجاز است از شروع کار، ادامه کار، سفارش قطعه، رزرو زمان کارگاهی یا تحویل خودرو خودداری نماید.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۱۰: خدمات VIP، خدمات خارج از ساعت کاری و خدمات روز تعطیل</h2>
    <p>
      مشتری می‌تواند در بخش گزینه‌های مازاد، خدمات ویژه یا خارج از شرایط عادی را انتخاب یا رد نماید.
    </p>
    <p>
      گزینه‌های مازاد شامل خدمات VIP، دریافت خودرو، تحویل خودرو، حمل، جابه‌جایی یا رفت‌وآمد خودرو، انجام کار روی خودرو خارج از ساعت کاری عرف مجموعه، و انجام کار روی خودرو در روز جمعه یا تعطیل رسمی است.
    </p>
    <p>
      در صورت انتخاب هر یک از گزینه‌های فوق، هزینه مازاد همان گزینه توسط سیستم محاسبه و به مشتری اعلام می‌شود.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۱۱: توقف کار، عدم پاسخ‌گویی و تکمیل مجوزها</h2>
    <p>
      در صورت عدم پرداخت علی‌الحساب، عدم تأیید هزینه‌های لازم، عدم پاسخ‌گویی مشتری، عدم تعیین تکلیف خرید قطعه، اختلاف در ادامه کار، عدم تسویه، تغییر نظر مشتری، عدم امکان تماس با مالک یا تحویل‌دهنده، ابهام در مالکیت یا عدم ارائه مجوزهای لازم، مجموعه مجاز است فرآیند بررسی، تعمیر، سفارش قطعه، تهیه قطعه، ادامه خدمات یا تحویل خودرو را تا تعیین تکلیف متوقف نماید.
    </p>
    <p>
      آثار مالی، افزایش هزینه، خواب خودرو، تأخیر در تحویل، تغییر قیمت قطعه یا از دست رفتن فرصت خرید ناشی از عدم پاسخ‌گویی یا عدم تصمیم‌گیری به‌موقع مشتری، بر عهده مشتری خواهد بود.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۱۲: حق نگهداری خودرو، تسویه و تحویل</h2>
    <p>
      خودرو تا زمان تسویه کامل هزینه‌های خدمات، قطعات، اجرت، هزینه‌های جانبی، حمل، انبارداری، توقف، خدمات VIP، خدمات خارج از زمان عرف، خدمات روز تعطیل یا سایر هزینه‌های ثبت‌شده در پرونده نزد مجموعه باقی خواهد ماند.
    </p>
    <p>
      تحویل نهایی خودرو منوط به تسویه کامل، تأیید واحد مالی، تکمیل فرآیند تحویل و احراز هویت تحویل‌گیرنده است.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۱۳: هزینه توقف خودرو پس از اعلام آماده بودن</h2>
    <p>
      پس از اعلام آماده بودن خودرو به مشتری، در صورت عدم مراجعه، عدم تسویه، عدم تعیین تکلیف، عدم پاسخ‌گویی یا عدم تحویل‌گیری ظرف ۲ روز کاری، مجموعه می‌تواند هزینه توقف، نگهداری، پارکینگ، مراقبت یا اشغال فضای کارگاهی را مطابق تعرفه اعلامی و ثبت‌شده در پرونده محاسبه نماید.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۱۴: تحویل‌دهنده غیرمالک و مسئولیت اظهار</h2>
    <p>
      در صورتی که تحویل‌دهنده خودرو مالک رسمی یا مالک عرفی خودرو نباشد، تحویل‌دهنده اعلام می‌نماید که با اجازه مالک یا ذی‌نفع مجاز نسبت به تحویل خودرو اقدام کرده و مسئولیت صحت این اظهار را می‌پذیرد.
    </p>
    <p>
      مجموعه می‌تواند در صورت نیاز، تأیید پیامکی مالک، مدارک تکمیلی، تصویر مدارک، معرفی‌نامه، وکالت‌نامه، تأیید شرکتی یا تأیید مدیریتی درخواست نماید.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۱۵: ترخیص، کنترل نهایی و تحویل</h2>
    <p>
      تحویل خودرو پس از تکمیل خدمات، کنترل نهایی، تکمیل چک‌لیست ترخیص، اعلام هزینه‌ها، تسویه کامل و تأیید مسئول مربوطه انجام خواهد شد.
    </p>
    <p>
      مشتری در زمان تحویل موظف است وضعیت خودرو، خدمات انجام‌شده، قطعات تعویض‌شده، توضیحات فنی، فاکتور، چک‌لیست ترخیص و وضعیت ظاهری خودرو را بررسی نماید.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۱۶: حل اختلاف و مرجع رسیدگی</h2>
    <p>
      در صورت بروز اختلاف، طرفین ابتدا موضوع را از طریق مذاکره، بررسی پرونده، مستندات ثبت‌شده، تصاویر، فاکتورها، پیامک‌ها، سوابق نرم‌افزاری، چک‌لیست‌ها و گزارش‌های فنی پیگیری می‌نمایند.
    </p>
    <p>
      در صورت عدم حصول توافق، موضوع از طریق اتحادیه صنفی مربوطه، مراجع قانونی یا مرجع صالح قابل پیگیری خواهد بود.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۱۷: اعتبار امضای آنلاین و کد تأیید پیامکی</h2>
    <p>
      مشتری با مشاهده نسخه آنلاین قرارداد، مطالعه مفاد، مشاهده اطلاعات پذیرش، پاسخ به گزینه‌های اصلی، ثبت امضا و وارد کردن کد تأیید پیامکی ارسال‌شده به شماره موبایل ثبت‌شده، قرارداد را به صورت آنلاین تأیید می‌نماید.
    </p>
    <p>
      ثبت کد تأیید پیامکی همراه با زمان ثبت، شماره موبایل، IP، اطلاعات مرورگر، نسخه قرارداد، اطلاعات پرونده و داده‌های ثبت‌شده در نرم‌افزار، به منزله تأیید و امضای آنلاین قرارداد است.
    </p>
  </section>

  <section class="m360-contract-block">
    <h2>ماده ۱۸: تأیید نهایی</h2>
    <p>
      مشتری، مالک، نماینده مالک یا تحویل‌دهنده خودرو با مطالعه کامل مفاد این قرارداد، مشاهده اطلاعات پذیرش، تصاویر خودرو، محدوده هزینه، مبلغ علی‌الحساب، سقف اختیار خرید، شرایط خدمات مازاد، وضعیت تست رانندگی، وضعیت بیمه بدنه، چک‌لیست پذیرش و سایر موارد ثبت‌شده، صحت اطلاعات درج‌شده را تأیید نموده و خودرو را جهت انجام خدمات به مجموعه تحویل می‌دهد.
    </p>
    <p>
      تأیید نهایی مشتری در نرم‌افزار، امضا و ورود کد پیامکی، به منزله پذیرش تمام مفاد این قرارداد و گزینه‌های انتخاب‌شده در پرونده خواهد بود.
    </p>
  </section>

  <p class="m360-contract-footer-note">
    این قرارداد برای پذیرش خودرو در {$company} تهیه شده است.
    پس از تأیید مطالعه، ثبت امضا، پاسخ به سؤالات حقوقی و ورود کد پیامکی، قرارداد آنلاین نهایی می‌شود.
  </p>
</article>
HTML;

    if (!$wrapDocument) {
        return $body;
    }

    $styles = <<<CSS
    .m360-contract-document,
    .m360-contract-document * {
      box-sizing: border-box;
    }

    .m360-contract-document {
      margin: 0;
      padding: 24px;
      background: #f8faf9;
      color: #15201b;
      font-family: "Vazirmatn", "Tahoma", sans-serif;
      line-height: 2;
      direction: rtl;
    }

    .m360-contract-sheet {
      max-width: 940px;
      margin: 0 auto;
      background: #ffffff;
      border: 1px solid #d7e6df;
      border-radius: 14px;
      padding: 28px;
      box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
    }

    .m360-contract-sheet h1 {
      margin: 0 0 8px;
      font-size: 28px;
      color: #0e3d2f;
      line-height: 1.6;
    }

    .m360-contract-sheet h2 {
      margin: 0 0 10px;
      font-size: 20px;
      color: #0e3d2f;
    }

    .m360-contract-sheet p {
      margin: 8px 0;
      text-align: justify;
    }

    .m360-contract-meta {
      margin: 0;
      color: #3a564b;
      font-size: 14px;
    }

    .m360-contract-block {
      margin-top: 18px;
      padding: 16px;
      border: 1px solid #d7e6df;
      border-radius: 10px;
      background: #f6fbf8;
    }

    .m360-contract-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }

    .m360-contract-item {
      padding: 10px;
      background: #ffffff;
      border: 1px solid #e4eee9;
      border-radius: 8px;
    }

    .m360-contract-item span {
      display: block;
      color: #60756d;
      font-size: 13px;
      margin-bottom: 4px;
    }

    .m360-contract-item strong {
      color: #15201b;
    }

    .m360-contract-footer-note {
      margin-top: 22px;
      font-size: 13px;
      color: #4f675f;
    }

    @media (max-width: 680px) {
      .m360-contract-document {
        padding: 12px;
      }

      .m360-contract-sheet {
        padding: 18px;
      }

      .m360-contract-grid {
        grid-template-columns: 1fr;
      }

      .m360-contract-sheet h1 {
        font-size: 22px;
      }
    }
CSS;

  return <<<HTML
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title} - {$jobcardId}</title>
  <style>{$styles}</style>
</head>
<body class="m360-contract-document">
{$body}
</body>
</html>
HTML;
}

/**
 * Complete RTL contract HTML for mPDF — same clauses as customer review + signature blocks.
 *
 * @param array<string, mixed> $data
 */
function m360_contract_render_pdf_html(array $data): string
{
    // Normalize agreement fields before canonical clause render.
    $data['third_party_insurance'] = m360_contract_normalize_agreement_display($data['third_party_insurance'] ?? '');
    $data['body_insurance_status'] = m360_contract_normalize_agreement_display($data['body_insurance_status'] ?? '');
    $data['test_drive_allowed'] = m360_contract_normalize_agreement_display($data['test_drive_allowed'] ?? '');
    $data['purchase_limit'] = m360_contract_normalize_agreement_display($data['purchase_limit'] ?? '');
    $data['other_agreements_note'] = m360_contract_normalize_agreement_display($data['other_agreements_note'] ?? '');
    if (m360_contract_is_generic_placeholder((string)($data['cost_range'] ?? ''))) {
        $data['cost_range'] = '';
    }
    if (m360_contract_is_generic_placeholder((string)($data['prepayment'] ?? ''))) {
        $data['prepayment'] = '';
    }
    if (m360_contract_is_generic_placeholder((string)($data['checklist_summary'] ?? ''))) {
        $data['checklist_summary'] = '';
    }

    $body = m360_contract_render_html($data, false);
    $signatures = m360_contract_render_pdf_signature_blocks($data);

    $styles = <<<'CSS'
div.m360-pdf-root { font-family: dejavusans; direction: rtl; text-align: right; color: #111; font-size: 10.5pt; line-height: 1.45; }
div.m360-pdf-root h1 { font-size: 14pt; margin: 0 0 6px; color: #0e3d2f; }
div.m360-pdf-root h2 { font-size: 11.5pt; margin: 10px 0 4px; color: #0e3d2f; page-break-after: avoid; }
div.m360-pdf-root p { margin: 4px 0; text-align: justify; }
div.m360-pdf-root .m360-contract-meta { font-size: 9.5pt; margin: 0 0 8px; }
div.m360-pdf-root .m360-contract-summary { margin: 4px 0 8px; }
div.m360-pdf-root .m360-contract-grid { width: 100%; }
div.m360-pdf-root .m360-contract-item { margin: 0 0 3px; padding: 2px 0; border-bottom: 1px solid #e5e5e5; }
div.m360-pdf-root .m360-contract-item span { display: inline; font-size: 9pt; color: #444; margin-left: 6px; }
div.m360-pdf-root .m360-contract-item strong { display: inline; font-size: 10.5pt; }
div.m360-pdf-root .m360-contract-block { margin: 6px 0 8px; page-break-inside: avoid; }
div.m360-pdf-root .m360-contract-footer-note { margin-top: 8px; font-size: 9pt; color: #333; }
div.m360-pdf-root .m360-pdf-sign-wrap { margin-top: 12px; page-break-before: always; page-break-inside: avoid; }
div.m360-pdf-root .m360-pdf-sign-box { border: 1px solid #999; padding: 8px; margin: 0 0 10px; }
div.m360-pdf-root .m360-pdf-sign-line { margin-top: 18px; border-bottom: 1px solid #333; height: 22px; }
div.m360-pdf-root .m360-pdf-sign-img { max-width: 220px; max-height: 80px; margin-top: 6px; }
div.m360-pdf-root .m360-pdf-stamp-box { margin-top: 10px; border: 1px dashed #666; height: 60px; text-align: center; padding-top: 18px; color: #555; }
div.m360-pdf-root .m360-pdf-meta-foot { margin-top: 8px; font-size: 8.5pt; color: #444; }
CSS;

    return '<div class="m360-pdf-root" style="font-family:dejavusans; direction:rtl; text-align:right;">'
        . '<style>' . $styles . '</style>'
        . $body
        . $signatures
        . '</div>';
}

/**
 * Customer + workshop signature/acceptance blocks for contract PDF.
 *
 * @param array<string, mixed> $data
 */
function m360_contract_render_pdf_signature_blocks(array $data): string
{
    $customerName = trim((string)($data['customer_name'] ?? ''));
    if ($customerName === '' || $customerName === '-') {
        $customerName = '—';
    }
    $mobileMasked = m360_contract_display_mobile((string)($data['mobile'] ?? ''));
    $statusFa = trim((string)($data['pdf_status_fa'] ?? ''));
    $acceptance = trim((string)($data['pdf_acceptance_info'] ?? ''));
    $signed = !empty($data['pdf_customer_signed']);
    $signedAt = trim((string)($data['pdf_customer_signed_at'] ?? ''));
    $method = trim((string)($data['pdf_acceptance_method'] ?? ''));
    if ($method === '') {
        $method = $signed ? 'OTP / امضای دیجیتال / ثبت سیستمی' : '—';
    }
    $customerStatus = $signed
        ? 'تأیید شده توسط مشتری'
        : (str_contains($acceptance, 'اصلاح') ? 'برگشت برای اصلاح' : 'در انتظار تأیید و امضای مشتری');

    $hash = trim((string)($data['contract_hash'] ?? ''));
    $generatedAt = trim((string)($data['pdf_generated_at'] ?? ''));
    $version = trim((string)($data['contract_version'] ?? M360_CONTRACT_VERSION));
    $company = M360_CONTRACT_COMPANY;
    $sigDataUrl = trim((string)($data['pdf_signature_image_data'] ?? ''));
    $sigMissingNote = trim((string)($data['pdf_signature_missing_note'] ?? ''));

    $customerExtra = '';
    if ($signed) {
        $customerExtra .= '<p><strong>وضعیت:</strong> ' . m360_contract_h($customerStatus) . '</p>';
        if ($signedAt !== '') {
            $customerExtra .= '<p><strong>زمان تأیید:</strong> ' . m360_contract_h($signedAt) . '</p>';
        }
        $customerExtra .= '<p><strong>روش تأیید:</strong> ' . m360_contract_h($method) . '</p>';
        $customerExtra .= '<p>تأیید الکترونیکی مشتری ثبت شده است'
            . ($hash !== '' && $hash !== '-' ? (' — هش: ' . m360_contract_h($hash)) : '')
            . '</p>';
        if ($sigDataUrl !== '' && str_starts_with($sigDataUrl, 'data:image/')) {
            $customerExtra .= '<p><strong>تصویر امضای مشتری:</strong></p>'
                . '<img class="m360-pdf-sign-img" src="' . m360_contract_h($sigDataUrl) . '" alt="امضای مشتری">';
        } elseif ($sigMissingNote !== '') {
            $customerExtra .= '<p>' . m360_contract_h($sigMissingNote) . '</p>';
        }
    } else {
        $customerExtra .= '<p><strong>وضعیت پذیرش:</strong> ' . m360_contract_h($customerStatus) . '</p>';
        $customerExtra .= '<p><strong>روش تأیید (پس از امضا):</strong> OTP / امضای دیجیتال</p>';
        if ($sigDataUrl !== '' && str_starts_with($sigDataUrl, 'data:image/')) {
            $customerExtra .= '<p><strong>پیش‌نویس امضا (قفل‌نشده):</strong></p>'
                . '<img class="m360-pdf-sign-img" src="' . m360_contract_h($sigDataUrl) . '" alt="امضا">';
        } else {
            $customerExtra .= '<p>محل امضا / اثر انگشت مشتری:</p><div class="m360-pdf-sign-line"></div>';
        }
        if ($sigMissingNote !== '') {
            $customerExtra .= '<p>' . m360_contract_h($sigMissingNote) . '</p>';
        }
    }

    return '<div class="m360-pdf-sign-wrap">'
        . '<h2>پذیرش و امضا</h2>'
        . '<div class="m360-pdf-sign-box">'
        . '<h2 style="margin-top:0;">امضای مشتری / مالک خودرو</h2>'
        . '<p><strong>نام مشتری:</strong> ' . m360_contract_h($customerName) . '</p>'
        . '<p><strong>موبایل (ماسک‌شده):</strong> ' . m360_contract_h($mobileMasked) . '</p>'
        . $customerExtra
        . '</div>'
        . '<div class="m360-pdf-sign-box">'
        . '<h2 style="margin-top:0;">امضای مالک یا نماینده مجاز تعمیرگاه</h2>'
        . '<p><strong>نام مجموعه:</strong> ' . m360_contract_h($company) . '</p>'
        . '<p><strong>نام مالک / نماینده مجاز:</strong> مالک / نماینده مجاز تعمیرگاه</p>'
        . '<p><strong>سمت:</strong> مالک / مدیر پذیرش / نماینده مجاز</p>'
        . '<p>محل امضا:</p><div class="m360-pdf-sign-line"></div>'
        . '<div class="m360-pdf-stamp-box">محل مهر مجموعه</div>'
        . '</div>'
        . '<div class="m360-pdf-meta-foot">'
        . 'نسخه: ' . m360_contract_h($version)
        . ' | وضعیت سند: ' . m360_contract_h($statusFa !== '' ? $statusFa : '—')
        . ' | تولید PDF: ' . m360_contract_h($generatedAt !== '' ? $generatedAt : '—')
        . ' | هش اسنپ‌شات: ' . m360_contract_h($hash !== '' ? $hash : '—')
        . '</div>'
        . '</div>';
}
