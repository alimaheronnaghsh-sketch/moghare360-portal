<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Invoice Preview Mock
 *
 * Mission 36 — Display-only invoice mock. No official number. No write.
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
$serviceOps = [];
$parts = [];
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
    $serviceOps = m36_ux_fetch_service_ops_summary($connection, $jobcardId);
    $parts = m36_ux_fetch_part_usage_summary($connection, $jobcardId);
    $balance = m36_ux_compute_balance_preview($connection, $jobcardId, $paymentSummary['total_received'] ?? '0');
}

$mockInvoiceNo = 'MOCK-PREVIEW-JC' . str_pad((string)$jobcardId, 4, '0', STR_PAD_LEFT);

moghare360_render_shell_start('پیش‌نمایش فاکتور Mock', $activeModule, $roleMode);
m36_ux_render_finance_css_link();
?>

<div class="m36-fin-board">
  <div class="m36-fin-page-nav">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-jobcard-finance-preview-ux.php?jobcard_id=<?= m36_ux_h((string)$jobcardId) ?>&role=<?= m36_ux_h(rawurlencode($roleMode)) ?>">بازگشت به Finance Preview</a>
  </div>

  <div class="m36-fin-boundary">
    <strong>Invoice Mock Only</strong> — شماره فاکتور رسمی، tax logic، accounting export، و finalization غیرفعال هستند.
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m36_ux_h($errorMessage) ?></div></div>
  <?php elseif ($jobcard === []): ?>
    <div class="m36-fin-empty">JobCard یافت نشد.</div>
  <?php else: ?>
    <article class="m36-fin-invoice-mock">
      <header class="m36-fin-invoice-header">
        <h1 class="m36-fin-invoice-title">پیش‌نمایش فاکتور (Mock)</h1>
        <p class="m36-fin-invoice-sub">شماره نمایشی: <span class="m360-ltr"><?= m36_ux_h($mockInvoiceNo) ?></span> — NOT OFFICIAL</p>
        <p class="m36-fin-invoice-sub">JobCard: <?= m36_ux_display($jobcard['jobcard_number'] ?? '') ?> · <?= m36_ux_display($jobcard['full_name'] ?? '') ?></p>
      </header>

      <table class="m36-fin-invoice-lines">
        <thead><tr><th>شرح</th><th>تعداد</th><th>مبلغ (Mock)</th></tr></thead>
        <tbody>
          <?php foreach ($serviceOps as $op): ?>
            <tr>
              <td>سرویس: <?= m36_ux_display($op['service_title'] ?? '') ?></td>
              <td class="m360-num">1</td>
              <td class="m360-num">500,000</td>
            </tr>
          <?php endforeach; ?>
          <?php foreach ($parts as $p): ?>
            <tr>
              <td>قطعه #<?= m36_ux_display($p['part_id'] ?? '') ?></td>
              <td class="m360-num"><?= m36_ux_display($p['quantity'] ?? '') ?></td>
              <td class="m360-num">150,000</td>
            </tr>
          <?php endforeach; ?>
          <?php if ($serviceOps === [] && $parts === []): ?>
            <tr><td>خط نمایشی placeholder</td><td class="m360-num">1</td><td class="m360-num">200,000</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="m36-fin-summary-grid" style="max-width:320px;margin-inline-start:auto;">
        <?php if ($balance['has_estimate']): ?>
          <div class="m36-fin-summary-item"><div class="m36-fin-summary-label">جمع تخمینی</div><div class="m36-fin-summary-value m360-num"><?= m36_ux_h(m36_ux_format_amount($balance['estimated_total'])) ?></div></div>
        <?php endif; ?>
        <div class="m36-fin-summary-item"><div class="m36-fin-summary-label">دریافتی</div><div class="m36-fin-summary-value m360-num"><?= m36_ux_h(m36_ux_format_amount($paymentSummary['total_received'] ?? '0')) ?></div></div>
        <?php if ($balance['has_estimate']): ?>
          <div class="m36-fin-summary-item"><div class="m36-fin-summary-label">مانده پیش‌نمایش</div><div class="m36-fin-summary-value m360-num"><?= m36_ux_h(m36_ux_format_amount($balance['balance_preview'])) ?></div></div>
        <?php endif; ?>
      </div>

      <div class="m36-fin-disabled-actions" style="margin-top:1.25rem;">
        <button type="button" class="m36-fin-btn-disabled" disabled>Finalize Invoice — Disabled</button>
        <button type="button" class="m36-fin-btn-disabled" disabled>Export Accounting — Disabled</button>
        <button type="button" class="m36-fin-btn-disabled" disabled>Tax Calculation — Disabled</button>
      </div>
    </article>
  <?php endif; ?>
</div>

<?php
if ($connection !== false) {
    @odbc_close($connection);
}
moghare360_render_shell_end();
