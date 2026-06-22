<?php
declare(strict_types=1);

/**
 * MOGHARE360 Finance Preview UX — read-only data helpers
 *
 * Mission 36 — SELECT only. No write. No invoice/accounting/tax.
 */

const M36_UX_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const M36_UX_PLACEHOLDER_ACTIONS = [
    'payment.create' => 'placeholder_payment_create',
    'payment.view' => 'placeholder_payment_view',
    'payment.list' => 'placeholder_payment_list',
    'payment.summary.view' => 'placeholder_payment_summary_view',
    'payment.cancel' => 'placeholder_payment_cancel',
    'payment.reverse' => 'placeholder_payment_reverse',
];

function m36_ux_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function m36_ux_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function m36_ux_bootstrap_auth(): void
{
    m36_ux_require_first_existing(m36_ux_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    m36_ux_require_first_existing(m36_ux_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
}

function m36_ux_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m36_ux_display(string $value): string
{
    return m36_ux_h(trim($value) === '' ? '—' : $value);
}

function m36_ux_parse_int(string $key, int $default = 0): int
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
function m36_ux_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(M36_UX_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === M36_UX_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

/**
 * @return list<array<string, string>>
 */
function m36_ux_fetch_rows($connection, string $sql, array $params = []): array
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

function m36_ux_scalar($connection, string $sql, array $params = []): string
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return '0';
    }

    if (@odbc_fetch_row($statement) !== true) {
        return '0';
    }

    $value = @odbc_result($statement, 1);

    return $value === false || $value === null ? '0' : (string)$value;
}

function m36_ux_table_exists($connection, string $tableName): bool
{
    $rows = m36_ux_fetch_rows(
        $connection,
        "SELECT 1 AS ok FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = ?",
        [$tableName]
    );

    return $rows !== [];
}

/**
 * @return array{connection: resource|false, userId: int, error: string}
 */
function m36_ux_connect(string $guardAction = 'payment.summary.view'): array
{
    $result = [
        'connection' => false,
        'userId' => M36_UX_PLATFORM_OWNER_ID,
        'error' => '',
    ];

    try {
        m36_ux_bootstrap_auth();
        erp_auth_context_start();

        $connection = erp_auth_create_local_odbc_connection();
        $userId = erp_auth_current_user_id();

        if ($userId !== M36_UX_PLATFORM_OWNER_ID) {
            throw new RuntimeException('Access denied.');
        }

        $user = erp_auth_load_current_user($connection);

        if ($user === null) {
            throw new RuntimeException('Access denied.');
        }

        $guard = m36_ux_guard_eval($connection, $userId, $guardAction);

        if (empty($guard['allowed'])) {
            throw new RuntimeException('Access denied.');
        }

        $result['connection'] = $connection;
        $result['userId'] = $userId;
    } catch (Throwable $exception) {
        $result['error'] = 'Finance Preview UX data could not be loaded.';
    }

    return $result;
}

/**
 * @return array<string, string>
 */
function m36_ux_fetch_workbench_kpi($connection): array
{
    if (!m36_ux_table_exists($connection, 'erp_payments')) {
        return [
            'total_received' => '0',
            'payment_count' => '0',
            'jobcards_with_payment' => '0',
            'preview_only' => '0',
        ];
    }

    $totalReceived = m36_ux_scalar(
        $connection,
        "SELECT COALESCE(SUM(payment_amount), 0) FROM dbo.erp_payments WHERE payment_status = 'RECEIVED' AND is_active = 1"
    );

    $paymentCount = m36_ux_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_payments WHERE payment_status = 'RECEIVED' AND is_active = 1"
    );

    $jobcardsWithPayment = m36_ux_scalar(
        $connection,
        "SELECT COUNT(DISTINCT jobcard_id) FROM dbo.erp_payments WHERE is_active = 1"
    );

    $previewOnly = '0';

    if (m36_ux_table_exists($connection, 'erp_jobcards')) {
        $previewOnly = m36_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE lifecycle_state = \'ACTIVE\''
        );
    }

    return [
        'total_received' => $totalReceived,
        'payment_count' => $paymentCount,
        'jobcards_with_payment' => $jobcardsWithPayment,
        'preview_only' => $previewOnly,
    ];
}

/**
 * @return list<array<string, string>>
 */
