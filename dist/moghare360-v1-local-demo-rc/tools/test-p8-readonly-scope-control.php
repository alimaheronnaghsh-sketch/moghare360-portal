<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p8r_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p8r_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$files = [
    'erp-management-dashboard.php',
    'erp-owner-control-center.php',
    'erp-operational-kpi.php',
    'erp-jobcard-timeline.php',
    'erp-bottleneck-monitor.php',
    'erp-financial-control-summary.php',
    'includes/m360-management-kpi-helper.php',
    'includes/m360-owner-control-helper.php',
    'includes/m360-bottleneck-helper.php',
    'includes/m360-jobcard-timeline-helper.php',
    'includes/m360-financial-control-helper.php',
    'api/management/kpi-summary.php',
    'api/management/bottleneck-summary.php',
    'api/management/jobcard-timeline.php',
    'assets/js/m360-management-dashboard.js',
];

$all = '';
foreach ($files as $rel) {
    $all .= p8r_read($public . '/' . $rel);
}

$results = [];
$results[] = p8r_pass('No P8 action endpoints', !is_file($public . '/erp-management-action.php') && !is_file($public . '/erp-owner-control-action.php'));
$results[] = p8r_pass('No SQL INSERT', !preg_match('/\bINSERT\s+INTO\b/i', $all));
$results[] = p8r_pass('No SQL UPDATE', !preg_match('/\bUPDATE\s+dbo\./i', $all));
$results[] = p8r_pass('No SQL DELETE', !preg_match('/\bDELETE\s+FROM\b/i', $all));
$results[] = p8r_pass('No POST forms', !preg_match('/method\s*=\s*[\'"]post[\'"]/i', $all));
$results[] = p8r_pass('No POST action handlers', !preg_match('/\$_POST[\s\S]{0,120}[\'"](approve|override|release|close|payment)[\'"]/i', $all));
$results[] = p8r_pass('No mutation approve fn', !preg_match('/function\s+m360_\w*(approve|override|payment)\s*\(/i', $all));
$results[] = p8r_pass('API kpi GET only', str_contains(p8r_read($public . '/api/management/kpi-summary.php'), "!== 'GET'"));
$results[] = p8r_pass('API bottleneck GET only', str_contains(p8r_read($public . '/api/management/bottleneck-summary.php'), "!== 'GET'"));
$results[] = p8r_pass('API timeline GET only', str_contains(p8r_read($public . '/api/management/jobcard-timeline.php'), "!== 'GET'"));
$results[] = p8r_pass('No accounting voucher', !preg_match('/journal_entry_create|accounting_voucher_post|INSERT INTO dbo\.erp_ledger|chart_of_accounts/i', $all));
$results[] = p8r_pass('No payment gateway', !preg_match('/zarinpal|stripe|paypal|saman|mellat|payment_gateway_api/i', $all));
$results[] = p8r_pass('No bank integration', !preg_match('/bank_transfer_api|iban_verify|shaparak/i', $all));
$results[] = p8r_pass('No tax filing module', !preg_match('/tax_filing|moadian|samt/i', $all));
$results[] = p8r_pass('No inventory write', !preg_match('/INSERT INTO dbo\.erp_inventory|inventory_adjustment|stock_write/i', $all));
$results[] = p8r_pass('No purchase write', !preg_match('/INSERT INTO dbo\.erp_purchase|purchase_order_create/i', $all));
$results[] = p8r_pass('Read-only markers present', str_contains($all, 'read_only') || str_contains($all, 'read-only'));
$results[] = p8r_pass('No gate bypass', !preg_match('/skip.*gate|bypass.*gate|gate.*override/i', $all));

$pass = 0; $fail = 0;
echo "# P8 Readonly Scope Control Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
