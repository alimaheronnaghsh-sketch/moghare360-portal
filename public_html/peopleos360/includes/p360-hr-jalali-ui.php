<?php
declare(strict_types=1);

/**
 * Controlled Jalali date field renderer (no free-form typing, no CDN).
 */

require_once __DIR__ . '/p360-jalali.php';

function p360hr_jalali_assets_once(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $css = '../assets/css/p360-jalali-picker.css';
    $js = '../assets/js/p360-jalali-picker.js';
    // Prefer peopleos360-local assets if mirrored
    $localCss = __DIR__ . '/../../assets/css/p360-jalali-picker.css';
    if (!is_file($localCss)) {
        $css = '../assets/css/p360-jalali-picker.css';
    }
    echo '<link rel="stylesheet" href="' . htmlspecialchars($css, ENT_QUOTES, 'UTF-8') . '">';
    echo '<script src="' . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . '" defer></script>';
}

/**
 * @param array{required?:bool,allow_clear?:bool,disabled?:bool} $opts
 */
function p360hr_jalali_date_field(string $name, ?string $sqlDate, string $labelFa, array $opts = []): void
{
    $required = !empty($opts['required']);
    $allowClear = array_key_exists('allow_clear', $opts) ? (bool)$opts['allow_clear'] : !$required;
    $disabled = !empty($opts['disabled']);
    $jalali = $sqlDate ? p360_sql_date_to_jalali($sqlDate) : '';
    $sql = $sqlDate ? substr((string)$sqlDate, 0, 10) : '';
    echo '<div class="p360-jdate" data-p360-jdate>';
    echo '<label class="p360-jdate-label">' . htmlspecialchars($labelFa, ENT_QUOTES, 'UTF-8');
    if ($required) {
        echo ' <span class="req">*</span>';
    }
    echo '</label>';
    echo '<div class="p360-jdate-row">';
    echo '<input type="text" class="p360-jdate-display" value="' . htmlspecialchars($jalali, ENT_QUOTES, 'UTF-8') . '" readonly '
        . 'placeholder="انتخاب از تقویم" data-jdate-display ' . ($disabled ? 'disabled' : '') . ' ' . ($required ? 'required' : '') . '>';
    echo '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($sql, ENT_QUOTES, 'UTF-8') . '" data-jdate-sql>';
    if (!$disabled) {
        echo '<button type="button" class="m360-btn p360-jdate-open" data-jdate-open>تقویم</button>';
        if ($allowClear) {
            echo '<button type="button" class="m360-btn p360-jdate-clear" data-jdate-clear>پاک</button>';
        }
    }
    echo '</div></div>';
}

function p360hr_parse_posted_jalali_sql(string $field, bool $required = false): array
{
    $raw = trim((string)($_POST[$field] ?? ''));
    if ($raw === '') {
        return $required
            ? ['ok' => false, 'ymd' => null, 'message' => 'انتخاب تاریخ الزامی است.']
            : ['ok' => true, 'ymd' => null, 'message' => ''];
    }
    // Prefer canonical SQL ymd from picker
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            return ['ok' => false, 'ymd' => null, 'message' => 'تاریخ نامعتبر است.'];
        }
        [$jy, $jm, $jd] = p360_gregorian_to_jalali((int)$m[1], (int)$m[2], (int)$m[3]);
        if (!p360_jalali_is_valid($jy, $jm, $jd)) {
            return ['ok' => false, 'ymd' => null, 'message' => 'تاریخ شمسی متناظر نامعتبر است.'];
        }
        return ['ok' => true, 'ymd' => $raw, 'message' => ''];
    }
    $c = p360_jalali_to_sql_date($raw);
    if (!$c['ok']) {
        return ['ok' => false, 'ymd' => null, 'message' => (string)$c['message']];
    }
    return ['ok' => true, 'ymd' => $c['ymd'], 'message' => ''];
}
