<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Service Operation Controlled Create Prototype
 *
 * Mission 20 - POST create only with Auth, Permission Guard, CSRF, and transaction.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M20_PLATFORM_OWNER_ID = 10001;
const ERP_M20_CSRF_SESSION_KEY = 'm20_service_operation_create_csrf';
const ERP_M20_CREATE_ACTION = 'service.operation.create';

/** @var array<string, string> */
const ERP_M20_PLACEHOLDER_ACTIONS = [
    'service.operation.create' => 'placeholder_service_operation_create',
    'service.operation.view' => 'placeholder_service_operation_view',
    'service.operation.list' => 'placeholder_service_operation_list',
    'service.operation.assign' => 'placeholder_service_operation_assign',
    'service.operation.status.change' => 'placeholder_service_operation_status_change',
];

/** @var list<string> */
const ERP_M20_ALLOWED_INITIAL_STATUSES = ['ASSIGNED', 'IN_PROGRESS'];

function erp_m20_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m20_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function erp_m20_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m20_post_string(string $key): string
{
    if (!isset($_POST[$key])) {
        return '';
    }

    return trim((string)$_POST[$key]);
}

function erp_m20_is_localhost_request(): bool
{
    $candidates = [
        strtolower((string)($_SERVER['HTTP_HOST'] ?? '')),
        strtolower((string)($_SERVER['SERVER_NAME'] ?? '')),
    ];

    foreach ($candidates as $host) {
        if ($host === 'localhost' || str_starts_with($host, 'localhost:')) {
            return true;
        }

        if ($host === '127.0.0.1' || str_starts_with($host, '127.0.0.1:')) {
            return true;
        }
    }

    return false;
}

function m20_csrf_ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function m20_csrf_get_token(): string
{
    m20_csrf_ensure_session();

    if (
        !isset($_SESSION[ERP_M20_CSRF_SESSION_KEY])
        || !is_string($_SESSION[ERP_M20_CSRF_SESSION_KEY])
        || $_SESSION[ERP_M20_CSRF_SESSION_KEY] === ''
    ) {
        $_SESSION[ERP_M20_CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[ERP_M20_CSRF_SESSION_KEY];
}

function m20_csrf_validate(string $postedToken): bool
{
    m20_csrf_ensure_session();

    $postedToken = trim($postedToken);

    if ($postedToken === '') {
        return false;
    }

    if (
        !isset($_SESSION[ERP_M20_CSRF_SESSION_KEY])
        || !is_string($_SESSION[ERP_M20_CSRF_SESSION_KEY])
        || $_SESSION[ERP_M20_CSRF_SESSION_KEY] === ''
    ) {
        return false;
    }

    return hash_equals($_SESSION[ERP_M20_CSRF_SESSION_KEY], $postedToken);
}

function erp_m20_execute($connection, string $sql, array $params = [])
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false) {
        return false;
    }

    if (!@odbc_execute($statement, $params)) {
        return false;
    }

    return $statement;
}

function m20_fetch_int_value($connection, string $sql, array $params = [], string $columnName = ''): ?int
{
    $statement = erp_m20_execute($connection, $sql, $params);

    if ($statement === false) {
        return null;
    }

    if (@odbc_fetch_row($statement) !== true) {
        return null;
    }

    $value = $columnName !== '' ? @odbc_result($statement, $columnName) : @odbc_result($statement, 1);

    if ($value === false || $value === null || !is_numeric($value)) {
        return null;
    }

    return (int)$value;
}

/**
 * @return list<array<string, string>>
 */
function m20_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = erp_m20_execute($connection, $sql, $params);

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

function m20_safe_throwable_message(Throwable $exception): string
{
    $message = preg_replace('/\s+/', ' ', trim($exception->getMessage())) ?? trim($exception->getMessage());
    $message = preg_replace('/[A-Za-z]:\\\\[^\]\s]+/i', '[path-redacted]', $message) ?? $message;

    if ($message === '' || strcasecmp($message, 'Service Operation create could not be completed.') === 0) {
        return 'Create operation failed.';
    }

    if (strlen($message) > 300) {
        return substr($message, 0, 300) . '...';
    }

    return $message;
}

