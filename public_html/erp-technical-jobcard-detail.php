<?php
declare(strict_types=1);

/**
 * MOGHARE360 P3 — Technical JobCard detail (GET display + POST forms).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-technical-operation-helper.php';

m360_technical_require_staff();

$jobcardId = isset($_GET['jobcard_id']) ? (int)$_GET['jobcard_id'] : 0;
$flashMsg = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$flashOk = isset($_GET['ok']) && (string)$_GET['ok'] === '1';
$p15Missing = !m360_technical_p15_gate_available();

$conn = customer_core_db();
$jobcard = null;
$history = [];
$serviceOps = [];
$serviceHistory = [];
$gatesOk = false;
$gateMessage = '';

if ($conn !== false && $jobcardId > 0) {
    $jobcard = m360_technical_fetch_jobcard($conn, $jobcardId);
    if ($jobcard !== null) {
        $gate = m360_technical_assert_gates($conn, $jobcardId, $jobcard);
        $gatesOk = $gate['ok'];
        $gateMessage = $gate['message'];
        $history = m360_technical_jobcard_history($conn, $jobcardId);
        $serviceOps = m360_technical_list_service_operations($conn, $jobcardId);
        $serviceHistory = m360_technical_service_operation_history($conn, $jobcardId);
    }
}

$allowed = ($jobcard !== null && $gatesOk) ? m360_technician_workflow_allowed_actions($jobcard, true) : [];
$effective = $jobcard !== null ? m360_technician_workflow_effective_status($jobcard) : '';

function p3_tech_form(string $action, int $jobcardId, string $label, string $class = 'primary'): void
{
    echo '<form method="post" action="erp-technical-jobcard-action.php" style="display:inline-block;margin:0.2rem;">';
    echo erp_csrf_input(M360_TECHNICAL_CSRF_PURPOSE);
    echo '<input type="hidden" name="jobcard_id" value="' . (int)$jobcardId . '">';
    echo '<input type="hidden" name="action" value="' . m360_technical_h($action) . '">';
    echo '<button type="submit" class="p3-tech-btn ' . m360_technical_h($class) . '">' . m360_technical_h($label) . '</button>';
    echo '</form>';
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات فنی — کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <style>
        .p3-detail { max-width: 1000px; margin: 0 auto; }
        .p3-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 720px) { .p3-grid { grid-template-columns: 1fr; } }
        .p3-kv { margin: 0.3rem 0; font-size: 0.9rem; }
        .p3-kv strong { color: #475569; }
        .p3-flash { padding: 0.7rem 1rem; border-radius: 0.45rem; margin-bottom: 1rem; }
        .p3-flash.ok { background: #dcfce7; color: #166534; }
        .p3-flash.err { background: #fee2e2; color: #991b1b; }
        .p3-alert { padding: 0.8rem; border-radius: 0.45rem; background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; margin: 0.6rem 0; }
        .p3-tech-btn { padding: 0.42rem 0.85rem; border: none; border-radius: 0.4rem; cursor: pointer; font-size: 0.86rem; }
        .p3-tech-btn.primary { background: #1e3a8a; color: #fff; }
        .p3-tech-btn.secondary { background: #e2e8f0; color: #1e293b; }
        .p3-tech-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .p3-textarea, .p3-input { width: 100%; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 0.4rem; font-family: inherit; }
        .p3-textarea { min-height: 80px; }
        .p3-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
        .p3-table th, .p3-table td { padding: 0.4rem; border-bottom: 1px solid #e2e8f0; text-align: right; }
        .p3-back { display: inline-block; margin-bottom: 1rem; color: #1e3a8a; text-decoration: none; }
    </style>
</head>
<body style="background:#f8fafc;margin:0;padding:1.25rem;color:#0f172a;">
<div class="w1c-wrap p3-detail">
    <a class="p3-back" href="erp-technical-board.php">← بازگشت به برد فنی</a>

    <header class="w1c-banner">
        <h1>جزئیات فنی کارت کار</h1>
        <p>شناسه <?= m360_technical_h((string)$jobcardId) ?></p>
    </header>

    <?php if ($p15Missing): ?>
        <div class="p3-flash err">P1.5 Gate missing — کنترل قرارداد فعال نیست.</div>
    <?php endif; ?>

    <?php if ($flashMsg !== ''): ?>
        <div class="p3-flash <?= $flashOk ? 'ok' : 'err' ?>"><?= m360_technical_h($flashMsg) ?></div>
    <?php endif; ?>

    <?php if ($jobcard === null): ?>
        <section class="w1c-card"><p>کارت کار یافت نشد.</p></section>
    <?php else: ?>

        <?php if (!$gatesOk): ?>
            <div class="p3-alert"><?= m360_technical_h($gateMessage) ?></div>
        <?php endif; ?>

        <section class="w1c-card">
            <div class="p3-grid">
                <div>
                    <div class="p3-kv"><strong>مشتری:</strong> <?= m360_technical_h((string)($jobcard['customer_name'] ?? '-')) ?></div>
                    <div class="p3-kv"><strong>موبایل:</strong> <?= m360_technical_h((string)($jobcard['customer_mobile'] ?? '-')) ?></div>
                    <div class="p3-kv"><strong>خودرو:</strong> <?= m360_technical_h((string)($jobcard['vehicle_label'] ?? '-')) ?></div>
                    <div class="p3-kv"><strong>پلاک:</strong> <?= m360_technical_h((string)($jobcard['plate_number'] ?? '-')) ?></div>
                </div>
                <div>
                    <div class="p3-kv"><strong>وضعیت پذیرش:</strong> <?= m360_technical_h((string)($jobcard['reception_status_label'] ?? '')) ?></div>
                    <div class="p3-kv"><strong>وضعیت فنی:</strong> <?= m360_technical_h(m360_technician_workflow_status_label($effective)) ?></div>
                    <div class="p3-kv"><strong>قرارداد:</strong> <?= m360_technical_h((string)($jobcard['contract_summary']['label'] ?? '-')) ?></div>
                    <div class="p3-kv"><strong>تکنسین:</strong> <?= m360_technical_h((string)($jobcard['assigned_technician_user_id'] ?? '-') ?: 'اختصاص نیافته') ?></div>
                </div>
            </div>
            <div class="p3-kv" style="margin-top:0.75rem;"><strong>شکایت مشتری:</strong> <?= m360_technical_h((string)($jobcard['customer_complaint'] ?? '-')) ?></div>
            <div class="p3-kv"><strong>یادداشت پذیرش:</strong> <?= m360_technical_h((string)($jobcard['reception_notes'] ?? '-')) ?></div>
            <div class="p3-kv"><strong>بررسی اولیه:</strong> <?= m360_technical_h((string)($jobcard['initial_inspection_notes'] ?? '-')) ?></div>
            <?php if (!empty($jobcard['diagnosis_summary'])): ?>
                <div class="p3-kv"><strong>خلاصه عیب‌یابی:</strong> <?= m360_technical_h((string)$jobcard['diagnosis_summary']) ?></div>
            <?php endif; ?>
        </section>

        <section class="w1c-card">
            <h2 style="margin-top:0;font-size:1rem;">عملیات فنی</h2>
            <?php if ($gatesOk): ?>
                <div>
                    <?php if (in_array('move_to_technical_queue', $allowed, true)) p3_tech_form('move_to_technical_queue', $jobcardId, 'ورود به صف فنی'); ?>
                    <?php if (in_array('assign_technician', $allowed, true)) p3_tech_form('assign_technician', $jobcardId, 'اختصاص تکنسین'); ?>
                    <?php if (in_array('start_diagnosis', $allowed, true)) p3_tech_form('start_diagnosis', $jobcardId, 'شروع عیب‌یابی'); ?>
                    <?php if (in_array('technical_review', $allowed, true)) p3_tech_form('technical_review', $jobcardId, 'بازبینی فنی', 'secondary'); ?>
                    <?php if (in_array('waiting_for_approval', $allowed, true)) p3_tech_form('waiting_for_approval', $jobcardId, 'منتظر تأیید', 'secondary'); ?>
                    <?php if (in_array('technical_done', $allowed, true)) p3_tech_form('technical_done', $jobcardId, 'پایان فنی'); ?>
                    <?php if (in_array('hold', $allowed, true)) p3_tech_form('hold', $jobcardId, 'تعلیق', 'secondary'); ?>
                </div>

                <?php if (in_array('complete_diagnosis', $allowed, true)): ?>
                <form method="post" action="erp-technical-jobcard-action.php" style="margin-top:0.75rem;">
                    <?= erp_csrf_input(M360_TECHNICAL_CSRF_PURPOSE) ?>
                    <input type="hidden" name="jobcard_id" value="<?= (int)$jobcardId ?>">
                    <input type="hidden" name="action" value="complete_diagnosis">
                    <label>خلاصه عیب‌یابی (الزامی)</label>
                    <textarea class="p3-textarea" name="diagnosis_summary" required><?= m360_technical_h((string)($jobcard['diagnosis_summary'] ?? '')) ?></textarea>
                    <div style="margin-top:0.4rem;"><button type="submit" class="p3-tech-btn primary">تکمیل عیب‌یابی</button></div>
                </form>
                <?php endif; ?>

                <?php if (in_array('create_service_operation', $allowed, true)): ?>
                <form method="post" action="erp-technical-jobcard-action.php" style="margin-top:0.75rem;">
                    <?= erp_csrf_input(M360_TECHNICAL_CSRF_PURPOSE) ?>
                    <input type="hidden" name="jobcard_id" value="<?= (int)$jobcardId ?>">
                    <input type="hidden" name="action" value="create_service_operation">
                    <label>عنوان عملیات سرویس</label>
                    <input class="p3-input" type="text" name="operation_title" required maxlength="200">
                    <label style="display:block;margin-top:0.4rem;">توضیحات</label>
                    <textarea class="p3-textarea" name="operation_description"></textarea>
                    <div style="margin-top:0.4rem;"><button type="submit" class="p3-tech-btn primary">ثبت عملیات سرویس</button></div>
                </form>
                <?php endif; ?>
            <?php else: ?>
                <button type="button" class="p3-tech-btn primary" disabled>عملیات فنی غیرفعال</button>
            <?php endif; ?>
        </section>

        <?php if ($gatesOk): ?>
        <section class="w1c-card">
            <h2 style="margin-top:0;font-size:1rem;">یادداشت تکنسین</h2>
            <form method="post" action="erp-technical-jobcard-action.php">
                <?= erp_csrf_input(M360_TECHNICAL_CSRF_PURPOSE) ?>
                <input type="hidden" name="jobcard_id" value="<?= (int)$jobcardId ?>">
                <input type="hidden" name="action" value="save_technician_notes">
                <textarea class="p3-textarea" name="technician_notes"><?= m360_technical_h((string)($jobcard['technician_notes'] ?? '')) ?></textarea>
                <div style="margin-top:0.4rem;"><button type="submit" class="p3-tech-btn primary">ذخیره یادداشت</button></div>
            </form>
        </section>
        <?php endif; ?>

        <section class="w1c-card">
            <h2 style="margin-top:0;font-size:1rem;">عملیات‌های سرویس</h2>
            <?php if ($serviceOps === []): ?>
                <p>عملیات سرویسی ثبت نشده است.</p>
            <?php else: ?>
                <table class="p3-table">
                    <thead><tr><th>شناسه</th><th>عنوان</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr></thead>
                    <tbody>
                    <?php foreach ($serviceOps as $so):
                        $soId = (int)($so['service_operation_id'] ?? 0);
                        $soStatus = (string)($so['service_status'] ?? '');
                    ?>
                        <tr>
                            <td><?= m360_technical_h((string)$soId) ?></td>
                            <td><?= m360_technical_h((string)($so['service_title'] ?? '')) ?></td>
                            <td><?= m360_technical_h($soStatus) ?></td>
                            <td><?= m360_technical_h(substr((string)($so['created_at'] ?? ''), 0, 16)) ?></td>
                            <td>
                                <?php if ($gatesOk && in_array('start_service_operation', $allowed, true) && $soStatus === M360_SO_STATUS_CREATED): ?>
                                <form method="post" action="erp-technical-jobcard-action.php" style="display:inline;">
                                    <?= erp_csrf_input(M360_TECHNICAL_CSRF_PURPOSE) ?>
                                    <input type="hidden" name="jobcard_id" value="<?= (int)$jobcardId ?>">
                                    <input type="hidden" name="action" value="start_service_operation">
                                    <input type="hidden" name="service_operation_id" value="<?= $soId ?>">
                                    <button type="submit" class="p3-tech-btn secondary">شروع</button>
                                </form>
                                <?php endif; ?>
                                <?php if ($gatesOk && in_array('complete_service_operation', $allowed, true) && $soStatus === M360_SO_STATUS_STARTED): ?>
                                <form method="post" action="erp-technical-jobcard-action.php" style="display:inline;">
                                    <?= erp_csrf_input(M360_TECHNICAL_CSRF_PURPOSE) ?>
                                    <input type="hidden" name="jobcard_id" value="<?= (int)$jobcardId ?>">
                                    <input type="hidden" name="action" value="complete_service_operation">
                                    <input type="hidden" name="service_operation_id" value="<?= $soId ?>">
                                    <button type="submit" class="p3-tech-btn primary">تکمیل</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section class="w1c-card">
            <h2 style="margin-top:0;font-size:1rem;">تاریخچه کارت کار</h2>
            <?php if ($history === []): ?>
                <p>تاریخچه‌ای ثبت نشده است.</p>
            <?php else: ?>
                <table class="p3-table">
                    <thead><tr><th>زمان</th><th>رویداد</th><th>از</th><th>به</th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= m360_technical_h(substr((string)($h['changed_at'] ?? ''), 0, 19)) ?></td>
                            <td><?= m360_technical_h((string)($h['change_type'] ?? '')) ?></td>
                            <td><?= m360_technical_h((string)($h['previous_status'] ?? '-')) ?></td>
                            <td><?= m360_technical_h((string)($h['new_status'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <?php if ($serviceHistory !== []): ?>
        <section class="w1c-card">
            <h2 style="margin-top:0;font-size:1rem;">تاریخچه عملیات سرویس</h2>
            <table class="p3-table">
                <thead><tr><th>زمان</th><th>رویداد</th><th>SO</th><th>از</th><th>به</th></tr></thead>
                <tbody>
                <?php foreach ($serviceHistory as $sh): ?>
                    <tr>
                        <td><?= m360_technical_h(substr((string)($sh['changed_at'] ?? ''), 0, 19)) ?></td>
                        <td><?= m360_technical_h((string)($sh['action_code'] ?? '')) ?></td>
                        <td><?= m360_technical_h((string)($sh['service_operation_id'] ?? '')) ?></td>
                        <td><?= m360_technical_h((string)($sh['old_status'] ?? '-')) ?></td>
                        <td><?= m360_technical_h((string)($sh['new_status'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
