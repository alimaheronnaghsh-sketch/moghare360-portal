<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

$mobile = normalizeMobile((string)($_GET['mobile'] ?? ''));
$purpose = (string)($_GET['purpose'] ?? 'customer_login');
if (!isValidMobile($mobile) || !validPurpose($purpose)) {
    flash('درخواست تایید معتبر نیست.', 'bad');
    redirect('customer-login.php');
}

renderHeader('تایید کد ورود', 'OTP');
renderFlashes();
?>
<main class="auth-wrap">
  <section class="card flow-card">
    <h2>تایید هویت مشتری</h2>
    <ol class="flow-steps">
      <li>ورود با موبایل</li>
      <li class="active">تایید کد پیامک</li>
      <li>ورود به پروفایل و پذیرش</li>
    </ol>
  </section>

  <form class="card form-card" method="post" action="check-otp.php">
    <h2>کد تایید را وارد کنید</h2>
    <?= csrfField() ?>
    <input type="hidden" name="purpose" value="<?= e($purpose) ?>">
    <label>شماره موبایل
      <input class="mobile-field input-number" name="mobile" value="<?= e($mobile) ?>" readonly>
    </label>
    <label>کد تایید
      <input class="otp-code-input input-number" name="otp_code" inputmode="numeric" maxlength="5" required placeholder="کد ۵ رقمی">
    </label>
    <div class="action-row">
      <button class="btn primary" type="submit">تایید و ورود</button>
      <a class="btn ghost" href="customer-login.php">بازگشت</a>
    </div>
  </form>

  <form class="card form-card compact-form" method="post" action="send-otp.php">
    <?= csrfField() ?>
    <input type="hidden" name="purpose" value="<?= e($purpose) ?>">
    <input type="hidden" name="mobile" value="<?= e($mobile) ?>">
    <button class="btn secondary" type="submit">ارسال مجدد کد تایید</button>
  </form>
</main>
<?php renderFooter(); ?>
