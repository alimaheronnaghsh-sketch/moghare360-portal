<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP JobCard Finance Preview UX
 *
 * Mission 36 — SELECT read-only financial preview. No invoice/accounting write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-finance-preview-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'finance');
$activeModule = 'payments';

$jobcardId = 0;
$jobcard = [];
$paymentSummary = [];
$payments = [];
$serviceOps = [];
$parts = [];
$purchases = [];
$balance = ['estimated_total' => '', 'balance_preview' => '', 'has_estimate' => false];
$errorMessage = '';
$connection = false;

$session = m36_ux_connect('payment.summary.view');

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $jobcardId = m36_ux_resolve_jobcard_id($connection, m36_ux_parse_int('jobcard_id', 1));
    $jobcard = m36_ux_fetch_jobcard_summary($connection, $jobcardId);
    $paymentSummary = m36_ux_fetch_payment_summary($connection, $jobcardId);
    $payments = m36_ux_fetch_jobcard_payments($connection, $jobcardId);
    $serviceOps = m36_ux_fetch_service_ops_summary($connection, $jobcardId);
    $parts = m36_ux_fetch_part_usage_summary($connection, $jobcardId);
    $purchases = m36_ux_fetch_purchase_summary($connection, $jobcardId);
    $balance = m36_ux_compute_balance_preview($connection, $jobcardId, $paymentSummary['total_received'] ?? '0');
}

moghare360_render_shell_start('پیش‌نمایش مالی JobCard #' . $jobcardId, $activeModule, $roleMode);
m36_ux_render_finance_css_link();
?>

