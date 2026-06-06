<?php
declare(strict_types=1);
require_once __DIR__ . '/access-control.php';

$staff = requireStaffLogin();
if (!isMasterAdmin($staff) && !accessHas('admin.access')) {
    showErrorPage('فقط مدیر ارشد می‌تواند سطح دسترسی را ذخیره کند.');
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('staff-access-profiles.php');
    }
    checkCsrf();
    $pdo = getPdo();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create_profile') {
        $name = trim((string)($_POST['profile_name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        if ($name === '') {
            flash('نام سطح دسترسی الزامی است.', 'error');
            redirect('staff-access-profiles.php');
        }
        $stmt = $pdo->prepare('INSERT INTO access_profiles (profile_name, description, is_active, updated_at) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE description = VALUES(description), is_active = 1, updated_at = NOW()');
        $stmt->execute([$name, $description]);
        flash('سطح دسترسی ساخته شد.', 'ok');
        redirect('staff-access-profiles.php');
    }

    if ($action === 'save_profile_permissions') {
        $profileId = (int)($_POST['profile_id'] ?? 0);
        $permissions = $_POST['permissions'] ?? [];
        if ($profileId <= 0 || !is_array($permissions)) {
            flash('اطلاعات سطح دسترسی نامعتبر است.', 'error');
            redirect('staff-access-profiles.php');
        }
        $pdo->beginTransaction();
        $del = $pdo->prepare('DELETE FROM access_profile_permissions WHERE profile_id = ?');
        $del->execute([$profileId]);
        $ins = $pdo->prepare('INSERT IGNORE INTO access_profile_permissions (profile_id, permission_key) VALUES (?, ?)');
        foreach ($permissions as $permission) {
            $permission = (string)$permission;
            if (preg_match('/^[a-zA-Z0-9_.]+$/', $permission)) {
                $ins->execute([$profileId, $permission]);
            }
        }
        $pdo->commit();
        flash('دسترسی‌های سطح انتخابی ذخیره شد.', 'ok');
        redirect('staff-access-profiles.php');
    }

    if ($action === 'assign_profiles') {
        $userProfiles = $_POST['user_profiles'] ?? [];
        if (!is_array($userProfiles)) {
            $userProfiles = [];
        }
        $userIds = $pdo->query('SELECT id FROM staff_users')->fetchAll(PDO::FETCH_COLUMN);
        $pdo->beginTransaction();
        $pdo->exec('DELETE FROM staff_user_access_profiles');
        $ins = $pdo->prepare('INSERT IGNORE INTO staff_user_access_profiles (staff_user_id, access_profile_id) VALUES (?, ?)');
        foreach ($userIds as $uid) {
            $uid = (int)$uid;
            $profiles = $userProfiles[$uid] ?? [];
            if (!is_array($profiles)) {
                continue;
            }
            foreach ($profiles as $pid) {
                $pid = (int)$pid;
                if ($pid > 0) {
                    $ins->execute([$uid, $pid]);
                }
            }
        }
        $pdo->commit();
        flash('دسترسی پرسنل ذخیره شد.', 'ok');
        redirect('staff-access-profiles.php');
    }

    flash('عملیات نامعتبر است.', 'error');
    redirect('staff-access-profiles.php');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    showErrorPage('ذخیره سطح دسترسی انجام نشد.', $e->getMessage());
}
