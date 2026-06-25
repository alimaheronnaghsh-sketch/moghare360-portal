<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP JobCard Read-Only Detail
 *
 * Mission 17 - SELECT only. No form. No POST. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M17_PLATFORM_OWNER_ID = 10001;
const ERP_M17_VIEW_ACTION = 'jobcard.view';

/** @var array<string, string> */
const ERP_M17_PLACEHOLDER_ACTIONS = [
    'jobcard.create' => 'placeholder_jobcard_create',
    'jobcard.view' => 'placeholder_jobcard_view',
    'jobcard.list' => 'placeholder_jobcard_list',
];

function erp_m17_detail_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m17_detail_helper_candidates(string $fileName): array
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
function erp_m17_detail_fetch_rows($connection, string $sql, array $params = []): array
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

function erp_m17_detail_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m17_detail_display(string $value): string
{
    return erp_m17_detail_h(trim($value) === '' ? '—' : $value);
}

function erp_m17_detail_parse_jobcard_id(): int
{
    if (!isset($_GET['jobcard_id'])) {
        return 0;
    }

    $raw = trim((string)$_GET['jobcard_id']);

    if ($raw === '' || !ctype_digit($raw)) {
        return 0;
    }

    return (int)$raw;
}

/**
 * @return array<string, mixed>
 */
