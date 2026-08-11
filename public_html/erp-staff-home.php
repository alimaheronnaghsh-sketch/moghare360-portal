<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-staff-home-helper.php';

$conn = customer_core_db();
$ctx = m360_staff_home_load_context($conn);

$userId = (int)($ctx['user_id'] ?? 0);
$isUnknown = !empty($ctx['is_unknown']);
$roleCode = strtoupper(trim((string)($ctx['role_code'] ?? 'UNKNOWN')));
$workbenchGroups = is_array($ctx['workbench_groups'] ?? null) ? $ctx['workbench_groups'] : [];

$displayName = trim(m360_staff_home_text_from_odbc((string)($ctx['full_name'] ?? '')));
if ($displayName === '') {
    $displayName = trim((string)($ctx['username'] ?? ''));
}
if ($displayName === '') {
    $displayName = 'کاربر سامانه';
}

$roleLabel = trim((string)($ctx['role_label_fa'] ?? m360_staff_home_role_label_fa($roleCode)));
$deptRaw = trim(m360_staff_home_text_from_odbc((string)($ctx['department_name'] ?? '')));
$posRaw = trim(m360_staff_home_text_from_odbc((string)($ctx['position_name'] ?? '')));
$deptName = ($deptRaw === '' || $deptRaw === '—') ? 'ثبت نشده' : $deptRaw;
$posName = ($posRaw === '' || $posRaw === '—') ? 'ثبت نشده' : $posRaw;

/**
 * Development / non-operational destinations — keep definitions in helper, do not render here.
 *
 * @var list<string>
 */
$hiddenFiles = [
    'erp-product-home.php',
    'erp-route-map.php',
    'erp-release-readiness.php',
    'erp-owner-control-center.php',
    'erp-management-dashboard.php',
    'erp-access-permission-preview.php',
    'erp-soft-run-readiness.php',
    'erp-soft-run-operator-test-pack.php',
];

/**
 * @param array<string, string> $item
 */
$isOperationalItem = static function (array $item) use ($hiddenFiles): bool {
    $cardType = (string)($item['card_type'] ?? 'nav');
    $file = trim((string)($item['file'] ?? ''));
    if (in_array($cardType, ['backlog', 'info', 'note', 'runtime_hold', 'diag'], true)) {
        return false;
    }
    if ($file === '' || in_array($file, $hiddenFiles, true)) {
        return false;
    }
    if (!m360_staff_home_item_clickable($item)) {
        return false;
    }

    $blob = mb_strtolower(
        (string)($item['label_fa'] ?? '') . ' ' . (string)($item['description_fa'] ?? ''),
        'UTF-8'
    );
    foreach (['permission', 'override', 'impersonation', 'backlog', 'hr self-service', 'read-only', 'p15', 'p1–p10', 'p1-p10'] as $bad) {
        if (str_contains($blob, $bad)) {
            return false;
        }
    }

    return true;
};

/**
 * @param string $text
 */
$faLabel = static function (string $text): string {
    $text = str_replace(
        ['JobCard', 'Jobcard', 'jobcard', 'QC', 'ERP'],
        ['پرونده کار', 'پرونده کار', 'پرونده کار', 'کنترل کیفیت', 'سامانه'],
        $text
    );

    return $text;
};

$dailyItems = [];
$adminItems = [];
$groupOrder = [
    M360_STAFF_HOME_GROUP_TODAY,
    M360_STAFF_HOME_GROUP_FOLLOWUP,
    M360_STAFF_HOME_GROUP_OPERATIONS,
    M360_STAFF_HOME_GROUP_REPORTS,
    M360_STAFF_HOME_GROUP_MANAGER_REF,
    M360_STAFF_HOME_GROUP_COORDINATION_REF,
];
foreach ($groupOrder as $groupKey) {
    $items = $workbenchGroups[$groupKey] ?? [];
    if (!is_array($items)) {
        continue;
    }
    foreach ($items as $item) {
        if (!is_array($item) || !$isOperationalItem($item)) {
            continue;
        }
        $file = (string)($item['file'] ?? '');
        if ($file === 'erp-access-management.php') {
            $adminItems[] = $item;
            continue;
        }
        $dailyItems[] = $item;
    }
}

