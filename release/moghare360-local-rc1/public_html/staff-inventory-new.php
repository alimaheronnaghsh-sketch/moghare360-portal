<?php
declare(strict_types=1);

require_once __DIR__ . '/inventory-controlled-helpers.php';

inv_require_inventory_access('new');
ensureSessionStarted();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

function inventoryBlankLockedPage(string $message): void
{
    renderHeader('فرم منقضی شده', 'ثبت کالا');
    echo '<main class="form-shell">';
    echo '<section class="panel-card wide-card" style="text-align:center;min-height:280px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px">';
    echo '<h2>' . e($message) . '</h2>';
    echo '<p class="muted">برای جلوگیری از ثبت تکراری، این فرم قفل شده است.</p>';
    echo '<div class="actions">';
    echo '<a class="btn primary" href="staff-inventory-new.php?new=1">ثبت کالای جدید</a>';
    echo '<a class="btn" href="staff-inventory.php">بازگشت به انبار</a>';
    echo '</div>';
    echo '</section>';
    echo '</main>';
    renderFooter();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['new'])) {
        unset($_SESSION['inventory_new_form_locked'], $_SESSION['inventory_new_form_token']);
    } elseif (!empty($_SESSION['inventory_new_form_locked'])) {
        inventoryBlankLockedPage('این فرم قبلاً ثبت شده است.');
    }

    $_SESSION['inventory_new_form_token'] = bin2hex(random_bytes(32));
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        checkCsrf();

        $postedFormToken = (string)($_POST['inventory_form_token'] ?? '');
        $sessionFormToken = (string)($_SESSION['inventory_new_form_token'] ?? '');

        if (
            $postedFormToken === '' ||
            $sessionFormToken === '' ||
            !hash_equals($sessionFormToken, $postedFormToken)
        ) {
            inventoryBlankLockedPage('این فرم منقضی شده یا قبلاً ارسال شده است.');
        }

        unset($_SESSION['inventory_new_form_token']);

        if (!empty($_SESSION['inventory_new_form_locked'])) {
            inventoryBlankLockedPage('این فرم قبلاً ثبت شده است.');
        }

        $itemName = trim((string)($_POST['item_name'] ?? ''));
        $technicalCode = trim((string)($_POST['technical_code'] ?? ''));
        $qualityLevel = trim((string)($_POST['quality_level'] ?? ''));
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $floor = trim((string)($_POST['floor_code'] ?? ''));
        $row = trim((string)($_POST['row_code'] ?? ''));
        $rowSide = trim((string)($_POST['row_side'] ?? ''));
        $rack = trim((string)($_POST['rack_code'] ?? ''));
        $section = trim((string)($_POST['section_code'] ?? ''));

        if ($itemName === '' || $technicalCode === '' || $qualityLevel === '' || $categoryId <= 0 || $warehouseId <= 0 || $floor === '' || $row === '' || $rowSide === '' || $rack === '' || $section === '') {
            throw new RuntimeException('فیلدهای ستاره‌دار را کامل کنید.');
        }

        $unitId = (int)($_POST['unit_id'] ?? 0);

        if ($unitId <= 0) {
            throw new RuntimeException('انتخاب واحد کالا الزامی است.');
        }

        $unit = inv_fetch_one("SELECT unit_name FROM inventory_units WHERE id = ?", [$unitId]);

        if (!$unit) {
            throw new RuntimeException('واحد انتخاب‌شده معتبر نیست.');
        }

        $isNoMoney = inv_is_no_money_data_entry();

        $quantity = (int)($_POST['quantity'] ?? 0);

        if ($quantity <= 0) {
            throw new RuntimeException('تعداد کالا باید عددی و بزرگ‌تر از صفر باشد.');
        }

        $category = inv_fetch_one("SELECT category_name FROM inventory_categories WHERE id = ?", [$categoryId]);
        $warehouse = inv_fetch_one("SELECT warehouse_name FROM inventory_warehouses WHERE id = ?", [$warehouseId]);

        if (!$category || !$warehouse) {
            throw new RuntimeException('دسته یا انبار معتبر نیست.');
        }

        $duplicate = inv_fetch_one("
            SELECT id, receipt_number, created_at
            FROM inventory_items_staging
            WHERE item_name = ?
              AND technical_code = ?
              AND quality_level = ?
              AND category_id = ?
              AND warehouse_id = ?
              AND floor_code = ?
              AND row_code = ?
              AND row_side = ?
              AND rack_code = ?
              AND section_code = ?
              AND unit_id = ?
              AND quantity = ?
            ORDER BY id DESC
            LIMIT 1
        ", [
            $itemName,
            $technicalCode,
            $qualityLevel,
            $categoryId,
            $warehouseId,
            $floor,
            $row,
            $rowSide,
            $rack,
            $section,
            $unitId,
            $quantity
        ]);

        if ($duplicate) {
            $receiptNo = (string)($duplicate['receipt_number'] ?? '');
            throw new RuntimeException('این کالا با همین مشخصات قبلاً ثبت شده است. شماره رسید قبلی: ' . ($receiptNo !== '' ? $receiptNo : 'نامشخص'));
        }

        $receipt = inv_generate_receipt_number();
        $locationCode = inv_location_code((string)$warehouse['warehouse_name'], $floor, $row, $rowSide, $rack, $section);

        $itemPhoto = inv_upload_file('item_photo', 'uploads/inventory-items');

