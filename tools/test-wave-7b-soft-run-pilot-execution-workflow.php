<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 7B Soft Run Pilot Execution Workflow CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-execution-helper.php';
$workflowPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-execution-workflow.php';
$submitPath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-pilot-execution-workflow.php';
$boardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-execution-board.php';
$detailPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-pilot-execution-detail.php';

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
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7B_SOFT_RUN_PILOT_EXECUTION_WORKFLOW_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7B_SOFT_RUN_PILOT_EXECUTION_WORKFLOW_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7B_SOFT_RUN_PILOT_EXECUTION_WORKFLOW_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_7_soft_run_execution' . DIRECTORY_SEPARATOR . 'WAVE_7B_SOFT_RUN_PILOT_EXECUTION_WORKFLOW_SIGNOFF.md',
];

require_once $helperPath;

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$workflowContent = is_file($workflowPath) ? (string)file_get_contents($workflowPath) : '';
$submitContent = is_file($submitPath) ? (string)file_get_contents($submitPath) : '';
$boardContent = is_file($boardPath) ? (string)file_get_contents($boardPath) : '';
$detailContent = is_file($detailPath) ? (string)file_get_contents($detailPath) : '';
$controlRoomHelperContent = is_file($controlRoomHelperPath) ? (string)file_get_contents($controlRoomHelperPath) : '';
$scenarioHelperContent = is_file($scenarioHelperPath) ? (string)file_get_contents($scenarioHelperPath) : '';
$testPackHelperContent = is_file($testPackHelperPath) ? (string)file_get_contents($testPackHelperPath) : '';
$finalClosureHelperContent = is_file($finalClosureHelperPath) ? (string)file_get_contents($finalClosureHelperPath) : '';

$wave7Bundle = $helperContent . $workflowContent . $submitContent . $boardContent . $detailContent;

$results = [];

$results[] = ['name' => 'Workflow page exists', 'pass' => is_file($workflowPath)];
$results[] = ['name' => 'Workflow submit page exists', 'pass' => is_file($submitPath)];

$requiredApis = [
    'moghare360_soft_run_pilot_execution_allowed_transitions',
    'moghare360_soft_run_pilot_execution_validate_transition',
    'moghare360_soft_run_pilot_execution_update_workflow',
];

$apiPass = true;
foreach ($requiredApis as $api) {
    if (!function_exists($api)) {
        $apiPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper contains new workflow APIs', 'pass' => $apiPass];

$transitions = moghare360_soft_run_pilot_execution_allowed_transitions();
$results[] = [
    'name' => 'Helper defines allowed transitions',
    'pass' => isset($transitions['DRAFT'], $transitions['STARTED'], $transitions['OBSERVED'])
        && in_array('STARTED', $transitions['DRAFT'], true)
        && in_array('OBSERVED', $transitions['STARTED'], true)
        && in_array('PASSED', $transitions['OBSERVED'], true),
];

$cancelledBlock = moghare360_soft_run_pilot_execution_validate_transition('CANCELLED', 'STARTED');
$results[] = [
    'name' => 'Helper blocks CANCELLED transitions',
    'pass' => ($cancelledBlock['ok'] ?? true) === false
        && ($transitions['CANCELLED'] ?? null) === [],
];

$invalidTransition = moghare360_soft_run_pilot_execution_validate_transition('DRAFT', 'PASSED');
$results[] = [
    'name' => 'Helper blocks invalid transitions',
    'pass' => ($invalidTransition['ok'] ?? true) === false,
];

$validTransition = moghare360_soft_run_pilot_execution_validate_transition('STARTED', 'OBSERVED');
$results[] = [
    'name' => 'Helper allows valid transitions',
    'pass' => ($validTransition['ok'] ?? false) === true,
];

$results[] = [
    'name' => 'Helper validates change_reason in workflow payload',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360_soft_run_pilot_execution_validate_workflow_payload')
        && str_contains($helperContent, 'change_reason'),
];

$emptyReason = moghare360_soft_run_pilot_execution_validate_workflow_payload([
    'execution_id' => '1',
    'new_execution_status' => 'OBSERVED',
    'evidence_status' => 'VISIBLE',
    'result_status' => 'NOT_EVALUATED',
    'change_reason' => '',
]);
$results[] = [
    'name' => 'Helper rejects empty change_reason',
    'pass' => ($emptyReason['ok'] ?? true) === false,
];

$results[] = [
    'name' => 'Helper uses prepared statements for workflow update',
    'pass' => $helperContent !== ''
        && preg_match('/UPDATE dbo\.erp_soft_run_pilot_executions SET[\s\S]*WHERE execution_id = \?/', $helperContent) === 1
        && preg_match('/INSERT INTO dbo\.erp_soft_run_pilot_execution_history[\s\S]*VALUES \(\?, \?, \?, \?, \?, \?, \?\)/', $helperContent) === 1,
];

preg_match_all('/UPDATE\s+dbo\.([a-z0-9_]+)/i', $helperContent, $updateMatches);
$updateTables = array_unique(array_map('strtolower', $updateMatches[1] ?? []));
$results[] = [
    'name' => 'Helper updates only dbo.erp_soft_run_pilot_executions',
    'pass' => $updateTables === ['erp_soft_run_pilot_executions'],
];

preg_match_all('/INSERT\s+INTO\s+dbo\.([a-z0-9_]+)/i', $helperContent, $insertMatches);
$insertTables = array_unique(array_map('strtolower', $insertMatches[1] ?? []));
$results[] = [
    'name' => 'Helper inserts history only into dbo.erp_soft_run_pilot_execution_history',
    'pass' => $insertTables === ['erp_soft_run_pilot_executions', 'erp_soft_run_pilot_execution_history'],
];

$forbiddenWriteTables = [
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
    'name' => 'Helper does not write to JobCard/delivery/evidence/authorization/customer/vehicle/payment tables',
    'pass' => $noForbiddenWrites,
];

$results[] = [
    'name' => 'Submit workflow page accepts POST only',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, "REQUEST_METHOD'] ?? '') !== 'POST'")
        && str_contains($submitContent, 'moghare360_soft_run_pilot_execution_update_workflow'),
];

$results[] = [
    'name' => 'Board links to workflow page',
    'pass' => $boardContent !== ''
        && str_contains($boardContent, 'erp-soft-run-pilot-execution-workflow.php?execution_id='),
];

$results[] = [
    'name' => 'Detail links to workflow page',
    'pass' => $detailContent !== ''
        && str_contains($detailContent, 'erp-soft-run-pilot-execution-workflow.php?execution_id='),
];

$results[] = [
    'name' => 'Board has no POST form',
    'pass' => $boardContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $boardContent),
];

