<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
$roleHelper = is_file($pub . '/includes/m360-access-role-helper.php') ? (string)file_get_contents($pub . '/includes/m360-access-role-helper.php') : '';

function p114r_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }

$results = [
    p114r_pass('role helper exists', $roleHelper !== ''),
    p114r_pass('assign existing role only', str_contains($roleHelper, 'm360_access_role_fetch_by_key')),
    p114r_pass('no INSERT core_roles', !preg_match('/INSERT\s+INTO\s+dbo\.core_roles/i', $roleHelper)),
    p114r_pass('no core_permissions mutation', !str_contains($roleHelper, 'core_permissions')),
    p114r_pass('no core_role_permissions mutation', !str_contains($roleHelper, 'core_role_permissions')),
    p114r_pass('revoke via revoked_at', str_contains($roleHelper, 'revoked_at')),
    p114r_pass('records history', str_contains($roleHelper, 'm360_access_audit_record_change')),
    p114r_pass('role assign page exists', is_file($pub . '/erp-access-role-assign.php')),
];

$pass = 0; $fail = 0;
echo "# P11.4 Role Assignment UI Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
