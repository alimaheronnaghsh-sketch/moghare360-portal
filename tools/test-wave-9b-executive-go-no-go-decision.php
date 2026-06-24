<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 9B Executive Go/No-Go Decision CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$sqlPath = $public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'wave_9b_executive_go_no_go_decision_log.sql';
$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-executive-go-no-go-decision-helper.php';
$createPath = $public . DIRECTORY_SEPARATOR . 'erp-executive-go-no-go-decision-create.php';
$submitPath = $public . DIRECTORY_SEPARATOR . 'submit-executive-go-no-go-decision.php';
$boardPath = $public . DIRECTORY_SEPARATOR . 'erp-executive-go-no-go-decision-board.php';
$detailPath = $public . DIRECTORY_SEPARATOR . 'erp-executive-go-no-go-decision-detail.php';
$readinessDashboardPath = $public . DIRECTORY_SEPARATOR . 'erp-executive-soft-run-readiness-dashboard.php';

$readinessHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-executive-soft-run-readiness-helper.php';
$wave6HelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-final-closure-helper.php';
$wave7HelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-final-closure-helper.php';
$wave8HelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-final-closure-helper.php';
$findingHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-helper.php';
$submitFindingPath = $public . DIRECTORY_SEPARATOR . 'submit-soft-run-finding.php';

$forbiddenPaths = [
    $public . DIRECTORY_SEPARATOR . 'config.php',
    $public . DIRECTORY_SEPARATOR . 'staff-auth.php',
    $public . DIRECTORY_SEPARATOR . 'access-control.php',
    $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-auth-helper.php',
];

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_9_executive_readiness' . DIRECTORY_SEPARATOR . 'WAVE_9B_EXECUTIVE_GO_NO_GO_DECISION_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_9_executive_readiness' . DIRECTORY_SEPARATOR . 'WAVE_9B_EXECUTIVE_GO_NO_GO_DECISION_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_9_executive_readiness' . DIRECTORY_SEPARATOR . 'WAVE_9B_EXECUTIVE_GO_NO_GO_DECISION_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_9_executive_readiness' . DIRECTORY_SEPARATOR . 'WAVE_9B_EXECUTIVE_GO_NO_GO_DECISION_SIGNOFF.md',
];

$sqlContent = is_file($sqlPath) ? (string)file_get_contents($sqlPath) : '';
$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$createContent = is_file($createPath) ? (string)file_get_contents($createPath) : '';
$submitContent = is_file($submitPath) ? (string)file_get_contents($submitPath) : '';
$boardContent = is_file($boardPath) ? (string)file_get_contents($boardPath) : '';
$detailContent = is_file($detailPath) ? (string)file_get_contents($detailPath) : '';
$readinessDashboardContent = is_file($readinessDashboardPath) ? (string)file_get_contents($readinessDashboardPath) : '';
$readinessHelperContent = is_file($readinessHelperPath) ? (string)file_get_contents($readinessHelperPath) : '';
$wave6HelperContent = is_file($wave6HelperPath) ? (string)file_get_contents($wave6HelperPath) : '';
$wave7HelperContent = is_file($wave7HelperPath) ? (string)file_get_contents($wave7HelperPath) : '';
$wave8HelperContent = is_file($wave8HelperPath) ? (string)file_get_contents($wave8HelperPath) : '';
$findingHelperContent = is_file($findingHelperPath) ? (string)file_get_contents($findingHelperPath) : '';
$submitFindingContent = is_file($submitFindingPath) ? (string)file_get_contents($submitFindingPath) : '';

require_once $helperPath;

$results = [];

$results[] = ['name' => 'SQL file exists', 'pass' => is_file($sqlPath)];

$results[] = [
    'name' => 'SQL contains dbo.erp_executive_soft_run_decisions',
    'pass' => $sqlContent !== '' && str_contains($sqlContent, 'dbo.erp_executive_soft_run_decisions'),
];

