<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    hr_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('hr_employment_contract', $_POST['erp_csrf_token'] ?? null);

$employeeId = hr_post_int('employee_id');
$contractType = hr_post_string('contract_type');
$startDate = hr_validate_date(hr_post_string('start_date'));

if ($employeeId === null || $contractType === '' || $startDate === null) {
    hr_error('خطای اعتبارسنجی', 'کارمند، نوع قرارداد و تاریخ شروع الزامی است.');
}

$connection = false;
$contractId = null;

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.contract.write');

    if (!hr_table_exists($connection, 'erp_hr_employment_contracts')) {
        throw new RuntimeException('جدول erp_hr_employment_contracts یافت نشد.');
    }

    $code = hr_generate_contract_code();
    $baseSalary = hr_post_float('base_salary') ?? 0.0;
    $allowance = hr_post_float('allowance_total') ?? 0.0;

    $ok = hr_execute(
        $connection,
        'INSERT INTO dbo.erp_hr_employment_contracts (employee_id, contract_code, contract_type, start_date, end_date, base_salary, allowance_total, overtime_allowed, friday_work_allowed, night_work_allowed, settlement_mode, contract_status, terms_summary, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $employeeId, $code, $contractType, $startDate,
            hr_validate_date(hr_post_string('end_date')),
            $baseSalary, $allowance,
            hr_post_bool('overtime_allowed') ? 1 : 0,
            hr_post_bool('friday_work_allowed') ? 1 : 0,
            hr_post_bool('night_work_allowed') ? 1 : 0,
            hr_post_string('settlement_mode') ?: 'MONTHLY_PREVIEW',
            hr_post_string('contract_status') ?: 'DRAFT',
            hr_post_string('terms_summary') ?: null,
            hr_safe_current_user(),
        ]
    );
    if ($ok === false) throw new RuntimeException('ثبت قرارداد انجام نشد.');

    $contractId = hr_scope_identity($connection);
    hr_insert_history($connection, 'CONTRACT', $contractId, 'CREATE', 'ثبت قرارداد کاری', null, $code);
} catch (Throwable) {
    hr_error('خطا', 'ثبت قرارداد انجام نشد.');
} finally {
    if ($connection !== false) @odbc_close($connection);
}

hr_safe_redirect('erp-employee-profile.php?employee_id=' . $employeeId . '&ok=contract_ok');
