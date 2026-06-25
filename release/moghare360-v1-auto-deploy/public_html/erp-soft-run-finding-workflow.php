<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Finding Workflow (Wave 8B)
 * Controlled workflow update form — NOT final vehicle delivery
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-helper.php';

function wave8b_workflow_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$findingIdRaw = trim((string)($_GET['finding_id'] ?? ''));
$findingId = ($findingIdRaw !== '' && ctype_digit($findingIdRaw) && (int)$findingIdRaw >= 1)
    ? (int)$findingIdRaw
    : 0;

$schema = moghare360_soft_run_finding_schema_status();
$detail = $findingId > 0 ? moghare360_soft_run_finding_fetch_detail($findingId) : null;
$record = ($detail !== null && ($detail['ok'] ?? false)) ? (array)($detail['record'] ?? []) : [];
$currentFindingStatus = moghare360_soft_run_finding_normalize((string)($record['finding_status'] ?? ''));
$currentCorrectiveStatus = moghare360_soft_run_finding_normalize((string)($record['corrective_action_status'] ?? ''));
$nextFindingStatuses = $currentFindingStatus !== '' ? moghare360_soft_run_finding_next_statuses($currentFindingStatus) : [];
$nextCorrectiveStatuses = $currentCorrectiveStatus !== ''
    ? moghare360_soft_run_finding_next_corrective_statuses($currentCorrectiveStatus)
    : [];
