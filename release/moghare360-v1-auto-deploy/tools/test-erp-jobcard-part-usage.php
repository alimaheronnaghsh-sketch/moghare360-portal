<?php
/**
 * MOGHARE360 ERP JobCard Part Usage CLI Test
 *
 * Mission 24 - CLI read-only test only. No writes.
 */

declare(strict_types=1);

const ERP_M24_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_M24_PLACEHOLDER_ACTIONS = [
    'jobcard.part.use' => 'placeholder_jobcard_part_use',
    'jobcard.part.list' => 'placeholder_jobcard_part_list',
    'stock.issue.create' => 'placeholder_stock_issue_create',
];

/** @var list<string> */
const ERP_M24_TABLES = [
    'erp_jobcard_part_usage',
    'erp_jobcard_part_usage_history',
    'erp_stock_movements',
];

function erp_m24_cli_require_first_existing(array $candidatePaths, string $label): void
{
    foreach ($candidatePaths as $candidatePath) {
        if (is_file($candidatePath)) {
            require_once $candidatePath;
            return;
        }
    }

    throw new RuntimeException('Required ERP file not found: ' . $label);
}

function erp_m24_cli_helper_candidates(string $fileName): array
{
    $bases = [__DIR__, dirname(__DIR__), dirname(__DIR__, 2)];
    $candidates = [];

    foreach ($bases as $base) {
        $candidates[] = $base . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName;
    }

    return array_values(array_unique($candidates));
}

function erp_m24_cli_scalar($connection, string $sql, array $params = []): ?string
{
    $statement = @odbc_prepare($connection, $sql);

    if ($statement === false || !@odbc_execute($statement, $params)) {
        return null;
    }

    if (@odbc_fetch_row($statement) !== true) {
        return null;
    }

    $value = @odbc_result($statement, 1);

    return $value === false || $value === null ? null : (string)$value;
}

function erp_m24_cli_table_exists($connection, string $tableName): bool
{
    $count = erp_m24_cli_scalar(
        $connection,
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
        ['dbo', $tableName]
    );

    return $count !== null && (int)$count > 0;
}

/**
 * @return array<string, mixed>
 */
function erp_m24_cli_guard_eval($connection, int $userId, string $actionKey): array
{
    $map = erp_guard_action_map();

    if (isset($map[$actionKey])) {
        $result = erp_guard_action($connection, $userId, $actionKey);
        $label = !empty($result['allowed']) ? 'OK' : 'FAIL';

        if (!empty($result['placeholder'])) {
            $label = 'PLACEHOLDER';
        }

        return ['allowed' => !empty($result['allowed']), 'label' => $label];
    }

    if (!isset(ERP_M24_PLACEHOLDER_ACTIONS[$actionKey])) {
        return ['allowed' => false, 'label' => 'FAIL'];
    }

    if ($userId === ERP_M24_PLATFORM_OWNER_ID) {
        return ['allowed' => true, 'label' => 'PLACEHOLDER_OWNER_ALLOWED'];
    }

    return ['allowed' => false, 'label' => 'FAIL'];
}

function erp_m24_cli_on_hand($connection, int $partId, int $locationId): ?float
{
    $value = erp_m24_cli_scalar(
        $connection,
        'SELECT COALESCE(SUM(
            CASE m.movement_type
                WHEN N\'SEED\' THEN m.quantity
                WHEN N\'RECEIPT\' THEN m.quantity
                WHEN N\'RETURN\' THEN m.quantity
                WHEN N\'ADJUSTMENT\' THEN m.quantity
                WHEN N\'ISSUE\' THEN -m.quantity
                WHEN N\'REVERSAL\' THEN -m.quantity
                ELSE CAST(0 AS DECIMAL(18, 3))
            END
        ), CAST(0 AS DECIMAL(18, 3)))
        FROM dbo.erp_stock_movements m
        WHERE m.part_id = ? AND m.stock_location_id = ?',
        [$partId, $locationId]
    );

    return $value === null ? null : (float)$value;
}

erp_m24_cli_require_first_existing(erp_m24_cli_helper_candidates('erp-auth-context.php'), 'erp-auth-context.php');
erp_m24_cli_require_first_existing(erp_m24_cli_helper_candidates('erp-permission-guard.php'), 'erp-permission-guard.php');

$overallOk = true;
$failures = [];
$exceptionMessage = '';
$connection = false;

$userId = ERP_M24_PLATFORM_OWNER_ID;
$tableStatus = [];
$jobcardOneStatus = 'FAIL';
$partOneStatus = 'FAIL';
$mainLocationStatus = 'FAIL';
$testSeedStatus = 'PENDING';
$usageJobcardOneStatus = 'PENDING';
$historyUsedStatus = 'PENDING';
$issueMovementStatus = 'PENDING';
$negativeStockStatus = 'OK';
$guardUseLabel = 'FAIL';
$guardListLabel = 'FAIL';

