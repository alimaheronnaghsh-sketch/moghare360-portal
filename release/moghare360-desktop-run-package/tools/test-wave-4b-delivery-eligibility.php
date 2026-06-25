<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 4B Delivery Eligibility CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-delivery-eligibility-helper.php';
$pagePath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-delivery-eligibility.php';
$finalReadinessHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-final-readiness-helper.php';
$finalReadinessPagePath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-final-readiness.php';
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
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4B_DELIVERY_ELIGIBILITY_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4B_DELIVERY_ELIGIBILITY_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4B_DELIVERY_ELIGIBILITY_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4B_DELIVERY_ELIGIBILITY_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$pageContent = is_file($pagePath) ? (string)file_get_contents($pagePath) : '';
$finalReadinessHelperContent = is_file($finalReadinessHelperPath) ? (string)file_get_contents($finalReadinessHelperPath) : '';
$finalReadinessPageContent = is_file($finalReadinessPagePath) ? (string)file_get_contents($finalReadinessPagePath) : '';
$evidenceHelperContent = is_file($evidenceHelperPath) ? (string)file_get_contents($evidenceHelperPath) : '';
$authGateHelperContent = is_file($authGateHelperPath) ? (string)file_get_contents($authGateHelperPath) : '';
$wave2ClosureContent = is_file($wave2ClosurePath) ? (string)file_get_contents($wave2ClosurePath) : '';
$wave3ClosureContent = is_file($wave3ClosurePath) ? (string)file_get_contents($wave3ClosurePath) : '';

$results = [];

$results[] = ['name' => 'Delivery eligibility helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Delivery eligibility page exists', 'pass' => is_file($pagePath)];

$requiredApis = [
    'moghare360_delivery_eligibility_fetch_final_readiness',
    'moghare360_delivery_eligibility_rules',
    'moghare360_delivery_eligibility_evaluate',
    'moghare360_delivery_eligibility_status_label',
    'moghare360_delivery_eligibility_recommended_action',
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
    'name' => 'Helper references WAVE 4A final readiness safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-jobcard-final-readiness-helper.php')
        && str_contains($helperContent, 'moghare360_jobcard_final_readiness_evaluate'),
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
        && str_contains($pageContent, 'moghare360_delivery_eligibility_evaluate'),
];

$linkChecks = [
    'erp-jobcard-final-readiness.php' => 'final readiness',
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
    'name' => 'Page has no POST form',
    'pass' => $pageContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $pageContent),
];

$results[] = [
    'name' => 'Page has no final delivery submit/action',
    'pass' => $pageContent !== ''
        && !preg_match('/submit-.*delivery/i', $pageContent)
        && str_contains($pageContent, 'No delivery action on this page'),
];

$results[] = [
    'name' => 'Page does not create delivery record',
    'pass' => $pageContent !== ''
        && !preg_match('/\bINSERT\s+INTO\b/i', $pageContent)
        && !preg_match('/delivery_record/i', $pageContent)
        && !preg_match('/createDeliveryRecord/i', $pageContent),
];

$wave4bSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_4b*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave4bSqlCreated = true;
        break;
    }
}
foreach (glob($root . DIRECTORY_SEPARATOR . '**' . DIRECTORY_SEPARATOR . '*wave_4b*.sql') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave4bSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for WAVE 4B', 'pass' => !$wave4bSqlCreated];

$results[] = [
    'name' => 'WAVE 2 evidence gate helper unchanged',
    'pass' => $evidenceHelperContent !== '' && !str_contains($evidenceHelperContent, 'delivery-eligibility'),
];

$results[] = [
    'name' => 'WAVE 3 authorization gate helper unchanged',
    'pass' => $authGateHelperContent !== '' && !str_contains($authGateHelperContent, 'delivery-eligibility'),
];

$results[] = [
    'name' => 'WAVE 4A final readiness helper unchanged',
    'pass' => $finalReadinessHelperContent !== ''
        && !str_contains($finalReadinessHelperContent, 'delivery-eligibility')
        && !str_contains($finalReadinessHelperContent, 'moghare360_delivery_eligibility'),
];

$results[] = [
    'name' => 'WAVE 2 closure helper unchanged',
    'pass' => $wave2ClosureContent !== '' && !str_contains($wave2ClosureContent, 'delivery-eligibility'),
];

$results[] = [
    'name' => 'WAVE 3 closure helper unchanged',
    'pass' => $wave3ClosureContent !== '' && !str_contains($wave3ClosureContent, 'delivery-eligibility'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'delivery-eligibility') || str_contains($content, 'moghare360_delivery_eligibility')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes for WAVE 4B', 'pass' => $authConfigUnchanged];

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
        && str_contains($pageContent, 'not legal e-signature')
        && !preg_match('/legal\s+final\s+e-?signature\s+confirmed/i', $pageContent),
];

$results[] = [
    'name' => 'Final readiness page links to delivery eligibility',
    'pass' => $finalReadinessPageContent !== ''
        && str_contains($finalReadinessPageContent, 'erp-jobcard-delivery-eligibility.php')
        && str_contains($finalReadinessPageContent, 'بررسی صلاحیت تحویل'),
];

$eval = moghare360_delivery_eligibility_evaluate(1);
$results[] = [
    'name' => 'Evaluate returns valid delivery eligibility status',
    'pass' => in_array($eval['status'] ?? '', [
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ELIGIBLE,
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_REVIEW_REQUIRED,
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE,
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_EMPTY,
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR,
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
    fwrite(STDERR, 'WAVE 4B DELIVERY ELIGIBILITY TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 4B DELIVERY ELIGIBILITY TEST PASSED' . PHP_EOL;
exit(0);
