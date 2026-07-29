<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
if (!work360_is_supervisor()) { http_response_code(403); echo 'دسترسی مجاز نیست.'; exit; }
$user = work360_current_user();
$conn = work360_db();
$today = work360_today();
$f = (string)($_GET['f'] ?? 'open');
$status = trim((string)($_GET['status'] ?? ''));
$assignee = (int)($_GET['assignee'] ?? 0);
$scope = work360_scope_sql($user, 't');
$where = $scope['sql'];
$params = $scope['params'];
if ($f === 'overdue') { $where .= ' AND t.due_date<? AND t.status_code IN (N\'TODO\',N\'IN_PROGRESS\',N\'WAITING\',N\'BLOCKED\')'; $params[] = $today; }
elseif ($f === 'today') { $where .= ' AND t.due_date=?'; $params[] = $today; }
else { $where .= ' AND t.status_code IN (N\'TODO\',N\'IN_PROGRESS\',N\'WAITING\',N\'BLOCKED\')'; }
if ($status !== '') { $where .= ' AND t.status_code=?'; $params[] = strtoupper($status); }
if ($assignee > 0) { $where .= ' AND t.assigned_to_user_id=?'; $params[] = $assignee; }

$flash = ''; $flashOk = true;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    work360_csrf_require();
    if (isset($_POST['complete_task_id'])) {
        $tid = (int)$_POST['complete_task_id'];
        $note = trim((string)($_POST['completion_note'] ?? ''));
        $res = work360_complete_task($conn, $user, $tid, $note);
        $flashOk = !empty($res['ok']);
        $flash = (string)($res['message'] ?? '');
        if ($flashOk) {
            header('Location: supervisor-board.php?f=' . urlencode($f));
            exit;
        }
    } elseif (isset($_POST['reassign_task_id'])) {
        $tid = (int)$_POST['reassign_task_id'];
        $to = (int)($_POST['new_assignee'] ?? 0);
        $task = work360_one($conn, 'SELECT TOP 1 * FROM dbo.work360_tasks WHERE task_id=?', [$tid]);
        if ($task && work360_can_access_task($user, $task) && $to > 0) {
            work360_exec($conn, 'UPDATE dbo.work360_tasks SET assigned_to_user_id=?, updated_at=SYSUTCDATETIME() WHERE task_id=?', [$to, $tid]);
            work360_exec($conn, 'INSERT INTO dbo.work360_task_followups (task_id, followup_by_user_id, followup_type, note) VALUES (?,?,N\'REASSIGN_NOTE\',?)', [$tid, (int)$user['user_id'], 'واگذاری مجدد']);
        }
        header('Location: supervisor-board.php');
        exit;
    }
}

$rows = work360_rows($conn, "SELECT TOP 150 t.*, d.department_name_fa, u.full_name AS assignee_name FROM dbo.work360_tasks t JOIN dbo.work360_departments d ON d.department_id=t.department_id LEFT JOIN dbo.work360_users u ON u.user_id=t.assigned_to_user_id WHERE $where ORDER BY t.due_date, t.priority_code DESC", $params);
$teamUsers = work360_is_manager()
    ? work360_rows($conn, 'SELECT user_id, full_name FROM dbo.work360_users WHERE is_active=1 ORDER BY full_name', [])
    : work360_rows($conn, 'SELECT user_id, full_name FROM dbo.work360_users WHERE is_active=1 AND department_id=? ORDER BY full_name', [(int)($user['department_id'] ?? 0)]);
work360_layout_start('برد سرپرست', 'supervisor-board.php');
work360_flash($flash, $flashOk);
?>
<div class="m360-toolbar">
  <a class="m360-btn m360-btn-secondary" href="?f=open">باز</a>
  <a class="m360-btn m360-btn-secondary" href="?f=today">امروز</a>
  <a class="m360-btn m360-btn-secondary" href="?f=overdue">عقب‌افتاده</a>
</div>
<div class="w360-table-wrap">
<table class="m360-table">
<thead><tr><th>کد</th><th>عنوان</th><th>واحد</th><th>مسئول</th><th>وضعیت</th><th>سررسید</th><th>پیگیری</th><th>اتمام</th><th>واگذاری</th></tr></thead>
<tbody>
<?php foreach ($rows as $r):
  $can = work360_can_complete_task($conn, $user, $r);
?>
<tr>
  <td><a href="task-view.php?id=<?= (int)$r['task_id'] ?>"><?= work360_h((string)$r['task_code']) ?></a></td>
  <td><?= work360_h((string)$r['title']) ?></td>
  <td><?= work360_h((string)$r['department_name_fa']) ?></td>
  <td><?= work360_h((string)($r['assignee_name'] ?? '—')) ?></td>
  <td><?= work360_h(work360_status_fa((string)$r['status_code'])) ?></td>
  <td><?= work360_h((string)$r['due_date']) ?></td>
  <td><a href="task-followup.php?id=<?= (int)$r['task_id'] ?>">پیگیری</a></td>
  <td>
    <?php if (!empty($can['ok'])): ?>
    <form method="post" style="display:flex;gap:.25rem;align-items:center;flex-wrap:wrap"><?= work360_csrf_field() ?>
      <input type="hidden" name="complete_task_id" value="<?= (int)$r['task_id'] ?>">
      <?php if (!empty($can['note_required'])): ?>
      <input type="text" name="completion_note" placeholder="یادداشت" required style="width:120px">
      <?php endif; ?>
      <button class="m360-btn m360-btn-primary" style="margin:0;padding:.3rem .5rem;font-size:.75rem"><?= work360_h((string)$can['label_fa']) ?></button>
    </form>
    <?php else: ?>—<?php endif; ?>
  </td>
  <td>
    <form method="post" style="display:flex;gap:.25rem;align-items:center"><?= work360_csrf_field() ?>
      <input type="hidden" name="reassign_task_id" value="<?= (int)$r['task_id'] ?>">
      <select name="new_assignee"><?php foreach ($teamUsers as $tu): ?><option value="<?= (int)$tu['user_id'] ?>"><?= work360_h((string)$tu['full_name']) ?></option><?php endforeach; ?></select>
      <button class="m360-btn m360-btn-secondary" style="margin:0;padding:.3rem .5rem;font-size:.75rem">واگذاری</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
<?php if ($rows === []): ?><tr><td colspan="9" class="m360-empty-state">موردی نیست.</td></tr><?php endif; ?>
</tbody></table></div>
<?php work360_layout_end(); ?>
