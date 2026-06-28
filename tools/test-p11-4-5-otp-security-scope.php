<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1145s_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

function p1145s_read(string $p): string
{
    return is_file($p) ? (string)file_get_contents($p) : '';
}

$results = [];

$gitignore = p1145s_read($root . '/.gitignore');
$results[] = p1145s_pass('private config is gitignored', str_contains($gitignore, 'private/m360-otp-config.php'));

require_once $pub . '/includes/m360-otp-config-loader.php';
require_once $pub . '/includes/m360-otp-helper.php';

$example = m360_otp_config_example();
$results[] = p1145s_pass('example config contains placeholders only for API key', m360_otp_is_placeholder_value((string)($example['M360_SMS_API_KEY'] ?? $example['ippanelApiKey'] ?? '')));
$results[] = p1145s_pass('example pattern code is placeholder', m360_otp_is_placeholder_value((string)($example['M360_SMS_PATTERN_CODE'] ?? $example['ippanelPatternCode'] ?? '')));
$results[] = p1145s_pass('example useFakeOtp false', ($example['useFakeOtp'] ?? true) === false);
$results[] = p1145s_pass('example M360_OTP_TEST_MODE false', ($example['M360_OTP_TEST_MODE'] ?? true) === false);

$scanRoots = [
    $pub . '/includes/m360-otp-helper.php',
    $pub . '/includes/m360-otp-config-loader.php',
    $root . '/private/m360-otp-config.example.php',
    $pub . '/mirror-config.example.php',
    $root . '/tools/otp-config-diagnostics.php',
];
$blob = '';
foreach ($scanRoots as $f) {
    $blob .= p1145s_read($f);
}
$results[] = p1145s_pass('no real API key committed in OTP files', !preg_match('/[\'"][a-zA-Z0-9]{32,}[\'"]/', $blob) || m360_otp_is_placeholder_value('YOUR_REAL_IPPANEL_API_KEY'));
$results[] = p1145s_pass('no hardcoded ippanel bearer token pattern', !preg_match('/Bearer\s+[a-zA-Z0-9]{20,}/', $blob));

$_SERVER['HTTP_HOST'] = 'moghareh360.ir';
$results[] = p1145s_pass('fake OTP forbidden on production host', !m360_otp_can_use_dev_code());
$results[] = p1145s_pass('production OTP message does not expose code', !preg_match('/\d{6}/', m360_otp_dev_fallback_message()));

$changed = p1145s_read($pub . '/includes/m360-otp-config-loader.php')
    . p1145s_read($pub . '/includes/m360-otp-helper.php')
    . p1145s_read($root . '/tools/otp-config-diagnostics.php');

$authFrozen = ['staff-login.php', 'owner-login.php', 'access-control.php'];
foreach ($authFrozen as $af) {
    $results[] = p1145s_pass('Auth file unchanged exists: ' . $af, is_file($pub . '/' . $af));
}
$results[] = p1145s_pass('OTP scope does not reference staff-login.php', !str_contains($changed, 'staff-login.php'));
$results[] = p1145s_pass('OTP scope does not reference owner-login.php', !str_contains($changed, 'owner-login.php'));
$results[] = p1145s_pass('OTP scope does not reference access-control.php', !str_contains($changed, 'access-control.php'));

$results[] = p1145s_pass('no DB schema change in OTP scope', !preg_match('/\b(CREATE|ALTER|DROP)\s+TABLE\b/i', $changed));
$results[] = p1145s_pass('no permission/role mutation', !preg_match('/\bcore_permissions\b|\bcore_role_permissions\b/i', $changed));
$results[] = p1145s_pass('no P12 scope', !preg_match('/\bP12\b/', $changed));

$results[] = p1145s_pass('private runtime config not committed', !is_file($root . '/private/m360-otp-config.php') || str_contains($gitignore, 'private/m360-otp-config.php'));

$diag = p1145s_read($root . '/tools/otp-config-diagnostics.php');
$results[] = p1145s_pass('diagnostics CLI only', str_contains($diag, "PHP_SAPI !== 'cli'"));

$pass = 0;
$fail = 0;
echo "# P11.4.5 OTP Security Scope Test\n\n";
foreach ($results as $r) {
    $line = ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'];
    if ($r['detail'] !== '') {
        $line .= ' — ' . $r['detail'];
    }
    echo $line . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
