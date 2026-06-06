<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/meeting-helpers.php';

function inv_has_text(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }

    if (function_exists('mb_strpos')) {
        return mb_strpos($haystack, $needle, 0, 'UTF-8') !== false;
    }

    return strpos($haystack, $needle) !== false;
}

if (!function_exists('inventoryCategories')) {
    function inventoryCategories(): array
    {
        return [
            'موتور و گیربکس',
            'تعلیق و زیروبند',
            'مصرفی و سرویس دوره‌ای',
            'کولر و رادیاتور',
            'برق و الکترونیک',
            'بدنه و تزئینات',
            'مبلمان داخلی',
            'آپشن و ارتقا',
            'ابزار و مواد مصرفی کارگاه',
            'سایر / نیازمند بررسی',
        ];
    }
}

if (!function_exists('inventoryQualities')) {
    function inventoryQualities(): array
    {
        return ['اصلی', 'OEM', 'Aftermarket معتبر', 'High Copy', 'استوک سالم', 'تعمیراتی', 'نامشخص'];
    }
}

if (!function_exists('inventoryUnits')) {
    function inventoryUnits(): array
    {
        return ['عدد', 'جفت', 'دست', 'ست', 'لیتر', 'متر', 'کیلوگرم', 'بسته'];
    }
}

if (!function_exists('inventoryCompatibilityStatuses')) {
    function inventoryCompatibilityStatuses(): array
    {
        return ['تایید نشده', 'نیازمند بررسی', 'تایید شده', 'ناسازگار', 'اطلاعات ناقص'];
    }
}

if (!function_exists('inventoryTechStatuses')) {
    function inventoryTechStatuses(): array
    {
        return ['در انتظار تایید فنی', 'نیازمند تکمیل عکس/کد', 'FormatInvalid', 'تایید شده', 'رد شده'];
    }
}

if (!function_exists('inventoryWorkflows')) {
    function inventoryWorkflows(): array
    {
        return ['Draft', 'NeedsReview', 'TechReview', 'PriceReview', 'Approved', 'Rejected', 'Archived'];
    }
}

if (!function_exists('inventoryVehicleBrands')) {
    function inventoryVehicleBrands(): array
    {
        return ['BMW', 'Mercedes-Benz', 'Porsche', 'Volkswagen', 'Volvo', 'Audi', 'Other'];
    }
}

if (!function_exists('inventoryWarehouseOptions')) {
    function inventoryWarehouseOptions(): array
    {
        return ['انبار اصلی', 'انبار قطعات مصرفی', 'انبار قطعات گران‌قیمت', 'قفسه موقت پذیرش', 'قرنطینه فنی', 'سایر'];
    }
}

if (!function_exists('inventoryLocationExamples')) {
    function inventoryLocationExamples(): array
    {
        return ['A01-R01-S01', 'A01-R02-S01', 'B01-R01-S01', 'B02-R03-S02', 'QC-HOLD', 'TEMP-IN'];
    }
}

if (!function_exists('inventoryCanRegisterInboundOnly')) {
    function inventoryCanRegisterInboundOnly(array $staff): bool
    {
        $role = (string)($staff['role_name'] ?? '');
        $user = (string)($staff['username'] ?? '');

        return inv_has_text($role, 'فقط ثبت ورود کالا')
            || substr($user, 0, strlen('inbound_receipt')) === 'inbound_receipt';
    }
}

if (!function_exists('inventoryCanEditFull')) {
    function inventoryCanEditFull(array $staff): bool
    {
        if (meetingIsViewOnly($staff)) {
            return false;
        }

        $role = (string)($staff['role_name'] ?? '');
        $username = (string)($staff['username'] ?? '');

        return !empty($staff['is_master_admin'])
            || inv_has_text($role, 'مالک')
            || inv_has_text($role, 'انبار')
            || $username === 'warehouse_price';
    }
}

if (!function_exists('inventoryReceiptNumber')) {
    function inventoryReceiptNumber(): string
    {
        return 'INV-' . date('Ymd') . '-' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('inventoryHeaderActions')) {
    function inventoryHeaderActions(string $active = ''): void
    {
        $links = [
            'dashboard' => ['داشبورد انبار', 'staff-inventory.php'],
            'new' => ['ثبت کالای جدید', 'staff-inventory-new.php'],
            'inbound' => ['ثبت ورود/رسید', 'staff-inventory-inbound.php'],
            'search' => ['جستجوی کالا', 'staff-inventory-search.php'],
            'reports' => ['گزارش انبار', 'staff-inventory-reports.php'],
            'outbound' => ['ثبت خروج', 'staff-inventory-outbound.php'],
            'counting' => ['انبارگردانی', 'staff-inventory-counting.php'],
            'valuation' => ['ارزش ریالی', 'staff-inventory-valuation.php'],
        ];
        echo '<nav class="inventory-subnav">';
        foreach ($links as $key => [$label, $href]) {
            echo '<a class="' . ($active === $key ? 'active' : '') . '" href="' . e($href) . '">' . e($label) . '</a>';
        }
        echo '</nav>';
    }
}

if (!function_exists('inventoryCount')) {
    function inventoryCount(): int
    {
        try {
            return (int)getPdo()->query('SELECT COUNT(*) FROM inventory_items_staging')->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('inventoryMoney')) {
    function inventoryMoney(?string $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }
        return number_format((float)$value, 0, '.', ',');
    }
}
