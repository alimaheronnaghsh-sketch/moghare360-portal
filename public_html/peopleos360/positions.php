<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) p360_position_save($conn, $_POST, $uid);
$rows = p360_position_list($conn);
p360_layout_start('سمت‌ها', 'positions.php');
p360_table($rows, ['position_code'=>'کد','position_title'=>'عنوان','department_id'=>'واحد']);
echo '<form class="card" method="post">'.p360_csrf_field().'<label>کد</label><input name="position_code"><label>عنوان</label><input name="position_title"><label>department_id</label><input name="department_id"><button>ثبت</button></form>';
p360_layout_end();