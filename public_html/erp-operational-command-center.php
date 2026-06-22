<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-business-layer-helper.php';

$connection = false;
$errorMessage = '';
$stats = bl_fetch_operational_stats(false);

try {
    $connection = bl_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال برقرار نشد.');
    }
    bl_require_auth($connection, 'business.layer.operational');
    $stats = bl_fetch_operational_stats($connection);
} catch (Throwable) {
    $errorMessage = 'برخی آمار عملیاتی در دسترس نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

bl_render_head('مرکز عملیاتی');
echo '<div class="p8bl-hero"><h1>مرکز عملیاتی روزانه</h1><p>Operational Command Center — read-only</p></div>';
if ($errorMessage !== '') {
    echo '<div class="p1cc-card p1cc-error"><p>' . bl_h($errorMessage) . '</p></div>';
}

echo '<div class="p8bl-kpi-grid">';
$labels = [
    'operation_cases' => 'پرونده عملیاتی',
    'pending_followups' => 'پیگیری معوق',
    'unpaid_payments' => 'پرداخت ناقص/معوق',
    'stock_items' => 'اقلام انبار',
    'active_employees' => 'کارمند فعال',
];
foreach ($labels as $k => $l) {
    echo '<div class="p8bl-kpi"><div class="label">' . bl_h($l) . '</div><div class="value m360-num">' . bl_h($stats[$k]) . '</div></div>';
}
echo '</div>';

echo '<div class="p1cc-card"><h2 class="p8bl-section-title">دسترسی سریع</h2><div class="p1cc-nav-grid">';
$quick = [
    ['erp-customer-entry.php', 'ورود مشتری', 'Customer'],
    ['erp-operation-control-center.php', 'کنترل عملیات', 'Operation'],
    ['erp-technician-board.php', 'تابلوی تکنسین', 'Technician'],
    ['erp-rule-decision-board.php', 'تابلو قواعد', 'Rule'],
    ['erp-stock-board.php', 'تابلوی انبار', 'Inventory'],
    ['erp-finance-control-center.php', 'کنترل مالی', 'Finance'],
    ['erp-crm-followup-board.php', 'پیگیری CRM', 'CRM'],
    ['erp-hr-dashboard.php', 'داشبورد HR', 'HR'],
    ['erp-management-dashboard.php', 'داشبورد مدیریت', 'Phase 9'],
    ['erp-kpi-report.php', 'گزارش KPI', 'Reporting'],
];
foreach ($quick as [$url, $title, $sub]) {
    if (bl_page_exists($url)) {
        echo '<a class="p1cc-nav-card" href="' . bl_h($url) . '"><span class="p1cc-nav-title">' . bl_h($title) . '</span><span class="p1cc-nav-sub">' . bl_h($sub) . '</span></a>';
    }
}
echo '</div></div>';

bl_render_foot();
