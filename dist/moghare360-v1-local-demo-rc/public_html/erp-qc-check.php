<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP QC Check Controlled Prototype
 *
 * Mission 30 - POST create QC + delivery control sync with Auth, CSRF, transaction.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M30_PLATFORM_OWNER_ID = 10001;
const ERP_M30_CSRF_SESSION_KEY = 'm30_qc_check_csrf';
const ERP_M30_CREATE_ACTION = 'qc.check.create';

/** @var array<string, string> */
const ERP_M30_PLACEHOLDER_ACTIONS = [
    'qc.check.create' => 'placeholder_qc_check_create',
    'qc.check.view' => 'placeholder_qc_check_view',
    'qc.check.list' => 'placeholder_qc_check_list',
    'qc.check.pass' => 'placeholder_qc_check_pass',
    'qc.check.fail' => 'placeholder_qc_check_fail',
    'delivery.control.view' => 'placeholder_delivery_control_view',
    'delivery.control.release' => 'placeholder_delivery_control_release',
    'soft.run.readiness.view' => 'placeholder_soft_run_readiness_view',
];

/** @var list<string> */
const ERP_M30_ALLOWED_QC_STATUSES = ['PASSED', 'FAILED'];

function erp_m30_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m30_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function erp_m30_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m30_post_string(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

function m30_csrf_ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function m30_csrf_get_token(): string
{
    m30_csrf_ensure_session();

    if (empty($_SESSION[ERP_M30_CSRF_SESSION_KEY]) || !is_string($_SESSION[ERP_M30_CSRF_SESSION_KEY])) {
        $_SESSION[ERP_M30_CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[ERP_M30_CSRF_SESSION_KEY];
}

function m30_csrf_validate(string $postedToken): bool
{
    m30_csrf_ensure_session();
    $postedToken = trim($postedToken);

    return $postedToken !== ''
        && isset($_SESSION[ERP_M30_CSRF_SESSION_KEY])
        && is_string($_SESSION[ERP_M30_CSRF_SESSION_KEY])
        && hash_equals($_SESSION[ERP_M30_CSRF_SESSION_KEY], $postedToken);
}

function erp_m30_execute($connection, string $sql, array $params = [])
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return false;
    }

    return $statement;
}

function m30_fetch_int_value($connection, string $sql, array $params = [], string $columnName = ''): ?int
{
    $statement = erp_m30_execute($connection, $sql, $params);

    if ($statement === false || @odbc_fetch_row($statement) !== true) {
        return null;
    }

    $value = $columnName !== '' ? @odbc_result($statement, $columnName) : @odbc_result($statement, 1);

    return ($value !== false && $value !== null && is_numeric($value)) ? (int)$value : null;
}

function m30_fetch_string_value($connection, string $sql, array $params = [], string $columnName = ''): ?string
{
    $statement = erp_m30_execute($connection, $sql, $params);

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
function m30_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = erp_m30_execute($connection, $sql, $params);

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

function m30_safe_sql_error_summary($connection): string
{
    $message = (string)@odbc_errormsg($connection);

    return $message === '' ? 'No ODBC error message available.' : (strlen($message) > 300 ? substr($message, 0, 300) . '...' : $message);
}

/**
 * @return array<string, mixed>
 */
function erp_m30_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M30_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === ERP_M30_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

/**
 * @return list<array<string, string>>
 */
function erp_m30_fetch_active_jobcards($connection): array
{
    return m30_fetch_rows(
        $connection,
        'SELECT jobcard_id, jobcard_number, jobcard_status
         FROM dbo.erp_jobcards
         WHERE lifecycle_state = \'ACTIVE\'
         ORDER BY jobcard_id ASC'
    );
}

function erp_m30_resolve_active_jobcard($connection, int $jobcardId): ?int
{
    if ($jobcardId <= 0) {
        return null;
    }

    $count = m30_fetch_int_value(
        $connection,
        'SELECT COUNT(*) AS jobcard_count FROM dbo.erp_jobcards WHERE jobcard_id = ? AND lifecycle_state = \'ACTIVE\'',
        [$jobcardId],
        'jobcard_count'
    );

    return ($count !== null && $count > 0) ? $jobcardId : null;
}

function erp_m30_resolve_service_operation($connection, int $serviceOperationId, int $jobcardId): ?int
{
    if ($serviceOperationId <= 0) {
        return null;
    }

    $count = m30_fetch_int_value(
        $connection,
        'SELECT COUNT(*) AS operation_count FROM dbo.erp_service_operations
         WHERE service_operation_id = ? AND jobcard_id = ? AND is_active = 1',
        [$serviceOperationId, $jobcardId],
        'operation_count'
    );

    return ($count !== null && $count > 0) ? $serviceOperationId : null;
}

/**
 * @return array<string, string>
 */
function erp_m30_validate_form(array $input): array
{
    $errors = [];

    if ($input['jobcard_id'] === '' || !ctype_digit($input['jobcard_id'])) {
        $errors[] = 'JobCard ID is required.';
    }

    if (!in_array($input['qc_status'], ERP_M30_ALLOWED_QC_STATUSES, true)) {
        $errors[] = 'QC status must be PASSED or FAILED.';
    }

    if ($input['service_operation_id'] !== '' && !ctype_digit($input['service_operation_id'])) {
        $errors[] = 'Service Operation ID must be a positive integer when provided.';
    }

    return $errors;
}

/**
 * @param array<string, mixed> $diagnostic
 * @return array<string, int|string>
 */
function erp_m30_create_qc_and_sync_delivery(
    $connection,
    int $userId,
    int $jobcardId,
    ?int $serviceOperationId,
    string $qcStatus,
    ?string $qcNote,
    array &$diagnostic
): array {
    $transactionStarted = false;

    try {
        if (!@odbc_autocommit($connection, false)) {
            $diagnostic['safe_error_message'] = m30_safe_sql_error_summary($connection);
            throw new RuntimeException('QC check could not be completed.');
        }

        $transactionStarted = true;

        if (erp_m30_execute(
            $connection,
            'INSERT INTO dbo.erp_qc_checks (
                jobcard_id, service_operation_id, qc_status, checked_by_user_id, qc_note, is_active
            ) VALUES (?, ?, ?, ?, ?, 1)',
            [$jobcardId, $serviceOperationId, $qcStatus, $userId, $qcNote]
        ) === false) {
            $diagnostic['safe_error_message'] = m30_safe_sql_error_summary($connection);
            throw new RuntimeException('QC check could not be completed.');
        }

        $qcCheckId = m30_fetch_int_value(
            $connection,
            'SELECT TOP 1 qc_check_id FROM dbo.erp_qc_checks
             WHERE jobcard_id = ? AND qc_status = ? AND checked_by_user_id = ?
             ORDER BY qc_check_id DESC',
            [$jobcardId, $qcStatus, $userId],
            'qc_check_id'
        );

        if ($qcCheckId === null || $qcCheckId <= 0) {
            $diagnostic['safe_error_message'] = m30_safe_sql_error_summary($connection);
            throw new RuntimeException('QC check could not be completed.');
        }

        if (erp_m30_execute(
            $connection,
            'INSERT INTO dbo.erp_qc_check_history (
                qc_check_id, jobcard_id, action_code, old_status, new_status, changed_by_user_id, change_note
            ) VALUES (?, ?, ?, NULL, ?, ?, ?)',
            [
                $qcCheckId,
                $jobcardId,
                'QC_CHECK_CREATED',
                $qcStatus,
                $userId,
                'QC check created via Mission 30 prototype.',
            ]
        ) === false) {
            $diagnostic['safe_error_message'] = m30_safe_sql_error_summary($connection);
            throw new RuntimeException('QC check could not be completed.');
        }

        $deliveryStatus = $qcStatus === 'PASSED' ? 'READY' : 'BLOCKED';
        $deliveryAllowed = $qcStatus === 'PASSED' ? 1 : 0;
        $blockReason = $qcStatus === 'FAILED' ? 'QC_FAILED' : null;
        $deliveryActionCode = $qcStatus === 'PASSED' ? 'DELIVERY_READY' : 'DELIVERY_BLOCKED';

        $existingRows = m30_fetch_rows(
            $connection,
            'SELECT TOP 1 delivery_control_id, delivery_status
             FROM dbo.erp_delivery_controls
             WHERE jobcard_id = ? AND is_active = 1
             ORDER BY delivery_control_id DESC',
            [$jobcardId]
        );

        $deliveryControlId = null;
        $oldDeliveryStatus = null;

        if ($existingRows !== [] && strtoupper((string)($existingRows[0]['delivery_status'] ?? '')) !== 'RELEASED') {
            $deliveryControlId = (int)($existingRows[0]['delivery_control_id'] ?? 0);
            $oldDeliveryStatus = trim((string)($existingRows[0]['delivery_status'] ?? ''));

            if ($deliveryControlId > 0 && erp_m30_execute(
                $connection,
                'UPDATE dbo.erp_delivery_controls
                 SET qc_check_id = ?, delivery_status = ?, delivery_allowed = ?, block_reason = ?
                 WHERE delivery_control_id = ?',
                [$qcCheckId, $deliveryStatus, $deliveryAllowed, $blockReason, $deliveryControlId]
            ) === false) {
                $diagnostic['safe_error_message'] = m30_safe_sql_error_summary($connection);
                throw new RuntimeException('QC check could not be completed.');
            }
        } else {
            if (erp_m30_execute(
                $connection,
                'INSERT INTO dbo.erp_delivery_controls (
                    jobcard_id, qc_check_id, delivery_status, delivery_allowed, block_reason, is_active
                ) VALUES (?, ?, ?, ?, ?, 1)',
                [$jobcardId, $qcCheckId, $deliveryStatus, $deliveryAllowed, $blockReason]
            ) === false) {
                $diagnostic['safe_error_message'] = m30_safe_sql_error_summary($connection);
                throw new RuntimeException('QC check could not be completed.');
            }

            $deliveryControlId = m30_fetch_int_value(
                $connection,
                'SELECT TOP 1 delivery_control_id FROM dbo.erp_delivery_controls
                 WHERE jobcard_id = ? AND qc_check_id = ? AND delivery_status = ?
                 ORDER BY delivery_control_id DESC',
                [$jobcardId, $qcCheckId, $deliveryStatus],
                'delivery_control_id'
            );
        }

        if ($deliveryControlId === null || $deliveryControlId <= 0) {
            $diagnostic['safe_error_message'] = m30_safe_sql_error_summary($connection);
            throw new RuntimeException('QC check could not be completed.');
        }

        if (erp_m30_execute(
            $connection,
            'INSERT INTO dbo.erp_delivery_control_history (
                delivery_control_id, jobcard_id, action_code, old_status, new_status, changed_by_user_id, change_note
            ) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $deliveryControlId,
                $jobcardId,
                $deliveryActionCode,
                $oldDeliveryStatus,
                $deliveryStatus,
                $userId,
                $qcStatus === 'PASSED'
                    ? 'Delivery set READY after QC PASSED.'
                    : 'Delivery BLOCKED after QC FAILED.',
            ]
        ) === false) {
            $diagnostic['safe_error_message'] = m30_safe_sql_error_summary($connection);
            throw new RuntimeException('QC check could not be completed.');
        }

        if (!@odbc_commit($connection)) {
            $diagnostic['safe_error_message'] = m30_safe_sql_error_summary($connection);
            throw new RuntimeException('QC check could not be completed.');
        }

        @odbc_autocommit($connection, true);

        return [
            'qc_check_id' => $qcCheckId,
            'jobcard_id' => $jobcardId,
            'qc_status' => $qcStatus,
            'delivery_control_id' => $deliveryControlId,
            'delivery_status' => $deliveryStatus,
        ];
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            @odbc_rollback($connection);
        }

        @odbc_autocommit($connection, true);

        if (($diagnostic['safe_error_message'] ?? '') === '') {
            $diagnostic['safe_error_message'] = trim($exception->getMessage());
        }

        throw $exception;
    }
}

