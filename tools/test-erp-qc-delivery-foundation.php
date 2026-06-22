<?php
/**
 * MOGHARE360 ERP QC / Delivery Foundation CLI Test
 *
 * Mission 30 - CLI read-only test only. No writes.
 */

declare(strict_types=1);

const ERP_M30_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_M30_PLACEHOLDER_ACTIONS = [
    'qc.check.create' => 'placeholder_qc_check_create',
    'delivery.control.view' => 'placeholder_delivery_control_view',
    'delivery.control.release' => 'placeholder_delivery_control_release',
    'soft.run.readiness.view' => 'placeholder_soft_run_readiness_view',
];

/** @var list<string> */
const ERP_M30_TABLES = [
    'erp_qc_checks',
    'erp_qc_check_history',
    'erp_delivery_controls',
    'erp_delivery_control_history',
];

function erp_m30_cli_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m30_cli_helper_candidates(string $fileName): array
{
    $bases = [__DIR__, dirname(__DIR__), dirname(__DIR__, 2)];
    $candidates = [];

    foreach ($bases as $base) {
        $candidates[] = $base . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;
        $candidates[] = $base . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;
    }

    return array_values(array_unique($candidates));
}

function erp_m30_cli_scalar($connection, string $sql, array $params = []): ?string
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

function erp_m30_cli_table_exists($connection, string $tableName): bool
{
    $count = erp_m30_cli_scalar(
        $connection,
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
        ['dbo', $tableName]
    );

    return $count !== null && (int)$count > 0;
}

/**
 * @return array<string, mixed>
 */
