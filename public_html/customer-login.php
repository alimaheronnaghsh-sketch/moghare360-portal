<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

renderHeader('ورود / ثبت‌نام مشتریان', 'ورود امن با کد تایید');
renderFlashes();
?>
<main class="auth-wrap">
  <section class="card flow-card">
    <h2>شروع سریع پذیرش مشتری</h2>
    <p class="muted">فقط شماره موبایل را وارد کنید. سیستم به‌صورت خودکار شما را به مرحله بعد هدایت می‌کند.</p>
    <ol class="flow-steps">
      <li class="active">ورود با موبایل</li>
      <li>تایید کد پیامک</li>
      <li>پروفایل و پذیرش خودرو</li>
    </ol>
  </section>

  <form class="card form-card" method="post" action="send-otp.php">
    <h2>ورود مشتری</h2>
    <p class="muted">کد تایید به همین شماره ارسال می‌شود.</p>
    <?= csrfField() ?>
    <input type="hidden" name="purpose" value="customer_login">
    <label>شماره موبایل
      <input class="mobile-field input-number" name="mobile" inputmode="tel" required placeholder="0912xxxxxxx" autocomplete="tel-national">
    </label>
    <div class="action-row">
      <button class="btn primary" type="submit">ارسال کد تایید</button>
      <a class="btn ghost" href="index.php">بازگشت</a>
    </div>
  </form>
</main>
<?php renderFooter(); ?>
