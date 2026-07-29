<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
if (!work360_is_manager()) { http_response_code(403); echo 'دسترسی مجاز نیست.'; exit; }
$conn = work360_db();
$today = work360_today();
$depts = work360_rows($conn, 'SELECT * FROM dbo.work360_departments WHERE is_active=1 ORDER BY sort_order', []);
work360_layout_start('گزارش مدیریت', 'manager-report.php');
?>
<div class="w360-lights">
<?php foreach ($depts as $d):
  $m = work360_dept_metrics($conn, (int)$d['department_id'], $today); ?>
  <div class="w360-light-card">
    <h3><span class="w360-light <?= work360_h($m['light']) ?>"></span><?= work360_h((string)$d['department_name_fa']) ?></h3>
    <div class="w360-gauge-row" style="margin:0">
      <?= work360_gauge_svg((float)$m['completion'], 'تکمیل') ?>
      <?= work360_gauge_svg((float)$m['delay'], 'تأخیر', $m['delay'] > 20 ? 'bad' : 'warn') ?>
    </div>
    <p style="font-size:.8rem;color:var(--m360-muted)">باز: <?= (int)$m['open'] ?> · امروز: <?= (int)$m['todayDue'] ?> · عقب‌افتاده: <?= (int)$m['overdue'] ?> · مسدود: <?= (int)$m['blocked'] ?> · انجام‌شده: <?= (int)$m['doneToday'] ?></p>
  </div>
<?php endforeach; ?>
</div>
<div class="w360-actions">
  <a class="m360-btn m360-btn-primary" href="daily-performance.php">عملکرد روزانه</a>
  <a class="m360-btn m360-btn-secondary" href="reports.php">گزارش‌ها</a>
  <a class="m360-btn m360-btn-secondary" href="supervisor-board.php">برد سرپرست</a>
</div>
<?php work360_layout_end(); ?>