$findingTransitions = moghare360_soft_run_finding_allowed_transitions();
$correctiveTransitions = moghare360_soft_run_finding_allowed_corrective_transitions();
$workflowAllowed = $currentFindingStatus !== 'CANCELLED'
    && ($nextFindingStatuses !== [] || count($nextCorrectiveStatuses) > 1);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>گردش کار یافته Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w8b-wrap">
    <header class="w1c-banner w8b-banner">
        <h1>گردش کار یافته Soft Run</h1>
        <p>WAVE 8B — Controlled Soft Run Findings Workflow</p>
    </header>

    <section class="w1c-card w8b-warning">
        <strong>Internal Soft Run finding workflow only — not final delivery. Not delivery completion. Not legal e-signature. Not payment/accounting. Not production activation.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این صفحه فقط برای به‌روزرسانی کنترل‌شده وضعیت یافته و اقدام اصلاحی داخلی است.</p>
    </section>

    <?php if (($schema['schema_status'] ?? '') === MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave8b_workflow_h(MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE) ?></p>
        </section>
    <?php elseif ($findingId < 1): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه یافته نامعتبر است.</p>
        </section>
    <?php elseif ($detail === null || !($detail['ok'] ?? false)): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave8b_workflow_h((string)($detail['message'] ?? 'رکورد یافته یافت نشد.')) ?></p>
        </section>
    <?php else: ?>
        <section class="w1c-card w8b-current">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">وضعیت فعلی</h2>
            <dl class="w8b-dl">
                <dt>شناسه یافته</dt>
                <dd><?= wave8b_workflow_h((string)($record['finding_id'] ?? '')) ?></dd>
                <dt>کد یافته</dt>
                <dd><?= wave8b_workflow_h((string)($record['finding_code'] ?? '')) ?></dd>
                <dt>نوع یافته</dt>
                <dd><?= wave8b_workflow_h(moghare360_soft_run_finding_type_label((string)($record['finding_type'] ?? ''))) ?>
                    (<?= wave8b_workflow_h((string)($record['finding_type'] ?? '')) ?>)</dd>
                <dt>سطح شدت</dt>
                <dd><?= wave8b_workflow_h(moghare360_soft_run_finding_severity_label((string)($record['severity_level'] ?? ''))) ?>
                    (<?= wave8b_workflow_h((string)($record['severity_level'] ?? '')) ?>)</dd>
                <dt>وضعیت یافته</dt>
                <dd><?= wave8b_workflow_h(moghare360_soft_run_finding_status_label($currentFindingStatus)) ?>
                    (<?= wave8b_workflow_h($currentFindingStatus) ?>)</dd>
                <dt>وضعیت اقدام اصلاحی</dt>
                <dd><?= wave8b_workflow_h(moghare360_soft_run_finding_status_label($currentCorrectiveStatus)) ?>
                    (<?= wave8b_workflow_h($currentCorrectiveStatus) ?>)</dd>
            </dl>
        </section>

        <section class="w1c-card w8b-transitions">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">انتقال‌های مجاز وضعیت یافته</h2>
            <?php if ($nextFindingStatuses === []): ?>
                <p style="margin:0;font-size:0.9rem;color:#525252;">
                    وضعیت <?= wave8b_workflow_h($currentFindingStatus) ?> انتقال بعدی مجاز ندارد.
                </p>
            <?php else: ?>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($nextFindingStatuses as $nextStatus): ?>
                        <li>
                            <?= wave8b_workflow_h($currentFindingStatus) ?> →
                            <strong><?= wave8b_workflow_h($nextStatus) ?></strong>
                            (<?= wave8b_workflow_h(moghare360_soft_run_finding_status_label($nextStatus)) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="w1c-card w8b-transitions">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">انتقال‌های مجاز اقدام اصلاحی</h2>
            <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                <?php foreach ($nextCorrectiveStatuses as $nextCorrective): ?>
                    <li>
                        <?= wave8b_workflow_h($currentCorrectiveStatus) ?> →
                        <strong><?= wave8b_workflow_h($nextCorrective) ?></strong>
                        (<?= wave8b_workflow_h(moghare360_soft_run_finding_status_label($nextCorrective)) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <?php if ($workflowAllowed): ?>
            <section class="w1c-card w1c-form w8b-form">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">فرم به‌روزرسانی گردش کار</h2>
                <form method="post" action="submit-soft-run-finding-workflow.php">
                    <input type="hidden" name="finding_id" value="<?= wave8b_workflow_h((string)$findingId) ?>">

                    <label for="new_finding_status">وضعیت یافته جدید <span style="color:#b91c1c;">*</span></label>
                    <select id="new_finding_status" name="new_finding_status" required>
                        <option value="<?= wave8b_workflow_h($currentFindingStatus) ?>" selected>
                            <?= wave8b_workflow_h(moghare360_soft_run_finding_status_label($currentFindingStatus)) ?>
                            (<?= wave8b_workflow_h($currentFindingStatus) ?>) — بدون تغییر
                        </option>
                        <?php foreach ($nextFindingStatuses as $nextStatus): ?>
                            <option value="<?= wave8b_workflow_h($nextStatus) ?>">
                                <?= wave8b_workflow_h(moghare360_soft_run_finding_status_label($nextStatus)) ?>
                                (<?= wave8b_workflow_h($nextStatus) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="corrective_action_status">وضعیت اقدام اصلاحی <span style="color:#b91c1c;">*</span></label>
                    <select id="corrective_action_status" name="corrective_action_status" required>
                        <?php foreach ($nextCorrectiveStatuses as $nextCorrective): ?>
                            <?php $selected = ($nextCorrective === $currentCorrectiveStatus) ? 'selected' : ''; ?>
                            <option value="<?= wave8b_workflow_h($nextCorrective) ?>" <?= $selected ?>>
                                <?= wave8b_workflow_h(moghare360_soft_run_finding_status_label($nextCorrective)) ?>
                                (<?= wave8b_workflow_h($nextCorrective) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="change_reason">دلیل تغییر <span style="color:#b91c1c;">*</span></label>
                    <textarea id="change_reason" name="change_reason" rows="3" maxlength="1000" required
                              placeholder="دلیل کنترل‌شده تغییر وضعیت"></textarea>

                    <label for="corrective_action">اقدام اصلاحی (اختیاری)</label>
                    <textarea id="corrective_action" name="corrective_action" rows="3" maxlength="1500"><?= wave8b_workflow_h((string)($record['corrective_action'] ?? '')) ?></textarea>

                    <label for="owner_user_id">شناسه مسئول (اختیاری)</label>
                    <input type="number" id="owner_user_id" name="owner_user_id" min="1"
                           value="<?= wave8b_workflow_h((string)($record['owner_user_id'] ?? '')) ?>">

                    <label for="due_at">مهلت انجام (اختیاری)</label>
                    <input type="datetime-local" id="due_at" name="due_at"
                           value="<?= wave8b_workflow_h(str_replace(' ', 'T', substr((string)($record['due_at'] ?? ''), 0, 16))) ?>">

                    <label for="resolved_at">زمان رفع (اختیاری)</label>
                    <input type="datetime-local" id="resolved_at" name="resolved_at"
                           value="<?= wave8b_workflow_h(str_replace(' ', 'T', substr((string)($record['resolved_at'] ?? ''), 0, 16))) ?>">

                    <button type="submit" class="w1c-btn w8b-btn">اعمال گردش کار یافته</button>
                </form>
            </section>
        <?php else: ?>
            <section class="w1c-card w1c-error-box">
                <p style="margin:0;">وضعیت CANCELLED نهایی است — گردش کار مجاز نیست.</p>
            </section>
        <?php endif; ?>

        <section class="w1c-card w8b-review">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">مرجع انتقال‌های کنترل‌شده (Read-only)</h2>
            <div style="overflow-x:auto;">
                <table class="w8b-table">
                    <thead>
                    <tr>
                        <th>نوع</th>
                        <th>از</th>
                        <th>به</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($findingTransitions as $from => $toList): ?>
                        <?php if ($toList === []) continue; ?>
                        <?php foreach ($toList as $to): ?>
                            <tr>
                                <td>یافته</td>
                                <td><?= wave8b_workflow_h($from) ?></td>
                                <td><?= wave8b_workflow_h($to) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php foreach ($correctiveTransitions as $from => $toList): ?>
                        <?php foreach ($toList as $to): ?>
                            <tr>
                                <td>اقدام اصلاحی</td>
                                <td><?= wave8b_workflow_h($from) ?></td>
                                <td><?= wave8b_workflow_h($to) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w8b-nav">
        <?php if ($findingId > 0): ?>
            <a href="erp-soft-run-finding-detail.php?finding_id=<?= wave8b_workflow_h((string)$findingId) ?>">جزئیات یافته</a>
        <?php endif; ?>
        <a href="erp-soft-run-finding-board.php">برد یافته‌ها</a>
        <a href="erp-soft-run-finding-create.php">ثبت یافته جدید</a>
    </nav>
</div>
</body>
</html>
