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
    mogh_loc_error('بسته نمایشی', 'دسترسی ممکن نیست.');
}

mogh_loc_render_head('بسته نمایشی MOGHARE360');
echo '<div class="m125bl-hero"><h1>بسته نمایشی MOGHARE360</h1>';
echo '<p>Demo Package Ready Plan — آماده‌سازی بسته نمایشی بدون Installer واقعی</p></div>';

echo '<div class="m125bl-warning-box">';
echo '<strong>هشدار:</strong> این صفحه Installer نیست. این صفحه Production Deploy نیست. این صفحه SaaS را فعال نمی‌کند.';
echo '</div>';

echo '<div class="m125bl-kpi-grid">';
echo '<div class="m125bl-kpi m125bl-kpi-neon"><div class="label">وضعیت</div><div class="value">Demo Package Ready Plan</div></div>';
echo '<div class="m125bl-kpi"><div class="label">محصول</div><div class="value">MOGHARE360 ERP</div></div>';
echo '<div class="m125bl-kpi"><div class="label">نسخه</div><div class="value">Local RC1</div></div>';
echo '</div>';

echo '<div class="m125bl-card-dark"><h2 class="m125bl-section-title">معرفی محصول</h2>';
echo '<p>MOGHARE360 ERP سیستم مدیریتی تعمیرگاه و عملیات سرویس است که از اجرای نرم داخلی تا نسخه نمایشی تجاری و اجرای آزمایشی کنترل‌شده تعمیرگاه آماده شده است.</p>';
echo '<table class="p1cc-table"><tbody>';
foreach (mogh_loc_product_status_labels() as $row) {
    echo '<tr><td>' . mogh_loc_h($row['label']) . '</td>';
    echo '<td><span class="m125bl-badge m125bl-badge-ok">' . mogh_loc_h($row['value']) . '</span></td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="m125bl-card-dark"><h2 class="m125bl-section-title">لینک صفحات Demo / Commercial</h2>';
echo '<div class="m125bl-nav-grid">';
foreach (mogh_loc_demo_package_links() as $link) {
    $file = (string)$link['file'];
    $exists = mogh_loc_page_exists($file);
    if ($exists) {
        echo '<a class="m125bl-nav-card" href="' . mogh_loc_h($file) . '">';
        echo '<span class="m125bl-nav-title">' . mogh_loc_h((string)$link['label']) . '</span>';
        echo '<span class="m125bl-nav-sub">' . mogh_loc_h($file) . '</span></a>';
    }
}
echo '</div></div>';

echo '<div class="m125bl-card-dark"><h2 class="m125bl-section-title">محتوای بسته نمایشی (Plan)</h2><ul>';
foreach (mogh_loc_demo_package_sections() as $sec) {
    $title = (string)$sec['title'];
    $path = (string)$sec['path'];
    $type = (string)$sec['type'];
    echo '<li><strong>' . mogh_loc_h($title) . '</strong> — ';
    if ($type === 'page' && mogh_loc_page_exists($path)) {
        echo '<a href="' . mogh_loc_h($path) . '">' . mogh_loc_h($path) . '</a>';
    } elseif ($type === 'doc' && mogh_loc_doc_exists($path)) {
        echo '<code class="m360-ltr">' . mogh_loc_h($path) . '</code> <span class="m125bl-badge m125bl-badge-ok">DOC OK</span>';
    } else {
        echo mogh_loc_h($path) . ' <span class="m125bl-badge m125bl-badge-warn">PENDING</span>';
    }
    echo '</li>';
}
echo '</ul></div>';

echo '<div class="m125bl-card-dark" style="text-align:center;padding:1.5rem">';
echo '<span class="m125bl-btn-disabled">آماده‌سازی بسته دانلود</span>';
echo '<p style="margin-top:.75rem;color:#9aa8b5;font-size:.88rem">بسته دانلودی واقعی در PHASE 15 ساخته می‌شود.</p>';
echo '<p style="margin-top:.5rem;color:#9aa8b5;font-size:.88rem">PHASE 14 فقط Deployment Plan است — <a href="erp-deployment-readiness-dashboard.php">داشبورد آمادگی استقرار</a></p>';
echo '</div>';

mogh_loc_render_phase125_nav();
mogh_loc_render_foot();
