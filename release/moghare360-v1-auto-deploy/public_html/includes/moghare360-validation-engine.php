<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Validation Engine Runtime (Wave 1A)
 *
 * Pure PHP helper: no database, session, or auth dependency.
 * UI → Validation Engine → Workflow Engine → Database → Audit Log
 */

/** @var list<string> */
const MOGHARE360_VALIDATION_IRAN_PLATE_LETTERS = [
    'الف', 'ب', 'پ', 'ت', 'ث', 'ج', 'چ', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'ژ',
    'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ک', 'گ', 'ل', 'م',
    'ن', 'و', 'ه', 'ی',
];

/**
 * @return array{ok: bool, errors: list<array{field: string, rule: string, message: string}>, clean: array<string, mixed>}
 */
function moghare360_validation_ok(array $data, array $rules): array
{
    $errors = [];
    $clean = [];

    foreach ($rules as $field => $fieldRules) {
        if (!is_array($fieldRules)) {
            $fieldRules = [$fieldRules];
        }

        $rawValue = $data[$field] ?? null;
        $optional = false;
        $skipField = false;
        $cleanValue = $rawValue;

        foreach ($fieldRules as $rule) {
            if ($rule === 'optional') {
                $optional = true;
                continue;
            }

            if ($skipField) {
                continue;
            }

            if ($optional && moghare360_validation_is_empty($rawValue)) {
                $clean[$field] = moghare360_validation_normalize_scalar($rawValue);
                $skipField = true;
                continue;
            }

            $ruleName = is_array($rule) ? (string)($rule[0] ?? '') : (string)$rule;
            $ruleParam = is_array($rule) ? ($rule[1] ?? null) : null;

            $passed = true;
            $message = moghare360_validation_default_message($ruleName);

            switch ($ruleName) {
                case 'required':
                    $passed = moghare360_validation_required($rawValue);
                    break;
                case 'persian_name':
                    $passed = moghare360_validation_persian_name($rawValue);
                    if ($passed) {
                        $cleanValue = moghare360_validation_clean_persian_name($rawValue);
                    }
                    break;
                case 'mobile_ir':
                    $passed = moghare360_validation_mobile_ir($rawValue);
                    if ($passed) {
                        $cleanValue = moghare360_validation_clean_mobile_ir($rawValue);
                    }
                    break;
                case 'national_id_ir':
                    $passed = moghare360_validation_national_id_ir($rawValue);
                    if ($passed) {
                        $cleanValue = moghare360_validation_clean_national_id_ir($rawValue);
                    }
                    break;
                case 'vin':
                    $passed = moghare360_validation_vin($rawValue);
                    if ($passed) {
                        $cleanValue = moghare360_validation_clean_vin($rawValue);
                    }
                    break;
                case 'iran_plate':
                    $passed = moghare360_validation_iran_plate(is_array($rawValue) ? $rawValue : []);
                    if ($passed) {
                        $cleanValue = moghare360_validation_clean_iran_plate(is_array($rawValue) ? $rawValue : []);
                    }
                    break;
                case 'engine_or_chassis':
                    $passed = moghare360_validation_engine_or_chassis($rawValue);
                    if ($passed) {
                        $cleanValue = moghare360_validation_clean_engine_or_chassis($rawValue);
                    }
                    break;
                case 'positive_number':
                    $passed = moghare360_validation_positive_number($rawValue);
                    if ($passed) {
                        $cleanValue = moghare360_validation_clean_positive_number($rawValue);
                    }
                    break;
                case 'money_amount':
                    $passed = moghare360_validation_money_amount($rawValue);
                    if ($passed) {
                        $cleanValue = moghare360_validation_clean_money_amount($rawValue);
                    }
                    break;
                case 'kilometer':
                    $passed = moghare360_validation_kilometer($rawValue);
                    if ($passed) {
                        $cleanValue = moghare360_validation_clean_kilometer($rawValue);
                    }
                    break;
                case 'safe_text':
                    $maxLength = is_int($ruleParam) ? $ruleParam : 255;
                    $passed = moghare360_validation_safe_text($rawValue, $maxLength);
                    if ($passed) {
                        $cleanValue = moghare360_validation_clean_safe_text($rawValue, $maxLength);
                    }
                    break;
                case 'date_yyyy_mm_dd':
                    $passed = moghare360_validation_date_yyyy_mm_dd($rawValue);
                    if ($passed) {
                        $cleanValue = moghare360_validation_clean_date_yyyy_mm_dd($rawValue);
                    }
                    break;
                case 'allowed_value':
                    $allowed = is_array($ruleParam) ? $ruleParam : [];
                    $passed = moghare360_validation_allowed_value($rawValue, $allowed);
                    if ($passed) {
                        $cleanValue = (string)$rawValue;
                    }
                    break;
                default:
                    $passed = false;
                    $message = 'قانون اعتبارسنجی ناشناخته است.';
                    $ruleName = 'unknown';
                    break;
            }

            if (!$passed) {
                $errors[] = [
                    'field' => (string)$field,
                    'rule' => $ruleName,
                    'message' => $message,
                ];
                $skipField = true;
            }
        }

        if (!$skipField || ($optional && moghare360_validation_is_empty($rawValue))) {
            if (!array_key_exists($field, $clean)) {
                $clean[$field] = $cleanValue;
            }
        }
    }

    return [
        'ok' => $errors === [],
        'errors' => $errors,
        'clean' => $clean,
    ];
}

