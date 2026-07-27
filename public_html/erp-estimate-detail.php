<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-technical-operation-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-case-stage-tree-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-case-stage-header.php';

m360_estimate_require_staff();

$jobcardId = isset($_GET['jobcard_id']) ? (int)$_GET['jobcard_id'] : 0;
$estimateId = isset($_GET['estimate_id']) ? (int)$_GET['estimate_id'] : 0;
$flash = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$flashOk = isset($_GET['ok']) && $_GET['ok'] === '1';

$conn = customer_core_db();
$jc = null; $est = null; $items = []; $events = []; $serviceOps = [];

if ($conn !== false) {
    if ($estimateId > 0) {
        $est = m360_estimate_fetch($conn, $estimateId);
        $jobcardId = (int)($est['jobcard_id'] ?? $jobcardId);
    } elseif ($jobcardId > 0) {
        $est = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
        if ($est !== null) {
            $estimateId = (int)$est['estimate_id'];
        }
    }
    if ($jobcardId > 0) {
        $jc = m360_estimate_fetch_jobcard($conn, $jobcardId);
        $serviceOps = m360_technical_list_service_operations($conn, $jobcardId);
    }
    if ($estimateId > 0) {
        $items = m360_estimate_list_items($conn, $estimateId);
        $events = m360_estimate_list_events($conn, $estimateId);
    }
}

