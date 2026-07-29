<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$hc = p360_report_headcount($conn);
$wf = p360_report_pending_workflows($conn);
$lv = p360_report_open_leaves($conn);
p360_layout_start('داشبورد PeopleOS360', 'dashboard.php');
$steps = [
    1 => ['login.php', 'ورود با amir'],
    2 => ['company-setup.php', 'تنظیم شرکت'],
    3 => ['branches.php', 'تعریف شعبه'],
    4 => ['departments.php', 'تعریف دپارتمان'],
    5 => ['positions.php', 'تعریف سمت'],
    6 => ['employment-types.php', 'تعریف نوع همکاری'],
    7 => ['contract-types.php', 'تعریف نوع قرارداد'],
    8 => ['legal-rule-center.php', 'ثبت جدول قوانین کار'],
    9 => ['legal-rule-form.php', 'ثبت قانون مرخصی'],
    10 => ['legal-rule-form.php', 'ثبت قانون بیمه'],
    11 => ['legal-rule-form.php', 'ثبت قانون مالیات'],
    12 => ['labor-calendar.php', 'ثبت تقویم شمسی'],
    13 => ['labor-calendar.php', 'تعریف شیفت'],
    14 => ['contract-templates.php', 'تعریف قالب قرارداد'],
    15 => ['legal-text-upload.php', 'آپلود / ورود متن قرارداد'],
    16 => ['employee-form.php', 'تعریف کارمند'],
    17 => ['employee-view.php', 'مشاهده Employee 360'],
    18 => ['employee-position-history.php', 'تغییر سمت با تاریخ مؤثر'],
    19 => ['employee-salary-history.php', 'تغییر حقوق با تاریخ مؤثر'],
    20 => ['contract-generate.php', 'ایجاد قرارداد از قالب'],
    21 => ['employees.php', 'ثبت سند در Document Center'],
    22 => ['attendance-devices.php', 'تعریف دستگاه حضور و غیاب'],
    23 => ['attendance-raw.php', 'ورود خام تردد'],
    24 => ['attendance-records.php', 'محاسبه کارکرد'],
    25 => ['timesheets.php', 'تأیید Timesheet'],
    26 => ['payroll-run.php', 'محاسبه حقوق'],
    27 => ['payroll-slips.php', 'مشاهده فیش حقوق'],
    28 => ['leave-requests.php', 'درخواست مرخصی'],
    29 => ['approvals.php', 'تأیید مرخصی از کارتابل'],
    30 => ['loan-form.php', 'درخواست وام'],
    31 => ['approvals.php', 'تأیید وام'],
    32 => ['loans.php', 'تولید اقساط'],
    33 => ['payroll-run.php', 'کسر قسط از حقوق'],
    34 => ['manpower-requests.php', 'ثبت درخواست نیروی انسانی'],
    35 => ['vacancies.php', 'تعریف موقعیت شغلی'],
    36 => ['candidates.php', 'ثبت کاندید'],
    37 => ['candidates.php', 'مصاحبه'],
    38 => ['candidates.php', 'پیشنهاد همکاری'],
    39 => ['candidates.php', 'تبدیل Candidate به Employee'],
    40 => ['performance-reviews.php', 'ثبت KPI'],
    41 => ['performance-reviews.php', 'ارزیابی عملکرد'],
    42 => ['training.php', 'ثبت آموزش'],
    43 => ['rewards.php', 'ثبت تشویق'],
    44 => ['disciplinary.php', 'ثبت تنبیه'],
    45 => ['equipment-assignments.php', 'تحویل تجهیزات'],
    46 => ['equipment-assignments.php', 'بازگشت تجهیزات'],
    47 => ['announcements.php', 'ثبت ابلاغ'],
    48 => ['announcements.php', 'تأیید دریافت ابلاغ'],
    49 => ['exit-cases.php', 'ثبت خروج پرسنل'],
    50 => ['settlement.php', 'چک‌لیست تسویه'],
    51 => ['reports.php', 'مشاهده گزارش‌ها'],
    52 => ['audit-log.php', 'مشاهده Audit'],
];
?>
<div class="kpi-grid">
<div class="kpi"><span>پرسنل فعال</span><strong><?= (int)$hc ?></strong></div>
<div class="kpi"><span>کارتابل باز</span><strong><?= (int)$wf ?></strong></div>
<div class="kpi"><span>مرخصی باز</span><strong><?= (int)$lv ?></strong></div>
</div>
<?= p360_legal_disclaimer_html() ?>
<h2>مسیر تست پیشنهادی مالک</h2>
<div class="steps">
<?php foreach ($steps as $n => $s): ?>
<a href="<?= p360_h($s[0]) ?>"><?= (int)$n ?>. <?= p360_h($s[1]) ?></a>
<?php endforeach; ?>
</div>
<?php p360_layout_end(); ?>
