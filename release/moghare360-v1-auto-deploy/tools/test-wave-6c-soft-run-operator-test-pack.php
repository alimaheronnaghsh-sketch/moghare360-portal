<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 6C Soft Run Operator Test Pack CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-operator-test-pack-helper.php';
$packPagePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-operator-test-pack.php';
$scenarioBoardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-scenario-board.php';
$controlRoomPagePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-control-room.php';
$scenarioHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-scenario-helper.php';
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
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6C_SOFT_RUN_OPERATOR_TEST_PACK_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6C_SOFT_RUN_OPERATOR_TEST_PACK_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6C_SOFT_RUN_OPERATOR_TEST_PACK_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_6_soft_run_control' . DIRECTORY_SEPARATOR . 'WAVE_6C_SOFT_RUN_OPERATOR_TEST_PACK_SIGNOFF.md',
];

require_once $helperPath;

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$packPageContent = is_file($packPagePath) ? (string)file_get_contents($packPagePath) : '';
$scenarioBoardContent = is_file($scenarioBoardPath) ? (string)file_get_contents($scenarioBoardPath) : '';
$controlRoomPageContent = is_file($controlRoomPagePath) ? (string)file_get_contents($controlRoomPagePath) : '';
$scenarioHelperContent = is_file($scenarioHelperPath) ? (string)file_get_contents($scenarioHelperPath) : '';
$controlRoomHelperContent = is_file($controlRoomHelperPath) ? (string)file_get_contents($controlRoomHelperPath) : '';
$wave2ClosureContent = is_file($wave2ClosurePath) ? (string)file_get_contents($wave2ClosurePath) : '';
$wave3ClosureContent = is_file($wave3ClosurePath) ? (string)file_get_contents($wave3ClosurePath) : '';
$wave4ClosureContent = is_file($wave4ClosurePath) ? (string)file_get_contents($wave4ClosurePath) : '';
$wave5ClosureContent = is_file($wave5ClosurePath) ? (string)file_get_contents($wave5ClosurePath) : '';

$results = [];

$results[] = ['name' => 'Operator test pack helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Operator test pack page exists', 'pass' => is_file($packPagePath)];

$requiredApis = [
    'moghare360_soft_run_operator_test_pack_steps',
    'moghare360_soft_run_operator_test_pack_expected_evidence',
    'moghare360_soft_run_operator_test_pack_required_pages',
    'moghare360_soft_run_operator_test_pack_fetch_scenario_status',
    'moghare360_soft_run_operator_test_pack_evaluate',
    'moghare360_soft_run_operator_test_pack_status_label',
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
    'name' => 'Helper references WAVE 6B scenario board safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-soft-run-scenario-helper.php')
        && str_contains($helperContent, 'moghare360_soft_run_scenario_evaluate'),
];

$results[] = [
    'name' => 'Helper references WAVE 6A control room safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'control_room_status')
        && str_contains($helperContent, 'moghare360-soft-run-scenario-helper.php'),
];

$requiredStepKeys = [
    'open_control_room',
    'open_scenario_board',
    'open_workbench',
    'open_command_center',
    'confirm_jobcard_context',
    'confirm_evidence_status',
    'confirm_authorization_status',
    'confirm_final_readiness',
    'confirm_delivery_eligibility',
    'confirm_delivery_clearance',
    'confirm_wave2_closure',
    'confirm_wave3_closure',
    'confirm_wave4_closure',
    'confirm_wave5_closure',
    'confirm_no_post_form',
    'confirm_no_final_delivery',
    'confirm_no_delivery_completion',
    'confirm_no_public_portal',
    'confirm_no_payment',
    'confirm_no_production_login',
];