function m36_ux_fetch_workbench_jobcards($connection): array
{
    if (!m36_ux_table_exists($connection, 'erp_jobcards')) {
        return [];
    }

    if (!m36_ux_table_exists($connection, 'erp_payments')) {
        return m36_ux_fetch_rows(
            $connection,
            'SELECT TOP 50 j.jobcard_id, j.jobcard_number, j.jobcard_status, c.full_name,
                    0 AS payment_count, 0 AS total_received
             FROM dbo.erp_jobcards j
             LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
             WHERE j.lifecycle_state = \'ACTIVE\'
             ORDER BY j.jobcard_id DESC'
        );
    }

    return m36_ux_fetch_rows(
        $connection,
        "SELECT TOP 50
            j.jobcard_id,
            j.jobcard_number,
            j.jobcard_status,
            c.full_name,
            COALESCE(p.payment_count, 0) AS payment_count,
            COALESCE(p.total_received, 0) AS total_received
        FROM dbo.erp_jobcards j
        LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
        LEFT JOIN (
            SELECT jobcard_id,
                   COUNT(*) AS payment_count,
                   SUM(CASE WHEN payment_status = 'RECEIVED' THEN payment_amount ELSE 0 END) AS total_received
            FROM dbo.erp_payments
            WHERE is_active = 1
            GROUP BY jobcard_id
        ) p ON p.jobcard_id = j.jobcard_id
        WHERE j.lifecycle_state = 'ACTIVE'
        ORDER BY COALESCE(p.payment_count, 0) DESC, j.jobcard_id DESC"
    );
}

function m36_ux_resolve_jobcard_id($connection, int $requested = 1): int
{
    if ($requested > 0 && m36_ux_table_exists($connection, 'erp_jobcards')) {
        $exists = m36_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE jobcard_id = ?',
            [$requested]
        );

        if ((int)$exists > 0) {
            return $requested;
        }
    }

    if (!m36_ux_table_exists($connection, 'erp_jobcards')) {
        return 0;
    }

    $rows = m36_ux_fetch_rows($connection, 'SELECT TOP 1 jobcard_id FROM dbo.erp_jobcards ORDER BY jobcard_id');

    return isset($rows[0]['jobcard_id']) ? (int)$rows[0]['jobcard_id'] : 0;
}

function m36_ux_resolve_payment_id($connection, int $requested = 1): int
{
    if ($requested > 0 && m36_ux_table_exists($connection, 'erp_payments')) {
        $exists = m36_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_payments WHERE payment_id = ? AND is_active = 1',
            [$requested]
        );

        if ((int)$exists > 0) {
            return $requested;
        }
    }

    if (!m36_ux_table_exists($connection, 'erp_payments')) {
        return 0;
    }

    $rows = m36_ux_fetch_rows(
        $connection,
        'SELECT TOP 1 payment_id FROM dbo.erp_payments WHERE is_active = 1 ORDER BY payment_id'
    );

    return isset($rows[0]['payment_id']) ? (int)$rows[0]['payment_id'] : 0;
}

/**
 * @return array<string, string>
 */
function m36_ux_fetch_jobcard_summary($connection, int $jobcardId): array
{
    if (!m36_ux_table_exists($connection, 'erp_jobcards')) {
        return [];
    }

    $sql = 'SELECT TOP 1 j.jobcard_id, j.jobcard_number, j.jobcard_status, j.customer_id, j.vehicle_id,
                   c.full_name, c.primary_mobile, v.brand, v.model, v.plate_number
            FROM dbo.erp_jobcards j';

    if (m36_ux_table_exists($connection, 'erp_customers')) {
        $sql .= ' LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id';
    } else {
        $sql .= ' LEFT JOIN (SELECT NULL AS customer_id, NULL AS full_name, NULL AS primary_mobile) c ON 1=0';
    }

    if (m36_ux_table_exists($connection, 'erp_vehicles')) {
        $sql .= ' LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id';
    } else {
        $sql .= ' LEFT JOIN (SELECT NULL AS vehicle_id, NULL AS brand, NULL AS model, NULL AS plate_number) v ON 1=0';
    }

    $sql .= ' WHERE j.jobcard_id = ?';

    $rows = m36_ux_fetch_rows($connection, $sql, [$jobcardId]);

    return $rows[0] ?? [];
}

/**
 * @return array<string, string>
 */
function m36_ux_fetch_payment_summary($connection, int $jobcardId): array
{
    if (!m36_ux_table_exists($connection, 'erp_payments')) {
        return ['payment_count' => '0', 'total_received' => '0', 'latest_payment' => ''];
    }

    $count = m36_ux_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_payments WHERE jobcard_id = ? AND payment_status = 'RECEIVED' AND is_active = 1",
        [$jobcardId]
    );

    $total = m36_ux_scalar(
        $connection,
        "SELECT COALESCE(SUM(payment_amount), 0) FROM dbo.erp_payments WHERE jobcard_id = ? AND payment_status = 'RECEIVED' AND is_active = 1",
        [$jobcardId]
    );

    $latestRows = m36_ux_fetch_rows(
        $connection,
        "SELECT TOP 1 payment_id, payment_amount, payment_type, received_at
         FROM dbo.erp_payments
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY payment_id DESC",
        [$jobcardId]
    );

    $latest = '';

    if ($latestRows !== []) {
        $r = $latestRows[0];
        $latest = ($r['payment_type'] ?? '') . ' ' . ($r['payment_amount'] ?? '') . ' @ ' . ($r['received_at'] ?? '');
    }

    return [
        'payment_count' => $count,
        'total_received' => $total,
        'latest_payment' => $latest,
    ];
}

/**
 * @return list<array<string, string>>
 */
