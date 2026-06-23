<?php
/**
 * MOGHARE360 ERP — Phase 4 Inventory & Purchase CLI Test
 */

declare(strict_types=1);

/** @var list<string> */
const P4IP_TABLES = [
    'erp_inventory_items',
    'erp_stock_locations',
    'erp_stock_balances',
    'erp_part_reservations',
    'erp_suppliers',
    'erp_purchase_requests',
    'erp_stock_movements',
    'erp_inventory_purchase_history',
];

/** @var list<string> */
const P4IP_EXTENSION_TABLES = [
    'erp_inventory_purchase_requests',
    'erp_inventory_stock_movements',
];

/** @var list<string> */
const P4IP_PHASE1_TABLES = [
    'erp_customer_intakes',
    'erp_customer_contracts',
    'erp_customer_vehicle_bindings',
];

/** @var list<string> */
const P4IP_PHASE2_TABLES = [
    'erp_operation_cases',
    'erp_operation_service_steps',
    'erp_operation_history',
];

/** @var list<string> */
const P4IP_PHASE3_TABLES = [
    'erp_rule_definitions',
    'erp_rule_decisions',
    'erp_inventory_rule_requests',
    'erp_rule_audit_history',
];

/** @var list<string> */
const P4IP_PHP_FILES = [
    'public_html/includes/erp-inventory-purchase-helper.php',
    'public_html/erp-parts-catalog.php',
    'public_html/erp-stock-board.php',
    'public_html/erp-part-reserve.php',
    'public_html/submit-part-reserve.php',
    'public_html/erp-purchase-request-create.php',
    'public_html/submit-purchase-request.php',
    'public_html/erp-supplier-board.php',
    'public_html/erp-stock-movement-history.php',
    'public_html/submit-purchase-status-update.php',
];

/** @var list<string> */
const P4IP_FORBIDDEN_FILES = [
    'staff-auth.php',
    'access-control.php',
    'staff-login.php',
    'config.php',
    'config.example.php',
    'private/erp-config.php',
    'private/erp-config.example.php',
];

function p4ip_root(): string
{
    return dirname(__DIR__);
}

function p4ip_line(string $label, string $status): void
{
    echo str_pad($label, 52, '.') . ' ' . $status . PHP_EOL;
}

function p4ip_php_binary(): string
{
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $candidate) {
        if ($candidate === '') {
            continue;
        }
        if ($candidate === 'php' || is_file($candidate)) {
            return $candidate;
        }
    }
    return 'php';
}

function p4ip_require_helper(): void
{
    $path = p4ip_root() . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-inventory-purchase-helper.php';
    if (!is_file($path)) {
        throw new RuntimeException('erp-inventory-purchase-helper.php not found');
    }
    require_once $path;
}

echo 'PHASE 4 INVENTORY PURCHASE TEST' . PHP_EOL;
echo str_repeat('=', 52) . PHP_EOL;

$overallOk = true;
$failures = [];

p4ip_require_helper();

$connection = false;

try {
    $connection = inventory_db();
    if ($connection === false) {
        $overallOk = false;
        $failures[] = 'database connection';
        p4ip_line('Database connection', 'FAILED');
    } else {
        p4ip_line('Database connection', 'PASSED');
    }
} catch (Throwable) {
    $overallOk = false;
    $failures[] = 'database connection exception';
    p4ip_line('Database connection', 'FAILED');
}

