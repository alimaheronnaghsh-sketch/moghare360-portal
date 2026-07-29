<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
$msg = null; $ok = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    $r = p360_employee_save($conn, $_POST, $uid);
    $ok = !empty($r['ok']); $msg = $r['message'] ?? '';
}
p360_layout_start('فرم پرسنل', 'employees.php');
p360_flash($msg, $ok);
echo '<form class="card" method="post">'.p360_csrf_field().'<label>کد</label><input name="employee_code" required><label>نام</label><input name="first_name" required><label>نام خانوادگی</label><input name="last_name" required><label>company_id</label><input name="company_id"><button>ذخیره</button></form>';
p360_layout_end();