<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Pilot Execution Workflow (Wave 7B)
 * Controlled workflow update form — NOT final vehicle delivery
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-execution-helper.php';

function wave7b_workflow_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$executionIdRaw = trim((string)($_GET['execution_id'] ?? ''));
$executionId = ($executionIdRaw !== '' && ctype_digit($executionIdRaw) && (int)$executionIdRaw >= 1)
    ? (int)$executionIdRaw
    : 0;

$schema = moghare360_soft_run_pilot_execution_schema_status();
$detail = $executionId > 0 ? moghare360_soft_run_pilot_execution_fetch_detail($executionId) : null;
$record = ($detail !== null && ($detail['ok'] ?? false)) ? (array)($detail['record'] ?? []) : [];
$currentStatus = moghare360_soft_run_pilot_execution_normalize_status((string)($record['execution_status'] ?? ''));
$nextStatuses = $currentStatus !== '' ? moghare360_soft_run_pilot_execution_next_statuses($currentStatus) : [];
$evidenceStatuses = moghare360_soft_run_pilot_execution_allowed_evidence_statuses();
$resultStatuses = moghare360_soft_run_pilot_execution_allowed_result_statuses();
$transitions = moghare360_soft_run_pilot_execution_allowed_transitions();

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>گردش کار اجرای پایلوت Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w7b-wrap">
    <header class="w1c-banner w7b-banner">
        <h1>گردش کار اجرای پایلوت Soft Run</h1>
        <p>WAVE 7B — Controlled Soft Run Pilot Execution Workflow</p>
    </header>

    <section class="w1c-card w7b-warning">
        <strong>Internal Soft Run workflow only — not final delivery. Not delivery completion. Not legal e-signature. Not payment/accounting. Not production activation.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این صفحه فقط برای به‌روزرسانی کنترل‌شده گردش کار لاگ اجرای پایلوت داخلی است.</p>
    </section>

    <?php if (($schema['schema_status'] ?? '') === MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave7b_workflow_h(MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE) ?></p>
        </section>
    <?php elseif ($executionId < 1): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه اجرا نامعتبر است.</p>
        </section>
    <?php elseif ($detail === null || !($detail['ok'] ?? false)): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave7b_workflow_h((string)($detail['message'] ?? 'رکورد اجرا یافت نشد.')) ?></p>
        </section>
    <?php else: ?>
        <section class="w1c-card w7b-current">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">وضعیت فعلی</h2>
            <dl class="w7b-dl">
                <dt>شناسه اجرا</dt>
                <dd><?= wave7b_workflow_h((string)($record['execution_id'] ?? '')) ?></dd>
                <dt>کد اجرا</dt>
                <dd><?= wave7b_workflow_h((string)($record['execution_code'] ?? '')) ?></dd>
                <dt>وضعیت اجرا</dt>
                <dd><?= wave7b_workflow_h(moghare360_soft_run_pilot_execution_status_label($currentStatus)) ?>
                    (<?= wave7b_workflow_h($currentStatus) ?>)</dd>
                <dt>وضعیت شواهد</dt>
                <dd><?= wave7b_workflow_h(moghare360_soft_run_pilot_execution_status_label((string)($record['evidence_status'] ?? ''))) ?>
                    (<?= wave7b_workflow_h((string)($record['evidence_status'] ?? '')) ?>)</dd>
                <dt>وضعیت نتیجه</dt>
                <dd><?= wave7b_workflow_h(moghare360_soft_run_pilot_execution_status_label((string)($record['result_status'] ?? ''))) ?>
                    (<?= wave7b_workflow_h((string)($record['result_status'] ?? '')) ?>)</dd>
            </dl>
        </section>

        <section class="w1c-card w7b-transitions">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">انتقال‌های مجاز از وضعیت فعلی</h2>
            <?php if ($nextStatuses === []): ?>
                <p style="margin:0;font-size:0.9rem;color:#525252;">
                    وضعیت <?= wave7b_workflow_h($currentStatus) ?> نهایی است — انتقال بعدی مجاز نیست.
                </p>
            <?php else: ?>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($nextStatuses as $nextStatus): ?>
                        <li>
                            <?= wave7b_workflow_h($currentStatus) ?> →
                            <strong><?= wave7b_workflow_h($nextStatus) ?></strong>
                            (<?= wave7b_workflow_h(moghare360_soft_run_pilot_execution_status_label($nextStatus)) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <?php if ($nextStatuses !== []): ?>
            <section class="w1c-card w1c-form w7b-form">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">فرم به‌روزرسانی گردش کار</h2>
                <form method="post" action="submit-soft-run-pilot-execution-workflow.php">
                    <input type="hidden" name="execution_id" value="<?= wave7b_workflow_h((string)$executionId) ?>">

                    <label for="new_execution_status">وضعیت اجرای جدید <span style="color:#b91c1c;">*</span></label>
                    <select id="new_execution_status" name="new_execution_status" required>
                        <option value="">— انتخاب وضعیت جدید —</option>
                        <?php foreach ($nextStatuses as $nextStatus): ?>
                            <option value="<?= wave7b_workflow_h($nextStatus) ?>">
                                <?= wave7b_workflow_h(moghare360_soft_run_pilot_execution_status_label($nextStatus)) ?>
                                (<?= wave7b_workflow_h($nextStatus) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="evidence_status">وضعیت شواهد <span style="color:#b91c1c;">*</span></label>
                    <select id="evidence_status" name="evidence_status" required>
                        <?php foreach ($evidenceStatuses as $status): ?>
                            <?php $selected = ((string)($record['evidence_status'] ?? '') === $status) ? 'selected' : ''; ?>
                            <option value="<?= wave7b_workflow_h($status) ?>" <?= $selected ?>>
                                <?= wave7b_workflow_h(moghare360_soft_run_pilot_execution_status_label($status)) ?>
                                (<?= wave7b_workflow_h($status) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="result_status">وضعیت نتیجه <span style="color:#b91c1c;">*</span></label>
                    <select id="result_status" name="result_status" required>
                        <?php foreach ($resultStatuses as $status): ?>
                            <?php $selected = ((string)($record['result_status'] ?? '') === $status) ? 'selected' : ''; ?>
                            <option value="<?= wave7b_workflow_h($status) ?>" <?= $selected ?>>
                                <?= wave7b_workflow_h(moghare360_soft_run_pilot_execution_status_label($status)) ?>
                                (<?= wave7b_workflow_h($status) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="change_reason">دلیل تغییر <span style="color:#b91c1c;">*</span></label>
                    <textarea id="change_reason" name="change_reason" rows="3" maxlength="1000" required
                              placeholder="دلیل کنترل‌شده تغییر وضعیت"></textarea>

                    <label for="actual_evidence">شواهد واقعی (اختیاری)</label>
                    <textarea id="actual_evidence" name="actual_evidence" rows="3" maxlength="1000"><?= wave7b_workflow_h((string)($record['actual_evidence'] ?? '')) ?></textarea>

                    <label for="blocker_notes">یادداشت مسدودکننده (اختیاری)</label>
                    <textarea id="blocker_notes" name="blocker_notes" rows="2" maxlength="1000"><?= wave7b_workflow_h((string)($record['blocker_notes'] ?? '')) ?></textarea>

                    <label for="internal_notes">یادداشت داخلی (اختیاری)</label>
                    <textarea id="internal_notes" name="internal_notes" rows="2" maxlength="1000"><?= wave7b_workflow_h((string)($record['internal_notes'] ?? '')) ?></textarea>

                    <button type="submit" class="w1c-btn-primary">اعمال گردش کار</button>
                </form>
            </section>
        <?php endif; ?>

        <section class="w1c-card w7b-review">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">مرجع انتقال‌های کنترل‌شده (Read-only)</h2>
            <div style="overflow-x:auto;">
                <table class="w7b-table">
                    <thead>
                    <tr>
                        <th>از</th>
                        <th>به</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transitions as $from => $toList): ?>
                        <?php if ($toList === []) continue; ?>
                        <?php foreach ($toList as $to): ?>
                            <tr>
                                <td><?= wave7b_workflow_h($from) ?></td>
                                <td><?= wave7b_workflow_h($to) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w7b-nav">
        <?php if ($executionId > 0): ?>
            <a href="erp-soft-run-pilot-execution-detail.php?execution_id=<?= wave7b_workflow_h((string)$executionId) ?>">جزئیات اجرا</a>
        <?php endif; ?>
        <a href="erp-soft-run-pilot-execution-board.php">برد اجرای پایلوت</a>
        <a href="erp-soft-run-pilot-execution-create.php">ثبت اجرای جدید</a>
    </nav>
</div>
</body>
</html>
