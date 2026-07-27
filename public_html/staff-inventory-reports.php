<?php
declare(strict_types=1);
require_once __DIR__ . '/inventory-helpers.php';
try {
    $staff = inv_require_inventory_access('reports');
    $total = inventoryCount();
    $low = (int)(inv_scalar(
        "SELECT COUNT(*) FROM (
            SELECT i.inventory_item_id, i.min_stock_qty,
                ISNULL(SUM(CASE
                    WHEN m.movement_type IN (N'OUTBOUND', N'JOB_CARD_CONSUMPTION') THEN -ABS(m.movement_qty)
                    WHEN m.movement_type = N'COUNT_ADJUSTMENT' THEN m.movement_qty
                    ELSE ABS(m.movement_qty)
                END), 0) AS current_qty
            FROM dbo.erp_inventory_items i
            LEFT JOIN dbo.erp_inventory_stock_movements m
              ON m.inventory_item_id = i.inventory_item_id AND m.movement_status = N'RECORDED'
            WHERE i.is_active = 1
            GROUP BY i.inventory_item_id, i.min_stock_qty
        ) s WHERE s.min_stock_qty > 0 AND s.current_qty <= s.min_stock_qty"
    ) ?? 0);
    $stockQty = (float)(inv_scalar(
        "SELECT ISNULL(SUM(CASE
            WHEN movement_type IN (N'OUTBOUND', N'JOB_CARD_CONSUMPTION') THEN -ABS(movement_qty)
            WHEN movement_type = N'COUNT_ADJUSTMENT' THEN movement_qty
            ELSE ABS(movement_qty)
        END), 0) FROM dbo.erp_inventory_stock_movements WHERE movement_status = N'RECORDED'"
    ) ?? 0);
    $byStatus = inv_fetch_all(
        'SELECT movement_type AS s, COUNT(*) AS c
         FROM dbo.erp_inventory_stock_movements
         GROUP BY movement_type
         ORDER BY c DESC'
    );
    renderHeader('گزارش انبار', 'StockCenter Reports');
    inventoryHeaderActions('reports');
?>
<main class="auth-wrap wide-auth inventory-page">
  <section class="inventory-kpis"><div class="kpi-card"><span>کل کالاها</span><strong class="numeric-badge"><?= e((string)$total) ?></strong></div><div class="kpi-card"><span>کم‌موجودی</span><strong class="numeric-badge"><?= e((string)$low) ?></strong></div><div class="kpi-card"><span>موجودی خالص</span><strong class="numeric-badge"><?= e((string)$stockQty) ?></strong></div></section>
  <section class="card table-card"><h2>گردش‌ها بر اساس نوع</h2><table class="data-table"><thead><tr><th>نوع گردش</th><th>تعداد</th></tr></thead><tbody><?php foreach ($byStatus as $r): ?><tr><td><?= e((string)$r['s']) ?></td><td class="num"><?= e((string)$r['c']) ?></td></tr><?php endforeach; ?><?php if ($byStatus === []): ?><tr><td colspan="2">هنوز گردشی ثبت نشده است.</td></tr><?php endif; ?></tbody></table></section>
  <section class="module-grid inventory-action-grid"><a class="module-card" href="staff-inventory-search.php?q="><span class="module-icon">SR</span><strong>گزارش ریز کالاها</strong><small>نمایش آخرین کالاهای ثبت‌شده</small></a><a class="module-card" href="staff-inventory-valuation.php"><span class="module-icon">VL</span><strong>ارزش ریالی انبار</strong><small>جمع موجودی × قیمت خرید</small></a><a class="module-card" href="staff-inventory-counting.php"><span class="module-icon">CT</span><strong>انبارگردانی</strong><small>ثبت شمارش و مغایرت</small></a></section>
</main>
<?php renderFooter(); } catch (Throwable $e) { showErrorPage('خطا در گزارش انبار.', $e->getMessage()); }
