<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Finance Preview Workbench
 *
 * Mission 36 — SELECT read-only. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-finance-preview-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'finance');
$activeModule = 'payments';

$kpi = ['total_received' => '0', 'payment_count' => '0', 'jobcards_with_payment' => '0', 'preview_only' => '0'];
$jobcards = [];
$errorMessage = '';
$connection = false;

$session = m36_ux_connect('payment.summary.view');

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $kpi = m36_ux_fetch_workbench_kpi($connection);
    $jobcards = m36_ux_fetch_workbench_jobcards($connection);
}

moghare360_render_shell_start('میز کار پیش‌نمایش مالی', $activeModule, $roleMode);
m36_ux_render_finance_css_link();
?>

<div class="m36-fin-board">
  <div class="m360-page-header">
    <div class="m360-page-header-eyebrow">Mission 36 — Finance Preview UX</div>
    <h2 class="m360-page-header-title">پیش‌نمایش مالی JobCard</h2>
    <p class="m360-page-header-meta">read-only — نقش: <?= moghare360_shell_h(strtoupper($roleMode)) ?></p>
  </div>

  <div class="m36-fin-boundary">
    <strong>Preview Only:</strong> این لایه فقط نمایش است — بدون invoice finalization، accounting export، tax logic، یا payment write.
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m36_ux_h($errorMessage) ?></div></div>
  <?php endif; ?>

  <div class="m360-page-toolbar">
    <a class="m360-btn m360-btn-primary m360-btn-sm" href="erp-payment-create.php">ثبت پرداخت (M28)</a>
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-payment-readonly-list.php">لیست پرداخت M28</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-workbench.php?role=<?= m36_ux_h(rawurlencode($roleMode)) ?>">میز کار JobCard</a>
  </div>

  <div class="m36-fin-kpi-grid">
    <div class="m36-fin-kpi"><div class="m36-fin-kpi-label">Total Received</div><div class="m36-fin-kpi-value m360-num"><?= m36_ux_h(m36_ux_format_amount($kpi['total_received'])) ?></div></div>
    <div class="m36-fin-kpi is-count"><div class="m36-fin-kpi-label">Payment Count</div><div class="m36-fin-kpi-value m360-num"><?= m36_ux_display($kpi['payment_count']) ?></div></div>
    <div class="m36-fin-kpi is-jobcard"><div class="m36-fin-kpi-label">JobCards With Payment</div><div class="m36-fin-kpi-value m360-num"><?= m36_ux_display($kpi['jobcards_with_payment']) ?></div></div>
    <div class="m36-fin-kpi is-preview"><div class="m36-fin-kpi-label">Preview Only</div><div class="m36-fin-kpi-value m360-num"><?= m36_ux_display($kpi['preview_only']) ?></div></div>
  </div>

  <div class="m360-card">
    <div class="m360-card-header"><h3 class="m360-card-title">JobCard Financial Preview</h3></div>
    <div class="m360-card-body" style="padding:0;">
      <?php if ($jobcards === []): ?>
        <div class="m36-fin-empty" style="margin:1rem;">JobCard یافت نشد.</div>
      <?php else: ?>
        <div class="m36-fin-table-wrap">
          <table class="m36-fin-table">
            <thead>
              <tr><th>JobCard</th><th>مشتری</th><th>وضعیت</th><th>پرداخت‌ها</th><th>دریافتی</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($jobcards as $row): ?>
                <?php $jcId = (int)($row['jobcard_id'] ?? 0); ?>
                <tr>
                  <td><?= m36_ux_display($row['jobcard_number'] ?? '') ?></td>
                  <td><?= m36_ux_display($row['full_name'] ?? '') ?></td>
                  <td><span class="m360-badge m360-badge-neutral"><?= m36_ux_display($row['jobcard_status'] ?? '') ?></span></td>
                  <td class="m360-num"><?= m36_ux_display($row['payment_count'] ?? '0') ?></td>
                  <td class="m360-num"><?= m36_ux_h(m36_ux_format_amount((string)($row['total_received'] ?? '0'))) ?></td>
                  <td>
                    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-finance-preview-ux.php?jobcard_id=<?= m36_ux_h((string)$jcId) ?>&role=<?= m36_ux_h(rawurlencode($roleMode)) ?>">پیش‌نمایش</a>
                    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-invoice-preview-mock.php?jobcard_id=<?= m36_ux_h((string)$jcId) ?>&role=<?= m36_ux_h(rawurlencode($roleMode)) ?>">فاکتور Mock</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
if ($connection !== false) {
    @odbc_close($connection);
}
moghare360_render_shell_end();
