<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    hr_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('hr_payroll_preview', $_POST['erp_csrf_token'] ?? null);

$employeeId = hr_post_int('employee_id');
$periodStart = hr_validate_date(hr_post_string('payroll_period_start'));
$periodEnd = hr_validate_date(hr_post_string('payroll_period_end'));

if ($employeeId === null || $periodStart === null || $periodEnd === null) {
    hr_error('خطای اعتبارسنجی', 'کارمند و بازه زمانی الزامی است.');
}

$previewData = [
    'base_salary' => hr_post_float('base_salary') ?? 0.0,
    'allowance_total' => hr_post_float('allowance_total') ?? 0.0,
    'overtime_amount' => hr_post_float('overtime_amount') ?? 0.0,
    'friday_work_amount' => hr_post_float('friday_work_amount') ?? 0.0,
    'bonus_amount' => hr_post_float('bonus_amount') ?? 0.0,
    'deduction_amount' => hr_post_float('deduction_amount') ?? 0.0,
];
$calc = hr_calculate_payroll_preview($previewData);
$contractId = hr_post_int('contract_id');

$connection = false;

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.payroll.preview');

    if (!hr_table_exists($connection, 'erp_hr_payroll_previews')) {
        throw new RuntimeException('جدول erp_hr_payroll_previews یافت نشد.');
    }

    $ok = hr_execute(
        $connection,
        'INSERT INTO dbo.erp_hr_payroll_previews (employee_id, contract_id, payroll_period_start, payroll_period_end, base_salary, allowance_total, overtime_amount, friday_work_amount, bonus_amount, deduction_amount, gross_preview_amount, net_preview_amount, preview_status, preview_note, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $employeeId, $contractId,
            $periodStart, $periodEnd,
            $previewData['base_salary'], $previewData['allowance_total'],
            $previewData['overtime_amount'], $previewData['friday_work_amount'],
            $previewData['bonus_amount'], $previewData['deduction_amount'],
            $calc['gross_preview_amount'], $calc['net_preview_amount'],
            'CALCULATED',
            hr_post_string('preview_note') ?: null,
            hr_safe_current_user(),
        ]
    );
    if ($ok === false) throw new RuntimeException('ثبت پیش‌نمایش حقوق انجام نشد.');

    $previewId = hr_scope_identity($connection);
    hr_insert_history($connection, 'PAYROLL_PREVIEW', $previewId, 'CREATE', 'ثبت پیش‌نمایش حقوق داخلی', null, (string)$calc['net_preview_amount']);
} catch (Throwable) {
    hr_error('خطا', 'ثبت پیش‌نمایش حقوق انجام نشد.');
} finally {
    if ($connection !== false) @odbc_close($connection);
}

hr_safe_redirect('erp-payroll-preview.php?employee_id=' . $employeeId . '&ok=payroll_ok');
