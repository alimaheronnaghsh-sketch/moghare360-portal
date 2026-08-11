<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-canonical-host-helper.php';
m360_canonical_local_host_enforce();

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-staff-walkin-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-intake-actor-matrix.php';

if (!function_exists('m360_walkin_gregorian_to_jalali')) {
    /**
     * @return array{jy:int,jm:int,jd:int}
     */
    function m360_walkin_gregorian_to_jalali(int $gy, int $gm, int $gd): array
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
}

$conn = customer_core_db();
$dbOk = is_resource($conn);
$actor = $dbOk ? m360_walkin_require_actor($conn) : ['ok' => false, 'message' => 'اتصال به پایگاه داده برقرار نشد.', 'status' => 500];
if (!$dbOk || empty($actor['ok'])) {
    if ((int)($actor['status'] ?? 500) === 401) {
        header('Location: staff-login.php');
        exit;
    }
    http_response_code((int)($actor['status'] ?? 500));
}

$flash = m360_walkin_consume_flash();
$csrfInputHtml = $dbOk && !empty($actor['ok']) ? m360_reception_csrf_input_html() : '';
$idempotencyKey = $dbOk && !empty($actor['ok']) ? m360_walkin_idempotency_key_from_session() : '';
$serviceTypes = m360_rw_customer_request_type_labels();
$fuelLevels = m360_rw_intake_fuel_levels();
$plateLetters = ['ب', 'ج', 'د', 'س', 'ص', 'ط', 'ق', 'ل', 'م', 'ن', 'و', 'ه', 'ی', 'ع', 'پ', 'ت', 'ک', 'گ'];
$tehranTz = new DateTimeZone('Asia/Tehran');
$todayGregorian = new DateTimeImmutable('today', $tehranTz);
$todayGy = (int)$todayGregorian->format('Y');
$vehicleYearOptions = [];
for ($i = 0; $i <= 20; $i++) {
    $gy = $todayGy - $i;
    $j = m360_walkin_gregorian_to_jalali($gy, 6, 15);
    $jy = $j['jy'];
    $vehicleYearOptions[] = [
        'value' => $jy . ' - ' . $gy,
        'label' => $jy . ' شمسی / ' . $gy . ' میلادی',
    ];
}
$visitCalendarDays = function_exists('m360_rw_calendar_next_30_day_window') ? m360_rw_calendar_next_30_day_window() : [];
$defaultVisitDate = $todayGregorian->format('Y-m-d');
$defaultVisitDisplay = '';
foreach ($visitCalendarDays as $day) {
    if (($day['gregorian'] ?? '') === $defaultVisitDate) {
        $defaultVisitDisplay = (string)($day['label'] ?? $defaultVisitDate);
        break;
    }
}
if ($defaultVisitDisplay === '') {
    $defaultVisitDisplay = $defaultVisitDate;
}
$walkinResumeRequestId = max(0, (int)($_GET['online_request_id'] ?? 0));
$m360CustomerPageBoot = [
    'staffWalkinMode' => true,
    'pr02bActive' => true,
    'mobileVerified' => true,
    'restoreForm' => true,
    'submitSuccess' => false,
    'walkinResumeRequestId' => $walkinResumeRequestId,
    'walkinStage56LockedMessage' => 'ابتدا اطلاعات مشتری، خودرو و خدمات را تکمیل و پرونده را ایجاد کنید.',
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>پذیرش حضوری — ماهین 360°</title>
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
</head>
<body class="m360-public-shell m360-rw-page m360-walkin-canonical-page">
<div class="m360-wrap m360-rw-wrap">
    <header class="m360-rw-header">
        <div class="m360-rw-header__top">
            <a class="m360-rw-back" href="erp-reception-board.php">بازگشت به مرکز ارتباط با مشتریان</a>
        </div>
        <h1 class="m360-rw-title">پذیرش حضوری</h1>
        <p class="m360-rw-subtitle">اطلاعات مشتری و خودرو را ثبت یا انتخاب کنید.</p>
    </header>

    <?php if (!$dbOk || empty($actor['ok'])): ?>
        <section class="m360-rw-alert"><?= m360_rw_h((string)($actor['message'] ?? 'دسترسی مجاز نیست.')) ?></section>
    <?php else: ?>
        <?php if ($flash !== []): ?>
            <section class="<?= !empty($flash['ok']) ? 'm360-rw-flash is-ok' : 'm360-rw-alert' ?>">
                <?= m360_rw_h((string)($flash['message'] ?? '')) ?>
            </section>
        <?php endif; ?>

        <section class="m360-card m360-form">
            <form class="m360-customer-form m360-walkin-form" method="post" action="erp-reception-walkin-save.php" novalidate>
                <?= $csrfInputHtml ?>
                <input type="hidden" name="staff_idempotency_key" value="<?= m360_rw_h($idempotencyKey) ?>">
                <input type="hidden" name="source_channel" value="<?= m360_rw_h(M360_ONLINE_REQ_SOURCE_STAFF_WALKIN) ?>">
                <input type="hidden" id="customer_flow" name="customer_flow" value="new">
                <input type="hidden" id="verified_customer_name" name="verified_customer_name" value="">
                <input type="hidden" id="mobile_verified" name="mobile_verified" value="1">
                <input type="hidden" id="selected_existing_customer_id" name="selected_existing_customer_id" value="">
                <input type="hidden" id="selected_vehicle_id" name="selected_vehicle_id" value="">
                <input type="hidden" id="vehicle_mode" name="vehicle_mode" value="new">

                <?php
                $walkinStages = m360_intake_stage_registry(M360_INTAKE_CHANNEL_WALKIN);
                $walkinLockedMessage = 'ابتدا اطلاعات مشتری، خودرو و خدمات را تکمیل و پرونده را ایجاد کنید.';
                // Operator-facing compact stepper (6 labels). Internal stage keys/logic unchanged.
                $walkinCompactSteps = [
                    [
                        'num' => 1,
                        'label' => 'مشتری',
                        'stage_key' => 'otp',
                        'data_step' => 'm360_section_customer_search',
                    ],
                    [
                        'num' => 2,
                        'label' => 'خودرو',
                        'stage_key' => 'vehicle',
                        'data_step' => 'm360_section_vehicle',
                    ],
                    [
                        'num' => 3,
                        'label' => 'خدمات',
                        'stage_key' => 'service',
                        'data_step' => 'm360_section_request',
                    ],
                    [
                        'num' => 4,
                        'label' => 'بررسی خودرو',
                        'stage_key' => 'condition',
                        'data_step' => '',
                    ],
                    [
                        'num' => 5,
                        'label' => 'مدارک و توافقات',
                        'stage_key' => 'documents',
                        'data_step' => '',
                    ],
                    [
                        'num' => 6,
                        'label' => 'تأیید و ثبت',
                        'stage_key' => 'signature',
                        'data_step' => '',
                    ],
                ];
                ?>
                <nav class="m360-customer-wizard-progress m360-rw-wizard-progress" id="m360_wizard_progress" aria-label="پیشرفت پذیرش حضوری" data-m360-stage-count="8" data-m360-walkin-create="1" data-m360-request-id="<?= (int)$walkinResumeRequestId ?>">
                    <ol class="m360-customer-wizard-progress__list m360-rw-wizard-progress-track">
                        <?php foreach ($walkinCompactSteps as $compactStep): ?>
                            <?php
                            $stageKey = (string)$compactStep['stage_key'];
                            $stageMeta = $walkinStages[$stageKey] ?? [];
                            $dataStep = (string)($compactStep['data_step'] ?? '');
                            $href = '';
                            $locked = false;
                            if ($dataStep !== '') {
                                // Create-page step — unlocked.
                            } elseif ($walkinResumeRequestId > 0 && $stageKey === 'condition') {
                                $href = 'erp-reception-intake-file.php?online_request_id=' . $walkinResumeRequestId
                                    . '&active_step=condition&walkin_resume=1#section-condition-photos';
                            } elseif ($walkinResumeRequestId > 0 && $stageKey === 'documents') {
                                $href = 'erp-reception-intake-file.php?online_request_id=' . $walkinResumeRequestId
                                    . '&active_step=documents&walkin_resume=1#section-agreements';
                            } else {
                                $locked = true;
                            }
                            $liClass = $locked ? 'm360-customer-wizard-progress__item--locked' : '';
                            if ($href !== '') {
                                $liClass = trim($liClass . ' m360-customer-wizard-progress__item--link');
                            }
                            ?>
                            <li
                                class="<?= m360_rw_h($liClass) ?>"
                                data-step="<?= m360_rw_h($dataStep) ?>"
                                data-stage-key="<?= m360_rw_h($stageKey) ?>"
                                data-actor="<?= m360_rw_h((string)($stageMeta['actor'] ?? '')) ?>"
                                data-locked="<?= $locked ? '1' : '0' ?>"
                                <?php if ($href !== ''): ?>data-href="<?= m360_rw_h($href) ?>"<?php endif; ?>
                                <?php if ($locked): ?>aria-disabled="true" title="<?= m360_rw_h($walkinLockedMessage) ?>"<?php endif; ?>
                            >
                                <?= (int)$compactStep['num'] ?>. <?= m360_rw_h((string)$compactStep['label']) ?>
                            </li>
                        <?php endforeach; ?>
                        <?php
                        // Hidden progress anchors so wizard JS can still mark profile / referral internal steps.
                        $walkinHiddenProgress = [
                            ['stage_key' => 'customer', 'data_step' => 'm360_section_profile'],
                            ['stage_key' => 'referral', 'data_step' => ''],
                        ];
                        foreach ($walkinHiddenProgress as $hiddenStep):
                            $stageKey = (string)$hiddenStep['stage_key'];
                            $stageMeta = $walkinStages[$stageKey] ?? [];
                            $dataStep = (string)($hiddenStep['data_step'] ?? '');
                            $locked = $dataStep === '';
                            ?>
                            <li
                                class="m360-step--hidden"
                                hidden
                                aria-hidden="true"
                                data-step="<?= m360_rw_h($dataStep) ?>"
                                data-stage-key="<?= m360_rw_h($stageKey) ?>"
                                data-actor="<?= m360_rw_h((string)($stageMeta['actor'] ?? '')) ?>"
                                data-locked="<?= $locked ? '1' : '0' ?>"
                            ></li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
                <section class="m360-rw-flash is-info" id="m360_walkin_stage56_lock_note" role="status"<?= $walkinResumeRequestId < 1 ? ' hidden' : '' ?>>
                    <?php if ($walkinResumeRequestId > 0): ?>
                        <a class="m360-rw-btn m360-rw-btn-secondary" href="erp-reception-intake-file.php?online_request_id=<?= (int)$walkinResumeRequestId ?>&amp;active_step=condition#section-condition-photos">ادامه بررسی خودرو</a>
                        <a class="m360-rw-btn m360-rw-btn-secondary" href="erp-reception-intake-file.php?online_request_id=<?= (int)$walkinResumeRequestId ?>&amp;active_step=documents#section-agreements">ادامه مدارک و توافقات</a>
                    <?php else: ?>
                        <strong><?= m360_rw_h($walkinLockedMessage) ?></strong>
                    <?php endif; ?>
                </section>

                <section id="m360_step_welcome" class="m360-step-card m360-step--hidden" aria-hidden="true" hidden></section>

                <section id="m360_section_customer_search" class="m360-step-card m360-profile-panel m360-step-card--active" aria-labelledby="m360_customer_search_title">
                    <p id="m360_customer_search_step_error" class="m360-step-error m360-step--hidden" role="alert" aria-live="polite"></p>
                    <div class="m360-step-header">
                        <span class="m360-step-badge" aria-hidden="true">۱</span>
                        <div class="m360-step-header__text">
                            <h3 id="m360_customer_search_title" class="m360-section-title">جست‌وجوی مشتری</h3>
                            <p class="m360-step-sub">مشتری را با شماره موبایل یا کد ملی جست‌وجو کنید.</p>
                        </div>
                    </div>
                    <div class="m360-form-grid">
                        <label for="walkin_search_mobile">شماره موبایل مشتری</label>
                        <input type="tel" id="walkin_search_mobile" inputmode="tel" maxlength="11" pattern="09[0-9]{9}" placeholder="09xxxxxxxxx" autocomplete="tel">
                        <label for="walkin_search_national_id">کد ملی مشتری</label>
                        <input type="text" id="walkin_search_national_id" maxlength="10" inputmode="numeric">
                    </div>
                    <p id="m360_walkin_customer_search_status" class="m360-otp-status" role="status" aria-live="polite"></p>
                    <div class="m360-wizard-nav">
                        <button type="button" id="m360_walkin_new_customer_btn" class="m360-btn m360-btn-secondary">مشتری جدید</button>
                        <button type="button" id="m360_walkin_customer_search_btn" class="m360-btn m360-luxury-action">جست‌وجو</button>
                    </div>
                </section>

                <section id="m360_section_profile" class="m360-step-card m360-profile-panel m360-step--hidden" aria-labelledby="m360_profile_title">
                    <p id="m360_profile_step_error" class="m360-step-error m360-step--hidden" role="alert" aria-live="polite"></p>
                    <div class="m360-step-header">
                        <span class="m360-step-badge" aria-hidden="true">۲</span>
                        <div class="m360-step-header__text">
                            <h3 id="m360_profile_title" class="m360-section-title">اطلاعات مشتری</h3>
                            <p class="m360-step-sub">در صورت نبود مشتری، اطلاعات او را ثبت کنید.</p>
                        </div>
                    </div>
                    <div class="m360-form-grid">
                        <label for="mobile">شماره موبایل <span class="m360-req">*</span></label>
                        <input type="tel" id="mobile" name="mobile" inputmode="tel" maxlength="11" required pattern="09[0-9]{9}" placeholder="09xxxxxxxxx" autocomplete="tel" title="شماره موبایل ۱۱ رقمی با 09 شروع شود">
                        <label for="first_name">نام <span class="m360-req">*</span></label>
                        <input type="text" id="first_name" name="first_name" maxlength="60" data-required-new="1">
                        <label for="last_name">نام خانوادگی <span class="m360-req">*</span></label>
                        <input type="text" id="last_name" name="last_name" maxlength="60" data-required-new="1">
                        <input type="hidden" id="full_name" name="full_name" value="">
                        <label for="national_id">کد ملی</label>
                        <input type="text" id="national_id" name="national_id" maxlength="10" inputmode="numeric">
                        <label for="second_phone">شماره تماس دوم</label>
                        <input type="tel" id="second_phone" name="second_phone" inputmode="tel" maxlength="11">
                        <label for="residence_address">آدرس محل سکونت</label>
                        <input type="text" id="residence_address" name="residence_address" maxlength="300">
                        <label for="vehicle_delivery_address">آدرس تحویل خودرو</label>
                        <input type="text" id="vehicle_delivery_address" name="vehicle_delivery_address" maxlength="300">
                        <label for="authorized_receiver_name">نام تحویل‌گیرنده مجاز</label>
                        <input type="text" id="authorized_receiver_name" name="authorized_receiver_name" maxlength="120">
                        <label for="authorized_receiver_phone">شماره تحویل‌گیرنده مجاز</label>
                        <input type="tel" id="authorized_receiver_phone" name="authorized_receiver_phone" inputmode="tel" maxlength="11">
                        <label for="province">استان</label>
                        <select id="province" name="province">
                            <option value="">انتخاب استان</option>
                        </select>
                        <label for="city">شهر</label>
                        <select id="city" name="city" disabled>
                            <option value="">ابتدا استان را انتخاب کنید</option>
                        </select>
                    </div>
                    <label for="customer_notes" class="m360-sub-label">یادداشت مشتری</label>
                    <textarea id="customer_notes" name="customer_notes" maxlength="1000" placeholder="در صورت نیاز، توضیح کوتاه بنویسید."></textarea>
                    <div class="m360-wizard-nav">
                        <button type="button" class="m360-btn m360-btn-secondary m360-wizard-prev" data-target="m360_section_customer_search">بازگشت</button>
                        <button type="button" class="m360-btn m360-luxury-action m360-wizard-next" data-target="m360_section_vehicle">ادامه</button>
                    </div>
                </section>

                <section id="m360_section_vehicle" class="m360-step-card m360-profile-panel m360-step--hidden" aria-labelledby="m360_vehicle_title">
                    <p id="m360_vehicle_step_error" class="m360-step-error m360-step--hidden" role="alert" aria-live="polite"></p>
                    <div class="m360-step-header">
                        <span class="m360-step-badge" aria-hidden="true">۳</span>
                        <div class="m360-step-header__text">
                            <h3 id="m360_vehicle_title" class="m360-section-title">اطلاعات خودرو</h3>
                        </div>
                    </div>
                    <div id="m360_vehicle_picker" class="m360-vehicle-picker" hidden>
                        <p class="m360-muted">در صورت وجود خودرو در سوابق مشتری، از فهرست زیر انتخاب کنید.</p>
                        <div id="m360_vehicle_picker_list" class="m360-vehicle-picker__list" role="radiogroup" aria-label="انتخاب خودرو"></div>
                        <div id="m360_vehicle_out_of_scope" class="m360-vehicle-out-of-scope" hidden>
                            <ul id="m360_vehicle_out_of_scope_list" class="m360-vehicle-out-of-scope__list"></ul>
                        </div>
                        <button type="button" id="m360_vehicle_add_new" class="m360-btn-link">افزودن خودرو جدید</button>
                    </div>
                    <div id="m360_existing_vehicle_summary" class="m360-rw-flash is-info" hidden role="status" aria-live="polite">
                        <strong>خودروی انتخاب‌شده از سوابق مشتری</strong>
                        <p id="m360_existing_vehicle_summary_label" class="m360-step-sub" style="margin:0.35rem 0 0"></p>
                        <p class="m360-step-sub" style="margin:0.35rem 0 0">ادامه تکمیل وضعیت پذیرش امروز — کیلومتر فعلی و سطح بنزین را ثبت کنید.</p>
                    </div>
                    <div id="m360_vehicle_new_fields">
                        <label for="vehicle_brand">برند خودرو <span class="m360-req">*</span></label>
                        <select id="vehicle_brand" name="vehicle_brand" data-required-both="1">
                            <option value="">انتخاب برند</option>
                        </select>
                        <label for="vehicle_class">کلاس / مدل خودرو <span class="m360-req">*</span></label>
                        <select id="vehicle_class" name="vehicle_class" data-required-both="1" disabled>
                            <option value="">ابتدا برند را انتخاب کنید</option>
                        </select>
                        <label for="vehicle_year_pair">سال تولید <span class="m360-req">*</span></label>
                        <select id="vehicle_year_pair" name="vehicle_year_pair" data-required-both="1">
                            <option value="">انتخاب سال تولید</option>
                            <?php foreach ($vehicleYearOptions as $yearOpt): ?>
                                <option value="<?= m360_rw_h($yearOpt['value']) ?>"><?= m360_rw_h($yearOpt['label']) ?></option>
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
                                        </select>
                                        <select class="plate-digit-select" id="plate_first_digit_2" name="plate_first_digit_2" data-required-both="1" aria-label="رقم دوم پلاک">
                                            <option value="">-</option>
                                        </select>
                                    </div>
                                </div>
                                <span class="iran-plate-sep" aria-hidden="true"></span>
                                <div class="iran-plate-group iran-plate-group--letter" aria-label="حرف پلاک">
                                    <span class="iran-plate-group__label">حرف</span>
                                    <select class="plate-letter-select" id="plate_letter" name="plate_letter" data-required-both="1" aria-label="حرف پلاک">
                                        <option value="">حرف</option>
                                        <?php foreach ($plateLetters as $letter): ?>
                                            <option value="<?= m360_rw_h($letter) ?>"><?= m360_rw_h($letter) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <span class="iran-plate-sep" aria-hidden="true"></span>
                                <div class="iran-plate-group iran-plate-group--middle" aria-label="سه رقم وسط">
                                    <span class="iran-plate-group__label">۳ رقم</span>
                                    <div class="iran-plate-group__inputs">
                                        <select class="plate-digit-select" id="plate_middle_digit_1" name="plate_middle_digit_1" data-required-both="1" aria-label="رقم اول سه‌رقمی">
                                            <option value="">-</option>
                                        </select>
                                        <select class="plate-digit-select" id="plate_middle_digit_2" name="plate_middle_digit_2" data-required-both="1" aria-label="رقم دوم سه‌رقمی">
                                            <option value="">-</option>
                                        </select>
                                        <select class="plate-digit-select" id="plate_middle_digit_3" name="plate_middle_digit_3" data-required-both="1" aria-label="رقم سوم سه‌رقمی">
                                            <option value="">-</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="iran-plate-region-box" aria-label="کد ایران">
                                <span class="iran-plate-region-box__label">ایران</span>
                                <div class="iran-plate-region-box__digits">
                                    <select class="plate-digit-select" id="plate_region_digit_1" name="plate_region_digit_1" data-required-both="1" aria-label="رقم اول کد ایران">
                                        <option value="">-</option>
                                    </select>
                                    <select class="plate-digit-select" id="plate_region_digit_2" name="plate_region_digit_2" data-required-both="1" aria-label="رقم دوم کد ایران">
                                        <option value="">-</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <p id="plate_preview" class="iran-plate-preview" aria-live="polite">پس از تکمیل، پلاک اینجا نمایش داده می‌شود</p>
                        <input type="hidden" id="plate_left_2_digits" name="plate_left_2_digits" value="">
                        <input type="hidden" id="plate_middle_3_digits" name="plate_middle_3_digits" value="">
                        <input type="hidden" id="plate_region_2_digits" name="plate_region_2_digits" value="">
                        <input type="hidden" id="plate_display" name="plate_display" value="">
                        <label for="vin">شماره شاسی (VIN)</label>
                        <input type="text" id="vin" name="vin" maxlength="17">
                        <label for="chassis_number">شماره شاسی داخلی</label>
                        <input type="text" id="chassis_number" name="chassis_number" maxlength="160">
                        <label for="color">رنگ</label>
                        <input type="text" id="color" name="color" maxlength="160">
                    </div>
                    <div id="m360_vehicle_visit_fields" class="m360-vehicle-visit-fields">
                        <label for="odometer_km">کیلومتر فعلی <span class="m360-req">*</span></label>
                        <input type="number" id="odometer_km" name="odometer_km" min="0" step="1" data-visit-field="1">
                        <label for="fuel_level">سطح بنزین <span class="m360-req">*</span></label>
                        <select id="fuel_level" name="fuel_level" data-visit-field="1">
                            <option value="">نامشخص</option>
                            <?php foreach ($fuelLevels as $level): ?>
                                <option value="<?= m360_rw_h($level) ?>"><?= m360_rw_h($level) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="m360-wizard-nav">
                        <button type="button" class="m360-btn m360-btn-secondary m360-wizard-prev" data-target="m360_section_profile">بازگشت</button>
                        <button type="button" class="m360-btn m360-luxury-action m360-wizard-next" data-target="m360_section_request">ادامه</button>
                    </div>
                </section>

                <section id="m360_section_request" class="m360-step-card m360-request-panel m360-step--hidden" aria-labelledby="m360_request_title">
                    <p id="m360_request_step_error" class="m360-step-error m360-step--hidden" role="alert" aria-live="polite"></p>
                    <div class="m360-step-header">
                        <span class="m360-step-badge" aria-hidden="true">۴</span>
                        <div class="m360-step-header__text">
                            <h3 id="m360_request_title" class="m360-section-title">خدمات</h3>
                        </div>
                    </div>
                    <label for="request_type">نوع درخواست <span class="m360-req">*</span></label>
                    <select id="request_type" name="request_type" data-required-both="1">
                        <option value="">انتخاب کنید</option>
                        <?php foreach ($serviceTypes as $code => $label): ?>
                            <option value="<?= m360_rw_h((string)$code) ?>"><?= m360_rw_h((string)$label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="fault_path">مسیر عیب / خدمت</label>
                    <input type="text" id="fault_path" name="fault_path" maxlength="500">
                    <label for="request_description">شرح درخواست / گفته مشتری <span class="m360-req">*</span></label>
                    <textarea id="request_description" name="request_description" data-required-both="1" maxlength="1500"></textarea>
                    <label for="diagnostic_options">گزینه‌های تشخیصی</label>
                    <textarea id="diagnostic_options" name="diagnostic_options" maxlength="500" placeholder="مثلاً صدای موتور، چراغ هشدار، سرویس دوره‌ای"></textarea>
                    <?php m360_rw_render_public_diagnostic_parity_fields(); ?>
                    <div class="m360-date-field">
                        <input type="text" id="visit_date_display" class="m360-date-display m360-date-display--filled" readonly value="<?= m360_rw_h($defaultVisitDisplay) ?>" aria-describedby="visit_date_hint">
                        <input type="hidden" id="visit_date" name="visit_date" value="<?= m360_rw_h($defaultVisitDate) ?>">
                        <p id="visit_date_hint" class="m360-jalali-datepicker__hint">انتخاب مراجعه در بازه کاری پذیرش؛ روزهای غیرقابل انتخاب غیرفعال هستند.</p>
                        <div class="m360-server-calendar" id="m360_server_calendar" role="group" aria-label="تقویم مراجعه">
                            <?php foreach ($visitCalendarDays as $day): ?>
                                <?php m360_rw_calendar_render_day_button($day, $defaultVisitDate, 'm360_rw_h'); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <p id="visit_time_hint" class="m360-visit-hint" style="display:none">ساعت حضور برای کارشناسی معمولاً بین 8:30 تا 11:30 است.</p>
                    <div class="m360-wizard-nav">
                        <button type="button" class="m360-btn m360-btn-secondary m360-wizard-prev" data-target="m360_section_vehicle">بازگشت</button>
                        <button type="submit" id="m360_submit_btn" class="m360-btn m360-luxury-action">ثبت و ادامه</button>
                    </div>
                </section>
            </form>
        </section>
    <?php endif; ?>

    <footer class="m360-rw-footer">
        <a href="erp-reception-board.php">بازگشت به مرکز ارتباط با مشتریان</a>
        <a href="erp-reception-online-requests.php">صف درخواست‌ها</a>
        <a href="erp-product-home.php">خانه محصول</a>
    </footer>
</div>
<script>window.m360CustomerPageBoot=<?= json_encode($m360CustomerPageBoot, JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="assets/js/iran-provinces-cities.js"></script>
<script src="assets/js/vehicle-brand-classes.js"></script>
<?php
$m360CustomerFormJs = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'customer-form.js';
$m360CustomerFormVer = is_file($m360CustomerFormJs) ? (string)filemtime($m360CustomerFormJs) : '1';
?>
<script src="assets/js/customer-form.js?v=<?= m360_rw_h($m360CustomerFormVer) ?>"></script>
</body>
</html>
