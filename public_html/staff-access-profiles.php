<?php
declare(strict_types=1);
require_once __DIR__ . '/access-control.php';

$staff = requireStaffLogin();
if (!isMasterAdmin($staff) && !accessHas('admin.access')) {
    showErrorPage('فقط مدیر ارشد می‌تواند سطح دسترسی تعریف کند.');
}

$pdo = getPdo();
$profiles = $pdo->query('SELECT * FROM access_profiles ORDER BY id')->fetchAll();
$permissions = $pdo->query('SELECT * FROM access_permissions WHERE is_active = 1 ORDER BY module_key, sort_order, id')->fetchAll();
$users = $pdo->query('SELECT id, full_name, username, role_name, is_active FROM staff_users ORDER BY full_name, username')->fetchAll();
$profilePermissions = $pdo->query('SELECT profile_id, permission_key FROM access_profile_permissions')->fetchAll();
$userProfiles = $pdo->query('SELECT staff_user_id, access_profile_id FROM staff_user_access_profiles')->fetchAll();

$ppMap = [];
foreach ($profilePermissions as $row) {
    $ppMap[(int)$row['profile_id']][(string)$row['permission_key']] = true;
}
$upMap = [];
foreach ($userProfiles as $row) {
    $upMap[(int)$row['staff_user_id']][(int)$row['access_profile_id']] = true;
}
$moduleNames = accessModuleGroups();
$grouped = [];
foreach ($permissions as $perm) {
    $grouped[(string)$perm['module_key']][] = $perm;
}

renderHeader('تعریف سطح دسترسی', 'روشن/خاموش کردن حوزه فعالیت پرسنل');
renderFlashes();
?>
<main class="form-shell">
  <section class="panel-card wide-card">
    <h2>ساخت سطح دسترسی جدید</h2>
    <form method="post" action="staff-access-save.php" class="form-grid">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create_profile">
      <div class="field">
        <label>نام سطح دسترسی *</label>
        <input name="profile_name" required placeholder="مثلاً انبار بدون مبلغ">
      </div>
      <div class="field full">
        <label>توضیحات</label>
        <textarea name="description"></textarea>
      </div>
      <div class="actions full"><button class="btn primary" type="submit">ایجاد سطح دسترسی</button></div>
    </form>
  </section>

  <?php foreach ($profiles as $profile): ?>
    <section class="panel-card wide-card">
      <div class="section-head">
        <h2><?= e($profile['profile_name']) ?></h2>
        <span class="badge <?= ((int)$profile['is_active'] === 1) ? 'ok' : 'muted' ?>"><?= ((int)$profile['is_active'] === 1) ? 'فعال' : 'غیرفعال' ?></span>
      </div>
      <p class="muted"><?= e($profile['description'] ?? '') ?></p>
      <form method="post" action="staff-access-save.php">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save_profile_permissions">
        <input type="hidden" name="profile_id" value="<?= e((string)$profile['id']) ?>">
        <div class="access-grid">
          <?php foreach ($grouped as $moduleKey => $items): ?>
            <div class="access-module-card">
              <h3><?= e($moduleNames[$moduleKey] ?? $moduleKey) ?></h3>
              <?php foreach ($items as $perm): ?>
                <?php $checked = !empty($ppMap[(int)$profile['id']][(string)$perm['permission_key']]); ?>
                <label class="toggle-line">
                  <input type="checkbox" name="permissions[]" value="<?= e($perm['permission_key']) ?>" <?= $checked ? 'checked' : '' ?>>
                  <span><?= e($perm['permission_label']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="actions"><button class="btn primary" type="submit">ذخیره دسترسی‌های این سطح</button></div>
      </form>
    </section>
  <?php endforeach; ?>

  <section class="panel-card wide-card">
    <h2>اتصال سطح دسترسی به پرسنل</h2>
    <form method="post" action="staff-access-save.php">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="assign_profiles">
      <div class="access-user-list">
        <?php foreach ($users as $user): ?>
          <div class="access-user-card">
            <h3><?= e($user['full_name']) ?> <small class="num">@<?= e($user['username']) ?></small></h3>
            <p class="muted"><?= e($user['role_name']) ?></p>
            <?php foreach ($profiles as $profile): ?>
              <?php $checked = !empty($upMap[(int)$user['id']][(int)$profile['id']]); ?>
              <label class="toggle-line">
                <input type="checkbox" name="user_profiles[<?= e((string)$user['id']) ?>][]" value="<?= e((string)$profile['id']) ?>" <?= $checked ? 'checked' : '' ?>>
                <span><?= e($profile['profile_name']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="actions"><button class="btn primary" type="submit">ذخیره دسترسی پرسنل</button></div>
    </form>
  </section>
</main>
<?php renderFooter(); ?>
