<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 3D Authorization Closure CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-3-authorization-closure-helper.php';
$dashboardPath = $public . DIRECTORY_SEPARATOR . 'erp-authorization-closure-dashboard.php';
$authHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-helper.php';
$wfHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-workflow-helper.php';
$gateHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-gate-helper.php';

require_once $helperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3D_AUTHORIZATION_CLOSURE_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3D_AUTHORIZATION_CLOSURE_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3D_AUTHORIZATION_CLOSURE_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3D_AUTHORIZATION_CLOSURE_SIGNOFF.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3_FINAL_CLOSURE_REPORT.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$dashboardContent = is_file($dashboardPath) ? (string)file_get_contents($dashboardPath) : '';
$authHelperContent = is_file($authHelperPath) ? (string)file_get_contents($authHelperPath) : '';
$wfHelperContent = is_file($wfHelperPath) ? (string)file_get_contents($wfHelperPath) : '';
$gateHelperContent = is_file($gateHelperPath) ? (string)file_get_contents($gateHelperPath) : '';

$results = [];

$results[] = ['name' => 'Closure helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Closure dashboard page exists', 'pass' => is_file($dashboardPath)];

$requiredApis = [
    'moghare360_wave_3_closure_fetch_summary',
    'moghare360_wave_3_closure_fetch_status_counts',
    'moghare360_wave_3_closure_fetch_type_counts',
    'moghare360_wave_3_closure_fetch_method_counts',
    'moghare360_wave_3_closure_fetch_recent_authorizations',
    'moghare360_wave_3_closure_fetch_recent_history',
    'moghare360_wave_3_closure_status',
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

$linkChecks = [
    'erp-jobcard-contract-authorization.php' => 'contract authorization create',
    'erp-jobcard-contract-authorization-preview.php' => 'authorization preview',
    'erp-jobcard-contract-authorization-workflow.php' => 'workflow',
    'erp-jobcard-authorization-gate.php' => 'authorization gate',
    'erp-media-evidence-closure-dashboard.php' => 'WAVE 2 closure dashboard',
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
    'name' => 'Dashboard uses read-only closure status',
    'pass' => $dashboardContent !== '' && str_contains($dashboardContent, 'moghare360_wave_3_closure_status'),
];

$results[] = [
    'name' => '3A authorization helper unchanged',
    'pass' => $authHelperContent !== '' && !str_contains($authHelperContent, 'wave-3-authorization-closure'),
];

$results[] = [
    'name' => '3B workflow helper unchanged',
    'pass' => $wfHelperContent !== '' && !str_contains($wfHelperContent, 'wave-3-authorization-closure'),
];

$results[] = [
    'name' => '3C gate helper unchanged',
    'pass' => $gateHelperContent !== '' && !str_contains($gateHelperContent, 'wave-3-authorization-closure'),
];

$results[] = [
    'name' => 'Public portal is not activated',
    'pass' => $dashboardContent !== '' && str_contains($dashboardContent, 'پورتال عمومی'),
];

$results[] = [
    'name' => 'Legal final e-signature is not claimed',
    'pass' => $dashboardContent !== ''
        && str_contains($dashboardContent, 'not final legal e-signature')
        && !preg_match('/legal\s+final\s+e-?signature\s+confirmed/i', $dashboardContent),
];

$wave3dSqlCreated = false;
foreach (glob($public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_3d*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave3dSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for Wave 3D', 'pass' => !$wave3dSqlCreated];

$status = moghare360_wave_3_closure_status();
$results[] = [
    'name' => 'Closure status returns valid status key',
    'pass' => in_array($status['status'] ?? '', [
        MOGHARE360_WAVE3_CLOSURE_STATUS_READY,
        MOGHARE360_WAVE3_CLOSURE_STATUS_PARTIAL,
        MOGHARE360_WAVE3_CLOSURE_STATUS_EMPTY,
        MOGHARE360_WAVE3_CLOSURE_STATUS_ERROR,
    ], true),
];

$results[] = [
    'name' => 'Closure status includes no_db_write_required check',
    'pass' => ($status['checks']['no_db_write_required'] ?? false) === true,
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
    fwrite(STDERR, 'WAVE 3D AUTHORIZATION CLOSURE TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 3D AUTHORIZATION CLOSURE TEST PASSED' . PHP_EOL;
exit(0);
