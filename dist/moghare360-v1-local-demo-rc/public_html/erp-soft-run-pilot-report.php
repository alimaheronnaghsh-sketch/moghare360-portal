<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-pilot-helper.php';

$c = false;
$summary = pilot_get_pilot_report_summary(false);

try {
    $c = pilot_db();
    if ($c === false) {
        throw new RuntimeException('db');
    }
    pilot_require_auth($c, 'pilot.view');
    $summary = pilot_get_pilot_report_summary($c);
} catch (Throwable) {
    pilot_error('گزارش Pilot', 'گزارش Pilot قابل بارگذاری نیست.');
} finally {
    if ($c !== false) {
        @odbc_close($c);
    }
}

$fb = $summary['feedback'] ?? [];

pilot_render_head('گزارش Soft Run Pilot');
echo '<div class="p12pl-hero"><h1>گزارش Soft Run Pilot</h1><p>خلاصه آمادگی Pilot — read-only</p></div>';

echo '<div class="p12pl-kpi-grid">';
echo '<div class="p12pl-kpi"><div class="label">سناریوها</div><div class="value m360-num">' . pilot_h((string)($summary['scenario_count'] ?? 0)) . '</div></div>';
echo '<div class="p12pl-kpi"><div class="label">بازخوردها</div><div class="value m360-num">' . pilot_h((string)($fb['total'] ?? 0)) . '</div></div>';
echo '<div class="p12pl-kpi p12pl-kpi-highlight"><div class="label">Blocker</div><div class="value m360-num">' . pilot_h((string)($fb['blocker'] ?? 0)) . '</div></div>';
echo '<div class="p12pl-kpi"><div class="label">High</div><div class="value m360-num">' . pilot_h((string)($fb['high'] ?? 0)) . '</div></div>';
echo '<div class="p12pl-kpi"><div class="label">Medium</div><div class="value m360-num">' . pilot_h((string)($fb['medium'] ?? 0)) . '</div></div>';
echo '<div class="p12pl-kpi"><div class="label">Low</div><div class="value m360-num">' . pilot_h((string)($fb['low'] ?? 0)) . '</div></div>';
echo '</div>';

echo '<div class="p1cc-card"><h2 class="p12pl-section-title">تصمیم Pilot</h2>';
echo '<p><span class="p12pl-badge ' . pilot_badge_class((string)($summary['decision'] ?? 'PENDING')) . '" style="font-size:1rem;padding:.4rem .8rem">' . pilot_h((string)($summary['decision'] ?? 'PENDING')) . '</span></p></div>';

echo '<div class="p1cc-card"><h2 class="p12pl-section-title">اقدامات بعدی</h2><ul>';
foreach (($summary['next_actions'] ?? []) as $action) {
    echo '<li>' . pilot_h($action) . '</li>';
}
echo '</ul></div>';

echo '<div class="p12pl-boundary-box"><strong>محدودیت‌های حفظ‌شده</strong><ul>';
foreach (pilot_boundary_labels() as $b) {
    echo '<li>' . pilot_h($b) . '</li>';
}
echo '</ul></div>';

echo '<p><a class="p1cc-btn p1cc-btn-primary" href="erp-soft-run-pilot-center.php">Pilot Center</a> ';
echo '<a class="p1cc-btn" href="erp-pilot-scenario-builder.php">ساخت سناریو</a></p>';

pilot_render_foot();
