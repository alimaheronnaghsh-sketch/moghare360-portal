<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/meeting-helpers.php';
ensureSessionStarted();

try {
    if (empty($_SESSION['admin_ok'])) {
        redirect('admin-login.php');
    }
    $pdo = getPdo();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        checkCsrf();
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'update_customer') {
            $stmt = $pdo->prepare('UPDATE portal_customers_staging SET full_name = ?, national_code = ?, job_title = ?, sync_status = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([
                trim((string)($_POST['full_name'] ?? '')),
                trim((string)($_POST['national_code'] ?? '')),
                trim((string)($_POST['job_title'] ?? '')),
                trim((string)($_POST['sync_status'] ?? 'Pending')),
                (int)($_POST['id'] ?? 0),
            ]);
            flash('رکورد مشتری به‌روزرسانی شد.');
        } elseif ($action === 'update_request') {
            $stmt = $pdo->prepare('UPDATE portal_service_requests_staging SET service_type = ?, sync_status = ?, sync_error = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([
                trim((string)($_POST['service_type'] ?? '')),
                trim((string)($_POST['sync_status'] ?? 'Pending')),
                trim((string)($_POST['sync_error'] ?? '')),
                (int)($_POST['id'] ?? 0),
            ]);
            flash('وضعیت درخواست خدمات به‌روزرسانی شد.');
        }
        redirect('admin-pending.php');
    }
    $customerQ = trim((string)($_GET['customer_q'] ?? ''));
    $requestQ = trim((string)($_GET['request_q'] ?? ''));
    $staffQ = trim((string)($_GET['staff_q'] ?? ''));
    if ($customerQ !== '') {
        $stmt = $pdo->prepare('SELECT id, full_name, national_code, mobile, job_title, customer_tracking_code, sync_status, created_at, updated_at FROM portal_customers_staging WHERE full_name LIKE ? OR mobile LIKE ? OR national_code LIKE ? OR customer_tracking_code LIKE ? ORDER BY id DESC LIMIT 50');
        $like = '%' . $customerQ . '%';
        $stmt->execute([$like, $like, $like, $like]);
        $customers = $stmt->fetchAll();
    } else {
        $customers = $pdo->query('SELECT id, full_name, national_code, mobile, job_title, customer_tracking_code, sync_status, created_at, updated_at FROM portal_customers_staging ORDER BY id DESC LIMIT 50')->fetchAll();
    }
    if ($requestQ !== '') {
        $stmt = $pdo->prepare('SELECT id, mobile, vehicle_brand, vehicle_model, vehicle_type, service_type, sync_status, sync_error, created_at FROM portal_service_requests_staging WHERE mobile LIKE ? OR vehicle_brand LIKE ? OR vehicle_model LIKE ? OR plate_number LIKE ? ORDER BY id DESC LIMIT 50');
        $like = '%' . $requestQ . '%';
        $stmt->execute([$like, $like, $like, $like]);
        $requests = $stmt->fetchAll();
    } else {
        $requests = $pdo->query('SELECT id, mobile, vehicle_brand, vehicle_model, vehicle_type, service_type, sync_status, sync_error, created_at FROM portal_service_requests_staging ORDER BY id DESC LIMIT 50')->fetchAll();
    }
    $otps = $pdo->query('SELECT id, mobile, purpose, attempt_count, is_used, expires_at, created_at, used_at FROM otp_verifications ORDER BY id DESC LIMIT 50')->fetchAll();
    if ($staffQ !== '') {
        $stmt = $pdo->prepare('SELECT id, full_name, username, role_name, is_master_admin, is_active, created_at FROM staff_users WHERE full_name LIKE ? OR username LIKE ? OR role_name LIKE ? ORDER BY id DESC LIMIT 50');
        $like = '%' . $staffQ . '%';
        $stmt->execute([$like, $like, $like]);
        $staffUsers = $stmt->fetchAll();
    } else {
        $staffUsers = $pdo->query('SELECT id, full_name, username, role_name, is_master_admin, is_active, created_at FROM staff_users ORDER BY id DESC LIMIT 50')->fetchAll();
    }
    renderHeader('پنل Pending ادمین', 'بررسی سریع داده‌های cPanel');
    renderFlashes();
    ?>
    <main class="dashboard">
      <div class="action-row"><a class="btn ghost" href="admin-logout.php">خروج ادمین</a></div>
      <section class="card wide-card">
        <h2>مشتریان Pending</h2>
        <form class="search-bar" method="get"><input name="customer_q" value="<?= e($customerQ) ?>" placeholder="جستجوی مشتری، موبایل، کد ملی"><button class="btn secondary" type="submit">جستجو</button></form>
        <div class="table-wrap"><table><thead><tr><th>ID</th><th>نام</th><th>موبایل</th><th>کد رهگیری</th><th>وضعیت</th><th>ویرایش سریع</th></tr></thead><tbody>
        <?php foreach ($customers as $row): ?>
          <tr>
            <td class="number"><?= e((string)$row['id']) ?></td>
            <td><?= e($row['full_name']) ?></td>
            <td class="mobile-field"><?= e($row['mobile']) ?></td>
            <td class="tracking-code"><?= e($row['customer_tracking_code'] ?? '-') ?></td>
            <td><?= e($row['sync_status']) ?></td>
            <td>
              <form method="post" class="inline-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_customer">
                <input type="hidden" name="id" value="<?= e((string)$row['id']) ?>">
                <input name="full_name" value="<?= e($row['full_name']) ?>" placeholder="نام">
                <input class="national-code-field input-number" name="national_code" value="<?= e($row['national_code'] ?? '') ?>" placeholder="کد ملی">
                <input name="job_title" value="<?= e($row['job_title'] ?? '') ?>" placeholder="شغل">
                <input name="sync_status" value="<?= e($row['sync_status']) ?>" placeholder="وضعیت">
                <button class="btn small" type="submit">ذخیره</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody></table></div>
      </section>
      <section class="card wide-card">
        <h2>درخواست‌های خدمات Pending</h2>
        <form class="search-bar" method="get"><input name="request_q" value="<?= e($requestQ) ?>" placeholder="جستجوی درخواست، موبایل، خودرو، پلاک"><button class="btn secondary" type="submit">جستجو</button></form>
        <div class="table-wrap"><table><thead><tr><th>ID</th><th>موبایل</th><th>خودرو</th><th>نوع</th><th>وضعیت</th><th>ویرایش سریع</th></tr></thead><tbody>
        <?php foreach ($requests as $row): ?>
          <tr>
            <td class="number"><?= e((string)$row['id']) ?></td><td class="mobile-field"><?= e($row['mobile']) ?></td><td><?= e($row['vehicle_brand'] . ' ' . $row['vehicle_model']) ?></td><td><?= e($row['vehicle_type']) ?></td><td><?= e($row['sync_status']) ?></td>
            <td>
              <form method="post" class="inline-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_request">
                <input type="hidden" name="id" value="<?= e((string)$row['id']) ?>">
                <input name="service_type" value="<?= e($row['service_type'] ?? '') ?>" placeholder="نوع خدمت">
                <input name="sync_status" value="<?= e($row['sync_status']) ?>" placeholder="وضعیت">
                <input name="sync_error" value="<?= e($row['sync_error'] ?? '') ?>" placeholder="خطا / توضیح">
                <button class="btn small" type="submit">ذخیره</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody></table></div>
      </section>
      <section class="card wide-card">
        <h2>آخرین OTPها</h2>
        <div class="table-wrap"><table><thead><tr><th>ID</th><th>موبایل</th><th>هدف</th><th>تلاش</th><th>مصرف</th><th>انقضا</th><th>ایجاد</th></tr></thead><tbody>
        <?php foreach ($otps as $row): ?>
          <tr><td class="number"><?= e((string)$row['id']) ?></td><td class="mobile-field"><?= e($row['mobile']) ?></td><td><?= e($row['purpose']) ?></td><td class="numeric-badge"><?= e((string)$row['attempt_count']) ?></td><td><?= ((int)$row['is_used'] === 1) ? 'بله' : 'خیر' ?></td><td class="number"><?= e($row['expires_at']) ?></td><td class="number"><?= e($row['created_at']) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
      </section>
      <section class="card wide-card">
        <h2>کاربران پرسنل</h2>
        <form class="search-bar" method="get"><input name="staff_q" value="<?= e($staffQ) ?>" placeholder="جستجوی پرسنل"><button class="btn secondary" type="submit">جستجو</button><a class="btn ghost" href="staff-users.php">مدیریت کامل کاربران</a></form>
        <div class="table-wrap"><table><thead><tr><th>ID</th><th>نام</th><th>کاربری</th><th>نقش</th><th>مدیر</th><th>فعال</th></tr></thead><tbody>
        <?php foreach ($staffUsers as $row): ?>
          <tr><td class="number"><?= e((string)$row['id']) ?></td><td><?= e($row['full_name']) ?></td><td><?= e($row['username']) ?></td><td><?= e($row['role_name']) ?></td><td><?= ((int)$row['is_master_admin'] === 1) ? 'بله' : 'خیر' ?></td><td><?= ((int)$row['is_active'] === 1) ? 'بله' : 'خیر' ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
      </section>
    </main>
    <?php
    renderFooter();
} catch (Throwable $e) {
    showErrorPage('خطا در پنل ادمین.', $e->getMessage());
}
