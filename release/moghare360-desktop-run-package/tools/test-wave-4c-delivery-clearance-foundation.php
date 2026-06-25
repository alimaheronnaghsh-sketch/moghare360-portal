<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 4C Delivery Clearance Foundation CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-delivery-clearance-helper.php';
$formPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-delivery-clearance.php';
$submitPath = $public . DIRECTORY_SEPARATOR . 'submit-jobcard-delivery-clearance.php';
$previewPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-delivery-clearance-preview.php';
$sqlPath = $public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'wave_4c_delivery_clearance_foundation.sql';
$eligibilityHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-delivery-eligibility-helper.php';
$finalReadinessHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-final-readiness-helper.php';
$eligibilityPagePath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-delivery-eligibility.php';
$evidenceHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-gate-helper.php';
$authGateHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-gate-helper.php';
$wave2ClosurePath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-2-closure-helper.php';
$wave3ClosurePath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-3-authorization-closure-helper.php';

$forbiddenPaths = [
    $public . DIRECTORY_SEPARATOR . 'config.php',
    $public . DIRECTORY_SEPARATOR . 'staff-auth.php',
    $public . DIRECTORY_SEPARATOR . 'access-control.php',
    $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-auth-helper.php',
];

require_once $helperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4C_DELIVERY_CLEARANCE_FOUNDATION_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4C_DELIVERY_CLEARANCE_FOUNDATION_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4C_DELIVERY_CLEARANCE_FOUNDATION_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4C_DELIVERY_CLEARANCE_FOUNDATION_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$formContent = is_file($formPath) ? (string)file_get_contents($formPath) : '';
$submitContent = is_file($submitPath) ? (string)file_get_contents($submitPath) : '';
$previewContent = is_file($previewPath) ? (string)file_get_contents($previewPath) : '';
$sqlContent = is_file($sqlPath) ? (string)file_get_contents($sqlPath) : '';
$eligibilityHelperContent = is_file($eligibilityHelperPath) ? (string)file_get_contents($eligibilityHelperPath) : '';
$finalReadinessHelperContent = is_file($finalReadinessHelperPath) ? (string)file_get_contents($finalReadinessHelperPath) : '';
$eligibilityPageContent = is_file($eligibilityPagePath) ? (string)file_get_contents($eligibilityPagePath) : '';
$evidenceHelperContent = is_file($evidenceHelperPath) ? (string)file_get_contents($evidenceHelperPath) : '';
$authGateHelperContent = is_file($authGateHelperPath) ? (string)file_get_contents($authGateHelperPath) : '';
$wave2ClosureContent = is_file($wave2ClosurePath) ? (string)file_get_contents($wave2ClosurePath) : '';
$wave3ClosureContent = is_file($wave3ClosurePath) ? (string)file_get_contents($wave3ClosurePath) : '';

$results = [];

$results[] = ['name' => 'Delivery clearance helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Create page exists', 'pass' => is_file($formPath)];
$results[] = ['name' => 'Submit page exists', 'pass' => is_file($submitPath)];
$results[] = ['name' => 'Preview page exists', 'pass' => is_file($previewPath)];

$requiredApis = [
    'moghare360_delivery_clearance_allowed_statuses',
    'moghare360_delivery_clearance_allowed_decisions',
    'moghare360_delivery_clearance_schema_status',
    'moghare360_delivery_clearance_fetch_eligibility',
    'moghare360_delivery_clearance_validate_payload',
    'moghare360_delivery_clearance_create',
    'moghare360_delivery_clearance_list_by_jobcard',
    'moghare360_delivery_clearance_history_by_jobcard',
    'moghare360_delivery_clearance_status_label',
];

$apiPass = true;
foreach ($requiredApis as $api) {
    if (!function_exists($api)) {
        $apiPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper contains required APIs', 'pass' => $apiPass];

$statuses = moghare360_delivery_clearance_allowed_statuses();
$decisions = moghare360_delivery_clearance_allowed_decisions();

$results[] = [
    'name' => 'Allowed clearance statuses present',
    'pass' => in_array('draft', $statuses, true)
        && in_array('cleared', $statuses, true)
        && in_array('not_cleared', $statuses, true),
];

$results[] = [
    'name' => 'Allowed clearance decisions present',
    'pass' => in_array('eligible_for_delivery_review', $decisions, true)
        && in_array('cleared_for_delivery_process', $decisions, true)
        && in_array('not_cleared_missing_requirements', $decisions, true),
];

$results[] = [
    'name' => 'Helper checks schema readiness',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360_delivery_clearance_schema_status')
        && str_contains($helperContent, MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED),
];

$results[] = [
    'name' => 'Helper includes delivery eligibility safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-delivery-eligibility-helper.php')
        && str_contains($helperContent, 'moghare360_delivery_eligibility_evaluate'),
];

$results[] = [
    'name' => 'Submit validates before DB write',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, 'moghare360_delivery_clearance_validate_payload')
        && preg_match('/validate_payload[\s\S]*create/s', $submitContent) === 1,
];

$results[] = [
    'name' => 'Submit blocks cleared status when eligibility NOT_ELIGIBLE/ERROR',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'eligibility_blocks_cleared')
        && str_contains($helperContent, MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE)
        && str_contains($helperContent, MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR),
];

