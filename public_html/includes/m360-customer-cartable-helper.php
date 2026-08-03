<?php
declare(strict_types=1);

/**
 * MOGHARE360 Wave 1C-B2.9 — Canonical customer cartable helper.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const M360_CARTABLE_TASK_TABLE = 'erp_customer_cartable_tasks';
const M360_CARTABLE_EVENT_TABLE = 'erp_customer_cartable_task_events';

const M360_CARTABLE_STATUS_PENDING = 'PENDING';
const M360_CARTABLE_STATUS_OPENED = 'OPENED';
const M360_CARTABLE_STATUS_COMPLETED = 'COMPLETED';
const M360_CARTABLE_STATUS_CANCELLED = 'CANCELLED';
const M360_CARTABLE_STATUS_EXPIRED = 'EXPIRED';

const M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE = 'CONTRACT_SIGNATURE';
const M360_CARTABLE_TASK_TYPE_ESTIMATE_APPROVAL = 'ESTIMATE_APPROVAL';
const M360_CARTABLE_SOURCE_MODULE_INTAKE_CONTRACT = 'INTAKE_CONTRACT';
const M360_CARTABLE_SOURCE_MODULE_ESTIMATE = 'ESTIMATE';
const M360_CARTABLE_SOURCE_ENTITY_TYPE_INTAKE_CONTRACT = 'ERP_INTAKE_CONTRACT';
const M360_CARTABLE_SOURCE_ENTITY_TYPE_ESTIMATE_VERSION = 'ERP_ESTIMATE_VERSION';

const M360_CARTABLE_EVENT_CREATED = 'CREATED';
const M360_CARTABLE_EVENT_BACKFILLED = 'BACKFILLED';
const M360_CARTABLE_EVENT_OPENED = 'OPENED';
const M360_CARTABLE_EVENT_COMPLETED = 'COMPLETED';
const M360_CARTABLE_EVENT_CANCELLED = 'CANCELLED';
const M360_CARTABLE_EVENT_EXPIRED = 'EXPIRED';
const M360_CARTABLE_EVENT_ACTION_LINK_REFRESHED = 'ACTION_LINK_REFRESHED';
const M360_CARTABLE_EVENT_CUSTOMER_CORRECTION_REQUESTED = 'CUSTOMER_CORRECTION_REQUESTED';
const M360_CARTABLE_EVENT_SYNCED_FROM_MODULE = 'SYNCED_FROM_MODULE';
const M360_CARTABLE_EVENT_SYNCED_TO_COMPATIBILITY_PAYLOAD = 'SYNCED_TO_COMPATIBILITY_PAYLOAD';

const M360_CARTABLE_CONTRACT_PRIORITY = 80;
const M360_CARTABLE_ESTIMATE_PRIORITY = 70;
const M360_CARTABLE_CONTRACT_ACTION_ROUTE = 'customer-intake-contract-review.php';
const M360_CARTABLE_ESTIMATE_ACTION_ROUTE = 'customer-estimate-approval.php';
const M360_CARTABLE_CONTRACT_TITLE_FA = 'قرارداد نیازمند امضا';
const M360_CARTABLE_CONTRACT_MESSAGE_FA = 'قرارداد پذیرش خودروی شما آماده بررسی و امضا است.';
const M360_CARTABLE_ESTIMATE_TITLE_FA = 'تأیید برآورد هزینه';
const M360_CARTABLE_ESTIMATE_MESSAGE_FA = 'برآورد هزینه پرونده شما آماده بررسی و تصمیم‌گیری است.';

function m360_cartable_tables_available($conn): bool
{
    if (!is_resource($conn)) {
        return false;
    }

    return customer_core_table_exists($conn, M360_CARTABLE_TASK_TABLE)
        && customer_core_table_exists($conn, M360_CARTABLE_EVENT_TABLE);
}

function m360_cartable_normalize_mobile(string $mobile): string
{
    $mobile = trim($mobile);
    if ($mobile === '') {
        return '';
    }
    $otpHelper = __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
    if (is_file($otpHelper)) {
        require_once $otpHelper;
        if (function_exists('m360_otp_normalize_phone')) {
            $normalized = m360_otp_normalize_phone($mobile);
            if ($normalized !== null && $normalized !== '') {
                return $normalized;
            }
        }
    }

    return preg_replace('/\D+/', '', $mobile) ?? '';
}

/**
 * @return array{customer_id:?int,mobile:string}
 */
function m360_cartable_normalize_owner(?int $customerId, string $mobile): array
{
    $customerId = $customerId !== null && $customerId > 0 ? $customerId : null;
    $mobile = m360_cartable_normalize_mobile($mobile);

    return [
        'customer_id' => $customerId,
        'mobile' => $mobile,
    ];
}

/**
 * @return ?array<string, string>
 */
function m360_cartable_fetch_task_by_id($conn, int $taskId): ?array
{
    if (!m360_cartable_tables_available($conn) || $taskId < 1) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_CARTABLE_TASK_TABLE . ' WHERE task_id = ?',
        [$taskId]
    );

    return $rows[0] ?? null;
}

/**
 * @return ?array<string, string>
 */
function m360_cartable_find_active_by_source(
    $conn,
    string $sourceModule,
    string $sourceEntityType,
    string $sourceEntityId,
    string $taskType
): ?array {
    if (!m360_cartable_tables_available($conn)) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_CARTABLE_TASK_TABLE . '
         WHERE source_module = ? AND source_entity_type = ? AND source_entity_id = ? AND task_type = ? AND is_active = 1',
        [$sourceModule, $sourceEntityType, $sourceEntityId, $taskType]
    );

    return $rows[0] ?? null;
}

