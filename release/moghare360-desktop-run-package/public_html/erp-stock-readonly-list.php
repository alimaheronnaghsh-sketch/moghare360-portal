<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Stock Read-Only List
 *
 * Mission 22 - SELECT only. Aggregated quantity_on_hand from stock movements.
 * No form. No POST. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

const ERP_M22_PLATFORM_OWNER_ID = 10001;
const ERP_M22_STOCK_VIEW_ACTION = 'stock.view';

/** @var array<string, string> */
const ERP_M22_PLACEHOLDER_ACTIONS = [
    'parts.create' => 'placeholder_parts_create',
    'parts.list' => 'placeholder_parts_list',
    'stock.view' => 'placeholder_stock_view',
];

function erp_m22_stock_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m22_stock_helper_candidates(string $fileName): array
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
function erp_m22_stock_fetch_rows($connection, string $sql, array $params = []): array
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

function erp_m22_stock_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_m22_stock_display(string $value): string
{
    return erp_m22_stock_h(trim($value) === '' ? '—' : $value);
}

/**
 * @return array<string, mixed>
 */
function erp_m22_stock_guard_eval($connection, int $userId, string $actionKey): array
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

    if (!isset(ERP_M22_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => false];
    }

    if ($userId === ERP_M22_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED', 'placeholder' => true];
    }

    return ['allowed' => false, 'label' => 'FAIL', 'placeholder' => true];
}

try {
    erp_m22_stock_require_first_existing(erp_m22_stock_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
    erp_m22_stock_require_first_existing(erp_m22_stock_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');
} catch (Throwable $exception) {
    echo '<!DOCTYPE html><html lang="en"><body><p>ERP Stock list could not be loaded.</p></body></html>';
    exit(1);
}

$userId = ERP_M22_PLATFORM_OWNER_ID;
$guardStockLabel = 'FAIL';
$connectionStatus = 'FAIL';
$errorMessage = '';
$overallOk = false;
$listRows = [];
$connection = false;

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();
    $connectionStatus = 'OK';

    if (erp_auth_current_user_id() !== $userId) {
        throw new RuntimeException('Access denied.');
    }

    if (erp_auth_load_current_user($connection) === null) {
        throw new RuntimeException('Access denied.');
    }

    $guardStock = erp_m22_stock_guard_eval($connection, $userId, ERP_M22_STOCK_VIEW_ACTION);
    $guardStockLabel = (string)($guardStock['label'] ?? 'FAIL');

    if (empty($guardStock['allowed'])) {
        throw new RuntimeException('Access denied.');
    }

    $listRows = erp_m22_stock_fetch_rows(
        $connection,
        'SELECT
            p.part_id,
            p.part_code,
            p.part_name,
            sl.stock_location_id,
            sl.location_code,
            COALESCE(SUM(
                CASE m.movement_type
                    WHEN N\'SEED\' THEN m.quantity
                    WHEN N\'RECEIPT\' THEN m.quantity
                    WHEN N\'RETURN\' THEN m.quantity
                    WHEN N\'ADJUSTMENT\' THEN m.quantity
                    WHEN N\'ISSUE\' THEN -m.quantity
                    WHEN N\'REVERSAL\' THEN -m.quantity
                    ELSE CAST(0 AS DECIMAL(18, 3))
                END
            ), CAST(0 AS DECIMAL(18, 3))) AS quantity_on_hand
        FROM dbo.erp_parts p
        INNER JOIN dbo.erp_stock_locations sl ON sl.is_active = 1
        LEFT JOIN dbo.erp_stock_movements m
            ON m.part_id = p.part_id
           AND m.stock_location_id = sl.stock_location_id
        WHERE p.is_active = 1
        GROUP BY
            p.part_id,
            p.part_code,
            p.part_name,
            sl.stock_location_id,
            sl.location_code
        ORDER BY p.part_id DESC, sl.location_code ASC'
    );

    $overallOk = in_array($guardStockLabel, ['OK', 'PLACEHOLDER', 'PLACEHOLDER_OWNER_ALLOWED'], true);
} catch (Throwable $exception) {
    $errorMessage = 'ERP Stock list could not be completed.';
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
    <title>Mission 22 - Stock List</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; line-height: 1.5; }
        .wrap { max-width: 1400px; margin: 0 auto; padding: 24px; }
        .banner { background: #7f1d1d; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
        th { background: #f3f4f6; }
        .summary-table th { width: 240px; }
        .ok { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">LOCAL READ-ONLY STOCK LIST - NO WRITE - NO CONSUMPTION</div>

    <div class="card">
        <h1>Stock Read-Only List</h1>
        <p>Mission 22 - Aggregated quantity_on_hand from dbo.erp_stock_movements (read-only)</p>
        <?php if ($errorMessage !== ''): ?><p class="fail"><?= erp_m22_stock_h($errorMessage) ?></p><?php endif; ?>
        <p>
            <a href="erp-part-create.php">Open create prototype</a> |
            <a href="erp-part-readonly-list.php">Open parts list</a>
        </p>
    </div>

    <div class="card">
        <h2>Summary</h2>
        <table class="summary-table">
            <tbody>
                <tr><th>Connection</th><td><?= erp_m22_stock_h($connectionStatus) ?></td></tr>
                <tr><th>user_id</th><td><?= erp_m22_stock_h((string)$userId) ?></td></tr>
                <tr><th>guard stock.view</th><td><?= erp_m22_stock_h($guardStockLabel) ?></td></tr>
                <tr><th>Overall Status</th><td class="<?= $overallOk ? 'ok' : 'fail' ?>"><?= $overallOk ? 'OK' : 'FAIL' ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Stock On Hand (by Part and Location)</h2>
        <?php if ($listRows === []): ?>
            <p>No stock rows to display. Create parts via prototype; movements are not written in Mission 22.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Part ID</th>
                        <th>Part Code</th>
                        <th>Part Name</th>
                        <th>Location ID</th>
                        <th>Location Code</th>
                        <th>Quantity On Hand</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listRows as $row): ?>
                        <tr>
                            <td><?= erp_m22_stock_display($row['part_id'] ?? '') ?></td>
                            <td><?= erp_m22_stock_display($row['part_code'] ?? '') ?></td>
                            <td><?= erp_m22_stock_display($row['part_name'] ?? '') ?></td>
                            <td><?= erp_m22_stock_display($row['stock_location_id'] ?? '') ?></td>
                            <td><?= erp_m22_stock_display($row['location_code'] ?? '') ?></td>
                            <td><?= erp_m22_stock_display($row['quantity_on_hand'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
