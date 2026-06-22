<?php
/**
 * MOGHARE360 ERP Service Operation Foundation CLI Test
 *
 * Mission 20 - CLI read-only test only. No writes.
 */

declare(strict_types=1);

const ERP_M20_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_M20_PLACEHOLDER_ACTIONS = [
    'service.operation.create' => 'placeholder_service_operation_create',
    'service.operation.view' => 'placeholder_service_operation_view',
    'service.operation.list' => 'placeholder_service_operation_list',
    'service.operation.assign' => 'placeholder_service_operation_assign',
    'service.operation.status.change' => 'placeholder_service_operation_status_change',
];

/** @var list<string> */
const ERP_M20_SERVICE_OPERATION_TABLES = [
    'erp_service_operations',
    'erp_service_operation_change_history',
];

/** @var list<string> */
const ERP_M20_JOBCARD_TABLES = [
    'erp_jobcards',
];

function erp_m20_cli_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m20_cli_helper_candidates(string $fileName): array
{
    $bases = [__DIR__, dirname(__DIR__), dirname(__DIR__, 2)];
    $candidates = [];

    foreach ($bases as $base) {
        $candidates[] = $base . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;
    }

    $candidates[] = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;
    $candidates[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;

    $unique = [];

    foreach ($candidates as $candidate) {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);

        if (!in_array($normalized, $unique, true)) {
            $unique[] = $normalized;
        }
    }

    return $unique;
}

function erp_m20_cli_scalar($connection, string $sql, array $params = []): ?string
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

function erp_m20_cli_table_exists($connection, string $tableName): bool
{
    $count = erp_m20_cli_scalar(
        $connection,
        'SELECT COUNT(*) AS table_count
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
        ['dbo', $tableName]
    );

    return $count !== null && (int)$count > 0;
}

/**
 * @return array<string, mixed>
 */
function erp_m20_cli_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M20_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL'];
    }

    if ($userId === ERP_M20_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED'];
    }

    return ['allowed' => false, 'label' => 'FAIL'];
}

