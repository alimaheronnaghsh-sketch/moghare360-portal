<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rows = p360_overtime_list($conn);
p360_layout_start('اضافه‌کار', 'self-service.php');
p360_table($rows, ['employee_id'=>'پرسنل','overtime_minutes'=>'دقیقه','request_status'=>'وضعیت']);
p360_layout_end();