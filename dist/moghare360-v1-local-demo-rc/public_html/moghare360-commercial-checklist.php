<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-commercial-system-helper.php';

$c = false;
$checks = [];
$score = 0.0;
$flash = cs_flash($_GET['ok'] ?? '');
$releases = [];

try {
    $c = commercial_db();
    if ($c === false) throw new RuntimeException('اتصال برقرار نشد.');
    cs_require_auth($c, 'commercial.demo.view');
    $checks = commercial_get_readiness_checks($c);
    $score = commercial_calculate_commercial_readiness_score($c);
    if (commercial_table_exists($c, 'erp_commercial_release_history')) {
        $releases = commercial_fetch_rows($c, 'SELECT TOP 10 release_code, release_type, release_title, release_status, created_at FROM dbo.erp_commercial_release_history ORDER BY release_history_id DESC');
    }
} catch (Throwable) {
    cs_error('Commercial Checklist', 'چک‌لیست تجاری قابل بارگذاری نیست.');
} finally {
    if ($c !== false) @odbc_close($c);
}

cs_render_head('Commercial Release Checklist');
echo '<div class="p10cs-hero"><h1>Commercial Release Checklist</h1><p>امتیاز آمادگی: <span class="m360-num">' . commercial_h((string)$score) . '%</span></p></div>';
if ($flash !== '') echo '<div class="p1cc-card p1cc-success"><p>' . commercial_h($flash) . '</p></div>';

echo '<div class="p1cc-card"><table class="p1cc-table"><thead><tr><th>کد</th><th>گروه</th><th>عنوان</th><th>وضعیت</th><th>امتیاز</th></tr></thead><tbody>';
foreach ($checks as $ch) {
    $st = $ch['live_status'] ?? ($ch['check_status'] ?? 'PENDING');
    echo '<tr><td class="m360-ltr">' . commercial_h($ch['check_code'] ?? '') . '</td>';
    echo '<td>' . commercial_h($ch['check_group'] ?? '') . '</td><td>' . commercial_h($ch['check_title'] ?? '') . '</td>';
    echo '<td><span class="p10cs-badge ' . cs_badge_class($st) . '">' . commercial_h($st) . '</span></td>';
    echo '<td class="m360-num">' . commercial_h((string)($ch['check_score'] ?? '0')) . '</td></tr>';
}
echo '</tbody></table></div>';

echo '<form class="p1cc-card" method="post" action="submit-commercial-release-history.php">';
echo erp_csrf_input('commercial_release_history');
echo '<h2 class="p10cs-section-title">ثبت Release History (controlled)</h2>';
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نوع</label><select class="p1cc-select" name="release_type">';
foreach (['COMMERCIAL_DEMO', 'SALES_PACKAGE', 'LICENSING_PREVIEW', 'FINAL_RELEASE_REPORT'] as $t) {
    echo '<option value="' . commercial_h($t) . '">' . commercial_h($t) . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">عنوان *</label><input class="p1cc-input" name="release_title" required maxlength="300"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">خلاصه</label><textarea class="p1cc-textarea" name="release_summary" maxlength="3000"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت Release</button></form>';

echo '<div class="p1cc-card"><h2 class="p10cs-section-title">مرزهای معماری</h2><ul class="p10cs-list">';
foreach (commercial_product_boundaries() as $b) echo '<li>' . commercial_h($b) . '</li>';
echo '</ul></div>';

if ($releases !== []) {
    echo '<div class="p1cc-card"><h2 class="p10cs-section-title">Releaseهای اخیر</h2><table class="p1cc-table"><thead><tr><th>کد</th><th>نوع</th><th>عنوان</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($releases as $r) {
        echo '<tr><td class="m360-ltr">' . commercial_h($r['release_code'] ?? '') . '</td>';
        echo '<td>' . commercial_h($r['release_type'] ?? '') . '</td><td>' . commercial_h($r['release_title'] ?? '') . '</td>';
        echo '<td><span class="p10cs-badge ' . cs_badge_class($r['release_status'] ?? '') . '">' . commercial_h($r['release_status'] ?? '') . '</span></td></tr>';
    }
    echo '</tbody></table></div>';
}

cs_render_foot();
