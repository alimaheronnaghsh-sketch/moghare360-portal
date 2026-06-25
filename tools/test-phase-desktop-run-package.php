<?php
declare(strict_types=1);

/**
 * MOGHARE360 — Desktop Run Package test
 */

$root = dirname(__DIR__);
$zipRepo = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-desktop-run-package.zip';
$zipWeb = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-desktop-run-package.zip';
$zipDl = getenv('USERPROFILE') . DIRECTORY_SEPARATOR . 'Downloads' . DIRECTORY_SEPARATOR . 'moghare360-desktop-run-package.zip';

$forbiddenPatterns = [
    '/(^|\/)private\//i',
    '/(^|\/)uploads\//i',
    '/(^|\/)logs\//i',
    '/(^|\/)backups\//i',
    '/(^|\/)\\.git\//i',
    '/(^|\/)config\.php$/i',
    '/(^|\/)mirror-config\.php$/i',
    '/(^|\/)erp-config\.php$/i',
    '/\.bak$/i',
    '/\.log$/i',
];

function test_zip_forbidden(string $zipPath, array $patterns): array
{
    if (!is_file($zipPath)) {
        return ['ok' => false, 'message' => 'ZIP missing: ' . $zipPath];
    }
    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'message' => 'ZipArchive not available'];
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['ok' => false, 'message' => 'Cannot open ZIP'];
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = (string)$zip->getNameIndex($i);
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, str_replace('\\', '/', $name))) {
                $zip->close();
                return ['ok' => false, 'message' => 'Forbidden entry: ' . $name];
            }
        }
    }
    $zip->close();
    return ['ok' => true, 'message' => 'OK'];
}

function test_php_syntax_in_zip(string $zipPath, array $needles): array
{
    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'message' => 'ZipArchive missing'];
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['ok' => false, 'message' => 'Cannot open ZIP'];
    }
    foreach ($needles as $needle) {
        $found = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string)$zip->getNameIndex($i);
            if (!str_contains(str_replace('\\', '/', $name), $needle)) {
                continue;
            }
            $found = true;
            if (!str_ends_with(strtolower($name), '.php')) {
                continue;
            }
            $content = $zip->getFromIndex($i);
            if ($content === false) {
                $zip->close();
                return ['ok' => false, 'message' => 'Cannot read: ' . $name];
            }
            $tmp = tempnam(sys_get_temp_dir(), 'm360');
            if ($tmp === false) {
                continue;
            }
            file_put_contents($tmp, $content);
            exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $code);
            @unlink($tmp);
            if ($code !== 0) {
                $zip->close();
                return ['ok' => false, 'message' => 'Syntax fail: ' . $name];
            }
        }
        if (!$found) {
            $zip->close();
            return ['ok' => false, 'message' => 'Missing in ZIP: ' . $needle];
        }
    }
    $zip->close();
    return ['ok' => true, 'message' => 'OK'];
}

$results = [];

$results[] = ['name' => 'Desktop ZIP in release/', 'pass' => is_file($zipRepo)];
$results[] = ['name' => 'Desktop ZIP in public_html/release/', 'pass' => is_file($zipWeb)];
$results[] = ['name' => 'Desktop ZIP in Downloads/', 'pass' => is_file($zipDl)];

$sec = test_zip_forbidden($zipRepo, $forbiddenPatterns);
$results[] = ['name' => 'Desktop ZIP security inspection', 'pass' => $sec['ok']];

$readmeCheck = false;
if (class_exists('ZipArchive') && is_file($zipRepo)) {
    $zip = new ZipArchive();
    if ($zip->open($zipRepo) === true) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_contains((string)$zip->getNameIndex($i), 'README_RUN_FIRST_FA.md')) {
                $readmeCheck = true;
                break;
            }
        }
        $zip->close();
    }
}
$results[] = ['name' => 'README_RUN_FIRST_FA.md in package', 'pass' => $readmeCheck];

$launcherCheck = false;
if (class_exists('ZipArchive') && is_file($zipRepo)) {
    $zip = new ZipArchive();
    if ($zip->open($zipRepo) === true) {
        $hasBat = $hasPs1 = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $n = (string)$zip->getNameIndex($i);
            if (str_ends_with($n, 'START_MOGHARE360.bat')) {
                $hasBat = true;
            }
            if (str_ends_with($n, 'START_MOGHARE360.ps1')) {
                $hasPs1 = true;
            }
        }
        $launcherCheck = $hasBat && $hasPs1;
        $zip->close();
    }
}
$results[] = ['name' => 'Launchers in package', 'pass' => $launcherCheck];

$manifestDoc = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'MOGHARE360_DESKTOP_RUN_PACKAGE_MANIFEST.md';
$results[] = ['name' => 'Desktop manifest doc exists', 'pass' => is_file($manifestDoc)];

$passed = 0;
$failed = 0;
foreach ($results as $row) {
    echo ($row['pass'] ? 'PASS' : 'FAIL') . ' — ' . $row['name'] . PHP_EOL;
    $row['pass'] ? $passed++ : $failed++;
}
echo PHP_EOL . 'Passed: ' . $passed . ' / ' . count($results) . PHP_EOL;

if ($failed > 0) {
    fwrite(STDERR, 'DESKTOP RUN PACKAGE TEST FAILED' . PHP_EOL);
    exit(1);
}
echo 'DESKTOP RUN PACKAGE TEST PASSED' . PHP_EOL;
exit(0);
