<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . '/public_html';
$signoffTest = $root . '/tools/test-v1-production-signoff.php';

function p11f_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p11f_lint(string $rel): array {
    global $phpBin, $root;
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $out = []; $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p11f_pass('PHP lint: ' . basename($rel), $code === 0, implode(' ', $out));
}

$p11Pages = [
    'public_html/erp-rc-final-audit.php',
    'public_html/erp-local-demo-package.php',
    'public_html/erp-owner-presentation-lock.php',
    'public_html/erp-rc-final-checklist.php',
    'public_html/includes/m360-rc-final-audit-helper.php',
    'public_html/includes/m360-local-demo-package-helper.php',
    'public_html/includes/m360-owner-presentation-helper.php',
    'public_html/includes/m360-release-lock-helper.php',
];

$results = [];
$results[] = p11f_pass('test-v1-production-signoff.php exists', is_file($signoffTest));
$results[] = p11f_pass('test-v1-production-signoff.php readable', is_readable($signoffTest));

require_once $public . '/includes/m360-navigation-registry.php';
$registry = m360_nav_registry();
$results[] = p11f_pass('Route registry 63+ routes', count($registry) >= 63, 'count=' . count($registry));
$results[] = p11f_pass('P9 soft run entry', isset(m360_nav_registry_by_key()['p9_soft_run_center']));
$results[] = p11f_pass('P8 management entry', isset(m360_nav_registry_by_key()['p8_management_dashboard']));
$results[] = p11f_pass('P10 product home route', isset(m360_nav_registry_by_key()['p10_product_home']));

foreach ($p11Pages as $rel) {
    $results[] = p11f_lint($rel);
}

$pass = 0; $fail = 0;
echo "# P11 Production Signoff Final Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
