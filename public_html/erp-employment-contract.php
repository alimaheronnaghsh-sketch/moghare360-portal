<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

$employeeId = hr_get_int('employee_id');
$connection = false;
$errorMessage = '';
$employees = [];
$contracts = [];
$flash = hr_flash(hr_get_string('ok'));

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.contract.write');
    if (hr_table_exists($connection, 'erp_hr_employees')) {
        $employees = hr_fetch_rows($connection, 'SELECT employee_id, employee_code, full_name FROM dbo.erp_hr_employees ORDER BY employee_id DESC');
    }
    if ($employeeId !== null && hr_table_exists($connection, 'erp_hr_employment_contracts')) {
        $contracts = hr_fetch_rows($connection, 'SELECT TOP 20 contract_id, contract_code, contract_type, contract_status, start_date, end_date, base_salary FROM dbo.erp_hr_employment_contracts WHERE employee_id=? ORDER BY contract_id DESC', [$employeeId]);
    }
} catch (Throwable) {
    $errorMessage = 'صفحه قرارداد کاری قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

hr_render_head('قرارداد کاری');
echo '<div class="p7hr-hero"><h1>قرارداد کاری</h1><p>ثبت قرارداد پرسنلی — controlled write</p></div>';
if ($flash !== '') echo '<div class="p1cc-card p1cc-success"><p>' . hr_h($flash) . '</p></div>';
if ($errorMessage !== '') hr_error('قرارداد', $errorMessage);

echo '<form class="p1cc-card" method="post" action="submit-employment-contract.php">';
echo erp_csrf_input('hr_employment_contract');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کارمند *</label><select class="p1cc-select" name="employee_id" required>';
echo '<option value="">انتخاب کنید</option>';
foreach ($employees as $e) {
    $id = (int)($e['employee_id'] ?? 0);
    $sel = $employeeId === $id ? ' selected' : '';
    echo '<option value="' . $id . '"' . $sel . '>' . hr_h(($e['full_name'] ?? '') . ' (' . ($e['employee_code'] ?? '') . ')') . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نوع قرارداد *</label><select class="p1cc-select" name="contract_type" required>';
foreach (['PERMANENT'=>'دائم','TEMPORARY_MONTHLY'=>'موقت ماهانه','TEMPORARY_DAILY'=>'موقت روزانه','TEMPORARY_HOURLY'=>'ساعتی','PROJECT_BASED'=>'پروژه‌ای','REMOTE'=>'دورکاری'] as $v=>$l) {
    echo '<option value="' . hr_h($v) . '">' . hr_h($l) . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تاریخ شروع *</label><input class="p1cc-input m360-ltr" type="date" name="start_date" required></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تاریخ پایان</label><input class="p1cc-input m360-ltr" type="date" name="end_date"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">حقوق پایه</label><input class="p1cc-input m360-ltr" type="number" step="0.01" min="0" name="base_salary" value="0"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">مجموع مزایا</label><input class="p1cc-input m360-ltr" type="number" step="0.01" min="0" name="allowance_total" value="0"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label"><input type="checkbox" name="overtime_allowed" value="1" checked> اضافه‌کار مجاز</label></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label"><input type="checkbox" name="friday_work_allowed" value="1"> جمعه‌کاری مجاز</label></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label"><input type="checkbox" name="night_work_allowed" value="1"> شب‌کاری مجاز</label></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نحوه تسویه</label><select class="p1cc-select" name="settlement_mode">';
foreach (['MONTHLY_PREVIEW'=>'ماهانه preview','YEARLY_PREVIEW'=>'سالانه preview','MANUAL'=>'دستی'] as $v=>$l) {
    echo '<option value="' . hr_h($v) . '">' . hr_h($l) . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">وضعیت قرارداد</label><select class="p1cc-select" name="contract_status">';
foreach (['DRAFT'=>'پیش‌نویس','ACTIVE'=>'فعال','EXPIRED'=>'منقضی','CANCELLED'=>'لغو','CLOSED'=>'بسته'] as $v=>$l) {
    echo '<option value="' . hr_h($v) . '">' . hr_h($l) . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">خلاصه شرایط</label><textarea class="p1cc-textarea" name="terms_summary" maxlength="2000"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت قرارداد</button></form>';

if ($contracts !== []) {
    echo '<div class="p1cc-card"><h2 class="p7hr-section-title">قراردادهای قبلی</h2><table class="p1cc-table"><thead><tr><th>کد</th><th>نوع</th><th>وضعیت</th><th>شروع</th><th>پایان</th><th>حقوق</th></tr></thead><tbody>';
    foreach ($contracts as $c) {
        echo '<tr><td class="m360-ltr">' . hr_h($c['contract_code'] ?? '') . '</td><td>' . hr_h($c['contract_type'] ?? '') . '</td>';
        echo '<td><span class="p1cc-badge ' . hr_badge_class($c['contract_status'] ?? '') . '">' . hr_h($c['contract_status'] ?? '') . '</span></td>';
        echo '<td class="m360-ltr">' . hr_h($c['start_date'] ?? '') . '</td><td class="m360-ltr">' . hr_h($c['end_date'] !== '' ? $c['end_date'] : '—') . '</td>';
        echo '<td class="m360-num">' . hr_h(hr_format_amount($c['base_salary'] ?? '0')) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

hr_render_foot();
