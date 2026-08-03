<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$uid = (int)(p360_current_user()['user_id'] ?? 0);
$msg = null; $ok = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    $r = p360_company_save($conn, $_POST, $uid);
    $ok = !empty($r['ok']); $msg = $r['message'] ?? ($ok ? 'ثبت شد.' : 'خطا');
}
$rows = p360_company_list($conn);
p360_layout_start('شرکت‌ها', 'companies.php');
p360_flash($msg, $ok);
p360_table($rows, ['company_code'=>'کد','company_name'=>'نام','tax_id'=>'شناسه مالیاتی']);
echo '<form class="m360-card m360-form" method="post">'; echo p360_csrf_field();
echo '<label>کد</label><input name="company_code" required><label>نام</label><input name="company_name" required><label>نام حقوقی</label><input name="legal_name"><button>ثبت شرکت</button></form>';
p360_layout_end();