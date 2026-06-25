<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 2D JobCard Evidence Gate CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-gate-helper.php';
$reviewPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-evidence-review.php';
$cameraHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-camera-media-helper.php';
$diagnosticHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-diagnostic-file-helper.php';

require_once $helperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2D_JOBCARD_EVIDENCE_GATE_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2D_JOBCARD_EVIDENCE_GATE_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2D_JOBCARD_EVIDENCE_GATE_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2D_JOBCARD_EVIDENCE_GATE_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$reviewContent = is_file($reviewPath) ? (string)file_get_contents($reviewPath) : '';
$cameraHelperContent = is_file($cameraHelperPath) ? (string)file_get_contents($cameraHelperPath) : '';
$diagnosticHelperContent = is_file($diagnosticHelperPath) ? (string)file_get_contents($diagnosticHelperPath) : '';

$results = [];

$results[] = ['name' => 'Evidence helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Review page exists', 'pass' => is_file($reviewPath)];

$requiredApis = [
    'moghare360_jobcard_evidence_allowed_required_items',
    'moghare360_jobcard_evidence_fetch_media',
    'moghare360_jobcard_evidence_fetch_history_count',
    'moghare360_jobcard_evidence_evaluate',
    'moghare360_jobcard_evidence_status_label',
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
    'name' => 'Helper does not write INSERT/UPDATE/DELETE',
    'pass' => $helperContent !== ''
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent)
        && !preg_match('/\bUPDATE\s+dbo\./i', $helperContent)
        && !preg_match('/\bDELETE\s+FROM\b/i', $helperContent),
];

$results[] = [
    'name' => 'Review page accepts jobcard_id',
    'pass' => $reviewContent !== ''
        && str_contains($reviewContent, 'jobcard_id')
        && str_contains($reviewContent, 'moghare360_jobcard_evidence_review'),
];

$results[] = [
    'name' => 'Review page links to camera capture',
    'pass' => $reviewContent !== '' && str_contains($reviewContent, 'erp-jobcard-camera-capture.php'),
];

$results[] = [
    'name' => 'Review page links to media preview',
    'pass' => $reviewContent !== '' && str_contains($reviewContent, 'erp-jobcard-media-preview.php'),
];

$results[] = [
    'name' => 'Review page links to diagnostic file page',
    'pass' => $reviewContent !== '' && str_contains($reviewContent, 'erp-jobcard-diagnostic-file.php'),
];

$results[] = [
    'name' => 'Review page links to diagnostic preview',
    'pass' => $reviewContent !== '' && str_contains($reviewContent, 'erp-jobcard-diagnostic-preview.php'),
];

$results[] = [
    'name' => 'Review page has no file upload input',
    'pass' => $reviewContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $reviewContent),
];

$wave2dSqlCreated = false;
foreach (glob($root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_2d*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave2dSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for Wave 2D', 'pass' => !$wave2dSqlCreated];

$results[] = [
    'name' => 'Camera helper unchanged (no evidence gate reference)',
    'pass' => $cameraHelperContent !== '' && !str_contains($cameraHelperContent, 'moghare360-jobcard-evidence-gate-helper'),
];

$results[] = [
    'name' => 'Diagnostic helper unchanged (no evidence gate reference)',
    'pass' => $diagnosticHelperContent !== '' && !str_contains($diagnosticHelperContent, 'moghare360-jobcard-evidence-gate-helper'),
];

$evalComplete = moghare360_jobcard_evidence_evaluate(1, [
    ['media_stage' => 'input', 'media_type' => 'front'],
    ['media_stage' => 'input', 'media_type' => 'odometer'],
    ['media_stage' => 'output', 'media_type' => 'front'],
    ['media_stage' => 'diagnostic_initial', 'media_type' => 'diagnostic'],
], 2);
$results[] = [
    'name' => 'Evaluate returns COMPLETE for full evidence set',
    'pass' => ($evalComplete['status'] ?? '') === MOGHARE360_JOBCARD_EVIDENCE_STATUS_COMPLETE,
];

$evalEmpty = moghare360_jobcard_evidence_evaluate(99, [], 0);
$results[] = [
    'name' => 'Evaluate returns EMPTY for no media',
    'pass' => ($evalEmpty['status'] ?? '') === MOGHARE360_JOBCARD_EVIDENCE_STATUS_EMPTY,
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
    fwrite(STDERR, 'WAVE 2D JOBCARD EVIDENCE GATE TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 2D JOBCARD EVIDENCE GATE TEST PASSED' . PHP_EOL;
exit(0);