function m360_cartable_task_belongs_to_customer(array $taskRow, ?int $customerId, string $mobile): bool
{
    $owner = m360_cartable_normalize_owner($customerId, $mobile);
    $taskCustomerId = (int)($taskRow['customer_id'] ?? 0);
    if ($owner['customer_id'] !== null && $taskCustomerId > 0) {
        return $taskCustomerId === $owner['customer_id'];
    }
    $taskMobile = m360_cartable_normalize_mobile((string)($taskRow['customer_mobile_normalized'] ?? ''));
    if ($owner['mobile'] !== '' && $taskMobile !== '') {
        return hash_equals($taskMobile, $owner['mobile']);
    }

    return false;
}

/**
 * @param array<string, mixed> $metadata
 */
function m360_cartable_append_event(
    $conn,
    int $taskId,
    string $eventType,
    string $actorType,
    ?string $actorId,
    ?string $oldStatus = null,
    ?string $newStatus = null,
    ?array $metadata = null
): bool {
    if (!m360_cartable_tables_available($conn) || $taskId < 1 || trim($eventType) === '') {
        return false;
    }
    $metadataJson = null;
    if ($metadata !== null && $metadata !== []) {
        $encoded = json_encode($metadata, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return false;
        }
        $metadataJson = $encoded;
    }

    return customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_CARTABLE_EVENT_TABLE . ' (
            task_id, event_type, old_status, new_status, actor_type, actor_id, event_at, event_metadata_json
        ) VALUES (?, ?, ?, ?, ?, ?, SYSUTCDATETIME(), ?)',
        [$taskId, $eventType, $oldStatus, $newStatus, $actorType, $actorId, $metadataJson]
    ) !== false;
}

/**
 * @param array<string, mixed> $spec
 * @return array{ok:bool,task_id:int,created_new:bool,status:string,is_active:bool,message:string}
 */
function m360_cartable_create_or_get_active($conn, array $spec): array
{
    $empty = ['ok' => false, 'task_id' => 0, 'created_new' => false, 'status' => '', 'is_active' => false, 'message' => ''];
    if (!m360_cartable_tables_available($conn)) {
        return array_merge($empty, ['message' => 'cartable_tables_unavailable']);
    }

    $sourceModule = trim((string)($spec['source_module'] ?? ''));
    $sourceEntityType = trim((string)($spec['source_entity_type'] ?? ''));
    $sourceEntityId = trim((string)($spec['source_entity_id'] ?? ''));
    $taskType = trim((string)($spec['task_type'] ?? ''));
    if ($sourceModule === '' || $sourceEntityType === '' || $sourceEntityId === '' || $taskType === '') {
        return array_merge($empty, ['message' => 'invalid_source']);
    }

    $existing = m360_cartable_find_active_by_source($conn, $sourceModule, $sourceEntityType, $sourceEntityId, $taskType);
    if ($existing !== null) {
        return [
            'ok' => true,
            'task_id' => (int)$existing['task_id'],
            'created_new' => false,
            'status' => (string)$existing['status'],
            'is_active' => ((int)($existing['is_active'] ?? 0)) === 1,
            'message' => '',
        ];
    }

    $owner = m360_cartable_normalize_owner(
        isset($spec['customer_id']) ? (int)$spec['customer_id'] : null,
        (string)($spec['customer_mobile_normalized'] ?? ($spec['customer_mobile'] ?? ''))
    );
    if ($owner['customer_id'] === null && $owner['mobile'] === '') {
        return array_merge($empty, ['message' => 'missing_owner']);
    }

    $status = trim((string)($spec['status'] ?? M360_CARTABLE_STATUS_PENDING));
    if (!in_array($status, [M360_CARTABLE_STATUS_PENDING, M360_CARTABLE_STATUS_OPENED], true)) {
        $status = M360_CARTABLE_STATUS_PENDING;
    }
    $isActive = 1;
    $priority = (int)($spec['priority'] ?? 50);
    if ($priority < 0) {
        $priority = 0;
    }
    if ($priority > 100) {
        $priority = 100;
    }

    $insertOk = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_CARTABLE_TASK_TABLE . ' (
            customer_id, customer_mobile_normalized, task_type, title, message, priority, status, is_active,
            source_module, source_entity_type, source_entity_id,
            online_request_id, intake_id, jobcard_id, contract_id, estimate_id, invoice_id,
            action_route, action_token_hash, action_expires_at, due_at,
            created_by_actor_type, created_by_actor_id, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, SYSUTCDATETIME())',
        [
            $owner['customer_id'],
            $owner['mobile'] !== '' ? $owner['mobile'] : null,
            $taskType,
            trim((string)($spec['title'] ?? '')),
            trim((string)($spec['message'] ?? '')),
            $priority,
            $status,
            $isActive,
            $sourceModule,
            $sourceEntityType,
            $sourceEntityId,
            m360_cartable_nullable_bigint($spec['online_request_id'] ?? null),
            m360_cartable_nullable_bigint($spec['intake_id'] ?? null),
            m360_cartable_nullable_int($spec['jobcard_id'] ?? null),
            m360_cartable_nullable_bigint($spec['contract_id'] ?? null),
            m360_cartable_nullable_bigint($spec['estimate_id'] ?? null),
            m360_cartable_nullable_bigint($spec['invoice_id'] ?? null),
            trim((string)($spec['action_route'] ?? '')) !== '' ? trim((string)$spec['action_route']) : null,
            trim((string)($spec['action_token_hash'] ?? '')) !== '' ? trim((string)$spec['action_token_hash']) : null,
            trim((string)($spec['action_expires_at'] ?? '')) !== '' ? trim((string)$spec['action_expires_at']) : null,
            trim((string)($spec['due_at'] ?? '')) !== '' ? trim((string)$spec['due_at']) : null,
            trim((string)($spec['created_by_actor_type'] ?? 'SYSTEM')),
            trim((string)($spec['created_by_actor_id'] ?? '')) !== '' ? trim((string)$spec['created_by_actor_id']) : null,
        ]
    );
    if ($insertOk === false) {
        $existing = m360_cartable_find_active_by_source($conn, $sourceModule, $sourceEntityType, $sourceEntityId, $taskType);
        if ($existing !== null) {
            return [
                'ok' => true,
                'task_id' => (int)$existing['task_id'],
                'created_new' => false,
                'status' => (string)$existing['status'],
                'is_active' => ((int)($existing['is_active'] ?? 0)) === 1,
                'message' => '',
            ];
        }

        return array_merge($empty, ['message' => 'insert_failed']);
    }

    $taskId = (int)(customer_core_scope_identity($conn) ?? 0);
    $createdNew = true;
    if ($taskId < 1) {
        $existing = m360_cartable_find_active_by_source($conn, $sourceModule, $sourceEntityType, $sourceEntityId, $taskType);
        if ($existing !== null) {
            $taskId = (int)$existing['task_id'];
            $status = (string)($existing['status'] ?? $status);
        } else {
            return array_merge($empty, ['message' => 'identity_failed']);
        }
    }

    $eventType = trim((string)($spec['event_type'] ?? M360_CARTABLE_EVENT_CREATED));
    m360_cartable_append_event(
        $conn,
        $taskId,
        $eventType,
        trim((string)($spec['created_by_actor_type'] ?? 'SYSTEM')),
        trim((string)($spec['created_by_actor_id'] ?? '')) !== '' ? trim((string)$spec['created_by_actor_id']) : null,
        null,
        $status,
        isset($spec['event_metadata']) && is_array($spec['event_metadata']) ? $spec['event_metadata'] : null
    );

    return [
        'ok' => true,
        'task_id' => $taskId,
        'created_new' => $createdNew,
        'status' => $status,
        'is_active' => true,
        'message' => '',
    ];
}

