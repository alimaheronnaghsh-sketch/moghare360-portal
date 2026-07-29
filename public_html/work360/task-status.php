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
    $new = strtoupper(trim((string)($_POST['status_code'] ?? '')));
    $note = trim((string)($_POST['note'] ?? '')) ?: null;
    if (!in_array($new, ['TODO','IN_PROGRESS','WAITING','BLOCKED','DONE','CANCELLED'], true)) {
        $ok = false; $msg = 'وضعیت نامعتبر.';
    } else {
        work360_set_status($conn, $id, $new, (int)$user['user_id'], $note);
        header('Location: task-view.php?id=' . $id);
        exit;
    }
}
work360_layout_start('تغییر وضعیت', 'task-status.php');
work360_flash($msg, $ok);
?>
<form method="post" class="m360-card m360-form">
<?= work360_csrf_field() ?>
<input type="hidden" name="task_id" value="<?= $id ?>">
<p>کار: <?= work360_h((string)$task['title']) ?> · وضعیت فعلی: <?= work360_h(work360_status_fa((string)$task['status_code'])) ?></p>
<label>وضعیت جدید</label>
<select name="status_code">
<?php foreach (['TODO','IN_PROGRESS','WAITING','BLOCKED','DONE','CANCELLED'] as $s): ?>
<option value="<?= $s ?>" <?= $s === strtoupper((string)$task['status_code']) ? 'selected' : '' ?>><?= work360_h(work360_status_fa($s)) ?></option>
<?php endforeach; ?>
</select>
<label>یادداشت</label><textarea name="note"></textarea>
<button class="m360-btn m360-btn-primary" type="submit">ذخیره</button>
<a class="m360-btn m360-btn-secondary" href="task-view.php?id=<?= $id ?>">بازگشت</a>
</form>
<?php work360_layout_end(); ?>
