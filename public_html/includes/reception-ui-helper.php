<?php
declare(strict_types=1);

/**
 * MOGHARE360 — Customer Relations / Reception presentation helpers (UI only).
 */

if (!function_exists('m360_rui_h')) {
    function m360_rui_h(?string $v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/** @return array{jy:int,jm:int,jd:int} */
function m360_rui_gregorian_to_jalali(int $gy, int $gm, int $gd): array
{
    $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (int)(355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $gdm[$gm - 1]);
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return ['jy' => $jy, 'jm' => $jm, 'jd' => $jd];
}

function m360_rui_jalali_date(?string $raw, bool $withTime = true): string
{
    $raw = trim((string)$raw);
    if ($raw === '' || $raw === '—') {
        return '—';
    }
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/', $raw, $m)) {
        return $raw;
    }
    $j = m360_rui_gregorian_to_jalali((int)$m[1], (int)$m[2], (int)$m[3]);
    $date = sprintf('%04d/%02d/%02d', $j['jy'], $j['jm'], $j['jd']);
    if ($withTime && isset($m[4], $m[5]) && $m[4] !== '') {
        return $date . ' - ' . $m[4] . ':' . $m[5];
    }
    return $date;
}

function m360_rui_label(?string $code): string
{
    $c = trim((string)$code);
    if ($c === '') {
        return '—';
    }
    static $map = [
        'diagnostic_inspection' => 'کارشناسی / عیب‌یابی',
        'DIAGNOSTIC_INSPECTION' => 'کارشناسی / عیب‌یابی',
        'buy_sell_inspection' => 'کارشناسی خرید و فروش',
        'BUY_SELL_INSPECTION' => 'کارشناسی خرید و فروش',
        'periodic_service' => 'سرویس دوره‌ای',
        'PERIODIC_SERVICE' => 'سرویس دوره‌ای',
        'repair' => 'تعمیر',
        'REPAIR' => 'تعمیر',
        'STAFF_ASSISTED_WALKIN' => 'پذیرش حضوری',
        'PUBLIC_SITE' => 'درخواست آنلاین',
        'WALKIN' => 'پذیرش حضوری',
        'ONLINE' => 'آنلاین',
        'RETURNING' => 'مراجع مجدد',
        'DRAFT' => 'پیش‌نویس',
        'PROFILE_INCOMPLETE' => 'پرونده ناقص',
        'IN_PROGRESS' => 'در حال تکمیل',
        'READY_FOR_CONTRACT' => 'آماده قرارداد',
        'CONTRACT_PENDING' => 'در انتظار قرارداد',
        'CONTRACT_SIGNED' => 'قرارداد امضا شده',
        'IN_SERVICE' => 'در سالن خدمات',
        'WAITING_CUSTOMER' => 'منتظر مشتری',
        'READY_FOR_DELIVERY' => 'آماده تحویل',
        'DELIVERED' => 'تحویل‌شده',
        'CLOSED' => 'بسته‌شده',
        'CANCELLED' => 'لغوشده',
        'NEW' => 'جدید',
        'PENDING' => 'در انتظار',
        'UNDER_REVIEW' => 'در حال بررسی',
        'IN_REVIEW' => 'در حال بررسی',
        'ACCEPTED' => 'پذیرفته‌شده',
        'CONVERTED_TO_JOBCARD' => 'تبدیل به کارت کار',
        'REJECTED' => 'رد شده',
        'WAITING' => 'منتظر',
        'DONE' => 'انجام‌شده',
        'OVERDUE' => 'عقب‌افتاده',
        'OPEN' => 'باز',
        'READY' => 'آماده',
        'MISSING' => 'ناقص',
        'UPLOADED' => 'آپلود شده',
        'VERIFIED' => 'تأیید شده',
        'SIGNED' => 'امضا شده',
        'NONE' => 'ندارد',
        'ACTIVE' => 'فعال',
        'READY_FOR_REVIEW' => 'آماده بررسی',
        'APPROVED_FOR_EXPORT' => 'تأیید برای خروجی',
        'EXPORTED' => 'خروجی‌گرفته',
        'SENT' => 'ارسال‌شده',
        'VIEWED' => 'مشاهده‌شده',
        'OVERRIDDEN' => 'با تأیید مدیر',
        'COMPLETED' => 'تکمیل‌شده',
        'NEEDS_FOLLOWUP' => 'نیازمند پیگیری',
        'DUE_SOON' => 'نزدیک سررسید',
        'DUE' => 'سررسید',
        'SCHEDULED' => 'زمان‌بندی‌شده',
        'CORRECTION_REQUIRED' => 'نیازمند اصلاح',
        'UNDER_REVIEW' => 'در حال بررسی',
        'GOLD' => 'طلایی',
        'PLATINUM' => 'پلاتین',
        'SILVER' => 'نقره‌ای',
        'VIP' => 'VIP',
        'PERSON' => 'حقیقی',
        'COMPANY' => 'حقوقی',
        'CONTACTED' => 'تماس گرفته‌شده',
        'IDENTIFIED' => 'شناسایی‌شده',
        'OFFERED' => 'پیشنهاد شده',
        'WON' => 'موفق',
        'LOST' => 'از دست‌رفته',
        'SUCCESS' => 'موفق',
        'FAILED' => 'ناموفق',
        'ASSIGNED' => 'تخصیص‌یافته',
        'USED' => 'استفاده‌شده',
        'EXPIRED' => 'منقضی',
        'HIGH' => 'بالا',
        'NORMAL' => 'عادی',
        'LOW' => 'کم',
        'MEDIUM' => 'متوسط',
        'CRITICAL' => 'بحرانی',
        'FOLLOWUP' => 'پیگیری',
        'DOCUMENT' => 'مدرک',
        'CALLBACK' => 'تماس',
        'SERVICE' => 'سرویس',
        'INSPECTION' => 'بازدید',
        'PRICE' => 'قیمت',
        'DELAY' => 'تأخیر',
        'DISCOUNT' => 'تخفیف',
        'PACKAGE' => 'پکیج',
        'CHURN' => 'ریزش',
        'INACTIVE' => 'غیرفعال',
        'COMPETITOR' => 'رقیب',
        'RETURNED' => 'بازگشته',
        'LEGACY' => 'قدیمی',
        'LEGACY_MANUAL' => 'ورود دستی قدیمی',
        'LEGACY_BATCH' => 'ورود گروهی قدیمی',
        'SUBMITTED' => 'در انتظار تأیید',
        'APPROVED' => 'تأیید‌شده',
        'VALIDATED' => 'اعتبارسنجی‌شده',
        'IMPORTED' => 'وارد‌شده',
        'VALID' => 'معتبر',
        'INVALID' => 'نامعتبر',
        'DUPLICATE' => 'تکراری',
        'SKIPPED' => 'رد‌شده',
        'NOMINATE' => 'معرفی VIP',
        'APPROVE' => 'تأیید',
        'REJECT' => 'رد',
        'VALIDATE' => 'اعتبارسنجی',
        'IMPORT' => 'ورود اطلاعات',
        'CREATE' => 'ایجاد',
        'UPDATE' => 'به‌روزرسانی',
        'EXPORT' => 'خروجی',
        'VISIT_COUNT' => 'تعداد مراجعه',
        'REVENUE' => 'درآمد',
        'MANUAL' => 'دستی',
        'MANUAL_NOMINATION' => 'معرفی دستی',
        'CUSTOMER_LEGACY' => 'مشتری قدیمی',
        'VIP' => 'VIP',
    ];
    $upper = strtoupper($c);
    if (isset($map[$c])) {
        return $map[$c];
    }
    if (isset($map[$upper])) {
        return $map[$upper];
    }
    $lower = strtolower($c);
    if (isset($map[$lower])) {
        return $map[$lower];
    }
    return $c;
}

/**
 * @param list<array<string,mixed>> $rows
 * @return array{rows:list<array<string,mixed>>,page:int,pages:int,total:int,per_page:int}
 */
function m360_rui_paginate(array $rows, int $page = 1, int $perPage = 10): array
{
    $perPage = max(1, min(50, $perPage));
    $total = count($rows);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($pages, $page));
    $offset = ($page - 1) * $perPage;
    return [
        'rows' => array_slice($rows, $offset, $perPage),
        'page' => $page,
        'pages' => $pages,
        'total' => $total,
        'per_page' => $perPage,
    ];
}

