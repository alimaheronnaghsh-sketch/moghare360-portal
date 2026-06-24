<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 7D Soft Run Pilot Final Closure CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-final-closure-helper.php';
$dashboardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-final-closure-dashboard.php';
$reviewDashboardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-review-dashboard.php';
$boardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-execution-board.php';
$detailPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-execution-detail.php';
$reviewHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-review-helper.php';
$pilotHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-execution-helper.php';
$submitCreatePath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-pilot-execution.php';
$submitWorkflowPath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-pilot-execution-workflow.php';

$controlRoomHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-control-room-helper.php';
$scenarioHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-scenario-helper.php';
$testPackHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-operator-test-pack-helper.php';
$finalClosureHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-final-closure-helper.php';

$forbiddenPaths = [
    $public . DIRECTORY_SEPARATOR . 'config.php',
    $public . DIRECTORY_SEPARATOR . 'staff-auth.php',
    $public . DIRECTORY_SEPARATOR . 'access-control.php',
    $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-auth-helper.php',
];

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7D_SOFT_RUN_PILOT_FINAL_CLOSURE_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7D_SOFT_RUN_PILOT_FINAL_CLOSURE_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7D_SOFT_RUN_PILOT_FINAL_CLOSURE_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7D_SOFT_RUN_PILOT_FINAL_CLOSURE_SIGNOFF.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7_FINAL_CLOSURE_REPORT.md',
];

require_once $helperPath;

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$dashboardContent = is_file($dashboardPath) ? (string)file_get_contents($dashboardPath) : '';
$reviewDashboardContent = is_file($reviewDashboardPath) ? (string)file_get_contents($reviewDashboardPath) : '';
$boardContent = is_file($boardPath) ? (string)file_get_contents($boardPath) : '';
$detailContent = is_file($detailPath) ? (string)file_get_contents($detailPath) : '';
$reviewHelperContent = is_file($reviewHelperPath) ? (string)file_get_contents($reviewHelperPath) : '';
$pilotHelperContent = is_file($pilotHelperPath) ? (string)file_get_contents($pilotHelperPath) : '';
$submitCreateContent = is_file($submitCreatePath) ? (string)file_get_contents($submitCreatePath) : '';
$submitWorkflowContent = is_file($submitWorkflowPath) ? (string)file_get_contents($submitWorkflowPath) : '';
$controlRoomHelperContent = is_file($controlRoomHelperPath) ? (string)file_get_contents($controlRoomHelperPath) : '';
$scenarioHelperContent = is_file($scenarioHelperPath) ? (string)file_get_contents($scenarioHelperPath) : '';
$testPackHelperContent = is_file($testPackHelperPath) ? (string)file_get_contents($testPackHelperPath) : '';
$finalClosureHelperContent = is_file($finalClosureHelperPath) ? (string)file_get_contents($finalClosureHelperPath) : '';

$results = [];

$results[] = ['name' => 'Final closure helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Final closure dashboard page exists', 'pass' => is_file($dashboardPath)];

$requiredApis = [
    'moghare360_soft_run_pilot_final_closure_fetch_review_status',
    'moghare360_soft_run_pilot_final_closure_fetch_execution_summary',
    'moghare360_soft_run_pilot_final_closure_fetch_workflow_summary',
    'moghare360_soft_run_pilot_final_closure_fetch_history_summary',
    'moghare360_soft_run_pilot_final_closure_required_pages',
    'moghare360_soft_run_pilot_final_closure_page_status',
    'moghare360_soft_run_pilot_final_closure_evaluate',
    'moghare360_soft_run_pilot_final_closure_status_label',
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
    'name' => 'Helper uses SELECT only',
    'pass' => $helperContent !== ''
        && preg_match('/\bSELECT\b/i', $helperContent) === 1
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent),
];

$results[] = [
    'name' => 'Helper does not write INSERT/UPDATE/DELETE',
    'pass' => $helperContent !== ''
        && !preg_match('/\bUPDATE\s+dbo\./i', $helperContent)
        && !preg_match('/\bDELETE\s+FROM\b/i', $helperContent),
];

preg_match_all('/FROM\s+dbo\.([a-z0-9_]+)/i', $helperContent, $fromMatches);
$readTables = array_unique(array_map('strtolower', $fromMatches[1] ?? []));
$allowedReadTables = ['erp_soft_run_pilot_executions', 'erp_soft_run_pilot_execution_history'];
$readBoundaryPass = $readTables === []
    || count(array_diff($readTables, $allowedReadTables)) === 0;
$results[] = [
    'name' => 'Helper reads only Soft Run pilot execution tables',
    'pass' => $readBoundaryPass,
];

$results[] = [
    'name' => 'Helper safely includes WAVE 7C review helper',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-soft-run-pilot-review-helper.php')
        && str_contains($helperContent, 'moghare360_soft_run_pilot_review_evaluate'),
];

$dashboardLinkChecks = [
    'erp-soft-run-pilot-execution-create.php' => 'create',
    'erp-soft-run-pilot-execution-board.php' => 'board',
    'erp-soft-run-pilot-review-dashboard.php' => 'review dashboard',
    'erp-soft-run-pilot-execution-detail.php?execution_id=' => 'detail',
    'erp-soft-run-pilot-execution-workflow.php?execution_id=' => 'workflow',
    'erp-soft-run-final-closure-dashboard.php' => 'WAVE 6 final closure',
    'erp-soft-run-operator-test-pack.php' => 'operator test pack',
];

