<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 2 Operation Control Center (read-only)
 */

require_once __DIR__ . '/includes/erp-operation-engine-helper.php';

$connection = false;
$errorMessage = '';

$stats = [
    'total' => '—',
    'waiting_approval' => '—',
    'waiting_parts' => '—',
    'qc' => '—',
    'ready_delivery' => '—',
];

$stageCounts = [];

try {
    $connection = operation_engine_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    operation_engine_require_auth_and_guard($connection, 'operation.engine.dashboard.view');

    if (operation_engine_table_exists($connection, 'erp_operation_cases')) {
        $stats['total'] = operation_engine_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_operation_cases') ?? '0';
        $stats['waiting_approval'] = operation_engine_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_operation_cases WHERE current_stage = 'WAITING_APPROVAL'"
        ) ?? '0';
        $stats['waiting_parts'] = operation_engine_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_operation_cases WHERE current_stage = 'WAITING_PARTS'"
        ) ?? '0';
        $stats['qc'] = operation_engine_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_operation_cases WHERE current_stage = 'QC'"
        ) ?? '0';
        $stats['ready_delivery'] = operation_engine_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_operation_cases WHERE current_stage = 'READY_FOR_DELIVERY'"
        ) ?? '0';

        foreach (ERP_PHASE2_OPERATION_STAGES as $stage) {
            $count = operation_engine_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.erp_operation_cases WHERE current_stage = ?',
                [$stage]
            );
            $stageCounts[$stage] = $count ?? '0';
        }
    }
} catch (Throwable) {
    $errorMessage = 'نمایش مرکز کنترل عملیات با خطا مواجه شد.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

operation_engine_render_head('مرکز کنترل عملیات', true);

echo '<div class="p2oe-hero">';
echo '<h1>مرکز کنترل عملیات</h1>';
echo '<p>جریان تعمیرگاه: مشتری → خودرو → JobCard → سرویس → QC → تحویل</p>';
echo '</div>';

if ($errorMessage !== '') {
    echo '<div class="p1cc-card p1cc-error"><p>' . operation_engine_h($errorMessage) . '</p></div>';
} else {
    echo '<div class="p1cc-card"><h2 class="p2oe-section-title">خلاصه KPI</h2><div class="p1cc-kpi-grid">';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">کل پرونده‌ها</div><div class="p1cc-kpi-value">' . operation_engine_h($stats['total']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">منتظر تأیید</div><div class="p1cc-kpi-value">' . operation_engine_h($stats['waiting_approval']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">منتظر قطعه</div><div class="p1cc-kpi-value">' . operation_engine_h($stats['waiting_parts']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">QC</div><div class="p1cc-kpi-value">' . operation_engine_h($stats['qc']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">آماده تحویل</div><div class="p1cc-kpi-value">' . operation_engine_h($stats['ready_delivery']) . '</div></div>';
    echo '</div></div>';

    if ($stageCounts !== []) {
        echo '<div class="p1cc-card"><h2 class="p2oe-section-title">توزیع مرحله (Stage)</h2><div class="p2oe-stage-grid">';

        foreach ($stageCounts as $stage => $count) {
            echo '<div class="p2oe-stage-chip">' . operation_engine_h($stage);
            echo '<strong>' . operation_engine_h($count) . '</strong></div>';
        }

        echo '</div></div>';
    }

    echo '<div class="p1cc-card"><h2 class="p2oe-section-title">دسترسی سریع</h2><div class="p1cc-nav-grid">';
    echo '<a class="p1cc-nav-card" href="erp-jobcard-operation-flow.php"><span class="p1cc-nav-title">جریان عملیاتی JobCard</span><span class="p1cc-nav-sub">پرونده، سرویس، QC، تحویل</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-technician-board.php"><span class="p1cc-nav-title">تابلوی تکنسین</span><span class="p1cc-nav-sub">مراحل سرویس و وضعیت</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-jobcard-readonly-list.php"><span class="p1cc-nav-title">لیست JobCard (M17)</span><span class="p1cc-nav-sub">foundation موجود</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-service-operation-readonly-list.php"><span class="p1cc-nav-title">لیست سرویس (M20)</span><span class="p1cc-nav-sub">foundation موجود</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-qc-check.php"><span class="p1cc-nav-title">QC Check (M30)</span><span class="p1cc-nav-sub">foundation موجود</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-delivery-control.php"><span class="p1cc-nav-title">Delivery (M30)</span><span class="p1cc-nav-sub">foundation موجود</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-jobcard-cost-preview.php"><span class="p1cc-nav-title">هزینه JobCard</span><span class="p1cc-nav-sub">Phase 5 — مالی</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-finance-control-center.php"><span class="p1cc-nav-title">مرکز کنترل مالی</span><span class="p1cc-nav-sub">Phase 5</span></a>';
    echo '</div></div>';
}

operation_engine_render_foot();
