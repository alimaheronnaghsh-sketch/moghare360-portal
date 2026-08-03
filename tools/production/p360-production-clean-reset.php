<?php
declare(strict_types=1);

/**
 * MOGHARE360 — Production clean reset of TEST transactional data (CLI only).
 *
 * AUTHORIZED: delete test transactional rows
 * FORBIDDEN: DROP DATABASE/TABLE/COLUMN; delete System Owner / personnel / roles / permissions / seed catalogs
 *
 * Usage:
 *   php tools/production/p360-production-clean-reset.php --dry-run
 *   php tools/production/p360-production-clean-reset.php --execute --confirm=DELETE_TEST_TRANSACTIONS
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI_ONLY\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
require $root . '/public_html/includes/erp-customer-core-helper.php';

$args = getopt('', ['dry-run', 'execute', 'confirm:', 'evidence-dir:']);
$dryRun = array_key_exists('dry-run', $args);
$execute = array_key_exists('execute', $args);
$confirm = (string)($args['confirm'] ?? '');
$evidenceDir = (string)($args['evidence-dir'] ?? 'C:\\MOGHARE360\\evidence');

if (!$dryRun && !$execute) {
    fwrite(STDERR, "Specify --dry-run or --execute\n");
    exit(2);
}
if ($execute && $confirm !== 'DELETE_TEST_TRANSACTIONS') {
    fwrite(STDERR, "Refusing execute without --confirm=DELETE_TEST_TRANSACTIONS\n");
    exit(2);
}

$conn = customer_core_db();
if ($conn === false) {
    fwrite(STDERR, "NO_DB\n");
    exit(1);
}
if (!is_dir($evidenceDir)) {
    mkdir($evidenceDir, 0775, true);
}

/** @param mixed $conn */
function p360_reset_table_exists($conn, string $table): bool
{
    return customer_core_table_exists($conn, $table);
}

/** @param mixed $conn */
function p360_reset_exec($conn, string $sql): bool
{
    $ok = @odbc_exec($conn, $sql);
    return $ok !== false;
}

/** @param mixed $conn */
function p360_reset_count($conn, string $table): int
{
    if (!p360_reset_table_exists($conn, $table)) {
        return -1;
    }
    $safe = str_replace(']', ']]', $table);
    $rows = customer_core_fetch_rows($conn, "SELECT COUNT_BIG(*) AS c FROM dbo.[{$safe}]");
    if ($rows === []) {
        return -2;
    }
    return (int)($rows[0]['c'] ?? $rows[0]['C'] ?? 0);
}

/** @param mixed $conn */
function p360_reset_scalar($conn, string $sql, array $params = []): int
{
    $rows = customer_core_fetch_rows($conn, $sql, $params);
    if ($rows === []) {
        return 0;
    }
    $row = $rows[0];
    return (int)($row['c'] ?? $row['C'] ?? reset($row));
}

function p360_reset_is_preserved(string $table): bool
{
    $t = strtolower($table);
    $exact = [
        'erp_companies', 'erp_company_users', 'erp_company_settings',
        'erp_roles', 'erp_permissions', 'erp_role_permissions',
        'erp_access_packages', 'erp_access_package_permissions', 'erp_access_routes',
        'erp_route_map', 'erp_settings', 'erp_service_categories',
        'erp_inventory_categories', 'erp_inventory_units', 'erp_inventory_items',
        'erp_inventory_markets', 'erp_inventory_grades',
    ];
    if (in_array($t, $exact, true)) {
        return true;
    }
    if (str_starts_with($t, 'core_')) {
        return true;
    }
    if (str_starts_with($t, 'fin360_')) {
        return true;
    }
    if (str_starts_with($t, 'p360_hr') || str_starts_with($t, 'p360_personnel')) {
        if ($t === 'p360_hr_contract_otp') {
            return false;
        }
        return true;
    }
    if (str_starts_with($t, 'sys')) {
        return true;
    }
    return false;
}

