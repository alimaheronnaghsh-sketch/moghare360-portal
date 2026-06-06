<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/meeting-helpers.php';
ensureSessionStarted();

try {
    $staff = requireStaffLogin();
    if (!isMasterAdmin($staff)) {
        showErrorPage('دسترسی به مدیریت کاربران فقط برای مدیر سیستم مجاز است.');
    }
    $q = trim((string)($_GET['q'] ?? ''));
    if ($q !== '') {
        $stmt = getPdo()->prepare('SELECT id, full_name, username, role_name, is_master_admin, is_active, created_at FROM staff_users WHERE full_name LIKE ? OR username LIKE ? OR role_name LIKE ? ORDER BY id DESC');
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like, $like]);
        $users = $stmt->fetchAll();
    } else {
        $users = getPdo()->query('SELECT id, full_name, username, role_name, is_master_admin, is_active, created_at FROM staff_users ORDER BY id DESC')->fetchAll();
    }
    renderHeader('مدیریت کاربران پرسنل', 'Master Admin');
    renderFlashes();
    ?>
    <main class="page-grid">
      <section class="card">
        <h2>افزودن کاربر جدید</h2>
        <form method="post" action="staff-user-save.php" class="form-grid">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="add">
          <label>نام کامل
            <input name="full_name" required>
          </label>
          <label>نام کاربری
            <input name="username" required>
          </label>
          <label>نقش
            <input name="role_name" required value="پذیرش">
          </label>
          <label>رمز عبور
            <input name="password" type="password" required>
          </label>
          <label class="checkbox-line">
            <input type="checkbox" name="is_master_admin" value="1"> مدیر سیستم
          </label>
          <button class="btn primary" type="submit">ذخیره کاربر</button>
        </form>
      </section>
      <section class="card wide-card">
        <h2>کاربران موجود</h2>
        <form class="search-bar" method="get" action="staff-users.php">
          <input name="q" value="<?= e($q) ?>" placeholder="جستجوی نام، نام کاربری یا نقش">
          <button class="btn secondary" type="submit">جستجو</button>
          <a class="btn ghost" href="staff-users.php">نمایش همه</a>
        </form>
        <div class="table-wrap">
          <table>
            <thead><tr><th>نام</th><th>کاربری</th><th>نقش</th><th>وضعیت</th><th>عملیات</th></tr></thead>
            <tbody>
            <?php foreach ($users as $user): ?>
              <tr>
                <td><?= e($user['full_name']) ?></td>
                <td><?= e($user['username']) ?></td>
                <td><?= e($user['role_name']) ?></td>
                <td><?= ((int)$user['is_active'] === 1) ? 'فعال' : 'غیرفعال' ?></td>
                <td class="actions-cell">
                  <form method="post" action="staff-user-save.php" class="inline-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= e((string)$user['id']) ?>">
                    <input name="full_name" value="<?= e($user['full_name']) ?>" placeholder="نام کامل">
                    <input name="role_name" value="<?= e($user['role_name']) ?>">
                    <input name="password" type="password" placeholder="رمز جدید">
                    <label class="checkbox-line small-check"><input type="checkbox" name="is_master_admin" value="1" <?= ((int)$user['is_master_admin'] === 1) ? 'checked' : '' ?>> مدیر</label>
                    <button class="btn small" type="submit">ذخیره</button>
                  </form>
                  <form method="post" action="staff-user-save.php" class="inline-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= e((string)$user['id']) ?>">
                    <button class="btn small danger" type="submit"><?= ((int)$user['is_active'] === 1) ? 'غیرفعال' : 'فعال' ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
    <?php
    renderFooter();
} catch (Throwable $e) {
    showErrorPage('خطا در مدیریت کاربران پرسنل.', $e->getMessage());
}
