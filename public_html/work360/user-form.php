<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
if (!work360_is_manager()) { http_response_code(403); echo 'دسترسی مجاز نیست.'; exit; }
$conn = work360_db();
$id = (int)($_GET['id'] ?? 0);
$row = $id > 0 ? work360_one($conn, 'SELECT TOP 1 * FROM dbo.work360_users WHERE user_id=?', [$id]) : null;
$depts = work360_rows($conn, 'SELECT * FROM dbo.work360_departments WHERE is_active=1 ORDER BY sort_order', []);
$supers = work360_rows($conn, 'SELECT user_id, full_name FROM dbo.work360_users WHERE is_active=1 AND role_code IN (N\'SUPERVISOR\',N\'MANAGER\',N\'OWNER\') ORDER BY full_name', []);
$msg = ''; $ok = true;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    work360_csrf_require();
    $username = trim((string)($_POST['username'] ?? ''));
    $full = trim((string)($_POST['full_name'] ?? ''));
    $role = strtoupper(trim((string)($_POST['role_code'] ?? 'STAFF')));
    $dept = (int)($_POST['department_id'] ?? 0) ?: null;
    $sup = (int)($_POST['supervisor_user_id'] ?? 0) ?: null;
    $active = isset($_POST['is_active']) ? 1 : 0;
    $pass = (string)($_POST['password'] ?? '');
    if ($username === '' || $full === '' || !in_array($role, ['OWNER','MANAGER','SUPERVISOR','STAFF'], true)) {
        $ok = false; $msg = 'اطلاعات نامعتبر.';
    } else {
        if ($id > 0) {
            if ($pass !== '') {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                work360_exec($conn, 'UPDATE dbo.work360_users SET username=?, full_name=?, role_code=?, department_id=?, supervisor_user_id=?, is_active=?, password_hash=?, updated_at=SYSUTCDATETIME() WHERE user_id=?',
                    [$username, $full, $role, $dept, $sup, $active, $hash, $id]);
            } else {
                work360_exec($conn, 'UPDATE dbo.work360_users SET username=?, full_name=?, role_code=?, department_id=?, supervisor_user_id=?, is_active=?, updated_at=SYSUTCDATETIME() WHERE user_id=?',
                    [$username, $full, $role, $dept, $sup, $active, $id]);
            }
            header('Location: users.php'); exit;
        } else {
            if ($pass === '') { $ok = false; $msg = 'رمز عبور برای کاربر جدید الزامی است.'; }
            else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $ins = work360_exec($conn, 'INSERT INTO dbo.work360_users (username, password_hash, full_name, role_code, department_id, supervisor_user_id, is_active) VALUES (?,?,?,?,?,?,?)',
                    [$username, $hash, $full, $role, $dept, $sup, $active]);
                if ($ins) { header('Location: users.php'); exit; }
                $ok = false; $msg = 'ثبت ناموفق (احتمالاً نام کاربری تکراری).';
            }
        }
    }
}
work360_layout_start($id > 0 ? 'ویرایش کاربر' : 'کاربر جدید', 'user-form.php');
work360_flash($msg, $ok);
?>
<form method="post" class="m360-card m360-form">
<?= work360_csrf_field() ?>
<label>نام کامل</label><input name="full_name" required value="<?= work360_h((string)($row['full_name'] ?? '')) ?>">
<label>نام کاربری</label><input name="username" required value="<?= work360_h((string)($row['username'] ?? '')) ?>">
<label>رمز عبور<?= $id > 0 ? ' (خالی = بدون تغییر)' : '' ?></label><input type="password" name="password" <?= $id > 0 ? '' : 'required' ?>>
<label>نقش</label>
<select name="role_code">
<?php foreach (['OWNER','MANAGER','SUPERVISOR','STAFF'] as $r): ?>
<option value="<?= $r ?>" <?= strtoupper((string)($row['role_code'] ?? 'STAFF')) === $r ? 'selected' : '' ?>><?= work360_h(work360_role_fa($r)) ?></option>
<?php endforeach; ?>
</select>
<label>واحد</label>
<select name="department_id"><option value="">—</option>
<?php foreach ($depts as $d): ?><option value="<?= (int)$d['department_id'] ?>" <?= ((int)($row['department_id'] ?? 0) === (int)$d['department_id']) ? 'selected' : '' ?>><?= work360_h((string)$d['department_name_fa']) ?></option><?php endforeach; ?>
</select>
<label>سرپرست</label>
<select name="supervisor_user_id"><option value="">—</option>
<?php foreach ($supers as $s): ?><option value="<?= (int)$s['user_id'] ?>" <?= ((int)($row['supervisor_user_id'] ?? 0) === (int)$s['user_id']) ? 'selected' : '' ?>><?= work360_h((string)$s['full_name']) ?></option><?php endforeach; ?>
</select>
<label><input type="checkbox" name="is_active" <?= !isset($row) || (int)($row['is_active'] ?? 1) === 1 ? 'checked' : '' ?>> فعال</label>
<button class="m360-btn m360-btn-primary" type="submit">ذخیره</button>
<a class="m360-btn m360-btn-secondary" href="users.php">بازگشت</a>
</form>
<?php work360_layout_end(); ?>
