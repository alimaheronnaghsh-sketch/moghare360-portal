<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 7A Soft Run Pilot Execution CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$sqlPath = $public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'wave_7a_soft_run_pilot_execution_log.sql';
$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-execution-helper.php';
$createPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-execution-create.php';
$submitPath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-pilot-execution.php';
$boardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-execution-board.php';
$detailPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-execution-detail.php';
$finalClosurePagePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-final-closure-dashboard.php';
$testPackPagePath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-operator-test-pack.php';

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
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7A_SOFT_RUN_PILOT_EXECUTION_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7A_SOFT_RUN_PILOT_EXECUTION_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7A_SOFT_RUN_PILOT_EXECUTION_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7A_SOFT_RUN_PILOT_EXECUTION_SIGNOFF.md',
];

require_once $helperPath;

$sqlContent = is_file($sqlPath) ? (string)file_get_contents($sqlPath) : '';
$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$createContent = is_file($createPath) ? (string)file_get_contents($createPath) : '';
$submitContent = is_file($submitPath) ? (string)file_get_contents($submitPath) : '';
$boardContent = is_file($boardPath) ? (string)file_get_contents($boardPath) : '';
$detailContent = is_file($detailPath) ? (string)file_get_contents($detailPath) : '';
$finalClosurePageContent = is_file($finalClosurePagePath) ? (string)file_get_contents($finalClosurePagePath) : '';
$testPackPageContent = is_file($testPackPagePath) ? (string)file_get_contents($testPackPagePath) : '';
$controlRoomHelperContent = is_file($controlRoomHelperPath) ? (string)file_get_contents($controlRoomHelperPath) : '';
$scenarioHelperContent = is_file($scenarioHelperPath) ? (string)file_get_contents($scenarioHelperPath) : '';
$testPackHelperContent = is_file($testPackHelperPath) ? (string)file_get_contents($testPackHelperPath) : '';
$finalClosureHelperContent = is_file($finalClosureHelperPath) ? (string)file_get_contents($finalClosureHelperPath) : '';

$wave7Bundle = $helperContent . $createContent . $submitContent . $boardContent . $detailContent;

$results = [];

$results[] = ['name' => 'SQL file exists', 'pass' => is_file($sqlPath)];

$results[] = [
    'name' => 'SQL contains dbo.erp_soft_run_pilot_executions',
    'pass' => $sqlContent !== '' && str_contains($sqlContent, 'dbo.erp_soft_run_pilot_executions'),
];

$results[] = [
    'name' => 'SQL contains dbo.erp_soft_run_pilot_execution_history',
    'pass' => $sqlContent !== '' && str_contains($sqlContent, 'dbo.erp_soft_run_pilot_execution_history'),
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
    'moghare360_soft_run_pilot_execution_allowed_statuses',
    'moghare360_soft_run_pilot_execution_allowed_evidence_statuses',
    'moghare360_soft_run_pilot_execution_allowed_result_statuses',
    'moghare360_soft_run_pilot_execution_create',
    'moghare360_soft_run_pilot_execution_fetch_recent',
    'moghare360_soft_run_pilot_execution_fetch_detail',
    'moghare360_soft_run_pilot_execution_fetch_history',
    'moghare360_soft_run_pilot_execution_status_label',
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
$allowedInsertTables = ['erp_soft_run_pilot_executions', 'erp_soft_run_pilot_execution_history'];
$insertBoundaryPass = $insertTables !== []
    && count(array_diff($insertTables, $allowedInsertTables)) === 0
    && count(array_diff($allowedInsertTables, $insertTables)) === 0;
$results[] = ['name' => 'Helper writes only to Soft Run pilot execution tables', 'pass' => $insertBoundaryPass];

$results[] = [
    'name' => 'Helper uses prepared statements',
    'pass' => $helperContent !== ''
        && preg_match('/VALUES \(\?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?\)/', $helperContent) === 1
        && preg_match('/VALUES \(\?, \?, \?, \?, \?, \?, \?\)/', $helperContent) === 1,
];

$executionStatuses = moghare360_soft_run_pilot_execution_allowed_statuses();
$evidenceStatuses = moghare360_soft_run_pilot_execution_allowed_evidence_statuses();
$resultStatuses = moghare360_soft_run_pilot_execution_allowed_result_statuses();

$results[] = [
    'name' => 'Helper validates execution statuses',
    'pass' => in_array('STARTED', $executionStatuses, true)
        && in_array('PASSED', $executionStatuses, true)
        && in_array('BLOCKED', $executionStatuses, true),
];

$results[] = [
    'name' => 'Helper validates evidence statuses',
    'pass' => in_array('PENDING_REVIEW', $evidenceStatuses, true)
        && in_array('VISIBLE', $evidenceStatuses, true),
];

$results[] = [
    'name' => 'Helper validates result statuses',
    'pass' => in_array('NOT_EVALUATED', $resultStatuses, true)
        && in_array('PASS', $resultStatuses, true)
        && in_array('NEEDS_REVIEW', $resultStatuses, true),
];

$results[] = [
    'name' => 'Submit page accepts POST only',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, "REQUEST_METHOD'] ?? '') !== 'POST'")
        && str_contains($submitContent, 'erp-soft-run-pilot-execution-create.php'),
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
    'pass' => $wave7Bundle !== ''
        && str_contains($createContent, 'not final delivery')
        && !preg_match('/submit-.*final-delivery/i', $wave7Bundle)
        && !preg_match('/vehicle_delivered/i', $wave7Bundle),
];

