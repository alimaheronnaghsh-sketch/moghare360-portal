<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$id = (int)($_GET['id'] ?? 0);
$e = $id ? p360_employee_get($conn, $id) : null;
p360_layout_start('پروفایل پرسنل', 'employees.php');
if ($e) p360_table([$e], ['employee_code'=>'کد','first_name'=>'نام','last_name'=>'نام خانوادگی','hire_date'=>'استخدام']);
else echo '<p>یافت نشد.</p>';
p360_layout_end();