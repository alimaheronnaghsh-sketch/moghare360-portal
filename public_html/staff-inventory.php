<?php
declare(strict_types=1);
require_once __DIR__ . '/inventory-helpers.php';
ensureSessionStarted();

try {
    $staff = requireStaffLogin();
    if (!meetingCanAccessStaffModule($staff, 'inventory')) {
        showErrorPage('دسترسی شما به کارتابل انبار فعال نیست.');
    }
    renderHeader('کارتابل انبار', 'MOGHARE360 StockCenter');
    renderFlashes();
    inventoryHeaderActions('dashboard');

    $total = inventoryCount();
    $value = 0;
    $low = 0;
    try {
        $value = (float)getPdo()->query('SELECT COALESCE(SUM(COALESCE(quantity, initial_stock, 0) * COALESCE(purchase_price_rial, purchase_price, 0)), 0) FROM inventory_items_staging')->fetchColumn();
        $low = (int)getPdo()->query('SELECT COUNT(*) FROM inventory_items_staging WHERE COALESCE(quantity, initial_stock, 0) <= COALESCE(minimum_stock, 0) AND COALESCE(minimum_stock, 0) > 0')->fetchColumn();
    } catch (Throwable $ignored) {}
?>
<main class="auth-wrap wide-auth inventory-page">
  <section class="card inventory-headline stockcenter-hero">
    <div class="avatar avatar-3x4"><?= e(initialLetter((string)($staff['full_name'] ?? 'W'))) ?></div>
    <div>
      <h2>StockCenter | مرکز عملیات انبار</h2>
      <p class="muted">ساختار این بخش مطابق منطق MOGHARE360_StockCenter تنظیم شده: شناسه فنی، OEM، لوکیشن انبار، وضعیت فنی، قیمت و رسید.</p>
    </div>
    <div class="logo-frame"><img src="assets/moghareh-logo.png" alt="Moghareh Motors"></div>
  </section>

  <section class="inventory-kpis">
    <div class="kpi-card"><span>کل کالاهای ثبت‌شده</span><strong class="numeric-badge"><?= e((string)$total) ?></strong></div>
    <div class="kpi-card"><span>کالاهای کم‌موجودی</span><strong class="numeric-badge"><?= e((string)$low) ?></strong></div>
    <div class="kpi-card"><span>ارزش ریالی تقریبی</span><strong class="numeric-badge"><?= e(inventoryMoney((string)$value)) ?></strong></div>
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
