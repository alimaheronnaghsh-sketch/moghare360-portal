<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
$eid = (int)($_GET['employee_id'] ?? $_POST['employee_id'] ?? 0);
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    $r = p360_employee_add_salary($conn, (int)$_POST['employee_id'], p360_money_parse($_POST['base_salary'] ?? '0'), (string)$_POST['effective_from'], $uid);
    $msg = !empty($r['ok']) ? 'ثبت شد (رکورد قبلی بسته شد).' : ($r['message'] ?? 'خطا');
    $eid = (int)$_POST['employee_id'];
}
$rows = $eid ? p360_employee_salary_history($conn, $eid) : [];
p360_layout_start('سابقه حقوق', 'employees.php');
p360_flash($msg, true);
p360_table($rows, ['base_salary'=>'حقوق','effective_from'=>'از','effective_to'=>'تا','change_reason'=>'دلیل']);
echo '<form class="m360-card m360-form" method="post">'.p360_csrf_field().'<label>employee_id</label><input name="employee_id" value="'.$eid.'" required><label>حقوق</label><input name="base_salary"><label>effective_from</label><input name="effective_from" required><button>افزودن</button></form>';
p360_layout_end();