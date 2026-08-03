<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) p360_loan_create($conn, (int)$_POST['employee_id'], (float)$_POST['principal_amount'], (int)$_POST['installment_count'], null, $uid);
p360_layout_start('فرم وام', 'loans.php');
echo '<form class="m360-card m360-form" method="post">'.p360_csrf_field().'<label>employee_id</label><input name="employee_id"><label>principal</label><input name="principal_amount"><label>installments</label><input name="installment_count" value="12"><button>ثبت</button></form>';
p360_layout_end();