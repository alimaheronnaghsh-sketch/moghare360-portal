<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Purchase Request Read-Only Detail
 *
 * Mission 26 - SELECT only. No form. No POST. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M26_PLATFORM_OWNER_ID = 10001;
const ERP_M26_VIEW_ACTION = 'purchase.request.view';

/** @var array<string, string> */
const ERP_M26_PLACEHOLDER_ACTIONS = [
    'purchase.request.create' => 'placeholder_purchase_request_create',
    'purchase.request.view' => 'placeholder_purchase_request_view',
    'purchase.request.list' => 'placeholder_purchase_request_list',
    'purchase.request.submit' => 'placeholder_purchase_request_submit',
    'purchase.request.approve' => 'placeholder_purchase_request_approve',
    'purchase.request.reject' => 'placeholder_purchase_request_reject',
    'purchase.request.cancel' => 'placeholder_purchase_request_cancel',
];

function erp_m26_detail_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m26_detail_helper_candidates(string $fileName): array
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
function erp_m26_detail_fetch_rows($connection, string $sql, array $params = []): array
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
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

function erp_m26_detail_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m26_detail_display(string $value): string
{
    return erp_m26_detail_h(trim($value) === '' ? '—' : $value);
}

function erp_m26_detail_parse_purchase_request_id(): int
{
    if (!isset($_GET['purchase_request_id'])) {
        return 0;
    }

    $raw = trim((string)$_GET['purchase_request_id']);

    return ($raw !== '' && ctype_digit($raw)) ? (int)$raw : 0;
}

/**
 * @return array<string, mixed>
 */
function erp_m26_detail_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M26_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === ERP_M26_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

