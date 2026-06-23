<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Customer Detail UX
 *
 * Mission 34 — SELECT read-only customer profile. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-customer-vehicle-ux-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'reception');
$activeModule = 'customers';

$customer = [];
$phones = [];
$linkedVehicles = [];
$jobcards = [];
$paymentSummary = [];
$serviceHistory = [];
$customerId = 0;
$errorMessage = '';
$connection = false;

$session = m34_ux_connect();

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $customerId = m34_ux_resolve_customer_id($connection, m34_ux_parse_entity_id('customer_id'));
    $customer = m34_ux_fetch_customer_detail($connection, $customerId);
    $phones = m34_ux_fetch_customer_phones($connection, $customerId);
    $linkedVehicles = m34_ux_fetch_customer_vehicles($connection, $customerId);
    $jobcards = m34_ux_fetch_customer_jobcards($connection, $customerId);
    $paymentSummary = m34_ux_fetch_customer_payment_summary($connection, $customerId);
    $serviceHistory = m34_ux_fetch_service_history_timeline($connection, $customerId);
}

moghare360_render_shell_start('پروفایل مشتری #' . $customerId, $activeModule, $roleMode);
m34_ux_render_cv_css_link();
?>

<div class="m34-cv-board">
  <div class="m34-cv-page-nav">
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-customer-vehicle-workbench.php?role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">میز کار</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-customer-vehicle-create-ux.php?role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">راهنمای ثبت</a>
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m34_ux_h($errorMessage) ?></div></div>
  <?php elseif ($customer === []): ?>
    <div class="m34-cv-empty">مشتری یافت نشد.</div>
  <?php else: ?>
    <article class="m34-cv-profile-card is-customer">
      <div class="m34-cv-profile-eyebrow">Customer Profile</div>
      <h2 class="m34-cv-profile-name"><?= m34_ux_display($customer['full_name'] ?? '') ?></h2>
      <p class="m34-cv-profile-meta">
        کد: <?= m34_ux_display($customer['customer_code'] ?? '') ?> ·
        نوع: <?= m34_ux_display($customer['customer_type'] ?? '') ?> ·
        <span class="m360-badge <?= m34_ux_h(m34_ux_status_badge_class((string)($customer['lifecycle_state'] ?? ''))) ?>"><?= m34_ux_display($customer['lifecycle_state'] ?? '') ?></span>
      </p>
      <p class="m34-cv-profile-meta" style="margin-top:0.5rem;">
        موبایل: <span class="m360-ltr"><?= m34_ux_display($customer['primary_mobile'] ?? '') ?></span>
        <?php if (trim((string)($customer['email'] ?? '')) !== ''): ?> · ایمیل: <?= m34_ux_display($customer['email'] ?? '') ?><?php endif; ?>
        <?php if (trim((string)($customer['city'] ?? '')) !== ''): ?> · شهر: <?= m34_ux_display($customer['city'] ?? '') ?><?php endif; ?>
      </p>
    </article>

    <?php if ($phones !== []): ?>
    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">شماره‌های تماس</h3></div>
      <div class="m360-card-body">
        <div class="m34-cv-phone-chips">
          <?php foreach ($phones as $phone): ?>
            <span class="m34-cv-phone-chip <?= ($phone['is_primary'] ?? '') === '1' ? 'is-primary' : '' ?>">
              <?= m34_ux_display($phone['phone_type'] ?? '') ?> · <span class="m360-ltr"><?= m34_ux_display($phone['phone_number'] ?? '') ?></span>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">خودروهای مرتبط</h3></div>
      <div class="m360-card-body">
        <?php if ($linkedVehicles === []): ?>
          <div class="m34-cv-empty">خودروی مرتبط یافت نشد.</div>
        <?php else: ?>
          <div class="m34-cv-binding-grid">
            <?php foreach ($linkedVehicles as $v): ?>
              <div class="m34-cv-binding-card">
                <a href="erp-vehicle-detail-ux.php?vehicle_id=<?= m34_ux_h($v['vehicle_id'] ?? '') ?>&role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">
                  <?= m34_ux_display(trim(($v['brand'] ?? '') . ' ' . ($v['model'] ?? ''))) ?>
                </a>
                <p style="margin:0.35rem 0 0;font-size:0.85rem;">پلاک: <span class="m360-ltr"><?= m34_ux_display($v['plate_number'] ?? '') ?></span></p>
                <span class="m34-cv-relation-badge"><?= m34_ux_display($v['relation_type'] ?? '') ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">تاریخچه کارت کار</h3></div>
      <div class="m360-card-body" style="padding:0;">
        <?php if ($jobcards === []): ?>
          <div class="m34-cv-empty" style="margin:1rem;">کارت کاری یافت نشد.</div>
        <?php else: ?>
          <div class="m34-cv-table-wrap">
            <table class="m34-cv-table">
              <thead><tr><th>JobCard</th><th>خودرو</th><th>وضعیت</th><th>پذیرش</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($jobcards as $jc): ?>
                  <tr>
                    <td><?= m34_ux_display($jc['jobcard_number'] ?? '') ?></td>
                    <td><?= m34_ux_display(trim(($jc['brand'] ?? '') . ' ' . ($jc['model'] ?? ''))) ?></td>
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

    <?php if ($paymentSummary !== []): ?>
    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">خلاصه پرداخت (read-only)</h3></div>
      <div class="m360-card-body">
        <p>تعداد پرداخت: <strong class="m360-num"><?= m34_ux_display($paymentSummary['payment_count'] ?? '0') ?></strong>
        · مجموع دریافتی: <strong class="m360-num"><?= m34_ux_display($paymentSummary['received_total'] ?? '0') ?></strong></p>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($serviceHistory !== []): ?>
    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">تایم‌لاین خدمات</h3></div>
      <div class="m360-card-body">
        <div class="m34-cv-service-timeline">
          <?php foreach ($serviceHistory as $item): ?>
            <article class="m34-cv-timeline-item">
              <strong><?= m34_ux_display($item['action_code'] ?? '') ?></strong> — <?= m34_ux_display($item['jobcard_number'] ?? '') ?>
              <div style="font-size:0.8rem;margin-top:0.25rem;">
                <span class="m360-badge m360-badge-neutral"><?= m34_ux_display($item['new_status'] ?? '') ?></span>
                · user <?= m34_ux_display($item['changed_by_user_id'] ?? '') ?>
                · <span class="m360-ltr"><?= m34_ux_display($item['changed_at'] ?? '') ?></span>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="m360-card">
      <div class="m360-card-header"><h3 class="m360-card-title">اقدامات</h3></div>
      <div class="m360-card-body">
        <div class="m34-cv-action-grid">
          <a class="m34-cv-action-card" href="erp-customer-vehicle-create.php">ثبت مشتری/خودرو (M15)</a>
          <a class="m34-cv-action-card" href="erp-jobcard-create-ux.php?role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">ثبت کارت کار (UX)</a>
          <a class="m34-cv-action-card" href="erp-jobcard-workbench.php?role=<?= m34_ux_h(rawurlencode($roleMode)) ?>">میز کار JobCard</a>
          <a class="m34-cv-action-card" href="erp-payment-readonly-list.php">لیست پرداخت‌ها</a>
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
