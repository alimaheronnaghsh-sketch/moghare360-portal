<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

ensureSessionStarted();

function moghare_has_text(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }

    if (function_exists('mb_strpos')) {
        return mb_strpos($haystack, $needle, 0, 'UTF-8') !== false;
    }

    return strpos($haystack, $needle) !== false;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('staff-login.php');
    }

    checkCsrf();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        flash('نام کاربری و رمز عبور الزامی است.', 'error');
        redirect('staff-login.php');
    }

    $stmt = getPdo()->prepare("
        SELECT
            id,
            full_name,
            username,
            password_hash,
            role_name,
            is_master_admin,
            is_active,
            profile_photo_path
        FROM staff_users
        WHERE username = ?
        LIMIT 1
    ");

    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        flash('نام کاربری یا رمز عبور اشتباه است.', 'error');
        redirect('staff-login.php');
    }

    if ((int)($user['is_active'] ?? 0) !== 1) {
        flash('این کاربر غیرفعال است.', 'error');
        redirect('staff-login.php');
    }

    $hash = (string)($user['password_hash'] ?? '');

    if ($hash === '' || !password_verify($password, $hash)) {
        flash('نام کاربری یا رمز عبور اشتباه است.', 'error');
        redirect('staff-login.php');
    }

    $_SESSION['staff_user'] = [
        'id' => (int)($user['id'] ?? 0),
        'full_name' => (string)($user['full_name'] ?? ''),
        'username' => (string)($user['username'] ?? ''),
        'role_name' => (string)($user['role_name'] ?? ''),
        'is_master_admin' => (int)($user['is_master_admin'] ?? 0),
        'is_active' => (int)($user['is_active'] ?? 1),
        'profile_photo_path' => (string)($user['profile_photo_path'] ?? ''),
    ];

    $inventoryDataEntryUsers = ['yazdani', 'salimi', 'jafar', 'soheil', 'omid'];
    $roleName = (string)($user['role_name'] ?? '');

    if (
        in_array((string)$user['username'], $inventoryDataEntryUsers, true)
        || moghare_has_text($roleName, 'بدون مبلغ')
        || moghare_has_text($roleName, 'بدون ریال')
    ) {
        redirect('staff-inventory-new.php');
    }

    redirect('staff-dashboard.php');

} catch (Throwable $e) {
    showErrorPage(
        'خطا در ورود پرسنل. SQL جدول staff_users و تنظیمات دیتابیس را بررسی کنید.',
        $e->getMessage()
    );
}