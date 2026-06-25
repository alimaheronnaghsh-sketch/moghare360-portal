<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 9C Executive Go/No-Go Decision Workflow CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-executive-go-no-go-decision-helper.php';
$workflowPath = $public . DIRECTORY_SEPARATOR . 'erp-executive-go-no-go-decision-workflow.php';
$submitPath = $public . DIRECTORY_SEPARATOR . 'submit-executive-go-no-go-decision-workflow.php';
$boardPath = $public . DIRECTORY_SEPARATOR . 'erp-executive-go-no-go-decision-board.php';
$detailPath = $public . DIRECTORY_SEPARATOR . 'erp-executive-go-no-go-decision-detail.php';
$createSubmitPath = $public . DIRECTORY_SEPARATOR . 'submit-executive-go-no-go-decision.php';
$sql9bPath = $public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'wave_9b_executive_go_no_go_decision_log.sql';

$readinessHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-executive-soft-run-readiness-helper.php';
$wave6HelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-final-closure-helper.php';
$wave7HelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-final-closure-helper.php';
$wave8HelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-final-closure-helper.php';
$findingHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-helper.php';

$forbiddenPaths = [
    $public . DIRECTORY_SEPARATOR . 'config.php',
    $public . DIRECTORY_SEPARATOR . 'staff-auth.php',
    $public . DIRECTORY_SEPARATOR . 'access-control.php',
    $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-auth-helper.php',
];

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_9_executive_readiness' . DIRECTORY_SEPARATOR . 'WAVE_9C_EXECUTIVE_GO_NO_GO_DECISION_WORKFLOW_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_9_executive_readiness' . DIRECTORY_SEPARATOR . 'WAVE_9C_EXECUTIVE_GO_NO_GO_DECISION_WORKFLOW_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_9_executive_readiness' . DIRECTORY_SEPARATOR . 'WAVE_9C_EXECUTIVE_GO_NO_GO_DECISION_WORKFLOW_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_9_executive_readiness' . DIRECTORY_SEPARATOR . 'WAVE_9C_EXECUTIVE_GO_NO_GO_DECISION_WORKFLOW_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$workflowContent = is_file($workflowPath) ? (string)file_get_contents($workflowPath) : '';
$submitContent = is_file($submitPath) ? (string)file_get_contents($submitPath) : '';
$boardContent = is_file($boardPath) ? (string)file_get_contents($boardPath) : '';
$detailContent = is_file($detailPath) ? (string)file_get_contents($detailPath) : '';
$createSubmitContent = is_file($createSubmitPath) ? (string)file_get_contents($createSubmitPath) : '';
$sql9bContent = is_file($sql9bPath) ? (string)file_get_contents($sql9bPath) : '';
$readinessHelperContent = is_file($readinessHelperPath) ? (string)file_get_contents($readinessHelperPath) : '';
$wave6HelperContent = is_file($wave6HelperPath) ? (string)file_get_contents($wave6HelperPath) : '';
$wave7HelperContent = is_file($wave7HelperPath) ? (string)file_get_contents($wave7HelperPath) : '';
$wave8HelperContent = is_file($wave8HelperPath) ? (string)file_get_contents($wave8HelperPath) : '';
$findingHelperContent = is_file($findingHelperPath) ? (string)file_get_contents($findingHelperPath) : '';

require_once $helperPath;

$wave9cBundle = $helperContent . $workflowContent . $submitContent . $boardContent . $detailContent;

$results = [];

$results[] = ['name' => 'Workflow page exists', 'pass' => is_file($workflowPath)];
$results[] = ['name' => 'Workflow submit page exists', 'pass' => is_file($submitPath)];

$requiredApis = [
    'moghare360_executive_go_no_go_decision_allowed_transitions',
    'moghare360_executive_go_no_go_decision_validate_transition',
    'moghare360_executive_go_no_go_decision_update_workflow',
];

