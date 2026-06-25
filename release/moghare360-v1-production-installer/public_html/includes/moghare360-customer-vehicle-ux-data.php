<?php
declare(strict_types=1);

/**
 * MOGHARE360 Customer & Vehicle UX — read-only data helpers
 *
 * Mission 34 — SELECT only. No write. No auth architecture change.
 */

const M34_UX_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const M34_UX_PLACEHOLDER_ACTIONS = [
    'customer.vehicle.create' => 'placeholder_customer_vehicle_create',
    'customer.vehicle.view' => 'placeholder_customer_vehicle_view',
];

function m34_ux_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function m34_ux_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function m34_ux_bootstrap_auth(): void
{
    m34_ux_require_first_existing(m34_ux_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    m34_ux_require_first_existing(m34_ux_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
}

function m34_ux_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m34_ux_display(string $value): string
{
    return m34_ux_h(trim($value) === '' ? '—' : $value);
}

function m34_ux_get_query(string $key): string
{
    if (!isset($_GET[$key])) {
        return '';
    }

    return trim((string)$_GET[$key]);
}

/**
 * @return array<string, mixed>
 */
function m34_ux_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(M34_UX_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === M34_UX_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

/**
 * @return list<array<string, string>>
 */
function m34_ux_fetch_rows($connection, string $sql, array $params = []): array
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

function m34_ux_scalar($connection, string $sql, array $params = []): int
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

function m34_ux_table_exists($connection, string $tableName): bool
{
    $rows = m34_ux_fetch_rows(
        $connection,
        "SELECT 1 AS ok FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = ?",
        [$tableName]
    );

    return $rows !== [];
}

/**
 * @return array{connection: resource|false, userId: int, error: string}
 */
function m34_ux_connect(string $guardAction = 'customer.vehicle.view'): array
{
    $result = [
        'connection' => false,
        'userId' => M34_UX_PLATFORM_OWNER_ID,
        'error' => '',
    ];

    try {
        m34_ux_bootstrap_auth();
        erp_auth_context_start();

        $connection = erp_auth_create_local_odbc_connection();
        $userId = erp_auth_current_user_id();

        if ($userId !== M34_UX_PLATFORM_OWNER_ID) {
            throw new RuntimeException('Access denied.');
        }

        $user = erp_auth_load_current_user($connection);

        if ($user === null) {
            throw new RuntimeException('Access denied.');
        }

        $guard = m34_ux_guard_eval($connection, $userId, $guardAction);

        if (empty($guard['allowed'])) {
            throw new RuntimeException('Access denied.');
        }

        $result['connection'] = $connection;
        $result['userId'] = $userId;
    } catch (Throwable $exception) {
        $result['error'] = 'Customer/Vehicle UX data could not be loaded.';
    }

    return $result;
}

/**
 * @return array<string, int>
 */
function m34_ux_fetch_kpi_counts($connection): array
{
    $customers = 0;
    $vehicles = 0;
    $relations = 0;
    $jobcards = 0;

    if (m34_ux_table_exists($connection, 'erp_customers')) {
        $customers = m34_ux_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_customers');
    }

    if (m34_ux_table_exists($connection, 'erp_vehicles')) {
        $vehicles = m34_ux_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_vehicles');
    }

    if (m34_ux_table_exists($connection, 'erp_customer_vehicle_relations')) {
        $relations = m34_ux_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_customer_vehicle_relations WHERE lifecycle_state = 'ACTIVE'"
        );
    }

    if (m34_ux_table_exists($connection, 'erp_jobcards')) {
        $jobcards = m34_ux_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_jobcards');
    }

    return [
        'customers' => $customers,
        'vehicles' => $vehicles,
        'relations' => $relations,
        'jobcards' => $jobcards,
    ];
}

/**
 * @return list<array<string, string>>
 */
function m34_ux_fetch_customers($connection, string $qCustomer = '', string $qPhone = ''): array
{
    if (!m34_ux_table_exists($connection, 'erp_customers')) {
        return [];
    }

    $sql = 'SELECT TOP 50 customer_id, customer_code, full_name, primary_mobile, secondary_mobile,
            email, city, lifecycle_state, created_at FROM dbo.erp_customers WHERE 1=1';
    $params = [];

    if ($qCustomer !== '') {
        $sql .= ' AND (full_name LIKE ? OR customer_code LIKE ?)';
        $like = '%' . $qCustomer . '%';
        $params[] = $like;
        $params[] = $like;
    }

    if ($qPhone !== '') {
        $sql .= ' AND (primary_mobile LIKE ? OR secondary_mobile LIKE ?)';
        $likePhone = '%' . $qPhone . '%';
        $params[] = $likePhone;
        $params[] = $likePhone;
    }

    $sql .= ' ORDER BY customer_id DESC';

    return m34_ux_fetch_rows($connection, $sql, $params);
}

/**
 * @return list<array<string, string>>
 */
function m34_ux_fetch_vehicles($connection, string $qPlate = '', string $qVin = ''): array
{
    if (!m34_ux_table_exists($connection, 'erp_vehicles')) {
        return [];
    }

    $sql = 'SELECT TOP 50 vehicle_id, vehicle_code, brand, model, plate_number, vin,
            production_year, color, mileage, lifecycle_state, created_at
            FROM dbo.erp_vehicles WHERE 1=1';
    $params = [];

    if ($qPlate !== '') {
        $sql .= ' AND plate_number LIKE ?';
        $params[] = '%' . $qPlate . '%';
    }

    if ($qVin !== '') {
        $sql .= ' AND vin LIKE ?';
        $params[] = '%' . $qVin . '%';
    }

    $sql .= ' ORDER BY vehicle_id DESC';

    return m34_ux_fetch_rows($connection, $sql, $params);
}

/**
 * @return list<array<string, string>>
 */
function m34_ux_fetch_relations_workbench($connection, string $qCustomer = '', string $qPhone = '', string $qPlate = '', string $qVin = ''): array
{
    if (!m34_ux_table_exists($connection, 'erp_customer_vehicle_relations')) {
        return [];
    }

    $sql = 'SELECT TOP 50
            r.relation_id, r.relation_type, r.lifecycle_state, r.is_primary_owner,
            c.customer_id, c.full_name, c.primary_mobile,
            v.vehicle_id, v.brand, v.model, v.plate_number, v.vin
        FROM dbo.erp_customer_vehicle_relations r
        JOIN dbo.erp_customers c ON c.customer_id = r.customer_id
        JOIN dbo.erp_vehicles v ON v.vehicle_id = r.vehicle_id
        WHERE 1=1';
    $params = [];

    if ($qCustomer !== '') {
        $sql .= ' AND (c.full_name LIKE ? OR c.customer_code LIKE ?)';
        $like = '%' . $qCustomer . '%';
        $params[] = $like;
        $params[] = $like;
    }

    if ($qPhone !== '') {
        $sql .= ' AND (c.primary_mobile LIKE ? OR c.secondary_mobile LIKE ?)';
        $likePhone = '%' . $qPhone . '%';
        $params[] = $likePhone;
        $params[] = $likePhone;
    }

    if ($qPlate !== '') {
        $sql .= ' AND v.plate_number LIKE ?';
        $params[] = '%' . $qPlate . '%';
    }

    if ($qVin !== '') {
        $sql .= ' AND v.vin LIKE ?';
        $params[] = '%' . $qVin . '%';
    }

    $sql .= ' ORDER BY r.relation_id DESC';

    return m34_ux_fetch_rows($connection, $sql, $params);
}

function m34_ux_resolve_customer_id($connection, int $requested = 0): int
{
    if ($requested > 0 && m34_ux_table_exists($connection, 'erp_customers')) {
        $exists = m34_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_customers WHERE customer_id = ?',
            [$requested]
        );

        if ($exists > 0) {
            return $requested;
        }
    }

    if (!m34_ux_table_exists($connection, 'erp_customers')) {
        return 0;
    }

    $rows = m34_ux_fetch_rows($connection, 'SELECT TOP 1 customer_id FROM dbo.erp_customers ORDER BY customer_id');

    return isset($rows[0]['customer_id']) ? (int)$rows[0]['customer_id'] : 0;
}

function m34_ux_resolve_vehicle_id($connection, int $requested = 0): int
{
    if ($requested > 0 && m34_ux_table_exists($connection, 'erp_vehicles')) {
        $exists = m34_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_vehicles WHERE vehicle_id = ?',
            [$requested]
        );

        if ($exists > 0) {
            return $requested;
        }
    }

    if (!m34_ux_table_exists($connection, 'erp_vehicles')) {
        return 0;
    }

    $rows = m34_ux_fetch_rows($connection, 'SELECT TOP 1 vehicle_id FROM dbo.erp_vehicles ORDER BY vehicle_id');

    return isset($rows[0]['vehicle_id']) ? (int)$rows[0]['vehicle_id'] : 0;
}