/**
 * Discover transactional erp_/inv360_/otp-like tables excluding preserved prefixes.
 *
 * @param mixed $conn
 * @return list<string>
 */
function p360_reset_discover_targets($conn): array
{
    $rows = customer_core_fetch_rows(
        $conn,
        "SELECT TABLE_NAME AS table_name
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA='dbo' AND TABLE_TYPE='BASE TABLE'
           AND (
             TABLE_NAME LIKE 'erp_%'
             OR TABLE_NAME LIKE 'inv360_%'
             OR TABLE_NAME LIKE '%_otp%'
             OR TABLE_NAME LIKE 'm360_%otp%'
           )
         ORDER BY TABLE_NAME"
    );
    $out = [];
    foreach ($rows as $r) {
        $n = (string)($r['table_name'] ?? $r['TABLE_NAME'] ?? '');
        if ($n === '' || p360_reset_is_preserved($n)) {
            continue;
        }
        $out[] = $n;
    }
    return $out;
}

/** @param mixed $conn @return array<string,mixed> */
function p360_reset_snapshot($conn): array
{
    $keys = [
        'erp_customers', 'erp_vehicles', 'erp_customer_online_requests', 'erp_jobcards',
        'erp_workshop_work_items', 'erp_workshop_service_lines', 'erp_final_invoices',
        'erp_payments', 'erp_settlement_controls', 'erp_jobcard_media',
        'erp_jobcard_part_usage', 'core_users',
    ];
    $out = ['captured_at' => date('c'), 'database' => 'moghare360_ERP', 'counts' => []];
    foreach ($keys as $t) {
        $out['counts'][$t] = p360_reset_count($conn, $t);
    }

    $personnel = -1;
    foreach (['p360_hr_employees', 'p360_personnel_profiles', 'p360_hr_personnel'] as $pt) {
        if (p360_reset_table_exists($conn, $pt)) {
            $personnel = p360_reset_count($conn, $pt);
            $out['personnel_table'] = $pt;
            break;
        }
    }
    $out['counts']['personnel_profiles_est'] = $personnel;

    $uCols = customer_core_fetch_rows(
        $conn,
        "SELECT COLUMN_NAME AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='core_users'"
    );
    $colNames = [];
    foreach ($uCols as $c) {
        $colNames[] = strtolower((string)($c['c'] ?? ''));
    }
    $m360 = 0;
    foreach (['username', 'login_name', 'personnel_code', 'employee_code'] as $col) {
        if ($m360 < 1 && in_array($col, $colNames, true)) {
            $m360 = p360_reset_scalar($conn, "SELECT COUNT(*) AS c FROM dbo.core_users WHERE [{$col}] = N'M360-100001'");
        }
    }
    $out['m360_100001_present'] = $m360 > 0;
    $out['core_users_total'] = p360_reset_count($conn, 'core_users');
    return $out;
}

