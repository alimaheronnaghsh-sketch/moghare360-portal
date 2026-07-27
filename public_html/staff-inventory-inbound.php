<?php
declare(strict_types=1);
require_once __DIR__ . '/inventory-controlled-helpers.php';

inv_require_inventory_access('inbound');

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $_SESSION['inventory_inbound_token'] = bin2hex(random_bytes(32));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        checkCsrf();

        $postedToken = trim((string)($_POST['inventory_form_token'] ?? ''));
        $sessionToken = (string)($_SESSION['inventory_inbound_token'] ?? '');
        unset($_SESSION['inventory_inbound_token']);
        if ($postedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
            throw new RuntimeException('فرم منقضی شده یا قبلاً ارسال شده است.');
        }

        $receiptNumber = trim((string)($_POST['receipt_number'] ?? ''));
        $itemId = (int)($_POST['inventory_item_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        $locationId = (int)($_POST['stock_location_id'] ?? 0);

        if ($itemId <= 0 || $quantity <= 0 || $locationId <= 0) {
            throw new RuntimeException('فیلدهای اجباری ثبت ورود کالا کامل نیست.');
        }

        $item = inv_fetch_one('SELECT item_name, item_code FROM dbo.erp_inventory_items WHERE inventory_item_id = ? AND is_active = 1', [$itemId]);
        $location = inv_fetch_one('SELECT location_name FROM dbo.erp_stock_locations WHERE stock_location_id = ? AND is_active = 1', [$locationId]);
        if ($item === null || $location === null) {
            throw new RuntimeException('قلم یا محل انبار معتبر نیست.');
        }

        if ($receiptNumber === '') {
            $receiptNumber = inventoryReceiptNumber();
        }
        $receiptPhoto = inv_upload_file('receipt_photo', 'uploads/inventory-receipts');
        if (!$receiptPhoto) throw new RuntimeException('برای ثبت ورود کالا، عکس رسید/فاکتور الزامی است.');

        $note = 'RECEIPT:' . $receiptNumber
            . ' | PHOTO:' . $receiptPhoto
            . ' | ' . trim((string)($_POST['notes'] ?? ''));
        inv_record_movement($itemId, $locationId, 'INBOUND', $quantity, trim($note));

        flash('ورود کالا با رسید ' . $receiptNumber . ' ثبت شد.', 'ok');
        redirect('staff-inventory-inbound.php');
    }
} catch (Throwable $e) {
    showErrorPage('ثبت ورود کالا انجام نشد.', $e->getMessage());
}

renderHeader('ثبت ورود کالا با رسید', 'کارتابل انبار');
renderFlashes();
$items = inv_fetch_all('SELECT inventory_item_id, item_code, item_name FROM dbo.erp_inventory_items WHERE is_active = 1 ORDER BY item_name');
$locations = inv_warehouses();
$formToken = (string)($_SESSION['inventory_inbound_token'] ?? '');
?>
<main class="form-shell">
  <section class="panel-card wide-card">
    <h2>ثبت ورود کالا</h2>
    <form method="post" enctype="multipart/form-data" class="form-grid">
      <?= csrfField() ?>
      <input type="hidden" name="inventory_form_token" value="<?= e($formToken) ?>">

      <div class="field"><label>شماره رسید</label><input name="receipt_number" class="num" placeholder="اگر خالی باشد خودکار ساخته می‌شود"></div>
      <div class="field"><label>قلم انبار *</label><select name="inventory_item_id" required><option value="">انتخاب کنید</option><?php foreach ($items as $item): ?><option value="<?= e((string)$item['inventory_item_id']) ?>"><?= e((string)$item['item_code'] . ' — ' . (string)$item['item_name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>تعداد *</label><input name="quantity" type="number" step="0.01" class="num" required min="0.01" inputmode="decimal"></div>
      <div class="field"><label>محل ورود *</label><select name="stock_location_id" required><option value="">انتخاب کنید</option><?php foreach ($locations as $location): ?><option value="<?= e((string)$location['id']) ?>"><?= e((string)$location['name']) ?></option><?php endforeach; ?></select></div>

      <div class="field"><label>عکس رسید/فاکتور *</label><input name="receipt_photo" type="file" accept=".jpg,.jpeg,.png,.webp" required></div>
      <div class="field full"><label>توضیحات رسید</label><textarea name="notes"></textarea></div>

      <div class="actions full">
        <button class="btn primary" type="submit">ثبت ورود کالا</button>
        <a class="btn" href="staff-inventory.php">بازگشت</a>
      </div>
    </form>
  </section>
</main>
<?php renderFooter(); ?>
