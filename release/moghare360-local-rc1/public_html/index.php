<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

renderHeader('MOGHARE360', 'پرتال خدمات خودرو مقاره', false);
?>
<main class="home-wrap">
  <section class="home-panel">
    <div>
      <p class="eyebrow">MOGHARE360 Portal</p>
      <h1>سامانه آنلاین مشتریان و پرسنل مقاره موتورز</h1>
      <p>
        درخواست پذیرش خودرو فقط پس از ورود مشتری و تکمیل پروفایل در حساب کاربری انجام می‌شود.
        این طراحی برای کاهش خطا، افزایش کنترل فرایند و ثبت حقوقی کامل پرونده است.
      </p>
    </div>
    <div class="home-actions two-only">
      <a class="btn primary" href="customer-login.php">ورود / ثبت‌نام مشتریان</a>
      <a class="btn secondary" href="staff-login.php">ورود پرسنل</a>
    </div>
  </section>
</main>
<?php renderFooter(); ?>
