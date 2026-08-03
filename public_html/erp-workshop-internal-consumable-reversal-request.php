<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-workshop-ic-reversal-helper.php';
require_once __DIR__ . '/includes/m360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN']);
$ctx = m360_ws_require_actor_context();
m360_ws_require('workshop.internal_consumable.reversal_request');

$msg = '';
$canCost = m360_ws_can('workshop.internal_consumable.cost_view') || (bool)$ctx['is_owner'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && is_resource($conn)) {
    $res = m360_ws_ic_reversal_request(
        $conn,
        (int)($_POST['request_item_id'] ?? 0),
        (float)($_POST['reversal_qty'] ?? 0),
        (string)($_POST['reason_category'] ?? ''),
        (string)($_POST['reason_detail'] ?? ''),
        (int)$ctx['user_id'],
        (int)$ctx['company_id'],
        (bool)$ctx['is_owner']
    );
    $msg = $res['message'];
}

$issued = [];
if (is_resource($conn)) {
    $params = [];
    $companySql = '1=1';
    if (!(bool)$ctx['is_owner']) {
        $companySql = 'r.company_id=?';
        $params[] = (int)$ctx['company_id'];
    }
    $issued = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 80 r.request_id, r.jobcard_id, r.status, r.usage_reason, i.request_item_id, i.quantity,
                i.quantity_reversed, i.unit_of_measure, i.manual_description, i.inventory_item_id,
                i.inventory_movement_id, i.cost_status, i.internal_unit_cost, i.internal_total_cost,
                i.customer_billable, i.invoice_excluded
         FROM dbo.erp_workshop_internal_consumable_requests r
         INNER JOIN dbo.erp_workshop_internal_consumable_request_items i ON i.request_id=r.request_id
         WHERE r.status IN (N'ISSUED', N'REVERSAL_REJECTED', N'REVERSAL_REQUESTED')
           AND (ISNULL(i.quantity_reversed,0) < i.quantity)
           AND ({$companySql})
         ORDER BY r.request_id DESC",
        $params
    );
    $issued = m360_ws_ic_filter_cost_rows($issued, $canCost);
}

mirror_render_head('درخواست ابطال مصرف داخلی', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">درخواست ابطال مصرف داخلی</h1>
  <p class="m360-muted">ابطال مصرف صادرشده فقط با تأیید مستقل انجام می‌شود. حرکت انبار اصلی حذف نمی‌شود. این مورد در صورتحساب مشتری درج نمی‌شود.</p>
  <?php if ($msg !== ''): ?><p class="m360-alert"><?= m360_am_h($msg) ?></p><?php endif; ?>
  <table class="m360-table">
    <thead>
      <tr>
        <th>قلم</th><th>پرونده</th><th>صادر</th><th>ابطال‌شده</th><th>خالص</th><th>وضعیت بها</th><th>درخواست ابطال</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($issued as $r): ?>
      <?php
        $net = (float)$r['quantity'] - (float)($r['quantity_reversed'] ?? 0);
        $label = (string)($r['manual_description'] ?? '');
        if ($label === '') {
            $label = 'INV#' . (int)($r['inventory_item_id'] ?? 0);
        }
      ?>
      <tr>
        <td><?= m360_am_h($label) ?> (#<?= (int)$r['request_item_id'] ?>)</td>
        <td><?= (int)$r['jobcard_id'] ?></td>
        <td><?= m360_am_h((string)$r['quantity']) ?></td>
        <td><?= m360_am_h((string)($r['quantity_reversed'] ?? '0')) ?></td>
        <td><?= m360_am_h((string)$net) ?></td>
        <td><?= m360_am_h((string)($r['cost_status'] ?? '—')) ?></td>
        <td>
          <form method="post" class="m360-form">
            <input type="hidden" name="request_item_id" value="<?= (int)$r['request_item_id'] ?>">
            <label>مقدار <input type="number" step="0.0001" min="0.0001" max="<?= m360_am_h((string)$net) ?>" name="reversal_qty" value="<?= m360_am_h((string)$net) ?>" required></label>
            <label>دلیل
              <select name="reason_category" required>
                <?php foreach (M360_WS_IC_REVERSAL_REASONS as $code => $fa): ?>
                  <option value="<?= m360_am_h($code) ?>"><?= m360_am_h($fa) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>توضیح <textarea name="reason_detail" required rows="2"></textarea></label>
            <button type="submit">ثبت درخواست ابطال</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($issued === []): ?><tr><td colspan="7">قلم صادرشده قابل ابطال یافت نشد.</td></tr><?php endif; ?>
    </tbody>
  </table>
  <p><a href="erp-workshop-internal-consumable-reversal-queue.php">صف تأیید ابطال</a></p>
</section>
<?php mirror_render_foot(); ?>
