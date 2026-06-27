<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Soft Run Readiness Gate (Read-Only)
 *
 * Mission 30 - Aggregated readiness checks. No writes.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M30_PLATFORM_OWNER_ID = 10001;
const ERP_M30_GATE_ACTION = 'soft.run.readiness.view';

/** @var array<string, string> */
const ERP_M30_PLACEHOLDER_ACTIONS = [
    'qc.check.create' => 'placeholder_qc_check_create',
    'delivery.control.view' => 'placeholder_delivery_control_view',
    'delivery.control.release' => 'placeholder_delivery_control_release',
    'soft.run.readiness.view' => 'placeholder_soft_run_readiness_view',
];

function erp_m30_gate_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m30_gate_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function erp_m30_gate_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m30_gate_scalar($connection, string $sql, array $params = []): ?string
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return null;
    }

    if (@odbc_fetch_row($statement) !== true) {
        return null;
    }

    $value = @odbc_result($statement, 1);

    return $value === false || $value === null ? null : (string)$value;
}

/**
 * @return list<array<string, string>>
 */
function erp_m30_gate_fetch_rows($connection, string $sql, array $params = []): array
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

/**
 * @return array<string, mixed>
 */
function erp_m30_gate_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M30_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === ERP_M30_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

function erp_m30_gate_parse_jobcard_id(): int
{
    if (!isset($_GET['jobcard_id'])) {
        return 1;
    }

    $raw = trim((string)$_GET['jobcard_id']);

    return ($raw !== '' && ctype_digit($raw)) ? (int)$raw : 1;
}

