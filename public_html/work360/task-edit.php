<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
$user = work360_current_user();
$conn = work360_db();
$id = (int)($_GET['id'] ?? $_POST['task_id'] ?? 0);
$task = work360_one($conn, 'SELECT TOP 1 * FROM dbo.work360_tasks WHERE task_id=?', [$id]);
if (!$task || !work360_can_access_task($user, $task)) { http_response_code(404); echo 'کار یافت نشد.'; exit; }
$msg = ''; $ok = true;
$depts = work360_is_manager()
    ? work360_rows($conn, 'SELECT * FROM dbo.work360_departments WHERE is_active=1 ORDER BY sort_order', [])
    : work360_visible_departments($conn, $user);
$users = work360_is_manager()
    ? work360_rows($conn, 'SELECT user_id, full_name FROM dbo.work360_users WHERE is_active=1 ORDER BY full_name', [])
    : work360_rows($conn, 'SELECT user_id, full_name FROM dbo.work360_users WHERE is_active=1 AND (department_id=? OR user_id=?) ORDER BY full_name', [(int)($user['department_id'] ?? 0), (int)$user['user_id']]);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    work360_csrf_require();
    $title = trim((string)($_POST['title'] ?? ''));
    $deptId = (int)($_POST['department_id'] ?? 0);
    $assign = (int)($_POST['assigned_to_user_id'] ?? 0) ?: null;
    $prio = strtoupper(trim((string)($_POST['priority_code'] ?? 'NORMAL')));
    $due = trim((string)($_POST['due_date'] ?? ''));
    $desc = trim((string)($_POST['description'] ?? '')) ?: null;
    if ($title === '' || $deptId < 1 || $due === '') { $ok = false; $msg = 'فیلدهای الزامی ناقص است.'; }
    else {
        work360_exec($conn, 'UPDATE dbo.work360_tasks SET title=?, description=?, department_id=?, assigned_to_user_id=?, priority_code=?, due_date=?, updated_at=SYSUTCDATETIME() WHERE task_id=?',
            [$title, $desc, $deptId, $assign, $prio, $due, $id]);
        header('Location: task-view.php?id=' . $id);
        exit;
    }
}
work360_layout_start('ویرایش کار', 'task-edit.php');
work360_flash($msg, $ok);
?>
<form method="post" class="m360-card m360-form">
<?= work360_csrf_field() ?>
<input type="hidden" name="task_id" value="<?= $id ?>">
<label>عنوان</label><input name="title" value="<?= work360_h((string)$task['title']) ?>" required>
<label>واحد</label><select name="department_id"><?php foreach ($depts as $d): ?><option value="<?= (int)$d['department_id'] ?>" <?= ((int)$d['department_id'] === (int)$task['department_id']) ? 'selected' : '' ?>><?= work360_h((string)$d['department_name_fa']) ?></option><?php endforeach; ?></select>
<label>مسئول</label><select name="assigned_to_user_id"><option value="">—</option><?php foreach ($users as $u): ?><option value="<?= (int)$u['user_id'] ?>" <?= ((int)$u['user_id'] === (int)($task['assigned_to_user_id'] ?? 0)) ? 'selected' : '' ?>><?= work360_h((string)$u['full_name']) ?></option><?php endforeach; ?></select>
<label>اولویت</label><select name="priority_code"><?php foreach (['LOW','NORMAL','HIGH','URGENT'] as $p): ?><option <?= $p === strtoupper((string)$task['priority_code']) ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?></select>
<label>سررسید</label><input type="date" name="due_date" value="<?= work360_h((string)$task['due_date']) ?>" required>
<label>توضیح</label><textarea name="description"><?= work360_h((string)($task['description'] ?? '')) ?></textarea>
<button class="m360-btn m360-btn-primary" type="submit">ذخیره</button>
</form>
<?php work360_layout_end(); ?>
