<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Local Master Entry (root landing).
 * Legacy Codex portal bootstrap removed — safe local ERP entry only.
 */

require_once __DIR__ . '/includes/moghare360-v1-master-console-helper.php';

v1mc_render_head('MOGHARE360 — ورود نرم‌افزار مادر');
?>
<div class="v1mc-banner">MOGHARE360 V1 — Local Master ERP Entry · SQL Server SaaS · Legacy MySQL portal inactive</div>
<div class="v1mc-hero">
  <h1 style="margin:0 0 .5rem;font-size:1.35rem">ورود نرم‌افزار مادر (Master ERP)</h1>
  <p style="margin:0;opacity:.92;font-size:.95rem">
    این آدرس ریشه برای ERP داخلی V1 است — نه mirror عمومی cPanel.
    از کنسول مادر برای دسترسی واحدبه‌واحد و بررسی مسیرهای دسترسی استفاده کنید.
  </p>
</div>

<nav class="v1mc-nav">
  <a href="erp-v1-master-console.php">کنسول مادر V1</a>
  <a href="erp-v1-unit-access-console.php">کنترل دسترسی واحدها</a>
  <a href="erp-moghare-ready.php">Moghare Ready</a>
  <a href="erp-soft-run-home.php?role=owner">Soft Run Home</a>
</nav>

<div class="v1mc-grid">
  <article class="v1mc-card">
    <span class="v1mc-badge v1mc-badge-ready">READY</span>
    <h3>شروع سریع</h3>
    <p>نمای واحدهای ERP، لینک صفحات اصلی، وضعیت تقریبی و مسیر تست.</p>
    <div class="v1mc-links">
      <a href="erp-v1-master-console.php">باز کردن Master Console</a>
    </div>
  </article>
  <article class="v1mc-card">
    <span class="v1mc-badge v1mc-badge-ready">READY</span>
    <h3>کنترل Production</h3>
    <p>Signoff و Fix Register پس از اجرای V1 — بدون تغییر auth core.</p>
    <div class="v1mc-links">
      <a href="erp-v1-production-signoff.php">Production Signoff</a>
      <a href="erp-v1-fix-register.php">Fix Register</a>
    </div>
  </article>
  <article class="v1mc-card">
    <span class="v1mc-badge v1mc-badge-check">CHECK</span>
    <h3>ورود پرسنل / مالک</h3>
    <p>مسیرهای ورود موجود در پروژه — ایجاد کاربر واقعی فقط از private JSON روی سرور.</p>
    <div class="v1mc-links">
      <a href="erp-v1-unit-access-console.php">مشاهده مسیرهای دسترسی</a>
      <a href="api/auth/staff-login.php">API staff-login</a>
    </div>
  </article>
</div>

<p class="v1mc-muted">سایت عمومی mirror: بسته جدا — از این index برای پذیرش مشتری Codex/MySQL استفاده نکنید.</p>
<?php v1mc_render_foot(); ?>
