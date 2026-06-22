<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP JobCard Timeline UX
 *
 * Mission 33 — Combined read-only timeline from history tables. Safe table checks.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'owner');
$activeModule = 'jobcards';
$jobcardId = m33_ux_parse_jobcard_id(1);

$jobcard = [];
$timeline = [];
$errorMessage = '';
$connection = false;

$session = m33_ux_connect('jobcard.view');

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $jobcard = m33_ux_fetch_jobcard_detail($connection, $jobcardId);
    $timeline = m33_ux_fetch_combined_timeline($connection, $jobcardId);
}

moghare360_render_shell_start('تایم‌لاین کارت کار #' . $jobcardId, $activeModule, $roleMode);
m33_ux_render_jobcard_css_link();
?>

<div class="m33-jc-board">
  <div class="m33-jc-page-nav">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-jobcard-detail-ux.php?jobcard_id=<?= m33_ux_h((string)$jobcardId) ?>&role=<?= m33_ux_h(rawurlencode($roleMode)) ?>">بازگشت به جزئیات</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-workbench.php?role=<?= m33_ux_h(rawurlencode($roleMode)) ?>">میز کار</a>
  </div>

  <div class="m360-page-header">
    <div class="m360-page-header-eyebrow">Timeline UX — Read Only</div>
    <h2 class="m360-page-header-title">تایم‌لاین کارت کار #<?= m33_ux_h((string)$jobcardId) ?></h2>
    <?php if ($jobcard !== []): ?>
      <p class="m360-page-header-meta"><?= m33_ux_display($jobcard['jobcard_number'] ?? '') ?> — <?= m33_ux_display($jobcard['full_name'] ?? '') ?></p>
    <?php endif; ?>
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m33_ux_h($errorMessage) ?></div></div>
  <?php elseif ($timeline === []): ?>
    <div class="m33-jc-empty">رویدادی در جداول history یافت نشد (یا جدول موجود نیست).</div>
  <?php else: ?>
    <div class="m360-card">
      <div class="m360-card-header">
        <h3 class="m360-card-title"><?= m33_ux_h((string)count($timeline)) ?> رویداد</h3>
        <p class="m360-card-subtitle">action_code · status · user_id · timestamp</p>
      </div>
      <div class="m360-card-body">
        <div class="m33-jc-timeline">
          <?php foreach ($timeline as $item): ?>
            <article class="m33-jc-timeline-item">
              <div class="m33-jc-timeline-source"><?= m33_ux_display($item['source'] ?? '') ?></div>
              <p class="m33-jc-timeline-action"><?= m33_ux_display($item['action_code'] ?? '') ?></p>
              <div class="m33-jc-timeline-meta">
                <span class="m360-badge <?= m33_ux_h(m33_ux_status_badge_class((string)($item['status'] ?? ''))) ?>"><?= m33_ux_display($item['status'] ?? '—') ?></span>
                · user_id: <span class="m360-num"><?= m33_ux_display($item['user_id'] ?? '') ?></span>
                · <span class="m360-ltr"><?= m33_ux_display($item['timestamp'] ?? '') ?></span>
              </div>
              <?php if (trim((string)($item['note'] ?? '')) !== ''): ?>
                <p style="margin:0.5rem 0 0;font-size:0.85rem;"><?= m33_ux_display($item['note'] ?? '') ?></p>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="m360-diagnostic-block">
    <p style="margin:0;font-size:0.85rem;">منابع: erp_jobcard_change_history, erp_service_operation_change_history, erp_jobcard_part_usage_history, erp_purchase_request_history, erp_payment_history, erp_qc_check_history, erp_delivery_control_history — با بررسی امن وجود جدول.</p>
  </div>
</div>

<?php
if ($connection !== false) {
    @odbc_close($connection);
}
moghare360_render_shell_end();
