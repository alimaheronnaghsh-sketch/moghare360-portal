<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';
$migration = is_file($root . '/database/migrations/P9_end_to_end_soft_run.sql')
    ? (string)file_get_contents($root . '/database/migrations/P9_end_to_end_soft_run.sql')
    : '';

$p9Files = [
    'includes/m360-soft-run-helper.php',
    'includes/m360-demo-scenario-helper.php',
    'includes/m360-demo-readiness-helper.php',
    'includes/m360-e2e-validation-helper.php',
    'erp-soft-run-control-center.php',
    'erp-end-to-end-demo-scenario.php',
    'erp-soft-run-checklist.php',
    'erp-demo-flow-map.php',
    'erp-demo-readiness-report.php',
    'api/soft-run/readiness-summary.php',
    'api/soft-run/demo-scenario-status.php',
];

function p9x_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p9x_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$all = '';
foreach ($p9Files as $rel) {
    $all .= p9x_read($public . '/' . $rel);
}
$softRun = p9x_read($public . '/includes/m360-soft-run-helper.php');

$results = [];
$results[] = p9x_pass('No credential leak in P9', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $all));
$results[] = p9x_pass('No connection string secret', !preg_match('/Server=.*Password=/i', $all));
$results[] = p9x_pass('staff-login exists', is_file($public . '/staff-login.php'));
$results[] = p9x_pass('owner-login exists', is_file($public . '/owner-login.php'));
$results[] = p9x_pass('access-control exists', is_file($public . '/access-control.php'));
$results[] = p9x_pass('P9 does not rewrite staff-login', !str_contains($all, 'file_put_contents') || !str_contains($all, 'staff-login.php'));
$results[] = p9x_pass('P9 uses soft run staff gate', str_contains($all, 'm360_soft_run_require_staff'));
$results[] = p9x_pass('Auth redirect to staff-login', str_contains($softRun, 'staff-login.php'));
$results[] = p9x_pass('No destructive SQL in migration', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p9x_pass('No gate bypass in P9', !preg_match('/skip.*gate|bypass.*gate|gate.*override/i', $all));
$results[] = p9x_pass('No assert false bypass', !preg_match('/assert.*false|gates.*disabled/i', $all));
$results[] = p9x_pass('API readiness GET only', str_contains(p9x_read($public . '/api/soft-run/readiness-summary.php'), "!== 'GET'"));
$results[] = p9x_pass('API demo scenario GET only', str_contains(p9x_read($public . '/api/soft-run/demo-scenario-status.php'), "!== 'GET'"));
$results[] = p9x_pass('No payment gateway', !preg_match('/zarinpal|stripe|paypal|saman|mellat|payment_gateway_api/i', $all));
$results[] = p9x_pass('No accounting voucher', !preg_match('/journal_entry_create|accounting_voucher_post|INSERT INTO dbo\.erp_ledger/i', $all));
$results[] = p9x_pass('No inventory write from P9', !preg_match('/INSERT INTO dbo\.erp_inventory|inventory_adjustment/i', $all));
$results[] = p9x_pass('Checklist auth unchanged item', str_contains(p9x_read($public . '/includes/m360-demo-readiness-helper.php'), 'auth_unchanged'));
$results[] = p9x_pass('CSRF on checklist POST', str_contains(p9x_read($public . '/erp-soft-run-checklist.php'), 'M360_SOFT_RUN_CSRF'));
$results[] = p9x_pass('No raw SQL echo in API', !preg_match('/echo\s+\$sql|var_dump\s*\(\s*\$sql/i', $all));

$pass = 0; $fail = 0;
echo "# P9 Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
