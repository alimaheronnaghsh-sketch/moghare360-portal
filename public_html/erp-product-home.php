<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-canonical-host-helper.php';
m360_canonical_local_host_enforce();

require_once __DIR__ . '/includes/m360-release-hardening-helper.php';
require_once __DIR__ . '/includes/m360-release-readiness-helper.php';

m360_release_hardening_require_staff();

$audit = m360_release_hardening_audit();
$report = m360_release_readiness_report();
$hostBare = explode(':', strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? ''))), 2)[0];
$isLocalDevHost = ($hostBare === '127.0.0.1' || m360_canonical_is_localhost_host($hostBare));

$moduleLinks = [
    ['label' => 'میز پذیرش', 'href' => 'erp-reception-workbench.php?section=reception', 'phase' => 'P2'],
    ['label' => 'شروع درخواست حضوری توسط پذیرش', 'href' => 'erp-reception-walkin-create.php', 'phase' => 'P2'],
    ['label' => 'کارتابل مدیر سالن', 'href' => 'erp-hall-cartable.php', 'phase' => 'P3'],
    ['label' => 'برد تکنسین', 'href' => 'erp-technician-work-board.php?jobcard_id=16', 'phase' => 'P3'],
    ['label' => 'مرکز درخواست فنی', 'href' => 'erp-technical-request-center.php?jobcard_id=16', 'phase' => 'P3'],
    ['label' => 'برآورد', 'href' => 'erp-estimate-detail.php?estimate_id=34', 'phase' => 'P4'],
    ['label' => 'اجرای کار', 'href' => 'erp-work-execution-board.php', 'phase' => 'P5'],
    ['label' => 'کنترل کیفیت', 'href' => 'erp-qc-board.php', 'phase' => 'P6'],
    ['label' => 'فاکتور نهایی', 'href' => 'erp-final-invoice-board.php', 'phase' => 'P7'],
    ['label' => 'کنترل تحویل', 'href' => 'erp-delivery-control.php?jobcard_id=16', 'phase' => 'P7'],
    ['label' => 'داشبورد مدیریت', 'href' => 'erp-management-dashboard.php', 'phase' => 'P8'],
];

/* Phase B: intake hardcoded UAT shortcuts (request 28 / contract 3) removed from nav. */
/* Task 41 must NEVER appear as a contract signing shortcut in normal workflow. */
$uatLinks = [
    ['label' => 'جزئیات JobCard 16', 'href' => 'erp-hall-jobcard-detail.php?jobcard_id=16'],
    ['label' => 'درخواست قطعه / مواد', 'href' => 'erp-parts-request-handoff.php?jobcard_id=16'],
    ['label' => 'خدمت خارج از مجموعه', 'href' => 'erp-external-service-handoff.php?jobcard_id=16'],
    ['label' => 'شفاف‌سازی مشتری', 'href' => 'erp-customer-clarification-queue.php?jobcard_id=16'],
    ['label' => 'توقف کار / ایمنی', 'href' => 'erp-work-hold-board.php?jobcard_id=16'],
];

$devEstimateTestLinks = $isLocalDevHost ? [
    [
        'label' => 'ابزار تست برآورد قدیمی — قرارداد نیست',
        'href' => 'customer-estimate-approval-sign.php?task_id=41',
    ],
] : [];

