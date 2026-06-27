<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';
$migration = is_file($root . '/database/migrations/P10_release_hardening_navigation_rc.sql')
    ? (string)file_get_contents($root . '/database/migrations/P10_release_hardening_navigation_rc.sql')
    : '';

$p10Files = [
    'includes/m360-navigation-registry.php',
    'includes/m360-release-hardening-helper.php',
    'includes/m360-release-readiness-helper.php',
    'includes/m360-route-audit-helper.php',
    'includes/m360-demo-package-helper.php',
    'erp-product-home.php',
    'erp-demo-package-rc.php',
    'erp-release-readiness.php',
    'erp-route-map.php',
    'erp-link-audit.php',
];

function p10s_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p10s_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$all = '';
foreach ($p10Files as $rel) {
    $all .= p10s_read($public . '/' . $rel);
}

$results = [];
$results[] = p10s_pass('No credential leak in P10', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $all));
$results[] = p10s_pass('No connection string secret', !preg_match('/Server=.*Password=/i', $all));
$results[] = p10s_pass('staff-login exists', is_file($public . '/staff-login.php'));
$results[] = p10s_pass('owner-login exists', is_file($public . '/owner-login.php'));
$results[] = p10s_pass('access-control exists', is_file($public . '/access-control.php'));
$results[] = p10s_pass('P10 does not rewrite staff-login', !str_contains($all, 'file_put_contents') || !str_contains($all, 'staff-login.php'));
$results[] = p10s_pass('P10 does not rewrite access-control', !preg_match('/file_put_contents\s*\([^)]*access-control/i', $all));
$results[] = p10s_pass('P10 uses staff gate', str_contains($all, 'm360_release_hardening_require_staff') || str_contains($all, 'm360_nav_require_staff'));
$results[] = p10s_pass('Auth redirect to staff-login', str_contains(p10s_read($public . '/includes/m360-navigation-registry.php'), 'staff-login.php'));
$results[] = p10s_pass('No destructive SQL in P10 migration', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p10s_pass('No gate bypass in P10', !preg_match('/skip.*gate|bypass.*gate|gate.*override/i', $all));
$results[] = p10s_pass('No workflow mutation SQL', !preg_match('/\b(INSERT INTO|UPDATE)\s+dbo\.erp_(jobcards|final_invoices|payments)\b/i', $all));
$results[] = p10s_pass('No payment gateway', !preg_match('/zarinpal|stripe|paypal|saman|mellat|payment_gateway_api/i', $all));
$results[] = p10s_pass('No accounting voucher', !preg_match('/journal_entry_create|accounting_voucher_post|INSERT INTO dbo\.erp_ledger/i', $all));
$results[] = p10s_pass('No inventory write from P10', !preg_match('/INSERT INTO dbo\.erp_inventory|inventory_adjustment/i', $all));
$results[] = p10s_pass('No ZipArchive in P10 pages', !preg_match('/\bZipArchive\b/i', $all));
$results[] = p10s_pass('Read-only navigation comment', str_contains($all, 'read-only') || str_contains($all, 'Read-only'));

$pass = 0; $fail = 0;
echo "# P10 Security Scope Control Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
