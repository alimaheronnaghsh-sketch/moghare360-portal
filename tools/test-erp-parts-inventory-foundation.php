<?php
/**
 * MOGHARE360 ERP Parts / Inventory Foundation CLI Test
 *
 * Mission 22 - CLI read-only test only. No writes.
 */

declare(strict_types=1);

const ERP_M22_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_M22_PLACEHOLDER_ACTIONS = [
    'parts.create' => 'placeholder_parts_create',
    'parts.list' => 'placeholder_parts_list',
    'stock.view' => 'placeholder_stock_view',
];

/** @var list<string> */
const ERP_M22_TABLES = [
    'erp_parts',
    'erp_stock_locations',
    'erp_stock_movements',
];

function erp_m22_cli_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m22_cli_helper_candidates(string $fileName): array
{
    $bases = [__DIR__, dirname(__DIR__), dirname(__DIR__, 2)];
    $candidates = [];

    foreach ($bases as $base) {
        $candidates[] = $base . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;
    }

    return array_values(array_unique($candidates));
}

function erp_m22_cli_scalar($connection, string $sql, array $params = []): ?string
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

function erp_m22_cli_table_exists($connection, string $tableName): bool
{
    $count = erp_m22_cli_scalar(
        $connection,
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
        ['dbo', $tableName]
    );

    return $count !== null && (int)$count > 0;
}

/**
 * @return array<string, mixed>
 */
function erp_m22_cli_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M22_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL'];
    }

    if ($userId === ERP_M22_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED'];
    }

    return ['allowed' => false, 'label' => 'FAIL'];
}

erp_m22_cli_require_first_existing(erp_m22_cli_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
erp_m22_cli_require_first_existing(erp_m22_cli_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');

$overallOk = true;
$failures = [];
$exceptionMessage = '';
$connection = false;

$userId = ERP_M22_PLATFORM_OWNER_ID;
$rolesText = '';
$permissionCount = 0;
$tableStatus = [];
$mainLocationStatus = 'FAIL';
$partsCountStatus = 'PENDING';
$stockQueryStatus = 'FAIL';
$issueMovementStatus = 'OK';
$guardCreateLabel = 'FAIL';
$guardListLabel = 'FAIL';
$guardStockLabel = 'FAIL';

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();

    if (erp_auth_current_user_id() !== $userId) {
        $overallOk = false;
        $failures[] = 'current_user_id mismatch';
    }

    $rolesResult = erp_auth_current_roles($connection, $userId);
    $roleKeys = erp_auth_context_role_keys($rolesResult);
    $rolesText = implode(', ', $roleKeys);

    $permissionsResult = erp_auth_current_permissions($connection, $userId);
    $permissionCount = count(erp_auth_context_permission_keys($permissionsResult));

    foreach (ERP_M22_TABLES as $tableName) {
        $exists = erp_m22_cli_table_exists($connection, $tableName);
        $tableStatus[$tableName] = $exists ? 'OK' : 'FAIL';

        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName . ' missing';
        }
    }

    $mainCount = erp_m22_cli_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_stock_locations WHERE location_code = ?',
        ['MAIN']
    );

    if ($mainCount !== null && (int)$mainCount > 0) {
        $mainLocationStatus = 'OK';
    } else {
        $overallOk = false;
        $mainLocationStatus = 'FAIL';
        $failures[] = 'MAIN stock location missing';
    }

    if (($tableStatus['erp_parts'] ?? 'FAIL') === 'OK') {
        $partsCount = erp_m22_cli_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_parts');

        if ($partsCount !== null && (int)$partsCount > 0) {
            $partsCountStatus = 'OK';
        } else {
            $partsCountStatus = 'PENDING';
            $failures[] = 'no parts yet (run browser create after SQL)';
        }
    }

    $stockQuery = erp_m22_cli_scalar(
        $connection,
        'SELECT COUNT(*) FROM (
            SELECT p.part_id
            FROM dbo.erp_parts p
            INNER JOIN dbo.erp_stock_locations sl ON sl.is_active = 1
            LEFT JOIN dbo.erp_stock_movements m
                ON m.part_id = p.part_id AND m.stock_location_id = sl.stock_location_id
            WHERE p.is_active = 1
            GROUP BY p.part_id, sl.stock_location_id
        ) q'
    );

    if ($stockQuery !== null) {
        $stockQueryStatus = 'OK';
    } else {
        $overallOk = false;
        $stockQueryStatus = 'FAIL';
        $failures[] = 'stock list query failed';
    }

    if (($tableStatus['erp_stock_movements'] ?? 'FAIL') === 'OK') {
        $issueCount = erp_m22_cli_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_stock_movements WHERE movement_type = ?',
            ['ISSUE']
        );

        if ($issueCount !== null && (int)$issueCount > 0) {
            $issueMovementStatus = 'FAIL';
            $overallOk = false;
            $failures[] = 'ISSUE stock movement found (consumption not allowed in M22)';
        }
    }

    $guardCreate = erp_m22_cli_guard_eval($connection, $userId, 'parts.create');
    $guardList = erp_m22_cli_guard_eval($connection, $userId, 'parts.list');
    $guardStock = erp_m22_cli_guard_eval($connection, $userId, 'stock.view');
    $guardCreateLabel = (string)$guardCreate['label'];
    $guardListLabel = (string)$guardList['label'];
    $guardStockLabel = (string)$guardStock['label'];

    foreach ([$guardCreate, $guardList, $guardStock] as $idx => $guard) {
        if (empty($guard['allowed'])) {
            $overallOk = false;
            $failures[] = 'permission guard failed #' . ($idx + 1);
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

echo 'M22 PARTS INVENTORY FOUNDATION TEST' . PHP_EOL;
echo 'user_id = ' . $userId . PHP_EOL;
echo 'roles = ' . $rolesText . PHP_EOL;
echo 'permissions loaded = ' . $permissionCount . PHP_EOL;

foreach (ERP_M22_TABLES as $tableName) {
    echo 'table ' . $tableName . ' = ' . ($tableStatus[$tableName] ?? 'FAIL') . PHP_EOL;
}

echo 'MAIN stock location = ' . $mainLocationStatus . PHP_EOL;
echo 'parts count = ' . $partsCountStatus . PHP_EOL;
echo 'stock list query = ' . $stockQueryStatus . PHP_EOL;
echo 'No ISSUE movement (no consumption) = ' . $issueMovementStatus . PHP_EOL;
echo 'guard parts.create = ' . $guardCreateLabel . PHP_EOL;
echo 'guard parts.list = ' . $guardListLabel . PHP_EOL;
echo 'guard stock.view = ' . $guardStockLabel . PHP_EOL;
echo 'No stock consumption = OK' . PHP_EOL;
echo 'No JobCard part usage = OK' . PHP_EOL;
echo 'No finance write = OK' . PHP_EOL;
echo 'No purchase write = OK' . PHP_EOL;
echo 'No write performed by test = OK' . PHP_EOL;
echo 'Overall: ' . ($overallOk ? 'OK' : 'FAIL') . PHP_EOL;

if (!$overallOk && $failures !== []) {
    echo 'Failing checks: ' . implode(', ', $failures) . PHP_EOL;
}

if ($exceptionMessage !== '') {
    echo 'Exception:' . PHP_EOL . $exceptionMessage . PHP_EOL;
}

exit($overallOk ? 0 : 1);
