<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rows = p360_rows($conn, 'SELECT * FROM dbo.p360_timesheets ORDER BY id DESC', []);
p360_layout_start('تایم‌شیت', 'timesheets.php');
p360_table($rows, ['employee_id'=>'پرسنل','period_id'=>'دوره','worked_minutes'=>'کار','timesheet_status'=>'وضعیت']);
p360_layout_end();