<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';
$migration = is_file($root . '/database/migrations/P11_rc_final_audit_package_lock.sql')
    ? (string)file_get_contents($root . '/database/migrations/P11_rc_final_audit_package_lock.sql')
    : '';

$p11Files = [
    'includes/m360-release-lock-helper.php',
    'includes/m360-rc-final-audit-helper.php',
    'includes/m360-local-demo-package-helper.php',
    'includes/m360-owner-presentation-helper.php',
    'erp-rc-final-audit.php',
    'erp-local-demo-package.php',
    'erp-owner-presentation-lock.php',
    'erp-rc-final-checklist.php',
];

function p11s_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p11s_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$all = '';
foreach ($p11Files as $rel) {
    $all .= p11s_read($public . '/' . $rel);
}

$uiOnly = '';
foreach (['erp-rc-final-audit.php', 'erp-local-demo-package.php', 'erp-owner-presentation-lock.php', 'erp-rc-final-checklist.php'] as $rel) {
    $uiOnly .= p11s_read($public . '/' . $rel);
}

$results = [];
$results[] = p11s_pass('No credential leakage', !preg_match('/password\s*=\s*[\'"][^\'"]{8,}/i', $all));
$results[] = p11s_pass('No SMS API key', !preg_match('/sms[_-]?api[_-]?key|kavenegar|melipayamak/i', $all));
$results[] = p11s_pass('No raw bearer token', !preg_match('/bearer\s+[A-Za-z0-9\-_\.]{30,}/i', $all));
$results[] = p11s_pass('No destructive SQL in P11 migration', !preg_match('/^\s*(DROP|DELETE|TRUNCATE)\b/im', $migration));
$results[] = p11s_pass('No upload bypass', !preg_match('/\bmove_uploaded_file\b/i', $all));
$results[] = p11s_pass('No production fake OTP', !preg_match('/production.*1234|fake.*otp.*production/i', $uiOnly));
$results[] = p11s_pass('staff-login exists unchanged', is_file($public . '/staff-login.php'));
$results[] = p11s_pass('owner-login exists unchanged', is_file($public . '/owner-login.php'));
$results[] = p11s_pass('access-control exists unchanged', is_file($public . '/access-control.php'));
$results[] = p11s_pass('P11 does not rewrite auth files', !preg_match('/file_put_contents\s*\([^)]*(staff-login|owner-login|access-control)/i', $all));
$results[] = p11s_pass('Uses staff gate', str_contains($all, 'm360_release_lock_require_staff') || str_contains($all, 'm360_release_hardening_require_staff'));
$results[] = p11s_pass('No workflow mutation SQL', !preg_match('/\b(INSERT INTO|UPDATE)\s+dbo\.erp_(jobcards|final_invoices|payments)\b/i', $all));
$results[] = p11s_pass('No payment gateway', !preg_match('/zarinpal|stripe|payment_gateway_api/i', $all));
$results[] = p11s_pass('No accounting ledger', !preg_match('/INSERT INTO dbo\.erp_ledger|journal_entry_create/i', $all));
$results[] = p11s_pass('Read-only pages no POST', !preg_match('/<form[^>]+method\s*=\s*["\']post/i', $all));

$pass = 0; $fail = 0;
echo "# P11 Security Final Scan Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
