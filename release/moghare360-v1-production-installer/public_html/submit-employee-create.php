<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    hr_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('hr_employee_create', $_POST['erp_csrf_token'] ?? null);

$fullName = hr_post_string('full_name');
$mobile = hr_post_string('mobile');
$nationalCode = hr_post_string('national_code');

if ($fullName === '') {
    hr_error('خطای اعتبارسنجی', 'نام کامل الزامی است.');
}

$connection = false;

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.employee.write');

    if (!hr_table_exists($connection, 'erp_hr_employees')) {
        throw new RuntimeException('جدول erp_hr_employees یافت نشد. ابتدا SQL فاز ۷ را اجرا کنید.');
    }

    $dupWarning = hr_check_duplicate_employee($connection, $mobile, $nationalCode);
    $code = hr_generate_employee_code();

    $ok = hr_execute(
        $connection,
        'INSERT INTO dbo.erp_hr_employees (employee_code, full_name, mobile, national_code, birth_date, marital_status, children_count, emergency_contact_name, emergency_contact_mobile, department_name, position_title, hire_date, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $code, $fullName, $mobile ?: null, $nationalCode ?: null,
            hr_validate_date(hr_post_string('birth_date')),
            hr_post_string('marital_status') ?: null,
            hr_post_int('children_count'),
            hr_post_string('emergency_contact_name') ?: null,
            hr_post_string('emergency_contact_mobile') ?: null,
            hr_post_string('department_name') ?: null,
            hr_post_string('position_title') ?: null,
            hr_validate_date(hr_post_string('hire_date')),
            hr_post_string('notes') ?: null,
            hr_safe_current_user(),
        ]
    );
    if ($ok === false) throw new RuntimeException('ثبت کارمند انجام نشد.');

    $employeeId = hr_scope_identity($connection);
    $summary = 'ثبت کارمند جدید' . ($dupWarning !== '' ? ' — هشدار: ' . $dupWarning : '');
    hr_insert_history($connection, 'EMPLOYEE', $employeeId, 'CREATE', $summary, null, $code);
} catch (Throwable) {
    hr_error('خطا', 'ثبت کارمند انجام نشد.');
} finally {
    if ($connection !== false) @odbc_close($connection);
}

hr_safe_redirect('erp-employee-profile.php?employee_id=' . ($employeeId ?? '') . '&ok=employee_ok');
