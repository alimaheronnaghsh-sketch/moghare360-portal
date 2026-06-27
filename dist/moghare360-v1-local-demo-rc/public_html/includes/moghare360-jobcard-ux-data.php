<?php
declare(strict_types=1);

/**
 * MOGHARE360 JobCard UX — read-only data helpers
 *
 * Mission 33 — SELECT only. No write. No auth architecture change.
 */

const M33_UX_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const M33_UX_PLACEHOLDER_ACTIONS = [
    'jobcard.create' => 'placeholder_jobcard_create',
    'jobcard.view' => 'placeholder_jobcard_view',
    'jobcard.list' => 'placeholder_jobcard_list',
];

function m33_ux_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function m33_ux_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function m33_ux_bootstrap_auth(): void
{
    m33_ux_require_first_existing(m33_ux_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    m33_ux_require_first_existing(m33_ux_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
}

function m33_ux_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m33_ux_display(string $value): string
{
    return m33_ux_h(trim($value) === '' ? '—' : $value);
}

function m33_ux_parse_jobcard_id(int $default = 1): int
{
    if (!isset($_GET['jobcard_id'])) {
        return $default;
    }

    $raw = trim((string)$_GET['jobcard_id']);

    return ($raw !== '' && ctype_digit($raw)) ? (int)$raw : $default;
}

/**
 * @return array<string, mixed>
 */
function m33_ux_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(M33_UX_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === M33_UX_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

/**
 * @return list<array<string, string>>
 */
function m33_ux_fetch_rows($connection, string $sql, array $params = []): array
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

function m33_ux_scalar($connection, string $sql, array $params = []): int
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

function m33_ux_table_exists($connection, string $tableName): bool
{
    $rows = m33_ux_fetch_rows(
        $connection,
        "SELECT 1 AS ok FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = ?",
        [$tableName]
    );

    return $rows !== [];
}

/**
 * @return array{connection: resource|false, userId: int, error: string}
 */
function m33_ux_connect(string $guardAction): array
{
    $result = [
        'connection' => false,
        'userId' => M33_UX_PLATFORM_OWNER_ID,
        'error' => '',
    ];

    try {
        m33_ux_bootstrap_auth();
        erp_auth_context_start();

        $connection = erp_auth_create_local_odbc_connection();
        $userId = erp_auth_current_user_id();

        if ($userId !== M33_UX_PLATFORM_OWNER_ID) {
            throw new RuntimeException('Access denied.');
        }

        $user = erp_auth_load_current_user($connection);

        if ($user === null) {
            throw new RuntimeException('Access denied.');
        }

        $guard = m33_ux_guard_eval($connection, $userId, $guardAction);

        if (empty($guard['allowed'])) {
            throw new RuntimeException('Access denied.');
        }

        $result['connection'] = $connection;
        $result['userId'] = $userId;
    } catch (Throwable $exception) {
        $result['error'] = 'JobCard UX data could not be loaded.';
    }

    return $result;
}

/**
 * @return list<array<string, string>>
 */
function m33_ux_fetch_jobcard_list($connection): array
{
    return m33_ux_fetch_rows(
        $connection,
        'SELECT TOP 50
            j.jobcard_id,
            j.jobcard_number,
            j.customer_id,
            c.full_name,
            c.primary_mobile,
            j.vehicle_id,
            v.brand,
            v.model,
            v.plate_number,
            j.jobcard_status,
            j.reception_at,
            j.priority_level,
            j.lifecycle_state,
            j.created_at
        FROM dbo.erp_jobcards j
        JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
        JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
        ORDER BY j.jobcard_id DESC'
    );
}

/**
 * @return array<string, int>
 */
function m33_ux_fetch_kpi_counts($connection): array
{
    $active = m33_ux_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_jobcards WHERE lifecycle_state = 'ACTIVE'"
    );

    $waitingParts = m33_ux_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_jobcards WHERE jobcard_status = 'WAITING_PARTS'"
    );

    if ($waitingParts === 0 && m33_ux_table_exists($connection, 'erp_service_operations')) {
        $waitingParts = m33_ux_scalar(
            $connection,
            "SELECT COUNT(DISTINCT jobcard_id) FROM dbo.erp_service_operations
             WHERE is_active = 1 AND service_status = 'WAITING_PARTS'"
        );
    }

    $qcPending = 0;

    if (m33_ux_table_exists($connection, 'erp_qc_checks')) {
        $qcPending = m33_ux_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_qc_checks WHERE is_active = 1 AND qc_status = 'PENDING'"
        );
    }

    if ($qcPending === 0) {
        $qcPending = m33_ux_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_jobcards WHERE jobcard_status IN ('QC_PENDING', 'QC_READY')"
        );
    }

    $readyDelivery = 0;

    if (m33_ux_table_exists($connection, 'erp_delivery_controls')) {
        $readyDelivery = m33_ux_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_delivery_controls
             WHERE is_active = 1 AND delivery_status IN ('READY', 'RELEASED')"
        );
    }

    return [
        'active' => $active,
        'waiting_parts' => $waitingParts,
        'qc_pending' => $qcPending,
        'ready_delivery' => $readyDelivery,
    ];
}