function m36_ux_fetch_jobcard_payments($connection, int $jobcardId): array
{
    if (!m36_ux_table_exists($connection, 'erp_payments')) {
        return [];
    }

    return m36_ux_fetch_rows(
        $connection,
        'SELECT payment_id, payment_type, payment_method, payment_amount, currency_code,
                payment_status, received_at
         FROM dbo.erp_payments
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY payment_id DESC',
        [$jobcardId]
    );
}

/**
 * @return list<array<string, string>>
 */
function m36_ux_fetch_service_ops_summary($connection, int $jobcardId): array
{
    if (!m36_ux_table_exists($connection, 'erp_service_operations')) {
        return [];
    }

    return m36_ux_fetch_rows(
        $connection,
        'SELECT service_operation_id, service_title, service_status
         FROM dbo.erp_service_operations
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY service_operation_id',
        [$jobcardId]
    );
}

/**
 * @return list<array<string, string>>
 */
function m36_ux_fetch_part_usage_summary($connection, int $jobcardId): array
{
    if (!m36_ux_table_exists($connection, 'erp_jobcard_part_usage')) {
        return [];
    }

    return m36_ux_fetch_rows(
        $connection,
        'SELECT TOP 20 part_usage_id, part_id, quantity, usage_status
         FROM dbo.erp_jobcard_part_usage
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY part_usage_id DESC',
        [$jobcardId]
    );
}

/**
 * @return list<array<string, string>>
 */
function m36_ux_fetch_purchase_summary($connection, int $jobcardId): array
{
    if (!m36_ux_table_exists($connection, 'erp_purchase_requests')) {
        return [];
    }

    return m36_ux_fetch_rows(
        $connection,
        'SELECT TOP 20 purchase_request_id, requested_part_name, requested_quantity, request_status
         FROM dbo.erp_purchase_requests
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY purchase_request_id DESC',
        [$jobcardId]
    );
}

/**
 * @return array{estimated_total: string, balance_preview: string, has_estimate: bool}
 */
function m36_ux_compute_balance_preview($connection, int $jobcardId, string $totalReceived): array
{
    $serviceCount = 0;
    $partCount = 0;

    if (m36_ux_table_exists($connection, 'erp_service_operations')) {
        $serviceCount = (int)m36_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_service_operations WHERE jobcard_id = ? AND is_active = 1',
            [$jobcardId]
        );
    }

    if (m36_ux_table_exists($connection, 'erp_jobcard_part_usage')) {
        $partCount = (int)m36_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_jobcard_part_usage WHERE jobcard_id = ? AND is_active = 1',
            [$jobcardId]
        );
    }

    if ($serviceCount === 0 && $partCount === 0) {
        return ['estimated_total' => '', 'balance_preview' => '', 'has_estimate' => false];
    }

    $estimated = (string)(200000 + ($serviceCount * 500000) + ($partCount * 150000));
    $received = (float)$totalReceived;
    $balance = (string)max(0, (float)$estimated - $received);

    return [
        'estimated_total' => $estimated,
        'balance_preview' => $balance,
        'has_estimate' => true,
    ];
}

/**
 * @return array<string, string>
 */
function m36_ux_fetch_payment_detail($connection, int $paymentId): array
{
    if (!m36_ux_table_exists($connection, 'erp_payments')) {
        return [];
    }

    $rows = m36_ux_fetch_rows(
        $connection,
        'SELECT TOP 1 payment_id, jobcard_id, customer_id, payment_type, payment_method,
                payment_amount, currency_code, payment_status, payment_reference, payment_note,
                received_by_user_id, received_at, created_at
         FROM dbo.erp_payments
         WHERE payment_id = ? AND is_active = 1',
        [$paymentId]
    );

    return $rows[0] ?? [];
}

/**
 * @return list<array<string, string>>
 */
function m36_ux_fetch_payment_history($connection, int $paymentId): array
{
    if (!m36_ux_table_exists($connection, 'erp_payment_history')) {
        return [];
    }

    return m36_ux_fetch_rows(
        $connection,
        'SELECT history_id, action_code, old_status, new_status, changed_by_user_id, changed_at, change_note
         FROM dbo.erp_payment_history
         WHERE payment_id = ?
         ORDER BY history_id DESC',
        [$paymentId]
    );
}

function m36_ux_status_badge_class(string $status): string
{
    $status = strtoupper(trim($status));

    if ($status === 'RECEIVED') {
        return 'm360-badge-success';
    }

    if (in_array($status, ['DRAFT', 'PENDING'], true)) {
        return 'm360-badge-warning';
    }

    if (in_array($status, ['CANCELLED', 'REVERSED'], true)) {
        return 'm360-badge-danger';
    }

    return 'm360-badge-neutral';
}

function m36_ux_render_finance_css_link(): void
{
    echo '<link rel="stylesheet" href="' . m36_ux_h('assets/moghare360-ui/moghare360-finance-preview-ux.css') . '">' . "\n";
}

function m36_ux_format_amount(string $amount): string
{
    if ($amount === '' || !is_numeric($amount)) {
        return '0';
    }

    return number_format((float)$amount, 0, '.', ',');
}
