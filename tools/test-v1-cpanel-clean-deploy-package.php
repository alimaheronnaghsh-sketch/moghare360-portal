<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — cPanel clean deploy package verification.
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$zipPath = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-cpanel-public-final-clean.zip';
$baseUrl = 'http://localhost:8080/moghare360';
$stageExtract = $root . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . '_cpanel_public_final_clean_test';
$expectedCacheName = 'moghare360-public-v1-final-clean-20260626';

function ccd_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function ccd_zip_list(string $zipPath): array
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

function ccd_zip_read(string $zipPath, string $entry): string
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
$results[] = ccd_pass('Clean package exists', is_file($zipPath), $zipPath);

$entries = ccd_zip_list($zipPath);
$entrySet = array_fill_keys($entries, true);

$required = [
    'index.php',
    'customer-request.php',
    'staff-login.php',
    'owner-login.php',
    'user-access-request.php',
    'company-owner-dashboard.php',
    'mirror-health.php',
    'includes/mirror-layout.php',
    'includes/mirror-api-client.php',
    'includes/m360-otp-helper.php',
    'assets/css/mirror.css',
    'assets/css/moghare360-v1-luxury-ui.css',
    'assets/js/iran-provinces-cities.js',
    'assets/js/vehicle-brand-classes.js',
    'assets/js/customer-form.js',
    'assets/js/m360-jalali-datepicker.js',
    'mirror-config.example.php',
    'manifest.webmanifest',
    'service-worker.js',
    'api/customer/request.php',
    'api/customer/send-otp.php',
    'api/customer/verify-otp.php',
    'api/customer/profile-status.php',
    'api/mirror/health.php',
];
foreach ($required as $rel) {
    $results[] = ccd_pass('ZIP contains: ' . $rel, isset($entrySet[$rel]));
}

$forbiddenEntries = [
    'mirror-config.php', 'config.php', 'erp-config.php', 'cpanel-public-index.php', 'debug-pending.php',
];
foreach ($forbiddenEntries as $name) {
    $found = false;
    foreach ($entries as $entry) {
        if (basename($entry) === $name) {
            $found = true;
            break;
        }
    }
    $results[] = ccd_pass('ZIP excludes: ' . $name, !$found);
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
    $results[] = ccd_pass('ZIP excludes dir: ' . trim($dir, '/'), !$found);
}

$nestedPublic = false;
$zipInZip = false;
$nestedPublicHtmlPrefix = false;
foreach ($entries as $entry) {
    if (str_contains($entry, 'public_html/public_html')) {
        $nestedPublic = true;
    }
    if (preg_match('/^public_html\//i', $entry)) {
        $nestedPublicHtmlPrefix = true;
    }
    if (preg_match('/\.zip$/i', $entry)) {
        $zipInZip = true;
    }
}
$results[] = ccd_pass('Flat root (no public_html/ prefix)', !$nestedPublicHtmlPrefix);
$results[] = ccd_pass('No public_html/public_html nesting', !$nestedPublic);
$results[] = ccd_pass('No zip inside zip', !$zipInZip);

$syncApiFound = false;
foreach ($entries as $entry) {
    if (str_contains($entry, 'api/sync/')) {
        $syncApiFound = true;
        break;
    }
}
$results[] = ccd_pass('No api/sync debug endpoints in package', !$syncApiFound);

$layout = ccd_zip_read($zipPath, 'includes/mirror-layout.php');
$css = ccd_zip_read($zipPath, 'assets/css/mirror.css') . ccd_zip_read($zipPath, 'assets/css/moghare360-v1-luxury-ui.css');
$sw = ccd_zip_read($zipPath, 'service-worker.js');
$customer = ccd_zip_read($zipPath, 'customer-request.php');
$formJs = ccd_zip_read($zipPath, 'assets/js/customer-form.js');
$jalaliJs = ccd_zip_read($zipPath, 'assets/js/m360-jalali-datepicker.js');
$index = ccd_zip_read($zipPath, 'index.php');
$combined = $layout . $index
    . $customer
    . ccd_zip_read($zipPath, 'staff-login.php')
    . ccd_zip_read($zipPath, 'owner-login.php');