/**
 * @return array<string, string>
 */
function m33_ux_fetch_jobcard_detail($connection, int $jobcardId): array
{
    $rows = m33_ux_fetch_rows(
        $connection,
        'SELECT TOP 1
            j.jobcard_id,
            j.jobcard_number,
            j.customer_id,
            j.vehicle_id,
            j.relation_id,
            j.jobcard_status,
            j.reception_at,
            j.promised_at,
            j.intake_mileage,
            j.fuel_level,
            j.customer_complaint,
            j.requested_services_summary,
            j.priority_level,
            j.lifecycle_state,
            j.created_at,
            c.full_name,
            c.primary_mobile,
            c.customer_code,
            v.brand,
            v.model,
            v.plate_number,
            v.vin,
            v.vehicle_code
        FROM dbo.erp_jobcards j
        JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
        JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
        WHERE j.jobcard_id = ?',
        [$jobcardId]
    );

    return $rows[0] ?? [];
}

/**
 * @return list<array<string, string>>
 */
function m33_ux_fetch_service_operations($connection, int $jobcardId): array
{
    if (!m33_ux_table_exists($connection, 'erp_service_operations')) {
        return [];
    }

    return m33_ux_fetch_rows(
        $connection,
        'SELECT service_operation_id, service_title, service_status, assigned_to_user_id, created_at
         FROM dbo.erp_service_operations
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY service_operation_id',
        [$jobcardId]
    );
}

/**
 * @return list<array<string, string>>
 */
function m33_ux_fetch_part_usage($connection, int $jobcardId): array
{
    if (!m33_ux_table_exists($connection, 'erp_jobcard_part_usage')) {
        return [];
    }

    return m33_ux_fetch_rows(
        $connection,
        'SELECT TOP 20 part_usage_id, part_id, quantity, usage_status, created_at
         FROM dbo.erp_jobcard_part_usage
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY part_usage_id DESC',
        [$jobcardId]
    );
}

/**
 * @return list<array<string, string>>
 */
function m33_ux_fetch_payments($connection, int $jobcardId): array
{
    if (!m33_ux_table_exists($connection, 'erp_payments')) {
        return [];
    }

    return m33_ux_fetch_rows(
        $connection,
        'SELECT payment_id, payment_type, payment_method, payment_amount, payment_status, received_at
         FROM dbo.erp_payments
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY payment_id DESC',
        [$jobcardId]
    );
}

/**
 * @return array<string, string>
 */
