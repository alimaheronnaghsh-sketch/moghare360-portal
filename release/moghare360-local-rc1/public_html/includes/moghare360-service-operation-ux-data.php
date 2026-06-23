<?php
declare(strict_types=1);

/**
 * MOGHARE360 Service Operation UX — read-only data helpers
 *
 * Mission 35 — SELECT only. No write. No status/assignment change.
 */

const M35_UX_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const M35_UX_PLACEHOLDER_ACTIONS = [
    'service.operation.create' => 'placeholder_service_operation_create',
    'service.operation.view' => 'placeholder_service_operation_view',
    'service.operation.list' => 'placeholder_service_operation_list',
    'service.operation.assign' => 'placeholder_service_operation_assign',
    'service.operation.status.change' => 'placeholder_service_operation_status_change',
];

/** @var list<string> */
const M35_UX_BOARD_STATUSES = [
    'DRAFT',
    'ASSIGNED',
    'IN_PROGRESS',
    'WAITING_PARTS',
    'DONE',
    'QC_REJECTED',
    'CANCELLED',
];

function m35_ux_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function m35_ux_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function m35_ux_bootstrap_auth(): void
{
    m35_ux_require_first_existing(m35_ux_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    m35_ux_require_first_existing(m35_ux_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
}

function m35_ux_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m35_ux_display(string $value): string
{
    return m35_ux_h(trim($value) === '' ? '—' : $value);
}

function m35_ux_parse_int(string $key, int $default = 0): int
{
    if (!isset($_GET[$key])) {
        return $default;
    }

    $raw = trim((string)$_GET[$key]);

    return ($raw !== '' && ctype_digit($raw)) ? (int)$raw : $default;
}

/**
 * @return array<string, mixed>
 */
function m35_ux_guard_eval($connection, int $userId, string $actionKey): array
{
    $map = erp_guard_action_map();

    if (isset($map[$actionKey])) {
        $result = erp_guard_action($connection, $userId, $actionKey);
        $result['label'] = !empty($result['allowed']) ? 'OK' : 'FAIL';

        if (!empty($result['placeholder'])) {
            $result['label'] = 'PLACEHOLDER';
        }

        return $result;
    }

    if (!isset(M35_UX_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === M35_UX_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

/**
 * @return list<array<string, string>>
 */
function m35_ux_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return [];
    }

    $rows = [];

    while (@odbc_fetch_row($statement)) {
        $row = [];
        $columnCount = @odbc_num_fields($statement);

        if ($columnCount === false || $columnCount < 1) {
            continue;
        }

        for ($i = 1; $i <= $columnCount; $i++) {
            $name = @odbc_field_name($statement, $i);

            if ($name === false) {
                continue;
            }

            $value = @odbc_result($statement, $i);
            $row[strtolower((string)$name)] = $value === false || $value === null ? '' : (string)$value;
        }

        if ($row !== []) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function m35_ux_scalar($connection, string $sql, array $params = []): int
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return 0;
    }

    if (@odbc_fetch_row($statement) !== true) {
        return 0;
    }

    $value = @odbc_result($statement, 1);

    return is_numeric($value) ? (int)$value : 0;
}

function m35_ux_table_exists($connection, string $tableName): bool
{
    $rows = m35_ux_fetch_rows(
        $connection,
        "SELECT 1 AS ok FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = ?",
        [$tableName]
    );

    return $rows !== [];
}

/**
 * @return array{connection: resource|false, userId: int, error: string}
 */
function m35_ux_connect(string $guardAction = 'service.operation.list'): array
{
    $result = [
        'connection' => false,
        'userId' => M35_UX_PLATFORM_OWNER_ID,
        'error' => '',
    ];

    try {
        m35_ux_bootstrap_auth();
        erp_auth_context_start();

        $connection = erp_auth_create_local_odbc_connection();
        $userId = erp_auth_current_user_id();

        if ($userId !== M35_UX_PLATFORM_OWNER_ID) {
            throw new RuntimeException('Access denied.');
        }

        $user = erp_auth_load_current_user($connection);

        if ($user === null) {
            throw new RuntimeException('Access denied.');
        }

        $guard = m35_ux_guard_eval($connection, $userId, $guardAction);

        if (empty($guard['allowed'])) {
            throw new RuntimeException('Access denied.');
        }

        $result['connection'] = $connection;
        $result['userId'] = $userId;
    } catch (Throwable $exception) {
        $result['error'] = 'Service Operation UX data could not be loaded.';
    }

    return $result;
}

/**
 * @return list<array<string, string>>
 */
function m35_ux_fetch_service_operations($connection, ?int $assignedUserId = null): array
{
    if (!m35_ux_table_exists($connection, 'erp_service_operations')) {
        return [];
    }

    $sql = 'SELECT TOP 100
            s.service_operation_id,
            s.jobcard_id,
            s.service_title,
            s.service_description,
            s.service_status,
            s.assigned_to_user_id,
            s.created_at,
            s.updated_at
        FROM dbo.erp_service_operations s
        WHERE s.is_active = 1';
    $params = [];

    if ($assignedUserId !== null && $assignedUserId > 0) {
        $sql .= ' AND s.assigned_to_user_id = ?';
        $params[] = $assignedUserId;
    }

    $sql .= ' ORDER BY s.service_operation_id DESC';

    return m35_ux_fetch_rows($connection, $sql, $params);
}

/**
 * @return array<string, int>
 */
function m35_ux_fetch_kpi_counts($connection): array
{
    if (!m35_ux_table_exists($connection, 'erp_service_operations')) {
        return ['total' => 0, 'in_progress' => 0, 'waiting_parts' => 0, 'done' => 0];
    }

    return [
        'total' => m35_ux_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_service_operations WHERE is_active = 1'),
        'in_progress' => m35_ux_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_service_operations WHERE is_active = 1 AND service_status = 'IN_PROGRESS'"
        ),
        'waiting_parts' => m35_ux_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_service_operations WHERE is_active = 1 AND service_status = 'WAITING_PARTS'"
        ),
        'done' => m35_ux_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_service_operations WHERE is_active = 1 AND service_status = 'DONE'"
        ),
    ];
}

