<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-calendar-1405-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-customer-online-submit-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';

m360_otp_session_start();

$mode = trim((string)($_GET['mode'] ?? ''));
$isExplicitNewRequest = ($mode === 'new');
$verifiedSession = m360_rw_customer_profile_resolve_verified_session_mobile();

/**
 * @return array{jy:int,jm:int,jd:int}
 */
function m360_gregorian_to_jalali(int $gy, int $gm, int $gd): array
{
    $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (int)(355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $gdm[$gm - 1]);
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return ['jy' => $jy, 'jm' => $jm, 'jd' => $jd];
}

function m360_persian_weekday(DateTimeImmutable $dt): string
{
    $map = [
        0 => 'یکشنبه',
        1 => 'دوشنبه',
        2 => 'سه‌شنبه',
        3 => 'چهارشنبه',
        4 => 'پنج‌شنبه',
        5 => 'جمعه',
        6 => 'شنبه',
    ];
    return $map[(int)$dt->format('w')] ?? '';
}

/** @return list<string> */
function m360_jalali_month_names(): array
{
    return ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
}

$tehranTz = new DateTimeZone('Asia/Tehran');
$todayGregorian = new DateTimeImmutable('today', $tehranTz);
$todayGy = (int)$todayGregorian->format('Y');
$todayGm = (int)$todayGregorian->format('n');
$todayGd = (int)$todayGregorian->format('j');
$todayJalali = m360_gregorian_to_jalali($todayGy, $todayGm, $todayGd);
$currentJalaliYear = $todayJalali['jy'];
$jalaliMonthNames = m360_jalali_month_names();

/** @var list<array{gregorian:string,jalali:string,label:string,weekday:string,day:int,month:string,is_today:bool,is_selectable:bool,disable_reason:string}> */
$visitCalendarDays = m360_rw_calendar_next_30_day_window();

/** @var list<array{value:string,label:string,jy:int,gy:int}> */
$vehicleYearOptions = [];
for ($i = 0; $i <= 20; $i++) {
    $gy = $todayGy - $i;
    $j = m360_gregorian_to_jalali($gy, 6, 15);
    $jy = $j['jy'];
    $vehicleYearOptions[] = [
        'value' => $jy . ' - ' . $gy,
        'label' => $jy . ' شمسی / ' . $gy . ' میلادی',
        'jy' => $jy,
        'gy' => $gy,
    ];
}

$birthYearSelected = '';
$birthMonthSelected = '';
$birthDaySelected = '';

$result = null;
$submitSuccess = false;
$createdRequestId = 0;
$input = [
    'first_name' => '',
    'last_name' => '',
    'full_name' => '',
    'mobile' => '',
    'national_id' => '',
    'second_phone' => '',
    'residence_address' => '',
    'vehicle_delivery_address' => '',
    'authorized_receiver_name' => '',
    'authorized_receiver_phone' => '',
    'selected_vehicle_id' => '',
    'vehicle_mode' => 'new',
    'province' => '',
    'city' => '',
    'address' => '',
    'postal_address' => '',
    'extra_contact_info' => '',
    'job_title' => '',
    'birth_date' => '',
    'vehicle_brand' => '',
    'vehicle_class' => '',
    'vehicle_year_pair' => '',
    'plate_left_2_digits' => '',
    'plate_letter' => '',
    'plate_middle_3_digits' => '',
    'plate_region_2_digits' => '',
    'plate_display' => '',
    'vin' => '',
    'odometer_km' => '',
    'request_type' => '',
    'visit_date' => '',
    'request_description' => '',
    'customer_flow' => 'new',
    'verified_customer_name' => '',
];

$requestTypes = [
    'diagnostic_inspection' => 'کارشناسی و عیب‌یابی',
    'buy_sell_inspection' => 'کارشناسی خرید/فروش',
    'periodic_service' => 'سرویس‌های دوره‌ای',
    'option_add' => 'افزودن آپشن',
    'other' => 'سایر',
];

