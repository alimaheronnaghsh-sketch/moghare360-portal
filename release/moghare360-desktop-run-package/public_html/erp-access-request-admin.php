<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Access Request Admin UI
 *
 * Mission 13 - Admin Controlled Read-Only UI
 * SELECT only. No form. No POST. No workflow write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M13_DEFAULT_USER_ID = 10001;
const ERP_M13_DEFAULT_REQUEST_ID = 4;
const ERP_M13_WORKFLOW_VIEWER_FILE = 'erp-access-request-workflow-readonly.php';

/** @var list<string> */
const ERP_M13_REQUIRED_HISTORY = [
    'ACCESS_REQUEST_SUBMITTED',
    'ACCESS_REQUEST_UNDER_REVIEW',
    'ACCESS_REQUEST_APPROVED',
    'ACCESS_REQUEST_APPLIED',
];

function erp_m13_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m13_helper_candidates(string $fileName): array
{
    return [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
}

/**
 * @return list<array<string, string>>
 */
function erp_m13_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false) {
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

function erp_m13_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m13_display(string $value): string
{
    return erp_m13_h(trim($value) === '' ? '—' : $value);
}

function erp_m13_row_value(array $row, string $key): string
{
    return (string)($row[$key] ?? '');
}

function erp_m13_guard_label(array $result): string
{
    if (!empty($result['placeholder'])) {
        return 'PLACEHOLDER';
    }

    return !empty($result['allowed']) ? 'OK' : 'FAIL';
}

function erp_m13_resolve_request_id(): int
{
    if (!isset($_GET['request_id'])) {
        return ERP_M13_DEFAULT_REQUEST_ID;
    }

    $raw = $_GET['request_id'];

    if (is_int($raw)) {
        return $raw > 0 ? $raw : ERP_M13_DEFAULT_REQUEST_ID;
    }

    if (is_string($raw) && ctype_digit(trim($raw))) {
        $requestId = (int)trim($raw);

        return $requestId > 0 ? $requestId : ERP_M13_DEFAULT_REQUEST_ID;
    }

    return ERP_M13_DEFAULT_REQUEST_ID;
}

function erp_m13_self_path(): string
{
    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? 'erp-access-request-admin.php'));

    return erp_m13_h($script);
}

try {
    erp_m13_require_first_existing(
        erp_m13_helper_candidates('erp-auth-context.php'),
        'erp-auth-context.php'
    );

    erp_m13_require_first_existing(
        erp_m13_helper_candidates('erp-permission-guard.php'),
        'erp-permission-guard.php'
    );
} catch (Throwable $exception) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Access Request Admin Error</title></head><body>';
    echo '<p>ERP Access Request Admin UI could not be loaded.</p>';
    echo '<p>Required helper files were not found in approved include paths.</p>';
    echo '</body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');

$connectionStatus = 'FAIL';
$connectionDetail = 'Not connected';
$errorMessage = '';
$connection = false;
$noWriteOk = true;
$noFormOk = true;
$overallOk = false;

$userId = ERP_M13_DEFAULT_USER_ID;
$username = '—';
$rolesText = '—';
$permissionCount = 0;
$guardView = 'FAIL';
$guardList = 'FAIL';
$guardApprove = 'FAIL';
$guardApply = 'FAIL';

