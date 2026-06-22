<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-business-ready-helper.php';

$connection = false;
$errorMessage = '';
$kpi = business_ready_get_kpi_summary(false);
$snapshots = [];
$flash = br_flash($_GET['ok'] ?? '');

try {
    $connection = business_ready_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    br_require_auth($connection, 'business.ready.report');
    $kpi = business_ready_get_kpi_summary($connection);
    if (business_ready_table_exists($connection, 'erp_business_kpi_snapshots')) {
        $snapshots = business_ready_fetch_rows($connection, 'SELECT TOP 10 kpi_snapshot_id, snapshot_code, readiness_score, total_remaining, created_at FROM dbo.erp_business_kpi_snapshots ORDER BY kpi_snapshot_id DESC');
    }
} catch (Throwable) {
    $errorMessage = 'گزارش KPI قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

br_render_head('گزارش KPI');
echo '<div class="p9br-hero"><h1>گزارش KPI مدیریتی</h1><p>خلاصه فازهای ۱ تا ۷ — readiness score</p></div>';
if ($flash !== '') echo '<div class="p1cc-card p1cc-success"><p>' . br_h($flash) . '</p></div>';
if ($errorMessage !== '') echo '<div class="p1cc-card p1cc-error"><p>' . br_h($errorMessage) . '</p></div>';

echo '<div class="p9br-kpi-grid">';
foreach ([
    'operation_open' => 'عملیات باز', 'operation_ready' => 'آماده تحویل', 'operation_delivered' => 'تحویل',
    'waiting_approval' => 'انتظار تأیید', 'waiting_parts' => 'انتظار قطعه',
    'unpaid_count' => 'پرداخت‌نشده', 'partial_paid_count' => 'پرداخت جزئی', 'paid_count' => 'تسویه‌شده',
    'crm_pending_followup' => 'CRM معوق', 'inventory_low_pressure' => 'فشار انبار', 'active_employees' => 'کارمند فعال',
] as $k => $l) {
    echo '<div class="p9br-kpi"><div class="label">' . br_h($l) . '</div><div class="value m360-num">' . br_h((string)($kpi[$k] ?? 0)) . '</div></div>';
}
echo '<div class="p9br-kpi p9br-kpi-highlight"><div class="label">Readiness</div><div class="value m360-num">' . br_h((string)($kpi['readiness_score'] ?? 0)) . '%</div></div>';
echo '</div>';

echo '<div class="p1cc-card"><h2 class="p9br-section-title">Operation Funnel</h2><p>باز: ' . br_h((string)$kpi['operation_open']) . ' · آماده: ' . br_h((string)$kpi['operation_ready']) . ' · تحویل: ' . br_h((string)$kpi['operation_delivered']) . '</p>';
echo '<p>مالی — قابل پرداخت: ' . br_h(business_ready_format_amount($kpi['total_payable'])) . ' · پرداخت‌شده: ' . br_h(business_ready_format_amount($kpi['total_paid'])) . ' · مانده: ' . br_h(business_ready_format_amount($kpi['total_remaining'])) . '</p></div>';

echo '<form class="p1cc-card" method="post" action="submit-business-kpi-snapshot.php">';
echo erp_csrf_input('br_kpi_snapshot');
echo '<h2 class="p9br-section-title">ثبت Snapshot KPI (controlled)</h2>';
echo '<p class="p1cc-hint">فقط گزارش — داده عملیاتی تغییر نمی‌کند.</p>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="snapshot_note" maxlength="1500"></textarea></div>';
echo '<button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت Snapshot</button></form>';

if ($snapshots !== []) {
    br_render_table('Snapshotهای اخیر', $snapshots, ['snapshot_code' => 'کد', 'readiness_score' => 'امتیاز', 'total_remaining' => 'مانده', 'created_at' => 'تاریخ']);
}

br_render_foot();
