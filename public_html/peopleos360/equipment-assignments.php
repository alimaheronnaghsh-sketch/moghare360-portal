<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) p360_equipment_assign($conn, (int)$_POST['equipment_id'], (int)$_POST['employee_id']);
$rows = p360_rows($conn, 'SELECT * FROM dbo.p360_equipment_assignments ORDER BY id DESC', []);
p360_layout_start('تحویل تجهیز', 'equipment.php');
p360_table($rows, ['equipment_id'=>'تجهیز','employee_id'=>'پرسنل','assignment_status'=>'وضعیت']);
p360_layout_end();