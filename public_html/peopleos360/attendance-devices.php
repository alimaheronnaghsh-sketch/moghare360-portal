<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) p360_attendance_device_save($conn, (string)$_POST['device_code'], (string)$_POST['device_name'], null, $uid);
$rows = p360_rows($conn, 'SELECT * FROM dbo.p360_attendance_devices ORDER BY id DESC', []);
p360_layout_start('دستگاه حضور', 'attendance-devices.php');
p360_table($rows, ['device_code'=>'کد','device_name'=>'نام']);
echo '<form class="card" method="post">'.p360_csrf_field().'<label>کد</label><input name="device_code"><label>نام</label><input name="device_name"><button>ثبت</button></form>';
p360_layout_end();