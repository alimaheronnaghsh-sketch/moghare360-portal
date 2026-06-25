<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Service Operation Read-Only Detail
 *
 * Mission 20 - SELECT only. No form. No POST. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M20_PLATFORM_OWNER_ID = 10001;
const ERP_M20_VIEW_ACTION = 'service.operation.view';

/** @var array<string, string> */
const ERP_M20_PLACEHOLDER_ACTIONS = [
    'service.operation.create' => 'placeholder_service_operation_create',
    'service.operation.view' => 'placeholder_service_operation_view',
    'service.operation.list' => 'placeholder_service_operation_list',
    'service.operation.assign' => 'placeholder_service_operation_assign',
    'service.operation.status.change' => 'placeholder_service_operation_status_change',
];

function erp_m20_detail_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m20_detail_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

/**
 * @return list<array<string, string>>
 */
function erp_m20_detail_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false) {
        return [];
    }

    if (!@odbc_execute($statement, $params)) {
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

function erp_m20_detail_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m20_detail_display(string $value): string
{
    return erp_m20_detail_h(trim($value) === '' ? '—' : $value);
}

function erp_m20_detail_parse_service_operation_id(): int
{
    if (!isset($_GET['service_operation_id'])) {
        return 0;
    }

    $raw = trim((string)$_GET['service_operation_id']);

    if ($raw === '' || !ctype_digit($raw)) {
        return 0;
    }

    return (int)$raw;
}

/**
 * @return array<string, mixed>
 */
function erp_m20_detail_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M20_PLACEHOLDER_ACTIONS[$actionKey])) {
        return [
            'allowed' => false,
            'label' => 'FAIL',
            'placeholder' => false,
        ];
    }

    if ($userId === ERP_M20_PLATFORM_OWNER_ID) {
        return [
            'allowed' => true,
            'label' => 'PLACEHOLDER_OWNER_ALLOWED',
            'placeholder' => true,
        ];
    }

    return [
        'allowed' => false,
        'label' => 'FAIL',
        'placeholder' => true,
    ];
}

