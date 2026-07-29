<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
$user = work360_current_user();
$conn = work360_db();
$today = work360_today();
$depts = work360_visible_departments($conn, $user);
$personal = work360_user_metrics($conn, (int)$user['user_id'], $today);
$scope = work360_scope_sql($user, 't');

$reminders = work360_rows($conn, "SELECT TOP 8 t.task_id, t.title, t.status_code, t.priority_code, t.due_date, d.department_name_fa
  FROM dbo.work360_tasks t
  JOIN dbo.work360_departments d ON d.department_id=t.department_id
  WHERE {$scope['sql']} AND t.status_code IN (N'TODO',N'IN_PROGRESS',N'WAITING',N'BLOCKED')
  ORDER BY CASE WHEN t.due_date < ? THEN 0 WHEN t.due_date=? THEN 1 ELSE 2 END, t.priority_code DESC, t.due_date",
  array_merge($scope['params'], [$today, $today]));

work360_layout_start('مرکز کار و پیگیری روزانه Work360', 'dashboard.php');
?>
<p class="m360-page-subtitle">چراغ عملیات، کارهای امروز، پیگیری سرپرست و گزارش عملکرد</p>
<div class="w360-hero-meta">
  <span class="m360-badge m360-badge-ok"><?= work360_h(work360_role_fa((string)$user['role_code'])) ?></span>
  <span class="m360-badge"><?= work360_h((string)($user['department_name_fa'] ?: 'همه واحدها')) ?></span>
  <span class="m360-badge m360-badge-info"><?= work360_h($today) ?></span>
  <span class="m360-badge">پایگاه داده: moghare360_ERP</span>
</div>

<div class="w360-kpi-strip">
  <div class="w360-kpi"><span>امروز من</span><strong><?= (int)$personal['todayTasks'] ?></strong></div>
  <div class="w360-kpi"><span>انجام‌شده امروز</span><strong><?= (int)$personal['done'] ?></strong></div>
  <div class="w360-kpi"><span>عقب‌افتاده</span><strong><?= (int)$personal['overdue'] ?></strong></div>
  <div class="w360-kpi"><span>منتظر</span><strong><?= (int)$personal['waiting'] ?></strong></div>
  <div class="w360-kpi"><span>مسدود</span><strong><?= (int)$personal['blocked'] ?></strong></div>
</div>

<div class="w360-gauge-row">
  <?= work360_gauge_svg((float)$personal['score'], 'امتیاز من', $personal['score'] >= 70 ? 'ok' : ($personal['score'] >= 40 ? 'warn' : 'bad')) ?>
  <?php
  $openMine = max(1, (int)$personal['todayTasks'] + (int)$personal['overdue']);
  $doneRate = round(((int)$personal['done'] / $openMine) * 100, 1);
  echo work360_gauge_svg($doneRate, 'پیشرفت امروز', 'ok');
  $delayRate = round(((int)$personal['overdue'] / $openMine) * 100, 1);
  echo work360_gauge_svg($delayRate, 'تأخیر', $delayRate > 20 ? 'bad' : 'warn');
  ?>
</div>

<div class="w360-actions">
<?php $role = work360_role(); ?>
<a class="m360-btn m360-btn-primary" href="my-tasks.php">کارهای من</a>
<?php if ($role === 'STAFF'): ?>
<a class="m360-btn m360-btn-secondary" href="task-create.php">ثبت کار</a>
<a class="m360-btn m360-btn-secondary" href="my-tasks.php?f=blocked">ثبت مانع</a>
<?php endif; ?>
<?php if (in_array($role, ['SUPERVISOR','MANAGER','OWNER'], true)): ?>
<a class="m360-btn m360-btn-secondary" href="supervisor-board.php">برد سرپرست</a>
<a class="m360-btn m360-btn-secondary" href="supervisor-board.php?f=overdue">عقب‌افتاده تیم</a>
<?php endif; ?>
<?php if (in_array($role, ['MANAGER','OWNER'], true)): ?>
<a class="m360-btn m360-btn-secondary" href="manager-report.php">گزارش مدیریت</a>
<a class="m360-btn m360-btn-secondary" href="daily-performance.php">عملکرد روزانه</a>
<a class="m360-btn m360-btn-secondary" href="users.php">کاربران</a>
<?php endif; ?>
</div>

<h2 style="font-size:1.05rem;margin:0 0 .75rem;">چراغ وضعیت واحدها</h2>
<div class="w360-lights">
<?php foreach ($depts as $d):
  $m = work360_dept_metrics($conn, (int)$d['department_id'], $today); ?>
  <div class="w360-light-card">
    <h3><span class="w360-light <?= work360_h($m['light']) ?>"></span><?= work360_h((string)$d['department_name_fa']) ?></h3>
    <div class="w360-gauge-row" style="margin:0">
      <?= work360_gauge_svg((float)$m['completion'], 'تکمیل', 'ok') ?>
      <?= work360_gauge_svg((float)$m['delay'], 'تأخیر', $m['delay'] > 20 ? 'bad' : 'warn') ?>
    </div>
    <p style="font-size:.8rem;color:var(--m360-muted);margin-top:.5rem;">
      امروز: <?= (int)$m['todayDue'] ?> · عقب‌افتاده: <?= (int)$m['overdue'] ?> · مسدود: <?= (int)$m['blocked'] ?> · انجام‌شده: <?= (int)$m['doneToday'] ?>
    </p>
  </div>
<?php endforeach; ?>
<?php if ($depts === []): ?>
  <div class="m360-alert m360-alert-warn">واحدی برای نمایش وجود ندارد.</div>
<?php endif; ?>
</div>

<h2 style="font-size:1.05rem;margin:0 0 .75rem;">یادآوری کارهای باز</h2>
<div class="w360-task-mini">
<?php foreach ($reminders as $t): ?>
  <a class="w360-task-card" href="task-view.php?id=<?= (int)$t['task_id'] ?>" style="text-decoration:none;color:inherit">
    <h4><?= work360_h((string)$t['title']) ?></h4>
    <p><?= work360_h((string)$t['department_name_fa']) ?> · <?= work360_h(work360_status_fa((string)$t['status_code'])) ?> · <?= work360_h((string)$t['due_date']) ?></p>
  </a>
<?php endforeach; ?>
<?php if ($reminders === []): ?>
  <div class="m360-empty-state">کار بازی برای یادآوری نیست.</div>
<?php endif; ?>
</div>
<?php work360_layout_end(); ?>
