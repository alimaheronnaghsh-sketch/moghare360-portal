<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Browser Read-Only Auth + Permission + Workflow Integration Test
 *
 * Mission 12 - SELECT only. No form. No write. No login replacement.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M12_USER_ID = 10001;
const ERP_M12_REQUEST_ID = 4;

/** @var list<string> */
const ERP_M12_REQUIRED_HISTORY = [
    'ACCESS_REQUEST_SUBMITTED',
    'ACCESS_REQUEST_UNDER_REVIEW',
    'ACCESS_REQUEST_APPROVED',
    'ACCESS_REQUEST_APPLIED',
];

function erp_m12_browser_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m12_browser_helper_candidates(string $fileName): array
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
function erp_m12_browser_fetch_rows($connection, string $sql, array $params = []): array
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

function erp_m12_browser_scalar($connection, string $sql, array $params = []): ?string
{
    $rows = erp_m12_browser_fetch_rows($connection, $sql, $params);

    if ($rows === []) {
        return null;
    }

    $firstValue = reset($rows[0]);

    return $firstValue === false ? null : (string)$firstValue;
}

function erp_m12_browser_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

try {
    erp_m12_browser_require_first_existing(
        erp_m12_browser_helper_candidates('erp-auth-context.php'),
        'erp-auth-context.php'
    );

    erp_m12_browser_require_first_existing(
        erp_m12_browser_helper_candidates('erp-permission-guard.php'),
        'erp-permission-guard.php'
    );
} catch (Throwable $exception) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Integration Test Error</title></head><body>';
    echo '<p>ERP integration test could not be loaded.</p>';
    echo '<p>Required helper files were not found in approved include paths.</p>';
    echo '</body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');

$connectionStatus = 'FAIL';
$connectionDetail = 'Not connected';
$overallOk = false;
$noWriteOk = true;
$errorMessage = '';
$connection = false;

