<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 3 Rule Decision Board (read-only)
 */

require_once __DIR__ . '/includes/erp-rule-engine.php';

$connection = false;
$errorMessage = '';
$stats = [
    'total' => '—',
    'allowed' => '—',
    'approval_required' => '—',
    'blocked' => '—',
    'purchase_required' => '—',
    'needs_review' => '—',
];
$recentDecisions = [];

try {
    $connection = rule_engine_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    rule_engine_require_auth_and_guard($connection, 'rule.engine.dashboard.view');

    if (rule_engine_table_exists($connection, 'erp_rule_decisions')) {
        $stats['total'] = rule_engine_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_rule_decisions') ?? '0';
        $stats['allowed'] = rule_engine_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_rule_decisions WHERE decision_status = 'ALLOWED'") ?? '0';
        $stats['approval_required'] = rule_engine_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_rule_decisions WHERE decision_status = 'APPROVAL_REQUIRED'") ?? '0';
        $stats['blocked'] = rule_engine_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_rule_decisions WHERE decision_status = 'BLOCKED'") ?? '0';
        $stats['purchase_required'] = rule_engine_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_rule_decisions WHERE decision_status = 'PURCHASE_REQUIRED'") ?? '0';
        $stats['needs_review'] = rule_engine_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_rule_decisions WHERE decision_status = 'NEEDS_REVIEW'") ?? '0';

        $recentDecisions = rule_engine_fetch_rows(
            $connection,
            'SELECT TOP 20 decision_id, decision_code, decision_status, next_action, decision_reason,
                    operation_case_id, is_blocking, created_at
             FROM dbo.erp_rule_decisions
             ORDER BY decision_id DESC'
        );
    }
} catch (Throwable) {
    $errorMessage = 'نمایش تابلوی تصمیم‌های قانون با خطا مواجه شد.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

rule_engine_render_head('تابلو تصمیم‌های قانون', true);

echo '<div class="p3re-hero">';
echo '<h1>تابلو تصمیم‌های قانون</h1>';
echo '<p>مغز تصمیم‌گیری ERP — قرارداد، سرویس، انبار</p>';
echo '</div>';

if ($errorMessage !== '') {
    echo '<div class="p1cc-card p1cc-error"><p>' . rule_engine_h($errorMessage) . '</p></div>';
} else {
    echo '<div class="p1cc-card"><h2 class="p3re-section-title">خلاصه KPI</h2><div class="p1cc-kpi-grid">';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">کل تصمیم‌ها</div><div class="p1cc-kpi-value">' . rule_engine_h($stats['total']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">مجاز</div><div class="p1cc-kpi-value">' . rule_engine_h($stats['allowed']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">نیازمند تأیید</div><div class="p1cc-kpi-value">' . rule_engine_h($stats['approval_required']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">متوقف</div><div class="p1cc-kpi-value">' . rule_engine_h($stats['blocked']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">مسیر خرید</div><div class="p1cc-kpi-value">' . rule_engine_h($stats['purchase_required']) . '</div></div>';
    echo '<div class="p1cc-kpi"><div class="p1cc-kpi-label">بررسی دستی</div><div class="p1cc-kpi-value">' . rule_engine_h($stats['needs_review']) . '</div></div>';
    echo '</div></div>';

    echo '<div class="p1cc-card"><h2 class="p3re-section-title">دسترسی سریع</h2><div class="p1cc-nav-grid">';
    echo '<a class="p1cc-nav-card" href="erp-service-approval-request.php"><span class="p1cc-nav-title">درخواست‌های تأیید سرویس</span><span class="p1cc-nav-sub">تأیید / رد داخلی</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-rule-test-console.php"><span class="p1cc-nav-title">کنسول تست قوانین</span><span class="p1cc-nav-sub">اجرای Rule Check داخلی</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-operation-control-center.php"><span class="p1cc-nav-title">مرکز کنترل عملیات</span><span class="p1cc-nav-sub">Phase 2</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-part-reserve.php"><span class="p1cc-nav-title">رزرو قطعه</span><span class="p1cc-nav-sub">Phase 4 — موجودی</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-purchase-request-create.php"><span class="p1cc-nav-title">درخواست خرید</span><span class="p1cc-nav-sub">Phase 4 — تامین</span></a>';
    echo '<a class="p1cc-nav-card" href="erp-stock-board.php"><span class="p1cc-nav-title">تابلو انبار</span><span class="p1cc-nav-sub">Phase 4</span></a>';
    echo '</div></div>';

    echo '<div class="p1cc-card"><h2 class="p3re-section-title">آخرین تصمیم‌ها</h2>';

    if ($recentDecisions === []) {
        echo '<p class="p1cc-hint">هنوز تصمیمی ثبت نشده است. از کنسول تست قوانین استفاده کنید.</p>';
    } else {
        echo '<table class="p1cc-table"><thead><tr>';
        echo '<th>کد</th><th>وضعیت</th><th>اقدام بعدی</th><th>پرونده</th><th>مسدود</th><th>تاریخ</th></tr></thead><tbody>';

        foreach ($recentDecisions as $row) {
            echo '<tr>';
            echo '<td class="m360-ltr">' . rule_engine_h($row['decision_code'] ?? '') . '</td>';
            echo '<td><span class="p1cc-badge ' . rule_engine_badge_class($row['decision_status'] ?? '') . '">' . rule_engine_h(rule_engine_status_label_fa($row['decision_status'] ?? '')) . '</span></td>';
            echo '<td>' . rule_engine_h($row['next_action'] ?? '') . '</td>';
            echo '<td>' . rule_engine_h($row['operation_case_id'] !== '' ? $row['operation_case_id'] : '—') . '</td>';
            echo '<td>' . rule_engine_h(($row['is_blocking'] ?? '0') === '1' ? 'بله' : 'خیر') . '</td>';
            echo '<td>' . rule_engine_h($row['created_at'] ?? '') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
}

rule_engine_render_foot();
