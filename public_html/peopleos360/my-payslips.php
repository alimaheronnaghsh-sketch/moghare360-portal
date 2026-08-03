<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-central-bridge.php';

p360hr_require_password_changed_for_cartable();
$emp = p360hr_employee_for_current_user();
p360hr_layout_start('فیش‌های حقوقی من');
if ($emp === null) {
    echo '<p>پرونده متصل نیست.</p>';
    p360hr_layout_end();
    exit;
}
$eid = (int)($emp['employee_id'] ?? 0);
$conn = p360hr_odbc();
echo '<p>فقط فیش‌های وضعیت‌های قابل‌نمایش برای خودتان نمایش داده می‌شود. مقادیر ساختگی ایجاد نمی‌شود.</p>';
echo '<table class="m360-table"><thead><tr><th>دوره</th><th>ناخالص</th><th>کسور</th><th>خالص</th><th>وضعیت</th></tr></thead><tbody>';
$rs = @odbc_prepare($conn, "SELECT TOP 50 period_id, gross_amount, deduction_amount, net_amount, slip_status FROM dbo.p360_payroll_slips WHERE employee_id=? AND slip_status IN (N'VISIBLE_TO_EMPLOYEE', N'APPROVED', N'LOCKED', N'PAID', N'calculated', N'CALCULATED') ORDER BY id DESC");
$any = false;
if ($rs && @odbc_execute($rs, [$eid])) {
    while ($r = odbc_fetch_array($rs)) {
        $any = true;
        echo '<tr><td>' . p360hr_h((string)($r['period_id'] ?? '')) . '</td><td>' . p360hr_h((string)($r['gross_amount'] ?? '')) . '</td><td>' . p360hr_h((string)($r['deduction_amount'] ?? '')) . '</td><td>' . p360hr_h((string)($r['net_amount'] ?? '')) . '</td><td>' . p360hr_h((string)($r['slip_status'] ?? '')) . '</td></tr>';
    }
}
if (!$any) {
    echo '<tr><td colspan="5" class="m360-empty-state">فیش قابل‌نمایش وجود ندارد.</td></tr>';
}
echo '</tbody></table>';
p360hr_layout_end();
