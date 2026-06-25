<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 9A Executive Soft Run Readiness CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-executive-soft-run-readiness-helper.php';
$dashboardPath = $public . DIRECTORY_SEPARATOR . 'erp-executive-soft-run-readiness-dashboard.php';
$controlRoomPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-control-room.php';
$wave6ClosurePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-final-closure-dashboard.php';
$wave7ClosurePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-final-closure-dashboard.php';
$wave8ClosurePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-finding-final-closure-dashboard.php';

$wave6HelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-final-closure-helper.php';
$wave7HelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-final-closure-helper.php';
$wave8HelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-final-closure-helper.php';
$findingHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-helper.php';
$findingReviewHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-review-helper.php';
$findingFinalClosureHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-final-closure-helper.php';
$submitFindingPath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-finding.php';
$submitWorkflowPath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-finding-workflow.php';

$forbiddenPaths = [
    $public . DIRECTORY_SEPARATOR . 'config.php',
    $public . DIRECTORY_SEPARATOR . 'staff-auth.php',
    $public . DIRECTORY_SEPARATOR . 'access-control.php',
    $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-auth-helper.php',
];

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_9_executive_readiness' . DIRECTORY_SEPARATOR . 'WAVE_9A_EXECUTIVE_SOFT_RUN_READINESS_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_9_executive_readiness' . DIRECTORY_SEPARATOR . 'WAVE_9A_EXECUTIVE_SOFT_RUN_READINESS_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_9_executive_readiness' . DIRECTORY_SEPARATOR . 'WAVE_9A_EXECUTIVE_SOFT_RUN_READINESS_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_9_executive_readiness' . DIRECTORY_SEPARATOR . 'WAVE_9A_EXECUTIVE_SOFT_RUN_READINESS_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$dashboardContent = is_file($dashboardPath) ? (string)file_get_contents($dashboardPath) : '';
$controlRoomContent = is_file($controlRoomPath) ? (string)file_get_contents($controlRoomPath) : '';
$wave6ClosureContent = is_file($wave6ClosurePath) ? (string)file_get_contents($wave6ClosurePath) : '';
$wave7ClosureContent = is_file($wave7ClosurePath) ? (string)file_get_contents($wave7ClosurePath) : '';
$wave8ClosureContent = is_file($wave8ClosurePath) ? (string)file_get_contents($wave8ClosurePath) : '';
$wave6HelperContent = is_file($wave6HelperPath) ? (string)file_get_contents($wave6HelperPath) : '';
$wave7HelperContent = is_file($wave7HelperPath) ? (string)file_get_contents($wave7HelperPath) : '';
$wave8HelperContent = is_file($wave8HelperPath) ? (string)file_get_contents($wave8HelperPath) : '';
$findingHelperContent = is_file($findingHelperPath) ? (string)file_get_contents($findingHelperPath) : '';
$findingReviewHelperContent = is_file($findingReviewHelperPath) ? (string)file_get_contents($findingReviewHelperPath) : '';
$findingFinalClosureHelperContent = is_file($findingFinalClosureHelperPath) ? (string)file_get_contents($findingFinalClosureHelperPath) : '';
$submitFindingContent = is_file($submitFindingPath) ? (string)file_get_contents($submitFindingPath) : '';
$submitWorkflowContent = is_file($submitWorkflowPath) ? (string)file_get_contents($submitWorkflowPath) : '';

require_once $helperPath;

$results = [];

$results[] = ['name' => 'Executive readiness helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Executive readiness dashboard exists', 'pass' => is_file($dashboardPath)];

