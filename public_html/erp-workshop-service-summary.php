<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-workshop-service-line-helper.php';
require_once __DIR__ . '/includes/m360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN']);
$ctx = m360_ws_require_actor_context();
$jobcardId = (int)($_GET['jobcard_id'] ?? 0);
m360_ws_assert_jobcard_object_scope($conn, $jobcardId);
m360_ws_require_any([
    'workshop.service_line.create_no_price',
    'workshop.service_line.view_price',
    'workshop.service_line.review',
    'workshop.jobcard.view',
], $jobcardId);

$canViewPrice = m360_ws_can('workshop.service_line.view_price') || m360_ws_can('workshop.service_line.price');
$lines = is_resource($conn) ? m360_ws_sl_list_for_jobcard($conn, $jobcardId) : [];
$totals = is_resource($conn) ? m360_ws_sl_summary_totals($conn, $jobcardId) : [
    'subtotal_irr' => 0, 'line_count' => 0, 'priced_count' => 0, 'pending_additional' => 0,
];

$grouped = [];
foreach ($lines as $r) {
    $cat = strtoupper((string)($r['sales_category'] ?? ''));
    $grouped[$cat][] = m360_ws_sl_public_row($r, $canViewPrice);
}

mirror_render_head('خلاصه خدمات فروش', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">خلاصه خدمات پرونده</h1>
  <p>
    <a href="erp-workshop-service-entry.php?jobcard_id=<?= $jobcardId ?>">ثبت خدمت</a>
    · <a href="erp-workshop-service-pricing.php?jobcard_id=<?= $jobcardId ?>">قیمت‌گذاری</a>
    · <a href="erp-hall-jobcard-detail.php?jobcard_id=<?= $jobcardId ?>">پرونده سالن</a>
  </p>

  <?php if ((int)$totals['pending_additional'] > 0): ?>
    <p class="m360-alert m360-alert-error">هشدار: <?= (int)$totals['pending_additional'] ?> خدمت اضافی هنوز برای فاکتور آماده نیست / نیاز به تأیید مشتری دارد.</p>
  <?php endif; ?>

  <?php if ($canViewPrice): ?>
    <p><strong>جمع خدمات (سرور):</strong> <?= m360_am_h(m360_ws_sl_format_price_irr((int)$totals['subtotal_irr'])) ?>
      — <?= (int)$totals['priced_count'] ?> از <?= (int)$totals['line_count'] ?> خط</p>
  <?php else: ?>
    <p class="m360-muted">مبالغ برای نقش شما نمایش داده نمی‌شود. تعداد خطوط: <?= (int)$totals['line_count'] ?></p>
  <?php endif; ?>

  <?php foreach ($grouped as $cat => $items): ?>
    <h2 class="m360-section-title"><?= m360_am_h(m360_ws_sl_category_label_fa($cat)) ?></h2>
    <table class="m360-table">
      <thead>
        <tr>
          <th>خدمت</th>
          <th>تکنسین</th>
          <th>دقیقه</th>
          <th>وضعیت</th>
          <th>محدوده</th>
          <?php if ($canViewPrice): ?><th>مبلغ</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?= m360_am_h((string)$it['display_title']) ?></td>
            <td><?= (int)($it['created_by_user_id'] ?? 0) ?></td>
            <td><?= (int)($it['actual_minutes'] ?? 0) ?></td>
            <td><?= m360_am_h((string)$it['status']) ?></td>
            <td><?= m360_am_h((string)($it['agreement_scope'] ?? '')) ?></td>
            <?php if ($canViewPrice): ?>
              <td><?= m360_am_h((string)($it['price_irr_display'] ?? '—')) ?></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endforeach; ?>
  <?php if ($grouped === []): ?>
    <p class="m360-muted">هنوز خط خدمتی ثبت نشده است.</p>
  <?php endif; ?>
</section>
<?php mirror_render_foot(); ?>
