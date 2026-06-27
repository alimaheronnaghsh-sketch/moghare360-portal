<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';
$e2ePath = $public . '/includes/m360-e2e-validation-helper.php';
$e2e = is_file($e2ePath) ? (string)file_get_contents($e2ePath) : '';

require_once $e2ePath;

function p9e_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$requiredChecks = [
    'm360_e2e_validate_jobcard',
    'm360_e2e_check_online_request',
    'm360_e2e_check_contract',
    'm360_e2e_check_parts_gate',
    'm360_e2e_check_finance_gate',
    'm360_e2e_check_qc',
    'm360_e2e_check_settlement',
    'm360_e2e_check_closed',
    'm360_e2e_check_management',
];

$results = [];
$results[] = p9e_pass('E2E helper exists', is_file($e2ePath));
$results[] = p9e_pass('Read-only comment in helper', str_contains($e2e, 'read-only') || str_contains($e2e, 'no bypass'));
foreach ($requiredChecks as $fn) {
    $results[] = p9e_pass('Function: ' . $fn, function_exists($fn));
}
$offline = m360_e2e_validate_jobcard(false, 0);
$results[] = p9e_pass('Offline validate returns empty', $offline === []);
$results[] = p9e_pass('Uses assert gates not bypass', str_contains($e2e, 'assert_gates') && !preg_match('/gate.*override|skip.*gate|bypass.*gate/i', $e2e));
$results[] = p9e_pass('No INSERT in E2E helper', !preg_match('/\bINSERT\s+INTO\b/i', $e2e));
$results[] = p9e_pass('No UPDATE in E2E helper', !preg_match('/\bUPDATE\s+dbo\./i', $e2e));
$results[] = p9e_pass('No DELETE in E2E helper', !preg_match('/\bDELETE\s+FROM\b/i', $e2e));
$results[] = p9e_pass('Gate result field present', str_contains($e2e, 'gate_result'));
$results[] = p9e_pass('Stage status constants', str_contains($e2e, 'M360_SOFT_RUN_STATUS_PASS') && str_contains($e2e, 'M360_SOFT_RUN_STATUS_BLOCKED'));
$results[] = p9e_pass('Contract gate uses BLOCKED when unsigned', str_contains($e2e, 'M360_SOFT_RUN_STATUS_BLOCKED') && str_contains($e2e, 'CONTRACT'));
$results[] = p9e_pass('Management checks P8 view', str_contains($e2e, 'M360_MGMT_VIEW_PIPELINE') && str_contains($e2e, 'MANAGEMENT_DASHBOARD'));

$pass = 0; $fail = 0;
echo "# P9 E2E Gate Validation Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
