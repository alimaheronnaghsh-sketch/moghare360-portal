<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP JobCard Detail UX
 *
 * Mission 33 — SELECT read-only summary. Action panel links to controlled prototypes only.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'owner');
$activeModule = 'jobcards';
$jobcardId = m33_ux_parse_jobcard_id(1);

$jobcard = [];
$serviceOps = [];
$partUsage = [];
$payments = [];
$qcSummary = [];
$deliverySummary = [];
$errorMessage = '';
$connection = false;

$session = m33_ux_connect('jobcard.view');

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $jobcard = m33_ux_fetch_jobcard_detail($connection, $jobcardId);
    $serviceOps = m33_ux_fetch_service_operations($connection, $jobcardId);
    $partUsage = m33_ux_fetch_part_usage($connection, $jobcardId);
    $payments = m33_ux_fetch_payments($connection, $jobcardId);
    $qcSummary = m33_ux_fetch_qc_summary($connection, $jobcardId);
    $deliverySummary = m33_ux_fetch_delivery_summary($connection, $jobcardId);
}

$status = (string)($jobcard['jobcard_status'] ?? '');
$flowSteps = m33_ux_jobcard_status_flow();
$currentIndex = array_search(strtoupper($status), $flowSteps, true);

moghare360_render_shell_start('جزئیات کارت کار #' . $jobcardId, $activeModule, $roleMode);
m33_ux_render_jobcard_css_link();
?>

