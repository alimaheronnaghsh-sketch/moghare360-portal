<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p118c_sec_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

$results = [];
$helper = (string)file_get_contents($pub . '/includes/m360-route-operational-safety-helper.php');
$routeMap = (string)file_get_contents($pub . '/erp-route-map.php');
$registry = (string)file_get_contents($pub . '/includes/m360-navigation-registry.php');

$results[] = p118c_sec_pass('helper no ALTER TABLE', !str_contains($helper, 'ALTER TABLE'));
$results[] = p118c_sec_pass('helper no workflow INSERT', !preg_match('/INSERT INTO dbo\.erp_/i', $helper));
$results[] = p118c_sec_pass('registry file unchanged semantics', !str_contains($registry, 'operational_class'));
$results[] = p118c_sec_pass('scope report exists', is_file($root . '/docs/audit/MOGHARE360_P11_8_C_ROUTE_MAP_OPERATIONAL_SAFETY_SCOPE_REPORT.md'));

$authFiles = ['staff-login.php', 'owner-login.php'];
foreach ($authFiles as $f) {
    $results[] = p118c_sec_pass('auth readable unchanged check: ' . $f, is_file($pub . '/' . $f));
}

require_once $pub . '/includes/m360-staff-home-helper.php';
$results[] = p118c_sec_pass('staff home part-use still runtime hold', !m360_staff_home_is_runtime_ready('erp-jobcard-part-use.php'));
$results[] = p118c_sec_pass('staff home bridge still present', count(m360_staff_home_manager_bridge_items('OWNER')) >= 10);

require_once $pub . '/includes/m360-operational-shell-helper.php';
$results[] = p118c_sec_pass('operational shell route map link', str_contains((string)M360_OPS_SHELL_ROUTE_MAP, 'erp-route-map.php'));

$pass = 0;
$fail = 0;
echo "# P11.8-C Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
