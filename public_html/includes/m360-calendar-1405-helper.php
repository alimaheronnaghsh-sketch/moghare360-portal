<?php
declare(strict_types=1);

/** MOGHARE360 — Iran calendar 1405 source (locked) for 30-day visit window. */

const M360_CALENDAR_1405_SOURCE_DOC = 'docs/source/calendar/iran_calendar_1405_source.xlsx';
const M360_RW_CALENDAR_OFFICIAL_HOLIDAY_MARKER = 'تعطیل';

function m360_rw_calendar_1405_embed_b64(): string
{
    return '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';
}

/** @return list<array{g:string,j:string,w:string,hol:string,fri:bool}> */
function m360_rw_calendar_1405_rows(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $raw = @gzdecode((string)base64_decode(m360_rw_calendar_1405_embed_b64(), true));
    if (!is_string($raw) || $raw === '') {
        $cache = [];

        return $cache;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $cache = [];

        return $cache;
    }
    $rows = [];
    foreach ($decoded as $item) {
        if (!is_array($item) || count($item) < 5) {
            continue;
        }
        $hol = trim((string)($item[3] ?? ''));
        $rows[] = [
            'g' => (string)($item[0] ?? ''),
            'j' => (string)($item[1] ?? ''),
            'w' => (string)($item[2] ?? ''),
            'hol' => $hol,
            'fri' => !empty($item[4]),
        ];
    }
    $cache = $rows;

    return $cache;
}

function m360_rw_calendar_day_is_official_holiday(array $row): bool
{
    $hol = trim((string)($row['hol'] ?? ''));
    if ($hol === '34') {
        return false;
    }

    return $hol === M360_RW_CALENDAR_OFFICIAL_HOLIDAY_MARKER;
}

function m360_rw_calendar_day_is_working(array $row): bool
{
    if (!empty($row['fri'])) {
        return false;
    }
    if (m360_rw_calendar_day_is_official_holiday($row)) {
        return false;
    }

    return trim((string)($row['g'] ?? '')) !== '';
}

/** @return array<string, array{g:string,j:string,w:string,hol:string,fri:bool}> */
function m360_rw_calendar_rows_by_gregorian_index(): array
{
    static $index = null;
    if (is_array($index)) {
        return $index;
    }
    $index = [];
    foreach (m360_rw_calendar_1405_rows() as $row) {
        $g = (string)($row['g'] ?? '');
        if ($g !== '') {
            $index[$g] = $row;
        }
    }

    return $index;
}

/**
 * Next 30 calendar days in the Jalali window; only working days are selectable.
 *
 * @return list<array{gregorian:string,jalali:string,label:string,weekday:string,day:int,month:string,is_today:bool,is_selectable:bool,disable_reason:string}>
 */
