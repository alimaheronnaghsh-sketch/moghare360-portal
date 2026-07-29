<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$rows = p360_employee_list($conn);
p360_layout_start('پرسنل', 'employees.php');
p360_table($rows, ['employee_code'=>'کد','first_name'=>'نام','last_name'=>'نام خانوادگی','employee_status'=>'وضعیت']);
echo '<a href="employee-form.php">پرسنل جدید</a>';
p360_layout_end();