try {
    erp_m26_detail_require_first_existing(erp_m26_detail_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m26_detail_require_first_existing(erp_m26_detail_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Detail Error</title></head><body>';
    echo '<p>ERP Purchase Request detail could not be loaded.</p></body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');
$userId = ERP_M26_PLATFORM_OWNER_ID;
$username = '—';
$guardViewLabel = 'FAIL';
$connectionStatus = 'FAIL';
$connectionDetail = 'Not connected';
$errorMessage = '';
$overallOk = false;
$requestRow = [];
$historyRows = [];
$selectedPurchaseRequestId = 0;
$connection = false;

try {
    erp_auth_context_start();

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

    $guardView = erp_m26_detail_guard_eval($connection, $userId, ERP_M26_VIEW_ACTION);
    $guardViewLabel = (string)($guardView['label'] ?? 'FAIL');

    if (empty($guardView['allowed'])) {
        throw new RuntimeException('Access denied.');
    }

    $selectedPurchaseRequestId = erp_m26_detail_parse_purchase_request_id();

    if ($selectedPurchaseRequestId <= 0) {
        $latestRows = erp_m26_detail_fetch_rows(
            $connection,
            'SELECT TOP 1 purchase_request_id FROM dbo.erp_purchase_requests ORDER BY purchase_request_id DESC'
        );

        if ($latestRows !== []) {
            $selectedPurchaseRequestId = (int)($latestRows[0]['purchase_request_id'] ?? 0);
        }
    }

    if ($selectedPurchaseRequestId > 0) {
        $requestRows = erp_m26_detail_fetch_rows(
            $connection,
            'SELECT TOP 1
                pr.*,
                j.jobcard_number,
                j.jobcard_status
            FROM dbo.erp_purchase_requests pr
            INNER JOIN dbo.erp_jobcards j ON j.jobcard_id = pr.jobcard_id
            WHERE pr.purchase_request_id = ?',
            [$selectedPurchaseRequestId]
        );

        if ($requestRows !== []) {
            $requestRow = $requestRows[0];
        }

        $historyRows = erp_m26_detail_fetch_rows(
            $connection,
            'SELECT
                history_id,
                purchase_request_id,
                jobcard_id,
                service_operation_id,
                action_code,
                old_status,
                new_status,
                changed_by_user_id,
                changed_at,
                change_note
            FROM dbo.erp_purchase_request_history
            WHERE purchase_request_id = ?
            ORDER BY history_id',
            [$selectedPurchaseRequestId]
        );
    }

    $overallOk = $connectionStatus === 'OK'
        && $requestRow !== []
        && in_array($guardViewLabel, ['OK', 'PLACEHOLDER', 'PLACEHOLDER_OWNER_ALLOWED'], true);
} catch (Throwable $exception) {
    $errorMessage = 'ERP Purchase Request detail could not be completed.';
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
    <title>Mission 26 - Purchase Request Detail</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; line-height: 1.5; }
        .wrap { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .banner { background: #7f1d1d; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        h1, h2 { margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.92rem; margin-bottom: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; width: 240px; }
        .list-table th { width: auto; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL READ-ONLY PURCHASE REQUEST DETAIL - NO WRITE</div>

    <div class="card">
        <h1>Purchase Request Detail</h1>
        <p>Mission 26 - SELECT only - No form - No write</p>
        <?php if ($errorMessage !== ''): ?>
            <p class="fail"><?= erp_m26_detail_h($errorMessage) ?></p>
        <?php endif; ?>
        <p>
            <a href="erp-purchase-request-readonly-list.php">Open read-only list</a>
            |
            <a href="erp-purchase-request-create.php">Open create prototype</a>
        </p>
    </div>

    <div class="card">
        <h2>Summary</h2>
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m26_detail_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection</th><td><?= erp_m26_detail_h($connectionStatus) ?> — <?= erp_m26_detail_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m26_detail_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m26_detail_h($username) ?></td></tr>
                <tr><th>guard purchase.request.view</th><td><?= erp_m26_detail_h($guardViewLabel) ?></td></tr>
                <tr><th>Selected purchase_request_id</th><td><?= erp_m26_detail_h((string)$selectedPurchaseRequestId) ?></td></tr>
                <tr><th>Overall Status</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
            </tbody>
        </table>
    </div>

    <?php if ($requestRow === []): ?>
        <div class="card">
            <p>No Purchase Request record found for the selected purchase_request_id.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>Purchase Request Header</h2>
            <table>
                <tbody>
                    <tr><th>Purchase Request ID</th><td><?= erp_m26_detail_display($requestRow['purchase_request_id'] ?? '') ?></td></tr>
                    <tr><th>JobCard ID</th><td><?= erp_m26_detail_display($requestRow['jobcard_id'] ?? '') ?></td></tr>
                    <tr><th>JobCard Number</th><td><?= erp_m26_detail_display($requestRow['jobcard_number'] ?? '') ?></td></tr>
                    <tr><th>JobCard Status</th><td><?= erp_m26_detail_display($requestRow['jobcard_status'] ?? '') ?></td></tr>
                    <tr><th>Service Operation ID</th><td><?= erp_m26_detail_display($requestRow['service_operation_id'] ?? '') ?></td></tr>
                    <tr><th>Part ID</th><td><?= erp_m26_detail_display($requestRow['part_id'] ?? '') ?></td></tr>
                    <tr><th>Requested Part Name</th><td><?= erp_m26_detail_display($requestRow['requested_part_name'] ?? '') ?></td></tr>
                    <tr><th>Requested Quantity</th><td><?= erp_m26_detail_display($requestRow['requested_quantity'] ?? '') ?></td></tr>
                    <tr><th>Request Reason</th><td><?= erp_m26_detail_display($requestRow['request_reason'] ?? '') ?></td></tr>
                    <tr><th>Request Status</th><td><?= erp_m26_detail_display($requestRow['request_status'] ?? '') ?></td></tr>
                    <tr><th>Requested By User ID</th><td><?= erp_m26_detail_display($requestRow['requested_by_user_id'] ?? '') ?></td></tr>
                    <tr><th>Requested At</th><td><?= erp_m26_detail_display($requestRow['requested_at'] ?? '') ?></td></tr>
                    <tr><th>Supplier ID</th><td><?= erp_m26_detail_display($requestRow['supplier_id'] ?? '') ?></td></tr>
                    <tr><th>Estimated Unit Cost</th><td><?= erp_m26_detail_display($requestRow['estimated_unit_cost'] ?? '') ?></td></tr>
                    <tr><th>Currency Code</th><td><?= erp_m26_detail_display($requestRow['currency_code'] ?? '') ?></td></tr>
                    <tr><th>Is Active</th><td><?= erp_m26_detail_display($requestRow['is_active'] ?? '') ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>History</h2>
            <?php if ($historyRows === []): ?>
                <p>No history rows found.</p>
            <?php else: ?>
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>History ID</th>
                            <th>Action Code</th>
                            <th>Old Status</th>
                            <th>New Status</th>
                            <th>Changed By</th>
                            <th>Changed At</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyRows as $historyRow): ?>
                            <tr>
                                <td><?= erp_m26_detail_display($historyRow['history_id'] ?? '') ?></td>
                                <td><?= erp_m26_detail_display($historyRow['action_code'] ?? '') ?></td>
                                <td><?= erp_m26_detail_display($historyRow['old_status'] ?? '') ?></td>
                                <td><?= erp_m26_detail_display($historyRow['new_status'] ?? '') ?></td>
                                <td><?= erp_m26_detail_display($historyRow['changed_by_user_id'] ?? '') ?></td>
                                <td><?= erp_m26_detail_display($historyRow['changed_at'] ?? '') ?></td>
                                <td><?= erp_m26_detail_display($historyRow['change_note'] ?? '') ?></td>
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