function m20_safe_sql_error_code($connection): string
{
    $message = (string)@odbc_errormsg($connection);

    if ($message === '') {
        return 'N/A';
    }

    if (preg_match('/\[SQLSTATE\s*\[(\w+)\]\]/i', $message, $matches) === 1) {
        return (string)$matches[1];
    }

    if (preg_match('/\[(HY\d{5})\]/i', $message, $matches) === 1) {
        return (string)$matches[1];
    }

    return 'N/A';
}

function m20_safe_sql_error_summary($connection): string
{
    $message = (string)@odbc_errormsg($connection);

    if ($message === '') {
        return 'No ODBC error message available.';
    }

    $message = preg_replace('/Driver=\{[^}]+\};[^\]]+/i', '[connection-redacted]', $message) ?? $message;
    $message = preg_replace('/Server=[^;\]\s]+/i', 'Server=[redacted]', $message) ?? $message;
    $message = preg_replace('/Database=[^;\]\s]+/i', 'Database=[redacted]', $message) ?? $message;
    $message = preg_replace('/Trusted_Connection=[^;\]\s]+/i', 'Trusted_Connection=[redacted]', $message) ?? $message;
    $message = preg_replace('/Password=[^;\]\s]+/i', 'Password=[redacted]', $message) ?? $message;
    $message = preg_replace('/UID=[^;\]\s]+/i', 'UID=[redacted]', $message) ?? $message;
    $message = preg_replace('/PWD=[^;\]\s]+/i', 'PWD=[redacted]', $message) ?? $message;
    $message = preg_replace('/[A-Za-z]:\\\\[^\]\s]+/i', '[path-redacted]', $message) ?? $message;
    $message = preg_replace('/\s+/', ' ', trim($message)) ?? trim($message);

    if (strlen($message) > 300) {
        return substr($message, 0, 300) . '...';
    }

    return $message;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m20_set_stage(array &$diagnostic, string $stage): void
{
    $diagnostic['failure_stage'] = $stage;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m20_mark_success(array &$diagnostic, string $stage): void
{
    $diagnostic['last_successful_step'] = $stage;
    $diagnostic['failure_stage'] = $stage;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m20_capture_sql_error(array &$diagnostic, $connection): void
{
    $diagnostic['safe_error_code'] = m20_safe_sql_error_code($connection);
    $diagnostic['safe_error_message'] = m20_safe_sql_error_summary($connection);
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m20_capture_failure(array &$diagnostic, $connection, ?Throwable $exception = null): void
{
    erp_m20_capture_sql_error($diagnostic, $connection);

    if (
        (string)($diagnostic['safe_error_message'] ?? '') === ''
        || (string)$diagnostic['safe_error_message'] === 'No ODBC error message available.'
    ) {
        if ($exception !== null) {
            $diagnostic['safe_error_message'] = m20_safe_throwable_message($exception);
        }
    }
}

/**
 * @return array<string, mixed>
 */
function erp_m20_guard_eval($connection, int $userId, string $actionKey): array
{
    $actionKey = trim($actionKey);
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

    if (!isset(ERP_M20_PLACEHOLDER_ACTIONS[$actionKey])) {
        return [
            'allowed' => false,
            'label' => 'FAIL',
            'placeholder' => false,
            'action_key' => $actionKey,
            'required_permission' => '',
        ];
    }

    if ($userId === ERP_M20_PLATFORM_OWNER_ID) {
        return [
            'allowed' => true,
            'label' => 'PLACEHOLDER_OWNER_ALLOWED',
            'placeholder' => true,
            'action_key' => $actionKey,
            'required_permission' => ERP_M20_PLACEHOLDER_ACTIONS[$actionKey],
        ];
    }

    return [
        'allowed' => false,
        'label' => 'FAIL',
        'placeholder' => true,
        'action_key' => $actionKey,
        'required_permission' => ERP_M20_PLACEHOLDER_ACTIONS[$actionKey],
    ];
}

/**
 * @return list<array<string, string>>
 */
function erp_m20_fetch_active_jobcards($connection): array
{
    return m20_fetch_rows(
        $connection,
        'SELECT
            j.jobcard_id,
            j.jobcard_number,
            j.jobcard_status,
            j.lifecycle_state,
            c.full_name,
            v.brand,
            v.model,
            v.plate_number
         FROM dbo.erp_jobcards j
         INNER JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
         INNER JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
         WHERE j.lifecycle_state = \'ACTIVE\'
         ORDER BY j.jobcard_id ASC'
    );
}

function erp_m20_resolve_active_jobcard($connection, int $jobcardId): ?int
{
    if ($jobcardId <= 0) {
        return null;
    }

    $count = m20_fetch_int_value(
        $connection,
        'SELECT COUNT(*) AS jobcard_count
         FROM dbo.erp_jobcards
         WHERE jobcard_id = ?
           AND lifecycle_state = \'ACTIVE\'',
        [$jobcardId],
        'jobcard_count'
    );

    if ($count === null || $count < 1) {
        return null;
    }

    return $jobcardId;
}

function erp_m20_resolve_assigned_to_user_id(mixed $assignedRaw): ?int
{
    if (is_string($assignedRaw)) {
        $assignedRaw = trim($assignedRaw);
    } elseif ($assignedRaw === null) {
        $assignedRaw = '';
    } else {
        $assignedRaw = trim((string)$assignedRaw);
    }

    if ($assignedRaw === '' || $assignedRaw === null) {
        return null;
    }

    if (ctype_digit((string)$assignedRaw) && (int)$assignedRaw > 0) {
        return (int)$assignedRaw;
    }

    throw new RuntimeException('Assigned technician user ID must be a positive integer when provided.');
}

/**
 * @return array<string, string>
 */
function erp_m20_validate_form(array $input): array
{
    $errors = [];

    if ($input['jobcard_id'] === '' || !ctype_digit($input['jobcard_id'])) {
        $errors[] = 'JobCard ID is required.';
    }

    if ($input['service_title'] === '') {
        $errors[] = 'Service title is required.';
    }

    if (!in_array($input['service_status'], ERP_M20_ALLOWED_INITIAL_STATUSES, true)) {
        $errors[] = 'Initial service status must be ASSIGNED or IN_PROGRESS.';
    }

    return $errors;
}

function erp_m20_jobcard_option_label(array $row): string
{
    $jobcardId = trim((string)($row['jobcard_id'] ?? ''));
    $jobcardNumber = trim((string)($row['jobcard_number'] ?? ''));
    $fullName = trim((string)($row['full_name'] ?? ''));
    $brand = trim((string)($row['brand'] ?? ''));
    $model = trim((string)($row['model'] ?? ''));
    $plateNumber = trim((string)($row['plate_number'] ?? ''));
    $status = trim((string)($row['jobcard_status'] ?? ''));

    return $jobcardId . ' — ' . $jobcardNumber . ' — ' . $fullName . ' — ' . $brand . ' ' . $model
        . ($plateNumber !== '' ? ' (' . $plateNumber . ')' : '')
        . ' [' . $status . ']';
}

/**
 * @param array<string, mixed> $diagnostic
 * @param array<string, string> $input
 * @return array<string, int|string>
 */
function erp_m20_create_service_operation(
    $connection,
    int $userId,
    int $jobcardId,
    array $input,
    ?int $assignedToUserId,
    array &$diagnostic
): array {
    $transactionStarted = false;

    try {
        erp_m20_set_stage($diagnostic, 'TRANSACTION_STARTED');

        if (!@odbc_autocommit($connection, false)) {
            erp_m20_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Service Operation create could not be completed.');
        }

        $transactionStarted = true;
        $diagnostic['transaction_rolled_back'] = 'NO';
        erp_m20_mark_success($diagnostic, 'TRANSACTION_STARTED');

        $serviceStatus = $input['service_status'];

        erp_m20_set_stage($diagnostic, 'SERVICE_OPERATION_INSERT_START');
        $operationStatement = erp_m20_execute(
            $connection,
            'INSERT INTO dbo.erp_service_operations (
                jobcard_id,
                service_title,
                service_description,
                assigned_to_user_id,
                service_status,
                created_by_user_id,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, 1)',
            [
                $jobcardId,
                $input['service_title'],
                $input['service_description'] !== '' ? $input['service_description'] : null,
                $assignedToUserId,
                $serviceStatus,
                $userId,
            ]
        );

        if ($operationStatement === false) {
            erp_m20_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Service Operation create could not be completed.');
        }

        erp_m20_mark_success($diagnostic, 'SERVICE_OPERATION_INSERT_DONE');

        erp_m20_set_stage($diagnostic, 'SERVICE_OPERATION_ID_FETCH_START');
        $serviceOperationId = m20_fetch_int_value(
            $connection,
            'SELECT TOP 1 service_operation_id
             FROM dbo.erp_service_operations
             WHERE jobcard_id = ?
               AND service_title = ?
               AND service_status = ?
               AND created_by_user_id = ?
             ORDER BY service_operation_id DESC',
            [$jobcardId, $input['service_title'], $serviceStatus, $userId],
            'service_operation_id'
        );

        if ($serviceOperationId === null || $serviceOperationId <= 0) {
            erp_m20_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Service Operation create could not be completed.');
        }

        erp_m20_mark_success($diagnostic, 'SERVICE_OPERATION_ID_FETCH_DONE');

        erp_m20_set_stage($diagnostic, 'HISTORY_INSERT_START');
        $historyStatement = erp_m20_execute(
            $connection,
            'INSERT INTO dbo.erp_service_operation_change_history (
                service_operation_id,
                jobcard_id,
                action_code,
                old_status,
                new_status,
                changed_by_user_id,
                change_note
            ) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $serviceOperationId,
                $jobcardId,
                'SERVICE_OPERATION_CREATED',
                null,
                $serviceStatus,
                $userId,
                'Service Operation record created via Mission 20 prototype.',
            ]
        );

        if ($historyStatement === false) {
            erp_m20_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Service Operation create could not be completed.');
        }

        erp_m20_mark_success($diagnostic, 'HISTORY_INSERT_DONE');

        erp_m20_set_stage($diagnostic, 'TRANSACTION_COMMIT_START');
        if (!@odbc_commit($connection)) {
            erp_m20_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Service Operation create could not be completed.');
        }

        @odbc_autocommit($connection, true);
        erp_m20_mark_success($diagnostic, 'TRANSACTION_COMMITTED');
        $diagnostic['transaction_rolled_back'] = 'NO';

        return [
            'service_operation_id' => $serviceOperationId,
            'jobcard_id' => $jobcardId,
            'service_title' => $input['service_title'],
            'service_status' => $serviceStatus,
        ];
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            $rollbackOk = @odbc_rollback($connection);
            $diagnostic['transaction_rolled_back'] = $rollbackOk ? 'YES' : 'UNKNOWN';
        }

        @odbc_autocommit($connection, true);
        erp_m20_capture_failure($diagnostic, $connection, $exception);

        throw $exception;
    }
}

