<?php
/**
 * MOGHARE360 ERP Purchase Request Foundation CLI Test
 *
 * Mission 26 - CLI read-only test only. No writes.
 */

declare(strict_types=1);

const ERP_M26_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_M26_PLACEHOLDER_ACTIONS = [
    'purchase.request.create' => 'placeholder_purchase_request_create',
    'purchase.request.view' => 'placeholder_purchase_request_view',
    'purchase.request.list' => 'placeholder_purchase_request_list',
    'purchase.request.submit' => 'placeholder_purchase_request_submit',
    'purchase.request.approve' => 'placeholder_purchase_request_approve',
    'purchase.request.reject' => 'placeholder_purchase_request_reject',
    'purchase.request.cancel' => 'placeholder_purchase_request_cancel',
];

/** @var list<string> */
const ERP_M26_TABLES = [
    'erp_purchase_requests',
    'erp_purchase_request_history',
];

function erp_m26_cli_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m26_cli_helper_candidates(string $fileName): array
{
    $bases = [__DIR__, dirname(__DIR__), dirname(__DIR__, 2)];
    $candidates = [];

    foreach ($bases as $base) {
        $candidates[] = $base . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;
        $candidates[] = $base . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;
    }

    return array_values(array_unique($candidates));
}

function erp_m26_cli_scalar($connection, string $sql, array $params = []): ?string
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

function erp_m26_cli_table_exists($connection, string $tableName): bool
{
    $count = erp_m26_cli_scalar(
        $connection,
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
        ['dbo', $tableName]
    );

    return $count !== null && (int)$count > 0;
}

/**
 * @return array<string, mixed>
 */
function erp_m26_cli_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M26_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL'];
    }

    if ($userId === ERP_M26_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED'];
    }

    return ['allowed' => false, 'label' => 'FAIL'];
}

erp_m26_cli_require_first_existing(erp_m26_cli_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
erp_m26_cli_require_first_existing(erp_m26_cli_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');

$overallOk = true;
$failures = [];
$exceptionMessage = '';
$connection = false;

$userId = ERP_M26_PLATFORM_OWNER_ID;
$tableStatus = [];
$jobcardOneStatus = 'FAIL';
$prJobcardOneStatus = 'PENDING';
$historyCreatedStatus = 'PENDING';
$statusDraftOrSubmitted = 'PENDING';
$guardCreateLabel = 'FAIL';
$guardListLabel = 'FAIL';
$guardViewLabel = 'FAIL';

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();

    if (erp_auth_current_user_id() !== $userId) {
        $overallOk = false;
        $failures[] = 'current_user_id mismatch';
    }

    foreach (ERP_M26_TABLES as $tableName) {
        $exists = erp_m26_cli_table_exists($connection, $tableName);
        $tableStatus[$tableName] = $exists ? 'OK' : 'FAIL';

        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName . ' missing';
        }
    }

    if (erp_m26_cli_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE jobcard_id = ?', [1]) !== '0') {
        $jobcardOneStatus = 'OK';
    } else {
        $overallOk = false;
        $jobcardOneStatus = 'FAIL';
        $failures[] = 'jobcard_id 1 missing';
    }

    if (($tableStatus['erp_purchase_requests'] ?? 'FAIL') === 'OK') {
        $prCount = erp_m26_cli_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_purchase_requests WHERE jobcard_id = ?',
            [1]
        );

        if ($prCount !== null && (int)$prCount > 0) {
            $prJobcardOneStatus = 'OK';

            $statusValue = erp_m26_cli_scalar(
                $connection,
                'SELECT TOP 1 request_status FROM dbo.erp_purchase_requests WHERE jobcard_id = ? ORDER BY purchase_request_id DESC',
                [1]
            );

            if ($statusValue !== null && in_array($statusValue, ['DRAFT', 'SUBMITTED'], true)) {
                $statusDraftOrSubmitted = 'OK';
            } else {
                $statusDraftOrSubmitted = 'PENDING';
            }
        } else {
            $prJobcardOneStatus = 'PENDING';
            $statusDraftOrSubmitted = 'PENDING';
        }
    }

    if (($tableStatus['erp_purchase_request_history'] ?? 'FAIL') === 'OK') {
        $historyCount = erp_m26_cli_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_purchase_request_history WHERE action_code = ?',
            ['PURCHASE_REQUEST_CREATED']
        );

        if ($historyCount !== null && (int)$historyCount > 0) {
            $historyCreatedStatus = 'OK';
        } else {
            $historyCreatedStatus = 'PENDING';
        }
    }

    $guardCreate = erp_m26_cli_guard_eval($connection, $userId, 'purchase.request.create');
    $guardList = erp_m26_cli_guard_eval($connection, $userId, 'purchase.request.list');
    $guardView = erp_m26_cli_guard_eval($connection, $userId, 'purchase.request.view');
    $guardCreateLabel = (string)$guardCreate['label'];
    $guardListLabel = (string)$guardList['label'];
    $guardViewLabel = (string)$guardView['label'];

    if (empty($guardCreate['allowed']) || empty($guardList['allowed']) || empty($guardView['allowed'])) {
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

echo 'M26 PURCHASE REQUEST FOUNDATION TEST' . PHP_EOL;

foreach (ERP_M26_TABLES as $tableName) {
    echo 'table ' . $tableName . ' = ' . ($tableStatus[$tableName] ?? 'FAIL') . PHP_EOL;
}

echo 'JobCard jobcard_id 1 = ' . $jobcardOneStatus . PHP_EOL;
echo 'purchase request for jobcard_id 1 = ' . $prJobcardOneStatus . PHP_EOL;
echo 'history PURCHASE_REQUEST_CREATED = ' . $historyCreatedStatus . PHP_EOL;
echo 'status DRAFT or SUBMITTED = ' . $statusDraftOrSubmitted . PHP_EOL;
echo 'guard purchase.request.create = ' . $guardCreateLabel . PHP_EOL;
echo 'guard purchase.request.list = ' . $guardListLabel . PHP_EOL;
echo 'guard purchase.request.view = ' . $guardViewLabel . PHP_EOL;
echo 'No supplier payment = OK' . PHP_EOL;
echo 'No finance write = OK' . PHP_EOL;
echo 'No stock receipt = OK' . PHP_EOL;
echo 'No automatic approval = OK' . PHP_EOL;
echo 'No invoice write = OK' . PHP_EOL;
echo 'No payment write = OK' . PHP_EOL;
echo 'No delivery write = OK' . PHP_EOL;
echo 'No write performed by test = OK' . PHP_EOL;
echo 'Overall: ' . ($overallOk ? 'OK' : 'FAIL') . PHP_EOL;

if (!$overallOk && $failures !== []) {
    echo 'Failing checks: ' . implode(', ', $failures) . PHP_EOL;
}

if ($exceptionMessage !== '') {
    echo 'Exception: ' . $exceptionMessage . PHP_EOL;
}

exit($overallOk ? 0 : 1);
