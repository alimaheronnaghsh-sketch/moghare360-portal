<?php
declare(strict_types=1);

/**
 * MOGHARE360 — Mirror Site Package test
 */

$root = dirname(__DIR__);
$zipRepo = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-mirror-site-package.zip';
$zipWeb = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-mirror-site-package.zip';
$zipDl = getenv('USERPROFILE') . DIRECTORY_SEPARATOR . 'Downloads' . DIRECTORY_SEPARATOR . 'moghare360-mirror-site-package.zip';
$srcDir = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-mirror-site-package';

$forbiddenPatterns = [
    '/(^|\/)private\//i',
    '/(^|\/)uploads\//i',
    '/mirror-config\.php$/i',
    '/(^|\/)config\.php$/i',
];

function mirror_zip_has(string $zipPath, string $needle): bool
{
    if (!class_exists('ZipArchive') || !is_file($zipPath)) {
        return false;
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return false;
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
        if (str_contains(str_replace('\\', '/', (string)$zip->getNameIndex($i)), $needle)) {
            $zip->close();
            return true;
        }
    }
    $zip->close();
    return false;
}

function mirror_zip_forbidden(string $zipPath, array $patterns): bool
{
    if (!class_exists('ZipArchive') || !is_file($zipPath)) {
        return false;
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return false;
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = str_replace('\\', '/', (string)$zip->getNameIndex($i));
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $name)) {
                $zip->close();
                return true;
            }
        }
    }
    $zip->close();
    return false;
}

$results = [];
$results[] = ['name' => 'Mirror ZIP in release/', 'pass' => is_file($zipRepo)];
$results[] = ['name' => 'Mirror ZIP in public_html/release/', 'pass' => is_file($zipWeb)];
$results[] = ['name' => 'Mirror ZIP in Downloads/', 'pass' => is_file($zipDl)];

$required = [
    'public_html/index.php',
    'public_html/customer-request.php',
    'public_html/manifest.webmanifest',
    'public_html/service-worker.js',
    'public_html/includes/mirror-api-client.php',
    'public_html/mirror-config.example.php',
    'docs/README_MIRROR_INSTALL_FA.md',
];
foreach ($required as $item) {
    $results[] = [
        'name' => 'ZIP contains ' . $item,
        'pass' => mirror_zip_has($zipRepo, str_replace('/', DIRECTORY_SEPARATOR, $item)),
    ];
}

$results[] = ['name' => 'No forbidden files in Mirror ZIP', 'pass' => is_file($zipRepo) && !mirror_zip_forbidden($zipRepo, $forbiddenPatterns)];

$boundaryDoc = $srcDir . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'MIRROR_SECURITY_BOUNDARY.md';
$boundary = is_file($boundaryDoc) ? (string)file_get_contents($boundaryDoc) : '';
$results[] = [
    'name' => 'No Cloud / No Host DB documented',
    'pass' => str_contains($boundary, 'No database on host') && str_contains($boundary, 'No cloud storage'),
];

$client = $srcDir . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';
$results[] = [
    'name' => 'mirror-api-client.php syntax',
    'pass' => is_file($client) && shell_exec('php -l ' . escapeshellarg($client) . ' 2>&1') !== null
        && str_contains((string)shell_exec('php -l ' . escapeshellarg($client) . ' 2>&1'), 'No syntax errors'),
];

$passed = $failed = 0;
foreach ($results as $row) {
    echo ($row['pass'] ? 'PASS' : 'FAIL') . ' — ' . $row['name'] . PHP_EOL;
    $row['pass'] ? $passed++ : $failed++;
}
echo PHP_EOL . 'Passed: ' . $passed . ' / ' . count($results) . PHP_EOL;

if ($failed > 0) {
    fwrite(STDERR, 'MIRROR SITE PACKAGE TEST FAILED' . PHP_EOL);
    exit(1);
}
echo 'MIRROR SITE PACKAGE TEST PASSED' . PHP_EOL;
exit(0);
