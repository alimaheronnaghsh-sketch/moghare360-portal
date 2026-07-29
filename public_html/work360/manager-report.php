<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
if (!work360_is_manager()) { http_response_code(403); echo 'دسترسی مجاز نیست.'; exit; }
$user = work360_current_user();
$conn = work360_db();
$today = work360_today();
$depts = work360_rows($conn, 'SELECT * FROM dbo.work360_departments WHERE is_active=1 ORDER BY sort_order', []);

$flash = ''; $flashOk = true;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['complete_task_id'])) {
    work360_csrf_require();
    $tid = (int)$_POST['complete_task_id'];
    $note = trim((string)($_POST['completion_note'] ?? ''));
    $res = work360_complete_task($conn, $user, $tid, $note);
    $flashOk = !empty($res['ok']);
    $flash = (string)($res['message'] ?? '');
    if ($flashOk) {
        header('Location: manager-report.php');
        exit;
    }
}

$openTasks = work360_rows($conn, "SELECT TOP 40 t.*, d.department_name_fa, u.full_name AS assignee_name, u.role_code AS assignee_role
  FROM dbo.work360_tasks t
  JOIN dbo.work360_departments d ON d.department_id=t.department_id
  LEFT JOIN dbo.work360_users u ON u.user_id=t.assigned_to_user_id
  WHERE t.status_code IN (N'TODO',N'IN_PROGRESS',N'WAITING',N'BLOCKED')
  ORDER BY t.due_date, t.priority_code DESC", []);

work360_layout_start('گزارش مدیریت', 'manager-report.php');
work360_flash($flash, $flashOk);
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

<h2 style="font-size:1rem;margin:1rem 0 .5rem">اتمام کارهای باز (مدیر)</h2>
<div class="w360-table-wrap">
<table class="m360-table">
<thead><tr><th>کد</th><th>عنوان</th><th>واحد</th><th>مسئول</th><th>نقش</th><th>وضعیت</th><th>اتمام</th></tr></thead>
<tbody>
<?php foreach ($openTasks as $r):
  $can = work360_can_complete_task($conn, $user, $r);
?>
<tr>
  <td><a href="task-view.php?id=<?= (int)$r['task_id'] ?>"><?= work360_h((string)$r['task_code']) ?></a></td>
  <td><?= work360_h((string)$r['title']) ?></td>
  <td><?= work360_h((string)$r['department_name_fa']) ?></td>
  <td><?= work360_h((string)($r['assignee_name'] ?? '—')) ?></td>
  <td><?= work360_h(work360_role_fa((string)($r['assignee_role'] ?? ''))) ?></td>
  <td><?= work360_h(work360_status_fa((string)$r['status_code'])) ?></td>
  <td>
    <?php if (!empty($can['ok'])): ?>
    <form method="post" style="display:flex;gap:.25rem;align-items:center;flex-wrap:wrap"><?= work360_csrf_field() ?>
      <input type="hidden" name="complete_task_id" value="<?= (int)$r['task_id'] ?>">
      <?php if (!empty($can['note_required'])): ?>
      <input type="text" name="completion_note" placeholder="یادداشت" required style="width:140px">
      <?php endif; ?>
      <button class="m360-btn m360-btn-primary" style="margin:0;padding:.3rem .5rem;font-size:.75rem"><?= work360_h((string)$can['label_fa']) ?></button>
    </form>
    <?php else: ?>—<?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
<?php if ($openTasks === []): ?><tr><td colspan="7">کار بازی نیست.</td></tr><?php endif; ?>
</tbody></table></div>
<?php work360_layout_end(); ?>
