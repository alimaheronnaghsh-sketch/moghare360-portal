<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 4A JobCard Final Readiness CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-final-readiness-helper.php';
$pagePath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-final-readiness.php';
$evidenceHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-gate-helper.php';
$authGateHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-gate-helper.php';
$wave2ClosurePath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-2-closure-helper.php';
$wave3ClosurePath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-3-authorization-closure-helper.php';

require_once $helperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4A_JOBCARD_FINAL_READINESS_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4A_JOBCARD_FINAL_READINESS_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4A_JOBCARD_FINAL_READINESS_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4A_JOBCARD_FINAL_READINESS_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$pageContent = is_file($pagePath) ? (string)file_get_contents($pagePath) : '';
$evidenceHelperContent = is_file($evidenceHelperPath) ? (string)file_get_contents($evidenceHelperPath) : '';
$authGateHelperContent = is_file($authGateHelperPath) ? (string)file_get_contents($authGateHelperPath) : '';
$wave2ClosureContent = is_file($wave2ClosurePath) ? (string)file_get_contents($wave2ClosurePath) : '';
$wave3ClosureContent = is_file($wave3ClosurePath) ? (string)file_get_contents($wave3ClosurePath) : '';

$results = [];

$results[] = ['name' => 'Final readiness helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Final readiness page exists', 'pass' => is_file($pagePath)];

$requiredApis = [
    'moghare360_jobcard_final_readiness_fetch_jobcard',
    'moghare360_jobcard_final_readiness_fetch_evidence_gate',
    'moghare360_jobcard_final_readiness_fetch_authorization_gate',
    'moghare360_jobcard_final_readiness_evaluate',
    'moghare360_jobcard_final_readiness_status_label',
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
    'name' => 'Helper references WAVE 2 evidence gate safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-jobcard-evidence-gate-helper.php')
        && str_contains($helperContent, 'moghare360_jobcard_evidence_review'),
];

$results[] = [
    'name' => 'Helper references WAVE 3 authorization gate safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-contract-authorization-gate-helper.php')
        && str_contains($helperContent, 'moghare360_contract_authorization_gate_review'),
];

$results[] = [
    'name' => 'Helper does not write INSERT/UPDATE/DELETE',
    'pass' => $helperContent !== ''
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent)
        && !preg_match('/\bUPDATE\s+dbo\./i', $helperContent)
        && !preg_match('/\bDELETE\s+FROM\b/i', $helperContent),
];

$results[] = [
    'name' => 'Page accepts jobcard_id',
    'pass' => $pageContent !== ''
        && str_contains($pageContent, 'jobcard_id')
        && str_contains($pageContent, 'moghare360_jobcard_final_readiness_evaluate'),
];

$linkChecks = [
    'erp-jobcard-evidence-review.php' => 'evidence review',
    'erp-jobcard-authorization-gate.php' => 'authorization gate',
    'erp-jobcard-contract-authorization-preview.php' => 'authorization preview',
    'erp-media-evidence-closure-dashboard.php' => 'WAVE 2 closure dashboard',
    'erp-authorization-closure-dashboard.php' => 'WAVE 3 closure dashboard',
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
    'name' => 'Page has no final delivery submit/action',
    'pass' => $pageContent !== ''
        && !preg_match('/submit-.*delivery/i', $pageContent)
        && str_contains($pageContent, 'No delivery action on this page'),
];

$results[] = [
    'name' => 'WAVE 2 evidence gate helper unchanged',
    'pass' => $evidenceHelperContent !== '' && !str_contains($evidenceHelperContent, 'final-readiness'),
];

$results[] = [
    'name' => 'WAVE 3 authorization gate helper unchanged',
    'pass' => $authGateHelperContent !== '' && !str_contains($authGateHelperContent, 'final-readiness'),
];

$results[] = [
    'name' => 'WAVE 2 closure helper unchanged',
    'pass' => $wave2ClosureContent !== '' && !str_contains($wave2ClosureContent, 'final-readiness'),
];

$results[] = [
    'name' => 'WAVE 3 closure helper unchanged',
    'pass' => $wave3ClosureContent !== '' && !str_contains($wave3ClosureContent, 'final-readiness'),
];

$results[] = [
    'name' => 'Public portal is not activated',
    'pass' => $pageContent !== '' && str_contains($pageContent, 'پورتال عمومی'),
];

$results[] = [
    'name' => 'Legal final e-signature is not claimed',
    'pass' => $pageContent !== ''
        && str_contains($pageContent, 'not legal e-signature')
        && !preg_match('/legal\s+final\s+e-?signature\s+confirmed/i', $pageContent),
];

$wave4aSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_4a*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave4aSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for Wave 4A', 'pass' => !$wave4aSqlCreated];

$eval = moghare360_jobcard_final_readiness_evaluate(1);
$results[] = [
    'name' => 'Evaluate returns valid final readiness status',
    'pass' => in_array($eval['status'] ?? '', [
        MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_READY,
        MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_PARTIAL,
        MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_BLOCKED,
        MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_EMPTY,
        MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_ERROR,
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
    fwrite(STDERR, 'WAVE 4A JOBCARD FINAL READINESS TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 4A JOBCARD FINAL READINESS TEST PASSED' . PHP_EOL;
exit(0);
