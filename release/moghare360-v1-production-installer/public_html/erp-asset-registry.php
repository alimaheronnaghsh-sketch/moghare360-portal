<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-localization-helper.php';

try {
    $c = mogh_loc_db();
    if ($c !== false) {
        mogh_loc_require_auth($c, 'localization.audit.view');
        @odbc_close($c);
    }
} catch (Throwable) {
    mogh_loc_error('دفتر ثبت دارایی', 'دسترسی ممکن نیست.');
}

mogh_loc_render_head('دفتر ثبت دارایی‌های محصول');
echo '<div class="m125bl-hero"><h1>دفتر ثبت دارایی‌های محصول</h1>';
echo '<p>Asset Registry — کنترل مالکیت و مجوز دارایی‌های برند و UI</p></div>';

echo '<div class="m125bl-warning-box"><strong>سیاست:</strong> دارایی بدون مالکیت یا مجوز نباید در نسخه نمایشی یا تجاری استفاده شود.</div>';

echo '<div class="m125bl-card-dark"><h2 class="m125bl-section-title">ثبت دارایی‌ها</h2>';
echo '<table class="p1cc-table"><thead><tr><th>دسته</th><th>نام</th><th>مسیر</th><th>مالکیت</th><th>یادداشت</th></tr></thead><tbody>';
foreach (mogh_loc_asset_registry() as $asset) {
    echo '<tr><td>' . mogh_loc_h((string)$asset['category']) . '</td>';
    echo '<td>' . mogh_loc_h((string)$asset['name']) . '</td>';
    echo '<td><code class="m360-ltr">' . mogh_loc_h((string)$asset['path']) . '</code></td>';
    $own = (string)$asset['ownership'];
    $badge = str_contains($own, 'PENDING') ? 'm125bl-badge-warn' : (str_contains($own, 'Prohibited') ? 'm125bl-badge-fail' : 'm125bl-badge-ok');
    echo '<td><span class="m125bl-badge ' . $badge . '">' . mogh_loc_h($own) . '</span></td>';
    echo '<td>' . mogh_loc_h((string)$asset['notes']) . '</td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="m125bl-brand-header" style="margin-top:1rem">';
echo '<div><h2 class="m125bl-section-title" style="margin-top:0">پیش‌نمایش لوگوی برند</h2>';
mogh_loc_render_brand_logo();
echo '</div><div>';
if (!mogh_loc_brand_logo_exists()) {
    echo '<p>Logo file not found. Expected path: <code class="m360-ltr">public_html/assets/moghare360-brand/moghareh-motors-logo.jpg</code></p>';
}
echo '<p>وضعیت: <span class="m125bl-badge ' . (mogh_loc_brand_logo_exists() ? 'm125bl-badge-ok' : 'm125bl-badge-warn') . '">' . mogh_loc_h(mogh_loc_brand_logo_status()) . '</span></p>';
echo '</div></div>';

echo '<div class="m125bl-boundary-box"><strong>ممنوعیت‌ها</strong><ul>';
echo '<li>لوگوی برند خودرو بدون مجوز</li>';
echo '<li>فونت فایل‌دار بدون سند مجوز</li>';
echo '<li>تصویر third-party بدون مجوز</li>';
echo '<li>متن کپی‌شده از وب‌سایت‌های دیگر</li>';
echo '</ul></div>';

mogh_loc_render_phase125_nav();
mogh_loc_render_foot();