if ($itemPhoto === null || $itemPhoto === '') {
    throw new RuntimeException('بارگذاری عکس کالا الزامی است.');
}

$receiptPhoto = inv_upload_file('receipt_photo', 'uploads/inventory-receipts');
        $minimumStock = $isNoMoney ? null : (float)($_POST['minimum_stock'] ?? 0);
        $purchasePrice = inv_can_purchase_price() ? (float)($_POST['purchase_price'] ?? 0) : null;
        $suggestedSalePrice = $isNoMoney ? null : (float)($_POST['suggested_sale_price'] ?? 0);

        $stmt = inv_pdo()->prepare("
    INSERT INTO inventory_items_staging
    (receipt_number, operation_type, item_name, technical_code, quality_level, category_id, category_name,
     engine_number, body_number, internal_code, barcode, unit_id, unit_name, quantity, minimum_stock,
     warehouse_id, warehouse_name, floor_code, row_code, row_side, rack_code, section_code, location_code,
     purchase_price, suggested_sale_price, description, receipt_photo_path, item_photo_path, technical_status,
     workflow_status, created_by_staff_id, updated_at)
    VALUES
    (?, 'NEW_ITEM', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'در انتظار بررسی', 'ثبت اولیه', ?, NOW())
");

        $stmt->execute([
            $receipt,
            $itemName,
            $technicalCode,
            $qualityLevel,
            $categoryId,
            $category['category_name'],
            trim((string)($_POST['engine_number'] ?? '')),
            trim((string)($_POST['body_number'] ?? '')),
            trim((string)($_POST['internal_code'] ?? '')),
            trim((string)($_POST['barcode'] ?? '')),
            $unitId,
            $unit['unit_name'],
            $quantity,
            $minimumStock,
            $warehouseId,
            $warehouse['warehouse_name'],
            $floor,
            $row,
            $rowSide,
            $rack,
            $section,
            $locationCode,
            $purchasePrice,
            $suggestedSalePrice,
            trim((string)($_POST['description'] ?? '')),
            $receiptPhoto,
            $itemPhoto,
            inv_staff_id()
        ]);

        $_SESSION['inventory_new_form_locked'] = true;
        $_SESSION['inventory_last_receipt'] = $receipt;

        redirect('staff-inventory-receipt.php?receipt=' . urlencode($receipt));
    }
} catch (Throwable $e) {
    showErrorPage('ثبت کالا انجام نشد.', $e->getMessage());
}

renderHeader('ثبت کالای جدید', 'فرم کنترل‌شده انبار');
renderFlashes();

$categories = inv_fetch_all("SELECT id, category_name AS name FROM inventory_categories WHERE is_active = 1 ORDER BY sort_order");
$units = inv_fetch_all("SELECT id, unit_name AS name FROM inventory_units WHERE is_active = 1 ORDER BY unit_name");
$qualities = inv_fetch_all("SELECT quality_name AS name FROM inventory_item_qualities WHERE is_active = 1 ORDER BY sort_order");

