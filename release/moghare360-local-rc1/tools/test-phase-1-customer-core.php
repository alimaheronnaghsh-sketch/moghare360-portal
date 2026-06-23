<?php
/**
 * MOGHARE360 ERP — Phase 1 Customer Core System CLI Test
 */

declare(strict_types=1);

/** @var list<string> */
const P1CC_TABLES = [
    'erp_customer_intakes',
    'erp_customer_contracts',
    'erp_customer_contract_acceptances',
    'erp_customer_vehicle_bindings',
    'erp_vehicle_photo_records',
    'erp_customer_core_history',
];

/** @var list<string> */
const P1CC_PHP_FILES = [
    'public_html/erp-customer-core-dashboard.php',
    'public_html/erp-customer-entry.php',
    'public_html/submit-customer-entry.php',
    'public_html/erp-customer-contract-create.php',
    'public_html/submit-customer-contract.php',
    'public_html/erp-customer-profile.php',
    'public_html/erp-vehicle-binding.php',
    'public_html/submit-vehicle-binding.php',
    'public_html/includes/erp-customer-core-helper.php',
];

/** @var list<string> */
const P1CC_FORBIDDEN_FILES = [
    'staff-auth.php',
    'access-control.php',
    'staff-login.php',
    'config.php',
    'config.example.php',
    'private/erp-config.php',
    'private/erp-config.example.php',
];

function p1cc_root(): string
{
    return dirname(__DIR__);
}

function p1cc_line(string $label, string $status): void
{
    echo str_pad($label, 52, '.') . ' ' . $status . PHP_EOL;
}

function p1cc_php_binary(): string
{
    $candidates = [
        getenv('PHP_BINARY') ?: '',
        'C:\\xampp\\php\\php.exe',
        'php',
    ];

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

function p1cc_require_helper(): void
{
    $paths = [
        p1cc_root() . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php',
        p1cc_root() . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php',
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }

    throw new RuntimeException('erp-customer-core-helper.php not found');
}

echo 'PHASE 1 CUSTOMER CORE SYSTEM TEST' . PHP_EOL;
echo str_repeat('=', 52) . PHP_EOL;

$overallOk = true;
$failures = [];

p1cc_require_helper();

$connection = false;

try {
    $connection = customer_core_db();

    if ($connection === false) {
        $overallOk = false;
        $failures[] = 'database connection';
        p1cc_line('Database connection', 'FAILED');
    } else {
        p1cc_line('Database connection', 'PASSED');
    }
} catch (Throwable $exception) {
    $overallOk = false;
    $failures[] = 'database connection exception';
    p1cc_line('Database connection', 'FAILED');
}

if ($connection !== false) {
    foreach (P1CC_TABLES as $tableName) {
        $exists = customer_core_table_exists($connection, $tableName);
        p1cc_line('Table dbo.' . $tableName, $exists ? 'PASSED' : 'FAILED');

        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName;
            continue;
        }

        $count = customer_core_scalar($connection, 'SELECT COUNT(*) FROM dbo.' . $tableName);

        if ($count === null) {
            $overallOk = false;
            $failures[] = 'select ' . $tableName;
            p1cc_line('SELECT dbo.' . $tableName, 'FAILED');
        } else {
            p1cc_line('SELECT dbo.' . $tableName, 'PASSED (' . $count . ' rows)');
        }
    }

    @odbc_close($connection);
}

foreach (P1CC_PHP_FILES as $relativePath) {
    $fullPath = p1cc_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!is_file($fullPath)) {
        $overallOk = false;
        $failures[] = 'missing ' . $relativePath;
        p1cc_line('File exists: ' . basename($relativePath), 'FAILED');
        continue;
    }

    p1cc_line('File exists: ' . basename($relativePath), 'PASSED');

    $output = [];
    $exitCode = 0;
    $phpBinary = p1cc_php_binary();
    exec($phpBinary . ' -l ' . escapeshellarg($fullPath) . ' 2>&1', $output, $exitCode);
    $syntaxOk = $exitCode === 0;
    p1cc_line('PHP syntax: ' . basename($relativePath), $syntaxOk ? 'PASSED' : 'FAILED');

    if (!$syntaxOk) {
        $overallOk = false;
        $failures[] = 'syntax ' . $relativePath;

        foreach ($output as $line) {
            echo '  ' . $line . PHP_EOL;
        }
    }
}

foreach (P1CC_FORBIDDEN_FILES as $relativePath) {
    $fullPath = p1cc_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!is_file($fullPath)) {
        p1cc_line('Forbidden check: ' . $relativePath, 'SKIP (not in repo root)');
        continue;
    }

    $gitCheck = [];
    $gitExit = 0;
    exec(
        'git -C ' . escapeshellarg(p1cc_root()) . ' status --short -- ' . escapeshellarg($relativePath) . ' 2>&1',
        $gitCheck,
        $gitExit
    );

    $modified = $gitCheck !== [] && trim(implode('', $gitCheck)) !== '';

    if ($modified) {
        $overallOk = false;
        $failures[] = 'forbidden modified ' . $relativePath;
        p1cc_line('Forbidden check: ' . $relativePath, 'WARNING MODIFIED');
        echo '  ' . trim(implode(' ', $gitCheck)) . PHP_EOL;
    } else {
        p1cc_line('Forbidden check: ' . $relativePath, 'PASSED (unchanged)');
    }
}

echo str_repeat('-', 52) . PHP_EOL;

if ($overallOk) {
    echo 'RESULT: PASSED' . PHP_EOL;
} else {
    echo 'RESULT: FAILED' . PHP_EOL;
    echo 'Failures: ' . implode(', ', $failures) . PHP_EOL;
}

echo 'PHASE 1 CUSTOMER CORE TEST COMPLETE' . PHP_EOL;

exit($overallOk ? 0 : 1);
