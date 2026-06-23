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
    bl_error('مرکز فرماندهی', 'دسترسی به مرکز فرماندهی تجاری ممکن نیست.');
}

bl_render_head('مرکز فرماندهی تجاری MOGHARE360');
echo '<div class="p8bl-hero"><h1>مرکز فرماندهی تجاری MOGHARE360</h1><p>Business Execution Layer — یکپارچه‌سازی فازهای ۱ تا ۷</p></div>';

echo '<div class="p8bl-phase-summary">';
echo '<div class="p8bl-summary-card"><span class="label">فاز ۱–۷</span><span class="value">COMPLETED</span></div>';
echo '<div class="p8bl-summary-card"><span class="label">فاز ۸</span><span class="value">UI PRODUCTIZED</span></div>';
echo '<div class="p8bl-summary-card"><span class="label">فاز ۹</span><span class="value">BUILT AFTER TEST</span></div>';
echo '<div class="p8bl-summary-card"><span class="label">فاز ۱۰</span><span class="value">COMMERCIAL DEMO</span></div>';
echo '</div>';
echo '<p style="margin-bottom:1.25rem"><a class="p1cc-btn p1cc-btn-primary" href="erp-management-dashboard.php">داشبورد مدیریت (Phase 9)</a> ';
echo '<a class="p1cc-btn" href="moghare360-commercial-demo.php">Commercial Demo (Phase 10)</a> ';
echo '<a class="p1cc-btn" href="erp-stabilization-dashboard.php">Stabilization Dashboard (Phase 11)</a></p>';

echo '<div class="p8bl-module-grid">';
foreach (bl_phase_modules() as $mod) {
    $mainExists = bl_page_exists((string)$mod['main']);
    echo '<article class="p8bl-module-card">';
    echo '<div class="p8bl-module-head"><h2>' . bl_h($mod['title_fa']) . '</h2>';
    echo '<span class="p8bl-badge ' . bl_status_badge_class((string)$mod['status']) . '">' . bl_h((string)$mod['status']) . '</span></div>';
    echo '<p class="p8bl-module-desc">' . bl_h((string)$mod['desc']) . '</p>';
    echo '<p class="p8bl-module-phase">Phase ' . bl_h((string)$mod['phase']) . ' · ' . bl_h((string)$mod['title']) . '</p>';
    if ($mainExists) {
        echo '<a class="p1cc-btn p1cc-btn-primary p8bl-main-link" href="' . bl_h((string)$mod['main']) . '">ورود به ماژول</a>';
    } else {
        echo '<span class="p8bl-badge p8bl-badge-missing">صفحه اصلی یافت نشد</span>';
    }
    echo '<ul class="p8bl-sub-links">';
    foreach ($mod['links'] as [$file, $label]) {
        bl_render_page_link($file, $label);
    }
    echo '</ul></article>';
}
echo '</div>';

bl_render_foot();
