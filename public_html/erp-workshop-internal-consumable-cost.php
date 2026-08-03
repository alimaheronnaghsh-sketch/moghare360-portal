<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-workshop-internal-consumable-helper.php';
require_once __DIR__ . '/includes/m360-inventory-valuation-helper.php';
require_once __DIR__ . '/includes/m360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);
$ctx = m360_ws_require_actor_context();
m360_ws_require('workshop.internal_consumable.cost_view');

$jobcardId = (int)($_GET['jobcard_id'] ?? 0);
$rows = [];
$summary = null;
if (is_resource($conn) && $jobcardId > 0) {
    m360_ws_assert_jobcard_object_scope($conn, $jobcardId, (int)$ctx['company_id'], (bool)$ctx['is_owner']);
    $summary = m360_ws_jobcard_true_cost_summary($conn, $jobcardId);
    $rows = $summary['snapshots'] ?? [];
}

mirror_render_head('خلاصه بهای واقعی پرونده', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">خلاصه بهای واقعی پرونده تعمیر</h1>
  <p class="m360-muted">مصرف داخلی در صورتحساب مشتری درج نمی‌شود. بهای نامشخص به‌صورت صفر گزارش نمی‌شود.</p>
  <form method="get" class="m360-form">
    <label>پرونده<input name="jobcard_id" inputmode="numeric" value="<?= $jobcardId > 0 ? $jobcardId : '' ?>" required></label>
    <button class="m360-btn" type="submit">نمایش</button>
  </form>

  <?php if (is_array($summary)): ?>
    <?php if (!empty($summary['has_pending_cost'])): ?>
      <p class="m360-alert">این پرونده دارای اقلام با بهای داخلی تأییدنشده است.</p>
    <?php endif; ?>
    <h2 class="m360-section-title">الف) قطعات قابل‌فاکتور مشتری</h2>
    <p><?= m360_am_h((string)$summary['customer_billable_parts']) ?></p>
    <h2 class="m360-section-title">ب) مواد مصرفی داخلی تأییدشده (ناخالص)</h2>
    <p><?= m360_am_h((string)$summary['internal_consumable_gross']) ?></p>
    <h2 class="m360-section-title">ج) مواد مصرفی ابطال‌شده</h2>
    <p><?= m360_am_h((string)$summary['internal_consumable_reversed']) ?></p>
    <h2 class="m360-section-title">د) مواد مصرفی در انتظار تعیین بها</h2>
    <p>تعداد: <?= (int)$summary['internal_consumable_pending_count'] ?> — مقدار خالص: <?= m360_am_h((string)$summary['internal_consumable_pending_qty']) ?></p>
    <h2 class="m360-section-title">هـ) خدمات و دستمزد داخلی</h2>
    <p><?= $summary['labor_internal'] === null ? 'در حال حاضر در دسترس نیست' : m360_am_h((string)$summary['labor_internal']) ?></p>
    <h2 class="m360-section-title">و) خدمات بیرونی</h2>
    <p><?= $summary['external_services'] === null ? 'در حال حاضر در دسترس نیست' : m360_am_h((string)$summary['external_services']) ?></p>
    <h2 class="m360-section-title">ز) جمع هزینه داخلی تأییدشده (خالص)</h2>
    <p><strong><?= m360_am_h((string)$summary['confirmed_jobcard_true_cost']) ?></strong>
      <?php if (!empty($summary['has_pending_cost'])): ?>
        <span class="m360-muted">— این جمع «هزینه کامل» نیست چون اقلام معلق باقی است.</span>
      <?php endif; ?>
    </p>
    <h2 class="m360-section-title">ح) تعداد اقلام با بهای نامشخص</h2>
    <p><?= (int)$summary['internal_consumable_pending_count'] ?></p>
  <?php endif; ?>

  <table class="m360-table">
    <thead><tr><th>قلم</th><th>صادر</th><th>ابطال</th><th>وضعیت بها</th><th>جمع صادر</th><th>جمع ابطال</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= m360_am_h((string)($r['manual_description'] ?? ('INV#' . (int)($r['inventory_item_id'] ?? 0)))) ?></td>
        <td><?= m360_am_h((string)$r['quantity']) ?></td>
        <td><?= m360_am_h((string)($r['quantity_reversed'] ?? '0')) ?></td>
        <td><?= m360_am_h((string)($r['cost_status'] ?? '—')) ?></td>
        <td><?= m360_am_h((string)($r['internal_total_cost'] !== '' && $r['internal_total_cost'] !== null ? $r['internal_total_cost'] : '—')) ?></td>
        <td><?= m360_am_h((string)($r['reversed_total_cost'] ?? '—')) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($rows === [] && $jobcardId > 0): ?><tr><td colspan="6">داده‌ای نیست.</td></tr><?php endif; ?>
    </tbody>
  </table>
  <p>
    <a href="erp-inventory-internal-cost.php">تکمیل بهای اقلام معلق</a> |
    <a href="erp-workshop-internal-consumable-reversal-request.php">درخواست ابطال</a>
  </p>
</section>
<?php mirror_render_foot(); ?>
