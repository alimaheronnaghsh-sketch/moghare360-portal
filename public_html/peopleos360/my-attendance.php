<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-central-bridge.php';

p360hr_require_password_changed_for_cartable();
$emp = p360hr_employee_for_current_user();
p360hr_layout_start('حضور و غیاب من');
if ($emp === null) {
    echo '<p>پرونده متصل نیست.</p>';
    p360hr_layout_end();
    exit;
}
$eid = (int)($emp['employee_id'] ?? 0);
$conn = p360hr_odbc();

echo '<h2>قوانین شیفت پایه</h2>';
echo '<table class="m360-table"><thead><tr><th>دامنه</th><th>شروع</th><th>پایان</th><th>استراحت</th><th>دقایق برنامه‌ای</th><th>توضیح</th></tr></thead><tbody>';
$rs = @odbc_exec($conn, 'SELECT day_scope, start_time, end_time, break_minutes, break_label_fa, scheduled_minutes, friday_closed, requires_approval FROM dbo.p360_hr_shift_rules WHERE is_active=1');
if ($rs) {
    while ($r = odbc_fetch_array($rs)) {
        $note = ((int)($r['friday_closed'] ?? 0) === 1) ? 'جمعه تعطیل؛ حضور نیازمند تأیید' : ((string)($r['break_label_fa'] ?? ''));
        echo '<tr><td>' . p360hr_h((string)($r['day_scope'] ?? '')) . '</td><td>' . p360hr_h((string)($r['start_time'] ?? '')) . '</td><td>' . p360hr_h((string)($r['end_time'] ?? '')) . '</td><td>' . p360hr_h((string)($r['break_minutes'] ?? '')) . '</td><td>' . p360hr_h((string)($r['scheduled_minutes'] ?? '')) . '</td><td>' . p360hr_h($note) . '</td></tr>';
    }
}
echo '</tbody></table>';

echo '<h2>سوابق حضور</h2>';
echo '<table class="m360-table"><thead><tr><th>تاریخ/زمان</th><th>نوع</th></tr></thead><tbody>';
$rs2 = @odbc_prepare($conn, 'SELECT TOP 50 punch_at, punch_type FROM dbo.p360_raw_attendance_logs WHERE employee_id=? ORDER BY punch_at DESC');
$any = false;
if ($rs2 && @odbc_execute($rs2, [$eid])) {
    while ($r = odbc_fetch_array($rs2)) {
        $any = true;
        echo '<tr><td>' . p360hr_h((string)($r['punch_at'] ?? '')) . '</td><td>' . p360hr_h((string)($r['punch_type'] ?? '')) . '</td></tr>';
    }
}
if (!$any) {
    echo '<tr><td colspan="2" class="m360-empty-state">رکوردی ثبت نشده است.</td></tr>';
}
echo '</tbody></table>';
echo '<p class="m360-otp-note">حضور روز جمعه به‌صورت خودکار قابل‌پرداخت نیست و نیازمند درخواست «تأیید حضور روز جمعه» است.</p>';
p360hr_layout_end();
