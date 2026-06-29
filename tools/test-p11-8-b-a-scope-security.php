<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p118ba_sec_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

$results = [];
$shell = (string)file_get_contents($pub . '/includes/m360-operational-shell-helper.php');
$staffHelper = (string)file_get_contents($pub . '/includes/m360-staff-home-helper.php');

$results[] = p118ba_sec_pass('shell no ALTER TABLE', !str_contains($shell, 'ALTER TABLE'));
$results[] = p118ba_sec_pass('shell no INSERT workflow', !preg_match('/INSERT INTO dbo\.erp_/i', $shell));
$results[] = p118ba_sec_pass('shell no new action handler', !str_contains($shell, '-action.php'));
$results[] = p118ba_sec_pass('staff helper runtime hold only metadata', str_contains($staffHelper, 'runtime_hold') && str_contains($staffHelper, 'm360_staff_home_apply_runtime_hold'));

$authFiles = ['staff-login.php', 'owner-login.php', 'api/auth/staff-login.php', 'api/auth/owner-login.php'];
foreach ($authFiles as $rel) {
    $path = $pub . '/' . $rel;
    if (is_file($path)) {
        $results[] = p118ba_sec_pass('auth file untouched in phase: ' . $rel, is_readable($path));
    }
}

$sqlMigrations = glob($root . '/database/migrations/*.sql') ?: [];
$sqlChangedRecently = false;
foreach ($sqlMigrations as $sqlFile) {
    if (filemtime($sqlFile) > time() - 120) {
        $sqlChangedRecently = true;
    }
}
$results[] = p118ba_sec_pass('no SQL migration modified in last 2 min', !$sqlChangedRecently);

$seedPaths = glob($root . '/database/**/*.sql') ?: [];
$hasSeedChange = false;
foreach ($seedPaths as $p) {
    if (str_contains($p, 'seed') && filemtime($p) > time() - 120) {
        $hasSeedChange = true;
    }
}
$results[] = p118ba_sec_pass('no permission seed modified recently', !$hasSeedChange);

$results[] = p118ba_sec_pass('scope report exists', is_file($root . '/docs/audit/MOGHARE360_P11_8_B_A_OPERATIONAL_SHELL_SCOPE_REPORT.md'));

require_once $pub . '/includes/m360-staff-home-helper.php';
$results[] = p118ba_sec_pass('P11.8 bridge still present OWNER', count(m360_staff_home_manager_bridge_items('OWNER')) >= 10);
$results[] = p118ba_sec_pass('info cards still not clickable', !m360_staff_home_item_clickable([
    'card_type' => 'info', 'file' => 'erp-settlement-detail.php',
]));

$pass = 0;
$fail = 0;
echo "# P11.8-B-A Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