/**
 * @param list<array<string,mixed>> $rows
 * @return list<array<string,mixed>>
 */
function m360_rui_sort_rows(array $rows, string $sort, string $dir = 'desc'): array
{
    $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';
    $sort = preg_replace('/[^a-z0-9_]/', '', strtolower($sort)) ?: 'id';
    usort($rows, static function (array $a, array $b) use ($sort, $dir): int {
        $av = $a[$sort] ?? $a['online_request_id'] ?? $a['case_id'] ?? $a['id'] ?? '';
        $bv = $b[$sort] ?? $b['online_request_id'] ?? $b['case_id'] ?? $b['id'] ?? '';
        if (is_numeric($av) && is_numeric($bv)) {
            $cmp = ((float)$av <=> (float)$bv);
        } else {
            $cmp = strcmp((string)$av, (string)$bv);
        }
        return $dir === 'asc' ? $cmp : -$cmp;
    });
    return $rows;
}

function m360_rui_query_keep(array $extra = [], array $drop = []): string
{
    $q = $_GET;
    foreach ($drop as $k) {
        unset($q[$k]);
    }
    foreach ($extra as $k => $v) {
        if ($v === null) {
            unset($q[$k]);
            continue;
        }
        $q[$k] = $v;
    }
    return http_build_query($q);
}

