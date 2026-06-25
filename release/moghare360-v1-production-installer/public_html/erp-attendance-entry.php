<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

$employeeId = hr_get_int('employee_id');
$connection = false;
$errorMessage = '';
$employees = [];
$recent = [];
$flash = hr_flash(hr_get_string('ok'));

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.attendance.write');
    if (hr_table_exists($connection, 'erp_hr_employees')) {
        $employees = hr_fetch_rows($connection, 'SELECT employee_id, employee_code, full_name FROM dbo.erp_hr_employees ORDER BY employee_id DESC');
    }
    if (hr_table_exists($connection, 'erp_hr_attendance_records')) {
        $recent = hr_fetch_rows($connection, 'SELECT TOP 20 a.attendance_id, a.attendance_date, a.net_work_hours, a.overtime_hours, a.attendance_status, e.full_name, e.employee_code FROM dbo.erp_hr_attendance_records a INNER JOIN dbo.erp_hr_employees e ON e.employee_id=a.employee_id ORDER BY a.attendance_id DESC');
    }
} catch (Throwable) {
    $errorMessage = 'صفحه حضور و غیاب قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

hr_render_head('حضور و غیاب');
echo '<div class="p7hr-hero"><h1>ثبت حضور و غیاب</h1><p>کارکرد روزانه — controlled write</p></div>';
if ($flash !== '') echo '<div class="p1cc-card p1cc-success"><p>' . hr_h($flash) . '</p></div>';
if ($errorMessage !== '') hr_error('حضور', $errorMessage);

echo '<form class="p1cc-card" method="post" action="submit-attendance-entry.php">';
echo erp_csrf_input('hr_attendance_entry');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کارمند *</label><select class="p1cc-select" name="employee_id" required><option value="">انتخاب</option>';
foreach ($employees as $e) {
    $id = (int)($e['employee_id'] ?? 0);
    $sel = $employeeId === $id ? ' selected' : '';
    echo '<option value="' . $id . '"' . $sel . '>' . hr_h(($e['full_name'] ?? '') . ' (' . ($e['employee_code'] ?? '') . ')') . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تاریخ *</label><input class="p1cc-input m360-ltr" type="date" name="attendance_date" required value="' . hr_h(date('Y-m-d')) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">ورود</label><input class="p1cc-input m360-ltr" type="time" name="check_in_time"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">خروج</label><input class="p1cc-input m360-ltr" type="time" name="check_out_time"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">استراحت (ساعت)</label><input class="p1cc-input m360-ltr" type="number" step="0.25" min="0" name="break_hours" value="0"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">ساعت مورد نیاز</label><input class="p1cc-input m360-ltr" type="number" step="0.25" min="0" name="required_hours" value="8"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label"><input type="checkbox" name="is_friday_or_holiday" value="1"> جمعه/تعطیل</label></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="notes" maxlength="1000"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت حضور</button></form>';

if ($recent !== []) {
    echo '<div class="p1cc-card"><h2 class="p7hr-section-title">آخرین رکوردها</h2><table class="p1cc-table"><thead><tr><th>کارمند</th><th>تاریخ</th><th>خالص</th><th>اضافه‌کار</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($recent as $r) {
        echo '<tr><td>' . hr_h($r['full_name'] ?? '') . ' <span class="m360-ltr">(' . hr_h($r['employee_code'] ?? '') . ')</span></td>';
        echo '<td class="m360-ltr">' . hr_h($r['attendance_date'] ?? '') . '</td>';
        echo '<td class="m360-num">' . hr_h($r['net_work_hours'] ?? '0') . '</td>';
        echo '<td class="m360-num">' . hr_h($r['overtime_hours'] ?? '0') . '</td>';
        echo '<td><span class="p1cc-badge ' . hr_badge_class($r['attendance_status'] ?? '') . '">' . hr_h($r['attendance_status'] ?? '') . '</span></td></tr>';
    }
    echo '</tbody></table></div>';
}

hr_render_foot();