try {
    erp_m20_require_first_existing(erp_m20_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m20_require_first_existing(erp_m20_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Create Error</title></head><body>';
    echo '<p>ERP Service Operation create page could not be loaded.</p>';
    echo '</body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');

$userId = ERP_M20_PLATFORM_OWNER_ID;
$username = '—';
$rolesText = '—';
$permissionCount = 0;
$guardCreateLabel = 'FAIL';
$connectionStatus = 'FAIL';
$connectionDetail = 'Not connected';
$accessDenied = false;
$errorMessage = '';
$successMessage = '';
$overallOk = false;
$connection = false;
$showOwnerDiagnostic = false;
$csrfToken = '';
$activeJobcards = [];

/** @var array<string, mixed> */
$createDiagnostic = [
    'failure_stage' => '',
    'safe_error_code' => 'N/A',
    'safe_error_message' => '',
    'last_successful_step' => '',
    'transaction_rolled_back' => 'UNKNOWN',
];

$formInput = [
    'jobcard_id' => '1',
    'service_title' => '',
    'service_description' => '',
    'assigned_to_user_id' => '',
    'service_status' => 'ASSIGNED',
];

$resultData = [
    'service_operation_id' => '',
    'jobcard_id' => '',
    'service_title' => '',
    'service_status' => '',
];

try {
    erp_auth_context_start();
    m20_csrf_ensure_session();

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

    $guardCreate = erp_m20_guard_eval($connection, $userId, ERP_M20_CREATE_ACTION);
    $guardCreateLabel = (string)($guardCreate['label'] ?? 'FAIL');

    if (!$guardCreate['allowed']) {
        $accessDenied = true;
        throw new RuntimeException('Access denied.');
    }

    $activeJobcards = erp_m20_fetch_active_jobcards($connection);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $createDiagnostic['last_successful_step'] = 'POST_RECEIVED';

        foreach (array_keys($formInput) as $fieldKey) {
            $formInput[$fieldKey] = erp_m20_post_string($fieldKey);
        }

        if ($formInput['service_status'] === '') {
            $formInput['service_status'] = 'ASSIGNED';
        }

        $postedCsrfToken = trim((string)($_POST['csrf_token'] ?? ''));

        if (!m20_csrf_validate($postedCsrfToken)) {
            erp_m20_set_stage($createDiagnostic, 'POST_FAILED');
            $createDiagnostic['last_successful_step'] = 'POST_RECEIVED';
            $createDiagnostic['safe_error_message'] = 'Invalid CSRF token.';
            $createDiagnostic['transaction_rolled_back'] = 'NO';
            throw new RuntimeException('Invalid CSRF token.');
        }

        $createDiagnostic['last_successful_step'] = 'CSRF_PASSED';

        $validationErrors = erp_m20_validate_form($formInput);

        if ($validationErrors !== []) {
            erp_m20_set_stage($createDiagnostic, 'POST_FAILED');
            $createDiagnostic['last_successful_step'] = 'CSRF_PASSED';
            $createDiagnostic['safe_error_message'] = $validationErrors[0];
            $createDiagnostic['transaction_rolled_back'] = 'NO';
            throw new RuntimeException($validationErrors[0]);
        }

        $jobcardId = (int)$formInput['jobcard_id'];
        $resolvedJobcardId = erp_m20_resolve_active_jobcard($connection, $jobcardId);

        if ($resolvedJobcardId === null) {
            erp_m20_set_stage($createDiagnostic, 'POST_FAILED');
            $createDiagnostic['last_successful_step'] = 'CSRF_PASSED';
            $createDiagnostic['safe_error_message'] = 'Selected JobCard is not active or does not exist.';
            $createDiagnostic['transaction_rolled_back'] = 'NO';
            throw new RuntimeException('Selected JobCard is not active or does not exist.');
        }

        erp_m20_mark_success($createDiagnostic, 'VALIDATION_PASSED');

        $assignedToUserId = erp_m20_resolve_assigned_to_user_id($_POST['assigned_to_user_id'] ?? '');

        $created = erp_m20_create_service_operation(
            $connection,
            $userId,
            $resolvedJobcardId,
            $formInput,
            $assignedToUserId,
            $createDiagnostic
        );

        $resultData = [
            'service_operation_id' => (string)$created['service_operation_id'],
            'jobcard_id' => (string)$created['jobcard_id'],
            'service_title' => (string)$created['service_title'],
            'service_status' => (string)$created['service_status'],
        ];

        $successMessage = 'Service Operation Created OK';
        $overallOk = true;

        unset($_SESSION[ERP_M20_CSRF_SESSION_KEY]);
        $csrfToken = m20_csrf_get_token();
    } else {
        $csrfToken = m20_csrf_get_token();
    }
} catch (Throwable $exception) {
    if ($accessDenied) {
        $errorMessage = 'Access denied. You do not have permission to perform this action.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ((string)$createDiagnostic['safe_error_message'] !== '') {
            $errorMessage = (string)$createDiagnostic['safe_error_message'];
        } elseif ($errorMessage === '') {
            $errorMessage = 'Service Operation create could not be completed.';
        }

        if (
            $connection !== false
            && (string)$createDiagnostic['safe_error_message'] === ''
        ) {
            erp_m20_capture_failure($createDiagnostic, $connection, $exception);
        } elseif ((string)$createDiagnostic['safe_error_message'] === '') {
            $createDiagnostic['safe_error_message'] = m20_safe_throwable_message($exception);
        }

        if ((string)$createDiagnostic['failure_stage'] === '') {
            erp_m20_set_stage($createDiagnostic, 'POST_FAILED');
        }

        if ((string)$createDiagnostic['last_successful_step'] === '') {
            $createDiagnostic['last_successful_step'] = 'POST_RECEIVED';
        }
    } elseif ($errorMessage === '') {
        $errorMessage = 'Service Operation create page could not be loaded.';
    }
} finally {
    if ($csrfToken === '' && !$accessDenied && $connectionStatus === 'OK') {
        $csrfToken = m20_csrf_get_token();
    }

    if (
        $userId === ERP_M20_PLATFORM_OWNER_ID
        && $_SERVER['REQUEST_METHOD'] === 'POST'
        && !$overallOk
        && erp_m20_is_localhost_request()
    ) {
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
    <title>Mission 20 - Service Operation Create</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; line-height: 1.5; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 24px; }
        .banner { background: #7f1d1d; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        h1, h2 { margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.92rem; margin-bottom: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; width: 240px; }
        label { display: block; font-weight: bold; margin-bottom: 4px; }
        input, select, textarea { width: 100%; box-sizing: border-box; padding: 8px; margin-bottom: 12px; }
        button { background: #1d4ed8; color: #fff; border: 0; padding: 10px 16px; border-radius: 6px; cursor: pointer; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
        .diag { background: #fff7ed; border-color: #fdba74; }
        .hint { font-size: 0.88rem; color: #4b5563; margin: -8px 0 12px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL CONTROLLED SERVICE OPERATION CREATE PROTOTYPE - REMOVE OR PROTECT BEFORE DEPLOYMENT</div>

    <div class="card">
        <h1>Service Operation Create</h1>
        <p>Mission 20 - Controlled internal ERP create prototype</p>
        <?php if ($errorMessage !== ''): ?>
            <p class="fail"><?= erp_m20_h($errorMessage) ?></p>
        <?php endif; ?>
        <?php if ($successMessage !== ''): ?>
            <p class="ok"><?= erp_m20_h($successMessage) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($showOwnerDiagnostic): ?>
        <div class="card diag">
            <h2>Local Owner Diagnostic - Safe</h2>
            <table>
                <tbody>
                    <tr><th>failure_stage</th><td><?= erp_m20_h((string)($createDiagnostic['failure_stage'] ?? '')) ?></td></tr>
                    <tr><th>safe_error_code</th><td><?= erp_m20_h((string)($createDiagnostic['safe_error_code'] ?? 'N/A')) ?></td></tr>
                    <tr><th>safe_error_message</th><td><?= erp_m20_h((string)($createDiagnostic['safe_error_message'] ?? '')) ?></td></tr>
                    <tr><th>last_successful_step</th><td><?= erp_m20_h((string)($createDiagnostic['last_successful_step'] ?? '')) ?></td></tr>
                    <tr><th>transaction_rolled_back</th><td><?= erp_m20_h((string)($createDiagnostic['transaction_rolled_back'] ?? 'UNKNOWN')) ?></td></tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Auth and Permission Summary</h2>
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m20_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection status</th><td><?= erp_m20_h($connectionStatus) ?> — <?= erp_m20_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m20_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m20_h($username) ?></td></tr>
                <tr><th>roles</th><td><?= erp_m20_h($rolesText) ?></td></tr>
                <tr><th>permissions count</th><td><?= erp_m20_h((string)$permissionCount) ?></td></tr>
                <tr><th>guard service.operation.create</th><td><?= erp_m20_h($guardCreateLabel) ?></td></tr>
            </tbody>
        </table>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="card">
            <h2>Create Result</h2>
            <table>
                <tbody>
                    <tr><th>service_operation_id</th><td><?= erp_m20_h($resultData['service_operation_id']) ?></td></tr>
                    <tr><th>jobcard_id</th><td><?= erp_m20_h($resultData['jobcard_id']) ?></td></tr>
                    <tr><th>service_title</th><td><?= erp_m20_h($resultData['service_title']) ?></td></tr>
                    <tr><th>service_status</th><td><?= erp_m20_h($resultData['service_status']) ?></td></tr>
                    <tr><th>Audit/History</th><td class="ok">SERVICE_OPERATION_CREATED RECORDED</td></tr>
                    <tr><th>Overall Status</th><td class="ok">OK</td></tr>
                </tbody>
            </table>
            <p><a href="erp-service-operation-readonly-list.php">Open read-only list</a></p>
        </div>
    <?php endif; ?>

    <?php if (!$accessDenied && $connectionStatus === 'OK'): ?>
        <div class="card">
            <h2>Create Form</h2>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= erp_m20_h($csrfToken) ?>">

                <label for="jobcard_id">jobcard_id *</label>
                <select id="jobcard_id" name="jobcard_id" required>
                    <option value="">Select active JobCard</option>
                    <?php foreach ($activeJobcards as $jobcardRow): ?>
                        <?php
                        $optionValue = trim((string)($jobcardRow['jobcard_id'] ?? ''));
                        if ($optionValue === '') {
                            continue;
                        }
                        ?>
                        <option value="<?= erp_m20_h($optionValue) ?>" <?= $formInput['jobcard_id'] === $optionValue ? 'selected' : '' ?>>
                            <?= erp_m20_h(erp_m20_jobcard_option_label($jobcardRow)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="hint">Accepts JobCard ID = 1 or any other active JobCard from foundation.</p>

                <label for="service_title">service_title *</label>
                <input type="text" id="service_title" name="service_title" maxlength="200" required value="<?= erp_m20_h($formInput['service_title']) ?>">

                <label for="service_description">service_description</label>
                <textarea id="service_description" name="service_description" rows="4"><?= erp_m20_h($formInput['service_description']) ?></textarea>

                <label for="assigned_to_user_id">assigned_to_user_id (placeholder)</label>
                <input type="text" id="assigned_to_user_id" name="assigned_to_user_id" value="<?= erp_m20_h($formInput['assigned_to_user_id']) ?>">
                <p class="hint">Optional. Leave empty for unassigned technician placeholder.</p>

                <label for="service_status">service_status</label>
                <select id="service_status" name="service_status">
                    <option value="ASSIGNED" <?= $formInput['service_status'] === 'ASSIGNED' ? 'selected' : '' ?>>ASSIGNED</option>
                    <option value="IN_PROGRESS" <?= $formInput['service_status'] === 'IN_PROGRESS' ? 'selected' : '' ?>>IN_PROGRESS</option>
                </select>

                <button type="submit">Create Service Operation</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
