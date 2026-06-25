<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP JobCard Part Usage Read-Only List
 *
 * Mission 24 - SELECT only. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M24_PLATFORM_OWNER_ID = 10001;
const ERP_M24_LIST_ACTION = 'jobcard.part.list';

/** @var array<string, string> */
const ERP_M24_PLACEHOLDER_ACTIONS = [
    'jobcard.part.use' => 'placeholder_jobcard_part_use',
    'jobcard.part.list' => 'placeholder_jobcard_part_list',
    'jobcard.part.view' => 'placeholder_jobcard_part_view',
];

function erp_m24_list_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m24_list_helper_candidates(string $fileName): array
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
function erp_m24_list_fetch_rows($connection, string $sql, array $params = []): array
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

function erp_m24_list_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m24_list_display(string $value): string
{
    return erp_m24_list_h(trim($value) === '' ? '—' : $value);
}

/**
 * @return array<string, mixed>
 */
function erp_m24_list_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M24_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL'];
    }

    if ($userId === ERP_M24_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED'];
    }

    return ['allowed' => false, 'label' => 'FAIL'];
}

try {
    erp_m24_list_require_first_existing(erp_m24_list_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m24_list_require_first_existing(erp_m24_list_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    echo '<!DOCTYPE html><html lang="en"><body><p>List could not be loaded.</p></body></html>';
    exit(1);
}

$userId = ERP_M24_PLATFORM_OWNER_ID;
$guardListLabel = 'FAIL';
$errorMessage = '';
$overallOk = false;
$listRows = [];
$connection = false;

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();

    if (erp_auth_current_user_id() !== $userId || erp_auth_load_current_user($connection) === null) {
        throw new RuntimeException('Access denied.');
    }

    $guardList = erp_m24_list_guard_eval($connection, $userId, ERP_M24_LIST_ACTION);
    $guardListLabel = (string)($guardList['label'] ?? 'FAIL');

    if (empty($guardList['allowed'])) {
        throw new RuntimeException('Access denied.');
    }

    $listRows = erp_m24_list_fetch_rows(
        $connection,
        'SELECT TOP 100
            u.part_usage_id,
            u.jobcard_id,
            u.service_operation_id,
            p.part_code,
            p.part_name,
            u.quantity,
            u.usage_status,
            u.created_at
        FROM dbo.erp_jobcard_part_usage u
        INNER JOIN dbo.erp_parts p ON p.part_id = u.part_id
        WHERE u.is_active = 1
        ORDER BY u.part_usage_id DESC'
    );

    $overallOk = in_array($guardListLabel, ['OK', 'PLACEHOLDER', 'PLACEHOLDER_OWNER_ALLOWED'], true);
} catch (Throwable $exception) {
    $errorMessage = 'Part usage list could not be completed.';
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
    <title>Mission 24 - Part Usage List</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; }
        .wrap { max-width: 1400px; margin: 0 auto; padding: 24px; }
        .banner { background: #7f1d1d; color: #fff; padding: 12px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL READ-ONLY PART USAGE LIST - NO WRITE</div>

    <div class="card">
        <h1>JobCard Part Usage List</h1>
        <?php if ($errorMessage !== ''): ?><p class="fail"><?= erp_m24_list_h($errorMessage) ?></p><?php endif; ?>
        <p><a href="erp-jobcard-part-use.php">Open part use prototype</a></p>
    </div>

    <div class="card">
        <table>
            <tr><th>user_id</th><td><?= erp_m24_list_h((string)$userId) ?></td></tr>
            <tr><th>guard jobcard.part.list</th><td><?= erp_m24_list_h($guardListLabel) ?></td></tr>
            <tr><th>Overall</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
        </table>
    </div>

    <div class="card">
        <h2>Part Usages</h2>
        <?php if ($listRows === []): ?>
            <p>No part usage rows found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>JobCard ID</th>
                        <th>Part Usage ID</th>
                        <th>Service Op ID</th>
                        <th>Part Code</th>
                        <th>Part Name</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listRows as $row): ?>
                        <tr>
                            <td><?= erp_m24_list_display($row['jobcard_id'] ?? '') ?></td>
                            <td><?= erp_m24_list_display($row['part_usage_id'] ?? '') ?></td>
                            <td><?= erp_m24_list_display($row['service_operation_id'] ?? '') ?></td>
                            <td><?= erp_m24_list_display($row['part_code'] ?? '') ?></td>
                            <td><?= erp_m24_list_display($row['part_name'] ?? '') ?></td>
                            <td><?= erp_m24_list_display($row['quantity'] ?? '') ?></td>
                            <td><?= erp_m24_list_display($row['usage_status'] ?? '') ?></td>
                            <td><?= erp_m24_list_display($row['created_at'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
