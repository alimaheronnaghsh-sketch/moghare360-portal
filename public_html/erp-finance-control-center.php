<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 5 Finance Control Center (read-only)
 */

require_once __DIR__ . '/includes/erp-pricing-engine.php';

$connection = false;
$errorMessage = '';
$stats = [
    'headers' => '0',
    'payable' => '0',
    'paid' => '0',
    'remaining' => '0',
    'unpaid' => '0',
    'partial' => '0',
    'paid_count' => '0',
];

try {
    $connection = pricing_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    pricing_require_auth($connection, 'finance.dashboard.view');

    if (pricing_table_exists($connection, 'erp_jobcard_cost_headers')) {
        $stats['headers'] = pricing_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_jobcard_cost_headers WHERE calculation_status <> 'CANCELLED'") ?? '0';
        $agg = pricing_fetch_rows(
            $connection,
            "SELECT ISNULL(SUM(payable_total),0) AS tp, ISNULL(SUM(paid_total),0) AS tpd, ISNULL(SUM(remaining_total),0) AS tr,
                    SUM(CASE WHEN payment_status='UNPAID' THEN 1 ELSE 0 END) AS uc,
                    SUM(CASE WHEN payment_status='PARTIAL_PAID' THEN 1 ELSE 0 END) AS pc,
                    SUM(CASE WHEN payment_status IN ('PAID','OVERPAID') THEN 1 ELSE 0 END) AS pac
             FROM dbo.erp_jobcard_cost_headers WHERE calculation_status <> 'CANCELLED'"
        );
        if ($agg !== []) {
            $stats['payable'] = $agg[0]['tp'] ?? '0';
            $stats['paid'] = $agg[0]['tpd'] ?? '0';
            $stats['remaining'] = $agg[0]['tr'] ?? '0';
            $stats['unpaid'] = $agg[0]['uc'] ?? '0';
            $stats['partial'] = $agg[0]['pc'] ?? '0';
            $stats['paid_count'] = $agg[0]['pac'] ?? '0';
        }
    }
} catch (Throwable) {
    $errorMessage = 'مرکز کنترل مالی قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

pricing_render_head('مرکز کنترل مالی', true);

echo '<div class="p5fs-hero"><h1>مرکز کنترل مالی</h1><p>قیمت‌گذاری، هزینه JobCard، پرداخت و پیش‌نمایش داخلی</p></div>';

if ($errorMessage !== '') {
    pricing_error('مرکز کنترل مالی', $errorMessage);
}

echo '<div class="p1cc-card"><h2 class="p5fs-section-title">خلاصه مالی</h2><div class="p5fs-finance-grid">';
echo '<div class="p5fs-finance-card"><div class="label">سربرگ هزینه</div><div class="value m360-num">' . pricing_h($stats['headers']) . '</div></div>';
echo '<div class="p5fs-finance-card"><div class="label">جمع قابل پرداخت</div><div class="value m360-num">' . pricing_h(pricing_format_amount($stats['payable'])) . '</div></div>';
echo '<div class="p5fs-finance-card"><div class="label">جمع پرداخت‌شده</div><div class="value m360-num">' . pricing_h(pricing_format_amount($stats['paid'])) . '</div></div>';
echo '<div class="p5fs-finance-card"><div class="label">مانده</div><div class="value m360-num">' . pricing_h(pricing_format_amount($stats['remaining'])) . '</div></div>';
echo '<div class="p5fs-finance-card"><div class="label">پرداخت‌نشده</div><div class="value m360-num">' . pricing_h($stats['unpaid']) . '</div></div>';
echo '<div class="p5fs-finance-card"><div class="label">پرداخت جزئی</div><div class="value m360-num">' . pricing_h($stats['partial']) . '</div></div>';
echo '<div class="p5fs-finance-card"><div class="label">تسویه‌شده</div><div class="value m360-num">' . pricing_h($stats['paid_count']) . '</div></div>';
echo '</div></div>';

echo '<div class="p1cc-card"><h2 class="p5fs-section-title">دسترسی سریع</h2><div class="p1cc-nav-grid">';
echo '<a class="p1cc-nav-card" href="erp-service-price-list.php"><span class="p1cc-nav-title">لیست قیمت خدمات</span><span class="p1cc-nav-sub">خدمت، اجرت، حاشیه قطعه</span></a>';
echo '<a class="p1cc-nav-card" href="erp-jobcard-cost-preview.php"><span class="p1cc-nav-title">هزینه JobCard</span><span class="p1cc-nav-sub">موتور محاسبه هزینه</span></a>';
echo '<a class="p1cc-nav-card" href="erp-payment-tracking.php"><span class="p1cc-nav-title">پیگیری پرداخت</span><span class="p1cc-nav-sub">ثبت و وضعیت بدهی</span></a>';
echo '<a class="p1cc-nav-card" href="erp-invoice-preview.php"><span class="p1cc-nav-title">پیش‌نمایش فاکتور</span><span class="p1cc-nav-sub">داخلی — غیر رسمی</span></a>';
echo '</div></div>';

pricing_render_foot();
