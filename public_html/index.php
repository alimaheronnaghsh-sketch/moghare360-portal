<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/m360-canonical-host-helper.php';
m360_canonical_local_host_enforce();

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/includes/m360-access-management-helper.php';

erp_auth_context_start();

$sessionUserId = erp_auth_context_session_user_id();
$conn = m360_access_mgmt_db();
$isAdmin = $sessionUserId !== null
    && $sessionUserId > 0
    && m360_access_mgmt_actor_is_admin($conn, $sessionUserId);

function m360_admin_index_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_admin_index_page_exists(string $relative): bool
{
    return is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
}

/** @return list<array{title:string,desc:string,href:string}> */
function m360_admin_index_hub_links(): array
{
    $candidates = [
        [
            'title' => 'مدیریت کاربران و دسترسی‌ها',
            'desc' => 'ایجاد پرسنل، تخصیص نقش و پیش‌نمایش دسترسی',
            'href' => 'erp-access-management.php',
        ],
        [
            'title' => 'کنسول مادر V1',
            'desc' => 'نمای کلی واحدها و مسیرهای مدیریتی نرم‌افزار',
            'href' => 'erp-v1-master-console.php',
        ],
        [
            'title' => 'وضعیت محصول',
            'desc' => 'ورود محصولی و ماژول‌های عملیاتی ERP',
            'href' => 'erp-product-home.php',
        ],
        [
            'title' => 'نقشه مسیرها',
            'desc' => 'مرجع مسیرهای نرم‌افزار و نمای عملیاتی',
            'href' => 'erp-route-map.php',
        ],
        [
            'title' => 'گزارش Production Signoff',
            'desc' => 'وضعیت اجرا و امضای تولید V1',
            'href' => 'erp-v1-production-signoff.php',
        ],
        [
            'title' => 'Fix Register',
            'desc' => 'ثبت و پیگیری اصلاحات پس از اجرا',
            'href' => 'erp-v1-fix-register.php',
        ],
        [
            'title' => 'ورود پرسنل',
            'desc' => 'صفحه ورود کارکنان مجموعه',
            'href' => 'staff-login.php',
        ],
    ];

    $links = [];
    foreach ($candidates as $item) {
        if (m360_admin_index_page_exists($item['href'])) {
            $links[] = $item;
        }
    }

    return $links;
}

if (!$isAdmin) {
    ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>ورود بخش مدیریت نرم‌افزار — مقاره ۳۶۰</title>
  <style>
    :root { --bg:#0f1714; --card:#1a2e24; --text:#ecfdf5; --muted:#94a3b8; --accent:#c9a962; }
    * { box-sizing: border-box; }
    body { margin:0; font-family:Tahoma,"Segoe UI",Arial,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1.5rem; }
    .lock-wrap { max-width:440px; width:100%; background:var(--card); border:1px solid rgba(201,169,98,.25); border-radius:16px; padding:2rem 1.75rem; text-align:center; box-shadow:0 20px 50px rgba(0,0,0,.35); }
    h1 { margin:0 0 1rem; font-size:1.25rem; line-height:1.6; }
    p { margin:0 0 1.5rem; color:var(--muted); font-size:.95rem; line-height:1.75; }
    .btn { display:inline-block; padding:.65rem 1.5rem; background:linear-gradient(135deg,var(--accent),#a88b42); color:#0f1714; text-decoration:none; border-radius:10px; font-weight:700; font-size:.95rem; }
    .btn:hover { opacity:.92; }
    .back { display:block; margin-top:1.25rem; color:var(--muted); font-size:.88rem; text-decoration:none; }
    .back:hover { color:var(--accent); }
  </style>
</head>
<body>
  <div class="lock-wrap">
    <h1>ورود بخش مدیریت نرم‌افزار</h1>
    <p>این بخش مخصوص مدیران مجاز نرم‌افزار است. برای ادامه، ابتدا وارد حساب مدیریتی شوید.</p>
    <a class="btn" href="owner-login.php">ورود مدیر سیستم</a>
    <a class="back" href="./">بازگشت به صفحه اصلی</a>
  </div>
  <script>
  (function(){if('serviceWorker'in navigator){navigator.serviceWorker.getRegistrations().then(function(r){r.forEach(function(x){x.unregister();});});}if(window.caches&&caches.keys){caches.keys().then(function(k){k.forEach(function(n){caches.delete(n);});});}})();
  </script>
</body>
</html>
    <?php
    exit;
}

$hubLinks = m360_admin_index_hub_links();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>کنسول مدیریت مقاره ۳۶۰</title>
  <style>
    :root { --bg:#0f1714; --card:#fff; --green:#1a2e24; --text:#1e293b; --muted:#64748b; --accent:#14532d; --gold:#c9a962; }
    * { box-sizing: border-box; }
    body { margin:0; font-family:Tahoma,"Segoe UI",Arial,sans-serif; background:#f4f7f5; color:var(--text); line-height:1.7; }
    .hub-wrap { max-width:960px; margin:0 auto; padding:1.5rem 1.25rem 2.5rem; }
    .hub-top { display:flex; flex-wrap:wrap; gap:.75rem; justify-content:space-between; align-items:center; margin-bottom:1.25rem; }
    .hub-top a { font-size:.88rem; color:var(--muted); text-decoration:none; }
    .hub-top a:hover { color:var(--accent); }
    .hub-hero { background:linear-gradient(135deg,var(--green),#0f1714); color:#ecfdf5; border-radius:14px; padding:1.75rem 1.5rem; margin-bottom:1.25rem; border:1px solid rgba(201,169,98,.2); }
    .hub-hero h1 { margin:0 0 .45rem; font-size:1.35rem; }
    .hub-hero p { margin:0; font-size:.92rem; opacity:.9; }
    .hub-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:.85rem; }
    .hub-card { background:var(--card); border:1px solid #d8e2dc; border-radius:12px; padding:1.15rem; text-decoration:none; color:inherit; display:block; transition:border-color .2s,box-shadow .2s; }
    .hub-card:hover { border-color:var(--gold); box-shadow:0 8px 24px rgba(0,0,0,.08); }
    .hub-card h2 { margin:0 0 .35rem; font-size:1rem; color:var(--green); }
    .hub-card p { margin:0; font-size:.85rem; color:var(--muted); }
    .hub-note { margin-top:1.25rem; font-size:.82rem; color:var(--muted); text-align:center; }
  </style>
</head>
<body>
  <div class="hub-wrap">
    <div class="hub-top">
      <a href="./">بازگشت به صفحه اصلی</a>
      <a href="owner-login.php">خروج / بازگشت به ورود مدیریت</a>
    </div>
    <header class="hub-hero">
      <h1>کنسول مدیریت مقاره ۳۶۰</h1>
      <p>دسترسی مدیریتی محافظت‌شده برای کنترل وضعیت نرم‌افزار، کاربران، مسیرها و گزارش‌های اجرایی</p>
    </header>
    <div class="hub-grid">
      <?php foreach ($hubLinks as $link): ?>
        <a class="hub-card" href="<?= m360_admin_index_h($link['href']) ?>">
          <h2><?= m360_admin_index_h($link['title']) ?></h2>
          <p><?= m360_admin_index_h($link['desc']) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
    <p class="hub-note">این بخش فقط برای مدیران مجاز است. صفحه عمومی در <a href="./">صفحه اصلی</a> در دسترس است.</p>
  </div>
</body>
</html>
