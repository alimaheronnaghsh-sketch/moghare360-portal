<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function p119bfixa_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];

$rolePath = $root . '/docs/dry-run/P11_9_A_ROLE_PROVISIONING_CHECKLIST.md';
$runbookPath = $root . '/docs/dry-run/P11_9_A_OPERATOR_RUNBOOK.md';
$goNoGoPath = $root . '/docs/dry-run/P11_9_A_GO_NO_GO_CHECKLIST.md';
$b0Path = $root . '/docs/audit/MOGHARE360_P11_9_B_0_DRY_RUN_PREFLIGHT_EXECUTION_PLAN.md';
$fixReportPath = $root . '/docs/audit/MOGHARE360_P11_9_B_FIX_A_DEMO_STAFF_PROVISIONING_DOC_CORRECTION_REPORT.md';

$roleDoc = is_file($rolePath) ? (string)file_get_contents($rolePath) : '';
$runbookDoc = is_file($runbookPath) ? (string)file_get_contents($runbookPath) : '';
$goNoGoDoc = is_file($goNoGoPath) ? (string)file_get_contents($goNoGoPath) : '';
$b0Doc = is_file($b0Path) ? (string)file_get_contents($b0Path) : '';
$combined = $roleDoc . $runbookDoc . $goNoGoDoc . $b0Doc;

$results[] = p119bfixa_pass('role checklist exists', is_file($rolePath));
$results[] = p119bfixa_pass(
    'checklist states Access Management dry-run path',
    str_contains($roleDoc, 'erp-access-management.php')
        && str_contains($roleDoc, 'erp-access-user-create.php')
);
$results[] = p119bfixa_pass(
    'checklist states Unit Access Console is not user creation',
    str_contains($roleDoc, 'erp-v1-unit-access-console.php')
        && (str_contains($roleDoc, 'does not create') || str_contains($roleDoc, 'not create'))
);
$results[] = p119bfixa_pass(
    'checklist states access-request-admin not for demo provisioning',
    str_contains($roleDoc, 'erp-access-request-admin.php')
        && (str_contains($roleDoc, 'not demo user provisioning') || str_contains($roleDoc, 'Do **not** use'))
);

$demoUsers = [
    ['demo.reception', 'RECEPTION'],
    ['demo.service.manager', 'SERVICE_MANAGER'],
    ['demo.technician', 'TECHNICIAN'],
    ['demo.parts', 'PARTS'],
    ['demo.finance', 'FINANCE'],
    ['demo.qc', 'QC'],
];
foreach ($demoUsers as [$user, $role]) {
    $results[] = p119bfixa_pass(
        'demo user listed: ' . $user . ' / ' . $role,
        str_contains($roleDoc, $user) && str_contains($roleDoc, $role)
    );
}

$results[] = p119bfixa_pass(
    'PARTS vs INVENTORY note exists',
    str_contains($roleDoc, 'INVENTORY') && str_contains($roleDoc, 'PARTS')
);
$results[] = p119bfixa_pass(
    'SERVICE_MANAGER JSON-template note exists',
    str_contains($roleDoc, 'SERVICE_MANAGER')
        && (str_contains($roleDoc, 'production JSON') || str_contains($roleDoc, 'production-users'))
);

$passwordPatterns = ['password123', 'Password123', 'changeme', 'demo1234', 'your_password'];
foreach ($passwordPatterns as $pat) {
    $results[] = p119bfixa_pass(
        'no password pattern: ' . $pat,
        !str_contains(strtolower($combined), strtolower($pat))
    );
}

$results[] = p119bfixa_pass(
    'no instruction to create users via raw SQL',
    str_contains($combined, 'raw SQL')
        && (
            preg_match('/Do\s+\*?\*?not\*?\*?\s+create users via raw SQL/i', $combined) === 1
            || str_contains($combined, 'no raw SQL user creation')
            || str_contains($combined, 'Forbidden unless')
        )
);
$results[] = p119bfixa_pass(
    'JSON import not P11.9-B dry-run path',
    str_contains($combined, 'private/production-users.json')
        && (str_contains($combined, 'not P11.9-B') || str_contains($combined, 'not** P11.9-B'))
);

$stopCombined = $runbookDoc . $goNoGoDoc . $roleDoc;
$results[] = p119bfixa_pass(
    'STOP rule if UI role unavailable',
    str_contains($stopCombined, 'unavailable')
        || str_contains($stopCombined, 'missing from UI')
        || str_contains($stopCombined, 'missing from Access Management')
);

$results[] = p119bfixa_pass('fix-a report exists', is_file($fixReportPath));

$pubHtml = $root . '/public_html';
$pubChanged = false;
if (is_dir($pubHtml)) {
    $gitCmd = 'git -C ' . escapeshellarg($root) . ' status --porcelain public_html 2>nul';
    $gitOut = shell_exec($gitCmd);
    if (is_string($gitOut) && trim($gitOut) !== '') {
        $pubChanged = true;
    }
}
$results[] = p119bfixa_pass('no public_html files changed (git)', !$pubChanged);

$sqlDir = $root . '/database/dry-run';
$sqlChanged = false;
if (is_dir($sqlDir)) {
    foreach (glob($sqlDir . '/P11_9_A_*.sql') ?: [] as $sqlFile) {
        if (filemtime($sqlFile) > time() - 300) {
            $sqlChanged = true;
            break;
        }
    }
}
$results[] = p119bfixa_pass('no SQL dry-run files changed recently', !$sqlChanged);

$authFiles = ['staff-login.php', 'owner-login.php'];
foreach ($authFiles as $f) {
    $path = $pubHtml . '/' . $f;
    $recent = is_file($path) && filemtime($path) > time() - 300;
    $results[] = p119bfixa_pass('auth file not recently modified: ' . $f, !$recent);
}

$roleSeed = $pubHtml . '/sql/sqlserver/core_v0_06_seed_roles_permissions.sql';
$seedRecent = is_file($roleSeed) && filemtime($roleSeed) > time() - 300;
$results[] = p119bfixa_pass('no permission/role seed changed recently', !$seedRecent);

$dryRunOnly = $roleDoc . $runbookDoc . $goNoGoDoc;
$results[] = p119bfixa_pass(
    'no P12 scope in dry-run provisioning docs',
    !preg_match('/\bP12\b/i', $dryRunOnly)
);

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-A Provisioning Docs Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
