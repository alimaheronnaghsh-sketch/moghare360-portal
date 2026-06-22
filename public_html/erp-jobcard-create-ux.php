<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP JobCard Create UX Guide
 *
 * Mission 33 — Reception flow guide. Links to controlled create. No new write logic.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'reception');
$activeModule = 'jobcards';

moghare360_render_shell_start('ثبت کارت کار — راهنمای پذیرش', $activeModule, $roleMode);
m33_ux_render_jobcard_css_link();
?>

<div class="m33-jc-board">
  <div class="m33-jc-page-nav">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-jobcard-workbench.php?role=<?= m33_ux_h(rawurlencode($roleMode)) ?>">بازگشت به میز کار</a>
  </div>

  <div class="m360-page-header">
    <div class="m360-page-header-eyebrow">Mission 33 — Reception Flow</div>
    <h2 class="m360-page-header-title">ثبت کارت کار جدید</h2>
    <p class="m360-page-header-meta">راهنمای UX — ثبت واقعی فقط از صفحه controlled prototype انجام می‌شود</p>
  </div>

  <div class="m360-alert m360-alert-warning">
    <div>
      <div class="m360-alert-title">فرم نمایشی — بدون ارسال</div>
      این صفحه فقط جریان پذیرش را نشان می‌دهد. برای ثبت واقعی از دکمه زیر استفاده کنید.
    </div>
  </div>

  <div class="m360-page-toolbar">
    <a class="m360-btn m360-btn-primary" href="erp-customer-vehicle-create.php">۱. ثبت مشتری و خودرو (M15)</a>
    <a class="m360-btn m360-btn-primary" href="erp-jobcard-create.php">۲. ثبت کارت کار (M17 Controlled)</a>
  </div>

  <section class="m360-module-section">
    <h3 class="m360-shell-section-title">مراحل پذیرش روزانه</h3>
    <div class="m33-jc-create-flow">
      <div class="m33-jc-create-step">
        <h4>مشتری</h4>
        <p>ثبت یا انتخاب مشتری — نام، موبایل، کد ملی</p>
      </div>
      <div class="m33-jc-create-step">
        <h4>خودرو</h4>
        <p>ثبت خودرو — پلاک، برند، مدل، VIN</p>
      </div>
      <div class="m33-jc-create-step">
        <h4>ارتباط</h4>
        <p>ایجاد relation مشتری-خودرو (OWNER)</p>
      </div>
      <div class="m33-jc-create-step">
        <h4>کارت کار</h4>
        <p>انتخاب relation و ثبت JobCard با وضعیت RECEIVED</p>
      </div>
    </div>
  </section>

  <div class="m360-grid m360-grid-2">
    <div class="m33-jc-mock-form" aria-hidden="true">
      <h3 style="margin:0 0 1rem;font-size:1rem;">نمونه فرم پذیرش (Mock)</h3>
      <div class="m33-jc-mock-field">
        <label>relation_id — مشتری / خودرو</label>
        <select disabled><option>انتخاب ارتباط فعال...</option></select>
      </div>
      <div class="m33-jc-mock-field">
        <label>وضعیت اولیه</label>
        <select disabled><option>RECEIVED</option></select>
      </div>
      <div class="m33-jc-mock-field">
        <label>کیلومتر ورود</label>
        <input type="text" value="45000" disabled>
      </div>
      <div class="m33-jc-mock-field">
        <label>شکایت مشتری</label>
        <textarea rows="3" disabled>صدای غیرعادی موتور</textarea>
      </div>
      <button type="button" class="m360-btn m360-btn-secondary" disabled>ثبت — غیرفعال (Mock)</button>
    </div>

    <div class="m33-jc-binding-grid">
      <article class="m33-jc-binding-card is-customer">
        <p class="m33-jc-binding-title">Customer + Vehicle Binding</p>
        <h4 class="m33-jc-binding-name">relation_id</h4>
        <p class="m33-jc-binding-meta">هر JobCard به customer_id و vehicle_id از طریق relation متصل است. ابتدا M15 سپس M17.</p>
      </article>
      <article class="m33-jc-tech-panel">
        <h4 class="m33-jc-tech-panel-title">پس از ثبت</h4>
        <ul class="m33-jc-tech-list">
          <li><span>عملیات سرویس</span><a href="erp-service-operation-create.php">M20</a></li>
          <li><span>مصرف قطعه</span><a href="erp-jobcard-part-use.php">M24</a></li>
          <li><span>پرداخت</span><a href="erp-payment-create.php">M28</a></li>
        </ul>
      </article>
    </div>
  </div>

  <div class="m360-diagnostic-block">
    <p style="margin:0;">Create UX — no write on this page. Controlled create: <a href="erp-jobcard-create.php">erp-jobcard-create.php</a></p>
  </div>
</div>

<?php moghare360_render_shell_end(); ?>
