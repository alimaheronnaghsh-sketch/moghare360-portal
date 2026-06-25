<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Controlled Access Request Apply Transition Page
 *
 * Phase 2 controlled write-enabled workflow transition.
 *
 * Entity: access_request
 * Transition: APPROVED -> APPLIED
 * Permission: access.request.apply
 * CSRF form key: access_request_apply
 * History change_type: ACCESS_REQUEST_APPLIED
 *
 * State-only apply. No role assignment.
 */

if (!function_exists('erp_access_request_apply_transition_require_helper')) {
    function erp_access_request_apply_transition_require_helper(string $helper_file): void
    {
        $helper_file = trim($helper_file);

        if ($helper_file === '') {
            throw new RuntimeException('ERP helper file name is required.');
        }

        $candidate_paths = [
            __DIR__ . '/../includes/' . $helper_file,
            __DIR__ . '/includes/' . $helper_file,
        ];

        foreach ($candidate_paths as $candidate_path) {
            if (is_file($candidate_path)) {
                require_once $candidate_path;
                return;
            }
        }

        throw new RuntimeException('Required ERP helper not found: ' . $helper_file);
    }
}

erp_access_request_apply_transition_require_helper('erp-config-loader.php');
erp_access_request_apply_transition_require_helper('erp-auth-context.php');
erp_access_request_apply_transition_require_helper('erp-csrf.php');
erp_access_request_apply_transition_require_helper('erp-permission-check.php');
erp_access_request_apply_transition_require_helper('erp-workflow-engine.php');

const ERP_ACCESS_REQUEST_APPLY_TRANSITION_FORM_KEY = 'access_request_apply';
const ERP_ACCESS_REQUEST_APPLY_TRANSITION_PERMISSION = 'access.request.apply';
const ERP_ACCESS_REQUEST_APPLY_TRANSITION_ENTITY = 'access_request';
const ERP_ACCESS_REQUEST_APPLY_TRANSITION_FROM_STATE = 'APPROVED';
const ERP_ACCESS_REQUEST_APPLY_TRANSITION_TO_STATE = 'APPLIED';
const ERP_ACCESS_REQUEST_APPLY_TRANSITION_HISTORY_TYPE = 'ACCESS_REQUEST_APPLIED';
const ERP_ACCESS_REQUEST_APPLY_TRANSITION_DEFAULT_REQUEST_ID = 4;

if (!function_exists('erp_access_request_apply_transition_escape')) {
    function erp_access_request_apply_transition_escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('erp_access_request_apply_transition_parse_request_id')) {
    function erp_access_request_apply_transition_parse_request_id(mixed $value): int
    {
        if (!is_string($value) && !is_int($value)) {
            return 0;
        }

        $raw = trim((string)$value);

        if ($raw === '' || !ctype_digit($raw)) {
            return 0;
        }

        $requestId = (int)$raw;

        return $requestId > 0 ? $requestId : 0;
    }
}

if (!function_exists('erp_access_request_apply_transition_get_actor_user_id')) {
    function erp_access_request_apply_transition_get_actor_user_id(array $context): int
    {
        if (isset($context['current_user_id']) && is_int($context['current_user_id'])) {
            return $context['current_user_id'];
        }

        if (isset($context['user_id']) && is_int($context['user_id'])) {
            return $context['user_id'];
        }

        return 0;
    }
}

if (!function_exists('erp_access_request_apply_transition_db_connection')) {
    function erp_access_request_apply_transition_db_connection()
    {
        $config = erp_load_config();
        $database = $config['database'] ?? [];

        $server = (string)($database['server'] ?? '');
        $name = (string)($database['name'] ?? '');
        $trusted = (bool)($database['trusted_connection'] ?? true);
        $username = (string)($database['username'] ?? '');
        $password = (string)($database['password'] ?? '');

        if ($server === '' || $name === '') {
            return false;
        }

        $connectionString = 'Driver={ODBC Driver 17 for SQL Server};Server=' .
            $server .
            ';Database=' .
            $name .
            ';TrustServerCertificate=Yes;';

        if ($trusted) {
            $connectionString .= 'Trusted_Connection=Yes;';

            return @odbc_connect($connectionString, '', '');
        }

        return @odbc_connect($connectionString, $username, $password);
    }
}

