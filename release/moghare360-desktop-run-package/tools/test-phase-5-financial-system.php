<?php
/**
 * MOGHARE360 ERP — Phase 5 Financial System CLI Test
 */

declare(strict_types=1);

/** @var list<string> */
const P5FS_TABLES = [
    'erp_finance_service_price_list',
    'erp_finance_labour_rates',
    'erp_finance_part_margin_rules',
    'erp_jobcard_cost_headers',
    'erp_jobcard_cost_lines',
    'erp_payment_records',
    'erp_invoice_previews',
    'erp_financial_summary_snapshots',
    'erp_finance_history',
];

/** @var list<string> */
const P5FS_SEEDS = [
    ['table' => 'erp_finance_labour_rates', 'column' => 'rate_code', 'value' => 'DEFAULT-LABOUR'],
    ['table' => 'erp_finance_part_margin_rules', 'column' => 'rule_code', 'value' => 'DEFAULT-PART-MARGIN'],
    ['table' => 'erp_finance_service_price_list', 'column' => 'service_code', 'value' => 'MANUAL-SERVICE'],
];

/** @var list<string> */
const P5FS_PHASE1_TABLES = [
    'erp_customer_intakes',
    'erp_customer_contracts',
    'erp_customer_vehicle_bindings',
];

/** @var list<string> */
const P5FS_PHASE2_TABLES = [
    'erp_operation_cases',
    'erp_operation_service_steps',
    'erp_operation_history',
];

/** @var list<string> */
const P5FS_PHASE3_TABLES = [
    'erp_rule_decisions',
    'erp_service_approval_requests',
];

/** @var list<string> */
const P5FS_PHASE4_TABLES = [
    'erp_inventory_items',
    'erp_stock_balances',
    'erp_part_reservations',
    'erp_suppliers',
];

/** @var list<string> */
const P5FS_PHP_FILES = [
    'public_html/includes/erp-pricing-engine.php',
    'public_html/erp-finance-control-center.php',
    'public_html/erp-service-price-list.php',
    'public_html/erp-jobcard-cost-preview.php',
    'public_html/erp-payment-tracking.php',
    'public_html/submit-payment-record.php',
    'public_html/erp-invoice-preview.php',
];

/** @var list<string> */
const P5FS_FORBIDDEN_FILES = [
    'staff-auth.php',
    'access-control.php',
    'staff-login.php',
    'config.php',
    'config.example.php',
    'private/erp-config.php',
    'private/erp-config.example.php',
];

function p5fs_root(): string { return dirname(__DIR__); }

function p5fs_line(string $label, string $status): void
{
    echo str_pad($label, 52, '.') . ' ' . $status . PHP_EOL;
}

function p5fs_php_binary(): string
{
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '' ) continue;
        if ($c === 'php' || is_file($c)) return $c;
    }
    return 'php';
}

function p5fs_require_helper(): void
{
    $path = p5fs_root() . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-pricing-engine.php';
    if (!is_file($path)) throw new RuntimeException('erp-pricing-engine.php not found');
    require_once $path;
}

echo 'PHASE 5 FINANCIAL SYSTEM TEST' . PHP_EOL;
echo str_repeat('=', 52) . PHP_EOL;

$overallOk = true;
$failures = [];

p5fs_require_helper();

$connection = false;

try {
    $connection = pricing_db();
    if ($connection === false) {
        $overallOk = false;
        $failures[] = 'database connection';
        p5fs_line('Database connection', 'FAILED');
    } else {
        p5fs_line('Database connection', 'PASSED');
    }
} catch (Throwable) {
    $overallOk = false;
    $failures[] = 'database connection exception';
    p5fs_line('Database connection', 'FAILED');
}

