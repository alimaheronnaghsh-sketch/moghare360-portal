<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Application Shell Demo
 *
 * Mission 32 — Dashboard demo using Design System + Shell include.
 * No SQL. No database. No auth change.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';

$roleMode = isset($_GET['role']) ? (string)$_GET['role'] : 'owner';
$roleMode = moghare360_shell_normalize_role_mode($roleMode);
$activeModule = 'dashboard';

moghare360_render_shell_start('داشبورد Soft Run', $activeModule, $roleMode);
?>

<div class="m360-page-header">
  <div class="m360-page-header-eyebrow">Mission 32 — Application Shell</div>
  <h2 class="m360-page-header-title">داشبورد عملیات امروز</h2>
  <p class="m360-page-header-meta">
    نمایش UI فقط — نقش فعلی:
    <span class="m360-badge m360-badge-accent"><?= moghare360_shell_h(strtoupper($roleMode)) ?></span>
    — برای تست نقش‌های دیگر:
    <span class="m360-ltr">?role=service|reception|finance|qc</span>
  </p>
</div>

<div class="m360-page-toolbar">
  <div class="m360-toolbar-actions">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="?role=owner">Owner</a>
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="?role=service">Service</a>
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="?role=reception">Reception</a>
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="?role=finance">Finance</a>
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="?role=qc">QC</a>
  </div>
  <div>
    <span class="m360-badge m360-badge-success">SOFT RUN READY</span>
    <span class="m360-badge m360-badge-primary">Shell Demo</span>
  </div>
</div>

<section class="m360-module-section">
  <div class="m360-shell-section-header">
    <div>
      <h3 class="m360-shell-section-title">شاخص‌های کلیدی</h3>
      <p class="m360-shell-section-subtitle">KPI Cards — داده نمایشی</p>
    </div>
  </div>
  <div class="m360-grid m360-grid-4">
    <div class="m360-kpi-card">
      <div class="m360-kpi-label">کارت کار فعال</div>
      <div class="m360-kpi-value m360-num">8</div>
      <div class="m360-kpi-meta">JobCards today</div>
    </div>
    <div class="m360-kpi-card is-accent">
      <div class="m360-kpi-label">در انتظار قطعه</div>
      <div class="m360-kpi-value m360-num">2</div>
      <div class="m360-kpi-meta">WAITING_PARTS</div>
    </div>
    <div class="m360-kpi-card is-warning">
      <div class="m360-kpi-label">QC معلق</div>
      <div class="m360-kpi-value m360-num">1</div>
      <div class="m360-kpi-meta">QC_PENDING</div>
    </div>
    <div class="m360-kpi-card is-success">
      <div class="m360-kpi-label">آماده تحویل</div>
      <div class="m360-kpi-value m360-num">3</div>
      <div class="m360-kpi-meta">READY / RELEASED</div>
    </div>
  </div>
</section>

<section class="m360-module-section">
  <div class="m360-shell-section-header">
    <div>
      <h3 class="m360-shell-section-title">گردش کار امروز</h3>
      <p class="m360-shell-section-subtitle">Today Workflow Cards</p>
    </div>
  </div>
  <div class="m360-shell-card-grid">
    <article class="m360-shell-module-card m360-shell-workflow-card">
      <h4 class="m360-shell-module-card-title">پذیرش خودرو</h4>
      <p class="m360-shell-module-card-text">ثبت مشتری و خودرو — نمونه کارت گردش کار پذیرش.</p>
      <p><span class="m360-badge m360-badge-neutral">RECEIVED</span></p>
    </article>
    <article class="m360-shell-module-card m360-shell-workflow-card">
      <h4 class="m360-shell-module-card-title">عملیات سرویس</h4>
      <p class="m360-shell-module-card-text">پیشرفت تعمیر و مصرف قطعه — لینک به ماژول‌های M20/M24.</p>
      <p><span class="m360-badge m360-badge-primary">IN_SERVICE</span></p>
    </article>
    <article class="m360-shell-module-card m360-shell-workflow-card">
      <h4 class="m360-shell-module-card-title">پرداخت مشتری</h4>
      <p class="m360-shell-module-card-text">پیش‌پرداخت / تسویه — نمایشی از Mission 28.</p>
      <p><span class="m360-badge m360-badge-success">RECEIVED</span></p>
    </article>
    <article class="m360-shell-module-card m360-shell-workflow-card">
      <h4 class="m360-shell-module-card-title">QC و تحویل</h4>
      <p class="m360-shell-module-card-text">کنترل کیفیت و آزادسازی تحویل — Mission 30.</p>
      <p><span class="m360-badge m360-badge-accent">READY</span></p>
    </article>
  </div>
</section>

<section class="m360-module-section">
  <div class="m360-shell-section-header">
    <div>
      <h3 class="m360-shell-section-title">ماژول‌های سیستم</h3>
      <p class="m360-shell-section-subtitle">Module Cards — فیلتر شده بر اساس نقش placeholder</p>
    </div>
  </div>
  <div class="m360-shell-card-grid">
    <?php foreach (moghare360_get_shell_menu($roleMode) as $moduleKey => $module): ?>
      <?php if ($moduleKey === 'dashboard') { continue; } ?>
      <article class="m360-shell-module-card">
        <h4 class="m360-shell-module-card-title"><?= moghare360_shell_h((string)$module['label']) ?></h4>
        <p class="m360-shell-module-card-text"><?= moghare360_shell_h((string)$module['label_en']) ?> — placeholder navigation card</p>
        <p><a class="m360-btn m360-btn-ghost m360-btn-sm" href="<?= moghare360_shell_h((string)$module['href']) ?>">باز کردن</a></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="m360-module-section">
  <div class="m360-grid m360-grid-2">
    <div class="m360-card">
      <div class="m360-card-header">
        <h3 class="m360-card-title">وضعیت سریع کارت کار</h3>
        <p class="m360-card-subtitle">JobCard Quick Status</p>
      </div>
      <div class="m360-card-body">
        <p style="margin:0 0 0.75rem;"><strong>JC-2026-0001</strong> — <span class="m360-badge m360-badge-success">QC_PASSED</span></p>
        <p style="margin:0 0 0.75rem;"><strong>JC-2026-0002</strong> — <span class="m360-badge m360-badge-warning">WAITING_PARTS</span></p>
        <p style="margin:0;"><strong>JC-2026-0003</strong> — <span class="m360-badge m360-badge-primary">IN_SERVICE</span></p>
      </div>
    </div>
    <div class="m360-card">
      <div class="m360-card-header">
        <h3 class="m360-card-title">وضعیت Soft Run</h3>
        <p class="m360-card-subtitle">Soft Run Status</p>
      </div>
      <div class="m360-card-body">
        <div class="m360-alert m360-alert-success" style="margin-bottom:1rem;">
          <div>
            <div class="m360-alert-title">SOFT RUN READY</div>
            نمونه وضعیت آماده — فقط UI
          </div>
        </div>
        <p style="margin:0;font-size:0.875rem;color:#525252;">Customer ✓ · Vehicle ✓ · JobCard ✓ · QC PASSED ✓ · Delivery READY ✓</p>
      </div>
    </div>
  </div>
</section>

<div class="m360-diagnostic-block">
  <h3 class="m360-diagnostic-block-title">Shell Diagnostic</h3>
  <p>Application Shell Mission 32 — sidebar/topbar/navigation demo. No SQL. No database write. No auth/login/permission model change.</p>
</div>

<?php
moghare360_render_shell_end();
