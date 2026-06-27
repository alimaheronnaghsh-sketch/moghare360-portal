<?php
declare(strict_types=1);
require_once __DIR__ . '/inventory-helpers.php';
ensureSessionStarted();

try {
    $staff = requireStaffLogin();
    if (!meetingCanAccessStaffModule($staff, 'inventory')) { showErrorPage('دسترسی شما به جستجوی انبار فعال نیست.'); }
    $q = trim((string)($_GET['q'] ?? ''));
    $rows = [];
    if ($q !== '') {
        $like = '%' . $q . '%';
        $stmt = getPdo()->prepare('SELECT * FROM inventory_items_staging WHERE item_name LIKE ? OR technical_code LIKE ? OR oem_code LIKE ? OR internal_code LIKE ? OR barcode LIKE ? OR manufacturer_brand LIKE ? ORDER BY id DESC LIMIT 100');
        $stmt->execute([$like, $like, $like, $like, $like, $like]);
        $rows = $stmt->fetchAll();
    } else {
        $rows = getPdo()->query('SELECT * FROM inventory_items_staging ORDER BY id DESC LIMIT 50')->fetchAll();
    }
    renderHeader('جستجوی کالا', 'StockCenter Search');
    renderFlashes();
    inventoryHeaderActions('search');
?>
<main class="auth-wrap wide-auth inventory-page">
  <form class="card inventory-search-box" method="get" action="staff-inventory-search.php">
    <label>جستجو براساس نام، کد فنی، OEM، کد داخلی، بارکد یا سازنده</label>
    <div class="inline-search"><input name="q" value="<?= e($q) ?>" autofocus><button class="btn primary" type="submit">جستجو</button></div>
  </form>
  <section class="card table-card">
    <h2>نتایج جستجو</h2>
    <div class="table-scroll">
  <table class="data-table">
    <thead>
      <tr>
        <th>عکس</th>
        <th>ID</th>
        <th>نام کالا</th>
        <th>گروه</th>
        <th>سازنده</th>
        <th>Technical</th>
        <th>OEM</th>
        <th>داخلی</th>
        <th>خودرو</th>
        <th>لوکیشن</th>
        <th>موجودی</th>
        <th>قیمت خرید</th>
        <th>وضعیت</th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
          $photo = (string)($r['item_photo_path'] ?? '');

          if ($photo === '') {
              $photo = (string)($r['photo_path'] ?? '');
          }

          if ($photo === '') {
              $photo = (string)($r['receipt_photo_path'] ?? '');
          }
        ?>

        <tr>
          <td>
            <?php if ($photo !== ''): ?>
              <a href="<?= e($photo) ?>" target="_blank">
                <img class="inventory-thumb" src="<?= e($photo) ?>" alt="عکس کالا">
              </a>
            <?php else: ?>
              <span class="muted">بدون عکس</span>
            <?php endif; ?>
          </td>

          <td class="num"><?= e((string)$r['id']) ?></td>
          <td><?= e((string)$r['item_name']) ?></td>
          <td><?= e((string)($r['main_category'] ?? $r['category_name'] ?? '')) ?></td>
          <td><?= e((string)($r['manufacturer_brand'] ?? $r['manufacturer'] ?? '')) ?></td>
          <td class="num"><?= e((string)($r['technical_code'] ?? '')) ?></td>
          <td class="num"><?= e((string)($r['oem_code'] ?? '')) ?></td>
          <td class="num"><?= e((string)($r['internal_code'] ?? '')) ?></td>
          <td><?= e(trim((string)($r['vehicle_brand'] ?? '') . ' ' . (string)($r['vehicle_model'] ?? ''))) ?></td>
          <td><?= e((string)($r['location_code'] ?? $r['warehouse_location_code'] ?? $r['warehouse_location'] ?? '')) ?></td>
          <td class="num"><?= e((string)($r['quantity'] ?? $r['initial_stock'] ?? '')) ?></td>
          <td class="num"><?= e(inventoryMoney((string)($r['purchase_price_rial'] ?? $r['purchase_price'] ?? '0'))) ?></td>
          <td><?= e((string)($r['workflow_status'] ?? $r['technical_status'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>

      <?php if (!$rows): ?>
        <tr>
          <td colspan="13">داده‌ای یافت نشد.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php
    renderFooter();
} catch (Throwable $e) { showErrorPage('خطا در جستجوی کالا.', $e->getMessage()); }
