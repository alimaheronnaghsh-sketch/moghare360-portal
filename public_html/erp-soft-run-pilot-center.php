<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-pilot-helper.php';

$c = false;
$pilot = null;
$summary = pilot_get_pilot_report_summary(false);
$fb = ['total' => 0, 'blocker' => 0, 'high' => 0];

try {
    $c = pilot_db();
    if ($c === false) {
        throw new RuntimeException('db');
    }
    pilot_require_auth($c, 'pilot.view');
    $pilot = pilot_get_active_pilot($c);
    $summary = pilot_get_pilot_report_summary($c);
    $fb = $summary['feedback'] ?? $fb;
} catch (Throwable) {
    pilot_error('مرکز کنترل Pilot', 'مرکز کنترل Soft Run Pilot قابل بارگذاری نیست.');
} finally {
    if ($c !== false) {
        @odbc_close($c);
    }
}

pilot_render_head('مرکز کنترل Soft Run Pilot');
echo '<div class="p12pl-hero"><h1>مرکز کنترل Soft Run Pilot</h1><p>MOGHARE360 — Controlled Internal Pilot Workspace</p></div>';

echo '<div class="p12pl-boundary-box"><strong>هشدار ثابت</strong><ul>';
foreach (pilot_boundary_labels() as $b) {
    echo '<li>' . pilot_h($b) . '</li>';
}
echo '</ul></div>';

echo '<div class="p12pl-kpi-grid">';
echo '<div class="p12pl-kpi"><div class="label">Pilot Status</div><div class="value" style="font-size:.95rem">' . pilot_h((string)($pilot['pilot_status'] ?? 'UNAVAILABLE')) . '</div></div>';
echo '<div class="p12pl-kpi"><div class="label">Active Pilot</div><div class="value" style="font-size:.9rem">' . pilot_h((string)($pilot['pilot_code'] ?? '—')) . '</div></div>';
echo '<div class="p12pl-kpi"><div class="label">Scenarios</div><div class="value m360-num">' . pilot_h((string)($summary['scenario_count'] ?? 0)) . '</div></div>';
echo '<div class="p12pl-kpi"><div class="label">Feedback</div><div class="value m360-num">' . pilot_h((string)($fb['total'] ?? 0)) . '</div></div>';
echo '<div class="p12pl-kpi p12pl-kpi-highlight"><div class="label">Blocker</div><div class="value m360-num">' . pilot_h((string)($fb['blocker'] ?? 0)) . '</div></div>';
echo '<div class="p12pl-kpi"><div class="label">High Severity</div><div class="value m360-num">' . pilot_h((string)($fb['high'] ?? 0)) . '</div></div>';
echo '<div class="p12pl-kpi"><div class="label">Pilot Decision</div><div class="value" style="font-size:.85rem"><span class="p12pl-badge ' . pilot_badge_class((string)($summary['pilot_decision'] ?? 'PENDING')) . '">' . pilot_h((string)($summary['pilot_decision'] ?? 'PENDING')) . '</span></div></div>';
echo '</div>';

if ($pilot !== null) {
    echo '<div class="p1cc-card"><p><strong>' . pilot_h((string)$pilot['pilot_title']) . '</strong><br>';
    echo pilot_h((string)($pilot['pilot_scope'] ?? '')) . '</p></div>';
}

echo '<div class="p1cc-card"><h2 class="p12pl-section-title">ابزارهای Pilot</h2><div class="p12pl-nav-grid">';
$tools = [
    ['erp-pilot-scenario-builder.php', 'ساخت سناریو', 'ثبت مشتری/خودرو/قرارداد'],
    ['erp-pilot-flow-viewer.php', 'نمایش جریان', 'Customer → HR flow'],
    ['erp-pilot-data-checklist.php', 'چک‌لیست داده', 'وضعیت هر مرحله'],
    ['erp-pilot-feedback.php', 'بازخورد Pilot', 'bug / ux / training'],
    ['erp-soft-run-pilot-report.php', 'گزارش Pilot', 'آمادگی و تصمیم'],
];
foreach ($tools as [$url, $title, $sub]) {
    echo '<a class="p12pl-nav-card" href="' . pilot_h($url) . '">';
    echo '<span class="p12pl-nav-title">' . pilot_h($title) . '</span>';
    echo '<span class="p12pl-nav-sub">' . pilot_h($sub) . '</span></a>';
}
echo '</div></div>';

pilot_render_foot();
