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
    bl_error('راهبری ماژول', 'دسترسی به راهبری ماژول ممکن نیست.');
}

bl_render_head('راهبری ماژول‌ها');
echo '<div class="p8bl-hero"><h1>راهبری ماژول‌ها</h1><p>Module Navigation — لایه‌های Business Execution</p></div>';

foreach (bl_module_navigation_groups() as $group) {
    echo '<section class="p1cc-card p8bl-nav-group">';
    echo '<h2 class="p8bl-section-title">' . bl_h($group['group_fa']) . ' <span class="p8bl-layer-code">' . bl_h($group['layer']) . '</span></h2>';
    echo '<ul class="p8bl-nav-list">';
    foreach ($group['pages'] as [$file, $label]) {
        bl_render_page_link($file, $label);
    }
    echo '</ul></section>';
}

bl_render_foot();
