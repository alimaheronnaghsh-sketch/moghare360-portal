<?php
/**
 * MOGHARE360 ERP — Phase 12.5 Localization & Branding CLI Test
 */

declare(strict_types=1);

const P125_BUILT = [
    'public_html/includes/moghare360-localization-helper.php',
    'public_html/erp-localization-audit.php',
    'public_html/erp-brand-system.php',
    'public_html/erp-asset-registry.php',
    'public_html/moghare360-demo-package.php',
    'public_html/assets/moghare360-ui/moghare360-brand-localization.css',
    'docs/product/MOGHARE360_COPYRIGHT_AND_ASSET_POLICY.md',
    'docs/product/MOGHARE360_PERSIAN_LANGUAGE_GUIDE.md',
];

const P125_PHP_SYNTAX = [
    'public_html/includes/moghare360-localization-helper.php',
    'public_html/erp-localization-audit.php',
    'public_html/erp-brand-system.php',
    'public_html/erp-asset-registry.php',
    'public_html/moghare360-demo-package.php',
];

const P125_MISSION_DOCS = [
    'docs/missions/phase_12_5_localization_branding/PHASE_12_5_00_INDEX.md',
    'docs/missions/phase_12_5_localization_branding/PHASE_12_5_01_SCOPE.md',
    'docs/missions/phase_12_5_localization_branding/PHASE_12_5_02_LOCALIZATION_AUDIT.md',
    'docs/missions/phase_12_5_localization_branding/PHASE_12_5_03_BRAND_SYSTEM.md',
    'docs/missions/phase_12_5_localization_branding/PHASE_12_5_04_ASSET_REGISTRY.md',
    'docs/missions/phase_12_5_localization_branding/PHASE_12_5_05_COPYRIGHT_POLICY.md',
    'docs/missions/phase_12_5_localization_branding/PHASE_12_5_06_DEMO_PACKAGE_PLAN.md',
    'docs/missions/phase_12_5_localization_branding/PHASE_12_5_90_TEST_RESULT.md',
    'docs/missions/phase_12_5_localization_branding/PHASE_12_5_99_SIGNOFF.md',
];

const P125_FORBIDDEN = [
    'staff-auth.php', 'access-control.php', 'staff-login.php', 'config.php', 'config.example.php',
    'private/erp-config.php', 'private/erp-config.example.php',
];

function p125_root(): string { return dirname(__DIR__); }
function p125_line(string $l, string $s): void { echo str_pad($l, 52, '.') . ' ' . $s . PHP_EOL; }
function p125_php(): string {
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '') continue;
        if ($c === 'php' || is_file($c)) return $c;
    }
    return 'php';
}

$root = p125_root();
$ok = true;
$fail = [];

echo 'PHASE 12.5 LOCALIZATION BRANDING TEST' . PHP_EOL . str_repeat('=', 52) . PHP_EOL;

$sqlPath = $root . '/public_html/sql/sqlserver/phase_12_5_localization_branding.sql';
p125_line('SQL phase_12_5 file', is_file($sqlPath) ? 'FOUND (optional)' : 'NOT REQUIRED');

$brandDir = $root . '/public_html/assets/moghare360-brand';
p125_line('Brand folder', is_dir($brandDir) ? 'PASSED' : 'FAILED');
if (!is_dir($brandDir)) { $ok = false; $fail[] = 'brand folder'; }

$logoPath = $brandDir . '/moghareh-motors-logo.jpg';
if (is_file($logoPath)) {
    p125_line('Brand logo', 'OK');
} else {
    p125_line('Brand logo', 'SKIP / FALLBACK EXPECTED');
}

foreach (P125_BUILT as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p125_line('Built ' . basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'PHP syntax:' . PHP_EOL;
$phpBin = p125_php();
foreach (P125_PHP_SYNTAX as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) {
        p125_line('Syntax ' . basename($rel), 'FAILED (missing)');
        $ok = false;
        continue;
    }
    $out = [];
    $code = 0;
    exec('"' . $phpBin . '" -l "' . $fp . '" 2>&1', $out, $code);
    $pass = $code === 0;
    p125_line('Syntax ' . basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = 'syntax:' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'Mission docs:' . PHP_EOL;
foreach (P125_MISSION_DOCS as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p125_line('Doc ' . basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'Helper load:' . PHP_EOL;
try {
    require_once $root . '/public_html/includes/moghare360-localization-helper.php';
    $dict = mogh_loc_dictionary();
    $registry = mogh_loc_page_registry();
    $assets = mogh_loc_asset_registry();
    $logoOk = function_exists('mogh_loc_brand_logo_exists');
    p125_line('Helper dictionary', count($dict) >= 20 ? 'PASSED' : 'FAILED');
    p125_line('Helper page registry', count($registry['audit_pages'] ?? []) >= 8 ? 'PASSED' : 'FAILED');
    p125_line('Helper asset registry', count($assets) >= 5 ? 'PASSED' : 'FAILED');
    p125_line('Logo fallback safe', $logoOk ? 'PASSED' : 'FAILED');
    if (count($dict) < 20 || count($registry['audit_pages'] ?? []) < 8 || count($assets) < 5 || !$logoOk) {
        $ok = false;
    }
} catch (Throwable $e) {
    p125_line('Helper load', 'FAILED: ' . $e->getMessage());
    $ok = false;
}

echo str_repeat('-', 52) . PHP_EOL . 'Forbidden files:' . PHP_EOL;
foreach (P125_FORBIDDEN as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) {
        p125_line('Forbidden ' . $rel, 'SKIP');
        continue;
    }
    $gitOut = [];
    exec('git -C "' . $root . '" status --porcelain -- "' . str_replace('\\', '/', $rel) . '" 2>&1', $gitOut);
    $modified = trim(implode('', $gitOut)) !== '';
    p125_line('Forbidden ' . $rel, $modified ? 'FAILED (modified)' : 'OK');
    if ($modified) { $ok = false; $fail[] = 'forbidden:' . $rel; }
}

echo str_repeat('=', 52) . PHP_EOL;
echo 'RESULT: ' . ($ok ? 'PASSED' : 'FAILED') . PHP_EOL;
if (!$ok && $fail !== []) {
    echo 'Failures: ' . implode(', ', $fail) . PHP_EOL;
}
echo 'PHASE 12.5 LOCALIZATION BRANDING TEST COMPLETE' . PHP_EOL;
exit($ok ? 0 : 1);
