<?php
declare(strict_types=1);

/**
 * Intake contracts list — G0.2R8 theme / return / containment only.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'reception-ui-helper.php';

m360_intake_contract_require_staff();
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$statusFilter = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'ALL';
$conn = customer_core_db();
$contracts = $conn !== false ? m360_intake_contract_list($conn, $statusFilter === 'ALL' ? null : $statusFilter, 500) : [];
$sort = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($_GET['sort'] ?? 'id'))) ?: 'id';
$dir = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
$sortKey = $sort === 'date' ? 'sent_at' : 'contract_id';
$contracts = m360_rui_sort_rows($contracts, $sortKey, $dir);
$pageInfo = m360_rui_paginate($contracts, max(1, (int)($_GET['page'] ?? 1)), 10);
$contractRows = $pageInfo['rows'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قراردادهای پذیرش | مقاره ۳۶۰</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <link rel="stylesheet" href="<?= m360_operational_shell_h(m360_operational_shell_css_href()) ?>">
    <style>
        .m360-contract-page { max-width:1120px; margin:0 auto; overflow-x:hidden; box-sizing:border-box; }
        .m360-ct-top { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.5rem; margin:0 0 .85rem; }
        .m360-ct-top h1 { margin:0; font-size:1.35rem; color:#f3f4f6; }
        .m360-contract-page .m360-rc-btn { min-height:32px; padding:.22rem .7rem; font-size:.78rem; font-weight:600; border-radius:10px; box-shadow:none; width:auto; }
        .m360-ct-actions { display:flex; flex-wrap:wrap; gap:.35rem; margin-bottom:.75rem; }
        .m360-ct-actions a {
            padding:.28rem .65rem; border-radius:999px; border:1px solid rgba(34,197,94,.28);
            text-decoration:none; color:#e5e7eb; font-size:.78rem; background:rgba(15,23,42,.55);
        }
        .m360-ct-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; max-width:100%; }
        .m360-ct-table { width:100%; border-collapse:collapse; font-size:.8rem; table-layout:fixed; color:#e5e7eb; }
        .m360-ct-table th, .m360-ct-table td {
            padding:.4rem .35rem; border-bottom:1px solid rgba(34,197,94,.12); text-align:right;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .m360-ct-table th { background:rgba(0,0,0,.18); color:#9ca3af; font-weight:600; }
        .m360-contract-page .w1c-card { background:linear-gradient(160deg,rgba(22,34,29,.94),rgba(15,23,42,.72)); border:1px solid rgba(34,197,94,.2); color:#e5e7eb; }
    </style>
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap m360-contract-page">
    <?php m360_operational_shell_render_board('intake_contracts'); ?>
    <div class="m360-ct-top">
        <div>
            <h1>قراردادهای پذیرش</h1>
            <p style="margin:.25rem 0 0;font-size:.86rem;color:#9ca3af;">تولید، ارسال و پیگیری امضای مشتری</p>
        </div>
        <a class="m360-rc-btn secondary" href="erp-reception-workbench.php">بازگشت به پذیرش</a>
    </div>
    <section class="w1c-card">
        <nav class="m360-ct-actions" aria-label="فیلتر و مرتب‌سازی">
            <?php foreach (['ALL' => 'همه'] + M360_CONTRACT_STATUS_LABELS_FA as $code => $label): ?>
                <a href="?status=<?= m360_intake_contract_h($code) ?>"><?= m360_intake_contract_h($label) ?></a>
            <?php endforeach; ?>
            <a href="?<?= m360_rui_h(m360_rui_query_keep(['sort'=>'id','dir'=>$sort==='id'&&$dir==='desc'?'asc':'desc','page'=>1])) ?>">مرتب‌سازی شناسه</a>
            <a href="?<?= m360_rui_h(m360_rui_query_keep(['sort'=>'date','dir'=>$sort==='date'&&$dir==='desc'?'asc':'desc','page'=>1])) ?>">مرتب‌سازی تاریخ</a>
        </nav>
        <?php if ($contractRows === []): ?>
            <p style="color:#9ca3af;">قراردادی یافت نشد.</p>
        <?php else: ?>
            <div class="m360-ct-table-wrap">
            <table class="m360-ct-table">
                <thead>
                <tr>
                    <th>شناسه</th>
                    <th>پرونده کار</th>
                    <th>موبایل</th>
                    <th>وضعیت</th>
                    <th>ارسال</th>
                    <th>امضا</th>
                    <th>اقدام</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($contractRows as $c): ?>
                    <tr>
                        <td><?= m360_intake_contract_h((string)$c['contract_id']) ?></td>
                        <td><?= m360_intake_contract_h((string)($c['jobcard_id'] ?: '-')) ?></td>
                        <td><?= m360_intake_contract_h((string)$c['mobile']) ?></td>
                        <td><?= m360_intake_contract_h(M360_CONTRACT_STATUS_LABELS_FA[$c['contract_status']] ?? m360_rui_label((string)$c['contract_status'])) ?></td>
                        <td><?= m360_rui_h(m360_rui_jalali_date((string)$c['sent_at'])) ?></td>
                        <td><?= m360_rui_h(m360_rui_jalali_date((string)$c['signed_at'])) ?></td>
                        <td><a class="m360-rc-btn" href="erp-intake-contract-detail.php?contract_id=<?= (int)$c['contract_id'] ?>">ورود</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php m360_rui_render_pagination($pageInfo, m360_rui_query_keep([], ['page'])); ?>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
