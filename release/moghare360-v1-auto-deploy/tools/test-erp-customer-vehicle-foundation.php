<?php
/**
 * MOGHARE360 ERP Customer / Vehicle Foundation CLI Test
 *
 * Mission 15 - CLI read-only test only. No writes.
 */

declare(strict_types=1);

const ERP_M15_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_M15_PLACEHOLDER_ACTIONS = [
    'customer.vehicle.create' => 'placeholder_customer_vehicle_create',
    'customer.vehicle.view' => 'placeholder_customer_vehicle_view',
];

/** @var list<string> */
const ERP_M15_TABLES = [
    'erp_customers',
    'erp_customer_phones',
    'erp_vehicles',
    'erp_customer_vehicle_relations',
    'erp_customer_vehicle_change_history',
];

function erp_m15_cli_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m15_cli_helper_candidates(string $fileName): array
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

function erp_m15_cli_scalar($connection, string $sql, array $params = []): ?string
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

function erp_m15_cli_table_exists($connection, string $tableName): bool
{
    $count = erp_m15_cli_scalar(
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
function erp_m15_cli_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M15_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL'];
    }

    if ($userId === ERP_M15_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED'];
    }

    return ['allowed' => false, 'label' => 'FAIL'];
}

erp_m15_cli_require_first_existing(erp_m15_cli_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
erp_m15_cli_require_first_existing(erp_m15_cli_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');

$overallOk = true;
$failures = [];
$exceptionMessage = '';
$connection = false;

$userId = ERP_M15_PLATFORM_OWNER_ID;
$rolesText = '';
$permissionCount = 0;
$tableStatus = [];
$guardCreateLabel = 'FAIL';
$guardViewLabel = 'FAIL';

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

    foreach (ERP_M15_TABLES as $tableName) {
        $exists = erp_m15_cli_table_exists($connection, $tableName);
        $tableStatus[$tableName] = $exists ? 'OK' : 'FAIL';

        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName . ' missing';
        }
    }

    $guardCreate = erp_m15_cli_guard_eval($connection, $userId, 'customer.vehicle.create');
    $guardView = erp_m15_cli_guard_eval($connection, $userId, 'customer.vehicle.view');
    $guardCreateLabel = (string)$guardCreate['label'];
    $guardViewLabel = (string)$guardView['label'];

    if (empty($guardCreate['allowed'])) {
        $overallOk = false;
        $failures[] = 'guard customer.vehicle.create failed';
    }

    if (empty($guardView['allowed'])) {
        $overallOk = false;
        $failures[] = 'guard customer.vehicle.view failed';
    }

    foreach (ERP_M15_TABLES as $tableName) {
        if ($tableStatus[$tableName] !== 'OK') {
            continue;
        }

        $count = erp_m15_cli_scalar($connection, 'SELECT COUNT(*) FROM dbo.' . $tableName);

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

echo 'M15 CUSTOMER / VEHICLE FOUNDATION TEST' . PHP_EOL;
echo 'user_id = ' . $userId . PHP_EOL;
echo 'roles = ' . $rolesText . PHP_EOL;
echo 'permissions loaded = ' . $permissionCount . PHP_EOL;

foreach (ERP_M15_TABLES as $tableName) {
    echo 'table ' . $tableName . ' = ' . ($tableStatus[$tableName] ?? 'FAIL') . PHP_EOL;
}

echo 'guard customer.vehicle.create = ' . $guardCreateLabel . PHP_EOL;
echo 'guard customer.vehicle.view = ' . $guardViewLabel . PHP_EOL;
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
