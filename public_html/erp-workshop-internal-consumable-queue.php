<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-workshop-internal-consumable-helper.php';
require_once __DIR__ . '/includes/m360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'PARTS', 'TECHNICIAN']);
$ctx = m360_ws_require_actor_context();

$tab = strtolower(trim((string)($_GET['tab'] ?? 'pending')));
if ($tab === 'returned') {
    m360_ws_require_any(['workshop.internal_consumable.create', 'workshop.internal_consumable.return']);
    $rows = is_resource($conn) ? m360_ws_ic_list_by_status($conn, ['RETURNED']) : [];
} else {
    m360_ws_require_any(['workshop.internal_consumable.approve', 'workshop.internal_consumable.return', 'workshop.internal_consumable.create']);
    $rows = is_resource($conn) ? m360_ws_ic_list_by_status($conn, ['SUBMITTED']) : [];
}

$message = '';
$okFlag = false;
if (is_resource($conn) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $req = m360_ws_ic_fetch_request($conn, $requestId);
    if ($req === null) {
        m360_am_forbidden('درخواست یافت نشد.');
    }
    m360_ws_assert_jobcard_object_scope($conn, (int)$req['jobcard_id']);
    $action = strtolower(trim((string)($_POST['action'] ?? '')));
    if ($action === 'approve') {
        m360_ws_require('workshop.internal_consumable.approve', (int)$req['jobcard_id']);
        $res = m360_ws_ic_approve($conn, $requestId, (int)$actor['user_id']);
    } elseif ($action === 'return') {
        m360_ws_require('workshop.internal_consumable.return', (int)$req['jobcard_id']);
        $res = m360_ws_ic_return($conn, $requestId, (int)$actor['user_id'], (string)($_POST['return_reason'] ?? ''));
    } else {
        $res = ['ok' => false, 'message' => 'اقدام نامعتبر'];
    }
    $message = $res['message'];
    $okFlag = !empty($res['ok']);
    $rows = $tab === 'returned'
        ? m360_ws_ic_list_by_status($conn, ['RETURNED'])
        : m360_ws_ic_list_by_status($conn, ['SUBMITTED']);
}

$canCost = m360_ws_can('workshop.internal_consumable.cost_view');

mirror_render_head('صف مصرف داخلی', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title"><?= $tab === 'returned' ? 'درخواست‌های برگشتی مصرف داخلی' : 'درخواست‌های در انتظار تأیید' ?></h1>
  <p><a href="?tab=pending">در انتظار</a> | <a href="?tab=returned">برگشتی</a> | <a href="erp-workshop-internal-consumable-create.php">ثبت جدید</a></p>
  <?php if ($message !== ''): ?><p class="m360-alert <?= $okFlag ? 'm360-alert-success' : 'm360-alert-error' ?>"><?= m360_am_h($message) ?></p><?php endif; ?>
  <table class="m360-table">
    <thead><tr><th>شناسه</th><th>پرونده</th><th>واحد</th><th>دلیل</th><th>وضعیت</th><th>اقلام</th><th>اقدام</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <?php
        $items = m360_ws_ic_filter_cost_rows(m360_ws_ic_fetch_items($conn, (int)$row['request_id']), $canCost);
        $itemTxt = [];
        foreach ($items as $it) {
            $label = $it['manual_description'] ?? ('INV#' . (int)($it['inventory_item_id'] ?? 0));
            $itemTxt[] = $label . ' × ' . $it['quantity'] . ' ' . $it['unit_of_measure'];
        }
      ?>
      <tr>
        <td><?= (int)$row['request_id'] ?></td>
        <td><?= (int)$row['jobcard_id'] ?></td>
        <td><?= m360_am_h((string)$row['consuming_unit']) ?></td>
        <td><?= m360_am_h((string)$row['usage_reason']) ?></td>
        <td><?= m360_am_h((string)$row['status']) ?></td>
        <td><?= m360_am_h(implode('؛ ', $itemTxt)) ?></td>
        <td>
          <?php if ($tab !== 'returned' && strtoupper((string)$row['status']) === 'SUBMITTED'): ?>
            <?php if (m360_ws_can('workshop.internal_consumable.approve')): ?>
            <form method="post" style="display:inline;">
              <input type="hidden" name="request_id" value="<?= (int)$row['request_id'] ?>">
              <input type="hidden" name="action" value="approve">
              <button class="m360-btn m360-btn-primary" type="submit">تأیید</button>
            </form>
            <?php endif; ?>
            <?php if (m360_ws_can('workshop.internal_consumable.return')): ?>
            <form method="post" style="display:inline;">
              <input type="hidden" name="request_id" value="<?= (int)$row['request_id'] ?>">
              <input type="hidden" name="action" value="return">
              <input name="return_reason" placeholder="دلیل برگشت" required>
              <button class="m360-btn" type="submit">برگشت</button>
            </form>
            <?php endif; ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($rows === []): ?><tr><td colspan="7">موردی نیست.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
<?php mirror_render_foot(); ?>
