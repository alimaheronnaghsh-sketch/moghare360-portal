<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-calendar-1405-helper.php';

function pr02a_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$helper = (string)file_get_contents($root . '/public_html/includes/m360-calendar-1405-helper.php');
$customerPhp = (string)file_get_contents($root . '/public_html/customer-request.php');
$window = m360_rw_calendar_next_30_day_window();

$results = [];
$results[] = pr02a_pass('calendar source doc constant', str_contains($helper, 'iran_calendar_1405_source.xlsx'));
$results[] = pr02a_pass('next_30_day_window returns 30 calendar days', count($window) === 30);
$results[] = pr02a_pass('official holiday marker تعطیل', str_contains($helper, "M360_RW_CALENDAR_OFFICIAL_HOLIDAY_MARKER = 'تعطیل'"));
$results[] = pr02a_pass('marker 34 ignored as holiday', str_contains($helper, "if (\$hol === '34')"));
$results[] = pr02a_pass('customer uses next_30_day_window', str_contains($customerPhp, 'm360_rw_calendar_next_30_day_window'));
$results[] = pr02a_pass('customer validates disabled dates server-side', str_contains($customerPhp, 'm360_rw_calendar_validate_visit_date'));
$results[] = pr02a_pass('disabled day render helper exists', function_exists('m360_rw_calendar_render_day_button'));
$results[] = pr02a_pass('disabled styling class present', str_contains($helper, 'm360-calendar-day--disabled'));

$disabledFriday = 0;
$disabledHoliday = 0;
$selectable = 0;
foreach ($window as $day) {
    if (!empty($day['is_selectable'])) {
        $selectable++;
        continue;
    }
    if (($day['disable_reason'] ?? '') === 'جمعه') {
        $disabledFriday++;
    }
    if (($day['disable_reason'] ?? '') === 'تعطیل رسمی') {
        $disabledHoliday++;
    }
}
$results[] = pr02a_pass('window includes disabled and selectable days', $selectable > 0 && ($disabledFriday + $disabledHoliday) > 0);
$results[] = pr02a_pass('selectable days exclude fridays', array_reduce($window, static function (bool $ok, array $day): bool {
    if (empty($day['is_selectable']) && ($day['disable_reason'] ?? '') === 'جمعه') {
        return $ok;
    }
    if (!empty($day['is_selectable'])) {
        return $ok && !str_contains((string)$day['weekday'], 'جمعه');
    }
    return $ok;
}, true));

$rows = m360_rw_calendar_1405_rows();
$marker34Holiday = 0;
foreach ($rows as $row) {
    if (trim((string)($row['hol'] ?? '')) === '34' && m360_rw_calendar_day_is_official_holiday($row)) {
        $marker34Holiday++;
    }
}
$results[] = pr02a_pass('marker 34 never treated as holiday', $marker34Holiday === 0);

if ($disabledFriday > 0) {
    foreach ($window as $day) {
        if (($day['disable_reason'] ?? '') === 'جمعه') {
            $reject = m360_rw_calendar_validate_visit_date((string)$day['gregorian']);
            $results[] = pr02a_pass('server rejects disabled friday', !$reject['ok']);
            break;
        }
    }
} else {
    $results[] = pr02a_pass('server rejects disabled friday', true, 'no friday in current 30-day slice');
}

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02A jalali calendar window: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
