<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — cPanel public final package verification.
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$zipPath = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-cpanel-public-final.zip';
$baseUrl = 'http://localhost:8080/moghare360';
$stageExtract = $root . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . '_cpanel_public_final_test';

function cp_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function cp_zip_list(string $zipPath): array
{
    $list = [];
    if (!is_file($zipPath)) {
        return $list;
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return $list;
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (is_array($stat) && isset($stat['name'])) {
            $list[] = str_replace('\\', '/', (string)$stat['name']);
        }
    }
    $zip->close();
    return $list;
}

function cp_zip_read(string $zipPath, string $entry): string
{
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return '';
    }
    $content = (string)$zip->getFromName($entry);
    if ($content === '') {
        $alt = str_replace('/', '\\', $entry);
        $content = (string)$zip->getFromName($alt);
    }
    $zip->close();
    return $content;
}

$results = [];
$results[] = cp_pass('Package exists', is_file($zipPath), $zipPath);

$entries = cp_zip_list($zipPath);
$entrySet = array_fill_keys($entries, true);

$required = [
    'index.php',
    'customer-request.php',
    'staff-login.php',
    'owner-login.php',
    'includes/mirror-layout.php',
    'assets/css/mirror.css',
    'assets/css/moghare360-v1-luxury-ui.css',
    'assets/js/customer-form.js',
    'mirror-config.example.php',
    'manifest.webmanifest',
    'service-worker.js',
    'api/customer/request.php',
    'api/mirror/health.php',
];
foreach ($required as $rel) {
    $results[] = cp_pass('ZIP contains: ' . $rel, isset($entrySet[$rel]));
}

$forbiddenEntries = ['mirror-config.php', 'config.php', 'erp-config.php', 'cpanel-public-index.php'];
foreach ($forbiddenEntries as $name) {
    $found = false;
    foreach ($entries as $entry) {
        if (basename($entry) === $name) {
            $found = true;
            break;
        }
    }
    $results[] = cp_pass('ZIP excludes: ' . $name, !$found);
}

$forbiddenDirs = ['private/', 'runtime/', 'logs/', 'uploads/', 'docs/'];
foreach ($forbiddenDirs as $dir) {
    $found = false;
    foreach ($entries as $entry) {
        if (str_starts_with($entry, $dir) || str_contains($entry, '/' . rtrim($dir, '/') . '/')) {
            $found = true;
            break;
        }
    }
    $results[] = cp_pass('ZIP excludes dir: ' . trim($dir, '/'), !$found);
}

$nestedPublic = false;
$zipInZip = false;
foreach ($entries as $entry) {
    if (str_contains($entry, 'public_html/public_html')) {
        $nestedPublic = true;
    }
    if (preg_match('/\.zip$/i', $entry)) {
        $zipInZip = true;
    }
}
$results[] = cp_pass('No public_html/public_html nesting', !$nestedPublic);
$results[] = cp_pass('No zip inside zip', !$zipInZip);

$layout = cp_zip_read($zipPath, 'includes/mirror-layout.php');
$css = cp_zip_read($zipPath, 'assets/css/mirror.css') . cp_zip_read($zipPath, 'assets/css/moghare360-v1-luxury-ui.css');
$index = cp_zip_read($zipPath, 'index.php');
$customer = cp_zip_read($zipPath, 'customer-request.php');
$combined = $layout . $index . $customer . cp_zip_read($zipPath, 'staff-login.php') . cp_zip_read($zipPath, 'owner-login.php');

$results[] = cp_pass('meta charset UTF-8 in layout', str_contains($layout, 'charset="UTF-8"') || str_contains($layout, "charset='UTF-8'"));
$results[] = cp_pass('Brand MOGHAREH360 in layout', str_contains($layout, 'MOGHAREH360'));
$results[] = cp_pass('Brand latin dir=ltr', str_contains($layout, 'm360-brand-latin') && str_contains($layout, 'dir="ltr"'));
$results[] = cp_pass('Logo max-height CSS', str_contains($css, 'max-height') && (str_contains($css, 'm360-public-brand__logo') || str_contains($css, 'm360-public-logo')));
$results[] = cp_pass('Brand unicode-bidi isolate', str_contains($css, 'unicode-bidi: isolate'));

$forbiddenText = [
    'Master Server', 'Master Laptop', 'No Host Database', 'No Cloud Storage',
    'mirror only', 'SQL Server', 'internal API', 'رابط آینه',
];
foreach ($forbiddenText as $text) {
    $results[] = cp_pass('No forbidden UI text: ' . $text, !str_contains($combined, $text));
}

$lintTargets = [
    'index.php',
    'customer-request.php',
    'staff-login.php',
    'owner-login.php',
    'includes/mirror-layout.php',
];
if (is_file($zipPath) && $entries !== []) {
    if (is_dir($stageExtract)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($stageExtract, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($stageExtract);
    }
    if (!is_dir($stageExtract)) {
        mkdir($stageExtract, 0775, true);
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === true) {
        $zip->extractTo($stageExtract);
        $zip->close();
    }
    foreach ($lintTargets as $rel) {
        $path = $stageExtract . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (!is_file($path)) {
            $results[] = cp_pass('PHP lint file exists: ' . $rel, false);
            continue;
        }
        exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        $results[] = cp_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
        $out = [];
    }
}

$httpPages = ['index.php', 'customer-request.php', 'staff-login.php', 'owner-login.php'];
foreach ($httpPages as $page) {
    $url = $baseUrl . '/' . $page;
    $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int)$m[1];
    }
    $results[] = cp_pass('HTTP 200: ' . $page, $status === 200 && $body !== false, 'status=' . $status);
}

$passed = 0;
$failed = 0;
echo "# MOGHARE360 V1 cPanel Public Final Package Test\n\n";
foreach ($results as $r) {
    $mark = $r['pass'] ? 'PASS' : 'FAIL';
    if ($r['pass']) {
        $passed++;
    } else {
        $failed++;
    }
    $detail = $r['detail'] !== '' ? ' — ' . $r['detail'] : '';
    echo sprintf("[%s] %s%s\n", $mark, $r['name'], $detail);
}
echo "\nTotal: " . count($results) . " | PASS: $passed | FAIL: $failed\n";
exit($failed > 0 ? 1 : 0);
