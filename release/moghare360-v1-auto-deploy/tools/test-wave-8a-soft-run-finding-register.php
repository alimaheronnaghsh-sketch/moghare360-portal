<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 8A Soft Run Findings Register CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$sqlPath = $public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'wave_8a_soft_run_findings_register.sql';
$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-helper.php';
$createPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-finding-create.php';
$submitPath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-finding.php';
$boardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-finding-board.php';
$detailPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-finding-detail.php';

$pilotExecutionHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-execution-helper.php';
$pilotReviewHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-review-helper.php';
$pilotFinalClosureHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-final-closure-helper.php';
$pilotExecutionSubmitPath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-pilot-execution.php';
$pilotWorkflowSubmitPath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-pilot-execution-workflow.php';

$pilotFinalClosurePagePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-final-closure-dashboard.php';
$pilotReviewPagePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-review-dashboard.php';

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
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_8_soft_run_findings' . DIRECTORY_SEPARATOR . 'WAVE_8A_SOFT_RUN_FINDINGS_REGISTER_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_8_soft_run_findings' . DIRECTORY_SEPARATOR . 'WAVE_8A_SOFT_RUN_FINDINGS_REGISTER_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_8_soft_run_findings' . DIRECTORY_SEPARATOR . 'WAVE_8A_SOFT_RUN_FINDINGS_REGISTER_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_8_soft_run_findings' . DIRECTORY_SEPARATOR . 'WAVE_8A_SOFT_RUN_FINDINGS_REGISTER_SIGNOFF.md',
];

require_once $helperPath;

$sqlContent = is_file($sqlPath) ? (string)file_get_contents($sqlPath) : '';
$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$createContent = is_file($createPath) ? (string)file_get_contents($createPath) : '';
$submitContent = is_file($submitPath) ? (string)file_get_contents($submitPath) : '';
$boardContent = is_file($boardPath) ? (string)file_get_contents($boardPath) : '';
$detailContent = is_file($detailPath) ? (string)file_get_contents($detailPath) : '';

$pilotExecutionHelperContent = is_file($pilotExecutionHelperPath) ? (string)file_get_contents($pilotExecutionHelperPath) : '';
$pilotReviewHelperContent = is_file($pilotReviewHelperPath) ? (string)file_get_contents($pilotReviewHelperPath) : '';
$pilotFinalClosureHelperContent = is_file($pilotFinalClosureHelperPath) ? (string)file_get_contents($pilotFinalClosureHelperPath) : '';
$pilotExecutionSubmitContent = is_file($pilotExecutionSubmitPath) ? (string)file_get_contents($pilotExecutionSubmitPath) : '';
$pilotWorkflowSubmitContent = is_file($pilotWorkflowSubmitPath) ? (string)file_get_contents($pilotWorkflowSubmitPath) : '';
$pilotFinalClosurePageContent = is_file($pilotFinalClosurePagePath) ? (string)file_get_contents($pilotFinalClosurePagePath) : '';
$pilotReviewPageContent = is_file($pilotReviewPagePath) ? (string)file_get_contents($pilotReviewPagePath) : '';

$controlRoomHelperContent = is_file($controlRoomHelperPath) ? (string)file_get_contents($controlRoomHelperPath) : '';
$scenarioHelperContent = is_file($scenarioHelperPath) ? (string)file_get_contents($scenarioHelperPath) : '';
$testPackHelperContent = is_file($testPackHelperPath) ? (string)file_get_contents($testPackHelperPath) : '';
$wave6FinalClosureHelperContent = is_file($wave6FinalClosureHelperPath) ? (string)file_get_contents($wave6FinalClosureHelperPath) : '';

$wave8Bundle = $helperContent . $createContent . $submitContent . $boardContent . $detailContent;

$results = [];

$results[] = ['name' => 'SQL file exists', 'pass' => is_file($sqlPath)];

$results[] = [
    'name' => 'SQL contains dbo.erp_soft_run_findings',
    'pass' => $sqlContent !== '' && str_contains($sqlContent, 'dbo.erp_soft_run_findings'),
];

$results[] = [
    'name' => 'SQL contains dbo.erp_soft_run_finding_history',
    'pass' => $sqlContent !== '' && str_contains($sqlContent, 'dbo.erp_soft_run_finding_history'),
];

$results[] = [
    'name' => 'SQL is idempotent',
    'pass' => $sqlContent !== ''
        && str_contains($sqlContent, 'IF OBJECT_ID')
        && !preg_match('/\bDROP\s+TABLE\b/i', $sqlContent),
];

$results[] = [
    'name' => 'SQL does not drop tables',
    'pass' => $sqlContent !== '' && !preg_match('/\bDROP\s+TABLE\b/i', $sqlContent),
];