function m34_ux_parse_entity_id(string $key): int
{
    if (!isset($_GET[$key])) {
        return 0;
    }

    $raw = trim((string)$_GET[$key]);

    return ($raw !== '' && ctype_digit($raw)) ? (int)$raw : 0;
}

/**
 * @return array<string, string>
 */
function m34_ux_fetch_customer_detail($connection, int $customerId): array
{
    if (!m34_ux_table_exists($connection, 'erp_customers')) {
        return [];
    }

    $rows = m34_ux_fetch_rows(
        $connection,
        'SELECT TOP 1 customer_id, customer_code, customer_type, full_name, national_id,
                primary_mobile, secondary_mobile, email, address, city, notes,
                lifecycle_state, created_at, updated_at
         FROM dbo.erp_customers WHERE customer_id = ?',
        [$customerId]
    );

    return $rows[0] ?? [];
}

/**
 * @return list<array<string, string>>
 */
function m34_ux_fetch_customer_phones($connection, int $customerId): array
{
    if (!m34_ux_table_exists($connection, 'erp_customer_phones')) {
        return [];
    }

    return m34_ux_fetch_rows(
        $connection,
        'SELECT phone_id, phone_type, phone_number, is_primary, is_verified, lifecycle_state
         FROM dbo.erp_customer_phones
         WHERE customer_id = ? AND lifecycle_state = \'ACTIVE\'
         ORDER BY is_primary DESC, phone_id',
        [$customerId]
    );
}