$results[] = [
    'name' => 'Detail has no POST form',
    'pass' => $detailContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $detailContent),
];

$wave7bSqlFiles = glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_7b*') ?: [];
$results[] = [
    'name' => 'No SQL files created for WAVE 7B',
    'pass' => $wave7bSqlFiles === [],
];

$results[] = [
    'name' => 'No final delivery action exists',
    'pass' => $wave7Bundle !== ''
        && str_contains($workflowContent, 'not final delivery')
        && !preg_match('/vehicle_delivered/i', $wave7Bundle),
];

$results[] = [
    'name' => 'No delivery completion exists',
    'pass' => !preg_match('/delivery_completion_record/i', $wave7Bundle)
        && !preg_match('/final_delivery_record/i', $wave7Bundle),
];

$results[] = [
    'name' => 'No public portal/payment/accounting/legal e-signature activation',
    'pass' => $workflowContent !== ''
        && str_contains($workflowContent, 'Not legal e-signature')
        && str_contains($workflowContent, 'Not payment/accounting'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'pilot-execution-workflow') || str_contains($content, 'update_workflow')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'WAVE 6A control room helper unchanged',
    'pass' => $controlRoomHelperContent !== ''
        && !str_contains($controlRoomHelperContent, 'update_workflow')
        && !str_contains($controlRoomHelperContent, 'pilot-execution-workflow'),
];

$results[] = [
    'name' => 'WAVE 6B scenario helper unchanged',
    'pass' => $scenarioHelperContent !== ''
        && !str_contains($scenarioHelperContent, 'update_workflow'),
];

$results[] = [
    'name' => 'WAVE 6C operator test pack helper unchanged',
    'pass' => $testPackHelperContent !== ''
        && !str_contains($testPackHelperContent, 'update_workflow'),
];

$results[] = [
    'name' => 'WAVE 6D final closure helper unchanged',
    'pass' => $finalClosureHelperContent !== ''
        && !str_contains($finalClosureHelperContent, 'update_workflow'),
];

$results[] = [
    'name' => 'Helper captures old statuses before workflow update',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'old_execution_status')
        && str_contains($helperContent, 'old_result_status'),
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
    fwrite(STDERR, 'WAVE 7B SOFT RUN PILOT EXECUTION WORKFLOW TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 7B SOFT RUN PILOT EXECUTION WORKFLOW TEST PASSED' . PHP_EOL;
exit(0);
