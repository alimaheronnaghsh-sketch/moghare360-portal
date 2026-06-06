<?php
declare(strict_types=1);
require_once __DIR__ . '/inventory-helpers.php';
ensureSessionStarted();
try {
    $staff = requireStaffLogin();
    if (!meetingCanAccessStaffModule($staff, 'inventory')) { showErrorPage('دسترسی شما به ارزش ریالی انبار فعال نیست.'); }
    $rows = getPdo()->query('SELECT main_category, COUNT(*) cnt, COALESCE(SUM(COALESCE(quantity, initial_stock, 0)),0) qty, COALESCE(SUM(COALESCE(quantity, initial_stock, 0) * COALESCE(purchase_price_rial, purchase_price, 0)),0) val FROM inventory_items_staging GROUP BY main_category ORDER BY val DESC')->fetchAll();
    renderHeader('ارزش ریالی انبار', 'StockCenter Valuation'); inventoryHeaderActions('valuation');
?>
<main class="auth-wrap wide-auth inventory-page"><section class="card table-card"><h2>ارزش ریالی به تفکیک گروه</h2><div class="table-scroll"><table class="data-table"><thead><tr><th>گروه</th><th>تعداد اقلام</th><th>موجودی</th><th>ارزش ریالی</th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td><?= e((string)($r['main_category'] ?: 'نامشخص')) ?></td><td class="num"><?= e((string)$r['cnt']) ?></td><td class="num"><?= e((string)$r['qty']) ?></td><td class="num"><?= e(inventoryMoney((string)$r['val'])) ?></td></tr><?php endforeach; ?></tbody></table></div></section></main>
<?php renderFooter(); } catch (Throwable $e) { showErrorPage('خطا در ارزش ریالی انبار.', $e->getMessage()); }