<div class="m36-fin-board">
  <div class="m36-fin-page-nav">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-finance-preview-workbench.php?role=<?= m36_ux_h(rawurlencode($roleMode)) ?>">میز کار مالی</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-detail-ux.php?jobcard_id=<?= m36_ux_h((string)$jobcardId) ?>&role=<?= m36_ux_h(rawurlencode($roleMode)) ?>">JobCard UX</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-invoice-preview-mock.php?jobcard_id=<?= m36_ux_h((string)$jobcardId) ?>&role=<?= m36_ux_h(rawurlencode($roleMode)) ?>">Invoice Mock</a>
  </div>

  <div class="m36-fin-warning">
    <strong>Financial Preview Only</strong> — estimated_total یک placeholder اطلاعاتی است. هیچ invoice، export حسابداری، یا delivery unlock انجام نمی‌شود.
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m36_ux_h($errorMessage) ?></div></div>
  <?php elseif ($jobcard === []): ?>
    <div class="m36-fin-empty">JobCard یافت نشد.</div>
  <?php else: ?>
    <div class="m360-page-header">
      <div class="m360-page-header-eyebrow"><?= m36_ux_display($jobcard['jobcard_number'] ?? '') ?></div>
      <h2 class="m360-page-header-title">پیش‌نمایش مالی #<?= m36_ux_h((string)$jobcardId) ?></h2>
      <p class="m360-page-header-meta">
        <?= m36_ux_display($jobcard['full_name'] ?? '') ?> ·
        <?= m36_ux_display(trim(($jobcard['brand'] ?? '') . ' ' . ($jobcard['model'] ?? ''))) ?>
        · <span class="m360-ltr"><?= m36_ux_display($jobcard['plate_number'] ?? '') ?></span>
      </p>
    </div>

    <div class="m36-fin-summary-grid">
      <div class="m36-fin-summary-item"><div class="m36-fin-summary-label">payment_count</div><div class="m36-fin-summary-value m360-num"><?= m36_ux_display($paymentSummary['payment_count'] ?? '0') ?></div></div>
      <div class="m36-fin-summary-item"><div class="m36-fin-summary-label">total_received</div><div class="m36-fin-summary-value m360-num"><?= m36_ux_h(m36_ux_format_amount($paymentSummary['total_received'] ?? '0')) ?></div></div>
      <div class="m36-fin-summary-item" style="grid-column:span 2;"><div class="m36-fin-summary-label">latest_payment</div><div class="m36-fin-summary-value" style="font-size:0.85rem;"><?= m36_ux_display($paymentSummary['latest_payment'] ?? '—') ?></div></div>
    </div>

    <?php if ($balance['has_estimate']): ?>
    <div class="m360-grid m360-grid-2">
      <div class="m36-fin-balance-card">
        <div class="m36-fin-summary-label">estimated_total (placeholder)</div>
        <div class="m36-fin-kpi-value m360-num"><?= m36_ux_h(m36_ux_format_amount($balance['estimated_total'])) ?></div>
      </div>
      <div class="m36-fin-balance-card is-outstanding">
        <div class="m36-fin-summary-label">balance_preview</div>
        <div class="m36-fin-kpi-value m360-num"><?= m36_ux_h(m36_ux_format_amount($balance['balance_preview'])) ?></div>
      </div>
    </div>
    <?php endif; ?>

    <div class="m360-grid m360-grid-2">
      <div class="m360-card">
        <div class="m360-card-header"><h3 class="m360-card-title">عملیات سرویس</h3></div>
        <div class="m360-card-body">
          <?php if ($serviceOps === []): ?><div class="m36-fin-empty">—</div><?php else: ?>
            <ul style="margin:0;padding:0;list-style:none;">
              <?php foreach ($serviceOps as $op): ?>
                <li style="margin-bottom:0.35rem;font-size:0.85rem;"><?= m36_ux_display($op['service_title'] ?? '') ?> — <?= m36_ux_display($op['service_status'] ?? '') ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
      <div class="m360-card">
        <div class="m360-card-header"><h3 class="m360-card-title">مصرف قطعه</h3></div>
        <div class="m360-card-body">
          <?php if ($parts === []): ?><div class="m36-fin-empty">—</div><?php else: ?>
            <ul style="margin:0;padding:0;list-style:none;">
              <?php foreach ($parts as $p): ?>
                <li style="margin-bottom:0.35rem;font-size:0.85rem;">Part #<?= m36_ux_display($p['part_id'] ?? '') ?> × <?= m36_ux_display($p['quantity'] ?? '') ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($purchases !== []): ?>
    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">درخواست خرید</h3></div>
      <div class="m360-card-body">
        <ul style="margin:0;padding:0;list-style:none;">
          <?php foreach ($purchases as $pr): ?>
            <li style="margin-bottom:0.35rem;font-size:0.85rem;"><?= m36_ux_display($pr['requested_part_name'] ?? '') ?> — <?= m36_ux_display($pr['request_status'] ?? '') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">پرداخت‌ها</h3></div>
      <div class="m360-card-body" style="padding:0;">
        <?php if ($payments === []): ?>
          <div class="m36-fin-empty" style="margin:1rem;">پرداختی ثبت نشده.</div>
        <?php else: ?>
          <div class="m36-fin-table-wrap">
            <table class="m36-fin-table">
              <thead><tr><th>ID</th><th>نوع</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($payments as $pay): ?>
                  <tr>
                    <td class="m360-num"><?= m36_ux_display($pay['payment_id'] ?? '') ?></td>
                    <td><?= m36_ux_display($pay['payment_type'] ?? '') ?></td>
                    <td class="m360-num"><?= m36_ux_h(m36_ux_format_amount($pay['payment_amount'] ?? '0')) ?></td>
                    <td><span class="m360-badge <?= m36_ux_h(m36_ux_status_badge_class((string)($pay['payment_status'] ?? ''))) ?>"><?= m36_ux_display($pay['payment_status'] ?? '') ?></span></td>
                    <td class="m360-ltr"><?= m36_ux_display($pay['received_at'] ?? '') ?></td>
                    <td><a href="erp-payment-preview-detail-ux.php?payment_id=<?= m36_ux_h($pay['payment_id'] ?? '') ?>&role=<?= m36_ux_h(rawurlencode($roleMode)) ?>">جزئیات</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="m360-page-toolbar">
      <a class="m360-btn m360-btn-primary m360-btn-sm" href="erp-payment-create.php">ثبت پرداخت M28</a>
      <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-jobcard-payment-summary.php?jobcard_id=<?= m36_ux_h((string)$jobcardId) ?>">خلاصه M28</a>
    </div>
  <?php endif; ?>
</div>

<?php
if ($connection !== false) {
    @odbc_close($connection);
}
moghare360_render_shell_end();
