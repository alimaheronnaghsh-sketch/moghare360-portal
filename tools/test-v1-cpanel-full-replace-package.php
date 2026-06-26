<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — cPanel full-replace v2 package verification.
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$zipPath = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-cpanel-public-full-replace-v2.zip';
$baseUrl = 'http://localhost:8080/moghare360';
$stageExtract = $root . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . '_cpanel_public_full_replace_v2_test';
$expectedCacheName = 'moghare360-public-v1-full-replace-v2-20260626';
$assetVersion = 'full-replace-v2';

function cfr_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function cfr_zip_list(string $zipPath): array
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

function cfr_zip_read(string $zipPath, string $entry): string
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
$results[] = cfr_pass('Full-replace v2 package exists', is_file($zipPath), $zipPath);
$results[] = cfr_pass('Deploy checklist doc exists', is_file($root . '/docs/release/MOGHARE360_CPANEL_FULL_REPLACE_DEPLOY_CHECKLIST.md'));
$results[] = cfr_pass('Delete manifest doc exists', is_file($root . '/docs/release/MOGHARE360_CPANEL_OLD_FILES_DELETE_MANIFEST.md'));
$results[] = cfr_pass('Package script exists', is_file($root . '/tools/package-moghare360-cpanel-public-full-replace-v2.ps1'));

$entries = cfr_zip_list($zipPath);
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
    $results[] = cfr_pass('ZIP contains: ' . $rel, isset($entrySet[$rel]));
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
    $results[] = cfr_pass('ZIP excludes: ' . $name, !$found);
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
    $results[] = cfr_pass('ZIP excludes dir: ' . trim($dir, '/'), !$found);
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
$results[] = cfr_pass('Flat root (no public_html/ prefix)', !$nestedPublicHtmlPrefix);
$results[] = cfr_pass('No public_html/public_html nesting', !$nestedPublic);
$results[] = cfr_pass('No zip inside zip', !$zipInZip);

$syncApiFound = false;
foreach ($entries as $entry) {
    if (str_contains($entry, 'api/sync/')) {
        $syncApiFound = true;
        break;
    }
}
$results[] = cfr_pass('No api/sync in package', !$syncApiFound);

$layout = cfr_zip_read($zipPath, 'includes/mirror-layout.php');
$css = cfr_zip_read($zipPath, 'assets/css/mirror.css') . cfr_zip_read($zipPath, 'assets/css/moghare360-v1-luxury-ui.css');
$sw = cfr_zip_read($zipPath, 'service-worker.js');
$customer = cfr_zip_read($zipPath, 'customer-request.php');
$formJs = cfr_zip_read($zipPath, 'assets/js/customer-form.js');
$jalaliJs = cfr_zip_read($zipPath, 'assets/js/m360-jalali-datepicker.js');
$index = cfr_zip_read($zipPath, 'index.php');
$helper = cfr_zip_read($zipPath, 'includes/m360-otp-helper.php');
$sendOtp = cfr_zip_read($zipPath, 'api/customer/send-otp.php');
$verifyOtp = cfr_zip_read($zipPath, 'api/customer/verify-otp.php');
$profileStatus = cfr_zip_read($zipPath, 'api/customer/profile-status.php');
$exampleCfg = cfr_zip_read($zipPath, 'mirror-config.example.php');
$combined = $layout . $index . $customer . cfr_zip_read($zipPath, 'staff-login.php') . cfr_zip_read($zipPath, 'owner-login.php');
$productionUi = $customer . $formJs . $layout;

