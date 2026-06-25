<?php
/**
 * MOGHARE360 ERP Auth Helper
 *
 * ERP-specific session validation only.
 *
 * Rules:
 * - No database connection
 * - No SQL
 * - No write operation
 * - No audit write
 * - No portal login dependency
 * - No secret display
 */

declare(strict_types=1);

function erp_auth_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * @return list<string>
 */
function erp_auth_session_keys(): array
{
    return [
        'erp_user_id',
        'erp_username',
        'erp_full_name',
        'erp_is_system_owner',
        'erp_roles',
        'erp_login_time',
        'erp_last_activity',
        'erp_session_token',
    ];
}

function erp_auth_is_logged_in(): bool
{
    erp_auth_start_session();

    if (!isset(
        $_SESSION['erp_user_id'],
        $_SESSION['erp_username'],
        $_SESSION['erp_full_name'],
        $_SESSION['erp_is_system_owner'],
        $_SESSION['erp_roles'],
        $_SESSION['erp_login_time'],
        $_SESSION['erp_last_activity'],
        $_SESSION['erp_session_token']
    )) {
        return false;
    }

    if (!is_int($_SESSION['erp_user_id']) || $_SESSION['erp_user_id'] < 1) {
        return false;
    }

    if (!is_string($_SESSION['erp_username']) || trim($_SESSION['erp_username']) === '') {
        return false;
    }

    if (!is_string($_SESSION['erp_full_name'])) {
        return false;
    }

    if (!is_bool($_SESSION['erp_is_system_owner']) || $_SESSION['erp_is_system_owner'] !== true) {
        return false;
    }

    if (!is_array($_SESSION['erp_roles'])) {
        return false;
    }

    if (!is_int($_SESSION['erp_login_time']) || $_SESSION['erp_login_time'] < 1) {
        return false;
    }

    if (!is_int($_SESSION['erp_last_activity']) || $_SESSION['erp_last_activity'] < 1) {
        return false;
    }

    if (!is_string($_SESSION['erp_session_token']) || trim($_SESSION['erp_session_token']) === '') {
        return false;
    }

    return true;
}

function erp_auth_require_login(): void
{
    if (erp_auth_is_logged_in()) {
        return;
    }

    header('Location: erp-admin-login.php');
    exit;
}

/**
 * @return array{
 *     user_id: int,
 *     username: string,
 *     full_name: string,
 *     is_system_owner: bool,
 *     roles: list<string>,
 *     login_time: int,
 *     last_activity: int
 * }|null
 */
function erp_auth_current_user(): ?array
{
    if (!erp_auth_is_logged_in()) {
        return null;
    }

    $roles = [];
    foreach ($_SESSION['erp_roles'] as $role) {
        if (is_string($role) && $role !== '') {
            $roles[] = $role;
        }
    }

    return [
        'user_id' => (int)$_SESSION['erp_user_id'],
        'username' => (string)$_SESSION['erp_username'],
        'full_name' => (string)$_SESSION['erp_full_name'],
        'is_system_owner' => (bool)$_SESSION['erp_is_system_owner'],
        'roles' => $roles,
        'login_time' => (int)$_SESSION['erp_login_time'],
        'last_activity' => (int)$_SESSION['erp_last_activity'],
    ];
}

function erp_auth_logout_keys(): void
{
    erp_auth_start_session();

    foreach (erp_auth_session_keys() as $key) {
        unset($_SESSION[$key]);
    }
}

function erp_auth_touch_activity(): void
{
    erp_auth_start_session();

    if (!erp_auth_is_logged_in()) {
        return;
    }

    $_SESSION['erp_last_activity'] = time();
}