try {
    erp_m30_require_first_existing(erp_m30_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m30_require_first_existing(erp_m30_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>QC Error</title></head><body><p>ERP QC check page could not be loaded.</p></body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');
$userId = ERP_M30_PLATFORM_OWNER_ID;
$username = '—';
$guardCreateLabel = 'FAIL';
$connectionStatus = 'FAIL';
$connectionDetail = 'Not connected';
$errorMessage = '';
$successMessage = '';
$overallOk = false;
$connection = false;
$csrfToken = '';
$activeJobcards = [];

$formInput = [
    'jobcard_id' => '1',
    'service_operation_id' => '',
    'qc_status' => 'PASSED',
    'qc_note' => '',
];

$resultData = [
    'qc_check_id' => '',
    'jobcard_id' => '',
    'qc_status' => '',
    'delivery_status' => '',
];

try {
    erp_auth_context_start();
    m30_csrf_ensure_session();

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

    $guardCreate = erp_m30_guard_eval($connection, $userId, ERP_M30_CREATE_ACTION);
    $guardCreateLabel = (string)($guardCreate['label'] ?? 'FAIL');

    if (empty($guardCreate['allowed'])) {
        throw new RuntimeException('Access denied.');
    }

    $activeJobcards = erp_m30_fetch_active_jobcards($connection);
    $csrfToken = m30_csrf_get_token();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach (array_keys($formInput) as $fieldKey) {
            $formInput[$fieldKey] = erp_m30_post_string($fieldKey);
        }

        if (!m30_csrf_validate(trim((string)($_POST['csrf_token'] ?? '')))) {
            throw new RuntimeException('Invalid CSRF token.');
        }

        $validationErrors = erp_m30_validate_form($formInput);

        if ($validationErrors !== []) {
            throw new RuntimeException($validationErrors[0]);
        }

        $resolvedJobcardId = erp_m30_resolve_active_jobcard($connection, (int)$formInput['jobcard_id']);

        if ($resolvedJobcardId === null) {
            throw new RuntimeException('Selected JobCard is not active or does not exist.');
        }

        $serviceOperationId = null;

        if ($formInput['service_operation_id'] !== '') {
            $serviceOperationId = erp_m30_resolve_service_operation(
                $connection,
                (int)$formInput['service_operation_id'],
                $resolvedJobcardId
            );

            if ($serviceOperationId === null) {
                throw new RuntimeException('Service Operation does not belong to the selected JobCard.');
            }
        }

        $qcNote = $formInput['qc_note'] !== '' ? $formInput['qc_note'] : null;

        /** @var array<string, mixed> */
        $createDiagnostic = [];

        $created = erp_m30_create_qc_and_sync_delivery(
            $connection,
            $userId,
            $resolvedJobcardId,
            $serviceOperationId,
            $formInput['qc_status'],
            $qcNote,
            $createDiagnostic
        );

        $resultData = [
            'qc_check_id' => (string)$created['qc_check_id'],
            'jobcard_id' => (string)$created['jobcard_id'],
            'qc_status' => (string)$created['qc_status'],
            'delivery_status' => (string)$created['delivery_status'],
        ];

        $successMessage = 'QC Check Created OK';
    }

    $overallOk = $connectionStatus === 'OK'
        && in_array($guardCreateLabel, ['OK', 'PLACEHOLDER', 'PLACEHOLDER_OWNER_ALLOWED'], true);
} catch (Throwable $exception) {
    $errorMessage = trim($exception->getMessage()) !== '' ? $exception->getMessage() : 'QC check could not be completed.';
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
    <title>Mission 30 - QC Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; line-height: 1.5; }
        .wrap { max-width: 900px; margin: 0 auto; padding: 24px; }
        .banner { background: #4c1d95; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        label { display: block; margin-bottom: 4px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; margin-bottom: 12px; box-sizing: border-box; }
        button { background: #4c1d95; color: #fff; border: 0; padding: 10px 16px; border-radius: 6px; cursor: pointer; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
        th { background: #f3f4f6; width: 240px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL QC CHECK - MISSION 30 PROTOTYPE</div>

    <div class="card">
        <h1>QC Check</h1>
        <p>
            <a href="erp-delivery-control.php">Delivery control</a> |
            <a href="erp-soft-run-readiness.php">Soft Run readiness</a>
        </p>
        <?php if ($successMessage !== ''): ?><p class="ok"><?= erp_m30_h($successMessage) ?></p><?php endif; ?>
        <?php if ($errorMessage !== ''): ?><p class="fail"><?= erp_m30_h($errorMessage) ?></p><?php endif; ?>
    </div>

    <?php if ($resultData['qc_check_id'] !== ''): ?>
        <div class="card">
            <h2>Created QC Check</h2>
            <table>
                <tbody>
                    <tr><th>QC Check ID</th><td><?= erp_m30_h($resultData['qc_check_id']) ?></td></tr>
                    <tr><th>JobCard ID</th><td><?= erp_m30_h($resultData['jobcard_id']) ?></td></tr>
                    <tr><th>QC Status</th><td><?= erp_m30_h($resultData['qc_status']) ?></td></tr>
                    <tr><th>Delivery Status</th><td><?= erp_m30_h($resultData['delivery_status']) ?></td></tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Create QC Check</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= erp_m30_h($csrfToken) ?>">
            <label for="jobcard_id">JobCard ID</label>
            <select id="jobcard_id" name="jobcard_id" required>
                <?php foreach ($activeJobcards as $row): ?>
                    <?php $jcId = (string)($row['jobcard_id'] ?? ''); ?>
                    <option value="<?= erp_m30_h($jcId) ?>"<?= $formInput['jobcard_id'] === $jcId ? ' selected' : '' ?>>
                        <?= erp_m30_h($jcId . ' — ' . ($row['jobcard_number'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
                <?php if ($activeJobcards === []): ?>
                    <option value="1">1</option>
                <?php endif; ?>
            </select>
            <label for="service_operation_id">Service Operation ID (optional)</label>
            <input type="text" id="service_operation_id" name="service_operation_id" value="<?= erp_m30_h($formInput['service_operation_id']) ?>">
            <label for="qc_status">QC Status</label>
            <select id="qc_status" name="qc_status">
                <option value="PASSED"<?= $formInput['qc_status'] === 'PASSED' ? ' selected' : '' ?>>PASSED</option>
                <option value="FAILED"<?= $formInput['qc_status'] === 'FAILED' ? ' selected' : '' ?>>FAILED</option>
            </select>
            <label for="qc_note">QC Note (optional)</label>
            <textarea id="qc_note" name="qc_note" rows="3"><?= erp_m30_h($formInput['qc_note']) ?></textarea>
            <button type="submit">Create QC Check</button>
        </form>
    </div>
</div>
</body>
</html>