function m360_rui_render_pagination(array $pageInfo, string $baseQuery = '', string $pageKey = 'page'): void
{
    $page = (int)$pageInfo['page'];
    $pages = (int)$pageInfo['pages'];
    $total = (int)$pageInfo['total'];
    if ($total <= (int)$pageInfo['per_page']) {
        echo '<div class="c360-pager"><span class="c360-muted">' . (int)$total . ' مورد</span></div>';
        return;
    }
    $qs = static function (int $p) use ($baseQuery, $pageKey): string {
        $parts = [];
        if ($baseQuery !== '') {
            parse_str($baseQuery, $parts);
        }
        $parts[$pageKey] = $p;
        return '?' . http_build_query($parts);
    };
    echo '<div class="c360-pager">';
    if ($page > 1) {
        echo '<a class="c360-btn" href="' . m360_rui_h($qs($page - 1)) . '" rel="prev">قبلی</a>';
    } else {
        echo '<span class="c360-btn is-disabled">قبلی</span>';
    }
    echo '<span class="c360-pager-meta">صفحه ' . $page . ' از ' . $pages . ' — ' . $total . ' مورد</span>';
    if ($page < $pages) {
        echo '<a class="c360-btn" href="' . m360_rui_h($qs($page + 1)) . '" rel="next">بعدی</a>';
    } else {
        echo '<span class="c360-btn is-disabled">بعدی</span>';
    }
    echo '</div>';
}

function m360_rui_render_head(string $title, string $pageLabel, string $subtitle = ''): void
{
    echo '<div class="c360-crumb"><a href="personnel.html">پرسنل</a> / <a href="erp-reception-board.php">ارتباط با مشتریان</a> / ' . m360_rui_h($pageLabel) . '</div>';
    echo '<header class="c360-head"><div>';
    echo '<h1>' . m360_rui_h($title) . '</h1>';
    if ($subtitle !== '') {
        echo '<p>' . m360_rui_h($subtitle) . '</p>';
    }
    echo '<div class="c360-head-meta">';
    echo '<a class="c360-btn" href="erp-reception-board.php">بازگشت به مرکز ارتباط با مشتریان</a>';
    echo '<span class="c360-badge">moghare360_ERP</span>';
    echo '</div></div></header>';
}

function m360_rui_css_links(): void
{
    echo '<link rel="stylesheet" href="assets/css/m360-suite-theme.css">' . "\n";
    echo '<link rel="stylesheet" href="assets/css/m360-crm.css">' . "\n";
}
