<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

$employeeId = hr_get_int('employee_id');
$connection = false;
$errorMessage = '';
$employees = [];
$contracts = [];
$previews = [];
$flash = hr_flash(hr_get_string('ok'));

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.payroll.preview');
    if (hr_table_exists($connection, 'erp_hr_employees')) {
        $employees = hr_fetch_rows($connection, 'SELECT employee_id, employee_code, full_name FROM dbo.erp_hr_employees ORDER BY employee_id DESC');
    }
    if ($employeeId !== null && hr_table_exists($connection, 'erp_hr_employment_contracts')) {
        $contracts = hr_fetch_rows($connection, 'SELECT contract_id, contract_code, base_salary, allowance_total FROM dbo.erp_hr_employment_contracts WHERE employee_id=? ORDER BY contract_id DESC', [$employeeId]);
    }
    if (hr_table_exists($connection, 'erp_hr_payroll_previews')) {
        $previews = hr_fetch_rows($connection, 'SELECT TOP 20 p.payroll_preview_id, p.payroll_period_start, p.payroll_period_end, p.net_preview_amount, p.preview_status, e.full_name FROM dbo.erp_hr_payroll_previews p INNER JOIN dbo.erp_hr_employees e ON e.employee_id=p.employee_id ORDER BY p.payroll_preview_id DESC');
    }
} catch (Throwable) {
    $errorMessage = 'صفحه پیش‌نمایش حقوق قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

$defaultBase = '0';
$defaultAllow = '0';
if ($contracts !== [] && isset($contracts[0])) {
    $defaultBase = (string)($contracts[0]['base_salary'] ?? '0');
    $defaultAllow = (string)($contracts[0]['allowance_total'] ?? '0');
}

hr_render_head('پیش‌نمایش حقوق');
echo '<div class="p7hr-hero"><h1>پیش‌نمایش حقوق</h1><p>محاسبه داخلی — غیر رسمی</p></div>';
echo '<div class="p7hr-payroll-warning">این پیش‌نمایش داخلی حقوق است و فیش رسمی/سند قانونی نیست.</div>';
if ($flash !== '') echo '<div class="p1cc-card p1cc-success"><p>' . hr_h($flash) . '</p></div>';
if ($errorMessage !== '') hr_error('حقوق preview', $errorMessage);

echo '<form class="p1cc-card" method="post" action="submit-payroll-preview.php">';
echo erp_csrf_input('hr_payroll_preview');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کارمند *</label><select class="p1cc-select" name="employee_id" required><option value="">انتخاب</option>';
foreach ($employees as $e) {
    $id = (int)($e['employee_id'] ?? 0);
    $sel = $employeeId === $id ? ' selected' : '';
    echo '<option value="' . $id . '"' . $sel . '>' . hr_h(($e['full_name'] ?? '') . ' (' . ($e['employee_code'] ?? '') . ')') . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">قرارداد (اختیاری)</label><select class="p1cc-select" name="contract_id"><option value="">—</option>';
foreach ($contracts as $c) {
    echo '<option value="' . (int)($c['contract_id'] ?? 0) . '">' . hr_h($c['contract_code'] ?? '') . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">از تاریخ *</label><input class="p1cc-input m360-ltr" type="date" name="payroll_period_start" required></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تا تاریخ *</label><input class="p1cc-input m360-ltr" type="date" name="payroll_period_end" required></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">حقوق پایه</label><input class="p1cc-input m360-ltr" type="number" step="0.01" min="0" name="base_salary" value="' . hr_h($defaultBase) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">مزایا</label><input class="p1cc-input m360-ltr" type="number" step="0.01" min="0" name="allowance_total" value="' . hr_h($defaultAllow) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">اضافه‌کار</label><input class="p1cc-input m360-ltr" type="number" step="0.01" min="0" name="overtime_amount" value="0"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">جمعه‌کاری</label><input class="p1cc-input m360-ltr" type="number" step="0.01" min="0" name="friday_work_amount" value="0"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">پاداش</label><input class="p1cc-input m360-ltr" type="number" step="0.01" min="0" name="bonus_amount" value="0"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کسورات</label><input class="p1cc-input m360-ltr" type="number" step="0.01" min="0" name="deduction_amount" value="0"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="preview_note" maxlength="1500"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">محاسبه و ثبت preview</button></form>';

if ($previews !== []) {
    echo '<div class="p1cc-card"><h2 class="p7hr-section-title">پیش‌نمایش‌های ثبت‌شده</h2><table class="p1cc-table"><thead><tr><th>کارمند</th><th>از</th><th>تا</th><th>خالص preview</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($previews as $p) {
        echo '<tr><td>' . hr_h($p['full_name'] ?? '') . '</td>';
        echo '<td class="m360-ltr">' . hr_h($p['payroll_period_start'] ?? '') . '</td>';
        echo '<td class="m360-ltr">' . hr_h($p['payroll_period_end'] ?? '') . '</td>';
        echo '<td class="m360-num">' . hr_h(hr_format_amount($p['net_preview_amount'] ?? '0')) . '</td>';
        echo '<td><span class="p1cc-badge ' . hr_badge_class($p['preview_status'] ?? '') . '">' . hr_h($p['preview_status'] ?? '') . '</span></td></tr>';
    }
    echo '</tbody></table></div>';
}

hr_render_foot();
