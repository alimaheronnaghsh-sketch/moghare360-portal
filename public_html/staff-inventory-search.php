<?php
declare(strict_types=1);
require_once __DIR__ . '/inventory-helpers.php';

try {
    $staff = inv_require_inventory_access('search');
    $q = trim((string)($_GET['q'] ?? ''));
    $rows = [];
    if ($q !== '') {
        $like = '%' . $q . '%';
        $rows = inv_fetch_all(
            "SELECT TOP 100 i.inventory_item_id AS id, i.item_code, i.item_name,
                    i.item_category AS main_category, i.brand AS manufacturer_brand,
                    i.item_code AS technical_code, i.compatible_vehicle,
                    i.unit_name, i.min_stock_qty,
                    ISNULL(SUM(CASE
                        WHEN m.movement_type IN (N'OUTBOUND', N'JOB_CARD_CONSUMPTION') THEN -ABS(m.movement_qty)
                        WHEN m.movement_type = N'COUNT_ADJUSTMENT' THEN m.movement_qty
                        ELSE ABS(m.movement_qty)
                    END), 0) AS quantity,
                    MAX(l.location_code) AS location_code,
                    CASE WHEN i.is_active = 1 THEN N'فعال' ELSE N'غیرفعال' END AS workflow_status
             FROM dbo.erp_inventory_items i
             LEFT JOIN dbo.erp_inventory_stock_movements m
               ON m.inventory_item_id = i.inventory_item_id AND m.movement_status = N'RECORDED'
             LEFT JOIN dbo.erp_stock_locations l ON l.stock_location_id = m.stock_location_id
             WHERE i.item_name LIKE ? OR i.item_code LIKE ? OR i.brand LIKE ? OR i.compatible_vehicle LIKE ?
             GROUP BY i.inventory_item_id, i.item_code, i.item_name, i.item_category, i.brand,
                      i.compatible_vehicle, i.unit_name, i.min_stock_qty, i.is_active
             ORDER BY i.inventory_item_id DESC",
            [$like, $like, $like, $like]
        );
    } else {
        $rows = inv_fetch_all(
            "SELECT TOP 50 i.inventory_item_id AS id, i.item_code, i.item_name,
                    i.item_category AS main_category, i.brand AS manufacturer_brand,
                    i.item_code AS technical_code, i.compatible_vehicle,
                    i.unit_name, i.min_stock_qty,
                    ISNULL(SUM(CASE
                        WHEN m.movement_type IN (N'OUTBOUND', N'JOB_CARD_CONSUMPTION') THEN -ABS(m.movement_qty)
                        WHEN m.movement_type = N'COUNT_ADJUSTMENT' THEN m.movement_qty
                        ELSE ABS(m.movement_qty)
                    END), 0) AS quantity,
                    MAX(l.location_code) AS location_code,
                    CASE WHEN i.is_active = 1 THEN N'فعال' ELSE N'غیرفعال' END AS workflow_status
             FROM dbo.erp_inventory_items i
             LEFT JOIN dbo.erp_inventory_stock_movements m
               ON m.inventory_item_id = i.inventory_item_id AND m.movement_status = N'RECORDED'
             LEFT JOIN dbo.erp_stock_locations l ON l.stock_location_id = m.stock_location_id
             GROUP BY i.inventory_item_id, i.item_code, i.item_name, i.item_category, i.brand,
                      i.compatible_vehicle, i.unit_name, i.min_stock_qty, i.is_active
             ORDER BY i.inventory_item_id DESC"
        );
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
          <td><?= e((string)($r['compatible_vehicle'] ?? '')) ?></td>
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
