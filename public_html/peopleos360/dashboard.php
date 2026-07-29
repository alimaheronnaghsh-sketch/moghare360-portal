<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$hc = p360_report_headcount($conn);
$wf = p360_report_pending_workflows($conn);
$lv = p360_report_open_leaves($conn);
p360_layout_start('داشبورد PeopleOS360', 'dashboard.php');
?>
<div class="m360-page-header" style="border-bottom:none;margin-bottom:0.5rem;">
<p class="m360-page-subtitle">سامانه مستقل اداری و منابع انسانی ۳۶۰</p>
<div class="m360-page-badges">
<span class="m360-badge m360-badge-ok">مستقل</span>
<span class="m360-badge m360-badge-info">پایگاه داده مشترک moghare360_ERP</span>
<span class="m360-badge">بدون اتصال عملیاتی</span>
</div>
</div>

<div class="m360-alert m360-alert-info" style="margin-top:1rem;">
PeopleOS360 یک سامانه مستقل اداری و منابع انسانی برای مدیریت شرکت، ساختار سازمانی، پرونده پرسنلی، حضور و غیاب، حقوق، قراردادها، درخواست‌ها، اسناد، و گردش‌کار است. این نرم‌افزار فعلاً مستقل است و در این مرحله به پذیرش، انبار، جاب‌کارت یا مالی متصل نیست.
</div>

<div class="m360-kpi-grid">
<div class="m360-kpi-card"><span>پرسنل فعال</span><strong><?= (int)$hc ?></strong></div>
<div class="m360-kpi-card"><span>کارتابل باز</span><strong><?= (int)$wf ?></strong></div>
<div class="m360-kpi-card"><span>مرخصی‌های باز</span><strong><?= (int)$lv ?></strong></div>
</div>

<h2 style="font-size:1.1rem;margin-bottom:1rem;">دسترسی سریع ماژول‌ها</h2>
<div class="m360-module-grid">
<a class="m360-module-card" href="companies.php"><h3>شرکت و سازمان</h3><p>شرکت، شعب، واحدها و سمت‌ها</p></a>
<a class="m360-module-card" href="employees.php"><h3>پرسنل و Employee 360</h3><p>پرونده پرسنلی، جستجو و سوابق</p></a>
<a class="m360-module-card" href="legal-rule-center.php"><h3>قوانین کار و قراردادها</h3><p>مرکز قوانین، نوع همکاری، قالب قرارداد</p></a>
<a class="m360-module-card" href="attendance-devices.php"><h3>حضور و غیاب</h3><p>دستگاه، لاگ خام، کارکرد، تایم‌شیت</p></a>
<a class="m360-module-card" href="payroll-periods.php"><h3>حقوق و دستمزد</h3><p>دوره حقوق، اجرا، فیش حقوق</p></a>
<a class="m360-module-card" href="recruitment.php"><h3>جذب و استخدام</h3><p>درخواست نیرو، موقعیت، کاندید، مصاحبه</p></a>
<a class="m360-module-card" href="self-service.php"><h3>خدمات پرسنلی</h3><p>مرخصی، مأموریت، اضافه‌کاری</p></a>
<a class="m360-module-card" href="loans.php"><h3>وام و رفاه</h3><p>درخواست وام، اقساط، کسر از حقوق</p></a>
<a class="m360-module-card" href="performance-reviews.php"><h3>عملکرد و آموزش</h3><p>KPI، ارزیابی، دوره‌های آموزشی</p></a>
<a class="m360-module-card" href="announcements.php"><h3>اسناد و ابلاغ</h3><p>اطلاعیه، تجهیزات، تشویق و تنبیه</p></a>
<a class="m360-module-card" href="exit-cases.php"><h3>خروج و تسویه</h3><p>ثبت خروج، چک‌لیست تسویه</p></a>
<a class="m360-module-card" href="reports.php"><h3>گزارش‌ها و Audit</h3><p>گزارش‌ها، لاگ فعالیت، تنظیمات</p></a>
</div>

<?= p360_legal_disclaimer_html() ?>

<h2 style="font-size:1.1rem;margin:1.5rem 0 0.75rem;">مسیر تست پیشنهادی مالک</h2>

