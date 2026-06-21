<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Access Denied Handler
 *
 * Mission 11 - Access Denied Audit Prototype
 *
 * Simulation only. No audit INSERT. No database write.
 * Does not replace login or Permission Guard enforcement.
 */

if (!function_exists('erp_access_denied_mode')) {
    function erp_access_denied_mode(): string
    {
        return 'SIMULATION_ONLY';
    }
}

if (!function_exists('erp_access_denied_should_write_audit')) {
    function erp_access_denied_should_write_audit(): bool
    {
        return false;
    }
}

if (!function_exists('erp_access_denied_safe_message')) {
    function erp_access_denied_safe_message(): string
    {
        return 'Access denied. You do not have permission to perform this action.';
    }
}

if (!function_exists('erp_access_denied_required_event_fields')) {
    /**
     * @return list<string>
     */
    function erp_access_denied_required_event_fields(): array
    {
        return [
            'actor_user_id',
            'action_key',
            'permission_key',
            'target_entity',
            'target_id',
            'decision',
            'reason',
            'ip_address',
            'user_agent',
            'created_at',
            'audit_mode',
            'write_performed',
        ];
    }
}

if (!function_exists('erp_access_denied_event_shape')) {
    /**
     * @return array<string, mixed>
     */
    function erp_access_denied_event_shape(
        int $actorUserId,
        string $actionKey,
        string $permissionKey,
        string $targetEntity,
        ?string $targetId,
        string $reason,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        return [
            'actor_user_id' => $actorUserId,
            'action_key' => trim($actionKey),
            'permission_key' => trim($permissionKey),
            'target_entity' => trim($targetEntity),
            'target_id' => $targetId !== null ? trim($targetId) : null,
            'decision' => 'DENIED',
            'reason' => trim($reason),
            'ip_address' => $ipAddress !== null && trim($ipAddress) !== ''
                ? trim($ipAddress)
                : 'placeholder',
            'user_agent' => $userAgent !== null && trim($userAgent) !== ''
                ? trim($userAgent)
                : 'placeholder',
            'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'audit_mode' => erp_access_denied_mode(),
            'write_performed' => false,
        ];
    }
}

if (!function_exists('erp_access_denied_validate_event')) {
    /**
     * @param array<string, mixed> $event
     * @return array{valid: bool, errors: list<string>}
     */
    function erp_access_denied_validate_event(array $event): array
    {
        $errors = [];

        foreach (erp_access_denied_required_event_fields() as $field) {
            if (!array_key_exists($field, $event)) {
                $errors[] = 'Missing required field: ' . $field;
            }
        }

        $decision = isset($event['decision']) ? strtoupper(trim((string)$event['decision'])) : '';

        if ($decision !== 'DENIED') {
            $errors[] = 'decision must be DENIED';
        }

        if (isset($event['actor_user_id']) && (!is_int($event['actor_user_id']) || $event['actor_user_id'] <= 0)) {
            $errors[] = 'actor_user_id must be a positive integer';
        }

        if (isset($event['audit_mode']) && (string)$event['audit_mode'] !== erp_access_denied_mode()) {
            $errors[] = 'audit_mode must be SIMULATION_ONLY';
        }

        if (array_key_exists('write_performed', $event) && $event['write_performed'] !== false) {
            $errors[] = 'write_performed must be false in Mission 11';
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }
}

if (!function_exists('erp_access_denied_simulate')) {
    /**
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    function erp_access_denied_simulate(array $event): array
    {
        $validation = erp_access_denied_validate_event($event);

        if (!$validation['valid']) {
            return [
                'simulated' => false,
                'write_performed' => false,
                'safe_message' => erp_access_denied_safe_message(),
                'event' => $event,
                'validation' => $validation,
            ];
        }

        return [
            'simulated' => true,
            'write_performed' => false,
            'safe_message' => erp_access_denied_safe_message(),
            'event' => $event,
        ];
    }
}
