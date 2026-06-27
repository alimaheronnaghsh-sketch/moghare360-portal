<?php
/**
 * MOGHARE360 ERP — Phase 3 Rule Engine CLI Test
 */

declare(strict_types=1);

/** @var list<string> */
const P3RE_TABLES = [
    'erp_rule_definitions',
    'erp_rule_decisions',
    'erp_service_approval_requests',
    'erp_inventory_rule_requests',
    'erp_rule_audit_history',
];

/** @var list<string> */
const P3RE_PHASE1_TABLES = [
    'erp_customer_intakes',
    'erp_customer_contracts',
    'erp_customer_vehicle_bindings',
];

/** @var list<string> */
const P3RE_PHASE2_TABLES = [
    'erp_operation_cases',
    'erp_operation_service_steps',
];

/** @var list<string> */
const P3RE_RULE_SEEDS = [
    'CONTRACT_OPEN_AUTHORIZATION_LIMIT',
    'CONTRACT_LIMITED_AUTHORIZATION_THRESHOLD',
    'SERVICE_OUT_OF_CONTRACT_APPROVAL',
    'INVENTORY_PART_AVAILABLE_USE_STOCK',
    'INVENTORY_PART_NOT_AVAILABLE_PURCHASE',
    'OPERATION_BLOCK_WITHOUT_RULE_CHECK',
];

/** @var list<string> */
const P3RE_PHP_FILES = [
    'public_html/includes/erp-rule-engine.php',
    'public_html/erp-rule-decision-board.php',
    'public_html/erp-service-approval-request.php',
    'public_html/submit-service-approval-request.php',
    'public_html/erp-rule-test-console.php',
];

/** @var list<string> */
const P3RE_FORBIDDEN_FILES = [
    'staff-auth.php',
    'access-control.php',
    'staff-login.php',
    'config.php',
    'config.example.php',
    'private/erp-config.php',
    'private/erp-config.example.php',
];

function p3re_root(): string
{
    return dirname(__DIR__);
}

function p3re_line(string $label, string $status): void
{
    echo str_pad($label, 52, '.') . ' ' . $status . PHP_EOL;
}

function p3re_php_binary(): string
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

function p3re_require_helper(): void
{
    $path = p3re_root() . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-rule-engine.php';

    if (!is_file($path)) {
        throw new RuntimeException('erp-rule-engine.php not found');
    }

    require_once $path;
}

echo 'PHASE 3 RULE ENGINE TEST' . PHP_EOL;
echo str_repeat('=', 52) . PHP_EOL;

$overallOk = true;
$failures = [];

p3re_require_helper();

$connection = false;

try {
    $connection = rule_engine_db();

    if ($connection === false) {
        $overallOk = false;
        $failures[] = 'database connection';
        p3re_line('Database connection', 'FAILED');
    } else {
        p3re_line('Database connection', 'PASSED');
    }
} catch (Throwable) {
    $overallOk = false;
    $failures[] = 'database connection exception';
    p3re_line('Database connection', 'FAILED');
}

if ($connection !== false) {
    foreach (P3RE_TABLES as $tableName) {
        $exists = rule_engine_table_exists($connection, $tableName);
        p3re_line('Table dbo.' . $tableName, $exists ? 'PASSED' : 'FAILED');

        if (!$exists) {
            $overallOk = false;
            $failures[] = 'table ' . $tableName;
            continue;
        }

        $count = rule_engine_scalar($connection, 'SELECT COUNT(*) FROM dbo.' . $tableName);

        if ($count === null) {
            $overallOk = false;
            $failures[] = 'select ' . $tableName;
            p3re_line('SELECT dbo.' . $tableName, 'FAILED');
        } else {
            p3re_line('SELECT dbo.' . $tableName, 'PASSED (' . $count . ' rows)');
        }
    }

    echo str_repeat('-', 52) . PHP_EOL;
    echo 'Phase 1 table report:' . PHP_EOL;

    foreach (P3RE_PHASE1_TABLES as $tableName) {
        p3re_line('Phase1 dbo.' . $tableName, rule_engine_table_exists($connection, $tableName) ? 'EXISTS' : 'MISSING');
    }

    echo 'Phase 2 table report:' . PHP_EOL;

    foreach (P3RE_PHASE2_TABLES as $tableName) {
        p3re_line('Phase2 dbo.' . $tableName, rule_engine_table_exists($connection, $tableName) ? 'EXISTS' : 'MISSING');
    }

    echo 'Rule seed report:' . PHP_EOL;

    foreach (P3RE_RULE_SEEDS as $ruleCode) {
        $count = rule_engine_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_rule_definitions WHERE rule_code = ?',
            [$ruleCode]
        );
        $ok = $count !== null && (int)$count > 0;
        p3re_line('Seed ' . $ruleCode, $ok ? 'PASSED' : 'FAILED');

        if (!$ok) {
            $overallOk = false;
            $failures[] = 'seed ' . $ruleCode;
        }
    }

    @odbc_close($connection);
}

$cssPath = p3re_root() . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'moghare360-ui' . DIRECTORY_SEPARATOR . 'moghare360-rule-engine.css';
p3re_line('CSS file moghare360-rule-engine.css', is_file($cssPath) ? 'PASSED' : 'FAILED');

if (!is_file($cssPath)) {
    $overallOk = false;
    $failures[] = 'css missing';
}

foreach (P3RE_PHP_FILES as $relativePath) {
    $fullPath = p3re_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!is_file($fullPath)) {
        $overallOk = false;
        $failures[] = 'missing ' . $relativePath;
        p3re_line('File exists: ' . basename($relativePath), 'FAILED');
        continue;
    }

    p3re_line('File exists: ' . basename($relativePath), 'PASSED');

    $output = [];
    $exitCode = 0;
    exec(p3re_php_binary() . ' -l ' . escapeshellarg($fullPath) . ' 2>&1', $output, $exitCode);
    p3re_line('PHP syntax: ' . basename($relativePath), $exitCode === 0 ? 'PASSED' : 'FAILED');

    if ($exitCode !== 0) {
        $overallOk = false;
        $failures[] = 'syntax ' . $relativePath;
    }
}

foreach (P3RE_FORBIDDEN_FILES as $relativePath) {
    $fullPath = p3re_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!is_file($fullPath)) {
        p3re_line('Forbidden check: ' . $relativePath, 'SKIP');
        continue;
    }

    $gitCheck = [];
    exec('git -C ' . escapeshellarg(p3re_root()) . ' status --short -- ' . escapeshellarg($relativePath) . ' 2>&1', $gitCheck);
    $modified = $gitCheck !== [] && trim(implode('', $gitCheck)) !== '';
    p3re_line('Forbidden check: ' . $relativePath, $modified ? 'WARNING MODIFIED' : 'PASSED (unchanged)');

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

echo 'PHASE 3 RULE ENGINE TEST COMPLETE' . PHP_EOL;

exit($overallOk ? 0 : 1);
