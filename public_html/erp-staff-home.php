<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-staff-home-helper.php';

$conn = customer_core_db();
$ctx = m360_staff_home_load_context($conn);

$userId = (int)($ctx['user_id'] ?? 0);
$isUnknown = !empty($ctx['is_unknown']);
$routes = is_array($ctx['allowed_routes'] ?? null) ? $ctx['allowed_routes'] : [];

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
    </header>

    <section class="m360-staff-card">
        <h2>شناسه کاربر</h2>
        <div class="m360-staff-grid">
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h((string)$userId) ?></div><div class="lbl">user_id</div></div>
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h((string)($ctx['username'] ?? '')) ?></div><div class="lbl">نام کاربری</div></div>
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h((string)($ctx['full_name'] ?? '')) ?></div><div class="lbl">نام نمایشی</div></div>
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h((string)($ctx['role_code'] ?? 'UNKNOWN')) ?></div><div class="lbl">role_code</div></div>
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h((string)($ctx['department_name'] ?? '—')) ?></div><div class="lbl">واحد</div></div>
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h((string)($ctx['position_name'] ?? '—')) ?></div><div class="lbl">سمت</div></div>
            <?php if ((int)($ctx['permission_count'] ?? 0) > 0): ?>
            <div class="m360-staff-kpi"><div class="val"><?= m360_staff_home_h((string)(int)$ctx['permission_count']) ?></div><div class="lbl">تعداد Permission مؤثر</div></div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($isUnknown): ?>
        <div class="m360-staff-alert-warn"><?= m360_staff_home_h(M360_STAFF_HOME_UNKNOWN_WARNING_FA) ?></div>
    <?php else: ?>
        <section class="m360-staff-card">
            <h2>صفحات مجاز برای نقش شما</h2>
            <div class="m360-staff-routes">
                <?php foreach ($routes as $route): ?>
                    <?php m360_staff_home_render_route_card($route, $userId); ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