$apiPass = true;
foreach ($requiredApis as $api) {
    if (!function_exists($api)) {
        $apiPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper contains new workflow APIs', 'pass' => $apiPass];

$transitions = moghare360_executive_go_no_go_decision_allowed_transitions();
$results[] = [
    'name' => 'Helper defines allowed decision status transitions',
    'pass' => isset($transitions['RECORDED'], $transitions['UNDER_REVIEW'], $transitions['ACCEPTED'])
        && in_array('UNDER_REVIEW', $transitions['RECORDED'], true)
        && in_array('ACCEPTED', $transitions['UNDER_REVIEW'], true)
        && in_array('CLOSED', $transitions['ACCEPTED'], true)
        && in_array('ACTION_REQUIRED', $transitions['ACCEPTED'], true)
        && in_array('UNDER_REVIEW', $transitions['CLOSED'], true),
];

$cancelledBlock = moghare360_executive_go_no_go_decision_validate_transition('CANCELLED', 'UNDER_REVIEW');
$results[] = [
    'name' => 'Helper blocks CANCELLED transitions',
    'pass' => ($cancelledBlock['ok'] ?? true) === false
        && ($transitions['CANCELLED'] ?? null) === [],
];

$invalidTransition = moghare360_executive_go_no_go_decision_validate_transition('RECORDED', 'CLOSED');
$results[] = [
    'name' => 'Helper blocks invalid decision transitions',
    'pass' => ($invalidTransition['ok'] ?? true) === false,
];

$validTransition = moghare360_executive_go_no_go_decision_validate_transition('RECORDED', 'UNDER_REVIEW');
$results[] = [
    'name' => 'Helper allows RECORDED to UNDER_REVIEW',
    'pass' => ($validTransition['ok'] ?? false) === true,
];

$correctionTransition = moghare360_executive_go_no_go_decision_validate_transition('ACCEPTED', 'ACTION_REQUIRED');
$results[] = [
    'name' => 'Helper allows ACCEPTED to ACTION_REQUIRED correction',
    'pass' => ($correctionTransition['ok'] ?? false) === true,
];

$reopenTransition = moghare360_executive_go_no_go_decision_validate_transition('CLOSED', 'UNDER_REVIEW');
$results[] = [
    'name' => 'Helper allows CLOSED to UNDER_REVIEW reopening',
    'pass' => ($reopenTransition['ok'] ?? false) === true,
];

$results[] = [
    'name' => 'Helper validates change_reason in workflow payload',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360_executive_go_no_go_decision_validate_workflow_payload')
        && str_contains($helperContent, 'change_reason'),
];

$emptyReason = moghare360_executive_go_no_go_decision_validate_workflow_payload([
    'decision_id' => '1',
    'new_decision_status' => 'UNDER_REVIEW',
    'change_reason' => '',
]);
$results[] = [
    'name' => 'Helper rejects empty change_reason',
    'pass' => ($emptyReason['ok'] ?? true) === false,
];

$results[] = [
    'name' => 'Helper validates decision statuses',
    'pass' => in_array('RECORDED', moghare360_executive_go_no_go_decision_allowed_statuses(), true)
        && in_array('ACTION_REQUIRED', moghare360_executive_go_no_go_decision_allowed_statuses(), true),
];

$results[] = [
    'name' => 'Helper uses prepared statements for workflow update',
    'pass' => $helperContent !== ''
        && preg_match('/UPDATE dbo\.erp_executive_soft_run_decisions SET[\s\S]*WHERE decision_id = \?/', $helperContent) === 1
        && preg_match('/INSERT INTO dbo\.erp_executive_soft_run_decision_history[\s\S]*VALUES \(\?, \?, \?, \?, \?, \?, \?\)/', $helperContent) === 1,
];

preg_match_all('/UPDATE\s+dbo\.([a-z0-9_]+)/i', $helperContent, $updateMatches);
$updateTables = array_unique(array_map('strtolower', $updateMatches[1] ?? []));
$results[] = [
    'name' => 'Helper updates only dbo.erp_executive_soft_run_decisions',
    'pass' => $updateTables === ['erp_executive_soft_run_decisions'],
];

$workflowFnStart = strpos($helperContent, 'function moghare360_executive_go_no_go_decision_update_workflow');
$workflowFnBody = $workflowFnStart !== false ? substr($helperContent, $workflowFnStart) : '';
$results[] = [
    'name' => 'Workflow update does not INSERT new decision records',
    'pass' => $workflowFnBody !== ''
        && !preg_match('/INSERT\s+INTO\s+dbo\.erp_executive_soft_run_decisions/i', $workflowFnBody),
];

$results[] = [
    'name' => 'Helper captures old status and type before workflow update',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'old_decision_status')
        && str_contains($helperContent, 'old_decision_type'),
];

$forbiddenWriteTables = [
    'erp_soft_run_findings',
    'erp_soft_run_finding_history',
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
    'name' => 'Helper does not write to findings/pilot/JobCard/delivery/evidence/authorization/customer/vehicle/payment tables',
    'pass' => $noForbiddenWrites,
];

$results[] = [
    'name' => 'Submit workflow page accepts POST only',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, "REQUEST_METHOD'] ?? '') !== 'POST'")
        && str_contains($submitContent, 'moghare360_executive_go_no_go_decision_update_workflow'),
];

