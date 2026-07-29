<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$uid = (int)(p360_current_user()['user_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    p360_exit_create($conn, [
        'employee_id' => (int)$_POST['employee_id'],
        'exit_type' => (string)$_POST['exit_type'],
        'exit_date' => (string)$_POST['exit_date'],
    ], $uid);
}
$rows = p360_rows($conn, 'SELECT * FROM dbo.p360_exit_cases ORDER BY id DESC', []);
p360_layout_start('خروج', 'exit-cases.php');
p360_table($rows, ['employee_id'=>'پرسنل','exit_type'=>'نوع','exit_date'=>'تاریخ','exit_status'=>'وضعیت']);
echo '<form class="card" method="post">'.p360_csrf_field().'<label>employee_id</label><input name="employee_id"><label>exit_type</label><input name="exit_type" value="resignation"><label>exit_date</label><input name="exit_date"><button>ثبت خروج</button></form>';
p360_layout_end();