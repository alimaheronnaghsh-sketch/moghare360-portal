<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Service Operation Detail UX
 *
 * Mission 35 — SELECT read-only. Action links only. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-service-operation-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'service');
$activeModule = 'service_operations';

$serviceOp = [];
$jobcard = [];
$parts = [];
$purchases = [];
$payments = [];
$qc = [];
$delivery = [];
$history = [];
$serviceOpId = 0;
$jobcardId = 0;
$errorMessage = '';
$connection = false;

$session = m35_ux_connect('service.operation.view');

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $serviceOpId = m35_ux_resolve_service_operation_id($connection, m35_ux_parse_int('service_operation_id', 1));
    $serviceOp = m35_ux_fetch_service_operation_detail($connection, $serviceOpId);
    $jobcardId = (int)($serviceOp['jobcard_id'] ?? 0);
    $jobcard = $jobcardId > 0 ? m35_ux_fetch_jobcard_binding($connection, $jobcardId) : [];
    $parts = m35_ux_fetch_part_usage($connection, $serviceOpId);
    $purchases = m35_ux_fetch_purchase_requests($connection, $serviceOpId);
    $payments = $jobcardId > 0 ? m35_ux_fetch_jobcard_payments($connection, $jobcardId) : [];
    $qc = $jobcardId > 0 ? m35_ux_fetch_qc_summary($connection, $jobcardId) : [];
    $delivery = $jobcardId > 0 ? m35_ux_fetch_delivery_summary($connection, $jobcardId) : [];
    $history = m35_ux_fetch_service_history($connection, $serviceOpId);
}

$status = (string)($serviceOp['service_status'] ?? '');

moghare360_render_shell_start('جزئیات عملیات #' . $serviceOpId, $activeModule, $roleMode);
m35_ux_render_so_css_link();
?>