$requiredApis = [
    'moghare360_executive_soft_run_readiness_fetch_wave6_status',
    'moghare360_executive_soft_run_readiness_fetch_wave7_status',
    'moghare360_executive_soft_run_readiness_fetch_wave8_status',
    'moghare360_executive_soft_run_readiness_fetch_findings_snapshot',
    'moghare360_executive_soft_run_readiness_required_pages',
    'moghare360_executive_soft_run_readiness_page_status',
    'moghare360_executive_soft_run_readiness_evaluate',
    'moghare360_executive_soft_run_readiness_status_label',
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
$allowedReadTables = ['erp_soft_run_pilot_executions', 'erp_soft_run_findings'];
$readBoundaryPass = $readTables === []
    || count(array_diff($readTables, $allowedReadTables)) === 0;
$results[] = [
    'name' => 'Helper reads only allowed Soft Run tables (fallback SELECT)',
    'pass' => $readBoundaryPass,
];

$results[] = [
    'name' => 'Helper safely includes WAVE 6/7/8 closure helpers',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-soft-run-final-closure-helper.php')
        && str_contains($helperContent, 'moghare360-soft-run-pilot-final-closure-helper.php')
        && str_contains($helperContent, 'moghare360-soft-run-finding-final-closure-helper.php'),
];

$dashboardLinkChecks = [
    'erp-soft-run-control-room.php' => 'WAVE 6 control room',
    'erp-soft-run-final-closure-dashboard.php' => 'WAVE 6 final closure',
    'erp-soft-run-pilot-final-closure-dashboard.php' => 'WAVE 7 final closure',
    'erp-soft-run-pilot-review-dashboard.php' => 'WAVE 7 pilot review',
    'erp-soft-run-finding-final-closure-dashboard.php' => 'WAVE 8 final closure',
    'erp-soft-run-finding-review-dashboard.php' => 'WAVE 8 review',
    'erp-soft-run-finding-board.php' => 'WAVE 8 board',
];

foreach ($dashboardLinkChecks as $href => $label) {
    $results[] = [
        'name' => 'Dashboard links to ' . $label,
        'pass' => $dashboardContent !== '' && str_contains($dashboardContent, $href),
    ];
}

$results[] = [
    'name' => 'Control room links to executive readiness dashboard',
    'pass' => $controlRoomContent !== ''
        && str_contains($controlRoomContent, 'erp-executive-soft-run-readiness-dashboard.php'),
];

$results[] = [
    'name' => 'WAVE 6 final closure links to executive readiness dashboard',
    'pass' => $wave6ClosureContent !== ''
        && str_contains($wave6ClosureContent, 'erp-executive-soft-run-readiness-dashboard.php'),
];

$results[] = [
    'name' => 'WAVE 7 final closure links to executive readiness dashboard',
    'pass' => $wave7ClosureContent !== ''
        && str_contains($wave7ClosureContent, 'erp-executive-soft-run-readiness-dashboard.php'),
];

$results[] = [
    'name' => 'WAVE 8 final closure links to executive readiness dashboard',
    'pass' => $wave8ClosureContent !== ''
        && str_contains($wave8ClosureContent, 'erp-executive-soft-run-readiness-dashboard.php'),
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

$wave9aSqlFiles = glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_9a*') ?: [];
$results[] = [
    'name' => 'No SQL files created for WAVE 9A',
    'pass' => $wave9aSqlFiles === [],
];

$forbiddenWriteTables = [
    'erp_soft_run_pilot_executions',
    'erp_soft_run_pilot_execution_history',
    'erp_soft_run_findings',
    'erp_soft_run_finding_history',
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
}
$results[] = [
    'name' => 'No writes to pilot/finding/JobCard/delivery/evidence/authorization/customer/vehicle/payment tables',
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
    if (str_contains($content, 'executive-soft-run-readiness') || str_contains($content, 'moghare360_executive_soft_run_readiness')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'WAVE 6 final closure helper unchanged',
    'pass' => $wave6HelperContent !== '' && !str_contains($wave6HelperContent, 'executive-soft-run-readiness'),
];

$results[] = [
    'name' => 'WAVE 7 pilot final closure helper unchanged',
    'pass' => $wave7HelperContent !== '' && !str_contains($wave7HelperContent, 'executive-soft-run-readiness'),
];

$results[] = [
    'name' => 'WAVE 8 finding final closure helper unchanged',
    'pass' => $wave8HelperContent !== '' && !str_contains($wave8HelperContent, 'executive-soft-run-readiness'),
];

$results[] = [
    'name' => 'WAVE 8A finding helper unchanged',
    'pass' => $findingHelperContent !== '' && !str_contains($findingHelperContent, 'executive-soft-run-readiness'),
];

$results[] = [
    'name' => 'WAVE 8C review helper unchanged',
    'pass' => $findingReviewHelperContent !== ''
        && !str_contains($findingReviewHelperContent, 'executive-soft-run-readiness')
        && !str_contains($findingReviewHelperContent, 'moghare360_executive_soft_run_readiness'),
];

$results[] = [
    'name' => 'WAVE 8D final closure helper file unchanged',
    'pass' => $findingFinalClosureHelperContent !== ''
        && !str_contains($findingFinalClosureHelperContent, 'executive-soft-run-readiness'),
];

$results[] = [
    'name' => 'WAVE 8A submit page unchanged',
    'pass' => $submitFindingContent !== '' && !str_contains($submitFindingContent, 'executive-soft-run-readiness'),
];

$results[] = [
    'name' => 'WAVE 8B submit workflow page unchanged',
    'pass' => $submitWorkflowContent !== '' && !str_contains($submitWorkflowContent, 'executive-soft-run-readiness'),
];

$requiredPages = moghare360_executive_soft_run_readiness_required_pages();
$results[] = [
    'name' => 'Helper defines required runtime pages',
    'pass' => count($requiredPages) >= 7,
];

$evaluation = moghare360_executive_soft_run_readiness_evaluate();
$results[] = [
    'name' => 'Evaluate returns known executive readiness status',
    'pass' => in_array($evaluation['status'] ?? '', [
        MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_READY,
        MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_GO_REVIEW,
        MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_BLOCKED,
        MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_EMPTY,
        MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_ERROR,
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
    fwrite(STDERR, 'WAVE 9A EXECUTIVE SOFT RUN READINESS TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 9A EXECUTIVE SOFT RUN READINESS TEST PASSED' . PHP_EOL;
exit(0);
