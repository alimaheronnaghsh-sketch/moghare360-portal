<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p114s_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }
function p114s_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$frozen = [
    'staff-login.php',
    'owner-login.php',
    'staff-auth.php',
    'access-control.php',
    'api/auth/staff-login.php',
    'api/auth/owner-login.php',
];

$results = [];
foreach ($frozen as $f) {
    $path = $pub . '/' . str_replace('/', DIRECTORY_SEPARATOR, $f);
    $results[] = p114s_pass($f . ' still exists', is_file($path));
}

$helpers = p114s_read($pub . '/includes/m360-access-management-helper.php')
    . p114s_read($pub . '/includes/m360-access-user-helper.php')
    . p114s_read($pub . '/includes/m360-access-role-helper.php');

$results[] = p114s_pass('reuses erp-auth-context', str_contains($helpers, 'erp-auth-context') || str_contains(p114s_read($pub . '/includes/erp-customer-core-helper.php'), 'erp-auth-context'));
$results[] = p114s_pass('no new Auth architecture file', !is_file($pub . '/includes/m360-new-auth.php'));
$results[] = p114s_pass('no core_permissions UPDATE', !preg_match('/UPDATE\s+dbo\.core_permissions/i', $helpers));
$results[] = p114s_pass('no core_role_permissions UPDATE', !preg_match('/UPDATE\s+dbo\.core_role_permissions/i', $helpers));
$results[] = p114s_pass('no DROP/TRUNCATE/DELETE', !preg_match('/\b(DROP|TRUNCATE|DELETE\s+FROM)\b/i', $helpers));
$results[] = p114s_pass('no staff_users', !str_contains($helpers, 'staff_users'));
$results[] = p114s_pass('no P12 taxonomy in access UI', !preg_match('/\bP12\b/', $helpers . p114s_read($pub . '/erp-access-management.php')));
$results[] = p114s_pass('no payment/accounting scope', !preg_match('/payment_gateway|official_tax|bank_transfer/i', $helpers));

$pass = 0; $fail = 0;
echo "# P11.4 Access Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
