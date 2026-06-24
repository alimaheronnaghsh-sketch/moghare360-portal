<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 8C Soft Run Finding Review CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-review-helper.php';
$dashboardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-finding-review-dashboard.php';
$boardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-finding-board.php';
$detailPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-finding-detail.php';

$findingHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-helper.php';
$submitCreatePath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-finding.php';
$submitWorkflowPath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-finding-workflow.php';

$pilotExecutionHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-execution-helper.php';
$pilotReviewHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-review-helper.php';
$pilotFinalClosureHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-final-closure-helper.php';

$controlRoomHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-control-room-helper.php';
$scenarioHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-scenario-helper.php';
$testPackHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-operator-test-pack-helper.php';
$wave6FinalClosureHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-final-closure-helper.php';

$forbiddenPaths = [
    $public . DIRECTORY_SEPARATOR . 'config.php',
    $public . DIRECTORY_SEPARATOR . 'staff-auth.php',
    $public . DIRECTORY_SEPARATOR . 'access-control.php',
    $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-auth-helper.php',
];

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_8_soft_run_findings' . DIRECTORY_SEPARATOR . 'WAVE_8C_SOFT_RUN_FINDINGS_REVIEW_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_8_soft_run_findings' . DIRECTORY_SEPARATOR . 'WAVE_8C_SOFT_RUN_FINDINGS_REVIEW_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_8_soft_run_findings' . DIRECTORY_SEPARATOR . 'WAVE_8C_SOFT_RUN_FINDINGS_REVIEW_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_8_soft_run_findings' . DIRECTORY_SEPARATOR . 'WAVE_8C_SOFT_RUN_FINDINGS_REVIEW_SIGNOFF.md',
];

require_once $helperPath;

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$dashboardContent = is_file($dashboardPath) ? (string)file_get_contents($dashboardPath) : '';
$boardContent = is_file($boardPath) ? (string)file_get_contents($boardPath) : '';
$detailContent = is_file($detailPath) ? (string)file_get_contents($detailPath) : '';
$findingHelperContent = is_file($findingHelperPath) ? (string)file_get_contents($findingHelperPath) : '';
$submitCreateContent = is_file($submitCreatePath) ? (string)file_get_contents($submitCreatePath) : '';
$submitWorkflowContent = is_file($submitWorkflowPath) ? (string)file_get_contents($submitWorkflowPath) : '';
$pilotExecutionHelperContent = is_file($pilotExecutionHelperPath) ? (string)file_get_contents($pilotExecutionHelperPath) : '';
$pilotReviewHelperContent = is_file($pilotReviewHelperPath) ? (string)file_get_contents($pilotReviewHelperPath) : '';
$pilotFinalClosureHelperContent = is_file($pilotFinalClosureHelperPath) ? (string)file_get_contents($pilotFinalClosureHelperPath) : '';
$controlRoomHelperContent = is_file($controlRoomHelperPath) ? (string)file_get_contents($controlRoomHelperPath) : '';
$scenarioHelperContent = is_file($scenarioHelperPath) ? (string)file_get_contents($scenarioHelperPath) : '';
$testPackHelperContent = is_file($testPackHelperPath) ? (string)file_get_contents($testPackHelperPath) : '';
$wave6FinalClosureHelperContent = is_file($wave6FinalClosureHelperPath) ? (string)file_get_contents($wave6FinalClosureHelperPath) : '';

$wave8cBundle = $helperContent . $dashboardContent . $boardContent . $detailContent;

$results = [];

$results[] = ['name' => 'Review helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Review dashboard page exists', 'pass' => is_file($dashboardPath)];

$requiredApis = [
    'moghare360_soft_run_finding_review_fetch_summary',
    'moghare360_soft_run_finding_review_fetch_status_counts',
    'moghare360_soft_run_finding_review_fetch_severity_counts',
    'moghare360_soft_run_finding_review_fetch_corrective_status_counts',
    'moghare360_soft_run_finding_review_fetch_recent_findings',
    'moghare360_soft_run_finding_review_fetch_history_coverage',
    'moghare360_soft_run_finding_review_evaluate',
    'moghare360_soft_run_finding_review_status_label',
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
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent)
        && !preg_match('/\bUPDATE\s+dbo\./i', $helperContent)
        && !preg_match('/\bDELETE\s+FROM\b/i', $helperContent),
];

$results[] = [
    'name' => 'Helper does not write INSERT/UPDATE/DELETE',
    'pass' => $helperContent !== ''
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent),
];

preg_match_all('/FROM\s+dbo\.([a-z0-9_]+)/i', $helperContent, $fromMatches);
$readTables = array_unique(array_map('strtolower', $fromMatches[1] ?? []));
$allowedReadTables = ['erp_soft_run_findings', 'erp_soft_run_finding_history'];
$readBoundaryPass = $readTables !== []
    && count(array_diff($readTables, $allowedReadTables)) === 0;
$results[] = [
    'name' => 'Helper reads only Soft Run finding tables',
    'pass' => $readBoundaryPass,
];

