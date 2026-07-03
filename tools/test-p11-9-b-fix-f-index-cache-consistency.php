<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
$indexPath = $pub . '/index.php';
$htmlPath = $pub . '/index.html';
$htaccessPath = $pub . '/.htaccess';
$layoutPath = $pub . '/includes/mirror-layout.php';
$swPath = $pub . '/service-worker.js';

function p119bfixf_cache_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$index = is_file($indexPath) ? (string)file_get_contents($indexPath) : '';
$html = is_file($htmlPath) ? (string)file_get_contents($htmlPath) : '';
$htaccess = is_file($htaccessPath) ? (string)file_get_contents($htaccessPath) : '';
$layout = is_file($layoutPath) ? (string)file_get_contents($layoutPath) : '';
$sw = is_file($swPath) ? (string)file_get_contents($swPath) : '';

$results[] = p119bfixf_cache_pass('DirectoryIndex index.html first', str_contains($htaccess, 'DirectoryIndex index.html index.php'));
$results[] = p119bfixf_cache_pass('.htaccess no-cache for index files', str_contains($htaccess, 'index\\.html|index\\.php') && str_contains($htaccess, 'no-store'));

$oldMarkers = [
    'v1mc_render_head', 'moghare360-v1-master-console-helper', 'SQL Server SaaS',
    'Legacy MySQL portal inactive', 'Local Master ERP', 'Master ERP Entry',
];
foreach ($oldMarkers as $m) {
    $results[] = p119bfixf_cache_pass('index.php no old: ' . $m, !str_contains($index, $m));
    $results[] = p119bfixf_cache_pass('index.html no old: ' . $m, !str_contains($html, $m));
}

$results[] = p119bfixf_cache_pass('index.php locked title', str_contains($index, 'ورود بخش مدیریت نرم‌افزار'));
$results[] = p119bfixf_cache_pass('index.php owner-login.php', str_contains($index, 'owner-login.php'));
$results[] = p119bfixf_cache_pass('index.php back to public root', str_contains($index, 'بازگشت به صفحه اصلی') && str_contains($index, 'href="./"'));
$results[] = p119bfixf_cache_pass('index.php PHP Expires header', str_contains($index, "header('Expires: 0')"));
$results[] = p119bfixf_cache_pass('index.php no-cache meta in locked view', str_contains($index, 'no-cache, no-store, must-revalidate'));
$results[] = p119bfixf_cache_pass('index.php SW unregister script', str_contains($index, 'unregister'));
$results[] = p119bfixf_cache_pass('index.html no-cache meta', str_contains($html, 'no-cache, no-store, must-revalidate'));
$results[] = p119bfixf_cache_pass('index.html SW unregister script', str_contains($html, 'unregister'));
$results[] = p119bfixf_cache_pass('mirror-layout no SW register', !str_contains($layout, 'serviceWorker.register'));
$results[] = p119bfixf_cache_pass('mirror-layout SW unregister', str_contains($layout, 'unregister'));
$results[] = p119bfixf_cache_pass('mirror-layout cache headers', str_contains($layout, "header('Expires: 0')"));
$results[] = p119bfixf_cache_pass('mirror-layout home uses ./', str_contains($layout, "'index' => ['./', 'خانه']"));
$results[] = p119bfixf_cache_pass('asset version fix-f', preg_match('/fix-f-v\d+/', $layout) === 1);

$results[] = p119bfixf_cache_pass('service worker reported (caches index.php)', str_contains($sw, 'index.php'));

$checkedPages = [
    'staff-login.php', 'owner-login.php', 'customer-request.php',
    'user-access-request.php', 'customer-login.php', 'customer-profile.php', 'mirror-layout.php',
];
foreach ($checkedPages as $name) {
    $path = $name === 'mirror-layout.php' ? $layoutPath : $pub . '/' . $name;
    if (!is_file($path)) {
        continue;
    }
    $content = (string)file_get_contents($path);
    $results[] = p119bfixf_cache_pass(
        $name . ' no public home to index.php',
        !preg_match('/href=[\'"]index\.php[\'"]/u', $content)
    );
}

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-F Index Cache Consistency Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