$steps = moghare360_soft_run_operator_test_pack_steps();
$stepKeys = array_column($steps, 'key');
$stepsPass = count($steps) === 20;
foreach ($requiredStepKeys as $key) {
    if (!in_array($key, $stepKeys, true)) {
        $stepsPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper defines all required operator test steps', 'pass' => $stepsPass];

$requiredEvidenceKeys = [
    'control_room_status',
    'scenario_board_status',
    'workbench_jobcard_list',
    'jobcard_number_visible',
    'command_center_unified_status',
    'evidence_panel',
    'authorization_panel',
    'final_readiness_panel',
    'delivery_eligibility_panel',
    'delivery_clearance_panel',
    'closure_dashboards_reachable',
    'no_post_form',
    'no_final_delivery',
    'no_delivery_completion',
    'no_public_portal',
    'no_payment_accounting',
    'no_production_login',
];

$evidence = moghare360_soft_run_operator_test_pack_expected_evidence();
$evidenceKeys = array_column($evidence, 'key');
$evidencePass = count($evidence) === 17;
foreach ($requiredEvidenceKeys as $key) {
    if (!in_array($key, $evidenceKeys, true)) {
        $evidencePass = false;
        break;
    }
}
$results[] = ['name' => 'Helper defines all expected evidence items', 'pass' => $evidencePass];

$requiredPagePaths = [
    'erp-soft-run-control-room.php',
    'erp-soft-run-scenario-board.php',
    'erp-jobcard-command-workbench.php',
    'erp-jobcard-command-center.php',
    'erp-unified-operational-closure-dashboard.php',
    'erp-delivery-control-closure-dashboard.php',
    'erp-authorization-closure-dashboard.php',
    'erp-media-evidence-closure-dashboard.php',
    'erp-jobcard-final-readiness.php',
    'erp-jobcard-delivery-eligibility.php',
    'erp-jobcard-delivery-clearance-preview.php',
];

$pages = moghare360_soft_run_operator_test_pack_required_pages();
$pagePaths = array_column($pages, 'path');
$pagesPass = count($pages) === 11;
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

$packLinkChecks = [
    'erp-soft-run-control-room.php' => 'control room',
    'erp-soft-run-scenario-board.php' => 'scenario board',
    'erp-jobcard-command-workbench.php' => 'workbench',
    'erp-jobcard-command-center.php?jobcard_id=1' => 'command center',
    'erp-media-evidence-closure-dashboard.php' => 'WAVE 2 closure',
    'erp-authorization-closure-dashboard.php' => 'WAVE 3 closure',
    'erp-delivery-control-closure-dashboard.php' => 'WAVE 4 closure',
    'erp-unified-operational-closure-dashboard.php' => 'WAVE 5 closure',
    'erp-jobcard-final-readiness.php?jobcard_id=1' => 'final readiness',
    'erp-jobcard-delivery-eligibility.php?jobcard_id=1' => 'delivery eligibility',
    'erp-jobcard-delivery-clearance-preview.php?jobcard_id=1' => 'clearance preview',
];

foreach ($packLinkChecks as $href => $label) {
    $results[] = [
        'name' => 'Pack page links to ' . $label,
        'pass' => $packPageContent !== '' && str_contains($packPageContent, $href),
    ];
}

$results[] = [
    'name' => 'Scenario board links to operator test pack',
    'pass' => $scenarioBoardContent !== ''
        && str_contains($scenarioBoardContent, 'erp-soft-run-operator-test-pack.php')
        && str_contains($scenarioBoardContent, 'بسته تست اپراتوری اجرای آزمایشی'),
];

$results[] = [
    'name' => 'Control room links to operator test pack',
    'pass' => $controlRoomPageContent !== ''
        && str_contains($controlRoomPageContent, 'erp-soft-run-operator-test-pack.php')
        && str_contains($controlRoomPageContent, 'بسته تست اپراتوری اجرای آزمایشی'),
];

$results[] = [
    'name' => 'Page has no file input',
    'pass' => $packPageContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $packPageContent),
];

$results[] = [
    'name' => 'Page has no POST form',
    'pass' => $packPageContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $packPageContent),
];

