<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Payment Preview Detail UX
 *
 * Mission 36 — SELECT read-only. Not accounting ledger.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-finance-preview-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'finance');
$activeModule = 'payments';

$paymentId = 0;
$payment = [];
$history = [];
$jobcard = [];
$jobcardId = 0;
$errorMessage = '';
$connection = false;

$session = m36_ux_connect('payment.view');

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $paymentId = m36_ux_resolve_payment_id($connection, m36_ux_parse_int('payment_id', 1));
    $payment = m36_ux_fetch_payment_detail($connection, $paymentId);
    $history = m36_ux_fetch_payment_history($connection, $paymentId);
    $jobcardId = (int)($payment['jobcard_id'] ?? 0);
    $jobcard = $jobcardId > 0 ? m36_ux_fetch_jobcard_summary($connection, $jobcardId) : [];
}

moghare360_render_shell_start('پیش‌نمایش پرداخت #' . $paymentId, $activeModule, $roleMode);
m36_ux_render_finance_css_link();
?>

<div class="m36-fin-board">
  <div class="m36-fin-page-nav">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-finance-preview-workbench.php?role=<?= m36_ux_h(rawurlencode($roleMode)) ?>">میز کار مالی</a>
    <?php if ($jobcardId > 0): ?>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-finance-preview-ux.php?jobcard_id=<?= m36_ux_h((string)$jobcardId) ?>&role=<?= m36_ux_h(rawurlencode($roleMode)) ?>">JobCard Finance</a>
    <?php endif; ?>
  </div>

  <div class="m36-fin-boundary">
    <strong>Payment Preview Only</strong> — This is payment preview only, not accounting ledger. No write operations.
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m36_ux_h($errorMessage) ?></div></div>
  <?php elseif ($payment === []): ?>
    <div class="m36-fin-empty">پرداخت یافت نشد.</div>
  <?php else: ?>
    <div class="m36-fin-payment-card">
      <h2 style="margin:0 0 0.5rem;font-size:1.1rem;">پرداخت #<?= m36_ux_h((string)$paymentId) ?></h2>
      <p style="margin:0;font-size:0.9rem;">
        <span class="m360-badge <?= m36_ux_h(m36_ux_status_badge_class((string)($payment['payment_status'] ?? ''))) ?>"><?= m36_ux_display($payment['payment_status'] ?? '') ?></span>
        · <?= m36_ux_display($payment['payment_type'] ?? '') ?> · <?= m36_ux_display($payment['payment_method'] ?? '') ?>
      </p>
      <p style="margin:0.5rem 0 0;font-size:1.25rem;font-weight:700;" class="m360-num"><?= m36_ux_h(m36_ux_format_amount($payment['payment_amount'] ?? '0')) ?> <?= m36_ux_display($payment['currency_code'] ?? '') ?></p>
      <p style="margin:0.35rem 0 0;font-size:0.8rem;color:#737373;">دریافت: <span class="m360-ltr"><?= m36_ux_display($payment['received_at'] ?? '') ?></span> · user <?= m36_ux_display($payment['received_by_user_id'] ?? '') ?></p>
      <?php if (trim((string)($payment['payment_note'] ?? '')) !== ''): ?>
        <p style="margin:0.5rem 0 0;font-size:0.85rem;"><?= m36_ux_display($payment['payment_note'] ?? '') ?></p>
      <?php endif; ?>
    </div>

    <?php if ($jobcard !== []): ?>
    <div class="m36-fin-payment-card">
      <h3 style="margin:0 0 0.35rem;font-size:0.95rem;">JobCard Binding</h3>
      <p style="margin:0;"><?= m36_ux_display($jobcard['jobcard_number'] ?? '') ?> — <?= m36_ux_display($jobcard['full_name'] ?? '') ?></p>
      <a class="m360-btn m360-btn-ghost m360-btn-sm" style="margin-top:0.5rem;" href="erp-jobcard-detail-ux.php?jobcard_id=<?= m36_ux_h((string)$jobcardId) ?>&role=<?= m36_ux_h(rawurlencode($roleMode)) ?>">JobCard Detail UX</a>
    </div>
    <?php endif; ?>

    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">Payment History</h3></div>
      <div class="m360-card-body">
        <?php if ($history === []): ?>
          <div class="m36-fin-empty">تاریخچه‌ای یافت نشد.</div>
        <?php else: ?>
          <div class="m36-fin-timeline">
            <?php foreach ($history as $item): ?>
              <article class="m36-fin-timeline-item">
                <strong><?= m36_ux_display($item['action_code'] ?? '') ?></strong>
                <?= m36_ux_display($item['old_status'] ?? '') ?> → <?= m36_ux_display($item['new_status'] ?? '') ?>
                <div style="margin-top:0.25rem;font-size:0.75rem;color:#737373;">
                  user <?= m36_ux_display($item['changed_by_user_id'] ?? '') ?> · <span class="m360-ltr"><?= m36_ux_display($item['changed_at'] ?? '') ?></span>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="m360-page-toolbar">
      <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-payment-readonly-list.php">لیست M28</a>
      <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-payment-create.php">ثبت M28</a>
    </div>
  <?php endif; ?>
</div>

<?php
if ($connection !== false) {
    @odbc_close($connection);
}
moghare360_render_shell_end();
