<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Technician Workflow UX
 *
 * Mission 35 — Technician readiness view. No assignment/status write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-service-operation-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'service');
$activeModule = 'service_operations';

$assignedUserId = m35_ux_parse_int('assigned_to_user_id', 0);
$filterUserId = $assignedUserId > 0 ? $assignedUserId : null;

$operations = [];
$errorMessage = '';
$connection = false;

$session = m35_ux_connect('service.operation.list');

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $operations = m35_ux_fetch_service_operations($connection, $filterUserId);
}

moghare360_render_shell_start('گردش کار تکنسین', $activeModule, $roleMode);
m35_ux_render_so_css_link();
?>

<div class="m35-so-board">
  <div class="m35-so-page-nav">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-service-operation-workbench-ux.php?role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">میز کار</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-service-operation-board-ux.php?role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">برد وضعیت</a>
  </div>

  <div class="m360-page-header">
    <div class="m360-page-header-eyebrow">Technician Workflow — Read Only</div>
    <h2 class="m360-page-header-title">گردش کار تکنسین</h2>
    <p class="m360-page-header-meta">
      <?php if ($assignedUserId > 0): ?>
        فیلتر assigned_to_user_id: <span class="m360-num"><?= m35_ux_h((string)$assignedUserId) ?></span>
      <?php else: ?>
        نمایش همه عملیات — برای فیلتر: <span class="m360-ltr">?assigned_to_user_id=10001</span>
      <?php endif; ?>
    </p>
  </div>

  <div class="m35-so-placeholder-note">
    <strong>Assignment Placeholder:</strong> تخصیص واقعی تکنسین هنوز از طریق controlled prototype انجام می‌شود. این صفحه فقط readiness و visibility است — بدون status update و بدون assignment write.
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m35_ux_h($errorMessage) ?></div></div>
  <?php elseif ($operations === []): ?>
    <div class="m35-so-empty">کارت کاری برای نمایش یافت نشد.</div>
  <?php else: ?>
    <div class="m35-so-binding-grid">
      <?php foreach ($operations as $op): ?>
        <?php
        $soId = (int)($op['service_operation_id'] ?? 0);
        $jcId = (int)($op['jobcard_id'] ?? 0);
        $status = (string)($op['service_status'] ?? '');
        $waitingParts = $status === 'WAITING_PARTS';
        $cardClass = 'm35-so-tech-card' . ($waitingParts ? ' is-waiting' : '');
        ?>
        <article class="<?= m35_ux_h($cardClass) ?>">
          <h4 class="m35-so-tech-card-title"><?= m35_ux_display($op['service_title'] ?? '') ?></h4>
          <p class="m35-so-tech-card-meta">
            JobCard #<?= m35_ux_display($op['jobcard_id'] ?? '') ?> ·
            SO #<?= m35_ux_display($op['service_operation_id'] ?? '') ?>
          </p>
          <p class="m35-so-tech-card-meta">
            <span class="m360-badge <?= m35_ux_h(m35_ux_status_badge_class($status)) ?>"><?= m35_ux_display($status) ?></span>
            <?php if ($waitingParts): ?>
              <span class="m360-badge m360-badge-warning">WAITING PARTS</span>
            <?php endif; ?>
          </p>
          <p class="m35-so-tech-card-meta">assigned_to: <span class="m360-num"><?= m35_ux_display($op['assigned_to_user_id'] ?? '—') ?></span></p>
          <div style="display:flex;flex-wrap:wrap;gap:0.35rem;margin-top:0.5rem;">
            <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-service-operation-detail-ux.php?service_operation_id=<?= m35_ux_h((string)$soId) ?>&role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">جزئیات</a>
            <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-detail-ux.php?jobcard_id=<?= m35_ux_h((string)$jcId) ?>&role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">JobCard</a>
            <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-part-use.php">قطعه M24</a>
            <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-purchase-request-create.php">خرید M26</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php
if ($connection !== false) {
    @odbc_close($connection);
}
moghare360_render_shell_end();
