<?php
declare(strict_types=1);

/**
 * MOGHARE360 Soft Run Release — read-only data helpers
 *
 * Mission 37 — SELECT only. No write. No new operational logic.
 */

const M37_UX_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const M37_UX_PLACEHOLDER_ACTIONS = [
    'soft.run.readiness.view' => 'placeholder_soft_run_readiness_view',
    'jobcard.list' => 'placeholder_jobcard_list',
    'customer.vehicle.view' => 'placeholder_customer_vehicle_view',
    'payment.summary.view' => 'placeholder_payment_summary_view',
];

function m37_ux_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function m37_ux_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function m37_ux_bootstrap_auth(): void
{
    m37_ux_require_first_existing(m37_ux_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    m37_ux_require_first_existing(m37_ux_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
}

function m37_ux_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m37_ux_display(string $value): string
{
    return m37_ux_h(trim($value) === '' ? '—' : $value);
}

function m37_ux_parse_jobcard_id(int $default = 1): int
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
function m37_ux_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(M37_UX_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === M37_UX_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

/**
 * @return list<array<string, string>>
 */
function m37_ux_fetch_rows($connection, string $sql, array $params = []): array
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

function m37_ux_scalar($connection, string $sql, array $params = []): int
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

function m37_ux_table_exists($connection, string $tableName): bool
{
    $rows = m37_ux_fetch_rows(
        $connection,
        "SELECT 1 AS ok FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = ?",
        [$tableName]
    );

    return $rows !== [];
}

/**
 * @return array{connection: resource|false, userId: int, error: string}
 */
function m37_ux_connect(string $guardAction = 'soft.run.readiness.view'): array
{
    $result = [
        'connection' => false,
        'userId' => M37_UX_PLATFORM_OWNER_ID,
        'error' => '',
    ];

    try {
        m37_ux_bootstrap_auth();
        erp_auth_context_start();

        $connection = erp_auth_create_local_odbc_connection();
        $userId = erp_auth_current_user_id();

        if ($userId !== M37_UX_PLATFORM_OWNER_ID) {
            throw new RuntimeException('Access denied.');
        }

        $user = erp_auth_load_current_user($connection);

        if ($user === null) {
            throw new RuntimeException('Access denied.');
        }

        $guard = m37_ux_guard_eval($connection, $userId, $guardAction);

        if (empty($guard['allowed'])) {
            throw new RuntimeException('Access denied.');
        }

        $result['connection'] = $connection;
        $result['userId'] = $userId;
    } catch (Throwable $exception) {
        $result['error'] = 'Soft Run release data could not be loaded.';
    }

    return $result;
}

/**
 * @return array<string, int|string>
 */
function m37_ux_fetch_home_kpi($connection): array
{
    $kpi = [
        'customers' => 0,
        'vehicles' => 0,
        'active_jobcards' => 0,
        'service_operations' => 0,
        'payments_received' => '0',
        'qc_passed' => 0,
        'delivery_released' => 0,
    ];

    if (m37_ux_table_exists($connection, 'erp_customers')) {
        $kpi['customers'] = m37_ux_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_customers');
    }

    if (m37_ux_table_exists($connection, 'erp_vehicles')) {
        $kpi['vehicles'] = m37_ux_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_vehicles');
    }

    if (m37_ux_table_exists($connection, 'erp_jobcards')) {
        $kpi['active_jobcards'] = m37_ux_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_jobcards WHERE lifecycle_state = 'ACTIVE'"
        );
    }

    if (m37_ux_table_exists($connection, 'erp_service_operations')) {
        $kpi['service_operations'] = m37_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_service_operations WHERE is_active = 1'
        );
    }

    if (m37_ux_table_exists($connection, 'erp_payments')) {
        $rows = m37_ux_fetch_rows(
            $connection,
            "SELECT COALESCE(SUM(payment_amount), 0) AS total FROM dbo.erp_payments WHERE payment_status = 'RECEIVED' AND is_active = 1"
        );
        $kpi['payments_received'] = $rows[0]['total'] ?? '0';
    }

    if (m37_ux_table_exists($connection, 'erp_qc_checks')) {
        $kpi['qc_passed'] = m37_ux_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_qc_checks WHERE qc_status = 'PASSED' AND is_active = 1"
        );
    }

    if (m37_ux_table_exists($connection, 'erp_delivery_controls')) {
        $kpi['delivery_released'] = m37_ux_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_delivery_controls WHERE delivery_status IN ('RELEASED', 'READY') AND is_active = 1"
        );
    }

    return $kpi;
}

/**
 * @return array<string, array<string, string>>
 */