function erp_m17_detail_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M17_PLACEHOLDER_ACTIONS[$actionKey])) {
        return [
            'allowed' => false,
            'label' => 'FAIL',
            'placeholder' => false,
        ];
    }

    if ($userId === ERP_M17_PLATFORM_OWNER_ID) {
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

try {
    erp_m17_detail_require_first_existing(erp_m17_detail_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m17_detail_require_first_existing(erp_m17_detail_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><title>Detail Error</title></head><body>';
    echo '<p>ERP JobCard detail could not be loaded.</p>';
    echo '</body></html>';
    exit(1);
}

$phpVersion = PHP_VERSION;
$odbcAvailable = extension_loaded('odbc');

$userId = ERP_M17_PLATFORM_OWNER_ID;
$username = '—';
$rolesText = '—';
$permissionCount = 0;
$guardViewLabel = 'FAIL';
$connectionStatus = 'FAIL';
$connectionDetail = 'Not connected';
$errorMessage = '';
$overallOk = false;
$jobcardRow = [];
$historyRows = [];
$selectedJobcardId = 0;
$connection = false;

try {
    erp_auth_context_start();

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

    $guardView = erp_m17_detail_guard_eval($connection, $userId, ERP_M17_VIEW_ACTION);
    $guardViewLabel = (string)($guardView['label'] ?? 'FAIL');

    if (empty($guardView['allowed'])) {
        throw new RuntimeException('Access denied.');
    }

    $selectedJobcardId = erp_m17_detail_parse_jobcard_id();

    if ($selectedJobcardId <= 0) {
        $latestRows = erp_m17_detail_fetch_rows(
            $connection,
            'SELECT TOP 1 jobcard_id FROM dbo.erp_jobcards ORDER BY jobcard_id DESC'
        );

        if ($latestRows !== []) {
            $selectedJobcardId = (int)($latestRows[0]['jobcard_id'] ?? 0);
        }
    }

    if ($selectedJobcardId > 0) {
        $jobcardRows = erp_m17_detail_fetch_rows(
            $connection,
            'SELECT TOP 1
                j.*,
                c.full_name,
                c.primary_mobile,
                v.brand,
                v.model,
                v.plate_number,
                v.vin
            FROM dbo.erp_jobcards j
            JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
            JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
            WHERE j.jobcard_id = ?',
            [$selectedJobcardId]
        );

        if ($jobcardRows !== []) {
            $jobcardRow = $jobcardRows[0];
        }

        $historyRows = erp_m17_detail_fetch_rows(
            $connection,
            'SELECT
                history_id,
                jobcard_id,
                change_type,
                previous_status,
                new_status,
                change_summary,
                changed_by_user_id,
                changed_at
            FROM dbo.erp_jobcard_change_history
            WHERE jobcard_id = ?
            ORDER BY history_id',
            [$selectedJobcardId]
        );
    }

    $overallOk = $connectionStatus === 'OK'
        && $jobcardRow !== []
        && in_array($guardViewLabel, ['OK', 'PLACEHOLDER', 'PLACEHOLDER_OWNER_ALLOWED'], true);
} catch (Throwable $exception) {
    $errorMessage = 'ERP JobCard detail could not be completed.';
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
    <title>Mission 17 - JobCard Detail</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; line-height: 1.5; }
        .wrap { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .banner { background: #7f1d1d; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        h1, h2 { margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.92rem; margin-bottom: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; width: 240px; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL READ-ONLY JOBCARD DETAIL - NO WRITE</div>

    <div class="card">
        <h1>JobCard Detail</h1>
        <p>Mission 17 - SELECT only - No form - No write</p>
        <?php if ($errorMessage !== ''): ?>
            <p class="fail"><?= erp_m17_detail_h($errorMessage) ?></p>
        <?php endif; ?>
        <p>
            <a href="erp-jobcard-readonly-list.php">Open read-only list</a>
            |
            <a href="erp-jobcard-create.php">Open create prototype</a>
        </p>
    </div>

    <div class="card">
        <h2>Summary</h2>
        <table>
            <tbody>
                <tr><th>PHP version</th><td><?= erp_m17_detail_h($phpVersion) ?></td></tr>
                <tr><th>ODBC extension</th><td><?= $odbcAvailable ? 'Available' : 'Missing' ?></td></tr>
                <tr><th>Connection status</th><td><?= erp_m17_detail_h($connectionStatus) ?> — <?= erp_m17_detail_h($connectionDetail) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m17_detail_h((string)$userId) ?></td></tr>
                <tr><th>username</th><td><?= erp_m17_detail_h($username) ?></td></tr>
                <tr><th>roles</th><td><?= erp_m17_detail_h($rolesText) ?></td></tr>
                <tr><th>permissions count</th><td><?= erp_m17_detail_h((string)$permissionCount) ?></td></tr>
                <tr><th>guard jobcard.view</th><td><?= erp_m17_detail_h($guardViewLabel) ?></td></tr>
                <tr><th>Selected jobcard_id</th><td><?= erp_m17_detail_h((string)$selectedJobcardId) ?></td></tr>
                <tr><th>Overall Status</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
            </tbody>
        </table>
    </div>

    <?php if ($jobcardRow === []): ?>
        <div class="card">
            <p>No JobCard record found for the selected jobcard_id.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>JobCard Header</h2>
            <table>
                <tbody>
                    <tr><th>JobCard ID</th><td><?= erp_m17_detail_display($jobcardRow['jobcard_id'] ?? '') ?></td></tr>
                    <tr><th>JobCard Number</th><td><?= erp_m17_detail_display($jobcardRow['jobcard_number'] ?? '') ?></td></tr>
                    <tr><th>Status</th><td><?= erp_m17_detail_display($jobcardRow['jobcard_status'] ?? '') ?></td></tr>
                    <tr><th>Priority</th><td><?= erp_m17_detail_display($jobcardRow['priority_level'] ?? '') ?></td></tr>
                    <tr><th>Lifecycle State</th><td><?= erp_m17_detail_display($jobcardRow['lifecycle_state'] ?? '') ?></td></tr>
                    <tr><th>Created At</th><td><?= erp_m17_detail_display($jobcardRow['created_at'] ?? '') ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Customer Summary</h2>
            <table>
                <tbody>
                    <tr><th>Customer ID</th><td><?= erp_m17_detail_display($jobcardRow['customer_id'] ?? '') ?></td></tr>
                    <tr><th>Full Name</th><td><?= erp_m17_detail_display($jobcardRow['full_name'] ?? '') ?></td></tr>
                    <tr><th>Primary Mobile</th><td><?= erp_m17_detail_display($jobcardRow['primary_mobile'] ?? '') ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Vehicle Summary</h2>
            <table>
                <tbody>
                    <tr><th>Vehicle ID</th><td><?= erp_m17_detail_display($jobcardRow['vehicle_id'] ?? '') ?></td></tr>
                    <tr><th>Brand / Model</th><td><?= erp_m17_detail_display(trim(($jobcardRow['brand'] ?? '') . ' ' . ($jobcardRow['model'] ?? ''))) ?></td></tr>
                    <tr><th>Plate Number</th><td><?= erp_m17_detail_display($jobcardRow['plate_number'] ?? '') ?></td></tr>
                    <tr><th>VIN</th><td><?= erp_m17_detail_display($jobcardRow['vin'] ?? '') ?></td></tr>
                    <tr><th>Relation ID</th><td><?= erp_m17_detail_display($jobcardRow['relation_id'] ?? '') ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Reception Data</h2>
            <table>
                <tbody>
                    <tr><th>Reception At</th><td><?= erp_m17_detail_display($jobcardRow['reception_at'] ?? '') ?></td></tr>
                    <tr><th>Promised At</th><td><?= erp_m17_detail_display($jobcardRow['promised_at'] ?? '') ?></td></tr>
                    <tr><th>Intake Mileage</th><td><?= erp_m17_detail_display($jobcardRow['intake_mileage'] ?? '') ?></td></tr>
                    <tr><th>Fuel Level</th><td><?= erp_m17_detail_display($jobcardRow['fuel_level'] ?? '') ?></td></tr>
                    <tr><th>Customer Complaint</th><td><?= erp_m17_detail_display($jobcardRow['customer_complaint'] ?? '') ?></td></tr>
                    <tr><th>Requested Services Summary</th><td><?= erp_m17_detail_display($jobcardRow['requested_services_summary'] ?? '') ?></td></tr>
                    <tr><th>Initial Vehicle Condition</th><td><?= erp_m17_detail_display($jobcardRow['initial_vehicle_condition'] ?? '') ?></td></tr>
                    <tr><th>Internal Notes</th><td><?= erp_m17_detail_display($jobcardRow['internal_notes'] ?? '') ?></td></tr>
                    <tr><th>Reception User ID</th><td><?= erp_m17_detail_display($jobcardRow['reception_user_id'] ?? '') ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>History Timeline</h2>
            <?php if ($historyRows === []): ?>
                <p>No history rows found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>History ID</th>
                            <th>Change Type</th>
                            <th>Previous Status</th>
                            <th>New Status</th>
                            <th>Summary</th>
                            <th>Changed By</th>
                            <th>Changed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyRows as $historyRow): ?>
                            <tr>
                                <td><?= erp_m17_detail_display($historyRow['history_id'] ?? '') ?></td>
                                <td><?= erp_m17_detail_display($historyRow['change_type'] ?? '') ?></td>
                                <td><?= erp_m17_detail_display($historyRow['previous_status'] ?? '') ?></td>
                                <td><?= erp_m17_detail_display($historyRow['new_status'] ?? '') ?></td>
                                <td><?= erp_m17_detail_display($historyRow['change_summary'] ?? '') ?></td>
                                <td><?= erp_m17_detail_display($historyRow['changed_by_user_id'] ?? '') ?></td>
                                <td><?= erp_m17_detail_display($historyRow['changed_at'] ?? '') ?></td>
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