$canSend = $est !== null && $items !== [] && in_array(strtoupper((string)($est['estimate_status'] ?? '')), [M360_EST_STATUS_DRAFT, M360_EST_STATUS_INTERNAL_REVIEW, M360_EST_STATUS_REVISION], true);
$activeCustomerTask = null;
if ($conn !== false && $estimateId > 0 && $est !== null && $jc !== null) {
    $activeCustomerTask = m360_estimate_find_active_customer_task(
        $conn,
        $estimateId,
        (int)($est['customer_id'] ?? $jc['customer_id'] ?? 0),
        $jobcardId
    );
}
$canSendToCustomer = $canSend || $activeCustomerTask !== null;
$approveForWorkGate = $est !== null ? m360_gates_can_approve_for_work($conn, $est) : ['ok' => false];
$canApproveForWork = !empty($approveForWorkGate['ok']);
$m360StageTree = m360_case_stage_tree_resolve($conn, [
    'jobcard_id' => $jobcardId,
    'estimate_id' => $estimateId,
]);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات برآورد</title>
    <!-- ESTIMATE_DETAIL_VISUAL_RECONCILE_20260721_1755 -->
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css?v=<?= (int)@filemtime(__DIR__ . '/assets/moghare360-ui/moghare360-soft-run-release.css') ?>">
    <link rel="stylesheet" href="assets/css/m360-estimate.css?v=<?= (int)@filemtime(__DIR__ . '/assets/css/m360-estimate.css') ?>">
    <link rel="stylesheet" href="<?= m360_operational_shell_h(m360_operational_shell_css_href()) ?>?v=<?= (int)@filemtime(__DIR__ . '/assets/css/m360-operational-shell.css') ?>">
    <style>
        /* Page-scoped Luxury Dark Green — estimate detail only */
        .m360-est-page {
            --m360-est-bg: #071a15;
            --m360-est-surface: #0c2b22;
            --m360-est-card: #12352b;
            --m360-est-border: rgba(212, 175, 55, 0.28);
            --m360-est-text: #f7fff9;
            --m360-est-muted: #b7d4c6;
            --m360-est-accent: #2dd4a0;
            --m360-est-gold: #f0d27a;
            background: radial-gradient(ellipse at top, #0f2f26 0%, var(--m360-est-bg) 55%);
            color: var(--m360-est-text);
            min-height: 100vh;
            margin: 0;
            padding: 1.25rem;
            font-family: Tahoma, "Segoe UI", Arial, sans-serif;
        }
        .m360-est-page .m360-est-wrap {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Top nav: neutralize slate/blue shell */
        .m360-est-page .m360-ops-topnav {
            background: linear-gradient(135deg, #0a241c, #134033);
            border: 1px solid var(--m360-est-border);
            color: var(--m360-est-text);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.28);
        }
        .m360-est-page .m360-ops-nav-link {
            background: #1a5c48;
            color: #f7fff9;
            border: 1px solid rgba(45, 212, 160, 0.28);
        }
        .m360-est-page .m360-ops-nav-link:hover {
            background: #217a5f;
            color: #fff;
        }
        .m360-est-page .m360-ops-nav-back {
            background: #173a30;
        }
        .m360-est-page .m360-ops-nav-back:hover {
            background: #1f4d3f;
        }
        .m360-est-page .m360-ops-nav-secondary {
            background: #0f766e;
        }
        .m360-est-page .m360-ops-topnav-kicker {
            color: var(--m360-est-gold);
            opacity: 1;
        }
        .m360-est-page .m360-ops-topnav-heading {
            color: #fff8df;
        }
        .m360-est-page .m360-ops-breadcrumb {
            background: rgba(18, 53, 43, 0.9);
            color: var(--m360-est-muted);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .m360-est-page .m360-ops-breadcrumb a {
            color: var(--m360-est-gold);
        }
        .m360-est-page .m360-ops-breadcrumb-item:not(:last-child)::after {
            color: rgba(183, 212, 198, 0.55);
        }

        /* Responsibility strip: dark green glass, not white/blue */
        .m360-est-page .m360-ops-strip {
            background: linear-gradient(135deg, rgba(12, 43, 34, 0.96), rgba(18, 53, 43, 0.94));
            border: 1px solid var(--m360-est-border);
            border-right: 4px solid var(--m360-est-accent);
            color: var(--m360-est-text);
            box-shadow: 0 14px 34px rgba(0, 0, 0, 0.24);
        }
        .m360-est-page .m360-ops-strip-head {
            border-bottom: 1px dashed rgba(255, 255, 255, 0.12);
        }
        .m360-est-page .m360-ops-strip-title {
            color: #fff8df;
        }
        .m360-est-page .m360-ops-strip-record,
        .m360-est-page .m360-ops-strip-lbl {
            color: var(--m360-est-muted);
        }
        .m360-est-page .m360-ops-strip-val {
            color: #ffffff;
            font-weight: 700;
        }
        .m360-est-page .m360-ops-strip-status {
            background: rgba(45, 212, 160, 0.18);
            color: #d8ffe9;
            border: 1px solid rgba(45, 212, 160, 0.35);
        }
        .m360-est-page .m360-ops-strip-next {
            background: rgba(240, 210, 122, 0.12);
            border: 1px solid rgba(240, 210, 122, 0.35);
            color: #fff2c8;
        }
        .m360-est-page .m360-ops-strip-links a {
            color: var(--m360-est-gold);
        }

        /* All cards unified dark green glass */
        .m360-est-page .w1c-card,
        .m360-est-page .m360-est-doc-card,
        .m360-est-page .m360-est-actions {
            background: linear-gradient(145deg, rgba(14, 48, 39, 0.97), rgba(10, 36, 29, 0.98));
            border: 1px solid var(--m360-est-border);
            border-radius: 14px;
            color: var(--m360-est-text);
            box-shadow: 0 16px 38px rgba(0, 0, 0, 0.26);
        }
        .m360-est-page .w1c-card h2,
        .m360-est-page .w1c-card h3,
        .m360-est-page .m360-est-doc-card h2 {
            color: #fff8df;
            margin-top: 0;
        }
        .m360-est-page .w1c-card p,
        .m360-est-page .w1c-card li,
        .m360-est-page .w1c-card label,
        .m360-est-page .m360-est-doc-card p {
            color: var(--m360-est-text);
        }
        .m360-est-page .m360-est-muted {
            color: var(--m360-est-muted);
        }
        .m360-est-page .m360-est-meta-label {
            color: var(--m360-est-muted);
            font-weight: 700;
        }
        .m360-est-page .m360-est-meta-value {
            color: #ffffff;
            font-weight: 800;
        }

        /* Forms / tables on dark surface */
        .m360-est-page .m360-est-input,
        .m360-est-page select.m360-est-input,
        .m360-est-page textarea.m360-est-input {
            background: #0a1f19;
            color: #f7fff9;
            border: 1px solid rgba(183, 212, 198, 0.28);
        }
        .m360-est-page .m360-est-table {
            color: var(--m360-est-text);
        }
        .m360-est-page .m360-est-table th {
            background: rgba(7, 32, 26, 0.95);
            color: var(--m360-est-gold);
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        .m360-est-page .m360-est-table td {
            border-bottom-color: rgba(255, 255, 255, 0.08);
            color: var(--m360-est-text);
        }

        /* Buttons */
        .m360-est-page .m360-est-btn {
            color: #f7fff9;
            border: 1px solid rgba(45, 212, 160, 0.35);
            background: #1a6b54;
            cursor: pointer;
            pointer-events: auto;
            position: relative;
            z-index: 2;
        }
        .m360-est-page .m360-est-btn.secondary {
            background: #18483b;
            color: #e8fff4;
            border-color: rgba(183, 212, 198, 0.28);
        }
        .m360-est-page .m360-est-btn.primary,
        .m360-est-page .m360-est-btn.m360-est-primary-action,
        .m360-est-page .m360-est-inline-form[data-est-action="send_to_customer"] .m360-est-btn {
            color: #102018;
            background: linear-gradient(135deg, #ffe18f, #67e2a4);
            border-color: rgba(255, 245, 190, 0.72);
            font-weight: 900;
            min-width: 170px;
        }
        .m360-est-page .m360-est-btn.danger,
        .m360-est-page .m360-est-btn.danger-sm {
            background: #9f1d1d;
            border-color: rgba(254, 202, 202, 0.35);
            color: #fff5f5;
        }
        .m360-est-page .m360-est-inline-form {
            display: inline-block;
            margin: 0.25rem;
            position: relative;
            z-index: 2;
        }
        .m360-est-page .m360-est-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            align-items: center;
        }

        /* Flash / result */
        .m360-est-page .m360-est-flash {
            font-weight: 800;
            font-size: 1rem;
            line-height: 1.7;
            padding: 1rem 1.1rem;
            margin: 0;
            border-radius: 12px;
            border: 1px solid transparent;
            position: relative;
            z-index: 5;
        }
        .m360-est-page .m360-est-flash.ok {
            background: rgba(22, 101, 52, 0.92);
            border-color: rgba(134, 239, 172, 0.55);
            color: #ecfdf5;
        }
        .m360-est-page .m360-est-flash.err {
            background: rgba(127, 29, 29, 0.92);
            border-color: rgba(254, 202, 202, 0.5);
            color: #fff1f2;
        }

        /* Case Stage Tree already dark green — keep readable, no redesign */
        .m360-est-page .m360-case-stage-header {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body class="m360-est-page">
<div class="w1c-wrap m360-est-wrap">
    <?php
    $opsStrip = null;
    if ($jc !== null) {
        $estStatus = $est !== null ? strtoupper((string)($est['estimate_status'] ?? '')) : '';
        $opsStrip = m360_operational_shell_build_jobcard_strip($conn, $jc, 'estimate', $estStatus, [], '');
        $opsStrip['doc_type_fa'] = 'سند برآورد';
        if ($estimateId > 0) {
            $opsStrip['record_label_fa'] = 'JobCard: ' . $jobcardId . ' — برآورد: ' . $estimateId;
        }
    }
    m360_operational_shell_render_detail('estimate_detail', 'erp-estimate-board.php', $jobcardId, $opsStrip);
    ?>
    <?= m360_render_case_stage_header($m360StageTree) ?>

    <?php if ($flash !== ''): ?>
        <div class="m360-est-flash <?= $flashOk ? 'ok' : 'err' ?>" role="alert" aria-live="polite"><?= m360_estimate_h($flash) ?></div>
    <?php endif; ?>

    <?php if ($jc === null): ?>
        <section class="w1c-card"><p>کارت کار یافت نشد.</p></section>
    <?php else: ?>
        <section class="w1c-card m360-est-doc-card">
            <h2>کارت کار <?= m360_estimate_h((string)$jobcardId) ?> — <?= m360_estimate_h((string)($jc['customer_name'] ?? '')) ?></h2>
            <p>عیب‌یابی: <?= m360_estimate_h((string)($jc['diagnosis_summary'] ?? '-')) ?></p>
            <?php if ($est !== null): ?>
                <p>وضعیت: <?= m360_estimate_h(m360_estimate_status_label((string)$est['estimate_status'])) ?> |
                   مبلغ کل: <?= m360_estimate_h(number_format((float)$est['total_amount'])) ?> تومان |
                   علی‌الحساب: <?= m360_estimate_h(number_format((float)$est['advance_required_amount'])) ?> |
                   قطعه: <?= m360_estimate_h((string)($est['parts_gate_status'] ?? '-')) ?> |
                   مالی: <?= m360_estimate_h((string)($est['finance_gate_status'] ?? '-')) ?></p>
            <?php endif; ?>
        </section>

        <section class="w1c-card m360-est-actions">
            <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form">
                <?= m360_estimate_csrf_input() ?>
                <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                <input type="hidden" name="action" value="create_draft">
                <button type="submit" class="m360-est-btn">ایجاد پیش‌نویس</button>
            </form>
            <?php if ($estimateId > 0): ?>
            <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form">
                <?= m360_estimate_csrf_input() ?>
                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                <input type="hidden" name="action" value="calculate_totals">
                <button type="submit" class="m360-est-btn secondary">محاسبه جمع</button>
            </form>
            <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form">
                <?= m360_estimate_csrf_input() ?>
                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                <input type="hidden" name="action" value="internal_review">
                <button type="submit" class="m360-est-btn secondary">بازبینی داخلی</button>
            </form>
            <?php if ($canSendToCustomer): ?>
            <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form" data-est-action="send_to_customer">
                <?= m360_estimate_csrf_input() ?>
                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                <input type="hidden" name="action" value="send_to_customer">
                <button type="submit" class="m360-est-btn primary m360-est-primary-action" id="m360-est-send-customer-btn">ارسال به مشتری</button>
            </form>
            <?php endif; ?>
            <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form">
                <?= m360_estimate_csrf_input() ?>
                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                <input type="hidden" name="action" value="clear_parts_gate">
                <button type="submit" class="m360-est-btn secondary">باز کردن گیت قطعه</button>
            </form>
            <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form">
                <?= m360_estimate_csrf_input() ?>
                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                <input type="hidden" name="action" value="clear_finance_gate">
                <button type="submit" class="m360-est-btn secondary">باز کردن گیت مالی</button>
            </form>
            <?php if ($canApproveForWork): ?>
                <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form">
                    <?= m360_estimate_csrf_input() ?>
                    <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                    <input type="hidden" name="action" value="approve_for_work">
                    <button type="submit" class="m360-est-btn primary">مجاز برای ادامه کار</button>
                </form>
            <?php else: ?>
                <div class="m360-est-inline-form m360-est-muted">ادامه کار تا تکمیل تأیید مشتری و گیت‌های مالی/قطعه فعال نمی‌شود.</div>
            <?php endif; ?>
            <?php endif; ?>
        </section>

        <?php if ($estimateId > 0): ?>
        <section class="w1c-card">
            <h3>افزودن آیتم</h3>
            <form method="post" action="erp-estimate-action.php">
                <?= m360_estimate_csrf_input() ?>
                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                <input type="hidden" name="action" value="add_item">
                <label>نوع</label>
                <select name="item_type" class="m360-est-input">
                    <?php foreach (M360_EST_ITEM_TYPES as $t): ?><option value="<?= m360_estimate_h($t) ?>"><?= m360_estimate_h($t) ?></option><?php endforeach; ?>
                </select>
                <label>عنوان</label><input class="m360-est-input" name="item_title" required>
                <label>تعداد</label><input class="m360-est-input" name="quantity" type="number" step="0.01" value="1">
                <label>قیمت واحد (تومان)</label><input class="m360-est-input" name="unit_price" type="number" step="1000" value="0">
                <label>توضیح</label><textarea class="m360-est-input" name="item_description"></textarea>
                <button type="submit" class="m360-est-btn">افزودن</button>
            </form>
        </section>

        <section class="w1c-card">
            <h3>آیتم‌های برآورد</h3>
            <table class="m360-est-table">
                <thead><tr><th>نوع</th><th>عنوان</th><th>تعداد</th><th>قیمت</th><th>جمع</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= m360_estimate_h((string)$it['item_type']) ?></td>
                        <td><?= m360_estimate_h((string)$it['item_title']) ?></td>
                        <td><?= m360_estimate_h((string)$it['quantity']) ?></td>
                        <td><?= m360_estimate_h(number_format((float)$it['unit_price'])) ?></td>
                        <td><?= m360_estimate_h(number_format((float)$it['line_total'])) ?></td>
                        <td>
                            <?php if (in_array(strtoupper((string)($est['estimate_status'] ?? '')), [M360_EST_STATUS_DRAFT, M360_EST_STATUS_INTERNAL_REVIEW], true)): ?>
                            <form method="post" action="erp-estimate-action.php" style="display:inline">
                                <?= m360_estimate_csrf_input() ?>
                                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                                <input type="hidden" name="action" value="remove_draft_item">
                                <input type="hidden" name="estimate_item_id" value="<?= (int)$it['estimate_item_id'] ?>">
                                <button type="submit" class="m360-est-btn danger-sm">حذف</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="w1c-card">
            <h3>عملیات سرویس (P3)</h3>
            <ul><?php foreach ($serviceOps as $so): ?><li><?= m360_estimate_h((string)$so['service_title']) ?> — <?= m360_estimate_h((string)$so['service_status']) ?></li><?php endforeach; ?></ul>
        </section>

        <section class="w1c-card">
            <h3>تاریخچه</h3>
            <ul><?php foreach ($events as $ev): ?><li><?= m360_estimate_h(substr((string)$ev['created_at'], 0, 19)) ?> — <?= m360_estimate_h((string)$ev['event_name']) ?></li><?php endforeach; ?></ul>
        </section>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
