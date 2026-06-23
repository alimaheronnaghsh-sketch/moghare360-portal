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
    mogh_rel_error('دانلود بسته خروجی', 'دسترسی ممکن نیست.');
}

$packages = mogh_rel_package_types();

mogh_rel_render_head('دانلود بسته خروجی MOGHARE360');
echo '<div class="p15rel-hero"><h1>دانلود بسته خروجی MOGHARE360</h1>';
echo '<p>Downloadable Release Package — فقط local/demo · نه production installer</p></div>';

echo '<div class="p15rel-warning-box"><strong>هشدار:</strong> این دانلود فقط local/demo است، نه production installer. این فایل‌ها شامل secret/private config/real data نیستند.</div>';

mogh_rel_render_warnings();

foreach ($packages as $pkg) {
    $st = mogh_rel_zip_status((string)$pkg['zip']);
    echo '<div class="p15rel-card-dark">';
    echo '<h2 class="p15rel-section-title">' . mogh_rel_h((string)$pkg['type']) . '</h2>';
    echo '<p>' . mogh_rel_h((string)$pkg['desc']) . '</p>';
    echo '<div class="p15rel-path-block"><code class="m360-ltr">' . mogh_rel_h((string)$pkg['zip']) . '</code></div>';
    echo '<p>وضعیت: <span class="p15rel-badge ' . mogh_rel_status_badge((string)$st['status']) . '">' . mogh_rel_h((string)$st['status']) . '</span></p>';
    if ($st['exists']) {
        echo '<p>حجم: ' . mogh_rel_h((string)$st['size']) . ' · آخرین تغییر: ' . mogh_rel_h((string)$st['modified']) . '</p>';
        if ((string)$st['web_path'] !== '') {
            echo '<p><a class="p15rel-download-btn" href="' . mogh_rel_h((string)$st['web_path']) . '" download>دانلود ZIP</a></p>';
        } else {
            echo '<p><span class="p15rel-badge p15rel-badge-warn">ZIP در repo موجود است — برای دانلود HTTP، public_html/release را sync کنید</span></p>';
        }
    } else {
        echo '<p><span class="p15rel-download-disabled">ZIP موجود نیست</span></p>';
        echo '<p style="margin-top:.75rem;font-size:.88rem;color:#9aa8b5">برای ساخت بسته، این دستور را در repo root اجرا کنید:</p>';
        echo '<div class="p15rel-path-block"><code class="m360-ltr">cd "' . mogh_rel_h(mogh_rel_project_root()) . '"</code><br>';
        echo '<code class="m360-ltr">' . mogh_rel_h(mogh_rel_script_command((string)$pkg['script'])) . '</code></div>';
    }
    echo '</div>';
}

echo '<p style="margin-top:1rem"><a class="p1cc-btn" href="erp-release-package-dashboard.php">بازگشت به داشبورد بسته خروجی</a></p>';

mogh_rel_render_foot();