if ($connection !== false) {
    foreach (P4IP_TABLES as $tableName) {
        $exists = inventory_table_exists($connection, $tableName);
        if (!$exists && $tableName === 'erp_purchase_requests') {
            $exists = inventory_table_exists($connection, 'erp_inventory_purchase_requests');
            $label = 'Table dbo.erp_purchase_requests (or extension)';
        } elseif (!$exists && $tableName === 'erp_stock_movements') {
            $exists = inventory_table_exists($connection, 'erp_inventory_stock_movements');
            $label = 'Table dbo.erp_stock_movements (or extension)';
        } else {
            $label = 'Table dbo.' . $tableName;
        }

        p4ip_line($label, $exists ? 'PASSED' : 'FAILED');

        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName;
            continue;
        }

        $actualTable = $tableName;
        if ($tableName === 'erp_purchase_requests' && !inventory_table_exists($connection, 'erp_purchase_requests')) {
            $actualTable = 'erp_inventory_purchase_requests';
        }
        if ($tableName === 'erp_stock_movements' && !inventory_table_exists($connection, 'erp_stock_movements')) {
            $actualTable = inventory_column_exists($connection, 'erp_stock_movements', 'inventory_item_id') ? 'erp_stock_movements' : 'erp_inventory_stock_movements';
        }

        if (inventory_table_exists($connection, $actualTable)) {
            $count = inventory_scalar($connection, 'SELECT COUNT(*) FROM dbo.' . $actualTable);
            p4ip_line('SELECT dbo.' . $actualTable, $count !== null ? 'PASSED (' . $count . ' rows)' : 'FAILED');
            if ($count === null) {
                $overallOk = false;
                $failures[] = 'select ' . $actualTable;
            }
        }
    }

    foreach (P4IP_EXTENSION_TABLES as $extTable) {
        p4ip_line('Extension dbo.' . $extTable, inventory_table_exists($connection, $extTable) ? 'EXISTS' : 'NOT_USED');
    }

    $mainLoc = inventory_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_stock_locations WHERE location_code = N'MAIN'");
    p4ip_line('Seed location MAIN', ($mainLoc !== null && (int)$mainLoc > 0) ? 'PASSED' : 'FAILED');
    if ($mainLoc === null || (int)$mainLoc < 1) {
        $overallOk = false;
        $failures[] = 'seed MAIN';
    }

    $pendingLoc = inventory_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_stock_locations WHERE location_code = N'PENDING'");
    p4ip_line('Seed location PENDING', ($pendingLoc !== null && (int)$pendingLoc > 0) ? 'PASSED' : 'FAILED');
    if ($pendingLoc === null || (int)$pendingLoc < 1) {
        $overallOk = false;
        $failures[] = 'seed PENDING';
    }

    $supplierSeed = inventory_scalar($connection, "SELECT COUNT(*) FROM dbo.erp_suppliers WHERE supplier_code = N'INTERNAL-MANUAL'");
    p4ip_line('Seed supplier INTERNAL-MANUAL', ($supplierSeed !== null && (int)$supplierSeed > 0) ? 'PASSED' : 'FAILED');
    if ($supplierSeed === null || (int)$supplierSeed < 1) {
        $overallOk = false;
        $failures[] = 'seed supplier';
    }

    echo str_repeat('-', 52) . PHP_EOL;
    echo 'Phase 1 table report:' . PHP_EOL;
    foreach (P4IP_PHASE1_TABLES as $tableName) {
        p4ip_line('Phase1 dbo.' . $tableName, inventory_table_exists($connection, $tableName) ? 'EXISTS' : 'MISSING');
    }

    echo 'Phase 2 table report:' . PHP_EOL;
    foreach (P4IP_PHASE2_TABLES as $tableName) {
        p4ip_line('Phase2 dbo.' . $tableName, inventory_table_exists($connection, $tableName) ? 'EXISTS' : 'MISSING');
    }

    echo 'Phase 3 table report:' . PHP_EOL;
    foreach (P4IP_PHASE3_TABLES as $tableName) {
        p4ip_line('Phase3 dbo.' . $tableName, inventory_table_exists($connection, $tableName) ? 'EXISTS' : 'MISSING');
    }

    @odbc_close($connection);
}

$cssPath = p4ip_root() . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'moghare360-ui' . DIRECTORY_SEPARATOR . 'moghare360-inventory-purchase.css';
p4ip_line('CSS file moghare360-inventory-purchase.css', is_file($cssPath) ? 'PASSED' : 'FAILED');
if (!is_file($cssPath)) {
    $overallOk = false;
    $failures[] = 'css missing';
}

foreach (P4IP_PHP_FILES as $relativePath) {
    $fullPath = p4ip_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!is_file($fullPath)) {
        $overallOk = false;
        $failures[] = 'missing ' . $relativePath;
        p4ip_line('File exists: ' . basename($relativePath), 'FAILED');
        continue;
    }

    p4ip_line('File exists: ' . basename($relativePath), 'PASSED');

    $output = [];
    $exitCode = 0;
    exec(p4ip_php_binary() . ' -l ' . escapeshellarg($fullPath) . ' 2>&1', $output, $exitCode);
    p4ip_line('PHP syntax: ' . basename($relativePath), $exitCode === 0 ? 'PASSED' : 'FAILED');

    if ($exitCode !== 0) {
        $overallOk = false;
        $failures[] = 'syntax ' . $relativePath;
    }
}

foreach (P4IP_FORBIDDEN_FILES as $relativePath) {
    $fullPath = p4ip_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!is_file($fullPath)) {
        p4ip_line('Forbidden check: ' . $relativePath, 'SKIP');
        continue;
    }

    $gitCheck = [];
    exec('git -C ' . escapeshellarg(p4ip_root()) . ' status --short -- ' . escapeshellarg($relativePath) . ' 2>&1', $gitCheck);
    $modified = $gitCheck !== [] && trim(implode('', $gitCheck)) !== '';
    p4ip_line('Forbidden check: ' . $relativePath, $modified ? 'WARNING MODIFIED' : 'PASSED (unchanged)');

    if ($modified) {
        $overallOk = false;
        $failures[] = 'forbidden ' . $relativePath;
    }
}

echo str_repeat('-', 52) . PHP_EOL;

if ($overallOk) {
    echo 'RESULT: PASSED' . PHP_EOL;
} else {
    echo 'RESULT: FAILED' . PHP_EOL;
    echo 'Failures: ' . implode(', ', $failures) . PHP_EOL;
}

echo 'PHASE 4 INVENTORY PURCHASE TEST COMPLETE' . PHP_EOL;

exit($overallOk ? 0 : 1);