$results[] = [
    'name' => 'Board links to workflow page',
    'pass' => $boardContent !== ''
        && str_contains($boardContent, 'erp-executive-go-no-go-decision-workflow.php?decision_id='),
];

$results[] = [
    'name' => 'Detail links to workflow page',
    'pass' => $detailContent !== ''
        && str_contains($detailContent, 'erp-executive-go-no-go-decision-workflow.php?decision_id='),
];

$results[] = [
    'name' => 'Board has no POST form',
    'pass' => $boardContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $boardContent),
];

$results[] = [
    'name' => 'Detail has no POST form',
    'pass' => $detailContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $detailContent),
];

$wave9cSqlFiles = glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_9c*') ?: [];
$results[] = [
    'name' => 'No SQL files created for WAVE 9C',
    'pass' => $wave9cSqlFiles === [],
];

$results[] = [
    'name' => 'WAVE 9B SQL unchanged',
    'pass' => $sql9bContent !== ''
        && !str_contains($sql9bContent, 'wave_9c')
        && !str_contains($sql9bContent, 'update_workflow'),
];

$results[] = [
    'name' => 'WAVE 9B create submit page unchanged',
    'pass' => $createSubmitContent !== ''
        && !str_contains($createSubmitContent, 'decision-workflow')
        && !str_contains($createSubmitContent, 'moghare360_executive_go_no_go_decision_update_workflow'),
];

$results[] = [
    'name' => 'No final delivery action exists',
    'pass' => $wave9cBundle !== ''
        && str_contains($workflowContent, 'not final delivery')
        && !preg_match('/vehicle_delivered/i', $wave9cBundle),
];

$results[] = [
    'name' => 'No delivery completion exists',
    'pass' => !preg_match('/delivery_completion_record/i', $wave9cBundle)
        && !preg_match('/final_delivery_record/i', $wave9cBundle),
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
    if (str_contains($content, 'decision-workflow') || str_contains($content, 'decision_update_workflow')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'WAVE 9A readiness helper unchanged',
    'pass' => $readinessHelperContent !== ''
        && !str_contains($readinessHelperContent, 'decision_update_workflow')
        && !str_contains($readinessHelperContent, 'decision-workflow'),
];

$wave678Helpers = [
    ['WAVE 6 final closure helper unchanged', $wave6HelperContent],
    ['WAVE 7 final closure helper unchanged', $wave7HelperContent],
    ['WAVE 8 final closure helper unchanged', $wave8HelperContent],
    ['WAVE 8 finding helper unchanged', $findingHelperContent],
];
foreach ($wave678Helpers as [$label, $content]) {
    $results[] = [
        'name' => $label,
        'pass' => $content !== ''
            && !str_contains($content, 'executive-go-no-go-decision-workflow')
            && !str_contains($content, 'moghare360_executive_go_no_go_decision_update_workflow'),
    ];
}

$results[] = [
    'name' => 'Workflow page includes read-only transition review table',
    'pass' => $workflowContent !== ''
        && str_contains($workflowContent, 'Read-only Decision Workflow Review')
        && str_contains($workflowContent, 'moghare360_executive_go_no_go_decision_allowed_transitions'),
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
    fwrite(STDERR, 'WAVE 9C EXECUTIVE GO NO GO DECISION WORKFLOW TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 9C EXECUTIVE GO NO GO DECISION WORKFLOW TEST PASSED' . PHP_EOL;
exit(0);
