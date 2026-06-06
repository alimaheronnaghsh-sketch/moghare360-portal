<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
ensureSessionStarted();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $password = (string)($_POST['admin_password'] ?? '');
    if ($adminPassword !== 'CHANGE_ME_ADMIN_PASSWORD' && hash_equals($adminPassword, $password)) {
        $_SESSION['admin_ok'] = true;
        redirect('admin-pending.php');
    }
    flash('رمز ادمین درست نیست یا هنوز در config.php تنظیم نشده است.', 'bad');
}

renderHeader('ورود ادمین موقت', 'بررسی داده‌های Pending');
renderFlashes();
?>
<main class="auth-wrap">
  <form class="card form-card" method="post" action="admin-login.php">
    <h2>ورود ادمین</h2>
    <?= csrfField() ?>
    <label>رمز ادمین از config.php
      <input name="admin_password" type="password" required>
    </label>
    <button class="btn primary" type="submit">ورود</button>
  </form>
</main>
<?php renderFooter(); ?>
