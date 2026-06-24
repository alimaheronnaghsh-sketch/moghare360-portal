<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 5A Unified JobCard Command Center CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-unified-jobcard-command-helper.php';
$pagePath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-command-center.php';
$evidenceHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-gate-helper.php';
$timelineHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-timeline-helper.php';
$authGateHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-gate-helper.php';
$finalReadinessHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-final-readiness-helper.php';
$eligibilityHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-delivery-eligibility-helper.php';
$clearanceHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-delivery-clearance-helper.php';
$wave4ClosurePath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-4-delivery-control-closure-helper.php';
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
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5A_UNIFIED_JOBCARD_COMMAND_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5A_UNIFIED_JOBCARD_COMMAND_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5A_UNIFIED_JOBCARD_COMMAND_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5A_UNIFIED_JOBCARD_COMMAND_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$pageContent = is_file($pagePath) ? (string)file_get_contents($pagePath) : '';
$evidenceHelperContent = is_file($evidenceHelperPath) ? (string)file_get_contents($evidenceHelperPath) : '';
$timelineHelperContent = is_file($timelineHelperPath) ? (string)file_get_contents($timelineHelperPath) : '';
$authGateHelperContent = is_file($authGateHelperPath) ? (string)file_get_contents($authGateHelperPath) : '';
$finalReadinessHelperContent = is_file($finalReadinessHelperPath) ? (string)file_get_contents($finalReadinessHelperPath) : '';
$eligibilityHelperContent = is_file($eligibilityHelperPath) ? (string)file_get_contents($eligibilityHelperPath) : '';
$clearanceHelperContent = is_file($clearanceHelperPath) ? (string)file_get_contents($clearanceHelperPath) : '';
$wave4ClosureContent = is_file($wave4ClosurePath) ? (string)file_get_contents($wave4ClosurePath) : '';
$wave2ClosureContent = is_file($wave2ClosurePath) ? (string)file_get_contents($wave2ClosurePath) : '';
$wave3ClosureContent = is_file($wave3ClosurePath) ? (string)file_get_contents($wave3ClosurePath) : '';

$results = [];

$results[] = ['name' => 'Unified command helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Command center page exists', 'pass' => is_file($pagePath)];

$requiredApis = [
    'moghare360_unified_jobcard_command_fetch_jobcard',
    'moghare360_unified_jobcard_command_fetch_evidence',
    'moghare360_unified_jobcard_command_fetch_authorization',
    'moghare360_unified_jobcard_command_fetch_final_readiness',
    'moghare360_unified_jobcard_command_fetch_delivery_eligibility',
    'moghare360_unified_jobcard_command_fetch_delivery_clearance',
    'moghare360_unified_jobcard_command_evaluate',
    'moghare360_unified_jobcard_command_status_label',
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
    'name' => 'Helper references WAVE 2 evidence safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-jobcard-evidence-gate-helper.php')
        && str_contains($helperContent, 'moghare360_jobcard_evidence_review')
        && str_contains($helperContent, 'moghare360-jobcard-evidence-timeline-helper.php'),
];

$results[] = [
    'name' => 'Helper references WAVE 3 authorization safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-contract-authorization-gate-helper.php')
        && str_contains($helperContent, 'moghare360_contract_authorization_gate_review'),
];

$results[] = [
    'name' => 'Helper references WAVE 4 final readiness/eligibility/clearance safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-jobcard-final-readiness-helper.php')
        && str_contains($helperContent, 'moghare360-delivery-eligibility-helper.php')
        && str_contains($helperContent, 'moghare360-delivery-clearance-helper.php')
        && str_contains($helperContent, 'moghare360_jobcard_final_readiness_evaluate')
        && str_contains($helperContent, 'moghare360_delivery_eligibility_evaluate')
        && str_contains($helperContent, 'moghare360_delivery_clearance_list_by_jobcard'),
];

$results[] = [
    'name' => 'Helper does not write INSERT/UPDATE/DELETE',
    'pass' => $helperContent !== ''
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent)
        && !preg_match('/\bUPDATE\s+dbo\./i', $helperContent)
        && !preg_match('/\bDELETE\s+FROM\b/i', $helperContent),
];

$results[] = [
    'name' => 'Helper references dbo.erp_jobcards for JobCard fetch',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'FROM dbo.erp_jobcards')
        && str_contains($helperContent, 'jobcard_id'),
];

$results[] = [
    'name' => 'Helper uses jobcard_number not legacy jobcard_code',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'jobcard_number')
        && !preg_match('/\bjobcard_code\b/', $helperContent),
];

$results[] = [
    'name' => 'Helper does not require is_active/is_deleted/tenant/company fields',
    'pass' => $helperContent !== ''
        && !preg_match('/\bis_active\b/i', $helperContent)
        && !preg_match('/\bis_deleted\b/i', $helperContent)
        && !preg_match('/\btenant_id\b/i', $helperContent)
        && !preg_match('/\bcompany_id\b/i', $helperContent),
];

$results[] = [
    'name' => 'Page accepts jobcard_id',
    'pass' => $pageContent !== ''
        && str_contains($pageContent, 'jobcard_id')
        && str_contains($pageContent, 'moghare360_unified_jobcard_command_evaluate'),
];

$linkChecks = [
    'erp-jobcard-final-readiness.php' => 'final readiness',
    'erp-jobcard-delivery-eligibility.php' => 'delivery eligibility',
    'erp-jobcard-delivery-clearance.php' => 'clearance create',
    'erp-jobcard-delivery-clearance-preview.php' => 'clearance preview',
    'erp-jobcard-evidence-review.php' => 'evidence review',
    'erp-jobcard-authorization-gate.php' => 'authorization gate',
    'erp-jobcard-contract-authorization-preview.php' => 'authorization preview',
    'erp-media-evidence-closure-dashboard.php' => 'WAVE 2 closure',
    'erp-authorization-closure-dashboard.php' => 'WAVE 3 closure',
    'erp-delivery-control-closure-dashboard.php' => 'WAVE 4 closure',
];