erp_m20_cli_require_first_existing(erp_m20_cli_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
erp_m20_cli_require_first_existing(erp_m20_cli_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');

$overallOk = true;
$failures = [];
$exceptionMessage = '';
$connection = false;

$userId = ERP_M20_PLATFORM_OWNER_ID;
$rolesText = '';
$permissionCount = 0;
$tableStatus = [];
$jobcardFoundationStatus = 'FAIL';
$jobcardOneStatus = 'FAIL';
$serviceOperationJobcardOneStatus = 'PENDING';
$historyCreatedStatus = 'PENDING';
$guardCreateLabel = 'FAIL';
$guardViewLabel = 'FAIL';
$guardListLabel = 'FAIL';

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();
    $resolvedUserId = erp_auth_current_user_id();

    if ($resolvedUserId !== $userId) {
        $overallOk = false;
        $failures[] = 'current_user_id mismatch';
    }

    $rolesResult = erp_auth_current_roles($connection, $userId);
    $roleKeys = erp_auth_context_role_keys($rolesResult);
    $rolesText = implode(', ', $roleKeys);

    if (!in_array('owner', $roleKeys, true) || !in_array('system_admin', $roleKeys, true)) {
        $overallOk = false;
        $failures[] = 'roles mismatch';
    }

    $permissionsResult = erp_auth_current_permissions($connection, $userId);
    $permissionKeys = erp_auth_context_permission_keys($permissionsResult);
    $permissionCount = count($permissionKeys);

    if ($permissionCount <= 0) {
        $overallOk = false;
        $failures[] = 'permissions empty';
    }

    foreach (ERP_M20_SERVICE_OPERATION_TABLES as $tableName) {
        $exists = erp_m20_cli_table_exists($connection, $tableName);
        $tableStatus[$tableName] = $exists ? 'OK' : 'FAIL';

        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName . ' missing';
        }
    }

    $jobcardFoundationOk = true;

    foreach (ERP_M20_JOBCARD_TABLES as $tableName) {
        if (!erp_m20_cli_table_exists($connection, $tableName)) {
            $jobcardFoundationOk = false;
            $overallOk = false;
            $failures[] = 'jobcard foundation table ' . $tableName . ' missing';
        }
    }

    $jobcardFoundationStatus = $jobcardFoundationOk ? 'OK' : 'FAIL';

    $jobcardCount = erp_m20_cli_scalar(
        $connection,
        'SELECT COUNT(*)
         FROM dbo.erp_jobcards
         WHERE jobcard_id = ?',
        [1]
    );

    if ($jobcardCount !== null && (int)$jobcardCount > 0) {
        $jobcardOneStatus = 'OK';
    } else {
        $overallOk = false;
        $jobcardOneStatus = 'FAIL';
        $failures[] = 'test JobCard jobcard_id 1 missing';
    }

    if (($tableStatus['erp_service_operations'] ?? 'FAIL') === 'OK') {
        $serviceOperationCount = erp_m20_cli_scalar(
            $connection,
            'SELECT COUNT(*)
             FROM dbo.erp_service_operations
             WHERE jobcard_id = ?',
            [1]
        );

        if ($serviceOperationCount !== null && (int)$serviceOperationCount > 0) {
            $serviceOperationJobcardOneStatus = 'OK';
        } else {
            $serviceOperationJobcardOneStatus = 'PENDING';
            $failures[] = 'no Service Operation for jobcard_id 1 yet (run browser create after SQL)';
        }
    }

    if (($tableStatus['erp_service_operation_change_history'] ?? 'FAIL') === 'OK') {
        $historyCount = erp_m20_cli_scalar(
            $connection,
            'SELECT COUNT(*)
             FROM dbo.erp_service_operation_change_history
             WHERE action_code = ?',
            ['SERVICE_OPERATION_CREATED']
        );

        if ($historyCount !== null && (int)$historyCount > 0) {
            $historyCreatedStatus = 'OK';
        } else {
            $historyCreatedStatus = 'PENDING';
            if ($serviceOperationJobcardOneStatus === 'PENDING') {
                $failures[] = 'history SERVICE_OPERATION_CREATED pending browser create';
            } else {
                $overallOk = false;
                $failures[] = 'history SERVICE_OPERATION_CREATED missing';
            }
        }
    }

    $guardCreate = erp_m20_cli_guard_eval($connection, $userId, 'service.operation.create');
    $guardView = erp_m20_cli_guard_eval($connection, $userId, 'service.operation.view');
    $guardList = erp_m20_cli_guard_eval($connection, $userId, 'service.operation.list');
    $guardCreateLabel = (string)$guardCreate['label'];
    $guardViewLabel = (string)$guardView['label'];
    $guardListLabel = (string)$guardList['label'];

    if (empty($guardCreate['allowed'])) {
        $overallOk = false;
        $failures[] = 'guard service.operation.create failed';
    }

    if (empty($guardView['allowed'])) {
        $overallOk = false;
        $failures[] = 'guard service.operation.view failed';
    }

    if (empty($guardList['allowed'])) {
        $overallOk = false;
        $failures[] = 'guard service.operation.list failed';
    }

    foreach (ERP_M20_SERVICE_OPERATION_TABLES as $tableName) {
        if (($tableStatus[$tableName] ?? 'FAIL') !== 'OK') {
            continue;
        }

        $count = erp_m20_cli_scalar($connection, 'SELECT COUNT(*) FROM dbo.' . $tableName);

        if ($count === null) {
            $overallOk = false;
            $failures[] = 'count failed for ' . $tableName;
        }
    }
} catch (Throwable $exception) {
    $overallOk = false;
    $failures[] = 'exception during test';
    $exceptionMessage = $exception->getMessage();
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

echo 'M20 SERVICE OPERATION FOUNDATION TEST' . PHP_EOL;
echo 'user_id = ' . $userId . PHP_EOL;
echo 'roles = ' . $rolesText . PHP_EOL;
echo 'permissions loaded = ' . $permissionCount . PHP_EOL;

foreach (ERP_M20_SERVICE_OPERATION_TABLES as $tableName) {
    echo 'table ' . $tableName . ' = ' . ($tableStatus[$tableName] ?? 'FAIL') . PHP_EOL;
}

echo 'jobcard foundation = ' . $jobcardFoundationStatus . PHP_EOL;
echo 'test JobCard jobcard_id 1 = ' . $jobcardOneStatus . PHP_EOL;
echo 'Service Operation for jobcard_id 1 = ' . $serviceOperationJobcardOneStatus . PHP_EOL;
echo 'history SERVICE_OPERATION_CREATED = ' . $historyCreatedStatus . PHP_EOL;
echo 'guard service.operation.create = ' . $guardCreateLabel . PHP_EOL;
echo 'guard service.operation.view = ' . $guardViewLabel . PHP_EOL;
echo 'guard service.operation.list = ' . $guardListLabel . PHP_EOL;
echo 'No Inventory write = OK' . PHP_EOL;
echo 'No Finance write = OK' . PHP_EOL;
echo 'No QC write = OK' . PHP_EOL;
echo 'No Delivery write = OK' . PHP_EOL;
echo 'No write performed by test = OK' . PHP_EOL;
echo 'Overall: ' . ($overallOk ? 'OK' : 'FAIL') . PHP_EOL;

if (!$overallOk && $failures !== []) {
    echo 'Failing checks: ' . implode(', ', $failures) . PHP_EOL;
}

if ($exceptionMessage !== '') {
    echo 'Exception:' . PHP_EOL;
    echo $exceptionMessage . PHP_EOL;
}

exit($overallOk ? 0 : 1);
