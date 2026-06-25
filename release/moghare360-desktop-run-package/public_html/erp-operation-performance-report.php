<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-business-ready-helper.php';

$connection = false;
$errorMessage = '';
$data = business_ready_get_operation_performance(false);

try {
    $connection = business_ready_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    br_require_auth($connection, 'business.ready.report');
    $data = business_ready_get_operation_performance($connection);
} catch (Throwable) {
    $errorMessage = 'گزارش عملکرد عملیات قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

br_render_head('گزارش عملکرد عملیات');
echo '<div class="p9br-hero"><h1>گزارش عملکرد عملیاتی</h1><p>Operation Performance — read-only</p></div>';
if ($errorMessage !== '') echo '<div class="p1cc-card p1cc-error"><p>' . br_h($errorMessage) . '</p></div>';

br_render_table('پرونده‌ها بر اساس Stage', $data['stages'], ['current_stage' => 'Stage', 'cnt' => 'تعداد']);
br_render_table('مراحل سرویس بر اساس وضعیت', $data['step_status'], ['step_status' => 'وضعیت', 'cnt' => 'تعداد']);
br_render_table('خلاصه QC', $data['qc'], ['decision_status' => 'تصمیم', 'cnt' => 'تعداد']);
br_render_table('بررسی تحویل', $data['delivery'], ['status' => 'وضعیت', 'cnt' => 'تعداد']);

echo '<div class="p1cc-card"><h2 class="p9br-section-title">Bottleneck Hints</h2><ul>';
foreach ($data['bottlenecks'] as $k => $v) {
    echo '<li><strong>' . br_h($k) . ':</strong> <span class="m360-num">' . br_h((string)$v) . '</span></li>';
}
echo '</ul></div>';

br_render_foot();
