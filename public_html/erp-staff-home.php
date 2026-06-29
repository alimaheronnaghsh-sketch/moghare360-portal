<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-staff-home-helper.php';

$conn = customer_core_db();
$ctx = m360_staff_home_load_context($conn);

$userId = (int)($ctx['user_id'] ?? 0);
$isUnknown = !empty($ctx['is_unknown']);
$workbenchGroups = is_array($ctx['workbench_groups'] ?? null) ? $ctx['workbench_groups'] : [];
$roleStartQuestion = trim((string)($ctx['role_start_question'] ?? ''));

?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= m360_staff_home_h((string)($ctx['landing_label'] ?? 'داشبورد پرسنل')) ?></title>
    <link rel="stylesheet" href="assets/css/m360-staff-home.css">
</head>
<body class="m360-staff-page">
<div class="m360-staff-wrap">
    <div class="m360-staff-topbar">
        <span>MOGHARE360 — ورود پرسنل</span>
        <a href="staff-logout.php">خروج</a>
    </div>

    <header class="m360-staff-banner">
        <h1><?= m360_staff_home_h((string)($ctx['landing_label'] ?? 'داشبورد پرسنل')) ?></h1>
        <p><?= m360_staff_home_h((string)($ctx['access_summary'] ?? '')) ?></p>
        <?php if ($roleStartQuestion !== ''): ?>
        <p class="m360-staff-start-question"><?= m360_staff_home_h($roleStartQuestion) ?></p>
        <?php endif; ?>
    </header>

    <section class="m360-staff-card">
        <h2>اطلاعات کاربر</h2>
        <div class="m360-staff-grid">
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h((string)$userId) ?></div><div class="lbl">شناسه کاربری</div></div>
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h((string)($ctx['username'] ?? '')) ?></div><div class="lbl">نام کاربری</div></div>
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h_db($ctx['full_name'] ?? '') ?></div><div class="lbl">نام نمایشی</div></div>
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h((string)($ctx['role_label_fa'] ?? m360_staff_home_role_label_fa((string)($ctx['role_code'] ?? 'UNKNOWN')))) ?></div><div class="lbl">نقش</div></div>
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h_db($ctx['department_name'] ?? '—') ?></div><div class="lbl">واحد</div></div>
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h_db($ctx['position_name'] ?? '—') ?></div><div class="lbl">سمت</div></div>
            <?php if ((int)($ctx['permission_count'] ?? 0) > 0): ?>
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h((string)(int)$ctx['permission_count']) ?></div><div class="lbl">سطح دسترسی</div></div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($isUnknown): ?>
        <div class="m360-staff-alert-warn"><?= m360_staff_home_h(M360_STAFF_HOME_UNKNOWN_WARNING_FA) ?></div>
    <?php else: ?>
        <section class="m360-staff-card m360-staff-workbench">
            <h2>میز کار روزانه</h2>
            <?php m360_staff_home_render_workbench($workbenchGroups, $userId); ?>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