/**
 * @return list<array<string, string>>
 */
function m360_cartable_list_active_for_customer($conn, ?int $customerId, string $mobile, ?string $taskType = null): array
{
    if (!m360_cartable_tables_available($conn)) {
        return [];
    }
    $owner = m360_cartable_normalize_owner($customerId, $mobile);
    if ($owner['customer_id'] !== null) {
        $sql = 'SELECT * FROM dbo.' . M360_CARTABLE_TASK_TABLE . '
                WHERE is_active = 1 AND customer_id = ?
                ORDER BY priority DESC, due_at ASC, created_at ASC';
        $params = [$owner['customer_id']];
    } elseif ($owner['mobile'] !== '') {
        $sql = 'SELECT * FROM dbo.' . M360_CARTABLE_TASK_TABLE . '
                WHERE is_active = 1 AND customer_mobile_normalized = ?
                ORDER BY priority DESC, due_at ASC, created_at ASC';
        $params = [$owner['mobile']];
    } else {
        return [];
    }
    if ($taskType !== null && trim($taskType) !== '') {
        $sql = str_replace(' ORDER BY', ' AND task_type = ? ORDER BY', $sql);
        $params[] = $taskType;
    }

    return customer_core_fetch_rows($conn, $sql, $params);
}

function m360_cartable_count_active_for_customer($conn, ?int $customerId, string $mobile, ?string $taskType = null): int
{
    return count(m360_cartable_list_active_for_customer($conn, $customerId, $mobile, $taskType));
}

/**
 * @return list<array<string, string>>
 */
function m360_cartable_list_history_for_customer(
    $conn,
    ?int $customerId,
    string $mobile,
    int $limit = 20,
    int $offset = 0,
    ?string $taskType = null
): array {
    if (!m360_cartable_tables_available($conn)) {
        return [];
    }
    $owner = m360_cartable_normalize_owner($customerId, $mobile);
    if ($limit < 1) {
        $limit = 20;
    }
    if ($offset < 0) {
        $offset = 0;
    }
    if ($owner['customer_id'] !== null) {
        $sql = 'SELECT * FROM dbo.' . M360_CARTABLE_TASK_TABLE . '
                WHERE is_active = 0 AND customer_id = ?';
        $params = [$owner['customer_id']];
    } elseif ($owner['mobile'] !== '') {
        $sql = 'SELECT * FROM dbo.' . M360_CARTABLE_TASK_TABLE . '
                WHERE is_active = 0 AND customer_mobile_normalized = ?';
        $params = [$owner['mobile']];
    } else {
        return [];
    }
    if ($taskType !== null && trim($taskType) !== '') {
        $sql .= ' AND task_type = ?';
        $params[] = $taskType;
    }
    $sql .= ' ORDER BY COALESCE(completed_at, cancelled_at, expired_at, updated_at, created_at) DESC
              OFFSET ? ROWS FETCH NEXT ? ROWS ONLY';
    $params[] = $offset;
    $params[] = $limit;

    return customer_core_fetch_rows($conn, $sql, $params);
}

/**
 * @return array{ok:bool,message:string,changed:bool}
 */
