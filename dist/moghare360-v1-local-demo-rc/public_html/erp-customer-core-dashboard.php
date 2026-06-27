<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 1 Customer Core Dashboard (read-only)
 */

require_once __DIR__ . '/includes/erp-customer-core-helper.php';

$connection = false;
$errorMessage = '';
$flashKey = customer_core_get_string('phase1');
$flashMessage = customer_core_flash_message($flashKey);

$stats = [
    'intakes' => '—',
    'contracts' => '—',
    'contracts_active' => '—',
    'contracts_accepted' => '—',
    'contracts_draft' => '—',
    'bindings' => '—',
    'duplicates' => '—',
];

try {
    $connection = customer_core_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    customer_core_require_auth_and_guard($connection, 'customer.core.dashboard.view');

    if (customer_core_table_exists($connection, 'erp_customer_intakes')) {
        $stats['intakes'] = customer_core_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_customer_intakes') ?? '0';
        $stats['duplicates'] = customer_core_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_customer_intakes WHERE duplicate_status = 'POSSIBLE_DUPLICATE'"
        ) ?? '0';
    }

    if (customer_core_table_exists($connection, 'erp_customer_contracts')) {
        $stats['contracts'] = customer_core_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_customer_contracts') ?? '0';
        $stats['contracts_active'] = customer_core_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_customer_contracts WHERE status = 'ACTIVE'"
        ) ?? '0';
        $stats['contracts_accepted'] = customer_core_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_customer_contracts WHERE status = 'ACCEPTED'"
        ) ?? '0';
        $stats['contracts_draft'] = customer_core_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_customer_contracts WHERE status = 'DRAFT'"
        ) ?? '0';
    }

    if (customer_core_table_exists($connection, 'erp_customer_vehicle_bindings')) {
        $stats['bindings'] = customer_core_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_customer_vehicle_bindings') ?? '0';
    }
} catch (Throwable) {
    $errorMessage = 'نمایش داشبورد هسته مشتریان با خطا مواجه شد.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

customer_core_render_head('داشبورد هسته مشتریان', true);

echo '<div class="p1cc-hero">';
echo '<h1>داشبورد هسته مشتریان</h1>';
echo '<p>نمای کلی ورود مشتری، قرارداد، پروفایل داخلی و اتصال خودرو — فاز ۱</p>';
echo '</div>';

if ($flashMessage !== '') {
    echo '<div class="p1cc-flash">' . customer_core_h($flashMessage) . '</div>';
}

if ($errorMessage !== '') {
    echo '<div class="p1cc-card p1cc-error"><p>' . customer_core_h($errorMessage) . '</p></div>';
} else {
    echo '<div class="p1cc-card"><h2>خلاصه وضعیت</h2><div class="p1cc-kpi-grid">';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">ورود مشتری (Intake)</div><div class="p1cc-kpi-value">' . customer_core_h($stats['intakes']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">قراردادها</div><div class="p1cc-kpi-value">' . customer_core_h($stats['contracts']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">قرارداد فعال</div><div class="p1cc-kpi-value">' . customer_core_h($stats['contracts_active']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">قرارداد پذیرفته‌شده</div><div class="p1cc-kpi-value">' . customer_core_h($stats['contracts_accepted']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">پیش‌نویس قرارداد</div><div class="p1cc-kpi-value">' . customer_core_h($stats['contracts_draft']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">اتصال خودرو</div><div class="p1cc-kpi-value">' . customer_core_h($stats['bindings']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">احتمال تکراری</div><div class="p1cc-kpi-value">' . customer_core_h($stats['duplicates']) . '</div></div>';
    echo '</div></div>';

    echo '<div class="p1cc-card"><h2>دسترسی سریع</h2><div class="p1cc-nav-grid">';
    echo '<a class="p1cc-nav-card" href="erp-customer-entry.php"><span class="p1cc-nav-title">ورود مشتری</span><span class="p1cc-nav-sub">ثبت Intake جدید</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-customer-contract-create.php"><span class="p1cc-nav-title">ایجاد قرارداد</span><span class="p1cc-nav-sub">قرارداد و مجوزدهی</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-customer-profile.php"><span class="p1cc-nav-title">پروفایل مشتری</span><span class="p1cc-nav-sub">نمای داخلی فقط خواندنی</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-vehicle-binding.php"><span class="p1cc-nav-title">اتصال خودرو</span><span class="p1cc-nav-sub">Binding و متادیتای عکس</span></a>';
    echo '</div></div>';
}

customer_core_render_foot();