try {
    erp_m20_detail_require_first_existing(erp_m20_detail_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m20_detail_require_first_existing(erp_m20_detail_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Detail Error</title></head><body>';
    echo '<p>ERP Service Operation detail could not be loaded.</p>';
    echo '</body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');

$userId = ERP_M20_PLATFORM_OWNER_ID;
$username = '—';
$rolesText = '—';
$permissionCount = 0;
$guardViewLabel = 'FAIL';
$connectionStatus = 'FAIL';
$connectionDetail = 'Not connected';
$errorMessage = '';
$overallOk = false;
$operationRow = [];
$historyRows = [];
$selectedServiceOperationId = 0;
$connection = false;

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();
    $connectionStatus = 'OK';
    $connectionDetail = 'ODBC Trusted Connection connected';

    $resolvedUserId = erp_auth_current_user_id();

    if ($resolvedUserId !== $userId) {
        throw new RuntimeException('Access denied.');
    }

    $user = erp_auth_load_current_user($connection);

    if ($user === null) {
        throw new RuntimeException('Access denied.');
    }

    $username = (string)($user['username'] ?? '—');

    $rolesResult = erp_auth_current_roles($connection, $userId);
    $roleKeys = erp_auth_context_role_keys($rolesResult);
    $rolesText = $roleKeys !== [] ? implode(', ', $roleKeys) : '—';

    $permissionsResult = erp_auth_current_permissions($connection, $userId);
    $permissionKeys = erp_auth_context_permission_keys($permissionsResult);
    $permissionCount = count($permissionKeys);

    $guardView = erp_m20_detail_guard_eval($connection, $userId, ERP_M20_VIEW_ACTION);
    $guardViewLabel = (string)($guardView['label'] ?? 'FAIL');

    if (empty($guardView['allowed'])) {
        throw new RuntimeException('Access denied.');
    }

    $selectedServiceOperationId = erp_m20_detail_parse_service_operation_id();

    if ($selectedServiceOperationId <= 0) {
        $latestRows = erp_m20_detail_fetch_rows(
            $connection,
            'SELECT TOP 1 service_operation_id FROM dbo.erp_service_operations ORDER BY service_operation_id DESC'
        );

        if ($latestRows !== []) {
            $selectedServiceOperationId = (int)($latestRows[0]['service_operation_id'] ?? 0);
        }
    }

    if ($selectedServiceOperationId > 0) {
        $operationRows = erp_m20_detail_fetch_rows(
            $connection,
            'SELECT TOP 1
                s.*,
                j.jobcard_number,
                j.jobcard_status
            FROM dbo.erp_service_operations s
            INNER JOIN dbo.erp_jobcards j ON j.jobcard_id = s.jobcard_id
            WHERE s.service_operation_id = ?',
            [$selectedServiceOperationId]
        );

        if ($operationRows !== []) {
            $operationRow = $operationRows[0];
        }

        $historyRows = erp_m20_detail_fetch_rows(
            $connection,
            'SELECT
                history_id,
                service_operation_id,
                jobcard_id,
                action_code,
                old_status,
                new_status,
                changed_by_user_id,
                changed_at,
                change_note
            FROM dbo.erp_service_operation_change_history
            WHERE service_operation_id = ?
            ORDER BY history_id',
            [$selectedServiceOperationId]
        );
    }

    $overallOk = $connectionStatus === 'OK'
        && $operationRow !== []
        && in_array($guardViewLabel, ['OK', 'PLACEHOLDER', 'PLACEHOLDER_OWNER_ALLOWED'], true);
} catch (Throwable $exception) {
    $errorMessage = 'ERP Service Operation detail could not be completed.';
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
    <title>Mission 20 - Service Operation Detail</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; line-height: 1.5; }
        .wrap { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .banner { background: #7f1d1d; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        h1, h2 { margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.92rem; margin-bottom: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; width: 240px; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL READ-ONLY SERVICE OPERATION DETAIL - NO WRITE</div>

    <div class="card">
        <h1>Service Operation Detail</h1>
        <p>Mission 20 - SELECT only - No form - No write</p>
        <?php if ($errorMessage !== ''): ?>
            <p class="fail"><?= erp_m20_detail_h($errorMessage) ?></p>
        <?php endif; ?>
        <p>
            <a href="erp-service-operation-readonly-list.php">Open read-only list</a>
            |
            <a href="erp-service-operation-create.php">Open create prototype</a>
        </p>
    </div>

    <div class="card">
        <h2>Summary</h2>
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m20_detail_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection status</th><td><?= erp_m20_detail_h($connectionStatus) ?> — <?= erp_m20_detail_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m20_detail_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m20_detail_h($username) ?></td></tr>
                <tr><th>roles</th><td><?= erp_m20_detail_h($rolesText) ?></td></tr>
                <tr><th>permissions count</th><td><?= erp_m20_detail_h((string)$permissionCount) ?></td></tr>
                <tr><th>guard service.operation.view</th><td><?= erp_m20_detail_h($guardViewLabel) ?></td></tr>
                <tr><th>Selected service_operation_id</th><td><?= erp_m20_detail_h((string)$selectedServiceOperationId) ?></td></tr>
                <tr><th>Overall Status</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
            </tbody>
        </table>
    </div>

    <?php if ($operationRow === []): ?>
        <div class="card">
            <p>No Service Operation record found for the selected service_operation_id.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>Service Operation Header</h2>
            <table>
                <tbody>
                    <tr><th>Service Operation ID</th><td><?= erp_m20_detail_display($operationRow['service_operation_id'] ?? '') ?></td></tr>
                    <tr><th>JobCard ID</th><td><?= erp_m20_detail_display($operationRow['jobcard_id'] ?? '') ?></td></tr>
                    <tr><th>JobCard Number</th><td><?= erp_m20_detail_display($operationRow['jobcard_number'] ?? '') ?></td></tr>
                    <tr><th>JobCard Status</th><td><?= erp_m20_detail_display($operationRow['jobcard_status'] ?? '') ?></td></tr>
                    <tr><th>Service Title</th><td><?= erp_m20_detail_display($operationRow['service_title'] ?? '') ?></td></tr>
                    <tr><th>Service Status</th><td><?= erp_m20_detail_display($operationRow['service_status'] ?? '') ?></td></tr>
                    <tr><th>Assigned To User ID</th><td><?= erp_m20_detail_display($operationRow['assigned_to_user_id'] ?? '') ?></td></tr>
                    <tr><th>Is Active</th><td><?= erp_m20_detail_display($operationRow['is_active'] ?? '') ?></td></tr>
                    <tr><th>Created By User ID</th><td><?= erp_m20_detail_display($operationRow['created_by_user_id'] ?? '') ?></td></tr>
                    <tr><th>Created At</th><td><?= erp_m20_detail_display($operationRow['created_at'] ?? '') ?></td></tr>
                    <tr><th>Updated At</th><td><?= erp_m20_detail_display($operationRow['updated_at'] ?? '') ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Service Description</h2>
            <p><?= erp_m20_detail_display($operationRow['service_description'] ?? '') ?></p>
        </div>

        <div class="card">
            <h2>History Timeline</h2>
            <?php if ($historyRows === []): ?>
                <p>No history rows found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>History ID</th>
                            <th>Action Code</th>
                            <th>Old Status</th>
                            <th>New Status</th>
                            <th>Changed By</th>
                            <th>Changed At</th>
                            <th>Change Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyRows as $historyRow): ?>
                            <tr>
                                <td><?= erp_m20_detail_display($historyRow['history_id'] ?? '') ?></td>
                                <td><?= erp_m20_detail_display($historyRow['action_code'] ?? '') ?></td>
                                <td><?= erp_m20_detail_display($historyRow['old_status'] ?? '') ?></td>
                                <td><?= erp_m20_detail_display($historyRow['new_status'] ?? '') ?></td>
                                <td><?= erp_m20_detail_display($historyRow['changed_by_user_id'] ?? '') ?></td>
                                <td><?= erp_m20_detail_display($historyRow['changed_at'] ?? '') ?></td>
                                <td><?= erp_m20_detail_display($historyRow['change_note'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
