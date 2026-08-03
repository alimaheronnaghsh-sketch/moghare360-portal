<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    p360_attendance_raw_log($conn, (int)$_POST['device_id'], (int)$_POST['employee_id'], (string)$_POST['punch_at'], (string)($_POST['punch_type']??'in'));
}
$rows = p360_rows($conn, 'SELECT TOP 50 * FROM dbo.p360_raw_attendance_logs ORDER BY id DESC', []);
p360_layout_start('لاگ خام حضور', 'attendance-devices.php');
p360_table($rows, ['employee_id'=>'پرسنل','punch_at'=>'زمان','punch_type'=>'نوع']);
echo '<form class="m360-card m360-form" method="post">'.p360_csrf_field().'<label>device_id</label><input name="device_id"><label>employee_id</label><input name="employee_id"><label>punch_at</label><input name="punch_at" placeholder="2026-01-15 08:00:00"><label>نوع</label><select name="punch_type"><option>in</option><option>out</option></select><button>ثبت</button></form>';
p360_layout_end();