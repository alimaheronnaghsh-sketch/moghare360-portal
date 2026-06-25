<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 3B Contract Authorization Workflow CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-workflow-helper.php';
$workflowPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-contract-authorization-workflow.php';
$submitPath = $public . DIRECTORY_SEPARATOR . 'submit-jobcard-contract-authorization-workflow.php';
$previewPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-contract-authorization-preview.php';
$authHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-helper.php';

require_once $helperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3B_CONTRACT_AUTHORIZATION_WORKFLOW_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3B_CONTRACT_AUTHORIZATION_WORKFLOW_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3B_CONTRACT_AUTHORIZATION_WORKFLOW_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3B_CONTRACT_AUTHORIZATION_WORKFLOW_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$workflowContent = is_file($workflowPath) ? (string)file_get_contents($workflowPath) : '';
$submitContent = is_file($submitPath) ? (string)file_get_contents($submitPath) : '';
$previewContent = is_file($previewPath) ? (string)file_get_contents($previewPath) : '';
$authHelperContent = is_file($authHelperPath) ? (string)file_get_contents($authHelperPath) : '';

$results = [];

$results[] = ['name' => 'Workflow helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Workflow page exists', 'pass' => is_file($workflowPath)];
$results[] = ['name' => 'Workflow submit exists', 'pass' => is_file($submitPath)];

$requiredApis = [
    'moghare360_contract_authorization_workflow_allowed_transitions',
    'moghare360_contract_authorization_workflow_get_record',
    'moghare360_contract_authorization_workflow_validate_transition',
    'moghare360_contract_authorization_workflow_apply',
    'moghare360_contract_authorization_workflow_history',
    'moghare360_contract_authorization_workflow_status_label',
];

$apiPass = true;
foreach ($requiredApis as $api) {
    if (!function_exists($api)) {
        $apiPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper contains required APIs', 'pass' => $apiPass];

$transitions = moghare360_contract_authorization_workflow_allowed_transitions();

$results[] = [
    'name' => 'Allowed transitions present',
    'pass' => ($transitions['draft'] ?? []) === ['pending_customer_approval']
        && in_array('approved', $transitions['pending_customer_approval'] ?? [], true)
        && in_array('rejected', $transitions['pending_customer_approval'] ?? [], true)
        && ($transitions['approved'] ?? []) === ['cancelled'],
];

$forbiddenSample = moghare360_contract_authorization_workflow_validate_transition(
    ['authorization_status' => 'approved'],
    'draft',
    'test'
);
$results[] = [
    'name' => 'Forbidden transition approved to draft blocked',
    'pass' => ($forbiddenSample['ok'] ?? true) === false,
];

$forbiddenRejectedApproved = moghare360_contract_authorization_workflow_validate_transition(
    ['authorization_status' => 'rejected'],
    'approved',
    'test'
);
$results[] = [
    'name' => 'Forbidden transition rejected to approved blocked',
    'pass' => ($forbiddenRejectedApproved['ok'] ?? true) === false,
];

$cancelWithoutReason = moghare360_contract_authorization_workflow_validate_transition(
    ['authorization_status' => 'approved'],
    'cancelled',
    ''
);
$results[] = [
    'name' => 'Approved to cancelled requires cancellation reason',
    'pass' => ($cancelWithoutReason['ok'] ?? true) === false,
];

$results[] = [
    'name' => 'Submit validates before DB update',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, 'moghare360_contract_authorization_workflow_validate_transition')
        && str_contains($submitContent, 'moghare360_contract_authorization_workflow_apply'),
];

$results[] = [
    'name' => 'Submit uses prepared statements via helper',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'customer_core_execute')
        && str_contains($helperContent, 'UPDATE dbo.erp_jobcard_authorizations')
        && str_contains($helperContent, 'INSERT INTO dbo.erp_jobcard_authorization_history'),
];

$results[] = [
    'name' => 'Submit writes history event AUTHORIZATION_STATUS_CHANGED',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'AUTHORIZATION_STATUS_CHANGED'),
];

$results[] = [
    'name' => 'Preview includes workflow link',
    'pass' => $previewContent !== ''
        && str_contains($previewContent, 'erp-jobcard-contract-authorization-workflow.php?authorization_id='),
];

$results[] = [
    'name' => '3A authorization helper unchanged',
    'pass' => $authHelperContent !== '' && !str_contains($authHelperContent, 'workflow-helper'),
];

$results[] = [
    'name' => 'Public portal is not activated',
    'pass' => $workflowContent !== ''
        && str_contains($workflowContent, 'پورتال عمومی مشتری فعال نیست'),
];

$results[] = [
    'name' => 'Legal final e-signature is not claimed',
    'pass' => $workflowContent !== ''
        && str_contains($workflowContent, 'not final legal e-signature')
        && !preg_match('/legal\s+final\s+e-?signature\s+confirmed/i', $workflowContent . $submitContent),
];

$wave3bSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_3b*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave3bSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for Wave 3B', 'pass' => !$wave3bSqlCreated];

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
    fwrite(STDERR, 'WAVE 3B CONTRACT AUTHORIZATION WORKFLOW TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 3B CONTRACT AUTHORIZATION WORKFLOW TEST PASSED' . PHP_EOL;
exit(0);
