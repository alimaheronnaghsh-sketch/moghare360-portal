<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function c2c_sc_pass(string $name, bool $ok): array
{
    return ['name' => $name, 'pass' => $ok];
}

$results = [];
$results[] = c2c_sc_pass('scope report exists', is_file($root . '/docs/audit/MOGHARE360_P11_9_C_2C_SCOPE_REPORT.md'));
$results[] = c2c_sc_pass('save endpoint exists', is_file($root . '/public_html/erp-reception-intake-save.php'));

$forbidden = ['public_html/staff-auth.php', 'public_html/access-control.php'];
foreach ($forbidden as $rel) {
    $path = $root . '/' . $rel;
    $mtime = is_file($path) ? filemtime($path) : 0;
    $results[] = c2c_sc_pass('not modified recently: ' . basename($rel), $mtime === 0 || $mtime < time() - 7200);
}

$migrations = glob($root . '/database/migrations/*.sql') ?: [];
$recentMigration = false;
foreach ($migrations as $sql) {
    if (filemtime($sql) > time() - 3600) {
        $recentMigration = true;
        break;
    }
}
$results[] = c2c_sc_pass('no new SQL migration', !$recentMigration);

$helper = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$results[] = c2c_sc_pass('no CREATE TABLE in helper', !preg_match('/CREATE\s+TABLE/i', $helper));
$results[] = c2c_sc_pass('intake write actions defined', str_contains($helper, 'm360_rw_intake_process_save'));
$requiredActions = [
    'save_vehicle_identity',
    'save_condition_notes',
    'save_service_classification',
    'save_temporary_reception',
    'save_documents_and_cost',
    'save_reception_confirmation',
    'save_mobile_correction',
    'save_referral_team',
    'save_camera_photo',
    'save_diagnostic_pdf',
    'run_intake_contract',
    'approve_intake_contract',
];
$allowed = m360_rw_intake_allowed_actions();
$missing = array_diff($requiredActions, $allowed);
$results[] = c2c_sc_pass('intake write action types present', $missing === []);

exec('"' . $phpBin . '" ' . escapeshellarg($root . '/tools/test-v1-production-signoff.php') . ' 2>&1', $sigOut, $sigCode);
$results[] = c2c_sc_pass('test-v1-production-signoff passes', $sigCode === 0);

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