$results[] = ccd_pass('Customer form: server-rendered visit calendar', str_contains($customer, 'm360_server_calendar') && str_contains($customer, 'm360-calendar-day'));
$results[] = ccd_pass('Customer form: birth year/month/day selects', str_contains($customer, 'birth_year_jalali') && str_contains($customer, 'birth_month_jalali') && str_contains($customer, 'birth_day_jalali'));
$results[] = ccd_pass('Customer form: vehicle year jalali/gregorian labels', str_contains($customer, 'شمسی /') && str_contains($customer, 'میلادی'));
$results[] = ccd_pass('Customer form: Iran plate schematic', str_contains($customer, 'iran-plate-widget') && str_contains($customer, 'plate_first_digit_1'));
$results[] = ccd_pass('Customer form CSS: server calendar grid', str_contains($css, 'm360-server-calendar'));
$results[] = ccd_pass('Customer form JS: server calendar click handler', str_contains($formJs, 'initServerVisitCalendar'));
$results[] = ccd_pass('Optional jalali datepicker asset present', $jalaliJs !== '' && str_contains($jalaliJs, 'm360Jalali'));

$results[] = ccd_pass('meta charset UTF-8 in layout', str_contains($layout, 'charset="UTF-8"') || str_contains($layout, "charset='UTF-8'"));
$results[] = ccd_pass('html lang=fa dir=rtl', str_contains($layout, 'lang="fa"') && str_contains($layout, 'dir="rtl"'));
$results[] = ccd_pass('Brand MOGHAREH360 in layout', str_contains($layout, 'MOGHAREH360'));
$results[] = ccd_pass('Brand latin class + dir=ltr + lang=en', str_contains($layout, 'm360-brand-latin') && str_contains($layout, 'dir="ltr"') && str_contains($layout, 'lang="en"'));
$results[] = ccd_pass('Logo max-height CSS', str_contains($css, 'max-height') && (str_contains($css, 'm360-public-logo') || str_contains($css, 'm360-brand-logo')));
$results[] = ccd_pass('Brand unicode-bidi isolate', str_contains($css, 'unicode-bidi: isolate'));
$results[] = ccd_pass('Brand Arial font-family', str_contains($css, 'Arial, Helvetica, sans-serif'));
$results[] = ccd_pass('Service worker cache version bump', str_contains($sw, $expectedCacheName));
$results[] = ccd_pass('Service worker deletes old caches on activate', str_contains($sw, 'caches.delete'));

$forbiddenText = [
    'Master Server', 'Master Laptop', 'No Host Database', 'No Cloud Storage',
    'mirror only', 'SQL Server', 'internal API', 'رابط آینه', 'laptop', 'cPanel',
];
foreach ($forbiddenText as $text) {
    $results[] = ccd_pass('No forbidden UI text: ' . $text, !str_contains($combined, $text));
}

$lintTargets = [
    'index.php',
    'customer-request.php',
    'staff-login.php',
    'owner-login.php',
    'includes/mirror-layout.php',
    'api/customer/request.php',
    'api/mirror/health.php',
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
            $results[] = ccd_pass('PHP lint file exists: ' . $rel, false);
            continue;
        }
        exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        $results[] = ccd_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
        $out = [];
    }
}

$httpPages = ['customer-request.php', 'staff-login.php', 'owner-login.php'];
foreach ($httpPages as $page) {
    $url = $baseUrl . '/' . $page;
    $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int)$m[1];
    }
    $results[] = ccd_pass('HTTP 200: ' . $page, $status === 200 && $body !== false, 'status=' . $status);
}

$passed = 0;
$failed = 0;
echo "# MOGHARE360 V1 cPanel Clean Deploy Package Test\n\n";
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
