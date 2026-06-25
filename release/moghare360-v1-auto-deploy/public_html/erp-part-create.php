<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Part Controlled Create Prototype
 *
 * Mission 22 - POST create only with Auth, Permission Guard, CSRF, and transaction.
 * Inserts dbo.erp_parts only. No stock movement write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M22_PLATFORM_OWNER_ID = 10001;
const ERP_M22_CSRF_SESSION_KEY = 'm22_part_create_csrf';
const ERP_M22_CREATE_ACTION = 'parts.create';

/** @var array<string, string> */
const ERP_M22_PLACEHOLDER_ACTIONS = [
    'parts.create' => 'placeholder_parts_create',
    'parts.view' => 'placeholder_parts_view',
    'parts.list' => 'placeholder_parts_list',
    'stock.view' => 'placeholder_stock_view',
    'stock.movement.view' => 'placeholder_stock_movement_view',
    'stock.movement.create' => 'placeholder_stock_movement_create',
    'jobcard.part.use' => 'placeholder_jobcard_part_use',
    'purchase.request.create' => 'placeholder_purchase_request_create',
];

function erp_m22_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m22_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function erp_m22_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m22_post_string(string $key): string
{
    if (!isset($_POST[$key])) {
        return '';
    }

    return trim((string)$_POST[$key]);
}

function erp_m22_is_localhost_request(): bool
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

