<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-business-ready-helper.php';

$connection = false;
$errorMessage = '';
$data = business_ready_get_inventory_pressure(false);

try {
    $connection = business_ready_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    br_require_auth($connection, 'business.ready.report');
    $data = business_ready_get_inventory_pressure($connection);
} catch (Throwable) {
    $errorMessage = 'گزارش فشار انبار قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

br_render_head('گزارش فشار انبار');
echo '<div class="p9br-hero"><h1>گزارش فشار موجودی و خرید</h1><p>بدون stock deduction</p></div>';
if ($errorMessage !== '') echo '<div class="p1cc-card p1cc-error"><p>' . br_h($errorMessage) . '</p></div>';

echo '<div class="p9br-kpi-grid">';
foreach ([
    'items' => 'اقلام', 'low_stock' => 'کم‌موجودی', 'out_of_stock' => 'تمام‌شده',
    'pending_receive' => 'در انتظار دریافت', 'waiting_parts_ops' => 'عملیات انتظار قطعه',
] as $k => $l) {
    $v = $data[$k] ?? 0;
    echo '<div class="p9br-kpi"><div class="label">' . br_h($l) . '</div><div class="value m360-num">' . br_h((string)$v) . '</div></div>';
}
echo '</div>';

br_render_table('درخواست خرید', $data['purchase_by_status'], ['st' => 'وضعیت', 'cnt' => 'تعداد']);
br_render_table('رزرو قطعات', $data['reservations'], ['reservation_status' => 'وضعیت', 'cnt' => 'تعداد']);

if ($data['hints'] !== []) {
    echo '<div class="p1cc-card"><h2 class="p9br-section-title">Pressure Hints</h2><ul>';
    foreach ($data['hints'] as $h) echo '<li>' . br_h($h) . '</li>';
    echo '</ul></div>';
}

br_render_foot();
