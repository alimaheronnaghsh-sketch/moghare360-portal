<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1142s_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p1142s_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$frozen = [
    'staff-login.php',
    'owner-login.php',
    'staff-auth.php',
    'access-control.php',
    'api/auth/staff-login.php',
    'api/auth/owner-login.php',
];

$changed = p1142s_read($pub . '/includes/m360-access-user-helper.php')
    . p1142s_read($pub . '/erp-access-user-create.php')
    . p1142s_read($pub . '/erp-access-user-edit.php')
    . p1142s_read($pub . '/assets/js/m360-access-position-filter.js');

$results = [];
foreach ($frozen as $f) {
    $path = $pub . '/' . str_replace('/', DIRECTORY_SEPARATOR, $f);
    $results[] = p1142s_pass($f . ' untouched (exists)', is_file($path));
}

$results[] = p1142s_pass('no new positions API endpoint required', !is_file($pub . '/api/access-management/positions-by-department.php'));
$results[] = p1142s_pass('no INSERT INTO core_positions', !preg_match('/INSERT\s+INTO\s+dbo\.core_positions/i', $changed));
$results[] = p1142s_pass('no UPDATE core_positions seed', !preg_match('/UPDATE\s+dbo\.core_positions/i', $changed));
$results[] = p1142s_pass('no DELETE core_positions', !preg_match('/DELETE\s+FROM\s+dbo\.core_positions/i', $changed));
$results[] = p1142s_pass('no ALTER TABLE', !preg_match('/\bALTER\s+TABLE\b/i', $changed));
$results[] = p1142s_pass('no core_roles mutation', !preg_match('/\b(INSERT|UPDATE|DELETE)\b[\s\S]*core_roles/i', $changed));
$results[] = p1142s_pass('no core_permissions mutation', !preg_match('/\b(INSERT|UPDATE|DELETE)\b[\s\S]*core_permissions/i', $changed));
$results[] = p1142s_pass('no core_role_permissions mutation', !preg_match('/\b(INSERT|UPDATE|DELETE)\b[\s\S]*core_role_permissions/i', $changed));
$results[] = p1142s_pass('no DROP/TRUNCATE', !preg_match('/\b(DROP|TRUNCATE)\b/i', $changed));
$results[] = p1142s_pass('no P12 scope', !preg_match('/\bP12\b/', $changed));
$results[] = p1142s_pass('no payment/accounting scope', !preg_match('/payment_gateway|official_tax|bank_transfer/i', $changed));
$results[] = p1142s_pass('docs UX report exists', is_file($root . '/docs/access/MOGHARE360_V1_POSITION_UX_FILTER_REPORT.md'));
$results[] = p1142s_pass('docs seed backlog exists', is_file($root . '/docs/access/MOGHARE360_V1_POSITION_SEED_CLEANUP_BACKLOG.md'));

$pass = 0; $fail = 0;
echo "# P11.4.2 Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
