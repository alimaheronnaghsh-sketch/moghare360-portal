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
    stab_error('گزارش لینک‌ها', 'دسترسی ممکن نیست.');
} finally {
    if ($c !== false) {
        @odbc_close($c);
    }
}

$rows = stabilization_broken_link_report();
$ok = count(array_filter($rows, static fn(array $r): bool => ($r['file_status'] ?? '') === 'OK'));
$missing = count($rows) - $ok;

stab_render_head('گزارش لینک‌ها و URL Registry');
echo '<div class="p11st-hero"><h1>گزارش لینک‌ها (Broken Link Report)</h1><p>File registry — بدون درخواست HTTP خارجی</p></div>';
echo '<div class="p11st-warning-box">وضعیت Browser: PENDING USER CHECK — لطفاً URLها را در مرورگر محلی تست کنید.</div>';
echo '<p>OK: <strong class="m360-num">' . stabilization_h((string)$ok) . '</strong> · MISSING: <strong class="m360-num">' . stabilization_h((string)$missing) . '</strong></p>';

echo '<div class="p1cc-card p11st-table-wrap"><table class="p1cc-table"><thead><tr>';
echo '<th>ماژول</th><th>عنوان</th><th>URL</th><th>فایل</th><th>File Status</th><th>Browser</th><th>یادداشت</th>';
echo '</tr></thead><tbody>';
foreach ($rows as $row) {
    $fs = (string)($row['file_status'] ?? 'MISSING');
    $url = (string)($row['url'] ?? '');
    echo '<tr><td>' . stabilization_h((string)($row['module'] ?? '')) . '</td>';
    echo '<td>' . stabilization_h((string)($row['title'] ?? '')) . '</td>';
    echo '<td>' . ($fs === 'OK' ? '<a href="' . stabilization_h($url) . '">' . stabilization_h($url) . '</a>' : stabilization_h($url)) . '</td>';
    echo '<td class="m360-num">' . stabilization_h((string)($row['expected_file'] ?? '')) . '</td>';
    echo '<td><span class="p11st-badge ' . stab_badge_class($fs) . '">' . stabilization_h($fs) . '</span></td>';
    echo '<td><span class="p11st-badge p11st-badge-warn">' . stabilization_h((string)($row['browser_status'] ?? 'PENDING USER CHECK')) . '</span></td>';
    echo '<td>' . stabilization_h((string)($row['notes'] ?? '')) . '</td></tr>';
}
echo '</tbody></table></div>';

stab_render_foot();
