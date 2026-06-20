<?php
/**
 * MOGHARE360 ERP Access Request List UI
 *
 * Phase 1A controlled read-only local prototype page.
 * SELECT-only. Safe output only.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/erp-config-loader.php';
require_once __DIR__ . '/includes/erp-auth-helper.php';
require_once __DIR__ . '/includes/erp-permission-helper.php';

erp_auth_require_login();
erp_permission_require_any_role(['owner', 'system_admin']);

function erp_arl_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function erp_arl_trim_summary(?string $value, int $limit = 120): string
{
    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    if (mb_strlen($value) <= $limit) {
        return $value;
    }

    return mb_substr($value, 0, $limit) . '...';
}

function erp_arl_db_connection()
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

function erp_arl_fetch_rows(): array
{
    $connection = erp_arl_db_connection();

    if (!$connection) {
        return [
            'ok' => false,
            'rows' => [],
        ];
    }

    $sql = '
        SELECT TOP 50
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
            r.created_at,
            i.item_type,
            i.role_id,
            roles.role_name,
            i.item_decision
        FROM dbo.core_access_requests r
        LEFT JOIN dbo.core_access_request_items i
            ON r.request_id = i.request_id
        LEFT JOIN dbo.core_users subject_user
            ON r.subject_user_id = subject_user.user_id
        LEFT JOIN dbo.core_users requester_user
            ON r.requested_by_user_id = requester_user.user_id
        LEFT JOIN dbo.core_roles roles
            ON i.role_id = roles.role_id
        ORDER BY 
            r.request_id DESC,
            i.item_id ASC
    ';

    $statement = @odbc_exec($connection, $sql);

    if (!$statement) {
        @odbc_close($connection);

        return [
            'ok' => false,
            'rows' => [],
        ];
    }

    $rows = [];

    while (@odbc_fetch_row($statement)) {
        $rows[] = [
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
            'created_at' => (string)@odbc_result($statement, 'created_at'),
            'item_type' => (string)@odbc_result($statement, 'item_type'),
            'role_id' => (string)@odbc_result($statement, 'role_id'),
            'role_name' => (string)@odbc_result($statement, 'role_name'),
            'item_decision' => (string)@odbc_result($statement, 'item_decision'),
        ];
    }

    @odbc_close($connection);

    return [
        'ok' => true,
        'rows' => $rows,
    ];
}

$result = erp_arl_fetch_rows();
$rows = $result['rows'];
$loadError = !$result['ok'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MOGHARE360 ERP - Access Request List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 32px;
            background: #f7f7f7;
            color: #222;
        }

        .box {
            max-width: 1400px;
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
        }

        .empty {
            padding: 16px;
            background: #f5f5f5;
            border: 1px solid #ddd;
        }

        .mono {
            font-family: Consolas, monospace;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>ERP Access Request List</h1>

        <p class="hint">Controlled Phase 1A read-only local prototype scope. Showing newest 50 records.</p>

        <div class="nav">
            <a href="erp-admin-dashboard.php">ERP Admin Dashboard</a>
            <a href="erp-access-request-create.php">Access Request Create UI</a>
        </div>

        <?php if ($loadError): ?>
            <div class="error">ERP request list could not be loaded.</div>
        <?php elseif (!$rows): ?>
            <div class="empty">No access requests found.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Request Number</th>
                        <th>Type</th>
                        <th>State</th>
                        <th>Priority</th>
                        <th>Subject User</th>
                        <th>Requester</th>
                        <th>Justification</th>
                        <th>Created At</th>
                        <th>Item Type</th>
                        <th>Role</th>
                        <th>Item Decision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="mono"><?php echo erp_arl_h($row['request_id']); ?></td>
                            <td class="mono"><?php echo erp_arl_h($row['request_number']); ?></td>
                            <td><?php echo erp_arl_h($row['request_type']); ?></td>
                            <td><?php echo erp_arl_h($row['request_state']); ?></td>
                            <td><?php echo erp_arl_h($row['priority']); ?></td>
                            <td>
                                <span class="mono"><?php echo erp_arl_h($row['subject_user_id']); ?></span><br>
                                <?php echo erp_arl_h($row['subject_username']); ?><br>
                                <?php echo erp_arl_h($row['subject_full_name']); ?>
                            </td>
                            <td>
                                <span class="mono"><?php echo erp_arl_h($row['requested_by_user_id']); ?></span><br>
                                <?php echo erp_arl_h($row['requester_username']); ?><br>
                                <?php echo erp_arl_h($row['requester_full_name']); ?>
                            </td>
                            <td><?php echo erp_arl_h(erp_arl_trim_summary($row['justification'])); ?></td>
                            <td class="mono"><?php echo erp_arl_h($row['created_at']); ?></td>
                            <td><?php echo erp_arl_h($row['item_type']); ?></td>
                            <td>
                                <span class="mono"><?php echo erp_arl_h($row['role_id']); ?></span><br>
                                <?php echo erp_arl_h($row['role_name']); ?>
                            </td>
                            <td><?php echo erp_arl_h($row['item_decision']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
