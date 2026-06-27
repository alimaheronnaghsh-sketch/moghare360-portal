<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Payment Controlled Create Prototype
 *
 * Mission 28 - POST create only with Auth, Permission Guard, CSRF, and transaction.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M28_PLATFORM_OWNER_ID = 10001;
const ERP_M28_CSRF_SESSION_KEY = 'm28_payment_create_csrf';
const ERP_M28_CREATE_ACTION = 'payment.create';
const ERP_M28_INITIAL_STATUS = 'RECEIVED';
const ERP_M28_DEFAULT_CURRENCY = 'IRR';

/** @var array<string, string> */
const ERP_M28_PLACEHOLDER_ACTIONS = [
    'payment.create' => 'placeholder_payment_create',
    'payment.view' => 'placeholder_payment_view',
    'payment.list' => 'placeholder_payment_list',
    'payment.summary.view' => 'placeholder_payment_summary_view',
    'payment.cancel' => 'placeholder_payment_cancel',
    'payment.reverse' => 'placeholder_payment_reverse',
];

/** @var list<string> */
const ERP_M28_ALLOWED_PAYMENT_TYPES = ['ADVANCE', 'PARTIAL', 'FULL'];

/** @var list<string> */
const ERP_M28_ALLOWED_PAYMENT_METHODS = ['CASH', 'CARD', 'BANK_TRANSFER', 'POS', 'OTHER'];

