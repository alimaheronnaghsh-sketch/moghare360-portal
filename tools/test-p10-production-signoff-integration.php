<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . '/public_html';
$signoffTest = $root . '/tools/test-v1-production-signoff.php';

function p10p_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p10p_lint(string $rel): array {
    global $phpBin, $root;
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $out = []; $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p10p_pass('PHP lint: ' . basename($rel), $code === 0, implode(' ', $out));
}

$p10Pages = [
    'public_html/erp-product-home.php',
    'public_html/erp-demo-package-rc.php',
    'public_html/erp-release-readiness.php',
    'public_html/erp-route-map.php',
    'public_html/erp-link-audit.php',
];

$results = [];
$results[] = p10p_pass('test-v1-production-signoff.php exists', is_file($signoffTest));
$results[] = p10p_pass('test-v1-production-signoff.php callable', is_readable($signoffTest));

require_once $public . '/includes/m360-navigation-registry.php';
$registry = m360_nav_registry();
$p8Routes = array_filter($registry, static fn(array $r): bool => ($r['phase_code'] ?? '') === 'P8');
$p9Routes = array_filter($registry, static fn(array $r): bool => ($r['phase_code'] ?? '') === 'P9');
$p10Routes = array_filter($registry, static fn(array $r): bool => ($r['phase_code'] ?? '') === 'P10');

$results[] = p10p_pass('P8 routes in registry', count($p8Routes) >= 5, 'count=' . count($p8Routes));
$results[] = p10p_pass('P9 routes in registry', count($p9Routes) >= 5, 'count=' . count($p9Routes));
$results[] = p10p_pass('P10 routes in registry', count($p10Routes) >= 5, 'count=' . count($p10Routes));
$results[] = p10p_pass('P8 management dashboard route', isset(m360_nav_registry_by_key()['p8_management_dashboard']));
$results[] = p10p_pass('P9 soft run center route', isset(m360_nav_registry_by_key()['p9_soft_run_center']));

foreach ($p10Pages as $rel) {
    $results[] = p10p_lint($rel);
}

require_once $public . '/includes/m360-demo-package-helper.php';
$manifest = m360_demo_package_manifest();
$results[] = p10p_pass('Manifest security exclusions', in_array('no_credentials_in_repo', $manifest['exclusions'] ?? [], true));
$results[] = p10p_pass('Manifest no production deploy', in_array('no_production_deploy', $manifest['exclusions'] ?? [], true));
$results[] = p10p_pass('Security scope lock doc required', is_file($root . '/docs/release/MOGHARE360_V1_SECURITY_SCOPE_LOCK.md'));
$results[] = p10p_pass('RC manifest doc required', is_file($root . '/docs/release/MOGHARE360_V1_RC_MANIFEST.md'));
$results[] = p10p_pass('Signoff test in required tests', in_array('test-v1-production-signoff.php', m360_release_required_tests(), true));

$pass = 0; $fail = 0;
echo "# P10 Production Signoff Integration Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