/**
 * @return list<array<string, string>>
 */
function m34_ux_fetch_customer_vehicles($connection, int $customerId): array
{
    if (!m34_ux_table_exists($connection, 'erp_customer_vehicle_relations')) {
        return [];
    }

    return m34_ux_fetch_rows(
        $connection,
        'SELECT r.relation_id, r.relation_type, r.is_primary_owner, r.lifecycle_state,
                v.vehicle_id, v.vehicle_code, v.brand, v.model, v.plate_number, v.vin
         FROM dbo.erp_customer_vehicle_relations r
         JOIN dbo.erp_vehicles v ON v.vehicle_id = r.vehicle_id
         WHERE r.customer_id = ?
         ORDER BY r.is_primary_owner DESC, r.relation_id DESC',
        [$customerId]
    );
}

/**
 * @return list<array<string, string>>
 */
function m34_ux_fetch_customer_jobcards($connection, int $customerId): array
{
    if (!m34_ux_table_exists($connection, 'erp_jobcards')) {
        return [];
    }

    return m34_ux_fetch_rows(
        $connection,
        'SELECT TOP 20 j.jobcard_id, j.jobcard_number, j.jobcard_status, j.reception_at, j.created_at,
                v.brand, v.model, v.plate_number
         FROM dbo.erp_jobcards j
         JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
         WHERE j.customer_id = ?
         ORDER BY j.jobcard_id DESC',
        [$customerId]
    );
}

/**
 * @return array<string, string>
 */
function m34_ux_fetch_customer_payment_summary($connection, int $customerId): array
{
    if (!m34_ux_table_exists($connection, 'erp_payments')) {
        return [];
    }

    $rows = m34_ux_fetch_rows(
        $connection,
        "SELECT COUNT(*) AS payment_count,
                SUM(CASE WHEN payment_status = 'RECEIVED' THEN payment_amount ELSE 0 END) AS received_total
         FROM dbo.erp_payments
         WHERE customer_id = ? AND is_active = 1",
        [$customerId]
    );

    return $rows[0] ?? [];
}

/**
 * @return array<string, string>
 */
function m34_ux_fetch_vehicle_detail($connection, int $vehicleId): array
{
    if (!m34_ux_table_exists($connection, 'erp_vehicles')) {
        return [];
    }

    $rows = m34_ux_fetch_rows(
        $connection,
        'SELECT TOP 1 vehicle_id, vehicle_code, plate_number, vin, chassis_number, engine_number,
                brand, model, production_year, color, mileage, fuel_type, transmission_type,
                lifecycle_state, notes, created_at, updated_at
         FROM dbo.erp_vehicles WHERE vehicle_id = ?',
        [$vehicleId]
    );

    return $rows[0] ?? [];
}

/**
 * @return list<array<string, string>>
 */
function m34_ux_fetch_vehicle_customers($connection, int $vehicleId): array
{
    if (!m34_ux_table_exists($connection, 'erp_customer_vehicle_relations')) {
        return [];
    }

    return m34_ux_fetch_rows(
        $connection,
        'SELECT r.relation_id, r.relation_type, r.is_primary_owner, r.lifecycle_state,
                c.customer_id, c.full_name, c.primary_mobile, c.customer_code
         FROM dbo.erp_customer_vehicle_relations r
         JOIN dbo.erp_customers c ON c.customer_id = r.customer_id
         WHERE r.vehicle_id = ?
         ORDER BY r.is_primary_owner DESC, r.relation_id DESC',
        [$vehicleId]
    );
}

