<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    $r = p360_leave_request_create($conn, (int)$_POST['employee_id'], (string)$_POST['leave_type'], (string)$_POST['from_at'], (string)$_POST['to_at'], (int)$_POST['minutes_count'], $uid, (int)$_POST['checker_user_id']);
    $msg = $r['message'] ?? '';
}
$rows = p360_rows($conn, 'SELECT * FROM dbo.p360_leave_requests ORDER BY id DESC', []);
p360_layout_start('مرخصی', 'leave-requests.php');
p360_flash($msg, true);
p360_table($rows, ['employee_id'=>'پرسنل','leave_type'=>'نوع','request_status'=>'وضعیت']);
echo '<form class="m360-card m360-form" method="post">'.p360_csrf_field().'<label>employee_id</label><input name="employee_id"><label>checker_user_id</label><input name="checker_user_id"><label>from_at</label><input name="from_at"><label>to_at</label><input name="to_at"><label>minutes</label><input name="minutes_count" value="480"><input type="hidden" name="leave_type" value="annual"><button>ثبت + workflow</button></form>';
p360_layout_end();