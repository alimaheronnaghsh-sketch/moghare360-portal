<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/meeting-helpers.php';
ensureSessionStarted();

try {
    $staff = requireStaffLogin();
    $module = (string)($_GET['module'] ?? '');
    $action = (string)($_GET['action'] ?? '');
    if ($module === 'inventory') {
        redirect('staff-inventory.php');
    }
    $modules = [
        'personal' => ['پروفایل / کارتابل شخصی', 'شرح وظایف، اعلان‌ها، پیگیری‌های شخصی', 'staff-profile.php'],
        'hr' => ['منابع انسانی، حضور، حقوق، وام و قرارداد', 'حضور و غیاب، اصلاح تردد، حقوق و قرارداد', 'staff-dashboard.php?module=hr'],
        'inventory' => ['انبار', 'کالا، ورود و خروج، انبارگردانی و ارزش ریالی', 'staff-inventory.php'],
        'reception' => ['پذیرش', 'پذیرش‌های جدید، مشتری، خودرو، JobCard و قرارداد', 'staff-dashboard.php?module=reception'],
        'sales' => ['حسابداری فروش', 'فاکتور، دریافت، بدهکاران و گزارش فروش', 'staff-dashboard.php?module=sales'],
        'domestic_purchase' => ['خرید داخلی', 'درخواست خرید، تامین‌کننده، سفارش و دریافت کالا', 'staff-dashboard.php?module=domestic_purchase'],
        'foreign_purchase' => ['خرید خارجی', 'ارز، حمل، گمرک، ترخیص و قیمت تمام‌شده', 'staff-dashboard.php?module=foreign_purchase'],
    ];
    $moduleCards = [
        'default' => ['dashboard' => 'داشبورد', 'create' => 'ثبت', 'search' => 'جستجو', 'report' => 'گزارش', 'approve' => 'اصلاح / تایید'],
        'inventory' => [
            'dashboard' => 'داشبورد انبار',
            'new_item' => 'ثبت کالای جدید',
            'search_item' => 'جستجوی کالا',
            'inventory_report' => 'گزارش انبار',
            'edit_item' => 'اصلاح کالا',
            'stock_in' => 'ثبت ورود کالا',
            'stock_out' => 'ثبت خروج کالا',
            'stocktaking' => 'انبارگردانی',
            'low_stock' => 'گزارش کالاهای کم‌موجودی',
            'movement' => 'گزارش گردش کالا',
            'valuation' => 'گزارش ارزش ریالی انبار',
            'parts_approval' => 'تأیید قطعه برای پذیرش',
            'purchase_request' => 'درخواست خرید قطعه',
            'supply_status' => 'وضعیت تأمین قطعه',
        ],
        'reception' => ['dashboard' => 'داشبورد پذیرش', 'new' => 'پذیرش‌های جدید', 'customer_search' => 'جستجوی مشتری', 'vehicle_search' => 'جستجوی خودرو', 'jobcard' => 'JobCard', 'contract' => 'قرارداد', 'report' => 'گزارش پذیرش'],
    ];
    renderHeader('داشبورد پرسنل', 'کارتابل داخلی');
    renderFlashes();
    ?>
    <main class="dashboard">
      <section class="card profile-summary">
        <div class="avatar avatar-3x4"><?= e(initialLetter((string)($staff['full_name'] ?? 'P'))) ?></div>
        <div>
          <h2><?= e($staff['full_name'] ?? '-') ?></h2>
          <p><?= e($staff['username'] ?? '-') ?> | <?= e($staff['role_name'] ?? '-') ?></p>
          <span class="pill">فعال</span>
          <?php if (isMasterAdmin($staff)): ?>
            <a class="btn small" href="staff-users.php">مدیریت کاربران پرسنل</a>
          <?php endif; ?>
        </div>
      </section>

      <section class="card">
        <h2>کارتابل‌ها</h2>
        <div class="module-grid">
          <?php foreach ($modules as $key => $info): ?>
            <?php $canAccess = meetingCanAccessStaffModule($staff, $key); ?>
            <a class="module-card <?= $module === $key ? 'active' : '' ?> <?= $canAccess ? '' : 'disabled-card' ?>" href="<?= $canAccess ? e($info[2]) : '#' ?>" <?= $canAccess ? '' : 'aria-disabled="true"' ?>>
              <strong><?= e($info[0]) ?></strong>
              <span><?= e($info[1]) ?></span>
              <?php if (!$canAccess): ?><small>برای نقش شما غیرفعال است</small><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </section>

      <?php if ($module !== '' && isset($modules[$module]) && meetingCanAccessStaffModule($staff, $module)): ?>
        <section class="card wide-card">
          <h2><?= e($modules[$module][0]) ?></h2>
          <p class="muted">این بخش امروز به صورت UI آماده و بدون خطای ۵۰۰ تحویل می‌شود. اتصال به ERP اصلی در فاز بعد انجام می‌شود.</p>
          <div class="module-grid compact">
            <?php foreach (($moduleCards[$module] ?? $moduleCards['default']) as $actionKey => $item): ?>
              <a class="module-card <?= $action === (string)$actionKey ? 'active' : '' ?>" href="staff-dashboard.php?module=<?= urlencode($module) ?>&action=<?= urlencode((string)$actionKey) ?>">
                <strong><?= e($item) ?></strong>
                <span>ورود به بخش</span>
              </a>
            <?php endforeach; ?>
          </div>
          <?php if ($action !== ''): ?>
            <?php $currentTitle = ($moduleCards[$module] ?? $moduleCards['default'])[$action] ?? 'بخش انتخاب‌شده'; ?>
            <div class="operation-panel">
              <h3><?= e($currentTitle) ?></h3>
              <p>این بخش برای عملیات cPanel امروز آماده است و بدون لینک خراب باز می‌شود.</p>
              <div class="module-grid compact">
                <article class="module-card placeholder"><strong>داشبورد</strong><span>نمای کلی</span></article>
                <article class="module-card placeholder"><strong>ثبت</strong><span>فرم عملیاتی</span></article>
                <article class="module-card placeholder"><strong>جستجو</strong><span>فیلتر و بازیابی</span></article>
                <article class="module-card placeholder"><strong>گزارش</strong><span>خروجی مدیریتی</span></article>
                <article class="module-card placeholder"><strong>اصلاح / تایید</strong><span>کنترل داخلی</span></article>
              </div>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>
    </main>
    <?php
    renderFooter();
} catch (Throwable $e) {
    showErrorPage('خطا در داشبورد پرسنل.', $e->getMessage());
}