/**
 * @return list<array<string, string>>
 */
function m34_ux_fetch_vehicle_jobcards($connection, int $vehicleId): array
{
    if (!m34_ux_table_exists($connection, 'erp_jobcards')) {
        return [];
    }

    return m34_ux_fetch_rows(
        $connection,
        'SELECT TOP 20 jobcard_id, jobcard_number, jobcard_status, reception_at, created_at, customer_id
         FROM dbo.erp_jobcards
         WHERE vehicle_id = ?
         ORDER BY jobcard_id DESC',
        [$vehicleId]
    );
}

/**
 * @return list<array<string, string>>
 */
function m34_ux_fetch_vehicle_service_ops($connection, int $vehicleId): array
{
    if (!m34_ux_table_exists($connection, 'erp_service_operations') || !m34_ux_table_exists($connection, 'erp_jobcards')) {
        return [];
    }

    return m34_ux_fetch_rows(
        $connection,
        'SELECT TOP 20 so.service_operation_id, so.service_title, so.service_status, so.created_at, j.jobcard_id, j.jobcard_number
         FROM dbo.erp_service_operations so
         JOIN dbo.erp_jobcards j ON j.jobcard_id = so.jobcard_id
         WHERE j.vehicle_id = ? AND so.is_active = 1
         ORDER BY so.service_operation_id DESC',
        [$vehicleId]
    );
}

/**
 * @return array<string, int>
 */
function m34_ux_fetch_vehicle_summaries($connection, int $vehicleId): array
{
    $parts = 0;
    $payments = 0;
    $qc = 0;

    if (m34_ux_table_exists($connection, 'erp_jobcard_part_usage') && m34_ux_table_exists($connection, 'erp_jobcards')) {
        $parts = m34_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_jobcard_part_usage pu
             JOIN dbo.erp_jobcards j ON j.jobcard_id = pu.jobcard_id
             WHERE j.vehicle_id = ? AND pu.is_active = 1',
            [$vehicleId]
        );
    }

    if (m34_ux_table_exists($connection, 'erp_payments') && m34_ux_table_exists($connection, 'erp_jobcards')) {
        $payments = m34_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_payments p
             JOIN dbo.erp_jobcards j ON j.jobcard_id = p.jobcard_id
             WHERE j.vehicle_id = ? AND p.is_active = 1',
            [$vehicleId]
        );
    }

    if (m34_ux_table_exists($connection, 'erp_qc_checks') && m34_ux_table_exists($connection, 'erp_jobcards')) {
        $qc = m34_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_qc_checks q
             JOIN dbo.erp_jobcards j ON j.jobcard_id = q.jobcard_id
             WHERE j.vehicle_id = ? AND q.is_active = 1',
            [$vehicleId]
        );
    }

    return ['parts' => $parts, 'payments' => $payments, 'qc' => $qc];
}

/**
 * @return list<array<string, string>>
 */
function m34_ux_fetch_service_history_timeline($connection, int $customerId): array
{
    if (!m34_ux_table_exists($connection, 'erp_jobcards') || !m34_ux_table_exists($connection, 'erp_jobcard_change_history')) {
        return [];
    }

    return m34_ux_fetch_rows(
        $connection,
        'SELECT TOP 30 h.history_id, h.jobcard_id, j.jobcard_number, h.change_type AS action_code,
                h.new_status, h.changed_by_user_id, h.changed_at, h.change_summary AS change_note
         FROM dbo.erp_jobcard_change_history h
         JOIN dbo.erp_jobcards j ON j.jobcard_id = h.jobcard_id
         WHERE j.customer_id = ?
         ORDER BY h.changed_at DESC, h.history_id DESC',
        [$customerId]
    );
}

function m34_ux_status_badge_class(string $status): string
{
    $status = strtoupper(trim($status));

    if (in_array($status, ['ACTIVE', 'RECEIVED', 'IN_PROGRESS', 'IN_SERVICE', 'OWNER'], true)) {
        return 'm360-badge-primary';
    }

    if (in_array($status, ['PENDING', 'WAITING_PARTS', 'DRAFT'], true)) {
        return 'm360-badge-warning';
    }

    if (in_array($status, ['DONE', 'PASSED', 'READY', 'RELEASED'], true)) {
        return 'm360-badge-success';
    }

    return 'm360-badge-neutral';
}

function m34_ux_render_cv_css_link(): void
{
    echo '<link rel="stylesheet" href="' . m34_ux_h('assets/moghare360-ui/moghare360-customer-vehicle-ux.css') . '">' . "\n";
}
