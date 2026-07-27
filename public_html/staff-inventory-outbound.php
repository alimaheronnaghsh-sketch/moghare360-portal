<?php
declare(strict_types=1);
require_once __DIR__ . '/inventory-helpers.php';
try {
    $staff = inv_require_inventory_access('outbound');
    $canEdit = inventoryCanEditFull($staff) && !meetingIsViewOnly($staff) && !inventoryCanRegisterInboundOnly($staff);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $_SESSION['inventory_outbound_token'] = bin2hex(random_bytes(32));
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        checkCsrf();
        if (!$canEdit) { flash('نقش شما اجازه ثبت خروج کالا ندارد.', 'bad'); redirect('staff-inventory-outbound.php'); }
        $postedToken = trim((string)($_POST['inventory_form_token'] ?? ''));
        $sessionToken = (string)($_SESSION['inventory_outbound_token'] ?? '');
        unset($_SESSION['inventory_outbound_token']);
        if ($postedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
            throw new RuntimeException('فرم منقضی شده یا قبلاً ارسال شده است.');
        }

        $itemId = (int)($_POST['inventory_item_id'] ?? 0);
        $locationId = (int)($_POST['stock_location_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        if ($itemId < 1 || $locationId < 1 || $quantity <= 0) {
            throw new RuntimeException('قلم، محل مبدأ و تعداد خروج الزامی است.');
        }

        inv_record_movement(
            $itemId,
            $locationId,
            'OUTBOUND',
            $quantity,
            trim((string)($_POST['movement_note'] ?? ''))
        );
        flash('خروج کالا ثبت شد.');
        redirect('staff-inventory.php');
    }
    $items = inv_fetch_all('SELECT inventory_item_id, item_code, item_name, unit_name FROM dbo.erp_inventory_items WHERE is_active = 1 ORDER BY item_name');
    $locations = inv_warehouses();
    $formToken = (string)($_SESSION['inventory_outbound_token'] ?? '');
    renderHeader('ثبت خروج کالا', 'StockCenter Outbound');
    inventoryHeaderActions('outbound');
?>
<main class="auth-wrap wide-auth inventory-page">
  <form class="card form-card stockcenter-form" method="post" action="staff-inventory-outbound.php">
    <?= csrfField() ?>
    <input type="hidden" name="inventory_form_token" value="<?= e($formToken) ?>">
    <div class="form-grid">
      <h3 class="form-section-title wide">خروج کالا از موجودی</h3>
      <label>قلم انبار *
        <select name="inventory_item_id" required <?= $canEdit ? '' : 'disabled' ?>>
          <option value="">انتخاب کنید</option>
          <?php foreach ($items as $item): ?>
            <option value="<?= e((string)$item['inventory_item_id']) ?>"><?= e((string)$item['item_code'] . ' — ' . (string)$item['item_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>محل مبدأ *
        <select name="stock_location_id" required <?= $canEdit ? '' : 'disabled' ?>>
          <option value="">انتخاب کنید</option>
          <?php foreach ($locations as $location): ?>
            <option value="<?= e((string)$location['id']) ?>"><?= e((string)$location['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>تعداد خروج *
        <input class="stock-field input-number" name="quantity" type="number" step="0.01" min="0.01" required inputmode="decimal" <?= $canEdit ? '' : 'disabled' ?>>
      </label>
      <label class="wide">توضیحات<textarea name="movement_note" <?= $canEdit ? '' : 'disabled' ?>></textarea></label>
    </div>
    <p class="muted">مصرف JobCard فقط از مسیر اجرای کار و پس از تأیید برآورد ثبت می‌شود.</p>
    <div class="action-row"><button class="btn primary" <?= $canEdit ? '' : 'disabled' ?>>ثبت خروج</button><a class="btn ghost" href="staff-inventory.php">بازگشت</a></div>
  </form>
</main>
<?php renderFooter(); } catch (Throwable $e) { showErrorPage('خطا در ثبت خروج کالا.', $e->getMessage()); }
