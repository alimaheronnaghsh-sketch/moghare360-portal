<?php
declare(strict_types=1);

/**
 * Inventory internal cost valuation admin — maker-checker for STANDARD_INTERNAL_COST / etc.
 * Not assigned to real personnel in this mission.
 */

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-inventory-valuation-helper.php';
require_once __DIR__ . '/includes/m360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);
$ctx = m360_ws_require_actor_context();

$msg = '';
$action = strtoupper(trim((string)($_POST['action'] ?? '')));
$canEdit = m360_ws_can('inventory.internal_cost.edit') || (bool)$ctx['is_owner'];
$canApprove = m360_ws_can('inventory.internal_cost.approve') || (bool)$ctx['is_owner'];
$canView = m360_ws_can('inventory.internal_cost.view') || $canEdit || $canApprove || (bool)$ctx['is_owner'];
if (!$canView) {
    m360_am_forbidden('مجوز مشاهده ارزیابی بهای داخلی را ندارید.');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && is_resource($conn)) {
    if ($action === 'CREATE' && $canEdit) {
        $res = m360_inv_val_create_draft(
            $conn,
            (int)$ctx['company_id'],
            (int)($_POST['inventory_item_id'] ?? 0),
            [
                'valuation_method' => (string)($_POST['valuation_method'] ?? 'STANDARD_INTERNAL_COST'),
                'unit_cost' => (float)($_POST['unit_cost'] ?? 0),
                'currency' => (string)($_POST['currency'] ?? 'IRR'),
                'source_document_type' => (string)($_POST['source_document_type'] ?? ''),
            ],
            (int)$ctx['user_id']
        );
        $msg = $res['message'];
        if (!empty($res['ok']) && !empty($_POST['submit_now'])) {
            $sub = m360_inv_val_submit($conn, (int)$res['valuation_id'], (int)$ctx['user_id']);
            $msg .= ' — ' . $sub['message'];
        }
    } elseif ($action === 'SUBMIT' && $canEdit) {
        $res = m360_inv_val_submit($conn, (int)($_POST['valuation_id'] ?? 0), (int)$ctx['user_id']);
        $msg = $res['message'];
    } elseif ($action === 'APPROVE' && $canApprove) {
        $res = m360_inv_val_approve(
            $conn,
            (int)($_POST['valuation_id'] ?? 0),
            (int)$ctx['user_id'],
            !empty($_POST['owner_override']) && (bool)$ctx['is_owner'],
            (string)($_POST['override_reason'] ?? '')
        );
        $msg = $res['message'];
    } elseif ($action === 'COMPLETE_PENDING' && $canApprove) {
        $res = m360_inv_val_complete_pending_cost(
            $conn,
            (int)($_POST['task_id'] ?? 0),
            (int)($_POST['valuation_id'] ?? 0),
            (int)$ctx['user_id'],
            (int)$ctx['company_id'],
            (bool)$ctx['is_owner'],
            (string)($_POST['later_valuation_reason'] ?? '')
        );
        $msg = $res['message'];
    } else {
        $msg = 'اقدام مجاز نیست.';
    }
}

$pending = is_resource($conn) && customer_core_table_exists($conn, 'erp_inventory_cost_pending_tasks')
    ? customer_core_fetch_rows(
        $conn,
        "SELECT TOP 50 * FROM dbo.erp_inventory_cost_pending_tasks
         WHERE company_id=? AND task_status=N'OPEN' ORDER BY task_id DESC",
        [(int)$ctx['company_id']]
    )
    : [];
$vals = is_resource($conn) && customer_core_table_exists($conn, 'erp_inventory_item_valuations')
    ? customer_core_fetch_rows(
        $conn,
        "SELECT TOP 50 * FROM dbo.erp_inventory_item_valuations WHERE company_id=? ORDER BY valuation_id DESC",
        [(int)$ctx['company_id']]
    )
    : [];
$items = is_resource($conn)
    ? customer_core_fetch_rows($conn, 'SELECT TOP 100 inventory_item_id, item_code, item_name FROM dbo.erp_inventory_items WHERE is_active=1 ORDER BY item_name')
    : [];

$approvedVals = array_values(array_filter($vals, static fn($v) => strtoupper((string)($v['status'] ?? '')) === 'APPROVED'));

