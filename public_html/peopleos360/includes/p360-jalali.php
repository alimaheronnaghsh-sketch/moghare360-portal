<?php
declare(strict_types=1);

/**
 * Jalali helpers — conversion, leap year, month lengths, contract month-end rule.
 */

function p360_gregorian_to_jalali(int $gy, int $gm, int $gd): array
{
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    $jm = ($days < 186) ? (1 + intdiv($days, 31)) : (7 + intdiv($days - 186, 30));
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    return [$jy, $jm, $jd];
}

function p360_jalali_to_gregorian(int $jy, int $jm, int $jd): array
{
    $jy += 1595;
    $days = -355668 + (365 * $jy) + intdiv($jy, 33) * 8 + intdiv(($jy % 33) + 3, 4) + $jd
        + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30 + 186));
    $gy = 400 * intdiv($days, 146097);
    $days %= 146097;
    if ($days > 36524) {
        $gy += 100 * intdiv(--$days, 36524);
        $days %= 36524;
        if ($days >= 365) {
            $days++;
        }
    }
    $gy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $gy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    $gd = $days + 1;
    $sal_a = [0, 31, (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $gm = 0;
    for ($gm = 1; $gm <= 12 && $gd > $sal_a[$gm]; $gm++) {
        $gd -= $sal_a[$gm];
    }
    return [$gy, $gm, $gd];
}

function p360_gregorian_to_jalali_display(string $ymd): string
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $ymd, $m)) {
        return $ymd;
    }
    [$jy, $jm, $jd] = p360_gregorian_to_jalali((int)$m[1], (int)$m[2], (int)$m[3]);
    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

function p360_jalali_month_name(int $m): string
{
    $names = [1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان', 9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'];
    return $names[$m] ?? (string)$m;
}

/** Jalali leap year — Esfand has 30 days when true. */
function p360_is_jalali_leap(int $jy): bool
{
    $a = (($jy - 474) % 2820 + 2820) % 2820 + 474;
    return (((($a + 38) * 682) % 2816) < 682);
}

function p360_jalali_month_days(int $jy, int $jm): int
{
    if ($jm < 1 || $jm > 12) {
        return 0;
    }
    if ($jm <= 6) {
        return 31;
    }
    if ($jm <= 11) {
        return 30;
    }
    return p360_is_jalali_leap($jy) ? 30 : 29;
}

function p360_jalali_is_valid(int $jy, int $jm, int $jd): bool
{
    if ($jy < 1200 || $jy > 1600 || $jm < 1 || $jm > 12 || $jd < 1) {
        return false;
    }
    return $jd <= p360_jalali_month_days($jy, $jm);
}

/** @return array{ok:bool,ymd?:string,jalali?:string,message?:string} */
function p360_jalali_to_sql_date(string $jalaliSlash): array
{
    $jalaliSlash = trim(str_replace(['-', '.', ' '], '/', $jalaliSlash));
    if (!preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $jalaliSlash, $m)) {
        return ['ok' => false, 'message' => 'قالب تاریخ شمسی نامعتبر است.'];
    }
    $jy = (int)$m[1];
    $jm = (int)$m[2];
    $jd = (int)$m[3];
    if (!p360_jalali_is_valid($jy, $jm, $jd)) {
        return ['ok' => false, 'message' => 'تاریخ شمسی نامعتبر است.'];
    }
    [$gy, $gm, $gd] = p360_jalali_to_gregorian($jy, $jm, $jd);
    return [
        'ok' => true,
        'ymd' => sprintf('%04d-%02d-%02d', $gy, $gm, $gd),
        'jalali' => sprintf('%04d/%02d/%02d', $jy, $jm, $jd),
    ];
}

function p360_sql_date_to_jalali(?string $ymd): string
{
    $ymd = trim((string)$ymd);
    if ($ymd === '') {
        return '';
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $ymd, $m)) {
        return p360_gregorian_to_jalali_display($m[1] . '-' . $m[2] . '-' . $m[3]);
    }
    return $ymd;
}

const M360_CONTRACT_MONTH_END_V1 = 'M360_CONTRACT_MONTH_END_V1';

/**
 * Owner rule: start month counts as month 1; end = last day of final counted month.
 *
 * @return array{ok:bool,end_ymd?:string,end_jalali?:string,duration_text?:string,rule?:string,message?:string}
 */
function p360_contract_end_by_month_duration(string $startYmd, int $durationMonths): array
{
    if ($durationMonths < 1 || $durationMonths > 600) {
        return ['ok' => false, 'message' => 'مدت قرارداد نامعتبر است.'];
    }
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $startYmd, $m)) {
        return ['ok' => false, 'message' => 'تاریخ شروع نامعتبر است.'];
    }
    [$jy, $jm, $jd] = p360_gregorian_to_jalali((int)$m[1], (int)$m[2], (int)$m[3]);
    if (!p360_jalali_is_valid($jy, $jm, $jd)) {
        return ['ok' => false, 'message' => 'تاریخ شروع شمسی نامعتبر است.'];
    }
    $idx = ($jm - 1) + ($durationMonths - 1);
    $finalJy = $jy + intdiv($idx, 12);
    $finalJm = ($idx % 12) + 1;
    $finalJd = p360_jalali_month_days($finalJy, $finalJm);
    [$gy, $gm, $gd] = p360_jalali_to_gregorian($finalJy, $finalJm, $finalJd);
    $endYmd = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    $endJalali = sprintf('%04d/%02d/%02d', $finalJy, $finalJm, $finalJd);
    return [
        'ok' => true,
        'end_ymd' => $endYmd,
        'end_jalali' => $endJalali,
        'duration_text' => p360_duration_text_fa($durationMonths),
        'rule' => M360_CONTRACT_MONTH_END_V1,
    ];
}

function p360_duration_text_fa(int $months): string
{
    $map = [
        1 => 'یک ماه',
        2 => 'دو ماه',
        3 => 'سه ماه',
        6 => 'شش ماه',
        9 => 'نه ماه',
        12 => 'یک سال',
    ];
    if (isset($map[$months])) {
        return $map[$months];
    }
    if ($months % 12 === 0) {
        $y = intdiv($months, 12);
        return $y . ' سال';
    }
    return $months . ' ماه';
}

/** Days remaining until end date (inclusive calendar difference from today local). */
function p360_days_remaining_until(?string $endYmd): ?int
{
    if ($endYmd === null || !preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string)$endYmd, $m)) {
        return null;
    }
    $end = new DateTimeImmutable(sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]));
    $today = new DateTimeImmutable('today');
    return (int)$today->diff($end)->format('%r%a');
}
