<?php
declare(strict_types=1);
function p360_normalize_search(string $q): string {
    $q = trim($q);
    $q = str_replace(['ي', 'ك', '‌'], ['ی', 'ک', ' '], $q);
    $q = preg_replace('/\s+/u', ' ', $q) ?? $q;
    return mb_strtolower($q, 'UTF-8');
}
function p360_require_fields(array $data, array $fields): ?string {
    foreach ($fields as $f) {
        if (trim((string)($data[$f] ?? '')) === '') return 'فیلد «' . $f . '» الزامی است.';
    }
    return null;
}
function p360_parse_date(?string $d): ?string {
    $d = trim((string)$d);
    if ($d === '') return null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
    return null;
}