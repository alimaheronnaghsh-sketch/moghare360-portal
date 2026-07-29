<?php
declare(strict_types=1);
function p360_money($n): string { return number_format((float)$n, 0, '.', ','); }
function p360_money_parse(?string $s): float {
    $s = str_replace([',', ' '], '', trim((string)$s));
    return is_numeric($s) ? (float)$s : 0.0;
}