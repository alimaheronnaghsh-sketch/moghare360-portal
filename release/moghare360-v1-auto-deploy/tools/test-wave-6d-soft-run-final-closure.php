<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 6D Soft Run Final Closure CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-final-closure-helper.php';
$dashboardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-final-closure-dashboard.php';
$controlRoomPagePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-control-room.php';
$scenarioBoardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-scenario-board.php';
$testPackPagePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-operator-test-pack.php';
$controlRoomHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-control-room-helper.php';
$scenarioHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-scenario-helper.php';
$testPackHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-operator-test-pack-helper.php';
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
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6D_SOFT_RUN_FINAL_CLOSURE_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6D_SOFT_RUN_FINAL_CLOSURE_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6D_SOFT_RUN_FINAL_CLOSURE_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6D_SOFT_RUN_FINAL_CLOSURE_SIGNOFF.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6_FINAL_CLOSURE_REPORT.md',
];

require_once $helperPath;

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$dashboardContent = is_file($dashboardPath) ? (string)file_get_contents($dashboardPath) : '';
$controlRoomPageContent = is_file($controlRoomPagePath) ? (string)file_get_contents($controlRoomPagePath) : '';
$scenarioBoardContent = is_file($scenarioBoardPath) ? (string)file_get_contents($scenarioBoardPath) : '';
$testPackPageContent = is_file($testPackPagePath) ? (string)file_get_contents($testPackPagePath) : '';
$controlRoomHelperContent = is_file($controlRoomHelperPath) ? (string)file_get_contents($controlRoomHelperPath) : '';
$scenarioHelperContent = is_file($scenarioHelperPath) ? (string)file_get_contents($scenarioHelperPath) : '';
$testPackHelperContent = is_file($testPackHelperPath) ? (string)file_get_contents($testPackHelperPath) : '';
$wave2ClosureContent = is_file($wave2ClosurePath) ? (string)file_get_contents($wave2ClosurePath) : '';
$wave3ClosureContent = is_file($wave3ClosurePath) ? (string)file_get_contents($wave3ClosurePath) : '';
$wave4ClosureContent = is_file($wave4ClosurePath) ? (string)file_get_contents($wave4ClosurePath) : '';
$wave5ClosureContent = is_file($wave5ClosurePath) ? (string)file_get_contents($wave5ClosurePath) : '';

$results = [];

$results[] = ['name' => 'Final closure helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Final closure dashboard page exists', 'pass' => is_file($dashboardPath)];

$requiredApis = [
    'moghare360_soft_run_final_closure_fetch_control_room_status',
    'moghare360_soft_run_final_closure_fetch_scenario_board_status',
    'moghare360_soft_run_final_closure_fetch_operator_test_pack_status',
    'moghare360_soft_run_final_closure_required_pages',
    'moghare360_soft_run_final_closure_page_status',
    'moghare360_soft_run_final_closure_evaluate',
    'moghare360_soft_run_final_closure_status_label',
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
    'name' => 'Helper references WAVE 6A control room safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-soft-run-control-room-helper.php')
        && str_contains($helperContent, 'moghare360_soft_run_control_room_evaluate'),
];

$results[] = [
    'name' => 'Helper references WAVE 6B scenario board safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-soft-run-scenario-helper.php')
        && str_contains($helperContent, 'moghare360_soft_run_scenario_evaluate'),
];

$results[] = [
    'name' => 'Helper references WAVE 6C operator test pack safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-soft-run-operator-test-pack-helper.php')
        && str_contains($helperContent, 'moghare360_soft_run_operator_test_pack_evaluate'),
];

$requiredPagePaths = [
    'erp-soft-run-control-room.php',
    'erp-soft-run-scenario-board.php',
    'erp-soft-run-operator-test-pack.php',
    'erp-jobcard-command-workbench.php',
    'erp-jobcard-command-center.php',
    'erp-unified-operational-closure-dashboard.php',
    'erp-delivery-control-closure-dashboard.php',
    'erp-authorization-closure-dashboard.php',
    'erp-media-evidence-closure-dashboard.php',
];

$pages = moghare360_soft_run_final_closure_required_pages();
$pagePaths = array_column($pages, 'path');
$pagesPass = count($pages) === 9;
foreach ($requiredPagePaths as $path) {
    if (!in_array($path, $pagePaths, true)) {
        $pagesPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper defines all required runtime pages', 'pass' => $pagesPass];

$results[] = [
    'name' => 'Helper does not write INSERT/UPDATE/DELETE',
    'pass' => $helperContent !== ''
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent)
        && !preg_match('/\bUPDATE\s+dbo\./i', $helperContent)
        && !preg_match('/\bDELETE\s+FROM\b/i', $helperContent),
];

$dashboardLinkChecks = [
    'erp-soft-run-control-room.php' => 'control room',
    'erp-soft-run-scenario-board.php' => 'scenario board',
    'erp-soft-run-operator-test-pack.php' => 'operator test pack',
    'erp-jobcard-command-workbench.php' => 'workbench',
    'erp-jobcard-command-center.php?jobcard_id=1' => 'command center',
    'erp-unified-operational-closure-dashboard.php' => 'WAVE 5 closure',
    'erp-delivery-control-closure-dashboard.php' => 'WAVE 4 closure',
    'erp-authorization-closure-dashboard.php' => 'WAVE 3 closure',
    'erp-media-evidence-closure-dashboard.php' => 'WAVE 2 closure',
];

foreach ($dashboardLinkChecks as $href => $label) {
    $results[] = [
        'name' => 'Dashboard page links to ' . $label,
        'pass' => $dashboardContent !== '' && str_contains($dashboardContent, $href),
    ];
}

$results[] = [
    'name' => 'Control room links to final closure dashboard',
    'pass' => $controlRoomPageContent !== ''
        && str_contains($controlRoomPageContent, 'erp-soft-run-final-closure-dashboard.php')
        && str_contains($controlRoomPageContent, 'داشبورد نهایی آمادگی پایلوت'),
];

$results[] = [
    'name' => 'Scenario board links to final closure dashboard',
    'pass' => $scenarioBoardContent !== ''
        && str_contains($scenarioBoardContent, 'erp-soft-run-final-closure-dashboard.php')
        && str_contains($scenarioBoardContent, 'داشبورد نهایی آمادگی پایلوت'),
];

$results[] = [
    'name' => 'Operator test pack links to final closure dashboard',
    'pass' => $testPackPageContent !== ''
        && str_contains($testPackPageContent, 'erp-soft-run-final-closure-dashboard.php')
        && str_contains($testPackPageContent, 'داشبورد نهایی آمادگی پایلوت'),
];

$results[] = [
    'name' => 'Page has no file input',
    'pass' => $dashboardContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $dashboardContent),
];

