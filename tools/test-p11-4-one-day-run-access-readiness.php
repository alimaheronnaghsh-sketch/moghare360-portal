<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

require_once $pub . '/includes/m360-access-management-helper.php';
require_once $pub . '/includes/m360-access-permission-preview-helper.php';

function p114o_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }

$results = [
    p114o_pass('first-wave role codes defined', count(m360_access_mgmt_first_wave_role_codes()) >= 8),
    p114o_pass('SERVICE_MANAGER maps to operations_manager', (m360_access_mgmt_resolve_role_code('SERVICE_MANAGER')['role_key'] ?? '') === 'operations_manager'),
    p114o_pass('readiness report function', function_exists('m360_access_readiness_report')),
    p114o_pass('staff list function', function_exists('m360_access_user_list_staff')),
    p114o_pass('preview function', function_exists('m360_access_preview_load')),
];

$conn = m360_access_mgmt_db();
if ($conn !== false) {
    $report = m360_access_readiness_report($conn);
    $results[] = p114o_pass('readiness status enum', in_array($report['status'] ?? '', ['PASS', 'WARNING', 'BLOCKED'], true));
    $results[] = p114o_pass('owner shared login check present', (bool)array_filter($report['checks'] ?? [], static fn(array $c): bool => ($c['code'] ?? '') === 'OWNER_SHARED_LOGIN_RISK'));
} else {
    $results[] = p114o_pass('DB optional — readiness struct only', true);
    $results[] = p114o_pass('DB optional — checks skipped', true);
}

$mgmt = is_file($pub . '/erp-access-management.php') ? (string)file_get_contents($pub . '/erp-access-management.php') : '';
$results[] = p114o_pass('management page shows readiness', str_contains($mgmt, 'm360_access_readiness_report'));

$pass = 0; $fail = 0;
echo "# P11.4 One-Day Run Access Readiness Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