$userId = ERP_M12_USER_ID;
$username = '—';
$rolesText = '—';
$permissionCount = 0;
$guardApprove = 'FAIL';
$guardApply = 'FAIL';
$requestId = ERP_M12_REQUEST_ID;
$requestNumber = '—';
$requestType = '—';
$requestState = '—';
$timelineStatus = 'INCOMPLETE';
$historyStatus = [
    'ACCESS_REQUEST_SUBMITTED' => 'FAIL',
    'ACCESS_REQUEST_UNDER_REVIEW' => 'FAIL',
    'ACCESS_REQUEST_APPROVED' => 'FAIL',
    'ACCESS_REQUEST_APPLIED' => 'FAIL',
];
$roleCount = '—';

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();
    $connectionStatus = 'OK';
    $connectionDetail = 'ODBC Trusted Connection connected';

    $resolvedUserId = erp_auth_current_user_id();

    if ($resolvedUserId !== $userId) {
        throw new RuntimeException('Unexpected current user id.');
    }

    $user = erp_auth_load_current_user($connection);

    if ($user === null) {
        throw new RuntimeException('Current user could not be loaded.');
    }

    $username = (string)($user['username'] ?? '—');

    $rolesResult = erp_auth_current_roles($connection, $userId);
    $roleKeys = erp_auth_context_role_keys($rolesResult);
    $rolesText = $roleKeys !== [] ? implode(', ', $roleKeys) : '—';

    $permissionsResult = erp_auth_current_permissions($connection, $userId);
    $permissionKeys = erp_auth_context_permission_keys($permissionsResult);
    $permissionCount = count($permissionKeys);

    $approveResult = erp_guard_action($connection, $userId, 'access.request.approve');
    $applyResult = erp_guard_action($connection, $userId, 'access.request.apply');
    $guardApprove = !empty($approveResult['allowed']) ? 'OK' : 'FAIL';
    $guardApply = !empty($applyResult['allowed']) ? 'OK' : 'FAIL';

    $requestRows = erp_m12_browser_fetch_rows(
        $connection,
        'SELECT request_id, request_number, request_type, request_state FROM dbo.core_access_requests WHERE request_id = ?',
        [$requestId]
    );

    if ($requestRows === []) {
        throw new RuntimeException('Request 4 could not be loaded.');
    }

    $requestRow = $requestRows[0];
    $requestNumber = (string)($requestRow['request_number'] ?? '—');
    $requestType = (string)($requestRow['request_type'] ?? '—');
    $requestState = strtoupper(trim((string)($requestRow['request_state'] ?? '—')));

    $historyRows = erp_m12_browser_fetch_rows(
        $connection,
        'SELECT change_type FROM dbo.core_access_change_history WHERE request_id = ? ORDER BY changed_at, history_id',
        [$requestId]
    );

    $historyChangeTypes = array_map(
        static fn(array $row): string => strtoupper(trim($row['change_type'] ?? '')),
        $historyRows
    );

    foreach (ERP_M12_REQUIRED_HISTORY as $requiredType) {
        $historyStatus[$requiredType] = in_array($requiredType, $historyChangeTypes, true) ? 'OK' : 'FAIL';
    }

    $timelineStatus = !in_array('FAIL', $historyStatus, true) ? 'COMPLETE' : 'INCOMPLETE';

    $roleCount = erp_m12_browser_scalar(
        $connection,
        'SELECT COUNT(*) AS role_count FROM dbo.core_user_roles WHERE user_id = ?',
        [$userId]
    ) ?? '—';

    $overallOk = $connectionStatus === 'OK'
        && $username === 'mahin.paradigm.owner'
        && in_array('owner', $roleKeys, true)
        && in_array('system_admin', $roleKeys, true)
        && $permissionCount > 0
        && $guardApprove === 'OK'
        && $guardApply === 'OK'
        && $requestState === 'APPLIED'
        && $requestNumber === 'AR-20260620-084634-10001'
        && $requestType === 'ROLE_GRANT'
        && $timelineStatus === 'COMPLETE'
        && $roleCount === '2'
        && $noWriteOk;
} catch (Throwable $exception) {
    $errorMessage = 'ERP integration test could not be completed.';
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
    <title>Mission 12 - Auth + Permission + Workflow Integration Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f6f8;
            color: #1f2937;
            line-height: 1.5;
        }

        .wrap {
            max-width: 960px;
            margin: 0 auto;
            padding: 24px;
        }

        .banner {
            background: #7f1d1d;
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 16px;
        }

        .card {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 1.4rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            width: 320px;
        }

        .ok {
            color: #166534;
            font-weight: bold;
        }

        .fail {
            color: #b91c1c;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL READ-ONLY AUTH + PERMISSION + WORKFLOW INTEGRATION TEST - REMOVE OR PROTECT BEFORE DEPLOYMENT</div>

    <div class="card">
        <h1>Browser Read-Only Integration Test</h1>
        <p>Mission 12 - SELECT only - No form - No write</p>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="card">
            <p class="fail"><?= erp_m12_browser_h($errorMessage) ?></p>
        </div>
    <?php endif; ?>

    <div class="card">
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m12_browser_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection status</th><td class="<?= $connectionStatus === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m12_browser_h($connectionStatus) ?> — <?= erp_m12_browser_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m12_browser_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m12_browser_h($username) ?></td></tr>
                <tr><th>roles</th><td><?= erp_m12_browser_h($rolesText) ?></td></tr>
                <tr><th>permissions count</th><td><?= erp_m12_browser_h((string)$permissionCount) ?></td></tr>
                <tr><th>guard access.request.approve</th><td class="<?= $guardApprove === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m12_browser_h($guardApprove) ?></td></tr>
                <tr><th>guard access.request.apply</th><td class="<?= $guardApply === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m12_browser_h($guardApply) ?></td></tr>
                <tr><th>request_id</th><td><?= erp_m12_browser_h((string)$requestId) ?></td></tr>
                <tr><th>request_number</th><td><?= erp_m12_browser_h($requestNumber) ?></td></tr>
                <tr><th>request_type</th><td><?= erp_m12_browser_h($requestType) ?></td></tr>
                <tr><th>request_state</th><td><?= erp_m12_browser_h($requestState) ?></td></tr>
                <tr><th>workflow timeline status</th><td class="<?= $timelineStatus === 'COMPLETE' ? 'ok' : 'fail' ?>"><?= erp_m12_browser_h($timelineStatus) ?></td></tr>
                <?php foreach ($historyStatus as $changeType => $status): ?>
                    <tr>
                        <th><?= erp_m12_browser_h($changeType) ?></th>
                        <td class="<?= $status === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m12_browser_h($status) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr><th>core_user_roles count</th><td><?= erp_m12_browser_h($roleCount) ?></td></tr>
                <tr><th>Real Assignment</th><td>NOT PERFORMED</td></tr>
                <tr><th>No write performed</th><td class="ok"><?= $noWriteOk ? 'OK' : 'FAIL' ?></td></tr>
                <tr><th>Overall Status</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