if (!function_exists('erp_access_request_apply_transition_execute')) {
    function erp_access_request_apply_transition_execute($connection, string $sql, array $params = [])
    {
        $statement = @odbc_prepare($connection, $sql);

        if (!$statement) {
            return false;
        }

        if (!@odbc_execute($statement, $params)) {
            return false;
        }

        return $statement;
    }
}

if (!function_exists('erp_access_request_apply_transition_has_applied_history')) {
    function erp_access_request_apply_transition_has_applied_history($connection, int $requestId): bool
    {
        $sql = '
            SELECT TOP (1) history_id
            FROM dbo.core_access_change_history
            WHERE request_id = ?
              AND change_type = ?
        ';

        $statement = erp_access_request_apply_transition_execute(
            $connection,
            $sql,
            [$requestId, ERP_ACCESS_REQUEST_APPLY_TRANSITION_HISTORY_TYPE]
        );

        if (!$statement) {
            throw new RuntimeException('ERP transition could not be completed.');
        }

        return @odbc_fetch_row($statement) === true;
    }
}

if (!function_exists('erp_access_request_apply_transition_normalize_nullable_timestamp')) {
    function erp_access_request_apply_transition_normalize_nullable_timestamp(mixed $value): ?string
    {
        if ($value === null || $value === false) {
            return null;
        }

        $normalized = trim((string)$value);

        return $normalized === '' ? null : $normalized;
    }
}

if (!function_exists('erp_access_request_apply_transition_normalize_nullable_user_id')) {
    function erp_access_request_apply_transition_normalize_nullable_user_id(mixed $value): ?int
    {
        if ($value === null || $value === false) {
            return null;
        }

        $normalized = trim((string)$value);

        if ($normalized === '') {
            return null;
        }

        if (!ctype_digit($normalized)) {
            return null;
        }

        $userId = (int)$normalized;

        return $userId > 0 ? $userId : null;
    }
}