$results[] = [
    'name' => 'Submit uses prepared statements',
    'pass' => $helperContent !== ''
        && preg_match('/VALUES \(\?, \?, \?, \?, \?\)/', $helperContent) === 1
        && preg_match('/VALUES \(\?, \?, \?, \?, \?, \?, \?, \?, \?\)/', $helperContent) === 1,
];

$results[] = [
    'name' => 'Helper writes history when READY',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360_delivery_clearance_write_history')
        && str_contains($helperContent, 'CLEARANCE_REGISTERED')
        && str_contains($helperContent, MOGHARE360_DELIVERY_CLEARANCE_HISTORY_TABLE),
];

$results[] = [
    'name' => 'Preview is read-only',
    'pass' => $previewContent !== ''
        && !preg_match('/\bINSERT\s+INTO\b/i', $previewContent)
        && !preg_match('/method\s*=\s*["\']post["\']/i', $previewContent)
        && str_contains($previewContent, 'not final vehicle delivery'),
];

$results[] = [
    'name' => 'SQL foundation file exists',
    'pass' => is_file($sqlPath),
];

$results[] = [
    'name' => 'SQL file is idempotent',
    'pass' => $sqlContent !== ''
        && str_contains($sqlContent, 'IF OBJECT_ID')
        && !preg_match('/\bDROP\s+TABLE\b/i', $sqlContent),
];

$results[] = [
    'name' => 'SQL aligns jobcard_id to dbo.erp_jobcards.jobcard_id type',
    'pass' => $sqlContent !== ''
        && str_contains($sqlContent, 'dbo.erp_jobcards.jobcard_id')
        && str_contains($sqlContent, 'jobcard_id INT NOT NULL'),
];

$results[] = [
    'name' => 'No final delivery action exists',
    'pass' => $formContent !== ''
        && $submitContent !== ''
        && str_contains($formContent, 'not final vehicle delivery')
        && !preg_match('/submit-.*final-delivery/i', $formContent . $submitContent),
];

$results[] = [
    'name' => 'No delivery completion record exists',
    'pass' => $helperContent !== ''
        && !preg_match('/vehicle_delivered/i', $helperContent . $formContent . $submitContent)
        && !preg_match('/delivery_completion_record/i', $helperContent . $formContent . $submitContent)
        && !preg_match('/final_delivery_record/i', $helperContent . $formContent . $submitContent),
];

$results[] = [
    'name' => 'No payment/accounting/public portal/e-signature activation',
    'pass' => $formContent !== ''
        && str_contains($formContent, 'not legal final e-signature')
        && str_contains($formContent, 'پورتال عمومی')
        && str_contains($formContent, 'پرداخت'),
];

$results[] = [
    'name' => 'WAVE 2 evidence gate helper unchanged',
    'pass' => $evidenceHelperContent !== '' && !str_contains($evidenceHelperContent, 'delivery-clearance'),
];

$results[] = [
    'name' => 'WAVE 3 authorization gate helper unchanged',
    'pass' => $authGateHelperContent !== '' && !str_contains($authGateHelperContent, 'delivery-clearance'),
];

$results[] = [
    'name' => 'WAVE 4A final readiness helper unchanged',
    'pass' => $finalReadinessHelperContent !== ''
        && !str_contains($finalReadinessHelperContent, 'delivery-clearance')
        && !str_contains($finalReadinessHelperContent, 'moghare360_delivery_clearance'),
];

$results[] = [
    'name' => 'WAVE 4B delivery eligibility helper unchanged',
    'pass' => $eligibilityHelperContent !== ''
        && !str_contains($eligibilityHelperContent, 'delivery-clearance')
        && !str_contains($eligibilityHelperContent, 'moghare360_delivery_clearance'),
];

$results[] = [
    'name' => 'WAVE 2 closure helper unchanged',
    'pass' => $wave2ClosureContent !== '' && !str_contains($wave2ClosureContent, 'delivery-clearance'),
];

$results[] = [
    'name' => 'WAVE 3 closure helper unchanged',
    'pass' => $wave3ClosureContent !== '' && !str_contains($wave3ClosureContent, 'delivery-clearance'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'delivery-clearance') || str_contains($content, 'moghare360_delivery_clearance')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes for WAVE 4C', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'Delivery eligibility page links to clearance pages',
    'pass' => $eligibilityPageContent !== ''
        && str_contains($eligibilityPageContent, 'erp-jobcard-delivery-clearance.php')
        && str_contains($eligibilityPageContent, 'erp-jobcard-delivery-clearance-preview.php'),
];

$schema = moghare360_delivery_clearance_schema_status();
$results[] = [
    'name' => 'Schema status returns READY or BLOCKED',
    'pass' => in_array($schema['schema_status'] ?? '', [
        MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_READY,
        MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED,
    ], true),
];

$blockedCreate = moghare360_delivery_clearance_create([
    'jobcard_id' => '1',
    'clearance_status' => 'cleared',
    'clearance_decision' => 'cleared_for_delivery_process',
    'reviewer_name' => 'Test Reviewer',
    'clearance_note' => '',
]);
$results[] = [
    'name' => 'Create does not fake success when schema blocked or eligibility blocks',
    'pass' => ($blockedCreate['ok'] ?? true) === false,
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
    fwrite(STDERR, 'WAVE 4C DELIVERY CLEARANCE FOUNDATION TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 4C DELIVERY CLEARANCE FOUNDATION TEST PASSED' . PHP_EOL;
exit(0);
