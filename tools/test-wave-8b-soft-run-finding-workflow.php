<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 8B Soft Run Finding Workflow CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-helper.php';
$workflowPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-finding-workflow.php';
$submitPath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-finding-workflow.php';
$boardPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-finding-board.php';
$detailPath = $public . DIRECTORY_SEPARATOR . 'erp-soft-run-finding-detail.php';
$createSubmitPath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-finding.php';
$sql8aPath = $public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'wave_8a_soft_run_findings_register.sql';

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
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_8_soft_run_findings' . DIRECTORY_SEPARATOR . 'WAVE_8B_SOFT_RUN_FINDINGS_WORKFLOW_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_8_soft_run_findings' . DIRECTORY_SEPARATOR . 'WAVE_8B_SOFT_RUN_FINDINGS_WORKFLOW_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_8_soft_run_findings' . DIRECTORY_SEPARATOR . 'WAVE_8B_SOFT_RUN_FINDINGS_WORKFLOW_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_8_soft_run_findings' . DIRECTORY_SEPARATOR . 'WAVE_8B_SOFT_RUN_FINDINGS_WORKFLOW_SIGNOFF.md',
];

require_once $helperPath;

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$workflowContent = is_file($workflowPath) ? (string)file_get_contents($workflowPath) : '';
$submitContent = is_file($submitPath) ? (string)file_get_contents($submitPath) : '';
$boardContent = is_file($boardPath) ? (string)file_get_contents($boardPath) : '';
$detailContent = is_file($detailPath) ? (string)file_get_contents($detailPath) : '';
$createSubmitContent = is_file($createSubmitPath) ? (string)file_get_contents($createSubmitPath) : '';
$sql8aContent = is_file($sql8aPath) ? (string)file_get_contents($sql8aPath) : '';

$pilotExecutionHelperContent = is_file($pilotExecutionHelperPath) ? (string)file_get_contents($pilotExecutionHelperPath) : '';
$pilotReviewHelperContent = is_file($pilotReviewHelperPath) ? (string)file_get_contents($pilotReviewHelperPath) : '';
$pilotFinalClosureHelperContent = is_file($pilotFinalClosureHelperPath) ? (string)file_get_contents($pilotFinalClosureHelperPath) : '';
$controlRoomHelperContent = is_file($controlRoomHelperPath) ? (string)file_get_contents($controlRoomHelperPath) : '';
$scenarioHelperContent = is_file($scenarioHelperPath) ? (string)file_get_contents($scenarioHelperPath) : '';
$testPackHelperContent = is_file($testPackHelperPath) ? (string)file_get_contents($testPackHelperPath) : '';
$wave6FinalClosureHelperContent = is_file($wave6FinalClosureHelperPath) ? (string)file_get_contents($wave6FinalClosureHelperPath) : '';

$wave8bBundle = $helperContent . $workflowContent . $submitContent . $boardContent . $detailContent;

$results = [];

$results[] = ['name' => 'Workflow page exists', 'pass' => is_file($workflowPath)];
$results[] = ['name' => 'Workflow submit page exists', 'pass' => is_file($submitPath)];

$requiredApis = [
    'moghare360_soft_run_finding_allowed_transitions',
    'moghare360_soft_run_finding_validate_transition',
    'moghare360_soft_run_finding_update_workflow',
];

