<?php
declare(strict_types=1);

require_once __DIR__ . '/inventory-controlled-helpers.php';

inv_require_inventory_access('receipt');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

$receipt = trim((string)($_GET['receipt'] ?? ''));

if ($receipt === '') {
    showErrorPage('شماره رسید مشخص نیست.');
}

$item = inv_fetch_one(
    "SELECT * FROM inventory_items_staging WHERE receipt_number = ? LIMIT 1",
    [$receipt]
);

if (!$item) {
    showErrorPage('رسید پیدا نشد.');
}

renderHeader('رسید انبار', $receipt);
?>
<main class="form-shell">
  <section class="panel-card wide-card">
    <h2>رسید ثبت کالا</h2>

    <div class="summary-grid">
      <div>
        <strong>شماره رسید</strong>
        <span class="num"><?= e((string)($item['receipt_number'] ?? '')) ?></span>
      </div>

      <div>
        <strong>نام کالا</strong>
        <span><?= e((string)($item['item_name'] ?? '')) ?></span>
      </div>

      <div>
        <strong>کد فنی</strong>
        <span class="num"><?= e((string)($item['technical_code'] ?? '')) ?></span>
      </div>

      <div>
        <strong>سطح کیفیت</strong>
        <span><?= e((string)($item['quality_level'] ?? '')) ?></span>
      </div>

      <div>
        <strong>گروه کالا</strong>
        <span><?= e((string)($item['category_name'] ?? '')) ?></span>
      </div>

      <div>
        <strong>واحد</strong>
        <span><?= e((string)($item['unit_name'] ?? '')) ?></span>
      </div>

      <div>
        <strong>تعداد</strong>
        <span class="num"><?= e((string)($item['quantity'] ?? '')) ?></span>
      </div>

      <div>
        <strong>انبار</strong>
        <span><?= e((string)($item['warehouse_name'] ?? '')) ?></span>
      </div>

      <div>
        <strong>مکان</strong>
        <span class="num"><?= e((string)($item['location_code'] ?? '')) ?></span>
      </div>

      <div>
        <strong>وضعیت</strong>
        <span><?= e((string)($item['workflow_status'] ?? 'پیش‌نویس')) ?></span>
      </div>

      <div>
        <strong>تاریخ ثبت</strong>
        <span class="num"><?= e((string)($item['created_at'] ?? '')) ?></span>
      </div>
    </div>

    <div class="actions">
      <button class="btn primary" type="button" onclick="window.print()">چاپ رسید</button>
      <a class="btn primary" href="staff-inventory-new.php?new=1">ثبت کالای جدید</a>
      <a class="btn" href="staff-inventory.php">کارتابل انبار</a>
    </div>
  </section>
</main>
<?php renderFooter(); ?>