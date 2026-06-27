<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-commercial-system-helper.php';

try {
    $c = commercial_db();
    if ($c !== false) { cs_require_auth($c, 'commercial.demo.view'); @odbc_close($c); }
} catch (Throwable) { cs_error('Commercial Demo', 'دسترسی به Demo تجاری ممکن نیست.'); }

$demos = commercial_get_demo_registry(commercial_db() ?: false);

cs_render_head('MOGHARE360 Commercial Demo');
echo '<div class="p10cs-hero"><h1>MOGHARE360 Commercial Demo</h1><p>Repair Shop Operating System — نمایش تجاری</p></div>';
echo '<div class="p10cs-warning">این Demo تجاری است، نه Production SaaS.</div>';

echo '<div class="p10cs-path"><span>Soft Run Internal ERP</span> → <span>Business Ready System</span> → <span>Commercial Demo Ready</span> → <span>SaaS Ready / Not Production SaaS</span></div>';

echo '<div class="p10cs-card-grid">';
foreach ($demos as $d) {
    $url = $d['demo_url'] ?? '';
    echo '<article class="p10cs-card">';
    echo '<h2>' . commercial_h($d['demo_title'] ?? '') . '</h2>';
    echo '<p class="p10cs-meta">' . commercial_h($d['demo_type'] ?? '') . ' · <span class="p10cs-badge ' . cs_badge_class($d['demo_status'] ?? 'READY') . '">' . commercial_h($d['demo_status'] ?? 'READY') . '</span></p>';
    if ($url !== '' && commercial_page_exists($url)) {
        echo '<a class="p1cc-btn" href="' . commercial_h($url) . '">باز کردن Demo</a>';
    } else {
        echo '<span class="p10cs-badge p10cs-badge-muted">NOT FOUND</span>';
    }
    echo '</article>';
}
echo '</div>';

echo '<div class="p1cc-card"><p>MOGHARE360 has been converted from Soft Run Internal ERP into a Business-Ready Repair Shop Operating System with Commercial Demo Readiness.</p></div>';

cs_render_foot();