$apiPass = true;
foreach ($requiredApis as $api) {
    if (!function_exists($api)) {
        $apiPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper contains new workflow APIs', 'pass' => $apiPass];

$transitions = moghare360_soft_run_finding_allowed_transitions();
$results[] = [
    'name' => 'Helper defines allowed transitions',
    'pass' => isset($transitions['OPEN'], $transitions['UNDER_REVIEW'], $transitions['RESOLVED'])
        && in_array('UNDER_REVIEW', $transitions['OPEN'], true)
        && in_array('RESOLVED', $transitions['UNDER_REVIEW'], true)
        && in_array('CLOSED', $transitions['RESOLVED'], true),
];

$cancelledBlock = moghare360_soft_run_finding_validate_transition('CANCELLED', 'OPEN');
$results[] = [
    'name' => 'Helper blocks CANCELLED transitions',
    'pass' => ($cancelledBlock['ok'] ?? true) === false
        && ($transitions['CANCELLED'] ?? null) === [],
];

$invalidFindingTransition = moghare360_soft_run_finding_validate_transition('OPEN', 'CLOSED');
$results[] = [
    'name' => 'Helper blocks invalid finding transitions',
    'pass' => ($invalidFindingTransition['ok'] ?? true) === false,
];

$validFindingTransition = moghare360_soft_run_finding_validate_transition('OPEN', 'UNDER_REVIEW');
$results[] = [
    'name' => 'Helper allows valid finding transitions',
    'pass' => ($validFindingTransition['ok'] ?? false) === true,
];

$invalidCorrective = moghare360_soft_run_finding_validate_corrective_transition('NOT_STARTED', 'DONE');
$results[] = [
    'name' => 'Helper blocks invalid corrective action transitions',
    'pass' => ($invalidCorrective['ok'] ?? true) === false,
];

$validCorrective = moghare360_soft_run_finding_validate_corrective_transition('NOT_STARTED', 'IN_PROGRESS');
$results[] = [
    'name' => 'Helper allows valid corrective action transitions',
    'pass' => ($validCorrective['ok'] ?? false) === true,
];

$results[] = [
    'name' => 'Helper validates change_reason in workflow payload',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360_soft_run_finding_validate_workflow_payload')
        && str_contains($helperContent, 'change_reason'),
];

$emptyReason = moghare360_soft_run_finding_validate_workflow_payload([
    'finding_id' => '1',
    'new_finding_status' => 'UNDER_REVIEW',
    'corrective_action_status' => 'NOT_STARTED',
    'change_reason' => '',
]);
$results[] = [
    'name' => 'Helper rejects empty change_reason',
    'pass' => ($emptyReason['ok'] ?? true) === false,
];

$results[] = [
    'name' => 'Helper validates finding_status',
    'pass' => in_array('OPEN', moghare360_soft_run_finding_allowed_statuses(), true)
        && in_array('ACTION_REQUIRED', moghare360_soft_run_finding_allowed_statuses(), true),
];

$results[] = [
    'name' => 'Helper validates corrective_action_status',
    'pass' => in_array('IN_PROGRESS', moghare360_soft_run_finding_allowed_corrective_statuses(), true)
        && in_array('DONE', moghare360_soft_run_finding_allowed_corrective_statuses(), true),
];

$results[] = [
    'name' => 'Helper uses prepared statements',
    'pass' => $helperContent !== ''
        && preg_match('/UPDATE dbo\.erp_soft_run_findings SET[\s\S]*WHERE finding_id = \?/', $helperContent) === 1
        && preg_match('/INSERT INTO dbo\.erp_soft_run_finding_history[\s\S]*VALUES \(\?, \?, \?, \?, \?, \?, \?\)/', $helperContent) === 1,
];

preg_match_all('/UPDATE\s+dbo\.([a-z0-9_]+)/i', $helperContent, $updateMatches);
$updateTables = array_unique(array_map('strtolower', $updateMatches[1] ?? []));
$results[] = [
    'name' => 'Helper updates only dbo.erp_soft_run_findings',
    'pass' => $updateTables === ['erp_soft_run_findings'],
];

preg_match_all('/INSERT\s+INTO\s+dbo\.([a-z0-9_]+)/i', $helperContent, $insertMatches);
$insertTables = array_unique(array_map('strtolower', $insertMatches[1] ?? []));
$results[] = [
    'name' => 'Helper inserts history only into dbo.erp_soft_run_finding_history',
    'pass' => $insertTables === ['erp_soft_run_findings', 'erp_soft_run_finding_history'],
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
    'name' => 'Helper does not write to pilot execution/JobCard/delivery/evidence/authorization/customer/vehicle/payment tables',
    'pass' => $noForbiddenWrites,
];

$results[] = [
    'name' => 'Submit workflow page accepts POST only',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, "REQUEST_METHOD'] ?? '') !== 'POST'")
        && str_contains($submitContent, 'moghare360_soft_run_finding_update_workflow'),
];

$results[] = [
    'name' => 'Board links to workflow page',
    'pass' => $boardContent !== ''
        && str_contains($boardContent, 'erp-soft-run-finding-workflow.php?finding_id='),
];

$results[] = [
    'name' => 'Detail links to workflow page',
    'pass' => $detailContent !== ''
        && str_contains($detailContent, 'erp-soft-run-finding-workflow.php?finding_id='),
];

$results[] = [
    'name' => 'Board has no POST form',
    'pass' => $boardContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $boardContent),
];

$results[] = [
    'name' => 'Detail has no POST form',
    'pass' => $detailContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $detailContent),
];

$wave8bSqlFiles = glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_8b*') ?: [];
$results[] = [
    'name' => 'No SQL files created for WAVE 8B',
    'pass' => $wave8bSqlFiles === [],
];

$results[] = [
    'name' => 'No final delivery action exists',
    'pass' => $wave8bBundle !== ''
        && str_contains($workflowContent, 'not final delivery')
        && !preg_match('/vehicle_delivered/i', $wave8bBundle),
];

$results[] = [
    'name' => 'No delivery completion exists',
    'pass' => !preg_match('/delivery_completion_record/i', $wave8bBundle)
        && !preg_match('/final_delivery_record/i', $wave8bBundle),
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
    if (str_contains($content, 'finding-workflow') || str_contains($content, 'finding_update_workflow')) {
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
            && !str_contains($content, 'finding_update_workflow')
            && !str_contains($content, 'finding-workflow'),
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
            && !str_contains($content, 'soft-run-finding-workflow')
            && !str_contains($content, 'moghare360_soft_run_finding_update_workflow'),
    ];
}

$results[] = [
    'name' => 'WAVE 8A SQL unchanged',
    'pass' => $sql8aContent !== ''
        && !str_contains($sql8aContent, 'wave_8b')
        && !str_contains($sql8aContent, 'update_workflow'),
];

$results[] = [
    'name' => 'WAVE 8A create submit page unchanged',
    'pass' => $createSubmitContent !== ''
        && !str_contains($createSubmitContent, 'finding-workflow')
        && !str_contains($createSubmitContent, 'moghare360_soft_run_finding_update_workflow'),
];

$results[] = [
    'name' => 'Helper captures old statuses before workflow update',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'old_finding_status')
        && str_contains($helperContent, 'old_corrective_action_status'),
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
    fwrite(STDERR, 'WAVE 8B SOFT RUN FINDING WORKFLOW TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 8B SOFT RUN FINDING WORKFLOW TEST PASSED' . PHP_EOL;
exit(0);
