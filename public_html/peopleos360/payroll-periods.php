<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    p360_payroll_period_create($conn, (string)$_POST['period_code'], (int)$_POST['jalali_year'], (int)$_POST['jalali_month'], (string)$_POST['start_date'], (string)$_POST['end_date'], $uid);
}
$rows = p360_rows($conn, 'SELECT * FROM dbo.p360_payroll_periods ORDER BY id DESC', []);
p360_layout_start('دوره حقوق', 'payroll-periods.php');
p360_table($rows, ['period_code'=>'کد','jalali_year'=>'سال','jalali_month'=>'ماه','period_status'=>'وضعیت']);
echo '<form class="m360-card m360-form" method="post">'.p360_csrf_field().'<label>period_code</label><input name="period_code"><label>jalali_year</label><input name="jalali_year"><label>jalali_month</label><input name="jalali_month"><label>start_date</label><input name="start_date"><label>end_date</label><input name="end_date"><button>ثبت</button></form>';
p360_layout_end();