$plateLetters = ['ب', 'ج', 'د', 'س', 'ص', 'ط', 'ق', 'ل', 'م', 'ن', 'و', 'ه', 'ی', 'ع', 'پ', 'ت', 'ک', 'گ'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    foreach (array_keys($input) as $key) {
        $input[$key] = trim((string)($_POST[$key] ?? ''));
    }
    $digitKeys = [
        'plate_first_digit_1', 'plate_first_digit_2',
        'plate_middle_digit_1', 'plate_middle_digit_2', 'plate_middle_digit_3',
        'plate_region_digit_1', 'plate_region_digit_2',
    ];
    foreach ($digitKeys as $key) {
        $input[$key] = trim((string)($_POST[$key] ?? ''));
    }
    $input['customer_flow'] = trim((string)($_POST['customer_flow'] ?? 'new'));
    $input['verified_customer_name'] = trim((string)($_POST['verified_customer_name'] ?? ''));

    if (!m360_otp_is_verified($input['mobile'])) {
        $result = [
            'ok' => false,
            'message' => 'برای ثبت درخواست، ابتدا شماره موبایل خود را با کد پیامکی تأیید کنید.',
        ];
    } elseif (!in_array($input['plate_letter'], $plateLetters, true) && $input['plate_letter'] !== '') {
        $result = [
            'ok' => false,
            'message' => 'لطفاً حرف پلاک را انتخاب کنید.',
        ];
    } else {
    $customerFlow = $input['customer_flow'];
    $isReturningCustomer = $customerFlow === 'returning';
    $verifiedCustomerName = $input['verified_customer_name'];

    $input['full_name'] = m360_pr02b_compose_full_name($input['first_name'], $input['last_name']);
    if ($input['full_name'] === '' && $input['first_name'] === '' && $input['last_name'] === '') {
        $input['full_name'] = trim((string)($_POST['full_name'] ?? ''));
    }
    if (!$isReturningCustomer && ($input['first_name'] === '' || $input['last_name'] === '')) {
        $result = ['ok' => false, 'message' => 'لطفاً نام و نام خانوادگی را وارد کنید.'];
    } elseif ($isReturningCustomer && $input['full_name'] === '' && $verifiedCustomerName !== '') {
        $input['full_name'] = $verifiedCustomerName;
        $parts = preg_split('/\s+/u', $verifiedCustomerName, 2) ?: [];
        $input['first_name'] = (string)($parts[0] ?? '');
        $input['last_name'] = (string)($parts[1] ?? '');
    } elseif ($isReturningCustomer && $input['full_name'] === '') {
        $input['full_name'] = 'مشتری گرامی';
    } else {

    $birthYearSelected = trim((string)($_POST['birth_year_jalali'] ?? ''));
    $birthMonthSelected = trim((string)($_POST['birth_month_jalali'] ?? ''));
    $birthDaySelected = trim((string)($_POST['birth_day_jalali'] ?? ''));
    if ($birthYearSelected !== '' && $birthMonthSelected !== '' && $birthDaySelected !== '') {
        $input['birth_date'] = sprintf(
            '%s/%02d/%02d',
            $birthYearSelected,
            (int)$birthMonthSelected,
            (int)$birthDaySelected
        );
    }

    $visitCheck = m360_rw_calendar_validate_visit_date($input['visit_date']);
    if (!$visitCheck['ok']) {
        $result = ['ok' => false, 'message' => $visitCheck['error']];
    } else {

    if ($input['plate_left_2_digits'] === '' && $input['plate_first_digit_1'] !== '' && $input['plate_first_digit_2'] !== '') {
        $input['plate_left_2_digits'] = $input['plate_first_digit_1'] . $input['plate_first_digit_2'];
    }
    if ($input['plate_middle_3_digits'] === '' && $input['plate_middle_digit_1'] !== '' && $input['plate_middle_digit_2'] !== '' && $input['plate_middle_digit_3'] !== '') {
        $input['plate_middle_3_digits'] = $input['plate_middle_digit_1'] . $input['plate_middle_digit_2'] . $input['plate_middle_digit_3'];
    }
    if ($input['plate_region_2_digits'] === '' && $input['plate_region_digit_1'] !== '' && $input['plate_region_digit_2'] !== '') {
        $input['plate_region_2_digits'] = $input['plate_region_digit_1'] . $input['plate_region_digit_2'];
    }

    if ($input['plate_display'] === '') {
        $l = $input['plate_left_2_digits'];
        $letter = $input['plate_letter'];
        $mid = $input['plate_middle_3_digits'];
        $region = $input['plate_region_2_digits'];
        if ($l !== '' && $letter !== '' && $mid !== '' && $region !== '') {
            $input['plate_display'] = $l . ' ' . $letter . ' ' . $mid . ' ایران ' . $region;
        }
    }

    if ($input['residence_address'] === '' && $input['address'] !== '') {
        $input['residence_address'] = $input['address'];
    }

    $payload = [
        'customer_name' => $input['full_name'],
        'full_name' => $input['full_name'],
        'first_name' => $input['first_name'],
        'last_name' => $input['last_name'],
        'mobile' => $input['mobile'],
        'national_id' => $input['national_id'],
        'second_phone' => $input['second_phone'],
        'residence_address' => $input['residence_address'],
        'vehicle_delivery_address' => $input['vehicle_delivery_address'],
        'authorized_receiver_name' => $input['authorized_receiver_name'],
        'authorized_receiver_phone' => $input['authorized_receiver_phone'],
        'selected_vehicle_id' => (int)$input['selected_vehicle_id'],
        'vehicle_mode' => $input['vehicle_mode'],
        'province' => $input['province'],
        'city' => $input['city'],
        'vehicle_brand' => $input['vehicle_brand'],
        'brand' => $input['vehicle_brand'],
        'vehicle_class' => $input['vehicle_class'],
        'vehicle_model' => $input['vehicle_class'],
        'model' => $input['vehicle_class'],
        'vehicle_year_pair' => $input['vehicle_year_pair'],
        'plate_left_2_digits' => $input['plate_left_2_digits'],
        'plate_letter' => $input['plate_letter'],
        'plate_middle_3_digits' => $input['plate_middle_3_digits'],
        'plate_region_2_digits' => $input['plate_region_2_digits'],
        'plate_display' => $input['plate_display'],
        'plate_first_digit_1' => $input['plate_first_digit_1'] ?? '',
        'plate_first_digit_2' => $input['plate_first_digit_2'] ?? '',
        'plate_middle_digit_1' => $input['plate_middle_digit_1'] ?? '',
        'plate_middle_digit_2' => $input['plate_middle_digit_2'] ?? '',
        'plate_middle_digit_3' => $input['plate_middle_digit_3'] ?? '',
        'plate_region_digit_1' => $input['plate_region_digit_1'] ?? '',
        'plate_region_digit_2' => $input['plate_region_digit_2'] ?? '',
        'plate_number' => $input['plate_display'],
        'vehicle_plate' => $input['plate_display'],
        'plate_parts' => [
            'left_2' => $input['plate_left_2_digits'],
            'letter' => $input['plate_letter'],
            'middle_3' => $input['plate_middle_3_digits'],
            'region_2' => $input['plate_region_2_digits'],
            'first_digit_1' => $input['plate_first_digit_1'] ?? '',
            'first_digit_2' => $input['plate_first_digit_2'] ?? '',
            'middle_digit_1' => $input['plate_middle_digit_1'] ?? '',
            'middle_digit_2' => $input['plate_middle_digit_2'] ?? '',
            'middle_digit_3' => $input['plate_middle_digit_3'] ?? '',
            'region_digit_1' => $input['plate_region_digit_1'] ?? '',
            'region_digit_2' => $input['plate_region_digit_2'] ?? '',
        ],
        'vin' => $input['vin'],
        'odometer_km' => $input['odometer_km'],
        'request_type' => $input['request_type'],
        'visit_date' => $input['visit_date'],
        'request_description' => $input['request_description'],
        'service_description' => $input['request_description'],
        'address' => $input['address'] !== '' ? $input['address'] : $input['postal_address'],
        'postal_address' => $input['postal_address'],
        'extra_contact_info' => $input['extra_contact_info'],
        'job_title' => $input['job_title'],
        'birth_date' => $input['birth_date'],
        'source' => 'moghareh360.ir',
        'source_channel' => M360_ONLINE_REQ_SOURCE_PUBLIC,
        'otp_verified_token' => m360_otp_verified_token(),
        'customer_flow' => $customerFlow,
        'verified_customer_name' => $verifiedCustomerName,
        'contract_ack_status' => 'pending_pr02c',
    ];

    $result = m360_customer_online_submit_from_post($payload);
    if (!empty($result['ok'])) {
        header('Location: ' . m360_rw_customer_portal_app_root_url('/customer-profile.php?request_created=1'), true, 303);
        exit;
    } else {
        $result = [
            'ok' => false,
            'message' => (string)($result['message'] ?? 'ثبت درخواست ناموفق بود.'),
            'error_code' => (string)($result['error_code'] ?? 'submit_failed'),
            'step' => (string)($result['step'] ?? 'm360_section_submit'),
        ];
    }
    }
    }
    }
}

