<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 6B Soft Run Scenario Board CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-scenario-helper.php';
$boardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-scenario-board.php';
$controlRoomPagePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-control-room.php';
$controlRoomHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-control-room-helper.php';
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
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6B_SOFT_RUN_SCENARIO_BOARD_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6B_SOFT_RUN_SCENARIO_BOARD_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6B_SOFT_RUN_SCENARIO_BOARD_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6B_SOFT_RUN_SCENARIO_BOARD_SIGNOFF.md',
];

require_once $helperPath;

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$boardContent = is_file($boardPath) ? (string)file_get_contents($boardPath) : '';
$controlRoomPageContent = is_file($controlRoomPagePath) ? (string)file_get_contents($controlRoomPagePath) : '';
$controlRoomHelperContent = is_file($controlRoomHelperPath) ? (string)file_get_contents($controlRoomHelperPath) : '';
$wave2ClosureContent = is_file($wave2ClosurePath) ? (string)file_get_contents($wave2ClosurePath) : '';
$wave3ClosureContent = is_file($wave3ClosurePath) ? (string)file_get_contents($wave3ClosurePath) : '';
$wave4ClosureContent = is_file($wave4ClosurePath) ? (string)file_get_contents($wave4ClosurePath) : '';
$wave5ClosureContent = is_file($wave5ClosurePath) ? (string)file_get_contents($wave5ClosurePath) : '';

$results = [];

$results[] = ['name' => 'Scenario helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Scenario board page exists', 'pass' => is_file($boardPath)];

