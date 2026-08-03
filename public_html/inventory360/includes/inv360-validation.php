<?php
declare(strict_types=1);

function inv360_normalize_search(string $text): string
{
    $text = trim($text);
    $text = str_replace(["\xE2\x80\x8C", "\xC2\xA0"], ' ', $text); // ZWNJ / nbsp
    $map = [
        'ي' => 'ی',
        'ك' => 'ک',
        'ة' => 'ه',
        'أ' => 'ا',
        'إ' => 'ا',
        'آ' => 'ا',
        'ؤ' => 'و',
        'ئ' => 'ی',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return mb_strtolower($text, 'UTF-8');
}

function inv360_require_reason(?string $reason): ?string
{
    $reason = trim((string)$reason);
    return $reason === '' ? 'دلیل الزامی است.' : null;
}

function inv360_positive_qty($qty): bool
{
    return is_numeric($qty) && (float)$qty > 0;
}