function m33_ux_fetch_qc_summary($connection, int $jobcardId): array
{
    if (!m33_ux_table_exists($connection, 'erp_qc_checks')) {
        return [];
    }

    $rows = m33_ux_fetch_rows(
        $connection,
        'SELECT TOP 1 qc_check_id, qc_status, checked_at, qc_note
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
function m33_ux_fetch_delivery_summary($connection, int $jobcardId): array
{
    if (!m33_ux_table_exists($connection, 'erp_delivery_controls')) {
        return [];
    }

    $rows = m33_ux_fetch_rows(
        $connection,
        'SELECT TOP 1 delivery_control_id, delivery_status, delivery_allowed, block_reason, released_at
         FROM dbo.erp_delivery_controls
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY delivery_control_id DESC',
        [$jobcardId]
    );

    return $rows[0] ?? [];
}

function m33_ux_status_badge_class(string $status): string
{
    $status = strtoupper(trim($status));

    if (in_array($status, ['RECEIVED', 'IN_PROGRESS', 'IN_SERVICE', 'ASSIGNED'], true)) {
        return 'm360-badge-primary';
    }

    if (in_array($status, ['WAITING_PARTS', 'QC_PENDING', 'PENDING', 'BLOCKED', 'DRAFT'], true)) {
        return 'm360-badge-warning';
    }

    if (in_array($status, ['DONE', 'PASSED', 'READY', 'RELEASED', 'RECEIVED'], true)) {
        return 'm360-badge-success';
    }

    if (in_array($status, ['CANCELLED', 'FAILED', 'QC_REJECTED'], true)) {
        return 'm360-badge-danger';
    }

    return 'm360-badge-neutral';
}

/**
 * @return list<array<string, string>>
 */
function m33_ux_fetch_combined_timeline($connection, int $jobcardId): array
{
    $items = [];

    $sources = [
        [
            'table' => 'erp_jobcard_change_history',
            'sql' => 'SELECT history_id, change_type AS action_code, new_status, changed_by_user_id, changed_at, change_summary AS change_note
                      FROM dbo.erp_jobcard_change_history WHERE jobcard_id = ?',
            'source' => 'JobCard',
        ],
        [
            'table' => 'erp_service_operation_change_history',
            'sql' => 'SELECT history_id, action_code, new_status, changed_by_user_id, changed_at, change_note
                      FROM dbo.erp_service_operation_change_history WHERE jobcard_id = ?',
            'source' => 'Service',
        ],
        [
            'table' => 'erp_jobcard_part_usage_history',
            'sql' => 'SELECT history_id, action_code, new_status, changed_by_user_id, changed_at, change_note
                      FROM dbo.erp_jobcard_part_usage_history WHERE jobcard_id = ?',
            'source' => 'Parts',
        ],
        [
            'table' => 'erp_purchase_request_history',
            'sql' => 'SELECT history_id, action_code, new_status, changed_by_user_id, changed_at, change_note
                      FROM dbo.erp_purchase_request_history WHERE jobcard_id = ?',
            'source' => 'Purchase',
        ],
        [
            'table' => 'erp_payment_history',
            'sql' => 'SELECT history_id, action_code, new_status, changed_by_user_id, changed_at, change_note
                      FROM dbo.erp_payment_history WHERE jobcard_id = ?',
            'source' => 'Payment',
        ],
        [
            'table' => 'erp_qc_check_history',
            'sql' => 'SELECT history_id, action_code, new_status, changed_by_user_id, changed_at, change_note
                      FROM dbo.erp_qc_check_history WHERE jobcard_id = ?',
            'source' => 'QC',
        ],
        [
            'table' => 'erp_delivery_control_history',
            'sql' => 'SELECT history_id, action_code, new_status, changed_by_user_id, changed_at, change_note
                      FROM dbo.erp_delivery_control_history WHERE jobcard_id = ?',
            'source' => 'Delivery',
        ],
    ];

    foreach ($sources as $def) {
        if (!m33_ux_table_exists($connection, $def['table'])) {
            continue;
        }

        $rows = m33_ux_fetch_rows($connection, $def['sql'], [$jobcardId]);

        foreach ($rows as $row) {
            $items[] = [
                'source' => $def['source'],
                'action_code' => $row['action_code'] ?? '',
                'status' => $row['new_status'] ?? '',
                'user_id' => $row['changed_by_user_id'] ?? '',
                'timestamp' => $row['changed_at'] ?? '',
                'note' => $row['change_note'] ?? '',
                'history_id' => $row['history_id'] ?? '',
            ];
        }
    }

    usort($items, static function (array $a, array $b): int {
        return strcmp($b['timestamp'], $a['timestamp']);
    });

    return $items;
}

function m33_ux_jobcard_status_flow(): array
{
    return ['DRAFT', 'RECEIVED', 'IN_PROGRESS', 'WAITING_PARTS', 'IN_SERVICE', 'QC_PENDING', 'QC_READY', 'READY', 'DELIVERED'];
}

function m33_ux_render_jobcard_css_link(): void
{
    echo '<link rel="stylesheet" href="' . m33_ux_h('assets/moghare360-ui/moghare360-jobcard-ux.css') . '">' . "\n";
}
