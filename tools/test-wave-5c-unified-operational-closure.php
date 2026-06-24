<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 5C Unified Operational Closure CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-5-unified-closure-helper.php';
$dashboardPath = $public . DIRECTORY_SEPARATOR . 'erp-unified-operational-closure-dashboard.php';
$commandHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-unified-jobcard-command-helper.php';
$workbenchHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-command-workbench-helper.php';
$wave2ClosurePath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-2-closure-helper.php';
$wave3ClosurePath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-3-authorization-closure-helper.php';
$wave4ClosurePath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-4-delivery-control-closure-helper.php';

$forbiddenPaths = [
    $public . DIRECTORY_SEPARATOR . 'config.php',
    $public . DIRECTORY_SEPARATOR . 'staff-auth.php',
    $public . DIRECTORY_SEPARATOR . 'access-control.php',
    $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-auth-helper.php',
];

require_once $helperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5C_UNIFIED_OPERATIONAL_CLOSURE_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5C_UNIFIED_OPERATIONAL_CLOSURE_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5C_UNIFIED_OPERATIONAL_CLOSURE_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5C_UNIFIED_OPERATIONAL_CLOSURE_SIGNOFF.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5_FINAL_CLOSURE_REPORT.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$dashboardContent = is_file($dashboardPath) ? (string)file_get_contents($dashboardPath) : '';
$commandHelperContent = is_file($commandHelperPath) ? (string)file_get_contents($commandHelperPath) : '';
$workbenchHelperContent = is_file($workbenchHelperPath) ? (string)file_get_contents($workbenchHelperPath) : '';
$wave2ClosureContent = is_file($wave2ClosurePath) ? (string)file_get_contents($wave2ClosurePath) : '';
$wave3ClosureContent = is_file($wave3ClosurePath) ? (string)file_get_contents($wave3ClosurePath) : '';
$wave4ClosureContent = is_file($wave4ClosurePath) ? (string)file_get_contents($wave4ClosurePath) : '';

$results = [];

$results[] = ['name' => 'Closure helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Closure dashboard page exists', 'pass' => is_file($dashboardPath)];

$requiredApis = [
    'moghare360_wave_5_closure_fetch_jobcards',
    'moghare360_wave_5_closure_fetch_status_counts',
    'moghare360_wave_5_closure_fetch_recent_jobcards',
    'moghare360_wave_5_closure_fetch_sample_command_status',
    'moghare360_wave_5_closure_status',
    'moghare360_wave_5_closure_status_label',
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
    'name' => 'Helper reads from dbo.erp_jobcards',
    'pass' => $helperContent !== '' && str_contains($helperContent, 'FROM dbo.erp_jobcards'),
];

$results[] = [
    'name' => 'Helper references actual fields jobcard_id and jobcard_number',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'jobcard_id')
        && str_contains($helperContent, 'jobcard_number'),
];

$forbiddenFieldPatterns = ['jobcard_code', 'is_active', 'is_deleted', 'tenant_id', 'company_id'];
$forbiddenFieldsPass = true;
foreach ($forbiddenFieldPatterns as $field) {
    if ($helperContent !== '' && str_contains($helperContent, $field)) {
        $forbiddenFieldsPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper does not reference forbidden fields', 'pass' => $forbiddenFieldsPass];

$results[] = [
    'name' => 'Helper references WAVE 5A unified command safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-unified-jobcard-command-helper.php')
        && str_contains($helperContent, 'moghare360_unified_jobcard_command_evaluate'),
];

$results[] = [
    'name' => 'Helper references WAVE 5B workbench safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-jobcard-command-workbench-helper.php')
        && str_contains($helperContent, 'moghare360_jobcard_command_workbench_fetch_jobcards'),
];

$results[] = [
    'name' => 'Helper does not write INSERT/UPDATE/DELETE',
    'pass' => $helperContent !== ''
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent)
        && !preg_match('/\bUPDATE\s+dbo\./i', $helperContent)
        && !preg_match('/\bDELETE\s+FROM\b/i', $helperContent),
];

$linkChecks = [
    'erp-jobcard-command-workbench.php' => 'workbench',
    'erp-jobcard-command-center.php?jobcard_id=1' => 'command center',
    'erp-jobcard-final-readiness.php?jobcard_id=1' => 'final readiness',
    'erp-jobcard-delivery-eligibility.php?jobcard_id=1' => 'delivery eligibility',
    'erp-jobcard-delivery-clearance-preview.php?jobcard_id=1' => 'clearance preview',
    'erp-media-evidence-closure-dashboard.php' => 'WAVE 2 closure',
    'erp-authorization-closure-dashboard.php' => 'WAVE 3 closure',
    'erp-delivery-control-closure-dashboard.php' => 'WAVE 4 closure',
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

$wave5cSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_5c*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave5cSqlCreated = true;
        break;
    }
}
foreach (glob($root . DIRECTORY_SEPARATOR . '**' . DIRECTORY_SEPARATOR . '*wave_5c*.sql') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave5cSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for WAVE 5C', 'pass' => !$wave5cSqlCreated];

$results[] = [
    'name' => 'WAVE 2 closure helper unchanged',
    'pass' => $wave2ClosureContent !== '' && !str_contains($wave2ClosureContent, 'wave-5-unified-closure'),
];

$results[] = [
    'name' => 'WAVE 3 closure helper unchanged',
    'pass' => $wave3ClosureContent !== '' && !str_contains($wave3ClosureContent, 'wave-5-unified-closure'),
];

$results[] = [
    'name' => 'WAVE 4 closure helper unchanged',
    'pass' => $wave4ClosureContent !== ''
        && !str_contains($wave4ClosureContent, 'wave-5-unified-closure')
        && !str_contains($wave4ClosureContent, 'moghare360_wave_5_closure'),
];

$results[] = [
    'name' => 'WAVE 5A unified command helper unchanged',
    'pass' => $commandHelperContent !== ''
        && !str_contains($commandHelperContent, 'wave-5-unified-closure')
        && !str_contains($commandHelperContent, 'moghare360_wave_5_closure'),
];

$results[] = [
    'name' => 'WAVE 5B workbench helper unchanged',
    'pass' => $workbenchHelperContent !== ''
        && !str_contains($workbenchHelperContent, 'wave-5-unified-closure')
        && !str_contains($workbenchHelperContent, 'moghare360_wave_5_closure'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'wave-5-unified-closure') || str_contains($content, 'moghare360_wave_5_closure')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes for WAVE 5C', 'pass' => $authConfigUnchanged];

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

$closure = moghare360_wave_5_closure_status();
$results[] = [
    'name' => 'Closure status returns valid status',
    'pass' => in_array($closure['status'] ?? '', [
        MOGHARE360_WAVE5_CLOSURE_STATUS_READY,
        MOGHARE360_WAVE5_CLOSURE_STATUS_PARTIAL,
        MOGHARE360_WAVE5_CLOSURE_STATUS_EMPTY,
        MOGHARE360_WAVE5_CLOSURE_STATUS_ERROR,
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
    fwrite(STDERR, 'WAVE 5C UNIFIED OPERATIONAL CLOSURE TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 5C UNIFIED OPERATIONAL CLOSURE TEST PASSED' . PHP_EOL;
exit(0);
