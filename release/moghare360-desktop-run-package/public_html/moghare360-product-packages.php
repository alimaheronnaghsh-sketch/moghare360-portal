<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-commercial-system-helper.php';

$c = commercial_db();
$plans = commercial_get_package_plans($c ?: false);
if ($c !== false) @odbc_close($c);

try {
    $auth = commercial_db();
    if ($auth !== false) { cs_require_auth($auth, 'commercial.demo.view'); @odbc_close($auth); }
} catch (Throwable) { cs_error('Product Packages', 'دسترسی ممکن نیست.'); }

cs_render_head('Product Packages');
echo '<div class="p10cs-hero"><h1>بسته‌های محصول MOGHARE360</h1><p>Product Package Plans — preview</p></div>';
echo '<div class="p10cs-warning">قیمت‌ها draft/preview هستند و قرارداد فروش رسمی نیستند.</div>';

echo '<div class="p10cs-package-grid">';
foreach ($plans as $p) {
    echo '<article class="p10cs-package-card">';
    echo '<h2>' . commercial_h($p['package_name'] ?? '') . '</h2>';
    echo '<span class="p10cs-badge ' . cs_badge_class($p['package_tier'] ?? '') . '">' . commercial_h($p['package_tier'] ?? '') . '</span>';
    echo '<p>' . commercial_h($p['package_description'] ?? '') . '</p>';
    echo '<p><strong>مشتری هدف:</strong> ' . commercial_h($p['target_customer'] ?? '—') . '</p>';
    echo '<p><strong>ماهانه:</strong> ' . commercial_h(commercial_format_price($p['monthly_price_preview'] ?? null)) . '</p>';
    echo '<p><strong>راه‌اندازی:</strong> ' . commercial_h(commercial_format_price($p['setup_price_preview'] ?? null)) . '</p>';
    echo '<p class="p10cs-meta"><strong>شامل:</strong> ' . commercial_h($p['included_modules'] ?? '—') . '</p>';
    echo '<p class="p10cs-meta"><strong>خارج:</strong> ' . commercial_h($p['excluded_modules'] ?? '—') . '</p>';
    echo '</article>';
}
echo '</div>';

cs_render_foot();
