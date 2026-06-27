<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-access-management-helper.php';

function m360_access_audit_json(array $payload): string
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    return substr($json, 0, 3800);
}

function m360_access_audit_request_number(int $subjectUserId, string $suffix): string
{
    return 'AMGMT-' . gmdate('YmdHis') . '-' . $subjectUserId . '-' . strtoupper(preg_replace('/[^A-Z0-9]/', '', $suffix) ?? 'X');
}

/**
 * Creates or reuses an APPLIED admin-management access request for FK-safe writes.
 */
function m360_access_audit_ensure_request(
    $conn,
    int $actorUserId,
    int $subjectUserId,
    string $requestType,
    string $justification,
    string $suffix = 'OP'
): ?int {
    if ($conn === false) {
        return null;
    }

    if (!customer_core_table_exists($conn, 'core_access_requests')) {
        return null;
    }

    $requestNumber = m360_access_audit_request_number($subjectUserId, $suffix);
    $existing = customer_core_scalar(
        $conn,
        'SELECT TOP 1 request_id FROM dbo.core_access_requests WHERE request_number = ?',
        [$requestNumber]
    );

    if ($existing !== null && (int)$existing > 0) {
        return (int)$existing;
    }

    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.core_access_requests (
            request_number, request_type, request_state, priority,
            subject_user_id, requested_by_user_id, justification,
            owner_acknowledged, is_emergency, migration_source,
            submitted_at, decided_at, applied_at, applied_by_user_id, created_at
        ) VALUES (
            ?, ?, N\'APPLIED\', N\'NORMAL\',
            ?, ?, ?,
            1, 1, ?,
            SYSUTCDATETIME(), SYSUTCDATETIME(), SYSUTCDATETIME(), ?, SYSUTCDATETIME()
        )',
        [
            $requestNumber,
            $requestType,
            $subjectUserId,
            $actorUserId,
            $justification,
            M360_ACCESS_MGMT_MIGRATION_SOURCE,
            $actorUserId,
        ]
    );

    if ($ok === false) {
        return null;
    }

    $newId = customer_core_scope_identity($conn);

    return $newId !== null && $newId > 0 ? $newId : null;
}

function m360_access_audit_record_change(
    $conn,
    int $subjectUserId,
    int $requestId,
    string $changeType,
    string $entityType,
    ?int $entityId,
    int $actorUserId,
    ?array $before,
    ?array $after,
    ?string $reason = null
): bool {
    if ($conn === false || !customer_core_table_exists($conn, 'core_access_change_history')) {
        return false;
    }

    $payloadAfter = $after ?? [];
    if ($reason !== null && trim($reason) !== '') {
        $payloadAfter['reason'] = trim($reason);
    }

    return customer_core_execute(
        $conn,
        'INSERT INTO dbo.core_access_change_history (
            user_id, request_id, change_type, entity_type, entity_id,
            before_json, after_json, changed_by_user_id, changed_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, SYSUTCDATETIME())',
        [
            $subjectUserId,
            $requestId,
            $changeType,
            $entityType,
            $entityId,
            $before !== null ? m360_access_audit_json($before) : null,
            m360_access_audit_json($payloadAfter),
            $actorUserId,
        ]
    ) !== false;
}

function m360_access_audit_record_event(
    $conn,
    int $actorUserId,
    string $action,
    ?int $subjectUserId,
    ?int $requestId,
    string $entityType,
    ?int $entityId,
    array $details,
    bool $isEmergency = true
): bool {
    if ($conn === false || !customer_core_table_exists($conn, 'core_audit_logs')) {
        return false;
    }

    unset($details['password'], $details['password_hash'], $details['temporary_password']);

    return customer_core_execute(
        $conn,
        'INSERT INTO dbo.core_audit_logs (
            actor_user_id, action, entity_type, entity_id, request_id, subject_user_id,
            details_json, ip_address, user_agent, is_emergency, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, SYSUTCDATETIME())',
        [
            $actorUserId,
            $action,
            $entityType,
            $entityId,
            $requestId,
            $subjectUserId,
            m360_access_audit_json($details),
            customer_core_client_ip(),
            customer_core_user_agent(),
            $isEmergency ? 1 : 0,
        ]
    ) !== false;
}

function m360_access_audit_log_operation(
    $conn,
    int $actorUserId,
    int $subjectUserId,
    string $requestType,
    string $changeType,
    string $auditAction,
    string $entityType,
    ?int $entityId,
    ?array $before,
    ?array $after,
    ?string $reason = null,
    string $suffix = 'OP'
): bool {
    $requestId = m360_access_audit_ensure_request(
        $conn,
        $actorUserId,
        $subjectUserId,
        $requestType,
        $reason ?? $changeType,
        $suffix
    );

    if ($requestId === null) {
        return false;
    }

    $historyOk = m360_access_audit_record_change(
        $conn,
        $subjectUserId,
        $requestId,
        $changeType,
        $entityType,
        $entityId,
        $actorUserId,
        $before,
        $after,
        $reason
    );

    $auditOk = m360_access_audit_record_event(
        $conn,
        $actorUserId,
        $auditAction,
        $subjectUserId,
        $requestId,
        $entityType,
        $entityId,
        array_merge($after ?? [], ['change_type' => $changeType, 'reason' => $reason]),
        true
    );

    return $historyOk || $auditOk;
}

/**
 * @return list<array<string, string>>
 */
function m360_access_audit_list_history($conn, ?int $userId = null, int $limit = 100): array
{
    if ($conn === false || !customer_core_table_exists($conn, 'core_access_change_history')) {
        return [];
    }

    $limit = max(1, min(500, $limit));

    if ($userId !== null && $userId > 0) {
        return customer_core_fetch_rows(
            $conn,
            'SELECT TOP ' . $limit . ' h.history_id, h.user_id, h.request_id, h.change_type, h.entity_type, h.entity_id,
                    h.before_json, h.after_json, h.changed_by_user_id, h.changed_at,
                    u.username AS subject_username, cu.username AS changed_by_username
             FROM dbo.core_access_change_history h
             LEFT JOIN dbo.core_users u ON u.user_id = h.user_id
             LEFT JOIN dbo.core_users cu ON cu.user_id = h.changed_by_user_id
             WHERE h.user_id = ?
             ORDER BY h.changed_at DESC, h.history_id DESC',
            [$userId]
        );
    }

    return customer_core_fetch_rows(
        $conn,
        'SELECT TOP ' . $limit . ' h.history_id, h.user_id, h.request_id, h.change_type, h.entity_type, h.entity_id,
                h.before_json, h.after_json, h.changed_by_user_id, h.changed_at,
                u.username AS subject_username, cu.username AS changed_by_username
         FROM dbo.core_access_change_history h
         LEFT JOIN dbo.core_users u ON u.user_id = h.user_id
         LEFT JOIN dbo.core_users cu ON cu.user_id = h.changed_by_user_id
         ORDER BY h.changed_at DESC, h.history_id DESC'
    );
}
