<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    hr_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('hr_attendance_entry', $_POST['erp_csrf_token'] ?? null);

$employeeId = hr_post_int('employee_id');
$attendanceDate = hr_validate_date(hr_post_string('attendance_date'));

if ($employeeId === null || $attendanceDate === null) {
    hr_error('خطای اعتبارسنجی', 'کارمند و تاریخ الزامی است.');
}

$checkIn = hr_post_string('check_in_time') ?: null;
$checkOut = hr_post_string('check_out_time') ?: null;
$breakHours = hr_post_float('break_hours') ?? 0.0;
$requiredHours = hr_post_float('required_hours') ?? 8.0;
$calc = hr_calculate_attendance_hours($checkIn, $checkOut, $breakHours, $requiredHours);

$connection = false;

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.attendance.write');

    if (!hr_table_exists($connection, 'erp_hr_attendance_records')) {
        throw new RuntimeException('جدول erp_hr_attendance_records یافت نشد.');
    }

    $ok = hr_execute(
        $connection,
        'INSERT INTO dbo.erp_hr_attendance_records (employee_id, attendance_date, check_in_time, check_out_time, work_hours, break_hours, net_work_hours, required_hours, overtime_hours, absence_hours, is_friday_or_holiday, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $employeeId, $attendanceDate, $checkIn, $checkOut,
            $calc['work_hours'], $breakHours, $calc['net_work_hours'], $requiredHours,
            $calc['overtime_hours'], $calc['absence_hours'],
            hr_post_bool('is_friday_or_holiday') ? 1 : 0,
            hr_post_string('notes') ?: null,
            hr_safe_current_user(),
        ]
    );
    if ($ok === false) throw new RuntimeException('ثبت حضور انجام نشد.');

    $attendanceId = hr_scope_identity($connection);
    hr_insert_history($connection, 'ATTENDANCE', $attendanceId, 'CREATE', 'ثبت حضور و غیاب', null, $attendanceDate);
} catch (Throwable) {
    hr_error('خطا', 'ثبت حضور انجام نشد.');
} finally {
    if ($connection !== false) @odbc_close($connection);
}

hr_safe_redirect('erp-attendance-entry.php?employee_id=' . $employeeId . '&ok=attendance_ok');
