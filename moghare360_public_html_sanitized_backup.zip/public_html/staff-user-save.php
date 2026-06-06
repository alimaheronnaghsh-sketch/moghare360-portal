<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/meeting-helpers.php';
ensureSessionStarted();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('staff-users.php');
    }
    checkCsrf();
    $staff = requireStaffLogin();
    if (!isMasterAdmin($staff)) {
        showErrorPage('دسترسی مجاز نیست.');
    }

    $action = (string)($_POST['action'] ?? '');
    $pdo = getPdo();
    if ($action === 'add') {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $role = trim((string)($_POST['role_name'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($fullName === '' || $username === '' || $role === '' || $password === '') {
            flash('همه فیلدهای کاربر جدید الزامی است.', 'bad');
            redirect('staff-users.php');
        }
        $stmt = $pdo->prepare('INSERT INTO staff_users (full_name, username, password_hash, role_name, is_master_admin, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())');
        $stmt->execute([$fullName, $username, hashPassword($password), $role, isset($_POST['is_master_admin']) ? 1 : 0]);
        flash('کاربر جدید ایجاد شد.');
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $role = trim((string)($_POST['role_name'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($id <= 0 || $fullName === '' || $role === '') {
            flash('اطلاعات ویرایش کاربر معتبر نیست.', 'bad');
            redirect('staff-users.php');
        }
        if ($password !== '') {
            $stmt = $pdo->prepare('UPDATE staff_users SET full_name = ?, role_name = ?, is_master_admin = ?, password_hash = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$fullName, $role, isset($_POST['is_master_admin']) ? 1 : 0, hashPassword($password), $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE staff_users SET full_name = ?, role_name = ?, is_master_admin = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$fullName, $role, isset($_POST['is_master_admin']) ? 1 : 0, $id]);
        }
        flash('کاربر به‌روزرسانی شد.');
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $id !== (int)$staff['id']) {
            $stmt = $pdo->prepare('UPDATE staff_users SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = ?');
            $stmt->execute([$id]);
            flash('وضعیت کاربر تغییر کرد.');
        }
    }
    redirect('staff-users.php');
} catch (Throwable $e) {
    showErrorPage('خطا در ذخیره کاربر پرسنل.', $e->getMessage());
}
