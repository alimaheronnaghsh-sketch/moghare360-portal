<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 4D Delivery Control Closure CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-4-delivery-control-closure-helper.php';
$dashboardPath = $public . DIRECTORY_SEPARATOR . 'erp-delivery-control-closure-dashboard.php';
$finalReadinessHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-final-readiness-helper.php';
$eligibilityHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-delivery-eligibility-helper.php';
$clearanceHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-delivery-clearance-helper.php';
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
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4D_DELIVERY_CONTROL_CLOSURE_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4D_DELIVERY_CONTROL_CLOSURE_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4D_DELIVERY_CONTROL_CLOSURE_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4D_DELIVERY_CONTROL_CLOSURE_SIGNOFF.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_4_jobcard_final_readiness' . DIRECTORY_SEPARATOR . 'WAVE_4_FINAL_CLOSURE_REPORT.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$dashboardContent = is_file($dashboardPath) ? (string)file_get_contents($dashboardPath) : '';
$finalReadinessHelperContent = is_file($finalReadinessHelperPath) ? (string)file_get_contents($finalReadinessHelperPath) : '';
$eligibilityHelperContent = is_file($eligibilityHelperPath) ? (string)file_get_contents($eligibilityHelperPath) : '';
$clearanceHelperContent = is_file($clearanceHelperPath) ? (string)file_get_contents($clearanceHelperPath) : '';
$evidenceHelperContent = is_file($evidenceHelperPath) ? (string)file_get_contents($evidenceHelperPath) : '';
$authGateHelperContent = is_file($authGateHelperPath) ? (string)file_get_contents($authGateHelperPath) : '';
$wave2ClosureContent = is_file($wave2ClosurePath) ? (string)file_get_contents($wave2ClosurePath) : '';
$wave3ClosureContent = is_file($wave3ClosurePath) ? (string)file_get_contents($wave3ClosurePath) : '';

$results = [];

$results[] = ['name' => 'Closure helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Closure dashboard page exists', 'pass' => is_file($dashboardPath)];

$requiredApis = [
    'moghare360_wave_4_closure_fetch_summary',
    'moghare360_wave_4_closure_fetch_clearance_status_counts',
    'moghare360_wave_4_closure_fetch_clearance_decision_counts',
    'moghare360_wave_4_closure_fetch_recent_clearances',
    'moghare360_wave_4_closure_fetch_recent_history',
    'moghare360_wave_4_closure_fetch_sample_jobcard_status',
    'moghare360_wave_4_closure_status',
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
    'name' => 'Helper reads from dbo.erp_jobcard_delivery_clearances',
    'pass' => $helperContent !== '' && str_contains($helperContent, 'FROM dbo.erp_jobcard_delivery_clearances'),
];

$results[] = [
    'name' => 'Helper reads from dbo.erp_jobcard_delivery_clearance_history',
    'pass' => $helperContent !== '' && str_contains($helperContent, 'FROM dbo.erp_jobcard_delivery_clearance_history'),
];

$results[] = [
    'name' => 'Helper references WAVE 4A final readiness safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-jobcard-final-readiness-helper.php')
        && str_contains($helperContent, 'moghare360_jobcard_final_readiness_evaluate'),
];

$results[] = [
    'name' => 'Helper references WAVE 4B delivery eligibility safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-delivery-eligibility-helper.php')
        && str_contains($helperContent, 'moghare360_delivery_eligibility_evaluate'),
];

$results[] = [
    'name' => 'Helper does not write INSERT/UPDATE/DELETE',
    'pass' => $helperContent !== ''
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent)
        && !preg_match('/\bUPDATE\s+dbo\./i', $helperContent)
        && !preg_match('/\bDELETE\s+FROM\b/i', $helperContent),
];

$linkChecks = [
    'erp-jobcard-final-readiness.php' => 'final readiness',
    'erp-jobcard-delivery-eligibility.php' => 'delivery eligibility',
    'erp-jobcard-delivery-clearance.php' => 'clearance create',
    'erp-jobcard-delivery-clearance-preview.php' => 'clearance preview',
    'erp-media-evidence-closure-dashboard.php' => 'WAVE 2 closure',
    'erp-authorization-closure-dashboard.php' => 'WAVE 3 closure',
];

foreach ($linkChecks as $href => $label) {
    $results[] = [
        'name' => 'Dashboard links to ' . $label,
        'pass' => $dashboardContent !== '' && str_contains($dashboardContent, $href),
    ];
}

