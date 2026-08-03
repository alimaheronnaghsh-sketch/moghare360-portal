<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-workshop-ic-reversal-helper.php';
require_once __DIR__ . '/includes/m360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);
$ctx = m360_ws_require_actor_context();
m360_ws_require('workshop.internal_consumable.reversal_approve');

$msg = '';
$canCost = m360_ws_can('workshop.internal_consumable.cost_view') || (bool)$ctx['is_owner'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && is_resource($conn)) {
    $action = strtoupper(trim((string)($_POST['action'] ?? '')));
    $rid = (int)($_POST['reversal_id'] ?? 0);
    if ($action === 'APPROVE') {
        $res = m360_ws_ic_reversal_approve(
            $conn,
            $rid,
            (int)$ctx['user_id'],
            (int)$ctx['company_id'],
            (bool)$ctx['is_owner'],
            !empty($_POST['owner_override']) && (bool)$ctx['is_owner'],
            (string)($_POST['override_reason'] ?? '')
        );
        $msg = $res['message'];
    } elseif ($action === 'REJECT') {
        $res = m360_ws_ic_reversal_reject($conn, $rid, (int)$ctx['user_id'], (string)($_POST['reject_reason'] ?? ''));
        $msg = $res['message'];
    } else {
        $msg = 'اقدام نامعتبر است.';
    }
}

$rows = is_resource($conn)
    ? m360_ws_ic_reversal_list($conn, ['REVERSAL_REQUESTED'], (int)$ctx['company_id'], (bool)$ctx['is_owner'], 100)
    : [];

mirror_render_head('صف تأیید ابطال مصرف داخلی', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">تأیید ابطال و برگشت موجودی</h1>
  <p class="m360-muted">تأیید ابطال یک حرکت مخالف انبار ایجاد می‌کند و حرکت اصلی را حفظ می‌کند. درخواست‌کننده نمی‌تواند درخواست خود را تأیید کند.</p>
  <?php if ($msg !== ''): ?><p class="m360-alert"><?= m360_am_h($msg) ?></p><?php endif; ?>
  <table class="m360-table">
    <thead>
      <tr>
        <th>ابطال</th><th>قلم</th><th>پرونده</th><th>مقدار</th><th>دلیل</th><th>حرکت اصلی</th><th>اقدام</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['reversal_id'] ?></td>
        <td><?= (int)$r['request_item_id'] ?></td>
        <td><?= (int)$r['jobcard_id'] ?></td>
        <td><?= m360_am_h((string)$r['reversal_qty']) ?></td>
        <td><?= m360_am_h((string)(M360_WS_IC_REVERSAL_REASONS[(string)$r['reason_category']] ?? $r['reason_category']) . ' — ' . (string)$r['reason_detail']) ?></td>
        <td><?= (int)($r['original_movement_id'] ?? 0) ?></td>
        <td>
          <form method="post" style="display:inline" onsubmit="return confirm('تأیید ابطال و برگشت موجودی؟');">
            <input type="hidden" name="action" value="APPROVE">
            <input type="hidden" name="reversal_id" value="<?= (int)$r['reversal_id'] ?>">
            <button type="submit">تأیید ابطال</button>
          </form>
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="REJECT">
            <input type="hidden" name="reversal_id" value="<?= (int)$r['reversal_id'] ?>">
            <input name="reject_reason" placeholder="دلیل رد" required>
            <button type="submit">رد</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($rows === []): ?><tr><td colspan="7">درخواست ابطالی در صف نیست.</td></tr><?php endif; ?>
    </tbody>
  </table>
  <p><a href="erp-workshop-internal-consumable-reversal-request.php">ثبت درخواست ابطال</a></p>
</section>
<?php mirror_render_foot(); ?>
