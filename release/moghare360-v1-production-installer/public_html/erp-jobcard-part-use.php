<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP JobCard Part Usage Controlled Prototype
 *
 * Mission 24 - POST register usage + ISSUE movement with Auth, Permission Guard, CSRF, transaction.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M24_PLATFORM_OWNER_ID = 10001;
const ERP_M24_CSRF_SESSION_KEY = 'm24_jobcard_part_use_csrf';
const ERP_M24_USE_ACTION = 'jobcard.part.use';
const ERP_M24_ISSUE_ACTION = 'stock.issue.create';

/** @var list<string> */
const ERP_M24_ALLOWED_SERVICE_STATUSES = ['ASSIGNED', 'IN_PROGRESS', 'WAITING_PARTS'];

/** @var array<string, string> */
const ERP_M24_PLACEHOLDER_ACTIONS = [
    'jobcard.part.use' => 'placeholder_jobcard_part_use',
    'jobcard.part.view' => 'placeholder_jobcard_part_view',
    'jobcard.part.list' => 'placeholder_jobcard_part_list',
    'jobcard.part.reverse' => 'placeholder_jobcard_part_reverse',
    'stock.issue.create' => 'placeholder_stock_issue_create',
    'stock.return.create' => 'placeholder_stock_return_create',
];