$results[] = [
    'name' => 'Dashboard has no file input',
    'pass' => $dashboardContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $dashboardContent),
];

$results[] = [
    'name' => 'Dashboard has no POST form',
    'pass' => $dashboardContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $dashboardContent),
];

$results[] = [
    'name' => 'Dashboard has no final delivery submit/action',
    'pass' => $dashboardContent !== ''
        && !preg_match('/submit-.*delivery/i', $dashboardContent)
        && str_contains($dashboardContent, 'not final vehicle delivery'),
];

$results[] = [
    'name' => 'Dashboard does not create delivery completion',
    'pass' => $dashboardContent !== ''
        && !preg_match('/vehicle_delivered/i', $dashboardContent)
        && !preg_match('/delivery_completion/i', $dashboardContent)
        && str_contains($dashboardContent, 'رکورد تکمیل تحویل ایجاد نمی‌شود'),
];

$wave4dSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_4d*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave4dSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for WAVE 4D', 'pass' => !$wave4dSqlCreated];

$results[] = [
    'name' => 'WAVE 2 evidence gate helper unchanged',
    'pass' => $evidenceHelperContent !== '' && !str_contains($evidenceHelperContent, 'wave-4-delivery-control-closure'),
];

$results[] = [
    'name' => 'WAVE 3 authorization gate helper unchanged',
    'pass' => $authGateHelperContent !== '' && !str_contains($authGateHelperContent, 'wave-4-delivery-control-closure'),
];

$results[] = [
    'name' => 'WAVE 4A final readiness helper unchanged',
    'pass' => $finalReadinessHelperContent !== ''
        && !str_contains($finalReadinessHelperContent, 'wave-4-delivery-control-closure')
        && !str_contains($finalReadinessHelperContent, 'moghare360_wave_4_closure'),
];

$results[] = [
    'name' => 'WAVE 4B delivery eligibility helper unchanged',
    'pass' => $eligibilityHelperContent !== ''
        && !str_contains($eligibilityHelperContent, 'wave-4-delivery-control-closure')
        && !str_contains($eligibilityHelperContent, 'moghare360_wave_4_closure'),
];

$results[] = [
    'name' => 'WAVE 4C delivery clearance helper unchanged',
    'pass' => $clearanceHelperContent !== ''
        && !str_contains($clearanceHelperContent, 'wave-4-delivery-control-closure')
        && !str_contains($clearanceHelperContent, 'moghare360_wave_4_closure'),
];

$results[] = [
    'name' => 'WAVE 2 closure helper unchanged',
    'pass' => $wave2ClosureContent !== '' && !str_contains($wave2ClosureContent, 'wave-4-delivery-control-closure'),
];

$results[] = [
    'name' => 'WAVE 3 closure helper unchanged',
    'pass' => $wave3ClosureContent !== '' && !str_contains($wave3ClosureContent, 'wave-4-delivery-control-closure'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'wave-4-delivery-control-closure') || str_contains($content, 'moghare360_wave_4_closure')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes for WAVE 4D', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'Public portal is not activated',
    'pass' => $dashboardContent !== '' && str_contains($dashboardContent, 'پورتال عمومی'),
];

$results[] = [
    'name' => 'Payment/accounting is not activated',
    'pass' => $dashboardContent !== ''
        && str_contains($dashboardContent, 'پرداخت')
        && !preg_match('/payment\s+gateway\s+active/i', $dashboardContent),
];

$results[] = [
    'name' => 'Legal final e-signature is not claimed',
    'pass' => $dashboardContent !== ''
        && str_contains($dashboardContent, 'not legal final e-signature')
        && !preg_match('/legal\s+final\s+e-?signature\s+confirmed/i', $dashboardContent),
];

$closure = moghare360_wave_4_closure_status();
$results[] = [
    'name' => 'Closure status returns valid status',
    'pass' => in_array($closure['status'] ?? '', [
        MOGHARE360_WAVE4_CLOSURE_STATUS_READY,
        MOGHARE360_WAVE4_CLOSURE_STATUS_PARTIAL,
        MOGHARE360_WAVE4_CLOSURE_STATUS_EMPTY,
        MOGHARE360_WAVE4_CLOSURE_STATUS_ERROR,
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
    fwrite(STDERR, 'WAVE 4D DELIVERY CONTROL CLOSURE TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 4D DELIVERY CONTROL CLOSURE TEST PASSED' . PHP_EOL;
exit(0);