$pageTitle = 'میز کار من';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= m360_staff_home_h($pageTitle) ?> | ماهین 360°</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <style>
        .m360-me-shell { max-width: 920px; margin: 0 auto; }
        .m360-me-topbar {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: .55rem; margin: 0 0 .85rem; padding: .55rem .75rem;
            border: 1px solid rgba(34,197,94,.18); border-radius: 12px; background: rgba(0,0,0,.18);
        }
        .m360-me-topbar__links { display: flex; flex-wrap: wrap; gap: .4rem; }
        .m360-me-shell .m360-rc-btn {
            min-height: 34px; padding: .28rem .7rem; font-size: .8rem; font-weight: 600;
            border-radius: 10px; box-shadow: none; width: auto;
        }
        .m360-me-summary {
            display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .55rem;
            margin: 0 0 1rem; padding: .75rem .85rem;
            border: 1px solid rgba(34,197,94,.2); border-radius: 12px;
            background: linear-gradient(165deg, #1f3229 0%, #16241d 55%, #122019 100%);
            color: #e5e7eb;
        }
        .m360-me-summary dt { margin: 0; font-size: .72rem; color: #9ca3af; }
        .m360-me-summary dd { margin: .15rem 0 0; font-size: .9rem; color: #f3f4f6; font-weight: 600; }
        .m360-me-list { display: flex; flex-direction: column; gap: .55rem; margin: 0 0 1rem; }
        .m360-me-item {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: .55rem .85rem; padding: .7rem .85rem;
            border: 1px solid rgba(34,197,94,.18); border-radius: 12px;
            background: linear-gradient(160deg, rgba(22,34,29,.94), rgba(15,23,42,.72));
            color: #e5e7eb;
        }
        .m360-me-item h3 { margin: 0 0 .2rem; font-size: .95rem; color: #fff8df; }
        .m360-me-item p { margin: 0; font-size: .8rem; color: #9ca3af; line-height: 1.45; max-width: 36rem; }
        .m360-me-admin {
            margin: 0 0 1rem; padding: .65rem .85rem;
            border: 1px solid rgba(212,175,55,.35); border-radius: 12px;
            background: rgba(15,23,42,.45); color: #e5e7eb;
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .55rem;
        }
        .m360-me-admin p { margin: 0; font-size: .84rem; color: #c5d0c8; }
        .m360-me-empty { color: #9ca3af; font-size: .9rem; }
        @media (max-width: 760px) {
            .m360-me-summary { grid-template-columns: 1fr 1fr; }
            .m360-me-item { flex-direction: column; align-items: stretch; }
            .m360-me-item .m360-rc-btn { align-self: flex-start; }
        }
    </style>
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap m360-me-shell">
    <div class="m360-me-topbar" aria-label="ناوبری میز کار">
        <div style="color:#c5d0c8;font-size:.88rem;">کاربر فعال: <strong style="color:#e8f5ee;"><?= m360_staff_home_h($displayName) ?></strong></div>
        <div class="m360-me-topbar__links">
            <a class="m360-rc-btn secondary" href="erp-product-home.php">خانه سامانه</a>
            <a class="m360-rc-btn secondary" href="staff-logout.php">خروج</a>
        </div>
    </div>

    <header class="w1c-banner">
        <h1><?= m360_staff_home_h($pageTitle) ?></h1>
        <p>کارهای روزانه و مقاصد مجاز شما در سامانه</p>
    </header>

    <dl class="m360-me-summary" aria-label="خلاصه کاربر">
        <div>
            <dt>نام کاربر</dt>
            <dd><?= m360_staff_home_h($displayName) ?></dd>
        </div>
        <div>
            <dt>نقش</dt>
            <dd><?= m360_staff_home_h($roleLabel) ?></dd>
        </div>
        <div>
            <dt>واحد</dt>
            <dd><?= m360_staff_home_h($deptName) ?></dd>
        </div>
        <div>
            <dt>سمت</dt>
            <dd><?= m360_staff_home_h($posName) ?></dd>
        </div>
    </dl>

    <?php if ($isUnknown): ?>
        <section class="w1c-card">
            <p class="m360-me-empty"><?= m360_staff_home_h(M360_STAFF_HOME_UNKNOWN_WARNING_FA) ?></p>
        </section>
    <?php else: ?>
        <?php if ($roleCode === 'RECEPTION'): ?>
            <div class="m360-me-item" style="margin-bottom:.75rem;">
                <div>
                    <h3>میز کار پذیرش</h3>
                    <p>درخواست آنلاین، تکمیل پرونده و قرارداد در یک مسیر</p>
                </div>
                <a class="m360-rc-btn" href="erp-reception-workbench.php">ورود</a>
            </div>
        <?php endif; ?>

        <h2 class="m360-section-title" style="margin:.35rem 0 .65rem;font-size:1rem;color:#e8f5ee;">کار روزانه</h2>
        <?php if ($dailyItems === []): ?>
            <section class="w1c-card">
                <p class="m360-me-empty">اقدام عملیاتی فعالی برای نمایش وجود ندارد.</p>
            </section>
        <?php else: ?>
            <div class="m360-me-list">
                <?php foreach ($dailyItems as $item): ?>
                    <?php
                    $file = (string)($item['file'] ?? '');
                    $href = m360_staff_home_route_href($file, $userId);
                    $label = $faLabel((string)($item['label_fa'] ?? ''));
                    $desc = $faLabel((string)($item['description_fa'] ?? ''));
                    ?>
                    <article class="m360-me-item">
                        <div>
                            <h3><?= m360_staff_home_h($label) ?></h3>
                            <?php if ($desc !== ''): ?>
                                <p><?= m360_staff_home_h($desc) ?></p>
                            <?php endif; ?>
                        </div>
                        <a class="m360-rc-btn" href="<?= m360_staff_home_h($href) ?>">ورود</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($adminItems !== []): ?>
            <?php
            $admin = $adminItems[0];
            $adminHref = m360_staff_home_route_href((string)($admin['file'] ?? ''), $userId);
            ?>
            <div class="m360-me-admin">
                <p>مدیریت دسترسی پرسنل</p>
                <a class="m360-rc-btn secondary" href="<?= m360_staff_home_h($adminHref) ?>">باز کردن</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