$results[] = [
    'name' => 'Page has no final delivery submit/action',
    'pass' => $packPageContent !== ''
        && !preg_match('/submit-.*delivery/i', $packPageContent)
        && str_contains($packPageContent, 'not final vehicle delivery'),
];

$results[] = [
    'name' => 'Page does not create delivery completion',
    'pass' => $packPageContent !== ''
        && !preg_match('/vehicle_delivered/i', $packPageContent)
        && !preg_match('/delivery_completion/i', $packPageContent)
        && str_contains($packPageContent, 'رکورد تکمیل تحویل ایجاد نمی‌شود'),
];

$wave6cSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_6c*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave6cSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for WAVE 6C', 'pass' => !$wave6cSqlCreated];

$results[] = [
    'name' => 'WAVE 2 closure helper unchanged',
    'pass' => $wave2ClosureContent !== '' && !str_contains($wave2ClosureContent, 'soft-run-operator-test-pack'),
];

$results[] = [
    'name' => 'WAVE 3 closure helper unchanged',
    'pass' => $wave3ClosureContent !== '' && !str_contains($wave3ClosureContent, 'soft-run-operator-test-pack'),
];

$results[] = [
    'name' => 'WAVE 4 closure helper unchanged',
    'pass' => $wave4ClosureContent !== ''
        && !str_contains($wave4ClosureContent, 'soft-run-operator-test-pack')
        && !str_contains($wave4ClosureContent, 'moghare360_soft_run_operator_test_pack'),
];

$results[] = [
    'name' => 'WAVE 5 closure helper unchanged',
    'pass' => $wave5ClosureContent !== ''
        && !str_contains($wave5ClosureContent, 'soft-run-operator-test-pack')
        && !str_contains($wave5ClosureContent, 'moghare360_soft_run_operator_test_pack'),
];

$results[] = [
    'name' => 'WAVE 6A control room helper unchanged',
    'pass' => $controlRoomHelperContent !== ''
        && !str_contains($controlRoomHelperContent, 'soft-run-operator-test-pack')
        && !str_contains($controlRoomHelperContent, 'moghare360_soft_run_operator_test_pack'),
];

$results[] = [
    'name' => 'WAVE 6B scenario helper unchanged',
    'pass' => $scenarioHelperContent !== ''
        && !str_contains($scenarioHelperContent, 'soft-run-operator-test-pack')
        && !str_contains($scenarioHelperContent, 'moghare360_soft_run_operator_test_pack'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'soft-run-operator-test-pack') || str_contains($content, 'moghare360_soft_run_operator_test_pack')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes for WAVE 6C', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'Public portal is not activated',
    'pass' => $packPageContent !== '' && str_contains($packPageContent, 'پورتال عمومی'),
];

$results[] = [
    'name' => 'Payment/accounting is not activated',
    'pass' => $packPageContent !== ''
        && str_contains($packPageContent, 'پرداخت')
        && !preg_match('/payment\s+gateway\s+active/i', $packPageContent),
];

$results[] = [
    'name' => 'Legal final e-signature is not claimed',
    'pass' => $packPageContent !== ''
        && str_contains($packPageContent, 'not legal final e-signature')
        && !preg_match('/legal\s+final\s+e-?signature\s+confirmed/i', $packPageContent),
];

$evaluation = moghare360_soft_run_operator_test_pack_evaluate();
$results[] = [
    'name' => 'Operator test pack evaluate returns valid status',
    'pass' => in_array($evaluation['status'] ?? '', [
        MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_READY,
        MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_REVIEW_REQUIRED,
        MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_BLOCKED,
        MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_EMPTY,
        MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_ERROR,
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
    fwrite(STDERR, 'WAVE 6C SOFT RUN OPERATOR TEST PACK TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 6C SOFT RUN OPERATOR TEST PACK TEST PASSED' . PHP_EOL;
exit(0);
