<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
$user = work360_current_user();
$conn = work360_db();
$today = work360_today();
$f = (string)($_GET['f'] ?? 'open');
$scope = work360_scope_sql($user, 't');
$where = $scope['sql'];
$params = $scope['params'];
if ($f === 'today') { $where .= ' AND t.due_date=? AND t.status_code<>N\'CANCELLED\''; $params[] = $today; }
elseif ($f === 'overdue') { $where .= ' AND t.due_date<? AND t.status_code IN (N\'TODO\',N\'IN_PROGRESS\',N\'WAITING\',N\'BLOCKED\')'; $params[] = $today; }
elseif ($f === 'blocked') { $where .= ' AND t.status_code=N\'BLOCKED\''; }
elseif ($f === 'done') { $where .= ' AND t.status_code=N\'DONE\''; }
else { $where .= ' AND t.status_code IN (N\'TODO\',N\'IN_PROGRESS\',N\'WAITING\',N\'BLOCKED\')'; }

$flash = ''; $flashOk = true;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    work360_csrf_require();
    $tid = (int)($_POST['task_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $task = work360_one($conn, 'SELECT TOP 1 * FROM dbo.work360_tasks WHERE task_id=?', [$tid]);
    if ($task && work360_can_access_task($user, $task)) {
        if ($action === 'done') {
            $res = work360_complete_task($conn, $user, $tid, trim((string)($_POST['note'] ?? '')));
            $flashOk = !empty($res['ok']);
            $flash = (string)($res['message'] ?? '');
            if (!$flashOk) {
                // stay and show message
            } else {
                header('Location: my-tasks.php?f=' . urlencode($f));
                exit;
            }
        } else {
            $map = ['start'=>'IN_PROGRESS','waiting'=>'WAITING','blocked'=>'BLOCKED'];
            if (isset($map[$action])) {
                work360_set_status($conn, $tid, $map[$action], (int)$user['user_id'], trim((string)($_POST['note'] ?? '')) ?: null);
            }
            header('Location: my-tasks.php?f=' . urlencode($f));
            exit;
        }
    } else {
        $flashOk = false;
        $flash = 'شما مجوز اتمام این کار را ندارید.';
    }
}

$rows = work360_rows($conn, "SELECT TOP 100 t.*, d.department_name_fa FROM dbo.work360_tasks t JOIN dbo.work360_departments d ON d.department_id=t.department_id WHERE $where ORDER BY t.due_date, t.task_id DESC", $params);
work360_layout_start('کارهای من', 'my-tasks.php');
work360_flash($flash, $flashOk);
?>
<div class="m360-toolbar">
  <a class="m360-btn m360-btn-secondary" href="?f=open">باز</a>
  <a class="m360-btn m360-btn-secondary" href="?f=today">امروز</a>
  <a class="m360-btn m360-btn-secondary" href="?f=overdue">عقب‌افتاده</a>
  <a class="m360-btn m360-btn-secondary" href="?f=blocked">مسدود</a>
  <a class="m360-btn m360-btn-secondary" href="?f=done">انجام‌شده</a>
  <a class="m360-btn m360-btn-primary" href="task-create.php">ثبت کار</a>
</div>
<div class="w360-table-wrap">
<table class="m360-table">
<thead><tr><th>کد</th><th>عنوان</th><th>واحد</th><th>وضعیت</th><th>اولویت</th><th>سررسید</th><th>عملیات</th></tr></thead>
<tbody>
<?php foreach ($rows as $r):
  $can = work360_can_complete_task($conn, $user, $r);
?>
<tr>
  <td><a href="task-view.php?id=<?= (int)$r['task_id'] ?>"><?= work360_h((string)$r['task_code']) ?></a></td>
  <td><?= work360_h((string)$r['title']) ?></td>
  <td><?= work360_h((string)$r['department_name_fa']) ?></td>
  <td><?= work360_h(work360_status_fa((string)$r['status_code'])) ?></td>
  <td><?= work360_h(work360_priority_fa((string)$r['priority_code'])) ?></td>
  <td><?= work360_h((string)$r['due_date']) ?></td>
  <td>
    <?php if (!in_array(strtoupper((string)$r['status_code']), ['DONE','CANCELLED'], true)): ?>
    <form method="post" style="display:inline"><?= work360_csrf_field() ?><input type="hidden" name="task_id" value="<?= (int)$r['task_id'] ?>">
      <button name="action" value="start" class="m360-btn m360-btn-secondary" style="margin:0;padding:.3rem .5rem;font-size:.75rem">شروع</button>
      <button name="action" value="waiting" class="m360-btn m360-btn-secondary" style="margin:0;padding:.3rem .5rem;font-size:.75rem">منتظر</button>
      <button name="action" value="blocked" class="m360-btn m360-btn-secondary" style="margin:0;padding:.3rem .5rem;font-size:.75rem">مانع</button>
      <?php if (!empty($can['ok'])): ?>
      <button name="action" value="done" class="m360-btn m360-btn-primary" style="margin:0;padding:.3rem .5rem;font-size:.75rem"><?= work360_h((string)$can['label_fa']) ?></button>
      <?php endif; ?>
    </form>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
<?php if ($rows === []): ?><tr><td colspan="7" class="m360-empty-state">موردی نیست.</td></tr><?php endif; ?>
</tbody></table></div>
<?php work360_layout_end(); ?>
