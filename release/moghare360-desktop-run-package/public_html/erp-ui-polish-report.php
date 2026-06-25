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
    stab_error('گزارش UI Polish', 'دسترسی ممکن نیست.');
} finally {
    if ($c !== false) {
        @odbc_close($c);
    }
}

$checks = stabilization_ui_polish_checks();
$ok = count(array_filter($checks, static fn(array $r): bool => ($r['status'] ?? '') === 'OK'));
$warn = count(array_filter($checks, static fn(array $r): bool => in_array($r['status'] ?? '', ['WARNING', 'NEEDS MANUAL REVIEW'], true)));

stab_render_head('گزارش UI Polish');
echo '<div class="p11st-hero"><h1>گزارش UI Polish</h1><p>بررسی read-only — بدون rewrite صفحات قبلی</p></div>';
echo '<p><a class="p1cc-btn" href="erp-business-command-center.php">بازگشت به Business Command Center</a></p>';
echo '<p>OK: <strong class="m360-num">' . stabilization_h((string)$ok) . '</strong> · نیاز به بررسی: <strong class="m360-num">' . stabilization_h((string)$warn) . '</strong></p>';

echo '<div class="p1cc-card p11st-table-wrap"><table class="p1cc-table"><thead><tr>';
echo '<th>حوزه</th><th>مورد</th><th>وضعیت</th><th>یادداشت</th></tr></thead><tbody>';
foreach ($checks as $ch) {
    $st = (string)($ch['status'] ?? '');
    echo '<tr><td>' . stabilization_h((string)($ch['area'] ?? '')) . '</td>';
    echo '<td>' . stabilization_h((string)($ch['item'] ?? '')) . '</td>';
    echo '<td><span class="p11st-badge ' . stab_badge_class($st) . '">' . stabilization_h($st) . '</span></td>';
    echo '<td>' . stabilization_h((string)($ch['note'] ?? '')) . '</td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p1cc-card"><h2 class="p11st-section-title">CSS Registry</h2><table class="p1cc-table"><tbody>';
$cssFiles = [
    'moghare360-soft-run-release.css', 'moghare360-business-layer.css',
    'moghare360-business-ready.css', 'moghare360-commercial-system.css', 'moghare360-stabilization.css',
];
foreach ($cssFiles as $css) {
    $path = 'public_html/assets/moghare360-ui/' . $css;
    $st = stabilization_file_exists_status($path);
    echo '<tr><td>' . stabilization_h($css) . '</td>';
    echo '<td><span class="p11st-badge ' . stab_badge_class($st) . '">' . stabilization_h($st) . '</span></td></tr>';
}
echo '</tbody></table></div>';

stab_render_foot();
