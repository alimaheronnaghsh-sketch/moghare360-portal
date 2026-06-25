<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Delivery Control — Read + Controlled Release
 *
 * Mission 30 - View delivery control; POST release when READY and allowed.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M30_PLATFORM_OWNER_ID = 10001;
const ERP_M30_CSRF_SESSION_KEY = 'm30_delivery_control_csrf';
const ERP_M30_VIEW_ACTION = 'delivery.control.view';
const ERP_M30_RELEASE_ACTION = 'delivery.control.release';

/** @var array<string, string> */
const ERP_M30_PLACEHOLDER_ACTIONS = [
    'qc.check.create' => 'placeholder_qc_check_create',
    'delivery.control.view' => 'placeholder_delivery_control_view',
    'delivery.control.release' => 'placeholder_delivery_control_release',
    'soft.run.readiness.view' => 'placeholder_soft_run_readiness_view',
];

function erp_m30_dc_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m30_dc_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

function erp_m30_dc_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m30_dc_display(string $value): string
{
    return erp_m30_dc_h(trim($value) === '' ? '—' : $value);
}

function erp_m30_dc_post_string(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

function m30_dc_csrf_ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function m30_dc_csrf_get_token(): string
{
    m30_dc_csrf_ensure_session();

    if (empty($_SESSION[ERP_M30_CSRF_SESSION_KEY]) || !is_string($_SESSION[ERP_M30_CSRF_SESSION_KEY])) {
        $_SESSION[ERP_M30_CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[ERP_M30_CSRF_SESSION_KEY];
}

function m30_dc_csrf_validate(string $postedToken): bool
{
    m30_dc_csrf_ensure_session();
    $postedToken = trim($postedToken);

    return $postedToken !== ''
        && isset($_SESSION[ERP_M30_CSRF_SESSION_KEY])
        && is_string($_SESSION[ERP_M30_CSRF_SESSION_KEY])
        && hash_equals($_SESSION[ERP_M30_CSRF_SESSION_KEY], $postedToken);
}

function erp_m30_dc_execute($connection, string $sql, array $params = [])
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return false;
    }

    return $statement;
}

/**
 * @return list<array<string, string>>
 */
function erp_m30_dc_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = erp_m30_dc_execute($connection, $sql, $params);

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

function m30_dc_safe_sql_error($connection): string
{
    $message = (string)@odbc_errormsg($connection);

    return $message === '' ? 'No ODBC error message available.' : $message;
}

/**
 * @return array<string, mixed>
 */
function erp_m30_dc_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M30_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === ERP_M30_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

function erp_m30_dc_parse_jobcard_id(): int
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = erp_m30_dc_post_string('jobcard_id');

        if ($raw !== '' && ctype_digit($raw)) {
            return (int)$raw;
        }
    }

    if (!isset($_GET['jobcard_id'])) {
        return 1;
    }

    $raw = trim((string)$_GET['jobcard_id']);

    return ($raw !== '' && ctype_digit($raw)) ? (int)$raw : 1;
}

function erp_m30_dc_resolve_active_jobcard($connection, int $jobcardId): ?int
{
    $rows = erp_m30_dc_fetch_rows(
        $connection,
        'SELECT TOP 1 jobcard_id FROM dbo.erp_jobcards WHERE jobcard_id = ? AND lifecycle_state = \'ACTIVE\'',
        [$jobcardId]
    );

    return $rows === [] ? null : (int)($rows[0]['jobcard_id'] ?? 0);
}

/**
 * @param array<string, mixed> $diagnostic
 */
function erp_m30_dc_release_delivery($connection, int $userId, int $deliveryControlId, int $jobcardId, string $oldStatus, array &$diagnostic): void
{
    $transactionStarted = false;

    try {
        if (!@odbc_autocommit($connection, false)) {
            $diagnostic['safe_error_message'] = m30_dc_safe_sql_error($connection);
            throw new RuntimeException('Delivery release could not be completed.');
        }

        $transactionStarted = true;

        if (erp_m30_dc_execute(
            $connection,
            'UPDATE dbo.erp_delivery_controls
             SET delivery_status = ?, released_by_user_id = ?, released_at = SYSUTCDATETIME()
             WHERE delivery_control_id = ? AND jobcard_id = ? AND delivery_status = ? AND delivery_allowed = 1',
            ['RELEASED', $userId, $deliveryControlId, $jobcardId, 'READY']
        ) === false) {
            $diagnostic['safe_error_message'] = m30_dc_safe_sql_error($connection);
            throw new RuntimeException('Delivery release could not be completed.');
        }

        if (erp_m30_dc_execute(
            $connection,
            'INSERT INTO dbo.erp_delivery_control_history (
                delivery_control_id, jobcard_id, action_code, old_status, new_status, changed_by_user_id, change_note
            ) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $deliveryControlId,
                $jobcardId,
                'DELIVERY_RELEASED',
                $oldStatus,
                'RELEASED',
                $userId,
                'Delivery released via Mission 30 prototype.',
            ]
        ) === false) {
            $diagnostic['safe_error_message'] = m30_dc_safe_sql_error($connection);
            throw new RuntimeException('Delivery release could not be completed.');
        }

        if (!@odbc_commit($connection)) {
            $diagnostic['safe_error_message'] = m30_dc_safe_sql_error($connection);
            throw new RuntimeException('Delivery release could not be completed.');
        }

        @odbc_autocommit($connection, true);
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            @odbc_rollback($connection);
        }

        @odbc_autocommit($connection, true);

        throw $exception;
    }
}