function m360_rw_calendar_next_30_day_window(?string $fromGregorian = null): array
{
    $tz = new DateTimeZone('Asia/Tehran');
    $from = ($fromGregorian !== null && $fromGregorian !== '')
        ? new DateTimeImmutable($fromGregorian, $tz)
        : new DateTimeImmutable('today', $tz);
    $fromIso = $from->format('Y-m-d');
    $index = m360_rw_calendar_rows_by_gregorian_index();
    $monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    $weekdayMap = [0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه', 6 => 'شنبه'];
    $out = [];
    for ($offset = 0; $offset < 30; $offset++) {
        $gDay = $from->modify('+' . $offset . ' days');
        $g = $gDay->format('Y-m-d');
        $row = $index[$g] ?? null;
        if ($row !== null) {
            $weekday = trim((string)($row['w'] ?? ''));
            $jalali = str_replace('-', '/', (string)($row['j'] ?? ''));
            $jParts = explode('/', str_replace('-', '/', (string)($row['j'] ?? '')));
            $jd = count($jParts) >= 3 ? (int)$jParts[2] : 0;
            $jm = count($jParts) >= 2 ? max(1, min(12, (int)$jParts[1])) : 1;
            $isFriday = !empty($row['fri']);
            $isHoliday = m360_rw_calendar_day_is_official_holiday($row);
        } else {
            $jalali = $g;
            $weekday = $weekdayMap[(int)$gDay->format('w')] ?? '';
            $jd = (int)$gDay->format('j');
            $jm = (int)$gDay->format('n');
            $isFriday = $weekday === 'جمعه';
            $isHoliday = false;
        }
        $disableReason = $isFriday ? 'جمعه' : ($isHoliday ? 'تعطیل رسمی' : '');
        $out[] = [
            'gregorian' => $g,
            'jalali' => $jalali,
            'label' => $weekday . ' ' . $jalali,
            'weekday' => $weekday,
            'day' => $jd,
            'month' => $monthNames[$jm - 1] ?? '',
            'is_today' => $g === $fromIso,
            'is_selectable' => !$isFriday && !$isHoliday,
            'disable_reason' => $disableReason,
        ];
    }

    return $out;
}

/** @return array{ok:bool,error:string} */
function m360_rw_calendar_validate_visit_date(string $gregorian): array
{
    $gregorian = trim($gregorian);
    if ($gregorian === '') {
        return ['ok' => false, 'error' => 'تاریخ مراجعه باید از تقویم انتخاب شود.'];
    }
    foreach (m360_rw_calendar_next_30_day_window() as $day) {
        if ($day['gregorian'] !== $gregorian) {
            continue;
        }
        if (empty($day['is_selectable'])) {
            $reason = trim((string)($day['disable_reason'] ?? ''));

            return ['ok' => false, 'error' => 'تاریخ انتخاب‌شده (' . ($reason !== '' ? $reason : 'غیرفعال') . ') قابل انتخاب نیست.'];
        }

        return ['ok' => true, 'error' => ''];
    }

    return ['ok' => false, 'error' => 'تاریخ مراجعه خارج از بازه ۳۰ روز آینده است.'];
}

/**
 * @param array{gregorian:string,jalali:string,label:string,weekday:string,day:int,month:string,is_today:bool,is_selectable:bool,disable_reason:string} $day
 */
function m360_rw_calendar_render_day_button(array $day, string $selectedGregorian = '', callable $escape = null): void
{
    $h = $escape ?? static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $btnClass = 'm360-calendar-day';
    if (!empty($day['is_today'])) {
        $btnClass .= ' m360-calendar-day--today';
    }
    if ($selectedGregorian !== '' && $selectedGregorian === ($day['gregorian'] ?? '')) {
        $btnClass .= ' m360-calendar-day--selected';
    }
    if (empty($day['is_selectable'])) {
        $btnClass .= ' m360-calendar-day--disabled';
    }
    $reason = trim((string)($day['disable_reason'] ?? ''));
    $disabledAttr = empty($day['is_selectable']) ? ' disabled aria-disabled="true"' : '';
    $reasonAttr = $reason !== '' ? ' title="' . $h($reason) . '" aria-label="' . $h(($day['label'] ?? '') . ' — ' . $reason) . '"' : ' aria-label="' . $h((string)($day['label'] ?? '')) . '"';
    echo '<button type="button" class="' . $h($btnClass) . '"' . $disabledAttr . ' data-gregorian="' . $h((string)($day['gregorian'] ?? '')) . '" data-jalali="' . $h((string)($day['jalali'] ?? '')) . '" data-label="' . $h((string)($day['label'] ?? '')) . '" data-selectable="' . (!empty($day['is_selectable']) ? '1' : '0') . '" data-disabled-reason="' . $h($reason) . '"' . $reasonAttr . '>';
    echo '<span class="m360-calendar-day__weekday">' . $h((string)($day['weekday'] ?? '')) . '</span>';
    echo '<strong class="m360-calendar-day__num">' . (int)($day['day'] ?? 0) . '</strong>';
    echo '<small class="m360-calendar-day__month">' . $h((string)($day['month'] ?? '')) . '</small>';
    if ($reason !== '') {
        echo '<small class="m360-calendar-day__reason">' . $h($reason) . '</small>';
    }
    echo '</button>';
}

/** @return list<array{gregorian:string,jalali:string,label:string,weekday:string,day:int,month:string,is_today:bool,is_selectable:bool,disable_reason:string}> */
function m360_rw_calendar_working_days_window(int $count = 30, ?string $fromGregorian = null): array
{
    $selectable = array_values(array_filter(
        m360_rw_calendar_next_30_day_window($fromGregorian),
        static fn(array $day): bool => !empty($day['is_selectable'])
    ));

    return array_slice($selectable, 0, $count);
}
