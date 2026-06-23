<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-business-layer-helper.php';

try {
    $connection = bl_db();
    if ($connection !== false) {
        bl_require_auth($connection, 'business.layer.view');
        @odbc_close($connection);
    }
} catch (Throwable) {
    bl_error('وضعیت محصول', 'دسترسی به وضعیت محصول ممکن نیست.');
}

bl_render_head('وضعیت محصول');
echo '<div class="p8bl-hero"><h1>وضعیت محصول MOGHARE360</h1><p>Product Status — مرزها و آمادگی فازها</p></div>';

echo '<div class="p1cc-card"><h2 class="p8bl-section-title">وضعیت کلی</h2><table class="p1cc-table"><tbody>';
foreach (bl_product_status_rows() as $row) {
    echo '<tr><td>' . bl_h($row['label']) . '</td><td><span class="p8bl-badge ' . bl_status_badge_class($row['value']) . '">' . bl_h($row['value']) . '</span></td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p1cc-card"><h2 class="p8bl-section-title">مرزهای معماری (غیرقابل تغییر در این فاز)</h2><ul class="p8bl-boundary-list">';
foreach (bl_product_boundaries() as $b) {
    echo '<li>' . bl_h($b) . '</li>';
}
echo '</ul></div>';

echo '<div class="p1cc-card"><h2 class="p8bl-section-title">چک‌لیست URL مرورگر — ماژول‌ها</h2>';
foreach (bl_module_navigation_groups() as $group) {
    echo '<h3 class="p8bl-subsection">' . bl_h($group['group_fa']) . '</h3><ul class="p8bl-url-checklist">';
    foreach ($group['pages'] as [$file, $label]) {
        echo '<li class="p8bl-link-item">';
        if (bl_page_exists($file)) {
            echo '<a href="' . bl_h($file) . '">' . bl_h($label) . '</a>';
            echo ' <code class="m360-ltr">http://localhost:8080/moghare360/' . bl_h($file) . '</code>';
        } else {
            echo bl_h($label) . ' <span class="p8bl-badge p8bl-badge-missing">NOT FOUND</span>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
echo '</div>';

echo '<div class="p1cc-card"><h2 class="p8bl-section-title">فاز ۱۳ — سخت‌سازی امنیت</h2>';
echo '<p>وضعیت: <span class="p8bl-badge p8bl-badge-warn">BUILT AFTER TEST</span></p>';
echo '<p style="margin-bottom:.75rem"><a href="erp-security-hardening-dashboard.php">داشبورد سخت‌سازی امنیت</a> · <a href="erp-write-route-audit.php">ممیزی Write Route</a> · <a href="erp-csrf-audit.php">ممیزی CSRF</a></p></div>';

echo '<div class="p1cc-card"><h2 class="p8bl-section-title">فاز ۱۲.۵ — برند و فارسی‌سازی</h2>';
echo '<p style="margin-bottom:.75rem"><a href="erp-brand-system.php">سیستم برند</a> · <a href="erp-localization-audit.php">ممیزی فارسی‌سازی</a> · <a href="erp-asset-registry.php">دفتر ثبت دارایی</a> · <a href="moghare360-demo-package.php">بسته نمایشی</a></p></div>';

bl_render_foot();
