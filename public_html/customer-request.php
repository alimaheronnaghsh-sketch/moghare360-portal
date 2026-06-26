<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';

m360_otp_session_start();

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

/** @var list<array{gregorian:string,jalali:string,label:string,weekday:string,day:int,month:string,is_today:bool}> */
$visitCalendarDays = [];
for ($offset = 0; $offset <= 30; $offset++) {
    $gDay = $todayGregorian->modify('+' . $offset . ' days');
    $jy = (int)$gDay->format('Y');
    $jm = (int)$gDay->format('n');
    $jd = (int)$gDay->format('j');
    $j = m360_gregorian_to_jalali($jy, $jm, $jd);
    $gregorianIso = $gDay->format('Y-m-d');
    $jalaliStr = sprintf('%d/%02d/%02d', $j['jy'], $j['jm'], $j['jd']);
    $weekday = m360_persian_weekday($gDay);
    $visitCalendarDays[] = [
        'gregorian' => $gregorianIso,
        'jalali' => $jalaliStr,
        'label' => $weekday . ' ' . $jalaliStr,
        'weekday' => $weekday,
        'day' => $j['jd'],
        'month' => $jalaliMonthNames[$j['jm'] - 1] ?? '',
        'is_today' => $offset === 0,
    ];
}

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
$input = [
    'full_name' => '',
    'mobile' => '',
    'national_id' => '',
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

    if (!m360_otp_is_verified($input['mobile'])) {
        $result = [
            'ok' => false,
            'message' => 'برای ثبت درخواست، ابتدا شماره موبایل خود را با کد پیامکی تأیید کنید.',
        ];
    } elseif (!in_array($input['plate_letter'], $plateLetters, true)) {
        $result = [
            'ok' => false,
            'message' => 'لطفاً حرف پلاک را انتخاب کنید.',
        ];
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

    $allowedVisitDates = array_column($visitCalendarDays, 'gregorian');
    if ($input['visit_date'] !== '' && !in_array($input['visit_date'], $allowedVisitDates, true)) {
        $input['visit_date'] = '';
    }

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

    $payload = [
        'customer_name' => $input['full_name'],
        'full_name' => $input['full_name'],
        'mobile' => $input['mobile'],
        'national_id' => $input['national_id'],
        'province' => $input['province'],
        'city' => $input['city'],
        'vehicle_brand' => $input['vehicle_brand'],
        'brand' => $input['vehicle_brand'],
        'vehicle_class' => $input['vehicle_class'],
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
        'source_channel' => 'PUBLIC_WEB',
        'otp_verified_token' => m360_otp_verified_token(),
    ];

    $result = mirror_api_customer_request($payload);
    }
}

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

mirror_render_head('ثبت درخواست مشتری', 'customer');
?>
<section class="m360-hero">
    <h2>ثبت درخواست آنلاین</h2>
    <p>فرم زیر را تکمیل کنید تا همکاران ما در اسرع وقت با شما تماس بگیرند.</p>
</section>

