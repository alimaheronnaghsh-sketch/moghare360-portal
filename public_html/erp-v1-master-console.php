<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-v1-master-console-helper.php';

/** @return list<array<string, string>> */
function v1mc_master_units(): array
{
    return [
        [
            'name' => 'Owner / System',
            'primary' => 'erp-operational-command-center.php',
            'test' => 'erp-product-status.php',
            'note' => 'فرماندهی عملیاتی، وضعیت محصول و نمای مالک سیستم.',
        ],
        [
            'name' => 'Customer / CRM',
            'primary' => 'erp-customer-vehicle-workbench.php',
            'test' => 'erp-crm-report.php',
            'note' => 'مشتری، خودرو و گزارش CRM — بدون portal قدیمی MySQL.',
        ],
        [
            'name' => 'Reception / JobCard',
            'primary' => 'erp-jobcard-command-center.php',
            'test' => 'erp-jobcard-readonly-list.php',
            'note' => 'پذیرش و کارتابل جاب‌کارت.',
        ],
        [
            'name' => 'Service Operations',
            'primary' => 'erp-operation-control-center.php',
            'test' => 'erp-service-operation-readonly-list.php',
            'note' => 'عملیات سرویس و کنترل اجرا.',
        ],
        [
            'name' => 'Parts / Inventory',
            'primary' => 'erp-stock-board.php',
            'test' => 'erp-stock-readonly-list.php',
            'note' => 'انبار، موجودی و فشار موجودی.',
        ],
        [
            'name' => 'Purchase',
            'primary' => 'erp-purchase-request-readonly-list.php',
            'test' => 'erp-purchase-request-detail.php',
            'note' => 'درخواست خرید و پیگیری.',
        ],
        [
            'name' => 'Finance / Payments',
            'primary' => 'erp-payment-readonly-list.php',
            'test' => 'erp-financial-preview-report.php',
            'note' => 'پرداخت‌ها و پیش‌نمایش مالی.',
        ],
        [
            'name' => 'HR / Internal Admin',
            'primary' => 'erp-hr-dashboard.php',
            'test' => 'erp-staff-performance-preview.php',
            'note' => 'منابع انسانی و عملکرد پرسنل.',
        ],
        [
            'name' => 'QC / Delivery',
            'primary' => 'erp-qc-check.php',
            'test' => 'erp-delivery-control.php',
            'note' => 'کنترل کیفیت و تحویل خودرو.',
        ],
        [
            'name' => 'SaaS / Website / Mirror',
            'primary' => 'saas-health.php',
            'test' => 'moghare360-release-download.php',
            'note' => 'سلامت SaaS، API و بسته mirror — نه runtime cPanel قدیمی.',
        ],
        [
            'name' => 'Access / Users / Permissions',
            'primary' => 'erp-access-request-admin.php',
            'test' => 'erp-role-access-matrix.php',
            'note' => 'درخواست دسترسی، ماتریس نقش — بدون ساخت کاربر از UI این صفحه.',
        ],
        [
            'name' => 'Production Signoff',
            'primary' => 'erp-v1-production-signoff.php',
            'test' => 'erp-v1-fix-register.php',
            'note' => 'ثبت وضعیت Production Run و کنترل پس از اجرا.',
        ],
        [
            'name' => 'Fix Register',
            'primary' => 'erp-v1-fix-register.php',
            'test' => 'erp-stabilization-dashboard.php',
            'note' => 'ثبت اصلاحات و موارد معوق V2.',
        ],
    ];
}

v1mc_render_head('MOGHARE360 V1 — Master Console');
$units = v1mc_master_units();
?>
<div class="v1mc-banner">MOGHARE360 V1 Master Console — Local unit launcher · No legacy MySQL index</div>
<div class="v1mc-hero">
  <h1 style="margin:0 0 .4rem;font-size:1.3rem">کنسول مادر V1</h1>
  <p style="margin:0;font-size:.92rem;opacity:.92">دسترسی واحدبه‌واحد به صفحات ERP موجود — وضعیت READY یعنی صفحه اصلی در repo موجود است.</p>
</div>

<nav class="v1mc-nav">
  <a href="erp-v1-unit-access-console.php">Unit Access Console</a>
  <a href="erp-v1-production-signoff.php">Production Signoff</a>
  <a href="erp-v1-fix-register.php">Fix Register</a>
  <a href="erp-moghare-ready.php">Moghare Ready</a>
  <a href="erp-soft-run-home.php?role=owner">Soft Run Home (Owner)</a>
  <a href="erp-operational-command-center.php">Operational Command Center</a>
  <a href="erp-product-status.php">Product Status</a>
</nav>

<div class="v1mc-grid">
<?php foreach ($units as $unit): ?>
  <?php
    $status = v1mc_unit_status($unit['primary']);
    $badge = $status === 'READY' ? 'v1mc-badge-ready' : 'v1mc-badge-check';
    $primaryHref = v1mc_page_exists($unit['primary']) ? $unit['primary'] : '#';
    $testHref = v1mc_page_exists($unit['test']) ? $unit['test'] : '';
  ?>
  <article class="v1mc-card">
    <span class="v1mc-badge <?= $badge ?>"><?= v1mc_h($status) ?></span>
    <h3><?= v1mc_h($unit['name']) ?></h3>
    <p><?= v1mc_h($unit['note']) ?></p>
    <div class="v1mc-links">
      <?php if ($status === 'READY'): ?>
        <a href="<?= v1mc_h($primaryHref) ?>">صفحه اصلی</a>
      <?php else: ?>
        <span class="v1mc-muted">NOT_LINKED — پیشنهاد در Fix Register</span>
      <?php endif; ?>
      <?php if ($testHref !== ''): ?>
        <a href="<?= v1mc_h($testHref) ?>">تست / گزارش</a>
      <?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>
</div>
<?php v1mc_render_foot(); ?>
