<?php
/**
 * MOGHARE360 ERP — Phase 6 CRM System CLI Test
 */

declare(strict_types=1);

const P6CRM_TABLES = [
    'erp_crm_followup_schedules',
    'erp_crm_followup_records',
    'erp_customer_satisfaction_surveys',
    'erp_customer_score_cards',
    'erp_upsell_opportunities',
    'erp_crm_history',
];

const P6CRM_PHASE1 = ['erp_customer_intakes', 'erp_customer_contracts', 'erp_customer_vehicle_bindings'];
const P6CRM_PHASE2 = ['erp_operation_cases', 'erp_operation_service_steps', 'erp_operation_delivery_checks'];
const P6CRM_PHASE5 = ['erp_jobcard_cost_headers', 'erp_payment_records', 'erp_invoice_previews'];

const P6CRM_PHP = [
    'public_html/includes/erp-crm-helper.php',
    'public_html/erp-crm-followup-board.php',
    'public_html/erp-crm-followup-detail.php',
    'public_html/submit-crm-followup.php',
    'public_html/erp-customer-satisfaction.php',
    'public_html/submit-customer-satisfaction.php',
    'public_html/erp-customer-score-board.php',
    'public_html/erp-upsell-opportunities.php',
];

const P6CRM_FORBIDDEN = [
    'staff-auth.php', 'access-control.php', 'staff-login.php', 'config.php', 'config.example.php',
    'private/erp-config.php', 'private/erp-config.example.php',
];

function p6crm_root(): string { return dirname(__DIR__); }
function p6crm_line(string $l, string $s): void { echo str_pad($l, 52, '.') . ' ' . $s . PHP_EOL; }
function p6crm_php(): string {
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '') continue;
        if ($c === 'php' || is_file($c)) return $c;
    }
    return 'php';
}

require_once p6crm_root() . '/public_html/includes/erp-crm-helper.php';

echo 'PHASE 6 CRM SYSTEM TEST' . PHP_EOL . str_repeat('=', 52) . PHP_EOL;
$ok = true; $fail = [];
$c = crm_db();
if ($c === false) { $ok = false; $fail[] = 'db'; p6crm_line('Database connection', 'FAILED'); }
else { p6crm_line('Database connection', 'PASSED'); }

if ($c !== false) {
    foreach (P6CRM_TABLES as $t) {
        $ex = crm_table_exists($c, $t);
        p6crm_line('Table dbo.' . $t, $ex ? 'PASSED' : 'FAILED');
        if (!$ex) { $ok = false; $fail[] = $t; continue; }
        $cnt = crm_scalar($c, 'SELECT COUNT(*) FROM dbo.' . $t);
        p6crm_line('SELECT dbo.' . $t, $cnt !== null ? 'PASSED (' . $cnt . ' rows)' : 'FAILED');
        if ($cnt === null) { $ok = false; $fail[] = 'sel ' . $t; }
    }
    echo str_repeat('-', 52) . PHP_EOL;
    echo 'Phase 1 table report:' . PHP_EOL;
    foreach (P6CRM_PHASE1 as $t) p6crm_line('Phase1 dbo.' . $t, crm_table_exists($c, $t) ? 'EXISTS' : 'MISSING');
    echo 'Phase 2 table report:' . PHP_EOL;
    foreach (P6CRM_PHASE2 as $t) p6crm_line('Phase2 dbo.' . $t, crm_table_exists($c, $t) ? 'EXISTS' : 'MISSING');
    echo 'Phase 5 table report:' . PHP_EOL;
    foreach (P6CRM_PHASE5 as $t) p6crm_line('Phase5 dbo.' . $t, crm_table_exists($c, $t) ? 'EXISTS' : 'MISSING');
    @odbc_close($c);
}

$css = p6crm_root() . '/public_html/assets/moghare360-ui/moghare360-crm-system.css';
p6crm_line('CSS moghare360-crm-system.css', is_file($css) ? 'PASSED' : 'FAILED');
if (!is_file($css)) { $ok = false; $fail[] = 'css'; }

foreach (P6CRM_PHP as $rel) {
    $fp = p6crm_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { $ok = false; $fail[] = $rel; p6crm_line('File ' . basename($rel), 'FAILED'); continue; }
    p6crm_line('File ' . basename($rel), 'PASSED');
    $out = []; $ec = 0;
    exec(p6crm_php() . ' -l ' . escapeshellarg($fp) . ' 2>&1', $out, $ec);
    p6crm_line('PHP syntax ' . basename($rel), $ec === 0 ? 'PASSED' : 'FAILED');
    if ($ec !== 0) { $ok = false; $fail[] = 'syntax ' . $rel; }
}

foreach (P6CRM_FORBIDDEN as $rel) {
    $fp = p6crm_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { p6crm_line('Forbidden ' . $rel, 'SKIP'); continue; }
    $gc = [];
    exec('git -C ' . escapeshellarg(p6crm_root()) . ' status --short -- ' . escapeshellarg($rel) . ' 2>&1', $gc);
    $mod = $gc !== [] && trim(implode('', $gc)) !== '';
    p6crm_line('Forbidden ' . $rel, $mod ? 'WARNING MODIFIED' : 'PASSED (unchanged)');
    if ($mod) { $ok = false; $fail[] = 'forbidden ' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL;
echo $ok ? 'RESULT: PASSED' . PHP_EOL : 'RESULT: FAILED' . PHP_EOL;
if (!$ok) echo 'Failures: ' . implode(', ', $fail) . PHP_EOL;
echo 'PHASE 6 CRM SYSTEM TEST COMPLETE' . PHP_EOL;
exit($ok ? 0 : 1);