try {
    erp_m30_gate_require_first_existing(erp_m30_gate_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m30_gate_require_first_existing(erp_m30_gate_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Gate Error</title></head><body><p>Soft Run readiness page could not be loaded.</p></body></html>';
    exit(1);
}

$selectedJobcardId = erp_m30_gate_parse_jobcard_id();
$gateResult = 'SOFT RUN BLOCKED';
$gateReason = 'Not evaluated';
$checks = [];
$connection = false;
$errorMessage = '';
$guardLabel = 'FAIL';
$jobcardExists = false;
/** @var list<array<string, string>> */
$jobcardRows = [];

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();

    if (erp_auth_current_user_id() !== ERP_M30_PLATFORM_OWNER_ID) {
        throw new RuntimeException('Access denied.');
    }

    if (erp_auth_load_current_user($connection) === null) {
        throw new RuntimeException('Access denied.');
    }

    $guard = erp_m30_gate_guard_eval($connection, ERP_M30_PLATFORM_OWNER_ID, ERP_M30_GATE_ACTION);
    $guardLabel = (string)($guard['label'] ?? 'FAIL');

    if (empty($guard['allowed'])) {
        throw new RuntimeException('Access denied.');
    }

    $jobcardRows = erp_m30_gate_fetch_rows(
        $connection,
        'SELECT TOP 1 j.jobcard_id, j.jobcard_number, j.jobcard_status, j.customer_id, j.vehicle_id,
                c.full_name, v.brand, v.model, v.plate_number
         FROM dbo.erp_jobcards j
         LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
         LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
         WHERE j.jobcard_id = ?',
        [$selectedJobcardId]
    );

    $jobcardExists = $jobcardRows !== [];
    $checks['JobCard exists'] = $jobcardExists ? 'OK' : 'FAIL';

    $customerExists = $jobcardExists && trim((string)($jobcardRows[0]['customer_id'] ?? '')) !== '';
    $checks['Customer exists'] = $customerExists ? 'OK' : 'FAIL';

    $vehicleExists = $jobcardExists && trim((string)($jobcardRows[0]['vehicle_id'] ?? '')) !== '';
    $checks['Vehicle exists'] = $vehicleExists ? 'OK' : 'FAIL';

    $serviceOpCount = erp_m30_gate_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_service_operations WHERE jobcard_id = ? AND is_active = 1',
        [$selectedJobcardId]
    );
    $serviceOpExists = $serviceOpCount !== null && (int)$serviceOpCount > 0;
    $checks['Service operation exists'] = $serviceOpExists ? 'OK' : 'FAIL';

    $partUsageCount = erp_m30_gate_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_jobcard_part_usage WHERE jobcard_id = ? AND is_active = 1',
        [$selectedJobcardId]
    );
    $checks['Part usage (optional)'] = ($partUsageCount !== null && (int)$partUsageCount > 0) ? 'OK' : 'OPTIONAL';

    $paymentCount = erp_m30_gate_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_payments WHERE jobcard_id = ? AND payment_status = ? AND is_active = 1',
        [$selectedJobcardId, 'RECEIVED']
    );
    $totalReceived = erp_m30_gate_scalar(
        $connection,
        'SELECT COALESCE(SUM(payment_amount), 0) FROM dbo.erp_payments WHERE jobcard_id = ? AND payment_status = ? AND is_active = 1',
        [$selectedJobcardId, 'RECEIVED']
    );
    $checks['Payment (optional/documented)'] = ($paymentCount !== null && (int)$paymentCount > 0)
        ? 'OK — total_received=' . ($totalReceived ?? '0')
        : 'OPTIONAL — no RECEIVED payment';

    $qcPassedCount = erp_m30_gate_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_qc_checks WHERE jobcard_id = ? AND qc_status = ? AND is_active = 1',
        [$selectedJobcardId, 'PASSED']
    );
    $qcPassed = $qcPassedCount !== null && (int)$qcPassedCount > 0;
    $checks['QC passed'] = $qcPassed ? 'OK' : 'FAIL';

    $deliveryRows = erp_m30_gate_fetch_rows(
        $connection,
        'SELECT TOP 1 delivery_control_id, delivery_status, delivery_allowed, block_reason
         FROM dbo.erp_delivery_controls WHERE jobcard_id = ? AND is_active = 1 ORDER BY delivery_control_id DESC',
        [$selectedJobcardId]
    );

    $deliveryAllowed = false;
    $deliveryStatus = '';
    $blockReason = '';

    if ($deliveryRows !== []) {
        $deliveryStatus = strtoupper(trim((string)($deliveryRows[0]['delivery_status'] ?? '')));
        $deliveryAllowed = (int)($deliveryRows[0]['delivery_allowed'] ?? 0) === 1;
        $blockReason = trim((string)($deliveryRows[0]['block_reason'] ?? ''));

        if ($deliveryAllowed && in_array($deliveryStatus, ['READY', 'RELEASED'], true)) {
            $checks['Delivery allowed or blocked'] = 'OK — status=' . $deliveryStatus;
        } else {
            $checks['Delivery allowed or blocked'] = 'BLOCKED — status=' . $deliveryStatus
                . ($blockReason !== '' ? ' reason=' . $blockReason : '');
        }
    } else {
        $checks['Delivery allowed or blocked'] = 'FAIL — no delivery control';
    }

    $qcHistoryCount = erp_m30_gate_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_qc_check_history h
         INNER JOIN dbo.erp_qc_checks q ON q.qc_check_id = h.qc_check_id
         WHERE q.jobcard_id = ?',
        [$selectedJobcardId]
    );
    $deliveryHistoryCount = erp_m30_gate_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_delivery_control_history h
         INNER JOIN dbo.erp_delivery_controls d ON d.delivery_control_id = h.delivery_control_id
         WHERE d.jobcard_id = ?',
        [$selectedJobcardId]
    );
    $auditOk = ($qcHistoryCount !== null && (int)$qcHistoryCount > 0)
        || ($deliveryHistoryCount !== null && (int)$deliveryHistoryCount > 0);
    $checks['Audit/History exists'] = $auditOk ? 'OK' : 'FAIL';

    $checks['No forbidden files changed'] = 'STATEMENT — config/auth/portal/legacy unchanged in Mission 30';
    $checks['No final invoice'] = 'STATEMENT — no invoice finalization';
    $checks['No customer portal'] = 'STATEMENT — customer portal not modified';
    $checks['No production deploy'] = 'STATEMENT — local prototype only';

    $blockReasons = [];

    if (!$jobcardExists) {
        $blockReasons[] = 'JobCard missing';
    }

    if (!$customerExists) {
        $blockReasons[] = 'Customer missing';
    }

    if (!$vehicleExists) {
        $blockReasons[] = 'Vehicle missing';
    }

    if (!$serviceOpExists) {
        $blockReasons[] = 'Service operation missing';
    }

    if (!$qcPassed) {
        $blockReasons[] = 'QC not PASSED';
    }

    if ($deliveryRows === [] || !$deliveryAllowed || !in_array($deliveryStatus, ['READY', 'RELEASED'], true)) {
        $blockReasons[] = 'Delivery not allowed';

        if ($blockReason !== '') {
            $blockReasons[] = $blockReason;
        }
    }

    if (!$auditOk) {
        $blockReasons[] = 'Audit/history missing';
    }

    if ($blockReasons === []) {
        $gateResult = 'SOFT RUN READY';
        $gateReason = 'All required Soft Run checks passed.';
    } else {
        $gateResult = 'SOFT RUN BLOCKED';
        $gateReason = implode('; ', $blockReasons);
    }
} catch (Throwable $exception) {
    $errorMessage = trim($exception->getMessage()) !== '' ? $exception->getMessage() : 'Soft Run readiness could not be completed.';
    $gateResult = 'SOFT RUN BLOCKED';
    $gateReason = $errorMessage;
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Mission 30 - Soft Run Readiness</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; line-height: 1.5; }
        .wrap { max-width: 1000px; margin: 0 auto; padding: 24px; }
        .banner { background: #1e3a5f; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
        th { background: #f3f4f6; width: 280px; }
        .ready { color: #166534; font-weight: bold; font-size: 1.2rem; }
        .blocked { color: #b91c1c; font-weight: bold; font-size: 1.2rem; }
        .fail { color: #b91c1c; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">SOFT RUN READINESS GATE — READ ONLY — LOCAL PROTOTYPE</div>

    <div class="card">
        <h1>Soft Run Readiness</h1>
        <p>
            JobCard ID: <?= erp_m30_gate_h((string)$selectedJobcardId) ?> |
            <a href="erp-qc-check.php">QC check</a> |
            <a href="erp-delivery-control.php?jobcard_id=<?= erp_m30_gate_h((string)$selectedJobcardId) ?>">Delivery control</a>
        </p>
        <?php if ($errorMessage !== ''): ?><p class="fail"><?= erp_m30_gate_h($errorMessage) ?></p><?php endif; ?>
        <p>guard soft.run.readiness.view = <?= erp_m30_gate_h($guardLabel) ?></p>
    </div>

    <div class="card">
        <h2>Gate Result</h2>
        <p class="<?= $gateResult === 'SOFT RUN READY' ? 'ready' : 'blocked' ?>"><?= erp_m30_gate_h($gateResult) ?></p>
        <p><strong>Reason:</strong> <?= erp_m30_gate_h($gateReason) ?></p>
    </div>

  <?php if ($jobcardExists && isset($jobcardRows[0])): ?>
    <div class="card">
        <h2>JobCard Context</h2>
        <table>
            <tbody>
                <tr><th>JobCard Number</th><td><?= erp_m30_gate_h((string)($jobcardRows[0]['jobcard_number'] ?? '—')) ?></td></tr>
                <tr><th>Customer</th><td><?= erp_m30_gate_h((string)($jobcardRows[0]['full_name'] ?? '—')) ?></td></tr>
                <tr><th>Vehicle</th><td><?= erp_m30_gate_h(trim((string)($jobcardRows[0]['brand'] ?? '') . ' ' . (string)($jobcardRows[0]['model'] ?? '') . ' ' . (string)($jobcardRows[0]['plate_number'] ?? ''))) ?></td></tr>
            </tbody>
        </table>
    </div>
  <?php endif; ?>

    <div class="card">
        <h2>Readiness Checks</h2>
        <table>
            <tbody>
                <?php foreach ($checks as $label => $status): ?>
                    <tr>
                        <th><?= erp_m30_gate_h($label) ?></th>
                        <td><?= erp_m30_gate_h($status) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