function m37_ux_module_cards(string $roleMode): array
{
    $r = rawurlencode($roleMode);

    return [
        'customers' => [
            'label' => 'مشتریان',
            'label_en' => 'Customers',
            'href' => 'erp-customer-vehicle-workbench.php?role=' . $r,
            'icon' => 'CU',
        ],
        'vehicles' => [
            'label' => 'خودروها',
            'label_en' => 'Vehicles',
            'href' => 'erp-customer-vehicle-workbench.php?role=' . $r,
            'icon' => 'VH',
        ],
        'jobcards' => [
            'label' => 'کارت‌های کار',
            'label_en' => 'JobCards',
            'href' => 'erp-jobcard-workbench.php?role=' . $r,
            'icon' => 'JC',
        ],
        'service_operations' => [
            'label' => 'عملیات سرویس',
            'label_en' => 'Service Operations',
            'href' => 'erp-service-operation-workbench-ux.php?role=' . $r,
            'icon' => 'SO',
        ],
        'parts_inventory' => [
            'label' => 'قطعات / انبار',
            'label_en' => 'Parts / Inventory',
            'href' => 'erp-stock-readonly-list.php',
            'icon' => 'PI',
        ],
        'purchase_requests' => [
            'label' => 'درخواست خرید',
            'label_en' => 'Purchase Requests',
            'href' => 'erp-purchase-request-readonly-list.php',
            'icon' => 'PR',
        ],
        'payments' => [
            'label' => 'پیش‌نمایش مالی',
            'label_en' => 'Payments Preview',
            'href' => 'erp-finance-preview-workbench.php?role=finance',
            'icon' => 'PY',
        ],
        'qc_delivery' => [
            'label' => 'QC / تحویل',
            'label_en' => 'QC / Delivery',
            'href' => 'erp-qc-check.php',
            'icon' => 'QC',
        ],
        'soft_run_gate' => [
            'label' => 'دروازه Soft Run',
            'label_en' => 'Soft Run Gate',
            'href' => 'erp-soft-run-readiness.php?jobcard_id=1',
            'icon' => 'SR',
        ],
    ];
}

/**
 * @return array<string, string>
 */
function m37_ux_fetch_jobcard_row($connection, int $jobcardId): array
{
    if (!m37_ux_table_exists($connection, 'erp_jobcards')) {
        return [];
    }

    $rows = m37_ux_fetch_rows(
        $connection,
        'SELECT TOP 1 jobcard_id, jobcard_number, customer_id, vehicle_id, jobcard_status
         FROM dbo.erp_jobcards WHERE jobcard_id = ?',
        [$jobcardId]
    );

    return $rows[0] ?? [];
}

/**
 * @param array<string, string> $jobcard
 * @return list<array<string, string>>
 */