$results[] = cfr_pass('OTP-first mobile step gated', str_contains($customer, 'm360_step_mobile') && str_contains($customer, 'm360_send_otp'));
$results[] = cfr_pass('OTP verify step present', str_contains($customer, 'm360_step_otp') && str_contains($customer, 'm360_verify_otp'));
$results[] = cfr_pass('Full form hidden before OTP', str_contains($customer, 'm360-step--hidden') && str_contains($customer, 'm360_section_profile'));
$results[] = cfr_pass('customer-form.js OTP-first flow', str_contains($formJs, 'sendOtp') && str_contains($formJs, 'api/customer/send-otp.php'));
$results[] = cfr_pass('profile-status.php OTP gate', str_contains($profileStatus, 'm360_otp_is_verified'));
$results[] = cfr_pass('Dev OTP helper gate', str_contains($helper, 'm360_otp_can_use_dev_code') && str_contains($helper, 'moghareh360.ir'));
$results[] = cfr_pass('OTP dedicated config loader in package', str_contains($helper, 'm360_otp_load_config'));
$results[] = cfr_pass('IPPanel pattern adapter in package', str_contains($helper, 'm360_otp_ippanel_pattern_payload'));
$results[] = cfr_pass('SMS failure messages separated in package', str_contains($helper, 'M360_OTP_MSG_SMS_FAILED'));
$results[] = cfr_pass('Dev code only in get_dev_code', preg_match('/function m360_otp_get_dev_code[\s\S]*return [\'"]123456[\'"];/', $helper) === 1);
$results[] = cfr_pass('No 123456 in production UI sources', !preg_match('/[\'"]123456[\'"]/', $productionUi));
$results[] = cfr_pass('No localhost text in customer HTML', !preg_match('/localhost/i', $customer));
$results[] = cfr_pass('mirror-config.example OTP safe default', preg_match("/M360_OTP_TEST_MODE'\s*=>\s*false/", $exampleCfg) === 1);
$results[] = cfr_pass('send-otp no hardcoded dev code', !preg_match('/123456/', $sendOtp));
$results[] = cfr_pass('verify-otp standard path only', str_contains($verifyOtp, 'm360_otp_verify') && !str_contains($verifyOtp, 'm360_otp_can_use_dev_code'));

$luxuryClasses = ['m360-step-card', 'm360-step-header', 'm360-step-badge', 'm360-otp-panel', 'm360-request-panel', 'm360-profile-panel', 'm360-luxury-action', 'm360-state-success', 'm360-state-error'];
foreach ($luxuryClasses as $cls) {
    $inCustomer = str_contains($customer, $cls);
    $inCss = str_contains($css, $cls);
    $results[] = cfr_pass('Luxury class: ' . $cls, $inCustomer || $inCss);
}

$results[] = cfr_pass('Persian status messages in JS', str_contains($formJs, 'در حال ارسال کد تأیید') && str_contains($formJs, 'setCustomValidity'));
$results[] = cfr_pass('Mobile responsive CSS', str_contains($css, '@media (max-width: 560px)'));
$results[] = cfr_pass('Plate schematic in form', str_contains($customer, 'iran-plate-widget'));
$results[] = cfr_pass('Server visit calendar', str_contains($customer, 'm360_server_calendar'));
$results[] = cfr_pass('Vehicle year jalali/gregorian', str_contains($customer, 'شمسی /') && str_contains($customer, 'میلادی'));
$results[] = cfr_pass('Birthdate selects', str_contains($customer, 'birth_year_jalali'));

$results[] = cfr_pass('Layout CSS cache bust', str_contains($layout, 'mirror_public_asset_version') || str_contains($layout, 'mirror.css?v='));
$results[] = cfr_pass('Layout luxury CSS linked', str_contains($layout, 'moghare360-v1-luxury-ui.css') && str_contains($layout, 'mirror_public_asset_version'));
$results[] = cfr_pass('customer-form.js cache bust', str_contains($customer, 'customer-form.js?v=' . $assetVersion));
$results[] = cfr_pass('Service worker cache bumped', str_contains($sw, $expectedCacheName));
$results[] = cfr_pass('Service worker clears old caches', str_contains($sw, 'caches.delete'));

$forbiddenText = ['Master Server', 'SQL Server', 'cPanel', 'localhost', 'database', 'internal API', 'رابط آینه'];
foreach ($forbiddenText as $text) {
    $results[] = cfr_pass('No forbidden UI text: ' . $text, !str_contains($combined, $text));
}

$credentialPatterns = [
    "/M360_SMS_API_KEY'\s*=>\s*'[^']{12,}/",
    '/Bearer\s+[A-Za-z0-9]{20,}/',
    '/IPPANEL_API_KEY\'\s*=>\s*\'[^\']{8,}/',
];
foreach ($credentialPatterns as $pattern) {
    $results[] = cfr_pass('No credential pattern in ZIP', !preg_match($pattern, $combined . $exampleCfg . $helper));
}

$sqlFound = false;
foreach ($entries as $entry) {
    if (preg_match('/\.sql$/i', $entry)) {
        $sqlFound = true;
        break;
    }
}
$results[] = cfr_pass('No SQL files in package', !$sqlFound);

$lintTargets = [
    'index.php',
    'customer-request.php',
    'includes/m360-otp-helper.php',
    'api/customer/send-otp.php',
    'api/customer/verify-otp.php',
    'api/customer/profile-status.php',
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
            $results[] = cfr_pass('PHP lint exists: ' . $rel, false);
            continue;
        }
        exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        $results[] = cfr_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
        $out = [];
    }
}

$passed = 0;
$failed = 0;
echo "# MOGHARE360 V1 cPanel Full Replace Package Test\n\n";
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