function erp_m28_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m28_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function erp_m28_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m28_post_string(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

function m28_csrf_ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function m28_csrf_get_token(): string
{
    m28_csrf_ensure_session();

    if (empty($_SESSION[ERP_M28_CSRF_SESSION_KEY]) || !is_string($_SESSION[ERP_M28_CSRF_SESSION_KEY])) {
        $_SESSION[ERP_M28_CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[ERP_M28_CSRF_SESSION_KEY];
}

function m28_csrf_validate(string $postedToken): bool
{
    m28_csrf_ensure_session();
    $postedToken = trim($postedToken);

    return $postedToken !== ''
        && isset($_SESSION[ERP_M28_CSRF_SESSION_KEY])
        && is_string($_SESSION[ERP_M28_CSRF_SESSION_KEY])
        && hash_equals($_SESSION[ERP_M28_CSRF_SESSION_KEY], $postedToken);
}

function erp_m28_execute($connection, string $sql, array $params = [])
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return false;
    }

    return $statement;
}

function m28_fetch_int_value($connection, string $sql, array $params = [], string $columnName = ''): ?int
{
    $statement = erp_m28_execute($connection, $sql, $params);

    if ($statement === false || @odbc_fetch_row($statement) !== true) {
        return null;
    }

    $value = $columnName !== '' ? @odbc_result($statement, $columnName) : @odbc_result($statement, 1);

    return ($value !== false && $value !== null && is_numeric($value)) ? (int)$value : null;
}

/**
 * @return list<array<string, string>>
 */
function m28_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = erp_m28_execute($connection, $sql, $params);

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

function m28_safe_sql_error_summary($connection): string
{
    $message = (string)@odbc_errormsg($connection);

    if ($message === '') {
        return 'No ODBC error message available.';
    }

    $message = preg_replace('/\s+/', ' ', trim($message)) ?? trim($message);

    return strlen($message) > 300 ? substr($message, 0, 300) . '...' : $message;
}

/**
 * @return array<string, mixed>
 */
function erp_m28_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M28_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === ERP_M28_PLATFORM_OWNER_ID) {
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
function erp_m28_fetch_active_jobcards($connection): array
{
    return m28_fetch_rows(
        $connection,
        'SELECT j.jobcard_id, j.jobcard_number, j.jobcard_status, j.lifecycle_state, j.customer_id
         FROM dbo.erp_jobcards j
         WHERE j.lifecycle_state = \'ACTIVE\'
         ORDER BY j.jobcard_id ASC'
    );
}

/**
 * @return array{jobcard_id: int, customer_id: int|null}|null
 */
function erp_m28_resolve_active_jobcard($connection, int $jobcardId): ?array
{
    if ($jobcardId <= 0) {
        return null;
    }

    $rows = m28_fetch_rows(
        $connection,
        'SELECT TOP 1 jobcard_id, customer_id
         FROM dbo.erp_jobcards
         WHERE jobcard_id = ?
           AND lifecycle_state = \'ACTIVE\'',
        [$jobcardId]
    );

    if ($rows === []) {
        return null;
    }

    $resolvedId = (int)($rows[0]['jobcard_id'] ?? 0);
    $customerRaw = trim((string)($rows[0]['customer_id'] ?? ''));

    return [
        'jobcard_id' => $resolvedId,
        'customer_id' => ($customerRaw !== '' && ctype_digit($customerRaw)) ? (int)$customerRaw : null,
    ];
}

/**
 * @return array<string, string>
 */
function erp_m28_validate_form(array $input): array
{
    $errors = [];

    if ($input['jobcard_id'] === '' || !ctype_digit($input['jobcard_id'])) {
        $errors[] = 'JobCard ID is required.';
    }

    if (!in_array($input['payment_type'], ERP_M28_ALLOWED_PAYMENT_TYPES, true)) {
        $errors[] = 'Payment type must be ADVANCE, PARTIAL, or FULL.';
    }

    if (!in_array($input['payment_method'], ERP_M28_ALLOWED_PAYMENT_METHODS, true)) {
        $errors[] = 'Payment method must be CASH, CARD, BANK_TRANSFER, POS, or OTHER.';
    }

    if ($input['payment_amount'] === '' || !is_numeric($input['payment_amount'])) {
        $errors[] = 'Payment amount must be a positive number.';
    } elseif ((float)$input['payment_amount'] <= 0) {
        $errors[] = 'Payment amount must be greater than zero.';
    }

    if ($input['currency_code'] === '') {
        $errors[] = 'Currency code is required.';
    }

    return $errors;
}

/**
 * @param array<string, mixed> $diagnostic
 * @return array<string, int|string>
 */
function erp_m28_create_payment(
    $connection,
    int $userId,
    int $jobcardId,
    ?int $customerId,
    array $input,
    array &$diagnostic
): array {
    $transactionStarted = false;
    $paymentStatus = ERP_M28_INITIAL_STATUS;
    $paymentAmount = (float)$input['payment_amount'];

    try {
        $diagnostic['failure_stage'] = 'TRANSACTION_STARTED';

        if (!@odbc_autocommit($connection, false)) {
            $diagnostic['safe_error_message'] = m28_safe_sql_error_summary($connection);
            throw new RuntimeException('Payment create could not be completed.');
        }

        $transactionStarted = true;
        $diagnostic['transaction_rolled_back'] = 'NO';
        $diagnostic['last_successful_step'] = 'TRANSACTION_STARTED';

        $paymentReference = $input['payment_reference'] !== '' ? $input['payment_reference'] : null;
        $paymentNote = $input['payment_note'] !== '' ? $input['payment_note'] : null;

        $diagnostic['failure_stage'] = 'PAYMENT_INSERT_START';
        $insertStatement = erp_m28_execute(
            $connection,
            'INSERT INTO dbo.erp_payments (
                jobcard_id,
                customer_id,
                payment_type,
                payment_method,
                payment_amount,
                currency_code,
                payment_status,
                payment_reference,
                payment_note,
                received_by_user_id,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)',
            [
                $jobcardId,
                $customerId,
                $input['payment_type'],
                $input['payment_method'],
                $paymentAmount,
                $input['currency_code'],
                $paymentStatus,
                $paymentReference,
                $paymentNote,
                $userId,
            ]
        );

        if ($insertStatement === false) {
            $diagnostic['safe_error_message'] = m28_safe_sql_error_summary($connection);
            throw new RuntimeException('Payment create could not be completed.');
        }

        $diagnostic['last_successful_step'] = 'PAYMENT_INSERT_DONE';
        $diagnostic['failure_stage'] = 'PAYMENT_ID_FETCH_START';

        $paymentId = m28_fetch_int_value(
            $connection,
            'SELECT TOP 1 payment_id
             FROM dbo.erp_payments
             WHERE jobcard_id = ?
               AND payment_type = ?
               AND payment_method = ?
               AND payment_amount = ?
               AND payment_status = ?
               AND received_by_user_id = ?
             ORDER BY payment_id DESC',
            [
                $jobcardId,
                $input['payment_type'],
                $input['payment_method'],
                $paymentAmount,
                $paymentStatus,
                $userId,
            ],
            'payment_id'
        );

        if ($paymentId === null || $paymentId <= 0) {
            $diagnostic['safe_error_message'] = m28_safe_sql_error_summary($connection);
            throw new RuntimeException('Payment create could not be completed.');
        }

        $diagnostic['last_successful_step'] = 'PAYMENT_ID_FETCH_DONE';
        $diagnostic['failure_stage'] = 'HISTORY_INSERT_START';

        $historyStatement = erp_m28_execute(
            $connection,
            'INSERT INTO dbo.erp_payment_history (
                payment_id,
                jobcard_id,
                action_code,
                old_status,
                new_status,
                changed_by_user_id,
                change_note
            ) VALUES (?, ?, ?, NULL, ?, ?, ?)',
            [
                $paymentId,
                $jobcardId,
                'PAYMENT_RECEIVED',
                $paymentStatus,
                $userId,
                'Payment recorded via Mission 28 prototype.',
            ]
        );

        if ($historyStatement === false) {
            $diagnostic['safe_error_message'] = m28_safe_sql_error_summary($connection);
            throw new RuntimeException('Payment create could not be completed.');
        }

        $diagnostic['last_successful_step'] = 'HISTORY_INSERT_DONE';
        $diagnostic['failure_stage'] = 'TRANSACTION_COMMIT_START';

        if (!@odbc_commit($connection)) {
            $diagnostic['safe_error_message'] = m28_safe_sql_error_summary($connection);
            throw new RuntimeException('Payment create could not be completed.');
        }

        @odbc_autocommit($connection, true);
        $diagnostic['last_successful_step'] = 'TRANSACTION_COMMITTED';

        return [
            'payment_id' => $paymentId,
            'jobcard_id' => $jobcardId,
            'payment_amount' => (string)$paymentAmount,
            'payment_status' => $paymentStatus,
        ];
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            @odbc_rollback($connection);
            $diagnostic['transaction_rolled_back'] = 'YES';
        }

        @odbc_autocommit($connection, true);

        if (($diagnostic['safe_error_message'] ?? '') === '') {
            $diagnostic['safe_error_message'] = trim($exception->getMessage());
        }

        throw $exception;
    }
}

