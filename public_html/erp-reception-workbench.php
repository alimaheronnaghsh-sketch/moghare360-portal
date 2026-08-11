<?php
declare(strict_types=1);

/**
 * MAHIN 360° P11.9-C-2B — Reception Staff Workbench
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';

m360_reception_require_staff();

$section = isset($_GET['section']) ? trim((string)$_GET['section']) : '';
$conn = customer_core_db();
$dbOk = $conn !== false;
$kpi = $dbOk ? m360_rw_workbench_kpis($conn) : [
    'online_active' => 0, 'incomplete' => 0, 'ready_convert' => 0,
    'jobcards_today' => 0, 'contracts_pending' => 0, 'prepayment_owner_pending' => 0, 'rejected_closed' => 0,
];
$hrCards = m360_rw_workbench_hr_shortcuts();
$hubCards = m360_rw_reception_process_hub_cards($kpi);
$isLanding = ($section === '' || $section === 'home');

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>میز کار پذیرش — MAHIN 360°</title>
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
</head>
<body class="m360-public-shell m360-rw-page">
<div class="m360-wrap m360-rw-wrap">
    <header class="m360-rw-header">
        <div class="m360-brand-lockup" aria-label="MAHIN 360°">
            <img class="m360-brand-logo" src="assets/brand/mahin360-logo.png" width="40" height="40" alt="MAHIN 360°" onerror="this.style.display='none'">
            <div class="m360-brand-wordmark">
                <span class="m360-brand-wordmark__title" lang="en" dir="ltr">MAHIN 360°</span>
                <span class="m360-brand-wordmark__sub">میز کار پذیرش</span>
            </div>
        </div>
        <div class="m360-rw-header__top">
            <a class="m360-op-nav-pill" href="erp-staff-home.php">← داشبورد پرسنل</a>
            <?php if (!$isLanding): ?>
                <a class="m360-op-nav-pill" href="erp-reception-workbench.php">میز کار پذیرش</a>
            <?php endif; ?>
            <span class="m360-rw-badge">پذیرش</span>
        </div>
        <h1 class="m360-rw-title">میز کار پذیرش</h1>
        <p class="m360-rw-subtitle">صفحه پشتیبان — مرکز اصلی ارتباط با مشتریان را از کارت زیر باز کنید</p>
    </header>

    <section class="m360-rw-panel" style="margin-bottom:1rem">
        <h2 style="margin:0 0 .4rem">مرکز ارتباط با مشتریان</h2>
        <p class="m360-rw-muted" style="margin:0 0 .75rem">مسیر اصلی پذیرش، پرونده‌ها، جستجو و پیگیری مشتری</p>
        <a class="m360-op-button" href="erp-reception-board.php">ورود به مرکز ارتباط با مشتریان</a>
    </section>

    <?php if (!$dbOk): ?>
        <section class="m360-rw-alert">اتصال به پایگاه داده برقرار نشد. لطفاً بعداً تلاش کنید.</section>
    <?php endif; ?>

    <?php if ($isLanding): ?>
        <section class="m360-rw-landing-grid">
            <a class="m360-rw-landing-card" href="erp-reception-workbench.php?section=reception">
                <h2>پذیرش</h2>
                <p>درخواست‌های آنلاین مشتریان، شروع درخواست حضوری توسط پذیرش، تکمیل پرونده و آماده‌سازی کارت کار</p>
                <span class="m360-op-button-secondary">ورود به hub پذیرش</span>
            </a>
            <a class="m360-rw-landing-card m360-rw-landing-card--profile" href="erp-reception-workbench.php?section=profile">
                <h2>پروفایل پرسنلی</h2>
                <p>پروفایل، مرخصی، مدارک و فیش حقوقی</p>
                <span class="m360-op-button-secondary">ورود</span>
            </a>
        </section>

    <?php elseif ($section === 'profile'): ?>
        <section class="m360-rw-section">
            <h2 class="m360-rw-section-title">پروفایل پرسنلی</h2>
            <div class="m360-rw-card-grid">
                <?php foreach ($hrCards as $card): ?>
                    <article class="m360-rw-card m360-rw-card--hr">
                        <h3><?= m360_rw_h($card['title']) ?></h3>
                        <p><?= m360_rw_h($card['desc']) ?></p>
                        <?php if ($card['placeholder'] || $card['href'] === ''): ?>
                            <span class="m360-rw-placeholder"><?= m360_rw_h($card['placeholder_text']) ?></span>
                        <?php else: ?>
                            <a class="m360-op-button" href="<?= m360_rw_h($card['href']) ?>">ورود</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

    <?php elseif ($section === 'reception'): ?>
        <section class="m360-rw-summary">
            <h2 class="m360-rw-section-title">خلاصه امروز</h2>
            <div class="m360-rw-kpi-grid">
                <div class="m360-rw-kpi"><span class="m360-rw-kpi-val"><?= m360_rw_h((string)(int)$kpi['online_active']) ?></span><span class="m360-rw-kpi-lbl">درخواست‌های فعال</span></div>
                <div class="m360-rw-kpi"><span class="m360-rw-kpi-val"><?= m360_rw_h((string)(int)$kpi['incomplete']) ?></span><span class="m360-rw-kpi-lbl">پرونده ناقص</span></div>
                <div class="m360-rw-kpi"><span class="m360-rw-kpi-val"><?= m360_rw_h((string)(int)$kpi['ready_convert']) ?></span><span class="m360-rw-kpi-lbl">آماده تبدیل</span></div>
                <div class="m360-rw-kpi"><span class="m360-rw-kpi-val"><?= m360_rw_h((string)(int)$kpi['jobcards_today']) ?></span><span class="m360-rw-kpi-lbl">کارت کار امروز</span></div>
                <div class="m360-rw-kpi"><span class="m360-rw-kpi-val"><?= m360_rw_h((string)(int)($kpi['prepayment_owner_pending'] ?? 0)) ?></span><span class="m360-rw-kpi-lbl">تصمیم مالک پیش‌پرداخت</span></div>
            </div>
        </section>
        <section class="m360-rw-section">
            <h2 class="m360-rw-section-title">Hub فرآیند پذیرش</h2>
            <div class="m360-rw-card-grid">
                <?php foreach ($hubCards as $card): ?>
                    <article class="m360-rw-card">
                        <h3><?= m360_rw_h($card['title']) ?></h3>
                        <p><?= m360_rw_h($card['desc']) ?></p>
                        <?php if (!empty($card['subs'])): ?>
                            <div class="m360-op-button-row m360-rw-hub-actions">
                                <?php foreach ($card['subs'] as $idx => $sub): ?>
                                    <a class="m360-op-button<?= $idx === 0 ? ' m360-op-button-primary' : '' ?> m360-rw-hub-btn" href="<?= m360_rw_h($sub['href']) ?>"><?= m360_rw_h($sub['title']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($card['placeholder'] || ($card['href'] ?? '') === ''): ?>
                            <span class="m360-rw-placeholder"><?= m360_rw_h($card['placeholder_text']) ?></span>
                        <?php else: ?>
                            <a class="m360-op-button m360-rw-hub-btn" href="<?= m360_rw_h($card['href']) ?>">ورود</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

    <?php elseif ($section === 'walkin'): ?>
        <section class="m360-rw-panel">
            <h2>شروع درخواست حضوری توسط پذیرش</h2>
            <p class="m360-rw-panel-lead">پذیرش حضوری فقط کانال ورود پرسنل است: همان شیء درخواست آنلاین ایجاد می‌شود و تکمیل پرونده فقط در موتور مشترک پذیرش انجام می‌شود.</p>
            <p class="m360-rw-muted">پس از ایجاد درخواست، مسیر canonical همان «پذیرش آنلاین / تکمیل پرونده» است. مسیر جداگانه عکس یا قرارداد حضوری وجود ندارد.</p>
            <p class="m360-rw-warn">قرارداد، امضای مشتری و OTP قانونی همچنان فقط از مسیر customer-facing انجام می‌شود؛ پذیرش مجاز به امضا یا تأیید از طرف مشتری نیست.</p>
            <div class="m360-rw-actions m360-op-button-row">
                <a class="m360-op-button m360-op-button-primary" href="erp-reception-walkin-create.php">شروع درخواست حضوری توسط پذیرش</a>
                <a class="m360-op-button" href="erp-reception-online-requests.php">پذیرش آنلاین / تکمیل پرونده</a>
                <a class="m360-op-button-secondary" href="erp-reception-workbench.php?section=reception">بازگشت به hub پذیرش</a>
            </div>
            <details class="m360-rw-mock-guides">
                <summary><?= m360_rw_h(M360_RW_MOCK_UX_LABEL_FA) ?></summary>
                <p class="m360-rw-muted">صفحات زیر فقط برای مرور UX هستند و مسیر عملیاتی پذیرش نیستند.</p>
                <div class="m360-rw-actions">
                    <a class="m360-rw-btn m360-rw-btn-secondary m360-rw-btn-mock" href="erp-jobcard-create-ux.php?role=reception"><?= m360_rw_h(M360_RW_MOCK_UX_LABEL_FA) ?> — JobCard</a>
                    <a class="m360-rw-btn m360-rw-btn-secondary m360-rw-btn-mock" href="erp-customer-vehicle-create-ux.php?role=reception"><?= m360_rw_h(M360_RW_MOCK_UX_LABEL_FA) ?> — مشتری/خودرو</a>
                </div>
            </details>
        </section>
    <?php endif; ?>

    <footer class="m360-rw-footer">
        <?php if (!$isLanding): ?>
            <a href="erp-reception-workbench.php">میز کار پذیرش</a>
        <?php endif; ?>
        <a href="erp-reception-online-requests.php">درخواست‌های آنلاین مشتریان</a>
        <a href="erp-reception-jobcards.php">JobCardهای پذیرش</a>
        <a href="erp-staff-home.php">داشبورد پرسنل</a>
    </footer>
</div>
</body>
</html>