$results[] = [
    'name' => 'SQL contains dbo.erp_executive_soft_run_decision_history',
    'pass' => $sqlContent !== '' && str_contains($sqlContent, 'dbo.erp_executive_soft_run_decision_history'),
];

$results[] = [
    'name' => 'SQL is idempotent',
    'pass' => $sqlContent !== ''
        && str_contains($sqlContent, 'IF OBJECT_ID')
        && !preg_match('/\bDROP\s+TABLE\b/i', $sqlContent),
];

$results[] = [
    'name' => 'SQL does not drop tables',
    'pass' => $sqlContent !== '' && !preg_match('/\bDROP\s+TABLE\b/i', $sqlContent),
];

$results[] = ['name' => 'Helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Create page exists', 'pass' => is_file($createPath)];
$results[] = ['name' => 'Submit page exists', 'pass' => is_file($submitPath)];
$results[] = ['name' => 'Board page exists', 'pass' => is_file($boardPath)];
$results[] = ['name' => 'Detail page exists', 'pass' => is_file($detailPath)];

$requiredApis = [
    'moghare360_executive_go_no_go_decision_allowed_types',
    'moghare360_executive_go_no_go_decision_allowed_statuses',
    'moghare360_executive_go_no_go_decision_allowed_readiness_statuses',
    'moghare360_executive_go_no_go_decision_fetch_current_snapshot',
    'moghare360_executive_go_no_go_decision_create',
    'moghare360_executive_go_no_go_decision_fetch_recent',
    'moghare360_executive_go_no_go_decision_fetch_detail',
    'moghare360_executive_go_no_go_decision_fetch_history',
    'moghare360_executive_go_no_go_decision_status_label',
    'moghare360_executive_go_no_go_decision_type_label',
];

$apiPass = true;
foreach ($requiredApis as $api) {
    if (!function_exists($api)) {
        $apiPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper contains required APIs', 'pass' => $apiPass];

preg_match_all('/INSERT\s+INTO\s+dbo\.([a-z0-9_]+)/i', $helperContent, $insertMatches);
$insertTables = array_unique(array_map('strtolower', $insertMatches[1] ?? []));
$allowedInsertTables = ['erp_executive_soft_run_decisions', 'erp_executive_soft_run_decision_history'];
$insertBoundaryPass = $insertTables !== []
    && count(array_diff($insertTables, $allowedInsertTables)) === 0
    && count(array_diff($allowedInsertTables, $insertTables)) === 0;
$results[] = ['name' => 'Helper writes only to executive decision tables', 'pass' => $insertBoundaryPass];

$results[] = [
    'name' => 'Helper uses prepared statements',
    'pass' => $helperContent !== ''
        && preg_match('/VALUES \(\?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?\)/', $helperContent) === 1
        && preg_match('/VALUES \(\?, \?, \?, \?, \?, \?, \?\)/', $helperContent) === 1,
];

$types = moghare360_executive_go_no_go_decision_allowed_types();
$statuses = moghare360_executive_go_no_go_decision_allowed_statuses();
$readinessStatuses = moghare360_executive_go_no_go_decision_allowed_readiness_statuses();

$results[] = [
    'name' => 'Helper validates decision types',
    'pass' => in_array('GO_REVIEW', $types, true)
        && in_array('NO_GO', $types, true)
        && in_array('REVIEW_REQUIRED', $types, true),
];

$results[] = [
    'name' => 'Helper validates decision statuses',
    'pass' => in_array('RECORDED', $statuses, true)
        && in_array('ACCEPTED', $statuses, true),
];

$results[] = [
    'name' => 'Helper validates executive readiness statuses',
    'pass' => in_array('GO_REVIEW_REQUIRED', $readinessStatuses, true)
        && in_array('EXECUTIVE_REVIEW_READY', $readinessStatuses, true),
];

$results[] = [
    'name' => 'Submit page accepts POST only',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, "REQUEST_METHOD'] ?? '') !== 'POST'")
        && str_contains($submitContent, 'erp-executive-go-no-go-decision-create.php'),
];

$results[] = [
    'name' => 'Board page has no POST form',
    'pass' => $boardContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $boardContent),
];

