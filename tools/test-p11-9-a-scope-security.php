<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p119a_sec_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];

$dryRunFiles = glob($root . '/docs/dry-run/P11_9_A_*.md') ?: [];
$allText = '';
foreach ($dryRunFiles as $f) {
    $allText .= (string)file_get_contents($f);
}
$sqlFiles = glob($root . '/database/dry-run/P11_9_A_*.sql') ?: [];
foreach ($sqlFiles as $f) {
    $allText .= (string)file_get_contents($f);
}

$results[] = p119a_sec_pass('no ALTER TABLE in pack', !preg_match('/ALTER TABLE/i', $allText));
$results[] = p119a_sec_pass('no OTP secret patterns', !preg_match('/ippanelApiKey\s*=\s*[\'"][^\'"]{8,}/i', $allText));
$results[] = p119a_sec_pass('pack states no auto user creation', str_contains($allText, 'not auto') || str_contains($allText, 'not automatically') || str_contains($allText, 'does NOT create'));

$authFiles = ['staff-login.php', 'owner-login.php'];
foreach ($authFiles as $f) {
    $path = $pub . '/' . $f;
    $results[] = p119a_sec_pass('auth file unchanged check readable: ' . $f, is_file($path));
}

$migrationDir = $root . '/database/migrations';
$migrationChanged = false;
if (is_dir($migrationDir)) {
    foreach (glob($migrationDir . '/*.sql') ?: [] as $sql) {
        if (filemtime($sql) > time() - 3600) {
            $migrationChanged = true;
            break;
        }
    }
}
$results[] = p119a_sec_pass('no recent SQL migration edits', !$migrationChanged);

$roleSeed = $pub . '/sql/sqlserver/core_v0_06_seed_roles_permissions.sql';
$results[] = p119a_sec_pass('role seed file readable', is_file($roleSeed));

require_once $pub . '/includes/m360-staff-home-helper.php';
$results[] = p119a_sec_pass('part-use still runtime hold', !m360_staff_home_is_runtime_ready('erp-jobcard-part-use.php'));
$results[] = p119a_sec_pass('payment still runtime hold', !m360_staff_home_is_runtime_ready('erp-payment-tracking.php'));

$pass = 0;
$fail = 0;
echo "# P11.9-A Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
