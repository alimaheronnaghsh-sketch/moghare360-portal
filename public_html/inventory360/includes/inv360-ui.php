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

function inv360_nav_items(): array
{
    return [
        'dashboard.php' => 'داشبورد',
        'items.php' => 'کالاها',
        'item-search.php' => 'جستجوی کالا',
        'warehouses.php' => 'انبارها',
        'locations.php' => 'مکان‌ها / Bin',
        'stock-documents.php' => 'اسناد انبار',
        'stock-balances.php' => 'موجودی',
        'reservations.php' => 'رزروها',
        'stock-counts.php' => 'انبارگردانی',
        'suppliers.php' => 'تأمین‌کنندگان',
        'purchase-requests.php' => 'درخواست خرید',
        'rfq.php' => 'RFQ',
        'purchase-orders.php' => 'سفارش خرید',
        'goods-receipts.php' => 'دریافت کالا',
        'quality-control.php' => 'کنترل کیفیت',
        'quarantine.php' => 'قرنطینه',
        'costing.php' => 'بهای تمام‌شده',
        'landed-cost.php' => 'هزینه فرود آمده',
        'logistics.php' => 'لجستیک',
        'tools-assets.php' => 'ابزار و اموال',
        'reports.php' => 'گزارش‌ها',
        'audit-log.php' => 'Audit',
        'settings.php' => 'تنظیمات',
    ];
}

function inv360_status_fa(string $code): string
{
    $map = [
        'draft' => 'پیش‌نویس',
        'submitted' => 'ارسال‌شده',
        'approved' => 'تأییدشده',
        'posted' => 'ثبت قطعی',
        'rejected' => 'رد شده',
        'cancelled' => 'لغو شده',
        'voided' => 'باطل',
        'returned_for_correction' => 'برگشت برای اصلاح',
        'active' => 'فعال',
        'inactive' => 'غیرفعال',
        'open' => 'باز',
        'closed' => 'بسته',
        'partial' => 'جزئی',
        'quarantine' => 'قرنطینه',
        'accepted' => 'پذیرفته',
        'released' => 'آزاد شده',
        'planned' => 'برنامه‌ریزی',
        'loaded' => 'بارگیری',
        'dispatched' => 'اعزام',
        'delivered' => 'تحویل',
        'good' => 'سالم',
        'issued' => 'صادر شده',
        'returned' => 'برگشت‌شده',
        'spare_part' => 'قطعه یدکی',
        'consumable' => 'مصرفی',
        'tool' => 'ابزار',
        'asset' => 'دارایی',
        'raw_material' => 'مواد اولیه',
        'finished_good' => 'کالای آماده',
        'main' => 'اصلی',
        'parts' => 'قطعات',
        'tools' => 'ابزار',
        'returns' => 'مرجوعی',
        'scrap' => 'ضایعات',
        'consignment' => 'امانی',
        'project' => 'پروژه',
        'mobile' => 'سیار',
        'in_transit' => 'در مسیر',
        'OWNER_ADMIN' => 'مالک / مدیر کامل',
    ];
    $k = strtolower(trim($code));
    return $map[$k] ?? ($map[$code] ?? $code);
}

function inv360_layout_start(string $title, string $active = ''): void
{
    $user = inv360_current_user();
    $name = inv360_h((string)($user['display_name'] ?? ''));
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . inv360_h($title) . ' | Inventory360</title>';
    echo '<link rel="stylesheet" href="assets/inv360.css">';
    echo '</head><body><div class="inv-shell">';
    echo '<aside class="inv-nav"><div class="brand">Inventory360</div><nav>';
    foreach (inv360_nav_items() as $href => $label) {
        $cls = ($active === $href || basename((string)($_SERVER['PHP_SELF'] ?? '')) === $href) ? ' class="active"' : '';
        echo '<a href="' . inv360_h($href) . '"' . $cls . '>' . inv360_h($label) . '</a>';
    }
    echo '</nav><div class="nav-foot"><div>' . $name . '</div><a href="logout.php">خروج</a></div></aside>';
    echo '<main class="inv-main"><header class="inv-top"><h1>' . inv360_h($title) . '</h1></header><div class="inv-content">';
}

function inv360_layout_end(): void
{
    echo '</div></main></div></body></html>';
}

function inv360_flash_render(?string $msg, bool $ok = true): void
{
    if ($msg === null || $msg === '') {
        return;
    }
    echo '<div class="notice ' . ($ok ? 'ok' : 'err') . '">' . inv360_h($msg) . '</div>';
}
