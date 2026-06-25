<?php
/**
 * MOGHARE360 ERP Auth Helper Local Test
 *
 * CLI-only local test.
 * Safe output only.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/erp-auth-helper.php';

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

add_check($checks, 'H01', 'Auth helper loaded', function_exists('erp_auth_is_logged_in'));
add_check($checks, 'H02', 'ERP session keys count', count(erp_auth_session_keys()) === 8);
add_check($checks, 'H03', 'Logged out state detected', erp_auth_is_logged_in() === false);

$_SESSION['erp_user_id'] = 10001;
$_SESSION['erp_username'] = 'mahin.paradigm.owner';
$_SESSION['erp_full_name'] = 'MahinParadigmCo.';
$_SESSION['erp_is_system_owner'] = true;
$_SESSION['erp_roles'] = ['owner', 'system_admin'];
$_SESSION['erp_login_time'] = time();
$_SESSION['erp_last_activity'] = time();
$_SESSION['erp_session_token'] = bin2hex(random_bytes(16));

add_check($checks, 'H04', 'Logged in state detected', erp_auth_is_logged_in() === true);

$currentUser = erp_auth_current_user();

add_check($checks, 'H05', 'Current user returned as array', is_array($currentUser));
add_check($checks, 'H06', 'Current user id matches', ($currentUser['user_id'] ?? null) === 10001);
add_check($checks, 'H07', 'Current username matches', ($currentUser['username'] ?? null) === 'mahin.paradigm.owner');
add_check($checks, 'H08', 'Session token not exposed in current user', !array_key_exists('session_token', $currentUser));

$oldActivity = $_SESSION['erp_last_activity'];
sleep(1);
erp_auth_touch_activity();

add_check($checks, 'H09', 'Last activity updated', $_SESSION['erp_last_activity'] > $oldActivity);

erp_auth_logout_keys();

add_check($checks, 'H10', 'ERP logout keys cleared', erp_auth_is_logged_in() === false);

$allOk = true;

echo "MOGHARE360 ERP Auth Helper Local Test\n";
echo "=====================================\n";

foreach ($checks as $check) {
    if ($check['result'] !== 'OK') {
        $allOk = false;
    }

    echo $check['code'] . "\t" . $check['name'] . "\t" . $check['result'] . PHP_EOL;
}

echo "=====================================\n";
echo "Overall Status\t" . ($allOk ? 'OK' : 'FAIL') . PHP_EOL;

exit($allOk ? 0 : 1);
