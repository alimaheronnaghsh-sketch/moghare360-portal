<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Vehicle Detail UX
 *
 * Mission 34 — SELECT read-only vehicle profile. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-customer-vehicle-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'reception');
$activeModule = 'vehicles';

$vehicle = [];
$customers = [];
$jobcards = [];
$serviceOps = [];
$summaries = ['parts' => 0, 'payments' => 0, 'qc' => 0];
$vehicleId = 0;
$errorMessage = '';
$connection = false;

$session = m34_ux_connect();

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $vehicleId = m34_ux_resolve_vehicle_id($connection, m34_ux_parse_entity_id('vehicle_id'));
    $vehicle = m34_ux_fetch_vehicle_detail($connection, $vehicleId);
    $customers = m34_ux_fetch_vehicle_customers($connection, $vehicleId);
    $jobcards = m34_ux_fetch_vehicle_jobcards($connection, $vehicleId);
    $serviceOps = m34_ux_fetch_vehicle_service_ops($connection, $vehicleId);
    $summaries = m34_ux_fetch_vehicle_summaries($connection, $vehicleId);
}

moghare360_render_shell_start('پروفایل خودرو #' . $vehicleId, $activeModule, $roleMode);
m34_ux_render_cv_css_link();
?>

<div class="m34-cv-board">
  <div class="m34-cv-page-nav">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-customer-vehicle-workbench.php?role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">میز کار</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-customer-vehicle-create-ux.php?role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">راهنمای ثبت</a>
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m34_ux_h($errorMessage) ?></div></div>
  <?php elseif ($vehicle === []): ?>
    <div class="m34-cv-empty">خودرو یافت نشد.</div>
  <?php else: ?>
    <article class="m34-cv-profile-card is-vehicle">
      <div class="m34-cv-profile-eyebrow">Vehicle Profile</div>
      <h2 class="m34-cv-profile-name"><?= m34_ux_display(trim(($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? ''))) ?></h2>
      <p class="m34-cv-profile-meta">
        کد: <?= m34_ux_display($vehicle['vehicle_code'] ?? '') ?> ·
        پلاک: <span class="m360-ltr"><?= m34_ux_display($vehicle['plate_number'] ?? '') ?></span> ·
        VIN: <span class="m360-ltr"><?= m34_ux_display($vehicle['vin'] ?? '') ?></span>
      </p>
      <p class="m34-cv-profile-meta" style="margin-top:0.5rem;">
        سال: <?= m34_ux_display($vehicle['production_year'] ?? '') ?> ·
        رنگ: <?= m34_ux_display($vehicle['color'] ?? '') ?> ·
        کیلومتر: <?= m34_ux_display($vehicle['mileage'] ?? '') ?> ·
        <span class="m360-badge <?= m34_ux_h(m34_ux_status_badge_class((string)($vehicle['lifecycle_state'] ?? ''))) ?>"><?= m34_ux_display($vehicle['lifecycle_state'] ?? '') ?></span>
      </p>
    </article>

    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">مالک / مشتری متصل</h3></div>
      <div class="m360-card-body">
        <?php if ($customers === []): ?>
          <div class="m34-cv-empty">مشتری متصل یافت نشد.</div>
        <?php else: ?>
          <div class="m34-cv-binding-grid">
            <?php foreach ($customers as $c): ?>
              <div class="m34-cv-binding-card">
                <a href="erp-customer-detail-ux.php?customer_id=<?= m34_ux_h($c['customer_id'] ?? '') ?>&role=<?= m34_ux_h(rawurlencode($roleMode)) ?>"><?= m34_ux_display($c['full_name'] ?? '') ?></a>
                <p style="margin:0.35rem 0 0;font-size:0.85rem;"><span class="m360-ltr"><?= m34_ux_display($c['primary_mobile'] ?? '') ?></span></p>
                <span class="m34-cv-relation-badge"><?= m34_ux_display($c['relation_type'] ?? '') ?><?= ($c['is_primary_owner'] ?? '') === '1' ? ' · PRIMARY' : '' ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="m360-grid m360-grid-3">
      <div class="m34-cv-kpi"><div class="m34-cv-kpi-label">Parts Usage</div><div class="m34-cv-kpi-value m360-num"><?= m34_ux_h((string)$summaries['parts']) ?></div></div>
      <div class="m34-cv-kpi is-relation"><div class="m34-cv-kpi-label">Payments</div><div class="m34-cv-kpi-value m360-num"><?= m34_ux_h((string)$summaries['payments']) ?></div></div>
      <div class="m34-cv-kpi is-jobcard"><div class="m34-cv-kpi-label">QC Checks</div><div class="m34-cv-kpi-value m360-num"><?= m34_ux_h((string)$summaries['qc']) ?></div></div>
    </div>

    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">تاریخچه کارت کار</h3></div>
      <div class="m360-card-body" style="padding:0;">
        <?php if ($jobcards === []): ?>
          <div class="m34-cv-empty" style="margin:1rem;">کارت کاری یافت نشد.</div>
        <?php else: ?>
          <div class="m34-cv-table-wrap">
            <table class="m34-cv-table">
              <thead><tr><th>JobCard</th><th>وضعیت</th><th>پذیرش</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($jobcards as $jc): ?>
                  <tr>
                    <td><?= m34_ux_display($jc['jobcard_number'] ?? '') ?></td>
                    <td><span class="m360-badge <?= m34_ux_h(m34_ux_status_badge_class((string)($jc['jobcard_status'] ?? ''))) ?>"><?= m34_ux_display($jc['jobcard_status'] ?? '') ?></span></td>
                    <td class="m360-ltr"><?= m34_ux_display($jc['reception_at'] ?? '') ?></td>
                    <td><a href="erp-jobcard-detail-ux.php?jobcard_id=<?= m34_ux_h($jc['jobcard_id'] ?? '') ?>&role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">جزئیات</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($serviceOps !== []): ?>
    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">عملیات سرویس مرتبط</h3></div>
      <div class="m360-card-body">
        <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:0.5rem;">
          <?php foreach ($serviceOps as $op): ?>
            <li style="display:flex;justify-content:space-between;gap:0.5rem;font-size:0.9rem;">
              <span><?= m34_ux_display($op['service_title'] ?? '') ?> (<?= m34_ux_display($op['jobcard_number'] ?? '') ?>)</span>
              <span class="m360-badge <?= m34_ux_h(m34_ux_status_badge_class((string)($op['service_status'] ?? ''))) ?>"><?= m34_ux_display($op['service_status'] ?? '') ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">اقدامات</h3></div>
      <div class="m360-card-body">
        <div class="m34-cv-action-grid">
          <a class="m34-cv-action-card" href="erp-jobcard-create-ux.php?role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">ثبت کارت کار</a>
          <a class="m34-cv-action-card" href="erp-jobcard-detail-ux.php?jobcard_id=<?= m34_ux_h((string)($jobcards[0]['jobcard_id'] ?? '1')) ?>&role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">آخرین JobCard UX</a>
          <a class="m34-cv-action-card" href="erp-service-operation-readonly-list.php">لیست عملیات سرویس</a>
          <a class="m34-cv-action-card" href="erp-jobcard-part-readonly-list.php">مصرف قطعه</a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php
if ($connection !== false) {
    @odbc_close($connection);
}
moghare360_render_shell_end();