function moghare360_validation_required(mixed $value): bool
{
    if ($value === null) {
        return false;
    }

    if (is_array($value)) {
        return $value !== [];
    }

    return trim((string)$value) !== '';
}

function moghare360_validation_persian_name(mixed $value): bool
{
    if (!is_string($value) && !is_numeric($value)) {
        return false;
    }

    $text = moghare360_validation_clean_persian_name($value);

    if ($text === '') {
        return false;
    }

    if (preg_match('/[A-Za-z]/u', $text) === 1) {
        return false;
    }

    if (preg_match('/\d/u', $text) === 1) {
        return false;
    }

    if (preg_match('/^[\p{Arabic}\s]+$/u', $text) !== 1) {
        return false;
    }

    $length = mb_strlen($text, 'UTF-8');

    return $length >= 2 && $length <= 100;
}

function moghare360_validation_mobile_ir(mixed $value): bool
{
    $mobile = moghare360_validation_clean_mobile_ir($value);

    if (strlen($mobile) !== 11) {
        return false;
    }

    if (!str_starts_with($mobile, '09')) {
        return false;
    }

    return ctype_digit($mobile);
}

function moghare360_validation_national_id_ir(mixed $value): bool
{
    $nationalId = moghare360_validation_clean_national_id_ir($value);

    if (strlen($nationalId) !== 10 || !ctype_digit($nationalId)) {
        return false;
    }

    if (preg_match('/^(\d)\1{9}$/', $nationalId) === 1) {
        return false;
    }

    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += ((int)$nationalId[$i]) * (10 - $i);
    }

    $remainder = $sum % 11;
    $checkDigit = (int)$nationalId[9];

    if ($remainder < 2) {
        return $checkDigit === $remainder;
    }

    return $checkDigit === (11 - $remainder);
}

function moghare360_validation_vin(mixed $value): bool
{
    $vin = moghare360_validation_clean_vin($value);

    if (strlen($vin) !== 17) {
        return false;
    }

    if (preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin) !== 1) {
        return false;
    }

    return true;
}

/**
 * @param array<string, mixed> $plate
 */
function moghare360_validation_iran_plate(array $plate): bool
{
    $clean = moghare360_validation_clean_iran_plate($plate);

    if ($clean === null) {
        return false;
    }

    return true;
}

function moghare360_validation_engine_or_chassis(mixed $value): bool
{
    $clean = moghare360_validation_clean_engine_or_chassis($value);

    if ($clean === '') {
        return false;
    }

    $length = strlen($clean);

    return $length >= 5 && $length <= 20;
}

