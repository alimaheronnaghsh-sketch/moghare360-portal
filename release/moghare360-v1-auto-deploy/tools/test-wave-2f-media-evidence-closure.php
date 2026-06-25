<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 2F Media Evidence Closure CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-2-closure-helper.php';
$dashboardPath = $public . DIRECTORY_SEPARATOR . 'erp-media-evidence-closure-dashboard.php';
$cameraHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-camera-media-helper.php';
$diagnosticHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-diagnostic-file-helper.php';
$gateHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-gate-helper.php';
$timelineHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-timeline-helper.php';

require_once $helperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2F_MEDIA_EVIDENCE_CLOSURE_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2F_MEDIA_EVIDENCE_CLOSURE_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2F_MEDIA_EVIDENCE_CLOSURE_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2F_MEDIA_EVIDENCE_CLOSURE_SIGNOFF.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2_FINAL_CLOSURE_REPORT.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$dashboardContent = is_file($dashboardPath) ? (string)file_get_contents($dashboardPath) : '';
$cameraHelperContent = is_file($cameraHelperPath) ? (string)file_get_contents($cameraHelperPath) : '';
$diagnosticHelperContent = is_file($diagnosticHelperPath) ? (string)file_get_contents($diagnosticHelperPath) : '';
$gateHelperContent = is_file($gateHelperPath) ? (string)file_get_contents($gateHelperPath) : '';
$timelineHelperContent = is_file($timelineHelperPath) ? (string)file_get_contents($timelineHelperPath) : '';

$results = [];

$results[] = ['name' => 'Closure helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Closure dashboard page exists', 'pass' => is_file($dashboardPath)];

$requiredApis = [
    'moghare360_wave_2_closure_fetch_summary',
    'moghare360_wave_2_closure_fetch_recent_media',
    'moghare360_wave_2_closure_fetch_recent_history',
    'moghare360_wave_2_closure_status',
];

$apiPass = true;
foreach ($requiredApis as $api) {
    if (!function_exists($api)) {
        $apiPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper contains required APIs', 'pass' => $apiPass];

$results[] = [
    'name' => 'Helper reads from dbo.erp_jobcard_media',
    'pass' => $helperContent !== '' && str_contains($helperContent, 'FROM dbo.erp_jobcard_media'),
];

$results[] = [
    'name' => 'Helper reads from dbo.erp_jobcard_media_history',
    'pass' => $helperContent !== '' && str_contains($helperContent, 'FROM dbo.erp_jobcard_media_history'),
];

$results[] = [
    'name' => 'Helper does not write INSERT/UPDATE/DELETE',
    'pass' => $helperContent !== ''
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent)
        && !preg_match('/\bUPDATE\s+dbo\./i', $helperContent)
        && !preg_match('/\bDELETE\s+FROM\b/i', $helperContent),
];

$linkChecks = [
    'erp-jobcard-camera-capture.php' => 'camera capture',
    'erp-jobcard-media-preview.php' => 'media preview',
    'erp-jobcard-diagnostic-file.php' => 'diagnostic file',
    'erp-jobcard-diagnostic-preview.php' => 'diagnostic preview',
    'erp-jobcard-evidence-review.php' => 'evidence review',
    'erp-jobcard-evidence-timeline.php' => 'evidence timeline',
];

foreach ($linkChecks as $href => $label) {
    $results[] = [
        'name' => 'Dashboard links to ' . $label,
        'pass' => $dashboardContent !== '' && str_contains($dashboardContent, $href),
    ];
}

$results[] = [
    'name' => 'Dashboard has no file input',
    'pass' => $dashboardContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $dashboardContent),
];

$results[] = [
    'name' => 'Dashboard uses read-only closure status',
    'pass' => $dashboardContent !== '' && str_contains($dashboardContent, 'moghare360_wave_2_closure_status'),
];

$wave2fSqlCreated = false;
foreach (glob($root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_2f*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave2fSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for Wave 2F', 'pass' => !$wave2fSqlCreated];

$results[] = [
    'name' => 'Camera helper unchanged',
    'pass' => $cameraHelperContent !== '' && !str_contains($cameraHelperContent, 'wave-2-closure'),
];

$results[] = [
    'name' => 'Diagnostic helper unchanged',
    'pass' => $diagnosticHelperContent !== '' && !str_contains($diagnosticHelperContent, 'wave-2-closure'),
];

$results[] = [
    'name' => 'Evidence gate helper unchanged',
    'pass' => $gateHelperContent !== '' && !str_contains($gateHelperContent, 'wave-2-closure'),
];

$results[] = [
    'name' => 'Evidence timeline helper unchanged',
    'pass' => $timelineHelperContent !== '' && !str_contains($timelineHelperContent, 'wave-2-closure'),
];

$status = moghare360_wave_2_closure_status();
$results[] = [
    'name' => 'Closure status returns valid status key',
    'pass' => in_array($status['status'] ?? '', [
        MOGHARE360_WAVE2_CLOSURE_STATUS_READY,
        MOGHARE360_WAVE2_CLOSURE_STATUS_PARTIAL,
        MOGHARE360_WAVE2_CLOSURE_STATUS_EMPTY,
        MOGHARE360_WAVE2_CLOSURE_STATUS_ERROR,
    ], true),
];

$results[] = [
    'name' => 'Closure status includes no_db_write_required check',
    'pass' => ($status['checks']['no_db_write_required'] ?? false) === true,
];

foreach ($docs as $docPath) {
    $results[] = ['name' => 'Doc exists: ' . basename($docPath), 'pass' => is_file($docPath)];
}

$passed = 0;
$failed = 0;

foreach ($results as $row) {
    $label = $row['pass'] ? 'PASS' : 'FAIL';
    echo $label . ' — ' . $row['name'] . PHP_EOL;
    if ($row['pass']) {
        $passed++;
    } else {
        $failed++;
    }
}

echo PHP_EOL;
echo 'Passed: ' . $passed . ' / ' . count($results) . PHP_EOL;

if ($failed > 0) {
    fwrite(STDERR, 'WAVE 2F MEDIA EVIDENCE CLOSURE TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 2F MEDIA EVIDENCE CLOSURE TEST PASSED' . PHP_EOL;
exit(0);
