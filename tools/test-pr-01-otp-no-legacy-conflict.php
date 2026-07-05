<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr01_legacy_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$js = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');
$legacyRoutes = [
    'send-otp.php',
    'verify-otp.php',
    'check-otp.php',
    'send-contract-otp.php',
    'verify-contract-otp.php',
];

$results = [];
foreach ($legacyRoutes as $route) {
    $src = (string)file_get_contents($root . '/public_html/' . $route);
    $results[] = pr01_legacy_pass($route . ' uses deprecation stub', str_contains($src, 'm360-legacy-otp-deprecation-stub.php'));
}

$results[] = pr01_legacy_pass('customer-form avoids legacy send-otp', !str_contains($js, "'send-otp.php'") && !str_contains($js, '"send-otp.php"'));
$results[] = pr01_legacy_pass('customer-form avoids contract otp', !str_contains($js, 'contract-otp'));
$results[] = pr01_legacy_pass('customer-form avoids verify-otp root', !preg_match("/fetchJson\\(['\"]verify-otp\\.php/", $js));

$pass = 0;
$fail = 0;
echo "# PR-01 OTP No Legacy Conflict Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
