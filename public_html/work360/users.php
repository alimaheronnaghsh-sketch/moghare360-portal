<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
if (!work360_is_manager()) { http_response_code(403); echo 'دسترسی مجاز نیست.'; exit; }
$conn = work360_db();
$rows = work360_rows($conn, 'SELECT u.*, d.department_name_fa, s.full_name AS supervisor_name FROM dbo.work360_users u LEFT JOIN dbo.work360_departments d ON d.department_id=u.department_id LEFT JOIN dbo.work360_users s ON s.user_id=u.supervisor_user_id ORDER BY u.user_id', []);
work360_layout_start('کاربران Work360', 'users.php');
?>
<div class="m360-toolbar"><a class="m360-btn m360-btn-primary" href="user-form.php">کاربر جدید</a></div>
<table class="m360-table"><thead><tr><th>نام</th><th>نام کاربری</th><th>نقش</th><th>واحد</th><th>سرپرست</th><th>فعال</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr>
  <td><?= work360_h((string)$r['full_name']) ?></td>
  <td><?= work360_h((string)$r['username']) ?></td>
  <td><?= work360_h(work360_role_fa((string)$r['role_code'])) ?></td>
  <td><?= work360_h((string)($r['department_name_fa'] ?? '—')) ?></td>
  <td><?= work360_h((string)($r['supervisor_name'] ?? '—')) ?></td>
  <td><?= ((int)$r['is_active'] === 1) ? 'بله' : 'خیر' ?></td>
  <td><a href="user-form.php?id=<?= (int)$r['user_id'] ?>">ویرایش</a></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php work360_layout_end(); ?>