foreach ($linkChecks as $href => $label) {
    $results[] = [
        'name' => 'Page links to ' . $label,
        'pass' => $pageContent !== '' && str_contains($pageContent, $href),
    ];
}

$results[] = [
    'name' => 'Page has no file input',
    'pass' => $pageContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $pageContent),
];

$results[] = [
    'name' => 'Page has no POST form',
    'pass' => $pageContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $pageContent),
];

$results[] = [
    'name' => 'Page has no final delivery submit/action',
    'pass' => $pageContent !== ''
        && !preg_match('/submit-.*delivery/i', $pageContent)
        && str_contains($pageContent, 'not final vehicle delivery'),
];

$results[] = [
    'name' => 'Page does not create delivery completion',
    'pass' => $pageContent !== ''
        && str_contains($pageContent, 'رکورد تکمیل تحویل ایجاد نمی‌شود')
        && !preg_match('/delivery_completion/i', $pageContent),
];

$wave5aSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_5a*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave5aSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for WAVE 5A', 'pass' => !$wave5aSqlCreated];

$results[] = [
    'name' => 'WAVE 2 evidence gate helper unchanged',
    'pass' => $evidenceHelperContent !== '' && !str_contains($evidenceHelperContent, 'unified-jobcard-command'),
];

$results[] = [
    'name' => 'WAVE 2 timeline helper unchanged',
    'pass' => $timelineHelperContent !== '' && !str_contains($timelineHelperContent, 'unified-jobcard-command'),
];

$results[] = [
    'name' => 'WAVE 3 authorization gate helper unchanged',
    'pass' => $authGateHelperContent !== '' && !str_contains($authGateHelperContent, 'unified-jobcard-command'),
];

$results[] = [
    'name' => 'WAVE 4A final readiness helper unchanged',
    'pass' => $finalReadinessHelperContent !== ''
        && !str_contains($finalReadinessHelperContent, 'unified-jobcard-command')
        && !str_contains($finalReadinessHelperContent, 'moghare360_unified_jobcard_command'),
];

$results[] = [
    'name' => 'WAVE 4B delivery eligibility helper unchanged',
    'pass' => $eligibilityHelperContent !== ''
        && !str_contains($eligibilityHelperContent, 'unified-jobcard-command')
        && !str_contains($eligibilityHelperContent, 'moghare360_unified_jobcard_command'),
];

$results[] = [
    'name' => 'WAVE 4C delivery clearance helper unchanged',
    'pass' => $clearanceHelperContent !== ''
        && !str_contains($clearanceHelperContent, 'unified-jobcard-command')
        && !str_contains($clearanceHelperContent, 'moghare360_unified_jobcard_command'),
];

$results[] = [
    'name' => 'WAVE 4D closure helper unchanged',
    'pass' => $wave4ClosureContent !== '' && !str_contains($wave4ClosureContent, 'unified-jobcard-command'),
];

$results[] = [
    'name' => 'WAVE 2 closure helper unchanged',
    'pass' => $wave2ClosureContent !== '' && !str_contains($wave2ClosureContent, 'unified-jobcard-command'),
];

$results[] = [
    'name' => 'WAVE 3 closure helper unchanged',
    'pass' => $wave3ClosureContent !== '' && !str_contains($wave3ClosureContent, 'unified-jobcard-command'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'unified-jobcard-command') || str_contains($content, 'moghare360_unified_jobcard_command')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes for WAVE 5A', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'Public portal is not activated',
    'pass' => $pageContent !== '' && str_contains($pageContent, 'پورتال عمومی'),
];

$results[] = [
    'name' => 'Payment/accounting is not activated',
    'pass' => $pageContent !== ''
        && str_contains($pageContent, 'پرداخت')
        && !preg_match('/payment\s+gateway\s+active/i', $pageContent),
];

$results[] = [
    'name' => 'Legal final e-signature is not claimed',
    'pass' => $pageContent !== ''
        && str_contains($pageContent, 'not legal final e-signature')
        && !preg_match('/legal\s+final\s+e-?signature\s+confirmed/i', $pageContent),
];

$eval = moghare360_unified_jobcard_command_evaluate(1);
$jobcardFetch = moghare360_unified_jobcard_command_fetch_jobcard(1);

$results[] = [
    'name' => 'Evaluate returns valid unified operational status',
    'pass' => in_array($eval['status'] ?? '', [
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_OPERATION_READY,
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ACTION_REQUIRED,
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_BLOCKED,
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_EMPTY,
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ERROR,
    ], true),
];

$results[] = [
    'name' => 'Existing JobCard 1 fetch returns ok when DB row exists',
    'pass' => ($jobcardFetch['ok'] ?? false) === true
        && (string)($jobcardFetch['jobcard']['jobcard_id'] ?? '') === '1',
];

$results[] = [
    'name' => 'Existing JobCard 1 unified status is not ERROR when fetch ok',
    'pass' => ($jobcardFetch['ok'] ?? false) === true
        && ($eval['status'] ?? '') !== MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ERROR,
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
    fwrite(STDERR, 'WAVE 5A UNIFIED JOBCARD COMMAND CENTER TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 5A UNIFIED JOBCARD COMMAND CENTER TEST PASSED' . PHP_EOL;
exit(0);
