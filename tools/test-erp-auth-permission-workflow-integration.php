<?php
/**
 * MOGHARE360 ERP Auth + Permission + Workflow Integration CLI Test
 *
 * Mission 12 - CLI integration test only. SELECT only. No writes.
 */

declare(strict_types=1);

const ERP_M12_USER_ID = 10001;
const ERP_M12_REQUEST_ID = 4;

/** @var list<string> */
const ERP_M12_REQUIRED_HISTORY = [
    'ACCESS_REQUEST_SUBMITTED',
    'ACCESS_REQUEST_UNDER_REVIEW',
    'ACCESS_REQUEST_APPROVED',
    'ACCESS_REQUEST_APPLIED',
];

function erp_m12_cli_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m12_cli_helper_candidates(string $fileName): array
{
    $bases = [
        __DIR__,
        dirname(__DIR__),
        dirname(__DIR__, 2),
    ];

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

/**
 * @return list<array<string, string>>
 */
function erp_m12_cli_fetch_rows($connection, string $sql, array $params = []): array
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

function erp_m12_cli_scalar($connection, string $sql, array $params = []): ?string
{
    $rows = erp_m12_cli_fetch_rows($connection, $sql, $params);

    if ($rows === []) {
        return null;
    }

    $firstValue = reset($rows[0]);

    return $firstValue === false ? null : (string)$firstValue;
}

erp_m12_cli_require_first_existing(
    erp_m12_cli_helper_candidates('erp-auth-context.php'),
    'erp-auth-context.php'
);

erp_m12_cli_require_first_existing(
    erp_m12_cli_helper_candidates('erp-permission-guard.php'),
    'erp-permission-guard.php'
);

$overallOk = true;
$failures = [];
$exceptionMessage = '';
$connection = false;

$userId = ERP_M12_USER_ID;
$username = 'unknown';
$rolesText = '';
$permissionCount = 0;
$guardApprove = 'FAIL';
$guardApply = 'FAIL';
$requestId = ERP_M12_REQUEST_ID;
$requestState = '—';
$timelineStatus = 'INCOMPLETE';
$roleCount = '—';

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();
    $resolvedUserId = erp_auth_current_user_id();

    if ($resolvedUserId !== $userId) {
        $overallOk = false;
        $failures[] = 'current_user_id mismatch';
    }

    $user = erp_auth_load_current_user($connection);

    if ($user === null) {
        $overallOk = false;
        $failures[] = 'user load failed';
    } else {
        $username = (string)($user['username'] ?? 'unknown');

        if ($username !== 'mahin.paradigm.owner') {
            $overallOk = false;
            $failures[] = 'username mismatch';
        }
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

    $approveResult = erp_guard_action($connection, $userId, 'access.request.approve');
    $applyResult = erp_guard_action($connection, $userId, 'access.request.apply');

    if (!empty($approveResult['allowed'])) {
        $guardApprove = 'OK';
    } else {
        $overallOk = false;
        $failures[] = 'guard access.request.approve failed';
    }

    if (!empty($applyResult['allowed'])) {
        $guardApply = 'OK';
    } else {
        $overallOk = false;
        $failures[] = 'guard access.request.apply failed';
    }

    $requestRows = erp_m12_cli_fetch_rows(
        $connection,
        'SELECT request_id, request_state FROM dbo.core_access_requests WHERE request_id = ?',
        [$requestId]
    );

    if ($requestRows === []) {
        $overallOk = false;
        $failures[] = 'request_id 4 not visible';
    } else {
        $requestState = strtoupper(trim($requestRows[0]['request_state'] ?? ''));

        if ($requestState !== 'APPLIED') {
            $overallOk = false;
            $failures[] = 'request_state mismatch';
        }
    }

    $historyRows = erp_m12_cli_fetch_rows(
        $connection,
        'SELECT change_type FROM dbo.core_access_change_history WHERE request_id = ? ORDER BY changed_at, history_id',
        [$requestId]
    );

    $historyChangeTypes = array_map(
        static fn(array $row): string => strtoupper(trim($row['change_type'] ?? '')),
        $historyRows
    );

    $missingHistoryTypes = array_values(array_filter(
        ERP_M12_REQUIRED_HISTORY,
        static fn(string $changeType): bool => !in_array($changeType, $historyChangeTypes, true)
    ));

    if ($missingHistoryTypes === []) {
        $timelineStatus = 'COMPLETE';
    } else {
        $overallOk = false;
        $failures[] = 'workflow timeline incomplete';
    }

    $roleCount = erp_m12_cli_scalar(
        $connection,
        'SELECT COUNT(*) AS role_count FROM dbo.core_user_roles WHERE user_id = ?',
        [$userId]
    ) ?? '—';

    if ($roleCount !== '2') {
        $overallOk = false;
        $failures[] = 'core_user_roles count mismatch';
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

echo 'M12 AUTH + PERMISSION + WORKFLOW INTEGRATION TEST' . PHP_EOL;
echo 'user_id = ' . $userId . PHP_EOL;
echo 'username = ' . $username . PHP_EOL;
echo 'roles = ' . $rolesText . PHP_EOL;
echo 'permissions loaded = ' . $permissionCount . PHP_EOL;
echo 'guard access.request.approve = ' . $guardApprove . PHP_EOL;
echo 'guard access.request.apply = ' . $guardApply . PHP_EOL;
echo 'request_id = ' . $requestId . PHP_EOL;
echo 'request_state = ' . $requestState . PHP_EOL;
echo 'workflow timeline = ' . $timelineStatus . PHP_EOL;
echo 'core_user_roles count = ' . $roleCount . PHP_EOL;
echo 'Real Assignment = NOT PERFORMED' . PHP_EOL;
echo 'No write performed = OK' . PHP_EOL;
echo 'Overall: ' . ($overallOk ? 'OK' : 'FAIL') . PHP_EOL;

if (!$overallOk && $failures !== []) {
    echo 'Failing checks: ' . implode(', ', $failures) . PHP_EOL;
}

if ($exceptionMessage !== '') {
    echo 'Exception:' . PHP_EOL;
    echo $exceptionMessage . PHP_EOL;
}

exit($overallOk ? 0 : 1);
