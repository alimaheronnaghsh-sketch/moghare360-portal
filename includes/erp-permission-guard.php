<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Permission Guard Helper
 *
 * Mission 10 - Permission Guard Helper Implementation
 *
 * Read-only guard evaluation only.
 * Does not execute actions, change workflow state, or perform database writes.
 */

if (!function_exists('erp_auth_can')) {
    $authContextPath = __DIR__ . DIRECTORY_SEPARATOR . 'erp-auth-context.php';

    if (is_file($authContextPath)) {
        require_once $authContextPath;
    }
}

if (!function_exists('erp_guard_action_map')) {
    /**
     * @return array<string, array<string, mixed>>
     */
    function erp_guard_action_map(): array
    {
        return [
            'access.request.view' => [
                'action_key' => 'access.request.view',
                'mode' => 'read',
                'required_permission' => 'access.request.view_all',
                'csrf_required' => false,
                'audit_required' => false,
                'workflow_state_required' => false,
                'notes' => 'Read-only single access request view.',
            ],
            'access.request.list' => [
                'action_key' => 'access.request.list',
                'mode' => 'read',
                'required_permission' => 'access.request.view_all',
                'csrf_required' => false,
                'audit_required' => false,
                'workflow_state_required' => false,
                'notes' => 'Read-only access request list view.',
            ],
            'access.request.submit' => [
                'action_key' => 'access.request.submit',
                'mode' => 'write',
                'required_permission' => 'access.request.create',
                'csrf_required' => true,
                'audit_required' => true,
                'workflow_state_required' => true,
                'notes' => 'Submit uses access.request.create per Mission 9 naming alignment.',
            ],
            'access.request.review' => [
                'action_key' => 'access.request.review',
                'mode' => 'write',
                'required_permission' => 'access.request.approve',
                'csrf_required' => true,
                'audit_required' => true,
                'workflow_state_required' => true,
                'notes' => 'Review transition requires approve permission.',
            ],
            'access.request.approve' => [
                'action_key' => 'access.request.approve',
                'mode' => 'write',
                'required_permission' => 'access.request.approve',
                'csrf_required' => true,
                'audit_required' => true,
                'workflow_state_required' => true,
                'notes' => 'Approve transition guard evaluation only.',
            ],
            'access.request.apply' => [
                'action_key' => 'access.request.apply',
                'mode' => 'write',
                'required_permission' => 'access.request.apply',
                'csrf_required' => true,
                'audit_required' => true,
                'workflow_state_required' => true,
                'notes' => 'Apply transition guard evaluation only.',
            ],
            'admin.dashboard.view' => [
                'action_key' => 'admin.dashboard.view',
                'mode' => 'read',
                'required_permission' => 'placeholder_admin_dashboard_view',
                'csrf_required' => false,
                'audit_required' => false,
                'workflow_state_required' => false,
                'notes' => 'Placeholder permission - documented only, not production enforced.',
            ],
            'admin.workflow.viewer.view' => [
                'action_key' => 'admin.workflow.viewer.view',
                'mode' => 'read',
                'required_permission' => 'access.request.view_all',
                'csrf_required' => false,
                'audit_required' => false,
                'workflow_state_required' => false,
                'notes' => 'Read-only workflow viewer mapped to access.request.view_all.',
            ],
            'admin.auth.context.test.view' => [
                'action_key' => 'admin.auth.context.test.view',
                'mode' => 'read',
                'required_permission' => 'placeholder_admin_auth_context_test_view',
                'csrf_required' => false,
                'audit_required' => false,
                'workflow_state_required' => false,
                'notes' => 'Placeholder permission - documented only, not production enforced.',
            ],
        ];
    }
}

if (!function_exists('erp_guard_is_placeholder_permission')) {
    function erp_guard_is_placeholder_permission(string $permissionKey): bool
    {
        return str_starts_with(trim($permissionKey), 'placeholder_');
    }
}

if (!function_exists('erp_guard_can')) {
    /**
     * @param resource $db
     */
    function erp_guard_can($db, int $userId, string $permissionKey): bool
    {
        $permissionKey = trim($permissionKey);

        if ($userId <= 0 || $permissionKey === '') {
            return false;
        }

        if (erp_guard_is_placeholder_permission($permissionKey)) {
            return false;
        }

        if (!function_exists('erp_auth_can')) {
            return false;
        }

        return erp_auth_can($db, $userId, $permissionKey);
    }
}

if (!function_exists('erp_guard_action')) {
    /**
     * @param resource $db
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    function erp_guard_action($db, int $userId, string $actionKey, array $context = []): array
    {
        $actionKey = trim($actionKey);
        $map = erp_guard_action_map();

        if ($actionKey === '' || !isset($map[$actionKey])) {
            return [
                'allowed' => false,
                'action_key' => $actionKey,
                'required_permission' => '',
                'reason' => 'Unknown action key.',
                'mode' => '',
                'csrf_required' => false,
                'audit_required' => false,
                'workflow_state_required' => false,
                'read_only_evaluation' => true,
            ];
        }

        $definition = $map[$actionKey];
        $requiredPermission = (string)($definition['required_permission'] ?? '');

        if (erp_guard_is_placeholder_permission($requiredPermission)) {
            return [
                'allowed' => false,
                'action_key' => $actionKey,
                'required_permission' => $requiredPermission,
                'reason' => 'Placeholder permission - documented only, not production enforced.',
                'mode' => (string)($definition['mode'] ?? ''),
                'csrf_required' => !empty($definition['csrf_required']),
                'audit_required' => !empty($definition['audit_required']),
                'workflow_state_required' => !empty($definition['workflow_state_required']),
                'read_only_evaluation' => true,
                'placeholder' => true,
            ];
        }

        $allowed = erp_guard_can($db, $userId, $requiredPermission);

        return [
            'allowed' => $allowed,
            'action_key' => $actionKey,
            'required_permission' => $requiredPermission,
            'reason' => $allowed ? 'Permission granted.' : 'Permission denied.',
            'mode' => (string)($definition['mode'] ?? ''),
            'csrf_required' => !empty($definition['csrf_required']),
            'audit_required' => !empty($definition['audit_required']),
            'workflow_state_required' => !empty($definition['workflow_state_required']),
            'read_only_evaluation' => true,
        ];
    }
}

if (!function_exists('erp_guard_require')) {
    /**
     * @param resource $db
     * @param array<string, mixed> $context
     */
    function erp_guard_require($db, int $userId, string $actionKey, array $context = []): void
    {
        $result = erp_guard_action($db, $userId, $actionKey, $context);

        if (!empty($result['placeholder'])) {
            throw new RuntimeException('Action requires placeholder permission not yet enforced in production.');
        }

        if (empty($result['allowed'])) {
            $reason = (string)($result['reason'] ?? 'Permission denied.');

            throw new RuntimeException('Permission guard denied action: ' . $actionKey . ' (' . $reason . ')');
        }
    }
}

if (!function_exists('erp_guard_denied_response')) {
    /**
     * @return array<string, mixed>
     */
    function erp_guard_denied_response(string $actionKey, string $reason): array
    {
        return [
            'allowed' => false,
            'action_key' => trim($actionKey),
            'message' => 'Access denied. You do not have permission to perform this action.',
            'reason' => trim($reason) !== '' ? trim($reason) : 'Permission denied.',
            'read_only_evaluation' => true,
        ];
    }
}
