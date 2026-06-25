<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP JobCard Workbench UX
 *
 * Mission 33 — SELECT read-only. M31 Design System + M32 Shell. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'owner');
$activeModule = 'jobcards';

$listRows = [];
$kpi = ['active' => 0, 'waiting_parts' => 0, 'qc_pending' => 0, 'ready_delivery' => 0];
$errorMessage = '';
$connection = false;

$session = m33_ux_connect('jobcard.list');

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $listRows = m33_ux_fetch_jobcard_list($connection);
    $kpi = m33_ux_fetch_kpi_counts($connection);
}

moghare360_render_shell_start('میز کار کارت کار', $activeModule, $roleMode);
m33_ux_render_jobcard_css_link();
?>

<div class="m33-jc-board">
  <div class="m360-page-header">
    <div class="m360-page-header-eyebrow">Mission 33 — JobCard UX</div>
    <h2 class="m360-page-header-title">میز کار کارت‌های کار</h2>
    <p class="m360-page-header-meta">نمایش read-only — بدون write — نقش: <?= moghare360_shell_h(strtoupper($roleMode)) ?></p>
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger">
      <div><div class="m360-alert-title">خطا</div><?= m33_ux_h($errorMessage) ?></div>
    </div>
  <?php endif; ?>

  <div class="m33-jc-board-toolbar">
    <div class="m360-toolbar-actions">
      <a class="m360-btn m360-btn-primary m360-btn-sm" href="erp-jobcard-create-ux.php?role=<?= m33_ux_h(rawurlencode($roleMode)) ?>">ثبت کارت کار جدید</a>
      <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-jobcard-readonly-list.php">لیست فنی M17</a>
    </div>
    <span class="m360-badge m360-badge-neutral">READ-ONLY UX</span>
  </div>

  <div class="m33-jc-kpi-grid">
    <div class="m33-jc-kpi">
      <div class="m33-jc-kpi-label">کارت‌های فعال</div>
      <div class="m33-jc-kpi-value m360-num"><?= m33_ux_h((string)$kpi['active']) ?></div>
    </div>
    <div class="m33-jc-kpi is-warning">
      <div class="m33-jc-kpi-label">در انتظار قطعه</div>
      <div class="m33-jc-kpi-value m360-num"><?= m33_ux_h((string)$kpi['waiting_parts']) ?></div>
    </div>
    <div class="m33-jc-kpi is-accent">
      <div class="m33-jc-kpi-label">QC معلق</div>
      <div class="m33-jc-kpi-value m360-num"><?= m33_ux_h((string)$kpi['qc_pending']) ?></div>
    </div>
    <div class="m33-jc-kpi is-success">
      <div class="m33-jc-kpi-label">آماده تحویل</div>
      <div class="m33-jc-kpi-value m360-num"><?= m33_ux_h((string)$kpi['ready_delivery']) ?></div>
    </div>
  </div>

  <div class="m360-card">
    <div class="m360-card-header">
      <h3 class="m360-card-title">کارت‌های کار</h3>
      <p class="m360-card-subtitle">JobCard ID · وضعیت · مشتری · خودرو · تاریخ ایجاد</p>
    </div>
    <div class="m360-card-body" style="padding:0;">
      <?php if ($listRows === []): ?>
        <div class="m33-jc-empty" style="margin:1rem;">کارت کاری یافت نشد.</div>
      <?php else: ?>
        <div class="m33-jc-table-wrap">
          <table class="m33-jc-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>شماره</th>
                <th>وضعیت</th>
                <th>مشتری</th>
                <th>خودرو</th>
                <th>پذیرش</th>
                <th>ایجاد</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($listRows as $row): ?>
                <?php
                $jcId = (int)($row['jobcard_id'] ?? 0);
                $vehicle = trim(($row['brand'] ?? '') . ' ' . ($row['model'] ?? ''));
                $status = (string)($row['jobcard_status'] ?? '');
                ?>
                <tr>
                  <td class="m360-num"><?= m33_ux_display($row['jobcard_id'] ?? '') ?></td>
                  <td><?= m33_ux_display($row['jobcard_number'] ?? '') ?></td>
                  <td><span class="m360-badge <?= m33_ux_h(m33_ux_status_badge_class($status)) ?>"><?= m33_ux_display($status) ?></span></td>
                  <td><?= m33_ux_display($row['full_name'] ?? '') ?></td>
                  <td><?= m33_ux_display($vehicle) ?> <span class="m360-ltr"><?= m33_ux_display($row['plate_number'] ?? '') ?></span></td>
                  <td class="m360-ltr"><?= m33_ux_display($row['reception_at'] ?? '') ?></td>
                  <td class="m360-ltr"><?= m33_ux_display($row['created_at'] ?? '') ?></td>
                  <td>
                    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-detail-ux.php?jobcard_id=<?= m33_ux_h((string)$jcId) ?>&role=<?= m33_ux_h(rawurlencode($roleMode)) ?>">جزئیات</a>
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
