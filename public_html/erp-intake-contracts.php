<?php
declare(strict_types=1);

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
    <title>قراردادهای پذیرش</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="<?= m360_operational_shell_h(m360_operational_shell_css_href()) ?>">
    <link rel="stylesheet" href="assets/css/m360-contract.css">
</head>
<body style="background:#f8fafc;margin:0;padding:1.25rem;">
<div class="w1c-wrap m360-contract-page">
    <p style="margin:0 0 .75rem"><a href="erp-reception-board.php" style="color:#166534;text-decoration:none;font-weight:600">← بازگشت به مرکز ارتباط با مشتریان</a></p>
    <?php m360_operational_shell_render_board('intake_contracts'); ?>
    <header class="w1c-banner">
        <h1>قراردادهای پذیرش</h1>
        <p>تولید، ارسال و پیگیری امضای مشتری</p>
    </header>
    <section class="w1c-card">
        <nav class="m360-contract-actions">
            <?php foreach (['ALL' => 'همه'] + M360_CONTRACT_STATUS_LABELS_FA as $code => $label): ?>
                <a class="m360-contract-btn secondary" href="?status=<?= m360_intake_contract_h($code) ?>"><?= m360_intake_contract_h($label) ?></a>
            <?php endforeach; ?>
            <a class="m360-contract-btn secondary" href="?<?= m360_rui_h(m360_rui_query_keep(['sort'=>'id','dir'=>$sort==='id'&&$dir==='desc'?'asc':'desc','page'=>1])) ?>">مرتب‌سازی شناسه</a>
            <a class="m360-contract-btn secondary" href="?<?= m360_rui_h(m360_rui_query_keep(['sort'=>'date','dir'=>$sort==='date'&&$dir==='desc'?'asc':'desc','page'=>1])) ?>">مرتب‌سازی تاریخ</a>
        </nav>
        <?php if ($contractRows === []): ?>
            <p>قراردادی یافت نشد.</p>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;font-size:0.92rem;table-layout:fixed">
                <thead>
                <tr style="background:#fafafa;">
                    <th style="padding:0.5rem;text-align:right;">شناسه</th>
                    <th style="padding:0.5rem;text-align:right;">کارت کار</th>
                    <th style="padding:0.5rem;text-align:right;">موبایل</th>
                    <th style="padding:0.5rem;text-align:right;">وضعیت</th>
                    <th style="padding:0.5rem;text-align:right;">ارسال</th>
                    <th style="padding:0.5rem;text-align:right;">امضا</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($contractRows as $c): ?>
                    <tr>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= m360_intake_contract_h((string)$c['contract_id']) ?></td>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= m360_intake_contract_h((string)($c['jobcard_id'] ?: '-')) ?></td>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= m360_intake_contract_h((string)$c['mobile']) ?></td>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= m360_intake_contract_h(M360_CONTRACT_STATUS_LABELS_FA[$c['contract_status']] ?? m360_rui_label((string)$c['contract_status'])) ?></td>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= m360_rui_h(m360_rui_jalali_date((string)$c['sent_at'])) ?></td>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= m360_rui_h(m360_rui_jalali_date((string)$c['signed_at'])) ?></td>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;"><a class="m360-contract-btn primary" href="erp-intake-contract-detail.php?contract_id=<?= (int)$c['contract_id'] ?>">ورود</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php m360_rui_render_pagination($pageInfo, m360_rui_query_keep([], ['page'])); ?>
        <?php endif; ?>
    </section>
    <nav class="w1c-card w1c-links">
        <a href="erp-reception-board.php">مرکز ارتباط با مشتریان</a>
        <a href="erp-reception-online-requests.php">درخواست‌های آنلاین</a>
    </nav>
</div>
</body>
</html>
