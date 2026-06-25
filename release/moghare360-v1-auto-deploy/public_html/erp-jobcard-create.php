<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP JobCard Controlled Create Prototype
 *
 * Mission 17 - POST create only with Auth, Permission Guard, CSRF, and transaction.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M17_PLATFORM_OWNER_ID = 10001;
const ERP_M17_CSRF_SESSION_KEY = 'm17_jobcard_create_csrf';
const ERP_M17_CREATE_ACTION = 'jobcard.create';

/** @var array<string, string> */
const ERP_M17_PLACEHOLDER_ACTIONS = [
    'jobcard.create' => 'placeholder_jobcard_create',
    'jobcard.view' => 'placeholder_jobcard_view',
    'jobcard.list' => 'placeholder_jobcard_list',
];

function erp_m17_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m17_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function erp_m17_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m17_post_string(string $key): string
{
    if (!isset($_POST[$key])) {
        return '';
    }

    return trim((string)$_POST[$key]);
}

function erp_m17_is_localhost_request(): bool
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

function m17_csrf_ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function m17_csrf_get_token(): string
{
    m17_csrf_ensure_session();

    if (
        !isset($_SESSION[ERP_M17_CSRF_SESSION_KEY])
        || !is_string($_SESSION[ERP_M17_CSRF_SESSION_KEY])
        || $_SESSION[ERP_M17_CSRF_SESSION_KEY] === ''
    ) {
        $_SESSION[ERP_M17_CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[ERP_M17_CSRF_SESSION_KEY];
}

function m17_csrf_validate(string $postedToken): bool
{
    m17_csrf_ensure_session();

    $postedToken = trim($postedToken);

    if ($postedToken === '') {
        return false;
    }

    if (
        !isset($_SESSION[ERP_M17_CSRF_SESSION_KEY])
        || !is_string($_SESSION[ERP_M17_CSRF_SESSION_KEY])
        || $_SESSION[ERP_M17_CSRF_SESSION_KEY] === ''
    ) {
        return false;
    }

    return hash_equals($_SESSION[ERP_M17_CSRF_SESSION_KEY], $postedToken);
}

function erp_m17_execute($connection, string $sql, array $params = [])
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

function m17_fetch_int_value($connection, string $sql, array $params = [], string $columnName = ''): ?int
{
    $statement = erp_m17_execute($connection, $sql, $params);

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

function m17_fetch_string_value($connection, string $sql, array $params = [], string $columnName = ''): ?string
{
    $statement = erp_m17_execute($connection, $sql, $params);

    if ($statement === false) {
        return null;
    }

    if (@odbc_fetch_row($statement) !== true) {
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
function m17_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = erp_m17_execute($connection, $sql, $params);

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

function m17_safe_throwable_message(Throwable $exception): string
{
    $message = preg_replace('/\s+/', ' ', trim($exception->getMessage())) ?? trim($exception->getMessage());
    $message = preg_replace('/[A-Za-z]:\\\\[^\]\s]+/i', '[path-redacted]', $message) ?? $message;

    if ($message === '' || strcasecmp($message, 'JobCard create could not be completed.') === 0) {
        return 'Create operation failed.';
    }

    if (strlen($message) > 300) {
        return substr($message, 0, 300) . '...';
    }

    return $message;
}

function m17_safe_sql_error_code($connection): string
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

function m17_safe_sql_error_summary($connection): string
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
function erp_m17_set_stage(array &$diagnostic, string $stage): void
{
    $diagnostic['failure_stage'] = $stage;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m17_mark_success(array &$diagnostic, string $stage): void
{
    $diagnostic['last_successful_step'] = $stage;
    $diagnostic['failure_stage'] = $stage;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m17_capture_sql_error(array &$diagnostic, $connection): void
{
    $diagnostic['safe_error_code'] = m17_safe_sql_error_code($connection);
    $diagnostic['safe_error_message'] = m17_safe_sql_error_summary($connection);
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m17_capture_failure(array &$diagnostic, $connection, ?Throwable $exception = null): void
{
    erp_m17_capture_sql_error($diagnostic, $connection);

    if (
        (string)($diagnostic['safe_error_message'] ?? '') === ''
        || (string)$diagnostic['safe_error_message'] === 'No ODBC error message available.'
    ) {
        if ($exception !== null) {
            $diagnostic['safe_error_message'] = m17_safe_throwable_message($exception);
        }
    }
}

function erp_m17_generate_jobcard_number(): string
{
    return 'JC-' . date('YmdHis') . '-' . (string)random_int(1000, 9999);
}

function erp_m17_normalize_datetime_input(string $value): ?string
{
    $value = trim($value);

    if ($value === '') {
        return null;
    }

    $value = str_replace('T', ' ', $value);

    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) === 1) {
        return $value . ':00.000';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) === 1) {
        return $value . '.000';
    }

    return $value;
}

/**
 * @return array<string, mixed>
 */
function erp_m17_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M17_PLACEHOLDER_ACTIONS[$actionKey])) {
        return [
            'allowed' => false,
            'label' => 'FAIL',
            'placeholder' => false,
            'action_key' => $actionKey,
            'required_permission' => '',
        ];
    }

    if ($userId === ERP_M17_PLATFORM_OWNER_ID) {
        return [
            'allowed' => true,
            'label' => 'PLACEHOLDER_OWNER_ALLOWED',
            'placeholder' => true,
            'action_key' => $actionKey,
            'required_permission' => ERP_M17_PLACEHOLDER_ACTIONS[$actionKey],
        ];
    }

    return [
        'allowed' => false,
        'label' => 'FAIL',
        'placeholder' => true,
        'action_key' => $actionKey,
        'required_permission' => ERP_M17_PLACEHOLDER_ACTIONS[$actionKey],
    ];
}

/**
 * @return list<array<string, string>>
 */
function erp_m17_fetch_active_relations($connection): array
{
    return m17_fetch_rows(
        $connection,
        'SELECT
            r.relation_id,
            c.customer_id,
            c.full_name,
            c.primary_mobile,
            v.vehicle_id,
            v.brand,
            v.model,
            v.plate_number,
            v.vin
         FROM dbo.erp_customer_vehicle_relations r
         INNER JOIN dbo.erp_customers c ON c.customer_id = r.customer_id
         INNER JOIN dbo.erp_vehicles v ON v.vehicle_id = r.vehicle_id
         WHERE r.lifecycle_state = \'ACTIVE\'
         ORDER BY r.relation_id DESC'
    );
}

/**
 * @return array<string, int>|null
 */
function erp_m17_resolve_active_relation($connection, int $relationId): ?array
{
    if ($relationId <= 0) {
        return null;
    }

    $rows = m17_fetch_rows(
        $connection,
        'SELECT TOP 1
            r.relation_id,
            r.customer_id,
            r.vehicle_id
         FROM dbo.erp_customer_vehicle_relations r
         INNER JOIN dbo.erp_customers c ON c.customer_id = r.customer_id
         INNER JOIN dbo.erp_vehicles v ON v.vehicle_id = r.vehicle_id
         WHERE r.relation_id = ?
           AND r.lifecycle_state = \'ACTIVE\'',
        [$relationId]
    );

    if ($rows === []) {
        return null;
    }

    $row = $rows[0];
    $resolvedRelationId = (int)($row['relation_id'] ?? 0);
    $customerId = (int)($row['customer_id'] ?? 0);
    $vehicleId = (int)($row['vehicle_id'] ?? 0);

    if ($resolvedRelationId <= 0 || $customerId <= 0 || $vehicleId <= 0) {
        return null;
    }

    return [
        'relation_id' => $resolvedRelationId,
        'customer_id' => $customerId,
        'vehicle_id' => $vehicleId,
    ];
}

/**
 * @return array<string, string>
 */
function erp_m17_validate_form(array $input): array
{
    $errors = [];

    if ($input['relation_id'] === '' || !ctype_digit($input['relation_id'])) {
        $errors[] = 'Customer / vehicle relation is required.';
    }

    if (!in_array($input['jobcard_status'], ['DRAFT', 'RECEIVED'], true)) {
        $errors[] = 'JobCard status must be DRAFT or RECEIVED.';
    }

    if ($input['intake_mileage'] !== '' && !ctype_digit($input['intake_mileage'])) {
        $errors[] = 'Intake mileage must be an integer.';
    }

    if ($input['customer_complaint'] === '' && $input['requested_services_summary'] === '') {
        $errors[] = 'At least one of customer complaint or requested services summary is required.';
    }

    return $errors;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m17_insert_jobcard_history_row(
    $connection,
    array &$diagnostic,
    string $startStage,
    string $doneStage,
    int $jobcardId,
    string $changeType,
    ?string $previousStatus,
    ?string $newStatus,
    string $changeSummary,
    int $userId
): void {
    erp_m17_set_stage($diagnostic, $startStage);

    $historyStatement = erp_m17_execute(
        $connection,
        'INSERT INTO dbo.erp_jobcard_change_history (
            jobcard_id,
            change_type,
            previous_status,
            new_status,
            change_summary,
            changed_by_user_id
        ) VALUES (?, ?, ?, ?, ?, ?)',
        [
            $jobcardId,
            $changeType,
            $previousStatus,
            $newStatus,
            $changeSummary,
            $userId,
        ]
    );

    if ($historyStatement === false) {
        erp_m17_capture_sql_error($diagnostic, $connection);
        throw new RuntimeException('JobCard create could not be completed.');
    }

    erp_m17_mark_success($diagnostic, $doneStage);
}

/**
 * @param array<string, mixed> $diagnostic
 * @param array<string, string> $input
 * @param array<string, int> $relationData
 * @return array<string, int|string>
 */
function erp_m17_create_jobcard(
    $connection,
    int $userId,
    array $input,
    array $relationData,
    array &$diagnostic
): array {
    $transactionStarted = false;

    try {
        erp_m17_set_stage($diagnostic, 'TRANSACTION_STARTED');

        if (!@odbc_autocommit($connection, false)) {
            erp_m17_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('JobCard create could not be completed.');
        }

        $transactionStarted = true;
        $diagnostic['transaction_rolled_back'] = 'NO';
        erp_m17_mark_success($diagnostic, 'TRANSACTION_STARTED');

        $jobcardNumber = erp_m17_generate_jobcard_number();
        $jobcardStatus = $input['jobcard_status'];
        $priorityLevel = $input['priority_level'] !== '' ? $input['priority_level'] : 'NORMAL';

        $receptionAt = erp_m17_normalize_datetime_input($input['reception_at']);

        if ($receptionAt === null) {
            $receptionAt = m17_fetch_string_value(
                $connection,
                'SELECT CONVERT(VARCHAR(23), SYSUTCDATETIME(), 121) AS reception_at',
                [],
                'reception_at'
            );

            if ($receptionAt === null) {
                erp_m17_capture_sql_error($diagnostic, $connection);
                throw new RuntimeException('JobCard create could not be completed.');
            }
        }

        $promisedAt = erp_m17_normalize_datetime_input($input['promised_at']);

        erp_m17_set_stage($diagnostic, 'JOBCARD_INSERT_START');
        $jobcardStatement = erp_m17_execute(
            $connection,
            'INSERT INTO dbo.erp_jobcards (
                jobcard_number,
                customer_id,
                vehicle_id,
                relation_id,
                reception_user_id,
                jobcard_status,
                reception_at,
                promised_at,
                intake_mileage,
                fuel_level,
                customer_complaint,
                requested_services_summary,
                initial_vehicle_condition,
                internal_notes,
                priority_level,
                lifecycle_state,
                created_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'ACTIVE\', ?)',
            [
                $jobcardNumber,
                $relationData['customer_id'],
                $relationData['vehicle_id'],
                $relationData['relation_id'],
                $userId,
                $jobcardStatus,
                $receptionAt,
                $promisedAt,
                $input['intake_mileage'] !== '' ? (int)$input['intake_mileage'] : null,
                $input['fuel_level'] !== '' ? $input['fuel_level'] : null,
                $input['customer_complaint'] !== '' ? $input['customer_complaint'] : null,
                $input['requested_services_summary'] !== '' ? $input['requested_services_summary'] : null,
                $input['initial_vehicle_condition'] !== '' ? $input['initial_vehicle_condition'] : null,
                $input['internal_notes'] !== '' ? $input['internal_notes'] : null,
                $priorityLevel,
                $userId,
            ]
        );

        if ($jobcardStatement === false) {
            erp_m17_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('JobCard create could not be completed.');
        }

        erp_m17_mark_success($diagnostic, 'JOBCARD_INSERT_DONE');

        erp_m17_set_stage($diagnostic, 'JOBCARD_ID_FETCH_START');
        $jobcardId = m17_fetch_int_value(
            $connection,
            'SELECT jobcard_id
             FROM dbo.erp_jobcards
             WHERE jobcard_number = ?',
            [$jobcardNumber],
            'jobcard_id'
        );

        if ($jobcardId === null || $jobcardId <= 0) {
            erp_m17_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('JobCard create could not be completed.');
        }

        erp_m17_mark_success($diagnostic, 'JOBCARD_ID_FETCH_DONE');

        erp_m17_insert_jobcard_history_row(
            $connection,
            $diagnostic,
            'HISTORY_JOBCARD_CREATED_START',
            'HISTORY_JOBCARD_CREATED_DONE',
            $jobcardId,
            'JOBCARD_CREATED',
            null,
            $jobcardStatus,
            'JobCard record created via Mission 17 prototype.',
            $userId
        );

        if ($jobcardStatus === 'RECEIVED') {
            erp_m17_insert_jobcard_history_row(
                $connection,
                $diagnostic,
                'HISTORY_JOBCARD_RECEIVED_START',
                'HISTORY_JOBCARD_RECEIVED_DONE',
                $jobcardId,
                'JOBCARD_RECEIVED',
                null,
                'RECEIVED',
                'JobCard received via Mission 17 prototype.',
                $userId
            );
        }

        erp_m17_set_stage($diagnostic, 'TRANSACTION_COMMIT_START');
        if (!@odbc_commit($connection)) {
            erp_m17_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('JobCard create could not be completed.');
        }

        @odbc_autocommit($connection, true);
        erp_m17_mark_success($diagnostic, 'TRANSACTION_COMMITTED');
        $diagnostic['transaction_rolled_back'] = 'NO';

        return [
            'jobcard_id' => $jobcardId,
            'jobcard_number' => $jobcardNumber,
            'customer_id' => $relationData['customer_id'],
            'vehicle_id' => $relationData['vehicle_id'],
            'relation_id' => $relationData['relation_id'],
            'jobcard_status' => $jobcardStatus,
        ];
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            $rollbackOk = @odbc_rollback($connection);
            $diagnostic['transaction_rolled_back'] = $rollbackOk ? 'YES' : 'UNKNOWN';
        }

        @odbc_autocommit($connection, true);
        erp_m17_capture_failure($diagnostic, $connection, $exception);

        throw $exception;
    }
}

function erp_m17_relation_option_label(array $row): string
{
    $relationId = trim((string)($row['relation_id'] ?? ''));
    $fullName = trim((string)($row['full_name'] ?? ''));
    $brand = trim((string)($row['brand'] ?? ''));
    $model = trim((string)($row['model'] ?? ''));
    $plateNumber = trim((string)($row['plate_number'] ?? ''));
    $vin = trim((string)($row['vin'] ?? ''));
    $vehicleRef = $plateNumber !== '' ? $plateNumber : ($vin !== '' ? $vin : 'no plate/vin');

    return $relationId . ' — ' . $fullName . ' — ' . $brand . ' ' . $model . ' (' . $vehicleRef . ')';
}

try {
    erp_m17_require_first_existing(erp_m17_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m17_require_first_existing(erp_m17_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Create Error</title></head><body>';
    echo '<p>ERP JobCard create page could not be loaded.</p>';
    echo '</body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');

$userId = ERP_M17_PLATFORM_OWNER_ID;
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
$activeRelations = [];

/** @var array<string, mixed> */
$createDiagnostic = [
    'failure_stage' => '',
    'safe_error_code' => 'N/A',
    'safe_error_message' => '',
    'last_successful_step' => '',
    'transaction_rolled_back' => 'UNKNOWN',
];

$formInput = [
    'relation_id' => '',
    'jobcard_status' => 'RECEIVED',
    'reception_at' => '',
    'promised_at' => '',
    'intake_mileage' => '',
    'fuel_level' => '',
    'customer_complaint' => '',
    'requested_services_summary' => '',
    'initial_vehicle_condition' => '',
    'internal_notes' => '',
    'priority_level' => 'NORMAL',
];

$resultData = [
    'jobcard_id' => '',
    'jobcard_number' => '',
    'customer_id' => '',
    'vehicle_id' => '',
    'relation_id' => '',
    'jobcard_status' => '',
];

try {
    erp_auth_context_start();
    m17_csrf_ensure_session();

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

    $guardCreate = erp_m17_guard_eval($connection, $userId, ERP_M17_CREATE_ACTION);
    $guardCreateLabel = (string)($guardCreate['label'] ?? 'FAIL');

    if (!$guardCreate['allowed']) {
        $accessDenied = true;
        throw new RuntimeException('Access denied.');
    }

    $activeRelations = erp_m17_fetch_active_relations($connection);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $createDiagnostic['last_successful_step'] = 'POST_RECEIVED';

        foreach (array_keys($formInput) as $fieldKey) {
            $formInput[$fieldKey] = erp_m17_post_string($fieldKey);
        }

        if ($formInput['jobcard_status'] === '') {
            $formInput['jobcard_status'] = 'RECEIVED';
        }

        if ($formInput['priority_level'] === '') {
            $formInput['priority_level'] = 'NORMAL';
        }

        $postedCsrfToken = trim((string)($_POST['csrf_token'] ?? ''));

        if (!m17_csrf_validate($postedCsrfToken)) {
            erp_m17_set_stage($createDiagnostic, 'POST_FAILED');
            $createDiagnostic['last_successful_step'] = 'POST_RECEIVED';
            $createDiagnostic['safe_error_message'] = 'Invalid CSRF token.';
            $createDiagnostic['transaction_rolled_back'] = 'NO';
            throw new RuntimeException('Invalid CSRF token.');
        }

        $createDiagnostic['last_successful_step'] = 'CSRF_PASSED';

        $validationErrors = erp_m17_validate_form($formInput);

        if ($validationErrors !== []) {
            erp_m17_set_stage($createDiagnostic, 'POST_FAILED');
            $createDiagnostic['last_successful_step'] = 'CSRF_PASSED';
            $createDiagnostic['safe_error_message'] = $validationErrors[0];
            $createDiagnostic['transaction_rolled_back'] = 'NO';
            throw new RuntimeException($validationErrors[0]);
        }

        $relationId = (int)$formInput['relation_id'];
        $relationData = erp_m17_resolve_active_relation($connection, $relationId);

        if ($relationData === null) {
            erp_m17_set_stage($createDiagnostic, 'POST_FAILED');
            $createDiagnostic['last_successful_step'] = 'CSRF_PASSED';
            $createDiagnostic['safe_error_message'] = 'Selected relation is not active or does not exist.';
            $createDiagnostic['transaction_rolled_back'] = 'NO';
            throw new RuntimeException('Selected relation is not active or does not exist.');
        }

        erp_m17_mark_success($createDiagnostic, 'VALIDATION_PASSED');

        $created = erp_m17_create_jobcard($connection, $userId, $formInput, $relationData, $createDiagnostic);

        $resultData = [
            'jobcard_id' => (string)$created['jobcard_id'],
            'jobcard_number' => (string)$created['jobcard_number'],
            'customer_id' => (string)$created['customer_id'],
            'vehicle_id' => (string)$created['vehicle_id'],
            'relation_id' => (string)$created['relation_id'],
            'jobcard_status' => (string)$created['jobcard_status'],
        ];

        $successMessage = 'JobCard record created successfully.';
        $overallOk = true;

        unset($_SESSION[ERP_M17_CSRF_SESSION_KEY]);
        $csrfToken = m17_csrf_get_token();
    } else {
        $csrfToken = m17_csrf_get_token();
    }
} catch (Throwable $exception) {
    if ($accessDenied) {
        $errorMessage = 'Access denied. You do not have permission to perform this action.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ((string)$createDiagnostic['safe_error_message'] !== '') {
            $errorMessage = (string)$createDiagnostic['safe_error_message'];
        } elseif ($errorMessage === '') {
            $errorMessage = 'JobCard create could not be completed.';
        }

        if (
            $connection !== false
            && (string)$createDiagnostic['safe_error_message'] === ''
        ) {
            erp_m17_capture_failure($createDiagnostic, $connection, $exception);
        } elseif ((string)$createDiagnostic['safe_error_message'] === '') {
            $createDiagnostic['safe_error_message'] = m17_safe_throwable_message($exception);
        }

        if ((string)$createDiagnostic['failure_stage'] === '') {
            erp_m17_set_stage($createDiagnostic, 'POST_FAILED');
        }

        if ((string)$createDiagnostic['last_successful_step'] === '') {
            $createDiagnostic['last_successful_step'] = 'POST_RECEIVED';
        }
    } elseif ($errorMessage === '') {
        $errorMessage = 'JobCard create page could not be loaded.';
    }
} finally {
    if ($csrfToken === '' && !$accessDenied && $connectionStatus === 'OK') {
        $csrfToken = m17_csrf_get_token();
    }

    if (
        $userId === ERP_M17_PLATFORM_OWNER_ID
        && $_SERVER['REQUEST_METHOD'] === 'POST'
        && !$overallOk
        && erp_m17_is_localhost_request()
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
    <title>Mission 17 - JobCard Create</title>
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
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .hint { font-size: 0.88rem; color: #4b5563; margin: -8px 0 12px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL CONTROLLED JOBCARD CREATE PROTOTYPE - REMOVE OR PROTECT BEFORE DEPLOYMENT</div>

    <div class="card">
        <h1>JobCard Create</h1>
        <p>Mission 17 - Controlled internal ERP create prototype</p>
        <?php if ($errorMessage !== ''): ?>
            <p class="fail"><?= erp_m17_h($errorMessage) ?></p>
        <?php endif; ?>
        <?php if ($successMessage !== ''): ?>
            <p class="ok"><?= erp_m17_h($successMessage) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($showOwnerDiagnostic): ?>
        <div class="card diag">
            <h2>Local Owner Diagnostic - Safe</h2>
            <table>
                <tbody>
                    <tr><th>failure_stage</th><td><?= erp_m17_h((string)($createDiagnostic['failure_stage'] ?? '')) ?></td></tr>
                    <tr><th>safe_error_code</th><td><?= erp_m17_h((string)($createDiagnostic['safe_error_code'] ?? 'N/A')) ?></td></tr>
                    <tr><th>safe_error_message</th><td><?= erp_m17_h((string)($createDiagnostic['safe_error_message'] ?? '')) ?></td></tr>
                    <tr><th>last_successful_step</th><td><?= erp_m17_h((string)($createDiagnostic['last_successful_step'] ?? '')) ?></td></tr>
                    <tr><th>transaction_rolled_back</th><td><?= erp_m17_h((string)($createDiagnostic['transaction_rolled_back'] ?? 'UNKNOWN')) ?></td></tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Auth and Permission Summary</h2>
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m17_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection status</th><td><?= erp_m17_h($connectionStatus) ?> — <?= erp_m17_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m17_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m17_h($username) ?></td></tr>
                <tr><th>roles</th><td><?= erp_m17_h($rolesText) ?></td></tr>
                <tr><th>permissions count</th><td><?= erp_m17_h((string)$permissionCount) ?></td></tr>
                <tr><th>guard jobcard.create</th><td><?= erp_m17_h($guardCreateLabel) ?></td></tr>
            </tbody>
        </table>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="card">
            <h2>Create Result</h2>
            <table>
                <tbody>
                    <tr><th>Created JobCard ID</th><td><?= erp_m17_h($resultData['jobcard_id']) ?></td></tr>
                    <tr><th>jobcard_number</th><td><?= erp_m17_h($resultData['jobcard_number']) ?></td></tr>
                    <tr><th>customer_id</th><td><?= erp_m17_h($resultData['customer_id']) ?></td></tr>
                    <tr><th>vehicle_id</th><td><?= erp_m17_h($resultData['vehicle_id']) ?></td></tr>
                    <tr><th>relation_id</th><td><?= erp_m17_h($resultData['relation_id']) ?></td></tr>
                    <tr><th>jobcard_status</th><td><?= erp_m17_h($resultData['jobcard_status']) ?></td></tr>
                    <tr><th>Audit/History</th><td class="ok">RECORDED</td></tr>
                    <tr><th>Overall Status</th><td class="ok">OK</td></tr>
                </tbody>
            </table>
            <p><a href="erp-jobcard-readonly-list.php">Open read-only list</a></p>
        </div>
    <?php endif; ?>

    <?php if (!$accessDenied && $connectionStatus === 'OK'): ?>
        <div class="card">
            <h2>Create Form</h2>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= erp_m17_h($csrfToken) ?>">

                <label for="relation_id">relation_id *</label>
                <select id="relation_id" name="relation_id" required>
                    <option value="">Select active customer / vehicle relation</option>
                    <?php foreach ($activeRelations as $relationRow): ?>
                        <?php
                        $optionValue = trim((string)($relationRow['relation_id'] ?? ''));
                        if ($optionValue === '') {
                            continue;
                        }
                        ?>
                        <option value="<?= erp_m17_h($optionValue) ?>" <?= $formInput['relation_id'] === $optionValue ? 'selected' : '' ?>>
                            <?= erp_m17_h(erp_m17_relation_option_label($relationRow)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="grid">
                    <div>
                        <label for="jobcard_status">jobcard_status</label>
                        <select id="jobcard_status" name="jobcard_status">
                            <option value="RECEIVED" <?= $formInput['jobcard_status'] === 'RECEIVED' ? 'selected' : '' ?>>RECEIVED</option>
                            <option value="DRAFT" <?= $formInput['jobcard_status'] === 'DRAFT' ? 'selected' : '' ?>>DRAFT</option>
                        </select>

                        <label for="reception_at">reception_at</label>
                        <input type="datetime-local" id="reception_at" name="reception_at" value="<?= erp_m17_h($formInput['reception_at']) ?>">
                        <p class="hint">Leave empty to use current UTC time from the database.</p>

                        <label for="promised_at">promised_at</label>
                        <input type="datetime-local" id="promised_at" name="promised_at" value="<?= erp_m17_h($formInput['promised_at']) ?>">

                        <label for="intake_mileage">intake_mileage</label>
                        <input type="text" id="intake_mileage" name="intake_mileage" value="<?= erp_m17_h($formInput['intake_mileage']) ?>">

                        <label for="fuel_level">fuel_level</label>
                        <input type="text" id="fuel_level" name="fuel_level" value="<?= erp_m17_h($formInput['fuel_level']) ?>">

                        <label for="priority_level">priority_level</label>
                        <input type="text" id="priority_level" name="priority_level" value="<?= erp_m17_h($formInput['priority_level']) ?>">
                    </div>

                    <div>
                        <label for="customer_complaint">customer_complaint</label>
                        <textarea id="customer_complaint" name="customer_complaint" rows="4"><?= erp_m17_h($formInput['customer_complaint']) ?></textarea>

                        <label for="requested_services_summary">requested_services_summary</label>
                        <textarea id="requested_services_summary" name="requested_services_summary" rows="4"><?= erp_m17_h($formInput['requested_services_summary']) ?></textarea>
                        <p class="hint">Required if customer complaint is empty.</p>

                        <label for="initial_vehicle_condition">initial_vehicle_condition</label>
                        <textarea id="initial_vehicle_condition" name="initial_vehicle_condition" rows="3"><?= erp_m17_h($formInput['initial_vehicle_condition']) ?></textarea>

                        <label for="internal_notes">internal_notes</label>
                        <textarea id="internal_notes" name="internal_notes" rows="3"><?= erp_m17_h($formInput['internal_notes']) ?></textarea>
                    </div>
                </div>

                <button type="submit">Create JobCard</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
