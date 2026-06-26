<?php
declare(strict_types=1);

/**
 * MOGHARE360 P2 — Reception JobCard detail (read-only GET + POST forms).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-jobcard-helper.php';

m360_reception_jobcard_require_staff();

$jobcardId = isset($_GET['jobcard_id']) ? (int)$_GET['jobcard_id'] : 0;
$flashMsg = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$flashOk = isset($_GET['ok']) && (string)$_GET['ok'] === '1';
$p15Missing = !m360_reception_jobcard_p15_gate_available();

$conn = customer_core_db();
$jobcard = null;
$history = [];
$contractEvents = [];

if ($conn !== false && $jobcardId > 0) {
    $jobcard = m360_reception_jobcard_fetch($conn, $jobcardId);
    if ($jobcard !== null) {
        $history = m360_reception_jobcard_history($conn, $jobcardId);
        $contractId = (int)($jobcard['contract_summary']['contract_id'] ?? 0);
        if ($contractId > 0) {
            $contractEvents = m360_reception_jobcard_contract_events($conn, $contractId);
        }
    }
}

$allowed = $jobcard !== null ? m360_reception_jobcard_allowed_actions($jobcard) : [];
$canContinue = (bool)($jobcard['contract_summary']['can_continue'] ?? false);
$status = $jobcard !== null ? m360_jobcard_workflow_normalize_status((string)($jobcard['jobcard_status'] ?? '')) : '';

function p2_jc_action_form(string $action, int $jobcardId, string $label, string $btnClass = 'primary'): void
{
    echo '<form method="post" action="erp-reception-jobcard-action.php" style="display:inline-block;margin:0.25rem;">';
    echo erp_csrf_input(M360_RECEPTION_JOBCARD_CSRF_PURPOSE);
    echo '<input type="hidden" name="jobcard_id" value="' . (int)$jobcardId . '">';
    echo '<input type="hidden" name="action" value="' . m360_reception_jobcard_h($action) . '">';
    echo '<button type="submit" class="p2-jc-action-btn ' . m360_reception_jobcard_h($btnClass) . '">' . m360_reception_jobcard_h($label) . '</button>';
    echo '</form>';
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>جزئیات کارت کار — پذیرش</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <style>
        .p2-jc-detail { max-width: 960px; margin: 0 auto; }
        .p2-jc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 720px) { .p2-jc-grid { grid-template-columns: 1fr; } }
        .p2-jc-kv { margin: 0.35rem 0; font-size: 0.92rem; }
        .p2-jc-kv strong { color: #52525b; font-weight: 600; }
        .p2-jc-flash { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; }
        .p2-jc-flash.ok { background: #dcfce7; color: #166534; }
        .p2-jc-flash.err { background: #fee2e2; color: #991b1b; }
        .p2-jc-alert { padding: 0.85rem; border-radius: 0.5rem; background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; margin: 0.75rem 0; }
        .p2-jc-action-btn { padding: 0.45rem 0.9rem; border: none; border-radius: 0.45rem; cursor: pointer; font-size: 0.88rem; }
        .p2-jc-action-btn.primary { background: #166534; color: #fff; }
        .p2-jc-action-btn.secondary { background: #e4e4e7; color: #27272a; }
        .p2-jc-action-btn.danger { background: #b91c1c; color: #fff; }
        .p2-jc-action-btn:disabled { opacity: 0.45; cursor: not-allowed; }
        .p2-jc-textarea { width: 100%; min-height: 90px; padding: 0.6rem; border: 1px solid #d4d4d8; border-radius: 0.45rem; font-family: inherit; }
        .p2-jc-history { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .p2-jc-history th, .p2-jc-history td { padding: 0.45rem; border-bottom: 1px solid #e5e7eb; text-align: right; }
        .p2-jc-back { display: inline-block; margin-bottom: 1rem; color: #166534; text-decoration: none; }
    </style>
</head>
<body style="background:#f8fafc;margin:0;padding:1.25rem;color:#18181b;">
<div class="w1c-wrap p2-jc-detail">
    <a class="p2-jc-back" href="erp-reception-jobcards.php">← بازگشت به لیست</a>

    <header class="w1c-banner">
        <h1>جزئیات کارت کار</h1>
        <p>شناسه <?= m360_reception_jobcard_h((string)$jobcardId) ?></p>
    </header>

    <?php if ($p15Missing): ?>
        <div class="p2-jc-flash err">P1.5 Gate missing — کنترل قرارداد فعال نیست.</div>
    <?php endif; ?>

    <?php if ($flashMsg !== ''): ?>
        <div class="p2-jc-flash <?= $flashOk ? 'ok' : 'err' ?>"><?= m360_reception_jobcard_h($flashMsg) ?></div>
    <?php endif; ?>

    <?php if ($jobcard === null): ?>
        <section class="w1c-card">
            <p>کارت کار یافت نشد.</p>
        </section>
    <?php else: ?>
        <section class="w1c-card">
            <div class="p2-jc-grid">
                <div>
                    <div class="p2-jc-kv"><strong>مشتری:</strong> <?= m360_reception_jobcard_h((string)($jobcard['customer_name'] ?? '-')) ?></div>
                    <div class="p2-jc-kv"><strong>موبایل:</strong> <?= m360_reception_jobcard_h((string)($jobcard['customer_mobile'] ?? '-')) ?></div>
                    <div class="p2-jc-kv"><strong>خودرو:</strong> <?= m360_reception_jobcard_h((string)($jobcard['vehicle_label'] ?? '-')) ?></div>
                    <div class="p2-jc-kv"><strong>پلاک:</strong> <?= m360_reception_jobcard_h((string)($jobcard['plate_number'] ?? '-')) ?></div>
                    <div class="p2-jc-kv"><strong>منبع:</strong> <?= m360_reception_jobcard_h((string)($jobcard['source_label'] ?? '-')) ?></div>
                </div>
                <div>
                    <div class="p2-jc-kv"><strong>وضعیت:</strong> <?= m360_reception_jobcard_h(m360_jobcard_workflow_status_label($status)) ?></div>
                    <div class="p2-jc-kv"><strong>قرارداد:</strong> <?= m360_reception_jobcard_h((string)($jobcard['contract_summary']['label'] ?? '-')) ?></div>
                    <?php if (!empty($jobcard['contract_summary']['contract_id'])): ?>
                        <div class="p2-jc-kv"><strong>لینک قرارداد:</strong>
                            <a href="erp-intake-contract-detail.php?contract_id=<?= (int)$jobcard['contract_summary']['contract_id'] ?>">مشاهده قرارداد</a>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($jobcard['contract_summary']['signed_at'])): ?>
                        <div class="p2-jc-kv"><strong>تاریخ امضا:</strong> <?= m360_reception_jobcard_h(substr((string)$jobcard['contract_summary']['signed_at'], 0, 16)) ?></div>
                    <?php endif; ?>
                    <div class="p2-jc-kv"><strong>رسیدن خودرو:</strong> <?= m360_reception_jobcard_h(substr((string)($jobcard['vehicle_arrival_at'] ?? ''), 0, 16) ?: '-') ?></div>
                    <div class="p2-jc-kv"><strong>ثبت ورود:</strong> <?= m360_reception_jobcard_h(substr((string)($jobcard['checked_in_at'] ?? ''), 0, 16) ?: '-') ?></div>
                </div>
            </div>
        </section>

        <section class="w1c-card">
            <h2 style="margin-top:0;font-size:1.05rem;">عملیات پذیرش</h2>
            <div>
                <?php if (in_array('mark_arrived', $allowed, true)): ?>
                    <?php p2_jc_action_form('mark_arrived', $jobcardId, 'خودرو رسید'); ?>
                <?php endif; ?>
                <?php if (in_array('check_in', $allowed, true)): ?>
                    <?php p2_jc_action_form('check_in', $jobcardId, 'ثبت ورود خودرو'); ?>
                <?php endif; ?>
                <?php if (in_array('hold', $allowed, true)): ?>
                    <?php p2_jc_action_form('hold', $jobcardId, 'تعلیق پرونده', 'secondary'); ?>
                <?php endif; ?>
                <?php if (in_array('cancel', $allowed, true)): ?>
                    <?php p2_jc_action_form('cancel', $jobcardId, 'لغو پرونده', 'danger'); ?>
                <?php endif; ?>
            </div>

            <?php if (in_array('ready_for_technical', $allowed, true) && $canContinue): ?>
                <?php p2_jc_action_form('ready_for_technical', $jobcardId, 'آماده فنی'); ?>
            <?php elseif (!$canContinue && !m360_jobcard_workflow_is_terminal($status)): ?>
                <div class="p2-jc-alert">ادامه عملیات فنی تا زمان امضای قرارداد پذیرش مجاز نیست.</div>
                <button type="button" class="p2-jc-action-btn primary" disabled>آماده فنی</button>
            <?php endif; ?>

            <?php if (in_array('manager_override_contract_gate', $allowed, true) && !$canContinue): ?>
                <form method="post" action="erp-reception-jobcard-action.php" style="margin-top:1rem;">
                    <?= erp_csrf_input(M360_RECEPTION_JOBCARD_CSRF_PURPOSE) ?>
                    <input type="hidden" name="jobcard_id" value="<?= (int)$jobcardId ?>">
                    <input type="hidden" name="action" value="manager_override_contract_gate">
                    <label for="override_reason"><strong>تأیید مدیریتی (دلیل الزامی — حداقل ۱۰ کاراکتر)</strong></label>
                    <textarea class="p2-jc-textarea" id="override_reason" name="override_reason" required minlength="10"></textarea>
                    <div style="margin-top:0.5rem;">
                        <button type="submit" class="p2-jc-action-btn secondary">ثبت تأیید مدیریتی</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <section class="w1c-card">
            <h2 style="margin-top:0;font-size:1.05rem;">شکایت و یادداشت‌ها</h2>
            <form method="post" action="erp-reception-jobcard-action.php" style="margin-bottom:1rem;">
                <?= erp_csrf_input(M360_RECEPTION_JOBCARD_CSRF_PURPOSE) ?>
                <input type="hidden" name="jobcard_id" value="<?= (int)$jobcardId ?>">
                <input type="hidden" name="action" value="save_customer_complaint">
                <label for="customer_complaint">شکایت / توضیح مشتری</label>
                <textarea class="p2-jc-textarea" id="customer_complaint" name="customer_complaint"><?= m360_reception_jobcard_h((string)($jobcard['customer_complaint'] ?? '')) ?></textarea>
                <div style="margin-top:0.5rem;"><button type="submit" class="p2-jc-action-btn primary">ذخیره شکایت</button></div>
            </form>
            <form method="post" action="erp-reception-jobcard-action.php" style="margin-bottom:1rem;">
                <?= erp_csrf_input(M360_RECEPTION_JOBCARD_CSRF_PURPOSE) ?>
                <input type="hidden" name="jobcard_id" value="<?= (int)$jobcardId ?>">
                <input type="hidden" name="action" value="save_reception_notes">
                <label for="reception_notes">یادداشت پذیرش</label>
                <textarea class="p2-jc-textarea" id="reception_notes" name="reception_notes"><?= m360_reception_jobcard_h((string)($jobcard['reception_notes'] ?? ($jobcard['internal_notes'] ?? ''))) ?></textarea>
                <div style="margin-top:0.5rem;"><button type="submit" class="p2-jc-action-btn primary">ذخیره یادداشت پذیرش</button></div>
            </form>
            <?php if (in_array('save_initial_inspection', $allowed, true)): ?>
            <form method="post" action="erp-reception-jobcard-action.php">
                <?= erp_csrf_input(M360_RECEPTION_JOBCARD_CSRF_PURPOSE) ?>
                <input type="hidden" name="jobcard_id" value="<?= (int)$jobcardId ?>">
                <input type="hidden" name="action" value="save_initial_inspection">
                <label for="initial_inspection_notes">بررسی اولیه</label>
                <textarea class="p2-jc-textarea" id="initial_inspection_notes" name="initial_inspection_notes"><?= m360_reception_jobcard_h((string)($jobcard['initial_inspection_notes'] ?? ($jobcard['initial_vehicle_condition'] ?? ''))) ?></textarea>
                <div style="margin-top:0.5rem;"><button type="submit" class="p2-jc-action-btn primary">ذخیره بررسی اولیه</button></div>
            </form>
            <?php endif; ?>
        </section>

        <section class="w1c-card">
            <h2 style="margin-top:0;font-size:1.05rem;">تاریخچه تغییرات</h2>
            <?php if ($history === []): ?>
                <p>تاریخچه‌ای ثبت نشده است.</p>
            <?php else: ?>
                <table class="p2-jc-history">
                    <thead><tr><th>زمان</th><th>رویداد</th><th>از</th><th>به</th><th>خلاصه</th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= m360_reception_jobcard_h(substr((string)($h['changed_at'] ?? ''), 0, 19)) ?></td>
                            <td><?= m360_reception_jobcard_h((string)($h['change_type'] ?? '')) ?></td>
                            <td><?= m360_reception_jobcard_h((string)($h['previous_status'] ?? '-')) ?></td>
                            <td><?= m360_reception_jobcard_h((string)($h['new_status'] ?? '-')) ?></td>
                            <td><?= m360_reception_jobcard_h((string)($h['change_summary'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <?php if ($contractEvents !== []): ?>
        <section class="w1c-card">
            <h2 style="margin-top:0;font-size:1.05rem;">تاریخچه قرارداد</h2>
            <table class="p2-jc-history">
                <thead><tr><th>زمان</th><th>رویداد</th><th>یادداشت</th></tr></thead>
                <tbody>
                <?php foreach ($contractEvents as $ev): ?>
                    <tr>
                        <td><?= m360_reception_jobcard_h(substr((string)($ev['created_at'] ?? ''), 0, 19)) ?></td>
                        <td><?= m360_reception_jobcard_h((string)($ev['event_name'] ?? '')) ?></td>
                        <td><?= m360_reception_jobcard_h((string)($ev['event_note'] ?? '')) ?></td>
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