<div class="m35-so-board">
  <div class="m35-so-page-nav">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-service-operation-workbench-ux.php?role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">میز کار</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-service-operation-board-ux.php?role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">برد وضعیت</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-service-operation-detail.php?service_operation_id=<?= m35_ux_h((string)$serviceOpId) ?>">جزئیات فنی M20</a>
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m35_ux_h($errorMessage) ?></div></div>
  <?php elseif ($serviceOp === []): ?>
    <div class="m35-so-empty">عملیات سرویس یافت نشد.</div>
  <?php else: ?>
    <div class="m360-page-header">
      <div class="m360-page-header-eyebrow">Service Operation #<?= m35_ux_h((string)$serviceOpId) ?></div>
      <h2 class="m360-page-header-title"><?= m35_ux_display($serviceOp['service_title'] ?? '') ?></h2>
      <p class="m360-page-header-meta">
        <span class="m360-badge <?= m35_ux_h(m35_ux_status_badge_class($status)) ?>"><?= m35_ux_display($status) ?></span>
        · assigned: <span class="m360-num"><?= m35_ux_display($serviceOp['assigned_to_user_id'] ?? '—') ?></span>
      </p>
    </div>

    <div class="m35-so-progress-rail" aria-label="پیشرفت وضعیت">
      <?php foreach (M35_UX_BOARD_STATUSES as $i => $step): ?>
        <?php
        $stepClass = 'm35-so-progress-step';
        $current = m35_ux_progress_index($status);
        if ($i < $current) {
            $stepClass .= ' is-past';
        } elseif ($i === $current) {
            $stepClass .= ' is-current';
        }
        ?>
        <span class="<?= m35_ux_h($stepClass) ?>"><?= m35_ux_h($step) ?></span>
      <?php endforeach; ?>
    </div>

    <div class="m35-so-detail-panel">
      <h3 style="margin:0 0 0.5rem;font-size:1rem;">خلاصه عملیات</h3>
      <p style="margin:0;font-size:0.9rem;"><?= m35_ux_display($serviceOp['service_description'] ?? '—') ?></p>
      <p style="margin:0.5rem 0 0;font-size:0.8rem;color:#737373;">ایجاد: <span class="m360-ltr"><?= m35_ux_display($serviceOp['created_at'] ?? '') ?></span></p>
    </div>

    <?php if ($jobcard !== []): ?>
    <div class="m35-so-detail-panel is-jobcard">
      <h3 style="margin:0 0 0.5rem;font-size:1rem;">JobCard Binding</h3>
      <p style="margin:0;"><strong><?= m35_ux_display($jobcard['jobcard_number'] ?? '') ?></strong> — <?= m35_ux_display($jobcard['full_name'] ?? '') ?></p>
      <p style="margin:0.35rem 0 0;font-size:0.85rem;">
        <?= m35_ux_display(trim(($jobcard['brand'] ?? '') . ' ' . ($jobcard['model'] ?? ''))) ?>
        · پلاک: <span class="m360-ltr"><?= m35_ux_display($jobcard['plate_number'] ?? '') ?></span>
      </p>
      <a class="m360-btn m360-btn-ghost m360-btn-sm" style="margin-top:0.5rem;" href="erp-jobcard-detail-ux.php?jobcard_id=<?= m35_ux_h((string)$jobcardId) ?>&role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">JobCard Detail UX</a>
    </div>
    <?php endif; ?>

    <div class="m360-grid m360-grid-2">
      <div class="m360-card">
        <div class="m360-card-header"><h3 class="m360-card-title">مصرف قطعه</h3></div>
        <div class="m360-card-body">
          <?php if ($parts === []): ?>
            <div class="m35-so-empty">—</div>
          <?php else: ?>
            <ul class="m35-so-linked-list">
              <?php foreach ($parts as $p): ?>
                <li><span>Part #<?= m35_ux_display($p['part_id'] ?? '') ?> × <?= m35_ux_display($p['quantity'] ?? '') ?></span><span class="m360-badge m360-badge-neutral"><?= m35_ux_display($p['usage_status'] ?? '') ?></span></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
      <div class="m360-card">
        <div class="m360-card-header"><h3 class="m360-card-title">درخواست خرید</h3></div>
        <div class="m360-card-body">
          <?php if ($purchases === []): ?>
            <div class="m35-so-empty">—</div>
          <?php else: ?>
            <ul class="m35-so-linked-list">
              <?php foreach ($purchases as $pr): ?>
                <li><span><?= m35_ux_display($pr['requested_part_name'] ?? '') ?></span><span class="m360-badge m360-badge-neutral"><?= m35_ux_display($pr['request_status'] ?? '') ?></span></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($payments !== []): ?>
    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">خلاصه پرداخت JobCard</h3></div>
      <div class="m360-card-body">
        <ul class="m35-so-linked-list">
          <?php foreach ($payments as $pay): ?>
            <li><span><?= m35_ux_display($pay['payment_type'] ?? '') ?> — <?= m35_ux_display($pay['payment_amount'] ?? '') ?></span><span class="m360-badge <?= m35_ux_h(m35_ux_status_badge_class((string)($pay['payment_status'] ?? ''))) ?>"><?= m35_ux_display($pay['payment_status'] ?? '') ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <div class="m360-grid m360-grid-2">
      <div class="m360-card">
        <div class="m360-card-header"><h3 class="m360-card-title">QC Status</h3></div>
        <div class="m360-card-body"><p><?= $qc === [] ? '—' : m35_ux_display($qc['qc_status'] ?? '') ?></p></div>
      </div>
      <div class="m360-card">
        <div class="m360-card-header"><h3 class="m360-card-title">Delivery Status</h3></div>
        <div class="m360-card-body"><p><?= $delivery === [] ? '—' : m35_ux_display($delivery['delivery_status'] ?? '') ?></p></div>
      </div>
    </div>

    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">اقدامات — لینک فقط</h3></div>
      <div class="m360-card-body">
        <div class="m35-so-action-grid">
          <a class="m35-so-action-card" href="erp-jobcard-detail-ux.php?jobcard_id=<?= m35_ux_h((string)$jobcardId) ?>&role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">JobCard Detail UX</a>
          <a class="m35-so-action-card" href="erp-jobcard-part-use.php">Part Usage M24</a>
          <a class="m35-so-action-card" href="erp-purchase-request-create.php">Purchase Request M26</a>
          <a class="m35-so-action-card" href="erp-payment-create.php">Payment M28</a>
          <a class="m35-so-action-card" href="erp-qc-check.php">QC M30</a>
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