$selectedRequestId = erp_m13_resolve_request_id();
$requestListRows = [];
$selectedRequestRow = null;
$itemRows = [];
$approvalRows = [];
$historyRows = [];
$timelineStatus = 'INCOMPLETE';
$historyStatus = [];
$workflowViewerExists = is_file(__DIR__ . DIRECTORY_SEPARATOR . ERP_M13_WORKFLOW_VIEWER_FILE);

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();
    $connectionStatus = 'OK';
    $connectionDetail = 'ODBC Trusted Connection connected';

    $resolvedUserId = erp_auth_current_user_id();

    if ($resolvedUserId !== $userId) {
        throw new RuntimeException('Unexpected current user id.');
    }

    $user = erp_auth_load_current_user($connection);

    if ($user === null) {
        throw new RuntimeException('Current user could not be loaded.');
    }

    $username = (string)($user['username'] ?? '—');

    $rolesResult = erp_auth_current_roles($connection, $userId);
    $roleKeys = erp_auth_context_role_keys($rolesResult);
    $rolesText = $roleKeys !== [] ? implode(', ', $roleKeys) : '—';

    $permissionsResult = erp_auth_current_permissions($connection, $userId);
    $permissionKeys = erp_auth_context_permission_keys($permissionsResult);
    $permissionCount = count($permissionKeys);

    $viewResult = erp_guard_action($connection, $userId, 'access.request.view');
    $listResult = erp_guard_action($connection, $userId, 'access.request.list');
    $approveResult = erp_guard_action($connection, $userId, 'access.request.approve');
    $applyResult = erp_guard_action($connection, $userId, 'access.request.apply');

    $guardView = erp_m13_guard_label($viewResult);
    $guardList = erp_m13_guard_label($listResult);
    $guardApprove = erp_m13_guard_label($approveResult);
    $guardApply = erp_m13_guard_label($applyResult);

    $requestListRows = erp_m13_fetch_rows(
        $connection,
        'SELECT
            request_id,
            request_number,
            request_type,
            request_state,
            subject_user_id,
            requested_by_user_id,
            submitted_at,
            decided_at,
            applied_at,
            created_at,
            updated_at
        FROM dbo.core_access_requests
        ORDER BY request_id DESC'
    );

    $selectedRows = erp_m13_fetch_rows(
        $connection,
        'SELECT TOP 1
            request_id,
            request_number,
            request_type,
            request_state,
            subject_user_id,
            requested_by_user_id,
            submitted_at,
            decided_at,
            applied_at,
            applied_by_user_id,
            created_at,
            updated_at
        FROM dbo.core_access_requests
        WHERE request_id = ?',
        [$selectedRequestId]
    );

    $selectedRequestRow = $selectedRows[0] ?? null;

    if ($selectedRequestRow !== null) {
        $itemRows = erp_m13_fetch_rows(
            $connection,
            'SELECT
                item_id,
                item_type,
                item_decision,
                role_id,
                department_id,
                position_id,
                module_key,
                permission_key,
                is_temporary,
                effective_from,
                expires_at,
                created_at
            FROM dbo.core_access_request_items
            WHERE request_id = ?
            ORDER BY item_id',
            [$selectedRequestId]
        );

        $approvalRows = erp_m13_fetch_rows(
            $connection,
            'SELECT
                approval_id,
                request_id,
                approver_user_id,
                approver_capacity,
                decision,
                comment,
                decided_at,
                created_at
            FROM dbo.core_access_approvals
            WHERE request_id = ?
            ORDER BY approval_id',
            [$selectedRequestId]
        );

        $historyRows = erp_m13_fetch_rows(
            $connection,
            'SELECT
                history_id,
                change_type,
                entity_type,
                entity_id,
                changed_by_user_id,
                changed_at
            FROM dbo.core_access_change_history
            WHERE request_id = ?
            ORDER BY changed_at, history_id',
            [$selectedRequestId]
        );
    }

    $historyChangeTypes = array_map(
        static fn(array $row): string => strtoupper(trim(erp_m13_row_value($row, 'change_type'))),
        $historyRows
    );

    foreach (ERP_M13_REQUIRED_HISTORY as $requiredType) {
        $historyStatus[$requiredType] = in_array($requiredType, $historyChangeTypes, true) ? 'OK' : 'MISSING';
    }

    if ($selectedRequestId === ERP_M13_DEFAULT_REQUEST_ID) {
        $timelineStatus = !in_array('MISSING', $historyStatus, true) ? 'COMPLETE' : 'INCOMPLETE';
    } else {
        $timelineStatus = $historyRows !== [] ? 'PARTIAL' : 'EMPTY';
    }

    $overallOk = $connectionStatus === 'OK'
        && $username === 'mahin.paradigm.owner'
        && $guardView === 'OK'
        && $guardList === 'OK'
        && $requestListRows !== []
        && $selectedRequestRow !== null
        && $noWriteOk
        && $noFormOk;
} catch (Throwable $exception) {
    $errorMessage = 'ERP Access Request Admin UI could not be completed.';
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
    <title>Mission 13 - Access Request Admin UI</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f6f8;
            color: #1f2937;
            line-height: 1.5;
        }

        .wrap {
            max-width: 1200px;
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
            font-size: 1.4rem;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 1.05rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
        }

        .summary-table th {
            width: 280px;
        }

        .ok {
            color: #166534;
            font-weight: bold;
        }

        .fail {
            color: #b91c1c;
            font-weight: bold;
        }

        .warn {
            color: #b45309;
            font-weight: bold;
        }

        ul {
            margin: 8px 0;
            padding-left: 20px;
        }

        a {
            color: #1d4ed8;
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
    <div class="banner">LOCAL READ-ONLY ACCESS REQUEST ADMIN UI - NO WORKFLOW WRITE - REMOVE OR PROTECT BEFORE DEPLOYMENT</div>

    <div class="card">
        <h1>Access Request Admin UI</h1>
        <p>Mission 13 - Admin Controlled Read-Only UI - SELECT only - No form - No write</p>
        <?php if ($errorMessage !== ''): ?>
            <p class="fail"><?= erp_m13_h($errorMessage) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Auth and Permission Summary</h2>
        <table class="summary-table">
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m13_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection status</th><td class="<?= $connectionStatus === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m13_h($connectionStatus) ?> — <?= erp_m13_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m13_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m13_h($username) ?></td></tr>
                <tr><th>roles</th><td><?= erp_m13_h($rolesText) ?></td></tr>
                <tr><th>permissions count</th><td><?= erp_m13_h((string)$permissionCount) ?></td></tr>
                <tr><th>guard access.request.view</th><td class="<?= $guardView === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m13_h($guardView) ?></td></tr>
                <tr><th>guard access.request.list</th><td class="<?= $guardList === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m13_h($guardList) ?></td></tr>
                <tr><th>guard access.request.approve</th><td class="<?= $guardApprove === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m13_h($guardApprove) ?></td></tr>
                <tr><th>guard access.request.apply</th><td class="<?= $guardApply === 'OK' ? 'ok' : 'fail' ?>"><?= erp_m13_h($guardApply) ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>1. Access Request Admin Summary</h2>
        <ul>
            <li><strong>Viewer Mode</strong> = READ ONLY</li>
            <li><strong>Workflow Write</strong> = DISABLED</li>
            <li><strong>Real Assignment</strong> = NOT PERFORMED</li>
            <li><strong>No Form Submit</strong></li>
            <li><strong>No Direct Action Execution</strong></li>
        </ul>
    </div>

    <div class="card">
        <h2>2. Access Request List</h2>
        <?php if ($requestListRows === []): ?>
            <p class="fail">No access request rows found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>request_id</th>
                        <th>request_number</th>
                        <th>request_type</th>
                        <th>request_state</th>
                        <th>subject_user_id</th>
                        <th>requested_by_user_id</th>
                        <th>submitted_at</th>
                        <th>decided_at</th>
                        <th>applied_at</th>
                        <th>created_at</th>
                        <th>updated_at</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requestListRows as $listRow): ?>
                        <?php $listRequestId = erp_m13_row_value($listRow, 'request_id'); ?>
                        <tr>
                            <td><?= erp_m13_display($listRequestId) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($listRow, 'request_number')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($listRow, 'request_type')) ?></td>
                            <td><strong><?= erp_m13_display(erp_m13_row_value($listRow, 'request_state')) ?></strong></td>
                            <td><?= erp_m13_display(erp_m13_row_value($listRow, 'subject_user_id')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($listRow, 'requested_by_user_id')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($listRow, 'submitted_at')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($listRow, 'decided_at')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($listRow, 'applied_at')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($listRow, 'created_at')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($listRow, 'updated_at')) ?></td>
                            <td><a href="<?= erp_m13_self_path() ?>?request_id=<?= erp_m13_h($listRequestId) ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>3. Selected Request Detail</h2>
        <p>Selected request_id = <strong><?= erp_m13_h((string)$selectedRequestId) ?></strong></p>
        <?php if ($selectedRequestRow === null): ?>
            <p class="fail">Selected request could not be loaded.</p>
        <?php else: ?>
            <table class="summary-table">
                <tbody>
                    <tr><th>request_id</th><td><?= erp_m13_display(erp_m13_row_value($selectedRequestRow, 'request_id')) ?></td></tr>
                    <tr><th>request_number</th><td><?= erp_m13_display(erp_m13_row_value($selectedRequestRow, 'request_number')) ?></td></tr>
                    <tr><th>request_type</th><td><?= erp_m13_display(erp_m13_row_value($selectedRequestRow, 'request_type')) ?></td></tr>
                    <tr><th>request_state</th><td><strong><?= erp_m13_display(erp_m13_row_value($selectedRequestRow, 'request_state')) ?></strong></td></tr>
                    <tr><th>subject_user_id</th><td><?= erp_m13_display(erp_m13_row_value($selectedRequestRow, 'subject_user_id')) ?></td></tr>
                    <tr><th>requested_by_user_id</th><td><?= erp_m13_display(erp_m13_row_value($selectedRequestRow, 'requested_by_user_id')) ?></td></tr>
                    <tr><th>submitted_at</th><td><?= erp_m13_display(erp_m13_row_value($selectedRequestRow, 'submitted_at')) ?></td></tr>
                    <tr><th>decided_at</th><td><?= erp_m13_display(erp_m13_row_value($selectedRequestRow, 'decided_at')) ?></td></tr>
                    <tr><th>applied_at</th><td><?= erp_m13_display(erp_m13_row_value($selectedRequestRow, 'applied_at')) ?></td></tr>
                    <tr><th>applied_by_user_id</th><td><?= erp_m13_display(erp_m13_row_value($selectedRequestRow, 'applied_by_user_id')) ?></td></tr>
                    <tr><th>created_at</th><td><?= erp_m13_display(erp_m13_row_value($selectedRequestRow, 'created_at')) ?></td></tr>
                    <tr><th>updated_at</th><td><?= erp_m13_display(erp_m13_row_value($selectedRequestRow, 'updated_at')) ?></td></tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>4. Request Items</h2>
        <?php if ($itemRows === []): ?>
            <p class="warn">No request item rows found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>item_id</th>
                        <th>item_type</th>
                        <th>item_decision</th>
                        <th>role_id</th>
                        <th>department_id</th>
                        <th>position_id</th>
                        <th>module_key</th>
                        <th>permission_key</th>
                        <th>is_temporary</th>
                        <th>effective_from</th>
                        <th>expires_at</th>
                        <th>created_at</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itemRows as $itemRow): ?>
                        <tr>
                            <td><?= erp_m13_display(erp_m13_row_value($itemRow, 'item_id')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($itemRow, 'item_type')) ?></td>
                            <td><strong><?= erp_m13_display(erp_m13_row_value($itemRow, 'item_decision')) ?></strong></td>
                            <td><?= erp_m13_display(erp_m13_row_value($itemRow, 'role_id')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($itemRow, 'department_id')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($itemRow, 'position_id')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($itemRow, 'module_key')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($itemRow, 'permission_key')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($itemRow, 'is_temporary')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($itemRow, 'effective_from')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($itemRow, 'expires_at')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($itemRow, 'created_at')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>5. Approval Result</h2>
        <?php if ($approvalRows === []): ?>
            <p class="warn">No approval rows found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>approval_id</th>
                        <th>request_id</th>
                        <th>approver_user_id</th>
                        <th>approver_capacity</th>
                        <th>decision</th>
                        <th>comment</th>
                        <th>decided_at</th>
                        <th>created_at</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approvalRows as $approvalRow): ?>
                        <tr>
                            <td><?= erp_m13_display(erp_m13_row_value($approvalRow, 'approval_id')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($approvalRow, 'request_id')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($approvalRow, 'approver_user_id')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($approvalRow, 'approver_capacity')) ?></td>
                            <td><strong><?= erp_m13_display(erp_m13_row_value($approvalRow, 'decision')) ?></strong></td>
                            <td><?= erp_m13_display(erp_m13_row_value($approvalRow, 'comment')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($approvalRow, 'decided_at')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($approvalRow, 'created_at')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>6. Workflow Timeline</h2>
        <p>
            Required change types for request_id = <?= erp_m13_h((string)ERP_M13_DEFAULT_REQUEST_ID) ?>:
            <?php foreach (ERP_M13_REQUIRED_HISTORY as $requiredType): ?>
                <span class="tag <?= ($historyStatus[$requiredType] ?? 'MISSING') === 'OK' ? '' : 'missing' ?>">
                    <?= erp_m13_h($requiredType) ?>
                </span>
            <?php endforeach; ?>
        </p>
        <p class="<?= $timelineStatus === 'COMPLETE' ? 'ok' : 'warn' ?>">
            Timeline status: <?= erp_m13_h($timelineStatus) ?>
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
                            <td><?= erp_m13_display(erp_m13_row_value($historyRow, 'history_id')) ?></td>
                            <td><strong><?= erp_m13_display(erp_m13_row_value($historyRow, 'change_type')) ?></strong></td>
                            <td><?= erp_m13_display(erp_m13_row_value($historyRow, 'entity_type')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($historyRow, 'entity_id')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($historyRow, 'changed_by_user_id')) ?></td>
                            <td><?= erp_m13_display(erp_m13_row_value($historyRow, 'changed_at')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>7. Read-Only Links</h2>
        <ul>
            <li>Detail links use GET <code>?request_id=[id]</code> on this page only.</li>
            <?php if ($workflowViewerExists): ?>
                <li>
                    Fixed workflow viewer for request_id = <?= erp_m13_h((string)ERP_M13_DEFAULT_REQUEST_ID) ?>:
                    <a href="<?= erp_m13_h(ERP_M13_WORKFLOW_VIEWER_FILE) ?>"><?= erp_m13_h(ERP_M13_WORKFLOW_VIEWER_FILE) ?></a>
                </li>
            <?php else: ?>
                <li class="warn">Fixed workflow viewer file not found in current runtime.</li>
            <?php endif; ?>
            <li>No submit, review, approve, or apply action links are provided.</li>
        </ul>
    </div>

    <div class="card">
        <h2>8. State-Only Warning</h2>
        <ul>
            <li><strong>APPLIED</strong> = State-Only</li>
            <li><strong>Real Assignment</strong> = NOT PERFORMED</li>
            <li><strong>core_user_roles write</strong> = FORBIDDEN</li>
            <li><strong>item_decision update</strong> = FORBIDDEN</li>
        </ul>
    </div>

    <div class="card">
        <h2>9. Overall Status</h2>
        <table class="summary-table">
            <tbody>
                <tr><th>No write performed</th><td class="ok"><?= $noWriteOk ? 'OK' : 'FAIL' ?></td></tr>
                <tr><th>No form exists</th><td class="ok"><?= $noFormOk ? 'OK' : 'FAIL' ?></td></tr>
                <tr><th>Overall Status</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
