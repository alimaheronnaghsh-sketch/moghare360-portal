<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Soft Run Flow Test — Read-Only End-to-End Check
 *
 * Mission 37 — No write. Safe empty states.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-release-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'owner');
$activeModule = 'soft_run_gate';
$jobcardId = m37_ux_parse_jobcard_id(1);

$jobcard = [];
$flowSteps = [];
$errorMessage = '';
$connection = false;

$session = m37_ux_connect();

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $jobcard = m37_ux_fetch_jobcard_row($connection, $jobcardId);
    $flowSteps = m37_ux_build_flow_steps($connection, $jobcardId, $jobcard);
}

moghare360_render_shell_start('تست جریان Soft Run', $activeModule, $roleMode);
m37_ux_render_release_css_link();
?>

<div class="m37-sr-board">
  <div class="m35-so-page-nav" style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.5rem;">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-soft-run-home.php?role=<?= m37_ux_h(rawurlencode($roleMode)) ?>">صفحه اصلی</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-moghare-ready.php?role=<?= m37_ux_h(rawurlencode($roleMode)) ?>">Moghare Ready</a>
  </div>

  <div class="m360-page-header">
    <div class="m360-page-header-eyebrow">Flow Test — Read Only</div>
    <h2 class="m360-page-header-title">تست جریان کامل JobCard #<?= m37_ux_h((string)$jobcardId) ?></h2>
    <p class="m360-page-header-meta">
      Customer → Vehicle → JobCard → Service → Parts → Purchase → Payment → QC → Delivery
    </p>
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m37_ux_h($errorMessage) ?></div></div>
  <?php else: ?>
    <?php if ($jobcard === []): ?>
      <div class="m37-sr-empty">JobCard #<?= m37_ux_h((string)$jobcardId) ?> یافت نشد — مراحل با وضعیت EMPTY/PENDING نمایش داده می‌شوند.</div>
    <?php else: ?>
      <div class="m37-sr-status-panel" style="margin-bottom:1rem;">
        JobCard: <strong><?= m37_ux_display($jobcard['jobcard_number'] ?? '') ?></strong>
        · Status: <?= m37_ux_display($jobcard['jobcard_status'] ?? '') ?>
      </div>
    <?php endif; ?>

    <div class="m37-sr-flow-timeline">
      <?php foreach ($flowSteps as $i => $step): ?>
        <div class="m37-sr-flow-step">
          <span class="m37-sr-flow-step-num"><?= m37_ux_h((string)($i + 1)) ?></span>
          <div>
            <p class="m37-sr-flow-step-title"><?= m37_ux_h((string)$step['title']) ?></p>
            <p class="m37-sr-flow-step-detail"><?= m37_ux_display((string)$step['detail']) ?></p>
          </div>
          <span class="m360-badge <?= m37_ux_h(m37_ux_flow_status_class((string)$step['status'])) ?>"><?= m37_ux_h((string)$step['status']) ?></span>
          <a class="m360-btn m360-btn-ghost m360-btn-sm" href="<?= m37_ux_h((string)$step['href']) ?>">باز کردن</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="m360-diagnostic-block">
    <p style="margin:0;font-size:0.85rem;">Flow test is read-only. No workflow write. Change jobcard: <span class="m360-ltr">?jobcard_id=N</span></p>
  </div>
</div>

<?php
if ($connection !== false) {
    @odbc_close($connection);
}
moghare360_render_shell_end();
