<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Service Operation Workbench UX
 *
 * Mission 35 — SELECT read-only. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-service-operation-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'service');
$activeModule = 'service_operations';

$operations = [];
$kpi = ['total' => 0, 'in_progress' => 0, 'waiting_parts' => 0, 'done' => 0];
$errorMessage = '';
$connection = false;

$session = m35_ux_connect('service.operation.list');

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $operations = m35_ux_fetch_service_operations($connection);
    $kpi = m35_ux_fetch_kpi_counts($connection);
}

moghare360_render_shell_start('میز کار عملیات سرویس', $activeModule, $roleMode);
m35_ux_render_so_css_link();
?>

<div class="m35-so-board">
  <div class="m360-page-header">
    <div class="m360-page-header-eyebrow">Mission 35 — Service Operation UX</div>
    <h2 class="m360-page-header-title">میز کار عملیات تعمیرگاهی</h2>
    <p class="m360-page-header-meta">read-only — نقش: <?= moghare360_shell_h(strtoupper($roleMode)) ?></p>
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m35_ux_h($errorMessage) ?></div></div>
  <?php endif; ?>

  <div class="m360-page-toolbar">
    <a class="m360-btn m360-btn-primary m360-btn-sm" href="erp-service-operation-create.php">ثبت عملیات (M20)</a>
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-service-operation-board-ux.php?role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">برد وضعیت</a>
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-technician-workflow-ux.php?role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">گردش کار تکنسین</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-service-operation-readonly-list.php">لیست فنی M20</a>
  </div>

  <div class="m35-so-kpi-grid">
    <div class="m35-so-kpi"><div class="m35-so-kpi-label">Total Service Operations</div><div class="m35-so-kpi-value m360-num"><?= m35_ux_h((string)$kpi['total']) ?></div></div>
    <div class="m35-so-kpi is-progress"><div class="m35-so-kpi-label">In Progress</div><div class="m35-so-kpi-value m360-num"><?= m35_ux_h((string)$kpi['in_progress']) ?></div></div>
    <div class="m35-so-kpi is-waiting"><div class="m35-so-kpi-label">Waiting Parts</div><div class="m35-so-kpi-value m360-num"><?= m35_ux_h((string)$kpi['waiting_parts']) ?></div></div>
    <div class="m35-so-kpi is-done"><div class="m35-so-kpi-label">Done</div><div class="m35-so-kpi-value m360-num"><?= m35_ux_h((string)$kpi['done']) ?></div></div>
  </div>

  <div class="m360-card">
    <div class="m360-card-header"><h3 class="m360-card-title">عملیات سرویس</h3></div>
    <div class="m360-card-body" style="padding:0;">
      <?php if ($operations === []): ?>
        <div class="m35-so-empty" style="margin:1rem;">عملیات سرویسی یافت نشد.</div>
      <?php else: ?>
        <div class="m35-so-table-wrap">
          <table class="m35-so-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>JobCard</th>
                <th>عنوان</th>
                <th>وضعیت</th>
                <th>تکنسین</th>
                <th>ایجاد</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($operations as $row): ?>
                <?php
                $soId = (int)($row['service_operation_id'] ?? 0);
                $jcId = (int)($row['jobcard_id'] ?? 0);
                $status = (string)($row['service_status'] ?? '');
                ?>
                <tr>
                  <td class="m360-num"><?= m35_ux_display($row['service_operation_id'] ?? '') ?></td>
                  <td class="m360-num"><?= m35_ux_display($row['jobcard_id'] ?? '') ?></td>
                  <td><?= m35_ux_display($row['service_title'] ?? '') ?></td>
                  <td><span class="m360-badge <?= m35_ux_h(m35_ux_status_badge_class($status)) ?>"><?= m35_ux_display($status) ?></span></td>
                  <td class="m360-num"><?= m35_ux_display($row['assigned_to_user_id'] ?? '—') ?></td>
                  <td class="m360-ltr"><?= m35_ux_display($row['created_at'] ?? '') ?></td>
                  <td>
                    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-service-operation-detail-ux.php?service_operation_id=<?= m35_ux_h((string)$soId) ?>&role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">جزئیات</a>
                    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-detail-ux.php?jobcard_id=<?= m35_ux_h((string)$jcId) ?>&role=<?= m35_ux_h(rawurlencode($roleMode)) ?>">JobCard</a>
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
