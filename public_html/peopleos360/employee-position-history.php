<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
$eid = (int)($_GET['employee_id'] ?? $_POST['employee_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    p360_employee_add_position_history($conn, (int)$_POST['employee_id'], (int)($_POST['position_id'] ?? 0) ?: null, $_POST['position_title'] ?? null, (int)($_POST['department_id'] ?? 0) ?: null, (string)$_POST['effective_from'], $uid);
    $eid = (int)$_POST['employee_id'];
}
$rows = $eid ? p360_employee_position_history($conn, $eid) : [];
p360_layout_start('سابقه سمت', 'employees.php');
p360_table($rows, ['position_title'=>'سمت','effective_from'=>'از','effective_to'=>'تا']);
echo '<form class="m360-card m360-form" method="post">'.p360_csrf_field().'<label>employee_id</label><input name="employee_id" value="'.$eid.'"><label>position_id</label><input name="position_id"><label>effective_from</label><input name="effective_from" required><button>افزودن</button></form>';
p360_layout_end();