$requiredApis = [
    'moghare360_soft_run_scenario_required_scenarios',
    'moghare360_soft_run_scenario_required_pages',
    'moghare360_soft_run_scenario_fetch_control_room_status',
    'moghare360_soft_run_scenario_page_status',
    'moghare360_soft_run_scenario_evaluate',
    'moghare360_soft_run_scenario_status_label',
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

$requiredScenarioKeys = [
    'customer_intake',
    'vehicle_binding',
    'jobcard_creation',
    'camera_evidence',
    'diagnostic_binding',
    'authorization_creation',
    'authorization_workflow',
    'authorization_gate',
    'final_readiness',
    'delivery_eligibility',
    'delivery_clearance',
    'unified_command_center',
    'operator_workbench',
    'soft_run_control_room',
];

$scenarios = moghare360_soft_run_scenario_required_scenarios();
$scenarioKeys = array_column($scenarios, 'key');
$scenarioPass = count($scenarios) === 14;
foreach ($requiredScenarioKeys as $key) {
    if (!in_array($key, $scenarioKeys, true)) {
        $scenarioPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper defines all required pilot scenarios', 'pass' => $scenarioPass];

$requiredPagePaths = [
    'erp-soft-run-control-room.php',
    'erp-jobcard-command-workbench.php',
    'erp-jobcard-command-center.php',
    'erp-unified-operational-closure-dashboard.php',
    'erp-delivery-control-closure-dashboard.php',
    'erp-authorization-closure-dashboard.php',
    'erp-media-evidence-closure-dashboard.php',
    'erp-jobcard-final-readiness.php',
    'erp-jobcard-delivery-eligibility.php',
    'erp-jobcard-delivery-clearance.php',
    'erp-jobcard-delivery-clearance-preview.php',
    'erp-jobcard-authorization-gate.php',
    'erp-jobcard-contract-authorization.php',
    'erp-jobcard-contract-authorization-preview.php',
    'erp-jobcard-contract-authorization-workflow.php',
];

$pages = moghare360_soft_run_scenario_required_pages();
$pagePaths = array_column($pages, 'path');
$pagesPass = count($pages) === 15;
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

$boardLinkChecks = [
    'erp-soft-run-control-room.php' => 'control room',
    'erp-media-evidence-closure-dashboard.php' => 'WAVE 2 closure',
    'erp-authorization-closure-dashboard.php' => 'WAVE 3 closure',
    'erp-delivery-control-closure-dashboard.php' => 'WAVE 4 closure',
    'erp-unified-operational-closure-dashboard.php' => 'WAVE 5 closure',
    'erp-jobcard-command-workbench.php' => 'workbench',
    'erp-jobcard-command-center.php?jobcard_id=1' => 'command center',
    'erp-jobcard-final-readiness.php?jobcard_id=1' => 'final readiness',
    'erp-jobcard-delivery-eligibility.php?jobcard_id=1' => 'delivery eligibility',
    'erp-jobcard-delivery-clearance.php?jobcard_id=1' => 'delivery clearance',
    'erp-jobcard-delivery-clearance-preview.php?jobcard_id=1' => 'clearance preview',
    'erp-jobcard-authorization-gate.php?jobcard_id=1' => 'authorization gate',
    'erp-jobcard-contract-authorization.php?jobcard_id=1' => 'contract authorization',
    'erp-jobcard-contract-authorization-preview.php?jobcard_id=1' => 'authorization preview',
    'erp-jobcard-contract-authorization-workflow.php?jobcard_id=1' => 'authorization workflow',
];

foreach ($boardLinkChecks as $href => $label) {
    $results[] = [
        'name' => 'Board page links to ' . $label,
        'pass' => $boardContent !== '' && str_contains($boardContent, $href),
    ];
}

$results[] = [
    'name' => 'Control room links to scenario board',
    'pass' => $controlRoomPageContent !== ''
        && str_contains($controlRoomPageContent, 'erp-soft-run-scenario-board.php')
        && str_contains($controlRoomPageContent, 'برد سناریوهای اجرای آزمایشی'),
];

$results[] = [
    'name' => 'Page has no file input',
    'pass' => $boardContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $boardContent),
];

$results[] = [
    'name' => 'Page has no POST form',
    'pass' => $boardContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $boardContent),
];

$results[] = [
    'name' => 'Page has no final delivery submit/action',
    'pass' => $boardContent !== ''
        && !preg_match('/submit-.*delivery/i', $boardContent)
        && str_contains($boardContent, 'not final vehicle delivery'),
];

$results[] = [
    'name' => 'Page does not create delivery completion',
    'pass' => $boardContent !== ''
        && !preg_match('/vehicle_delivered/i', $boardContent)
        && !preg_match('/delivery_completion/i', $boardContent)
        && str_contains($boardContent, 'رکورد تکمیل تحویل ایجاد نمی‌شود'),
];

$wave6bSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_6b*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave6bSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for WAVE 6B', 'pass' => !$wave6bSqlCreated];

$results[] = [
    'name' => 'WAVE 2 closure helper unchanged',
    'pass' => $wave2ClosureContent !== '' && !str_contains($wave2ClosureContent, 'soft-run-scenario'),
];

$results[] = [
    'name' => 'WAVE 3 closure helper unchanged',
    'pass' => $wave3ClosureContent !== '' && !str_contains($wave3ClosureContent, 'soft-run-scenario'),
];

$results[] = [
    'name' => 'WAVE 4 closure helper unchanged',
    'pass' => $wave4ClosureContent !== ''
        && !str_contains($wave4ClosureContent, 'soft-run-scenario')
        && !str_contains($wave4ClosureContent, 'moghare360_soft_run_scenario'),
];

$results[] = [
    'name' => 'WAVE 5 closure helper unchanged',
    'pass' => $wave5ClosureContent !== ''
        && !str_contains($wave5ClosureContent, 'soft-run-scenario')
        && !str_contains($wave5ClosureContent, 'moghare360_soft_run_scenario'),
];

$results[] = [
    'name' => 'WAVE 6A control room helper unchanged',
    'pass' => $controlRoomHelperContent !== ''
        && !str_contains($controlRoomHelperContent, 'soft-run-scenario')
        && !str_contains($controlRoomHelperContent, 'moghare360_soft_run_scenario'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'soft-run-scenario') || str_contains($content, 'moghare360_soft_run_scenario')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes for WAVE 6B', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'Public portal is not activated',
    'pass' => $boardContent !== '' && str_contains($boardContent, 'پورتال عمومی'),
];

$results[] = [
    'name' => 'Payment/accounting is not activated',
    'pass' => $boardContent !== ''
        && str_contains($boardContent, 'پرداخت')
        && !preg_match('/payment\s+gateway\s+active/i', $boardContent),
];

$results[] = [
    'name' => 'Legal final e-signature is not claimed',
    'pass' => $boardContent !== ''
        && str_contains($boardContent, 'not legal final e-signature')
        && !preg_match('/legal\s+final\s+e-?signature\s+confirmed/i', $boardContent),
];

$evaluation = moghare360_soft_run_scenario_evaluate();
$results[] = [
    'name' => 'Scenario evaluate returns valid status',
    'pass' => in_array($evaluation['status'] ?? '', [
        MOGHARE360_SOFT_RUN_SCENARIO_STATUS_PILOT_READY,
        MOGHARE360_SOFT_RUN_SCENARIO_STATUS_REVIEW_REQUIRED,
        MOGHARE360_SOFT_RUN_SCENARIO_STATUS_BLOCKED,
        MOGHARE360_SOFT_RUN_SCENARIO_STATUS_EMPTY,
        MOGHARE360_SOFT_RUN_SCENARIO_STATUS_ERROR,
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
    fwrite(STDERR, 'WAVE 6B SOFT RUN SCENARIO BOARD TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 6B SOFT RUN SCENARIO BOARD TEST PASSED' . PHP_EOL;
exit(0);
