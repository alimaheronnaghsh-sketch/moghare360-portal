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
    mogh_loc_error('سیستم برند', 'دسترسی ممکن نیست.');
}

mogh_loc_render_head('سیستم برند MOGHARE360');
echo '<div class="m125bl-hero"><h1>سیستم برند MOGHARE360</h1>';
echo '<p>Brand System — Industrial Premium Persian ERP</p></div>';

echo '<div class="m125bl-brand-header">';
mogh_loc_render_brand_logo();
echo '<div><h2 style="margin:0 0 .35rem;color:#39ff14">MOGHARE360 ERP</h2>';
echo '<p style="margin:0 0 .5rem;color:#9aa8b5">نسخه: Local Release Candidate 1 / Controlled Pilot Ready</p>';
echo '<p style="margin:0;color:#e8ecef">از فرآیند تعمیرگاه تا محصول نرم‌افزاری</p></div></div>';

if (!mogh_loc_brand_logo_exists()) {
    echo '<div class="m125bl-warning-box">Logo file not found. Expected path: <code class="m360-ltr">public_html/assets/moghare360-brand/moghareh-motors-logo.jpg</code></div>';
}

echo '<div class="m125bl-kpi-grid">';
echo '<div class="m125bl-kpi m125bl-kpi-neon"><div class="label">نام برند</div><div class="value">MOGHAREH MOTORS</div></div>';
echo '<div class="m125bl-kpi"><div class="label">نام محصول</div><div class="value">MOGHARE360 ERP</div></div>';
echo '<div class="m125bl-kpi"><div class="label">وضعیت لوگو</div><div class="value">' . mogh_loc_h(mogh_loc_brand_logo_status()) . '</div></div>';
echo '</div>';

echo '<div class="m125bl-card-dark"><h2 class="m125bl-section-title">پالت رنگ</h2><ul style="list-style:none;padding:0;margin:0">';
$colors = [
    ['#0f1419', 'زغالی / پس‌زمینه'],
    ['#39ff14', 'سبز نئون برند'],
    ['#e8ecef', 'متن روشن'],
    ['#d4af37', 'طلایی محدود (Premium)'],
];
foreach ($colors as [$hex, $label]) {
    echo '<li style="margin-bottom:.5rem"><span class="m125bl-color-swatch" style="background:' . mogh_loc_h($hex) . '"></span> ' . mogh_loc_h($label) . ' <code class="m360-ltr">' . mogh_loc_h($hex) . '</code></li>';
}
echo '</ul></div>';

echo '<div class="m125bl-card-dark"><h2 class="m125bl-section-title">Typography Stack</h2>';
echo '<p><code class="m360-ltr">Vazirmatn, Tahoma, Segoe UI, Arial, sans-serif</code></p>';
echo '<p style="color:#9aa8b5;font-size:.88rem">بدون فایل فونت تجاری — فقط CSS font stack.</p></div>';

echo '<div class="m125bl-card-dark"><h2 class="m125bl-section-title">نمونه Badge و Warning</h2>';
echo '<p><span class="m125bl-badge m125bl-badge-ok">آماده</span> ';
echo '<span class="m125bl-badge m125bl-badge-warn">اجرای آزمایشی</span> ';
echo '<span class="m125bl-badge m125bl-badge-neon">نسخه نمایشی</span> ';
echo '<span class="m125bl-badge m125bl-badge-gold">Premium</span> ';
echo '<span class="m125bl-badge m125bl-badge-muted">فعال نیست</span></p>';
echo '<div class="m125bl-warning-box" style="margin-top:.75rem">Pilot / Demo / Not Production — این صفحه Production Deploy نیست.</div></div>';

echo '<div class="m125bl-card-dark"><h2 class="m125bl-section-title">نمونه دکمه</h2>';
echo '<a class="p1cc-btn p1cc-btn-primary" href="erp-localization-audit.php">ممیزی فارسی‌سازی</a> ';
echo '<span class="m125bl-btn-disabled">غیرفعال (نمونه)</span></div>';

echo '<div class="m125bl-boundary-box"><strong>مرزهای محصول (Product Boundaries)</strong><ul>';
foreach (mogh_loc_boundary_labels() as $label) {
    echo '<li>' . mogh_loc_h($label) . '</li>';
}
echo '</ul></div>';

mogh_loc_render_phase125_nav();
mogh_loc_render_foot();
