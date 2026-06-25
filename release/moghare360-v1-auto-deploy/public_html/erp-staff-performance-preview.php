<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-business-ready-helper.php';

$connection = false;
$errorMessage = '';
$data = business_ready_get_staff_preview(false);

try {
    $connection = business_ready_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    br_require_auth($connection, 'business.ready.report');
    $data = business_ready_get_staff_preview($connection);
} catch (Throwable) {
    $errorMessage = 'گزارش پرسنل قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

br_render_head('پیش‌نمایش عملکرد پرسنل');
echo '<div class="p9br-hero"><h1>پیش‌نمایش عملکرد پرسنل</h1><p>Staff Performance Preview</p></div>';
echo '<div class="p9br-warning">این فقط پیش‌نمایش مدیریتی است و فیش رسمی/سند قانونی نیست.</div>';
if ($errorMessage !== '') echo '<div class="p1cc-card p1cc-error"><p>' . br_h($errorMessage) . '</p></div>';

echo '<div class="p9br-kpi-grid">';
foreach ([
    'active' => 'کارمند فعال', 'attendance' => 'رکورد حضور', 'overtime_hours' => 'اضافه‌کار (ساعت)',
    'absence_hours' => 'غیبت (ساعت)', 'training' => 'آموزش', 'disciplinary' => 'تنبیه', 'reward' => 'پاداش/ترفیع',
    'payroll_net' => 'جمع حقوق preview',
] as $k => $l) {
    $v = $data[$k] ?? 0;
    if ($k === 'payroll_net') $v = business_ready_format_amount($v);
    echo '<div class="p9br-kpi"><div class="label">' . br_h($l) . '</div><div class="value m360-num">' . br_h((string)$v) . '</div></div>';
}
echo '</div>';

br_render_foot();
