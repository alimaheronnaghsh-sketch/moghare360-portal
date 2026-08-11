<?php
declare(strict_types=1);

/**
 * MOGHARE360 G0.1 — Operations domain home (UI/navigation shell only).
 * Links existing operational routes. No workflow writes. No hardcoded entity IDs.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-canonical-host-helper.php';
m360_canonical_local_host_enforce();

require_once __DIR__ . '/includes/m360-release-hardening-helper.php';

m360_release_hardening_require_staff();

require_once __DIR__ . '/includes/m360-access-matrix-guard.php';
require_once __DIR__ . '/includes/m360-workshop-access-enforcement.php';
m360_am_guard('workshop.operations.home.view');
m360_ws_require_actor_context();
// m360_ws_object_scope_not_applicable — navigation shell only; no JobCard entity.

erp_auth_context_start();
$displayName = trim((string)($_SESSION['erp_username'] ?? ''));
if ($displayName === '') {
    $displayName = 'کاربر سامانه';
}

/**
 * Focused Operations domain entries.
 * href=null means unit/route gap — shown as unavailable, not a fake link.
 *
 * @var list<array{key:string,title:string,desc:string,href:?string,action:string,available:bool}>
 */
$opsEntries = [
    [
        'key' => 'hall_manager',
        'title' => 'کارتابل مدیر سالن',
        'desc' => 'تحویل از پذیرش، پذیرش پرونده، بررسی محدوده کار، تخصیص واحدها، توقف/ادامه، دریافت کار تکمیل‌شده و ارسال به کنترل کیفیت.',
        'href' => 'erp-hall-cartable.php',
        'action' => 'ورود به کارتابل سالن',
        'available' => true,
    ],
    [
        'key' => 'mechanical',
        'title' => 'واحد مکانیک',
        'desc' => 'کارتابل کارهای تخصیص‌یافته به واحد مکانیک.',
        'href' => 'erp-unit-work-board.php?unit=MECHANICAL',
        'action' => 'ورود به کارتابل مکانیک',
        'available' => true,
    ],
    [
        'key' => 'electrical',
        'title' => 'واحد برق',
        'desc' => 'کارتابل کارهای تخصیص‌یافته به واحد برق.',
        'href' => 'erp-unit-work-board.php?unit=ELECTRICAL',
        'action' => 'ورود به کارتابل برق',
        'available' => true,
    ],
    [
        'key' => 'options',
        'title' => 'واحد آپشن',
        'desc' => 'کارتابل کارهای تخصیص‌یافته به واحد آپشن.',
        'href' => 'erp-unit-work-board.php?unit=OPTIONS',
        'action' => 'ورود به کارتابل آپشن',
        'available' => true,
    ],
    [
        'key' => 'external',
        'title' => 'خدمات بیرونی',
        'desc' => 'پیگیری خدمات برون‌سپاری، بازگشت و زنجیره امانت.',
        'href' => 'erp-external-service-handoff.php',
        'action' => 'ورود به خدمات بیرونی',
        'available' => true,
    ],
    [
        'key' => 'tech_parts',
        'title' => 'درخواست‌های فنی و قطعه',
        'desc' => 'عیب جدید، کار اضافه، قطعه، شواهد، تصمیم مدیر سالن و وضعیت انبار/خرید.',
        'href' => 'erp-hall-review-queue.php',
        'action' => 'صف بررسی فنی',
        'available' => true,
        'secondary_href' => 'erp-parts-request-handoff.php',
        'secondary_action' => 'مسیر قطعه',
    ],
    [
        'key' => 'paused',
        'title' => 'توقف و ادامه کار',
        'desc' => 'پرونده‌های متوقف‌شده و رفع توقف پس از تصمیم سالن.',
        'href' => 'erp-work-hold-board.php',
        'action' => 'تابلوی توقف کار',
        'available' => true,
    ],
    [
        'key' => 'execution',
        'title' => 'اجرای کار واحدها',
        'desc' => 'وضعیت اجرای کار و پرونده‌های در حال انجام تا تکمیل واحد.',
        'href' => 'erp-work-execution-board.php',
        'action' => 'برد اجرای کار',
        'available' => true,
    ],
    [
        'key' => 'qc',
        'title' => 'کنترل کیفیت',
        'desc' => 'صف بازبینی نهایی، تأیید یا برگشت به سالن/واحد.',
        'href' => 'erp-qc-board.php',
        'action' => 'ورود به کنترل کیفیت',
        'available' => true,
    ],
    [
        'key' => 'finance_delivery',
        'title' => 'مالی و آماده‌سازی ترخیص',
        'desc' => 'دست‌به‌دست به مالی برای فاکتور/تسویه و سپس کنترل تحویل خودرو.',
        'href' => 'erp-final-invoice-board.php',
        'action' => 'وضعیت مالی',
        'available' => true,
        'secondary_href' => 'erp-delivery-control.php',
        'secondary_action' => 'کنترل تحویل',
    ],
    [
        'key' => 'service_sales',
        'title' => 'خدمات فروش تعمیرگاه',
        'desc' => 'ثبت خط خدمت بدون مبلغ توسط تکنسین و قیمت‌گذاری ریالی توسط مدیر سالن.',
        'href' => 'erp-workshop-service-entry.php',
        'action' => 'ثبت خدمت',
        'available' => true,
        'secondary_href' => 'erp-workshop-service-pricing.php',
        'secondary_action' => 'قیمت‌گذاری سالن',
    ],
    [
        'key' => 'internal_consumable',
        'title' => 'مواد و ملزومات مصرفی داخلی',
        'desc' => 'ثبت، تأیید و سوابق مصرف داخلی غیرقابل‌صورتحساب مشتری.',
        'href' => 'erp-workshop-internal-consumable-create.php',
        'action' => 'ثبت مصرف داخلی',
        'available' => true,
        'secondary_href' => 'erp-workshop-internal-consumable-queue.php',
        'secondary_action' => 'صف تأیید',
    ],
];

