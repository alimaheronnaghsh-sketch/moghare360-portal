<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

require_once $pub . '/includes/m360-staff-home-helper.php';

function p1144a_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p1144a_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

function p1144a_route_files(string $roleCode): array
{
    $matrix = m360_staff_home_role_routes($roleCode);
    $files = [];
    foreach ($matrix['allowed_routes'] as $route) {
        $files[] = (string)($route['file'] ?? '');
    }
    return $files;
}

$staffRoles = ['RECEPTION', 'SERVICE_MANAGER', 'TECHNICIAN', 'PARTS', 'FINANCE', 'QC'];
$adminOnly = ['erp-access-management.php', 'erp-management-dashboard.php', 'erp-owner-control-center.php', 'erp-release-readiness.php'];
$financePages = ['erp-finance-center.php', 'erp-payment-tracking.php', 'erp-final-invoice-board.php', 'erp-settlement-detail.php'];
$technicalPages = ['erp-technical-board.php', 'erp-work-execution-board.php'];

$results = [];

foreach ($staffRoles as $role) {
    $files = p1144a_route_files($role);
    foreach ($adminOnly as $blocked) {
        $results[] = p1144a_pass($role . ' excludes ' . $blocked, !in_array($blocked, $files, true));
    }
}

$receptionFiles = p1144a_route_files('RECEPTION');
foreach (array_merge($financePages, $technicalPages, ['erp-access-management.php']) as $blocked) {
    $results[] = p1144a_pass('RECEPTION excludes ' . $blocked, !in_array($blocked, $receptionFiles, true));
}

$techFiles = p1144a_route_files('TECHNICIAN');
foreach (array_merge($financePages, ['erp-access-management.php', 'erp-stock-board.php', 'erp-parts-catalog.php']) as $blocked) {
    $results[] = p1144a_pass('TECHNICIAN excludes ' . $blocked, !in_array($blocked, $techFiles, true));
}

$financeFiles = p1144a_route_files('FINANCE');
foreach ($technicalPages as $blocked) {
    $results[] = p1144a_pass('FINANCE excludes ' . $blocked, !in_array($blocked, $financeFiles, true));
}
$results[] = p1144a_pass('FINANCE excludes access management', !in_array('erp-access-management.php', $financeFiles, true));

$partsFiles = p1144a_route_files('PARTS');
$results[] = p1144a_pass('PARTS excludes settlement-detail', !in_array('erp-settlement-detail.php', $partsFiles, true));
$results[] = p1144a_pass('PARTS excludes access management', !in_array('erp-access-management.php', $partsFiles, true));

$qcFiles = p1144a_route_files('QC');
foreach (['erp-settlement-detail.php', 'erp-payment-tracking.php', 'erp-access-management.php'] as $blocked) {
    $results[] = p1144a_pass('QC excludes ' . $blocked, !in_array($blocked, $qcFiles, true));
}

$ownerFiles = p1144a_route_files('OWNER');
$results[] = p1144a_pass('OWNER includes access management', in_array('erp-access-management.php', $ownerFiles, true));

$helper = p1144a_read($pub . '/includes/m360-staff-home-helper.php');
$home = p1144a_read($pub . '/erp-staff-home.php');
$results[] = p1144a_pass('missing route message constant', str_contains($helper, M360_STAFF_HOME_MISSING_ROUTE_FA));
$results[] = p1144a_pass('render checks file_exists via nav helper', str_contains($helper, 'm360_staff_home_route_exists'));
$results[] = p1144a_pass('disabled card for missing route', str_contains($helper, 'is-missing') && str_contains($helper, 'aria-disabled="true"'));
$results[] = p1144a_pass('staff home requires session not fallback user', str_contains($helper, 'erp_auth_context_session_user_id'));
$results[] = p1144a_pass('unknown role warning on page', str_contains($home, 'M360_STAFF_HOME_UNKNOWN_WARNING_FA'));

$pass = 0; $fail = 0;
echo "# P11.4.4 Staff Home Authorization Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
