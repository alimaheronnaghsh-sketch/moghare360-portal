<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Public site visual UX hotfix verification.
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . DIRECTORY_SEPARATOR . 'public_html';
$baseUrl = 'http://localhost:8080/moghare360';

function vh_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function vh_read(string $path): string
{
    return is_file($path) ? (string)file_get_contents($path) : '';
}

$results = [];

$customer = vh_read($public . DIRECTORY_SEPARATOR . 'customer-request.php');
$formJs = vh_read($public . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'customer-form.js');
$mirrorCss = vh_read($public . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'mirror.css');
$layout = vh_read($public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php');
$api = vh_read($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'request.php');

$digitFields = [
    'plate_first_digit_1', 'plate_first_digit_2',
    'plate_middle_digit_1', 'plate_middle_digit_2', 'plate_middle_digit_3',
    'plate_region_digit_1', 'plate_region_digit_2',
];
foreach ($digitFields as $field) {
    $results[] = vh_pass('Customer has digit field: ' . $field, str_contains($customer, 'name="' . $field . '"'));
}

$results[] = vh_pass('No 100-999 middle select in customer PHP', !preg_match('/plate_middle_3_digits.*100.*999/s', $customer));
$results[] = vh_pass('No 100-999 middle select in JS', !preg_match('/populateNumericSelect\([^,]+,\s*100,\s*999/', $formJs));
$results[] = vh_pass('plate_display hidden exists', str_contains($customer, 'id="plate_display"'));
$results[] = vh_pass('plate_left_2_digits hidden exists', str_contains($customer, 'id="plate_left_2_digits"'));
$results[] = vh_pass('IR band class exists', str_contains($customer, 'iran-plate-ir-band') && str_contains($mirrorCss, '.iran-plate-ir-band'));
$results[] = vh_pass('Region box class exists', str_contains($customer, 'iran-plate-region-box') && str_contains($mirrorCss, '.iran-plate-region-box'));
$results[] = vh_pass('Plate LTR layout in CSS', str_contains($mirrorCss, 'direction: ltr') && str_contains($mirrorCss, '.iran-plate-widget'));
$results[] = vh_pass('Calendar toolbar for navigation', str_contains($customer, 'visit_cal_toolbar') && str_contains($formJs, 'shiftMonth'));
$results[] = vh_pass('Calendar year navigation', str_contains($formJs, 'shiftYear'));
$results[] = vh_pass('Calendar availability window in JS', str_contains($formJs, 'isSelectable') && str_contains($formJs, 'addJalaliDays'));
$results[] = vh_pass('Booking hint text present', str_contains($customer, 'انتخاب نوبت فقط از فردا تا ۷ روز آینده فعال است.'));
$results[] = vh_pass('Diagnostic time hint preserved', str_contains($customer, '8:30') && str_contains($customer, '11:30'));
$results[] = vh_pass('Public shell header class', str_contains($layout, 'm360-public-shell') && str_contains($layout, 'm360-public-header'));
$results[] = vh_pass('API accepts plate digit fields', str_contains($api, 'plate_first_digit_1'));

$forbiddenPatterns = [
    'Master Server', 'Master Laptop', 'SQL Server', 'mirror only', 'cpanel',
    'No Host Database', 'internal API', 'بدون دیتابیس هاست',
];
$combinedPublic = vh_read($public . DIRECTORY_SEPARATOR . 'customer-request.php')
    . vh_read($public . DIRECTORY_SEPARATOR . 'staff-login.php')
    . vh_read($public . DIRECTORY_SEPARATOR . 'owner-login.php')
    . $layout;
foreach ($forbiddenPatterns as $pattern) {
    $results[] = vh_pass('No forbidden text: ' . $pattern, !str_contains($combinedPublic, $pattern));
}

$lintFiles = [
    'customer-request.php',
    'staff-login.php',
    'owner-login.php',
    'includes/mirror-layout.php',
    'api/customer/request.php',
];
foreach ($lintFiles as $rel) {
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($path)) {
        $results[] = vh_pass('PHP lint file exists: ' . $rel, false, 'missing');
        continue;
    }
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    $results[] = vh_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
    $out = [];
}

$httpPages = [
    'customer-request.php',
    'staff-login.php',
    'owner-login.php',
];
foreach ($httpPages as $page) {
    $url = $baseUrl . '/' . $page;
    $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int)$m[1];
    }
    $results[] = vh_pass('HTTP 200: ' . $page, $status === 200 && $body !== false, 'status=' . $status);
}

$passed = 0;
$failed = 0;
echo "# MOGHARE360 V1 Public Site Visual Hotfix Test\n\n";
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
