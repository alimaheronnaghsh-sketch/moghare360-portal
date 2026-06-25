<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
ensureSessionStarted();
$staff = requireStaffLogin();
renderHeader('پروفایل پرسنل', 'کارتابل شخصی');
?>
<main class="auth-wrap wide-auth">
  <section class="card profile-summary">
    <div class="avatar"><?= e(initialLetter((string)($staff['full_name'] ?? 'P'))) ?></div>
    <div>
      <h2><?= e($staff['full_name'] ?? '-') ?></h2>
      <p>نام کاربری: <?= e($staff['username'] ?? '-') ?></p>
      <p>نقش: <?= e($staff['role_name'] ?? '-') ?></p>
      <span class="pill">وضعیت: فعال</span>
    </div>
  </section>
  <section class="card">
    <h2>کارتابل شخصی</h2>
    <div class="module-grid compact">
      <article class="module-card placeholder"><strong>وظایف امروز</strong><span>آماده اتصال</span></article>
      <article class="module-card placeholder"><strong>اعلان‌ها</strong><span>آماده اتصال</span></article>
      <article class="module-card placeholder"><strong>درخواست‌ها</strong><span>آماده اتصال</span></article>
    </div>
    <a class="btn ghost" href="staff-dashboard.php">بازگشت به داشبورد</a>
  </section>
</main>
<?php renderFooter(); ?>
