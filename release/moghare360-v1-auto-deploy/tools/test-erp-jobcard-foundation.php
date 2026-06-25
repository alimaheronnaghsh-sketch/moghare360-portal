<?php
/**
 * MOGHARE360 ERP JobCard Foundation CLI Test
 *
 * Mission 17 - CLI read-only test only. No writes.
 */

declare(strict_types=1);

const ERP_M17_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_M17_PLACEHOLDER_ACTIONS = [
    'jobcard.create' => 'placeholder_jobcard_create',
    'jobcard.view' => 'placeholder_jobcard_view',
    'jobcard.list' => 'placeholder_jobcard_list',
];

/** @var list<string> */
const ERP_M17_JOBCARD_TABLES = [
    'erp_jobcards',
    'erp_jobcard_change_history',
];

/** @var list<string> */
const ERP_M17_FOUNDATION_TABLES = [
    'erp_customers',
    'erp_vehicles',
    'erp_customer_vehicle_relations',
];

function erp_m17_cli_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m17_cli_helper_candidates(string $fileName): array
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

function erp_m17_cli_scalar($connection, string $sql, array $params = []): ?string
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

function erp_m17_cli_table_exists($connection, string $tableName): bool
{
    $count = erp_m17_cli_scalar(
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
function erp_m17_cli_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M17_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL'];
    }

    if ($userId === ERP_M17_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED'];
    }

    return ['allowed' => false, 'label' => 'FAIL'];
}

erp_m17_cli_require_first_existing(erp_m17_cli_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
erp_m17_cli_require_first_existing(erp_m17_cli_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');

$overallOk = true;
$failures = [];
$exceptionMessage = '';
$connection = false;

$userId = ERP_M17_PLATFORM_OWNER_ID;
$rolesText = '';
$permissionCount = 0;
$tableStatus = [];
$foundationStatus = 'FAIL';
$relationStatus = 'FAIL';
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

    foreach (ERP_M17_JOBCARD_TABLES as $tableName) {
        $exists = erp_m17_cli_table_exists($connection, $tableName);
        $tableStatus[$tableName] = $exists ? 'OK' : 'FAIL';

        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName . ' missing';
        }
    }

    $foundationOk = true;

    foreach (ERP_M17_FOUNDATION_TABLES as $tableName) {
        if (!erp_m17_cli_table_exists($connection, $tableName)) {
            $foundationOk = false;
            $overallOk = false;
            $failures[] = 'foundation table ' . $tableName . ' missing';
        }
    }

    $foundationStatus = $foundationOk ? 'OK' : 'FAIL';

    $relationCount = erp_m17_cli_scalar(
        $connection,
        'SELECT COUNT(*)
         FROM dbo.erp_customer_vehicle_relations
         WHERE relation_id = ?',
        [1]
    );

    if ($relationCount !== null && (int)$relationCount > 0) {
        $relationStatus = 'OK';
    } else {
        $overallOk = false;
        $relationStatus = 'FAIL';
        $failures[] = 'test relation relation_id 1 missing';
    }

    $guardCreate = erp_m17_cli_guard_eval($connection, $userId, 'jobcard.create');
    $guardView = erp_m17_cli_guard_eval($connection, $userId, 'jobcard.view');
    $guardList = erp_m17_cli_guard_eval($connection, $userId, 'jobcard.list');
    $guardCreateLabel = (string)$guardCreate['label'];
    $guardViewLabel = (string)$guardView['label'];
    $guardListLabel = (string)$guardList['label'];

    if (empty($guardCreate['allowed'])) {
        $overallOk = false;
        $failures[] = 'guard jobcard.create failed';
    }

    if (empty($guardView['allowed'])) {
        $overallOk = false;
        $failures[] = 'guard jobcard.view failed';
    }

    if (empty($guardList['allowed'])) {
        $overallOk = false;
        $failures[] = 'guard jobcard.list failed';
    }

    foreach (ERP_M17_JOBCARD_TABLES as $tableName) {
        if (($tableStatus[$tableName] ?? 'FAIL') !== 'OK') {
            continue;
        }

        $count = erp_m17_cli_scalar($connection, 'SELECT COUNT(*) FROM dbo.' . $tableName);

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

echo 'M17 JOBCARD FOUNDATION TEST' . PHP_EOL;
echo 'user_id = ' . $userId . PHP_EOL;
echo 'roles = ' . $rolesText . PHP_EOL;
echo 'permissions loaded = ' . $permissionCount . PHP_EOL;

foreach (ERP_M17_JOBCARD_TABLES as $tableName) {
    echo 'table ' . $tableName . ' = ' . ($tableStatus[$tableName] ?? 'FAIL') . PHP_EOL;
}

echo 'customer_vehicle_foundation = ' . $foundationStatus . PHP_EOL;
echo 'test relation relation_id 1 = ' . $relationStatus . PHP_EOL;
echo 'guard jobcard.create = ' . $guardCreateLabel . PHP_EOL;
echo 'guard jobcard.view = ' . $guardViewLabel . PHP_EOL;
echo 'guard jobcard.list = ' . $guardListLabel . PHP_EOL;
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