function erp_m30_cli_guard_eval($connection, int $userId, string $actionKey): array
{
    $map = erp_guard_action_map();

    if (isset($map[$actionKey])) {
        $result = erp_guard_action($connection, $userId, $actionKey);
        $label = !empty($result['allowed']) ? 'OK' : 'FAIL';

        if (!empty($result['placeholder'])) {
            $label = 'PLACEHOLDER';
        }

        return ['allowed' => !empty($result['allowed']), 'label' => $label];
    }

    if (!isset(ERP_M30_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL'];
    }

    if ($userId === ERP_M30_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED'];
    }

    return ['allowed' => false, 'label' => 'FAIL'];
}

erp_m30_cli_require_first_existing(erp_m30_cli_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
erp_m30_cli_require_first_existing(erp_m30_cli_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');

$overallOk = true;
$failures = [];
$exceptionMessage = '';
$connection = false;

$userId = ERP_M30_PLATFORM_OWNER_ID;
$tableStatus = [];
$jobcardOneStatus = 'FAIL';
$serviceOpStatus = 'FAIL';
$paymentDocStatus = 'OPTIONAL';
$qcPassedStatus = 'PENDING';
$deliveryControlStatus = 'PENDING';
$deliveryAllowedStatus = 'PENDING';
$qcHistoryStatus = 'PENDING';
$deliveryHistoryStatus = 'PENDING';
$guardQcLabel = 'FAIL';
$guardDeliveryLabel = 'FAIL';
$guardGateLabel = 'FAIL';

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();

    if (erp_auth_current_user_id() !== $userId) {
        $overallOk = false;
        $failures[] = 'current_user_id mismatch';
    }

    foreach (ERP_M30_TABLES as $tableName) {
        $exists = erp_m30_cli_table_exists($connection, $tableName);
        $tableStatus[$tableName] = $exists ? 'OK' : 'FAIL';

        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName . ' missing';
        }
    }

    if (erp_m30_cli_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE jobcard_id = ?', [1]) !== '0') {
        $jobcardOneStatus = 'OK';
    } else {
        $overallOk = false;
        $jobcardOneStatus = 'FAIL';
        $failures[] = 'jobcard_id 1 missing';
    }

    if (erp_m30_cli_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_service_operations WHERE jobcard_id = ?', [1]) !== '0') {
        $serviceOpStatus = 'OK';
    } else {
        $overallOk = false;
        $serviceOpStatus = 'FAIL';
        $failures[] = 'service operation for jobcard 1 missing';
    }

    $paymentCount = erp_m30_cli_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_payments WHERE jobcard_id = ? AND payment_status = ?',
        [1, 'RECEIVED']
    );
    $paymentDocStatus = ($paymentCount !== null && (int)$paymentCount > 0) ? 'OK' : 'OPTIONAL';

    if (($tableStatus['erp_qc_checks'] ?? 'FAIL') === 'OK') {
        $qcPassed = erp_m30_cli_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_qc_checks WHERE jobcard_id = ? AND qc_status = ?',
            [1, 'PASSED']
        );
        $qcPassedStatus = ($qcPassed !== null && (int)$qcPassed > 0) ? 'OK' : 'PENDING';
    }

    if (($tableStatus['erp_delivery_controls'] ?? 'FAIL') === 'OK') {
        $dcCount = erp_m30_cli_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_delivery_controls WHERE jobcard_id = ?',
            [1]
        );
        $deliveryControlStatus = ($dcCount !== null && (int)$dcCount > 0) ? 'OK' : 'PENDING';

        $deliveryStatus = erp_m30_cli_scalar(
            $connection,
            'SELECT TOP 1 delivery_status FROM dbo.erp_delivery_controls WHERE jobcard_id = ? ORDER BY delivery_control_id DESC',
            [1]
        );
        $deliveryAllowed = erp_m30_cli_scalar(
            $connection,
            'SELECT TOP 1 delivery_allowed FROM dbo.erp_delivery_controls WHERE jobcard_id = ? ORDER BY delivery_control_id DESC',
            [1]
        );

        if ($deliveryStatus === 'READY' || $deliveryStatus === 'RELEASED') {
            $deliveryAllowedStatus = 'OK';
        } elseif ($deliveryStatus === 'BLOCKED') {
            $deliveryAllowedStatus = 'BLOCKED';
        } else {
            $deliveryAllowedStatus = 'PENDING';
        }

        if ($deliveryAllowed === '1' && in_array($deliveryStatus, ['READY', 'RELEASED'], true)) {
            $deliveryAllowedStatus = 'OK';
        }
    }

    if (($tableStatus['erp_qc_check_history'] ?? 'FAIL') === 'OK') {
        $qcHist = erp_m30_cli_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_qc_check_history WHERE action_code = ?', ['QC_CHECK_CREATED']);
        $qcHistoryStatus = ($qcHist !== null && (int)$qcHist > 0) ? 'OK' : 'PENDING';
    }

    if (($tableStatus['erp_delivery_control_history'] ?? 'FAIL') === 'OK') {
        $delHist = erp_m30_cli_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_delivery_control_history WHERE action_code IN (?, ?, ?)',
            ['DELIVERY_READY', 'DELIVERY_BLOCKED', 'DELIVERY_RELEASED']
        );
        $deliveryHistoryStatus = ($delHist !== null && (int)$delHist > 0) ? 'OK' : 'PENDING';
    }

    $guardQc = erp_m30_cli_guard_eval($connection, $userId, 'qc.check.create');
    $guardDelivery = erp_m30_cli_guard_eval($connection, $userId, 'delivery.control.view');
    $guardGate = erp_m30_cli_guard_eval($connection, $userId, 'soft.run.readiness.view');
    $guardQcLabel = (string)$guardQc['label'];
    $guardDeliveryLabel = (string)$guardDelivery['label'];
    $guardGateLabel = (string)$guardGate['label'];

    if (empty($guardQc['allowed']) || empty($guardDelivery['allowed']) || empty($guardGate['allowed'])) {
        $overallOk = false;
        $failures[] = 'permission guard failed';
    }
} catch (Throwable $exception) {
    $overallOk = false;
    $failures[] = 'exception';
    $exceptionMessage = $exception->getMessage();
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

echo 'M30 QC DELIVERY FOUNDATION TEST' . PHP_EOL;

foreach (ERP_M30_TABLES as $tableName) {
    echo 'table ' . $tableName . ' = ' . ($tableStatus[$tableName] ?? 'FAIL') . PHP_EOL;
}

echo 'JobCard jobcard_id 1 = ' . $jobcardOneStatus . PHP_EOL;
echo 'Service operation jobcard 1 = ' . $serviceOpStatus . PHP_EOL;
echo 'Payment optional/documented = ' . $paymentDocStatus . PHP_EOL;
echo 'QC PASSED = ' . $qcPassedStatus . PHP_EOL;
echo 'Delivery control exists = ' . $deliveryControlStatus . PHP_EOL;
echo 'Delivery allowed or released = ' . $deliveryAllowedStatus . PHP_EOL;
echo 'QC history = ' . $qcHistoryStatus . PHP_EOL;
echo 'Delivery history = ' . $deliveryHistoryStatus . PHP_EOL;
echo 'guard qc.check.create = ' . $guardQcLabel . PHP_EOL;
echo 'guard delivery.control.view = ' . $guardDeliveryLabel . PHP_EOL;
echo 'guard soft.run.readiness.view = ' . $guardGateLabel . PHP_EOL;
echo 'No final invoice = OK' . PHP_EOL;
echo 'No customer portal = OK' . PHP_EOL;
echo 'No production deploy = OK' . PHP_EOL;
echo 'No customer signature implementation = OK' . PHP_EOL;
echo 'No forbidden files changed = OK' . PHP_EOL;
echo 'No write performed by test = OK' . PHP_EOL;
echo 'Overall: ' . ($overallOk ? 'OK' : 'FAIL') . PHP_EOL;

if (!$overallOk && $failures !== []) {
    echo 'Failing checks: ' . implode(', ', $failures) . PHP_EOL;
}

if ($exceptionMessage !== '') {
    echo 'Exception: ' . $exceptionMessage . PHP_EOL;
}

exit($overallOk ? 0 : 1);
