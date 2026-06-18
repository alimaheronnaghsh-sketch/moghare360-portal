<?php
/**
 * MOGHARE360 ERP Permission Helper
 *
 * Phase 1A session-role permission helper.
 * Safe output only.
 */

declare(strict_types=1);

require_once __DIR__ . '/erp-auth-helper.php';

function erp_permission_user_roles(): array
{
    erp_auth_start_session();

    if (!erp_auth_is_logged_in()) {
        return [];
    }

    $currentUser = erp_auth_current_user();
    $roles = $currentUser['roles'] ?? [];

    if (!is_array($roles)) {
        return [];
    }

    return array_values(array_filter(array_map(
        static function ($role): string {
            return trim((string) $role);
        },
        $roles
    )));
}

function erp_permission_has_role(string $role): bool
{
    $requiredRole = trim($role);

    if ($requiredRole === '') {
        return false;
    }

    return in_array($requiredRole, erp_permission_user_roles(), true);
}

function erp_permission_has_any_role(array $roles): bool
{
    foreach ($roles as $role) {
        if (erp_permission_has_role((string) $role)) {
            return true;
        }
    }

    return false;
}

function erp_permission_is_system_owner(): bool
{
    erp_auth_start_session();

    if (!erp_auth_is_logged_in()) {
        return false;
    }

    $currentUser = erp_auth_current_user();

    return ($currentUser['is_system_owner'] ?? false) === true;
}

function erp_permission_require_role(string $role): void
{
    erp_auth_require_login();

    if (!erp_permission_has_role($role)) {
        erp_permission_access_denied();
    }
}

function erp_permission_require_any_role(array $roles): void
{
    erp_auth_require_login();

    if (!erp_permission_has_any_role($roles)) {
        erp_permission_access_denied();
    }
}

function erp_permission_access_denied(): void
{
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'ERP access denied.';
    exit;
}
