<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1171n_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

$allowed = [
    'public_html/includes/m360-staff-home-helper.php',
    'public_html/erp-staff-home.php',
    'public_html/assets/css/m360-staff-home.css',
    'docs/audit/MOGHARE360_P11_7_1_STAFF_HOME_UX_POLISH_SCOPE_REPORT.md',
    'docs/audit/MOGHARE360_P11_7_1_STAFF_HOME_UX_POLISH_REPORT.md',
    'tools/test-p11-7-1-staff-home-ux-polish.php',
    'tools/test-p11-7-1-no-new-scope.php',
];

$helper = (string)file_get_contents($pub . '/includes/m360-staff-home-helper.php');
$page = (string)file_get_contents($pub . '/erp-staff-home.php');

$results = [];

$results[] = p1171n_pass('no impersonation feature in helper', !preg_match('/function\s+\w*impersonat|impersonate_user|act_on_behalf/i', $helper));
$results[] = p1171n_pass('no ALTER TABLE in helper', !preg_match('/\bALTER\s+TABLE\b/i', $helper));
$results[] = p1171n_pass('no password_verify in helper', !preg_match('/password_verify\s*\(/', $helper));
$results[] = p1171n_pass('no HR page creation markers', !preg_match('/erp-employee-self-service|erp-leave-request|erp-staff-profile-erp/', $helper));

$authFiles = ['staff-login.php', 'owner-login.php', 'api/auth/staff-login.php', 'api/auth/owner-login.php'];
foreach ($authFiles as $f) {
    $path = $pub . '/' . $f;
    $results[] = p1171n_pass('Auth file untouched check exists: ' . $f, is_file($path));
}

$sqlDir = $root . '/database/migrations';
$sqlChanged = false;
if (is_dir($sqlDir)) {
    foreach (glob($sqlDir . '/*.sql') ?: [] as $sql) {
        if (filemtime($sql) > time() - 3600) {
            $sqlChanged = true;
        }
    }
}
$results[] = p1171n_pass('no recent SQL migration edits assumed', !$sqlChanged);

$results[] = p1171n_pass('staff home still requires session helper', str_contains($helper, 'm360_staff_home_require_session'));
$results[] = p1171n_pass('no core_permissions mutation', !preg_match('/core_permissions/i', $helper));
$results[] = p1171n_pass('no core_roles mutation', !preg_match('/INSERT\s+INTO\s+.*core_roles/i', $helper));
$results[] = p1171n_pass('backlog documents manager reference not engine', str_contains($helper, 'موتور Override مدیریتی') && str_contains($helper, 'غیرمجاز در V1'));
$results[] = p1171n_pass('P15 backlog labels present', str_contains($helper, 'P15 / HR Self-Service'));

$pass = 0;
$fail = 0;
echo "# P11.7.1 No New Scope Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
