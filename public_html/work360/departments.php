<?php
require_once __DIR__ . '/includes/bootstrap.php';
work360_require_login();
if (!work360_is_manager()) { http_response_code(403); echo 'دسترسی مجاز نیست.'; exit; }
$conn = work360_db();
$rows = work360_rows($conn, 'SELECT * FROM dbo.work360_departments ORDER BY sort_order', []);
work360_layout_start('واحدهای کاری', 'departments.php');
?>
<table class="m360-table"><thead><tr><th>کد</th><th>نام</th><th>ترتیب</th><th>فعال</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr>
  <td><?= work360_h((string)$r['department_code']) ?></td>
  <td><?= work360_h((string)$r['department_name_fa']) ?></td>
  <td><?= (int)$r['sort_order'] ?></td>
  <td><?= ((int)$r['is_active'] === 1) ? 'بله' : 'خیر' ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php work360_layout_end(); ?>
