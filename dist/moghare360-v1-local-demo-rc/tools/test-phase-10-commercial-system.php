<?php
/**
 * MOGHARE360 ERP — Phase 10 Commercial System CLI Test
 */

declare(strict_types=1);

const P10CS_TABLES = [
    'erp_commercial_demo_registry',
    'erp_commercial_package_plans',
    'erp_license_preview_models',
    'erp_commercial_readiness_checks',
    'erp_commercial_release_history',
];

const P10CS_DEMO_SEEDS = ['INTERNAL-ERP-DEMO', 'BUSINESS-READY-DEMO', 'COMMERCIAL-SHOWCASE', 'PRODUCT-PACKAGES', 'LICENSE-PREVIEW'];
const P10CS_PKG_SEEDS = ['STARTER-WORKSHOP', 'STANDARD-WORKSHOP', 'PROFESSIONAL-WORKSHOP', 'ENTERPRISE-READY'];
const P10CS_LIC_SEEDS = ['DEMO-ONLY', 'SINGLE-WORKSHOP', 'MULTI-BRANCH-READY', 'ENTERPRISE-READY'];
const P10CS_CHECK_SEEDS = ['INTERNAL_ERP_READY', 'SAAS_NOT_ACTIVE_SAFE', 'FINAL_REPORT_READY'];

const P10CS_PAGES = [
    'moghare360-commercial-demo.php',
    'moghare360-sales-showcase.php',
    'moghare360-product-packages.php',
    'moghare360-license-preview.php',
    'moghare360-commercial-checklist.php',
    'moghare360-final-release-report.php',
];

const P10CS_PRODUCT_DOCS = [
    'docs/product/MOGHARE360_COMMERCIAL_PRODUCT_BRIEF.md',
    'docs/product/MOGHARE360_SAAS_PACKAGING_PLAN.md',
    'docs/product/MOGHARE360_TENANT_READY_ARCHITECTURE.md',
    'docs/product/MOGHARE360_PRICING_MODEL_DRAFT.md',
    'docs/product/MOGHARE360_COMMERCIAL_RELEASE_CHECKLIST.md',
    'docs/product/MOGHARE360_FINAL_COMMERCIAL_RELEASE_REPORT.md',
];

const P10CS_MISSION_DOCS = [
    'docs/missions/phase_10_commercial_system/PHASE_10_00_INDEX.md',
    'docs/missions/phase_10_commercial_system/PHASE_10_90_TEST_RESULT.md',
    'docs/missions/phase_10_commercial_system/PHASE_10_99_SIGNOFF.md',
];

const P10CS_PHP = [
    'public_html/includes/erp-commercial-system-helper.php',
    'public_html/moghare360-commercial-demo.php',
    'public_html/moghare360-sales-showcase.php',
    'public_html/moghare360-product-packages.php',
    'public_html/moghare360-license-preview.php',
    'public_html/moghare360-commercial-checklist.php',
    'public_html/moghare360-final-release-report.php',
    'public_html/submit-commercial-release-history.php',
];

const P10CS_FORBIDDEN = [
    'staff-auth.php', 'access-control.php', 'staff-login.php', 'config.php', 'config.example.php',
    'private/erp-config.php', 'private/erp-config.example.php',
];

function p10cs_root(): string { return dirname(__DIR__); }
function p10cs_line(string $l, string $s): void { echo str_pad($l, 52, '.') . ' ' . $s . PHP_EOL; }
function p10cs_php(): string {
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '') continue;
        if ($c === 'php' || is_file($c)) return $c;
    }
    return 'php';
}

require_once p10cs_root() . '/public_html/includes/erp-commercial-system-helper.php';

echo 'PHASE 10 COMMERCIAL SYSTEM TEST' . PHP_EOL . str_repeat('=', 52) . PHP_EOL;
$ok = true; $fail = [];
$c = commercial_db();
if ($c === false) { $ok = false; $fail[] = 'db'; p10cs_line('Database connection', 'FAILED'); }
else { p10cs_line('Database connection', 'PASSED'); }

