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
    mogh_loc_error('ممیزی فارسی‌سازی', 'دسترسی ممکن نیست.');
}

$registry = mogh_loc_page_registry();

mogh_loc_render_head('ممیزی فارسی‌سازی محصول');
echo '<div class="m125bl-hero"><h1>ممیزی فارسی‌سازی محصول</h1>';
echo '<p>Localization Audit — وضعیت تقریبی فارسی/انگلیسی صفحات اصلی (registry/static)</p></div>';

mogh_loc_render_phase125_nav();

echo '<div class="m125bl-card-dark"><h2 class="m125bl-section-title">صفحات اصلی محصول</h2>';
echo '<table class="p1cc-table"><thead><tr><th>صفحه</th><th>عنوان فارسی</th><th>عنوان انگلیسی</th><th>وضعیت زبان</th></tr></thead><tbody>';
foreach ($registry['audit_pages'] as $page) {
    $exists = mogh_loc_page_exists((string)$page['file']);
    $st = (string)($page['status'] ?? 'NEEDS REVIEW');
    echo '<tr><td>';
    if ($exists) {
        echo '<a href="' . mogh_loc_h((string)$page['file']) . '">' . mogh_loc_h((string)$page['file']) . '</a>';
    } else {
        echo mogh_loc_h((string)$page['file']);
    }
    echo '</td><td>' . mogh_loc_h((string)$page['title_fa']) . '</td>';
    echo '<td>' . mogh_loc_h((string)$page['title_en']) . '</td>';
    echo '<td><span class="m125bl-badge ' . mogh_loc_status_badge_class($st) . '">' . mogh_loc_h($st) . '</span></td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="m125bl-card-dark"><h2 class="m125bl-section-title">صفحات Phase 12.5</h2><ul>';
foreach ($registry['phase_12_5_pages'] as $p) {
    $file = (string)$p['file'];
    echo '<li>';
    if (mogh_loc_page_exists($file)) {
        echo '<a href="' . mogh_loc_h($file) . '">' . mogh_loc_h((string)$p['title_fa']) . '</a>';
    } else {
        echo mogh_loc_h((string)$p['title_fa']) . ' <span class="m125bl-badge m125bl-badge-fail">MISSING</span>';
    }
    echo '</li>';
}
echo '</ul></div>';

echo '<div class="m125bl-card-dark"><h2 class="m125bl-section-title">اصطلاحات انگلیسی مجاز (واژه‌نامه رسمی)</h2>';
echo '<table class="p1cc-table"><thead><tr><th>انگلیسی</th><th>معادل فارسی</th></tr></thead><tbody>';
foreach (mogh_loc_dictionary() as $en => $fa) {
    echo '<tr><td>' . mogh_loc_h($en) . '</td><td>' . mogh_loc_h($fa) . '</td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="m125bl-card-dark"><h2 class="m125bl-section-title">اصطلاحات نیازمند بازبینی</h2><ul>';
foreach ($registry['needs_review'] as $term) {
    echo '<li><span class="m125bl-badge m125bl-badge-warn">' . mogh_loc_h($term) . '</span> — باید کنار توضیح فارسی نمایش داده شود.</li>';
}
echo '</ul></div>';

echo '<div class="m125bl-boundary-box"><strong>قانون زبان</strong><ul>';
echo '<li>حذف کور انگلیسی ممنوع است.</li>';
echo '<li>اصطلاحات فنی مجازند، اما کنارشان توضیح فارسی بیاید.</li>';
echo '<li>وضعیت OK = فارسی غالب · NEEDS REVIEW = ترکیبی · HIGH ENGLISH CONTENT = انگلیسی زیاد</li>';
echo '</ul></div>';

mogh_loc_render_foot();
