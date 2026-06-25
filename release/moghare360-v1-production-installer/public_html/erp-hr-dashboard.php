<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-hr-helper.php';

$connection = false;
$errorMessage = '';
$stats = array_fill_keys(['employees','active','exited','suspended','contracts_active','contracts_draft','attendance','payroll','training','disciplinary'], '0');

try {
    $connection = hr_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    hr_require_auth($connection, 'hr.dashboard.view');

    if (hr_table_exists($connection, 'erp_hr_employees')) {
        $stats['employees'] = hr_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_hr_employees') ?? '0';
        $stats['active'] = hr_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_hr_employees WHERE employment_status='ACTIVE'") ?? '0';
        $stats['exited'] = hr_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_hr_employees WHERE employment_status='EXITED'") ?? '0';
        $stats['suspended'] = hr_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_hr_employees WHERE employment_status='SUSPENDED'") ?? '0';
    }
    if (hr_table_exists($connection, 'erp_hr_employment_contracts')) {
        $stats['contracts_active'] = hr_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_hr_employment_contracts WHERE contract_status='ACTIVE'") ?? '0';
        $stats['contracts_draft'] = hr_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_hr_employment_contracts WHERE contract_status='DRAFT'") ?? '0';
    }
    if (hr_table_exists($connection, 'erp_hr_attendance_records')) {
        $stats['attendance'] = hr_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_hr_attendance_records') ?? '0';
    }
    if (hr_table_exists($connection, 'erp_hr_payroll_previews')) {
        $stats['payroll'] = hr_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_hr_payroll_previews') ?? '0';
    }
    if (hr_table_exists($connection, 'erp_hr_training_records')) {
        $stats['training'] = hr_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_hr_training_records') ?? '0';
    }
    if (hr_table_exists($connection, 'erp_hr_disciplinary_records')) {
        $stats['disciplinary'] = hr_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_hr_disciplinary_records') ?? '0';
    }
} catch (Throwable) {
    $errorMessage = 'داشبورد HR قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

hr_render_head('داشبورد HR', true);
echo '<div class="p7hr-hero"><h1>داشبورد منابع انسانی</h1><p>پورتال اداری داخلی — بدون پرداخت واقعی حقوق</p></div>';
if ($errorMessage !== '') hr_error('داشبورد HR', $errorMessage);

echo '<div class="p1cc-card"><h2 class="p7hr-section-title">خلاصه</h2><div class="p7hr-kpi-grid">';
$labels = ['employees'=>'کارمندان','active'=>'فعال','exited'=>'خروج','suspended'=>'تعلیق','contracts_active'=>'قرارداد فعال','contracts_draft'=>'قرارداد پیش‌نویس','attendance'=>'حضور','payroll'=>'حقوق preview','training'=>'آموزش','disciplinary'=>'ترفیع/تنبیه'];
foreach ($labels as $k => $l) {
    echo '<div class="p7hr-kpi"><div class="label">' . hr_h($l) . '</div><div class="value m360-num">' . hr_h($stats[$k]) . '</div></div>';
}
echo '</div></div>';

echo '<div class="p1cc-card"><h2 class="p7hr-section-title">دسترسی سریع</h2><div class="p1cc-nav-grid">';
$links = [
    ['erp-employee-create.php','ثبت کارمند','فرم controlled'],
    ['erp-employee-profile.php','پروفایل پرسنلی','جستجو و نمایش'],
    ['erp-employment-contract.php','قرارداد کاری','ثبت قرارداد'],
    ['erp-attendance-entry.php','حضور و غیاب','ثبت کارکرد'],
    ['erp-payroll-preview.php','پیش‌نمایش حقوق','غیر رسمی'],
    ['erp-hr-training-discipline.php','آموزش و انضباط','ثبت رکورد'],
];
foreach ($links as [$url,$title,$sub]) {
    echo '<a class="p1cc-nav-card" href="' . hr_h($url) . '"><span class="p1cc-nav-title">' . hr_h($title) . '</span><span class="p1cc-nav-sub">' . hr_h($sub) . '</span></a>';
}
echo '</div></div>';
hr_render_foot();
