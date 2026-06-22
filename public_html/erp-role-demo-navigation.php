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
    bl_error('Demo Navigation', 'دسترسی به Demo Navigation ممکن نیست.');
}

bl_render_head('Demo Navigation نقش‌ها');
echo '<div class="p8bl-hero"><h1>Demo Navigation نقش‌ها</h1><p>راهبری نمایشی — بدون تغییر Permission واقعی</p></div>';

echo '<div class="p8bl-demo-warning">این صفحه Demo Navigation است و Permission واقعی را تغییر نمی‌دهد.</div>';

echo '<div class="p8bl-role-grid">';
foreach (bl_role_demo_groups() as $group) {
    echo '<article class="p1cc-card p8bl-role-card">';
    echo '<h2 class="p8bl-section-title">' . bl_h($group['role_fa']) . '</h2>';
    echo '<p class="p8bl-role-en">' . bl_h($group['role']) . '</p>';
    echo '<ul class="p8bl-nav-list">';
    foreach ($group['links'] as [$file, $label]) {
        bl_render_page_link($file, $label);
    }
    echo '</ul></article>';
}
echo '</div>';

bl_render_foot();
