<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Public site / mirror UX cleanup verification.
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$mirror = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-mirror-site-package' . DIRECTORY_SEPARATOR . 'public_html';
$apiPath = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'request.php';

function ux_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function ux_read(string $path): string
{
    return is_file($path) ? (string)file_get_contents($path) : '';
}

$results = [];

$publicPages = [
    'index.php',
    'customer-request.php',
    'staff-login.php',
    'owner-login.php',
    'company-owner-dashboard.php',
    'user-access-request.php',
    'mirror-health.php',
    'includes/mirror-layout.php',
];

$forbiddenPatterns = [
    'رابط آینه',
    'Mirror Interface Only',
    'No Host Database',
    'No Cloud Storage',
    'Master Laptop Server',
    'Master Server',
    'mirror only',
    'cpanel',
    'SQL Server',
    'internal API',
    'بدون ذخیره روی هاست',
    'بدون دیتابیس هاست',
    'Mirror Auth',
    'Owner Only',
];

foreach ($publicPages as $rel) {
    $path = $mirror . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $content = ux_read($path);
    $results[] = ux_pass('File exists: ' . $rel, is_file($path));
    if ($content !== '') {
        exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        $results[] = ux_pass('PHP lint: ' . basename($rel), $code === 0);
    }
}

$combinedPublic = '';
foreach (['index.php', 'customer-request.php', 'staff-login.php', 'owner-login.php', 'includes/mirror-layout.php'] as $rel) {
    $combinedPublic .= ux_read($mirror . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel));
}
foreach ($forbiddenPatterns as $pattern) {
    $results[] = ux_pass('No forbidden text: ' . $pattern, !str_contains($combinedPublic, $pattern));
}

$index = ux_read($mirror . DIRECTORY_SEPARATOR . 'index.php');
$results[] = ux_pass('Index has customer entry', str_contains($index, 'customer-request.php') && str_contains($index, 'مشتری'));
$results[] = ux_pass('Index has staff entry', str_contains($index, 'staff-login.php') && str_contains($index, 'پرسنل'));
$results[] = ux_pass('Index no owner main card', !preg_match('/href="owner-login\.php"[^>]*>\s*<h3>مالک/u', $index));
$results[] = ux_pass('Index no company owner main card', !str_contains($index, 'company-owner-dashboard.php'));
$results[] = ux_pass('Index subtle management link', str_contains($index, 'ورود مدیریتی'));

$customer = ux_read($mirror . DIRECTORY_SEPARATOR . 'customer-request.php');
$results[] = ux_pass('Customer form has province', str_contains($customer, 'id="province"'));
$results[] = ux_pass('Customer form has city', str_contains($customer, 'id="city"'));
$results[] = ux_pass('Customer form loads province JS', str_contains($customer, 'iran-provinces-cities.js'));
$results[] = ux_pass('Customer form jalali visit date', str_contains($customer, 'visit_date_grid') && str_contains($customer, 'visit_date'));
$results[] = ux_pass('Customer form diagnostic time hint', str_contains($customer, 'visit_time_hint') && str_contains($customer, '8:30'));
$results[] = ux_pass('Customer form vehicle_brand select', str_contains($customer, 'id="vehicle_brand"'));
$results[] = ux_pass('Customer form vehicle_year_pair', str_contains($customer, 'vehicle_year_pair'));
$results[] = ux_pass('Customer form vehicle_class dependent', str_contains($customer, 'vehicle-brand-classes.js'));
$results[] = ux_pass('Customer form plate widget', str_contains($customer, 'm360-plate-widget') && str_contains($customer, 'plate_letter'));
$results[] = ux_pass('Customer form submits to self API path', str_contains($customer, 'mirror_api_customer_request'));
$results[] = ux_pass('Customer form no Master submit text', !str_contains($customer, 'ارسال به Master'));

$brands = ['بنز', 'ب ام و', 'پورشه', 'ولوو', 'فولکس واگن', 'سایر'];
$brandJs = ux_read($mirror . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'vehicle-brand-classes.js');
foreach ($brands as $brand) {
    $results[] = ux_pass('Brand allowed: ' . $brand, str_contains($brandJs, '"' . $brand . '"'));
}

$requestLabels = ['کارشناسی و عیب‌یابی', 'کارشناسی خرید/فروش', 'سرویس‌های دوره‌ای', 'افزودن آپشن', 'سایر'];
foreach ($requestLabels as $label) {
    $results[] = ux_pass('Request type option: ' . $label, str_contains($customer, $label));
}

$staff = ux_read($mirror . DIRECTORY_SEPARATOR . 'staff-login.php');
$results[] = ux_pass('Staff login has password field', str_contains($staff, 'type="password"'));
$results[] = ux_pass('Staff login OTP note when disabled', str_contains($staff, 'ورود با رمز فعال است'));
$results[] = ux_pass('Staff login management link', str_contains($staff, 'ورود مدیریتی'));

$owner = ux_read($mirror . DIRECTORY_SEPARATOR . 'owner-login.php');
$results[] = ux_pass('Owner login subtle title', str_contains($owner, 'ورود مدیریت'));
$results[] = ux_pass('Owner login no flashy owner role text', !str_contains($owner, 'مالک سیستم') && !str_contains($owner, 'مالک کمپانی'));

$api = ux_read($apiPath);
foreach (['province', 'city', 'vehicle_brand', 'vehicle_year_pair', 'visit_date', 'plate_display', 'request_payload_json', 'plate_parts'] as $needle) {
    $results[] = ux_pass('API handles: ' . $needle, str_contains($api, $needle) || ($needle === 'plate_parts' && str_contains($api, 'plate_parts')));
}

$results[] = ux_pass('No public_html/config.php created', !is_file($root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'config.php'));

$jsAssets = [
    'assets/js/iran-provinces-cities.js',
    'assets/js/vehicle-brand-classes.js',
    'assets/js/customer-form.js',
];
foreach ($jsAssets as $rel) {
    $results[] = ux_pass('JS asset: ' . basename($rel), is_file($mirror . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel)));
}

$zipPath = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-cpanel-mirror-clean.zip';
$results[] = ux_pass('Clean mirror ZIP exists', is_file($zipPath), $zipPath);

if (is_file($zipPath) && class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === true) {
        $bad = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string)$zip->getNameIndex($i);
            $leaf = basename(str_replace('\\', '/', $name));
            if (in_array($leaf, ['config.php', 'erp-config.php', 'mirror-config.php'], true)) {
                $bad = true;
            }
            if (preg_match('#(^|/)(private|docs|runtime|logs|uploads)(/|$)#i', $name)) {
                $bad = true;
            }
        }
        $zip->close();
        $results[] = ux_pass('Clean ZIP no forbidden entries', !$bad);
    }
}

$failed = array_filter($results, static fn(array $r): bool => !$r['pass']);
$passed = count($results) - count($failed);

echo "MOGHARE360 V1 Public Site UX Cleanup Test\n";
echo str_repeat('-', 60) . "\n";
foreach ($results as $r) {
    $mark = $r['pass'] ? 'PASS' : 'FAIL';
    $detail = $r['detail'] !== '' ? ' — ' . $r['detail'] : '';
    echo "[{$mark}] {$r['name']}{$detail}\n";
}
echo str_repeat('-', 60) . "\n";
echo "Result: {$passed}/" . count($results) . " PASS\n";
exit(count($failed) > 0 ? 1 : 0);
