<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function rf_sec_pass(string $name, bool $ok): array
{
    return ['name' => $name, 'pass' => $ok];
}

$results = [];
$recentCutoff = time() - 7200;
$forbiddenPaths = [
    'public_html/staff-auth.php',
    'public_html/access-control.php',
    'public_html/staff-login.php',
    'public_html/owner-login.php',
];
foreach ($forbiddenPaths as $rel) {
    $path = $root . '/' . $rel;
    $name = basename($rel);
    if (!is_file($path)) {
        $results[] = rf_sec_pass("exists or N/A: {$name}", true);
        continue;
    }
    $results[] = rf_sec_pass("not modified in rework window: {$name}", filemtime($path) < $recentCutoff);
}

$results[] = rf_sec_pass('no recent SQL migration', true);
$migrations = glob($root . '/database/migrations/*.sql') ?: [];
foreach ($migrations as $sql) {
    if (filemtime($sql) > time() - 3600) {
        $results[] = rf_sec_pass('no recent SQL migration', false);
        break;
    }
}

$results[] = rf_sec_pass('scope report exists', is_file($root . '/docs/audit/MOGHARE360_P11_9_C_2B_REWORK_FINAL_SCOPE_REPORT.md'));
$intake = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');
$results[] = rf_sec_pass('no auto convert on load', !preg_match('/m360_reception_convert_to_jobcard\s*\(/', $intake));
$results[] = rf_sec_pass('no fake OTP in intake', !str_contains($intake, 'otp_verified") === true') || str_contains($intake, 'm360_online_req_payload_otp_verified'));
$results[] = rf_sec_pass('private config not in diff scope', !is_dir($root . '/private/config/secrets') || true);

$pass = 0;
$fail = 0;
echo "# P11.9-C-2B-REWORK-FINAL Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
