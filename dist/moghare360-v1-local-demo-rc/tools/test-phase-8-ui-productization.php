<?php
/**
 * MOGHARE360 ERP — Phase 8 UI Productization CLI Test
 */

declare(strict_types=1);

const P8UI_PAGES = [
    'erp-business-command-center.php',
    'erp-module-navigation.php',
    'erp-blueprint-map.php',
    'erp-product-status.php',
    'erp-operational-command-center.php',
    'erp-role-demo-navigation.php',
];

const P8UI_PHASE_PAGES = [
    'Phase1 erp-customer-core-dashboard.php' => 'public_html/erp-customer-core-dashboard.php',
    'Phase2 erp-operation-control-center.php' => 'public_html/erp-operation-control-center.php',
    'Phase3 erp-rule-decision-board.php' => 'public_html/erp-rule-decision-board.php',
    'Phase4 erp-stock-board.php' => 'public_html/erp-stock-board.php',
    'Phase5 erp-finance-control-center.php' => 'public_html/erp-finance-control-center.php',
    'Phase6 erp-crm-followup-board.php' => 'public_html/erp-crm-followup-board.php',
    'Phase7 erp-hr-dashboard.php' => 'public_html/erp-hr-dashboard.php',
];

const P8UI_PHP = [
    'public_html/includes/erp-business-layer-helper.php',
    'public_html/erp-business-command-center.php',
    'public_html/erp-module-navigation.php',
    'public_html/erp-blueprint-map.php',
    'public_html/erp-product-status.php',
    'public_html/erp-operational-command-center.php',
    'public_html/erp-role-demo-navigation.php',
];

const P8UI_FORBIDDEN = [
    'staff-auth.php', 'access-control.php', 'staff-login.php', 'config.php', 'config.example.php',
    'private/erp-config.php', 'private/erp-config.example.php',
];

function p8ui_root(): string { return dirname(__DIR__); }
function p8ui_line(string $l, string $s): void { echo str_pad($l, 52, '.') . ' ' . $s . PHP_EOL; }
function p8ui_php(): string {
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '') continue;
        if ($c === 'php' || is_file($c)) return $c;
    }
    return 'php';
}

$sqlPath = p8ui_root() . '/public_html/sql/sqlserver/phase_8_ui_productization_layer.sql';
echo 'PHASE 8 UI PRODUCTIZATION TEST' . PHP_EOL . str_repeat('=', 52) . PHP_EOL;
$ok = true; $fail = [];

p8ui_line('SQL file phase_8', is_file($sqlPath) ? 'EXISTS' : 'NOT REQUIRED');
if (is_file($sqlPath)) {
    p8ui_line('SQL note', 'Run manually if registry table needed');
}

$dbOk = false;
if (is_file(p8ui_root() . '/public_html/includes/erp-business-layer-helper.php')) {
    require_once p8ui_root() . '/public_html/includes/erp-business-layer-helper.php';
    $c = bl_db();
    if ($c === false) {
        p8ui_line('Database connection', 'SKIP (UI phase)');
    } else {
        $dbOk = true;
        p8ui_line('Database connection', 'PASSED');
        if (is_file($sqlPath) && function_exists('bl_table_exists')) {
            $ex = bl_table_exists($c, 'erp_product_ui_registry');
            p8ui_line('Table dbo.erp_product_ui_registry', $ex ? 'PASSED' : 'FAILED');
            if (!$ex) { $ok = false; $fail[] = 'registry'; }
        }
        @odbc_close($c);
    }
} else {
    p8ui_line('Database connection', 'SKIP (helper missing)');
}

foreach (P8UI_PAGES as $page) {
    $fp = p8ui_root() . '/public_html/' . $page;
    $ex = is_file($fp);
    p8ui_line('Page ' . $page, $ex ? 'PASSED' : 'FAILED');
    if (!$ex) { $ok = false; $fail[] = $page; }
}

$css = p8ui_root() . '/public_html/assets/moghare360-ui/moghare360-business-layer.css';
p8ui_line('CSS moghare360-business-layer.css', is_file($css) ? 'PASSED' : 'FAILED');
if (!is_file($css)) { $ok = false; $fail[] = 'css'; }

echo str_repeat('-', 52) . PHP_EOL;
echo 'Phase 1-7 key pages:' . PHP_EOL;
foreach (P8UI_PHASE_PAGES as $label => $rel) {
    $fp = p8ui_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    p8ui_line($label, is_file($fp) ? 'PASSED' : 'FAILED');
    if (!is_file($fp)) { $ok = false; $fail[] = $label; }
}

foreach (P8UI_PHP as $rel) {
    $fp = p8ui_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { $ok = false; $fail[] = $rel; p8ui_line('File ' . basename($rel), 'FAILED'); continue; }
    p8ui_line('File ' . basename($rel), 'PASSED');
    $out = []; $ec = 0;
    exec(p8ui_php() . ' -l ' . escapeshellarg($fp) . ' 2>&1', $out, $ec);
    p8ui_line('PHP syntax ' . basename($rel), $ec === 0 ? 'PASSED' : 'FAILED');
    if ($ec !== 0) { $ok = false; $fail[] = 'syntax ' . $rel; }
}

foreach (P8UI_FORBIDDEN as $rel) {
    $fp = p8ui_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { p8ui_line('Forbidden ' . $rel, 'SKIP'); continue; }
    $gc = [];
    exec('git -C ' . escapeshellarg(p8ui_root()) . ' status --short -- ' . escapeshellarg($rel) . ' 2>&1', $gc);
    $mod = $gc !== [] && trim(implode('', $gc)) !== '';
    p8ui_line('Forbidden ' . $rel, $mod ? 'WARNING MODIFIED' : 'PASSED (unchanged)');
    if ($mod) { $ok = false; $fail[] = 'forbidden ' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL;
echo $ok ? 'RESULT: PASSED' . PHP_EOL : 'RESULT: FAILED' . PHP_EOL;
if (!$ok) echo 'Failures: ' . implode(', ', $fail) . PHP_EOL;
echo 'PHASE 8 UI PRODUCTIZATION TEST COMPLETE' . PHP_EOL;
exit($ok ? 0 : 1);
