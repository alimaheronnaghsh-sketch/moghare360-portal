<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-business-ready-helper.php';

$connection = false;
$errorMessage = '';
$kpi = business_ready_get_kpi_summary(false);

try {
    $connection = business_ready_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    br_require_auth($connection, 'business.ready.dashboard');
    $kpi = business_ready_get_kpi_summary($connection);
} catch (Throwable) {
    $errorMessage = 'داشبورد مدیریت قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

br_render_head('داشبورد مدیریت MOGHARE360');
echo '<div class="p9br-hero"><h1>داشبورد مدیریت MOGHARE360</h1><p>Management Reporting — read-only</p></div>';
if ($errorMessage !== '') echo '<div class="p1cc-card p1cc-error"><p>' . br_h($errorMessage) . '</p></div>';

echo '<div class="p9br-kpi-grid">';
$cards = [
    'operation_open' => 'عملیات باز', 'operation_ready' => 'آماده تحویل', 'operation_delivered' => 'تحویل‌شده',
    'waiting_approval' => 'انتظار تأیید', 'waiting_parts' => 'انتظار قطعه',
    'total_payable' => 'جمع قابل پرداخت', 'total_paid' => 'جمع پرداخت‌شده', 'total_remaining' => 'مانده',
    'crm_pending_followup' => 'پیگیری CRM معوق', 'active_employees' => 'کارمند فعال',
];
foreach ($cards as $k => $l) {
    $v = $kpi[$k] ?? 0;
    if (str_contains($k, 'total_')) $v = business_ready_format_amount($v);
    echo '<div class="p9br-kpi"><div class="label">' . br_h($l) . '</div><div class="value m360-num">' . br_h((string)$v) . '</div></div>';
}
echo '<div class="p9br-kpi p9br-kpi-highlight"><div class="label">Readiness Score</div><div class="value m360-num">' . br_h((string)($kpi['readiness_score'] ?? 0)) . '%</div></div>';
echo '</div>';

echo '<div class="p1cc-card"><h2 class="p9br-section-title">گزارش‌های مدیریتی</h2><div class="p1cc-nav-grid">';
$reports = [
    ['erp-kpi-report.php', 'گزارش KPI', 'KPI و snapshot'],
    ['erp-operation-performance-report.php', 'عملکرد عملیات', 'Stage / QC / Delivery'],
    ['erp-financial-preview-report.php', 'پیش‌نمایش مالی', 'غیر رسمی'],
    ['erp-crm-report.php', 'گزارش CRM', 'پیگیری و VIP'],
    ['erp-inventory-pressure-report.php', 'فشار انبار', 'موجودی و خرید'],
    ['erp-staff-performance-preview.php', 'عملکرد پرسنل', 'HR preview'],
    ['erp-soft-run-audit.php', 'Soft Run Audit', 'چک‌لیست آمادگی'],
    ['moghare360-final-release-report.php', 'گزارش نهایی Commercial', 'Phase 10'],
];
foreach ($reports as [$url, $title, $sub]) {
    echo '<a class="p1cc-nav-card" href="' . br_h($url) . '"><span class="p1cc-nav-title">' . br_h($title) . '</span><span class="p1cc-nav-sub">' . br_h($sub) . '</span></a>';
}
echo '</div></div>';

br_render_foot();