if ($c !== false) {
    foreach (P10CS_TABLES as $t) {
        $ex = commercial_table_exists($c, $t);
        p10cs_line('Table dbo.' . $t, $ex ? 'PASSED' : 'FAILED');
        if (!$ex) { $ok = false; $fail[] = $t; continue; }
        $cnt = commercial_scalar($c, 'SELECT COUNT(*) FROM dbo.' . $t);
        p10cs_line('SELECT dbo.' . $t, $cnt !== null ? 'PASSED (' . $cnt . ' rows)' : 'FAILED');
    }
    if (commercial_table_exists($c, 'erp_commercial_demo_registry')) {
        foreach (P10CS_DEMO_SEEDS as $code) {
            $cnt = commercial_scalar($c, 'SELECT COUNT(*) FROM dbo.erp_commercial_demo_registry WHERE demo_code=?', [$code]);
            p10cs_line('Seed demo ' . $code, ($cnt !== null && (int)$cnt > 0) ? 'PASSED' : 'FAILED');
            if ($cnt === null || (int)$cnt < 1) { $ok = false; $fail[] = 'demo ' . $code; }
        }
    }
    if (commercial_table_exists($c, 'erp_commercial_package_plans')) {
        foreach (P10CS_PKG_SEEDS as $code) {
            $cnt = commercial_scalar($c, 'SELECT COUNT(*) FROM dbo.erp_commercial_package_plans WHERE package_code=?', [$code]);
            p10cs_line('Seed package ' . $code, ($cnt !== null && (int)$cnt > 0) ? 'PASSED' : 'FAILED');
            if ($cnt === null || (int)$cnt < 1) { $ok = false; $fail[] = 'pkg ' . $code; }
        }
    }
    if (commercial_table_exists($c, 'erp_commercial_readiness_checks')) {
        foreach (P10CS_CHECK_SEEDS as $code) {
            $cnt = commercial_scalar($c, 'SELECT COUNT(*) FROM dbo.erp_commercial_readiness_checks WHERE check_code=?', [$code]);
            p10cs_line('Seed check ' . $code, ($cnt !== null && (int)$cnt > 0) ? 'PASSED' : 'FAILED');
            if ($cnt === null || (int)$cnt < 1) { $ok = false; $fail[] = 'check ' . $code; }
        }
    }
    @odbc_close($c);
}

foreach (P10CS_PAGES as $p) {
    $fp = p10cs_root() . '/public_html/' . $p;
    p10cs_line('Page ' . $p, is_file($fp) ? 'PASSED' : 'FAILED');
    if (!is_file($fp)) { $ok = false; $fail[] = $p; }
}

$css = p10cs_root() . '/public_html/assets/moghare360-ui/moghare360-commercial-system.css';
p10cs_line('CSS moghare360-commercial-system.css', is_file($css) ? 'PASSED' : 'FAILED');
if (!is_file($css)) { $ok = false; $fail[] = 'css'; }

echo str_repeat('-', 52) . PHP_EOL . 'Product docs:' . PHP_EOL;
foreach (P10CS_PRODUCT_DOCS as $rel) {
    $fp = p10cs_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    p10cs_line(basename($rel), is_file($fp) ? 'PASSED' : 'FAILED');
    if (!is_file($fp)) { $ok = false; $fail[] = $rel; }
}

echo 'Mission docs:' . PHP_EOL;
foreach (P10CS_MISSION_DOCS as $rel) {
    $fp = p10cs_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    p10cs_line(basename($rel), is_file($fp) ? 'PASSED' : 'FAILED');
    if (!is_file($fp)) { $ok = false; $fail[] = $rel; }
}

foreach (P10CS_PHP as $rel) {
    $fp = p10cs_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { $ok = false; $fail[] = $rel; p10cs_line('File ' . basename($rel), 'FAILED'); continue; }
    p10cs_line('File ' . basename($rel), 'PASSED');
    $out = []; $ec = 0;
    exec(p10cs_php() . ' -l ' . escapeshellarg($fp) . ' 2>&1', $out, $ec);
    p10cs_line('PHP syntax ' . basename($rel), $ec === 0 ? 'PASSED' : 'FAILED');
    if ($ec !== 0) { $ok = false; $fail[] = 'syntax ' . $rel; }
}

foreach (P10CS_FORBIDDEN as $rel) {
    $fp = p10cs_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { p10cs_line('Forbidden ' . $rel, 'SKIP'); continue; }
    $gc = [];
    exec('git -C ' . escapeshellarg(p10cs_root()) . ' status --short -- ' . escapeshellarg($rel) . ' 2>&1', $gc);
    $mod = $gc !== [] && trim(implode('', $gc)) !== '';
    p10cs_line('Forbidden ' . $rel, $mod ? 'WARNING MODIFIED' : 'PASSED (unchanged)');
    if ($mod) { $ok = false; $fail[] = 'forbidden ' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL;
echo $ok ? 'RESULT: PASSED' . PHP_EOL : 'RESULT: FAILED' . PHP_EOL;
if (!$ok) echo 'Failures: ' . implode(', ', $fail) . PHP_EOL;
echo 'PHASE 10 COMMERCIAL SYSTEM TEST COMPLETE' . PHP_EOL;
exit($ok ? 0 : 1);
