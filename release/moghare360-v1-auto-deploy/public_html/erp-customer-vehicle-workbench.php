<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Customer & Vehicle Workbench UX
 *
 * Mission 34 — SELECT read-only. M31 + M32. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-customer-vehicle-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'reception');
$activeModule = 'customers';

$qCustomer = m34_ux_get_query('customer');
$qPhone = m34_ux_get_query('phone');
$qPlate = m34_ux_get_query('plate');
$qVin = m34_ux_get_query('vin');

$kpi = ['customers' => 0, 'vehicles' => 0, 'relations' => 0, 'jobcards' => 0];
$customers = [];
$vehicles = [];
$relations = [];
$errorMessage = '';
$connection = false;

$session = m34_ux_connect();

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $kpi = m34_ux_fetch_kpi_counts($connection);
    $customers = m34_ux_fetch_customers($connection, $qCustomer, $qPhone);
    $vehicles = m34_ux_fetch_vehicles($connection, $qPlate, $qVin);
    $relations = m34_ux_fetch_relations_workbench($connection, $qCustomer, $qPhone, $qPlate, $qVin);
}

moghare360_render_shell_start('میز کار مشتری و خودرو', $activeModule, $roleMode);
m34_ux_render_cv_css_link();
?>

<div class="m34-cv-board">
  <div class="m360-page-header">
    <div class="m360-page-header-eyebrow">Mission 34 — Customer & Vehicle UX</div>
    <h2 class="m360-page-header-title">میز کار مشتری و خودرو</h2>
    <p class="m360-page-header-meta">جستجو و مشاهده read-only — نقش: <?= moghare360_shell_h(strtoupper($roleMode)) ?></p>
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m34_ux_h($errorMessage) ?></div></div>
  <?php endif; ?>

  <div class="m360-page-toolbar">
    <a class="m360-btn m360-btn-primary m360-btn-sm" href="erp-customer-vehicle-create-ux.php?role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">راهنمای ثبت مشتری/خودرو</a>
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-customer-vehicle-readonly-list.php">لیست فنی M15</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-jobcard-workbench.php?role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">میز کار JobCard</a>
  </div>

  <div class="m34-cv-kpi-grid">
    <div class="m34-cv-kpi"><div class="m34-cv-kpi-label">Total Customers</div><div class="m34-cv-kpi-value m360-num"><?= m34_ux_h((string)$kpi['customers']) ?></div></div>
    <div class="m34-cv-kpi is-vehicle"><div class="m34-cv-kpi-label">Total Vehicles</div><div class="m34-cv-kpi-value m360-num"><?= m34_ux_h((string)$kpi['vehicles']) ?></div></div>
    <div class="m34-cv-kpi is-relation"><div class="m34-cv-kpi-label">Active Relations</div><div class="m34-cv-kpi-value m360-num"><?= m34_ux_h((string)$kpi['relations']) ?></div></div>
    <div class="m34-cv-kpi is-jobcard"><div class="m34-cv-kpi-label">JobCards Linked</div><div class="m34-cv-kpi-value m360-num"><?= m34_ux_h((string)$kpi['jobcards']) ?></div></div>
  </div>

  <div class="m34-cv-lookup-panel">
    <h3 style="margin:0 0 0.75rem;font-size:1rem;">جستجوی پذیرش (GET)</h3>
    <form method="get" class="m34-cv-lookup-grid">
      <input type="hidden" name="role" value="<?= m34_ux_h($roleMode) ?>">
      <div class="m34-cv-lookup-field"><label>مشتری / نام</label><input type="text" name="customer" value="<?= m34_ux_h($qCustomer) ?>" placeholder="نام یا کد"></div>
      <div class="m34-cv-lookup-field"><label>موبایل</label><input type="text" name="phone" value="<?= m34_ux_h($qPhone) ?>" placeholder="09..."></div>
      <div class="m34-cv-lookup-field"><label>پلاک</label><input type="text" name="plate" value="<?= m34_ux_h($qPlate) ?>"></div>
      <div class="m34-cv-lookup-field"><label>VIN</label><input type="text" name="vin" value="<?= m34_ux_h($qVin) ?>"></div>
      <div><button type="submit" class="m360-btn m360-btn-primary m360-btn-sm">جستجو</button></div>
    </form>
  </div>

  <div class="m360-grid m360-grid-2">
    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">مشتریان</h3></div>
      <div class="m360-card-body" style="padding:0;">
        <?php if ($customers === []): ?>
          <div class="m34-cv-empty" style="margin:1rem;">مشتری یافت نشد.</div>
        <?php else: ?>
          <div class="m34-cv-table-wrap">
            <table class="m34-cv-table">
              <thead><tr><th>ID</th><th>نام</th><th>موبایل</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($customers as $row): ?>
                  <tr>
                    <td class="m360-num"><?= m34_ux_display($row['customer_id'] ?? '') ?></td>
                    <td><?= m34_ux_display($row['full_name'] ?? '') ?></td>
                    <td class="m360-ltr"><?= m34_ux_display($row['primary_mobile'] ?? '') ?></td>
                    <td><a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-customer-detail-ux.php?customer_id=<?= m34_ux_h($row['customer_id'] ?? '') ?>&role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">پروفایل</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">خودروها</h3></div>
      <div class="m360-card-body" style="padding:0;">
        <?php if ($vehicles === []): ?>
          <div class="m34-cv-empty" style="margin:1rem;">خودرو یافت نشد.</div>
        <?php else: ?>
          <div class="m34-cv-table-wrap">
            <table class="m34-cv-table">
              <thead><tr><th>ID</th><th>خودرو</th><th>پلاک</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($vehicles as $row): ?>
                  <tr>
                    <td class="m360-num"><?= m34_ux_display($row['vehicle_id'] ?? '') ?></td>
                    <td><?= m34_ux_display(trim(($row['brand'] ?? '') . ' ' . ($row['model'] ?? ''))) ?></td>
                    <td class="m360-ltr"><?= m34_ux_display($row['plate_number'] ?? '') ?></td>
                    <td><a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-vehicle-detail-ux.php?vehicle_id=<?= m34_ux_h($row['vehicle_id'] ?? '') ?>&role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">پروفایل</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="m360-card">
    <div class="m360-card-header"><h3 class="m360-card-title">ارتباط‌های مشتری-خودرو</h3></div>
    <div class="m360-card-body" style="padding:0;">
      <?php if ($relations === []): ?>
        <div class="m34-cv-empty" style="margin:1rem;">ارتباطی یافت نشد.</div>
      <?php else: ?>
        <div class="m34-cv-table-wrap">
          <table class="m34-cv-table">
            <thead><tr><th>Relation</th><th>مشتری</th><th>خودرو</th><th>نوع</th><th>وضعیت</th></tr></thead>
            <tbody>
              <?php foreach ($relations as $row): ?>
                <tr>
                  <td class="m360-num"><?= m34_ux_display($row['relation_id'] ?? '') ?></td>
                  <td><a href="erp-customer-detail-ux.php?customer_id=<?= m34_ux_h($row['customer_id'] ?? '') ?>&role=<?= m34_ux_h(rawurlencode($roleMode)) ?>"><?= m34_ux_display($row['full_name'] ?? '') ?></a></td>
                  <td><a href="erp-vehicle-detail-ux.php?vehicle_id=<?= m34_ux_h($row['vehicle_id'] ?? '') ?>&role=<?= m34_ux_h(rawurlencode($roleMode)) ?>"><?= m34_ux_display(trim(($row['brand'] ?? '') . ' ' . ($row['model'] ?? ''))) ?></a></td>
                  <td><span class="m34-cv-relation-badge"><?= m34_ux_display($row['relation_type'] ?? '') ?></span></td>
                  <td><span class="m360-badge <?= m34_ux_h(m34_ux_status_badge_class((string)($row['lifecycle_state'] ?? ''))) ?>"><?= m34_ux_display($row['lifecycle_state'] ?? '') ?></span></td>
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
