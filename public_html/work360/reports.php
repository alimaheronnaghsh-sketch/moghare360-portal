<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
if (!work360_is_manager()) { http_response_code(403); echo 'دسترسی مجاز نیست.'; exit; }
$conn = work360_db();
$today = work360_today();
$total = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE status_code<>N'CANCELLED'", []) ?? 0);
$open = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE status_code IN (N'TODO',N'IN_PROGRESS',N'WAITING',N'BLOCKED')", []) ?? 0);
$overdue = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE due_date<? AND status_code IN (N'TODO',N'IN_PROGRESS',N'WAITING',N'BLOCKED')", [$today]) ?? 0);
$done = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_tasks WHERE status_code=N'DONE' AND CONVERT(date, completed_at)=?", [$today]) ?? 0);
$fu = (int)(work360_scalar($conn, "SELECT COUNT(*) FROM dbo.work360_task_followups WHERE CONVERT(date, created_at)=?", [$today]) ?? 0);
work360_layout_start('گزارش‌ها', 'reports.php');
?>
<div class="w360-kpi-strip">
  <div class="w360-kpi"><span>کل کارها</span><strong><?= $total ?></strong></div>
  <div class="w360-kpi"><span>باز</span><strong><?= $open ?></strong></div>
  <div class="w360-kpi"><span>عقب‌افتاده</span><strong><?= $overdue ?></strong></div>
  <div class="w360-kpi"><span>انجام‌شده امروز</span><strong><?= $done ?></strong></div>
  <div class="w360-kpi"><span>پیگیری امروز</span><strong><?= $fu ?></strong></div>
</div>
<div class="w360-actions">
  <a class="m360-btn m360-btn-primary" href="manager-report.php">گزارش واحدها</a>
  <a class="m360-btn m360-btn-secondary" href="daily-performance.php">عملکرد روزانه</a>
</div>
<?php work360_layout_end(); ?>
