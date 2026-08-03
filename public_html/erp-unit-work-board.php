<?php
declare(strict_types=1);

/**
 * MOGHARE360 G0.2R1 — Specialist unit work board (MECHANICAL / ELECTRICAL / OPTIONS).
 * Filter only via server-side allowlist. No writes on GET.
 * G0.2R7 — visual/navigation consistency only.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-canonical-host-helper.php';
m360_canonical_local_host_enforce();

require_once __DIR__ . '/includes/m360-release-hardening-helper.php';
require_once __DIR__ . '/includes/m360-fulljob-lifecycle-helper.php';
require_once __DIR__ . '/includes/mirror-layout.php';

m360_release_hardening_require_staff();

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN']);

$unitRaw = strtoupper(trim((string)($_GET['unit'] ?? '')));
if (!m360_fulljob_is_valid_team_code($unitRaw)) {
    http_response_code(400);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><title>واحد نامعتبر</title>';
    echo '<link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css"></head><body class="m360-rc-page">';
    echo '<div class="w1c-wrap"><section class="w1c-card"><h1>واحد نامعتبر</h1>';
    echo '<p>پارامتر واحد نامعتبر است. یکی از واحدهای مکانیک، برق یا آپشن را انتخاب کنید.</p>';
    echo '<p><a class="m360-rc-btn" href="erp-operations-home.php">بازگشت به عملیات تعمیرگاه</a></p>';
    echo '</section></div></body></html>';
    exit;
}

require_once __DIR__ . '/includes/m360-access-matrix-guard.php';
require_once __DIR__ . '/includes/m360-workshop-access-enforcement.php';
$viewKey = m360_ws_view_permission_for_unit($unitRaw);
if ($viewKey === null) {
    m360_am_forbidden('واحد عملیاتی نامعتبر است.');
}
m360_ws_require($viewKey);
$wsCtx = m360_ws_require_actor_context();

$unit = $unitRaw;
$title = 'کارتابل ' . m360_fulljob_team_label_fa($unit);
$rows = is_resource($conn)
    ? m360_fulljob_unit_work_board($conn, $unit, (int)$wsCtx['company_id'], (bool)$wsCtx['is_owner'])
    : [];

// Technicians only see JobCards where they have an active technician assignment.
if ((string)($actor['role_code'] ?? '') === 'TECHNICIAN' && is_resource($conn) && $rows !== []) {
    $scoped = [];
    foreach ($rows as $row) {
        $jcId = (int)$row['jobcard_id'];
        $can = m360_fulljob_technician_can_open($conn, $jcId, $actor);
        if ($can) {
            $scoped[] = $row;
        }
    }
    $rows = $scoped;
    unset($scoped);
}

$queues = [
    'کارهای جدید' => [],
    'آماده شروع' => [],
    'در حال انجام' => [],
    'متوقف‌شده' => [],
    'در انتظار تأیید مشتری' => [],
    'در انتظار قطعه' => [],
    'در حال بررسی مدیر سالن' => [],
    'برگشت از کنترل کیفیت' => [],
    'تکمیل‌شده و ارسال‌شده برای بررسی مدیر سالن' => [],
];
foreach ($rows as $row) {
    $group = m360_fulljob_unit_board_queue_group($row);
    if (!isset($queues[$group])) {
        $queues[$group] = [];
    }
    $queues[$group][] = $row;
}

erp_auth_context_start();
$displayName = trim((string)($_SESSION['erp_username'] ?? ''));
if ($displayName === '') {
    $displayName = 'کاربر سامانه';
}

$hallReturn = m360_hall_parse_return_context();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= m360_fulljob_h($title) ?> | مقاره ۳۶۰</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <style>
        .m360-unit-shell { max-width: 1180px; margin: 0 auto; }
        .m360-unit-topbar {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: .5rem .75rem; margin: 0 0 .85rem; padding: .5rem .7rem;
            border: 1px solid rgba(34,197,94,.18); border-radius: 12px; background: rgba(0,0,0,.18);
        }
        .m360-unit-topbar__user { color: #c5d0c8; font-size: .86rem; }
        .m360-unit-topbar__user strong { color: #e8f5ee; font-weight: 600; }
        .m360-unit-topbar__nav { display: flex; flex-wrap: wrap; gap: .35rem; align-items: center; }
        .m360-unit-shell .m360-rc-btn {
            min-height: 32px; padding: .22rem .65rem; font-size: .78rem; font-weight: 600;
            border-radius: 10px; box-shadow: none; width: auto;
        }
        .m360-unit-title-row {
            display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between;
            gap: .5rem; margin: 0 0 .85rem;
        }
        .m360-unit-title-row h1 { margin: 0; font-size: 1.35rem; color: #f3f4f6; }
        .m360-unit-title-row p { margin: .25rem 0 0; font-size: .86rem; color: #9ca3af; }
        .m360-unit-queue { margin: .75rem 0 1.1rem; padding: .75rem .8rem; }
        .m360-unit-queue h2 { margin: 0 0 .55rem; font-size: .95rem; color: #e8f5ee; }
        .m360-unit-empty { color: #9ca3af; font-size: .86rem; }
        .m360-unit-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .m360-unit-table { width: 100%; border-collapse: collapse; }
        .m360-unit-table th, .m360-unit-table td {
            padding: .4rem .35rem; border-bottom: 1px solid rgba(34,197,94,.12);
            text-align: right; font-size: .8rem; vertical-align: top;
        }
        .m360-unit-table th { color: #9ca3af; font-weight: 600; white-space: nowrap; }
        .m360-unit-table td { color: #e5e7eb; white-space: nowrap; }
        .m360-unit-table td.m360-unit-desc {
            white-space: normal; max-width: 11rem; overflow: hidden;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        }
        .m360-unit-actions {
            display: flex; flex-wrap: wrap; gap: .25rem .35rem; align-items: center;
            min-width: 9.5rem; max-width: 14rem;
        }
        .m360-unit-actions a.m360-unit-link {
            color: #cbd5e1; font-size: .74rem; text-decoration: none;
            border-bottom: 1px dotted rgba(212,175,55,.45); padding: 0;
            background: none; border-radius: 0; min-height: 0; box-shadow: none;
        }
        .m360-unit-actions a.m360-unit-link:hover { color: #fff8df; border-bottom-color: rgba(212,175,55,.8); }
        @media (max-width: 760px) {
            .m360-unit-topbar { flex-direction: column; align-items: stretch; }
            .m360-unit-actions { max-width: none; }
            .m360-unit-table td.m360-unit-desc { max-width: 9rem; }
        }
    </style>
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap m360-unit-shell">
    <div class="m360-unit-topbar" aria-label="ناوبری کارتابل واحد">
        <div class="m360-unit-topbar__user">کاربر فعال: <strong><?= m360_fulljob_h($displayName) ?></strong></div>
        <nav class="m360-unit-topbar__nav">
            <?php if ($hallReturn['ok']): ?>
                <a class="m360-rc-btn secondary" href="<?= m360_fulljob_h($hallReturn['href']) ?>"><?= m360_fulljob_h($hallReturn['label']) ?></a>
            <?php endif; ?>
            <a class="m360-rc-btn secondary" href="erp-operations-home.php">بازگشت به عملیات تعمیرگاه</a>
            <a class="m360-rc-btn secondary" href="erp-product-home.php">خانه سامانه</a>
        </nav>
    </div>

    <div class="m360-unit-title-row">
        <div>
            <h1><?= m360_fulljob_h($title) ?></h1>
            <p>فقط کارهای تخصیص‌یافته به <?= m360_fulljob_h(m360_fulljob_team_label_fa($unit)) ?> — بدون تداخل واحدهای دیگر</p>
        </div>
    </div>

    <?php if ($rows === []): ?>
        <section class="w1c-card">
            <p class="m360-unit-empty">موردی برای این واحد ثبت نشده است.</p>
        </section>
    <?php else: ?>
        <?php foreach ($queues as $queueTitle => $queueRows): ?>
            <?php if ($queueRows === []) { continue; } ?>
            <section class="w1c-card m360-unit-queue">
                <h2><?= m360_fulljob_h($queueTitle) ?> <span class="m360-num">(<?= m360_fulljob_h(m360_fulljob_to_persian_digits((string)count($queueRows))) ?>)</span></h2>
                <div class="m360-unit-table-wrap">
                <table class="m360-unit-table">
                    <thead>
                    <tr>
                        <th>پرونده کار</th>
                        <th>خودرو</th>
                        <th>پلاک</th>
                        <th>شرح کار</th>
                        <th>واحد</th>
                        <th>تکنسین</th>
                        <th>زمان ارجاع</th>
                        <th>زمان شروع</th>
                        <th>مدت انتظار</th>
                        <th>وضعیت</th>
                        <th>اولویت</th>
                        <th>اقدام</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($queueRows as $row): ?>
                        <?php
                        $jcId = (int)$row['jobcard_id'];
                        $statusFa = m360_fulljob_status_label_fa(
                            (string)($row['work_execution_status'] ?: $row['technical_status'] ?: $row['assignment_status'])
                        );
                        $vehicle = trim((string)($row['brand'] ?? '') . ' ' . (string)($row['model'] ?? ''));
                        $techName = trim((string)($row['technician_full_name'] ?? ''));
                        if ($techName === '' && (int)($row['assigned_to_user_id'] ?? 0) > 0) {
                            $techName = 'تکنسین ثبت‌شده';
                        }
                        if ($techName === '') {
                            $techName = 'ثبت نشده';
                        }
                        $assignedAt = trim((string)($row['assigned_at'] ?? ''));
                        $startedAt = trim((string)($row['work_started_at'] ?? ''));
                        $waitFa = 'ثبت نشده';
                        if ($assignedAt !== '' && $startedAt !== '') {
                            $from = strtotime($assignedAt);
                            $to = strtotime($startedAt);
                            if ($from !== false && $to !== false && $to >= $from) {
                                $waitFa = m360_rui_duration_fa((int)($to - $from));
                            }
                        } elseif ($assignedAt !== '' && strtoupper(trim((string)($row['assignment_status'] ?? ''))) === 'ACTIVE' && $startedAt === '') {
                            $waitFa = 'در انتظار شروع';
                        }
                        $closedAt = trim((string)($row['closed_at'] ?? ''));
                        ?>
                        <tr>
                            <td><?= m360_fulljob_h(m360_fulljob_to_persian_digits((string)$row['jobcard_number'])) ?></td>
                            <td><?= m360_fulljob_h($vehicle !== '' ? $vehicle : '—') ?></td>
                            <td><?= m360_fulljob_h((string)($row['plate_number'] ?? '—')) ?></td>
                            <td class="m360-unit-desc"><?= m360_fulljob_h((string)($row['assignment_description'] ?? '—')) ?></td>
                            <td><?= m360_fulljob_h(m360_fulljob_team_label_fa((string)$row['team_code'])) ?></td>
                            <td><?= m360_fulljob_h($techName) ?></td>
                            <td><?= m360_fulljob_h(m360_fulljob_display_jalali_datetime($assignedAt !== '' ? $assignedAt : null)) ?></td>
                            <td><?= m360_fulljob_h(m360_fulljob_display_jalali_datetime($startedAt !== '' ? $startedAt : null)) ?></td>
                            <td><?= m360_fulljob_h($waitFa) ?></td>
                            <td><?= m360_fulljob_h($statusFa) ?>
                              <?php if ($closedAt !== ''): ?>
                                <div class="m360-unit-empty">تکمیل: <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime($closedAt)) ?></div>
                              <?php endif; ?>
                            </td>
                            <td><?= m360_fulljob_h(m360_fulljob_priority_label_fa((string)($row['priority'] ?? ''))) ?></td>
                            <td>
                                <div class="m360-unit-actions">
                                    <a class="m360-rc-btn" href="erp-work-execution-detail.php?jobcard_id=<?= $jcId ?>&unit=<?= rawurlencode($unit) ?>">اجرای کار</a>
                                    <a class="m360-unit-link" href="erp-technical-request-center.php?jobcard_id=<?= $jcId ?>">درخواست فنی</a>
                                    <a class="m360-unit-link" href="erp-parts-request-handoff.php">درخواست قطعه</a>
                                    <a class="m360-unit-link" href="erp-work-hold-board.php">توقف</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script src="assets/js/m360-release-hardening.js"></script>
</body>
</html>
