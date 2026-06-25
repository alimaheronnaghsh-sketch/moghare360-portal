<?php
/**
 * MOGHARE360 ERP — Phase 9 Business Ready CLI Test
 */

declare(strict_types=1);

const P9BR_TABLES = [
    'erp_business_kpi_snapshots',
    'erp_soft_run_audit_checks',
    'erp_management_report_history',
];

const P9BR_SEED_CHECKS = [
    'CUSTOMER_CORE_READY', 'OPERATION_ENGINE_READY', 'RULE_ENGINE_READY',
    'INVENTORY_PURCHASE_READY', 'FINANCIAL_PREVIEW_READY', 'CRM_READY',
    'HR_READY', 'UI_PRODUCTIZED', 'BUSINESS_REPORTING_READY', 'COMMERCIAL_PENDING',
];

const P9BR_PHASE_TABLES = [
    'Phase1 erp_customer_intakes' => 'erp_customer_intakes',
    'Phase2 erp_operation_cases' => 'erp_operation_cases',
    'Phase3 erp_rule_decisions' => 'erp_rule_decisions',
    'Phase4 erp_stock_balances' => 'erp_stock_balances',
    'Phase5 erp_jobcard_cost_headers' => 'erp_jobcard_cost_headers',
    'Phase6 erp_crm_followup_schedules' => 'erp_crm_followup_schedules',
    'Phase7 erp_hr_employees' => 'erp_hr_employees',
];

const P9BR_PHASE8_PAGES = [
    'erp-business-command-center.php',
    'erp-module-navigation.php',
    'erp-product-status.php',
];

const P9BR_PHP = [
    'public_html/includes/erp-business-ready-helper.php',
    'public_html/erp-management-dashboard.php',
    'public_html/erp-kpi-report.php',
    'public_html/erp-operation-performance-report.php',
    'public_html/erp-financial-preview-report.php',
    'public_html/erp-crm-report.php',
    'public_html/erp-inventory-pressure-report.php',
    'public_html/erp-staff-performance-preview.php',
    'public_html/erp-soft-run-audit.php',
    'public_html/submit-business-kpi-snapshot.php',
    'public_html/submit-soft-run-audit-check.php',
];

const P9BR_FORBIDDEN = [
    'staff-auth.php', 'access-control.php', 'staff-login.php', 'config.php', 'config.example.php',
    'private/erp-config.php', 'private/erp-config.example.php',
];

function p9br_root(): string { return dirname(__DIR__); }
function p9br_line(string $l, string $s): void { echo str_pad($l, 52, '.') . ' ' . $s . PHP_EOL; }
function p9br_php(): string {
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '') continue;
        if ($c === 'php' || is_file($c)) return $c;
    }
    return 'php';
}

require_once p9br_root() . '/public_html/includes/erp-business-ready-helper.php';

echo 'PHASE 9 BUSINESS READY TEST' . PHP_EOL . str_repeat('=', 52) . PHP_EOL;
$ok = true; $fail = [];
$c = business_ready_db();
if ($c === false) { $ok = false; $fail[] = 'db'; p9br_line('Database connection', 'FAILED'); }
else { p9br_line('Database connection', 'PASSED'); }

if ($c !== false) {
    foreach (P9BR_TABLES as $t) {
        $ex = business_ready_table_exists($c, $t);
        p9br_line('Table dbo.' . $t, $ex ? 'PASSED' : 'FAILED');
        if (!$ex) { $ok = false; $fail[] = $t; continue; }
        $cnt = business_ready_scalar($c, 'SELECT COUNT(*) FROM dbo.' . $t);
        p9br_line('SELECT dbo.' . $t, $cnt !== null ? 'PASSED (' . $cnt . ' rows)' : 'FAILED');
        if ($cnt === null) { $ok = false; $fail[] = 'sel ' . $t; }
    }
    if (business_ready_table_exists($c, 'erp_soft_run_audit_checks')) {
        foreach (P9BR_SEED_CHECKS as $code) {
            $cnt = business_ready_scalar($c, 'SELECT COUNT(*) FROM dbo.erp_soft_run_audit_checks WHERE check_code=?', [$code]);
            $pass = $cnt !== null && (int)$cnt > 0;
            p9br_line('Seed audit ' . $code, $pass ? 'PASSED' : 'FAILED');
            if (!$pass) { $ok = false; $fail[] = 'seed ' . $code; }
        }
    }
    echo str_repeat('-', 52) . PHP_EOL;
    echo 'Phase 1-7 key tables:' . PHP_EOL;
    foreach (P9BR_PHASE_TABLES as $label => $t) {
        p9br_line($label, business_ready_table_exists($c, $t) ? 'EXISTS' : 'MISSING');
    }
    echo 'Phase 8 pages:' . PHP_EOL;
    foreach (P9BR_PHASE8_PAGES as $p) {
        p9br_line($p, is_file(p9br_root() . '/public_html/' . $p) ? 'PASSED' : 'FAILED');
    }
    @odbc_close($c);
}

$css = p9br_root() . '/public_html/assets/moghare360-ui/moghare360-business-ready.css';
p9br_line('CSS moghare360-business-ready.css', is_file($css) ? 'PASSED' : 'FAILED');
if (!is_file($css)) { $ok = false; $fail[] = 'css'; }

foreach (P9BR_PHP as $rel) {
    $fp = p9br_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { $ok = false; $fail[] = $rel; p9br_line('File ' . basename($rel), 'FAILED'); continue; }
    p9br_line('File ' . basename($rel), 'PASSED');
    $out = []; $ec = 0;
    exec(p9br_php() . ' -l ' . escapeshellarg($fp) . ' 2>&1', $out, $ec);
    p9br_line('PHP syntax ' . basename($rel), $ec === 0 ? 'PASSED' : 'FAILED');
    if ($ec !== 0) { $ok = false; $fail[] = 'syntax ' . $rel; }
}

foreach (P9BR_FORBIDDEN as $rel) {
    $fp = p9br_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { p9br_line('Forbidden ' . $rel, 'SKIP'); continue; }
    $gc = [];
    exec('git -C ' . escapeshellarg(p9br_root()) . ' status --short -- ' . escapeshellarg($rel) . ' 2>&1', $gc);
    $mod = $gc !== [] && trim(implode('', $gc)) !== '';
    p9br_line('Forbidden ' . $rel, $mod ? 'WARNING MODIFIED' : 'PASSED (unchanged)');
    if ($mod) { $ok = false; $fail[] = 'forbidden ' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL;
echo $ok ? 'RESULT: PASSED' . PHP_EOL : 'RESULT: FAILED' . PHP_EOL;
if (!$ok) echo 'Failures: ' . implode(', ', $fail) . PHP_EOL;
echo 'PHASE 9 BUSINESS READY TEST COMPLETE' . PHP_EOL;
exit($ok ? 0 : 1);
