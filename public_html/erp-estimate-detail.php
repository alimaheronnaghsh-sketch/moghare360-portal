<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-technical-operation-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';

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

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات برآورد</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-estimate.css">
    <link rel="stylesheet" href="<?= m360_operational_shell_h(m360_operational_shell_css_href()) ?>">
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
    <?php if ($flash !== ''): ?><div class="m360-est-flash <?= $flashOk ? 'ok' : 'err' ?>"><?= m360_estimate_h($flash) ?></div><?php endif; ?>

    <?php if ($jc === null): ?>
        <section class="w1c-card"><p>کارت کار یافت نشد.</p></section>
    <?php else: ?>
        <section class="w1c-card">
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
                <?= erp_csrf_input(M360_ESTIMATE_CSRF) ?>
                <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                <input type="hidden" name="action" value="create_draft">
                <button type="submit" class="m360-est-btn">ایجاد پیش‌نویس</button>
            </form>
            <?php if ($estimateId > 0): ?>
            <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form">
                <?= erp_csrf_input(M360_ESTIMATE_CSRF) ?>
                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                <input type="hidden" name="action" value="calculate_totals">
                <button type="submit" class="m360-est-btn secondary">محاسبه جمع</button>
            </form>
            <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form">
                <?= erp_csrf_input(M360_ESTIMATE_CSRF) ?>
                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                <input type="hidden" name="action" value="internal_review">
                <button type="submit" class="m360-est-btn secondary">بازبینی داخلی</button>
            </form>
            <?php if ($canSend): ?>
            <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form">
                <?= erp_csrf_input(M360_ESTIMATE_CSRF) ?>
                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                <input type="hidden" name="action" value="send_to_customer">
                <button type="submit" class="m360-est-btn">ارسال به مشتری</button>
            </form>
            <?php endif; ?>
            <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form">
                <?= erp_csrf_input(M360_ESTIMATE_CSRF) ?>
                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                <input type="hidden" name="action" value="clear_parts_gate">
                <button type="submit" class="m360-est-btn secondary">باز کردن گیت قطعه</button>
            </form>
            <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form">
                <?= erp_csrf_input(M360_ESTIMATE_CSRF) ?>
                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                <input type="hidden" name="action" value="clear_finance_gate">
                <button type="submit" class="m360-est-btn secondary">باز کردن گیت مالی</button>
            </form>
            <form method="post" action="erp-estimate-action.php" class="m360-est-inline-form">
                <?= erp_csrf_input(M360_ESTIMATE_CSRF) ?>
                <input type="hidden" name="estimate_id" value="<?= $estimateId ?>">
                <input type="hidden" name="action" value="approve_for_work">
                <button type="submit" class="m360-est-btn primary">مجاز برای ادامه کار</button>
            </form>
            <?php endif; ?>
        </section>

        <?php if ($estimateId > 0): ?>
        <section class="w1c-card">
            <h3>افزودن آیتم</h3>
            <form method="post" action="erp-estimate-action.php">
                <?= erp_csrf_input(M360_ESTIMATE_CSRF) ?>
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
                                <?= erp_csrf_input(M360_ESTIMATE_CSRF) ?>
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
