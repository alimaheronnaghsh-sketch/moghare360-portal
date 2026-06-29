<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-staff-home-helper.php';

function p118n_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

$helper = (string)file_get_contents($pub . '/includes/m360-staff-home-helper.php');
$results = [];

$results[] = p118n_pass('bridge helper exists', str_contains($helper, 'm360_staff_home_admin_manager_bridge_items'));
$results[] = p118n_pass('no impersonation function', !preg_match('/function\s+\w*impersonat|act_as_staff|act_on_behalf/i', $helper));
$results[] = p118n_pass('impersonation backlog documents forbidden', str_contains($helper, 'غیرمجاز در V1'));
$results[] = p118n_pass('no ALTER TABLE', !preg_match('/\bALTER\s+TABLE\b/i', $helper));
$results[] = p118n_pass('no password_verify', !preg_match('/password_verify\s*\(/', $helper));
$results[] = p118n_pass('no core_roles insert', !preg_match('/INSERT\s+INTO\s+.*core_roles/i', $helper));
$results[] = p118n_pass('session require unchanged', str_contains($helper, 'm360_staff_home_require_session'));

foreach (['staff-login.php', 'owner-login.php'] as $f) {
    $results[] = p118n_pass('auth file exists untouched: ' . $f, is_file($pub . '/' . $f));
}

$pass = 0;
$fail = 0;
echo "# P11.8-A No Impersonation Scope Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