function m360_cartable_mark_opened($conn, int $taskId, string $actorType, ?string $actorId = null): array
{
    $task = m360_cartable_fetch_task_by_id($conn, $taskId);
    if ($task === null) {
        return ['ok' => false, 'message' => 'task_not_found', 'changed' => false];
    }
    if (strtoupper(trim($actorType)) === 'CUSTOMER') {
        $actorMobile = m360_cartable_normalize_mobile((string)($actorId ?? ''));
        if ($actorMobile !== '' && !m360_cartable_task_belongs_to_customer($task, null, $actorMobile)) {
            return ['ok' => false, 'message' => 'ownership_denied', 'changed' => false];
        }
    }
    $status = (string)($task['status'] ?? '');
    if ($status === M360_CARTABLE_STATUS_OPENED) {
        return ['ok' => true, 'message' => '', 'changed' => false];
    }
    if ($status !== M360_CARTABLE_STATUS_PENDING) {
        return ['ok' => false, 'message' => 'invalid_transition', 'changed' => false];
    }

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_CARTABLE_TASK_TABLE . '
         SET status = ?, opened_at = COALESCE(opened_at, SYSUTCDATETIME()), updated_at = SYSUTCDATETIME()
         WHERE task_id = ? AND status = ? AND is_active = 1',
        [M360_CARTABLE_STATUS_OPENED, $taskId, M360_CARTABLE_STATUS_PENDING]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'update_failed', 'changed' => false];
    }

    m360_cartable_append_event(
        $conn,
        $taskId,
        M360_CARTABLE_EVENT_OPENED,
        $actorType,
        $actorId,
        M360_CARTABLE_STATUS_PENDING,
        M360_CARTABLE_STATUS_OPENED
    );

    return ['ok' => true, 'message' => '', 'changed' => true];
}

/**
 * @param array<string, mixed>|null $metadata
 * @return array{ok:bool,message:string,changed:bool}
 */
function m360_cartable_complete_task(
    $conn,
    int $taskId,
    string $actorType,
    ?string $actorId = null,
    ?string $completedChannel = null,
    ?array $metadata = null
): array {
    $task = m360_cartable_fetch_task_by_id($conn, $taskId);
    if ($task === null) {
        return ['ok' => false, 'message' => 'task_not_found', 'changed' => false];
    }
    if (strtoupper(trim($actorType)) === 'CUSTOMER') {
        $actorMobile = m360_cartable_normalize_mobile((string)($actorId ?? ''));
        if ($actorMobile !== '' && !m360_cartable_task_belongs_to_customer($task, null, $actorMobile)) {
            return ['ok' => false, 'message' => 'ownership_denied', 'changed' => false];
        }
    }
    $status = (string)($task['status'] ?? '');
    if ($status === M360_CARTABLE_STATUS_COMPLETED) {
        return ['ok' => true, 'message' => '', 'changed' => false];
    }
    if (!in_array($status, [M360_CARTABLE_STATUS_PENDING, M360_CARTABLE_STATUS_OPENED], true)) {
        return ['ok' => false, 'message' => 'invalid_transition', 'changed' => false];
    }

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_CARTABLE_TASK_TABLE . '
         SET status = ?, is_active = 0, completed_at = COALESCE(completed_at, SYSUTCDATETIME()),
             completed_channel = ?, updated_at = SYSUTCDATETIME()
         WHERE task_id = ? AND status IN (?, ?) AND is_active = 1',
        [
            M360_CARTABLE_STATUS_COMPLETED,
            $completedChannel,
            $taskId,
            M360_CARTABLE_STATUS_PENDING,
            M360_CARTABLE_STATUS_OPENED,
        ]
    );
    if ($ok === false) {
        $task = m360_cartable_fetch_task_by_id($conn, $taskId);
        if ($task !== null && (string)($task['status'] ?? '') === M360_CARTABLE_STATUS_COMPLETED) {
            return ['ok' => true, 'message' => '', 'changed' => false];
        }

        return ['ok' => false, 'message' => 'update_failed', 'changed' => false];
    }

    m360_cartable_append_event(
        $conn,
        $taskId,
        M360_CARTABLE_EVENT_COMPLETED,
        $actorType,
        $actorId,
        $status,
        M360_CARTABLE_STATUS_COMPLETED,
        $metadata
    );

    return ['ok' => true, 'message' => '', 'changed' => true];
}

/**
 * @param array<string, mixed>|null $metadata
 * @return array{ok:bool,message:string,changed:bool}
 */
function m360_cartable_cancel_task(
    $conn,
    int $taskId,
    string $actorType,
    ?string $actorId = null,
    ?array $metadata = null
): array {
    $task = m360_cartable_fetch_task_by_id($conn, $taskId);
    if ($task === null) {
        return ['ok' => false, 'message' => 'task_not_found', 'changed' => false];
    }
    $status = (string)($task['status'] ?? '');
    if ($status === M360_CARTABLE_STATUS_CANCELLED) {
        return ['ok' => true, 'message' => '', 'changed' => false];
    }
    if (!in_array($status, [M360_CARTABLE_STATUS_PENDING, M360_CARTABLE_STATUS_OPENED], true)) {
        return ['ok' => false, 'message' => 'invalid_transition', 'changed' => false];
    }

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_CARTABLE_TASK_TABLE . '
         SET status = ?, is_active = 0, cancelled_at = COALESCE(cancelled_at, SYSUTCDATETIME()), updated_at = SYSUTCDATETIME()
         WHERE task_id = ? AND status IN (?, ?) AND is_active = 1',
        [M360_CARTABLE_STATUS_CANCELLED, $taskId, M360_CARTABLE_STATUS_PENDING, M360_CARTABLE_STATUS_OPENED]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'update_failed', 'changed' => false];
    }

    m360_cartable_append_event(
        $conn,
        $taskId,
        M360_CARTABLE_EVENT_CANCELLED,
        $actorType,
        $actorId,
        $status,
        M360_CARTABLE_STATUS_CANCELLED,
        $metadata
    );

    return ['ok' => true, 'message' => '', 'changed' => true];
}

