<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-canonical-host-helper.php';
m360_canonical_local_host_enforce();

require_once __DIR__ . '/includes/m360-release-hardening-helper.php';

m360_release_hardening_require_staff();

erp_auth_context_start();
$displayName = trim((string)($_SESSION['erp_username'] ?? ''));
if ($displayName === '') {
    $displayName = 'کاربر سامانه';
}

/**
 * Level-1 domain cards — destinations validated against existing filesystem routes.
 * No hardcoded entity IDs. No development/UAT tools.
 *
 * @var list<array{title:string,desc:string,href:string,action:string}>
 */
$domainCards = [
    [
        'title' => 'مشتریان و ارتباط با مشتری',
        'desc' => 'پروفایل مشتری، خودرو، پیگیری، رضایت و باشگاه مشتریان',
        'href' => 'erp-reception-board.php?tab=customers',
        'action' => 'ورود به مشتریان',
    ],
    [
        'title' => 'پذیرش و پرونده خودرو',
        'desc' => 'میز پذیرش، درخواست حضوری و تکمیل پرونده',
        'href' => 'erp-reception-workbench.php',
        'action' => 'ورود به پذیرش',
    ],
    [
        'title' => 'عملیات تعمیرگاه',
        'desc' => 'سالن، واحدها، درخواست فنی، کنترل کیفیت و آماده‌سازی ترخیص',
        'href' => 'erp-operations-home.php',
        'action' => 'ورود به عملیات',
    ],
    [
        'title' => 'انبار و خرید',
        'desc' => 'کالا، موجودی، خرید، تأمین و لجستیک',
        'href' => 'inventory360/dashboard.php',
        'action' => 'ورود به انبار و خرید',
    ],
    [
        'title' => 'مالی و حسابداری',
        'desc' => 'فاکتور، تسویه، دریافت و گزارش مالی',
        'href' => 'erp-final-invoice-board.php',
        'action' => 'ورود به مالی',
    ],
    [
        'title' => 'منابع انسانی',
        'desc' => 'پرسنل، قرارداد، حضور، حقوق و درخواست‌ها',
        'href' => 'peopleos360/dashboard.php',
        'action' => 'ورود به منابع انسانی',
    ],
    [
        'title' => 'گزارش‌ها و کنترل مدیریت',
        'desc' => 'شاخص‌های عملیاتی و نظارت مدیریتی',
        'href' => 'erp-management-dashboard.php',
        'action' => 'ورود به گزارش‌ها',
    ],
    [
        'title' => 'کاربران و تنظیمات',
        'desc' => 'کاربران فعال، نقش‌ها و کنترل دسترسی',
        'href' => 'erp-user-role-admin.php',
        'action' => 'ورود به کاربران و نقش‌ها',
    ],
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ماهین 360° | سامانه یکپارچه مدیریت</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <style>
        .m360-g0-shell { max-width: 1120px; margin: 0 auto; }
        .m360-g0-topbar {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: .75rem; margin: 0 0 1rem; padding: .65rem .85rem;
            border: 1px solid rgba(34,197,94,.18); border-radius: 12px;
            background: rgba(0,0,0,.18);
        }
        .m360-g0-topbar__user { color: #c5d0c8; font-size: .88rem; }
        .m360-g0-topbar__user strong { color: #e8f5ee; font-weight: 600; }
        .m360-g0-domain-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            margin: 1rem 0 1.5rem;
        }
        .m360-g0-domain-card {
            display: flex; flex-direction: column; gap: .55rem;
            min-height: 148px; padding: 1.1rem 1.15rem;
            text-decoration: none; color: inherit;
            border-radius: 14px;
            border: 1px solid rgba(34,197,94,.2);
            background: linear-gradient(165deg, #1f3229 0%, #16241d 55%, #122019 100%);
            box-shadow: 0 12px 28px rgba(0,0,0,.28);
            transition: border-color .15s ease, transform .15s ease;
        }
        .m360-g0-domain-card:hover,
        .m360-g0-domain-card:focus-visible {
            border-color: rgba(34,197,94,.45);
            transform: translateY(-1px);
            outline: none;
        }
        .m360-g0-domain-card h2 {
            margin: 0; font-size: 1.05rem; font-weight: 700; color: #f3f4f6;
        }
        .m360-g0-domain-card p {
            margin: 0; flex: 1; font-size: .86rem; line-height: 1.55; color: #9ca3af;
        }
        .m360-g0-domain-card .m360-rc-btn {
            align-self: flex-start; margin-top: .25rem;
        }
        @media (max-width: 760px) {
            .m360-g0-domain-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="m360-rc-page m360-product-home">
<div class="w1c-wrap m360-rc-wrap m360-g0-shell">
    <div class="m360-g0-topbar" aria-label="نوار کاربر">
        <div class="m360-g0-topbar__user">کاربر فعال: <strong><?= m360_release_h($displayName) ?></strong></div>
        <a class="m360-rc-btn secondary" href="staff-logout.php">خروج</a>
    </div>

    <header class="w1c-banner m360-page-brand-header">
        <div class="m360-brand-lockup" aria-label="ماهین 360°">
            <img class="m360-brand-logo" src="assets/brand/mahin360-logo.png" width="40" height="40" alt="ماهین 360°" onerror="this.style.display='none'">
            <div class="m360-brand-wordmark">
                <span class="m360-brand-wordmark__title">ماهین 360°</span>
                <span class="m360-brand-wordmark__sub">خانه اصلی سامانه</span>
            </div>
        </div>
        <h1>سامانه یکپارچه مدیریت ماهین 360°</h1>
        <p>دسترسی یکپارچه به مشتریان، عملیات، انبار، مالی و منابع انسانی</p>
    </header>

    <section class="w1c-card" aria-labelledby="m360-g0-domains-title">
        <h2 id="m360-g0-domains-title">حوزه‌های اصلی سامانه</h2>
        <div class="m360-g0-domain-grid">
            <?php foreach ($domainCards as $card): ?>
                <a class="m360-g0-domain-card" href="<?= m360_release_h((string)$card['href']) ?>">
                    <h2><?= m360_release_h((string)$card['title']) ?></h2>
                    <p><?= m360_release_h((string)$card['desc']) ?></p>
                    <span class="m360-rc-btn"><?= m360_release_h((string)$card['action']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<script src="assets/js/m360-release-hardening.js"></script>
</body>
</html>
