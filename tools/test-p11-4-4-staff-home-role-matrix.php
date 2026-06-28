<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

require_once $pub . '/includes/m360-staff-home-helper.php';

function p1144m_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p1144m_pass('erp-staff-home.php exists', is_file($pub . '/erp-staff-home.php'));
$results[] = p1144m_pass('helper function exists', function_exists('m360_staff_home_role_routes'));

$requiredRoles = m360_staff_home_known_role_codes();
foreach ($requiredRoles as $role) {
    $matrix = m360_staff_home_role_routes($role);
    $results[] = p1144m_pass($role . ' has landing label', trim((string)($matrix['landing_label'] ?? '')) !== '');
    $results[] = p1144m_pass($role . ' has access summary', trim((string)($matrix['access_summary'] ?? '')) !== '');
    $results[] = p1144m_pass($role . ' has allowed_routes array', is_array($matrix['allowed_routes'] ?? null));
    if ($role !== 'UNKNOWN') {
        $results[] = p1144m_pass($role . ' has at least one route', count($matrix['allowed_routes']) > 0);
    }
}

$pass = 0; $fail = 0;
echo "# P11.4.4 Staff Home Role Matrix Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
