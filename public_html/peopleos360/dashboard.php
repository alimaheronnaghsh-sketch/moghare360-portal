<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$hc = p360_report_headcount($conn);
$wf = p360_report_pending_workflows($conn);
$lv = p360_report_open_leaves($conn);
p360_layout_start('منابع انسانی', 'dashboard.php');
?>
<div class="m360-page-header" style="border-bottom:none;margin-bottom:0.75rem;">
<p class="m360-page-subtitle">مدیریت پرسنل، قراردادها، حضور، حقوق و درخواست‌های سازمانی</p>
</div>

<div class="m360-kpi-grid">
  <div class="m360-kpi-card"><span>پرسنل فعال</span><strong><?= (int)$hc ?></strong></div>
  <div class="m360-kpi-card"><span>درخواست‌های در انتظار بررسی</span><strong><?= (int)$wf ?></strong></div>
  <div class="m360-kpi-card"><span>مرخصی در انتظار بررسی</span><strong><?= (int)$lv ?></strong></div>
</div>

<div class="m360-module-grid" aria-label="حوزه‌های منابع انسانی">
  <a class="m360-module-card" href="employees.php">
    <h3>پرسنل</h3>
    <p>فهرست و پرونده کارکنان</p>
  </a>
  <a class="m360-module-card" href="contract-templates.php">
    <h3>قرارداد و جایگاه سازمانی</h3>
    <p>قالب قرارداد، نوع همکاری و سمت‌ها</p>
  </a>
  <a class="m360-module-card" href="attendance-records.php">
    <h3>زمان کاری و حضور و غیاب</h3>
    <p>کارکرد روزانه و کنترل حضور</p>
  </a>
  <a class="m360-module-card" href="leave-requests.php">
    <h3>مرخصی، اضافه‌کاری و انضباط</h3>
    <p>درخواست‌ها و پیگیری موارد انضباطی</p>
  </a>
  <a class="m360-module-card" href="payroll-slips.php">
    <h3>حقوق و مزایا</h3>
    <p>فیش حقوق و دوره‌های پرداخت</p>
  </a>
  <a class="m360-module-card" href="self-service.php">
    <h3>کارتابل پرسنل</h3>
    <p>خدمات و درخواست‌های شخصی پرسنل</p>
  </a>
  <a class="m360-module-card" href="reports.php">
    <h3>گزارش‌ها و شاخص‌ها</h3>
    <p>گزارش‌های منابع انسانی و شاخص‌ها</p>
  </a>
  <a class="m360-module-card" href="company-setup.php">
    <h3>تنظیمات مدیریتی</h3>
    <p>راه‌اندازی شرکت و ساختار سازمانی</p>
  </a>
</div>

<?php p360_layout_end(); ?>
