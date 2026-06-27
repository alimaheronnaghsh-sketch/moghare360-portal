<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Customer & Vehicle Create UX Guide
 *
 * Mission 34 — Reception flow guide. Links to M15 controlled create. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-customer-vehicle-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'reception');
$activeModule = 'customers';

moghare360_render_shell_start('راهنمای ثبت مشتری و خودرو', $activeModule, $roleMode);
m34_ux_render_cv_css_link();
?>

<div class="m34-cv-board">
  <div class="m34-cv-page-nav">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-customer-vehicle-workbench.php?role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">میز کار</a>
  </div>

  <div class="m360-page-header">
    <div class="m360-page-header-eyebrow">Mission 34 — Reception Guide</div>
    <h2 class="m360-page-header-title">ثبت مشتری و خودرو</h2>
    <p class="m360-page-header-meta">Customer → Phone → Vehicle → Relation → JobCard</p>
  </div>

  <div class="m360-alert m360-alert-warning">
    <div>
      <div class="m360-alert-title">فرم نمایشی — بدون ارسال</div>
      ثبت واقعی فقط از صفحه controlled prototype Mission 15 انجام می‌شود.
    </div>
  </div>

  <div class="m360-page-toolbar">
    <a class="m360-btn m360-btn-primary" href="erp-customer-vehicle-create.php">ثبت Controlled (M15)</a>
    <a class="m360-btn m360-btn-secondary" href="erp-jobcard-create-ux.php?role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">مرحله بعد: JobCard UX</a>
  </div>

  <section class="m360-module-section">
    <h3 class="m360-shell-section-title">جریان ثبت</h3>
    <div class="m34-cv-create-flow">
      <div class="m34-cv-create-step"><h4>Customer</h4><p>ثبت نام، کد ملی، آدرس</p></div>
      <div class="m34-cv-create-step"><h4>Phone</h4><p>موبایل اصلی و شماره‌های اضافی</p></div>
      <div class="m34-cv-create-step"><h4>Vehicle</h4><p>پلاک، برند، مدل، VIN</p></div>
      <div class="m34-cv-create-step"><h4>Relation</h4><p>ارتباط OWNER بین مشتری و خودرو</p></div>
      <div class="m34-cv-create-step"><h4>JobCard</h4><p>انتخاب relation و ثبت کارت کار</p></div>
    </div>
  </section>

  <div class="m360-grid m360-grid-2">
    <div class="m34-cv-mock-form m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">نمونه فرم مشتری (Mock)</h3></div>
      <div class="m360-card-body">
        <div style="margin-bottom:0.75rem;"><label style="font-size:0.8rem;font-weight:600;">نام کامل</label><input type="text" value="علی رضایی" disabled></div>
        <div style="margin-bottom:0.75rem;"><label style="font-size:0.8rem;font-weight:600;">موبایل</label><input type="text" value="09121234567" disabled></div>
        <div><label style="font-size:0.8rem;font-weight:600;">شهر</label><input type="text" value="تهران" disabled></div>
      </div>
    </div>
    <div class="m34-cv-mock-form m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">نمونه فرم خودرو (Mock)</h3></div>
      <div class="m360-card-body">
        <div style="margin-bottom:0.75rem;"><label style="font-size:0.8rem;font-weight:600;">پلاک</label><input type="text" value="12ب345-67" disabled></div>
        <div style="margin-bottom:0.75rem;"><label style="font-size:0.8rem;font-weight:600;">برند / مدل</label><input type="text" value="پژو 206" disabled></div>
        <div><label style="font-size:0.8rem;font-weight:600;">VIN</label><input type="text" value="VF3XXXXXXXXXXXX" disabled></div>
      </div>
    </div>
  </div>

  <div class="m360-diagnostic-block">
    <p style="margin:0;">Create UX — no write. Controlled: <a href="erp-customer-vehicle-create.php">erp-customer-vehicle-create.php</a></p>
  </div>
</div>

<?php moghare360_render_shell_end(); ?>