$results[] = [
    'name' => 'Detail page has no POST form',
    'pass' => $detailContent !== '' && !preg_match('/method\s*=\s*["\']post["\']/i', $detailContent),
];

$results[] = [
    'name' => 'No final delivery approval exists',
    'pass' => $createContent !== ''
        && str_contains($createContent, 'not final delivery approval')
        && !preg_match('/final.delivery.approv/i', $helperContent),
];

$results[] = [
    'name' => 'Dashboard does not create delivery completion',
    'pass' => !preg_match('/delivery_completion_record/i', $createContent . $boardContent . $detailContent),
];

$results[] = [
    'name' => 'No public portal/payment/accounting/legal e-signature activation',
    'pass' => $createContent !== ''
        && str_contains($createContent, 'Not legal e-signature')
        && str_contains($createContent, 'Not payment/accounting'),
];

$forbiddenWriteTables = [
    'erp_soft_run_findings',
    'erp_soft_run_finding_history',
    'erp_soft_run_pilot_executions',
    'erp_soft_run_pilot_execution_history',
    'erp_jobcards',
    'erp_jobcard_delivery',
    'erp_media',
    'erp_authorization',
    'erp_customers',
    'erp_vehicles',
    'erp_invoice',
    'erp_payment',
];
$noForbiddenWrites = true;
foreach ($forbiddenWriteTables as $table) {
    if (preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\s+dbo\.' . preg_quote($table, '/') . '\b/i', $helperContent)) {
        $noForbiddenWrites = false;
        break;
    }
}
$results[] = [
    'name' => 'No writes to finding/pilot/JobCard/delivery/evidence/authorization/customer/vehicle/payment tables',
    'pass' => $noForbiddenWrites,
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $forbiddenPath) {
    if (!is_file($forbiddenPath)) {
        continue;
    }
    $content = (string)file_get_contents($forbiddenPath);
    if (str_contains($content, 'executive-go-no-go') || str_contains($content, 'moghare360_executive_go_no_go')) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No auth/config changes', 'pass' => $authConfigUnchanged];

$results[] = [
    'name' => 'WAVE 9A readiness helper unchanged',
    'pass' => $readinessHelperContent !== ''
        && !str_contains($readinessHelperContent, 'executive-go-no-go')
        && !str_contains($readinessHelperContent, 'moghare360_executive_go_no_go'),
];

$results[] = [
    'name' => 'WAVE 6 final closure helper unchanged',
    'pass' => $wave6HelperContent !== '' && !str_contains($wave6HelperContent, 'executive-go-no-go'),
];

$results[] = [
    'name' => 'WAVE 7 pilot final closure helper unchanged',
    'pass' => $wave7HelperContent !== '' && !str_contains($wave7HelperContent, 'executive-go-no-go'),
];

$results[] = [
    'name' => 'WAVE 8 finding final closure helper unchanged',
    'pass' => $wave8HelperContent !== '' && !str_contains($wave8HelperContent, 'executive-go-no-go'),
];

$results[] = [
    'name' => 'WAVE 8A finding helper unchanged',
    'pass' => $findingHelperContent !== '' && !str_contains($findingHelperContent, 'executive-go-no-go'),
];

$results[] = [
    'name' => 'WAVE 8A submit page unchanged',
    'pass' => $submitFindingContent !== '' && !str_contains($submitFindingContent, 'executive-go-no-go'),
];

$results[] = [
    'name' => 'Executive readiness dashboard links to create and board',
    'pass' => $readinessDashboardContent !== ''
        && str_contains($readinessDashboardContent, 'erp-executive-go-no-go-decision-create.php')
        && str_contains($readinessDashboardContent, 'erp-executive-go-no-go-decision-board.php'),
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
    fwrite(STDERR, 'WAVE 9B EXECUTIVE GO NO GO DECISION TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 9B EXECUTIVE GO NO GO DECISION TEST PASSED' . PHP_EOL;
exit(0);
