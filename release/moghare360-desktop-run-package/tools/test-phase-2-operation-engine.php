<?php
/**
 * MOGHARE360 ERP — Phase 2 Operation Engine CLI Test
 */

declare(strict_types=1);

/** @var list<string> */
const P2OE_TABLES = [
    'erp_operation_cases',
    'erp_operation_service_steps',
    'erp_operation_qc_decisions',
    'erp_operation_delivery_checks',
    'erp_operation_history',
];

/** @var list<string> */
const P2OE_PHASE1_TABLES = [
    'erp_customer_intakes',
    'erp_customer_contracts',
    'erp_customer_vehicle_bindings',
    'erp_customer_core_history',
];

/** @var list<string> */
const P2OE_PHP_FILES = [
    'public_html/erp-operation-control-center.php',
    'public_html/erp-technician-board.php',
    'public_html/erp-jobcard-operation-flow.php',
    'public_html/submit-service-status-update.php',
    'public_html/submit-qc-decision.php',
    'public_html/submit-delivery-final-check.php',
    'public_html/includes/erp-operation-engine-helper.php',
    'public_html/submit-operation-case-create.php',
];

/** @var list<string> */
const P2OE_FORBIDDEN_FILES = [
    'staff-auth.php',
    'access-control.php',
    'staff-login.php',
    'config.php',
    'config.example.php',
    'private/erp-config.php',
    'private/erp-config.example.php',
];

function p2oe_root(): string
{
    return dirname(__DIR__);
}

function p2oe_line(string $label, string $status): void
{
    echo str_pad($label, 52, '.') . ' ' . $status . PHP_EOL;
}

function p2oe_php_binary(): string
{
    $candidates = [getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'];

    foreach ($candidates as $candidate) {
        if ($candidate === '') {
            continue;
        }

        if ($candidate === 'php') {
            return $candidate;
        }

        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return 'php';
}

function p2oe_require_helper(): void
{
    $paths = [
        p2oe_root() . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-operation-engine-helper.php',
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }

    throw new RuntimeException('erp-operation-engine-helper.php not found');
}

echo 'PHASE 2 OPERATION ENGINE TEST' . PHP_EOL;
echo str_repeat('=', 52) . PHP_EOL;

$overallOk = true;
$failures = [];

p2oe_require_helper();

$connection = false;

try {
    $connection = operation_engine_db();

    if ($connection === false) {
        $overallOk = false;
        $failures[] = 'database connection';
        p2oe_line('Database connection', 'FAILED');
    } else {
        p2oe_line('Database connection', 'PASSED');
    }
} catch (Throwable) {
    $overallOk = false;
    $failures[] = 'database connection exception';
    p2oe_line('Database connection', 'FAILED');
}

if ($connection !== false) {
    foreach (P2OE_TABLES as $tableName) {
        $exists = operation_engine_table_exists($connection, $tableName);
        p2oe_line('Table dbo.' . $tableName, $exists ? 'PASSED' : 'FAILED');

        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName;
            continue;
        }

        $count = operation_engine_scalar($connection, 'SELECT COUNT(*) FROM dbo.' . $tableName);

        if ($count === null) {
            $overallOk = false;
            $failures[] = 'select ' . $tableName;
            p2oe_line('SELECT dbo.' . $tableName, 'FAILED');
        } else {
            p2oe_line('SELECT dbo.' . $tableName, 'PASSED (' . $count . ' rows)');
        }
    }

    echo str_repeat('-', 52) . PHP_EOL;
    echo 'Phase 1 table report (informational):' . PHP_EOL;

    foreach (P2OE_PHASE1_TABLES as $tableName) {
        $exists = operation_engine_table_exists($connection, $tableName);
        p2oe_line('Phase1 dbo.' . $tableName, $exists ? 'EXISTS' : 'MISSING');
    }

    @odbc_close($connection);
}

foreach (P2OE_PHP_FILES as $relativePath) {
    $fullPath = p2oe_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!is_file($fullPath)) {
        $overallOk = false;
        $failures[] = 'missing ' . $relativePath;
        p2oe_line('File exists: ' . basename($relativePath), 'FAILED');
        continue;
    }

    p2oe_line('File exists: ' . basename($relativePath), 'PASSED');

    $output = [];
    $exitCode = 0;
    $phpBinary = p2oe_php_binary();
    exec($phpBinary . ' -l ' . escapeshellarg($fullPath) . ' 2>&1', $output, $exitCode);
    p2oe_line('PHP syntax: ' . basename($relativePath), $exitCode === 0 ? 'PASSED' : 'FAILED');

    if ($exitCode !== 0) {
        $overallOk = false;
        $failures[] = 'syntax ' . $relativePath;

        foreach ($output as $line) {
            echo '  ' . $line . PHP_EOL;
        }
    }
}

foreach (P2OE_FORBIDDEN_FILES as $relativePath) {
    $fullPath = p2oe_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!is_file($fullPath)) {
        p2oe_line('Forbidden check: ' . $relativePath, 'SKIP (not in repo root)');
        continue;
    }

    $gitCheck = [];
    exec(
        'git -C ' . escapeshellarg(p2oe_root()) . ' status --short -- ' . escapeshellarg($relativePath) . ' 2>&1',
        $gitCheck
    );

    $modified = $gitCheck !== [] && trim(implode('', $gitCheck)) !== '';

    if ($modified) {
        $overallOk = false;
        $failures[] = 'forbidden modified ' . $relativePath;
        p2oe_line('Forbidden check: ' . $relativePath, 'WARNING MODIFIED');
    } else {
        p2oe_line('Forbidden check: ' . $relativePath, 'PASSED (unchanged)');
    }
}

echo str_repeat('-', 52) . PHP_EOL;

if ($overallOk) {
    echo 'RESULT: PASSED' . PHP_EOL;
} else {
    echo 'RESULT: FAILED' . PHP_EOL;
    echo 'Failures: ' . implode(', ', $failures) . PHP_EOL;
}

echo 'PHASE 2 OPERATION ENGINE TEST COMPLETE' . PHP_EOL;

exit($overallOk ? 0 : 1);