function m37_ux_build_flow_steps($connection, int $jobcardId, array $jobcard): array
{
    $customerId = (int)($jobcard['customer_id'] ?? 0);
    $vehicleId = (int)($jobcard['vehicle_id'] ?? 0);
    $role = 'owner';

    $steps = [];

    $customerOk = false;
    if ($customerId > 0 && m37_ux_table_exists($connection, 'erp_customers')) {
        $customerOk = m37_ux_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_customers WHERE customer_id = ?', [$customerId]) > 0;
    }
    $steps[] = [
        'key' => 'customer',
        'title' => 'Customer',
        'status' => $customerOk ? 'OK' : ($customerId > 0 ? 'PENDING' : 'EMPTY'),
        'detail' => $customerOk ? 'customer_id ' . $customerId : '—',
        'href' => 'erp-customer-detail-ux.php?customer_id=' . $customerId . '&role=reception',
    ];

    $vehicleOk = false;
    if ($vehicleId > 0 && m37_ux_table_exists($connection, 'erp_vehicles')) {
        $vehicleOk = m37_ux_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_vehicles WHERE vehicle_id = ?', [$vehicleId]) > 0;
    }
    $steps[] = [
        'key' => 'vehicle',
        'title' => 'Vehicle',
        'status' => $vehicleOk ? 'OK' : ($vehicleId > 0 ? 'PENDING' : 'EMPTY'),
        'detail' => $vehicleOk ? 'vehicle_id ' . $vehicleId : '—',
        'href' => 'erp-vehicle-detail-ux.php?vehicle_id=' . $vehicleId . '&role=reception',
    ];

    $jobcardOk = $jobcard !== [];
    $steps[] = [
        'key' => 'jobcard',
        'title' => 'JobCard',
        'status' => $jobcardOk ? 'OK' : 'EMPTY',
        'detail' => $jobcardOk ? ($jobcard['jobcard_number'] ?? '') : '—',
        'href' => 'erp-jobcard-detail-ux.php?jobcard_id=' . $jobcardId . '&role=' . $role,
    ];

    $serviceCount = 0;
    if (m37_ux_table_exists($connection, 'erp_service_operations')) {
        $serviceCount = m37_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_service_operations WHERE jobcard_id = ? AND is_active = 1',
            [$jobcardId]
        );
    }
    $steps[] = [
        'key' => 'service',
        'title' => 'Service',
        'status' => $serviceCount > 0 ? 'OK' : 'EMPTY',
        'detail' => (string)$serviceCount . ' operation(s)',
        'href' => 'erp-service-operation-workbench-ux.php?role=service',
    ];

    $partsCount = 0;
    if (m37_ux_table_exists($connection, 'erp_jobcard_part_usage')) {
        $partsCount = m37_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_jobcard_part_usage WHERE jobcard_id = ? AND is_active = 1',
            [$jobcardId]
        );
    }
    $steps[] = [
        'key' => 'parts',
        'title' => 'Part Usage',
        'status' => $partsCount > 0 ? 'OK' : 'EMPTY',
        'detail' => (string)$partsCount . ' usage(s)',
        'href' => 'erp-jobcard-part-readonly-list.php',
    ];

    $purchaseCount = 0;
    if (m37_ux_table_exists($connection, 'erp_purchase_requests')) {
        $purchaseCount = m37_ux_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_purchase_requests WHERE jobcard_id = ? AND is_active = 1',
            [$jobcardId]
        );
    }
    $steps[] = [
        'key' => 'purchase',
        'title' => 'Purchase Request',
        'status' => $purchaseCount > 0 ? 'OK' : 'EMPTY',
        'detail' => (string)$purchaseCount . ' request(s)',
        'href' => 'erp-purchase-request-readonly-list.php',
    ];

    $paymentCount = 0;
    if (m37_ux_table_exists($connection, 'erp_payments')) {
        $paymentCount = m37_ux_scalar(
            $connection,
            "SELECT COUNT(*) FROM dbo.erp_payments WHERE jobcard_id = ? AND payment_status = 'RECEIVED' AND is_active = 1",
            [$jobcardId]
        );
    }
    $steps[] = [
        'key' => 'payment',
        'title' => 'Payment Preview',
        'status' => $paymentCount > 0 ? 'OK' : 'PENDING',
        'detail' => (string)$paymentCount . ' received',
        'href' => 'erp-jobcard-finance-preview-ux.php?jobcard_id=' . $jobcardId . '&role=finance',
    ];

    $qcStatus = 'EMPTY';
    if (m37_ux_table_exists($connection, 'erp_qc_checks')) {
        $qcRows = m37_ux_fetch_rows(
            $connection,
            "SELECT TOP 1 qc_status FROM dbo.erp_qc_checks WHERE jobcard_id = ? AND is_active = 1 ORDER BY qc_check_id DESC",
            [$jobcardId]
        );
        if ($qcRows !== []) {
            $s = strtoupper((string)($qcRows[0]['qc_status'] ?? ''));
            $qcStatus = $s === 'PASSED' ? 'OK' : 'PENDING';
        }
    }
    $steps[] = [
        'key' => 'qc',
        'title' => 'QC',
        'status' => $qcStatus,
        'detail' => $qcStatus === 'OK' ? 'PASSED' : ($qcStatus === 'PENDING' ? 'pending' : '—'),
        'href' => 'erp-qc-check.php',
    ];

    $deliveryStatus = 'EMPTY';
    if (m37_ux_table_exists($connection, 'erp_delivery_controls')) {
        $delRows = m37_ux_fetch_rows(
            $connection,
            "SELECT TOP 1 delivery_status FROM dbo.erp_delivery_controls WHERE jobcard_id = ? AND is_active = 1 ORDER BY delivery_control_id DESC",
            [$jobcardId]
        );
        if ($delRows !== []) {
            $s = strtoupper((string)($delRows[0]['delivery_status'] ?? ''));
            $deliveryStatus = in_array($s, ['RELEASED', 'READY'], true) ? 'OK' : 'PENDING';
        }
    }
    $steps[] = [
        'key' => 'delivery',
        'title' => 'Delivery',
        'status' => $deliveryStatus,
        'detail' => $deliveryStatus === 'OK' ? 'ready/released' : ($deliveryStatus === 'PENDING' ? 'blocked/pending' : '—'),
        'href' => 'erp-delivery-control.php?jobcard_id=' . $jobcardId,
    ];

    return $steps;
}

/**
 * @return list<array<string, string>>
 */
function m37_ux_mission_status_list(): array
{
    return [
        ['mission' => 'M31', 'name' => 'Design System', 'status' => 'OK'],
        ['mission' => 'M32', 'name' => 'Application Shell', 'status' => 'OK'],
        ['mission' => 'M33', 'name' => 'JobCard UX', 'status' => 'OK'],
        ['mission' => 'M34', 'name' => 'Customer Vehicle UX', 'status' => 'OK'],
        ['mission' => 'M35', 'name' => 'Service Operation UX', 'status' => 'OK'],
        ['mission' => 'M36', 'name' => 'Finance Preview UX', 'status' => 'OK'],
        ['mission' => 'M37', 'name' => 'Soft Run Release', 'status' => 'OK'],
    ];
}

function m37_ux_flow_status_class(string $status): string
{
    return match (strtoupper($status)) {
        'OK' => 'm360-badge-success',
        'PENDING' => 'm360-badge-warning',
        default => 'm360-badge-neutral',
    };
}

function m37_ux_format_amount(string $amount): string
{
    if ($amount === '' || !is_numeric($amount)) {
        return '0';
    }

    return number_format((float)$amount, 0, '.', ',');
}

function m37_ux_render_release_css_link(): void
{
    echo '<link rel="stylesheet" href="' . m37_ux_h('assets/moghare360-ui/moghare360-soft-run-release.css') . '">' . "\n";
}