if (!function_exists('erp_access_request_apply_transition_perform_write')) {
    /**
     * @return array<string, mixed>
     */
    function erp_access_request_apply_transition_perform_write($connection, int $requestId, int $changedByUserId): array
    {
        $selectSql = '
            SELECT
                request_id,
                request_number,
                request_state,
                subject_user_id,
                applied_at,
                applied_by_user_id,
                updated_at
            FROM dbo.core_access_requests
            WHERE request_id = ?
        ';

        $selectStatement = erp_access_request_apply_transition_execute($connection, $selectSql, [$requestId]);

        if (!$selectStatement || @odbc_fetch_row($selectStatement) !== true) {
            throw new RuntimeException('ERP access request was not found.');
        }

        $requestNumber = trim((string)@odbc_result($selectStatement, 'request_number'));
        $currentState = strtoupper(trim((string)@odbc_result($selectStatement, 'request_state')));
        $subjectUserId = (int)@odbc_result($selectStatement, 'subject_user_id');
        $currentAppliedAt = erp_access_request_apply_transition_normalize_nullable_timestamp(
            @odbc_result($selectStatement, 'applied_at')
        );
        $currentAppliedByUserId = erp_access_request_apply_transition_normalize_nullable_user_id(
            @odbc_result($selectStatement, 'applied_by_user_id')
        );
        $currentUpdatedAt = erp_access_request_apply_transition_normalize_nullable_timestamp(
            @odbc_result($selectStatement, 'updated_at')
        );

        if ($currentState !== ERP_ACCESS_REQUEST_APPLY_TRANSITION_FROM_STATE) {
            throw new RuntimeException('ERP access request is not in APPROVED state.');
        }

        if ($subjectUserId <= 0) {
            throw new RuntimeException('ERP transition could not be completed.');
        }

        if ($currentAppliedAt !== null) {
            throw new RuntimeException('ERP access request already has applied_at set.');
        }

        if ($currentAppliedByUserId !== null) {
            throw new RuntimeException('ERP access request already has applied_by_user_id set.');
        }

        if (erp_access_request_apply_transition_has_applied_history($connection, $requestId)) {
            throw new RuntimeException('ERP access request already has ACCESS_REQUEST_APPLIED history.');
        }

        $beforeJson = json_encode([
            'request_state' => $currentState,
            'applied_at' => $currentAppliedAt,
            'applied_by_user_id' => $currentAppliedByUserId,
            'updated_at' => $currentUpdatedAt,
        ], JSON_UNESCAPED_UNICODE);

        if (!is_string($beforeJson) || $beforeJson === '') {
            throw new RuntimeException('ERP transition could not be completed.');
        }

        if (!@odbc_autocommit($connection, false)) {
            throw new RuntimeException('ERP transition could not be completed.');
        }

        try {
            $updateSql = '
                UPDATE dbo.core_access_requests
                SET
                    request_state = ?,
                    applied_at = SYSDATETIME(),
                    applied_by_user_id = ?,
                    updated_at = SYSDATETIME()
                OUTPUT
                    INSERTED.request_state,
                    INSERTED.applied_at,
                    INSERTED.applied_by_user_id,
                    INSERTED.updated_at
                WHERE
                    request_id = ?
                    AND request_state = ?
                    AND applied_at IS NULL
                    AND applied_by_user_id IS NULL
            ';

            $updateStatement = erp_access_request_apply_transition_execute(
                $connection,
                $updateSql,
                [
                    ERP_ACCESS_REQUEST_APPLY_TRANSITION_TO_STATE,
                    $changedByUserId,
                    $requestId,
                    ERP_ACCESS_REQUEST_APPLY_TRANSITION_FROM_STATE,
                ]
            );

            if (!$updateStatement || @odbc_fetch_row($updateStatement) !== true) {
                throw new RuntimeException('ERP access request is not in APPROVED state.');
            }

            $newState = strtoupper(trim((string)@odbc_result($updateStatement, 'request_state')));
            $newAppliedAt = trim((string)@odbc_result($updateStatement, 'applied_at'));
            $newAppliedByUserId = (int)@odbc_result($updateStatement, 'applied_by_user_id');
            $newUpdatedAt = trim((string)@odbc_result($updateStatement, 'updated_at'));

            $afterJson = json_encode([
                'request_state' => $newState,
                'applied_at' => $newAppliedAt,
                'applied_by_user_id' => $newAppliedByUserId,
                'updated_at' => $newUpdatedAt,
            ], JSON_UNESCAPED_UNICODE);

            if (!is_string($afterJson) || $afterJson === '') {
                throw new RuntimeException('ERP transition could not be completed.');
            }

            $historySql = '
                INSERT INTO dbo.core_access_change_history
                (
                    user_id,
                    request_id,
                    change_type,
                    entity_type,
                    entity_id,
                    before_json,
                    after_json,
                    changed_by_user_id,
                    changed_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, SYSDATETIME())
            ';

            $historyStatement = erp_access_request_apply_transition_execute(
                $connection,
                $historySql,
                [
                    $subjectUserId,
                    $requestId,
                    ERP_ACCESS_REQUEST_APPLY_TRANSITION_HISTORY_TYPE,
                    'core_access_requests',
                    $requestId,
                    $beforeJson,
                    $afterJson,
                    $changedByUserId,
                ]
            );

            if (!$historyStatement) {
                throw new RuntimeException('ERP transition could not be completed.');
            }

            if (!@odbc_commit($connection)) {
                throw new RuntimeException('ERP transition could not be completed.');
            }

            @odbc_autocommit($connection, true);

            return [
                'ok' => true,
                'request_id' => $requestId,
                'request_number' => $requestNumber,
                'from_state' => ERP_ACCESS_REQUEST_APPLY_TRANSITION_FROM_STATE,
                'to_state' => ERP_ACCESS_REQUEST_APPLY_TRANSITION_TO_STATE,
                'transition' => ERP_ACCESS_REQUEST_APPLY_TRANSITION_FROM_STATE . ' -> ' . ERP_ACCESS_REQUEST_APPLY_TRANSITION_TO_STATE,
                'history_change_type' => ERP_ACCESS_REQUEST_APPLY_TRANSITION_HISTORY_TYPE,
                'database_updated' => true,
                'history_inserted' => true,
            ];
        } catch (Throwable $exception) {
            @odbc_rollback($connection);
            @odbc_autocommit($connection, true);

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException('ERP transition could not be completed.');
        }
    }
}

$status_message = '';
$error_message = '';
$transition_result = null;
$write_result = null;
$context = [];

