<?php
declare(strict_types=1);
require_once __DIR__ . '/inventory-helpers.php';
ensureSessionStarted();
try {
    $staff = requireStaffLogin();
    if (!meetingCanAccessStaffModule($staff, 'inventory')) { showErrorPage('دسترسی شما به گزارش انبار فعال نیست.'); }
    $pdo = getPdo();
    $total = inventoryCount();
    $low = (int)$pdo->query('SELECT COUNT(*) FROM inventory_items_staging WHERE COALESCE(quantity, initial_stock, 0) <= COALESCE(minimum_stock, 0) AND COALESCE(minimum_stock, 0) > 0')->fetchColumn();
    $value = (float)$pdo->query('SELECT COALESCE(SUM(COALESCE(quantity, initial_stock, 0) * COALESCE(purchase_price_rial, purchase_price, 0)), 0) FROM inventory_items_staging')->fetchColumn();
    $byStatus = $pdo->query('SELECT COALESCE(workflow_status, sync_status, "نامشخص") AS s, COUNT(*) c FROM inventory_items_staging GROUP BY s ORDER BY c DESC')->fetchAll();
    renderHeader('گزارش انبار', 'StockCenter Reports');
    inventoryHeaderActions('reports');
?>
<main class="auth-wrap wide-auth inventory-page">
  <section class="inventory-kpis"><div class="kpi-card"><span>کل کالاها</span><strong class="numeric-badge"><?= e((string)$total) ?></strong></div><div class="kpi-card"><span>کم‌موجودی</span><strong class="numeric-badge"><?= e((string)$low) ?></strong></div><div class="kpi-card"><span>ارزش ریالی</span><strong class="numeric-badge"><?= e(inventoryMoney((string)$value)) ?></strong></div></section>
  <section class="card table-card"><h2>وضعیت Workflow</h2><table class="data-table"><thead><tr><th>وضعیت</th><th>تعداد</th></tr></thead><tbody><?php foreach ($byStatus as $r): ?><tr><td><?= e((string)$r['s']) ?></td><td class="num"><?= e((string)$r['c']) ?></td></tr><?php endforeach; ?></tbody></table></section>
  <section class="module-grid inventory-action-grid"><a class="module-card" href="staff-inventory-search.php?q="><span class="module-icon">SR</span><strong>گزارش ریز کالاها</strong><small>نمایش آخرین کالاهای ثبت‌شده</small></a><a class="module-card" href="staff-inventory-valuation.php"><span class="module-icon">VL</span><strong>ارزش ریالی انبار</strong><small>جمع موجودی × قیمت خرید</small></a><a class="module-card" href="staff-inventory-counting.php"><span class="module-icon">CT</span><strong>انبارگردانی</strong><small>ثبت شمارش و مغایرت</small></a></section>
</main>
<?php renderFooter(); } catch (Throwable $e) { showErrorPage('خطا در گزارش انبار.', $e->getMessage()); }
