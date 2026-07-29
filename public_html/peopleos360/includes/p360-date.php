<?php
declare(strict_types=1);
require_once __DIR__ . '/p360-jalali.php';
function p360_today_gregorian(): string { return gmdate('Y-m-d'); }
function p360_display_date(?string $gregorian): string {
    if ($gregorian === null || $gregorian === '') return '—';
    return p360_gregorian_to_jalali_display($gregorian);
}
function p360_day_before(string $ymd): string {
    $t = strtotime($ymd . ' UTC');
    return gmdate('Y-m-d', $t - 86400);
}