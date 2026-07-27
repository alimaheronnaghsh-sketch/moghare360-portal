<?php
declare(strict_types=1);
require_once __DIR__ . '/inventory-helpers.php';
try {
    $staff = inv_require_inventory_access('valuation');
    $rows = inv_fetch_all(
        "SELECT ISNULL(i.item_category, N'نامشخص') AS main_category,
                COUNT(DISTINCT i.inventory_item_id) AS cnt,
                ISNULL(SUM(CASE
                    WHEN m.movement_type IN (N'OUTBOUND', N'JOB_CARD_CONSUMPTION') THEN -ABS(m.movement_qty)
                    WHEN m.movement_type = N'COUNT_ADJUSTMENT' THEN m.movement_qty
                    ELSE ABS(m.movement_qty)
                END), 0) AS qty
         FROM dbo.erp_inventory_items i
         LEFT JOIN dbo.erp_inventory_stock_movements m
           ON m.inventory_item_id = i.inventory_item_id AND m.movement_status = N'RECORDED'
         WHERE i.is_active = 1
         GROUP BY i.item_category
         ORDER BY qty DESC"
    );
    renderHeader('ارزش ریالی انبار', 'StockCenter Valuation'); inventoryHeaderActions('valuation');
?>
<main class="auth-wrap wide-auth inventory-page"><section class="card table-card"><h2>موجودی به تفکیک گروه</h2><p class="notice">ارزش ریالی نمایش داده نمی‌شود؛ جدول کاتالوگ فعال قیمت خریدِ تأییدشده ندارد و عدد ساختگی مجاز نیست.</p><div class="table-scroll"><table class="data-table"><thead><tr><th>گروه</th><th>تعداد اقلام</th><th>موجودی خالص</th><th>ارزش ریالی</th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td><?= e((string)($r['main_category'] ?: 'نامشخص')) ?></td><td class="num"><?= e((string)$r['cnt']) ?></td><td class="num"><?= e((string)$r['qty']) ?></td><td>تأیید نشده</td></tr><?php endforeach; ?><?php if ($rows === []): ?><tr><td colspan="4">داده‌ای برای نمایش وجود ندارد.</td></tr><?php endif; ?></tbody></table></div></section></main>
<?php renderFooter(); } catch (Throwable $e) { showErrorPage('خطا در ارزش ریالی انبار.', $e->getMessage()); }
