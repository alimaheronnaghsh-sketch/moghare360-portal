<?php
/**
 * MOGHARE360 ERP Permission Helper Local Test
 *
 * CLI-only local test.
 * Safe output only.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/erp-permission-helper.php';

$checks = [];

function add_check(array &$checks, string $code, string $name, bool $ok): void
{
    $checks[] = [
        'code' => $code,
        'name' => $name,
        'result' => $ok ? 'OK' : 'FAIL',
    ];
}

erp_auth_start_session();

foreach (erp_auth_session_keys() as $key) {
    unset($_SESSION[$key]);
}

add_check($checks, 'P01', 'Permission helper loaded', function_exists('erp_permission_has_role'));
add_check($checks, 'P02', 'Logged out roles empty', erp_permission_user_roles() === []);
add_check($checks, 'P03', 'Logged out owner false', erp_permission_is_system_owner() === false);
add_check($checks, 'P04', 'Logged out role check false', erp_permission_has_role('owner') === false);

$_SESSION['erp_user_id'] = 10001;
$_SESSION['erp_username'] = 'mahin.paradigm.owner';
$_SESSION['erp_full_name'] = 'MahinParadigmCo.';
$_SESSION['erp_is_system_owner'] = true;
$_SESSION['erp_roles'] = ['owner', 'system_admin'];
$_SESSION['erp_login_time'] = time();
$_SESSION['erp_last_activity'] = time();
$_SESSION['erp_session_token'] = bin2hex(random_bytes(16));

add_check($checks, 'P05', 'Logged in state detected', erp_auth_is_logged_in() === true);
add_check($checks, 'P06', 'Owner role detected', erp_permission_has_role('owner') === true);
add_check($checks, 'P07', 'System admin role detected', erp_permission_has_role('system_admin') === true);
add_check($checks, 'P08', 'Missing role returns false', erp_permission_has_role('tenant_admin') === false);
add_check($checks, 'P09', 'Any role matching returns true', erp_permission_has_any_role(['tenant_admin', 'owner']) === true);
add_check($checks, 'P10', 'Any role non-matching returns false', erp_permission_has_any_role(['tenant_admin', 'service_manager']) === false);
add_check($checks, 'P11', 'System owner detected', erp_permission_is_system_owner() === true);

$_SESSION['erp_roles'] = ['system_admin'];
$_SESSION['erp_is_system_owner'] = true;

add_check($checks, 'P12', 'Owner removed returns false', erp_permission_has_role('owner') === false);
add_check($checks, 'P13', 'System admin remains true within owner-only auth boundary', erp_permission_has_role('system_admin') === true);
add_check($checks, 'P14', 'System owner remains true within current auth boundary', erp_permission_is_system_owner() === true);

erp_auth_logout_keys();

add_check($checks, 'P15', 'ERP logout keys cleared', erp_auth_is_logged_in() === false);
add_check($checks, 'P16', 'Roles empty after logout', erp_permission_user_roles() === []);

$allOk = true;

echo "MOGHARE360 ERP Permission Helper Local Test\n";
echo "===========================================\n";

foreach ($checks as $check) {
    if ($check['result'] !== 'OK') {
        $allOk = false;
    }

    echo $check['code'] . "\t" . $check['name'] . "\t" . $check['result'] . PHP_EOL;
}

echo "===========================================\n";
echo "Overall Status\t" . ($allOk ? 'OK' : 'FAIL') . PHP_EOL;

exit($allOk ? 0 : 1);
