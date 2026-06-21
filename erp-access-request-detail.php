<?php
/**
 * MOGHARE360 ERP Access Request Detail UI
 *
 * Phase 1A controlled read-only local prototype page.
 * SELECT-only. Safe output only.
 *
 * Safety guard chain:
 * - Config loader: erp-config-loader.php
 * - Auth/session: erp_auth_require_login()
 * - Permission: erp_permission_require_any_role(['owner', 'system_admin'])
 * - CSRF: not used (read-only GET page, no forms/actions)
 * - Workflow engine: not used (no transition actions on this page)
 *
 * Forbidden: direct role assignment, permission change, user creation, write bypass.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/erp-config-loader.php';
require_once __DIR__ . '/includes/erp-auth-helper.php';
require_once __DIR__ . '/includes/erp-permission-helper.php';

// Runtime guards: config loaded above; auth and permission enforced before SELECT.
erp_auth_require_login();
erp_permission_require_any_role(['owner', 'system_admin']);

function erp_ard_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function erp_ard_db_connection()
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

function erp_ard_execute($connection, string $sql, array $params = [])
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

function erp_ard_parse_request_id(): ?int
{
    if (!isset($_GET['request_id'])) {
        return null;
    }

    $value = trim((string)$_GET['request_id']);

    if ($value === '' || !ctype_digit($value)) {
        return null;
    }

    $requestId = (int)$value;

    if ($requestId <= 0) {
        return null;
    }

    return $requestId;
}

function erp_ard_fetch_header($connection, int $requestId): array
{
    $sql = '
        SELECT
            r.request_id,
            r.request_number,
            r.request_type,
            r.request_state,
            r.priority,
            r.subject_user_id,
            subject_user.username AS subject_username,
            subject_user.full_name AS subject_full_name,
            r.requested_by_user_id,
            requester_user.username AS requester_username,
            requester_user.full_name AS requester_full_name,
            r.justification,
            r.owner_acknowledged,
            r.is_emergency,
            r.submitted_at,
            r.decided_at,
            r.applied_at,
            r.cancelled_at,
            r.created_at,
            r.updated_at
        FROM dbo.core_access_requests r
        LEFT JOIN dbo.core_users subject_user
            ON r.subject_user_id = subject_user.user_id
        LEFT JOIN dbo.core_users requester_user
            ON r.requested_by_user_id = requester_user.user_id
        WHERE r.request_id = ?
    ';

    $statement = erp_ard_execute($connection, $sql, [$requestId]);

    if (!$statement) {
        return [
            'status' => 'error',
            'header' => null,
        ];
    }

    if (@odbc_fetch_row($statement) !== true) {
        return [
            'status' => 'not_found',
            'header' => null,
        ];
    }

    return [
        'status' => 'ok',
        'header' => [
            'request_id' => (string)@odbc_result($statement, 'request_id'),
            'request_number' => (string)@odbc_result($statement, 'request_number'),
            'request_type' => (string)@odbc_result($statement, 'request_type'),
            'request_state' => (string)@odbc_result($statement, 'request_state'),
            'priority' => (string)@odbc_result($statement, 'priority'),
            'subject_user_id' => (string)@odbc_result($statement, 'subject_user_id'),
            'subject_username' => (string)@odbc_result($statement, 'subject_username'),
            'subject_full_name' => (string)@odbc_result($statement, 'subject_full_name'),
            'requested_by_user_id' => (string)@odbc_result($statement, 'requested_by_user_id'),
            'requester_username' => (string)@odbc_result($statement, 'requester_username'),
            'requester_full_name' => (string)@odbc_result($statement, 'requester_full_name'),
            'justification' => (string)@odbc_result($statement, 'justification'),
            'owner_acknowledged' => (string)@odbc_result($statement, 'owner_acknowledged'),
            'is_emergency' => (string)@odbc_result($statement, 'is_emergency'),
            'submitted_at' => (string)@odbc_result($statement, 'submitted_at'),
            'decided_at' => (string)@odbc_result($statement, 'decided_at'),
            'applied_at' => (string)@odbc_result($statement, 'applied_at'),
            'cancelled_at' => (string)@odbc_result($statement, 'cancelled_at'),
            'created_at' => (string)@odbc_result($statement, 'created_at'),
            'updated_at' => (string)@odbc_result($statement, 'updated_at'),
        ],
    ];
}

function erp_ard_fetch_items($connection, int $requestId): array
{
    $sql = '
        SELECT
            i.item_id,
            i.item_type,
            i.role_id,
            roles.role_name,
            i.department_id,
            i.position_id,
            i.module_key,
            i.permission_key,
            i.scope_type,
            i.effective_from,
            i.expires_at,
            i.is_temporary,
            i.item_decision,
            i.sort_order,
            i.created_at
        FROM dbo.core_access_request_items i
        LEFT JOIN dbo.core_roles roles
            ON i.role_id = roles.role_id
        WHERE i.request_id = ?
        ORDER BY
            i.sort_order ASC,
            i.item_id ASC
    ';

    $statement = erp_ard_execute($connection, $sql, [$requestId]);

    if (!$statement) {
        return [
            'ok' => false,
            'items' => [],
        ];
    }

    $items = [];

    while (@odbc_fetch_row($statement)) {
        $items[] = [
            'item_id' => (string)@odbc_result($statement, 'item_id'),
            'item_type' => (string)@odbc_result($statement, 'item_type'),
            'role_id' => (string)@odbc_result($statement, 'role_id'),
            'role_name' => (string)@odbc_result($statement, 'role_name'),
            'department_id' => (string)@odbc_result($statement, 'department_id'),
            'position_id' => (string)@odbc_result($statement, 'position_id'),
            'module_key' => (string)@odbc_result($statement, 'module_key'),
            'permission_key' => (string)@odbc_result($statement, 'permission_key'),
            'scope_type' => (string)@odbc_result($statement, 'scope_type'),
            'effective_from' => (string)@odbc_result($statement, 'effective_from'),
            'expires_at' => (string)@odbc_result($statement, 'expires_at'),
            'is_temporary' => (string)@odbc_result($statement, 'is_temporary'),
            'item_decision' => (string)@odbc_result($statement, 'item_decision'),
            'sort_order' => (string)@odbc_result($statement, 'sort_order'),
            'created_at' => (string)@odbc_result($statement, 'created_at'),
        ];
    }

    return [
        'ok' => true,
        'items' => $items,
    ];
}

$requestId = erp_ard_parse_request_id();
$notFound = false;
$loadError = false;
$header = null;
$items = [];

if ($requestId === null) {
    $notFound = true;
} else {
    $connection = erp_ard_db_connection();

    if (!$connection) {
        $loadError = true;
    } else {
        $headerResult = erp_ard_fetch_header($connection, $requestId);

        if ($headerResult['status'] === 'error') {
            $loadError = true;
        } elseif ($headerResult['status'] === 'not_found') {
            $notFound = true;
        } else {
            $header = $headerResult['header'];
            $itemsResult = erp_ard_fetch_items($connection, $requestId);

            if (!$itemsResult['ok']) {
                $loadError = true;
                $header = null;
            } else {
                $items = $itemsResult['items'];
            }
        }

        @odbc_close($connection);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MOGHARE360 ERP - Access Request Detail</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 32px;
            background: #f7f7f7;
            color: #222;
        }

        .box {
            max-width: 1200px;
            background: #fff;
            border: 1px solid #ddd;
            padding: 24px;
            border-radius: 8px;
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
            margin: 16px 0 24px;
        }

        .nav a {
            display: inline-block;
            margin-right: 12px;
        }

        h2 {
            margin-top: 28px;
            margin-bottom: 12px;
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            width: 220px;
        }

        .items-table th {
            width: auto;
        }

        .mono {
            font-family: Consolas, monospace;
        }

        .empty {
            padding: 16px;
            background: #f5f5f5;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>ERP Access Request Detail</h1>

        <p class="hint">Controlled Phase 1A read-only local prototype scope.</p>

        <div class="nav">
            <a href="erp-access-request-list.php">Access Request List UI</a>
            <a href="erp-admin-dashboard.php">ERP Admin Dashboard</a>
            <a href="erp-access-request-create.php">Access Request Create UI</a>
        </div>

        <?php if ($notFound): ?>
            <div class="error">ERP access request was not found.</div>
        <?php elseif ($loadError): ?>
            <div class="error">ERP access request detail could not be loaded.</div>
        <?php else: ?>
            <h2>Request Header</h2>
            <table>
                <tbody>
                    <tr>
                        <th>Request ID</th>
                        <td class="mono"><?php echo erp_ard_h($header['request_id']); ?></td>
                    </tr>
                    <tr>
                        <th>Request Number</th>
                        <td class="mono"><?php echo erp_ard_h($header['request_number']); ?></td>
                    </tr>
                    <tr>
                        <th>Request Type</th>
                        <td><?php echo erp_ard_h($header['request_type']); ?></td>
                    </tr>
                    <tr>
                        <th>Request State</th>
                        <td><?php echo erp_ard_h($header['request_state']); ?></td>
                    </tr>
                    <tr>
                        <th>Priority</th>
                        <td><?php echo erp_ard_h($header['priority']); ?></td>
                    </tr>
                    <tr>
                        <th>Subject User</th>
                        <td>
                            <span class="mono"><?php echo erp_ard_h($header['subject_user_id']); ?></span><br>
                            <?php echo erp_ard_h($header['subject_username']); ?><br>
                            <?php echo erp_ard_h($header['subject_full_name']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Requester</th>
                        <td>
                            <span class="mono"><?php echo erp_ard_h($header['requested_by_user_id']); ?></span><br>
                            <?php echo erp_ard_h($header['requester_username']); ?><br>
                            <?php echo erp_ard_h($header['requester_full_name']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Justification</th>
                        <td><?php echo erp_ard_h($header['justification']); ?></td>
                    </tr>
                    <tr>
                        <th>Owner Acknowledged</th>
                        <td><?php echo erp_ard_h($header['owner_acknowledged']); ?></td>
                    </tr>
                    <tr>
                        <th>Is Emergency</th>
                        <td><?php echo erp_ard_h($header['is_emergency']); ?></td>
                    </tr>
                    <tr>
                        <th>Submitted At</th>
                        <td class="mono"><?php echo erp_ard_h($header['submitted_at']); ?></td>
                    </tr>
                    <tr>
                        <th>Decided At</th>
                        <td class="mono"><?php echo erp_ard_h($header['decided_at']); ?></td>
                    </tr>
                    <tr>
                        <th>Applied At</th>
                        <td class="mono"><?php echo erp_ard_h($header['applied_at']); ?></td>
                    </tr>
                    <tr>
                        <th>Cancelled At</th>
                        <td class="mono"><?php echo erp_ard_h($header['cancelled_at']); ?></td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td class="mono"><?php echo erp_ard_h($header['created_at']); ?></td>
                    </tr>
                    <tr>
                        <th>Updated At</th>
                        <td class="mono"><?php echo erp_ard_h($header['updated_at']); ?></td>
                    </tr>
                </tbody>
            </table>

            <h2>Request Items</h2>

            <?php if (!$items): ?>
                <div class="empty">No request items found.</div>
            <?php else: ?>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Item Type</th>
                            <th>Role</th>
                            <th>Department ID</th>
                            <th>Position ID</th>
                            <th>Module Key</th>
                            <th>Permission Key</th>
                            <th>Scope Type</th>
                            <th>Effective From</th>
                            <th>Expires At</th>
                            <th>Temporary</th>
                            <th>Item Decision</th>
                            <th>Sort Order</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="mono"><?php echo erp_ard_h($item['item_id']); ?></td>
                                <td><?php echo erp_ard_h($item['item_type']); ?></td>
                                <td>
                                    <span class="mono"><?php echo erp_ard_h($item['role_id']); ?></span><br>
                                    <?php echo erp_ard_h($item['role_name']); ?>
                                </td>
                                <td class="mono"><?php echo erp_ard_h($item['department_id']); ?></td>
                                <td class="mono"><?php echo erp_ard_h($item['position_id']); ?></td>
                                <td><?php echo erp_ard_h($item['module_key']); ?></td>
                                <td><?php echo erp_ard_h($item['permission_key']); ?></td>
                                <td><?php echo erp_ard_h($item['scope_type']); ?></td>
                                <td class="mono"><?php echo erp_ard_h($item['effective_from']); ?></td>
                                <td class="mono"><?php echo erp_ard_h($item['expires_at']); ?></td>
                                <td><?php echo erp_ard_h($item['is_temporary']); ?></td>
                                <td><?php echo erp_ard_h($item['item_decision']); ?></td>
                                <td class="mono"><?php echo erp_ard_h($item['sort_order']); ?></td>
                                <td class="mono"><?php echo erp_ard_h($item['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
