<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p117s_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }
function p117s_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$changed = p117s_read($pub . '/includes/m360-staff-home-helper.php')
    . p117s_read($pub . '/erp-staff-home.php')
    . p117s_read($pub . '/assets/css/m360-staff-home.css');

$results = [];
$results[] = p117s_pass('staff home session redirect unchanged', str_contains($changed, "header('Location: staff-login.php')"));
$results[] = p117s_pass('owner-login.php not referenced for change', !str_contains($changed, 'owner-login.php'));
$results[] = p117s_pass('no ALTER TABLE', !preg_match('/\bALTER\s+TABLE\b/i', $changed));
$results[] = p117s_pass('no core_permissions mutation', !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\s+dbo\.core_permissions\b/i', $changed));
$results[] = p117s_pass('no core_roles mutation', !preg_match('/\b(INSERT|UPDATE|DELETE)\s+INTO\s+dbo\.core_roles\b/i', $changed));
$results[] = p117s_pass('no password_hash in staff home scope', !preg_match('/password_hash\s*\(/', $changed));
$results[] = p117s_pass('no P12 scope markers', !preg_match('/\bP12[_\s]/', $changed));
$results[] = p117s_pass('workbench groups present', str_contains($changed, 'M360_STAFF_HOME_GROUP_TODAY'));
$results[] = p117s_pass('backlog card type present', str_contains($changed, "'backlog'"));
$results[] = p117s_pass('action endpoints not direct nav', str_contains($changed, 'm360_staff_home_is_action_endpoint'));

$pass = 0; $fail = 0;
echo "# P11.7 Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
