<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Workflow Engine Helper
 *
 * Phase 2 controlled prototype helper.
 *
 * This file provides isolated workflow transition validation only.
 * It does not replace login.
 * It does not connect to database.
 * It does not create users, roles, permissions, tenants, workflow state, or database schema.
 * It does not perform database writes.
 * It does not update workflow state.
 *
 * Transitions:
 * Entity: access_request
 * - DRAFT -> SUBMITTED
 * - SUBMITTED -> UNDER_REVIEW
 * - UNDER_REVIEW -> APPROVED
 */

if (!function_exists('erp_workflow_normalize_value')) {
    function erp_workflow_normalize_value(string $value, string $label): string
    {
        $value = trim($value);
        $label = trim($label);

        if ($label === '') {
            $label = 'workflow value';
        }

        if ($value === '') {
            throw new RuntimeException('ERP ' . $label . ' is required.');
        }

        return $value;
    }
}

if (!function_exists('erp_workflow_get_allowed_transitions')) {
    function erp_workflow_get_allowed_transitions(): array
    {
        return [
            'access_request' => [
                'DRAFT' => [
                    'SUBMITTED',
                ],
                'SUBMITTED' => [
                    'UNDER_REVIEW',
                ],
                'UNDER_REVIEW' => [
                    'APPROVED',
                ],
            ],
        ];
    }
}

if (!function_exists('erp_workflow_can_transition')) {
    function erp_workflow_can_transition(string $entity, string $from_state, string $to_state): bool
    {
        $entity = strtolower(erp_workflow_normalize_value($entity, 'workflow entity'));
        $from_state = strtoupper(erp_workflow_normalize_value($from_state, 'current workflow state'));
        $to_state = strtoupper(erp_workflow_normalize_value($to_state, 'requested workflow state'));

        $allowed_transitions = erp_workflow_get_allowed_transitions();

        if (!isset($allowed_transitions[$entity]) || !is_array($allowed_transitions[$entity])) {
            return false;
        }

        if (
            !isset($allowed_transitions[$entity][$from_state])
            || !is_array($allowed_transitions[$entity][$from_state])
        ) {
            return false;
        }

        foreach ($allowed_transitions[$entity][$from_state] as $allowed_to_state) {
            if (is_string($allowed_to_state) && strtoupper(trim($allowed_to_state)) === $to_state) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('erp_workflow_require_transition')) {
    function erp_workflow_require_transition(string $entity, string $from_state, string $to_state): void
    {
        $entity = strtolower(erp_workflow_normalize_value($entity, 'workflow entity'));
        $from_state = strtoupper(erp_workflow_normalize_value($from_state, 'current workflow state'));
        $to_state = strtoupper(erp_workflow_normalize_value($to_state, 'requested workflow state'));

        if (!erp_workflow_can_transition($entity, $from_state, $to_state)) {
            throw new RuntimeException(
                'ERP workflow transition denied: '
                . $entity
                . ' '
                . $from_state
                . ' -> '
                . $to_state
            );
        }
    }
}

if (!function_exists('erp_workflow_build_transition_result')) {
    function erp_workflow_build_transition_result(string $entity, string $from_state, string $to_state): array
    {
        $entity = strtolower(erp_workflow_normalize_value($entity, 'workflow entity'));
        $from_state = strtoupper(erp_workflow_normalize_value($from_state, 'current workflow state'));
        $to_state = strtoupper(erp_workflow_normalize_value($to_state, 'requested workflow state'));

        erp_workflow_require_transition($entity, $from_state, $to_state);

        return [
            'ok' => true,
            'entity' => $entity,
            'from_state' => $from_state,
            'to_state' => $to_state,
            'transition' => $from_state . ' -> ' . $to_state,
        ];
    }
}