try {
    $context = erp_auth_get_current_context();

    if (!is_array($context)) {
        throw new RuntimeException('ERP auth context is invalid.');
    }

    erp_auth_require_current_user();

    $request_method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($request_method === 'POST') {
        $posted_action = $_POST['transition_action'] ?? '';
        $posted_token = $_POST['csrf_token'] ?? '';
        $posted_request_id = $_POST['request_id'] ?? '';

        if (!is_string($posted_action) || trim($posted_action) !== 'apply_access_request') {
            throw new RuntimeException('Invalid ERP transition action.');
        }

        if (!is_string($posted_token)) {
            throw new RuntimeException('Invalid ERP CSRF token format.');
        }

        erp_csrf_require_valid_token(ERP_ACCESS_REQUEST_APPLY_TRANSITION_FORM_KEY, $posted_token);

        erp_permission_require($context, ERP_ACCESS_REQUEST_APPLY_TRANSITION_PERMISSION);

        erp_workflow_require_transition(
            ERP_ACCESS_REQUEST_APPLY_TRANSITION_ENTITY,
            ERP_ACCESS_REQUEST_APPLY_TRANSITION_FROM_STATE,
            ERP_ACCESS_REQUEST_APPLY_TRANSITION_TO_STATE
        );

        $transition_result = erp_workflow_build_transition_result(
            ERP_ACCESS_REQUEST_APPLY_TRANSITION_ENTITY,
            ERP_ACCESS_REQUEST_APPLY_TRANSITION_FROM_STATE,
            ERP_ACCESS_REQUEST_APPLY_TRANSITION_TO_STATE
        );

        $requestId = erp_access_request_apply_transition_parse_request_id($posted_request_id);

        if ($requestId <= 0) {
            throw new RuntimeException('Invalid ERP access request ID.');
        }

        $changedByUserId = erp_access_request_apply_transition_get_actor_user_id($context);

        if ($changedByUserId <= 0) {
            throw new RuntimeException('ERP auth context is missing current_user_id.');
        }

        $connection = erp_access_request_apply_transition_db_connection();

        if (!$connection) {
            throw new RuntimeException('ERP transition could not be completed.');
        }

        try {
            $write_result = erp_access_request_apply_transition_perform_write($connection, $requestId, $changedByUserId);
        } finally {
            @odbc_close($connection);
        }

        $status_message = 'Controlled state-only apply transition completed. Database state was updated.';
    }
} catch (Throwable $exception) {
    $error_message = $exception->getMessage();
}

$csrf_token = erp_csrf_create_token(ERP_ACCESS_REQUEST_APPLY_TRANSITION_FORM_KEY);

$current_user_id = '';
$current_username = '';
$current_full_name = '';

if (isset($context['user_id'])) {
    $current_user_id = (string)$context['user_id'];
} elseif (isset($context['current_user_id'])) {
    $current_user_id = (string)$context['current_user_id'];
}

if (isset($context['username']) && is_string($context['username'])) {
    $current_username = $context['username'];
}

if (isset($context['full_name']) && is_string($context['full_name'])) {
    $current_full_name = $context['full_name'];
}
?>

<!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8">
    <title>MOGHARE360 ERP - Access Request Apply Transition</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 32px;
            background: #f7f7f7;
            color: #222;
        }

        .page {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
            padding: 24px;
        }

        h1 {
            margin-top: 0;
        }

        .box {
            border: 1px solid #ddd;
            padding: 16px;
            margin: 16px 0;
            background: #fafafa;
        }

        .success {
            border-color: #2e7d32;
            background: #eef8ee;
        }

        .error {
            border-color: #c62828;
            background: #fff0f0;
        }

        .warning {
            border-color: #ef6c00;
            background: #fff7ed;
        }

        code {
            background: #eee;
            padding: 2px 5px;
        }

        button {
            padding: 10px 16px;
            cursor: pointer;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 8px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            width: 240px;
        }
    </style>
