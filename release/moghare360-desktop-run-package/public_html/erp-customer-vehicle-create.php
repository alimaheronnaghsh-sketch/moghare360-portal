<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Customer / Vehicle Controlled Create Prototype
 *
 * Mission 15 - POST create only with Auth, Permission Guard, CSRF, and transaction.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M15_PLATFORM_OWNER_ID = 10001;
const ERP_M15_CSRF_SESSION_KEY = 'm15_customer_vehicle_create_csrf';
const ERP_M15_CREATE_ACTION = 'customer.vehicle.create';

/** @var array<string, string> */
const ERP_M15_PLACEHOLDER_ACTIONS = [
    'customer.vehicle.create' => 'placeholder_customer_vehicle_create',
    'customer.vehicle.view' => 'placeholder_customer_vehicle_view',
];

function erp_m15_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m15_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function erp_m15_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m15_post_string(string $key): string
{
    if (!isset($_POST[$key])) {
        return '';
    }

    return trim((string)$_POST[$key]);
}

function erp_m15_is_localhost_request(): bool
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

function m15_csrf_ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function m15_csrf_get_token(): string
{
    m15_csrf_ensure_session();

    if (
        !isset($_SESSION[ERP_M15_CSRF_SESSION_KEY])
        || !is_string($_SESSION[ERP_M15_CSRF_SESSION_KEY])
        || $_SESSION[ERP_M15_CSRF_SESSION_KEY] === ''
    ) {
        $_SESSION[ERP_M15_CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[ERP_M15_CSRF_SESSION_KEY];
}

function m15_csrf_validate(string $postedToken): bool
{
    m15_csrf_ensure_session();

    $postedToken = trim($postedToken);

    if ($postedToken === '') {
        return false;
    }

    if (
        !isset($_SESSION[ERP_M15_CSRF_SESSION_KEY])
        || !is_string($_SESSION[ERP_M15_CSRF_SESSION_KEY])
        || $_SESSION[ERP_M15_CSRF_SESSION_KEY] === ''
    ) {
        return false;
    }

    return hash_equals($_SESSION[ERP_M15_CSRF_SESSION_KEY], $postedToken);
}

function erp_m15_execute($connection, string $sql, array $params = [])
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

function m15_fetch_int_value($connection, string $sql, array $params = [], string $columnName = ''): ?int
{
    $statement = erp_m15_execute($connection, $sql, $params);

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

function m15_safe_throwable_message(Throwable $exception): string
{
    $message = preg_replace('/\s+/', ' ', trim($exception->getMessage())) ?? trim($exception->getMessage());
    $message = preg_replace('/[A-Za-z]:\\\\[^\]\s]+/i', '[path-redacted]', $message) ?? $message;

    if ($message === '' || strcasecmp($message, 'Customer / vehicle create could not be completed.') === 0) {
        return 'Create operation failed.';
    }

    if (strlen($message) > 300) {
        return substr($message, 0, 300) . '...';
    }

    return $message;
}

function m15_safe_sql_error_code($connection): string
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

function m15_safe_sql_error_summary($connection): string
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
function erp_m15_set_stage(array &$diagnostic, string $stage): void
{
    $diagnostic['failure_stage'] = $stage;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m15_mark_success(array &$diagnostic, string $stage): void
{
    $diagnostic['last_successful_step'] = $stage;
    $diagnostic['failure_stage'] = $stage;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m15_capture_sql_error(array &$diagnostic, $connection): void
{
    $diagnostic['safe_error_code'] = m15_safe_sql_error_code($connection);
    $diagnostic['safe_error_message'] = m15_safe_sql_error_summary($connection);
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m15_capture_failure(array &$diagnostic, $connection, ?Throwable $exception = null): void
{
    erp_m15_capture_sql_error($diagnostic, $connection);

    if (
        (string)($diagnostic['safe_error_message'] ?? '') === ''
        || (string)$diagnostic['safe_error_message'] === 'No ODBC error message available.'
    ) {
        if ($exception !== null) {
            $diagnostic['safe_error_message'] = m15_safe_throwable_message($exception);
        }
    }
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m15_insert_history_row(
    $connection,
    array &$diagnostic,
    string $startStage,
    string $doneStage,
    string $entityType,
    int $entityId,
    string $changeType,
    string $changeSummary,
    int $userId
): void {
    erp_m15_set_stage($diagnostic, $startStage);

    $historyStatement = erp_m15_execute(
        $connection,
        'INSERT INTO dbo.erp_customer_vehicle_change_history (
            entity_type,
            entity_id,
            change_type,
            change_summary,
            changed_by_user_id
        ) VALUES (?, ?, ?, ?, ?)',
        [
            $entityType,
            $entityId,
            $changeType,
            $changeSummary,
            $userId,
        ]
    );

    if ($historyStatement === false) {
        erp_m15_capture_sql_error($diagnostic, $connection);
        throw new RuntimeException('Customer / vehicle create could not be completed.');
    }

    erp_m15_mark_success($diagnostic, $doneStage);
}

function erp_m15_generate_code(string $prefix): string
{
    return $prefix . '-' . date('YmdHis') . '-' . (string)random_int(1000, 9999);
}

/**
 * @return array<string, mixed>
 */
function erp_m15_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M15_PLACEHOLDER_ACTIONS[$actionKey])) {
        return [
            'allowed' => false,
            'label' => 'FAIL',
            'placeholder' => false,
            'action_key' => $actionKey,
            'required_permission' => '',
        ];
    }

    if ($userId === ERP_M15_PLATFORM_OWNER_ID) {
        return [
            'allowed' => true,
            'label' => 'PLACEHOLDER_OWNER_ALLOWED',
            'placeholder' => true,
            'action_key' => $actionKey,
            'required_permission' => ERP_M15_PLACEHOLDER_ACTIONS[$actionKey],
        ];
    }

    return [
        'allowed' => false,
        'label' => 'FAIL',
        'placeholder' => true,
        'action_key' => $actionKey,
        'required_permission' => ERP_M15_PLACEHOLDER_ACTIONS[$actionKey],
    ];
}

/**
 * @return array<string, string>
 */
function erp_m15_validate_form(array $input): array
{
    $errors = [];

    if ($input['full_name'] === '') {
        $errors[] = 'Customer full name is required.';
    }

    if ($input['primary_mobile'] === '') {
        $errors[] = 'Customer primary mobile is required.';
    }

    if ($input['brand'] === '') {
        $errors[] = 'Vehicle brand is required.';
    }

    if ($input['model'] === '') {
        $errors[] = 'Vehicle model is required.';
    }

    if ($input['plate_number'] === '' && $input['vin'] === '') {
        $errors[] = 'At least one of plate number or VIN is required.';
    }

    if ($input['production_year'] !== '' && !ctype_digit($input['production_year'])) {
        $errors[] = 'Production year must be an integer.';
    }

    if ($input['mileage'] !== '' && !ctype_digit($input['mileage'])) {
        $errors[] = 'Mileage must be an integer.';
    }

    if (!in_array($input['customer_type'], ['PERSON', 'COMPANY'], true)) {
        $errors[] = 'Customer type is invalid.';
    }

    return $errors;
}

/**
 * @param array<string, mixed> $diagnostic
 * @param array<string, string> $input
 * @return array<string, int|string>
 */
function erp_m15_create_records($connection, int $userId, array $input, array &$diagnostic): array
{
    $transactionStarted = false;

    try {
        erp_m15_set_stage($diagnostic, 'TRANSACTION_STARTED');

        if (!@odbc_autocommit($connection, false)) {
            erp_m15_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Customer / vehicle create could not be completed.');
        }

        $transactionStarted = true;
        $diagnostic['transaction_rolled_back'] = 'NO';
        erp_m15_mark_success($diagnostic, 'TRANSACTION_STARTED');

        $customerCode = erp_m15_generate_code('M15C');
        $vehicleCode = erp_m15_generate_code('M15V');

        erp_m15_set_stage($diagnostic, 'CUSTOMER_INSERT_START');
        $customerStatement = erp_m15_execute(
            $connection,
            'INSERT INTO dbo.erp_customers (
                customer_code,
                customer_type,
                full_name,
                national_id,
                primary_mobile,
                city,
                address,
                notes,
                lifecycle_state,
                created_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'ACTIVE\', ?)',
            [
                $customerCode,
                $input['customer_type'],
                $input['full_name'],
                $input['national_id'] !== '' ? $input['national_id'] : null,
                $input['primary_mobile'],
                $input['city'] !== '' ? $input['city'] : null,
                $input['address'] !== '' ? $input['address'] : null,
                $input['customer_notes'] !== '' ? $input['customer_notes'] : null,
                $userId,
            ]
        );

        if ($customerStatement === false) {
            erp_m15_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Customer / vehicle create could not be completed.');
        }

        erp_m15_mark_success($diagnostic, 'CUSTOMER_INSERT_DONE');

        erp_m15_set_stage($diagnostic, 'CUSTOMER_ID_FETCH_START');
        $customerId = m15_fetch_int_value(
            $connection,
            'SELECT customer_id
             FROM dbo.erp_customers
             WHERE customer_code = ?',
            [$customerCode],
            'customer_id'
        );

        if ($customerId === null || $customerId <= 0) {
            erp_m15_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Customer / vehicle create could not be completed.');
        }

        erp_m15_mark_success($diagnostic, 'CUSTOMER_ID_FETCH_DONE');

        erp_m15_set_stage($diagnostic, 'PHONE_INSERT_START');
        $phoneStatement = erp_m15_execute(
            $connection,
            'INSERT INTO dbo.erp_customer_phones (
                customer_id,
                phone_type,
                phone_number,
                is_primary,
                lifecycle_state,
                created_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $customerId,
                'MOBILE',
                $input['primary_mobile'],
                1,
                'ACTIVE',
                $userId,
            ]
        );

        if ($phoneStatement === false) {
            erp_m15_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Customer / vehicle create could not be completed.');
        }

        erp_m15_mark_success($diagnostic, 'PHONE_INSERT_DONE');

        erp_m15_set_stage($diagnostic, 'VEHICLE_INSERT_START');
        $vehicleStatement = erp_m15_execute(
            $connection,
            'INSERT INTO dbo.erp_vehicles (
                vehicle_code,
                plate_number,
                vin,
                brand,
                model,
                production_year,
                mileage,
                color,
                notes,
                lifecycle_state,
                created_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'ACTIVE\', ?)',
            [
                $vehicleCode,
                $input['plate_number'] !== '' ? $input['plate_number'] : null,
                $input['vin'] !== '' ? $input['vin'] : null,
                $input['brand'],
                $input['model'],
                $input['production_year'] !== '' ? (int)$input['production_year'] : null,
                $input['mileage'] !== '' ? (int)$input['mileage'] : null,
                $input['color'] !== '' ? $input['color'] : null,
                $input['vehicle_notes'] !== '' ? $input['vehicle_notes'] : null,
                $userId,
            ]
        );

        if ($vehicleStatement === false) {
            erp_m15_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Customer / vehicle create could not be completed.');
        }

        erp_m15_mark_success($diagnostic, 'VEHICLE_INSERT_DONE');

        erp_m15_set_stage($diagnostic, 'VEHICLE_ID_FETCH_START');
        $vehicleId = m15_fetch_int_value(
            $connection,
            'SELECT vehicle_id
             FROM dbo.erp_vehicles
             WHERE vehicle_code = ?',
            [$vehicleCode],
            'vehicle_id'
        );

        if ($vehicleId === null || $vehicleId <= 0) {
            erp_m15_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Customer / vehicle create could not be completed.');
        }

        erp_m15_mark_success($diagnostic, 'VEHICLE_ID_FETCH_DONE');

        erp_m15_set_stage($diagnostic, 'RELATION_INSERT_START');
        $relationStatement = erp_m15_execute(
            $connection,
            'INSERT INTO dbo.erp_customer_vehicle_relations (
                customer_id,
                vehicle_id,
                relation_type,
                is_primary_owner,
                lifecycle_state,
                created_by_user_id
            ) VALUES (?, ?, \'OWNER\', 1, \'ACTIVE\', ?)',
            [
                $customerId,
                $vehicleId,
                $userId,
            ]
        );

        if ($relationStatement === false) {
            erp_m15_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Customer / vehicle create could not be completed.');
        }

        erp_m15_mark_success($diagnostic, 'RELATION_INSERT_DONE');

        erp_m15_set_stage($diagnostic, 'RELATION_ID_FETCH_START');
        $relationId = m15_fetch_int_value(
            $connection,
            'SELECT TOP 1 relation_id
             FROM dbo.erp_customer_vehicle_relations
             WHERE customer_id = ?
               AND vehicle_id = ?
               AND relation_type = \'OWNER\'
               AND lifecycle_state = \'ACTIVE\'
             ORDER BY relation_id DESC',
            [$customerId, $vehicleId],
            'relation_id'
        );

        if ($relationId === null || $relationId <= 0) {
            erp_m15_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Customer / vehicle create could not be completed.');
        }

        erp_m15_mark_success($diagnostic, 'RELATION_ID_FETCH_DONE');

        erp_m15_insert_history_row(
            $connection,
            $diagnostic,
            'HISTORY_CUSTOMER_CREATED_START',
            'HISTORY_CUSTOMER_CREATED_DONE',
            'erp_customers',
            $customerId,
            'CUSTOMER_CREATED',
            'Customer record created via Mission 15 prototype.',
            $userId
        );

        erp_m15_insert_history_row(
            $connection,
            $diagnostic,
            'HISTORY_CUSTOMER_PHONE_CREATED_START',
            'HISTORY_CUSTOMER_PHONE_CREATED_DONE',
            'erp_customer_phones',
            $customerId,
            'CUSTOMER_PHONE_CREATED',
            'Primary customer phone created via Mission 15 prototype.',
            $userId
        );

        erp_m15_insert_history_row(
            $connection,
            $diagnostic,
            'HISTORY_VEHICLE_CREATED_START',
            'HISTORY_VEHICLE_CREATED_DONE',
            'erp_vehicles',
            $vehicleId,
            'VEHICLE_CREATED',
            'Vehicle record created via Mission 15 prototype.',
            $userId
        );

        erp_m15_insert_history_row(
            $connection,
            $diagnostic,
            'HISTORY_CUSTOMER_VEHICLE_RELATION_CREATED_START',
            'HISTORY_CUSTOMER_VEHICLE_RELATION_CREATED_DONE',
            'erp_customer_vehicle_relations',
            $relationId,
            'CUSTOMER_VEHICLE_RELATION_CREATED',
            'Owner relation created via Mission 15 prototype.',
            $userId
        );

        erp_m15_set_stage($diagnostic, 'TRANSACTION_COMMIT_START');
        if (!@odbc_commit($connection)) {
            erp_m15_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Customer / vehicle create could not be completed.');
        }

        @odbc_autocommit($connection, true);
        erp_m15_mark_success($diagnostic, 'TRANSACTION_COMMITTED');
        $diagnostic['transaction_rolled_back'] = 'NO';

        return [
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'relation_id' => $relationId,
            'customer_code' => $customerCode,
            'vehicle_code' => $vehicleCode,
        ];
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            $rollbackOk = @odbc_rollback($connection);
            $diagnostic['transaction_rolled_back'] = $rollbackOk ? 'YES' : 'UNKNOWN';
        }

        @odbc_autocommit($connection, true);
        erp_m15_capture_failure($diagnostic, $connection, $exception);

        throw $exception;
    }
}

try {
    erp_m15_require_first_existing(erp_m15_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m15_require_first_existing(erp_m15_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
    erp_m15_require_first_existing(erp_m15_helper_candidates('erp-csrf.php'), 'erp-csrf.php');
} catch (Throwable $exception) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Create Error</title></head><body>';
    echo '<p>ERP customer / vehicle create page could not be loaded.</p>';
    echo '</body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');

$userId = ERP_M15_PLATFORM_OWNER_ID;
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

/** @var array<string, mixed> */
$createDiagnostic = [
    'failure_stage' => '',
    'safe_error_code' => 'N/A',
    'safe_error_message' => '',
    'last_successful_step' => '',
    'transaction_rolled_back' => 'UNKNOWN',
];

$formInput = [
    'full_name' => '',
    'primary_mobile' => '',
    'national_id' => '',
    'customer_type' => 'PERSON',
    'city' => '',
    'address' => '',
    'customer_notes' => '',
    'brand' => '',
    'model' => '',
    'plate_number' => '',
    'vin' => '',
    'production_year' => '',
    'mileage' => '',
    'color' => '',
    'vehicle_notes' => '',
];

$resultData = [
    'customer_id' => '',
    'vehicle_id' => '',
    'relation_id' => '',
    'customer_code' => '',
    'vehicle_code' => '',
];

try {
    erp_auth_context_start();
    m15_csrf_ensure_session();

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

    $guardCreate = erp_m15_guard_eval($connection, $userId, ERP_M15_CREATE_ACTION);
    $guardCreateLabel = (string)($guardCreate['label'] ?? 'FAIL');

    if (!$guardCreate['allowed']) {
        $accessDenied = true;
        throw new RuntimeException('Access denied.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $createDiagnostic['last_successful_step'] = 'POST_RECEIVED';

        foreach (array_keys($formInput) as $fieldKey) {
            $formInput[$fieldKey] = erp_m15_post_string($fieldKey);
        }

        if ($formInput['customer_type'] === '') {
            $formInput['customer_type'] = 'PERSON';
        }

        $postedCsrfToken = trim((string)($_POST['csrf_token'] ?? ''));

        if (!m15_csrf_validate($postedCsrfToken)) {
            erp_m15_set_stage($createDiagnostic, 'POST_FAILED');
            $createDiagnostic['last_successful_step'] = 'POST_RECEIVED';
            $createDiagnostic['safe_error_message'] = 'Invalid CSRF token.';
            $createDiagnostic['transaction_rolled_back'] = 'NO';
            throw new RuntimeException('Invalid CSRF token.');
        }

        $createDiagnostic['last_successful_step'] = 'CSRF_PASSED';

        $validationErrors = erp_m15_validate_form($formInput);

        if ($validationErrors !== []) {
            erp_m15_set_stage($createDiagnostic, 'VALIDATION_FAILED');
            $createDiagnostic['last_successful_step'] = 'CSRF_PASSED';
            $createDiagnostic['safe_error_message'] = $validationErrors[0];
            $createDiagnostic['transaction_rolled_back'] = 'NO';
            throw new RuntimeException($validationErrors[0]);
        }

        erp_m15_mark_success($createDiagnostic, 'VALIDATION_PASSED');

        $created = erp_m15_create_records($connection, $userId, $formInput, $createDiagnostic);

        $resultData = [
            'customer_id' => (string)$created['customer_id'],
            'vehicle_id' => (string)$created['vehicle_id'],
            'relation_id' => (string)$created['relation_id'],
            'customer_code' => (string)$created['customer_code'],
            'vehicle_code' => (string)$created['vehicle_code'],
        ];

        $successMessage = 'Customer / vehicle records created successfully.';
        $overallOk = true;

        unset($_SESSION[ERP_M15_CSRF_SESSION_KEY]);
        $csrfToken = m15_csrf_get_token();
    } else {
        $csrfToken = m15_csrf_get_token();
    }
} catch (Throwable $exception) {
    if ($accessDenied) {
        $errorMessage = 'Access denied. You do not have permission to perform this action.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ((string)$createDiagnostic['safe_error_message'] !== '') {
            $errorMessage = (string)$createDiagnostic['safe_error_message'];
        } elseif ($errorMessage === '') {
            $errorMessage = 'Customer / vehicle create could not be completed.';
        }

        if (
            $connection !== false
            && (string)$createDiagnostic['safe_error_message'] === ''
        ) {
            erp_m15_capture_failure($createDiagnostic, $connection, $exception);
        } elseif ((string)$createDiagnostic['safe_error_message'] === '') {
            $createDiagnostic['safe_error_message'] = m15_safe_throwable_message($exception);
        }

        if ((string)$createDiagnostic['failure_stage'] === '') {
            erp_m15_set_stage($createDiagnostic, 'POST_FAILED');
        }

        if ((string)$createDiagnostic['last_successful_step'] === '') {
            $createDiagnostic['last_successful_step'] = 'POST_RECEIVED';
        }
    } elseif ($errorMessage === '') {
        $errorMessage = 'Customer / vehicle create page could not be loaded.';
    }
} finally {
    if ($csrfToken === '' && !$accessDenied && $connectionStatus === 'OK') {
        $csrfToken = m15_csrf_get_token();
    }

    if (
        $userId === ERP_M15_PLATFORM_OWNER_ID
        && $_SERVER['REQUEST_METHOD'] === 'POST'
        && !$overallOk
        && erp_m15_is_localhost_request()
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
    <title>Mission 15 - Customer / Vehicle Create</title>
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
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL CONTROLLED CUSTOMER / VEHICLE CREATE PROTOTYPE - REMOVE OR PROTECT BEFORE DEPLOYMENT</div>

    <div class="card">
        <h1>Customer / Vehicle Create</h1>
        <p>Mission 15 - Controlled internal ERP create prototype</p>
        <?php if ($errorMessage !== ''): ?>
            <p class="fail"><?= erp_m15_h($errorMessage) ?></p>
        <?php endif; ?>
        <?php if ($successMessage !== ''): ?>
            <p class="ok"><?= erp_m15_h($successMessage) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($showOwnerDiagnostic): ?>
        <div class="card diag">
            <h2>Local Owner Diagnostic - Safe</h2>
            <table>
                <tbody>
                    <tr><th>failure_stage</th><td><?= erp_m15_h((string)($createDiagnostic['failure_stage'] ?? '')) ?></td></tr>
                    <tr><th>safe_error_code</th><td><?= erp_m15_h((string)($createDiagnostic['safe_error_code'] ?? 'N/A')) ?></td></tr>
                    <tr><th>safe_error_message</th><td><?= erp_m15_h((string)($createDiagnostic['safe_error_message'] ?? '')) ?></td></tr>
                    <tr><th>last_successful_step</th><td><?= erp_m15_h((string)($createDiagnostic['last_successful_step'] ?? '')) ?></td></tr>
                    <tr><th>transaction_rolled_back</th><td><?= erp_m15_h((string)($createDiagnostic['transaction_rolled_back'] ?? 'UNKNOWN')) ?></td></tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Auth and Permission Summary</h2>
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m15_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection status</th><td><?= erp_m15_h($connectionStatus) ?> — <?= erp_m15_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m15_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m15_h($username) ?></td></tr>
                <tr><th>roles</th><td><?= erp_m15_h($rolesText) ?></td></tr>
                <tr><th>permissions count</th><td><?= erp_m15_h((string)$permissionCount) ?></td></tr>
                <tr><th>guard customer.vehicle.create</th><td><?= erp_m15_h($guardCreateLabel) ?></td></tr>
            </tbody>
        </table>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="card">
            <h2>Create Result</h2>
            <table>
                <tbody>
                    <tr><th>Created Customer ID</th><td><?= erp_m15_h($resultData['customer_id']) ?></td></tr>
                    <tr><th>Created Vehicle ID</th><td><?= erp_m15_h($resultData['vehicle_id']) ?></td></tr>
                    <tr><th>Created Relation ID</th><td><?= erp_m15_h($resultData['relation_id']) ?></td></tr>
                    <tr><th>customer_code</th><td><?= erp_m15_h($resultData['customer_code']) ?></td></tr>
                    <tr><th>vehicle_code</th><td><?= erp_m15_h($resultData['vehicle_code']) ?></td></tr>
                    <tr><th>Audit/History</th><td class="ok">RECORDED</td></tr>
                    <tr><th>Overall Status</th><td class="ok">OK</td></tr>
                </tbody>
            </table>
            <p><a href="erp-customer-vehicle-readonly-list.php">Open read-only list</a></p>
        </div>
    <?php endif; ?>

    <?php if (!$accessDenied && $connectionStatus === 'OK'): ?>
        <div class="card">
            <h2>Create Form</h2>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= erp_m15_h($csrfToken) ?>">

                <div class="grid">
                    <div>
                        <h2>Customer</h2>
                        <label for="full_name">full_name *</label>
                        <input type="text" id="full_name" name="full_name" value="<?= erp_m15_h($formInput['full_name']) ?>" required>

                        <label for="primary_mobile">primary_mobile *</label>
                        <input type="text" id="primary_mobile" name="primary_mobile" value="<?= erp_m15_h($formInput['primary_mobile']) ?>" required>

                        <label for="national_id">national_id</label>
                        <input type="text" id="national_id" name="national_id" value="<?= erp_m15_h($formInput['national_id']) ?>">

                        <label for="customer_type">customer_type</label>
                        <select id="customer_type" name="customer_type">
                            <option value="PERSON" <?= $formInput['customer_type'] === 'PERSON' ? 'selected' : '' ?>>PERSON</option>
                            <option value="COMPANY" <?= $formInput['customer_type'] === 'COMPANY' ? 'selected' : '' ?>>COMPANY</option>
                        </select>

                        <label for="city">city</label>
                        <input type="text" id="city" name="city" value="<?= erp_m15_h($formInput['city']) ?>">

                        <label for="address">address</label>
                        <input type="text" id="address" name="address" value="<?= erp_m15_h($formInput['address']) ?>">

                        <label for="customer_notes">customer_notes</label>
                        <textarea id="customer_notes" name="customer_notes" rows="3"><?= erp_m15_h($formInput['customer_notes']) ?></textarea>
                    </div>

                    <div>
                        <h2>Vehicle</h2>
                        <label for="brand">brand *</label>
                        <input type="text" id="brand" name="brand" value="<?= erp_m15_h($formInput['brand']) ?>" required>

                        <label for="model">model *</label>
                        <input type="text" id="model" name="model" value="<?= erp_m15_h($formInput['model']) ?>" required>

                        <label for="plate_number">plate_number</label>
                        <input type="text" id="plate_number" name="plate_number" value="<?= erp_m15_h($formInput['plate_number']) ?>">

                        <label for="vin">vin</label>
                        <input type="text" id="vin" name="vin" value="<?= erp_m15_h($formInput['vin']) ?>">

                        <label for="production_year">production_year</label>
                        <input type="text" id="production_year" name="production_year" value="<?= erp_m15_h($formInput['production_year']) ?>">

                        <label for="mileage">mileage</label>
                        <input type="text" id="mileage" name="mileage" value="<?= erp_m15_h($formInput['mileage']) ?>">

                        <label for="color">color</label>
                        <input type="text" id="color" name="color" value="<?= erp_m15_h($formInput['color']) ?>">

                        <label for="vehicle_notes">vehicle_notes</label>
                        <textarea id="vehicle_notes" name="vehicle_notes" rows="3"><?= erp_m15_h($formInput['vehicle_notes']) ?></textarea>
                    </div>
                </div>

                <button type="submit">Create Customer / Vehicle</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
