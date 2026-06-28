<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1144r_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p1144r_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$login = p1144r_read($pub . '/staff-login.php');
$api = p1144r_read($pub . '/api/auth/staff-login.php');
$helper = p1144r_read($pub . '/includes/m360-staff-home-helper.php');

$results = [];
$results[] = p1144r_pass('staff-login redirects on success', str_contains($login, "header('Location:") && (str_contains($login, 'erp-staff-home') || str_contains($login, 'M360_STAFF_HOME_REDIRECT_PATH')));
$results[] = p1144r_pass('staff-login uses M360_STAFF_HOME_REDIRECT_PATH', str_contains($login, 'M360_STAFF_HOME_REDIRECT_PATH'));
$results[] = p1144r_pass('staff-login syncs session from payload', str_contains($login, 'm360_staff_home_sync_session_from_login_payload'));
$results[] = p1144r_pass('API returns redirect_url', str_contains($api, "'redirect_url' => 'erp-staff-home.php'"));
$results[] = p1144r_pass('redirect path is relative', !preg_match('/localhost|moghareh360\.ir|\d{1,3}(?:\.\d{1,3}){3}/i', $login . $api));
$results[] = p1144r_pass('no hardcoded absolute URL in redirect', !str_contains($login, 'http://') && !str_contains($login, 'https://'));
$results[] = p1144r_pass('failure path still renders form', str_contains($login, 'm360-alert-error'));
$results[] = p1144r_pass('helper defines redirect constant', str_contains($helper, "M360_STAFF_HOME_REDIRECT_PATH = 'erp-staff-home.php'"));

$pass = 0; $fail = 0;
echo "# P11.4.4 Staff Login Redirect Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
