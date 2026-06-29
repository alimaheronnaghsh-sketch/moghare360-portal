<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p118cfa_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
// Split intentionally so this test file does not contain the contiguous typo literal.
$typo = 'تشخ' . 'ic' . 'صی';
$expectedDiagnosticBadge = 'تشخیصی / مدیریتی';

$scanPaths = [
    $pub . '/includes/m360-route-operational-safety-helper.php',
    $pub . '/erp-route-map.php',
    $pub . '/assets/css/m360-route-map-safety.css',
    $root . '/docs/audit/MOGHARE360_P11_8_C_ROUTE_MAP_OPERATIONAL_SAFETY_REPORT.md',
    $root . '/docs/audit/MOGHARE360_P11_8_C_ROUTE_MAP_OPERATIONAL_SAFETY_SCOPE_REPORT.md',
    $root . '/tools/test-p11-8-c-route-classifier.php',
    $root . '/tools/test-p11-8-c-route-map-operational-safety.php',
    $root . '/tools/test-p11-8-c-scope-security.php',
    __FILE__,
];

foreach ($scanPaths as $path) {
    $label = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    if (!is_file($path)) {
        $results[] = p118cfa_pass('scan file exists: ' . $label, false, 'missing');
        continue;
    }
    $content = (string)file_get_contents($path);
    $results[] = p118cfa_pass('no typo in: ' . $label, !str_contains($content, $typo));
}

require_once $pub . '/includes/m360-route-operational-safety-helper.php';

$results[] = p118cfa_pass(
    'diagnostic badge exact',
    (M360_ROUTE_OPS_BADGE_FA[M360_ROUTE_OPS_CLASS_DIAGNOSTIC] ?? '') === $expectedDiagnosticBadge,
    M360_ROUTE_OPS_BADGE_FA[M360_ROUTE_OPS_CLASS_DIAGNOSTIC] ?? ''
);

$expectedBadges = [
    M360_ROUTE_OPS_CLASS_OPERATIONAL => 'قابل ورود',
    M360_ROUTE_OPS_CLASS_GUIDED => 'راهنمای مسیر',
    M360_ROUTE_OPS_CLASS_ACTION => 'عملیات داخلی',
    M360_ROUTE_OPS_CLASS_API => 'API سیستم',
    M360_ROUTE_OPS_CLASS_CUSTOMER => 'مسیر مشتری',
    M360_ROUTE_OPS_CLASS_DIAGNOSTIC => 'تشخیصی / مدیریتی',
    M360_ROUTE_OPS_CLASS_RUNTIME_HOLD => 'نیازمند بررسی عملیاتی',
];

foreach ($expectedBadges as $class => $label) {
    $actual = M360_ROUTE_OPS_BADGE_FA[$class] ?? '';
    $results[] = p118cfa_pass('badge fa: ' . $class, $actual === $label, $actual);
    if ($class !== M360_ROUTE_OPS_CLASS_API && preg_match('/[a-z]/', $label)) {
        $results[] = p118cfa_pass('badge no stray latin: ' . $class, false, $label);
    }
}

$rows = m360_route_ops_enrich_audit_rows();
$counts = m360_route_ops_summary_counts($rows);
$results[] = p118cfa_pass('registry route count stable', count($rows) === count(m360_nav_registry()));
$results[] = p118cfa_pass('ops clickable count stable', ($counts['ops_clickable'] ?? 0) === 23, (string)($counts['ops_clickable'] ?? 0));

$classCounts = [];
foreach ($rows as $row) {
    $k = (string)($row['ops_class'] ?? '');
    $classCounts[$k] = ($classCounts[$k] ?? 0) + 1;
}
$expectedClassCounts = [
    'operational' => 9,
    'guided' => 10,
    'action' => 11,
    'api' => 12,
    'customer' => 7,
    'diagnostic' => 14,
];
foreach ($expectedClassCounts as $class => $expected) {
    $actual = $classCounts[$class] ?? 0;
    $results[] = p118cfa_pass('class count unchanged: ' . $class, $actual === $expected, "$actual expected $expected");
}

$page = (string)file_get_contents($pub . '/erp-route-map.php');
$results[] = p118cfa_pass('operational view present', str_contains($page, 'نمای عملیاتی'));
$results[] = p118cfa_pass('technical view present', str_contains($page, 'view=technical'));

$authFiles = [$pub . '/staff-login.php', $pub . '/owner-login.php'];
foreach ($authFiles as $f) {
    $results[] = p118cfa_pass('auth file exists: ' . basename($f), is_file($f));
}

$sqlDir = $root . '/sql';
$sqlChanged = false;
if (is_dir($sqlDir)) {
    foreach (glob($sqlDir . '/*.sql') ?: [] as $sqlFile) {
        if (filemtime($sqlFile) > time() - 3600) {
            $sqlChanged = true;
            break;
        }
    }
}
$results[] = p118cfa_pass('no recent SQL migration edits', !$sqlChanged);

$pass = 0;
$fail = 0;
echo "# P11.8-C-FIX-A Persian Label Consistency Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'];
    if ($r['detail'] !== '') {
        echo ' — ' . $r['detail'];
    }
    echo "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
