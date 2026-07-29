<?php
declare(strict_types=1);

function inv360_money_parse($v): float
{
    if (is_numeric($v)) {
        return (float)$v;
    }
    $s = preg_replace('/[^\d.\-]/', '', str_replace(',', '', (string)$v));
    return (float)$s;
}

function inv360_money_format($v, int $decimals = 0): string
{
    return number_format((float)$v, $decimals, '.', ',');
}

function inv360_money_fa($v, int $decimals = 0): string
{
    return inv360_money_format($v, $decimals);
}
