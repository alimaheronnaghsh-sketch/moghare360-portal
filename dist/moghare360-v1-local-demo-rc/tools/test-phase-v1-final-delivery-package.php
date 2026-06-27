<?php
declare(strict_types=1);

/**
 * MOGHARE360 — V1 Final Delivery Bundle test
 */

$root = dirname(__DIR__);
$zipRepo = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-v1-final-delivery.zip';
$zipWeb = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-v1-final-delivery.zip';
$zipDl = getenv('USERPROFILE') . DIRECTORY_SEPARATOR . 'Downloads' . DIRECTORY_SEPARATOR . 'moghare360-v1-final-delivery.zip';

function bundle_has(string $zipPath, string $needle): bool
{
    if (!class_exists('ZipArchive') || !is_file($zipPath)) {
        return false;
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return false;
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
        if (str_contains((string)$zip->getNameIndex($i), $needle)) {
            $zip->close();
            return true;
        }
    }
    $zip->close();
    return false;
}

$results = [];
$results[] = ['name' => 'Final bundle ZIP in release/', 'pass' => is_file($zipRepo)];
$results[] = ['name' => 'Final bundle ZIP in public_html/release/', 'pass' => is_file($zipWeb)];
$results[] = ['name' => 'Final bundle ZIP in Downloads/', 'pass' => is_file($zipDl)];
$results[] = ['name' => 'Bundle contains desktop ZIP', 'pass' => bundle_has($zipRepo, 'moghare360-desktop-run-package.zip')];
$results[] = ['name' => 'Bundle contains mirror ZIP', 'pass' => bundle_has($zipRepo, 'moghare360-mirror-site-package.zip')];
$results[] = ['name' => 'Bundle contains README_V1_FINAL_FA.md', 'pass' => bundle_has($zipRepo, 'README_V1_FINAL_FA.md')];

$manifest = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'MOGHARE360_V1_FINAL_DELIVERY_MANIFEST.md';
$results[] = ['name' => 'V1 manifest doc exists', 'pass' => is_file($manifest)];

$downloadPage = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'moghare360-release-download.php';
$results[] = ['name' => 'Release download page exists', 'pass' => is_file($downloadPage)];

$passed = $failed = 0;
foreach ($results as $row) {
    echo ($row['pass'] ? 'PASS' : 'FAIL') . ' — ' . $row['name'] . PHP_EOL;
    $row['pass'] ? $passed++ : $failed++;
}
echo PHP_EOL . 'Passed: ' . $passed . ' / ' . count($results) . PHP_EOL;

if ($failed > 0) {
    fwrite(STDERR, 'V1 FINAL DELIVERY PACKAGE TEST FAILED' . PHP_EOL);
    exit(1);
}
echo 'V1 FINAL DELIVERY PACKAGE TEST PASSED' . PHP_EOL;
exit(0);