/**
 * @param array<string, string> $taskRow
 * @param array<string, mixed>|null $requestRow
 * @param array<string, mixed>|null $payload
 * @return array{ok:bool,action_route:string,review_url:string,message:string}
 */
function m360_cartable_resolve_task_action($conn, array $taskRow, ?array $requestRow = null, ?array $payload = null): array
{
    $empty = ['ok' => false, 'action_route' => '', 'review_url' => '', 'message' => ''];
    $taskType = (string)($taskRow['task_type'] ?? '');
    if ($taskType === M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE) {
        return m360_cartable_resolve_contract_signature_action($conn, $taskRow, $requestRow, $payload);
    }
    if ($taskType === M360_CARTABLE_TASK_TYPE_ESTIMATE_APPROVAL) {
        return m360_cartable_resolve_estimate_approval_action($conn, $taskRow);
    }

    $route = trim((string)($taskRow['action_route'] ?? ''));
    if ($route !== '' && !str_contains($route, '?')) {
        return ['ok' => true, 'action_route' => $route, 'review_url' => $route, 'message' => ''];
    }

    return array_merge($empty, ['message' => 'unsupported_task_type']);
}

/**
 * @param array<string, string> $taskRow
 * @param array<string, mixed>|null $requestRow
 * @param array<string, mixed>|null $payload
 * @return array{ok:bool,action_route:string,review_url:string,message:string}
 */
function m360_cartable_resolve_contract_signature_action(
    $conn,
    array $taskRow,
    ?array $requestRow = null,
    ?array $payload = null
): array {
    $empty = ['ok' => false, 'action_route' => '', 'review_url' => '', 'message' => ''];
    $taskId = (int)($taskRow['task_id'] ?? 0);
    $contractId = (int)($taskRow['contract_id'] ?? 0);
    if ($taskId < 1) {
        return array_merge($empty, ['message' => 'missing_task']);
    }
    if ($contractId < 1) {
        return array_merge($empty, ['message' => 'missing_contract']);
    }

    if (!function_exists('m360_intake_contract_fetch_by_id')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
    }
    $contractRow = m360_intake_contract_fetch_by_id($conn, $contractId);
    if ($contractRow === null) {
        return array_merge($empty, ['message' => 'contract_not_found']);
    }
    if (m360_intake_contract_is_signed($contractRow)) {
        return array_merge($empty, ['message' => 'contract_not_actionable']);
    }
    $contractStatus = strtoupper((string)($contractRow['contract_status'] ?? ''));
    if (in_array($contractStatus, [M360_CONTRACT_STATUS_CANCELLED, M360_CONTRACT_STATUS_EXPIRED], true)) {
        return array_merge($empty, ['message' => 'contract_not_actionable']);
    }
    $taskStatus = strtoupper((string)($taskRow['status'] ?? ''));
    if ((int)($taskRow['is_active'] ?? 0) !== 1
        || !in_array($taskStatus, [M360_CARTABLE_STATUS_PENDING, M360_CARTABLE_STATUS_OPENED], true)) {
        return array_merge($empty, ['message' => 'task_not_active']);
    }
    $tokenHash = trim((string)($taskRow['action_token_hash'] ?? ''));
    $contractTokenHash = trim((string)($contractRow['secure_token_hash'] ?? ''));
    if ($tokenHash === '' && $contractTokenHash === '') {
        return array_merge($empty, ['message' => 'missing_token_reference']);
    }
    if ($tokenHash !== '' && $contractTokenHash !== '' && !hash_equals($contractTokenHash, $tokenHash)) {
        return array_merge($empty, ['message' => 'token_reference_mismatch']);
    }

    $route = M360_CARTABLE_CONTRACT_ACTION_ROUTE;
    // Hard guard: contract signature must never open estimate approval (Task 41 / AUTO-UAT).
    if (str_contains(strtolower($route), 'estimate')) {
        $route = M360_CARTABLE_CONTRACT_ACTION_ROUTE;
    }

    return [
        'ok' => true,
        'action_route' => $route,
        'review_url' => $route . '?task_id=' . (string)$taskId,
        'message' => '',
    ];
}

/**
 * @param array<string, mixed> $estimateRow
 * @param array<string, mixed> $jobcardRow
 * @return array{ok:bool,task_id:int,created_new:bool,message:string}
 */
