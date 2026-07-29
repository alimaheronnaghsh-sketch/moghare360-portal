<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) p360_equipment_save($conn, (string)$_POST['equipment_code'], (string)$_POST['equipment_name'], $_POST['serial_no']??null);
$rows = p360_equipment_list($conn);
p360_layout_start('تجهیزات', 'equipment.php');
p360_table($rows, ['equipment_code'=>'کد','equipment_name'=>'نام','serial_no'=>'سریال']);
echo '<form class="card" method="post">'.p360_csrf_field().'<label>کد</label><input name="equipment_code"><label>نام</label><input name="equipment_name"><button>ثبت</button></form>';
p360_layout_end();