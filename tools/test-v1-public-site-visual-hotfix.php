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

/**
 * @return array{jy:int,jm:int,jd:int}
 */
function vh_gregorian_to_jalali(int $gy, int $gm, int $gd): array
{
    $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (int)(355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $gdm[$gm - 1]);
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return ['jy' => $jy, 'jm' => $jm, 'jd' => $jd];
}

$tehranToday = new DateTimeImmutable('today', new DateTimeZone('Asia/Tehran'));
$currentJalaliYear = vh_gregorian_to_jalali(
    (int)$tehranToday->format('Y'),
    (int)$tehranToday->format('n'),
    (int)$tehranToday->format('j')
)['jy'];
$todayGregorianIso = $tehranToday->format('Y-m-d');

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

// --- P0.5C server-rendered visit calendar (replaces legacy JS datepicker assertions) ---
$results[] = vh_pass('Server calendar container in PHP', str_contains($customer, 'm360-server-calendar') && str_contains($customer, 'id="m360_server_calendar"'));
$results[] = vh_pass('Server calendar day buttons in PHP', str_contains($customer, 'm360-calendar-day'));
$results[] = vh_pass('Server calendar today marker in PHP', str_contains($customer, 'm360-calendar-day--today'));
$results[] = vh_pass('Visit date display field exists', str_contains($customer, 'id="visit_date_display"'));
$results[] = vh_pass('Visit date hidden input exists', str_contains($customer, 'id="visit_date"') && str_contains($customer, 'name="visit_date"'));
$results[] = vh_pass('Calendar day data-gregorian attribute', str_contains($customer, 'data-gregorian='));
$results[] = vh_pass('Calendar day data-jalali attribute', str_contains($customer, 'data-jalali='));
$results[] = vh_pass('Calendar day data-label attribute', str_contains($customer, 'data-label='));
$results[] = vh_pass('Booking window hint: today through 30 days', str_contains($customer, 'امروز تا ۳۰ روز آینده'));
$results[] = vh_pass('Visit calendar PHP loop starts at today (offset 0)', preg_match('/\$offset\s*=\s*0;\s*\$offset\s*<=\s*30/', $customer) === 1);
$results[] = vh_pass('Visit calendar PHP loop has no past-day offset', !preg_match('/\$offset\s*=\s*-\d+/', $customer) && !preg_match('/modify\(\s*[\'"]-/', $customer));
$results[] = vh_pass('Server calendar click handler in JS', str_contains($formJs, 'initServerVisitCalendar') && str_contains($formJs, 'm360_server_calendar'));
$results[] = vh_pass('No legacy JS calendar toolbar in PHP', !str_contains($customer, 'visit_cal_toolbar'));
$results[] = vh_pass('No legacy shiftMonth in form JS', !str_contains($formJs, 'shiftMonth'));
$results[] = vh_pass('No legacy isSelectable in form JS', !str_contains($formJs, 'isSelectable'));
$results[] = vh_pass('No legacy booking hint (tomorrow to 7 days)', !str_contains($customer, 'انتخاب نوبت فقط از فردا تا ۷ روز آینده فعال است.'));

// --- Birth date selects ---
$results[] = vh_pass('Birth year select exists', str_contains($customer, 'id="birth_year_jalali"') && str_contains($customer, 'name="birth_year_jalali"'));
$results[] = vh_pass('Birth year includes 1310', preg_match('/\$y\s*>=\s*1310/', $customer) === 1);
$results[] = vh_pass('Birth month select exists', str_contains($customer, 'birth_month_jalali'));
$results[] = vh_pass('Birth day select exists', str_contains($customer, 'birth_day_jalali'));
$jalaliMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
foreach ($jalaliMonths as $monthName) {
    $results[] = vh_pass('Birth month option: ' . $monthName, str_contains($customer, $monthName));
}
$results[] = vh_pass('Birth day 1 through 31 range in PHP', preg_match('/for\s*\(\s*\$d\s*=\s*1;\s*\$d\s*<=\s*31/', $customer) === 1);
$results[] = vh_pass('Birth date hidden field exists', str_contains($customer, 'id="birth_date"') && str_contains($customer, 'name="birth_date"'));

