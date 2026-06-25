<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-release-package-helper.php';

try {
    $c = mogh_rel_db();
    if ($c !== false) {
        mogh_rel_require_auth($c, 'release.package.view');
        @odbc_close($c);
    }
} catch (Throwable) {
    mogh_rel_error('داشبورد بسته خروجی', 'دسترسی ممکن نیست.');
}

$packages = mogh_rel_package_types();

mogh_rel_render_head('داشبورد بسته خروجی');
echo '<div class="p15rel-hero"><h1>داشبورد بسته خروجی</h1>';
echo '<p>Downloadable Release Package — Demo و Local Release Candidate 1</p></div>';

mogh_rel_render_warnings();

echo '<div class="p15rel-kpi-grid">';
foreach ($packages as $pkg) {
    $st = mogh_rel_zip_status((string)$pkg['zip']);
    echo '<div class="p15rel-kpi"><div class="label">' . mogh_rel_h((string)$pkg['type']) . '</div>';
    echo '<div class="value"><span class="p15rel-badge ' . mogh_rel_status_badge((string)$st['status']) . '">' . mogh_rel_h((string)$st['status']) . '</span></div></div>';
}
echo '</div>';

echo '<div class="p15rel-nav-grid">';
echo '<a class="p15rel-nav-card" href="moghare360-release-download.php"><span class="p15rel-nav-title">دانلود بسته خروجی</span><span class="p15rel-nav-sub">Release Download</span></a>';
echo '<a class="p15rel-nav-card" href="moghare360-demo-package.php"><span class="p15rel-nav-title">بسته نمایشی</span><span class="p15rel-nav-sub">Demo Package Plan</span></a>';
echo '<a class="p15rel-nav-card" href="erp-deployment-readiness-dashboard.php"><span class="p15rel-nav-title">آمادگی استقرار</span><span class="p15rel-nav-sub">Phase 14</span></a>';
echo '<a class="p15rel-nav-card" href="erp-production-readiness-checklist.php"><span class="p15rel-nav-title">چک‌لیست Production</span><span class="p15rel-nav-sub">Readiness</span></a>';
echo '</div>';

echo '<div class="p15rel-card-dark"><h2 class="p15rel-section-title">انواع بسته</h2>';
foreach ($packages as $pkg) {
    $st = mogh_rel_zip_status((string)$pkg['zip']);
    echo '<div class="p15rel-path-block"><strong>' . mogh_rel_h((string)$pkg['type']) . '</strong><br>';
    echo '<code class="m360-ltr">' . mogh_rel_h((string)$pkg['zip']) . '</code> — ';
    echo '<span class="p15rel-badge ' . mogh_rel_status_badge((string)$st['status']) . '">' . mogh_rel_h((string)$st['status']) . '</span>';
    if ($st['exists']) {
        echo '<br>حجم: ' . mogh_rel_h((string)$st['size']) . ' · آخرین تغییر: ' . mogh_rel_h((string)$st['modified']);
    }
    echo '<p style="margin:.5rem 0 0;font-size:.85rem;color:#9aa8b5">' . mogh_rel_h((string)$pkg['desc']) . '</p>';
    echo '<p style="margin:.35rem 0 0;font-size:.82rem"><code class="m360-ltr">' . mogh_rel_h(mogh_rel_script_command((string)$pkg['script'])) . '</code></p></div>';
}
echo '</div>';

echo '<div class="p15rel-card-dark"><h2 class="p15rel-section-title">شامل (Included)</h2><ul class="p15rel-manifest-list">';
foreach (mogh_rel_included_items() as $item) {
    echo '<li>' . mogh_rel_h($item) . '</li>';
}
echo '</ul></div>';

echo '<div class="p15rel-card-dark"><h2 class="p15rel-section-title">مستثنی (Excluded)</h2><ul class="p15rel-manifest-list">';
foreach (mogh_rel_excluded_items() as $item) {
    echo '<li>' . mogh_rel_h($item) . '</li>';
}
echo '</ul></div>';

echo '<div class="p15rel-card-dark"><h2 class="p15rel-section-title">مستندات Release</h2><ul class="p15rel-manifest-list">';
$docs = [
    'docs/release/MOGHARE360_RELEASE_PACKAGE_MANIFEST.md',
    'docs/release/MOGHARE360_DEMO_PACKAGE_MANIFEST.md',
    'docs/release/MOGHARE360_LOCAL_RC1_RELEASE_NOTES.md',
];
foreach ($docs as $doc) {
    $ok = is_file(mogh_rel_project_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $doc));
    echo '<li><code class="m360-ltr">' . mogh_rel_h($doc) . '</code> ';
    echo '<span class="p15rel-badge ' . ($ok ? 'p15rel-badge-ok' : 'p15rel-badge-warn') . '">' . ($ok ? 'OK' : 'PENDING') . '</span></li>';
}
echo '</ul></div>';

mogh_rel_render_foot();
