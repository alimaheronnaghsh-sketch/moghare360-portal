<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
$user = work360_current_user();
$conn = work360_db();
$msg = ''; $ok = true;
$depts = work360_visible_departments($conn, $user);
if (work360_is_manager()) {
    $depts = work360_rows($conn, 'SELECT * FROM dbo.work360_departments WHERE is_active=1 ORDER BY sort_order', []);
}
$users = [];
if (work360_is_supervisor()) {
    if (work360_is_manager()) {
        $users = work360_rows($conn, 'SELECT user_id, full_name, department_id FROM dbo.work360_users WHERE is_active=1 ORDER BY full_name', []);
    } else {
        $deptId = (int)($user['department_id'] ?? 0);
        $users = work360_rows($conn, 'SELECT user_id, full_name, department_id FROM dbo.work360_users WHERE is_active=1 AND (department_id=? OR user_id=?) ORDER BY full_name', [$deptId, (int)$user['user_id']]);
    }
} else {
    $users = [['user_id' => (int)$user['user_id'], 'full_name' => (string)$user['full_name'], 'department_id' => $user['department_id']]];
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    work360_csrf_require();
    $title = trim((string)($_POST['title'] ?? ''));
    $deptId = (int)($_POST['department_id'] ?? 0);
    $assign = (int)($_POST['assigned_to_user_id'] ?? 0);
    $prio = strtoupper(trim((string)($_POST['priority_code'] ?? 'NORMAL')));
    $due = trim((string)($_POST['due_date'] ?? work360_today()));
    $dueTime = trim((string)($_POST['due_time'] ?? '')) ?: null;
    $source = trim((string)($_POST['source_type'] ?? 'MANUAL')) ?: 'MANUAL';
    $ref = trim((string)($_POST['reference_text'] ?? '')) ?: null;
    $expected = trim((string)($_POST['expected_result'] ?? '')) ?: null;
    $desc = trim((string)($_POST['description'] ?? '')) ?: null;
    $sup = (int)($_POST['supervisor_user_id'] ?? 0) ?: null;
    if ($title === '' || $deptId < 1 || $due === '') {
        $ok = false; $msg = 'عنوان، واحد و تاریخ سررسید الزامی است.';
    } else {
        if (!work360_is_supervisor()) {
            $assign = (int)$user['user_id'];
            $deptId = (int)($user['department_id'] ?? $deptId);
        }
        $code = work360_next_task_code($conn);
        $ins = work360_exec($conn, 'INSERT INTO dbo.work360_tasks (task_code,title,description,department_id,assigned_to_user_id,supervisor_user_id,created_by_user_id,priority_code,status_code,due_date,due_time,source_type,reference_text,expected_result) VALUES (?,?,?,?,?,?,?,?,N\'TODO\',?,?,?,?,?)',
            [$code, $title, $desc, $deptId, $assign ?: null, $sup, (int)$user['user_id'], $prio, $due, $dueTime, $source, $ref, $expected]);
        if ($ins) {
            $tid = (int)(work360_scalar($conn, 'SELECT TOP 1 task_id FROM dbo.work360_tasks WHERE task_code=?', [$code]) ?? 0);
            work360_exec($conn, 'INSERT INTO dbo.work360_task_status_history (task_id, old_status_code, new_status_code, changed_by_user_id, note) VALUES (?,NULL,N\'TODO\',?,N\'ایجاد کار\')', [$tid, (int)$user['user_id']]);
            header('Location: task-view.php?id=' . $tid);
            exit;
        }
        $ok = false; $msg = 'ثبت کار ناموفق بود.';
    }
}

$firstDeptCode = (string)($depts[0]['department_code'] ?? 'CRM_RECEPTION');
$suggestions = work360_suggestions($firstDeptCode);
work360_layout_start('ثبت کار جدید', 'task-create.php');
work360_flash($msg, $ok);
?>
<form method="post" class="m360-card m360-form" id="taskForm">
<?= work360_csrf_field() ?>
<label>عنوان</label><input name="title" id="title" required>
<div class="w360-suggest" id="suggestBox">
<?php foreach ($suggestions as $s): ?>
<button type="button" onclick="document.getElementById('title').value=this.textContent"><?= work360_h($s) ?></button>
<?php endforeach; ?>
</div>
<label>واحد</label>
<select name="department_id" required>
<?php foreach ($depts as $d): ?><option value="<?= (int)$d['department_id'] ?>"><?= work360_h((string)$d['department_name_fa']) ?></option><?php endforeach; ?>
</select>
<label>مسئول</label>
<select name="assigned_to_user_id">
<?php foreach ($users as $u): ?><option value="<?= (int)$u['user_id'] ?>" <?= ((int)$u['user_id'] === (int)$user['user_id']) ? 'selected' : '' ?>><?= work360_h((string)$u['full_name']) ?></option><?php endforeach; ?>
</select>
<label>سرپرست (اختیاری)</label>
<select name="supervisor_user_id"><option value="">—</option>
<?php foreach ($users as $u): ?><option value="<?= (int)$u['user_id'] ?>"><?= work360_h((string)$u['full_name']) ?></option><?php endforeach; ?>
</select>
<label>اولویت</label>
<select name="priority_code"><option>NORMAL</option><option>LOW</option><option>HIGH</option><option>URGENT</option></select>
<label>تاریخ سررسید</label><input type="date" name="due_date" value="<?= work360_h(work360_today()) ?>" required>
<label>ساعت سررسید</label><input type="time" name="due_time">
<label>منبع</label>
<select name="source_type"><option>MANUAL</option><option>CRM</option><option>RECEPTION</option><option>SERVICE_HALL</option><option>LOGISTICS</option><option>PROCUREMENT</option><option>FINANCE</option><option>FOREIGN_PURCHASE</option><option>OTHER</option></select>
<label>ارجاع متنی</label><input name="reference_text" placeholder="فقط متن مرجع — بدون اتصال ماژول">
<label>نتیجه مورد انتظار</label><input name="expected_result">
<label>توضیح</label><textarea name="description"></textarea>
<button type="submit" class="m360-btn m360-btn-primary">ثبت</button>
</form>
<?php work360_layout_end(); ?>