// --- Vehicle year select ---
$results[] = vh_pass('Vehicle year select exists', str_contains($customer, 'id="vehicle_year_pair"') && str_contains($customer, 'name="vehicle_year_pair"'));
$results[] = vh_pass('Vehicle year shows jalali and gregorian', str_contains($customer, 'شمسی /') && str_contains($customer, 'میلادی'));
$results[] = vh_pass('Vehicle year PHP range: 20 years back through today', preg_match('/for\s*\(\s*\$i\s*=\s*0;\s*\$i\s*<=\s*20;\s*\$i\+\+/', $customer) === 1);

// --- Plate schematic (extended) ---
$results[] = vh_pass('Plate schematic container exists', str_contains($customer, 'iran-plate-widget'));
$results[] = vh_pass('Plate IR band text exists', str_contains($customer, 'iran-plate-ir-band__ir') && str_contains($customer, '>IR<'));
$results[] = vh_pass('Plate Iran region label exists', str_contains($customer, 'iran-plate-region-box__label') && str_contains($customer, '>ایران<'));
$results[] = vh_pass('Plate body sections exist', str_contains($customer, 'iran-plate-body') && str_contains($customer, 'iran-plate-group--series') && str_contains($customer, 'iran-plate-group--letter') && str_contains($customer, 'iran-plate-group--middle'));
$results[] = vh_pass('Plate preview element exists', str_contains($customer, 'id="plate_preview"') && str_contains($customer, 'iran-plate-preview'));

// --- CSS for server calendar and plate ---
$results[] = vh_pass('CSS: .m360-server-calendar grid layout', str_contains($mirrorCss, '.m360-server-calendar') && str_contains($mirrorCss, 'display: grid'));
$results[] = vh_pass('CSS: .m360-server-calendar visible rules', str_contains($mirrorCss, 'visibility: visible') && str_contains($mirrorCss, 'opacity: 1'));
$results[] = vh_pass('CSS: .m360-calendar-day styles', str_contains($mirrorCss, '.m360-calendar-day'));
$results[] = vh_pass('CSS: today marker style', str_contains($mirrorCss, '.m360-calendar-day--today'));
$results[] = vh_pass('CSS: selected day style', str_contains($mirrorCss, '.m360-calendar-day--selected'));
$results[] = vh_pass('CSS: birthdate row grid', str_contains($mirrorCss, '.m360-birthdate-row'));
$results[] = vh_pass('CSS: plate preview styles', str_contains($mirrorCss, '.iran-plate-preview'));

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

    if ($page === 'customer-request.php' && $status === 200 && $body !== false) {
        $dayCount = preg_match_all('/<button[^>]*class="[^"]*m360-calendar-day(?:\s|")/', $body, $dayMatches);
        $results[] = vh_pass('Rendered calendar has 31 day buttons (today + 30)', $dayCount === 31, 'count=' . $dayCount);
        $results[] = vh_pass('Rendered calendar marks today', str_contains($body, 'm360-calendar-day--today'));
        $results[] = vh_pass('Rendered birth year includes current jalali year', str_contains($body, '>' . $currentJalaliYear . '<') || str_contains($body, 'value="' . $currentJalaliYear . '"'));
        $results[] = vh_pass('Rendered vehicle year includes current jalali/gregorian pair', str_contains($body, (string)$currentJalaliYear . ' شمسی /'));
        if (preg_match('/data-gregorian="(\d{4}-\d{2}-\d{2})"/', $body, $firstGregorian)) {
            $firstDate = $firstGregorian[1];
            $results[] = vh_pass('Rendered calendar first day is today (no past days)', $firstDate === $todayGregorianIso, 'first=' . $firstDate);
        } else {
            $results[] = vh_pass('Rendered calendar first day is today (no past days)', false, 'no data-gregorian found');
        }
    }
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