try {
    erp_m28_require_first_existing(erp_m28_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m28_require_first_existing(erp_m28_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Create Error</title></head><body>';
    echo '<p>ERP Payment create page could not be loaded.</p></body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');
$userId = ERP_M28_PLATFORM_OWNER_ID;
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
    'payment_type' => 'ADVANCE',
    'payment_method' => 'CASH',
    'payment_amount' => '',
    'currency_code' => ERP_M28_DEFAULT_CURRENCY,
    'payment_reference' => '',
    'payment_note' => '',
];

$resultData = [
    'payment_id' => '',
    'jobcard_id' => '',
    'payment_amount' => '',
    'payment_status' => '',
];

try {
    erp_auth_context_start();
    m28_csrf_ensure_session();

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

    $guardCreate = erp_m28_guard_eval($connection, $userId, ERP_M28_CREATE_ACTION);
    $guardCreateLabel = (string)($guardCreate['label'] ?? 'FAIL');

    if (empty($guardCreate['allowed'])) {
        throw new RuntimeException('Access denied.');
    }

    $activeJobcards = erp_m28_fetch_active_jobcards($connection);
    $csrfToken = m28_csrf_get_token();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach (array_keys($formInput) as $fieldKey) {
            $formInput[$fieldKey] = erp_m28_post_string($fieldKey);
        }

        if ($formInput['currency_code'] === '') {
            $formInput['currency_code'] = ERP_M28_DEFAULT_CURRENCY;
        }

        if (!m28_csrf_validate(trim((string)($_POST['csrf_token'] ?? '')))) {
            throw new RuntimeException('Invalid CSRF token.');
        }

        $validationErrors = erp_m28_validate_form($formInput);

        if ($validationErrors !== []) {
            throw new RuntimeException($validationErrors[0]);
        }

        $resolvedJobcard = erp_m28_resolve_active_jobcard($connection, (int)$formInput['jobcard_id']);

        if ($resolvedJobcard === null) {
            throw new RuntimeException('Selected JobCard is not active or does not exist.');
        }

        /** @var array<string, mixed> */
        $createDiagnostic = [];

        $created = erp_m28_create_payment(
            $connection,
            $userId,
            $resolvedJobcard['jobcard_id'],
            $resolvedJobcard['customer_id'],
            $formInput,
            $createDiagnostic
        );

        $resultData = [
            'payment_id' => (string)$created['payment_id'],
            'jobcard_id' => (string)$created['jobcard_id'],
            'payment_amount' => (string)$created['payment_amount'],
            'payment_status' => (string)$created['payment_status'],
        ];

        $successMessage = 'Payment Created OK';
    }

    $overallOk = $connectionStatus === 'OK'
        && in_array($guardCreateLabel, ['OK', 'PLACEHOLDER', 'PLACEHOLDER_OWNER_ALLOWED'], true);
} catch (Throwable $exception) {
    $errorMessage = trim($exception->getMessage()) !== '' ? $exception->getMessage() : 'Payment create could not be completed.';
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
    <title>Mission 28 - Payment Create</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; line-height: 1.5; }
        .wrap { max-width: 900px; margin: 0 auto; padding: 24px; }
        .banner { background: #14532d; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        h1, h2 { margin: 0 0 12px; }
        label { display: block; margin-bottom: 4px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; margin-bottom: 12px; box-sizing: border-box; }
        button { background: #14532d; color: #fff; border: 0; padding: 10px 16px; border-radius: 6px; cursor: pointer; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
        th { background: #f3f4f6; width: 240px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL PAYMENT CREATE - MISSION 28 PROTOTYPE</div>

    <div class="card">
        <h1>Payment Create</h1>
        <p>Mission 28 - Auth + CSRF + Permission Guard + Transaction (status RECEIVED only)</p>
        <p>
            <a href="erp-payment-readonly-list.php">Open read-only list</a>
            |
            <a href="erp-jobcard-payment-summary.php">Open JobCard payment summary</a>
        </p>
        <?php if ($successMessage !== ''): ?>
            <p class="ok"><?= erp_m28_h($successMessage) ?></p>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <p class="fail"><?= erp_m28_h($errorMessage) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Summary</h2>
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m28_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection</th><td><?= erp_m28_h($connectionStatus) ?> — <?= erp_m28_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m28_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m28_h($username) ?></td></tr>
                <tr><th>guard payment.create</th><td><?= erp_m28_h($guardCreateLabel) ?></td></tr>
                <tr><th>Initial payment_status</th><td><?= erp_m28_h(ERP_M28_INITIAL_STATUS) ?></td></tr>
                <tr><th>Overall Status</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
            </tbody>
        </table>
    </div>

    <?php if ($resultData['payment_id'] !== ''): ?>
        <div class="card">
            <h2>Created Payment</h2>
            <table>
                <tbody>
                    <tr><th>Payment ID</th><td><?= erp_m28_h($resultData['payment_id']) ?></td></tr>
                    <tr><th>JobCard ID</th><td><?= erp_m28_h($resultData['jobcard_id']) ?></td></tr>
                    <tr><th>Payment Amount</th><td><?= erp_m28_h($resultData['payment_amount']) ?></td></tr>
                    <tr><th>Payment Status</th><td><?= erp_m28_h($resultData['payment_status']) ?></td></tr>
                </tbody>
            </table>
            <p><a href="erp-jobcard-payment-summary.php?jobcard_id=<?= erp_m28_h($resultData['jobcard_id']) ?>">Open JobCard summary</a></p>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Create Form</h2>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= erp_m28_h($csrfToken) ?>">

            <label for="jobcard_id">JobCard ID (required)</label>
            <select id="jobcard_id" name="jobcard_id" required>
                <?php if ($activeJobcards === []): ?>
                    <option value="<?= erp_m28_h($formInput['jobcard_id']) ?>"><?= erp_m28_h($formInput['jobcard_id']) ?></option>
                <?php else: ?>
                    <?php foreach ($activeJobcards as $row): ?>
                        <?php $jcId = (string)($row['jobcard_id'] ?? ''); ?>
                        <option value="<?= erp_m28_h($jcId) ?>"<?= $formInput['jobcard_id'] === $jcId ? ' selected' : '' ?>>
                            <?= erp_m28_h($jcId . ' — ' . ($row['jobcard_number'] ?? '') . ' [' . ($row['jobcard_status'] ?? '') . ']') ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <label for="payment_type">Payment Type (required)</label>
            <select id="payment_type" name="payment_type" required>
                <?php foreach (ERP_M28_ALLOWED_PAYMENT_TYPES as $type): ?>
                    <option value="<?= erp_m28_h($type) ?>"<?= $formInput['payment_type'] === $type ? ' selected' : '' ?>><?= erp_m28_h($type) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="payment_method">Payment Method (required)</label>
            <select id="payment_method" name="payment_method" required>
                <?php foreach (ERP_M28_ALLOWED_PAYMENT_METHODS as $method): ?>
                    <option value="<?= erp_m28_h($method) ?>"<?= $formInput['payment_method'] === $method ? ' selected' : '' ?>><?= erp_m28_h($method) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="payment_amount">Payment Amount (required)</label>
            <input type="text" id="payment_amount" name="payment_amount" required value="<?= erp_m28_h($formInput['payment_amount']) ?>">

            <label for="currency_code">Currency Code</label>
            <input type="text" id="currency_code" name="currency_code" value="<?= erp_m28_h($formInput['currency_code']) ?>">

            <label for="payment_reference">Payment Reference (optional)</label>
            <input type="text" id="payment_reference" name="payment_reference" value="<?= erp_m28_h($formInput['payment_reference']) ?>">

            <label for="payment_note">Payment Note (optional)</label>
            <textarea id="payment_note" name="payment_note" rows="3"><?= erp_m28_h($formInput['payment_note']) ?></textarea>

            <button type="submit">Create Payment</button>
        </form>
    </div>
</div>
</body>
</html>
