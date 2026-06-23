<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-stabilization-helper.php';

$c = false;
try {
    $c = stabilization_db();
    if ($c !== false) {
        stab_require_auth($c, 'stabilization.audit.view');
    }
} catch (Throwable) {
    stab_error('داشبورد پایداری', 'دسترسی به داشبورد پایداری ممکن نیست.');
} finally {
    if ($c !== false) {
        @odbc_close($c);
    }
}

$health = stabilization_basic_health_score();
$rc = stabilization_release_candidate_status();
$links = stabilization_broken_link_report();
$missingCount = count(array_filter($links, static fn(array $r): bool => ($r['file_status'] ?? '') === 'MISSING'));
$syntax = stabilization_php_syntax_audit();
$syntaxFail = count(array_filter($syntax, static fn(array $r): bool => ($r['status'] ?? '') !== 'OK'));
$forbidden = stabilization_forbidden_check();
$forbiddenMod = count(array_filter($forbidden, static fn(array $r): bool => ($r['status'] ?? '') === 'MODIFIED'));
$sqlReview = stabilization_expected_sql_files();
$sqlWarn = count(array_filter($sqlReview, static fn(array $r): bool => ($r['review'] ?? '') !== 'OK'));

stab_render_head('داشبورد پایداری MOGHARE360');
echo '<div class="p11st-hero"><h1>داشبورد پایداری MOGHARE360</h1><p>PHASE 11 — Stabilization Sprint · Local Release Candidate 1</p></div>';

echo '<div class="p11st-phase-row">';
foreach (stabilization_phase_status_rows() as $row) {
    $cls = ($row['phase'] ?? '') === '11' ? 'p11st-badge-warn' : 'p11st-badge-ok';
    echo '<span class="p11st-phase-chip">فاز ' . stabilization_h($row['phase']) . ': ';
    echo '<span class="p11st-badge ' . $cls . '">' . stabilization_h($row['status']) . '</span></span>';
}
echo '</div>';

echo '<div class="p11st-kpi-grid">';
echo '<div class="p11st-kpi p11st-kpi-highlight"><div class="label">Health Score</div><div class="value m360-num">' . stabilization_h((string)$health) . '%</div></div>';
echo '<div class="p11st-kpi"><div class="label">URL Registry</div><div class="value">' . stabilization_h((string)(count($links) - $missingCount)) . '/' . stabilization_h((string)count($links)) . '</div></div>';
echo '<div class="p11st-kpi"><div class="label">PHP Syntax</div><div class="value">' . stabilization_h($syntaxFail === 0 ? 'OK' : (string)$syntaxFail . ' FAIL') . '</div></div>';
echo '<div class="p11st-kpi"><div class="label">Forbidden Files</div><div class="value">' . stabilization_h($forbiddenMod === 0 ? 'OK' : 'MODIFIED') . '</div></div>';
echo '<div class="p11st-kpi"><div class="label">Local RC1</div><div class="value" style="font-size:.95rem">' . stabilization_h($rc['status']) . '</div></div>';
echo '</div>';

echo '<div class="p11st-boundary-box"><strong>Product Boundary</strong><ul>';
foreach (stabilization_boundary_labels() as $label) {
    echo '<li>' . stabilization_h($label) . '</li>';
}
echo '</ul></div>';

echo '<div class="p1cc-card"><h2 class="p11st-section-title">گزارش‌های پایداری (Phase 11)</h2><div class="p11st-nav-grid">';
$reports = [
    ['erp-broken-link-report.php', 'گزارش لینک‌ها', 'URL registry + file status'],
    ['erp-ui-polish-report.php', 'گزارش UI Polish', 'RTL · titles · boundaries'],
    ['erp-db-consistency-check.php', 'بررسی یکپارچگی DB', 'جداول Phase 1–10'],
    ['erp-local-release-candidate.php', 'Local Release Candidate 1', 'MOGHARE360 RC1'],
    ['erp-soft-run-pilot-center.php', 'Soft Run Pilot Center', 'Phase 12 controlled pilot'],
];
foreach ($reports as [$url, $title, $sub]) {
    echo '<a class="p11st-nav-card" href="' . stabilization_h($url) . '">';
    echo '<span class="p11st-nav-title">' . stabilization_h($title) . '</span>';
    echo '<span class="p11st-nav-sub">' . stabilization_h($sub) . '</span></a>';
}
echo '</div></div>';

echo '<div class="p1cc-card"><h2 class="p11st-section-title">Health Cards</h2><table class="p1cc-table"><thead><tr><th>حوزه</th><th>وضعیت</th><th>یادداشت</th></tr></thead><tbody>';
$cards = [
    ['File Registry', $missingCount === 0 ? 'OK' : 'WARNING', $missingCount . ' missing'],
    ['URL Registry', $missingCount === 0 ? 'OK' : 'WARNING', count($links) . ' URLs'],
    ['PHP Syntax Audit', $syntaxFail === 0 ? 'OK' : 'FAILED', count($syntax) . ' files'],
    ['Browser URL Audit', 'PENDING USER CHECK', 'Manual browser test required'],
    ['UI Polish', 'AVAILABLE', 'erp-ui-polish-report.php'],
    ['Database Consistency', 'AVAILABLE', 'erp-db-consistency-check.php'],
    ['Forbidden File Boundary', $forbiddenMod === 0 ? 'OK' : 'FAILED', 'Git status check'],
    ['SQL Idempotency Review', $sqlWarn === 0 ? 'OK' : 'WARNING', count($sqlReview) . ' SQL files'],
    ['Local Release Candidate', $rc['status'], 'RC1 report'],
];
foreach ($cards as [$area, $status, $note]) {
    echo '<tr><td>' . stabilization_h($area) . '</td>';
    echo '<td><span class="p11st-badge ' . stab_badge_class($status) . '">' . stabilization_h($status) . '</span></td>';
    echo '<td>' . stabilization_h($note) . '</td></tr>';
}
echo '</tbody></table></div>';

stab_render_foot();
