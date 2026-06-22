<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-business-ready-helper.php';

$connection = false;
$errorMessage = '';
$data = business_ready_get_financial_preview(false);

try {
    $connection = business_ready_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    br_require_auth($connection, 'business.ready.report');
    $data = business_ready_get_financial_preview($connection);
} catch (Throwable) {
    $errorMessage = 'گزارش مالی قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

br_render_head('گزارش پیش‌نمایش مالی');
echo '<div class="p9br-hero"><h1>گزارش پیش‌نمایش مالی</h1><p>Financial Preview Report</p></div>';
echo '<div class="p9br-warning">این گزارش پیش‌نمایش مدیریتی است و حسابداری رسمی/مالیاتی نیست.</div>';
if ($errorMessage !== '') echo '<div class="p1cc-card p1cc-error"><p>' . br_h($errorMessage) . '</p></div>';

echo '<div class="p9br-kpi-grid">';
foreach ([
    'payable' => 'قابل پرداخت', 'paid_amount' => 'پرداخت‌شده', 'remaining' => 'مانده',
    'unpaid' => 'تعداد unpaid', 'partial' => 'تعداد partial', 'paid_count' => 'تعداد paid', 'overpaid' => 'overpaid',
] as $k => $l) {
    $v = $data[$k] ?? 0;
    if (in_array($k, ['payable', 'paid_amount', 'remaining'], true)) $v = business_ready_format_amount($v);
    echo '<div class="p9br-kpi"><div class="label">' . br_h($l) . '</div><div class="value m360-num">' . br_h((string)$v) . '</div></div>';
}
echo '</div>';

$top = $data['top_receivables'];
if ($top !== []) {
    echo '<div class="p1cc-card"><h2 class="p9br-section-title">بدهی‌های باز (Top)</h2><table class="p1cc-table"><thead><tr><th>کد</th><th>قابل پرداخت</th><th>مانده</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($top as $r) {
        echo '<tr><td class="m360-ltr">' . br_h($r['cost_code'] ?? '') . '</td>';
        echo '<td class="m360-num">' . br_h(business_ready_format_amount($r['payable_total'] ?? '0')) . '</td>';
        echo '<td class="m360-num">' . br_h(business_ready_format_amount($r['remaining_total'] ?? '0')) . '</td>';
        echo '<td><span class="p9br-badge ' . br_badge_class($r['payment_status'] ?? '') . '">' . br_h($r['payment_status'] ?? '') . '</span></td></tr>';
    }
    echo '</tbody></table></div>';
}

br_render_foot();
