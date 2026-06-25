<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Permission Check Helper
 *
 * Phase 2 controlled prototype helper.
 *
 * This file provides isolated permission validation from ERP auth context arrays.
 * It does not replace login.
 * It does not connect to database.
 * It does not create users, roles, permissions, tenants, workflow state, or database schema.
 * It does not perform database writes.
 *
 * Temporary prototype rule:
 * Platform Owner user_id 10001 may pass permission checks during controlled prototype only.
 * This is not production authorization.
 */

if (!function_exists('erp_permission_normalize_key')) {
    function erp_permission_normalize_key(string $permission_key): string
    {
        $permission_key = trim($permission_key);

        if ($permission_key === '') {
            throw new RuntimeException('ERP permission key is required.');
        }

        return $permission_key;
    }
}

if (!function_exists('erp_permission_context_has_role')) {
    function erp_permission_context_has_role(array $context, string $role_key): bool
    {
        $role_key = trim($role_key);

        if ($role_key === '') {
            return false;
        }

        $roles = [];

        if (isset($context['active_roles']) && is_array($context['active_roles'])) {
            $roles = $context['active_roles'];
        } elseif (isset($context['roles']) && is_array($context['roles'])) {
            $roles = $context['roles'];
        }

        foreach ($roles as $role) {
            if (is_string($role) && trim($role) === $role_key) {
                return true;
            }

            if (is_array($role)) {
                foreach (['role_key', 'role_code', 'code', 'name'] as $key) {
                    if (isset($role[$key]) && is_string($role[$key]) && trim($role[$key]) === $role_key) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}

if (!function_exists('erp_permission_context_has_permission')) {
    function erp_permission_context_has_permission(array $context, string $permission_key): bool
    {
        $permission_key = erp_permission_normalize_key($permission_key);

        $permissions = [];

        if (isset($context['active_permissions']) && is_array($context['active_permissions'])) {
            $permissions = $context['active_permissions'];
        } elseif (isset($context['permissions']) && is_array($context['permissions'])) {
            $permissions = $context['permissions'];
        }

        foreach ($permissions as $permission) {
            if (is_string($permission) && trim($permission) === $permission_key) {
                return true;
            }

            if (is_array($permission)) {
                foreach (['permission_key', 'permission_code', 'code', 'name'] as $key) {
                    if (
                        isset($permission[$key])
                        && is_string($permission[$key])
                        && trim($permission[$key]) === $permission_key
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}

if (!function_exists('erp_permission_is_platform_owner_prototype')) {
    function erp_permission_is_platform_owner_prototype(array $context): bool
    {
        $user_id = $context['user_id'] ?? $context['current_user_id'] ?? null;
        $username = $context['username'] ?? '';

        $is_platform_owner_user = ((string)$user_id === '10001')
            && is_string($username)
            && trim($username) === 'mahin.paradigm.owner';

        if (!$is_platform_owner_user) {
            return false;
        }

        return erp_permission_context_has_role($context, 'owner')
            || erp_permission_context_has_role($context, 'system_admin');
    }
}

if (!function_exists('erp_permission_user_has')) {
    function erp_permission_user_has(array $context, string $permission_key): bool
    {
        $permission_key = erp_permission_normalize_key($permission_key);

        $user_id = $context['user_id'] ?? $context['current_user_id'] ?? null;

        if ($user_id === null || trim((string)$user_id) === '') {
            return false;
        }

        if (erp_permission_context_has_permission($context, $permission_key)) {
            return true;
        }

        if (erp_permission_is_platform_owner_prototype($context)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('erp_permission_require')) {
    function erp_permission_require(array $context, string $permission_key): void
    {
        $permission_key = erp_permission_normalize_key($permission_key);

        if (!erp_permission_user_has($context, $permission_key)) {
            throw new RuntimeException('ERP permission denied: ' . $permission_key);
        }
    }
}
