<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

$p113Files = [
    'includes/m360-online-intake-security-helper.php',
    'includes/m360-online-intake-bridge-helper.php',
    'api/online-intake-secure-receive.php',
    'erp-online-test-readiness.php',
];

function p113sc_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p113sc_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$all = '';
foreach ($p113Files as $rel) {
    $all .= p113sc_read($public . '/' . $rel);
}

$results = [];
$results[] = p113sc_pass('staff-login unchanged exists', is_file($public . '/staff-login.php'));
$results[] = p113sc_pass('owner-login unchanged exists', is_file($public . '/owner-login.php'));
$results[] = p113sc_pass('access-control unchanged exists', is_file($public . '/access-control.php'));
$results[] = p113sc_pass('No auth file rewrite', !preg_match('/file_put_contents\s*\([^)]*(staff-login|owner-login|access-control)/i', $all));
$results[] = p113sc_pass('No payment gateway', !preg_match('/zarinpal|payment_gateway_api/i', $all));
$results[] = p113sc_pass('No accounting ledger', !preg_match('/INSERT INTO dbo\.erp_ledger/i', $all));
$results[] = p113sc_pass('No P12 taxonomy', !preg_match('/p12_|P12_/i', $all));
$results[] = p113sc_pass('No upload bypass', !preg_match('/\bmove_uploaded_file\b/i', $all));
$results[] = p113sc_pass('No production fake OTP', !preg_match('/production.*123456|fake.*otp.*production/i', $all));
$results[] = p113sc_pass('No credential leak pattern', !preg_match('/password\s*=\s*[\'"][^\'"]{8,}/i', $all));
$results[] = p113sc_pass('No workflow mutation SQL', !preg_match('/\bUPDATE\s+dbo\.erp_jobcards\b/i', $all));
$results[] = p113sc_pass('Uses staff gate on readiness', str_contains($all, 'm360_release_lock_require_staff'));
$results[] = p113sc_pass('No public debug endpoint', !preg_match('/debug\s*=\s*1|phpinfo/i', $all));

$pass = 0; $fail = 0;
echo "# P11.3 Security Scope Control Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
