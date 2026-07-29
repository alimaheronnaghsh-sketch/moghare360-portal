<?php
declare(strict_types=1);
function p360_gregorian_to_jalali(int $gy, int $gm, int $gd): array {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) { $jy += intdiv($days - 1, 365); $days = ($days - 1) % 365; }
    $jm = ($days < 186) ? (1 + intdiv($days, 31)) : (7 + intdiv($days - 186, 30));
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    return [$jy, $jm, $jd];
}
function p360_gregorian_to_jalali_display(string $ymd): string {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $m)) return $ymd;
    [$jy, $jm, $jd] = p360_gregorian_to_jalali((int)$m[1], (int)$m[2], (int)$m[3]);
    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}
function p360_jalali_month_name(int $m): string {
    $names = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند'];
    return $names[$m] ?? (string)$m;
}