function moghare360_validation_positive_number(mixed $value): bool
{
    if ($value === null || $value === '') {
        return false;
    }

    if (is_int($value) || is_float($value)) {
        return $value > 0;
    }

    $normalized = str_replace([',', ' '], '', (string)$value);

    if (!is_numeric($normalized)) {
        return false;
    }

    return (float)$normalized > 0;
}

function moghare360_validation_money_amount(mixed $value): bool
{
    if ($value === null || $value === '') {
        return false;
    }

    $normalized = str_replace([',', ' '], '', (string)$value);

    if (!is_numeric($normalized)) {
        return false;
    }

    if ((float)$normalized < 0) {
        return false;
    }

    return preg_match('/^\d+(\.\d{1,2})?$/', $normalized) === 1;
}

function moghare360_validation_kilometer(mixed $value): bool
{
    if ($value === null || $value === '') {
        return false;
    }

    $normalized = str_replace([',', ' '], '', (string)$value);

    if (!is_numeric($normalized)) {
        return false;
    }

    if ((float)$normalized < 0) {
        return false;
    }

    return (float)$normalized <= 9999999;
}

function moghare360_validation_safe_text(mixed $value, int $maxLength = 255): bool
{
    if (!is_string($value) && !is_numeric($value)) {
        return false;
    }

    $text = moghare360_validation_clean_safe_text($value, $maxLength);

    if ($text === '') {
        return false;
    }

    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $text) === 1) {
        return false;
    }

    return mb_strlen($text, 'UTF-8') <= $maxLength;
}

function moghare360_validation_date_yyyy_mm_dd(mixed $value): bool
{
    $date = moghare360_validation_clean_date_yyyy_mm_dd($value);

    if ($date === '') {
        return false;
    }

    $parts = explode('-', $date);
    if (count($parts) !== 3) {
        return false;
    }

    [$year, $month, $day] = array_map('intval', $parts);

    return checkdate($month, $day, $year);
}

function moghare360_validation_allowed_value(mixed $value, array $allowed): bool
{
    if ($allowed === []) {
        return false;
    }

    return in_array((string)$value, array_map('strval', $allowed), true);
}

function moghare360_validation_is_empty(mixed $value): bool
{
    if ($value === null) {
        return true;
    }

    if (is_array($value)) {
        return $value === [];
    }

    return trim((string)$value) === '';
}

function moghare360_validation_normalize_digits(string $value): string
{
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    $value = str_replace($persian, $latin, $value);
    $value = str_replace($arabic, $latin, $value);

    return $value;
}

function moghare360_validation_normalize_scalar(mixed $value): mixed
{
    if (is_array($value)) {
        return $value;
    }

    return trim((string)$value);
}

function moghare360_validation_clean_persian_name(mixed $value): string
{
    $text = trim((string)$value);
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

    return $text;
}

function moghare360_validation_clean_mobile_ir(mixed $value): string
{
    $mobile = moghare360_validation_normalize_digits(trim((string)$value));
    $mobile = preg_replace('/\D+/', '', $mobile) ?? '';

    if (str_starts_with($mobile, '98') && strlen($mobile) === 12) {
        $mobile = '0' . substr($mobile, 2);
    }

    if (str_starts_with($mobile, '9') && strlen($mobile) === 10) {
        $mobile = '0' . $mobile;
    }

    return $mobile;
}

function moghare360_validation_clean_national_id_ir(mixed $value): string
{
    $digits = moghare360_validation_normalize_digits(trim((string)$value));
    $digits = preg_replace('/\D+/', '', $digits) ?? '';

    return $digits;
}

function moghare360_validation_clean_vin(mixed $value): string
{
    $vin = strtoupper(trim((string)$value));
    $vin = str_replace([' ', '-'], '', $vin);

    return $vin;
}

/**
 * @param array<string, mixed> $plate
 * @return array<string, string>|null
 */
