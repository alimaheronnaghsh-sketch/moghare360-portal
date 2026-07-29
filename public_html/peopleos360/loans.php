<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    p360_loan_create($conn, (int)$_POST['employee_id'], (float)$_POST['principal_amount'], (int)$_POST['installment_count'], null, $uid);
}
$rows = p360_loans_list($conn);
p360_layout_start('وام', 'loans.php');
p360_table($rows, ['employee_id'=>'پرسنل','principal_amount'=>'اصل','installment_count'=>'اقساط','loan_status'=>'وضعیت']);
echo '<a href="loan-form.php">فرم وام</a>';
p360_layout_end();