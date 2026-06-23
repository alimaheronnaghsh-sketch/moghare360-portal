<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-business-ready-helper.php';

$connection = false;
$errorMessage = '';
$data = business_ready_get_crm_summary(false);

try {
    $connection = business_ready_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    br_require_auth($connection, 'business.ready.report');
    $data = business_ready_get_crm_summary($connection);
} catch (Throwable) {
    $errorMessage = 'گزارش CRM قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

br_render_head('گزارش CRM');
echo '<div class="p9br-hero"><h1>گزارش CRM و مشتری</h1><p>بدون ارسال پیام واقعی</p></div>';
if ($errorMessage !== '') echo '<div class="p1cc-card p1cc-error"><p>' . br_h($errorMessage) . '</p></div>';

br_render_table('وضعیت پیگیری', $data['followup'], ['followup_status' => 'وضعیت', 'cnt' => 'تعداد']);
br_render_table('امتیاز VIP', $data['vip_counts'], ['vip_status' => 'VIP', 'cnt' => 'تعداد']);
br_render_table('فرصت فروش', $data['upsell'], ['opportunity_status' => 'وضعیت', 'cnt' => 'تعداد']);

echo '<div class="p1cc-card"><h2 class="p9br-section-title">خلاصه</h2>';
if ($data['satisfaction_avg'] !== null) {
    echo '<p>میانگین رضایت: <span class="m360-num">' . br_h((string)$data['satisfaction_avg']) . '</span></p>';
} else {
    echo '<p class="p1cc-hint">میانگین رضایت — داده در دسترس نیست.</p>';
}
echo '<p>شکایت/اولویت: شکایت ' . br_h((string)($data['highlights']['complaints'] ?? 0)) . ' · نیاز مدیر ' . br_h((string)($data['highlights']['needs_manager'] ?? 0)) . '</p></div>';

br_render_foot();
