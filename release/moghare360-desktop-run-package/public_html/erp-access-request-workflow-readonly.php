<?php
declare(strict_types=1);

/**
 * Mission 5 / Phase 2.1 / Admin Read-Only Workflow Viewer / SELECT ONLY
 *
 * MOGHARE360 ERP — Phase 2.1 Admin Read-Only Workflow Viewer
 * READ ONLY PAGE — SELECT queries only.
 *
 * Forbidden SQL verbs in this file:
 * INSERT / UPDATE / DELETE / MERGE are forbidden in this file.
 *
 * Fixed request_id = 4
 * No form. No POST handling. No write operations.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_ACCESS_REQUEST_WORKFLOW_READONLY_REQUEST_ID = 4;
const ERP_ACCESS_REQUEST_WORKFLOW_READONLY_SUBJECT_USER_ID = 10001;

/** @var list<string> */
const ERP_ACCESS_REQUEST_WORKFLOW_READONLY_REQUIRED_HISTORY = [
    'ACCESS_REQUEST_SUBMITTED',
    'ACCESS_REQUEST_UNDER_REVIEW',
    'ACCESS_REQUEST_APPROVED',
    'ACCESS_REQUEST_APPLIED',
];

function erp_arw_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_arw_connect()
{
    $dsns = [
        'Driver={ODBC Driver 17 for SQL Server};Server=.\SQLEXPRESS;Database=moghare360_ERP;Trusted_Connection=yes;TrustServerCertificate=Yes;',
        'Driver={ODBC Driver 18 for SQL Server};Server=.\SQLEXPRESS;Database=moghare360_ERP;Trusted_Connection=yes;TrustServerCertificate=Yes;',
    ];

    foreach ($dsns as $dsn) {
        $connection = @odbc_connect($dsn, '', '');
        if ($connection !== false) {
            return $connection;
        }
    }

    return false;
}

/**
 * @return list<array<string, string>>
 */
