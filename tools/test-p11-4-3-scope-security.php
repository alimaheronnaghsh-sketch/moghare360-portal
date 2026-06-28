<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1143s_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }
function p1143s_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$frozen = [
    'staff-login.php',
    'owner-login.php',
    'staff-auth.php',
    'access-control.php',
    'api/auth/staff-login.php',
    'api/auth/owner-login.php',
];

$changed = p1143s_read($pub . '/includes/m360-access-audit-helper.php')
    . p1143s_read($pub . '/includes/m360-access-role-helper.php')
    . p1143s_read($pub . '/includes/m360-access-user-helper.php')
    . p1143s_read($pub . '/includes/m360-access-management-helper.php');

$results = [];
foreach ($frozen as $f) {
    $path = $pub . '/' . str_replace('/', DIRECTORY_SEPARATOR, $f);
    $results[] = p1143s_pass($f . ' still exists', is_file($path));
}

$results[] = p1143s_pass('no ALTER TABLE in changed files', !preg_match('/\bALTER\s+TABLE\b/i', $changed));
$results[] = p1143s_pass('no core_permissions mutation', !preg_match('/\b(INSERT|UPDATE|DELETE)\b[\s\S]*core_permissions/i', $changed));
$results[] = p1143s_pass('no core_role_permissions mutation', !preg_match('/\b(INSERT|UPDATE|DELETE)\b[\s\S]*core_role_permissions/i', $changed));
$results[] = p1143s_pass('no core_roles seed mutation', !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\s+dbo\.core_roles\b/i', $changed));
$results[] = p1143s_pass('no DROP/TRUNCATE/DELETE users', !preg_match('/\b(DROP|TRUNCATE|DELETE\s+FROM\s+dbo\.core_users)\b/i', $changed));
$results[] = p1143s_pass('no P12 scope', !preg_match('/\bP12\b/', $changed));
$results[] = p1143s_pass('granted_by_request_id still required', str_contains($changed, 'granted_by_request_id'));

$pass = 0; $fail = 0;
echo "# P11.4.3 Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