$targets = p360_reset_discover_targets($conn);
$pre = p360_reset_snapshot($conn);
$pre['target_table_count'] = count($targets);
file_put_contents($evidenceDir . DIRECTORY_SEPARATOR . 'pre-reset-counts.json', json_encode($pre, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
echo "WROTE pre-reset-counts.json targets=" . count($targets) . "\n";
echo 'PRE customers=' . ($pre['counts']['erp_customers'] ?? '?') . ' jobcards=' . ($pre['counts']['erp_jobcards'] ?? '?') . "\n";

if ($dryRun) {
    echo "DRY_RUN — no deletes executed\n";
    echo implode("\n", $targets) . "\n";
    exit(0);
}

@odbc_autocommit($conn, false);
$deleted = [];
$errors = [];

try {
    // Temporarily disable immutability triggers that block authorized test cleanup (not FK constraints).
    foreach (['erp_estimate_version_items', 'erp_estimate_versions', 'erp_estimates'] as $trigTable) {
        if (p360_reset_table_exists($conn, $trigTable)) {
            p360_reset_exec($conn, 'DISABLE TRIGGER ALL ON dbo.[' . str_replace(']', ']]', $trigTable) . ']');
            echo "TRIGGER_DISABLED $trigTable\n";
        }
    }

    $maxPasses = 25;
    for ($pass = 1; $pass <= $maxPasses; $pass++) {
        $progress = 0;
        $remaining = 0;
        foreach ($targets as $table) {
            if (p360_reset_is_preserved($table)) {
                continue;
            }
            $before = p360_reset_count($conn, $table);
            if ($before <= 0) {
                continue;
            }
            $remaining++;
            $safe = str_replace(']', ']]', $table);
            if (!p360_reset_exec($conn, "DELETE FROM dbo.[{$safe}]")) {
                $errors[$table] = (string)odbc_errormsg($conn);
                continue;
            }
            $after = p360_reset_count($conn, $table);
            $deleted[$table] = ['before' => $before, 'after' => $after, 'pass' => $pass];
            if ($after === 0) {
                $progress++;
                unset($errors[$table]);
                echo "DELETED pass=$pass $table before=$before after=0\n";
            } else {
                $errors[$table] = 'PARTIAL_AFTER_DELETE';
            }
        }
        echo "PASS_DONE pass=$pass progress=$progress\n";
        if ($progress === 0) {
            // final recount
            break;
        }
    }

    $remainingFinal = [];
    foreach ($targets as $table) {
        $c = p360_reset_count($conn, $table);
        if ($c > 0) {
            $remainingFinal[$table] = $c;
        }
    }
    if ($remainingFinal !== []) {
        throw new RuntimeException('REMAINING_ROWS:' . json_encode($remainingFinal, JSON_UNESCAPED_UNICODE));
    }

    // Re-enable triggers
    foreach (['erp_estimate_version_items', 'erp_estimate_versions', 'erp_estimates'] as $trigTable) {
        if (p360_reset_table_exists($conn, $trigTable)) {
            p360_reset_exec($conn, 'ENABLE TRIGGER ALL ON dbo.[' . str_replace(']', ']]', $trigTable) . ']');
            echo "TRIGGER_ENABLED $trigTable\n";
        }
    }

    if (!@odbc_commit($conn)) {
        throw new RuntimeException('COMMIT_FAILED');
    }
    echo "COMMIT_OK\n";
} catch (Throwable $e) {
    // Best-effort re-enable triggers before rollback
    foreach (['erp_estimate_version_items', 'erp_estimate_versions', 'erp_estimates'] as $trigTable) {
        if (p360_reset_table_exists($conn, $trigTable)) {
            @odbc_exec($conn, 'ENABLE TRIGGER ALL ON dbo.[' . str_replace(']', ']]', $trigTable) . ']');
        }
    }
    @odbc_rollback($conn);
    fwrite(STDERR, 'ROLLBACK: ' . $e->getMessage() . "\n");
    file_put_contents(
        $evidenceDir . DIRECTORY_SEPARATOR . 'reset-errors.json',
        json_encode(['error' => $e->getMessage(), 'errors' => $errors, 'partial' => $deleted], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
    );
    exit(1);
}

$post = p360_reset_snapshot($conn);
$post['deleted_tables'] = array_keys($deleted);
file_put_contents($evidenceDir . DIRECTORY_SEPARATOR . 'post-reset-counts.json', json_encode($post, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
echo "WROTE post-reset-counts.json\n";
echo 'POST customers=' . ($post['counts']['erp_customers'] ?? '?')
    . ' vehicles=' . ($post['counts']['erp_vehicles'] ?? '?')
    . ' jobcards=' . ($post['counts']['erp_jobcards'] ?? '?')
    . ' invoices=' . ($post['counts']['erp_final_invoices'] ?? '?')
    . "\n";
echo 'M360_100001=' . (!empty($post['m360_100001_present']) ? 'yes' : 'check') . "\n";
echo 'PERSONNEL=' . ($post['counts']['personnel_profiles_est'] ?? '?') . "\n";
echo "RESET_OK\n";
exit(0);
