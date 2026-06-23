<?php
/**
 * MOGHARE360 ERP — Phase 14 Production Deployment Plan CLI Test
 */

declare(strict_types=1);

const P14_BUILT = [
    'public_html/erp-deployment-readiness-dashboard.php',
    'public_html/erp-production-readiness-checklist.php',
    'public_html/includes/moghare360-deployment-helper.php',
    'public_html/assets/moghare360-ui/moghare360-deployment.css',
];

const P14_PHP_SYNTAX = [
    'public_html/erp-deployment-readiness-dashboard.php',
    'public_html/erp-production-readiness-checklist.php',
    'public_html/includes/moghare360-deployment-helper.php',
];

const P14_DEPLOY_DOCS = [
    'docs/deployment/MOGHARE360_ENVIRONMENT_CONFIG_PLAN.md',
    'docs/deployment/MOGHARE360_BACKUP_STRATEGY.md',
    'docs/deployment/MOGHARE360_DATABASE_MIGRATION_PLAN.md',
    'docs/deployment/MOGHARE360_ROLLBACK_PLAN.md',
    'docs/deployment/MOGHARE360_MONITORING_PLAN.md',
];

const P14_PAGES = [
    'public_html/erp-deployment-readiness-dashboard.php',
    'public_html/erp-production-readiness-checklist.php',
];

const P14_BOUNDARY_PHRASES = [
    'Not Production',
    'Not SaaS',
    'Not Customer Portal',
    'Not Official Accounting',
    'Not Production Deployed',
    'No credentials in repo',
    'No deployment executed',
];

const P14_MISSION_DOCS = [
    'docs/missions/phase_14_production_deployment_plan/PHASE_14_00_INDEX.md',
    'docs/missions/phase_14_production_deployment_plan/PHASE_14_01_SCOPE.md',
    'docs/missions/phase_14_production_deployment_plan/PHASE_14_02_ENVIRONMENT_CONFIG_PLAN.md',
    'docs/missions/phase_14_production_deployment_plan/PHASE_14_03_BACKUP_STRATEGY.md',
    'docs/missions/phase_14_production_deployment_plan/PHASE_14_04_DATABASE_MIGRATION_PLAN.md',
    'docs/missions/phase_14_production_deployment_plan/PHASE_14_05_ROLLBACK_PLAN.md',
    'docs/missions/phase_14_production_deployment_plan/PHASE_14_06_MONITORING_PLAN.md',
    'docs/missions/phase_14_production_deployment_plan/PHASE_14_90_TEST_RESULT.md',
    'docs/missions/phase_14_production_deployment_plan/PHASE_14_99_SIGNOFF.md',
];

const P14_FORBIDDEN = [
    'staff-auth.php', 'access-control.php', 'staff-login.php', 'config.php', 'config.example.php',
    'private/erp-config.php', 'private/erp-config.example.php',
];

function p14_root(): string { return dirname(__DIR__); }
function p14_line(string $l, string $s): void { echo str_pad($l, 52, '.') . ' ' . $s . PHP_EOL; }
function p14_php(): string {
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '') continue;
        if ($c === 'php' || is_file($c)) return $c;
    }
    return 'php';
}

$root = p14_root();
$ok = true;
$fail = [];

echo 'PHASE 14 DEPLOYMENT PLAN TEST' . PHP_EOL . str_repeat('=', 52) . PHP_EOL;

$sqlPath = $root . '/public_html/sql/sqlserver/phase_14_deployment_plan.sql';
p14_line('SQL phase_14 file', is_file($sqlPath) ? 'FOUND (optional)' : 'NOT REQUIRED');

foreach (P14_BUILT as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p14_line('Built ' . basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

foreach (P14_DEPLOY_DOCS as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p14_line('Deploy doc ' . basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'PHP syntax:' . PHP_EOL;
$phpBin = p14_php();
foreach (P14_PHP_SYNTAX as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) {
        p14_line('Syntax ' . basename($rel), 'FAILED (missing)');
        $ok = false;
        continue;
    }
    $out = [];
    $code = 0;
    exec('"' . $phpBin . '" -l "' . $fp . '" 2>&1', $out, $code);
    p14_line('Syntax ' . basename($rel), $code === 0 ? 'PASSED' : 'FAILED');
    if ($code !== 0) { $ok = false; $fail[] = 'syntax:' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'Mission docs:' . PHP_EOL;
foreach (P14_MISSION_DOCS as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p14_line('Doc ' . basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'Boundary phrases:' . PHP_EOL;
foreach (P14_PAGES as $rel) {
    $content = @file_get_contents($root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel));
    if ($content === false) {
        p14_line('Boundary ' . basename($rel), 'FAILED');
        $ok = false;
        continue;
    }
    $missing = [];
    foreach (P14_BOUNDARY_PHRASES as $phrase) {
        if (!str_contains($content, $phrase)) {
            $missing[] = $phrase;
        }
    }
    p14_line('Boundary ' . basename($rel), $missing === [] ? 'PASSED' : 'FAILED');
    if ($missing !== []) { $ok = false; $fail[] = 'boundary:' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'Helper load:' . PHP_EOL;
try {
    require_once $root . '/public_html/includes/moghare360-deployment-helper.php';
    $envs = mogh_deploy_environment_registry();
    $items = mogh_deploy_readiness_items();
    p14_line('Helper environments', count($envs) >= 3 ? 'PASSED' : 'FAILED');
    p14_line('Helper checklist', count($items) >= 15 ? 'PASSED' : 'FAILED');
    if (count($envs) < 3 || count($items) < 15) { $ok = false; }
} catch (Throwable $e) {
    p14_line('Helper load', 'FAILED: ' . $e->getMessage());
    $ok = false;
}

echo str_repeat('-', 52) . PHP_EOL . 'Forbidden files:' . PHP_EOL;
foreach (P14_FORBIDDEN as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) {
        p14_line('Forbidden ' . $rel, 'SKIP');
        continue;
    }
    $gitOut = [];
    exec('git -C "' . $root . '" status --porcelain -- "' . str_replace('\\', '/', $rel) . '" 2>&1', $gitOut);
    $modified = trim(implode('', $gitOut)) !== '';
    p14_line('Forbidden ' . $rel, $modified ? 'FAILED (modified)' : 'OK');
    if ($modified) { $ok = false; $fail[] = 'forbidden:' . $rel; }
}

echo str_repeat('=', 52) . PHP_EOL;
echo 'RESULT: ' . ($ok ? 'PASSED' : 'FAILED') . PHP_EOL;
if (!$ok && $fail !== []) {
    echo 'Failures: ' . implode(', ', $fail) . PHP_EOL;
}
echo 'PHASE 14 DEPLOYMENT PLAN TEST COMPLETE' . PHP_EOL;
exit($ok ? 0 : 1);