$results[] = [
    'name' => 'Page has no POST form',
    'pass' => $dashboardContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $dashboardContent),
];

$results[] = [
    'name' => 'Page has no final delivery submit/action',
    'pass' => $dashboardContent !== ''
        && !preg_match('/submit-.*delivery/i', $dashboardContent)
        && str_contains($dashboardContent, 'not final vehicle delivery'),
];

$results[] = [
    'name' => 'Page does not create delivery completion',
    'pass' => $dashboardContent !== ''
        && !preg_match('/vehicle_delivered/i', $dashboardContent)
        && !preg_match('/delivery_completion/i', $dashboardContent)
        && str_contains($dashboardContent, 'رکورد تکمیل تحویل ایجاد نمی‌شود'),
];

$wave6dSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_6d*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave6dSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for WAVE 6D', 'pass' => !$wave6dSqlCreated];

$results[] = [
    'name' => 'WAVE 2 closure helper unchanged',
    'pass' => $wave2ClosureContent !== '' && !str_contains($wave2ClosureContent, 'soft-run-final-closure'),
];

$results[] = [
    'name' => 'WAVE 3 closure helper unchanged',
    'pass' => $wave3ClosureContent !== '' && !str_contains($wave3ClosureContent, 'soft-run-final-closure'),
];

$results[] = [
    'name' => 'WAVE 4 closure helper unchanged',
    'pass' => $wave4ClosureContent !== ''
        && !str_contains($wave4ClosureContent, 'soft-run-final-closure')
        && !str_contains($wave4ClosureContent, 'moghare360_soft_run_final_closure'),
];

$results[] = [
    'name' => 'WAVE 5 closure helper unchanged',
    'pass' => $wave5ClosureContent !== ''
        && !str_contains($wave5ClosureContent, 'soft-run-final-closure')
        && !str_contains($wave5ClosureContent, 'moghare360_soft_run_final_closure'),
];

$results[] = [
    'name' => 'WAVE 6A control room helper unchanged',
    'pass' => $controlRoomHelperContent !== ''
        && !str_contains($controlRoomHelperContent, 'soft-run-final-closure')
        && !str_contains($controlRoomHelperContent, 'moghare360_soft_run_final_closure'),
];

$results[] = [
    'name' => 'WAVE 6B scenario helper unchanged',
    'pass' => $scenarioHelperContent !== ''
        && !str_contains($scenarioHelperContent, 'soft-run-final-closure')
        && !str_contains($scenarioHelperContent, 'moghare360_soft_run_final_closure'),
];

$results[] = [
    'name' => 'WAVE 6C operator test pack helper unchanged',
    'pass' => $testPackHelperContent !== ''
        && !str_contains($testPackHelperContent, 'soft-run-final-closure')
        && !str_contains($testPackHelperContent, 'moghare360_soft_run_final_closure'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'soft-run-final-closure') || str_contains($content, 'moghare360_soft_run_final_closure')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes for WAVE 6D', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'Public portal is not activated',
    'pass' => $dashboardContent !== '' && str_contains($dashboardContent, 'پورتال عمومی'),
];

$results[] = [
    'name' => 'Payment/accounting is not activated',
    'pass' => $dashboardContent !== ''
        && str_contains($dashboardContent, 'پرداخت')
        && !preg_match('/payment\s+gateway\s+active/i', $dashboardContent),
];

$results[] = [
    'name' => 'Legal final e-signature is not claimed',
    'pass' => $dashboardContent !== ''
        && str_contains($dashboardContent, 'not legal final e-signature')
        && !preg_match('/legal\s+final\s+e-?signature\s+confirmed/i', $dashboardContent),
];

$evaluation = moghare360_soft_run_final_closure_evaluate();
$results[] = [
    'name' => 'Final closure evaluate returns valid status',
    'pass' => in_array($evaluation['status'] ?? '', [
        MOGHARE360_SOFT_RUN_FINAL_STATUS_PILOT_READY,
        MOGHARE360_SOFT_RUN_FINAL_STATUS_REVIEW_REQUIRED,
        MOGHARE360_SOFT_RUN_FINAL_STATUS_BLOCKED,
        MOGHARE360_SOFT_RUN_FINAL_STATUS_EMPTY,
        MOGHARE360_SOFT_RUN_FINAL_STATUS_ERROR,
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
    fwrite(STDERR, 'WAVE 6D SOFT RUN FINAL CLOSURE TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 6D SOFT RUN FINAL CLOSURE TEST PASSED' . PHP_EOL;
exit(0);
