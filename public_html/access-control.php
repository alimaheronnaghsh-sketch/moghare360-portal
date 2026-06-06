<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function accessCurrentStaffId(): int
{
    $staff = currentStaffUser();
    return (int)($staff['id'] ?? 0);
}

function accessIsMaster(): bool
{
    $staff = currentStaffUser();
    return isMasterAdmin($staff);
}

function accessHas(string $permissionKey): bool
{
    if (accessIsMaster()) {
        return true;
    }

    $staffId = accessCurrentStaffId();
    if ($staffId <= 0) {
        return false;
    }

    $stmt = getPdo()->prepare("\n        SELECT 1\n        FROM staff_user_access_profiles suap\n        INNER JOIN access_profile_permissions app ON app.profile_id = suap.access_profile_id\n        INNER JOIN access_profiles ap ON ap.id = suap.access_profile_id AND ap.is_active = 1\n        INNER JOIN access_permissions p ON p.permission_key = app.permission_key AND p.is_active = 1\n        WHERE suap.staff_user_id = ?\n          AND app.permission_key = ?\n        LIMIT 1\n    ");
    $stmt->execute([$staffId, $permissionKey]);
    return (bool)$stmt->fetchColumn();
}

function accessRequire(string $permissionKey): void
{
    requireStaffLogin();
    if (!accessHas($permissionKey)) {
        showErrorPage('شما به این بخش دسترسی ندارید.');
    }
}

function accessModuleGroups(): array
{
    return [
        'dashboard' => 'داشبورد',
        'customer' => 'مشتریان',
        'reception' => 'پذیرش',
        'inventory' => 'انبار',
        'purchase_domestic' => 'خرید داخلی',
        'purchase_foreign' => 'خرید خارجی',
        'sales_accounting' => 'حسابداری فروش',
        'hr' => 'منابع انسانی',
        'admin' => 'مدیریت سیستم',
        'reports' => 'گزارش‌ها',
    ];
}