function m360_cartable_sync_estimate_approval_task(
    $conn,
    int $estimateVersionId,
    array $estimateRow,
    array $jobcardRow,
    string $tokenHash,
    string $tokenExpiresAt
): array {
    $empty = ['ok' => false, 'task_id' => 0, 'created_new' => false, 'message' => ''];
    if (!is_resource($conn) || $estimateVersionId < 1 || !m360_cartable_tables_available($conn)) {
        return array_merge($empty, ['message' => 'unavailable']);
    }

    $mobile = m360_cartable_normalize_mobile((string)($jobcardRow['customer_mobile'] ?? ''));
    if ($mobile === '') {
        return array_merge($empty, ['message' => 'missing_mobile']);
    }

    $customerId = (int)($estimateRow['customer_id'] ?? 0);
    if ($customerId < 1) {
        $customerId = (int)($jobcardRow['customer_id'] ?? 0);
    }
    $estimateId = (int)($estimateRow['estimate_id'] ?? 0);
    $jobcardId = (int)($jobcardRow['jobcard_id'] ?? 0);
    $onlineRequestId = (int)($jobcardRow['online_request_id'] ?? 0);
    $expiresAt = trim($tokenExpiresAt) !== '' ? str_replace('T', ' ', substr($tokenExpiresAt, 0, 19)) : null;

    $created = m360_cartable_create_or_get_active($conn, [
        'customer_id' => $customerId > 0 ? $customerId : null,
        'customer_mobile_normalized' => $mobile,
        'task_type' => M360_CARTABLE_TASK_TYPE_ESTIMATE_APPROVAL,
        'title' => M360_CARTABLE_ESTIMATE_TITLE_FA,
        'message' => M360_CARTABLE_ESTIMATE_MESSAGE_FA,
        'priority' => M360_CARTABLE_ESTIMATE_PRIORITY,
        'status' => M360_CARTABLE_STATUS_PENDING,
        'source_module' => M360_CARTABLE_SOURCE_MODULE_ESTIMATE,
        'source_entity_type' => M360_CARTABLE_SOURCE_ENTITY_TYPE_ESTIMATE_VERSION,
        'source_entity_id' => (string)$estimateVersionId,
        'online_request_id' => $onlineRequestId > 0 ? $onlineRequestId : null,
        'jobcard_id' => $jobcardId > 0 ? $jobcardId : null,
        'estimate_id' => $estimateId > 0 ? $estimateId : null,
        'action_route' => M360_CARTABLE_ESTIMATE_ACTION_ROUTE,
        'action_token_hash' => trim($tokenHash) !== '' ? trim($tokenHash) : null,
        'action_expires_at' => $expiresAt,
        'created_by_actor_type' => 'SYSTEM',
        'created_by_actor_id' => 'estimate_issue',
        'event_type' => M360_CARTABLE_EVENT_CREATED,
        'event_metadata' => [
            'estimate_id' => $estimateId,
            'estimate_version_id' => $estimateVersionId,
            'jobcard_id' => $jobcardId,
        ],
    ]);

    if (!$created['ok']) {
        return array_merge($empty, ['message' => (string)$created['message']]);
    }

    return [
        'ok' => true,
        'task_id' => (int)$created['task_id'],
        'created_new' => (bool)$created['created_new'],
        'message' => '',
    ];
}

function m360_cartable_cancel_task_for_estimate_version(
    $conn,
    int $estimateVersionId,
    string $actorType,
    ?string $actorId = null
): array {
    if (!is_resource($conn) || $estimateVersionId < 1 || !m360_cartable_tables_available($conn)) {
        return ['ok' => false, 'message' => 'unavailable', 'changed' => false];
    }
    $task = m360_cartable_find_active_by_source(
        $conn,
        M360_CARTABLE_SOURCE_MODULE_ESTIMATE,
        M360_CARTABLE_SOURCE_ENTITY_TYPE_ESTIMATE_VERSION,
        (string)$estimateVersionId,
        M360_CARTABLE_TASK_TYPE_ESTIMATE_APPROVAL
    );
    if ($task === null) {
        return ['ok' => true, 'message' => '', 'changed' => false];
    }

    return m360_cartable_cancel_task(
        $conn,
        (int)$task['task_id'],
        $actorType,
        $actorId,
        ['estimate_version_id' => $estimateVersionId, 'reason' => 'superseded']
    );
}

/**
 * @param array<string, string> $taskRow
 * @return array{ok:bool,action_route:string,review_url:string,message:string}
 */
function m360_cartable_resolve_estimate_approval_action($conn, array $taskRow): array
{
    $empty = ['ok' => false, 'action_route' => '', 'review_url' => '', 'message' => ''];
    $taskId = (int)($taskRow['task_id'] ?? 0);
    if ($taskId < 1) {
        return array_merge($empty, ['message' => 'missing_task']);
    }
    $route = M360_CARTABLE_ESTIMATE_ACTION_ROUTE;
    // Hard guard: estimate approval must never open intake contract review.
    if (str_contains(strtolower($route), 'intake-contract')) {
        $route = M360_CARTABLE_ESTIMATE_ACTION_ROUTE;
    }

    return [
        'ok' => true,
        'action_route' => $route,
        'review_url' => $route . '?task_id=' . (string)$taskId,
        'message' => '',
    ];
}

/**
 * @return array{ok:bool,message:string,changed:bool,compatibility_only:bool}
 */
function m360_cartable_complete_estimate_approval_task(
    $conn,
    int $estimateVersionId,
    string $completedChannel = 'CUSTOMER_PORTAL',
    ?array $metadata = null
): array {
    $empty = ['ok' => false, 'message' => 'unavailable', 'changed' => false, 'compatibility_only' => false];
    if (!is_resource($conn) || $estimateVersionId < 1 || !m360_cartable_tables_available($conn)) {
        return $empty;
    }
    $task = m360_cartable_find_active_by_source(
        $conn,
        M360_CARTABLE_SOURCE_MODULE_ESTIMATE,
        M360_CARTABLE_SOURCE_ENTITY_TYPE_ESTIMATE_VERSION,
        (string)$estimateVersionId,
        M360_CARTABLE_TASK_TYPE_ESTIMATE_APPROVAL
    );
    if ($task === null) {
        return ['ok' => true, 'message' => '', 'changed' => false, 'compatibility_only' => true];
    }

    $meta = is_array($metadata) ? $metadata : ['estimate_version_id' => $estimateVersionId];
    $result = m360_cartable_complete_task(
        $conn,
        (int)$task['task_id'],
        'CUSTOMER',
        null,
        $completedChannel,
        $meta
    );

    return [
        'ok' => $result['ok'],
        'message' => (string)$result['message'],
        'changed' => (bool)$result['changed'],
        'compatibility_only' => false,
    ];
}

