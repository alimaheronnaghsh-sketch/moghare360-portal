<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-stabilization-helper.php';

$c = false;
try {
    $c = stabilization_db();
    if ($c !== false) {
        stab_require_auth($c, 'stabilization.audit.view');
    }
} catch (Throwable) {
    stab_error('Local Release Candidate', 'دسترسی ممکن نیست.');
} finally {
    if ($c !== false) {
        @odbc_close($c);
    }
}

$rc = stabilization_release_candidate_status();

stab_render_head('MOGHARE360 Local Release Candidate 1');
echo '<div class="p11st-rc-hero"><h1>MOGHARE360 Local Release Candidate 1</h1>';
echo '<p>' . stabilization_h((string)$rc['scope']) . '</p></div>';

echo '<div class="p1cc-card"><h2 class="p11st-section-title">وضعیت Release</h2>';
echo '<table class="p1cc-table"><tbody>';
echo '<tr><td>Release Candidate Name</td><td><strong>' . stabilization_h((string)$rc['name']) . '</strong></td></tr>';
echo '<tr><td>Scope</td><td>' . stabilization_h((string)$rc['scope']) . '</td></tr>';
echo '<tr><td>Status</td><td><span class="p11st-badge ' . stab_badge_class((string)$rc['status']) . '">' . stabilization_h((string)$rc['status']) . '</span></td></tr>';
echo '</tbody></table></div>';

echo '<div class="p1cc-card"><h2 class="p11st-section-title">محدودیت‌های عمدی</h2><ul>';
foreach ($rc['limitations'] as $lim) {
    echo '<li>' . stabilization_h($lim) . '</li>';
}
echo '</ul></div>';

echo '<div class="p1cc-card"><h2 class="p11st-section-title">Checklist آمادگی</h2><table class="p1cc-table"><thead><tr><th>مورد</th><th>وضعیت</th></tr></thead><tbody>';
foreach ($rc['checklist'] as $item) {
    $st = (string)($item['status'] ?? 'PENDING');
    echo '<tr><td>' . stabilization_h((string)($item['item'] ?? '')) . '</td>';
    echo '<td><span class="p11st-badge ' . stab_badge_class($st) . '">' . stabilization_h($st) . '</span></td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p1cc-card"><h2 class="p11st-section-title">فازهای تکمیل‌شده</h2><table class="p1cc-table"><thead><tr><th>فاز</th><th>وضعیت</th></tr></thead><tbody>';
foreach (stabilization_phase_status_rows() as $row) {
    if (($row['phase'] ?? '') === '11') {
        continue;
    }
    echo '<tr><td class="m360-num">' . stabilization_h($row['phase']) . '</td>';
    echo '<td><span class="p11st-badge p11st-badge-ok">' . stabilization_h($row['status']) . '</span></td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p11st-boundary-box"><strong>مرزهای معماری</strong><ul>';
foreach (stabilization_boundary_labels() as $label) {
    echo '<li>' . stabilization_h($label) . '</li>';
}
echo '</ul></div>';

echo '<p><a class="p1cc-btn p1cc-btn-primary" href="erp-stabilization-dashboard.php">داشبورد پایداری</a> ';
echo '<a class="p1cc-btn" href="moghare360-final-release-report.php">گزارش Commercial (Phase 10)</a> ';
echo '<a class="p1cc-btn" href="erp-soft-run-pilot-center.php">Soft Run Pilot (Phase 12)</a></p>';
echo '<p style="margin-top:.75rem;font-size:.88rem"><a href="erp-brand-system.php">سیستم برند</a> · <a href="erp-localization-audit.php">ممیزی فارسی‌سازی</a> · <a href="erp-asset-registry.php">دفتر ثبت دارایی</a> · <a href="moghare360-demo-package.php">بسته نمایشی</a> · <a href="erp-security-hardening-dashboard.php">داشبورد امنیت (Phase 13)</a></p>';

stab_render_foot();
