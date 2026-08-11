<?php
declare(strict_types=1);
require_once __DIR__ . '/inventory-helpers.php';

try {
    $staff = inv_require_inventory_access('view');
    renderHeader('کارتابل انبار', 'MAHIN 360° StockCenter');
    renderFlashes();
    inventoryHeaderActions('dashboard');

    $total = inventoryCount();
    $stockSql = "SELECT ISNULL(SUM(CASE
                    WHEN m.movement_type IN (N'OUTBOUND', N'JOB_CARD_CONSUMPTION') THEN -ABS(m.movement_qty)
                    WHEN m.movement_type = N'COUNT_ADJUSTMENT' THEN m.movement_qty
                    ELSE ABS(m.movement_qty)
                END), 0)
                FROM dbo.erp_inventory_stock_movements m
                WHERE m.movement_status = N'RECORDED'";
    $stockQty = (float)(inv_scalar($stockSql) ?? 0);
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
?>
<main class="auth-wrap wide-auth inventory-page">
  <section class="card inventory-headline stockcenter-hero">
    <div class="avatar avatar-3x4"><?= e(initialLetter((string)($staff['full_name'] ?? 'W'))) ?></div>
    <div>
      <h2>StockCenter | مرکز عملیات انبار</h2>
      <p class="muted">ساختار این بخش مطابق منطق StockCenter تنظیم شده: شناسه فنی، OEM، لوکیشن انبار، وضعیت فنی، قیمت و رسید.</p>
    </div>
    <div class="logo-frame"><img src="assets/brand/mahin360-logo.png" alt="ماهین 360°"></div>
  </section>

  <section class="inventory-kpis">
    <div class="kpi-card"><span>کل کالاهای ثبت‌شده</span><strong class="numeric-badge"><?= e((string)$total) ?></strong></div>
    <div class="kpi-card"><span>کالاهای کم‌موجودی</span><strong class="numeric-badge"><?= e((string)$low) ?></strong></div>
    <div class="kpi-card"><span>موجودی خالص ثبت‌شده</span><strong class="numeric-badge"><?= e((string)$stockQty) ?></strong></div>
  </section>

  <section class="module-grid inventory-action-grid">
    <?php foreach (inventoryMainCards() as [$title, $href, $desc, $icon]): ?>
      <a class="module-card" href="<?= e($href) ?>">
        <span class="module-icon"><?= e($icon) ?></span>
        <strong><?= e($title) ?></strong>
        <small><?= e($desc) ?></small>
      </a>
    <?php endforeach; ?>
  </section>
</main>
<?php
    renderFooter();
} catch (Throwable $e) {
    showErrorPage('خطا در نمایش کارتابل انبار.', $e->getMessage());
}
