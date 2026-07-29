<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    p360_attendance_calculate_day($conn, (int)$_POST['employee_id'], (string)$_POST['work_date']);
}
$rows = p360_rows($conn, 'SELECT TOP 50 * FROM dbo.p360_attendance_records ORDER BY id DESC', []);
p360_layout_start('کارکرد', 'attendance-records.php');
p360_table($rows, ['employee_id'=>'پرسنل','work_date'=>'تاریخ','worked_minutes'=>'دقیقه','record_status'=>'وضعیت']);
echo '<form class="m360-card m360-form" method="post">'.p360_csrf_field().'<label>employee_id</label><input name="employee_id"><label>work_date</label><input name="work_date"><button>محاسبه</button></form>';
p360_layout_end();