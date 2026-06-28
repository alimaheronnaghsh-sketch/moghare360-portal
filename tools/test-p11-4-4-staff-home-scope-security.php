<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1144s_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }
function p1144s_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$frozen = [
    'staff-auth.php',
    'owner-login.php',
    'access-control.php',
    'api/auth/owner-login.php',
];

$changed = p1144s_read($pub . '/staff-login.php')
    . p1144s_read($pub . '/api/auth/staff-login.php')
    . p1144s_read($pub . '/includes/m360-staff-home-helper.php')
    . p1144s_read($pub . '/erp-staff-home.php')
    . p1144s_read($pub . '/staff-logout.php');

$api = p1144s_read($pub . '/api/auth/staff-login.php');

$results = [];
foreach ($frozen as $f) {
    $results[] = p1144s_pass($f . ' untouched path exists', is_file($pub . '/' . str_replace('/', DIRECTORY_SEPARATOR, $f)));
}

$results[] = p1144s_pass('password_verify still in staff-login API', str_contains($api, 'password_verify('));
$results[] = p1144s_pass('no password rule change markers', !preg_match('/PASSWORD_BCRYPT|password_hash\s*\(/', $changed));
$results[] = p1144s_pass('no core_permissions mutation', !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\s+dbo\.core_permissions\b/i', $changed));
$results[] = p1144s_pass('no core_roles seed mutation', !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\s+dbo\.core_roles\b/i', $changed));
$results[] = p1144s_pass('no ALTER TABLE', !preg_match('/\bALTER\s+TABLE\b/i', $changed));
$results[] = p1144s_pass('no P12 scope', !preg_match('/\bP12\b/', $changed));
$results[] = p1144s_pass('no fake login bypass', !preg_match('/fake.*login|bypass.*auth/i', $changed));
$results[] = p1144s_pass('session keys unchanged contract', str_contains($changed, 'erp_user_id') && str_contains($changed, 'erp_username'));

$pass = 0; $fail = 0;
echo "# P11.4.4 Staff Home Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
