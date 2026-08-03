<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-workshop-internal-consumable-helper.php';
require_once __DIR__ . '/includes/m360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN', 'PARTS']);
$ctx = m360_ws_require_actor_context();
m360_ws_require('workshop.internal_consumable.create');

$jobcardId = (int)($_GET['jobcard_id'] ?? $_POST['jobcard_id'] ?? 0);
$message = '';
$okFlag = false;

if (is_resource($conn) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = strtolower(trim((string)($_POST['action'] ?? 'create')));
    if ($action === 'create') {
        $items = [[
            'inventory_item_id' => (int)($_POST['inventory_item_id'] ?? 0),
            'manual_description' => (string)($_POST['manual_description'] ?? ''),
            'quantity' => (float)($_POST['quantity'] ?? 0),
            'unit_of_measure' => (string)($_POST['unit_of_measure'] ?? 'عدد'),
            'notes' => (string)($_POST['item_notes'] ?? ''),
        ]];
        $res = m360_ws_ic_create(
            $conn,
            (int)$ctx['company_id'],
            $jobcardId,
            (int)($_POST['work_item_id'] ?? 0) ?: null,
            $_POST,
            $items,
            (int)$actor['user_id']
        );
        $message = $res['message'];
        $okFlag = !empty($res['ok']);
        if ($okFlag && !empty($res['request_id'])) {
            $sub = m360_ws_ic_submit($conn, (int)$res['request_id'], (int)$actor['user_id']);
            $message .= ' ' . $sub['message'];
        }
    }
}

$invOptions = is_resource($conn)
    ? customer_core_fetch_rows($conn, 'SELECT TOP 200 inventory_item_id, item_code, item_name, unit_name FROM dbo.erp_inventory_items WHERE is_active=1 ORDER BY item_name')
    : [];

mirror_render_head('ثبت مصرف داخلی', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">ثبت مواد و ملزومات مصرفی داخلی</h1>
  <p class="m360-alert m360-alert-warning"><strong>این مورد در صورتحساب مشتری درج نمی‌شود.</strong></p>
  <?php if ($message !== ''): ?><p class="m360-alert <?= $okFlag ? 'm360-alert-success' : 'm360-alert-error' ?>"><?= m360_am_h($message) ?></p><?php endif; ?>
  <form method="post" class="m360-form">
    <input type="hidden" name="action" value="create">
    <label>شناسه پرونده تعمیر<input name="jobcard_id" inputmode="numeric" required value="<?= $jobcardId > 0 ? $jobcardId : '' ?>"></label>
    <label>شناسه آیتم کاری (اختیاری)<input name="work_item_id" inputmode="numeric"></label>
    <label>واحد مصرف‌کننده
      <select name="consuming_unit" required>
        <option value="MECHANICAL">مکانیک</option>
        <option value="ELECTRICAL">برق</option>
        <option value="OPTIONS">آپشن</option>
        <option value="HALL">سالن</option>
        <option value="QC">کنترل کیفیت</option>
      </select>
    </label>
    <label>دلیل مصرف<textarea name="usage_reason" required></textarea></label>
    <label>قلم انبار
      <select name="inventory_item_id">
        <option value="0">— غیرانباردار / دستی —</option>
        <?php foreach ($invOptions as $opt): ?>
          <option value="<?= (int)$opt['inventory_item_id'] ?>"><?= m360_am_h(($opt['item_code'] ?? '') . ' — ' . ($opt['item_name'] ?? '')) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>شرح دستی (اگر غیرانباردار)<input name="manual_description"></label>
    <label>مقدار<input name="quantity" inputmode="decimal" required value="1"></label>
    <label>واحد اندازه‌گیری<input name="unit_of_measure" value="عدد" required></label>
    <label>یادداشت قلم<input name="item_notes"></label>
    <button class="m360-btn m360-btn-primary" type="submit">ثبت و ارسال</button>
  </form>
  <p class="m360-ops-actions">
    <a class="m360-btn" href="erp-workshop-internal-consumable-queue.php">در انتظار تأیید</a>
    <a class="m360-btn" href="erp-workshop-internal-consumable-history.php">سوابق</a>
  </p>
</section>
<?php mirror_render_foot(); ?>
