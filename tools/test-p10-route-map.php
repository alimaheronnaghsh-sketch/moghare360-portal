<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . '/public_html';

function p10m_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p10m_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }
function p10m_lint(string $rel): array {
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $out = []; $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p10m_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$pages = ['erp-route-map.php', 'erp-product-home.php'];
$helpers = ['includes/m360-route-audit-helper.php', 'includes/m360-navigation-registry.php'];

$ui = '';
foreach ($pages as $page) {
    $ui .= p10m_read($public . '/' . $page);
}

$results = [];
foreach ($pages as $page) {
    $results[] = p10m_pass('Page exists: ' . $page, is_file($public . '/' . $page));
}
foreach ($helpers as $helper) {
    $results[] = p10m_pass('Helper: ' . basename($helper), is_file($public . '/' . $helper));
}
$results[] = p10m_pass('Route map uses m360_route_audit_summary', str_contains(p10m_read($public . '/erp-route-map.php'), 'm360_route_audit_summary'));
$results[] = p10m_pass('Route map uses m360_nav_registry', str_contains(p10m_read($public . '/includes/m360-route-audit-helper.php'), 'm360_nav_registry'));
$results[] = p10m_pass('Product home uses registry audit', str_contains(p10m_read($public . '/erp-product-home.php'), 'm360_release_hardening_audit'));
$results[] = p10m_pass('RTL on route map pages', str_contains($ui, 'dir="rtl"'));
$results[] = p10m_pass('Persian product home title', str_contains($ui, 'MOGHARE360 V1 Product Home') || str_contains($ui, 'خانه محصول'));
$results[] = p10m_pass('No credentials in UI', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $ui));
$results[] = p10m_pass('Staff gate on route map', str_contains(p10m_read($public . '/erp-route-map.php'), 'm360_release_hardening_require_staff'));
$results[] = p10m_pass('Staff gate on product home', str_contains(p10m_read($public . '/erp-product-home.php'), 'm360_release_hardening_require_staff'));

$readOnlyUi = '';
foreach (['erp-route-map.php', 'erp-product-home.php'] as $page) {
    $readOnlyUi .= p10m_read($public . '/' . $page);
}
$results[] = p10m_pass('No POST on read-only pages', !preg_match('/method\s*=\s*[\'"]post[\'"]/i', $readOnlyUi));
$results[] = p10m_pass('No SQL mutation on read-only pages', !preg_match('/\b(INSERT INTO|UPDATE dbo|DELETE FROM)\b/i', $readOnlyUi));
$results[] = p10m_pass('Route map shows P1–P10', str_contains(p10m_read($public . '/erp-route-map.php'), 'P1–P10'));

foreach ($pages as $page) {
    $results[] = p10m_lint($page);
}
foreach ($helpers as $helper) {
    $results[] = p10m_lint($helper);
}

$pass = 0; $fail = 0;
echo "# P10 Route Map Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
