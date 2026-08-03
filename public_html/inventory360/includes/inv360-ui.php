<?php
declare(strict_types=1);

function inv360_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function inv360_money($n): string
{
    return number_format((float)$n, 0, '.', ',');
}

function inv360_nav_sections(): array
{
    return [
        'راهبری' => [
            'dashboard.php' => 'داشبورد',
            'settings.php' => 'تنظیمات',
        ],
        'کالا' => [
            'items.php' => 'کالاها',
            'item-form.php' => 'تعریف کالا',
            'item-search.php' => 'جستجوی کالا',
        ],
        'انبار' => [
            'warehouses.php' => 'انبارها',
            'locations.php' => 'مکان‌ها / Bin',
            'stock-balances.php' => 'موجودی',
        ],
        'عملیات موجودی' => [
            'stock-documents.php' => 'اسناد انبار',
            'reservations.php' => 'رزروها',
            'stock-counts.php' => 'انبارگردانی',
        ],
        'خرید' => [
            'suppliers.php' => 'تأمین‌کنندگان',
            'purchase-requests.php' => 'درخواست خرید',
            'rfq.php' => 'RFQ',
            'purchase-orders.php' => 'سفارش خرید',
        ],
        'دریافت و کیفیت' => [
            'goods-receipts.php' => 'دریافت کالا',
            'quality-control.php' => 'کنترل کیفیت',
            'quarantine.php' => 'قرنطینه',
            'supplier-returns.php' => 'مرجوعی تأمین‌کننده',
        ],
        'لجستیک و ابزار' => [
            'costing.php' => 'بهای تمام‌شده',
            'landed-cost.php' => 'هزینه فرود آمده',
            'logistics.php' => 'لجستیک',
            'tools-assets.php' => 'ابزار و اموال',
        ],
        'گزارش و Audit' => [
            'reports.php' => 'گزارش‌ها',
            'audit-log.php' => 'Audit',
        ],
    ];
}

function inv360_status_fa(string $code): string
{
    $map = [
        'draft' => 'پیش‌نویس', 'submitted' => 'ارسال‌شده', 'approved' => 'تأییدشده',
        'posted' => 'ثبت قطعی', 'rejected' => 'رد شده', 'cancelled' => 'لغو شده',
        'voided' => 'باطل', 'returned_for_correction' => 'برگشت برای اصلاح',
        'active' => 'فعال', 'inactive' => 'غیرفعال', 'open' => 'باز', 'closed' => 'بسته',
        'partial' => 'جزئی', 'quarantine' => 'قرنطینه', 'accepted' => 'پذیرفته',
        'released' => 'آزاد شده', 'planned' => 'برنامه‌ریزی', 'loaded' => 'بارگیری',
        'dispatched' => 'اعزام', 'delivered' => 'تحویل', 'good' => 'سالم',
        'issued' => 'صادر شده', 'returned' => 'برگشت‌شده',
        'spare_part' => 'قطعه یدکی', 'consumable' => 'مصرفی', 'tool' => 'ابزار',
        'asset' => 'دارایی', 'raw_material' => 'مواد اولیه', 'finished_good' => 'کالای آماده',
        'main' => 'اصلی', 'parts' => 'قطعات', 'tools' => 'ابزار', 'returns' => 'مرجوعی',
        'scrap' => 'ضایعات', 'consignment' => 'امانی', 'project' => 'پروژه',
        'mobile' => 'سیار', 'in_transit' => 'در مسیر', 'OWNER_ADMIN' => 'مالک / مدیر کامل',
    ];
    $k = strtolower(trim($code));
    return $map[$k] ?? ($map[$code] ?? $code);
}

function inv360_layout_start(string $title, string $active = ''): void
{
    $user = inv360_current_user();
    $name = inv360_h((string)($user['display_name'] ?? ''));
    $current = basename((string)($_SERVER['PHP_SELF'] ?? ''));
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . inv360_h($title) . ' | Inventory360</title>';
    echo '<link rel="stylesheet" href="../assets/css/m360-suite-theme.css">';
    echo '</head><body>';
    echo '<div class="m360-app-shell">';
    echo '<aside class="m360-sidebar">';
    echo '<div class="m360-sidebar-brand"><h2>Inventory360</h2><small>سامانه مستقل انبار، خرید و لجستیک</small></div>';
    foreach (inv360_nav_sections() as $section => $links) {
        echo '<div class="m360-sidebar-section">' . inv360_h($section) . '</div>';
        foreach ($links as $href => $label) {
            $cls = ($active === $href || $current === $href) ? ' active' : '';
            echo '<a class="m360-sidebar-link' . $cls . '" href="' . inv360_h($href) . '">' . inv360_h($label) . '</a>';
        }
    }
    echo '<div class="m360-sidebar-foot"><span>' . $name . '</span><a href="logout.php">خروج</a></div>';
    echo '</aside>';
    echo '<main class="m360-main">';
    echo '<div class="m360-page-header"><h1 class="m360-page-title">' . inv360_h($title) . '</h1></div>';
}

function inv360_layout_end(): void
{
    echo '</main></div></body></html>';
}

function inv360_flash_render(?string $msg, bool $ok = true): void
{
    if ($msg === null || $msg === '') return;
    echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . inv360_h($msg) . '</div>';
}
