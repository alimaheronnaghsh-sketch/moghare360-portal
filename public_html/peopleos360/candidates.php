<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    if (isset($_POST['hire_id'])) {
        $r = p360_candidate_hire($conn, (int)$_POST['hire_id'], ['employee_code'=>$_POST['employee_code']??('EMP-'.time()),'first_name'=>'','last_name'=>''], $uid);
        $msg = $r['message'] ?? 'استخدام';
    } else {
        p360_exec($conn, 'INSERT INTO dbo.p360_candidates (full_name, mobile, email) VALUES (?,?,?)', [$_POST['full_name']??'', $_POST['mobile']??null, $_POST['email']??null]);
    }
}
$rows = p360_candidate_list($conn);
p360_layout_start('کاندیدها', 'recruitment.php');
p360_flash($msg, true);
p360_table($rows, ['full_name'=>'نام','candidate_status'=>'وضعیت']);
echo '<form class="m360-card m360-form" method="post">'.p360_csrf_field().'<label>نام</label><input name="full_name"><button>کاندید جدید</button></form>';
p360_layout_end();