foreach ($dashboardLinkChecks as $href => $label) {
    $results[] = [
        'name' => 'Dashboard links to ' . $label,
        'pass' => $dashboardContent !== '' && str_contains($dashboardContent, $href),
    ];
}

$results[] = [
    'name' => 'Review dashboard links to final closure dashboard',
    'pass' => $reviewDashboardContent !== ''
        && str_contains($reviewDashboardContent, 'erp-soft-run-pilot-final-closure-dashboard.php'),
];

$results[] = [
    'name' => 'Board links to final closure dashboard',
    'pass' => $boardContent !== ''
        && str_contains($boardContent, 'erp-soft-run-pilot-final-closure-dashboard.php'),
];

$results[] = [
    'name' => 'Detail links to final closure dashboard',
    'pass' => $detailContent !== ''
        && str_contains($detailContent, 'erp-soft-run-pilot-final-closure-dashboard.php'),
];

$results[] = [
    'name' => 'Dashboard has no file input',
    'pass' => $dashboardContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $dashboardContent),
];

$results[] = [
    'name' => 'Dashboard has no POST form',
    'pass' => $dashboardContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $dashboardContent),
];

$results[] = [
    'name' => 'Dashboard has no final delivery submit/action',
    'pass' => $dashboardContent !== ''
        && str_contains($dashboardContent, 'not final vehicle delivery')
        && !preg_match('/submit-.*final-delivery/i', $dashboardContent),
];

$results[] = [
    'name' => 'Dashboard does not create delivery completion',
    'pass' => !preg_match('/delivery_completion_record/i', $dashboardContent),
];

$wave7dSqlFiles = glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_7d*') ?: [];
$results[] = [
    'name' => 'No SQL files created for WAVE 7D',
    'pass' => $wave7dSqlFiles === [],
];

$forbiddenWriteTables = [
    'erp_jobcards', 'erp_jobcard_delivery', 'erp_media', 'erp_authorization',
    'erp_customers', 'erp_vehicles', 'erp_invoice', 'erp_payment',
];
$noForbiddenWrites = true;
foreach ($forbiddenWriteTables as $table) {
    if (preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\s+dbo\.' . preg_quote($table, '/') . '\b/i', $helperContent)) {
        $noForbiddenWrites = false;
        break;
    }
}
$results[] = [
    'name' => 'No writes to JobCard/delivery/evidence/authorization/customer/vehicle/payment tables',
    'pass' => $noForbiddenWrites,
];

$results[] = [
    'name' => 'No public portal/payment/accounting/legal e-signature activation',
    'pass' => $dashboardContent !== ''
        && str_contains($dashboardContent, 'Not legal e-signature')
        && str_contains($dashboardContent, 'Not payment/accounting'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'pilot-final-closure') || str_contains($content, 'moghare360_soft_run_pilot_final_closure')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'WAVE 6A control room helper unchanged',
    'pass' => $controlRoomHelperContent !== '' && !str_contains($controlRoomHelperContent, 'pilot-final-closure'),
];

$results[] = [
    'name' => 'WAVE 6B scenario helper unchanged',
    'pass' => $scenarioHelperContent !== '' && !str_contains($scenarioHelperContent, 'pilot-final-closure'),
];

$results[] = [
    'name' => 'WAVE 6C operator test pack helper unchanged',
    'pass' => $testPackHelperContent !== '' && !str_contains($testPackHelperContent, 'pilot-final-closure'),
];

$results[] = [
    'name' => 'WAVE 6D final closure helper unchanged',
    'pass' => $finalClosureHelperContent !== '' && !str_contains($finalClosureHelperContent, 'pilot-final-closure'),
];

$results[] = [
    'name' => 'WAVE 7A pilot execution helper unchanged',
    'pass' => $pilotHelperContent !== '' && !str_contains($pilotHelperContent, 'pilot-final-closure'),
];

$results[] = [
    'name' => 'WAVE 7A submit page unchanged',
    'pass' => $submitCreateContent !== '' && !str_contains($submitCreateContent, 'pilot-final-closure'),
];

$results[] = [
    'name' => 'WAVE 7B submit workflow page unchanged',
    'pass' => $submitWorkflowContent !== '' && !str_contains($submitWorkflowContent, 'pilot-final-closure'),
];

$results[] = [
    'name' => 'WAVE 7C review helper unchanged',
    'pass' => $reviewHelperContent !== ''
        && !str_contains($reviewHelperContent, 'pilot-final-closure')
        && !str_contains($reviewHelperContent, 'moghare360_soft_run_pilot_final_closure'),
];

$requiredPages = moghare360_soft_run_pilot_final_closure_required_pages();
$results[] = [
    'name' => 'Helper defines required runtime pages',
    'pass' => count($requiredPages) >= 6,
];

$evaluation = moghare360_soft_run_pilot_final_closure_evaluate();
$results[] = [
    'name' => 'Evaluate returns known final closure status',
    'pass' => in_array($evaluation['status'] ?? '', [
        MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_READY,
        MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_REVIEW_REQUIRED,
        MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED,
        MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_EMPTY,
        MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_ERROR,
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
    fwrite(STDERR, 'WAVE 7D SOFT RUN PILOT FINAL CLOSURE TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 7D SOFT RUN PILOT FINAL CLOSURE TEST PASSED' . PHP_EOL;
exit(0);
