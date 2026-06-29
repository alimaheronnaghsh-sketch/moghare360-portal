<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-qc-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';

m360_qc_require_staff();

$jobcardId = isset($_GET['jobcard_id']) ? (int)$_GET['jobcard_id'] : 0;
$qcCheckId = isset($_GET['qc_check_id']) ? (int)$_GET['qc_check_id'] : 0;
$flash = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$flashOk = isset($_GET['ok']) && $_GET['ok'] === '1';

$conn = customer_core_db();
$jc = null;
$check = null;
$checklist = [];
$serviceOps = [];
$consumedParts = [];
$history = [];
$events = [];
$gatesOk = false;
$gateMessage = '';
$allowed = [];
$qcStatus = '';

if ($conn !== false && $jobcardId > 0) {
    $jc = m360_qc_fetch_jobcard($conn, $jobcardId);
    if ($jc !== null) {
        $gate = m360_qc_assert_gates($conn, $jobcardId, $jc);
        $gatesOk = $gate['ok'];
        $gateMessage = $gate['message'];
        $qcStatus = m360_qc_effective_status($jc);
        $allowed = m360_qc_allowed_actions($jc, $gatesOk);
        $check = $qcCheckId > 0 ? m360_qc_fetch_check($conn, $qcCheckId) : m360_qc_fetch_active_check($conn, $jobcardId);
        if ($check !== null) {
            $qcCheckId = (int)$check['qc_check_id'];
            $checklist = m360_final_inspection_list_items($conn, $qcCheckId);
        }
        $serviceOps = m360_technical_list_service_operations($conn, $jobcardId);
        $consumedParts = m360_parts_list_consumed($conn, $jobcardId);
        $history = m360_qc_list_history($conn, $jobcardId);
        $events = m360_qc_list_events($conn, $jobcardId);
    }
}

