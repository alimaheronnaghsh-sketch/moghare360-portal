<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$registryPath = $root . '/public_html/includes/m360-navigation-registry.php';

function p10n_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p10n_lint(string $path): array {
    global $phpBin;
    $out = []; $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p10n_pass('PHP lint: m360-navigation-registry.php', $code === 0, implode(' ', $out));
}

$results = [];
$results[] = p10n_pass('Registry file exists', is_file($registryPath));
$results[] = p10n_lint($registryPath);

require_once $registryPath;

$results[] = p10n_pass('m360_nav_registry exists', function_exists('m360_nav_registry'));
$results[] = p10n_pass('m360_nav_phases exists', function_exists('m360_nav_phases'));
$results[] = p10n_pass('m360_nav_registry_by_key exists', function_exists('m360_nav_registry_by_key'));

$registry = m360_nav_registry();
$phases = m360_nav_phases();
$results[] = p10n_pass('Registry not empty', count($registry) >= 50, 'count=' . count($registry));
$results[] = p10n_pass('Phases include P1–P10', in_array('P1', $phases, true) && in_array('P10', $phases, true));

$expectedPhases = ['P1', 'P1.5', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7', 'P8', 'P9', 'P10'];
foreach ($expectedPhases as $phase) {
    $phaseRoutes = array_filter($registry, static fn(array $r): bool => ($r['phase_code'] ?? '') === $phase);
    $results[] = p10n_pass('Routes for ' . $phase, count($phaseRoutes) >= 1, 'count=' . count($phaseRoutes));
}

$keys = [];
$urls = [];
$allOk = true;
$flagChecks = ['is_api' => 0, 'is_customer_entry' => 0, 'is_staff_entry' => 0, 'is_demo_entry' => 0, 'is_owner_entry' => 0];
foreach ($registry as $route) {
    $key = (string)($route['route_key'] ?? '');
    $url = (string)($route['url'] ?? '');
    $method = (string)($route['expected_method'] ?? '');
    if ($key === '' || isset($keys[$key])) {
        $allOk = false;
    }
    $keys[$key] = true;
    if ($url === '') {
        $allOk = false;
    }
    $urls[$url] = ($urls[$url] ?? 0) + 1;
    if (!in_array($method, ['GET', 'POST'], true)) {
        $allOk = false;
    }
    foreach (array_keys($flagChecks) as $flag) {
        if (!empty($route[$flag])) {
            $flagChecks[$flag]++;
        }
    }
}
$results[] = p10n_pass('Unique route_key values', count($keys) === count($registry), 'keys=' . count($keys));
$results[] = p10n_pass('Non-empty url on all routes', $allOk);
$results[] = p10n_pass('expected_method GET or POST', $allOk);
$results[] = p10n_pass('API routes present', $flagChecks['is_api'] >= 10, 'count=' . $flagChecks['is_api']);
$results[] = p10n_pass('Customer entry routes present', $flagChecks['is_customer_entry'] >= 5, 'count=' . $flagChecks['is_customer_entry']);
$results[] = p10n_pass('Staff entry routes present', $flagChecks['is_staff_entry'] >= 30, 'count=' . $flagChecks['is_staff_entry']);
$results[] = p10n_pass('Demo entry routes present', $flagChecks['is_demo_entry'] >= 5, 'count=' . $flagChecks['is_demo_entry']);
$results[] = p10n_pass('Owner entry routes present', $flagChecks['is_owner_entry'] >= 5, 'count=' . $flagChecks['is_owner_entry']);

$p10Routes = array_filter($registry, static fn(array $r): bool => ($r['phase_code'] ?? '') === 'P10');
$results[] = p10n_pass('P10 routes in registry', count($p10Routes) >= 5, 'count=' . count($p10Routes));
$p10Keys = ['p10_product_home', 'p10_demo_package_rc', 'p10_release_readiness', 'p10_route_map', 'p10_link_audit'];
$byKey = m360_nav_registry_by_key();
foreach ($p10Keys as $pk) {
    $results[] = p10n_pass('P10 route_key: ' . $pk, isset($byKey[$pk]));
}

$pass = 0; $fail = 0;
echo "# P10 Navigation Registry Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
