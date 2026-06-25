<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 3C Contract Authorization Gate CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-gate-helper.php';
$gatePath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-authorization-gate.php';
$previewPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-contract-authorization-preview.php';
$authHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-helper.php';
$wfHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-workflow-helper.php';

require_once $helperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3C_CONTRACT_AUTHORIZATION_GATE_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3C_CONTRACT_AUTHORIZATION_GATE_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3C_CONTRACT_AUTHORIZATION_GATE_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3C_CONTRACT_AUTHORIZATION_GATE_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$gateContent = is_file($gatePath) ? (string)file_get_contents($gatePath) : '';
$previewContent = is_file($previewPath) ? (string)file_get_contents($previewPath) : '';
$authHelperContent = is_file($authHelperPath) ? (string)file_get_contents($authHelperPath) : '';
$wfHelperContent = is_file($wfHelperPath) ? (string)file_get_contents($wfHelperPath) : '';

$results = [];

$results[] = ['name' => 'Gate helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Gate page exists', 'pass' => is_file($gatePath)];

$requiredApis = [
    'moghare360_contract_authorization_gate_required_rules',
    'moghare360_contract_authorization_gate_fetch_records',
    'moghare360_contract_authorization_gate_fetch_history_count',
    'moghare360_contract_authorization_gate_evaluate',
    'moghare360_contract_authorization_gate_status_label',
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
    'name' => 'Helper reads from dbo.erp_jobcard_authorizations',
    'pass' => $helperContent !== '' && str_contains($helperContent, 'FROM dbo.erp_jobcard_authorizations'),
];

$results[] = [
    'name' => 'Helper reads from dbo.erp_jobcard_authorization_history',
    'pass' => $helperContent !== '' && str_contains($helperContent, 'FROM dbo.erp_jobcard_authorization_history'),
];

$results[] = [
    'name' => 'Helper does not write INSERT/UPDATE/DELETE',
    'pass' => $helperContent !== ''
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent)
        && !preg_match('/\bUPDATE\s+dbo\./i', $helperContent)
        && !preg_match('/\bDELETE\s+FROM\b/i', $helperContent),
];

$results[] = [
    'name' => 'Gate page accepts jobcard_id',
    'pass' => $gateContent !== ''
        && str_contains($gateContent, 'jobcard_id')
        && str_contains($gateContent, 'moghare360_contract_authorization_gate_review'),
];

$linkChecks = [
    'erp-jobcard-contract-authorization.php' => 'contract authorization create',
    'erp-jobcard-contract-authorization-preview.php' => 'authorization preview',
    'erp-jobcard-contract-authorization-workflow.php?authorization_id=' => 'workflow',
    'erp-jobcard-evidence-review.php?jobcard_id=' => 'evidence review',
];

foreach ($linkChecks as $href => $label) {
    $results[] = [
        'name' => 'Gate page links to ' . $label,
        'pass' => $gateContent !== '' && str_contains($gateContent, $href),
    ];
}

$results[] = [
    'name' => 'Preview includes gate link',
    'pass' => $previewContent !== ''
        && str_contains($previewContent, 'erp-jobcard-authorization-gate.php?jobcard_id='),
];

$rules = moghare360_contract_authorization_gate_required_rules();
$ruleKeys = array_map(static fn(array $r): string => (string)$r['key'], $rules);

$results[] = [
    'name' => 'Required authorization rules present',
    'pass' => in_array('acceptance_contract_approved', $ruleKeys, true)
        && in_array('repair_permission_approved', $ruleKeys, true)
        && in_array('diagnostic_authorization_or_evidence', $ruleKeys, true)
        && in_array('delivery_approval_approved', $ruleKeys, true),
];

$emptyEval = moghare360_contract_authorization_gate_evaluate(1, []);
$results[] = [
    'name' => 'Gate evaluate returns EMPTY for no records',
    'pass' => ($emptyEval['status'] ?? '') === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_EMPTY,
];

$results[] = [
    'name' => '3A authorization helper unchanged',
    'pass' => $authHelperContent !== '' && !str_contains($authHelperContent, 'authorization-gate'),
];

$results[] = [
    'name' => '3B workflow helper unchanged',
    'pass' => $wfHelperContent !== '' && !str_contains($wfHelperContent, 'authorization-gate'),
];

$results[] = [
    'name' => 'Public portal is not activated',
    'pass' => $gateContent !== '' && str_contains($gateContent, 'پورتال عمومی'),
];

$results[] = [
    'name' => 'Legal final e-signature is not claimed',
    'pass' => $gateContent !== ''
        && str_contains($gateContent, 'not final legal e-signature')
        && !preg_match('/legal\s+final\s+e-?signature\s+confirmed/i', $gateContent),
];

$wave3cSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_3c*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave3cSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for Wave 3C', 'pass' => !$wave3cSqlCreated];

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
    fwrite(STDERR, 'WAVE 3C CONTRACT AUTHORIZATION GATE TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 3C CONTRACT AUTHORIZATION GATE TEST PASSED' . PHP_EOL;
exit(0);
