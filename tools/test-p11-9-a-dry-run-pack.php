<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function p119a_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$dryRunDir = $root . '/docs/dry-run';

$requiredDocs = [
    'P11_9_A_ONE_DAY_RUN_DRY_RUN_PACK.md',
    'P11_9_A_OPERATOR_RUNBOOK.md',
    'P11_9_A_ROLE_PROVISIONING_CHECKLIST.md',
    'P11_9_A_M360_DEMO_JOBCARD_PREPARATION_PLAN.md',
    'P11_9_A_OTP_DEFERRAL_PROTOCOL.md',
    'P11_9_A_115_STEP_EXECUTION_LOG_TEMPLATE.md',
    'P11_9_A_GO_NO_GO_CHECKLIST.md',
    'P11_9_A_MANAGER_OBSERVATION_GUIDE.md',
    'P11_9_A_DRY_RUN_INCIDENT_REGISTER_TEMPLATE.md',
];

foreach ($requiredDocs as $doc) {
    $path = $dryRunDir . '/' . $doc;
    $results[] = p119a_pass('dry-run doc: ' . $doc, is_file($path));
}

$results[] = p119a_pass('scope report exists', is_file($root . '/docs/audit/MOGHARE360_P11_9_A_DRY_RUN_PACK_SCOPE_REPORT.md'));
$results[] = p119a_pass('implementation report exists', is_file($root . '/docs/audit/MOGHARE360_P11_9_A_DRY_RUN_PACK_REPORT.md'));

$roleDoc = (string)file_get_contents($dryRunDir . '/P11_9_A_ROLE_PROVISIONING_CHECKLIST.md');
$roles = ['OWNER', 'RECEPTION', 'SERVICE_MANAGER', 'TECHNICIAN', 'PARTS', 'FINANCE', 'QC'];
foreach ($roles as $role) {
    $results[] = p119a_pass('role checklist includes ' . $role, str_contains($roleDoc, $role));
}

$allDryRunText = '';
foreach ($requiredDocs as $doc) {
    $allDryRunText .= (string)file_get_contents($dryRunDir . '/' . $doc);
}
$passwordPatterns = ['password123', 'Password123', 'changeme', 'demo1234', 'password_hash', 'your_password'];
foreach ($passwordPatterns as $pat) {
    $results[] = p119a_pass('no password pattern in docs: ' . $pat, !str_contains(strtolower($allDryRunText), strtolower($pat)));
}

$results[] = p119a_pass('OTP deferral protocol exists', is_file($dryRunDir . '/P11_9_A_OTP_DEFERRAL_PROTOCOL.md'));
$results[] = p119a_pass('M360-DEMO plan exists', is_file($dryRunDir . '/P11_9_A_M360_DEMO_JOBCARD_PREPARATION_PLAN.md'));
$results[] = p119a_pass('115-step log template exists', is_file($dryRunDir . '/P11_9_A_115_STEP_EXECUTION_LOG_TEMPLATE.md'));
$results[] = p119a_pass('Go/No-Go checklist exists', is_file($dryRunDir . '/P11_9_A_GO_NO_GO_CHECKLIST.md'));
$results[] = p119a_pass('manager observation guide exists', is_file($dryRunDir . '/P11_9_A_MANAGER_OBSERVATION_GUIDE.md'));
$results[] = p119a_pass('incident register template exists', is_file($dryRunDir . '/P11_9_A_DRY_RUN_INCIDENT_REGISTER_TEMPLATE.md'));

$preflight = $root . '/database/dry-run/P11_9_A_READONLY_PREFLIGHT_CHECK.sql';
$results[] = p119a_pass('read-only preflight SQL exists', is_file($preflight));

$master = (string)file_get_contents($dryRunDir . '/P11_9_A_ONE_DAY_RUN_DRY_RUN_PACK.md');
$results[] = p119a_pass('master pack references P11.9-1', str_contains($master, 'P11.9-1') || str_contains($master, 'P11_9_1'));
$results[] = p119a_pass('master pack does not execute dry run', str_contains($master, 'does not execute'));

$pass = 0;
$fail = 0;
echo "# P11.9-A Dry Run Pack Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