/**
 * @return array{
 *   ok:bool,
 *   candidates:int,
 *   inserted:int,
 *   skipped:int,
 *   duplicates_prevented:int,
 *   details:list<array<string, string|int>>
 * }
 */
function m360_cartable_backfill_contract_signature_tasks($conn): array
{
    $report = [
        'ok' => false,
        'candidates' => 0,
        'inserted' => 0,
        'skipped' => 0,
        'duplicates_prevented' => 0,
        'details' => [],
    ];
    if (!m360_cartable_tables_available($conn)) {
        return $report;
    }
    if (!function_exists('m360_intake_contract_find_active_for_online_request')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
    }

    $contracts = customer_core_fetch_rows(
        $conn,
        "SELECT c.contract_id, c.online_request_id, c.customer_id, c.mobile, c.contract_status,
                c.secure_token_hash, c.secure_token_expires_at, c.jobcard_id
         FROM dbo.erp_intake_contracts c
         WHERE c.online_request_id IS NOT NULL
           AND c.contract_status IN (?, ?, ?, ?)
           AND c.secure_token_hash IS NOT NULL
           AND LTRIM(RTRIM(c.secure_token_hash)) <> ''
         ORDER BY c.contract_id ASC",
        [
            M360_CONTRACT_STATUS_GENERATED,
            M360_CONTRACT_STATUS_SENT,
            M360_CONTRACT_STATUS_VIEWED,
            M360_CONTRACT_STATUS_OTP_SENT,
        ]
    );

    foreach ($contracts as $contract) {
        $report['candidates']++;
        $contractId = (int)($contract['contract_id'] ?? 0);
        $requestId = (int)($contract['online_request_id'] ?? 0);
        $detail = [
            'contract_id' => $contractId,
            'online_request_id' => $requestId,
            'result' => 'skipped',
            'reason' => '',
        ];

        if ($requestId === 24) {
            $detail['reason'] = 'request_24_excluded';
            $report['skipped']++;
            $report['details'][] = $detail;
            continue;
        }

        if (!function_exists('m360_online_req_fetch_by_id')) {
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php';
        }
        $requestRow = m360_online_req_fetch_by_id($conn, $requestId);
        if ($requestRow === null) {
            $detail['reason'] = 'request_not_found';
            $report['skipped']++;
            $report['details'][] = $detail;
            continue;
        }

        $payload = function_exists('m360_online_req_parse_payload')
            ? m360_online_req_parse_payload($requestRow['request_payload_json'] ?? null)
            : [];
        if (function_exists('m360_rw_intake_payload_for_recovery')) {
            $payload = m360_rw_intake_payload_for_recovery($payload);
        }
        if (function_exists('m360_rw_intake_contract_customer_accepted') && m360_rw_intake_contract_customer_accepted($payload)) {
            $detail['reason'] = 'legacy_payload_accepted';
            $report['skipped']++;
            $report['details'][] = $detail;
            continue;
        }

        if (function_exists('m360_rw_customer_profile_contract_has_signature_proof')
            && m360_rw_customer_profile_contract_has_signature_proof($conn, $contractId)) {
            $detail['reason'] = 'signature_proof_exists';
            $report['skipped']++;
            $report['details'][] = $detail;
            continue;
        }

        $mobile = m360_cartable_normalize_mobile((string)($contract['mobile'] ?? ($requestRow['mobile'] ?? '')));
        if ($mobile === '') {
            $detail['reason'] = 'missing_mobile';
            $report['skipped']++;
            $report['details'][] = $detail;
            continue;
        }

        if (function_exists('m360_rw_customer_profile_contract_binding_valid')
            && !m360_rw_customer_profile_contract_binding_valid($requestRow, $contract, $mobile)) {
            $detail['reason'] = 'binding_invalid';
            $report['skipped']++;
            $report['details'][] = $detail;
            continue;
        }

        $customerId = (int)($requestRow['customer_id'] ?? 0);
        if ($customerId < 1) {
            $customerId = (int)($contract['customer_id'] ?? 0);
        }
        if ($customerId < 1 && function_exists('m360_rw_customer_profile_fetch_customer_row')) {
            $customerRow = m360_rw_customer_profile_fetch_customer_row($conn, $mobile);
            $customerId = (int)($customerRow['customer_id'] ?? 0);
        }

        $existing = m360_cartable_find_active_by_source(
            $conn,
            M360_CARTABLE_SOURCE_MODULE_INTAKE_CONTRACT,
            M360_CARTABLE_SOURCE_ENTITY_TYPE_INTAKE_CONTRACT,
            (string)$contractId,
            M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE
        );
        if ($existing !== null) {
            $detail['result'] = 'duplicate_prevented';
            $detail['reason'] = 'active_task_exists';
            $report['duplicates_prevented']++;
            $report['details'][] = $detail;
            continue;
        }

        $created = m360_cartable_create_or_get_active($conn, [
            'customer_id' => $customerId > 0 ? $customerId : null,
            'customer_mobile_normalized' => $mobile,
            'task_type' => M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE,
            'title' => M360_CARTABLE_CONTRACT_TITLE_FA,
            'message' => M360_CARTABLE_CONTRACT_MESSAGE_FA,
            'priority' => M360_CARTABLE_CONTRACT_PRIORITY,
            'status' => M360_CARTABLE_STATUS_PENDING,
            'source_module' => M360_CARTABLE_SOURCE_MODULE_INTAKE_CONTRACT,
            'source_entity_type' => M360_CARTABLE_SOURCE_ENTITY_TYPE_INTAKE_CONTRACT,
            'source_entity_id' => (string)$contractId,
            'online_request_id' => $requestId,
            'jobcard_id' => (int)($contract['jobcard_id'] ?? 0) > 0 ? (int)$contract['jobcard_id'] : null,
            'contract_id' => $contractId,
            'action_route' => M360_CARTABLE_CONTRACT_ACTION_ROUTE,
            'action_token_hash' => trim((string)($contract['secure_token_hash'] ?? '')),
            'action_expires_at' => trim((string)($contract['secure_token_expires_at'] ?? '')) !== ''
                ? trim((string)$contract['secure_token_expires_at'])
                : null,
            'created_by_actor_type' => 'SYSTEM',
            'created_by_actor_id' => 'backfill',
            'event_type' => M360_CARTABLE_EVENT_BACKFILLED,
            'event_metadata' => ['online_request_id' => $requestId, 'contract_id' => $contractId],
        ]);
        if (!$created['ok']) {
            $detail['reason'] = (string)$created['message'];
            $report['skipped']++;
            $report['details'][] = $detail;
            continue;
        }
        if ($created['created_new']) {
            $detail['result'] = 'inserted';
            $detail['reason'] = 'backfilled';
            $detail['task_id'] = $created['task_id'];
            $report['inserted']++;
        } else {
            $detail['result'] = 'duplicate_prevented';
            $detail['reason'] = 'create_or_get_existing';
            $report['duplicates_prevented']++;
        }
        $report['details'][] = $detail;
    }

    $report['ok'] = true;

    return $report;
}

