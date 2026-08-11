<?php
declare(strict_types=1);

/**
 * MOGHARE360 P2 — Reception JobCards dashboard (read-only GET).
 * G0.2R8 — theme / return / Persian labels only.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-jobcard-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'reception-ui-helper.php';

m360_reception_jobcard_require_staff();
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$p15Missing = !m360_reception_jobcard_p15_gate_available();
$statusFilter = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'ALL';
$contractFilter = isset($_GET['contract']) ? strtoupper(trim((string)$_GET['contract'])) : 'ALL';
$conn = customer_core_db();
$jobcards = [];
$dbOk = $conn !== false;

if ($dbOk) {
    $jobcards = m360_reception_jobcard_list(
        $conn,
        $statusFilter === 'ALL' ? null : $statusFilter,
        $contractFilter === 'ALL' ? null : $contractFilter,
        500
    );
}
$sort = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($_GET['sort'] ?? 'id'))) ?: 'id';
$dir = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
$sortKey = $sort === 'date' ? 'created_at' : 'jobcard_id';
$jobcards = m360_rui_sort_rows($jobcards, $sortKey, $dir);
$pageInfo = m360_rui_paginate($jobcards, max(1, (int)($_GET['page'] ?? 1)), 10);
$jobcardRows = $pageInfo['rows'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>پرونده‌های کار پذیرش | ماهین 360°</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <?php m360_operational_shell_render_stylesheets(); ?>
    <style>
        .p2-jc-wrap { max-width: 1120px; margin: 0 auto; box-sizing: border-box; overflow-x: hidden; }
        .p2-jc-top { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.5rem; margin:0 0 .85rem; }
        .p2-jc-top h1 { margin:0; font-size:1.35rem; color:#f3f4f6; }
        .p2-jc-wrap .m360-rc-btn { min-height:32px; padding:.22rem .7rem; font-size:.78rem; font-weight:600; border-radius:10px; box-shadow:none; width:auto; }
        .p2-jc-filters { display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:.65rem; }
        .p2-jc-filters a { padding:.32rem .7rem; border-radius:999px; border:1px solid rgba(34,197,94,.28); text-decoration:none; color:#e5e7eb; font-size:.8rem; background:rgba(15,23,42,.55); }
        .p2-jc-filters a.active { background:#0f766e; color:#fff; border-color:#14b8a6; }
        .p2-jc-filters .label { width:100%; font-size:.78rem; color:#9ca3af; margin-top:.35rem; }
        .p2-jc-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; max-width:100%; }
        .p2-jc-table { width:100%; border-collapse:collapse; font-size:.8rem; table-layout:fixed; color:#e5e7eb; }
        .p2-jc-table th, .p2-jc-table td { padding:.4rem .35rem; border-bottom:1px solid rgba(34,197,94,.12); text-align:right; vertical-align:middle; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .p2-jc-table th { background:rgba(0,0,0,.18); font-weight:600; color:#9ca3af; }
        .p2-jc-badge { display:inline-block; padding:.15rem .45rem; border-radius:999px; font-size:.74rem; background:rgba(148,163,184,.18); color:#e5e7eb; }
        .p2-jc-badge.signed { background:rgba(6,95,70,.45); color:#a7f3d0; }
        .p2-jc-badge.unsigned { background:rgba(127,29,29,.4); color:#fecaca; }
        .p2-jc-badge.overridden { background:rgba(146,64,14,.4); color:#fde68a; }
        .p2-jc-alert { padding:.75rem .9rem; border-radius:10px; margin-bottom:.85rem; background:rgba(127,29,29,.35); color:#fecaca; border:1px solid rgba(248,113,113,.35); }
        .p2-jc-empty { padding:1.5rem; text-align:center; color:#9ca3af; }
        .p2-jc-wrap .w1c-banner { background:transparent; border:0; box-shadow:none; padding:0; margin:0 0 .75rem; color:#e5e7eb; }
        .p2-jc-wrap .w1c-banner p { color:#9ca3af; }
        .p2-jc-wrap .w1c-card { background:linear-gradient(160deg,rgba(22,34,29,.94),rgba(15,23,42,.72)); border:1px solid rgba(34,197,94,.2); color:#e5e7eb; }
    </style>
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap p2-jc-wrap">
    <?php m360_operational_shell_render_board('reception_jobcards'); ?>
    <div class="p2-jc-top">
        <div>
            <h1>پرونده‌های کار پذیرش</h1>
            <p style="margin:.25rem 0 0;font-size:.86rem;color:#9ca3af;">پیگیری مراجعه، ثبت ورود و آماده‌سازی فنی</p>
        </div>
        <a class="m360-rc-btn secondary" href="erp-reception-workbench.php">بازگشت به پذیرش</a>
    </div>
    <p style="margin:0 0 .75rem;font-size:.82rem;">
        <a href="?<?= m360_rui_h(m360_rui_query_keep(['sort'=>'id','dir'=>$sort==='id'&&$dir==='desc'?'asc':'desc','page'=>1])) ?>">مرتب‌سازی شناسه</a>
        · <a href="?<?= m360_rui_h(m360_rui_query_keep(['sort'=>'date','dir'=>$sort==='date'&&$dir==='desc'?'asc':'desc','page'=>1])) ?>">مرتب‌سازی تاریخ</a>
    </p>

    <?php if ($p15Missing): ?>
        <div class="p2-jc-alert">گیت پذیرش ناقص است — ادامه عملیات کنترل‌شده ممکن نیست.</div>
    <?php endif; ?>

    <?php if (!$dbOk): ?>
        <section class="w1c-card">
            <p>اتصال به پایگاه داده برقرار نشد. لطفاً بعداً تلاش کنید.</p>
        </section>
    <?php else: ?>
        <section class="w1c-card">
            <nav class="p2-jc-filters" aria-label="فیلتر وضعیت پرونده کار">
                <span class="label">وضعیت پرونده:</span>
                <?php
                $statusFilters = ['ALL' => 'همه'] + array_combine(
                    m360_jobcard_workflow_statuses(),
                    array_map('m360_jobcard_workflow_status_label', m360_jobcard_workflow_statuses())
                );
                foreach ($statusFilters as $code => $label):
                    $active = ($statusFilter === $code);
                    $href = '?status=' . rawurlencode($code) . '&contract=' . rawurlencode($contractFilter);
                ?>
                    <a href="<?= m360_reception_jobcard_h($href) ?>" class="<?= $active ? 'active' : '' ?>"><?= m360_reception_jobcard_h($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <nav class="p2-jc-filters" aria-label="فیلتر قرارداد">
                <span class="label">وضعیت قرارداد:</span>
                <?php foreach (m360_reception_jobcard_contract_filter_codes() as $code):
                    $active = ($contractFilter === $code);
                    $href = '?status=' . rawurlencode($statusFilter) . '&contract=' . rawurlencode($code);
                ?>
                    <a href="<?= m360_reception_jobcard_h($href) ?>" class="<?= $active ? 'active' : '' ?>"><?= m360_reception_jobcard_h(m360_reception_jobcard_contract_filter_label($code)) ?></a>
                <?php endforeach; ?>
            </nav>

            <?php if ($jobcards === []): ?>
                <div class="p2-jc-empty">پرونده کاری برای نمایش وجود ندارد.</div>
            <?php else: ?>
                <div class="p2-jc-table-wrap">
                    <table class="p2-jc-table">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>تاریخ</th>
                            <th>مشتری</th>
                            <th>موبایل</th>
                            <th>خودرو</th>
                            <th>پلاک</th>
                            <th>منبع</th>
                            <th>وضعیت</th>
                            <th>قرارداد</th>
                            <th>امضا / ورود</th>
                            <th>اقدام</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($jobcardRows as $jc):
                            $cs = $jc['contract_summary'] ?? [];
                            $badgeClass = 'unsigned';
                            if (($cs['code'] ?? '') === 'SIGNED') {
                                $badgeClass = 'signed';
                            } elseif (($cs['code'] ?? '') === 'OVERRIDDEN') {
                                $badgeClass = 'overridden';
                            }
                            $arrival = (string)($jc['vehicle_arrival_at'] ?? $jc['checked_in_at'] ?? $jc['reception_at'] ?? '');
                            $signedAt = (string)($cs['signed_at'] ?? '');
                        ?>
                            <tr>
                                <td><?= m360_reception_jobcard_h((string)$jc['jobcard_id']) ?></td>
                                <td><?= m360_rui_h(m360_rui_jalali_date((string)($jc['created_at'] ?? ''))) ?></td>
                                <td title="<?= m360_reception_jobcard_h((string)($jc['customer_name'] ?? '-')) ?>"><?= m360_reception_jobcard_h((string)($jc['customer_name'] ?? '-')) ?></td>
                                <td><?= m360_reception_jobcard_h((string)($jc['customer_mobile'] ?? '-')) ?></td>
                                <td title="<?= m360_reception_jobcard_h((string)($jc['vehicle_label'] ?? '-')) ?>"><?= m360_reception_jobcard_h((string)($jc['vehicle_label'] ?? '-')) ?></td>
                                <td><?= m360_reception_jobcard_h((string)($jc['plate_number'] ?? '-')) ?></td>
                                <td><?= m360_rui_h(m360_rui_label((string)($jc['source_label'] ?? $jc['source'] ?? '-'))) ?></td>
                                <td><span class="p2-jc-badge"><?= m360_rui_h(m360_rui_label((string)($jc['status_label'] ?? $jc['status'] ?? ''))) ?></span></td>
                                <td><span class="p2-jc-badge <?= m360_reception_jobcard_h($badgeClass) ?>"><?= m360_rui_h(m360_rui_label((string)($cs['label'] ?? $cs['code'] ?? '-'))) ?></span></td>
                                <td><?= m360_rui_h(m360_rui_jalali_date($signedAt !== '' ? $signedAt : $arrival)) ?></td>
                                <td><a class="m360-rc-btn" href="erp-reception-jobcard-detail.php?jobcard_id=<?= (int)$jc['jobcard_id'] ?>">ورود</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php m360_rui_render_pagination($pageInfo, m360_rui_query_keep([], ['page'])); ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
</body>
</html>

