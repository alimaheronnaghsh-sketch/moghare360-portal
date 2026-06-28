<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1144o_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p1144o_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$login = p1144o_read($pub . '/owner-login.php');
$api = p1144o_read($pub . '/api/auth/owner-login.php');
$helper = p1144o_read($pub . '/includes/m360-staff-home-helper.php');

$results = [];
$results[] = p1144o_pass('owner-login redirects after success', str_contains($login, 'm360_owner_login_redirect_after_success'));
$results[] = p1144o_pass('redirect uses header Location', str_contains($helper, "header('Location:"));
$results[] = p1144o_pass('API includes redirect_url', str_contains($api, "'redirect_url' => 'erp-product-home.php'"));
$results[] = p1144o_pass('primary redirect constant', str_contains($helper, "M360_OWNER_LOGIN_REDIRECT_PRIMARY = 'erp-product-home.php'"));
$results[] = p1144o_pass('fallback redirect paths documented', str_contains($helper, 'erp-owner-control-center.php') && str_contains($helper, 'erp-management-dashboard.php'));
$results[] = p1144o_pass('resolve redirect helper exists', str_contains($helper, 'm360_owner_login_resolve_redirect_path'));
$results[] = p1144o_pass('redirect path is relative', !preg_match('/localhost|moghareh360\.ir|\d{1,3}(?:\.\d{1,3}){3}/i', $login . $api . $helper));
$results[] = p1144o_pass('no hardcoded absolute URL', !str_contains($login, 'http://') && !str_contains($login, 'https://'));
$results[] = p1144o_pass('failure path still renders form', str_contains($login, 'm360-alert-error'));

$pass = 0; $fail = 0;
echo "# P11.4.4 Owner Login Redirect Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
