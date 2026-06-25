<?php
/**
 * MOGHARE360 ERP Audit Helper Local Test
 *
 * CLI-only local test.
 * Safe output only.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/erp-audit-helper.php';

$checks = [];

function add_check(array &$checks, string $code, string $name, bool $ok): void
{
    $checks[] = [
        'code' => $code,
        'name' => $name,
        'result' => $ok ? 'OK' : 'FAIL',
    ];
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
}

session_id('moghare360_audit_test_' . bin2hex(random_bytes(8)));

erp_auth_start_session();

foreach (erp_auth_session_keys() as $key) {
    unset($_SESSION[$key]);
}

$_SESSION = [];

add_check($checks, 'A01', 'Audit helper loaded', function_exists('erp_audit_write'));
add_check($checks, 'A02', 'Audit sanitize function loaded', function_exists('erp_audit_sanitize_string'));
add_check($checks, 'A03', 'Audit safe json function loaded', function_exists('erp_audit_safe_json'));

$loggedOutActor = erp_audit_safe_actor();

add_check($checks, 'A04', 'Logged out actor user id is null', array_key_exists('actor_user_id', $loggedOutActor) && $loggedOutActor['actor_user_id'] === null);
add_check($checks, 'A05', 'Logged out actor username is null', array_key_exists('actor_username', $loggedOutActor) && $loggedOutActor['actor_username'] === null);

$safeJson = erp_audit_safe_json([
    'safe_key' => 'safe_value',
    'password' => 'must_not_be_stored',
    'password_hash' => 'must_not_be_stored',
    'erp_session_token' => 'must_not_be_stored',
    'sql_error' => 'must_not_be_stored',
]);

add_check($checks, 'A06', 'Safe JSON created', is_string($safeJson) && $safeJson !== '');
add_check($checks, 'A07', 'Unsafe password key filtered', is_string($safeJson) && strpos($safeJson, 'password') === false);
add_check($checks, 'A08', 'Unsafe token key filtered', is_string($safeJson) && strpos($safeJson, 'session_token') === false);
add_check($checks, 'A09', 'Unsafe SQL error key filtered', is_string($safeJson) && strpos($safeJson, 'sql_error') === false);

$loggedOutWrite = erp_audit_write([
    'action' => 'ERP_AUDIT_TEST',
    'entity_type' => 'ERP_AUDIT',
    'details' => [
        'test_scope' => 'logged_out_safe_insert',
        'result' => 'expected_success',
    ],
]);

add_check($checks, 'A10', 'Logged out audit test insert succeeded', $loggedOutWrite === true);

$_SESSION['erp_user_id'] = 10001;
$_SESSION['erp_username'] = 'mahin.paradigm.owner';
$_SESSION['erp_full_name'] = 'MahinParadigmCo.';
$_SESSION['erp_is_system_owner'] = true;
$_SESSION['erp_roles'] = ['owner', 'system_admin'];
$_SESSION['erp_login_time'] = time();
$_SESSION['erp_last_activity'] = time();
$_SESSION['erp_session_token'] = bin2hex(random_bytes(16));

$loggedInActor = erp_audit_safe_actor();

add_check($checks, 'A11', 'Logged in actor user id detected', ($loggedInActor['actor_user_id'] ?? null) === 10001);
add_check($checks, 'A12', 'Logged in actor username detected', ($loggedInActor['actor_username'] ?? null) === 'mahin.paradigm.owner');

$loggedInWrite = erp_audit_write([
    'action' => 'ERP_AUDIT_TEST',
    'entity_type' => 'ERP_AUDIT',
    'entity_id' => 10001,
    'details' => [
        'test_scope' => 'logged_in_safe_insert',
        'result' => 'expected_success',
        'actor_check' => 'platform_owner',
    ],
]);

add_check($checks, 'A13', 'Logged in audit test insert succeeded', $loggedInWrite === true);

$loginSuccessWrite = erp_audit_login_success(10001, 'mahin.paradigm.owner');

add_check($checks, 'A14', 'Login success audit helper insert succeeded', $loginSuccessWrite === true);

$loginFailureWrite = erp_audit_login_failure('invalid.test.user');

add_check($checks, 'A15', 'Login failure audit helper insert succeeded', $loginFailureWrite === true);

$allOk = true;

echo "MOGHARE360 ERP Audit Helper Local Test\n";
echo "======================================\n";

foreach ($checks as $check) {
    if ($check['result'] !== 'OK') {
        $allOk = false;
    }

    echo $check['code'] . "\t" . $check['name'] . "\t" . $check['result'] . PHP_EOL;
}

echo "======================================\n";
echo "Overall Status\t" . ($allOk ? 'OK' : 'FAIL') . PHP_EOL;

exit($allOk ? 0 : 1);