$results[] = ['name' => 'Helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Create page exists', 'pass' => is_file($createPath)];
$results[] = ['name' => 'Submit page exists', 'pass' => is_file($submitPath)];
$results[] = ['name' => 'Board page exists', 'pass' => is_file($boardPath)];
$results[] = ['name' => 'Detail page exists', 'pass' => is_file($detailPath)];

$requiredApis = [
    'moghare360_soft_run_finding_allowed_types',
    'moghare360_soft_run_finding_allowed_severities',
    'moghare360_soft_run_finding_allowed_statuses',
    'moghare360_soft_run_finding_allowed_corrective_statuses',
    'moghare360_soft_run_finding_create',
    'moghare360_soft_run_finding_fetch_recent',
    'moghare360_soft_run_finding_fetch_detail',
    'moghare360_soft_run_finding_fetch_history',
    'moghare360_soft_run_finding_status_label',
    'moghare360_soft_run_finding_severity_label',
];

$apiPass = true;
foreach ($requiredApis as $api) {
    if (!function_exists($api)) {
        $apiPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper contains required APIs', 'pass' => $apiPass];

preg_match_all('/INSERT\s+INTO\s+dbo\.([a-z0-9_]+)/i', $helperContent, $insertMatches);
$insertTables = array_unique(array_map('strtolower', $insertMatches[1] ?? []));
$allowedInsertTables = ['erp_soft_run_findings', 'erp_soft_run_finding_history'];
$insertBoundaryPass = $insertTables !== []
    && count(array_diff($insertTables, $allowedInsertTables)) === 0
    && count(array_diff($allowedInsertTables, $insertTables)) === 0;
$results[] = ['name' => 'Helper writes only to Soft Run finding tables', 'pass' => $insertBoundaryPass];

$results[] = [
    'name' => 'Helper uses prepared statements',
    'pass' => $helperContent !== ''
        && preg_match('/VALUES \(\?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?\)/', $helperContent) === 1
        && preg_match('/VALUES \(\?, \?, \?, \?, \?, \?, \?\)/', $helperContent) === 1,
];

$findingTypes = moghare360_soft_run_finding_allowed_types();
$severities = moghare360_soft_run_finding_allowed_severities();
$findingStatuses = moghare360_soft_run_finding_allowed_statuses();
$correctiveStatuses = moghare360_soft_run_finding_allowed_corrective_statuses();

$results[] = [
    'name' => 'Helper validates finding types',
    'pass' => in_array('ISSUE', $findingTypes, true)
        && in_array('OBSERVATION', $findingTypes, true)
        && in_array('RISK', $findingTypes, true),
];

$results[] = [
    'name' => 'Helper validates severity levels',
    'pass' => in_array('LOW', $severities, true)
        && in_array('CRITICAL', $severities, true),
];

$results[] = [
    'name' => 'Helper validates finding statuses',
    'pass' => in_array('OPEN', $findingStatuses, true)
        && in_array('ACTION_REQUIRED', $findingStatuses, true),
];

$results[] = [
    'name' => 'Helper validates corrective action statuses',
    'pass' => in_array('NOT_STARTED', $correctiveStatuses, true)
        && in_array('IN_PROGRESS', $correctiveStatuses, true),
];

$results[] = [
    'name' => 'Submit page accepts POST only',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, "REQUEST_METHOD'] ?? '') !== 'POST'")
        && str_contains($submitContent, 'erp-soft-run-finding-create.php'),
];

$results[] = [
    'name' => 'Board page has no POST form',
    'pass' => $boardContent !== ''
        && !preg_match('/method\s*=\s*["\']post["\']/i', $boardContent)
        && !preg_match('/\bINSERT\s+INTO\b/i', $boardContent),
];

$results[] = [
    'name' => 'Detail page has no POST form',
    'pass' => $detailContent !== ''
        && !preg_match('/method\s*=\s*["\']post["\']/i', $detailContent)
        && !preg_match('/\bINSERT\s+INTO\b/i', $detailContent),
];

$results[] = [
    'name' => 'No final delivery action exists',
    'pass' => $wave8Bundle !== ''
        && str_contains($createContent, 'not final delivery')
        && !preg_match('/submit-.*final-delivery/i', $wave8Bundle)
        && !preg_match('/vehicle_delivered/i', $wave8Bundle),
];

$results[] = [
    'name' => 'No delivery completion exists',
    'pass' => !preg_match('/delivery_completion_record/i', $wave8Bundle)
        && !preg_match('/final_delivery_record/i', $wave8Bundle),
];

$results[] = [
    'name' => 'No public portal/payment/accounting/legal e-signature activation',
    'pass' => $createContent !== ''
        && str_contains($createContent, 'Not legal e-signature')
        && str_contains($createContent, 'Not payment/accounting')
        && str_contains($createContent, 'پورتال عمومی'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'soft-run-finding') || str_contains($content, 'moghare360_soft_run_finding')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'WAVE 6A control room helper unchanged',
    'pass' => $controlRoomHelperContent !== ''
        && !str_contains($controlRoomHelperContent, 'soft-run-finding')
        && !str_contains($controlRoomHelperContent, 'moghare360_soft_run_finding'),
];

$results[] = [
    'name' => 'WAVE 6B scenario helper unchanged',
    'pass' => $scenarioHelperContent !== ''
        && !str_contains($scenarioHelperContent, 'soft-run-finding')
        && !str_contains($scenarioHelperContent, 'moghare360_soft_run_finding'),
];

$results[] = [
    'name' => 'WAVE 6C operator test pack helper unchanged',
    'pass' => $testPackHelperContent !== ''
        && !str_contains($testPackHelperContent, 'soft-run-finding')
        && !str_contains($testPackHelperContent, 'moghare360_soft_run_finding'),
];

$results[] = [
    'name' => 'WAVE 6D final closure helper unchanged',
    'pass' => $wave6FinalClosureHelperContent !== ''
        && !str_contains($wave6FinalClosureHelperContent, 'soft-run-finding')
        && !str_contains($wave6FinalClosureHelperContent, 'moghare360_soft_run_finding'),
];

$results[] = [
    'name' => 'WAVE 7A pilot execution helper unchanged',
    'pass' => $pilotExecutionHelperContent !== ''
        && !str_contains($pilotExecutionHelperContent, 'soft-run-finding')
        && !str_contains($pilotExecutionHelperContent, 'moghare360_soft_run_finding'),
];

$results[] = [
    'name' => 'WAVE 7A submit page unchanged',
    'pass' => $pilotExecutionSubmitContent !== ''
        && !str_contains($pilotExecutionSubmitContent, 'soft-run-finding')
        && !str_contains($pilotExecutionSubmitContent, 'moghare360_soft_run_finding'),
];

$results[] = [
    'name' => 'WAVE 7B submit workflow page unchanged',
    'pass' => $pilotWorkflowSubmitContent !== ''
        && !str_contains($pilotWorkflowSubmitContent, 'soft-run-finding')
        && !str_contains($pilotWorkflowSubmitContent, 'moghare360_soft_run_finding'),
];

$results[] = [
    'name' => 'WAVE 7C review helper unchanged',
    'pass' => $pilotReviewHelperContent !== ''
        && !str_contains($pilotReviewHelperContent, 'soft-run-finding')
        && !str_contains($pilotReviewHelperContent, 'moghare360_soft_run_finding'),
];

$results[] = [
    'name' => 'WAVE 7D final closure helper unchanged',
    'pass' => $pilotFinalClosureHelperContent !== ''
        && !str_contains($pilotFinalClosureHelperContent, 'soft-run-finding')
        && !str_contains($pilotFinalClosureHelperContent, 'moghare360_soft_run_finding'),
];

$results[] = [
    'name' => 'Pilot final closure dashboard links to finding pages',
    'pass' => $pilotFinalClosurePageContent !== ''
        && str_contains($pilotFinalClosurePageContent, 'erp-soft-run-finding-create.php')
        && str_contains($pilotFinalClosurePageContent, 'erp-soft-run-finding-board.php'),
];

$results[] = [
    'name' => 'Pilot review dashboard links to finding pages',
    'pass' => $pilotReviewPageContent !== ''
        && str_contains($pilotReviewPageContent, 'erp-soft-run-finding-create.php')
        && str_contains($pilotReviewPageContent, 'erp-soft-run-finding-board.php'),
];

$results[] = [
    'name' => 'Create page posts to submit-soft-run-finding.php',
    'pass' => $createContent !== ''
        && str_contains($createContent, 'submit-soft-run-finding.php'),
];

$results[] = [
    'name' => 'Helper creates history after finding create',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'erp_soft_run_finding_history')
        && str_contains($helperContent, 'Initial Soft Run finding record created'),
];

$schema = moghare360_soft_run_finding_schema_status();
$results[] = [
    'name' => 'Schema status returns READY or BLOCKED safely',
    'pass' => in_array($schema['schema_status'] ?? '', [
        MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY,
        MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED,
    ], true),
];

$blockedCreate = moghare360_soft_run_finding_create([
    'finding_type' => 'ISSUE',
    'severity_level' => 'LOW',
    'finding_title' => 'Test finding',
]);
$results[] = [
    'name' => 'Create does not fake success when schema blocked',
    'pass' => ($schema['schema_status'] ?? '') === MOGHARE360_SOFT_RUN_FINDING_SCHEMA_READY
        || ($blockedCreate['ok'] ?? true) === false,
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
    fwrite(STDERR, 'WAVE 8A SOFT RUN FINDINGS REGISTER TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 8A SOFT RUN FINDINGS REGISTER TEST PASSED' . PHP_EOL;
exit(0);
