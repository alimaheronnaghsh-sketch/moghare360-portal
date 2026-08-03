<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "CLI-only\n";
    exit(1);
}
require __DIR__ . '/../public_html/includes/m360-access-matrix-catalog.php';
$d = m360_access_matrix_catalog_definitions();
$w = array_filter($d, fn($r) => $r['module_key'] === 'workshop');
$l = array_filter($d, fn($r) => $r['module_key'] === 'workshop_legacy');
$enf = [];
foreach ($d as $r) {
    $enf[$r['enforcement_state']] = ($enf[$r['enforcement_state']] ?? 0) + 1;
}
echo 'catalog_total=' . count($d) . "\n";
echo 'workshop=' . count($w) . "\n";
echo 'legacy=' . count($l) . "\n";
foreach ($enf as $k => $v) {
    echo "enf_$k=$v\n";
}
$g = [];
foreach ($w as $r) {
    $g[$r['group_key']] = ($g[$r['group_key']] ?? 0) + 1;
}
foreach ($g as $k => $v) {
    echo "group_$k=$v\n";
}
$assign = 0;
$not = 0;
foreach ($w as $r) {
    if (!empty($r['is_assignable'])) {
        $assign++;
    } else {
        $not++;
    }
}
echo "workshop_assignable=$assign\nworkshop_not_assignable=$not\n";
$jobcardLabels = 0;
foreach ($w as $r) {
    if (stripos($r['title_fa'], 'JobCard') !== false || stripos($r['title_fa'], 'jobcard') !== false) {
        $jobcardLabels++;
    }
}
echo "raw_jobcard_labels=$jobcardLabels\n";
