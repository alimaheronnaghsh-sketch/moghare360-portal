<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
$user = work360_current_user();
$conn = work360_db();
$id = (int)($_GET['id'] ?? $_POST['task_id'] ?? 0);
$task = work360_one($conn, 'SELECT TOP 1 * FROM dbo.work360_tasks WHERE task_id=?', [$id]);
if (!$task || !work360_can_access_task($user, $task)) { http_response_code(404); echo 'کار یافت نشد.'; exit; }
$msg = ''; $ok = true;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    work360_csrf_require();
    $type = strtoupper(trim((string)($_POST['followup_type'] ?? 'COMMENT')));
    $note = trim((string)($_POST['note'] ?? ''));
    $next = trim((string)($_POST['next_followup_date'] ?? '')) ?: null;
    if ($note === '') { $ok = false; $msg = 'یادداشت الزامی است.'; }
    else {
        work360_exec($conn, 'INSERT INTO dbo.work360_task_followups (task_id, followup_by_user_id, followup_type, note, next_followup_date) VALUES (?,?,?,?,?)',
            [$id, (int)$user['user_id'], $type, $note, $next]);
        header('Location: task-view.php?id=' . $id);
        exit;
    }
}
work360_layout_start('ثبت پیگیری', 'task-followup.php');
work360_flash($msg, $ok);
?>
<form method="post" class="m360-card m360-form">
<?= work360_csrf_field() ?>
<input type="hidden" name="task_id" value="<?= $id ?>">
<p>کار: <?= work360_h((string)$task['title']) ?></p>
<label>نوع پیگیری</label>
<select name="followup_type">
<?php foreach (['COMMENT','REMINDER','ESCALATION','BLOCKER','APPROVAL_NOTE','REASSIGN_NOTE'] as $t): ?>
<option value="<?= $t ?>"><?= $t ?></option>
<?php endforeach; ?>
</select>
<label>یادداشت</label><textarea name="note" required></textarea>
<label>تاریخ پیگیری بعدی</label><input type="date" name="next_followup_date">
<button class="m360-btn m360-btn-primary" type="submit">ثبت</button>
<a class="m360-btn m360-btn-secondary" href="task-view.php?id=<?= $id ?>">بازگشت</a>
</form>
<?php work360_layout_end(); ?>
