<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1143f_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p1143f_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$audit = p1143f_read($pub . '/includes/m360-access-audit-helper.php');
$role = p1143f_read($pub . '/includes/m360-access-role-helper.php');

$results = [];
$results[] = p1143f_pass('ensure_request called before core_user_roles insert', preg_match('/m360_access_audit_ensure_request[\s\S]*?INSERT\s+INTO\s+dbo\.core_user_roles/s', $role) === 1);
$results[] = p1143f_pass('request_state APPLIED in ensure_request', preg_match("/request_state[\s\S]*APPLIED/s", $audit) === 1);
$results[] = p1143f_pass('request_type ROLE_GRANT in role assign', preg_match('/m360_access_role_assign[\s\S]*?ROLE_GRANT/s', $role) === 1);
$results[] = p1143f_pass('migration_source constant used', str_contains($audit, 'M360_ACCESS_MGMT_MIGRATION_SOURCE'));
$results[] = p1143f_pass('duplicate active grant check', str_contains($role, 'revoked_at IS NULL'));
$results[] = p1143f_pass('history ACCESS_MGMT_ROLE_GRANTED', str_contains($role, 'ACCESS_MGMT_ROLE_GRANTED'));
$results[] = p1143f_pass('after_json includes role_key', str_contains($role, "'role_key'"));
$results[] = p1143f_pass('after_json includes request_id', str_contains($role, "'request_id'"));
$results[] = p1143f_pass('is_emergency 0 for access mgmt request', preg_match('/INSERT\s+INTO\s+dbo\.core_access_requests[\s\S]*?1,\s*0,\s*\?/s', $audit) === 1);
$results[] = p1143f_pass('fetch user_role_id fallback', str_contains($role, 'm360_access_role_fetch_user_role_id'));

$pass = 0; $fail = 0;
echo "# P11.4.3 Role Grant Flow Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