$mobileVerifiedSession = $input['mobile'] !== '' && m360_otp_is_verified($input['mobile']);
$showSubmitSuccess = $submitSuccess && $createdRequestId > 0;
$showSubmitError = $result !== null && empty($result['ok']);
$submitErrorMessage = $showSubmitError ? (string)($result['message'] ?? '') : '';
$submitErrorMeta = $submitErrorMessage !== ''
    ? m360_pr02b_submit_error_meta(
        $submitErrorMessage,
        $mobileVerifiedSession,
        $showSubmitError ? (string)($result['error_code'] ?? '') : ''
    )
    : ['step' => 'm360_step_mobile', 'is_otp_error' => false];
$showTopSubmitAlert = $showSubmitError && (!$mobileVerifiedSession || !empty($submitErrorMeta['is_otp_error']));

if ($input['birth_date'] !== '' && preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $input['birth_date'], $birthParts)) {
    $birthYearSelected = $birthParts[1];
    $birthMonthSelected = (string)(int)$birthParts[2];
    $birthDaySelected = (string)(int)$birthParts[3];
}

$visitDateDisplay = '';
if ($input['visit_date'] !== '') {
    foreach ($visitCalendarDays as $day) {
        if ($day['gregorian'] === $input['visit_date']) {
            $visitDateDisplay = $day['label'];
            break;
        }
    }
    if ($visitDateDisplay === '') {
        $visitDateDisplay = $input['visit_date'];
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' && !empty($verifiedSession['ok']) && !$isExplicitNewRequest) {
    header('Location: ' . m360_rw_customer_portal_app_root_url('/customer-profile.php'), true, 302);
    exit;
}

mirror_render_head('ثبت درخواست مشتری', 'customer');
?>
<section class="m360-hero m360-hero--luxury">
    <h2>ثبت درخواست آنلاین</h2>
    <p>در چند قدم ساده، درخواست خود را ثبت کنید. ابتدا شماره موبایل را تأیید می‌کنید؛ سپس فرم مناسب شما نمایش داده می‌شود.</p>
</section>

<?php if ($showTopSubmitAlert): ?>
    <div class="m360-alert m360-alert-error" id="m360_top_submit_alert">
        <?= mirror_h($submitErrorMessage !== '' ? $submitErrorMessage : 'ثبت درخواست ناموفق بود. لطفاً دوباره تلاش کنید.') ?>
    </div>
<?php endif; ?>

<?php if ($showSubmitSuccess): ?>
<section class="m360-card m360-form m360-customer-success-panel" id="m360_customer_success_panel">
    <div class="m360-step-card m360-step-card--success m360-step-card--active">
        <div class="m360-step-header">
            <span class="m360-step-badge" aria-hidden="true">✓</span>
            <div class="m360-step-header__text">
                <h3 class="m360-step-title">درخواست شما ثبت شد</h3>
                <p class="m360-step-sub">پس از بررسی، با شما تماس گرفته می‌شود.</p>
            </div>
        </div>
        <p class="m360-success-tracking">شماره پیگیری درخواست آنلاین: <strong id="m360_created_request_id"><?= mirror_h((string)$createdRequestId) ?></strong></p>
        <p class="m360-muted">لطفاً این شماره را یادداشت کنید.</p>
    </div>
</section>
<?php else: ?>

<!-- PR-02B-ACTIVE: step-wizard-direct-submit -->
<section class="m360-card m360-form">
    <form method="post" action="customer-request.php<?= $isExplicitNewRequest ? '?mode=new' : '' ?>" class="m360-customer-form" novalidate>
        <input type="hidden" id="customer_flow" name="customer_flow" value="<?= mirror_h((string)($input['customer_flow'] ?? 'new')) ?>">
        <input type="hidden" id="verified_customer_name" name="verified_customer_name" value="<?= mirror_h((string)($input['verified_customer_name'] ?? '')) ?>">
        <input type="hidden" id="mobile_verified" name="mobile_verified" value="<?= $mobileVerifiedSession ? '1' : '0' ?>">
        <input type="hidden" id="selected_vehicle_id" name="selected_vehicle_id" value="<?= mirror_h((string)($input['selected_vehicle_id'] ?? '')) ?>">
        <input type="hidden" id="vehicle_mode" name="vehicle_mode" value="<?= mirror_h((string)($input['vehicle_mode'] ?? 'new')) ?>">

        <nav class="m360-customer-wizard-progress" id="m360_wizard_progress" aria-label="پیشرفت مراحل" hidden>
            <ol class="m360-customer-wizard-progress__list">
                <li data-step="m360_step_mobile">۱. موبایل</li>
                <li data-step="m360_section_profile">۲. پروفایل</li>
                <li data-step="m360_section_vehicle">۳. خودرو</li>
                <li data-step="m360_section_request">۴. درخواست</li>
                <li data-step="m360_section_visit">۵. مراجعه</li>
                <li data-step="m360_section_contract">۶. قرارداد</li>
                <li data-step="m360_section_submit">۷. ثبت</li>
            </ol>
        </nav>

        <section id="m360_step_mobile" class="m360-step-card m360-otp-panel m360-step-card--active" aria-labelledby="m360_step_mobile_title">
            <div class="m360-step-header">
                <span class="m360-step-badge" aria-hidden="true">۱</span>
                <div class="m360-step-header__text">
                    <h3 id="m360_step_mobile_title" class="m360-step-title">ورود شماره موبایل</h3>
                    <p class="m360-step-sub">برای شروع، شماره موبایل خود را وارد کنید تا کد تأیید ارسال شود.</p>
                </div>
            </div>
            <?php if (m360_otp_can_use_dev_code()): ?>
                <p class="m360-otp-status m360-state-success" role="note">حالت تست لوکال فعال است. کد تأیید: <?= mirror_h(m360_otp_get_dev_code()) ?></p>
            <?php endif; ?>
            <label for="mobile">شماره موبایل <span class="m360-req">*</span></label>
            <div class="m360-mobile-row">
                <input type="tel" id="mobile" name="mobile" inputmode="tel" maxlength="11" required value="<?= mirror_h($input['mobile']) ?>" placeholder="09xxxxxxxxx" autocomplete="tel" title="شماره موبایل ۱۱ رقمی با 09 شروع شود">
                <button type="button" id="m360_send_otp" class="m360-btn m360-btn-secondary m360-luxury-action">ارسال کد تأیید</button>
            </div>
            <p id="m360_mobile_status" class="m360-otp-status" role="status" aria-live="polite"></p>
        </section>

        <section id="m360_step_otp" class="m360-step-card m360-otp-panel m360-step--hidden" aria-labelledby="m360_step_otp_title">
            <div class="m360-step-header">
                <span class="m360-step-badge" aria-hidden="true">۲</span>
                <div class="m360-step-header__text">
                    <h3 id="m360_step_otp_title" class="m360-step-title">تأیید کد پیامکی</h3>
                    <p class="m360-step-sub">کد ۶ رقمی ارسال‌شده را وارد کنید.</p>
                </div>
            </div>
            <div class="m360-otp-verify-row">
                <div class="m360-otp-verify-col">
                    <label for="m360_otp_code" class="m360-sub-label">کد ۶ رقمی</label>
                    <input type="text" id="m360_otp_code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" placeholder="۶ رقم" autocomplete="one-time-code" title="کد ۶ رقمی پیامک">
                </div>
                <button type="button" id="m360_verify_otp" class="m360-btn m360-btn-secondary m360-luxury-action">تأیید شماره موبایل</button>
            </div>
            <div class="m360-otp-actions">
                <button type="button" id="m360_resend_otp" class="m360-btn-link" disabled>ارسال مجدد کد</button>
                <span id="m360_resend_timer" class="m360-resend-timer"></span>
            </div>
            <p id="m360_otp_status" class="m360-otp-status" role="status" aria-live="polite"></p>
        </section>

        <section id="m360_step_welcome" class="m360-step-card m360-step--hidden" aria-hidden="true" hidden></section>

        <section id="m360_section_profile" class="m360-step-card m360-profile-panel m360-step--hidden" aria-labelledby="m360_profile_title">
        <p id="m360_profile_step_error" class="m360-step-error m360-step--hidden" role="alert" aria-live="polite"></p>
        <div class="m360-step-header">
            <span class="m360-step-badge" aria-hidden="true">۲</span>
            <div class="m360-step-header__text">
                <h3 id="m360_profile_title" class="m360-section-title">اطلاعات مشتری</h3>
                <p class="m360-step-sub">پس از تأیید موبایل، پروفایل خود را تکمیل یا بازبینی کنید.</p>
            </div>
        </div>
        <label for="first_name">نام <span class="m360-req">*</span></label>
        <input type="text" id="first_name" name="first_name" maxlength="60" data-required-new="1" value="<?= mirror_h($input['first_name']) ?>">
        <label for="last_name">نام خانوادگی <span class="m360-req">*</span></label>
        <input type="text" id="last_name" name="last_name" maxlength="60" data-required-new="1" value="<?= mirror_h($input['last_name']) ?>">
        <input type="hidden" id="full_name" name="full_name" value="<?= mirror_h($input['full_name']) ?>">
        <label for="profile_primary_mobile">شماره موبایل (تأییدشده)</label>
        <input type="tel" id="profile_primary_mobile" readonly value="<?= mirror_h($input['mobile']) ?>" class="m360-readonly-field">
        <label for="national_id">کد ملی</label>
        <input type="text" id="national_id" name="national_id" maxlength="10" inputmode="numeric" value="<?= mirror_h($input['national_id']) ?>">
        <label for="second_phone">شماره تماس دوم</label>
        <input type="tel" id="second_phone" name="second_phone" inputmode="tel" maxlength="11" value="<?= mirror_h($input['second_phone']) ?>">
        <label for="residence_address">آدرس محل سکونت</label>
        <input type="text" id="residence_address" name="residence_address" maxlength="300" value="<?= mirror_h($input['residence_address']) ?>">
        <label for="vehicle_delivery_address">آدرس تحویل خودرو</label>
        <input type="text" id="vehicle_delivery_address" name="vehicle_delivery_address" maxlength="300" value="<?= mirror_h($input['vehicle_delivery_address']) ?>">
        <label for="authorized_receiver_name">نام تحویل‌گیرنده مجاز</label>
        <input type="text" id="authorized_receiver_name" name="authorized_receiver_name" maxlength="120" value="<?= mirror_h($input['authorized_receiver_name']) ?>">
        <label for="authorized_receiver_phone">شماره تحویل‌گیرنده مجاز</label>
        <input type="tel" id="authorized_receiver_phone" name="authorized_receiver_phone" inputmode="tel" maxlength="11" value="<?= mirror_h($input['authorized_receiver_phone']) ?>">

        <label for="province">استان</label>
        <select id="province" name="province">
            <option value="">انتخاب استان</option>
            <?php if ($input['province'] !== ''): ?>
                <option value="<?= mirror_h($input['province']) ?>" selected><?= mirror_h($input['province']) ?></option>
            <?php endif; ?>
        </select>

        <label for="city">شهر</label>
        <select id="city" name="city" <?= $input['city'] === '' ? 'disabled' : '' ?>>
            <option value="">انتخاب شهر</option>
            <?php if ($input['city'] !== ''): ?>
                <option value="<?= mirror_h($input['city']) ?>" selected><?= mirror_h($input['city']) ?></option>
            <?php endif; ?>
        </select>
        <div class="m360-wizard-nav">
            <button type="button" class="m360-btn m360-btn-secondary m360-wizard-prev" data-target="m360_step_otp">قبلی</button>
            <button type="button" class="m360-btn m360-luxury-action m360-wizard-next" data-target="m360_section_vehicle">مرحله بعد — خودرو</button>
        </div>
        </section>

        <section id="m360_section_vehicle" class="m360-step-card m360-profile-panel m360-step--hidden" aria-labelledby="m360_vehicle_title">
        <p id="m360_vehicle_step_error" class="m360-step-error m360-step--hidden" role="alert" aria-live="polite"></p>
        <div class="m360-step-header">
            <span class="m360-step-badge" aria-hidden="true">۳</span>
            <div class="m360-step-header__text">
                <h3 id="m360_vehicle_title" class="m360-section-title">انتخاب یا ثبت خودرو</h3>
                <p class="m360-step-sub">یکی از خودروهای قبلی را انتخاب کنید یا خودرو جدید اضافه کنید.</p>
            </div>
        </div>

        <div id="m360_vehicle_picker" class="m360-vehicle-picker" hidden>
            <p class="m360-muted">خودروهای تأییدشده شما:</p>
            <div id="m360_vehicle_picker_list" class="m360-vehicle-picker__list" role="radiogroup" aria-label="انتخاب خودرو"></div>
            <div id="m360_vehicle_out_of_scope" class="m360-vehicle-out-of-scope" hidden>
                <p class="m360-muted">خودروهای خارج از محدوده فعلی (قابل انتخاب برای ثبت آنلاین نیستند):</p>
                <ul id="m360_vehicle_out_of_scope_list" class="m360-vehicle-out-of-scope__list"></ul>
            </div>
            <button type="button" id="m360_vehicle_add_new" class="m360-btn-link">افزودن خودرو جدید (برند تأییدشده)</button>
        </div>

        <div id="m360_vehicle_new_fields">
        <label for="vehicle_brand">برند خودرو <span class="m360-req">*</span></label>
        <select id="vehicle_brand" name="vehicle_brand" data-required-both="1">
            <option value="">انتخاب برند</option>
            <?php if ($input['vehicle_brand'] !== ''): ?>
                <option value="<?= mirror_h($input['vehicle_brand']) ?>" selected><?= mirror_h($input['vehicle_brand']) ?></option>
            <?php endif; ?>
        </select>

        <label for="vehicle_class">کلاس / مدل خودرو <span class="m360-req">*</span></label>
        <select id="vehicle_class" name="vehicle_class" data-required-both="1" <?= $input['vehicle_class'] === '' ? 'disabled' : '' ?>>
            <option value="">انتخاب کلاس / مدل</option>
            <?php if ($input['vehicle_class'] !== ''): ?>
                <option value="<?= mirror_h($input['vehicle_class']) ?>" selected><?= mirror_h($input['vehicle_class']) ?></option>
            <?php endif; ?>
        </select>

        <label for="vehicle_year_pair">سال تولید <span class="m360-req">*</span></label>
        <select id="vehicle_year_pair" name="vehicle_year_pair" data-required-both="1">
            <option value="">انتخاب سال</option>
            <?php foreach ($vehicleYearOptions as $yearOpt): ?>
                <option value="<?= mirror_h($yearOpt['value']) ?>" <?= $input['vehicle_year_pair'] === $yearOpt['value'] ? 'selected' : '' ?>>
                    <?= mirror_h($yearOpt['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="iran-plate-field-label">پلاک خودرو <span class="m360-req">*</span></label>
        <p class="iran-plate-field-hint">از چپ به راست: دو رقم، حرف، سه رقم، سپس کد ایران</p>
        <div class="iran-plate-widget" aria-label="پلاک خودرو">
            <div class="iran-plate-ir-band" aria-hidden="true">
                <span class="iran-plate-ir-band__ir">IR</span>
                <span class="iran-plate-ir-band__flag" aria-hidden="true">🇮🇷</span>
            </div>
            <div class="iran-plate-body">
                <div class="iran-plate-group iran-plate-group--series" aria-label="دو رقم اول">
                    <span class="iran-plate-group__label">۲ رقم</span>
                    <div class="iran-plate-group__inputs">
                        <select class="plate-digit-select" id="plate_first_digit_1" name="plate_first_digit_1" data-required-both="1" aria-label="رقم اول پلاک">
                            <option value="">-</option>
                            <?php for ($d = 0; $d <= 9; $d++): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                        <select class="plate-digit-select" id="plate_first_digit_2" name="plate_first_digit_2" data-required-both="1" aria-label="رقم دوم پلاک">
                            <option value="">-</option>
                            <?php for ($d = 0; $d <= 9; $d++): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <span class="iran-plate-sep" aria-hidden="true"></span>
                <div class="iran-plate-group iran-plate-group--letter" aria-label="حرف پلاک">
                    <span class="iran-plate-group__label">حرف</span>
                    <select class="plate-letter-select" id="plate_letter" name="plate_letter" data-required-both="1" aria-label="حرف پلاک">
                        <option value="">حرف</option>
                        <?php foreach ($plateLetters as $letter): ?>
                            <option value="<?= mirror_h($letter) ?>" <?= $input['plate_letter'] === $letter ? 'selected' : '' ?>><?= mirror_h($letter) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <span class="iran-plate-sep" aria-hidden="true"></span>
                <div class="iran-plate-group iran-plate-group--middle" aria-label="سه رقم وسط">
                    <span class="iran-plate-group__label">۳ رقم</span>
                    <div class="iran-plate-group__inputs">
                        <select class="plate-digit-select" id="plate_middle_digit_1" name="plate_middle_digit_1" data-required-both="1" aria-label="رقم اول سه‌رقمی">
                            <option value="">-</option>
                            <?php for ($d = 0; $d <= 9; $d++): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                        <select class="plate-digit-select" id="plate_middle_digit_2" name="plate_middle_digit_2" data-required-both="1" aria-label="رقم دوم سه‌رقمی">
                            <option value="">-</option>
                            <?php for ($d = 0; $d <= 9; $d++): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                        <select class="plate-digit-select" id="plate_middle_digit_3" name="plate_middle_digit_3" data-required-both="1" aria-label="رقم سوم سه‌رقمی">
                            <option value="">-</option>
                            <?php for ($d = 0; $d <= 9; $d++): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="iran-plate-region-box" aria-label="کد ایران">
                <span class="iran-plate-region-box__label">ایران</span>
                <div class="iran-plate-region-box__digits">
                    <select class="plate-digit-select" id="plate_region_digit_1" name="plate_region_digit_1" data-required-both="1" aria-label="رقم اول کد ایران">
                        <option value="">-</option>
                        <?php for ($d = 0; $d <= 9; $d++): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endfor; ?>
                    </select>
                    <select class="plate-digit-select" id="plate_region_digit_2" name="plate_region_digit_2" data-required-both="1" aria-label="رقم دوم کد ایران">
                        <option value="">-</option>
                        <?php for ($d = 0; $d <= 9; $d++): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>
        <p id="plate_preview" class="iran-plate-preview" aria-live="polite">پس از تکمیل، پلاک اینجا نمایش داده می‌شود</p>
        <input type="hidden" id="plate_left_2_digits" name="plate_left_2_digits" value="<?= mirror_h($input['plate_left_2_digits']) ?>">
        <input type="hidden" id="plate_middle_3_digits" name="plate_middle_3_digits" value="<?= mirror_h($input['plate_middle_3_digits']) ?>">
        <input type="hidden" id="plate_region_2_digits" name="plate_region_2_digits" value="<?= mirror_h($input['plate_region_2_digits']) ?>">
        <input type="hidden" id="plate_display" name="plate_display" value="<?= mirror_h($input['plate_display']) ?>">

        <label for="vin">شماره شاسی (VIN)</label>
        <input type="text" id="vin" name="vin" maxlength="17" value="<?= mirror_h($input['vin']) ?>">

        <label for="odometer_km">کیلومتر خودرو</label>
        <input type="number" id="odometer_km" name="odometer_km" min="0" step="1" value="<?= mirror_h($input['odometer_km']) ?>">
        </div>
        <div class="m360-wizard-nav">
            <button type="button" class="m360-btn m360-btn-secondary m360-wizard-prev" data-target="m360_section_profile">قبلی</button>
            <button type="button" class="m360-btn m360-luxury-action m360-wizard-next" data-target="m360_section_request">مرحله بعد — درخواست</button>
        </div>
        </section>

        <section id="m360_section_request" class="m360-step-card m360-request-panel m360-step--hidden" aria-labelledby="m360_request_title">
        <p id="m360_request_step_error" class="m360-step-error m360-step--hidden" role="alert" aria-live="polite"></p>
        <div class="m360-step-header">
            <span class="m360-step-badge" aria-hidden="true">۴</span>
            <div class="m360-step-header__text">
                <h3 id="m360_request_title" class="m360-section-title">شرح درخواست</h3>
                <p class="m360-step-sub">نوع خدمت و علائم / نیاز خود را بنویسید.</p>
            </div>
        </div>

        <label for="request_type">نوع درخواست <span class="m360-req">*</span></label>
        <select id="request_type" name="request_type" data-required-both="1">
            <option value="">انتخاب کنید</option>
            <?php foreach ($requestTypes as $code => $label): ?>
                <option value="<?= mirror_h($code) ?>" <?= $input['request_type'] === $code ? 'selected' : '' ?>><?= mirror_h($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="request_description">شرح درخواست <span class="m360-req">*</span></label>
        <textarea id="request_description" name="request_description" data-required-both="1" maxlength="1500"><?= mirror_h($input['request_description']) ?></textarea>
        <div class="m360-wizard-nav">
            <button type="button" class="m360-btn m360-btn-secondary m360-wizard-prev" data-target="m360_section_vehicle">قبلی</button>
            <button type="button" class="m360-btn m360-luxury-action m360-wizard-next" data-target="m360_section_visit">مرحله بعد — تاریخ مراجعه</button>
        </div>
        </section>

        <section id="m360_section_visit" class="m360-step-card m360-request-panel m360-step--hidden" aria-labelledby="m360_visit_title">
        <p id="m360_visit_step_error" class="m360-step-error m360-step--hidden" role="alert" aria-live="polite"></p>
        <div class="m360-step-header">
            <span class="m360-step-badge" aria-hidden="true">۵</span>
            <div class="m360-step-header__text">
                <h3 id="m360_visit_title" class="m360-section-title">تاریخ مراجعه</h3>
                <p class="m360-step-sub">روز مراجعه را از تقویم کاری انتخاب کنید.</p>
            </div>
        </div>
        <div class="m360-date-field">
            <input
                type="text"
                id="visit_date_display"
                class="m360-date-display<?= $visitDateDisplay !== '' ? ' m360-date-display--filled' : '' ?>"
                readonly
                placeholder="روز مراجعه را از تقویم انتخاب کنید"
                value="<?= mirror_h($visitDateDisplay) ?>"
                aria-describedby="visit_date_hint"
            >
            <input type="hidden" id="visit_date" name="visit_date" value="<?= mirror_h($input['visit_date']) ?>">
            <p id="visit_date_hint" class="m360-jalali-datepicker__hint">انتخاب مراجعه در بازه ۳۰ روز آینده تقویم شمسی — فقط روزهای کاری (جمعه و تعطیلات رسمی غیرفعال)</p>
            <div class="m360-server-calendar" id="m360_server_calendar" role="group" aria-label="تقویم مراجعه">
                <?php foreach ($visitCalendarDays as $day): ?>
                    <?php m360_rw_calendar_render_day_button($day, $input['visit_date'], 'mirror_h'); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <p id="visit_time_hint" class="m360-visit-hint" style="display:none">ساعت حضور الزاما بین 8:30 الی 11:30 می‌باشد.</p>
        <div class="m360-wizard-nav">
            <button type="button" class="m360-btn m360-btn-secondary m360-wizard-prev" data-target="m360_section_request">قبلی</button>
            <button type="button" class="m360-btn m360-luxury-action m360-wizard-next" data-target="m360_section_contract">مرحله بعد — قرارداد</button>
        </div>
        </section>

        <section id="m360_section_contract" class="m360-step-card m360-request-panel m360-step--hidden" aria-labelledby="m360_contract_title">
        <div class="m360-step-header">
            <span class="m360-step-badge" aria-hidden="true">۶</span>
            <div class="m360-step-header__text">
                <h3 id="m360_contract_title" class="m360-section-title">قرارداد و تأیید نهایی</h3>
                <p class="m360-step-sub">قرارداد و تأیید نهایی در مرحله بعدی فعال می‌شود.</p>
            </div>
        </div>
        <p class="m360-contract-placeholder" role="status">قرارداد و تأیید نهایی در مرحله بعدی فعال می‌شود</p>
        <div class="m360-wizard-nav">
            <button type="button" class="m360-btn m360-btn-secondary m360-wizard-prev" data-target="m360_section_visit">قبلی</button>
            <button type="button" class="m360-btn m360-luxury-action m360-wizard-next" data-target="m360_section_submit">مرحله بعد — ثبت نهایی</button>
        </div>
        </section>

        <section id="m360_section_submit" class="m360-step-card m360-request-panel m360-step--hidden" aria-labelledby="m360_submit_title">
        <p id="m360_submit_step_error" class="m360-step-error m360-step--hidden" role="alert" aria-live="polite"></p>
        <div class="m360-step-header">
            <span class="m360-step-badge" aria-hidden="true">۷</span>
            <div class="m360-step-header__text">
                <h3 id="m360_submit_title" class="m360-section-title">ثبت و پیگیری</h3>
                <p class="m360-step-sub">پس از ثبت، شماره پیگیری درخواست آنلاین نمایش داده می‌شود.</p>
            </div>
        </div>
        <button type="submit" id="m360_submit_btn" class="m360-btn m360-luxury-action" disabled>ثبت درخواست</button>
        <div class="m360-wizard-nav">
            <button type="button" class="m360-btn m360-btn-secondary m360-wizard-prev" data-target="m360_section_contract">قبلی</button>
        </div>
        </section>
    </form>
</section>
<?php endif; ?>

<?php
$m360CustomerPageBoot = [
    'submitSuccess' => $showSubmitSuccess,
    'createdRequestId' => $createdRequestId,
    'submitError' => $submitErrorMessage,
    'submitErrorCode' => $showSubmitError ? (string)($result['error_code'] ?? '') : '',
    'submitErrorStep' => (string)($submitErrorMeta['step'] ?? ($result['step'] ?? 'm360_section_submit')),
    'submitErrorIsOtp' => !empty($submitErrorMeta['is_otp_error']),
    'mobileVerified' => $mobileVerifiedSession,
    'mobile' => $input['mobile'],
    'customerFlow' => (string)($input['customer_flow'] ?? 'new'),
    'verifiedCustomerName' => (string)($input['verified_customer_name'] ?? ''),
    'restoreForm' => !$showSubmitSuccess && ($mobileVerifiedSession || $showSubmitError),
    'pr02bActive' => true,
    'explicitNewRequest' => $isExplicitNewRequest,
    'verifiedSessionMobile' => !empty($verifiedSession['ok']) ? (string)$verifiedSession['mobile'] : '',
    'skipOtpToWizard' => !empty($verifiedSession['ok']) && $isExplicitNewRequest,
    'profileRedirectUrl' => m360_rw_customer_portal_app_root_url('/customer-profile.php'),
];
?>
<script>window.m360CustomerPageBoot=<?= json_encode($m360CustomerPageBoot, JSON_UNESCAPED_UNICODE) ?>;</script>
<!-- PR-02B-UAT-REPAIR-4: customer wizard only; header nav remains plain anchors -->
<script src="assets/js/iran-provinces-cities.js"></script>
<script src="assets/js/vehicle-brand-classes.js"></script>
<?php
$m360CustomerFormJs = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'customer-form.js';
$m360CustomerFormVer = is_file($m360CustomerFormJs) ? (string)filemtime($m360CustomerFormJs) : '1';
?>
<script src="assets/js/customer-form.js?v=<?= mirror_h($m360CustomerFormVer) ?>"></script>
<?php mirror_render_foot(); ?>
