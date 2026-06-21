<?php
/**
 * MOGHARE360 ERP Access Request Create UI
 *
 * Phase 1A controlled local prototype write UI.
 * Safe output only.
 *
 * Safety guard chain:
 * - Config loader: erp-config-loader.php
 * - Auth/session: erp_auth_require_login()
 * - Permission: erp_permission_require_any_role(['owner', 'system_admin'])
 * - CSRF: erp_csrf_require_valid() on POST forms only
 * - Workflow engine: not used (request creation, not state transition)
 * - Audit: erp_audit_write() on approved create write path only
 *
 * Forbidden: direct role assignment, permission change, user creation, workflow bypass.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/erp-config-loader.php';
require_once __DIR__ . '/includes/erp-auth-helper.php';
require_once __DIR__ . '/includes/erp-permission-helper.php';
require_once __DIR__ . '/includes/erp-csrf-helper.php';
require_once __DIR__ . '/includes/erp-audit-helper.php';

// Runtime guards: config loaded above; auth and permission enforced before any action.
erp_auth_require_login();
erp_permission_require_any_role(['owner', 'system_admin']);

$currentUser = erp_auth_current_user();
$currentUserId = isset($currentUser['user_id']) ? (int)$currentUser['user_id'] : 0;

if ($currentUserId <= 0) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'ERP access denied.';
    exit;
}

function erp_ar_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function erp_ar_db_connection()
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

function erp_ar_execute($connection, string $sql, array $params = [])
{
    $statement = @odbc_prepare($connection, $sql);

    if (!$statement) {
        return false;
    }

    $ok = @odbc_execute($statement, $params);

    if (!$ok) {
        return false;
    }

    return $statement;
}

function erp_ar_exists($connection, string $sql, array $params): bool
{
    $statement = erp_ar_execute($connection, $sql, $params);

    if (!$statement) {
        return false;
    }

    return @odbc_fetch_row($statement) === true;
}

function erp_ar_generate_request_number(int $userId): string
{
    return 'AR-' . date('Ymd-His') . '-' . $userId;
}

function erp_ar_normalize_datetime(?string $value, bool $endOfDay = false): ?string
{
    $value = trim((string)$value);

    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return null;
    }

    if ($endOfDay) {
        return date('Y-m-d 23:59:59', $timestamp);
    }

    return date('Y-m-d 00:00:00', $timestamp);
}

function erp_ar_safe_value(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

$allowedRequestTypes = [
    'ROLE_GRANT',
    'TEMPORARY_ROLE_GRANT',
];

$allowedPriorities = [
    'NORMAL',
    'URGENT',
];

$allowedItemTypes = [
    'ROLE_GRANT',
];

$errors = [];
$successMessage = '';
$failureMessage = '';

$form = [
    'request_type' => 'ROLE_GRANT',
    'subject_user_id' => '',
    'justification' => '',
    'priority' => 'NORMAL',
    'item_type' => 'ROLE_GRANT',
    'role_id' => '',
    'effective_from' => date('Y-m-d'),
    'expires_at' => '',
    'is_temporary' => '0',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST write path: CSRF first, then validation, then transaction + audit.
    erp_csrf_require_valid('access_request_create', $_POST['erp_csrf_token'] ?? null);

    $form['request_type'] = erp_ar_safe_value('request_type', 'ROLE_GRANT');
    $form['subject_user_id'] = erp_ar_safe_value('subject_user_id');
    $form['justification'] = erp_ar_safe_value('justification');
    $form['priority'] = erp_ar_safe_value('priority', 'NORMAL');
    $form['item_type'] = erp_ar_safe_value('item_type', 'ROLE_GRANT');
    $form['role_id'] = erp_ar_safe_value('role_id');
    $form['effective_from'] = erp_ar_safe_value('effective_from');
    $form['expires_at'] = erp_ar_safe_value('expires_at');
    $form['is_temporary'] = isset($_POST['is_temporary']) ? '1' : '0';

    if (!in_array($form['request_type'], $allowedRequestTypes, true)) {
        $errors[] = 'Invalid request type.';
    }

    if (!ctype_digit($form['subject_user_id']) || (int)$form['subject_user_id'] <= 0) {
        $errors[] = 'Invalid subject user.';
    }

    if ($form['justification'] === '' || mb_strlen($form['justification']) > 2000) {
        $errors[] = 'Justification is required and must be limited.';
    }

    if (!in_array($form['priority'], $allowedPriorities, true)) {
        $errors[] = 'Invalid priority.';
    }

    if (!in_array($form['item_type'], $allowedItemTypes, true)) {
        $errors[] = 'Invalid item type.';
    }

    if ($form['item_type'] === 'ROLE_GRANT') {
        if (!ctype_digit($form['role_id']) || (int)$form['role_id'] <= 0) {
            $errors[] = 'Invalid role.';
        }
    }

    $effectiveFrom = erp_ar_normalize_datetime($form['effective_from']);
    $expiresAt = erp_ar_normalize_datetime($form['expires_at'], true);

    if ($effectiveFrom === null) {
        $errors[] = 'Invalid effective date.';
    }

    if ($form['expires_at'] !== '' && $expiresAt === null) {
        $errors[] = 'Invalid expiry date.';
    }

    if ($effectiveFrom !== null && $expiresAt !== null && strtotime($expiresAt) < strtotime($effectiveFrom)) {
        $errors[] = 'Expiry date cannot be before effective date.';
    }

    $connection = false;

    if (!$errors) {
        $connection = erp_ar_db_connection();

        if (!$connection) {
            $failureMessage = 'ERP request could not be completed.';
        }
    }

    if (!$errors && $connection) {
        $subjectUserId = (int)$form['subject_user_id'];
        $roleId = (int)$form['role_id'];

        if (!erp_ar_exists(
            $connection,
            'SELECT 1 FROM dbo.core_users WHERE user_id = ?',
            [$subjectUserId]
        )) {
            $errors[] = 'Subject user was not found.';
        }

        if ($form['item_type'] === 'ROLE_GRANT' && !erp_ar_exists(
            $connection,
            'SELECT 1 FROM dbo.core_roles WHERE role_id = ?',
            [$roleId]
        )) {
            $errors[] = 'Role was not found.';
        }
    }

    if (!$errors && $connection && $failureMessage === '') {
        $requestNumber = erp_ar_generate_request_number($currentUserId);
        $requestId = null;

        try {
            @odbc_autocommit($connection, false);

            $requestSql = '
                INSERT INTO dbo.core_access_requests
                (
                    request_number,
                    request_type,
                    priority,
                    subject_user_id,
                    requested_by_user_id,
                    justification
                )
                OUTPUT INSERTED.request_id
                VALUES (?, ?, ?, ?, ?, ?)
            ';

            $requestStatement = erp_ar_execute($connection, $requestSql, [
                $requestNumber,
                $form['request_type'],
                $form['priority'],
                $subjectUserId,
                $currentUserId,
                $form['justification'],
            ]);

            if (!$requestStatement || !@odbc_fetch_row($requestStatement)) {
                throw new RuntimeException('request insert failed');
            }

            $requestId = (int)@odbc_result($requestStatement, 'request_id');

            if ($requestId <= 0) {
                throw new RuntimeException('request id failed');
            }

            $itemSql = '
                INSERT INTO dbo.core_access_request_items
                (
                    request_id,
                    item_type,
                    role_id,
                    permission_key,
                    effective_from,
                    expires_at,
                    is_temporary
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ';

            $itemStatement = erp_ar_execute($connection, $itemSql, [
                $requestId,
                'ROLE_GRANT',
                $roleId,
                null,
                $effectiveFrom,
                $expiresAt,
                (int)$form['is_temporary'],
            ]);

            if (!$itemStatement) {
                throw new RuntimeException('request item insert failed');
            }

            if (!@odbc_commit($connection)) {
                throw new RuntimeException('commit failed');
            }

            @odbc_autocommit($connection, true);

            erp_audit_write([
                'action' => 'ERP_ACCESS_REQUEST_CREATED',
                'entity_type' => 'core_access_requests',
                'entity_id' => $requestId,
                'request_id' => $requestId,
                'subject_user_id' => $subjectUserId,
                'details' => [
                    'request_number' => $requestNumber,
                    'request_type' => $form['request_type'],
                    'subject_user_id' => $subjectUserId,
                    'requested_by_user_id' => $currentUserId,
                    'item_type' => 'ROLE_GRANT',
                    'role_id' => $roleId,
                    'priority' => $form['priority'],
                ],
            ]);

            erp_csrf_clear('access_request_create');

            $successMessage = 'ERP access request created.';

            $form = [
                'request_type' => 'ROLE_GRANT',
                'subject_user_id' => '',
                'justification' => '',
                'priority' => 'NORMAL',
                'item_type' => 'ROLE_GRANT',
                'role_id' => '',
                'effective_from' => date('Y-m-d'),
                'expires_at' => '',
                'is_temporary' => '0',
            ];
        } catch (Throwable $exception) {
            @odbc_rollback($connection);
            @odbc_autocommit($connection, true);
            $failureMessage = 'ERP request could not be completed.';
        }
    }

    if ($connection) {
        @odbc_close($connection);
    }
}

$csrfInput = erp_csrf_input('access_request_create');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MOGHARE360 ERP - Access Request Create</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 32px;
            background: #f7f7f7;
            color: #222;
        }

        .box {
            max-width: 900px;
            background: #fff;
            border: 1px solid #ddd;
            padding: 24px;
            border-radius: 8px;
        }

        label {
            display: block;
            margin-top: 14px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
            width: 100%;
            max-width: 520px;
            padding: 8px;
            margin-top: 4px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 120px;
        }

        button {
            margin-top: 18px;
            padding: 10px 18px;
            cursor: pointer;
        }

        .success {
            background: #e8f7e8;
            border: 1px solid #8bc58b;
            padding: 12px;
            margin-bottom: 16px;
        }

        .error {
            background: #fdecec;
            border: 1px solid #d99;
            padding: 12px;
            margin-bottom: 16px;
        }

        .hint {
            color: #555;
            font-size: 13px;
        }

        .nav {
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>ERP Access Request Create</h1>

        <p class="hint">Controlled Phase 1A local prototype scope.</p>

        <?php if ($successMessage !== ''): ?>
            <div class="success"><?php echo erp_ar_h($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($failureMessage !== ''): ?>
            <div class="error"><?php echo erp_ar_h($failureMessage); ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="error">
                <strong>Validation failed.</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo erp_ar_h($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php echo $csrfInput; ?>

            <label for="request_type">Request Type</label>
            <select id="request_type" name="request_type" required>
                <?php foreach ($allowedRequestTypes as $type): ?>
                    <option value="<?php echo erp_ar_h($type); ?>" <?php echo $form['request_type'] === $type ? 'selected' : ''; ?>>
                        <?php echo erp_ar_h($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="subject_user_id">Subject User ID</label>
            <input id="subject_user_id" name="subject_user_id" type="number" min="1" value="<?php echo erp_ar_h($form['subject_user_id']); ?>" required>

            <label for="justification">Justification</label>
            <textarea id="justification" name="justification" maxlength="2000" required><?php echo erp_ar_h($form['justification']); ?></textarea>

            <label for="priority">Priority</label>
            <select id="priority" name="priority" required>
                <?php foreach ($allowedPriorities as $priority): ?>
                    <option value="<?php echo erp_ar_h($priority); ?>" <?php echo $form['priority'] === $priority ? 'selected' : ''; ?>>
                        <?php echo erp_ar_h($priority); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="item_type">Item Type</label>
            <select id="item_type" name="item_type" required>
                <?php foreach ($allowedItemTypes as $itemType): ?>
                    <option value="<?php echo erp_ar_h($itemType); ?>" <?php echo $form['item_type'] === $itemType ? 'selected' : ''; ?>>
                        <?php echo erp_ar_h($itemType); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="role_id">Role ID</label>
            <input id="role_id" name="role_id" type="number" min="1" value="<?php echo erp_ar_h($form['role_id']); ?>" required>
            <div class="hint">Required for role grant requests.</div>

            <label for="effective_from">Effective From</label>
            <input id="effective_from" name="effective_from" type="date" value="<?php echo erp_ar_h($form['effective_from']); ?>" required>

            <label for="expires_at">Expires At</label>
            <input id="expires_at" name="expires_at" type="date" value="<?php echo erp_ar_h($form['expires_at']); ?>">

            <label>
                <input name="is_temporary" type="checkbox" value="1" <?php echo $form['is_temporary'] === '1' ? 'checked' : ''; ?> style="width:auto;">
                Temporary Access
            </label>

            <button type="submit">Create Access Request</button>
        </form>

        <div class="nav">
            <a href="erp-admin-dashboard.php">Back to ERP Admin Dashboard</a>
        </div>
    </div>
</body>
</html>
