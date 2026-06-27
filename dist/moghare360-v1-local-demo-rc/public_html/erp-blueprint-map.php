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
    bl_error('نقشه Blueprint', 'دسترسی به نقشه Blueprint ممکن نیست.');
}

bl_render_head('نقشه Blueprint');
echo '<div class="p8bl-hero"><h1>نقشه Blueprint تجاری</h1><p>مسیر Business Execution Layer از مشتری تا پشتیبانی HR</p></div>';

echo '<div class="p8bl-blueprint-flow">';
$nodes = bl_blueprint_nodes();
$last = count($nodes) - 1;
foreach ($nodes as $i => $node) {
    $status = $node['status'];
    echo '<div class="p8bl-blueprint-node">';
    echo '<div class="p8bl-node-header"><strong>' . bl_h($node['label_fa']) . '</strong>';
    echo ' <span class="p8bl-badge ' . bl_status_badge_class($status) . '">' . bl_h($status) . '</span></div>';
    echo '<div class="p8bl-node-meta">Phase ' . bl_h($node['phase']) . ' · ' . bl_h($node['label']) . '</div>';
    if (($node['url'] ?? '') !== '' && bl_page_exists($node['url'])) {
        echo '<a class="p8bl-node-link" href="' . bl_h($node['url']) . '">باز کردن صفحه</a>';
    } elseif (($node['url'] ?? '') !== '') {
        echo '<span class="p8bl-badge p8bl-badge-missing">NOT FOUND</span>';
    } else {
        echo '<span class="p8bl-hint">در فاز بعدی ساخته می‌شود</span>';
    }
    echo '</div>';
    if ($i < $last) {
        echo '<div class="p8bl-blueprint-arrow" aria-hidden="true">↓</div>';
    }
}
echo '</div>';

echo '<div class="p1cc-card p8bl-pending-note">';
echo '<h2 class="p8bl-section-title">فازهای بعدی</h2>';
echo '<p><strong>Phase 9 — Management Reporting:</strong> هنوز pending — گزارش‌گیری مدیریتی سنگین در این فاز نیست.</p>';
echo '<p><strong>Phase 10 — Commercial System:</strong> هنوز pending — دموی تجاری و SaaS واقعی فعال نیست.</p>';
echo '</div>';

bl_render_foot();
