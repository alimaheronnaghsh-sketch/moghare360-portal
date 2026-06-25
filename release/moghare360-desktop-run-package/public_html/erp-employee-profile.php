<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

$employeeId = hr_get_int('employee_id');
$search = hr_get_string('q');
$connection = false;
$errorMessage = '';
$employee = null;
$employees = [];
$contracts = [];
$attendance = [];
$payrolls = [];
$trainings = [];
$disciplinary = [];
$flash = hr_flash(hr_get_string('ok'));

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.employee.view');

    if ($employeeId !== null) {
        $employee = hr_get_employee_preview($connection, $employeeId);
        if ($employee !== null) {
            $contracts = hr_fetch_rows($connection, 'SELECT TOP 20 contract_id, contract_code, contract_type, contract_status, start_date, base_salary FROM dbo.erp_hr_employment_contracts WHERE employee_id=? ORDER BY contract_id DESC', [$employeeId]);
            $attendance = hr_fetch_rows($connection, 'SELECT TOP 10 attendance_id, attendance_date, net_work_hours, overtime_hours, attendance_status FROM dbo.erp_hr_attendance_records WHERE employee_id=? ORDER BY attendance_date DESC', [$employeeId]);
            $payrolls = hr_fetch_rows($connection, 'SELECT TOP 10 payroll_preview_id, payroll_period_start, payroll_period_end, net_preview_amount, preview_status FROM dbo.erp_hr_payroll_previews WHERE employee_id=? ORDER BY payroll_preview_id DESC', [$employeeId]);
            $trainings = hr_fetch_rows($connection, 'SELECT TOP 10 training_id, training_title, training_type, result_status FROM dbo.erp_hr_training_records WHERE employee_id=? ORDER BY training_id DESC', [$employeeId]);
            $disciplinary = hr_fetch_rows($connection, 'SELECT TOP 10 disciplinary_id, record_type, record_title, severity_level, record_date FROM dbo.erp_hr_disciplinary_records WHERE employee_id=? ORDER BY disciplinary_id DESC', [$employeeId]);
        }
    } elseif ($search !== '' && hr_table_exists($connection, 'erp_hr_employees')) {
        $employees = hr_fetch_rows($connection, 'SELECT TOP 30 employee_id, employee_code, full_name, mobile, employment_status, department_name FROM dbo.erp_hr_employees WHERE full_name LIKE ? OR mobile LIKE ? OR employee_code LIKE ? ORDER BY employee_id DESC', ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%']);
    } elseif (hr_table_exists($connection, 'erp_hr_employees')) {
        $employees = hr_fetch_rows($connection, 'SELECT TOP 30 employee_id, employee_code, full_name, mobile, employment_status, department_name FROM dbo.erp_hr_employees ORDER BY employee_id DESC');
    }
} catch (Throwable) {
    $errorMessage = 'پروفایل پرسنلی قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

hr_render_head('پروفایل پرسنلی', true);
echo '<div class="p7hr-hero"><h1>پروفایل پرسنلی</h1><p>نمای داخلی پرونده کارمند</p></div>';
if ($flash !== '') echo '<div class="p1cc-card p1cc-success"><p>' . hr_h($flash) . '</p></div>';
if ($errorMessage !== '') hr_error('پروفایل', $errorMessage);

echo '<div class="p1cc-card"><form method="get" class="p1cc-form-grid" style="align-items:end">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">جستجو</label><input class="p1cc-input" name="q" value="' . hr_h($search) . '" placeholder="نام، موبایل، کد"></div>';
echo '<button class="p1cc-btn" type="submit">جستجو</button></form></div>';

if ($employee === null && $employeeId === null) {
    if ($employees === []) {
        echo '<div class="p1cc-card"><p class="p1cc-hint">کارمندی یافت نشد. <a href="erp-employee-create.php">ثبت کارمند جدید</a></p></div>';
    } else {
        echo '<div class="p1cc-card"><h2 class="p7hr-section-title">لیست کارمندان</h2><table class="p1cc-table"><thead><tr><th>کد</th><th>نام</th><th>موبایل</th><th>دپارتمان</th><th>وضعیت</th><th></th></tr></thead><tbody>';
        foreach ($employees as $e) {
            $id = (int)($e['employee_id'] ?? 0);
            echo '<tr><td class="m360-ltr">' . hr_h($e['employee_code'] ?? '') . '</td><td>' . hr_h($e['full_name'] ?? '') . '</td><td class="m360-ltr">' . hr_h($e['mobile'] ?? '') . '</td><td>' . hr_h($e['department_name'] ?? '—') . '</td>';
            echo '<td><span class="p1cc-badge ' . hr_badge_class($e['employment_status'] ?? '') . '">' . hr_h($e['employment_status'] ?? '') . '</span></td>';
            echo '<td><a href="erp-employee-profile.php?employee_id=' . $id . '">مشاهده</a></td></tr>';
        }
        echo '</tbody></table></div>';
    }
} elseif ($employee === null) {
    echo '<div class="p1cc-card p1cc-error"><p>کارمند یافت نشد.</p></div>';
} else {
    echo '<div class="p1cc-card"><h2 class="p7hr-section-title">' . hr_h($employee['full_name'] ?? '') . ' <span class="m360-ltr">(' . hr_h($employee['employee_code'] ?? '') . ')</span></h2>';
    echo '<div class="p7hr-profile-grid">';
    foreach (['mobile'=>'موبایل','national_code'=>'کد ملی','department_name'=>'دپارتمان','position_title'=>'سمت','hire_date'=>'استخدام','employment_status'=>'وضعیت'] as $k=>$l) {
        echo '<div><strong>' . hr_h($l) . ':</strong> ' . hr_h($employee[$k] !== '' ? $employee[$k] : '—') . '</div>';
    }
    echo '</div>';
    echo '<p style="margin-top:1rem"><a class="p1cc-btn" href="erp-employment-contract.php?employee_id=' . (int)$employeeId . '">قرارداد</a> ';
    echo '<a class="p1cc-btn" href="erp-attendance-entry.php?employee_id=' . (int)$employeeId . '">حضور</a> ';
    echo '<a class="p1cc-btn" href="erp-payroll-preview.php?employee_id=' . (int)$employeeId . '">حقوق preview</a> ';
    echo '<a class="p1cc-btn" href="erp-hr-training-discipline.php?employee_id=' . (int)$employeeId . '">آموزش/انضباط</a></p></div>';

    hr_render_section_table('قراردادها', $contracts, ['contract_code'=>'کد','contract_type'=>'نوع','contract_status'=>'وضعیت','start_date'=>'شروع','base_salary'=>'حقوق پایه']);
    hr_render_section_table('حضور اخیر', $attendance, ['attendance_date'=>'تاریخ','net_work_hours'=>'خالص','overtime_hours'=>'اضافه‌کار','attendance_status'=>'وضعیت']);
    hr_render_section_table('پیش‌نمایش حقوق', $payrolls, ['payroll_period_start'=>'از','payroll_period_end'=>'تا','net_preview_amount'=>'خالص','preview_status'=>'وضعیت']);
    hr_render_section_table('آموزش', $trainings, ['training_title'=>'عنوان','training_type'=>'نوع','result_status'=>'نتیجه']);
    hr_render_section_table('ترفیع/تنبیه', $disciplinary, ['record_type'=>'نوع','record_title'=>'عنوان','severity_level'=>'شدت','record_date'=>'تاریخ']);
}

function hr_render_section_table(string $title, array $rows, array $cols): void
{
    echo '<div class="p1cc-card"><h2 class="p7hr-section-title">' . hr_h($title) . '</h2>';
    if ($rows === []) { echo '<p class="p1cc-hint">رکوردی نیست.</p></div>'; return; }
    echo '<table class="p1cc-table"><thead><tr>';
    foreach ($cols as $l) echo '<th>' . hr_h($l) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach (array_keys($cols) as $k) {
            $v = $row[$k] ?? '—';
            if ($k === 'base_salary' || $k === 'net_preview_amount') $v = hr_format_amount($v);
            echo '<td>' . hr_h((string)$v) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

hr_render_foot();