function erp_arw_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = @odbc_prepare($connection, $sql);

    if (!$statement) {
        return [];
    }

    if (!@odbc_execute($statement, $params)) {
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

function erp_arw_scalar($connection, string $sql, array $params = []): ?string
{
    $rows = erp_arw_fetch_rows($connection, $sql, $params);

    if ($rows === []) {
        return null;
    }

    $first = $rows[0];
    $firstValue = reset($first);

    return $firstValue === false ? null : (string)$firstValue;
}

function erp_arw_display_value(string $value): string
{
    return erp_arw_h(trim($value) === '' ? '—' : $value);
}

function erp_arw_row_value(array $row, string $key): string
{
    return (string)($row[$key] ?? '');
}

$connectionError = '';
$requestRow = null;
$itemRows = [];
$approvalRows = [];
$historyRows = [];
$roleCount = null;
$connection = false;

if (!extension_loaded('odbc')) {
    $connectionError = 'ODBC extension is not available.';
} else {
    $connection = erp_arw_connect();

    if ($connection === false) {
        $connectionError = 'Database connection could not be established.';
    } else {
        $requestRows = erp_arw_fetch_rows(
            $connection,
            'SELECT * FROM dbo.core_access_requests WHERE request_id = ?',
            [ERP_ACCESS_REQUEST_WORKFLOW_READONLY_REQUEST_ID]
        );
        $requestRow = $requestRows[0] ?? null;

        $itemRows = erp_arw_fetch_rows(
            $connection,
            'SELECT * FROM dbo.core_access_request_items WHERE request_id = ? ORDER BY item_id',
            [ERP_ACCESS_REQUEST_WORKFLOW_READONLY_REQUEST_ID]
        );

        $approvalRows = erp_arw_fetch_rows(
            $connection,
            'SELECT * FROM dbo.core_access_approvals WHERE request_id = ? ORDER BY approval_id',
            [ERP_ACCESS_REQUEST_WORKFLOW_READONLY_REQUEST_ID]
        );

        $historyRows = erp_arw_fetch_rows(
            $connection,
            'SELECT * FROM dbo.core_access_change_history WHERE request_id = ? ORDER BY changed_at, history_id',
            [ERP_ACCESS_REQUEST_WORKFLOW_READONLY_REQUEST_ID]
        );

        $roleCount = erp_arw_scalar(
            $connection,
            'SELECT COUNT(*) AS role_count FROM dbo.core_user_roles WHERE user_id = ?',
            [ERP_ACCESS_REQUEST_WORKFLOW_READONLY_SUBJECT_USER_ID]
        );

        @odbc_close($connection);
        $connection = false;
    }
}

$historyChangeTypes = array_map(
    static fn(array $row): string => strtoupper(trim(erp_arw_row_value($row, 'change_type'))),
    $historyRows
);

$missingHistoryTypes = array_values(array_filter(
    ERP_ACCESS_REQUEST_WORKFLOW_READONLY_REQUIRED_HISTORY,
    static fn(string $changeType): bool => !in_array($changeType, $historyChangeTypes, true)
));

$timelineComplete = $missingHistoryTypes === [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Phase 2.1 - Admin Read-Only Workflow Viewer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f6f8;
            color: #1f2937;
            line-height: 1.5;
        }

        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px;
        }

        .banner {
            background: #7f1d1d;
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 16px;
        }

        .card {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 1.5rem;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 1.1rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }

        .muted {
            color: #6b7280;
            font-size: 0.92rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
            margin-top: 8px;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            width: 220px;
        }

        code, pre {
            background: #f3f4f6;
            border-radius: 4px;
            font-size: 0.88rem;
        }

        code {
            padding: 1px 4px;
        }

        pre {
            padding: 10px;
            overflow-x: auto;
            white-space: pre-wrap;
        }

        .ok {
            color: #166534;
            font-weight: bold;
        }

        .warn {
            color: #b45309;
            font-weight: bold;
        }

        .fail {
            color: #b91c1c;
            font-weight: bold;
        }

        ul {
            margin: 8px 0;
            padding-left: 20px;
        }

        .tag {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            padding: 2px 8px;
            border-radius: 6px;
            margin: 2px 4px 2px 0;
            font-size: 0.85rem;
        }

        .tag.missing {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">READ ONLY — SELECT ONLY — NO WRITE OPERATIONS</div>

    <div class="card">
        <h1>Phase 2.1 - Admin Read-Only Workflow Viewer</h1>
        <p class="muted">MOGHARE360 ERP — fixed request_id = <?= erp_arw_h((string)ERP_ACCESS_REQUEST_WORKFLOW_READONLY_REQUEST_ID) ?></p>
        <?php if ($connectionError !== ''): ?>
            <p class="fail"><?= erp_arw_h($connectionError) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>1. Request Summary</h2>
        <pre>SELECT *
FROM dbo.core_access_requests
WHERE request_id = 4;</pre>
        <?php if ($requestRow === null): ?>
            <p class="fail">Request not found.</p>
        <?php else: ?>
            <table>
                <tbody>
                    <tr><th>request_id</th><td><?= erp_arw_display_value(erp_arw_row_value($requestRow, 'request_id')) ?></td></tr>
                    <tr><th>request_number</th><td><?= erp_arw_display_value(erp_arw_row_value($requestRow, 'request_number')) ?></td></tr>
                    <tr><th>request_type</th><td><?= erp_arw_display_value(erp_arw_row_value($requestRow, 'request_type')) ?></td></tr>
                    <tr><th>request_state</th><td><strong><?= erp_arw_display_value(erp_arw_row_value($requestRow, 'request_state')) ?></strong></td></tr>
                    <tr><th>subject_user_id</th><td><?= erp_arw_display_value(erp_arw_row_value($requestRow, 'subject_user_id')) ?></td></tr>
                    <tr><th>requested_by_user_id</th><td><?= erp_arw_display_value(erp_arw_row_value($requestRow, 'requested_by_user_id')) ?></td></tr>
                    <tr><th>submitted_at</th><td><?= erp_arw_display_value(erp_arw_row_value($requestRow, 'submitted_at')) ?></td></tr>
                    <tr><th>decided_at</th><td><?= erp_arw_display_value(erp_arw_row_value($requestRow, 'decided_at')) ?></td></tr>
                    <tr><th>applied_at</th><td><?= erp_arw_display_value(erp_arw_row_value($requestRow, 'applied_at')) ?></td></tr>
                    <tr><th>applied_by_user_id</th><td><?= erp_arw_display_value(erp_arw_row_value($requestRow, 'applied_by_user_id')) ?></td></tr>
                    <tr><th>created_at</th><td><?= erp_arw_display_value(erp_arw_row_value($requestRow, 'created_at')) ?></td></tr>
                    <tr><th>updated_at</th><td><?= erp_arw_display_value(erp_arw_row_value($requestRow, 'updated_at')) ?></td></tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>2. Request Item</h2>
        <pre>SELECT *
FROM dbo.core_access_request_items
WHERE request_id = 4
ORDER BY item_id;</pre>
        <?php if ($itemRows === []): ?>
            <p class="warn">No request item rows found.</p>
        <?php else: ?>
            <?php foreach ($itemRows as $index => $itemRow): ?>
                <?php if ($index > 0): ?>
                    <hr>
                <?php endif; ?>
                <table>
                    <tbody>
                        <tr><th>item_id</th><td><?= erp_arw_display_value(erp_arw_row_value($itemRow, 'item_id')) ?></td></tr>
                        <tr><th>item_type</th><td><?= erp_arw_display_value(erp_arw_row_value($itemRow, 'item_type')) ?></td></tr>
                        <tr><th>item_decision</th><td><strong><?= erp_arw_display_value(erp_arw_row_value($itemRow, 'item_decision')) ?></strong></td></tr>
                        <tr><th>role_id</th><td><?= erp_arw_display_value(erp_arw_row_value($itemRow, 'role_id')) ?></td></tr>
                        <tr><th>department_id</th><td><?= erp_arw_display_value(erp_arw_row_value($itemRow, 'department_id')) ?></td></tr>
                        <tr><th>position_id</th><td><?= erp_arw_display_value(erp_arw_row_value($itemRow, 'position_id')) ?></td></tr>
                        <tr><th>module_key</th><td><?= erp_arw_display_value(erp_arw_row_value($itemRow, 'module_key')) ?></td></tr>
                        <tr><th>permission_key</th><td><?= erp_arw_display_value(erp_arw_row_value($itemRow, 'permission_key')) ?></td></tr>
                        <tr><th>is_temporary</th><td><?= erp_arw_display_value(erp_arw_row_value($itemRow, 'is_temporary')) ?></td></tr>
                        <tr><th>effective_from</th><td><?= erp_arw_display_value(erp_arw_row_value($itemRow, 'effective_from')) ?></td></tr>
                        <tr><th>expires_at</th><td><?= erp_arw_display_value(erp_arw_row_value($itemRow, 'expires_at')) ?></td></tr>
                        <tr><th>created_at</th><td><?= erp_arw_display_value(erp_arw_row_value($itemRow, 'created_at')) ?></td></tr>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>3. Approval Result</h2>
        <pre>SELECT *
FROM dbo.core_access_approvals
WHERE request_id = 4
ORDER BY approval_id;</pre>
        <?php if ($approvalRows === []): ?>
            <p class="warn"><strong>Approval Result: NO APPROVAL ROW FOUND</strong></p>
        <?php else: ?>
            <?php foreach ($approvalRows as $index => $approvalRow): ?>
                <?php if ($index > 0): ?>
                    <hr>
                <?php endif; ?>
                <table>
                    <tbody>
                        <tr><th>approval_id</th><td><?= erp_arw_display_value(erp_arw_row_value($approvalRow, 'approval_id')) ?></td></tr>
                        <tr><th>request_id</th><td><?= erp_arw_display_value(erp_arw_row_value($approvalRow, 'request_id')) ?></td></tr>
                        <tr><th>approver_user_id</th><td><?= erp_arw_display_value(erp_arw_row_value($approvalRow, 'approver_user_id')) ?></td></tr>
                        <tr><th>approver_capacity</th><td><?= erp_arw_display_value(erp_arw_row_value($approvalRow, 'approver_capacity')) ?></td></tr>
                        <tr><th>decision</th><td><strong><?= erp_arw_display_value(erp_arw_row_value($approvalRow, 'decision')) ?></strong></td></tr>
                        <tr><th>comment</th><td><?= erp_arw_display_value(erp_arw_row_value($approvalRow, 'comment')) ?></td></tr>
                        <tr><th>decided_at</th><td><?= erp_arw_display_value(erp_arw_row_value($approvalRow, 'decided_at')) ?></td></tr>
                        <tr><th>created_at</th><td><?= erp_arw_display_value(erp_arw_row_value($approvalRow, 'created_at')) ?></td></tr>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>4. Workflow Timeline</h2>
        <pre>SELECT *
FROM dbo.core_access_change_history
WHERE request_id = 4
ORDER BY changed_at, history_id;</pre>
        <p>
            Required change types:
            <?php foreach (ERP_ACCESS_REQUEST_WORKFLOW_READONLY_REQUIRED_HISTORY as $requiredType): ?>
                <span class="tag <?= in_array($requiredType, $historyChangeTypes, true) ? '' : 'missing' ?>">
                    <?= erp_arw_h($requiredType) ?>
                </span>
            <?php endforeach; ?>
        </p>
        <p class="<?= $timelineComplete ? 'ok' : 'fail' ?>">
            Timeline status: <?= $timelineComplete ? 'COMPLETE' : 'INCOMPLETE' ?>
        </p>
        <?php if ($historyRows === []): ?>
            <p class="warn">No workflow history rows found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>history_id</th>
                        <th>change_type</th>
                        <th>entity_type</th>
                        <th>entity_id</th>
                        <th>changed_by_user_id</th>
                        <th>changed_at</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historyRows as $historyRow): ?>
                        <tr>
                            <td><?= erp_arw_display_value(erp_arw_row_value($historyRow, 'history_id')) ?></td>
                            <td><strong><?= erp_arw_display_value(erp_arw_row_value($historyRow, 'change_type')) ?></strong></td>
                            <td><?= erp_arw_display_value(erp_arw_row_value($historyRow, 'entity_type')) ?></td>
                            <td><?= erp_arw_display_value(erp_arw_row_value($historyRow, 'entity_id')) ?></td>
                            <td><?= erp_arw_display_value(erp_arw_row_value($historyRow, 'changed_by_user_id')) ?></td>
                            <td><?= erp_arw_display_value(erp_arw_row_value($historyRow, 'changed_at')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>5. State-Only Apply Verification</h2>
        <ul>
            <li><strong>APPLIED is State-Only.</strong></li>
            <li><strong>Real Assignment = NOT PERFORMED.</strong></li>
        </ul>
    </div>

    <div class="card">
        <h2>6. core_user_roles Verification</h2>
        <pre>SELECT COUNT(*) AS role_count
FROM dbo.core_user_roles
WHERE user_id = 10001;</pre>
        <?php if ($roleCount === null): ?>
            <p class="fail">Role count could not be read.</p>
        <?php else: ?>
            <p>
                core_user_roles count =
                <strong class="<?= $roleCount === '2' ? 'ok' : 'warn' ?>"><?= erp_arw_h($roleCount) ?></strong>
            </p>
            <?php if ($roleCount === '2'): ?>
                <p class="ok">Expected count = 2 confirmed.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>7. Deferred Boundaries</h2>
        <ul>
            <li><strong>Real Role Assignment:</strong> DEFERRED</li>
            <li><strong>Revocation:</strong> DEFERRED</li>
            <li><strong>Tenant Scope:</strong> DEFERRED</li>
            <li><strong>Production Deploy:</strong> DEFERRED</li>
        </ul>
    </div>

    <div class="card">
        <h2>8. Security Warnings</h2>
        <ul>
            <li><strong>Viewer Mode = READ ONLY</strong></li>
            <li><strong>No Form Submit</strong></li>
            <li><strong>No Write Operation</strong></li>
            <li><strong>No Permission Mutation</strong></li>
            <li><strong>No Role Assignment</strong></li>
        </ul>
    </div>

    <div class="card">
        <h2>9. Next Step</h2>
        <p>Create test document after Browser test and Commit/Push.</p>
    </div>
</div>
</body>
</html>