function m360_qc_form(string $action, int $jobcardId, int $qcCheckId, string $label, string $class = ''): void
{
    echo '<form method="post" action="erp-qc-action.php" class="m360-qc-inline-form">';
    echo erp_csrf_input(M360_QC_CSRF);
    echo '<input type="hidden" name="jobcard_id" value="' . (int)$jobcardId . '">';
    if ($qcCheckId > 0) {
        echo '<input type="hidden" name="qc_check_id" value="' . (int)$qcCheckId . '">';
    }
    echo '<input type="hidden" name="action" value="' . m360_qc_h($action) . '">';
    echo '<button type="submit" class="m360-qc-btn ' . m360_qc_h($class) . '">' . m360_qc_h($label) . '</button>';
    echo '</form>';
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات QC</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-qc.css">
    <link rel="stylesheet" href="<?= m360_operational_shell_h(m360_operational_shell_css_href()) ?>">
</head>
<body class="m360-qc-page">
<div class="w1c-wrap m360-qc-wrap">
    <?php
    $opsStrip = null;
    if ($jc !== null) {
        $opsStrip = m360_operational_shell_build_jobcard_strip($conn, $jc, 'qc', $qcStatus, $allowed, $gateMessage);
        $opsStrip['doc_type_fa'] = 'سند QC';
    }
    m360_operational_shell_render_detail('qc_detail', 'erp-qc-board.php', $jobcardId, $opsStrip);
    ?>
    <?php if ($flash !== ''): ?><div class="m360-qc-flash <?= $flashOk ? 'ok' : 'err' ?>"><?= m360_qc_h($flash) ?></div><?php endif; ?>

    <?php if ($jc === null): ?>
        <section class="w1c-card"><p>کارت کار یافت نشد.</p></section>
    <?php else: ?>
        <section class="w1c-card">
            <h2>QC — کارت کار <?= m360_qc_h((string)$jobcardId) ?></h2>
            <div class="m360-qc-grid">
                <div>
                    <p class="m360-qc-kv"><strong>مشتری:</strong> <?= m360_qc_h((string)($jc['customer_name'] ?? '-')) ?></p>
                    <p class="m360-qc-kv"><strong>موبایل:</strong> <?= m360_qc_h((string)($jc['customer_mobile'] ?? '-')) ?></p>
                    <p class="m360-qc-kv"><strong>خودرو:</strong> <?= m360_qc_h(trim((string)($jc['brand'] ?? '') . ' ' . (string)($jc['model'] ?? '')) ?: '-') ?></p>
                    <p class="m360-qc-kv"><strong>پلاک:</strong> <?= m360_qc_h((string)($jc['plate_number'] ?? '-')) ?></p>
                </div>
                <div>
                    <p class="m360-qc-kv"><strong>وضعیت QC:</strong> <span class="m360-qc-badge"><?= m360_qc_h(m360_qc_status_label($qcStatus)) ?></span></p>
                    <p class="m360-qc-kv"><strong>اجرای کار:</strong> <?= m360_qc_h(m360_work_status_label(m360_work_effective_status($jc))) ?></p>
                    <p class="m360-qc-kv"><strong>برآورد:</strong> <?= m360_qc_h(m360_estimate_status_label((string)($jc['estimate_status'] ?? ''))) ?></p>
                    <p class="m360-qc-kv"><strong>آمادگی تحویل:</strong> <?= m360_qc_h((string)($jc['delivery_readiness_status'] ?? '-')) ?></p>
                </div>
            </div>
            <p class="m360-qc-kv"><strong>عیب‌یابی:</strong> <?= m360_qc_h((string)($jc['diagnosis_summary'] ?? '-')) ?></p>
            <p class="m360-qc-kv"><strong>یادداشت تکمیل فنی (P5):</strong> <?= m360_qc_h((string)($jc['technical_completion_notes'] ?? '-')) ?></p>
            <?php if (!$gatesOk): ?>
                <div class="m360-qc-alert"><?= m360_qc_h($gateMessage !== '' ? $gateMessage : 'گیت‌های P5/P4/P1.5 باز نشده‌اند.') ?></div>
            <?php endif; ?>
        </section>

        <section class="w1c-card m360-qc-actions">
            <h3>عملیات QC</h3>
            <?php if (in_array('start_qc', $allowed, true)): m360_qc_form('start_qc', $jobcardId, $qcCheckId, 'شروع QC'); endif; ?>
            <?php if (in_array('qc_passed', $allowed, true)): m360_qc_form('qc_passed', $jobcardId, $qcCheckId, 'QC Pass', 'pass'); endif; ?>
            <?php if (in_array('delivery_ready', $allowed, true)): m360_qc_form('delivery_ready', $jobcardId, $qcCheckId, 'آماده تحویل', 'pass'); endif; ?>
            <?php if (in_array('rework_completed', $allowed, true)): m360_qc_form('rework_completed', $jobcardId, $qcCheckId, 'Rework تکمیل', 'secondary'); endif; ?>
            <?php if (in_array('hold', $allowed, true)): m360_qc_form('hold', $jobcardId, $qcCheckId, 'معلق', 'secondary'); endif; ?>
            <?php if (in_array('cancel', $allowed, true)): m360_qc_form('cancel', $jobcardId, $qcCheckId, 'لغو QC', 'danger'); endif; ?>
        </section>

        <section class="w1c-card">
            <h3>یادداشت بازبینی نهایی</h3>
            <form method="post" action="erp-qc-action.php">
                <?= erp_csrf_input(M360_QC_CSRF) ?>
                <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                <input type="hidden" name="qc_check_id" value="<?= $qcCheckId ?>">
                <input type="hidden" name="action" value="save_final_inspection_notes">
                <textarea name="final_inspection_notes" class="m360-qc-textarea" placeholder="نتیجه بازبینی نهایی QC"><?= m360_qc_h((string)($jc['final_inspection_notes'] ?? '')) ?></textarea>
                <button type="submit" class="m360-qc-btn secondary" style="margin-top:0.5rem;">ذخیره یادداشت</button>
            </form>
        </section>

        <section class="w1c-card">
            <h3>رد QC / Rework</h3>
            <form method="post" action="erp-qc-action.php">
                <?= erp_csrf_input(M360_QC_CSRF) ?>
                <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                <input type="hidden" name="qc_check_id" value="<?= $qcCheckId ?>">
                <input type="hidden" name="action" value="rework_required">
                <textarea name="failure_reason" class="m360-qc-textarea" placeholder="دلیل رد QC" required><?= m360_qc_h((string)($jc['qc_failure_reason'] ?? '')) ?></textarea>
                <button type="submit" class="m360-qc-btn danger" style="margin-top:0.5rem;">ثبت Rework</button>
            </form>
        </section>

        <section class="w1c-card">
            <h3>چک‌لیست بازبینی نهایی</h3>
            <?php if ($checklist === []): ?>
                <p class="m360-qc-kv">ابتدا QC را شروع کنید.</p>
            <?php else: ?>
                <table class="m360-qc-table">
                    <thead><tr><th>آیتم</th><th>نتیجه</th><th>یادداشت</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($checklist as $item):
                        $res = strtoupper((string)($item['item_result'] ?? ''));
                        $resClass = $res === 'FAIL' ? 'm360-qc-item-fail' : ($res === 'PASS' ? 'm360-qc-item-pass' : '');
                    ?>
                        <tr>
                            <td><?= m360_qc_h((string)($item['item_title'] ?? '')) ?></td>
                            <td class="<?= $resClass ?>"><?= m360_qc_h(m360_final_inspection_result_label($res)) ?></td>
                            <td><?= m360_qc_h((string)($item['item_note'] ?? '')) ?></td>
                            <td>
                                <form method="post" action="erp-qc-action.php" class="m360-qc-inline-form">
                                    <?= erp_csrf_input(M360_QC_CSRF) ?>
                                    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                                    <input type="hidden" name="qc_check_id" value="<?= $qcCheckId ?>">
                                    <input type="hidden" name="action" value="save_checklist_item">
                                    <input type="hidden" name="qc_item_id" value="<?= (int)($item['qc_item_id'] ?? 0) ?>">
                                    <select name="item_result" class="m360-qc-select" style="width:auto;display:inline-block;">
                                        <option value="PASS">قبول</option>
                                        <option value="FAIL">رد</option>
                                        <option value="NOT_APPLICABLE">نامربوط</option>
                                    </select>
                                    <input type="text" name="item_note" class="m360-qc-input" placeholder="یادداشت" style="max-width:120px;display:inline-block;">
                                    <button type="submit" class="m360-qc-btn secondary">ذخیره</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section class="w1c-card">
            <h3>عملیات سرویس (تکمیل‌شده)</h3>
            <?php if ($serviceOps === []): ?>
                <p class="m360-qc-kv">—</p>
            <?php else: ?>
                <table class="m360-qc-table">
                    <thead><tr><th>عنوان</th><th>وضعیت</th></tr></thead>
                    <tbody>
                    <?php foreach ($serviceOps as $so): ?>
                        <tr>
                            <td><?= m360_qc_h((string)($so['operation_title'] ?? $so['service_title'] ?? '-')) ?></td>
                            <td><?= m360_qc_h((string)($so['service_status'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <?php if ($consumedParts !== []): ?>
        <section class="w1c-card">
            <h3>قطعات مصرف‌شده</h3>
            <table class="m360-qc-table">
                <thead><tr><th>قطعه</th><th>تعداد</th></tr></thead>
                <tbody>
                <?php foreach ($consumedParts as $c): ?>
                    <tr><td><?= m360_qc_h((string)($c['part_id'] ?? '')) ?></td><td><?= m360_qc_h((string)($c['quantity'] ?? '')) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php endif; ?>

        <section class="w1c-card">
            <h3>مستندات QC (دوربین کنترل‌شده)</h3>
            <p class="m360-qc-kv">آپلود آزاد مجاز نیست — فقط ثبت دوربین مستقیم.</p>
            <a class="m360-qc-btn secondary" href="erp-jobcard-camera-capture.php">ثبت تصویر دوربین</a>
        </section>

        <section class="w1c-card">
            <h3>تاریخچه / رویدادها</h3>
            <table class="m360-qc-table">
                <thead><tr><th>نوع</th><th>قبل</th><th>بعد</th><th>خلاصه</th></tr></thead>
                <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?= m360_qc_h((string)($h['change_type'] ?? '')) ?></td>
                        <td><?= m360_qc_h((string)($h['previous_status'] ?? '')) ?></td>
                        <td><?= m360_qc_h((string)($h['new_status'] ?? '')) ?></td>
                        <td><?= m360_qc_h((string)($h['change_summary'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php foreach ($events as $e): ?>
                    <tr>
                        <td><?= m360_qc_h((string)($e['event_name'] ?? '')) ?></td>
                        <td colspan="3"><?= m360_qc_h((string)($e['event_note'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