$results[] = [
    'name' => 'No delivery completion exists',
    'pass' => !preg_match('/delivery_completion_record/i', $wave7Bundle)
        && !preg_match('/final_delivery_record/i', $wave7Bundle),
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
    if (str_contains($content, 'soft-run-pilot-execution') || str_contains($content, 'moghare360_soft_run_pilot_execution')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'WAVE 6A control room helper unchanged',
    'pass' => $controlRoomHelperContent !== ''
        && !str_contains($controlRoomHelperContent, 'soft-run-pilot-execution')
        && !str_contains($controlRoomHelperContent, 'moghare360_soft_run_pilot_execution'),
];

$results[] = [
    'name' => 'WAVE 6B scenario helper unchanged',
    'pass' => $scenarioHelperContent !== ''
        && !str_contains($scenarioHelperContent, 'soft-run-pilot-execution')
        && !str_contains($scenarioHelperContent, 'moghare360_soft_run_pilot_execution'),
];

$results[] = [
    'name' => 'WAVE 6C operator test pack helper unchanged',
    'pass' => $testPackHelperContent !== ''
        && !str_contains($testPackHelperContent, 'soft-run-pilot-execution')
        && !str_contains($testPackHelperContent, 'moghare360_soft_run_pilot_execution'),
];

$results[] = [
    'name' => 'WAVE 6D final closure helper unchanged',
    'pass' => $finalClosureHelperContent !== ''
        && !str_contains($finalClosureHelperContent, 'soft-run-pilot-execution')
        && !str_contains($finalClosureHelperContent, 'moghare360_soft_run_pilot_execution'),
];

$results[] = [
    'name' => 'Final closure dashboard links to pilot execution pages',
    'pass' => $finalClosurePageContent !== ''
        && str_contains($finalClosurePageContent, 'erp-soft-run-pilot-execution-create.php')
        && str_contains($finalClosurePageContent, 'erp-soft-run-pilot-execution-board.php'),
];

$results[] = [
    'name' => 'Operator test pack links to pilot execution pages',
    'pass' => $testPackPageContent !== ''
        && str_contains($testPackPageContent, 'erp-soft-run-pilot-execution-create.php')
        && str_contains($testPackPageContent, 'erp-soft-run-pilot-execution-board.php'),
];

$results[] = [
    'name' => 'Create page posts to submit-soft-run-pilot-execution.php',
    'pass' => $createContent !== ''
        && str_contains($createContent, 'submit-soft-run-pilot-execution.php'),
];

$results[] = [
    'name' => 'Helper creates history after execution create',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'erp_soft_run_pilot_execution_history')
        && str_contains($helperContent, 'Initial pilot execution record created'),
];

$schema = moghare360_soft_run_pilot_execution_schema_status();
$results[] = [
    'name' => 'Schema status returns READY or BLOCKED safely',
    'pass' => in_array($schema['schema_status'] ?? '', [
        MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY,
        MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED,
    ], true),
];

$blockedCreate = moghare360_soft_run_pilot_execution_create([
    'scenario_key' => 'soft_run_control_room',
    'scenario_title' => 'Test scenario',
    'execution_status' => 'STARTED',
]);
$results[] = [
    'name' => 'Create does not fake success when schema blocked',
    'pass' => ($schema['schema_status'] ?? '') === MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_READY
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
    fwrite(STDERR, 'WAVE 7A SOFT RUN PILOT EXECUTION TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 7A SOFT RUN PILOT EXECUTION TEST PASSED' . PHP_EOL;
exit(0);