function m35_ux_resolve_service_operation_id($connection, int $requested = 1): int
{
    if ($requested > 0 && m35_ux_table_exists($connection, 'erp_service_operations')) {
        $exists = m35_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_service_operations WHERE service_operation_id = ? AND is_active = 1',
            [$requested]
        );

        if ($exists > 0) {
            return $requested;
        }
    }

    if (!m35_ux_table_exists($connection, 'erp_service_operations')) {
        return 0;
    }

    $rows = m35_ux_fetch_rows(
        $connection,
        'SELECT TOP 1 service_operation_id FROM dbo.erp_service_operations WHERE is_active = 1 ORDER BY service_operation_id'
    );

    return isset($rows[0]['service_operation_id']) ? (int)$rows[0]['service_operation_id'] : 0;
}

/**
 * @return array<string, string>
 */
function m35_ux_fetch_service_operation_detail($connection, int $serviceOperationId): array
{
    if (!m35_ux_table_exists($connection, 'erp_service_operations')) {
        return [];
    }

    $rows = m35_ux_fetch_rows(
        $connection,
        'SELECT TOP 1 service_operation_id, jobcard_id, service_title, service_description,
                service_status, assigned_to_user_id, created_by_user_id, created_at, updated_at
         FROM dbo.erp_service_operations
         WHERE service_operation_id = ? AND is_active = 1',
        [$serviceOperationId]
    );

    return $rows[0] ?? [];
}

/**
 * @return array<string, string>
 */
function m35_ux_fetch_jobcard_binding($connection, int $jobcardId): array
{
    if (!m35_ux_table_exists($connection, 'erp_jobcards')) {
        return [];
    }

    $sql = 'SELECT TOP 1 j.jobcard_id, j.jobcard_number, j.jobcard_status, j.customer_id, j.vehicle_id,
                   c.full_name, c.primary_mobile, v.brand, v.model, v.plate_number, v.vin
            FROM dbo.erp_jobcards j';

    if (m35_ux_table_exists($connection, 'erp_customers')) {
        $sql .= ' LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id';
    } else {
        $sql .= ' LEFT JOIN (SELECT NULL AS customer_id, NULL AS full_name, NULL AS primary_mobile) c ON 1=0';
    }

    if (m35_ux_table_exists($connection, 'erp_vehicles')) {
        $sql .= ' LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id';
    } else {
        $sql .= ' LEFT JOIN (SELECT NULL AS vehicle_id, NULL AS brand, NULL AS model, NULL AS plate_number, NULL AS vin) v ON 1=0';
    }

    $sql .= ' WHERE j.jobcard_id = ?';

    $rows = m35_ux_fetch_rows($connection, $sql, [$jobcardId]);

    return $rows[0] ?? [];
}

/**
 * @return list<array<string, string>>
 */