<div class="m33-jc-board">
  <div class="m33-jc-page-nav">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-jobcard-workbench.php?role=<?= m33_ux_h(rawurlencode($roleMode)) ?>">بازگشت به میز کار</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-timeline-ux.php?jobcard_id=<?= m33_ux_h((string)$jobcardId) ?>&role=<?= m33_ux_h(rawurlencode($roleMode)) ?>">تایم‌لاین</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-detail.php?jobcard_id=<?= m33_ux_h((string)$jobcardId) ?>">جزئیات فنی M17</a>
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m33_ux_h($errorMessage) ?></div></div>
  <?php elseif ($jobcard === []): ?>
    <div class="m33-jc-empty">کارت کار با شناسه <?= m33_ux_h((string)$jobcardId) ?> یافت نشد.</div>
  <?php else: ?>
    <div class="m360-page-header">
      <div class="m360-page-header-eyebrow"><?= m33_ux_display($jobcard['jobcard_number'] ?? '') ?></div>
      <h2 class="m360-page-header-title">کارت کار #<?= m33_ux_h((string)$jobcardId) ?></h2>
      <p class="m360-page-header-meta">
        <span class="m360-badge <?= m33_ux_h(m33_ux_status_badge_class($status)) ?>"><?= m33_ux_display($status) ?></span>
        اولویت: <?= m33_ux_display($jobcard['priority_level'] ?? '') ?>
      </p>
    </div>

    <div class="m33-jc-status-flow" aria-label="جریان وضعیت">
      <?php foreach ($flowSteps as $i => $step): ?>
        <?php if ($i > 0): ?><span class="m33-jc-flow-arrow">←</span><?php endif; ?>
        <?php
        $stepClass = 'm33-jc-flow-step';
        if ($currentIndex !== false && $i < $currentIndex) {
            $stepClass .= ' is-past';
        } elseif ($currentIndex !== false && $i === $currentIndex) {
            $stepClass .= ' is-current';
        }
        ?>
        <span class="<?= m33_ux_h($stepClass) ?>"><?= m33_ux_h($step) ?></span>
      <?php endforeach; ?>
    </div>

    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">خلاصه کارت کار</h3></div>
      <div class="m360-card-body">
        <div class="m33-jc-summary-grid">
          <div class="m33-jc-summary-item"><div class="m33-jc-summary-label">پذیرش</div><div class="m33-jc-summary-value m360-ltr"><?= m33_ux_display($jobcard['reception_at'] ?? '') ?></div></div>
          <div class="m33-jc-summary-item"><div class="m33-jc-summary-label">وعده تحویل</div><div class="m33-jc-summary-value m360-ltr"><?= m33_ux_display($jobcard['promised_at'] ?? '') ?></div></div>
          <div class="m33-jc-summary-item"><div class="m33-jc-summary-label">کیلومتر</div><div class="m33-jc-summary-value"><?= m33_ux_display($jobcard['intake_mileage'] ?? '') ?></div></div>
          <div class="m33-jc-summary-item"><div class="m33-jc-summary-label">سوخت</div><div class="m33-jc-summary-value"><?= m33_ux_display($jobcard['fuel_level'] ?? '') ?></div></div>
          <div class="m33-jc-summary-item"><div class="m33-jc-summary-label">relation_id</div><div class="m33-jc-summary-value m360-num"><?= m33_ux_display($jobcard['relation_id'] ?? '') ?></div></div>
          <div class="m33-jc-summary-item"><div class="m33-jc-summary-label">lifecycle</div><div class="m33-jc-summary-value"><?= m33_ux_display($jobcard['lifecycle_state'] ?? '') ?></div></div>
        </div>
        <?php if (trim((string)($jobcard['customer_complaint'] ?? '')) !== ''): ?>
          <p style="margin:1rem 0 0;font-size:0.9rem;"><strong>شکایت مشتری:</strong> <?= m33_ux_display($jobcard['customer_complaint'] ?? '') ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="m33-jc-binding-grid">
      <article class="m33-jc-binding-card is-customer">
        <p class="m33-jc-binding-title">مشتری</p>
        <h4 class="m33-jc-binding-name"><?= m33_ux_display($jobcard['full_name'] ?? '') ?></h4>
        <p class="m33-jc-binding-meta">کد: <?= m33_ux_display($jobcard['customer_code'] ?? '') ?> · موبایل: <span class="m360-ltr"><?= m33_ux_display($jobcard['primary_mobile'] ?? '') ?></span></p>
      </article>
      <article class="m33-jc-binding-card is-vehicle">
        <p class="m33-jc-binding-title">خودرو</p>
        <h4 class="m33-jc-binding-name"><?= m33_ux_display(trim(($jobcard['brand'] ?? '') . ' ' . ($jobcard['model'] ?? ''))) ?></h4>
        <p class="m33-jc-binding-meta">پلاک: <span class="m360-ltr"><?= m33_ux_display($jobcard['plate_number'] ?? '') ?></span> · VIN: <span class="m360-ltr"><?= m33_ux_display($jobcard['vin'] ?? '') ?></span></p>
      </article>
    </div>

    <div class="m360-grid m360-grid-2">
      <div class="m360-card">
        <div class="m360-card-header"><h3 class="m360-card-title">عملیات سرویس</h3></div>
        <div class="m360-card-body">
          <?php if ($serviceOps === []): ?>
            <p class="m33-jc-empty" style="padding:1rem;">عملیات سرویسی ثبت نشده.</p>
          <?php else: ?>
            <ul class="m33-jc-tech-list">
              <?php foreach ($serviceOps as $op): ?>
                <li>
                  <span><?= m33_ux_display($op['service_title'] ?? '') ?></span>
                  <span class="m360-badge <?= m33_ux_h(m33_ux_status_badge_class((string)($op['service_status'] ?? ''))) ?>"><?= m33_ux_display($op['service_status'] ?? '') ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

      <div class="m33-jc-tech-panel">
        <h4 class="m33-jc-tech-panel-title">آمادگی تکنسین (نمایشی)</h4>
        <ul class="m33-jc-tech-list">
          <li><span>عملیات فعال</span><strong><?= m33_ux_h((string)count($serviceOps)) ?></strong></li>
          <li><span>قطعات مصرفی</span><strong><?= m33_ux_h((string)count($partUsage)) ?></strong></li>
          <li><span>وضعیت QC</span><strong><?= m33_ux_display($qcSummary['qc_status'] ?? '—') ?></strong></li>
        </ul>
      </div>
    </div>

    <?php if ($partUsage !== []): ?>
    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">خلاصه مصرف قطعه</h3></div>
      <div class="m360-card-body">
        <ul class="m33-jc-tech-list">
          <?php foreach ($partUsage as $part): ?>
            <li>
              <span>Part #<?= m33_ux_display($part['part_id'] ?? '') ?> × <?= m33_ux_display($part['quantity'] ?? '') ?></span>
              <span class="m360-badge m360-badge-neutral"><?= m33_ux_display($part['usage_status'] ?? '') ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($payments !== []): ?>
    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">خلاصه پرداخت</h3></div>
      <div class="m360-card-body">
        <ul class="m33-jc-tech-list">
          <?php foreach ($payments as $pay): ?>
            <li>
              <span><?= m33_ux_display($pay['payment_type'] ?? '') ?> — <?= m33_ux_display($pay['payment_amount'] ?? '') ?></span>
              <span class="m360-badge <?= m33_ux_h(m33_ux_status_badge_class((string)($pay['payment_status'] ?? ''))) ?>"><?= m33_ux_display($pay['payment_status'] ?? '') ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <div class="m360-grid m360-grid-2">
      <div class="m360-card">
        <div class="m360-card-header"><h3 class="m360-card-title">وضعیت QC</h3></div>
        <div class="m360-card-body">
          <?php if ($qcSummary === []): ?>
            <p>—</p>
          <?php else: ?>
            <p><span class="m360-badge <?= m33_ux_h(m33_ux_status_badge_class((string)($qcSummary['qc_status'] ?? ''))) ?>"><?= m33_ux_display($qcSummary['qc_status'] ?? '') ?></span></p>
            <p class="m360-ltr" style="font-size:0.85rem;"><?= m33_ux_display($qcSummary['checked_at'] ?? '') ?></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="m360-card">
        <div class="m360-card-header"><h3 class="m360-card-title">وضعیت تحویل</h3></div>
        <div class="m360-card-body">
          <?php if ($deliverySummary === []): ?>
            <p>—</p>
          <?php else: ?>
            <p><span class="m360-badge <?= m33_ux_h(m33_ux_status_badge_class((string)($deliverySummary['delivery_status'] ?? ''))) ?>"><?= m33_ux_display($deliverySummary['delivery_status'] ?? '') ?></span></p>
            <p style="font-size:0.85rem;">مجاز: <?= m33_ux_display($deliverySummary['delivery_allowed'] ?? '') ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="m360-card">
      <div class="m360-card-header">
        <h3 class="m360-card-title">اقدامات عملیاتی</h3>
        <p class="m360-card-subtitle">لینک به صفحات controlled prototype — بدون write در این صفحه</p>
      </div>
      <div class="m360-card-body">
        <div class="m33-jc-action-grid">
          <a class="m33-jc-action-card is-controlled" href="erp-service-operation-create.php"><span class="m33-jc-action-card-title">ثبت عملیات سرویس</span><span class="m33-jc-action-card-desc">M20 controlled create</span></a>
          <a class="m33-jc-action-card is-controlled" href="erp-jobcard-part-use.php"><span class="m33-jc-action-card-title">مصرف قطعه</span><span class="m33-jc-action-card-desc">M24 controlled write</span></a>
          <a class="m33-jc-action-card is-controlled" href="erp-payment-create.php"><span class="m33-jc-action-card-title">ثبت پرداخت</span><span class="m33-jc-action-card-desc">M28 controlled create</span></a>
          <a class="m33-jc-action-card is-controlled" href="erp-qc-check.php"><span class="m33-jc-action-card-title">کنترل کیفیت</span><span class="m33-jc-action-card-desc">M30 QC prototype</span></a>
          <a class="m33-jc-action-card is-controlled" href="erp-delivery-control.php?jobcard_id=<?= m33_ux_h((string)$jobcardId) ?>"><span class="m33-jc-action-card-title">کنترل تحویل</span><span class="m33-jc-action-card-desc">M30 delivery gate</span></a>
          <a class="m33-jc-action-card is-controlled" href="erp-jobcard-payment-summary.php?jobcard_id=<?= m33_ux_h((string)$jobcardId) ?>"><span class="m33-jc-action-card-title">خلاصه پرداخت</span><span class="m33-jc-action-card-desc">M28 read-only summary</span></a>
          <a class="m33-jc-action-card is-controlled" href="erp-soft-run-readiness.php?jobcard_id=<?= m33_ux_h((string)$jobcardId) ?>"><span class="m33-jc-action-card-title">Soft Run Gate</span><span class="m33-jc-action-card-desc">M30 readiness check</span></a>
          <a class="m33-jc-action-card is-controlled" href="erp-purchase-request-create.php"><span class="m33-jc-action-card-title">درخواست خرید</span><span class="m33-jc-action-card-desc">M26 controlled create</span></a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php
if ($connection !== false) {
    @odbc_close($connection);
}
moghare360_render_shell_end();
