<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-commercial-system-helper.php';

$c = commercial_db();
$licenses = commercial_get_license_models($c ?: false);
if ($c !== false) @odbc_close($c);

try {
    $auth = commercial_db();
    if ($auth !== false) { cs_require_auth($auth, 'commercial.demo.view'); @odbc_close($auth); }
} catch (Throwable) { cs_error('License Preview', 'دسترسی ممکن نیست.'); }

cs_render_head('License Preview');
echo '<div class="p10cs-hero"><h1>مدل‌های License Preview</h1><p>Licensing concept — بدون enforcement</p></div>';
echo '<div class="p10cs-warning">License enforcement واقعی در این فاز فعال نیست.</div>';

echo '<table class="p1cc-table p1cc-card"><thead><tr><th>کد</th><th>نام</th><th>نوع</th><th>کاربر</th><th>شعبه</th><th>JobCard/ماه</th><th>پشتیبانی</th><th>یادداشت</th></tr></thead><tbody>';
foreach ($licenses as $l) {
    echo '<tr>';
    echo '<td class="m360-ltr">' . commercial_h($l['license_code'] ?? '') . '</td>';
    echo '<td>' . commercial_h($l['license_name'] ?? '') . '</td>';
    echo '<td>' . commercial_h($l['license_type'] ?? '') . '</td>';
    echo '<td class="m360-num">' . commercial_h($l['max_users_preview'] !== '' ? $l['max_users_preview'] : '—') . '</td>';
    echo '<td class="m360-num">' . commercial_h($l['max_branches_preview'] !== '' ? $l['max_branches_preview'] : '—') . '</td>';
    echo '<td class="m360-num">' . commercial_h($l['max_jobcards_monthly_preview'] !== '' ? $l['max_jobcards_monthly_preview'] : '—') . '</td>';
    echo '<td>' . commercial_h($l['support_level'] ?? '—') . '</td>';
    echo '<td>' . commercial_h($l['license_note'] ?? '') . '</td>';
    echo '</tr>';
}
echo '</tbody></table>';

cs_render_foot();
