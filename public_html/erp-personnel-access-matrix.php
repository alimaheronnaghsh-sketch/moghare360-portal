<?php
declare(strict_types=1);

/**
 * Personnel operational access matrix — Owner / authorized access manager UI.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

/**
 * Resolve helpers for both repo layout (public_html + root includes)
 * and flattened XAMPP runtime (moghare360/includes).
 */
$m360AmRequire = static function (string $fileName): void {
    $candidates = [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $fileName,
    ];
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            require_once $candidate;
            return;
        }
    }
    throw new RuntimeException('Required file not found: ' . $fileName);
};

$m360AmRequire('m360-access-management-helper.php');
$m360AmRequire('m360-access-matrix-helper.php');
// erp-auth-context + erp-csrf.php already loaded via access-management → customer-core.
// Do not load erp-csrf-helper.php (redeclares erp_csrf_input against customer-core stubs).

erp_auth_require_login();
$actorId = (int)(erp_auth_current_user_id() ?? 0);
$conn = m360_am_db();

if ($conn === false || $actorId < 1 || !m360_am_can_manage_matrix($conn, $actorId)) {
    m360_am_forbidden('مدیریت ماتریس دسترسی پرسنل برای حساب شما مجاز نیست.');
}

$csrf = function_exists('erp_csrf_get_or_create_token')
    ? erp_csrf_get_or_create_token(M360_ACCESS_MATRIX_CSRF)
    : erp_csrf_create_token(M360_ACCESS_MATRIX_CSRF);
$tabs = m360_access_matrix_module_tabs();
$canDirect = m360_am_can_apply_direct($conn, $actorId) || m360_am_is_owner($conn, $actorId);

m360_access_mgmt_render_head('ماتریس دسترسی پرسنل');
?>
<link rel="stylesheet" href="assets/css/m360-access-matrix.css?v=pkg-rec-1">
<section class="m360-access-card m360-am-wrap">
    <div class="m360-brand-lockup" aria-label="MOGHARE360">
        <img class="m360-brand-logo" src="assets/brand/moghareh-motors-logo.jpg" width="40" height="40" alt="MOGHARE360" onerror="this.style.display='none'">
        <div class="m360-brand-wordmark">
            <span class="m360-brand-wordmark__title" lang="en" dir="ltr">MOGHARE360</span>
            <span class="m360-brand-wordmark__sub">ماتریس دسترسی پرسنل</span>
        </div>
    </div>
    <p class="m360-rc-note">
        ردیف‌ها از پرسنل فعال متصل به حساب مرکزی بارگذاری می‌شوند.
        ستون‌ها مجوزهای عملیاتی واقعی هستند — پروفایل پرسنلی قفل است.
        <?= $canDirect ? 'اعمال مستقیم برای مالک فعال است.' : 'تغییرات به‌صورت پیشنهاد برای تأیید ارسال می‌شود.' ?>
    </p>
    <p><a class="m360-op-button-secondary" href="erp-user-role-admin.php">بازگشت به کاربران و نقش‌ها</a>
       <a class="m360-op-button-secondary" href="erp-access-change-history.php">تاریخچه دسترسی</a></p>
</section>

<input type="hidden" id="amCsrf" value="<?= m360_am_h($csrf) ?>">

<section class="m360-access-card m360-am-wrap">
    <div id="amUnsaved" class="m360-am-unsaved">تغییرات ذخیره‌نشده دارید — از کشوی عملیات ذخیره/پیشنهاد کنید.</div>
    <div class="m360-am-filters">
        <input type="search" id="amQ" placeholder="جست‌وجوی نام / کد پرسنلی" aria-label="جست‌وجو">
        <input type="search" id="amUnit" placeholder="واحد" aria-label="واحد">
        <select id="amRecFilter" aria-label="فیلتر پیشنهاد">
            <option value="">همه پیشنهادها</option>
            <option value="ready">فقط پیشنهادهای آماده</option>
            <option value="conflict">فقط تعارض وظایف</option>
            <option value="sensitive">فقط دسترسی‌های حساس</option>
            <option value="pending">فقط دسترسی‌های در انتظار تکمیل</option>
        </select>
        <select id="amPkgFilter" aria-label="بسته شغلی">
            <option value="">همه بسته‌ها</option>
        </select>
        <button type="button" class="m360-btn m360-btn-primary" id="amSearch">اعمال فیلتر</button>
        <button type="button" class="m360-btn m360-btn-secondary" id="amRecRebuild">بازسازی پیشنهاد</button>
        <a class="m360-btn m360-btn-secondary" id="amRecPreview" href="#" download>خروجی پیش‌نمایش</a>
    </div>
    <p class="m360-am-rec-banner" id="amRecBanner">پیشنهاد بسته‌های دسترسی فقط برای بررسی مالک است — اعمال نقش/ALLOW/DENY در این مرحله انجام نمی‌شود.</p>
    <div class="m360-am-tabs" role="tablist">
        <?php $first = true; foreach ($tabs as $key => $label): ?>
            <button type="button" data-module="<?= m360_am_h($key) ?>" class="<?= $first ? 'active' : '' ?>"><?= m360_am_h($label) ?></button>
        <?php $first = false; endforeach; ?>
    </div>
    <div class="m360-am-table-wrap">
        <table class="m360-am-table" aria-label="ماتریس دسترسی">
            <thead id="amHead"></thead>
            <tbody id="amBody"><tr><td class="sticky-col">در حال بارگذاری…</td></tr></tbody>
        </table>
    </div>
</section>

<div id="amDrawerBackdrop" class="m360-am-backdrop" aria-hidden="true"></div>
<aside
  class="m360-am-drawer"
  id="amDrawer"
  role="dialog"
  aria-labelledby="amDrawerTitle"
  aria-hidden="true"
>
  <div class="m360-am-drawer__header">
    <h3 id="amDrawerTitle">جزئیات</h3>
    <button
      type="button"
      id="amDrawerClose"
      class="m360-am-drawer__close"
      aria-label="بستن پنل جزئیات"
    >بستن</button>
  </div>
  <div class="m360-am-drawer__body" id="amDrawerBody"></div>
</aside>

<script src="assets/js/m360-access-matrix.js?v=pkg-rec-1" defer></script>
<?php
m360_access_mgmt_render_foot();
