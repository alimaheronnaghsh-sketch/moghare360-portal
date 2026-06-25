<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Service Operation Status Board UX
 *
 * Mission 35 — Visual kanban board. No drag/drop. No status write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-service-operation-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'service');
$activeModule = 'service_operations';

$grouped = [];
$errorMessage = '';
$connection = false;

$session = m35_ux_connect('service.operation.list');

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $operations = m35_ux_fetch_service_operations($connection);
    $grouped = m35_ux_group_by_status($operations);
}

moghare360_render_shell_start('برد وضعیت عملیات سرویس', $activeModule, $roleMode);
m35_ux_render_so_css_link();
?>

<div class="m35-so-board">
  <div class="m35-so-page-nav">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-service-operation-workbench-ux.php?role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">میز کار</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-technician-workflow-ux.php?role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">گردش کار تکنسین</a>
  </div>

  <div class="m360-page-header">
    <div class="m360-page-header-eyebrow">Status Board — Visualization Only</div>
    <h2 class="m360-page-header-title">برد وضعیت عملیات</h2>
    <p class="m360-page-header-meta">بدون drag/drop — بدون تغییر وضعیت</p>
  </div>

  <div class="m35-so-placeholder-note">
    این برد فقط نمایش پیشرفت است. تغییر وضعیت از صفحات controlled prototype انجام می‌شود.
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m35_ux_h($errorMessage) ?></div></div>
  <?php else: ?>
    <div class="m35-so-kanban">
      <?php foreach (M35_UX_BOARD_STATUSES as $status): ?>
        <?php $cards = $grouped[$status] ?? []; ?>
        <div class="m35-so-kanban-col">
          <div class="m35-so-kanban-col-header">
            <?= m35_ux_h($status) ?> <span class="m360-num">(<?= m35_ux_h((string)count($cards)) ?>)</span>
          </div>
          <div class="m35-so-kanban-cards">
            <?php if ($cards === []): ?>
              <div class="m35-so-empty">خالی</div>
            <?php else: ?>
              <?php foreach ($cards as $card): ?>
                <?php
                $soId = (int)($card['service_operation_id'] ?? 0);
                $jcId = (int)($card['jobcard_id'] ?? 0);
                ?>
                <article class="m35-so-kanban-card">
                  <p class="m35-so-kanban-card-title"><?= m35_ux_display($card['service_title'] ?? '') ?></p>
                  <p class="m35-so-kanban-card-meta">SO #<?= m35_ux_display($card['service_operation_id'] ?? '') ?> · JC #<?= m35_ux_display($card['jobcard_id'] ?? '') ?></p>
                  <p class="m35-so-kanban-card-meta">Tech: <?= m35_ux_display($card['assigned_to_user_id'] ?? '—') ?></p>
                  <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-service-operation-detail-ux.php?service_operation_id=<?= m35_ux_h((string)$soId) ?>&role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">جزئیات</a>
                  <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-detail-ux.php?jobcard_id=<?= m35_ux_h((string)$jcId) ?>&role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">JobCard</a>
                </article>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php
if ($connection !== false) {
    @odbc_close($connection);
}
moghare360_render_shell_end();
