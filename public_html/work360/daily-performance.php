<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
$user = work360_current_user();
$conn = work360_db();
$today = work360_today();
$date = trim((string)($_GET['date'] ?? $today));
$deptFilter = (int)($_GET['department_id'] ?? 0);
$personFilter = (int)($_GET['user_id'] ?? 0);
$msg = ''; $ok = true;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['snapshot']) && work360_is_manager()) {
    work360_csrf_require();
    $depts = work360_rows($conn, 'SELECT department_id FROM dbo.work360_departments WHERE is_active=1', []);
    foreach ($depts as $d) {
        $m = work360_dept_metrics($conn, (int)$d['department_id'], $date);
        $total = max(1, $m['open'] + $m['doneToday']);
        $score = work360_performance_score($m['todayDue'], $m['doneToday'], $m['overdue'], $m['blocked'], $m['waiting']);
        work360_exec($conn, 'INSERT INTO dbo.work360_daily_performance_snapshots (snapshot_date, department_id, user_id, total_tasks, today_tasks, overdue_tasks, blocked_tasks, waiting_tasks, done_tasks, completion_rate, delay_rate, performance_score) VALUES (?,?,NULL,?,?,?,?,?,?,?,?,?)',
            [$date, (int)$d['department_id'], $m['open'] + $m['doneToday'], $m['todayDue'], $m['overdue'], $m['blocked'], $m['waiting'], $m['doneToday'], $m['completion'], $m['delay'], $score]);
    }
    $users = work360_rows($conn, 'SELECT user_id FROM dbo.work360_users WHERE is_active=1', []);
    foreach ($users as $u) {
        $pm = work360_user_metrics($conn, (int)$u['user_id'], $date);
        work360_exec($conn, 'INSERT INTO dbo.work360_daily_performance_snapshots (snapshot_date, department_id, user_id, total_tasks, today_tasks, overdue_tasks, blocked_tasks, waiting_tasks, done_tasks, completion_rate, delay_rate, performance_score) VALUES (?,NULL,?,?,?,?,?,?,?,?,?,?)',
            [$date, (int)$u['user_id'], $pm['todayTasks'] + $pm['overdue'] + $pm['done'], $pm['todayTasks'], $pm['overdue'], $pm['blocked'], $pm['waiting'], $pm['done'], $pm['todayTasks'] > 0 ? round($pm['done'] / max(1, $pm['todayTasks']) * 100, 1) : 0, $pm['overdue'], $pm['score']]);
    }
    $msg = 'Snapshot روزانه ثبت شد.';
}

$role = work360_role();
$people = [];
if ($role === 'STAFF') {
    $people = [['user_id' => (int)$user['user_id'], 'full_name' => (string)$user['full_name'], 'department_id' => $user['department_id'], 'department_name_fa' => (string)($user['department_name_fa'] ?? '')]];
} elseif ($role === 'SUPERVISOR') {
    $people = work360_rows($conn, 'SELECT u.user_id, u.full_name, u.department_id, d.department_name_fa FROM dbo.work360_users u LEFT JOIN dbo.work360_departments d ON d.department_id=u.department_id WHERE u.is_active=1 AND u.department_id=? ORDER BY u.full_name', [(int)($user['department_id'] ?? 0)]);
} else {
    $sql = 'SELECT u.user_id, u.full_name, u.department_id, d.department_name_fa FROM dbo.work360_users u LEFT JOIN dbo.work360_departments d ON d.department_id=u.department_id WHERE u.is_active=1';
    $params = [];
    if ($deptFilter > 0) { $sql .= ' AND u.department_id=?'; $params[] = $deptFilter; }
    if ($personFilter > 0) { $sql .= ' AND u.user_id=?'; $params[] = $personFilter; }
    $sql .= ' ORDER BY d.sort_order, u.full_name';
    $people = work360_rows($conn, $sql, $params);
}

$depts = work360_is_manager()
    ? work360_rows($conn, 'SELECT * FROM dbo.work360_departments WHERE is_active=1 ORDER BY sort_order', [])
    : work360_visible_departments($conn, $user);

work360_layout_start('عملکرد روزانه', 'daily-performance.php');
work360_flash($msg, $ok);
?>
<form method="get" class="m360-toolbar" style="gap:.5rem;align-items:end">
  <label>تاریخ<input type="date" name="date" value="<?= work360_h($date) ?>"></label>
  <?php if (work360_is_manager()): ?>
  <label>واحد<select name="department_id"><option value="0">همه</option><?php foreach ($depts as $d): ?><option value="<?= (int)$d['department_id'] ?>" <?= $deptFilter === (int)$d['department_id'] ? 'selected' : '' ?>><?= work360_h((string)$d['department_name_fa']) ?></option><?php endforeach; ?></select></label>
  <?php endif; ?>
  <button class="m360-btn m360-btn-secondary" type="submit">اعمال</button>
</form>
<?php if (work360_is_manager()): ?>
<form method="post" style="margin-bottom:1rem"><?= work360_csrf_field() ?><button class="m360-btn m360-btn-primary" name="snapshot" value="1">ثبت Snapshot روزانه</button></form>
<?php endif; ?>

<h2 style="font-size:1rem;margin:.5rem 0">واحدها</h2>
<div class="w360-lights">
<?php foreach ($depts as $d):
  if ($deptFilter > 0 && (int)$d['department_id'] !== $deptFilter) continue;
  $m = work360_dept_metrics($conn, (int)$d['department_id'], $date);
  $score = work360_performance_score($m['todayDue'], $m['doneToday'], $m['overdue'], $m['blocked'], $m['waiting']); ?>
  <div class="w360-light-card">
    <h3><span class="w360-light <?= work360_h($m['light']) ?>"></span><?= work360_h((string)$d['department_name_fa']) ?></h3>
    <?= work360_gauge_svg($score, 'امتیاز', $score >= 70 ? 'ok' : ($score >= 40 ? 'warn' : 'bad')) ?>
    <p style="font-size:.78rem;color:var(--m360-muted)">تکمیل <?= (float)$m['completion'] ?>% · تأخیر <?= (float)$m['delay'] ?>% · باز <?= (int)$m['open'] ?> · انجام <?= (int)$m['doneToday'] ?></p>
  </div>
<?php endforeach; ?>
</div>

<h2 style="font-size:1rem;margin:1rem 0 .5rem">افراد</h2>
<div class="w360-table-wrap">
<table class="m360-table">
<thead><tr><th>فرد</th><th>واحد</th><th>امروز</th><th>انجام</th><th>عقب‌افتاده</th><th>مسدود</th><th>منتظر</th><th>امتیاز</th></tr></thead>
<tbody>
<?php foreach ($people as $p):
  $pm = work360_user_metrics($conn, (int)$p['user_id'], $date); ?>
<tr>
  <td><?= work360_h((string)$p['full_name']) ?></td>
  <td><?= work360_h((string)($p['department_name_fa'] ?? '—')) ?></td>
  <td><?= (int)$pm['todayTasks'] ?></td>
  <td><?= (int)$pm['done'] ?></td>
  <td><?= (int)$pm['overdue'] ?></td>
  <td><?= (int)$pm['blocked'] ?></td>
  <td><?= (int)$pm['waiting'] ?></td>
  <td><?= work360_h((string)$pm['score']) ?></td>
</tr>
<?php endforeach; ?>
<?php if ($people === []): ?><tr><td colspan="8">موردی نیست.</td></tr><?php endif; ?>
</tbody></table></div>
<?php work360_layout_end(); ?>
