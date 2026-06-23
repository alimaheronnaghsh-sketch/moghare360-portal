<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-stabilization-helper.php';

$c = false;
$dbError = '';
try {
    $c = stabilization_db();
    if ($c !== false) {
        stab_require_auth($c, 'stabilization.audit.view');
    } else {
        $dbError = 'اتصال به پایگاه داده برقرار نشد. گزارش فقط وضعیت MISSING نشان می‌دهد.';
    }
} catch (Throwable) {
    stab_error('بررسی یکپارچگی DB', 'دسترسی ممکن نیست.');
}

$report = stabilization_db_consistency_report($c);
if ($c !== false) {
    @odbc_close($c);
}

stab_render_head('بررسی یکپارچگی پایگاه داده');
echo '<div class="p11st-hero"><h1>بررسی یکپارچگی DB</h1><p>Database: moghare360_ERP · Read-only · بدون write</p></div>';
if ($dbError !== '') {
    echo '<div class="p1cc-card p1cc-error"><p>' . stabilization_h($dbError) . '</p></div>';
} else {
    $s = $report['summary'];
    echo '<div class="p11st-kpi-grid">';
    echo '<div class="p11st-kpi p11st-kpi-highlight"><div class="label">OK</div><div class="value m360-num">' . stabilization_h((string)($s['ok'] ?? 0)) . '</div></div>';
    echo '<div class="p11st-kpi"><div class="label">MISSING</div><div class="value m360-num">' . stabilization_h((string)($s['missing'] ?? 0)) . '</div></div>';
    echo '<div class="p11st-kpi"><div class="label">Errors</div><div class="value m360-num">' . stabilization_h((string)($s['error'] ?? 0)) . '</div></div>';
    echo '</div>';
}

foreach ($report['groups'] as $group => $tables) {
    echo '<div class="p1cc-card"><h2 class="p11st-section-title">' . stabilization_h($group) . '</h2>';
    echo '<table class="p1cc-table"><thead><tr><th>جدول</th><th>وضعیت</th><th>تعداد ردیف</th></tr></thead><tbody>';
    foreach ($tables as $row) {
        $st = (string)($row['status'] ?? 'MISSING');
        $cnt = $row['count'];
        echo '<tr><td class="m360-num">' . stabilization_h((string)($row['table'] ?? '')) . '</td>';
        echo '<td><span class="p11st-badge ' . stab_badge_class($st) . '">' . stabilization_h($st) . '</span></td>';
        echo '<td class="m360-num">' . ($cnt === null ? '—' : stabilization_h((string)$cnt)) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

stab_render_foot();