// Menu visibility uses the same resolver as page guards (family keys independent of home.view).
$opsVisibility = [
    'mechanical' => ['workshop.mechanical.view'],
    'electrical' => ['workshop.electrical_options.view'],
    'options' => ['workshop.electrical_options.view'],
    'qc' => ['workshop.qc.queue.view'],
    'finance_delivery' => ['workshop.delivery.queue.view'],
    'tech_parts' => ['workshop.part_request.view_status', 'workshop.part_request.create'],
    'internal_consumable' => ['workshop.internal_consumable.create', 'workshop.internal_consumable.approve'],
];
$opsEntries = array_values(array_filter($opsEntries, static function (array $entry) use ($opsVisibility): bool {
    $key = (string)($entry['key'] ?? '');
    if (!isset($opsVisibility[$key])) {
        return true;
    }
    return m360_ws_can_any($opsVisibility[$key]);
}));
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عملیات تعمیرگاه | ماهین 360°</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <style>
        .m360-ops-shell { max-width: 1120px; margin: 0 auto; }
        .m360-ops-topbar {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: .75rem; margin: 0 0 1rem; padding: .65rem .85rem;
            border: 1px solid rgba(34,197,94,.18); border-radius: 12px;
            background: rgba(0,0,0,.18);
        }
        .m360-ops-topbar__user { color: #c5d0c8; font-size: .88rem; }
        .m360-ops-topbar__user strong { color: #e8f5ee; font-weight: 600; }
        .m360-ops-topbar__links { display: flex; flex-wrap: wrap; gap: .5rem; }
        .m360-ops-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            margin: 1rem 0 1.5rem;
        }
        .m360-ops-card {
            display: flex; flex-direction: column; gap: .55rem;
            min-height: 140px; padding: 1.1rem 1.15rem;
            text-decoration: none; color: inherit;
            border-radius: 14px;
            border: 1px solid rgba(34,197,94,.2);
            background: linear-gradient(165deg, #1f3229 0%, #16241d 55%, #122019 100%);
            box-shadow: 0 12px 28px rgba(0,0,0,.28);
            transition: border-color .15s ease, transform .15s ease;
        }
        a.m360-ops-card:hover,
        a.m360-ops-card:focus-visible {
            border-color: rgba(34,197,94,.45);
            transform: translateY(-1px);
            outline: none;
        }
        .m360-ops-card.is-gap {
            opacity: .72;
            border-style: dashed;
            border-color: rgba(245,158,11,.35);
            background: linear-gradient(165deg, #2a2418 0%, #1a1610 55%, #14110c 100%);
            cursor: default;
        }
        .m360-ops-card h2 {
            margin: 0; font-size: 1.02rem; font-weight: 700; color: #f3f4f6;
        }
        .m360-ops-card p {
            margin: 0; flex: 1; font-size: .86rem; line-height: 1.55; color: #9ca3af;
        }
        .m360-ops-card .m360-rc-btn { align-self: flex-start; margin-top: .25rem; }
        .m360-ops-card .m360-ops-actions { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .25rem; }
        .m360-ops-card .m360-ops-gap-label {
            align-self: flex-start; margin-top: .25rem;
            font-size: .78rem; color: #fbbf24;
        }
        @media (max-width: 760px) {
            .m360-ops-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="m360-rc-page m360-operations-home">
<div class="w1c-wrap m360-rc-wrap m360-ops-shell">
    <div class="m360-ops-topbar" aria-label="نوار کاربر">
        <div class="m360-ops-topbar__user">کاربر فعال: <strong><?= m360_release_h($displayName) ?></strong></div>
        <div class="m360-ops-topbar__links">
            <a class="m360-rc-btn secondary" href="erp-product-home.php">خانه سامانه</a>
            <a class="m360-rc-btn secondary" href="staff-logout.php">خروج</a>
        </div>
    </div>

    <header class="w1c-banner m360-page-brand-header">
        <div class="m360-brand-lockup" aria-label="ماهین 360°">
            <img class="m360-brand-logo" src="assets/brand/mahin360-logo.png" width="40" height="40" alt="ماهین 360°" onerror="this.style.display='none'">
            <div class="m360-brand-wordmark">
                <span class="m360-brand-wordmark__title">ماهین 360°</span>
                <span class="m360-brand-wordmark__sub">حوزه عملیات تعمیرگاه</span>
            </div>
        </div>
        <h1>عملیات تعمیرگاه</h1>
        <p>از تحویل پذیرش تا سالن، واحدها، کنترل کیفیت و آماده‌سازی ترخیص</p>
    </header>

    <section class="w1c-card" aria-labelledby="m360-ops-entries-title">
        <h2 id="m360-ops-entries-title">ورودی‌های حوزه عملیات</h2>
        <div class="m360-ops-grid">
            <?php foreach ($opsEntries as $entry): ?>
                <?php
                $hasPrimary = !empty($entry['available']) && is_string($entry['href'] ?? null) && $entry['href'] !== '';
                $hasSecondary = $hasPrimary && !empty($entry['secondary_href']);
                ?>
                <?php if ($hasPrimary && !$hasSecondary): ?>
                    <a class="m360-ops-card" href="<?= m360_release_h((string)$entry['href']) ?>">
                        <h2><?= m360_release_h((string)$entry['title']) ?></h2>
                        <p><?= m360_release_h((string)$entry['desc']) ?></p>
                        <span class="m360-rc-btn"><?= m360_release_h((string)$entry['action']) ?></span>
                    </a>
                <?php elseif ($hasPrimary && $hasSecondary): ?>
                    <div class="m360-ops-card" role="group" aria-label="<?= m360_release_h((string)$entry['title']) ?>">
                        <h2><?= m360_release_h((string)$entry['title']) ?></h2>
                        <p><?= m360_release_h((string)$entry['desc']) ?></p>
                        <div class="m360-ops-actions">
                            <a class="m360-rc-btn" href="<?= m360_release_h((string)$entry['href']) ?>"><?= m360_release_h((string)$entry['action']) ?></a>
                            <a class="m360-rc-btn secondary" href="<?= m360_release_h((string)$entry['secondary_href']) ?>"><?= m360_release_h((string)$entry['secondary_action']) ?></a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="m360-ops-card is-gap" role="group" aria-label="<?= m360_release_h((string)$entry['title']) ?>">
                        <h2><?= m360_release_h((string)$entry['title']) ?></h2>
                        <p><?= m360_release_h((string)$entry['desc']) ?></p>
                        <span class="m360-ops-gap-label"><?= m360_release_h((string)$entry['action']) ?></span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<script src="assets/js/m360-release-hardening.js"></script>
</body>
</html>
