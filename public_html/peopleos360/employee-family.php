<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn=p360_db();
$eid=(int)($_GET['employee_id']??0);
$rows=$eid?p360_rows($conn,'SELECT * FROM dbo.p360_employee_family WHERE employee_id=?',[$eid]):[];
p360_layout_start('employee-family','employees.php');
p360_table($rows, array_combine(array_keys($rows[0]??['id'=>'id']), array_keys($rows[0]??['id'=>'id'])));
p360_layout_end();