mirror_render_head('ارزیابی بهای داخلی انبار', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">ارزیابی بهای داخلی انبار</h1>
  <p class="m360-muted">فقط ارزیابی تأییدشده برای مصرف داخلی استفاده می‌شود. صفر به‌معنای بهای معتبر نیست. تکمیل بهای معلق نیازمند مجوز تأیید است.</p>
  <?php if ($msg !== ''): ?><p class="m360-alert"><?= m360_am_h($msg) ?></p><?php endif; ?>

  <?php if ($canEdit): ?>
  <h2 class="m360-section-title">ثبت پیش‌نویس ارزیابی</h2>
  <form method="post" class="m360-form">
    <input type="hidden" name="action" value="CREATE">
    <label>قلم انبار
      <select name="inventory_item_id" required>
        <?php foreach ($items as $it): ?>
          <option value="<?= (int)$it['inventory_item_id'] ?>"><?= m360_am_h(($it['item_code'] ?? '') . ' — ' . ($it['item_name'] ?? '')) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>روش
      <select name="valuation_method">
        <option value="STANDARD_INTERNAL_COST">بهای استاندارد داخلی</option>
        <option value="LAST_APPROVED_PURCHASE">آخرین خرید تأییدشده</option>
        <option value="MOVING_AVERAGE">میانگین متحرک</option>
      </select>
    </label>
    <label>بهای واحد <input type="number" step="0.0001" min="0.0001" name="unit_cost" required></label>
    <label>ارز <input name="currency" value="IRR"></label>
    <label><input type="checkbox" name="submit_now" value="1"> ارسال فوری برای تأیید</label>
    <button type="submit">ثبت</button>
  </form>
  <?php endif; ?>

  <h2 class="m360-section-title">تکمیل بهای اقلام معلق</h2>
  <table class="m360-table">
    <thead><tr><th>شناسه</th><th>کالا</th><th>پرونده</th><th>مقدار معلق</th><th>یادداشت</th><th>تکمیل</th></tr></thead>
    <tbody>
    <?php foreach ($pending as $p): ?>
      <tr>
        <td><?= (int)$p['task_id'] ?></td>
        <td><?= (int)($p['inventory_item_id'] ?? 0) ?></td>
        <td><?= (int)($p['jobcard_id'] ?? 0) ?></td>
        <td><?= m360_am_h((string)($p['pending_qty'] ?? '—')) ?></td>
        <td><?= m360_am_h((string)($p['task_note'] ?? '')) ?></td>
        <td>
          <?php if ($canApprove): ?>
          <form method="post" class="m360-form">
            <input type="hidden" name="action" value="COMPLETE_PENDING">
            <input type="hidden" name="task_id" value="<?= (int)$p['task_id'] ?>">
            <label>ارزیابی تأییدشده
              <select name="valuation_id" required>
                <?php foreach ($approvedVals as $av): ?>
                  <?php if ((int)$av['inventory_item_id'] === (int)($p['inventory_item_id'] ?? 0) || (int)($p['inventory_item_id'] ?? 0) < 1): ?>
                    <option value="<?= (int)$av['valuation_id'] ?>">
                      #<?= (int)$av['valuation_id'] ?> — <?= m360_am_h((string)$av['unit_cost']) ?> (<?= m360_am_h((string)$av['valuation_method']) ?>)
                    </option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </label>
            <label>دلیل ارزیابی دیرتر (در صورت نیاز)<input name="later_valuation_reason"></label>
            <button type="submit">تکمیل بها</button>
          </form>
          <?php else: ?>
            <span class="m360-muted">نیاز به مجوز تأیید</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($pending === []): ?><tr><td colspan="6">موردی نیست.</td></tr><?php endif; ?>
    </tbody>
  </table>

  <h2 class="m360-section-title">سوابق ارزیابی</h2>
  <table class="m360-table">
    <thead><tr><th>ID</th><th>کالا</th><th>روش</th><th>بهای واحد</th><th>وضعیت</th><th>اقدام</th></tr></thead>
    <tbody>
    <?php foreach ($vals as $v): ?>
      <tr>
        <td><?= (int)$v['valuation_id'] ?></td>
        <td><?= (int)$v['inventory_item_id'] ?></td>
        <td><?= m360_am_h((string)$v['valuation_method']) ?></td>
        <td><?= m360_am_h((string)$v['unit_cost']) ?></td>
        <td><?= m360_am_h((string)$v['status']) ?></td>
        <td>
          <?php if (strtoupper((string)$v['status']) === 'DRAFT' && $canEdit): ?>
            <form method="post" style="display:inline"><input type="hidden" name="action" value="SUBMIT"><input type="hidden" name="valuation_id" value="<?= (int)$v['valuation_id'] ?>"><button type="submit">ارسال</button></form>
          <?php endif; ?>
          <?php if (strtoupper((string)$v['status']) === 'SUBMITTED' && $canApprove): ?>
            <form method="post" style="display:inline"><input type="hidden" name="action" value="APPROVE"><input type="hidden" name="valuation_id" value="<?= (int)$v['valuation_id'] ?>"><button type="submit">تأیید</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($vals === []): ?><tr><td colspan="6">موردی نیست.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
<?php mirror_render_foot(); ?>