<?php
$sections = [
    'راه‌اندازی پایه' => [
        1 => ['login.php', 'ورود با amir'],
        2 => ['company-setup.php', 'تنظیم شرکت'],
        3 => ['branches.php', 'تعریف شعبه'],
        4 => ['departments.php', 'تعریف دپارتمان'],
        5 => ['positions.php', 'تعریف سمت'],
    ],
    'قوانین و قراردادها' => [
        6 => ['employment-types.php', 'نوع همکاری'],
        7 => ['contract-types.php', 'نوع قرارداد'],
        8 => ['legal-rule-center.php', 'جدول قوانین کار'],
        9 => ['legal-rule-form.php', 'قانون مرخصی'],
        10 => ['legal-rule-form.php', 'قانون بیمه'],
        11 => ['legal-rule-form.php', 'قانون مالیات'],
        12 => ['labor-calendar.php', 'تقویم شمسی'],
        13 => ['labor-calendar.php', 'تعریف شیفت'],
        14 => ['contract-templates.php', 'قالب قرارداد'],
        15 => ['legal-text-upload.php', 'آپلود متن قرارداد'],
    ],
    'پرونده پرسنلی' => [
        16 => ['employee-form.php', 'تعریف کارمند'],
        17 => ['employee-view.php', 'Employee 360'],
        18 => ['employee-position-history.php', 'تغییر سمت'],
        19 => ['employee-salary-history.php', 'تغییر حقوق'],
        20 => ['contract-generate.php', 'قرارداد از قالب'],
        21 => ['employees.php', 'Document Center'],
    ],
    'حضور و حقوق' => [
        22 => ['attendance-devices.php', 'دستگاه حضور'],
        23 => ['attendance-raw.php', 'ورود خام تردد'],
        24 => ['attendance-records.php', 'محاسبه کارکرد'],
        25 => ['timesheets.php', 'تأیید Timesheet'],
        26 => ['payroll-run.php', 'محاسبه حقوق'],
        27 => ['payroll-slips.php', 'فیش حقوق'],
    ],
    'خدمات پرسنلی' => [
        28 => ['leave-requests.php', 'درخواست مرخصی'],
        29 => ['approvals.php', 'تأیید مرخصی'],
        30 => ['loan-form.php', 'درخواست وام'],
        31 => ['approvals.php', 'تأیید وام'],
        32 => ['loans.php', 'تولید اقساط'],
        33 => ['payroll-run.php', 'کسر قسط از حقوق'],
    ],
    'جذب و استخدام' => [
        34 => ['manpower-requests.php', 'درخواست نیرو'],
        35 => ['vacancies.php', 'موقعیت شغلی'],
        36 => ['candidates.php', 'ثبت کاندید'],
        37 => ['candidates.php', 'مصاحبه'],
        38 => ['candidates.php', 'پیشنهاد همکاری'],
        39 => ['candidates.php', 'تبدیل به Employee'],
    ],
    'عملکرد و تجهیزات' => [
        40 => ['performance-reviews.php', 'ثبت KPI'],
        41 => ['performance-reviews.php', 'ارزیابی عملکرد'],
        42 => ['training.php', 'ثبت آموزش'],
        43 => ['rewards.php', 'ثبت تشویق'],
        44 => ['disciplinary.php', 'ثبت تنبیه'],
        45 => ['equipment-assignments.php', 'تحویل تجهیزات'],
        46 => ['equipment-assignments.php', 'بازگشت تجهیزات'],
    ],
    'خروج، گزارش و Audit' => [
        47 => ['announcements.php', 'ثبت ابلاغ'],
        48 => ['announcements.php', 'تأیید دریافت'],
        49 => ['exit-cases.php', 'خروج پرسنل'],
        50 => ['settlement.php', 'چک‌لیست تسویه'],
        51 => ['reports.php', 'گزارش‌ها'],
        52 => ['audit-log.php', 'Audit'],
    ],
];
foreach ($sections as $secTitle => $steps): ?>
<div class="m360-step-section">
<h3><?= p360_h($secTitle) ?></h3>
<div class="m360-step-grid">
<?php foreach ($steps as $n => $s): ?>
<a class="m360-step-pill" href="<?= p360_h($s[0]) ?>"><?= (int)$n ?>. <?= p360_h($s[1]) ?></a>
<?php endforeach; ?>
</div>
</div>
<?php endforeach; ?>

<?php p360_layout_end(); ?>