$warnings = [
    'امضای قرارداد فقط از کارتابل قرارداد / customer-intake-contract-review انجام می‌شود.',
    'OTP/امضای قرارداد خودکار نیست.',
    'تحویل تا عبور QC و گیت مالی مسدود است.',
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خانه محصول MOGHARE360</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
</head>
<body class="m360-rc-page m360-product-home">
<div class="w1c-wrap m360-rc-wrap">
    <header class="w1c-banner m360-page-brand-header">
        <div class="m360-brand-lockup" aria-label="MOGHARE360">
            <img class="m360-brand-logo" src="assets/brand/moghareh-motors-logo.jpg" width="40" height="40" alt="MOGHARE360" onerror="this.style.display='none'">
            <div class="m360-brand-wordmark">
                <span class="m360-brand-wordmark__title" lang="en" dir="ltr">MOGHARE360</span>
                <span class="m360-brand-wordmark__sub">خانه محصول</span>
            </div>
        </div>
        <h1>خانه محصول</h1>
        <p>کنسول مالک — مسیر canonical پذیرش تا تحویل، پوسته لوکس سبز تیره.</p>
    </header>

    <section class="w1c-card">
        <h2>وضعیت آماده‌سازی</h2>
        <div class="m360-rc-cards">
            <div class="m360-rc-card"><div class="val"><span class="m360-rc-badge pass">آماده</span></div><div class="lbl">مسیر عملیاتی P1 تا P7</div></div>
            <div class="m360-rc-card"><div class="val"><span class="m360-rc-badge pass">آماده</span></div><div class="lbl">داشبورد مدیریت P8</div></div>
            <div class="m360-rc-card"><div class="val"><span class="m360-rc-badge pass">UAT</span></div><div class="lbl">فقط پرونده‌های تعریف‌شده توسط مالک</div></div>
            <div class="m360-rc-card"><div class="val"><span class="m360-rc-badge <?= m360_nav_badge_class((string)$audit['rc_status']) ?>"><?= m360_release_h((string)$audit['rc_status']) ?></span></div><div class="lbl">امتیاز آماده‌سازی: <?= m360_release_h((string)($audit['readiness_score'] ?? 0)) ?>٪</div></div>
        </div>
    </section>

    <section class="w1c-card">
        <h2>ورود به ماژول‌ها</h2>
        <div class="m360-rc-cards">
            <?php foreach ($moduleLinks as $link): ?>
                <a class="m360-rc-card" href="<?= m360_release_h((string)$link['href']) ?>">
                    <div class="val m360-rc-phase"><?= m360_release_h((string)$link['phase']) ?></div>
                    <div class="lbl"><?= m360_release_h((string)$link['label']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card">
        <h2>میانبرهای UAT مالک (غیر intake)</h2>
        <p class="m360-rc-note">میانبرهای سخت‌کد intake حذف شدند. مسیر پذیرش: آنلاین یا حضوری → تکمیل پرونده. قرارداد ≠ برآورد.</p>
        <div class="m360-rc-cards">
            <?php foreach ($uatLinks as $link): ?>
                <a class="m360-rc-card" href="<?= m360_release_h((string)$link['href']) ?>">
                    <div class="val m360-rc-phase">UAT</div>
                    <div class="lbl"><?= m360_release_h((string)$link['label']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($devEstimateTestLinks !== []): ?>
    <section class="w1c-card">
        <h2>ابزار توسعه محلی (برآورد)</h2>
        <p class="m360-rc-note">فقط روی 127.0.0.1 — Task 41 برآورد است و برای امضای قرارداد استفاده نمی‌شود.</p>
        <div class="m360-rc-cards">
            <?php foreach ($devEstimateTestLinks as $link): ?>
                <a class="m360-rc-card" href="<?= m360_release_h((string)$link['href']) ?>">
                    <div class="val m360-rc-phase">DEV</div>
                    <div class="lbl"><?= m360_release_h((string)$link['label']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="w1c-card">
        <h2>مدیریت کاربران و دسترسی‌ها</h2>
        <p class="m360-rc-note">مالک می‌تواند نقش‌ها را ببیند؛ OTP/امضای مشتری را انجام نمی‌دهد.</p>
        <p>
            <a class="m360-rc-btn" href="erp-user-role-admin.php">داشبورد کاربران و نقش‌ها</a>
            <a class="m360-rc-btn secondary" href="erp-access-management.php">کنسول مدیریت دسترسی</a>
        </p>
    </section>

    <section class="w1c-card">
        <h2>مسیرهای مالک UAT</h2>
        <p>
            <a class="m360-rc-btn" href="erp-route-map.php">نقشه مسیرها</a>
            <a class="m360-rc-btn secondary" href="erp-link-audit.php">بررسی لینک‌ها</a>
            <a class="m360-rc-btn secondary" href="erp-release-readiness.php">آمادگی انتشار</a>
        </p>
    </section>

    <section class="w1c-card">
        <h2>یادداشت‌های کوتاه</h2>
        <ul class="m360-product-home-notes">
            <?php foreach ($warnings as $warning): ?>
                <li><?= m360_release_h($warning) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="m360-rc-note">مسیرهای قابل مشاهده: <?= (int)($audit['existing_files'] ?? 0) ?>/<?= (int)($audit['total_routes'] ?? 0) ?></p>
    </section>
</div>
<script src="assets/js/m360-release-hardening.js"></script>
</body>
</html>
