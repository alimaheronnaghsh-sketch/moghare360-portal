<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 5B JobCard Command Workbench CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-command-workbench-helper.php';
$pagePath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-command-workbench.php';
$commandCenterPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-command-center.php';
$unifiedHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-unified-jobcard-command-helper.php';
$evidenceHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-gate-helper.php';
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
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5B_JOBCARD_COMMAND_WORKBENCH_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5B_JOBCARD_COMMAND_WORKBENCH_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5B_JOBCARD_COMMAND_WORKBENCH_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_5_unified_jobcard_command' . DIRECTORY_SEPARATOR . 'WAVE_5B_JOBCARD_COMMAND_WORKBENCH_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$pageContent = is_file($pagePath) ? (string)file_get_contents($pagePath) : '';
$commandCenterContent = is_file($commandCenterPath) ? (string)file_get_contents($commandCenterPath) : '';
$unifiedHelperContent = is_file($unifiedHelperPath) ? (string)file_get_contents($unifiedHelperPath) : '';
$evidenceHelperContent = is_file($evidenceHelperPath) ? (string)file_get_contents($evidenceHelperPath) : '';
$authGateHelperContent = is_file($authGateHelperPath) ? (string)file_get_contents($authGateHelperPath) : '';
$finalReadinessHelperContent = is_file($finalReadinessHelperPath) ? (string)file_get_contents($finalReadinessHelperPath) : '';
$eligibilityHelperContent = is_file($eligibilityHelperPath) ? (string)file_get_contents($eligibilityHelperPath) : '';
$clearanceHelperContent = is_file($clearanceHelperPath) ? (string)file_get_contents($clearanceHelperPath) : '';
$wave4ClosureContent = is_file($wave4ClosurePath) ? (string)file_get_contents($wave4ClosurePath) : '';
$wave2ClosureContent = is_file($wave2ClosurePath) ? (string)file_get_contents($wave2ClosurePath) : '';
$wave3ClosureContent = is_file($wave3ClosurePath) ? (string)file_get_contents($wave3ClosurePath) : '';

$results = [];

$results[] = ['name' => 'Workbench helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Workbench page exists', 'pass' => is_file($pagePath)];

$requiredApis = [
    'moghare360_jobcard_command_workbench_fetch_jobcards',
    'moghare360_jobcard_command_workbench_fetch_jobcard_snapshot',
    'moghare360_jobcard_command_workbench_status_summary',
    'moghare360_jobcard_command_workbench_status_label',
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
    'name' => 'Helper references jobcard_id and jobcard_number',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'jobcard_id')
        && str_contains($helperContent, 'jobcard_number'),
];

$results[] = [
    'name' => 'Helper does not reference invalid legacy fields',
    'pass' => $helperContent !== ''
        && !preg_match('/\bjobcard_code\b/', $helperContent)
        && !preg_match('/\bis_active\b/i', $helperContent)
        && !preg_match('/\bis_deleted\b/i', $helperContent)
        && !preg_match('/\btenant_id\b/i', $helperContent)
        && !preg_match('/\bcompany_id\b/i', $helperContent),
];

$results[] = [
    'name' => 'Helper references WAVE 5A unified command safely',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'moghare360-unified-jobcard-command-helper.php')
        && str_contains($helperContent, 'moghare360_unified_jobcard_command_evaluate'),
];

$results[] = [
    'name' => 'Helper does not write INSERT/UPDATE/DELETE',
    'pass' => $helperContent !== ''
        && !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\b/i', $helperContent)
        && !preg_match('/\bUPDATE\s+dbo\./i', $helperContent)
        && !preg_match('/\bDELETE\s+FROM\b/i', $helperContent),
];

$linkChecks = [
    'erp-jobcard-command-center.php' => 'command center',
    'erp-jobcard-final-readiness.php' => 'final readiness',
    'erp-jobcard-delivery-eligibility.php' => 'delivery eligibility',
    'erp-jobcard-delivery-clearance-preview.php' => 'clearance preview',
    'erp-jobcard-evidence-review.php' => 'evidence review',
    'erp-jobcard-authorization-gate.php' => 'authorization gate',
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

$results[] = [
    'name' => 'Command center links back to workbench',
    'pass' => $commandCenterContent !== ''
        && str_contains($commandCenterContent, 'erp-jobcard-command-workbench.php')
        && str_contains($commandCenterContent, 'بازگشت به میز فرمان کارت کار'),
];

$wave5bSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_5b*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave5bSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for WAVE 5B', 'pass' => !$wave5bSqlCreated];

$results[] = [
    'name' => 'WAVE 2 evidence gate helper unchanged',
    'pass' => $evidenceHelperContent !== '' && !str_contains($evidenceHelperContent, 'command-workbench'),
];

$results[] = [
    'name' => 'WAVE 3 authorization gate helper unchanged',
    'pass' => $authGateHelperContent !== '' && !str_contains($authGateHelperContent, 'command-workbench'),
];

$results[] = [
    'name' => 'WAVE 4A final readiness helper unchanged',
    'pass' => $finalReadinessHelperContent !== '' && !str_contains($finalReadinessHelperContent, 'command-workbench'),
];

$results[] = [
    'name' => 'WAVE 4B delivery eligibility helper unchanged',
    'pass' => $eligibilityHelperContent !== '' && !str_contains($eligibilityHelperContent, 'command-workbench'),
];

$results[] = [
    'name' => 'WAVE 4C delivery clearance helper unchanged',
    'pass' => $clearanceHelperContent !== '' && !str_contains($clearanceHelperContent, 'command-workbench'),
];

$results[] = [
    'name' => 'WAVE 4D closure helper unchanged',
    'pass' => $wave4ClosureContent !== '' && !str_contains($wave4ClosureContent, 'command-workbench'),
];

$results[] = [
    'name' => 'WAVE 5A unified command helper unchanged',
    'pass' => $unifiedHelperContent !== ''
        && !str_contains($unifiedHelperContent, 'command-workbench')
        && !str_contains($unifiedHelperContent, 'moghare360_jobcard_command_workbench'),
];

$results[] = [
    'name' => 'WAVE 2 closure helper unchanged',
    'pass' => $wave2ClosureContent !== '' && !str_contains($wave2ClosureContent, 'command-workbench'),
];

$results[] = [
    'name' => 'WAVE 3 closure helper unchanged',
    'pass' => $wave3ClosureContent !== '' && !str_contains($wave3ClosureContent, 'command-workbench'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'command-workbench') || str_contains($content, 'moghare360_jobcard_command_workbench')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes for WAVE 5B', 'pass' => $authConfigUnchanged];

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

$listResult = moghare360_jobcard_command_workbench_fetch_jobcards(5);
$results[] = [
    'name' => 'Workbench fetch jobcards returns list structure',
    'pass' => is_array($listResult) && array_key_exists('jobcards', $listResult),
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
    fwrite(STDERR, 'WAVE 5B JOBCARD COMMAND WORKBENCH TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 5B JOBCARD COMMAND WORKBENCH TEST PASSED' . PHP_EOL;
exit(0);
