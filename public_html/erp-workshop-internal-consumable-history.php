<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-workshop-internal-consumable-helper.php';
require_once __DIR__ . '/includes/m360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN', 'PARTS']);
m360_ws_require_any(['workshop.internal_consumable.create', 'workshop.internal_consumable.approve', 'workshop.internal_consumable.cost_view']);
$canCost = m360_ws_can('workshop.internal_consumable.cost_view');
$jobcardId = (int)($_GET['jobcard_id'] ?? 0);

$rows = [];
if (is_resource($conn)) {
    if ($jobcardId > 0) {
        m360_ws_assert_jobcard_object_scope($conn, $jobcardId);
        $rows = customer_core_fetch_rows(
            $conn,
            'SELECT TOP 100 * FROM dbo.erp_workshop_internal_consumable_requests WHERE jobcard_id=? ORDER BY request_id DESC',
            [$jobcardId]
        );
    } else {
        $rows = m360_ws_ic_list_by_status($conn, ['DRAFT', 'SUBMITTED', 'RETURNED', 'ISSUED', 'APPROVED', 'CANCELLED', 'VOIDED'], 100);
    }
}

mirror_render_head('سوابق مصرف داخلی', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">سوابق مواد و ملزومات مصرفی داخلی</h1>
  <p class="m360-alert m360-alert-warning">این موارد در صورتحساب مشتری درج نمی‌شوند.</p>
  <form method="get" class="m360-form">
    <label>فیلتر پرونده<input name="jobcard_id" inputmode="numeric" value="<?= $jobcardId > 0 ? $jobcardId : '' ?>"></label>
    <button class="m360-btn" type="submit">فیلتر</button>
  </form>
  <table class="m360-table">
    <thead><tr><th>شناسه</th><th>پرونده</th><th>واحد</th><th>وضعیت</th><th>اقلام</th><?php if ($canCost): ?><th>هزینه داخلی</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <?php
        $items = m360_ws_ic_filter_cost_rows(m360_ws_ic_fetch_items($conn, (int)$row['request_id']), $canCost);
        $sum = 0.0;
        $labels = [];
        foreach ($items as $it) {
            $labels[] = ($it['manual_description'] ?? ('INV#' . (int)($it['inventory_item_id'] ?? 0))) . ' × ' . $it['quantity'];
            if ($canCost && isset($it['internal_total_cost'])) {
                $sum += (float)$it['internal_total_cost'];
            }
        }
      ?>
      <tr>
        <td><?= (int)$row['request_id'] ?></td>
        <td><?= (int)$row['jobcard_id'] ?></td>
        <td><?= m360_am_h((string)$row['consuming_unit']) ?></td>
        <td><?= m360_am_h((string)$row['status']) ?></td>
        <td><?= m360_am_h(implode('؛ ', $labels)) ?></td>
        <?php if ($canCost): ?><td><?= m360_am_h((string)$sum) ?></td><?php endif; ?>
      </tr>
    <?php endforeach; ?>
    <?php if ($rows === []): ?><tr><td colspan="<?= $canCost ? 6 : 5 ?>">سابقه‌ای نیست.</td></tr><?php endif; ?>
    </tbody>
  </table>
  <?php if ($canCost && $jobcardId > 0): ?>
    <p>جمع هزینه داخلی پرونده: <?= m360_am_h((string)m360_ws_ic_jobcard_true_cost($conn, $jobcardId)) ?></p>
  <?php endif; ?>
  <p><a class="m360-btn" href="erp-workshop-internal-consumable-cost.php">گزارش هزینه داخلی</a></p>
</section>
<?php mirror_render_foot(); ?>
