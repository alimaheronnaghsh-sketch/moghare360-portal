<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Auth Context Helper
 *
 * Phase 2 controlled prototype helper.
 *
 * This file is intentionally isolated from legacy login files.
 * It does not replace staff-auth.php.
 * It does not modify users, roles, permissions, tenants, workflow state, or database schema.
 * It does not perform database writes.
 */

if (!function_exists('erp_auth_start_session_if_needed')) {
    function erp_auth_start_session_if_needed(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('erp_auth_get_current_context')) {
    /**
     * Return temporary ERP auth context for the controlled prototype.
     *
     * Temporary prototype actor:
     * - user_id: 10001
     * - username: mahin.paradigm.owner
     *
     * This is not a production login implementation.
     *
     * @return array<string, mixed>
     */
    function erp_auth_get_current_context(): array
    {
        erp_auth_start_session_if_needed();

        if (!isset($_SESSION['erp_auth_context']) || !is_array($_SESSION['erp_auth_context'])) {
            $_SESSION['erp_auth_context'] = [
                'current_user_id' => 10001,
                'username' => 'mahin.paradigm.owner',
                'full_name' => 'MahinParadigmCo.',
                'is_system_owner' => true,
                'active_roles' => ['owner', 'system_admin'],
                'active_permissions' => [],
                'tenant_context' => 'PLATFORM_DEFAULT',
                'is_prototype_context' => true,
            ];
        }

        return $_SESSION['erp_auth_context'];
    }
}

if (!function_exists('erp_auth_require_current_user')) {
    /**
     * Require current ERP prototype user context.
     *
     * @return array<string, mixed>
     */
    function erp_auth_require_current_user(): array
    {
        $context = erp_auth_get_current_context();

        if (
            !isset($context['current_user_id'])
            || !is_int($context['current_user_id'])
            || $context['current_user_id'] <= 0
        ) {
            throw new RuntimeException('ERP auth context is missing current_user_id.');
        }

        return $context;
    }
}

if (!function_exists('erp_auth_is_system_owner')) {
    /**
     * @param array<string, mixed> $context
     */
    function erp_auth_is_system_owner(array $context): bool
    {
        return isset($context['is_system_owner']) && $context['is_system_owner'] === true;
    }
}

if (!function_exists('erp_auth_get_user_roles')) {
    /**
     * @param array<string, mixed> $context
     * @return list<string>
     */
    function erp_auth_get_user_roles(array $context): array
    {
        if (!isset($context['active_roles']) || !is_array($context['active_roles'])) {
            return [];
        }

        return array_values(array_filter(
            $context['active_roles'],
            static fn ($role): bool => is_string($role) && $role !== ''
        ));
    }
}

if (!function_exists('erp_auth_get_user_permissions')) {
    /**
     * @param array<string, mixed> $context
     * @return list<string>
     */
    function erp_auth_get_user_permissions(array $context): array
    {
        if (!isset($context['active_permissions']) || !is_array($context['active_permissions'])) {
            return [];
        }

        return array_values(array_filter(
            $context['active_permissions'],
            static fn ($permission): bool => is_string($permission) && $permission !== ''
        ));
    }
}