function m22_csrf_ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function m22_csrf_get_token(): string
{
    m22_csrf_ensure_session();

    if (
        !isset($_SESSION[ERP_M22_CSRF_SESSION_KEY])
        || !is_string($_SESSION[ERP_M22_CSRF_SESSION_KEY])
        || $_SESSION[ERP_M22_CSRF_SESSION_KEY] === ''
    ) {
        $_SESSION[ERP_M22_CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[ERP_M22_CSRF_SESSION_KEY];
}

function m22_csrf_validate(string $postedToken): bool
{
    m22_csrf_ensure_session();

    $postedToken = trim($postedToken);

    if ($postedToken === '') {
        return false;
    }

    if (
        !isset($_SESSION[ERP_M22_CSRF_SESSION_KEY])
        || !is_string($_SESSION[ERP_M22_CSRF_SESSION_KEY])
        || $_SESSION[ERP_M22_CSRF_SESSION_KEY] === ''
    ) {
        return false;
    }

    return hash_equals($_SESSION[ERP_M22_CSRF_SESSION_KEY], $postedToken);
}

function erp_m22_execute($connection, string $sql, array $params = [])
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

function m22_fetch_int_value($connection, string $sql, array $params = [], string $columnName = ''): ?int
{
    $statement = erp_m22_execute($connection, $sql, $params);

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

function m22_safe_throwable_message(Throwable $exception): string
{
    $message = preg_replace('/\s+/', ' ', trim($exception->getMessage())) ?? trim($exception->getMessage());
    $message = preg_replace('/[A-Za-z]:\\\\[^\]\s]+/i', '[path-redacted]', $message) ?? $message;

    if ($message === '' || strcasecmp($message, 'Part create could not be completed.') === 0) {
        return 'Create operation failed.';
    }

    if (strlen($message) > 300) {
        return substr($message, 0, 300) . '...';
    }

    return $message;
}

function m22_safe_sql_error_code($connection): string
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

function m22_safe_sql_error_summary($connection): string
{
    $message = (string)@odbc_errormsg($connection);

    if ($message === '') {
        return 'No ODBC error message available.';
    }

    $message = preg_replace('/Driver=\{[^}]+\};[^\]]+/i', '[connection-redacted]', $message) ?? $message;
    $message = preg_replace('/Server=[^;\]\s]+/i', 'Server=[redacted]', $message) ?? $message;
    $message = preg_replace('/Database=[^;\]\s]+/i', 'Database=[redacted]', $message) ?? $message;
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
function erp_m22_set_stage(array &$diagnostic, string $stage): void
{
    $diagnostic['failure_stage'] = $stage;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m22_mark_success(array &$diagnostic, string $stage): void
{
    $diagnostic['last_successful_step'] = $stage;
    $diagnostic['failure_stage'] = $stage;
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m22_capture_sql_error(array &$diagnostic, $connection): void
{
    $diagnostic['safe_error_code'] = m22_safe_sql_error_code($connection);
    $diagnostic['safe_error_message'] = m22_safe_sql_error_summary($connection);
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m22_capture_failure(array &$diagnostic, $connection, ?Throwable $exception = null): void
{
    erp_m22_capture_sql_error($diagnostic, $connection);

    if (
        (string)($diagnostic['safe_error_message'] ?? '') === ''
        || (string)$diagnostic['safe_error_message'] === 'No ODBC error message available.'
    ) {
        if ($exception !== null) {
            $diagnostic['safe_error_message'] = m22_safe_throwable_message($exception);
        }
    }
}

/**
 * @return array<string, mixed>
 */
function erp_m22_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M22_PLACEHOLDER_ACTIONS[$actionKey])) {
        return [
            'allowed' => false,
            'label' => 'FAIL',
            'placeholder' => false,
        ];
    }

    if ($userId === ERP_M22_PLATFORM_OWNER_ID) {
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

function erp_m22_part_code_exists($connection, string $partCode): bool
{
    $count = m22_fetch_int_value(
        $connection,
        'SELECT COUNT(*) AS part_count
         FROM dbo.erp_parts
         WHERE part_code = ?',
        [$partCode],
        'part_count'
    );

    return $count !== null && $count > 0;
}

/**
 * @return array<string, string>
 */
function erp_m22_validate_form(array $input): array
{
    $errors = [];

    if ($input['part_code'] === '') {
        $errors[] = 'Part code is required.';
    }

    if ($input['part_name'] === '') {
        $errors[] = 'Part name is required.';
    }

    if ($input['unit_of_measure'] === '') {
        $errors[] = 'Unit of measure is required.';
    }

    return $errors;
}

/**
 * @param array<string, mixed> $diagnostic
 * @param array<string, string> $input
 * @return array<string, int|string>
 */
function erp_m22_create_part(
    $connection,
    int $userId,
    array $input,
    array &$diagnostic
): array {
    $transactionStarted = false;

    try {
        erp_m22_set_stage($diagnostic, 'TRANSACTION_STARTED');

        if (!@odbc_autocommit($connection, false)) {
            erp_m22_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Part create could not be completed.');
        }

        $transactionStarted = true;
        $diagnostic['transaction_rolled_back'] = 'NO';
        erp_m22_mark_success($diagnostic, 'TRANSACTION_STARTED');

        erp_m22_set_stage($diagnostic, 'DUPLICATE_CHECK_START');
        if (erp_m22_part_code_exists($connection, $input['part_code'])) {
            erp_m22_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Part code already exists.');
        }

        erp_m22_mark_success($diagnostic, 'DUPLICATE_CHECK_DONE');

        erp_m22_set_stage($diagnostic, 'PART_INSERT_START');
        $partStatement = erp_m22_execute(
            $connection,
            'INSERT INTO dbo.erp_parts (
                part_code,
                part_name,
                brand,
                manufacturer,
                oem_number,
                aftermarket_number,
                category,
                unit_of_measure,
                is_active,
                created_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)',
            [
                $input['part_code'],
                $input['part_name'],
                $input['brand'] !== '' ? $input['brand'] : null,
                $input['manufacturer'] !== '' ? $input['manufacturer'] : null,
                $input['oem_number'] !== '' ? $input['oem_number'] : null,
                $input['aftermarket_number'] !== '' ? $input['aftermarket_number'] : null,
                $input['category'] !== '' ? $input['category'] : null,
                $input['unit_of_measure'],
                $userId,
            ]
        );

        if ($partStatement === false) {
            erp_m22_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Part create could not be completed.');
        }

        erp_m22_mark_success($diagnostic, 'PART_INSERT_DONE');

        erp_m22_set_stage($diagnostic, 'PART_ID_FETCH_START');
        $partId = m22_fetch_int_value(
            $connection,
            'SELECT part_id
             FROM dbo.erp_parts
             WHERE part_code = ?',
            [$input['part_code']],
            'part_id'
        );

        if ($partId === null || $partId <= 0) {
            erp_m22_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Part create could not be completed.');
        }

        erp_m22_mark_success($diagnostic, 'PART_ID_FETCH_DONE');

        erp_m22_set_stage($diagnostic, 'TRANSACTION_COMMIT_START');
        if (!@odbc_commit($connection)) {
            erp_m22_capture_sql_error($diagnostic, $connection);
            throw new RuntimeException('Part create could not be completed.');
        }

        @odbc_autocommit($connection, true);
        erp_m22_mark_success($diagnostic, 'TRANSACTION_COMMITTED');
        $diagnostic['transaction_rolled_back'] = 'NO';

        return [
            'part_id' => $partId,
            'part_code' => $input['part_code'],
            'part_name' => $input['part_name'],
            'unit_of_measure' => $input['unit_of_measure'],
        ];
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            $rollbackOk = @odbc_rollback($connection);
            $diagnostic['transaction_rolled_back'] = $rollbackOk ? 'YES' : 'UNKNOWN';
        }

        @odbc_autocommit($connection, true);
        erp_m22_capture_failure($diagnostic, $connection, $exception);

        throw $exception;
    }
}

try {
    erp_m22_require_first_existing(erp_m22_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m22_require_first_existing(erp_m22_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Create Error</title></head><body>';
    echo '<p>ERP Part create page could not be loaded.</p>';
    echo '</body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');

$userId = ERP_M22_PLATFORM_OWNER_ID;
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
    'part_code' => '',
    'part_name' => '',
    'brand' => '',
    'manufacturer' => '',
    'oem_number' => '',
    'aftermarket_number' => '',
    'category' => '',
    'unit_of_measure' => 'PCS',
];

$resultData = [
    'part_id' => '',
    'part_code' => '',
    'part_name' => '',
    'unit_of_measure' => '',
];

try {
    erp_auth_context_start();
    m22_csrf_ensure_session();

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

    $guardCreate = erp_m22_guard_eval($connection, $userId, ERP_M22_CREATE_ACTION);
    $guardCreateLabel = (string)($guardCreate['label'] ?? 'FAIL');

    if (!$guardCreate['allowed']) {
        $accessDenied = true;
        throw new RuntimeException('Access denied.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $createDiagnostic['last_successful_step'] = 'POST_RECEIVED';

        foreach (array_keys($formInput) as $fieldKey) {
            $formInput[$fieldKey] = erp_m22_post_string($fieldKey);
        }

        if ($formInput['unit_of_measure'] === '') {
            $formInput['unit_of_measure'] = 'PCS';
        }

        $postedCsrfToken = trim((string)($_POST['csrf_token'] ?? ''));

        if (!m22_csrf_validate($postedCsrfToken)) {
            erp_m22_set_stage($createDiagnostic, 'POST_FAILED');
            $createDiagnostic['safe_error_message'] = 'Invalid CSRF token.';
            $createDiagnostic['transaction_rolled_back'] = 'NO';
            throw new RuntimeException('Invalid CSRF token.');
        }

        $createDiagnostic['last_successful_step'] = 'CSRF_PASSED';

        $validationErrors = erp_m22_validate_form($formInput);

        if ($validationErrors !== []) {
            erp_m22_set_stage($createDiagnostic, 'POST_FAILED');
            $createDiagnostic['safe_error_message'] = $validationErrors[0];
            $createDiagnostic['transaction_rolled_back'] = 'NO';
            throw new RuntimeException($validationErrors[0]);
        }

        erp_m22_mark_success($createDiagnostic, 'VALIDATION_PASSED');

        $created = erp_m22_create_part($connection, $userId, $formInput, $createDiagnostic);

        $resultData = [
            'part_id' => (string)$created['part_id'],
            'part_code' => (string)$created['part_code'],
            'part_name' => (string)$created['part_name'],
            'unit_of_measure' => (string)$created['unit_of_measure'],
        ];

        $successMessage = 'Part Created OK';
        $overallOk = true;

        unset($_SESSION[ERP_M22_CSRF_SESSION_KEY]);
        $csrfToken = m22_csrf_get_token();
    } else {
        $csrfToken = m22_csrf_get_token();
    }
} catch (Throwable $exception) {
    if ($accessDenied) {
        $errorMessage = 'Access denied. You do not have permission to perform this action.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ((string)$createDiagnostic['safe_error_message'] !== '') {
            $errorMessage = (string)$createDiagnostic['safe_error_message'];
        } elseif ($errorMessage === '') {
            $errorMessage = 'Part create could not be completed.';
        }

        if ($connection !== false && (string)$createDiagnostic['safe_error_message'] === '') {
            erp_m22_capture_failure($createDiagnostic, $connection, $exception);
        } elseif ((string)$createDiagnostic['safe_error_message'] === '') {
            $createDiagnostic['safe_error_message'] = m22_safe_throwable_message($exception);
        }

        if ((string)$createDiagnostic['failure_stage'] === '') {
            erp_m22_set_stage($createDiagnostic, 'POST_FAILED');
        }
    } elseif ($errorMessage === '') {
        $errorMessage = 'Part create page could not be loaded.';
    }
} finally {
    if ($csrfToken === '' && !$accessDenied && $connectionStatus === 'OK') {
        $csrfToken = m22_csrf_get_token();
    }

    if (
        $userId === ERP_M22_PLATFORM_OWNER_ID
        && $_SERVER['REQUEST_METHOD'] === 'POST'
        && !$overallOk
        && erp_m22_is_localhost_request()
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
    <title>Mission 22 - Part Create</title>
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
        input, textarea { width: 100%; box-sizing: border-box; padding: 8px; margin-bottom: 12px; }
        button { background: #1d4ed8; color: #fff; border: 0; padding: 10px 16px; border-radius: 6px; cursor: pointer; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
        .diag { background: #fff7ed; border-color: #fdba74; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL CONTROLLED PART CREATE PROTOTYPE - REMOVE OR PROTECT BEFORE DEPLOYMENT</div>

    <div class="card">
        <h1>Part Create</h1>
        <p>Mission 22 - Controlled internal ERP create prototype (parts master only)</p>
        <?php if ($errorMessage !== ''): ?>
            <p class="fail"><?= erp_m22_h($errorMessage) ?></p>
        <?php endif; ?>
        <?php if ($successMessage !== ''): ?>
            <p class="ok"><?= erp_m22_h($successMessage) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($showOwnerDiagnostic): ?>
        <div class="card diag">
            <h2>Local Owner Diagnostic - Safe</h2>
            <table>
                <tbody>
                    <tr><th>failure_stage</th><td><?= erp_m22_h((string)($createDiagnostic['failure_stage'] ?? '')) ?></td></tr>
                    <tr><th>safe_error_code</th><td><?= erp_m22_h((string)($createDiagnostic['safe_error_code'] ?? 'N/A')) ?></td></tr>
                    <tr><th>safe_error_message</th><td><?= erp_m22_h((string)($createDiagnostic['safe_error_message'] ?? '')) ?></td></tr>
                    <tr><th>last_successful_step</th><td><?= erp_m22_h((string)($createDiagnostic['last_successful_step'] ?? '')) ?></td></tr>
                    <tr><th>transaction_rolled_back</th><td><?= erp_m22_h((string)($createDiagnostic['transaction_rolled_back'] ?? 'UNKNOWN')) ?></td></tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Auth and Permission Summary</h2>
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m22_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection status</th><td><?= erp_m22_h($connectionStatus) ?> — <?= erp_m22_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m22_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m22_h($username) ?></td></tr>
                <tr><th>roles</th><td><?= erp_m22_h($rolesText) ?></td></tr>
                <tr><th>permissions count</th><td><?= erp_m22_h((string)$permissionCount) ?></td></tr>
                <tr><th>guard parts.create</th><td><?= erp_m22_h($guardCreateLabel) ?></td></tr>
            </tbody>
        </table>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="card">
            <h2>Create Result</h2>
            <table>
                <tbody>
                    <tr><th>part_id</th><td><?= erp_m22_h($resultData['part_id']) ?></td></tr>
                    <tr><th>part_code</th><td><?= erp_m22_h($resultData['part_code']) ?></td></tr>
                    <tr><th>part_name</th><td><?= erp_m22_h($resultData['part_name']) ?></td></tr>
                    <tr><th>unit_of_measure</th><td><?= erp_m22_h($resultData['unit_of_measure']) ?></td></tr>
                    <tr><th>Stock Movement</th><td>NOT CREATED (Mission 22 boundary)</td></tr>
                    <tr><th>Overall Status</th><td class="ok">OK</td></tr>
                </tbody>
            </table>
            <p><a href="erp-part-readonly-list.php">Open parts list</a> | <a href="erp-stock-readonly-list.php">Open stock list</a></p>
        </div>
    <?php endif; ?>

    <?php if (!$accessDenied && $connectionStatus === 'OK'): ?>
        <div class="card">
            <h2>Create Form</h2>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= erp_m22_h($csrfToken) ?>">

                <label for="part_code">part_code *</label>
                <input type="text" id="part_code" name="part_code" maxlength="80" required value="<?= erp_m22_h($formInput['part_code']) ?>">

                <label for="part_name">part_name *</label>
                <input type="text" id="part_name" name="part_name" maxlength="200" required value="<?= erp_m22_h($formInput['part_name']) ?>">

                <div class="grid">
                    <div>
                        <label for="brand">brand</label>
                        <input type="text" id="brand" name="brand" maxlength="120" value="<?= erp_m22_h($formInput['brand']) ?>">

                        <label for="manufacturer">manufacturer</label>
                        <input type="text" id="manufacturer" name="manufacturer" maxlength="120" value="<?= erp_m22_h($formInput['manufacturer']) ?>">

                        <label for="category">category</label>
                        <input type="text" id="category" name="category" maxlength="120" value="<?= erp_m22_h($formInput['category']) ?>">

                        <label for="unit_of_measure">unit_of_measure</label>
                        <input type="text" id="unit_of_measure" name="unit_of_measure" maxlength="30" value="<?= erp_m22_h($formInput['unit_of_measure']) ?>">
                    </div>
                    <div>
                        <label for="oem_number">oem_number</label>
                        <input type="text" id="oem_number" name="oem_number" maxlength="120" value="<?= erp_m22_h($formInput['oem_number']) ?>">

                        <label for="aftermarket_number">aftermarket_number</label>
                        <input type="text" id="aftermarket_number" name="aftermarket_number" maxlength="120" value="<?= erp_m22_h($formInput['aftermarket_number']) ?>">
                    </div>
                </div>

                <button type="submit">Create Part</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