try {
    erp_m30_dc_require_first_existing(erp_m30_dc_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m30_dc_require_first_existing(erp_m30_dc_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Delivery Error</title></head><body><p>Delivery control page could not be loaded.</p></body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$userId = ERP_M30_PLATFORM_OWNER_ID;
$guardViewLabel = 'FAIL';
$guardReleaseLabel = 'FAIL';
$errorMessage = '';
$successMessage = '';
$deliveryRow = [];
$historyRows = [];
$selectedJobcardId = 1;
$canRelease = false;
$csrfToken = '';
$connection = false;

try {
    erp_auth_context_start();
    m30_dc_csrf_ensure_session();

    $connection = erp_auth_create_local_odbc_connection();

    if (erp_auth_current_user_id() !== $userId) {
        throw new RuntimeException('Access denied.');
    }

    if (erp_auth_load_current_user($connection) === null) {
        throw new RuntimeException('Access denied.');
    }

    $guardView = erp_m30_dc_guard_eval($connection, $userId, ERP_M30_VIEW_ACTION);
    $guardRelease = erp_m30_dc_guard_eval($connection, $userId, ERP_M30_RELEASE_ACTION);
    $guardViewLabel = (string)($guardView['label'] ?? 'FAIL');
    $guardReleaseLabel = (string)($guardRelease['label'] ?? 'FAIL');

    if (empty($guardView['allowed'])) {
        throw new RuntimeException('Access denied.');
    }

    $selectedJobcardId = erp_m30_dc_parse_jobcard_id();
    $resolvedJobcardId = erp_m30_dc_resolve_active_jobcard($connection, $selectedJobcardId);

    if ($resolvedJobcardId === null) {
        throw new RuntimeException('JobCard is not active or does not exist.');
    }

    $selectedJobcardId = $resolvedJobcardId;
    $csrfToken = m30_dc_csrf_get_token();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && erp_m30_dc_post_string('action') === 'release') {
        if (empty($guardRelease['allowed'])) {
            throw new RuntimeException('Access denied.');
        }

        if (!m30_dc_csrf_validate(trim((string)($_POST['csrf_token'] ?? '')))) {
            throw new RuntimeException('Invalid CSRF token.');
        }

        $deliveryRows = erp_m30_dc_fetch_rows(
            $connection,
            'SELECT TOP 1 delivery_control_id, delivery_status, delivery_allowed
             FROM dbo.erp_delivery_controls
             WHERE jobcard_id = ? AND is_active = 1
             ORDER BY delivery_control_id DESC',
            [$selectedJobcardId]
        );

        if ($deliveryRows === []) {
            throw new RuntimeException('No delivery control record found.');
        }

        $deliveryControlId = (int)($deliveryRows[0]['delivery_control_id'] ?? 0);
        $deliveryStatus = strtoupper(trim((string)($deliveryRows[0]['delivery_status'] ?? '')));
        $deliveryAllowed = (int)($deliveryRows[0]['delivery_allowed'] ?? 0);

        if ($deliveryAllowed !== 1 || $deliveryStatus !== 'READY') {
            throw new RuntimeException('Delivery release is not allowed for current status.');
        }

        /** @var array<string, mixed> */
        $releaseDiagnostic = [];

        erp_m30_dc_release_delivery($connection, $userId, $deliveryControlId, $selectedJobcardId, $deliveryStatus, $releaseDiagnostic);
        $successMessage = 'Delivery Released OK';
    }

    $deliveryRows = erp_m30_dc_fetch_rows(
        $connection,
        'SELECT TOP 1 *
         FROM dbo.erp_delivery_controls
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY delivery_control_id DESC',
        [$selectedJobcardId]
    );

    if ($deliveryRows !== []) {
        $deliveryRow = $deliveryRows[0];
        $deliveryControlId = (int)($deliveryRow['delivery_control_id'] ?? 0);

        $historyRows = erp_m30_dc_fetch_rows(
            $connection,
            'SELECT history_id, action_code, old_status, new_status, changed_by_user_id, changed_at, change_note
             FROM dbo.erp_delivery_control_history
             WHERE delivery_control_id = ?
             ORDER BY history_id',
            [$deliveryControlId]
        );

        $canRelease = (int)($deliveryRow['delivery_allowed'] ?? 0) === 1
            && strtoupper((string)($deliveryRow['delivery_status'] ?? '')) === 'READY'
            && !empty($guardRelease['allowed']);
    }
} catch (Throwable $exception) {
    $errorMessage = trim($exception->getMessage()) !== '' ? $exception->getMessage() : 'Delivery control could not be completed.';
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
    <title>Mission 30 - Delivery Control</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; line-height: 1.5; }
        .wrap { max-width: 1000px; margin: 0 auto; padding: 24px; }
        .banner { background: #7f1d1d; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
        th { background: #f3f4f6; width: 240px; }
        .list-table th { width: auto; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
        button { background: #7f1d1d; color: #fff; border: 0; padding: 10px 16px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">DELIVERY CONTROL - CONTROLLED RELEASE ONLY WHEN READY</div>

    <div class="card">
        <h1>Delivery Control</h1>
        <p>
            <a href="erp-qc-check.php">QC check</a> |
            <a href="erp-soft-run-readiness.php?jobcard_id=<?= erp_m30_dc_h((string)$selectedJobcardId) ?>">Soft Run readiness</a>
        </p>
        <?php if ($successMessage !== ''): ?><p class="ok"><?= erp_m30_dc_h($successMessage) ?></p><?php endif; ?>
        <?php if ($errorMessage !== ''): ?><p class="fail"><?= erp_m30_dc_h($errorMessage) ?></p><?php endif; ?>
        <p>JobCard ID: <?= erp_m30_dc_h((string)$selectedJobcardId) ?></p>
    </div>

    <?php if ($deliveryRow === []): ?>
        <div class="card"><p>No delivery control record found. Create a QC check first.</p></div>
    <?php else: ?>
        <div class="card">
            <h2>Latest Delivery Control</h2>
            <table>
                <tbody>
                    <tr><th>Delivery Control ID</th><td><?= erp_m30_dc_display($deliveryRow['delivery_control_id'] ?? '') ?></td></tr>
                    <tr><th>QC Check ID</th><td><?= erp_m30_dc_display($deliveryRow['qc_check_id'] ?? '') ?></td></tr>
                    <tr><th>Delivery Status</th><td><?= erp_m30_dc_display($deliveryRow['delivery_status'] ?? '') ?></td></tr>
                    <tr><th>Delivery Allowed</th><td><?= erp_m30_dc_display($deliveryRow['delivery_allowed'] ?? '') ?></td></tr>
                    <tr><th>Block Reason</th><td><?= erp_m30_dc_display($deliveryRow['block_reason'] ?? '') ?></td></tr>
                    <tr><th>Released By</th><td><?= erp_m30_dc_display($deliveryRow['released_by_user_id'] ?? '') ?></td></tr>
                    <tr><th>Released At</th><td><?= erp_m30_dc_display($deliveryRow['released_at'] ?? '') ?></td></tr>
                </tbody>
            </table>

            <?php if ($canRelease): ?>
                <form method="post" style="margin-top: 12px;">
                    <input type="hidden" name="csrf_token" value="<?= erp_m30_dc_h($csrfToken) ?>">
                    <input type="hidden" name="action" value="release">
                    <input type="hidden" name="jobcard_id" value="<?= erp_m30_dc_h((string)$selectedJobcardId) ?>">
                    <button type="submit">Release Delivery</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Delivery History</h2>
            <?php if ($historyRows === []): ?>
                <p>No history rows.</p>
            <?php else: ?>
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>ID</th><th>Action</th><th>Old</th><th>New</th><th>By</th><th>At</th><th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyRows as $historyRow): ?>
                            <tr>
                                <td><?= erp_m30_dc_display($historyRow['history_id'] ?? '') ?></td>
                                <td><?= erp_m30_dc_display($historyRow['action_code'] ?? '') ?></td>
                                <td><?= erp_m30_dc_display($historyRow['old_status'] ?? '') ?></td>
                                <td><?= erp_m30_dc_display($historyRow['new_status'] ?? '') ?></td>
                                <td><?= erp_m30_dc_display($historyRow['changed_by_user_id'] ?? '') ?></td>
                                <td><?= erp_m30_dc_display($historyRow['changed_at'] ?? '') ?></td>
                                <td><?= erp_m30_dc_display($historyRow['change_note'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