function m35_ux_fetch_part_usage($connection, int $serviceOperationId): array
{
    if (!m35_ux_table_exists($connection, 'erp_jobcard_part_usage')) {
        return [];
    }

    return m35_ux_fetch_rows(
        $connection,
        'SELECT TOP 20 part_usage_id, part_id, quantity, usage_status, created_at
         FROM dbo.erp_jobcard_part_usage
         WHERE service_operation_id = ? AND is_active = 1
         ORDER BY part_usage_id DESC',
        [$serviceOperationId]
    );
}

/**
 * @return list<array<string, string>>
 */
function m35_ux_fetch_purchase_requests($connection, int $serviceOperationId): array
{
    if (!m35_ux_table_exists($connection, 'erp_purchase_requests')) {
        return [];
    }

    return m35_ux_fetch_rows(
        $connection,
        'SELECT TOP 20 purchase_request_id, requested_part_name, requested_quantity,
                request_status, requested_at
         FROM dbo.erp_purchase_requests
         WHERE service_operation_id = ? AND is_active = 1
         ORDER BY purchase_request_id DESC',
        [$serviceOperationId]
    );
}

/**
 * @return list<array<string, string>>
 */
function m35_ux_fetch_jobcard_payments($connection, int $jobcardId): array
{
    if (!m35_ux_table_exists($connection, 'erp_payments')) {
        return [];
    }

    return m35_ux_fetch_rows(
        $connection,
        'SELECT TOP 10 payment_id, payment_type, payment_amount, payment_status, received_at
         FROM dbo.erp_payments
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY payment_id DESC',
        [$jobcardId]
    );
}

/**
 * @return array<string, string>
 */
function m35_ux_fetch_qc_summary($connection, int $jobcardId): array
{
    if (!m35_ux_table_exists($connection, 'erp_qc_checks')) {
        return [];
    }

    $rows = m35_ux_fetch_rows(
        $connection,
        'SELECT TOP 1 qc_check_id, qc_status, checked_at
         FROM dbo.erp_qc_checks
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY qc_check_id DESC',
        [$jobcardId]
    );

    return $rows[0] ?? [];
}

/**
 * @return array<string, string>
 */
function m35_ux_fetch_delivery_summary($connection, int $jobcardId): array
{
    if (!m35_ux_table_exists($connection, 'erp_delivery_controls')) {
        return [];
    }

    $rows = m35_ux_fetch_rows(
        $connection,
        'SELECT TOP 1 delivery_control_id, delivery_status, delivery_allowed, released_at
         FROM dbo.erp_delivery_controls
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY delivery_control_id DESC',
        [$jobcardId]
    );

    return $rows[0] ?? [];
}

/**
 * @return list<array<string, string>>
 */
function m35_ux_fetch_service_history($connection, int $serviceOperationId): array
{
    if (!m35_ux_table_exists($connection, 'erp_service_operation_change_history')) {
        return [];
    }

    return m35_ux_fetch_rows(
        $connection,
        'SELECT TOP 20 history_id, action_code, old_status, new_status, changed_by_user_id, changed_at, change_note
         FROM dbo.erp_service_operation_change_history
         WHERE service_operation_id = ?
         ORDER BY history_id DESC',
        [$serviceOperationId]
    );
}

/**
 * @return array<string, list<array<string, string>>>
 */
function m35_ux_group_by_status(array $operations): array
{
    $grouped = [];

    foreach (M35_UX_BOARD_STATUSES as $status) {
        $grouped[$status] = [];
    }

    foreach ($operations as $op) {
        $status = strtoupper(trim((string)($op['service_status'] ?? '')));

        if (!isset($grouped[$status])) {
            $grouped[$status] = [];
        }

        $grouped[$status][] = $op;
    }

    return $grouped;
}

function m35_ux_status_badge_class(string $status): string
{
    $status = strtoupper(trim($status));

    if (in_array($status, ['ASSIGNED', 'IN_PROGRESS'], true)) {
        return 'm360-badge-primary';
    }

    if (in_array($status, ['WAITING_PARTS', 'DRAFT', 'QC_REJECTED'], true)) {
        return 'm360-badge-warning';
    }

    if ($status === 'DONE') {
        return 'm360-badge-success';
    }

    if ($status === 'CANCELLED') {
        return 'm360-badge-danger';
    }

    return 'm360-badge-neutral';
}

function m35_ux_progress_index(string $status): int
{
    $index = array_search(strtoupper(trim($status)), M35_UX_BOARD_STATUSES, true);

    return $index === false ? 0 : (int)$index;
}

function m35_ux_render_so_css_link(): void
{
    echo '<link rel="stylesheet" href="' . m35_ux_h('assets/moghare360-ui/moghare360-service-operation-ux.css') . '">' . "\n";
}