function moghare360_validation_clean_iran_plate(array $plate): ?array
{
    $province = moghare360_validation_normalize_digits(trim((string)($plate['province'] ?? $plate['region'] ?? '')));
    $letter = trim((string)($plate['letter'] ?? ''));
    $number = moghare360_validation_normalize_digits(trim((string)($plate['number'] ?? $plate['three_digit'] ?? '')));
    $series = moghare360_validation_normalize_digits(trim((string)($plate['series'] ?? '')));

    if ($province === '' || $letter === '' || $number === '' || $series === '') {
        return null;
    }

    if (preg_match('/^\d{2}$/', $province) !== 1) {
        return null;
    }

    if (preg_match('/^\d{3}$/', $number) !== 1) {
        return null;
    }

    if (preg_match('/^\d{2}$/', $series) !== 1) {
        return null;
    }

    if (mb_strlen($letter, 'UTF-8') !== 1) {
        return null;
    }

    if (!in_array($letter, MOGHARE360_VALIDATION_IRAN_PLATE_LETTERS, true)) {
        if (preg_match('/^[\p{Arabic}]$/u', $letter) !== 1) {
            return null;
        }
    }

    return [
        'province' => $province,
        'letter' => $letter,
        'number' => $number,
        'series' => $series,
    ];
}

function moghare360_validation_clean_engine_or_chassis(mixed $value): string
{
    $text = strtoupper(trim((string)$value));
    $text = preg_replace('/\s+/', '', $text) ?? '';

    if (preg_match('/^[A-Z0-9\-\/]+$/', $text) !== 1) {
        return '';
    }

    return $text;
}

function moghare360_validation_clean_positive_number(mixed $value): string
{
    $normalized = str_replace([',', ' '], '', (string)$value);

    if (!is_numeric($normalized)) {
        return '';
    }

    $number = (float)$normalized;

    if ($number <= 0) {
        return '';
    }

    if (floor($number) == $number) {
        return (string)(int)$number;
    }

    return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
}

function moghare360_validation_clean_money_amount(mixed $value): string
{
    $normalized = str_replace([',', ' '], '', (string)$value);

    if (!is_numeric($normalized) || (float)$normalized < 0) {
        return '';
    }

    return number_format((float)$normalized, 2, '.', '');
}

function moghare360_validation_clean_kilometer(mixed $value): string
{
    $normalized = str_replace([',', ' '], '', (string)$value);

    if (!is_numeric($normalized) || (float)$normalized < 0) {
        return '';
    }

    if (floor((float)$normalized) == (float)$normalized) {
        return (string)(int)$normalized;
    }

    return rtrim(rtrim(number_format((float)$normalized, 1, '.', ''), '0'), '.');
}

function moghare360_validation_clean_safe_text(mixed $value, int $maxLength = 255): string
{
    $text = trim((string)$value);

    if ($text === '') {
        return '';
    }

    if (mb_strlen($text, 'UTF-8') > $maxLength) {
        $text = mb_substr($text, 0, $maxLength, 'UTF-8');
    }

    return $text;
}

function moghare360_validation_clean_date_yyyy_mm_dd(mixed $value): string
{
    $date = trim((string)$value);

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
        return '';
    }

    return $date;
}

function moghare360_validation_default_message(string $ruleName): string
{
    return match ($ruleName) {
        'required' => 'این فیلد الزامی است.',
        'persian_name' => 'نام باید فارسی و معتبر باشد.',
        'mobile_ir' => 'شماره موبایل نامعتبر است (مثال: 09123456789).',
        'national_id_ir' => 'کد ملی نامعتبر است.',
        'vin' => 'شماره VIN نامعتبر است (۱۷ کاراکتر، بدون I و O و Q).',
        'iran_plate' => 'پلاک خودرو نامعتبر است.',
        'engine_or_chassis' => 'شماره موتور یا شاسی نامعتبر است.',
        'positive_number' => 'مقدار عددی مثبت معتبر وارد کنید.',
        'money_amount' => 'مبلغ نامعتبر است.',
        'kilometer' => 'کارکرد کیلومتر نامعتبر است.',
        'safe_text' => 'متن وارد شده نامعتبر است.',
        'date_yyyy_mm_dd' => 'تاریخ باید به صورت YYYY-MM-DD باشد.',
        'allowed_value' => 'مقدار انتخاب‌شده مجاز نیست.',
        default => 'اعتبارسنجی ناموفق بود.',
    };
}