$isNoMoney = inv_is_no_money_data_entry();
$formToken = (string)($_SESSION['inventory_new_form_token'] ?? '');
?>
<main class="form-shell">
  <section class="panel-card wide-card">
    <h2>ثبت استاندارد کالا</h2>
    <p class="muted">
      <?php if ($isNoMoney): ?>
        دسترسی شما فقط برای ثبت اطلاعات کالا است؛ فیلدهای مبلغ و ریال برای این حساب خاموش است.
      <?php else: ?>
        نام کالا، کد فنی، سطح کیفیت و آدرس کالا اجباری است.
      <?php endif; ?>
    </p>

    <form method="post" enctype="multipart/form-data" class="form-grid" autocomplete="off">
      <?= csrfField() ?>
      <input type="hidden" name="inventory_form_token" value="<?= e($formToken) ?>">

      <h3 class="form-section-title">شناسه کالا</h3>

      <div class="field">
        <label>نام کالا *</label>
        <input name="item_name" required autocomplete="off">
      </div>

      <div class="field">
        <label>کد فنی *</label>
        <input name="technical_code" class="num" required autocomplete="off">
      </div>

      <div class="field">
        <label>سطح کیفیت *</label>
        <select name="quality_level" required>
          <option value="">انتخاب کنید</option>
          <?php foreach ($qualities as $q): ?>
            <option value="<?= e($q['name']) ?>"><?= e($q['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>گروه کالا *</label>
        <select name="category_id" id="category_id" required>
          <option value="">انتخاب کنید</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= e((string)$c['id']) ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field vehicle-identity">
        <label>شماره موتور</label>
        <input name="engine_number" class="num" autocomplete="off">
      </div>

      <div class="field vehicle-identity">
        <label>شماره اتاق</label>
        <input name="body_number" class="num" autocomplete="off">
      </div>

      <h3 class="form-section-title">کدها و تعداد</h3>

      <?php if (!$isNoMoney): ?>
      <div class="field">
        <label>OEM Code</label>
        <input name="oem_code" class="num" autocomplete="off">
      </div>
      <?php endif; ?>

      <div class="field">
        <label>کد داخلی</label>
        <input name="internal_code" class="num" autocomplete="off">
      </div>

      <div class="field">
        <label>بارکد</label>
        <input name="barcode" class="num" autocomplete="off">
      </div>

      <div class="field">
        <label>واحد *</label>
        <select name="unit_id" required>
          <option value="">انتخاب کنید</option>
          <?php foreach ($units as $u): ?>
            <option value="<?= e((string)$u['id']) ?>"><?= e($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>تعداد *</label>
        <input name="quantity" type="number" min="1" step="1" inputmode="numeric" pattern="[0-9]*" class="num" required autocomplete="off">
      </div>

      <?php if (!$isNoMoney): ?>
      <div class="field">
        <label>حداقل موجودی</label>
        <input name="minimum_stock" type="number" step="0.01" class="num" autocomplete="off">
      </div>
      <?php endif; ?>

      <h3 class="form-section-title">آدرس کالا</h3>
      <?php inv_render_location_selectors(); ?>

      <h3 class="form-section-title">مالی و سند</h3>

      <?php if (inv_can_purchase_price()): ?>
      <div class="field">
        <label>قیمت خرید ریال</label>
        <input name="purchase_price" type="number" step="1" class="num" autocomplete="off">
      </div>
      <?php endif; ?>

      <?php if (!$isNoMoney): ?>
      <div class="field">
        <label>قیمت فروش پیشنهادی</label>
        <input name="suggested_sale_price" type="number" step="1" class="num" autocomplete="off">
      </div>
      <?php endif; ?>

      <div class="field">
        <label>عکس کالا</label>
        <input name="item_photo" type="file" accept=".jpg,.jpeg,.png,.webp">
      </div>

      <div class="field">
  <label>عکس کالا *</label>
  <input name="item_photo" type="file" accept=".jpg,.jpeg,.png,.webp" required>
</div>

      <div class="field full">
        <label>توضیحات</label>
        <textarea name="description" autocomplete="off"></textarea>
      </div>

      <div class="actions full">
        <button class="btn primary" type="submit">ثبت و صدور رسید</button>
        <a class="btn" href="staff-inventory.php">بازگشت</a>
      </div>
    </form>
  </section>
</main>
<script>
document.getElementById('category_id')?.addEventListener('change', function () {
  document.querySelectorAll('.vehicle-identity').forEach(x => x.style.display = this.value ? 'block' : 'none');
});

window.addEventListener('pageshow', function (event) {
  if (event.persisted) {
    window.location.reload();
  }
});
</script>
<?php renderFooter(); ?>