<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    $r = p360_payroll_run_employee($conn, (int)$_POST['employee_id'], (int)$_POST['period_id'], $uid);
    $msg = 'net=' . ($r['net'] ?? '');
}
p360_layout_start('اجرای حقوق', 'payroll-slips.php');
p360_flash($msg, true);
echo '<form class="card" method="post">'.p360_csrf_field().'<label>employee_id</label><input name="employee_id"><label>period_id</label><input name="period_id"><button>محاسبه فیش</button></form>';
p360_layout_end();