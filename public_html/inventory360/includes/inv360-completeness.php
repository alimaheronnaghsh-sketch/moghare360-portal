<?php
declare(strict_types=1);

/**
 * I1R2-A completeness evaluation for inv360_items (no fitment/media yet).
 */

function inv360_i1r2a_item_types_new(): array
{
    return [
        'spare_part' => 'قطعه یدکی',
        'consumable' => 'مواد مصرفی',
        'tool' => 'ابزار',
        'equipment' => 'تجهیزات',
        'asset' => 'دارایی',
        'raw_material' => 'مواد اولیه',
        'packaging' => 'بسته‌بندی',
        'other' => 'سایر',
    ];
}

function inv360_i1r2a_market_grades(): array
{
    return [
        'genuine_new' => 'نو اصلی / اورجینال',
        'company_new' => 'نو شرکتی',
        'used_stock' => 'استوک',
    ];
}

/** Persian digits for operator-facing display only. */
function inv360_fa_digits(string $s): string
{
    return strtr($s, [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ]);
}

function inv360_lifecycle_fa(string $code): string
{
    $map = [
        'DRAFT' => 'پیش‌نویس',
        'NEEDS_COMPLETION' => 'نیازمند تکمیل',
        'PENDING_MANAGER_APPROVAL' => 'در انتظار تأیید مدیر',
        'ACTIVE' => 'فعال',
        'REJECTED' => 'ردشده',
        'INACTIVE' => 'غیرفعال',
    ];
    $c = trim($code);

    return $map[$c] ?? ($c !== '' ? $c : '—');
}

function inv360_completeness_fa(string $code): string
{
    $map = [
        'COMPLETE' => 'کامل',
        'NEEDS_COMPLETION' => 'نیازمند تکمیل',
        'FATAL_MISSING_IDENTITY' => 'هویت پایه ناقص',
    ];
    $c = trim($code);

    return $map[$c] ?? ($c !== '' ? $c : '—');
}

/** Operator badge: «هویت پایه ناقص · ۲۰٪» */
function inv360_completeness_badge(?string $status, $score = null): string
{
    $status = trim((string)$status);
    if ($status === '') {
        return '—';
    }
    $label = inv360_completeness_fa($status);
    if ($score === null || $score === '') {
        return $label;
    }

    return $label . ' · ' . inv360_fa_digits((string)(int)$score) . '٪';
}

/**
 * Jalali datetime display — reuses canonical m360_rui_jalali_date.
 * Stored Gregorian DATETIME values are not modified.
 */
function inv360_jalali_datetime(?string $raw): string
{
    static $ready = false;
    if (!$ready) {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'reception-ui-helper.php';
        if (is_file($path)) {
            require_once $path;
        }
        $ready = true;
    }
    if (!function_exists('m360_rui_jalali_date')) {
        return '—';
    }
    $s = m360_rui_jalali_date($raw, true);
    if ($s === '' || $s === '—') {
        return '—';
    }
    $s = str_replace(' - ', '، ', $s);

    return inv360_fa_digits($s);
}

/**
 * @return list<array{code:string,label:string,severity:string}>
 */
function inv360_i1r2a_missing_fields(array $data): array
{
    $missing = [];
    $type = trim((string)($data['item_type'] ?? ''));
    $name = trim((string)($data['item_name_fa'] ?? ''));
    $types = inv360_i1r2a_item_types_new();
    $legacyOk = $type !== '' && !isset($types[$type]); // preserve legacy e.g. finished_good

    if ($type === '') {
        $missing[] = ['code' => 'item_type', 'label' => 'نوع کالا', 'severity' => 'fatal'];
    }
    if ($name === '') {
        $missing[] = ['code' => 'item_name_fa', 'label' => 'نام فارسی کالا', 'severity' => 'fatal'];
    }

    $hasId = false;
    foreach (['workshop_code', 'item_code', 'part_number', 'manufacturer_part_number', 'barcode', 'supplier_code'] as $k) {
        if (trim((string)($data[$k] ?? '')) !== '') {
            $hasId = true;
            break;
        }
    }
    if (!$hasId) {
        $missing[] = ['code' => 'identifier', 'label' => 'حداقل یک شناسه کالا', 'severity' => 'required'];
    }

    if (!array_key_exists('min_stock', $data) || $data['min_stock'] === null || $data['min_stock'] === '') {
        $missing[] = ['code' => 'min_stock', 'label' => 'حداقل موجودی هشدار', 'severity' => 'required'];
    } elseif (!is_numeric($data['min_stock']) || (float)$data['min_stock'] < 0) {
        $missing[] = ['code' => 'min_stock_invalid', 'label' => 'حداقل موجودی هشدار معتبر', 'severity' => 'required'];
    }

    if ($type === 'spare_part') {
        if ((int)($data['category_id'] ?? 0) <= 0) {
            $missing[] = ['code' => 'category_id', 'label' => 'گروه اصلی قطعه', 'severity' => 'required'];
        }
        $mg = trim((string)($data['market_grade'] ?? ''));
        if ($mg === '' || !isset(inv360_i1r2a_market_grades()[$mg])) {
            $missing[] = ['code' => 'market_grade', 'label' => 'رده قطعه', 'severity' => 'required'];
        }
        if ($mg === 'used_stock') {
            if (trim((string)($data['stock_authenticity'] ?? '')) === '') {
                $missing[] = ['code' => 'stock_authenticity', 'label' => 'اصالت استوک', 'severity' => 'required'];
            }
            if (trim((string)($data['quality_grade'] ?? '')) === '') {
                $missing[] = ['code' => 'quality_grade', 'label' => 'درجه کیفیت', 'severity' => 'required'];
            }
            if (trim((string)($data['test_status'] ?? '')) === '') {
                $missing[] = ['code' => 'test_status', 'label' => 'وضعیت تست', 'severity' => 'required'];
            }
            // I1R2-C will enforce ≥2 real images; tracked here as deferred (non-blocking in A).
            $missing[] = ['code' => 'used_stock_images', 'label' => 'حداقل دو تصویر واقعی استوک', 'severity' => 'deferred'];
        }
    }

    return $missing;
}