$dashboardLinkChecks = [
    'erp-soft-run-finding-create.php' => 'create',
    'erp-soft-run-finding-board.php' => 'board',
    'erp-soft-run-finding-detail.php?finding_id=' => 'detail',
    'erp-soft-run-finding-workflow.php?finding_id=' => 'workflow',
    'erp-soft-run-pilot-final-closure-dashboard.php' => 'WAVE 7 final closure',
    'erp-soft-run-pilot-review-dashboard.php' => 'WAVE 7 pilot review',
];

foreach ($dashboardLinkChecks as $href => $label) {
    $results[] = [
        'name' => 'Dashboard links to ' . $label,
        'pass' => $dashboardContent !== '' && str_contains($dashboardContent, $href),
    ];
}

$results[] = [
    'name' => 'Board links to review dashboard',
    'pass' => $boardContent !== '' && str_contains($boardContent, 'erp-soft-run-finding-review-dashboard.php'),
];

$results[] = [
    'name' => 'Detail links to review dashboard',
    'pass' => $detailContent !== '' && str_contains($detailContent, 'erp-soft-run-finding-review-dashboard.php'),
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
        && str_contains($dashboardContent, 'not final delivery')
        && !preg_match('/submit-.*final-delivery/i', $dashboardContent),
];

$results[] = [
    'name' => 'Dashboard does not create delivery completion',
    'pass' => !preg_match('/delivery_completion_record/i', $dashboardContent)
        && !preg_match('/final_delivery_record/i', $dashboardContent),
];

$wave8cSqlFiles = glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_8c*') ?: [];
$results[] = [
    'name' => 'No SQL files created for WAVE 8C',
    'pass' => $wave8cSqlFiles === [],
];

$forbiddenWriteTables = [
    'erp_soft_run_pilot_executions',
    'erp_soft_run_pilot_execution_history',
    'erp_jobcards',
    'erp_jobcard_delivery',
    'erp_media',
    'erp_authorization',
    'erp_customers',
    'erp_vehicles',
    'erp_invoice',
    'erp_payment',
];
$noForbiddenWrites = true;
foreach ($forbiddenWriteTables as $table) {
    if (preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\s+dbo\.' . preg_quote($table, '/') . '\b/i', $helperContent)) {
        $noForbiddenWrites = false;
        break;
    }
    if (preg_match('/\bUPDATE\s+dbo\.' . preg_quote($table, '/') . '\b/i', $helperContent)) {
        $noForbiddenWrites = false;
        break;
    }
}
$results[] = [
    'name' => 'No writes to pilot execution/JobCard/delivery/evidence/authorization/customer/vehicle/payment tables',
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
    if (str_contains($content, 'finding-review') || str_contains($content, 'moghare360_soft_run_finding_review')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes', 'pass' => $authConfigUnchanged];

$wave6Helpers = [
    ['WAVE 6A control room helper unchanged', $controlRoomHelperContent],
    ['WAVE 6B scenario helper unchanged', $scenarioHelperContent],
    ['WAVE 6C operator test pack helper unchanged', $testPackHelperContent],
    ['WAVE 6D final closure helper unchanged', $wave6FinalClosureHelperContent],
];
foreach ($wave6Helpers as [$label, $content]) {
    $results[] = [
        'name' => $label,
        'pass' => $content !== ''
            && !str_contains($content, 'finding-review')
            && !str_contains($content, 'moghare360_soft_run_finding_review'),
    ];
}

$wave7Helpers = [
    ['WAVE 7A pilot execution helper unchanged', $pilotExecutionHelperContent],
    ['WAVE 7C review helper unchanged', $pilotReviewHelperContent],
    ['WAVE 7D final closure helper unchanged', $pilotFinalClosureHelperContent],
];
foreach ($wave7Helpers as [$label, $content]) {
    $results[] = [
        'name' => $label,
        'pass' => $content !== ''
            && !str_contains($content, 'finding-review')
            && !str_contains($content, 'moghare360_soft_run_finding_review'),
    ];
}

$results[] = [
    'name' => 'WAVE 8A/8B finding helper unchanged',
    'pass' => $findingHelperContent !== ''
        && !str_contains($findingHelperContent, 'moghare360_soft_run_finding_review'),
];

$results[] = [
    'name' => 'WAVE 8A create submit page unchanged',
    'pass' => $submitCreateContent !== ''
        && !str_contains($submitCreateContent, 'finding-review')
        && !str_contains($submitCreateContent, 'moghare360_soft_run_finding_review'),
];

$results[] = [
    'name' => 'WAVE 8B workflow submit page unchanged',
    'pass' => $submitWorkflowContent !== ''
        && !str_contains($submitWorkflowContent, 'finding-review')
        && !str_contains($submitWorkflowContent, 'moghare360_soft_run_finding_review'),
];

$evaluation = moghare360_soft_run_finding_review_evaluate();
$results[] = [
    'name' => 'Evaluate returns known review status',
    'pass' => in_array($evaluation['status'] ?? '', [
        MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_READY,
        MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ACTION_REQUIRED,
        MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_BLOCKED,
        MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_EMPTY,
        MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ERROR,
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
    fwrite(STDERR, 'WAVE 8C SOFT RUN FINDING REVIEW TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 8C SOFT RUN FINDING REVIEW TEST PASSED' . PHP_EOL;
exit(0);