function erp_m24_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m24_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function erp_m24_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m24_post_string(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

function erp_m24_is_localhost_request(): bool
{
    foreach ([$_SERVER['HTTP_HOST'] ?? '', $_SERVER['SERVER_NAME'] ?? ''] as $host) {
        $host = strtolower((string)$host);
        if ($host === 'localhost' || str_starts_with($host, 'localhost:')
            || $host === '127.0.0.1' || str_starts_with($host, '127.0.0.1:')) {
            return true;
        }
    }

    return false;
}

function m24_csrf_ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function m24_csrf_get_token(): string
{
    m24_csrf_ensure_session();

    if (empty($_SESSION[ERP_M24_CSRF_SESSION_KEY]) || !is_string($_SESSION[ERP_M24_CSRF_SESSION_KEY])) {
        $_SESSION[ERP_M24_CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[ERP_M24_CSRF_SESSION_KEY];
}

function m24_csrf_validate(string $postedToken): bool
{
    m24_csrf_ensure_session();
    $postedToken = trim($postedToken);

    return $postedToken !== ''
        && isset($_SESSION[ERP_M24_CSRF_SESSION_KEY])
        && is_string($_SESSION[ERP_M24_CSRF_SESSION_KEY])
        && hash_equals($_SESSION[ERP_M24_CSRF_SESSION_KEY], $postedToken);
}

function erp_m24_execute($connection, string $sql, array $params = [])
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return false;
    }

    return $statement;
}

function m24_fetch_int_value($connection, string $sql, array $params = [], string $columnName = ''): ?int
{
    $statement = erp_m24_execute($connection, $sql, $params);

    if ($statement === false || @odbc_fetch_row($statement) !== true) {
        return null;
    }

    $value = $columnName !== '' ? @odbc_result($statement, $columnName) : @odbc_result($statement, 1);

    return ($value !== false && $value !== null && is_numeric($value)) ? (int)$value : null;
}

function m24_fetch_string_value($connection, string $sql, array $params = [], string $columnName = ''): ?string
{
    $statement = erp_m24_execute($connection, $sql, $params);

    if ($statement === false || @odbc_fetch_row($statement) !== true) {
        return null;
    }

    $value = $columnName !== '' ? @odbc_result($statement, $columnName) : @odbc_result($statement, 1);

    if ($value === false || $value === null) {
        return null;
    }

    $value = trim((string)$value);

    return $value === '' ? null : $value;
}

/**
 * @return list<array<string, string>>
 */
function m24_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = erp_m24_execute($connection, $sql, $params);

    if ($statement === false) {
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

function m24_safe_sql_summary($connection): string
{
    $message = preg_replace('/\s+/', ' ', trim((string)@odbc_errormsg($connection))) ?? '';

    return strlen($message) > 300 ? substr($message, 0, 300) . '...' : $message;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m24_set_stage(array &$diagnostic, string $stage): void
{
    $diagnostic['failure_stage'] = $stage;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m24_mark_success(array &$diagnostic, string $stage): void
{
    $diagnostic['last_successful_step'] = $stage;
    $diagnostic['failure_stage'] = $stage;
}

/**
 * @return array<string, mixed>
 */
function erp_m24_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M24_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === ERP_M24_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

function erp_m24_parse_positive_quantity(string $raw): ?string
{
    $raw = trim($raw);

    if ($raw === '' || !preg_match('/^\d+(\.\d{1,3})?$/', $raw)) {
        return null;
    }

    if ((float)$raw <= 0) {
        return null;
    }

    return $raw;
}

function erp_m24_fetch_quantity_on_hand($connection, int $partId, int $stockLocationId): ?float
{
    $value = m24_fetch_string_value(
        $connection,
        'SELECT COALESCE(SUM(
            CASE m.movement_type
                WHEN N\'SEED\' THEN m.quantity
                WHEN N\'RECEIPT\' THEN m.quantity
                WHEN N\'RETURN\' THEN m.quantity
                WHEN N\'ADJUSTMENT\' THEN m.quantity
                WHEN N\'ISSUE\' THEN -m.quantity
                WHEN N\'REVERSAL\' THEN -m.quantity
                ELSE CAST(0 AS DECIMAL(18, 3))
            END
        ), CAST(0 AS DECIMAL(18, 3))) AS quantity_on_hand
        FROM dbo.erp_stock_movements m
        WHERE m.part_id = ?
          AND m.stock_location_id = ?',
        [$partId, $stockLocationId],
        'quantity_on_hand'
    );

    return $value === null ? null : (float)$value;
}

function erp_m24_resolve_active_jobcard($connection, int $jobcardId): bool
{
    if ($jobcardId <= 0) {
        return false;
    }

    $count = m24_fetch_int_value(
        $connection,
        'SELECT COUNT(*) AS c FROM dbo.erp_jobcards WHERE jobcard_id = ? AND lifecycle_state = N\'ACTIVE\'',
        [$jobcardId],
        'c'
    );

    return $count !== null && $count > 0;
}

function erp_m24_resolve_active_part($connection, int $partId): bool
{
    if ($partId <= 0) {
        return false;
    }

    $count = m24_fetch_int_value(
        $connection,
        'SELECT COUNT(*) AS c FROM dbo.erp_parts WHERE part_id = ? AND is_active = 1',
        [$partId],
        'c'
    );

    return $count !== null && $count > 0;
}

function erp_m24_resolve_active_location($connection, int $stockLocationId): bool
{
    if ($stockLocationId <= 0) {
        return false;
    }

    $count = m24_fetch_int_value(
        $connection,
        'SELECT COUNT(*) AS c FROM dbo.erp_stock_locations WHERE stock_location_id = ? AND is_active = 1',
        [$stockLocationId],
        'c'
    );

    return $count !== null && $count > 0;
}

/**
 * @return array{service_operation_id: int}|null
 */
function erp_m24_resolve_service_operation($connection, int $jobcardId, int $serviceOperationId): ?array
{
    if ($serviceOperationId <= 0) {
        return null;
    }

    $rows = m24_fetch_rows(
        $connection,
        'SELECT TOP 1 service_operation_id, jobcard_id, service_status, is_active
         FROM dbo.erp_service_operations
         WHERE service_operation_id = ?',
        [$serviceOperationId]
    );

    if ($rows === []) {
        return null;
    }

    $row = $rows[0];
    $resolvedId = (int)($row['service_operation_id'] ?? 0);
    $resolvedJobcardId = (int)($row['jobcard_id'] ?? 0);
    $status = strtoupper(trim((string)($row['service_status'] ?? '')));
    $isActive = trim((string)($row['is_active'] ?? '1'));

    if ($resolvedId <= 0 || $resolvedJobcardId !== $jobcardId || $isActive !== '1') {
        return null;
    }

    if ($status === 'CANCELLED' || $status === 'DONE') {
        return null;
    }

    if (!in_array($status, ERP_M24_ALLOWED_SERVICE_STATUSES, true)) {
        return null;
    }

    return ['service_operation_id' => $resolvedId];
}

/**
 * @param array<string, mixed> $diagnostic
 * @return array<string, int|string>
 */
function erp_m24_register_part_usage(
    $connection,
    int $userId,
    int $jobcardId,
    ?int $serviceOperationId,
    int $partId,
    int $stockLocationId,
    string $quantity,
    array &$diagnostic
): array {
    $transactionStarted = false;

    try {
        erp_m24_set_stage($diagnostic, 'TRANSACTION_STARTED');

        if (!@odbc_autocommit($connection, false)) {
            throw new RuntimeException('Part usage could not be completed.');
        }

        $transactionStarted = true;
        $diagnostic['transaction_rolled_back'] = 'NO';
        erp_m24_mark_success($diagnostic, 'TRANSACTION_STARTED');

        erp_m24_set_stage($diagnostic, 'STOCK_CHECK_START');
        $onHand = erp_m24_fetch_quantity_on_hand($connection, $partId, $stockLocationId);

        if ($onHand === null) {
            throw new RuntimeException('Part usage could not be completed.');
        }

        if ($onHand < (float)$quantity) {
            throw new RuntimeException('Insufficient stock for this part at the selected location.');
        }

        erp_m24_mark_success($diagnostic, 'STOCK_CHECK_DONE');

        erp_m24_set_stage($diagnostic, 'USAGE_INSERT_START');
        $usageOk = erp_m24_execute(
            $connection,
            'INSERT INTO dbo.erp_jobcard_part_usage (
                jobcard_id,
                service_operation_id,
                part_id,
                stock_location_id,
                quantity,
                usage_status,
                created_by_user_id,
                is_active
            ) VALUES (?, ?, ?, ?, ?, N\'USED\', ?, 1)',
            [
                $jobcardId,
                $serviceOperationId,
                $partId,
                $stockLocationId,
                $quantity,
                $userId,
            ]
        );

        if ($usageOk === false) {
            throw new RuntimeException('Part usage could not be completed.');
        }

        erp_m24_mark_success($diagnostic, 'USAGE_INSERT_DONE');

        erp_m24_set_stage($diagnostic, 'USAGE_ID_FETCH_START');
        $partUsageId = m24_fetch_int_value(
            $connection,
            'SELECT TOP 1 part_usage_id
             FROM dbo.erp_jobcard_part_usage
             WHERE jobcard_id = ?
               AND part_id = ?
               AND stock_location_id = ?
               AND quantity = ?
               AND usage_status = N\'USED\'
               AND created_by_user_id = ?
             ORDER BY part_usage_id DESC',
            [$jobcardId, $partId, $stockLocationId, $quantity, $userId],
            'part_usage_id'
        );

        if ($partUsageId === null || $partUsageId <= 0) {
            throw new RuntimeException('Part usage could not be completed.');
        }

        erp_m24_mark_success($diagnostic, 'USAGE_ID_FETCH_DONE');

        erp_m24_set_stage($diagnostic, 'MOVEMENT_INSERT_START');
        $movementOk = erp_m24_execute(
            $connection,
            'INSERT INTO dbo.erp_stock_movements (
                part_id,
                stock_location_id,
                movement_type,
                quantity,
                reference_type,
                reference_id,
                movement_note,
                created_by_user_id
            ) VALUES (?, ?, N\'ISSUE\', ?, N\'JOBCARD_PART_USAGE\', ?, ?, ?)',
            [
                $partId,
                $stockLocationId,
                $quantity,
                $partUsageId,
                'JobCard part usage ISSUE via Mission 24 prototype.',
                $userId,
            ]
        );

        if ($movementOk === false) {
            throw new RuntimeException('Part usage could not be completed.');
        }

        erp_m24_mark_success($diagnostic, 'MOVEMENT_INSERT_DONE');

        erp_m24_set_stage($diagnostic, 'HISTORY_INSERT_START');
        $historyOk = erp_m24_execute(
            $connection,
            'INSERT INTO dbo.erp_jobcard_part_usage_history (
                part_usage_id,
                jobcard_id,
                service_operation_id,
                part_id,
                action_code,
                old_status,
                new_status,
                changed_by_user_id,
                change_note
            ) VALUES (?, ?, ?, ?, N\'JOBCARD_PART_USED\', NULL, N\'USED\', ?, ?)',
            [
                $partUsageId,
                $jobcardId,
                $serviceOperationId,
                $partId,
                $userId,
                'JobCard part usage registered via Mission 24 prototype.',
            ]
        );

        if ($historyOk === false) {
            throw new RuntimeException('Part usage could not be completed.');
        }

        erp_m24_mark_success($diagnostic, 'HISTORY_INSERT_DONE');

        erp_m24_set_stage($diagnostic, 'NEGATIVE_STOCK_CHECK_START');
        $onHandAfter = erp_m24_fetch_quantity_on_hand($connection, $partId, $stockLocationId);

        if ($onHandAfter === null || $onHandAfter < 0) {
            throw new RuntimeException('Part usage could not be completed.');
        }

        erp_m24_mark_success($diagnostic, 'NEGATIVE_STOCK_CHECK_DONE');

        if (!@odbc_commit($connection)) {
            throw new RuntimeException('Part usage could not be completed.');
        }

        @odbc_autocommit($connection, true);
        erp_m24_mark_success($diagnostic, 'TRANSACTION_COMMITTED');

        return [
            'part_usage_id' => $partUsageId,
            'jobcard_id' => $jobcardId,
            'part_id' => $partId,
            'quantity' => $quantity,
        ];
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            @odbc_rollback($connection);
            $diagnostic['transaction_rolled_back'] = 'YES';
        }

        @odbc_autocommit($connection, true);

        if ((string)($diagnostic['safe_error_message'] ?? '') === '') {
            $diagnostic['safe_error_message'] = $exception->getMessage();
        }

        throw $exception;
    }
}

try {
    erp_m24_require_first_existing(erp_m24_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m24_require_first_existing(erp_m24_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    echo '<!DOCTYPE html><html lang="en"><body><p>ERP JobCard part use page could not be loaded.</p></body></html>';
    exit(1);
}

$userId = ERP_M24_PLATFORM_OWNER_ID;
$guardUseLabel = 'FAIL';
$guardIssueLabel = 'FAIL';
$connectionStatus = 'FAIL';
$accessDenied = false;
$errorMessage = '';
$successMessage = '';
$overallOk = false;
$connection = false;
$showOwnerDiagnostic = false;
$csrfToken = '';
$jobcardOptions = [];
$serviceOptions = [];
$partOptions = [];
$locationOptions = [];

/** @var array<string, mixed> */
$createDiagnostic = [
    'failure_stage' => '',
    'safe_error_message' => '',
    'last_successful_step' => '',
    'transaction_rolled_back' => 'UNKNOWN',
];

$formInput = [
    'jobcard_id' => '1',
    'service_operation_id' => '',
    'part_id' => '1',
    'stock_location_id' => '',
    'quantity' => '1',
];

$resultData = [
    'part_usage_id' => '',
    'jobcard_id' => '',
    'part_id' => '',
    'quantity' => '',
];

try {
    erp_auth_context_start();
    m24_csrf_ensure_session();

    $connection = erp_auth_create_local_odbc_connection();
    $connectionStatus = 'OK';

    if (erp_auth_current_user_id() !== $userId || erp_auth_load_current_user($connection) === null) {
        throw new RuntimeException('Access denied.');
    }

    $guardUse = erp_m24_guard_eval($connection, $userId, ERP_M24_USE_ACTION);
    $guardIssue = erp_m24_guard_eval($connection, $userId, ERP_M24_ISSUE_ACTION);
    $guardUseLabel = (string)($guardUse['label'] ?? 'FAIL');
    $guardIssueLabel = (string)($guardIssue['label'] ?? 'FAIL');

    if (empty($guardUse['allowed']) || empty($guardIssue['allowed'])) {
        $accessDenied = true;
        throw new RuntimeException('Access denied.');
    }

    $jobcardOptions = m24_fetch_rows(
        $connection,
        'SELECT jobcard_id, jobcard_number, jobcard_status
         FROM dbo.erp_jobcards
         WHERE lifecycle_state = N\'ACTIVE\'
         ORDER BY jobcard_id ASC'
    );

    $partOptions = m24_fetch_rows(
        $connection,
        'SELECT part_id, part_code, part_name, unit_of_measure
         FROM dbo.erp_parts
         WHERE is_active = 1
         ORDER BY part_id ASC'
    );

    $locationOptions = m24_fetch_rows(
        $connection,
        'SELECT stock_location_id, location_code, location_name
         FROM dbo.erp_stock_locations
         WHERE is_active = 1
         ORDER BY location_code ASC'
    );

    foreach ($locationOptions as $locRow) {
        if (trim((string)($locRow['location_code'] ?? '')) === 'MAIN' && $formInput['stock_location_id'] === '') {
            $formInput['stock_location_id'] = trim((string)($locRow['stock_location_id'] ?? ''));
            break;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach (array_keys($formInput) as $key) {
            $formInput[$key] = erp_m24_post_string($key);
        }

        if (!m24_csrf_validate(trim((string)($_POST['csrf_token'] ?? '')))) {
            throw new RuntimeException('Invalid CSRF token.');
        }

        if ($formInput['jobcard_id'] === '' || !ctype_digit($formInput['jobcard_id'])) {
            throw new RuntimeException('JobCard ID is required.');
        }

        if ($formInput['part_id'] === '' || !ctype_digit($formInput['part_id'])) {
            throw new RuntimeException('Part ID is required.');
        }

        if ($formInput['stock_location_id'] === '' || !ctype_digit($formInput['stock_location_id'])) {
            throw new RuntimeException('Stock location ID is required.');
        }

        $quantity = erp_m24_parse_positive_quantity($formInput['quantity']);

        if ($quantity === null) {
            throw new RuntimeException('Quantity must be a positive number.');
        }

        $jobcardId = (int)$formInput['jobcard_id'];
        $partId = (int)$formInput['part_id'];
        $stockLocationId = (int)$formInput['stock_location_id'];

        if (!erp_m24_resolve_active_jobcard($connection, $jobcardId)) {
            throw new RuntimeException('Selected JobCard is not active or does not exist.');
        }

        if (!erp_m24_resolve_active_part($connection, $partId)) {
            throw new RuntimeException('Selected part is not active or does not exist.');
        }

        if (!erp_m24_resolve_active_location($connection, $stockLocationId)) {
            throw new RuntimeException('Selected stock location is not active or does not exist.');
        }

        $serviceOperationId = null;

        if ($formInput['service_operation_id'] !== '') {
            if (!ctype_digit($formInput['service_operation_id'])) {
                throw new RuntimeException('Service Operation ID must be an integer when provided.');
            }

            $resolvedService = erp_m24_resolve_service_operation(
                $connection,
                $jobcardId,
                (int)$formInput['service_operation_id']
            );

            if ($resolvedService === null) {
                throw new RuntimeException('Service Operation is invalid, inactive, or does not belong to this JobCard.');
            }

            $serviceOperationId = $resolvedService['service_operation_id'];
        }

        $created = erp_m24_register_part_usage(
            $connection,
            $userId,
            $jobcardId,
            $serviceOperationId,
            $partId,
            $stockLocationId,
            $quantity,
            $createDiagnostic
        );

        $resultData = [
            'part_usage_id' => (string)$created['part_usage_id'],
            'jobcard_id' => (string)$created['jobcard_id'],
            'part_id' => (string)$created['part_id'],
            'quantity' => (string)$created['quantity'],
        ];

        $successMessage = 'JobCard Part Usage Created OK';
        $overallOk = true;

        unset($_SESSION[ERP_M24_CSRF_SESSION_KEY]);
    }

    $csrfToken = m24_csrf_get_token();

    $selectedJobcardId = (int)$formInput['jobcard_id'];

    if ($selectedJobcardId > 0) {
        $serviceOptions = m24_fetch_rows(
            $connection,
            'SELECT service_operation_id, service_title, service_status
             FROM dbo.erp_service_operations
             WHERE jobcard_id = ?
               AND is_active = 1
               AND service_status IN (N\'ASSIGNED\', N\'IN_PROGRESS\', N\'WAITING_PARTS\')
             ORDER BY service_operation_id ASC',
            [$selectedJobcardId]
        );
    }
} catch (Throwable $exception) {
    if ($accessDenied) {
        $errorMessage = 'Access denied. You do not have permission to perform this action.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $errorMessage = (string)($createDiagnostic['safe_error_message'] ?? '');

        if ($errorMessage === '') {
            $errorMessage = $exception->getMessage() !== '' ? $exception->getMessage() : 'Part usage could not be completed.';
        }

        if ($connection !== false && (string)($createDiagnostic['safe_error_message'] ?? '') === '') {
            $createDiagnostic['safe_error_message'] = m24_safe_sql_summary($connection);
        }
    } elseif ($errorMessage === '') {
        $errorMessage = 'JobCard part use page could not be loaded.';
    }
} finally {
    if ($csrfToken === '' && !$accessDenied && $connectionStatus === 'OK') {
        $csrfToken = m24_csrf_get_token();
    }

    if ($userId === ERP_M24_PLATFORM_OWNER_ID && $_SERVER['REQUEST_METHOD'] === 'POST' && !$overallOk && erp_m24_is_localhost_request()) {
        $showOwnerDiagnostic = true;
    }

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
    <title>Mission 24 - JobCard Part Use</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 24px; }
        .banner { background: #7f1d1d; color: #fff; padding: 12px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; width: 240px; }
        label { display: block; font-weight: bold; margin-bottom: 4px; }
        input, select { width: 100%; box-sizing: border-box; padding: 8px; margin-bottom: 12px; }
        button { background: #1d4ed8; color: #fff; border: 0; padding: 10px 16px; border-radius: 6px; cursor: pointer; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
        .diag { background: #fff7ed; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL CONTROLLED JOBCARD PART USAGE - ISSUE MOVEMENT ONLY</div>

    <div class="card">
        <h1>JobCard Part Use</h1>
        <p>Mission 24 — usage + ISSUE movement (no finance)</p>
        <?php if ($errorMessage !== ''): ?><p class="fail"><?= erp_m24_h($errorMessage) ?></p><?php endif; ?>
        <?php if ($successMessage !== ''): ?><p class="ok"><?= erp_m24_h($successMessage) ?></p><?php endif; ?>
    </div>

    <?php if ($showOwnerDiagnostic): ?>
        <div class="card diag">
            <h2>Local Owner Diagnostic</h2>
            <table>
                <tr><th>failure_stage</th><td><?= erp_m24_h((string)($createDiagnostic['failure_stage'] ?? '')) ?></td></tr>
                <tr><th>safe_error_message</th><td><?= erp_m24_h((string)($createDiagnostic['safe_error_message'] ?? '')) ?></td></tr>
                <tr><th>last_successful_step</th><td><?= erp_m24_h((string)($createDiagnostic['last_successful_step'] ?? '')) ?></td></tr>
                <tr><th>transaction_rolled_back</th><td><?= erp_m24_h((string)($createDiagnostic['transaction_rolled_back'] ?? '')) ?></td></tr>
            </table>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Auth Summary</h2>
        <table>
            <tr><th>Connection</th><td><?= erp_m24_h($connectionStatus) ?></td></tr>
            <tr><th>guard jobcard.part.use</th><td><?= erp_m24_h($guardUseLabel) ?></td></tr>
            <tr><th>guard stock.issue.create</th><td><?= erp_m24_h($guardIssueLabel) ?></td></tr>
        </table>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="card">
            <h2>Result</h2>
            <table>
                <tr><th>part_usage_id</th><td><?= erp_m24_h($resultData['part_usage_id']) ?></td></tr>
                <tr><th>jobcard_id</th><td><?= erp_m24_h($resultData['jobcard_id']) ?></td></tr>
                <tr><th>part_id</th><td><?= erp_m24_h($resultData['part_id']) ?></td></tr>
                <tr><th>quantity</th><td><?= erp_m24_h($resultData['quantity']) ?></td></tr>
                <tr><th>History</th><td class="ok">JOBCARD_PART_USED</td></tr>
                <tr><th>Movement</th><td class="ok">ISSUE / JOBCARD_PART_USAGE</td></tr>
            </table>
            <p><a href="erp-jobcard-part-readonly-list.php">Open part usage list</a></p>
        </div>
    <?php endif; ?>

    <?php if (!$accessDenied && $connectionStatus === 'OK'): ?>
        <div class="card">
            <h2>Register Part Usage</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= erp_m24_h($csrfToken) ?>">

                <label for="jobcard_id">jobcard_id *</label>
                <select id="jobcard_id" name="jobcard_id" required>
                    <option value="">Select JobCard</option>
                    <?php foreach ($jobcardOptions as $row): ?>
                        <?php $val = trim((string)($row['jobcard_id'] ?? '')); if ($val === '') continue; ?>
                        <option value="<?= erp_m24_h($val) ?>" <?= $formInput['jobcard_id'] === $val ? 'selected' : '' ?>>
                            <?= erp_m24_h($val . ' — ' . ($row['jobcard_number'] ?? '') . ' [' . ($row['jobcard_status'] ?? '') . ']') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="service_operation_id">service_operation_id (optional)</label>
                <select id="service_operation_id" name="service_operation_id">
                    <option value="">None</option>
                    <?php foreach ($serviceOptions as $row): ?>
                        <?php $val = trim((string)($row['service_operation_id'] ?? '')); if ($val === '') continue; ?>
                        <option value="<?= erp_m24_h($val) ?>" <?= $formInput['service_operation_id'] === $val ? 'selected' : '' ?>>
                            <?= erp_m24_h($val . ' — ' . ($row['service_title'] ?? '') . ' [' . ($row['service_status'] ?? '') . ']') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="part_id">part_id *</label>
                <select id="part_id" name="part_id" required>
                    <option value="">Select part</option>
                    <?php foreach ($partOptions as $row): ?>
                        <?php $val = trim((string)($row['part_id'] ?? '')); if ($val === '') continue; ?>
                        <option value="<?= erp_m24_h($val) ?>" <?= $formInput['part_id'] === $val ? 'selected' : '' ?>>
                            <?= erp_m24_h($val . ' — ' . ($row['part_code'] ?? '') . ' — ' . ($row['part_name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="stock_location_id">stock_location_id *</label>
                <select id="stock_location_id" name="stock_location_id" required>
                    <option value="">Select location</option>
                    <?php foreach ($locationOptions as $row): ?>
                        <?php $val = trim((string)($row['stock_location_id'] ?? '')); if ($val === '') continue; ?>
                        <option value="<?= erp_m24_h($val) ?>" <?= $formInput['stock_location_id'] === $val ? 'selected' : '' ?>>
                            <?= erp_m24_h(($row['location_code'] ?? '') . ' — ' . ($row['location_name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="quantity">quantity *</label>
                <input type="text" id="quantity" name="quantity" required value="<?= erp_m24_h($formInput['quantity']) ?>">

                <button type="submit">Register Part Usage</button>
            </form>
            <p>Requires sufficient stock (run Mission 24 SQL for MISSION_24_TEST_SEED if needed).</p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
