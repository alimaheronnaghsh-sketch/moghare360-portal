<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    $r = p360_contract_issue($conn, (int)$_POST['employee_id'], (int)$_POST['template_id'], (string)$_POST['contract_no'], (string)$_POST['start_date'], $_POST['end_date'] ?? null, ['employee_name'=>$_POST['employee_name']??'','salary'=>$_POST['salary']??''], $uid);
    $msg = $r['message'] ?? '';
}
p360_layout_start('صدور قرارداد', 'contract-templates.php');
p360_flash($msg, true);
echo '<form class="m360-card m360-form" method="post">'.p360_csrf_field().'<label>employee_id</label><input name="employee_id"><label>template_id</label><input name="template_id"><label>contract_no</label><input name="contract_no"><label>start_date</label><input name="start_date"><label>employee_name</label><input name="employee_name"><label>salary</label><input name="salary"><button>صدور + snapshot</button></form>';
p360_layout_end();