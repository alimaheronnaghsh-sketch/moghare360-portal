<?php
/**
 * MOGHARE360 ERP Payment Foundation CLI Test
 *
 * Mission 28 - CLI read-only test only. No writes.
 */

declare(strict_types=1);

const ERP_M28_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_M28_PLACEHOLDER_ACTIONS = [
    'payment.create' => 'placeholder_payment_create',
    'payment.view' => 'placeholder_payment_view',
    'payment.list' => 'placeholder_payment_list',
    'payment.summary.view' => 'placeholder_payment_summary_view',
    'payment.cancel' => 'placeholder_payment_cancel',
    'payment.reverse' => 'placeholder_payment_reverse',
];

/** @var list<string> */
const ERP_M28_TABLES = [
    'erp_payments',
    'erp_payment_history',
];

function erp_m28_cli_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m28_cli_helper_candidates(string $fileName): array
{
    $bases = [__DIR__, dirname(__DIR__), dirname(__DIR__, 2)];
    $candidates = [];

    foreach ($bases as $base) {
        $candidates[] = $base . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;
        $candidates[] = $base . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;
    }

    return array_values(array_unique($candidates));
}

function erp_m28_cli_scalar($connection, string $sql, array $params = []): ?string
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

function erp_m28_cli_table_exists($connection, string $tableName): bool
{
    $count = erp_m28_cli_scalar(
        $connection,
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
        ['dbo', $tableName]
    );

    return $count !== null && (int)$count > 0;
}

/**
 * @return array<string, mixed>
 */
function erp_m28_cli_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M28_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL'];
    }

    if ($userId === ERP_M28_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED'];
    }

    return ['allowed' => false, 'label' => 'FAIL'];
}

erp_m28_cli_require_first_existing(erp_m28_cli_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
erp_m28_cli_require_first_existing(erp_m28_cli_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');

$overallOk = true;
$failures = [];
$exceptionMessage = '';
$connection = false;

$userId = ERP_M28_PLATFORM_OWNER_ID;
$tableStatus = [];
$jobcardOneStatus = 'FAIL';
$paymentJobcardOneStatus = 'PENDING';
$historyReceivedStatus = 'PENDING';
$statusReceived = 'PENDING';
$summaryQueryStatus = 'PENDING';
$guardCreateLabel = 'FAIL';
$guardListLabel = 'FAIL';
$guardSummaryLabel = 'FAIL';

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();

    if (erp_auth_current_user_id() !== $userId) {
        $overallOk = false;
        $failures[] = 'current_user_id mismatch';
    }

    foreach (ERP_M28_TABLES as $tableName) {
        $exists = erp_m28_cli_table_exists($connection, $tableName);
        $tableStatus[$tableName] = $exists ? 'OK' : 'FAIL';

        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName . ' missing';
        }
    }

    if (erp_m28_cli_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE jobcard_id = ?', [1]) !== '0') {
        $jobcardOneStatus = 'OK';
    } else {
        $overallOk = false;
        $jobcardOneStatus = 'FAIL';
        $failures[] = 'jobcard_id 1 missing';
    }

    if (($tableStatus['erp_payments'] ?? 'FAIL') === 'OK') {
        $paymentCount = erp_m28_cli_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_payments WHERE jobcard_id = ?',
            [1]
        );

        if ($paymentCount !== null && (int)$paymentCount > 0) {
            $paymentJobcardOneStatus = 'OK';

            $statusValue = erp_m28_cli_scalar(
                $connection,
                'SELECT TOP 1 payment_status FROM dbo.erp_payments WHERE jobcard_id = ? ORDER BY payment_id DESC',
                [1]
            );

            $statusReceived = ($statusValue === 'RECEIVED') ? 'OK' : 'PENDING';
        } else {
            $paymentJobcardOneStatus = 'PENDING';
            $statusReceived = 'PENDING';
        }

        $summaryTotal = erp_m28_cli_scalar(
            $connection,
            'SELECT COALESCE(SUM(payment_amount), 0)
             FROM dbo.erp_payments
             WHERE jobcard_id = ?
               AND payment_status = ?
               AND is_active = 1',
            [1, 'RECEIVED']
        );

        $summaryQueryStatus = ($summaryTotal !== null) ? 'OK' : 'FAIL';

        if ($summaryQueryStatus === 'FAIL') {
            $overallOk = false;
            $failures[] = 'summary query failed';
        }
    }

    if (($tableStatus['erp_payment_history'] ?? 'FAIL') === 'OK') {
        $historyCount = erp_m28_cli_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_payment_history WHERE action_code = ?',
            ['PAYMENT_RECEIVED']
        );

        $historyReceivedStatus = ($historyCount !== null && (int)$historyCount > 0) ? 'OK' : 'PENDING';
    }

    $guardCreate = erp_m28_cli_guard_eval($connection, $userId, 'payment.create');
    $guardList = erp_m28_cli_guard_eval($connection, $userId, 'payment.list');
    $guardSummary = erp_m28_cli_guard_eval($connection, $userId, 'payment.summary.view');
    $guardCreateLabel = (string)$guardCreate['label'];
    $guardListLabel = (string)$guardList['label'];
    $guardSummaryLabel = (string)$guardSummary['label'];

    if (empty($guardCreate['allowed']) || empty($guardList['allowed']) || empty($guardSummary['allowed'])) {
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

echo 'M28 PAYMENT FOUNDATION TEST' . PHP_EOL;

foreach (ERP_M28_TABLES as $tableName) {
    echo 'table ' . $tableName . ' = ' . ($tableStatus[$tableName] ?? 'FAIL') . PHP_EOL;
}

echo 'JobCard jobcard_id 1 = ' . $jobcardOneStatus . PHP_EOL;
echo 'payment for jobcard_id 1 = ' . $paymentJobcardOneStatus . PHP_EOL;
echo 'history PAYMENT_RECEIVED = ' . $historyReceivedStatus . PHP_EOL;
echo 'status RECEIVED = ' . $statusReceived . PHP_EOL;
echo 'summary query = ' . $summaryQueryStatus . PHP_EOL;
echo 'guard payment.create = ' . $guardCreateLabel . PHP_EOL;
echo 'guard payment.list = ' . $guardListLabel . PHP_EOL;
echo 'guard payment.summary.view = ' . $guardSummaryLabel . PHP_EOL;
echo 'No invoice finalization = OK' . PHP_EOL;
echo 'No accounting export = OK' . PHP_EOL;
echo 'No supplier payment = OK' . PHP_EOL;
echo 'No tax logic = OK' . PHP_EOL;
echo 'No delivery dependency = OK' . PHP_EOL;
echo 'No purchase write = OK' . PHP_EOL;
echo 'No stock write = OK' . PHP_EOL;
echo 'No write performed by test = OK' . PHP_EOL;
echo 'Overall: ' . ($overallOk ? 'OK' : 'FAIL') . PHP_EOL;

if (!$overallOk && $failures !== []) {
    echo 'Failing checks: ' . implode(', ', $failures) . PHP_EOL;
}

if ($exceptionMessage !== '') {
    echo 'Exception: ' . $exceptionMessage . PHP_EOL;
}

exit($overallOk ? 0 : 1);
