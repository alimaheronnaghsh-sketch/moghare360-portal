<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 6A Soft Run Control Room CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-control-room-helper.php';
$pagePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-control-room.php';
$wave2ClosurePath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-2-closure-helper.php';
$wave3ClosurePath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-3-authorization-closure-helper.php';
$wave4ClosurePath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-4-delivery-control-closure-helper.php';
$wave5ClosurePath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-5-unified-closure-helper.php';

$forbiddenPaths = [
    $public . DIRECTORY_SEPARATOR . 'config.php',
    $public . DIRECTORY_SEPARATOR . 'staff-auth.php',
    $public . DIRECTORY_SEPARATOR . 'access-control.php',
    $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-auth-helper.php',
];

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6A_SOFT_RUN_CONTROL_ROOM_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6A_SOFT_RUN_CONTROL_ROOM_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6A_SOFT_RUN_CONTROL_ROOM_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6A_SOFT_RUN_CONTROL_ROOM_SIGNOFF.md',
];

require_once $helperPath;

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$pageContent = is_file($pagePath) ? (string)file_get_contents($pagePath) : '';
$wave2ClosureContent = is_file($wave2ClosurePath) ? (string)file_get_contents($wave2ClosurePath) : '';
$wave3ClosureContent = is_file($wave3ClosurePath) ? (string)file_get_contents($wave3ClosurePath) : '';
$wave4ClosureContent = is_file($wave4ClosurePath) ? (string)file_get_contents($wave4ClosurePath) : '';
$wave5ClosureContent = is_file($wave5ClosurePath) ? (string)file_get_contents($wave5ClosurePath) : '';

$results = [];

$results[] = ['name' => 'Soft run control helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Soft run control page exists', 'pass' => is_file($pagePath)];

$requiredApis = [
    'moghare360_soft_run_control_room_fetch_wave_2_status',
    'moghare360_soft_run_control_room_fetch_wave_3_status',
    'moghare360_soft_run_control_room_fetch_wave_4_status',
    'moghare360_soft_run_control_room_fetch_wave_5_status',
    'moghare360_soft_run_control_room_fetch_runtime_summary',
    'moghare360_soft_run_control_room_evaluate',
    'moghare360_soft_run_control_room_status_label',
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
    'name' => 'Helper references WAVE 2 closure safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-wave-2-closure-helper.php')
        && str_contains($helperContent, 'moghare360_wave_2_closure_status'),
];

$results[] = [
    'name' => 'Helper references WAVE 3 closure safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-wave-3-authorization-closure-helper.php')
        && str_contains($helperContent, 'moghare360_wave_3_closure_status'),
];

$results[] = [
    'name' => 'Helper references WAVE 4 closure safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-wave-4-delivery-control-closure-helper.php')
        && str_contains($helperContent, 'moghare360_wave_4_closure_status'),
];

$results[] = [
    'name' => 'Helper references WAVE 5 closure safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-wave-5-unified-closure-helper.php')
        && str_contains($helperContent, 'moghare360_wave_5_closure_status'),
];

$results[] = [
    'name' => 'Helper does not write INSERT/UPDATE/DELETE',
    'pass' => $helperContent !== ''
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent)
        && !preg_match('/\bUPDATE\s+dbo\./i', $helperContent)
        && !preg_match('/\bDELETE\s+FROM\b/i', $helperContent),
];

$linkChecks = [
    'erp-media-evidence-closure-dashboard.php' => 'WAVE 2 closure',
    'erp-authorization-closure-dashboard.php' => 'WAVE 3 closure',
    'erp-delivery-control-closure-dashboard.php' => 'WAVE 4 closure',
    'erp-unified-operational-closure-dashboard.php' => 'WAVE 5 closure',
    'erp-jobcard-command-workbench.php' => 'workbench',
    'erp-jobcard-command-center.php?jobcard_id=1' => 'command center',
];

foreach ($linkChecks as $href => $label) {
    $results[] = [
        'name' => 'Page links to ' . $label,
        'pass' => $pageContent !== '' && str_contains($pageContent, $href),
    ];
}

$results[] = [
    'name' => 'Page has no file input',
    'pass' => $pageContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $pageContent),
];

$results[] = [
    'name' => 'Page has no POST form',
    'pass' => $pageContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $pageContent),
];

$results[] = [
    'name' => 'Page has no final delivery submit/action',
    'pass' => $pageContent !== ''
        && !preg_match('/submit-.*delivery/i', $pageContent)
        && str_contains($pageContent, 'not final vehicle delivery'),
];

$results[] = [
    'name' => 'Page does not create delivery completion',
    'pass' => $pageContent !== ''
        && !preg_match('/vehicle_delivered/i', $pageContent)
        && !preg_match('/delivery_completion/i', $pageContent)
        && str_contains($pageContent, 'رکورد تکمیل تحویل ایجاد نمی‌شود'),
];

$wave6aSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_6a*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave6aSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for WAVE 6A', 'pass' => !$wave6aSqlCreated];

$results[] = [
    'name' => 'WAVE 2 closure helper unchanged',
    'pass' => $wave2ClosureContent !== '' && !str_contains($wave2ClosureContent, 'soft-run-control-room'),
];

$results[] = [
    'name' => 'WAVE 3 closure helper unchanged',
    'pass' => $wave3ClosureContent !== '' && !str_contains($wave3ClosureContent, 'soft-run-control-room'),
];

$results[] = [
    'name' => 'WAVE 4 closure helper unchanged',
    'pass' => $wave4ClosureContent !== ''
        && !str_contains($wave4ClosureContent, 'soft-run-control-room')
        && !str_contains($wave4ClosureContent, 'moghare360_soft_run_control_room'),
];

$results[] = [
    'name' => 'WAVE 5 closure helper unchanged',
    'pass' => $wave5ClosureContent !== ''
        && !str_contains($wave5ClosureContent, 'soft-run-control-room')
        && !str_contains($wave5ClosureContent, 'moghare360_soft_run_control_room'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'soft-run-control-room') || str_contains($content, 'moghare360_soft_run_control_room')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes for WAVE 6A', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'Public portal is not activated',
    'pass' => $pageContent !== '' && str_contains($pageContent, 'پورتال عمومی'),
];

$results[] = [
    'name' => 'Payment/accounting is not activated',
    'pass' => $pageContent !== ''
        && str_contains($pageContent, 'پرداخت')
        && !preg_match('/payment\s+gateway\s+active/i', $pageContent),
];

$results[] = [
    'name' => 'Legal final e-signature is not claimed',
    'pass' => $pageContent !== ''
        && str_contains($pageContent, 'not legal final e-signature')
        && !preg_match('/legal\s+final\s+e-?signature\s+confirmed/i', $pageContent),
];

$evaluation = moghare360_soft_run_control_room_evaluate();
$results[] = [
    'name' => 'Soft run evaluate returns valid status',
    'pass' => in_array($evaluation['status'] ?? '', [
        MOGHARE360_SOFT_RUN_STATUS_READY,
        MOGHARE360_SOFT_RUN_STATUS_REVIEW_REQUIRED,
        MOGHARE360_SOFT_RUN_STATUS_BLOCKED,
        MOGHARE360_SOFT_RUN_STATUS_EMPTY,
        MOGHARE360_SOFT_RUN_STATUS_ERROR,
    ], true),
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
    fwrite(STDERR, 'WAVE 6A SOFT RUN CONTROL ROOM TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 6A SOFT RUN CONTROL ROOM TEST PASSED' . PHP_EOL;
exit(0);