if ($connection !== false) {
    foreach (P5FS_TABLES as $tableName) {
        $exists = pricing_table_exists($connection, $tableName);
        p5fs_line('Table dbo.' . $tableName, $exists ? 'PASSED' : 'FAILED');
        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName;
            continue;
        }
        $count = pricing_scalar($connection, 'SELECT COUNT(*) FROM dbo.' . $tableName);
        p5fs_line('SELECT dbo.' . $tableName, $count !== null ? 'PASSED (' . $count . ' rows)' : 'FAILED');
        if ($count === null) {
            $overallOk = false;
            $failures[] = 'select ' . $tableName;
        }
    }

    foreach (P5FS_SEEDS as $seed) {
        $count = pricing_scalar($connection, 'SELECT COUNT(*) FROM dbo.' . $seed['table'] . ' WHERE ' . $seed['column'] . ' = ?', [$seed['value']]);
        $ok = $count !== null && (int)$count > 0;
        p5fs_line('Seed ' . $seed['value'], $ok ? 'PASSED' : 'FAILED');
        if (!$ok) {
            $overallOk = false;
            $failures[] = 'seed ' . $seed['value'];
        }
    }

    echo str_repeat('-', 52) . PHP_EOL;
    echo 'Phase 1 table report:' . PHP_EOL;
    foreach (P5FS_PHASE1_TABLES as $t) {
        p5fs_line('Phase1 dbo.' . $t, pricing_table_exists($connection, $t) ? 'EXISTS' : 'MISSING');
    }
    echo 'Phase 2 table report:' . PHP_EOL;
    foreach (P5FS_PHASE2_TABLES as $t) {
        p5fs_line('Phase2 dbo.' . $t, pricing_table_exists($connection, $t) ? 'EXISTS' : 'MISSING');
    }
    echo 'Phase 3 table report:' . PHP_EOL;
    foreach (P5FS_PHASE3_TABLES as $t) {
        p5fs_line('Phase3 dbo.' . $t, pricing_table_exists($connection, $t) ? 'EXISTS' : 'MISSING');
    }
    echo 'Phase 4 table report:' . PHP_EOL;
    foreach (P5FS_PHASE4_TABLES as $t) {
        p5fs_line('Phase4 dbo.' . $t, pricing_table_exists($connection, $t) ? 'EXISTS' : 'MISSING');
    }

    @odbc_close($connection);
}

$cssPath = p5fs_root() . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'moghare360-ui' . DIRECTORY_SEPARATOR . 'moghare360-financial-system.css';
p5fs_line('CSS file moghare360-financial-system.css', is_file($cssPath) ? 'PASSED' : 'FAILED');
if (!is_file($cssPath)) {
    $overallOk = false;
    $failures[] = 'css missing';
}

foreach (P5FS_PHP_FILES as $relativePath) {
    $fullPath = p5fs_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($fullPath)) {
        $overallOk = false;
        $failures[] = 'missing ' . $relativePath;
        p5fs_line('File exists: ' . basename($relativePath), 'FAILED');
        continue;
    }
    p5fs_line('File exists: ' . basename($relativePath), 'PASSED');
    $output = [];
    $exitCode = 0;
    exec(p5fs_php_binary() . ' -l ' . escapeshellarg($fullPath) . ' 2>&1', $output, $exitCode);
    p5fs_line('PHP syntax: ' . basename($relativePath), $exitCode === 0 ? 'PASSED' : 'FAILED');
    if ($exitCode !== 0) {
        $overallOk = false;
        $failures[] = 'syntax ' . $relativePath;
    }
}

foreach (P5FS_FORBIDDEN_FILES as $relativePath) {
    $fullPath = p5fs_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($fullPath)) {
        p5fs_line('Forbidden check: ' . $relativePath, 'SKIP');
        continue;
    }
    $gitCheck = [];
    exec('git -C ' . escapeshellarg(p5fs_root()) . ' status --short -- ' . escapeshellarg($relativePath) . ' 2>&1', $gitCheck);
    $modified = $gitCheck !== [] && trim(implode('', $gitCheck)) !== '';
    p5fs_line('Forbidden check: ' . $relativePath, $modified ? 'WARNING MODIFIED' : 'PASSED (unchanged)');
    if ($modified) {
        $overallOk = false;
        $failures[] = 'forbidden ' . $relativePath;
    }
}

echo str_repeat('-', 52) . PHP_EOL;
echo $overallOk ? 'RESULT: PASSED' . PHP_EOL : 'RESULT: FAILED' . PHP_EOL;
if (!$overallOk) echo 'Failures: ' . implode(', ', $failures) . PHP_EOL;
echo 'PHASE 5 FINANCIAL SYSTEM TEST COMPLETE' . PHP_EOL;
exit($overallOk ? 0 : 1);