function m360_cartable_nullable_bigint(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }
    $int = (int)$value;

    return $int > 0 ? $int : null;
}

function m360_cartable_nullable_int(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }
    $int = (int)$value;

    return $int > 0 ? $int : null;
}

/**
 * @return list<array<string, mixed>>
 */
function m360_cartable_list_dashboard_inbox($conn, int $customerId, string $mobile): array
{
    if (!is_resource($conn)) {
        return [];
    }
    $tasks = m360_cartable_list_active_for_customer($conn, $customerId > 0 ? $customerId : null, $mobile);
    $items = [];
    foreach ($tasks as $taskRow) {
        if (!m360_cartable_task_belongs_to_customer($taskRow, $customerId > 0 ? $customerId : null, $mobile)) {
            continue;
        }
        $requestId = (int)($taskRow['online_request_id'] ?? 0);
        $requestRow = $requestId > 0 ? m360_online_req_fetch_by_id($conn, $requestId) : null;
        $payload = $requestRow !== null
            ? m360_rw_intake_payload_for_recovery(m360_online_req_parse_payload($requestRow['request_payload_json'] ?? null))
            : [];
        $action = m360_cartable_resolve_task_action($conn, $taskRow, $requestRow, $payload);
        $status = (string)($taskRow['status'] ?? M360_CARTABLE_STATUS_PENDING);
        $statusLabels = [
            M360_CARTABLE_STATUS_PENDING => 'نیازمند اقدام',
            M360_CARTABLE_STATUS_OPENED => 'در حال انجام',
        ];
        $taskType = strtoupper(trim((string)($taskRow['task_type'] ?? '')));
        $actionLabel = '';
        $actionUrl = $action['ok'] ? (string)$action['review_url'] : '';
        $isContractTask = in_array($taskType, [
            M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE,
            'CONTRACT_REVIEW',
            'CONTRACT_SIGN',
        ], true);
        $isEstimateTask = ($taskType === M360_CARTABLE_TASK_TYPE_ESTIMATE_APPROVAL);
        // Never cross-wire contract ↔ estimate action URLs in the customer inbox.
        if ($isContractTask) {
            $actionUrl = M360_CARTABLE_CONTRACT_ACTION_ROUTE . '?task_id=' . (string)(int)($taskRow['task_id'] ?? 0);
            if ($action['ok'] || $actionUrl !== '') {
                $actionLabel = 'بررسی و امضای قرارداد';
            }
        } elseif ($isEstimateTask) {
            $actionUrl = M360_CARTABLE_ESTIMATE_ACTION_ROUTE . '?task_id=' . (string)(int)($taskRow['task_id'] ?? 0);
            if ($action['ok'] || $actionUrl !== '') {
                $actionLabel = 'بررسی و تصمیم‌گیری برآورد';
            }
        } elseif ($action['ok']) {
            $actionLabel = 'اقدام';
        }
        $items[] = [
            'task_id' => (int)($taskRow['task_id'] ?? 0),
            'task_type' => $taskType,
            'title' => (string)($taskRow['title'] ?? ($isEstimateTask ? M360_CARTABLE_ESTIMATE_TITLE_FA : ($isContractTask ? M360_CARTABLE_CONTRACT_TITLE_FA : 'اقدام مشتری'))),
            'message' => (string)($taskRow['message'] ?? ($isEstimateTask ? M360_CARTABLE_ESTIMATE_MESSAGE_FA : ($isContractTask ? M360_CARTABLE_CONTRACT_MESSAGE_FA : ''))),
            'priority' => (int)($taskRow['priority'] ?? 50),
            'status_label' => $statusLabels[$status] ?? 'نیازمند اقدام',
            'context' => $requestId > 0 ? 'REQ-' . (string)$requestId : '',
            'action_label' => $actionLabel,
            'action_url' => $actionUrl,
            'group' => $isContractTask ? 'contract' : ($isEstimateTask ? 'estimate' : 'other'),
        ];
    }

    return $items;
}