<?php if ($result !== null): ?>
    <div class="m360-alert <?= ($result['ok'] ?? false) ? 'm360-alert-info' : 'm360-alert-error' ?>">
        <?php if ($result['ok'] ?? false): ?>
            <strong>ثبت موفق.</strong> درخواست شما ثبت شد و پس از بررسی با شما تماس گرفته می‌شود.
        <?php else: ?>
            <?= mirror_h((string)($result['message'] ?? 'ثبت درخواست ناموفق بود. لطفاً دوباره تلاش کنید.')) ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<section class="m360-card m360-form">
    <form method="post" action="customer-request.php" class="m360-customer-form">
        <h3>اطلاعات مشتری</h3>
        <label for="full_name">نام و نام خانوادگی <span class="m360-req">*</span></label>
        <input type="text" id="full_name" name="full_name" maxlength="100" required value="<?= mirror_h($input['full_name']) ?>">

        <label for="mobile">موبایل <span class="m360-req">*</span></label>
        <div class="m360-mobile-row">
            <input type="tel" id="mobile" name="mobile" inputmode="tel" maxlength="11" required value="<?= mirror_h($input['mobile']) ?>" placeholder="09xxxxxxxxx" autocomplete="tel">
            <button type="button" id="m360_send_otp" class="m360-btn m360-btn-secondary">ارسال کد تأیید</button>
        </div>
        <div class="m360-otp-verify-row">
            <div class="m360-otp-verify-col">
                <label for="m360_otp_code" class="m360-sub-label">کد پیامکی</label>
                <input type="text" id="m360_otp_code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" placeholder="۶ رقم" autocomplete="one-time-code">
            </div>
            <button type="button" id="m360_verify_otp" class="m360-btn m360-btn-secondary">تأیید شماره موبایل</button>
        </div>
        <p id="m360_otp_status" class="m360-otp-status" role="status" aria-live="polite"></p>
        <input type="hidden" id="mobile_verified" name="mobile_verified" value="0">

        <label for="national_id">کد ملی</label>
        <input type="text" id="national_id" name="national_id" maxlength="10" inputmode="numeric" value="<?= mirror_h($input['national_id']) ?>">

        <label for="province">استان <span class="m360-req">*</span></label>
        <select id="province" name="province" required>
            <option value="">انتخاب استان</option>
            <?php if ($input['province'] !== ''): ?>
                <option value="<?= mirror_h($input['province']) ?>" selected><?= mirror_h($input['province']) ?></option>
            <?php endif; ?>
        </select>

        <label for="city">شهر <span class="m360-req">*</span></label>
        <select id="city" name="city" required <?= $input['city'] === '' ? 'disabled' : '' ?>>
            <option value="">انتخاب شهر</option>
            <?php if ($input['city'] !== ''): ?>
                <option value="<?= mirror_h($input['city']) ?>" selected><?= mirror_h($input['city']) ?></option>
            <?php endif; ?>
        </select>

        <label for="address">آدرس</label>
        <input type="text" id="address" name="address" maxlength="200" value="<?= mirror_h($input['address']) ?>">

        <label for="postal_address">آدرس پستی</label>
        <input type="text" id="postal_address" name="postal_address" maxlength="200" value="<?= mirror_h($input['postal_address']) ?>">

        <label for="extra_contact_info">اطلاعات تماس تکمیلی</label>
        <textarea id="extra_contact_info" name="extra_contact_info" maxlength="500"><?= mirror_h($input['extra_contact_info']) ?></textarea>

        <label for="job_title">شغل</label>
        <input type="text" id="job_title" name="job_title" maxlength="100" value="<?= mirror_h($input['job_title']) ?>">

        <label>تاریخ تولد</label>
        <div class="m360-birthdate-row">
            <div class="m360-birthdate-col">
                <label for="birth_year_jalali" class="m360-sub-label">سال</label>
                <select id="birth_year_jalali" name="birth_year_jalali">
                    <option value="">انتخاب سال</option>
                    <?php for ($y = $currentJalaliYear; $y >= 1310; $y--): ?>
                        <option value="<?= $y ?>" <?= $birthYearSelected === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="m360-birthdate-col">
                <label for="birth_month_jalali" class="m360-sub-label">ماه</label>
                <select id="birth_month_jalali" name="birth_month_jalali">
                    <option value="">انتخاب ماه</option>
                    <?php foreach ($jalaliMonthNames as $mi => $monthName): ?>
                        <option value="<?= $mi + 1 ?>" <?= $birthMonthSelected === (string)($mi + 1) ? 'selected' : '' ?>><?= mirror_h($monthName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="m360-birthdate-col">
                <label for="birth_day_jalali" class="m360-sub-label">روز</label>
                <select id="birth_day_jalali" name="birth_day_jalali">
                    <option value="">انتخاب روز</option>
                    <?php for ($d = 1; $d <= 31; $d++): ?>
                        <option value="<?= $d ?>" <?= $birthDaySelected === (string)$d ? 'selected' : '' ?>><?= $d ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <input type="hidden" id="birth_date" name="birth_date" value="<?= mirror_h($input['birth_date']) ?>">

        <h3 class="m360-section-title">اطلاعات خودرو</h3>

        <label for="vehicle_brand">برند خودرو <span class="m360-req">*</span></label>
        <select id="vehicle_brand" name="vehicle_brand" required>
            <option value="">انتخاب برند</option>
            <?php if ($input['vehicle_brand'] !== ''): ?>
                <option value="<?= mirror_h($input['vehicle_brand']) ?>" selected><?= mirror_h($input['vehicle_brand']) ?></option>
            <?php endif; ?>
        </select>

        <label for="vehicle_class">کلاس / مدل خودرو <span class="m360-req">*</span></label>
        <select id="vehicle_class" name="vehicle_class" required <?= $input['vehicle_class'] === '' ? 'disabled' : '' ?>>
            <option value="">انتخاب کلاس / مدل</option>
            <?php if ($input['vehicle_class'] !== ''): ?>
                <option value="<?= mirror_h($input['vehicle_class']) ?>" selected><?= mirror_h($input['vehicle_class']) ?></option>
            <?php endif; ?>
        </select>

        <label for="vehicle_year_pair">سال تولید <span class="m360-req">*</span></label>
        <select id="vehicle_year_pair" name="vehicle_year_pair" required>
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
                        <select class="plate-digit-select" id="plate_first_digit_1" name="plate_first_digit_1" required aria-label="رقم اول پلاک">
                            <option value="">-</option>
                            <?php for ($d = 0; $d <= 9; $d++): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                        <select class="plate-digit-select" id="plate_first_digit_2" name="plate_first_digit_2" required aria-label="رقم دوم پلاک">
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
                    <select class="plate-letter-select" id="plate_letter" name="plate_letter" required aria-label="حرف پلاک">
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
                        <select class="plate-digit-select" id="plate_middle_digit_1" name="plate_middle_digit_1" required aria-label="رقم اول سه‌رقمی">
                            <option value="">-</option>
                            <?php for ($d = 0; $d <= 9; $d++): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                        <select class="plate-digit-select" id="plate_middle_digit_2" name="plate_middle_digit_2" required aria-label="رقم دوم سه‌رقمی">
                            <option value="">-</option>
                            <?php for ($d = 0; $d <= 9; $d++): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                        <select class="plate-digit-select" id="plate_middle_digit_3" name="plate_middle_digit_3" required aria-label="رقم سوم سه‌رقمی">
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
                    <select class="plate-digit-select" id="plate_region_digit_1" name="plate_region_digit_1" required aria-label="رقم اول کد ایران">
                        <option value="">-</option>
                        <?php for ($d = 0; $d <= 9; $d++): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endfor; ?>
                    </select>
                    <select class="plate-digit-select" id="plate_region_digit_2" name="plate_region_digit_2" required aria-label="رقم دوم کد ایران">
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

        <h3 class="m360-section-title">درخواست</h3>

        <label for="request_type">نوع درخواست <span class="m360-req">*</span></label>
        <select id="request_type" name="request_type" required>
            <option value="">انتخاب کنید</option>
            <?php foreach ($requestTypes as $code => $label): ?>
                <option value="<?= mirror_h($code) ?>" <?= $input['request_type'] === $code ? 'selected' : '' ?>><?= mirror_h($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="visit_date_display">تاریخ مراجعه <span class="m360-req">*</span></label>
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
            <input type="hidden" id="visit_date" name="visit_date" required value="<?= mirror_h($input['visit_date']) ?>">
            <p id="visit_date_hint" class="m360-jalali-datepicker__hint">انتخاب مراجعه فقط از امروز تا ۳۰ روز آینده امکان‌پذیر است</p>
            <div class="m360-server-calendar" id="m360_server_calendar" role="group" aria-label="تقویم مراجعه">
                <?php foreach ($visitCalendarDays as $day): ?>
                    <?php
                    $btnClass = 'm360-calendar-day';
                    if ($day['is_today']) {
                        $btnClass .= ' m360-calendar-day--today';
                    }
                    if ($input['visit_date'] !== '' && $input['visit_date'] === $day['gregorian']) {
                        $btnClass .= ' m360-calendar-day--selected';
                    }
                    ?>
                    <button
                        type="button"
                        class="<?= mirror_h($btnClass) ?>"
                        data-gregorian="<?= mirror_h($day['gregorian']) ?>"
                        data-jalali="<?= mirror_h($day['jalali']) ?>"
                        data-label="<?= mirror_h($day['label']) ?>"
                    >
                        <span class="m360-calendar-day__weekday"><?= mirror_h($day['weekday']) ?></span>
                        <strong class="m360-calendar-day__num"><?= (int)$day['day'] ?></strong>
                        <small class="m360-calendar-day__month"><?= mirror_h($day['month']) ?></small>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <p id="visit_time_hint" class="m360-visit-hint" style="display:none">ساعت حضور الزاما بین 8:30 الی 11:30 می‌باشد.</p>

        <label for="request_description">شرح درخواست <span class="m360-req">*</span></label>
        <textarea id="request_description" name="request_description" required maxlength="1500"><?= mirror_h($input['request_description']) ?></textarea>

        <button type="submit" id="m360_submit_btn" class="m360-btn" disabled>ثبت درخواست</button>
    </form>
</section>

<script src="assets/js/iran-provinces-cities.js"></script>
<script src="assets/js/vehicle-brand-classes.js"></script>
<script src="assets/js/customer-form.js"></script>
<?php mirror_render_foot(); ?>
