<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 2E Evidence Timeline Audit CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-timeline-helper.php';
$timelinePath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-evidence-timeline.php';
$reviewPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-evidence-review.php';
$cameraHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-camera-media-helper.php';
$diagnosticHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-diagnostic-file-helper.php';
$gateHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-gate-helper.php';

require_once $helperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2E_EVIDENCE_TIMELINE_AUDIT_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2E_EVIDENCE_TIMELINE_AUDIT_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2E_EVIDENCE_TIMELINE_AUDIT_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2E_EVIDENCE_TIMELINE_AUDIT_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$timelineContent = is_file($timelinePath) ? (string)file_get_contents($timelinePath) : '';
$reviewContent = is_file($reviewPath) ? (string)file_get_contents($reviewPath) : '';
$cameraHelperContent = is_file($cameraHelperPath) ? (string)file_get_contents($cameraHelperPath) : '';
$diagnosticHelperContent = is_file($diagnosticHelperPath) ? (string)file_get_contents($diagnosticHelperPath) : '';
$gateHelperContent = is_file($gateHelperPath) ? (string)file_get_contents($gateHelperPath) : '';

$results = [];

$results[] = ['name' => 'Timeline helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Timeline page exists', 'pass' => is_file($timelinePath)];

$requiredApis = [
    'moghare360_jobcard_evidence_timeline_fetch_media',
    'moghare360_jobcard_evidence_timeline_fetch_history',
    'moghare360_jobcard_evidence_timeline_build',
    'moghare360_jobcard_evidence_timeline_stage_label',
    'moghare360_jobcard_evidence_timeline_event_label',
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

$results[] = [
    'name' => 'Helper does not expose file_path in timeline build',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360_jobcard_evidence_timeline_safe_relative_path')
        && !str_contains($helperContent, "'file_path'"),
];

$results[] = [
    'name' => 'Timeline page accepts jobcard_id',
    'pass' => $timelineContent !== ''
        && str_contains($timelineContent, 'jobcard_id')
        && str_contains($timelineContent, 'moghare360_jobcard_evidence_timeline_review'),
];

$results[] = [
    'name' => 'Timeline page links to evidence review',
    'pass' => $timelineContent !== '' && str_contains($timelineContent, 'erp-jobcard-evidence-review.php'),
];

$results[] = [
    'name' => 'Timeline page links to media preview',
    'pass' => $timelineContent !== '' && str_contains($timelineContent, 'erp-jobcard-media-preview.php'),
];

$results[] = [
    'name' => 'Timeline page links to diagnostic preview',
    'pass' => $timelineContent !== '' && str_contains($timelineContent, 'erp-jobcard-diagnostic-preview.php'),
];

$results[] = [
    'name' => 'Timeline page has no file upload input',
    'pass' => $timelineContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $timelineContent),
];

$results[] = [
    'name' => 'Evidence review page includes timeline link',
    'pass' => $reviewContent !== ''
        && str_contains($reviewContent, 'erp-jobcard-evidence-timeline.php')
        && str_contains($reviewContent, 'خط زمانی مدارک'),
];

$wave2eSqlCreated = false;
foreach (glob($root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_2e*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave2eSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for Wave 2E', 'pass' => !$wave2eSqlCreated];

$results[] = [
    'name' => 'Camera helper unchanged',
    'pass' => $cameraHelperContent !== '' && !str_contains($cameraHelperContent, 'evidence-timeline'),
];

$results[] = [
    'name' => 'Diagnostic helper unchanged',
    'pass' => $diagnosticHelperContent !== '' && !str_contains($diagnosticHelperContent, 'evidence-timeline'),
];

$results[] = [
    'name' => 'Evidence gate helper unchanged',
    'pass' => $gateHelperContent !== '' && !str_contains($gateHelperContent, 'evidence-timeline'),
];

$built = moghare360_jobcard_evidence_timeline_build(
    [
        [
            'media_id' => '10',
            'jobcard_id' => '1',
            'media_stage' => 'input',
            'media_type' => 'front',
            'relative_path' => 'storage/jobcard-media/1/test.jpg',
            'mime_type' => 'image/jpeg',
            'source' => 'CAMERA_ONLY',
            'capture_method' => 'BROWSER_CAMERA',
            'metadata_status' => 'ACTIVE',
            'created_at' => '2026-06-23 12:00:00',
        ],
    ],
    [
        [
            'history_id' => '5',
            'media_id' => '99',
            'jobcard_id' => '1',
            'event_code' => 'DIAGNOSTIC_FILE_REGISTERED',
            'event_title' => 'Diagnostic file registered',
            'event_at' => '2026-06-23 11:00:00',
        ],
    ]
);
$results[] = [
    'name' => 'Timeline build returns events newest first',
    'pass' => count($built) === 2 && ($built[0]['media_id'] ?? '') === '10',
];

$warnings = moghare360_jobcard_evidence_timeline_build_warnings(
    [['media_id' => '1']],
    [['history_id' => '2', 'media_id' => '99', 'event_code' => 'DIAGNOSTIC_FILE_REGISTERED']]
);
$results[] = [
    'name' => 'Orphan history rows produce audit warnings',
    'pass' => $warnings !== [],
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
    fwrite(STDERR, 'WAVE 2E EVIDENCE TIMELINE AUDIT TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 2E EVIDENCE TIMELINE AUDIT TEST PASSED' . PHP_EOL;
exit(0);