</head>
<body>
<div class="page">
    <h1>MOGHARE360 ERP - Access Request Apply Transition</h1>

    <div class="box warning">
        <strong>Controlled Write-Enabled Boundary</strong>
        <p>This page performs the controlled state-only apply workflow transition only:</p>
        <p><code>APPROVED -&gt; APPLIED</code> for a single <code>request_id</code> form submission.</p>
        <p>It updates request apply fields and inserts history.</p>
        <p>It does not assign roles, touch <code>core_user_roles</code>, update request items, or change <code>submitted_at</code> or <code>decided_at</code>.</p>
    </div>

    <div class="box">
        <h2>Current Prototype Actor</h2>
        <table>
            <tr>
                <th>User ID</th>
                <td><?php echo erp_access_request_apply_transition_escape($current_user_id); ?></td>
            </tr>
            <tr>
                <th>Username</th>
                <td><?php echo erp_access_request_apply_transition_escape($current_username); ?></td>
            </tr>
            <tr>
                <th>Full Name</th>
                <td><?php echo erp_access_request_apply_transition_escape($current_full_name); ?></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>Controlled Transition</h2>
        <table>
            <tr>
                <th>Entity</th>
                <td><code><?php echo erp_access_request_apply_transition_escape(ERP_ACCESS_REQUEST_APPLY_TRANSITION_ENTITY); ?></code></td>
            </tr>
            <tr>
                <th>From State</th>
                <td><code><?php echo erp_access_request_apply_transition_escape(ERP_ACCESS_REQUEST_APPLY_TRANSITION_FROM_STATE); ?></code></td>
            </tr>
            <tr>
                <th>To State</th>
                <td><code><?php echo erp_access_request_apply_transition_escape(ERP_ACCESS_REQUEST_APPLY_TRANSITION_TO_STATE); ?></code></td>
            </tr>
            <tr>
                <th>Permission</th>
                <td><code><?php echo erp_access_request_apply_transition_escape(ERP_ACCESS_REQUEST_APPLY_TRANSITION_PERMISSION); ?></code></td>
            </tr>
            <tr>
                <th>History Change Type</th>
                <td><code><?php echo erp_access_request_apply_transition_escape(ERP_ACCESS_REQUEST_APPLY_TRANSITION_HISTORY_TYPE); ?></code></td>
            </tr>
            <tr>
                <th>CSRF Form Key</th>
                <td><code><?php echo erp_access_request_apply_transition_escape(ERP_ACCESS_REQUEST_APPLY_TRANSITION_FORM_KEY); ?></code></td>
            </tr>
            <tr>
                <th>Candidate Request ID</th>
                <td><code><?php echo erp_access_request_apply_transition_escape((string)ERP_ACCESS_REQUEST_APPLY_TRANSITION_DEFAULT_REQUEST_ID); ?></code></td>
            </tr>
            <tr>
                <th>Candidate Request Number</th>
                <td><code>AR-20260620-084634-10001</code></td>
            </tr>
        </table>
    </div>

    <?php if ($status_message !== ''): ?>
        <div class="box success">
            <strong>Success</strong>
            <p><?php echo erp_access_request_apply_transition_escape($status_message); ?></p>

            <?php if (is_array($transition_result)): ?>
                <table>
                    <tr>
                        <th>Result</th>
                        <td><?php echo !empty($transition_result['ok']) ? 'OK' : 'FAILED'; ?></td>
                    </tr>
                    <tr>
                        <th>Transition</th>
                        <td><?php echo erp_access_request_apply_transition_escape((string)($transition_result['transition'] ?? '')); ?></td>
                    </tr>
                    <tr>
                        <th>Database Update</th>
                        <td><?php echo is_array($write_result) && !empty($write_result['database_updated']) ? 'Applied' : 'Not Applied'; ?></td>
                    </tr>
                    <tr>
                        <th>History Write</th>
                        <td><?php echo is_array($write_result) && !empty($write_result['history_inserted']) ? 'Applied' : 'Not Applied'; ?></td>
                    </tr>
                    <?php if (is_array($write_result)): ?>
                        <tr>
                            <th>Request ID</th>
                            <td><?php echo erp_access_request_apply_transition_escape((string)($write_result['request_id'] ?? '')); ?></td>
                        </tr>
                        <tr>
                            <th>Request Number</th>
                            <td><?php echo erp_access_request_apply_transition_escape((string)($write_result['request_number'] ?? '')); ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message !== ''): ?>
        <div class="box error">
            <strong>Blocked</strong>
            <p><?php echo erp_access_request_apply_transition_escape($error_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="box">
        <h2>Submit Apply Transition</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo erp_access_request_apply_transition_escape($csrf_token); ?>">
            <input type="hidden" name="transition_action" value="apply_access_request">
            <input type="hidden" name="request_id" value="<?php echo erp_access_request_apply_transition_escape((string)ERP_ACCESS_REQUEST_APPLY_TRANSITION_DEFAULT_REQUEST_ID); ?>">
            <button type="submit">Submit APPROVED to APPLIED Transition</button>
        </form>
    </div>
</div>
</body>
</html>
