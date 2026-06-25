<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
ensureSessionStarted();
renderHeader('ورود پرسنل', 'کارتابل داخلی MOGHARE360');
renderFlashes();
?>
<main class="auth-wrap">
  <form class="card form-card" method="post" action="staff-auth.php">
    <h2>ورود پرسنل</h2>
    <?= csrfField() ?>
    <label>نام کاربری
      <input name="username" required autocomplete="username">
    </label>
    <label>رمز عبور
      <input name="password" type="password" required autocomplete="current-password">
    </label>
    <button class="btn primary" type="submit">ورود به کارتابل</button>
  </form>
</main>
<?php renderFooter(); ?>
