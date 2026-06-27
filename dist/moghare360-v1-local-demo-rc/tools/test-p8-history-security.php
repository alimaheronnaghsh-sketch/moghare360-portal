<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';
$migration = is_file($root . '/database/migrations/P8_management_dashboard_owner_control.sql')
    ? (string)file_get_contents($root . '/database/migrations/P8_management_dashboard_owner_control.sql')
    : '';

function p8h_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p8h_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$p8Files = [
    'erp-management-dashboard.php',
    'erp-owner-control-center.php',
    'erp-operational-kpi.php',
    'erp-jobcard-timeline.php',
    'erp-bottleneck-monitor.php',
    'erp-financial-control-summary.php',
    'includes/m360-management-kpi-helper.php',
    'includes/m360-owner-control-helper.php',
    'includes/m360-bottleneck-helper.php',
    'includes/m360-jobcard-timeline-helper.php',
    'includes/m360-financial-control-helper.php',
    'api/management/kpi-summary.php',
    'api/management/bottleneck-summary.php',
    'api/management/jobcard-timeline.php',
];

$all = '';
foreach ($p8Files as $rel) {
    $all .= p8h_read($public . '/' . $rel);
}

$results = [];
$results[] = p8h_pass('No credential leak in P8', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $all));
$results[] = p8h_pass('No connection string secret', !preg_match('/Server=.*Password=/i', $all));
$results[] = p8h_pass('staff-login exists', is_file($public . '/staff-login.php'));
$results[] = p8h_pass('access-control exists', is_file($public . '/access-control.php'));
$results[] = p8h_pass('P8 does not rewrite staff-login', !str_contains($all, 'file_put_contents') || !str_contains($all, 'staff-login.php'));
$results[] = p8h_pass('P8 uses staff auth gate', str_contains($all, 'm360_mgmt_require_staff') || str_contains($all, 'erp_auth_current_user_id'));
$results[] = p8h_pass('No destructive SQL in migration', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p8h_pass('No gate bypass in P8', !preg_match('/skip.*gate|bypass.*gate|gate.*=.*true.*override/i', $all));
$results[] = p8h_pass('No m360_p7_assert bypass', !preg_match('/assert.*false|gates.*disabled/i', $all));
$results[] = p8h_pass('Auth redirect to staff-login', str_contains(p8h_read($public . '/includes/m360-management-kpi-helper.php'), 'staff-login.php'));
$results[] = p8h_pass('API requires staff session', str_contains(p8h_read($public . '/api/management/kpi-summary.php'), 'm360_mgmt_require_staff'));
$results[] = p8h_pass('No raw SQL echo in API', !preg_match('/echo\s+\$sql|var_dump\s*\(\s*\$sql/i', $all));

$pass = 0; $fail = 0;
echo "# P8 History Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
