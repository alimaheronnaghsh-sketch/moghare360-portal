<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1144os_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p1144os_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$login = p1144os_read($pub . '/owner-login.php');
$api = p1144os_read($pub . '/api/auth/owner-login.php');
$helper = p1144os_read($pub . '/includes/m360-staff-home-helper.php');

$results = [];
$results[] = p1144os_pass('owner-login syncs via owner helper', str_contains($login, 'm360_owner_login_sync_session_from_login_payload') || str_contains($helper, 'm360_owner_login_sync_session_from_login_payload') && str_contains($login, 'm360_owner_login_redirect_after_success'));
$results[] = p1144os_pass('reuses staff session sync', str_contains($helper, 'm360_staff_home_sync_session_from_login_payload'));
$results[] = p1144os_pass('sets erp_user_id', str_contains($helper, "\$_SESSION['erp_user_id']"));
$results[] = p1144os_pass('sets erp_username', str_contains($helper, "\$_SESSION['erp_username']"));
$results[] = p1144os_pass('sets erp_company_id', str_contains($helper, "\$_SESSION['erp_company_id']"));
$results[] = p1144os_pass('sets erp_is_owner for owner login', str_contains($helper, "\$_SESSION['erp_is_owner'] = 1"));
$results[] = p1144os_pass('API validates is_system_owner', str_contains($api, 'is_system_owner = 1'));
$results[] = p1144os_pass('API validates password_verify', str_contains($api, 'password_verify('));
$results[] = p1144os_pass('API validates lifecycle ACTIVE', str_contains($api, "lifecycle_state") && str_contains($api, 'ACTIVE'));
$results[] = p1144os_pass('API validates is_login_enabled', str_contains($api, 'is_login_enabled'));
$results[] = p1144os_pass('no invented session keys', !preg_match("/\$_SESSION\['m360_/", $helper . $login));

$pass = 0; $fail = 0;
echo "# P11.4.4 Owner Session Sync Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
