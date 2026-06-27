<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-business-ready-helper.php';

$connection = false;
$errorMessage = '';
$checks = [];
$score = 0.0;
$flash = br_flash($_GET['ok'] ?? '');

try {
    $connection = business_ready_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    br_require_auth($connection, 'business.ready.audit');
    $checks = business_ready_fetch_audit_rows($connection);
    $score = business_ready_calculate_readiness_score($connection);
} catch (Throwable) {
    $errorMessage = 'Soft Run Audit قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) @odbc_close($connection);
}

br_render_head('Soft Run Readiness Audit');
echo '<div class="p9br-hero"><h1>Soft Run Readiness Audit</h1><p>چک‌لیست آمادگی فازها — score: <span class="m360-num">' . br_h((string)$score) . '%</span></p></div>';
if ($flash !== '') echo '<div class="p1cc-card p1cc-success"><p>' . br_h($flash) . '</p></div>';
if ($errorMessage !== '') echo '<div class="p1cc-card p1cc-error"><p>' . br_h($errorMessage) . '</p></div>';

echo '<div class="p1cc-card"><h2 class="p9br-section-title">چک‌لیست فازها</h2><table class="p1cc-table"><thead><tr><th>کد</th><th>گروه</th><th>عنوان</th><th>وضعیت</th><th>امتیاز</th></tr></thead><tbody>';
foreach ($checks as $ch) {
    $status = $ch['check_status'] ?? ($ch['live_status'] ?? 'PENDING');
    echo '<tr><td class="m360-ltr">' . br_h($ch['check_code'] ?? '') . '</td>';
    echo '<td>' . br_h($ch['check_group'] ?? '') . '</td><td>' . br_h($ch['check_title'] ?? '') . '</td>';
    echo '<td><span class="p9br-badge ' . br_badge_class($status) . '">' . br_h($status) . '</span></td>';
    echo '<td class="m360-num">' . br_h((string)($ch['check_score'] ?? '0')) . '</td></tr>';
}
echo '</tbody></table></div>';

echo '<form class="p1cc-card" method="post" action="submit-soft-run-audit-check.php">';
echo erp_csrf_input('br_soft_run_audit');
echo '<h2 class="p9br-section-title">ثبت وضعیت Audit (controlled)</h2>';
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کد چک *</label><select class="p1cc-select" name="check_code" required>';
foreach ($checks as $ch) {
    $code = $ch['check_code'] ?? '';
    if ($code === '') continue;
    echo '<option value="' . br_h($code) . '">' . br_h($code) . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">وضعیت</label><select class="p1cc-select" name="check_status">';
foreach (['PASSED', 'WARNING', 'FAILED', 'PENDING', 'NOT_APPLICABLE'] as $st) {
    echo '<option value="' . br_h($st) . '">' . br_h($st) . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">امتیاز</label><input class="p1cc-input m360-ltr" type="number" step="0.01" min="0" max="10" name="check_score" value="10"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="check_note" maxlength="1500"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت Audit</button></form>';

echo '<div class="p1cc-card"><h2 class="p9br-section-title">مرزهای معماری</h2><ul class="p9br-boundary-list">';
foreach (business_ready_product_boundaries() as $b) echo '<li>' . br_h($b) . '</li>';
echo '</ul></div>';

br_render_foot();
