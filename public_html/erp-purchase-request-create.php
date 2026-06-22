<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Purchase Request Controlled Create Prototype
 *
 * Mission 26 - POST create only with Auth, Permission Guard, CSRF, and transaction.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M26_PLATFORM_OWNER_ID = 10001;
const ERP_M26_CSRF_SESSION_KEY = 'm26_purchase_request_create_csrf';
const ERP_M26_CREATE_ACTION = 'purchase.request.create';

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
const ERP_M26_ALLOWED_INITIAL_STATUSES = ['DRAFT', 'SUBMITTED'];

function erp_m26_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m26_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function erp_m26_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m26_post_string(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

function m26_csrf_ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function m26_csrf_get_token(): string
{
    m26_csrf_ensure_session();

    if (empty($_SESSION[ERP_M26_CSRF_SESSION_KEY]) || !is_string($_SESSION[ERP_M26_CSRF_SESSION_KEY])) {
        $_SESSION[ERP_M26_CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[ERP_M26_CSRF_SESSION_KEY];
}

function m26_csrf_validate(string $postedToken): bool
{
    m26_csrf_ensure_session();
    $postedToken = trim($postedToken);

    return $postedToken !== ''
        && isset($_SESSION[ERP_M26_CSRF_SESSION_KEY])
        && is_string($_SESSION[ERP_M26_CSRF_SESSION_KEY])
        && hash_equals($_SESSION[ERP_M26_CSRF_SESSION_KEY], $postedToken);
}

function erp_m26_execute($connection, string $sql, array $params = [])
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return false;
    }

    return $statement;
}

function m26_fetch_int_value($connection, string $sql, array $params = [], string $columnName = ''): ?int
{
    $statement = erp_m26_execute($connection, $sql, $params);

    if ($statement === false || @odbc_fetch_row($statement) !== true) {
        return null;
    }

    $value = $columnName !== '' ? @odbc_result($statement, $columnName) : @odbc_result($statement, 1);

    return ($value !== false && $value !== null && is_numeric($value)) ? (int)$value : null;
}

/**
 * @return list<array<string, string>>
 */
function m26_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = erp_m26_execute($connection, $sql, $params);

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

function m26_safe_sql_error_summary($connection): string
{
    $message = (string)@odbc_errormsg($connection);

    if ($message === '') {
        return 'No ODBC error message available.';
    }

    $message = preg_replace('/Driver=\{[^}]+\};[^\]]+/i', '[connection-redacted]', $message) ?? $message;
    $message = preg_replace('/\s+/', ' ', trim($message)) ?? trim($message);

    return strlen($message) > 300 ? substr($message, 0, 300) . '...' : $message;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m26_set_stage(array &$diagnostic, string $stage): void
{
    $diagnostic['failure_stage'] = $stage;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m26_mark_success(array &$diagnostic, string $stage): void
{
    $diagnostic['last_successful_step'] = $stage;
    $diagnostic['failure_stage'] = $stage;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m26_capture_sql_error(array &$diagnostic, $connection): void
{
    $diagnostic['safe_error_message'] = m26_safe_sql_error_summary($connection);
}

/**
 * @return array<string, mixed>
 */
function erp_m26_guard_eval($connection, int $userId, string $actionKey): array
{
    $map = erp_guard_action_map();

    if (isset($map[$actionKey])) {
        $result = erp_guard_action($connection, $userId, $actionKey);
        $label = !empty($result['allowed']) ? 'OK' : 'FAIL';

        if (!empty($result['placeholder'])) {
            $label = 'PLACEHOLDER';
        }

        $result['label'] = $label;

        return $result;
    }

    if (!isset(ERP_M26_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === ERP_M26_PLATFORM_OWNER_ID) {
        return [
            'allowed' => true,
            'label' => 'PLACEHOLDER_OWNER_ALLOWED',
            'placeholder' => true,
        ];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

/**
 * @return list<array<string, string>>
 */
function erp_m26_fetch_active_jobcards($connection): array
{
    return m26_fetch_rows(
        $connection,
        'SELECT j.jobcard_id, j.jobcard_number, j.jobcard_status, j.lifecycle_state
         FROM dbo.erp_jobcards j
         WHERE j.lifecycle_state = \'ACTIVE\'
         ORDER BY j.jobcard_id ASC'
    );
}

function erp_m26_resolve_active_jobcard($connection, int $jobcardId): ?int
{
    if ($jobcardId <= 0) {
        return null;
    }

    $count = m26_fetch_int_value(
        $connection,
        'SELECT COUNT(*) AS jobcard_count
         FROM dbo.erp_jobcards
         WHERE jobcard_id = ?
           AND lifecycle_state = \'ACTIVE\'',
        [$jobcardId],
        'jobcard_count'
    );

    return ($count !== null && $count > 0) ? $jobcardId : null;
}

function erp_m26_resolve_service_operation($connection, int $serviceOperationId, int $jobcardId): ?int
{
    if ($serviceOperationId <= 0) {
        return null;
    }

    $count = m26_fetch_int_value(
        $connection,
        'SELECT COUNT(*) AS operation_count
         FROM dbo.erp_service_operations
         WHERE service_operation_id = ?
           AND jobcard_id = ?
           AND is_active = 1',
        [$serviceOperationId, $jobcardId],
        'operation_count'
    );

    return ($count !== null && $count > 0) ? $serviceOperationId : null;
}

function erp_m26_resolve_part($connection, int $partId): ?int
{
    if ($partId <= 0) {
        return null;
    }

    $count = m26_fetch_int_value(
        $connection,
        'SELECT COUNT(*) AS part_count FROM dbo.erp_parts WHERE part_id = ? AND is_active = 1',
        [$partId],
        'part_count'
    );

    return ($count !== null && $count > 0) ? $partId : null;
}

/**
 * @return array<string, string>
 */
function erp_m26_validate_form(array $input): array
{
    $errors = [];

    if ($input['jobcard_id'] === '' || !ctype_digit($input['jobcard_id'])) {
        $errors[] = 'JobCard ID is required.';
    }

    if ($input['requested_part_name'] === '') {
        $errors[] = 'Requested part name is required.';
    }

    if ($input['requested_quantity'] === '' || !is_numeric($input['requested_quantity'])) {
        $errors[] = 'Requested quantity must be a positive number.';
    } elseif ((float)$input['requested_quantity'] <= 0) {
        $errors[] = 'Requested quantity must be greater than zero.';
    }

    if (!in_array($input['request_status'], ERP_M26_ALLOWED_INITIAL_STATUSES, true)) {
        $errors[] = 'Initial request status must be DRAFT or SUBMITTED.';
    }

    if ($input['service_operation_id'] !== '' && !ctype_digit($input['service_operation_id'])) {
        $errors[] = 'Service Operation ID must be a positive integer when provided.';
    }

    if ($input['part_id'] !== '' && !ctype_digit($input['part_id'])) {
        $errors[] = 'Part ID must be a positive integer when provided.';
    }

    if ($input['estimated_unit_cost'] !== '' && !is_numeric($input['estimated_unit_cost'])) {
        $errors[] = 'Estimated unit cost must be numeric when provided.';
    }

    return $errors;
}

/**
 * @param array<string, mixed> $diagnostic
 * @return array<string, int|string>
 */
function erp_m26_create_purchase_request(
    $connection,
    int $userId,
    int $jobcardId,
    ?int $serviceOperationId,
    ?int $partId,
    array $input,
    array &$diagnostic
): array {
    $transactionStarted = false;

    try {
        erp_m26_set_stage($diagnostic, 'TRANSACTION_STARTED');

        if (!@odbc_autocommit($connection, false)) {
            erp_m26_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Purchase Request create could not be completed.');
        }

        $transactionStarted = true;
        $diagnostic['transaction_rolled_back'] = 'NO';
        erp_m26_mark_success($diagnostic, 'TRANSACTION_STARTED');

        $requestStatus = $input['request_status'];
        $requestedQuantity = (float)$input['requested_quantity'];
        $requestReason = $input['request_reason'] !== '' ? $input['request_reason'] : null;
        $estimatedUnitCost = $input['estimated_unit_cost'] !== '' ? (float)$input['estimated_unit_cost'] : null;
        $currencyCode = $input['currency_code'] !== '' ? $input['currency_code'] : null;

        erp_m26_set_stage($diagnostic, 'PURCHASE_REQUEST_INSERT_START');
        $insertStatement = erp_m26_execute(
            $connection,
            'INSERT INTO dbo.erp_purchase_requests (
                jobcard_id,
                service_operation_id,
                part_id,
                requested_part_name,
                requested_quantity,
                request_reason,
                request_status,
                requested_by_user_id,
                supplier_id,
                estimated_unit_cost,
                currency_code,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, 1)',
            [
                $jobcardId,
                $serviceOperationId,
                $partId,
                $input['requested_part_name'],
                $requestedQuantity,
                $requestReason,
                $requestStatus,
                $userId,
                $estimatedUnitCost,
                $currencyCode,
            ]
        );

        if ($insertStatement === false) {
            erp_m26_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Purchase Request create could not be completed.');
        }

        erp_m26_mark_success($diagnostic, 'PURCHASE_REQUEST_INSERT_DONE');

        erp_m26_set_stage($diagnostic, 'PURCHASE_REQUEST_ID_FETCH_START');
        $purchaseRequestId = m26_fetch_int_value(
            $connection,
            'SELECT TOP 1 purchase_request_id
             FROM dbo.erp_purchase_requests
             WHERE jobcard_id = ?
               AND requested_part_name = ?
               AND requested_quantity = ?
               AND request_status = ?
               AND requested_by_user_id = ?
             ORDER BY purchase_request_id DESC',
            [
                $jobcardId,
                $input['requested_part_name'],
                $requestedQuantity,
                $requestStatus,
                $userId,
            ],
            'purchase_request_id'
        );

        if ($purchaseRequestId === null || $purchaseRequestId <= 0) {
            erp_m26_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Purchase Request create could not be completed.');
        }

        erp_m26_mark_success($diagnostic, 'PURCHASE_REQUEST_ID_FETCH_DONE');

        erp_m26_set_stage($diagnostic, 'HISTORY_INSERT_START');
        $historyStatement = erp_m26_execute(
            $connection,
            'INSERT INTO dbo.erp_purchase_request_history (
                purchase_request_id,
                jobcard_id,
                service_operation_id,
                action_code,
                old_status,
                new_status,
                changed_by_user_id,
                change_note
            ) VALUES (?, ?, ?, ?, NULL, ?, ?, ?)',
            [
                $purchaseRequestId,
                $jobcardId,
                $serviceOperationId,
                'PURCHASE_REQUEST_CREATED',
                $requestStatus,
                $userId,
                'Purchase Request record created via Mission 26 prototype.',
            ]
        );

        if ($historyStatement === false) {
            erp_m26_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Purchase Request create could not be completed.');
        }

        erp_m26_mark_success($diagnostic, 'HISTORY_INSERT_DONE');

        erp_m26_set_stage($diagnostic, 'TRANSACTION_COMMIT_START');
        if (!@odbc_commit($connection)) {
            erp_m26_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Purchase Request create could not be completed.');
        }

        @odbc_autocommit($connection, true);
        erp_m26_mark_success($diagnostic, 'TRANSACTION_COMMITTED');

        return [
            'purchase_request_id' => $purchaseRequestId,
            'jobcard_id' => $jobcardId,
            'requested_part_name' => $input['requested_part_name'],
            'request_status' => $requestStatus,
        ];
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            @odbc_rollback($connection);
            $diagnostic['transaction_rolled_back'] = 'YES';
        }

        @odbc_autocommit($connection, true);
        erp_m26_capture_sql_error($diagnostic, $connection);

        throw $exception;
    }
}

try {
    erp_m26_require_first_existing(erp_m26_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m26_require_first_existing(erp_m26_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Create Error</title></head><body>';
    echo '<p>ERP Purchase Request create page could not be loaded.</p></body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');
$userId = ERP_M26_PLATFORM_OWNER_ID;
$username = '—';
$rolesText = '—';
$permissionCount = 0;
$guardCreateLabel = 'FAIL';
$connectionStatus = 'FAIL';
$connectionDetail = 'Not connected';
$errorMessage = '';
$successMessage = '';
$overallOk = false;
$connection = false;
$csrfToken = '';
$activeJobcards = [];

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
    'part_id' => '',
    'requested_part_name' => '',
    'requested_quantity' => '',
    'request_reason' => '',
    'request_status' => 'DRAFT',
    'estimated_unit_cost' => '',
    'currency_code' => '',
];

$resultData = [
    'purchase_request_id' => '',
    'jobcard_id' => '',
    'requested_part_name' => '',
    'request_status' => '',
];

try {
    erp_auth_context_start();
    m26_csrf_ensure_session();

    $connection = erp_auth_create_local_odbc_connection();
    $connectionStatus = 'OK';
    $connectionDetail = 'ODBC Trusted Connection connected';

    if (erp_auth_current_user_id() !== $userId) {
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
    $permissionCount = count(erp_auth_context_permission_keys($permissionsResult));

    $guardCreate = erp_m26_guard_eval($connection, $userId, ERP_M26_CREATE_ACTION);
    $guardCreateLabel = (string)($guardCreate['label'] ?? 'FAIL');

    if (empty($guardCreate['allowed'])) {
        throw new RuntimeException('Access denied.');
    }

    $activeJobcards = erp_m26_fetch_active_jobcards($connection);
    $csrfToken = m26_csrf_get_token();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach (array_keys($formInput) as $fieldKey) {
            $formInput[$fieldKey] = erp_m26_post_string($fieldKey);
        }

        if ($formInput['request_status'] === '') {
            $formInput['request_status'] = 'DRAFT';
        }

        if (!m26_csrf_validate(trim((string)($_POST['csrf_token'] ?? '')))) {
            throw new RuntimeException('Invalid CSRF token.');
        }

        $validationErrors = erp_m26_validate_form($formInput);

        if ($validationErrors !== []) {
            throw new RuntimeException($validationErrors[0]);
        }

        $jobcardId = (int)$formInput['jobcard_id'];
        $resolvedJobcardId = erp_m26_resolve_active_jobcard($connection, $jobcardId);

        if ($resolvedJobcardId === null) {
            throw new RuntimeException('Selected JobCard is not active or does not exist.');
        }

        $serviceOperationId = null;
        if ($formInput['service_operation_id'] !== '') {
            $serviceOperationId = erp_m26_resolve_service_operation(
                $connection,
                (int)$formInput['service_operation_id'],
                $resolvedJobcardId
            );

            if ($serviceOperationId === null) {
                throw new RuntimeException('Service Operation does not belong to the selected JobCard.');
            }
        }

        $partId = null;
        if ($formInput['part_id'] !== '') {
            $partId = erp_m26_resolve_part($connection, (int)$formInput['part_id']);

            if ($partId === null) {
                throw new RuntimeException('Part ID does not exist in erp_parts.');
            }
        }

        $created = erp_m26_create_purchase_request(
            $connection,
            $userId,
            $resolvedJobcardId,
            $serviceOperationId,
            $partId,
            $formInput,
            $createDiagnostic
        );

        $resultData = [
            'purchase_request_id' => (string)$created['purchase_request_id'],
            'jobcard_id' => (string)$created['jobcard_id'],
            'requested_part_name' => (string)$created['requested_part_name'],
            'request_status' => (string)$created['request_status'],
        ];

        $successMessage = 'Purchase Request Created OK';
    }

    $overallOk = $connectionStatus === 'OK'
        && in_array($guardCreateLabel, ['OK', 'PLACEHOLDER', 'PLACEHOLDER_OWNER_ALLOWED'], true);
} catch (Throwable $exception) {
    $errorMessage = trim($exception->getMessage()) !== '' ? $exception->getMessage() : 'Purchase Request create could not be completed.';
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
    <title>Mission 26 - Purchase Request Create</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; line-height: 1.5; }
        .wrap { max-width: 900px; margin: 0 auto; padding: 24px; }
        .banner { background: #1e3a5f; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        h1, h2 { margin: 0 0 12px; }
        label { display: block; margin-bottom: 4px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; margin-bottom: 12px; box-sizing: border-box; }
        button { background: #1e3a5f; color: #fff; border: 0; padding: 10px 16px; border-radius: 6px; cursor: pointer; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
        th { background: #f3f4f6; width: 240px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL PURCHASE REQUEST CREATE - MISSION 26 PROTOTYPE</div>

    <div class="card">
        <h1>Purchase Request Create</h1>
        <p>Mission 26 - Auth + CSRF + Permission Guard + Transaction</p>
        <p>
            <a href="erp-purchase-request-readonly-list.php">Open read-only list</a>
            |
            <a href="erp-purchase-request-detail.php">Open detail</a>
        </p>
        <?php if ($successMessage !== ''): ?>
            <p class="ok"><?= erp_m26_h($successMessage) ?></p>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <p class="fail"><?= erp_m26_h($errorMessage) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Summary</h2>
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m26_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection</th><td><?= erp_m26_h($connectionStatus) ?> — <?= erp_m26_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m26_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m26_h($username) ?></td></tr>
                <tr><th>guard purchase.request.create</th><td><?= erp_m26_h($guardCreateLabel) ?></td></tr>
                <tr><th>Overall Status</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
            </tbody>
        </table>
    </div>

    <?php if ($resultData['purchase_request_id'] !== ''): ?>
        <div class="card">
            <h2>Created Purchase Request</h2>
            <table>
                <tbody>
                    <tr><th>Purchase Request ID</th><td><?= erp_m26_h($resultData['purchase_request_id']) ?></td></tr>
                    <tr><th>JobCard ID</th><td><?= erp_m26_h($resultData['jobcard_id']) ?></td></tr>
                    <tr><th>Requested Part Name</th><td><?= erp_m26_h($resultData['requested_part_name']) ?></td></tr>
                    <tr><th>Request Status</th><td><?= erp_m26_h($resultData['request_status']) ?></td></tr>
                </tbody>
            </table>
            <p><a href="erp-purchase-request-detail.php?purchase_request_id=<?= erp_m26_h($resultData['purchase_request_id']) ?>">Open detail</a></p>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Create Form</h2>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= erp_m26_h($csrfToken) ?>">

            <label for="jobcard_id">JobCard ID (required)</label>
            <select id="jobcard_id" name="jobcard_id" required>
                <?php if ($activeJobcards === []): ?>
                    <option value="<?= erp_m26_h($formInput['jobcard_id']) ?>"><?= erp_m26_h($formInput['jobcard_id']) ?></option>
                <?php else: ?>
                    <?php foreach ($activeJobcards as $row): ?>
                        <?php $jcId = (string)($row['jobcard_id'] ?? ''); ?>
                        <option value="<?= erp_m26_h($jcId) ?>"<?= $formInput['jobcard_id'] === $jcId ? ' selected' : '' ?>>
                            <?= erp_m26_h($jcId . ' — ' . ($row['jobcard_number'] ?? '') . ' [' . ($row['jobcard_status'] ?? '') . ']') ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <label for="service_operation_id">Service Operation ID (optional)</label>
            <input type="text" id="service_operation_id" name="service_operation_id" value="<?= erp_m26_h($formInput['service_operation_id']) ?>">

            <label for="part_id">Part ID (optional)</label>
            <input type="text" id="part_id" name="part_id" value="<?= erp_m26_h($formInput['part_id']) ?>">

            <label for="requested_part_name">Requested Part Name (required)</label>
            <input type="text" id="requested_part_name" name="requested_part_name" required value="<?= erp_m26_h($formInput['requested_part_name']) ?>">

            <label for="requested_quantity">Requested Quantity (required)</label>
            <input type="text" id="requested_quantity" name="requested_quantity" required value="<?= erp_m26_h($formInput['requested_quantity']) ?>">

            <label for="request_reason">Request Reason (optional)</label>
            <textarea id="request_reason" name="request_reason" rows="3"><?= erp_m26_h($formInput['request_reason']) ?></textarea>

            <label for="request_status">Initial Request Status</label>
            <select id="request_status" name="request_status">
                <option value="DRAFT"<?= $formInput['request_status'] === 'DRAFT' ? ' selected' : '' ?>>DRAFT</option>
                <option value="SUBMITTED"<?= $formInput['request_status'] === 'SUBMITTED' ? ' selected' : '' ?>>SUBMITTED</option>
            </select>

            <label for="estimated_unit_cost">Estimated Unit Cost (informational, optional)</label>
            <input type="text" id="estimated_unit_cost" name="estimated_unit_cost" value="<?= erp_m26_h($formInput['estimated_unit_cost']) ?>">

            <label for="currency_code">Currency Code (optional)</label>
            <input type="text" id="currency_code" name="currency_code" value="<?= erp_m26_h($formInput['currency_code']) ?>" placeholder="IRR">

            <button type="submit">Create Purchase Request</button>
        </form>
    </div>
</div>
</body>
</html>