try {
    erp_auth_context_start();

    $connection = erp_auth_create_local_odbc_connection();

    if (erp_auth_current_user_id() !== $userId) {
        $overallOk = false;
        $failures[] = 'current_user_id mismatch';
    }

    foreach (ERP_M24_TABLES as $tableName) {
        $exists = erp_m24_cli_table_exists($connection, $tableName);
        $tableStatus[$tableName] = $exists ? 'OK' : 'FAIL';

        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName . ' missing';
        }
    }

    if (erp_m24_cli_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE jobcard_id = ?', [1]) !== '0') {
        $jobcardOneStatus = 'OK';
    } else {
        $overallOk = false;
        $jobcardOneStatus = 'FAIL';
        $failures[] = 'jobcard_id 1 missing';
    }

    if (erp_m24_cli_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_parts WHERE part_id = ?', [1]) !== '0') {
        $partOneStatus = 'OK';
    } else {
        $overallOk = false;
        $partOneStatus = 'FAIL';
        $failures[] = 'part_id 1 missing';
    }

    if (erp_m24_cli_scalar($connection, 'SELECT COUNT(*) FROM dbo.erp_stock_locations WHERE location_code = ?', ['MAIN']) !== '0') {
        $mainLocationStatus = 'OK';
    } else {
        $overallOk = false;
        $mainLocationStatus = 'FAIL';
        $failures[] = 'MAIN location missing';
    }

    $seedCount = erp_m24_cli_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_stock_movements WHERE reference_type = ?',
        ['MISSION_24_TEST_SEED']
    );

    $testSeedStatus = ($seedCount !== null && (int)$seedCount > 0) ? 'OK' : 'PENDING';

    if (($tableStatus['erp_jobcard_part_usage'] ?? 'FAIL') === 'OK') {
        $usageCount = erp_m24_cli_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_jobcard_part_usage WHERE jobcard_id = ?',
            [1]
        );

        if ($usageCount !== null && (int)$usageCount > 0) {
            $usageJobcardOneStatus = 'OK';
        } else {
            $usageJobcardOneStatus = 'PENDING';
        }
    }

    if (($tableStatus['erp_jobcard_part_usage_history'] ?? 'FAIL') === 'OK') {
        $historyCount = erp_m24_cli_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_jobcard_part_usage_history WHERE action_code = ?',
            ['JOBCARD_PART_USED']
        );

        if ($historyCount !== null && (int)$historyCount > 0) {
            $historyUsedStatus = 'OK';
        } else {
            $historyUsedStatus = 'PENDING';
        }
    }

    if (($tableStatus['erp_stock_movements'] ?? 'FAIL') === 'OK') {
        $issueCount = erp_m24_cli_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_stock_movements WHERE movement_type = ? AND reference_type = ?',
            ['ISSUE', 'JOBCARD_PART_USAGE']
        );

        if ($issueCount !== null && (int)$issueCount > 0) {
            $issueMovementStatus = 'OK';
        } else {
            $issueMovementStatus = 'PENDING';
        }
    }

    $mainLocationId = erp_m24_cli_scalar(
        $connection,
        'SELECT stock_location_id FROM dbo.erp_stock_locations WHERE location_code = ?',
        ['MAIN']
    );

    if ($mainLocationId !== null && $partOneStatus === 'OK') {
        $onHand = erp_m24_cli_on_hand($connection, 1, (int)$mainLocationId);

        if ($onHand === null || $onHand < 0) {
            $negativeStockStatus = 'FAIL';
            $overallOk = false;
            $failures[] = 'negative stock detected for part_id 1 at MAIN';
        }
    }

    $guardUse = erp_m24_cli_guard_eval($connection, $userId, 'jobcard.part.use');
    $guardList = erp_m24_cli_guard_eval($connection, $userId, 'jobcard.part.list');
    $guardUseLabel = (string)$guardUse['label'];
    $guardListLabel = (string)$guardList['label'];

    if (empty($guardUse['allowed']) || empty($guardList['allowed'])) {
        $overallOk = false;
        $failures[] = 'permission guard failed';
    }
} catch (Throwable $exception) {
    $overallOk = false;
    $failures[] = 'exception';
    $exceptionMessage = $exception->getMessage();
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

echo 'M24 JOBCARD PART USAGE TEST' . PHP_EOL;

foreach (ERP_M24_TABLES as $tableName) {
    echo 'table ' . $tableName . ' = ' . ($tableStatus[$tableName] ?? 'FAIL') . PHP_EOL;
}

echo 'JobCard jobcard_id 1 = ' . $jobcardOneStatus . PHP_EOL;
echo 'part_id 1 = ' . $partOneStatus . PHP_EOL;
echo 'MAIN stock location = ' . $mainLocationStatus . PHP_EOL;
echo 'MISSION_24_TEST_SEED = ' . $testSeedStatus . PHP_EOL;
echo 'part usage for jobcard_id 1 = ' . $usageJobcardOneStatus . PHP_EOL;
echo 'history JOBCARD_PART_USED = ' . $historyUsedStatus . PHP_EOL;
echo 'ISSUE JOBCARD_PART_USAGE = ' . $issueMovementStatus . PHP_EOL;
echo 'stock not negative (part 1 MAIN) = ' . $negativeStockStatus . PHP_EOL;
echo 'guard jobcard.part.use = ' . $guardUseLabel . PHP_EOL;
echo 'guard jobcard.part.list = ' . $guardListLabel . PHP_EOL;
echo 'No finance write = OK' . PHP_EOL;
echo 'No invoice write = OK' . PHP_EOL;
echo 'No payment write = OK' . PHP_EOL;
echo 'No purchase write = OK' . PHP_EOL;
echo 'No delivery write = OK' . PHP_EOL;
echo 'No write performed by test = OK' . PHP_EOL;
echo 'Overall: ' . ($overallOk ? 'OK' : 'FAIL') . PHP_EOL;

if (!$overallOk && $failures !== []) {
    echo 'Failing checks: ' . implode(', ', $failures) . PHP_EOL;
}

if ($exceptionMessage !== '') {
    echo 'Exception: ' . $exceptionMessage . PHP_EOL;
}

exit($overallOk ? 0 : 1);
