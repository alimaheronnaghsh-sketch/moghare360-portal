<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1144osc_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }
function p1144osc_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$changed = p1144osc_read($pub . '/owner-login.php')
    . p1144osc_read($pub . '/api/auth/owner-login.php')
    . p1144osc_read($pub . '/includes/m360-staff-home-helper.php');

$api = p1144osc_read($pub . '/api/auth/owner-login.php');
$staffLogin = p1144osc_read($pub . '/staff-login.php');
$staffApi = p1144osc_read($pub . '/api/auth/staff-login.php');

$results = [];
$results[] = p1144osc_pass('password_verify unchanged in owner API', str_contains($api, 'password_verify('));
$results[] = p1144osc_pass('no password_hash generation in bridge', !preg_match('/password_hash\s*\(/', $changed));
$results[] = p1144osc_pass('no core_permissions mutation', !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\s+dbo\.core_permissions\b/i', $changed));
$results[] = p1144osc_pass('no core_roles seed mutation', !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\s+dbo\.core_roles\b/i', $changed));
$results[] = p1144osc_pass('no ALTER TABLE', !preg_match('/\bALTER\s+TABLE\b/i', $changed));
$results[] = p1144osc_pass('no P12 scope', !preg_match('/\bP12\b/', $changed));
$results[] = p1144osc_pass('no fake owner login bypass', !preg_match('/fake.*owner|bypass.*owner|skip.*password/i', $changed));
$results[] = p1144osc_pass('owner still requires is_system_owner query', str_contains($api, 'is_system_owner = 1'));
$results[] = p1144osc_pass('staff-login unchanged redirect path', str_contains($staffLogin, 'M360_STAFF_HOME_REDIRECT_PATH'));
$results[] = p1144osc_pass('staff API redirect unchanged', str_contains($staffApi, "'redirect_url' => 'erp-staff-home.php'"));

$pass = 0; $fail = 0;
echo "# P11.4.4 Owner Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