/**
 * @param list<array{code:string,label:string,severity:string}> $missing
 * @return array{status:string,score:float,missing_count:int,missing:list}
 */
function inv360_i1r2a_completeness_result(array $missing): array
{
    $blocking = array_values(array_filter(
        $missing,
        static fn($m) => !in_array(($m['severity'] ?? ''), ['info', 'deferred'], true)
    ));
    $fatal = array_values(array_filter($blocking, static fn($m) => ($m['severity'] ?? '') === 'fatal'));
    $count = count($blocking);
    $baseRequired = 6; // approximate denominator for score
    $score = max(0.0, round(100.0 * (1.0 - min($count, $baseRequired) / $baseRequired), 2));
    if ($fatal !== []) {
        $status = 'FATAL_MISSING_IDENTITY';
        $score = min($score, 20.0);
    } elseif ($count > 0) {
        $status = 'NEEDS_COMPLETION';
    } else {
        $status = 'COMPLETE';
        $score = 100.0;
    }

    return [
        'status' => $status,
        'score' => $score,
        'missing_count' => $count,
        'missing' => $blocking,
    ];
}

function inv360_i1r2a_categories($conn, string $itemType = 'spare_part'): array
{
    if (!inv360_table_exists($conn, 'inv360_item_categories')) {
        return [];
    }

    return inv360_rows(
        $conn,
        'SELECT category_id, category_code, category_name_fa, sort_order
         FROM dbo.inv360_item_categories
         WHERE is_active = 1 AND item_type = ?
         ORDER BY sort_order, category_name_fa',
        [$itemType]
    );
}

function inv360_i1r2a_apply_market_defaults(array &$data): void
{
    $mg = trim((string)($data['market_grade'] ?? ''));
    if ($mg === 'genuine_new') {
        $data['part_condition'] = 'new';
        $data['authenticity_grade'] = 'original_vehicle_brand';
    } elseif ($mg === 'company_new') {
        $data['part_condition'] = 'new';
        $data['authenticity_grade'] = 'recognized_parts_brand';
    } elseif ($mg === 'used_stock') {
        $data['part_condition'] = 'used';
    }
}

/**
 * Persist open missing-field rows for an item (resolve previous open rows first).
 *
 * @param list<array{code:string,label:string,severity:string}> $missing
 */
function inv360_i1r2a_sync_missing_fields($conn, int $itemId, array $missing, int $userId, string $source = 'system'): void
{
    if (!inv360_table_exists($conn, 'inv360_item_missing_fields') || $itemId <= 0) {
        return;
    }
    inv360_exec(
        $conn,
        'UPDATE dbo.inv360_item_missing_fields
         SET resolved_at = SYSUTCDATETIME(), resolved_by = ?
         WHERE item_id = ? AND resolved_at IS NULL',
        [$userId > 0 ? $userId : null, $itemId]
    );
    foreach ($missing as $m) {
        $code = trim((string)($m['code'] ?? ''));
        $label = trim((string)($m['label'] ?? $code));
        $sev = trim((string)($m['severity'] ?? 'required'));
        if ($code === '') {
            continue;
        }
        inv360_exec(
            $conn,
            'INSERT INTO dbo.inv360_item_missing_fields (item_id, field_code, field_label_fa, severity, source_code)
             VALUES (?,?,?,?,?)',
            [$itemId, $code, $label, $sev, $source]
        );
    }
}
