<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Executive Go/No-Go Decision Workflow (Wave 9C)
 * Controlled workflow update form — NOT final delivery approval
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-executive-go-no-go-decision-helper.php';

function wave9c_workflow_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$decisionIdRaw = trim((string)($_GET['decision_id'] ?? ''));
$decisionId = ($decisionIdRaw !== '' && ctype_digit($decisionIdRaw) && (int)$decisionIdRaw >= 1)
    ? (int)$decisionIdRaw
    : 0;

$schema = moghare360_executive_go_no_go_decision_schema_status();
$detail = $decisionId > 0 ? moghare360_executive_go_no_go_decision_fetch_detail($decisionId) : null;
$record = ($detail !== null && ($detail['ok'] ?? false)) ? (array)($detail['record'] ?? []) : [];
$currentStatus = moghare360_executive_go_no_go_decision_normalize((string)($record['decision_status'] ?? ''));
$currentType = moghare360_executive_go_no_go_decision_normalize((string)($record['decision_type'] ?? ''));
$nextStatuses = $currentStatus !== '' ? moghare360_executive_go_no_go_decision_next_statuses($currentStatus) : [];
$statusTransitions = moghare360_executive_go_no_go_decision_allowed_transitions();
$allowedTypes = moghare360_executive_go_no_go_decision_allowed_types();
$workflowAllowed = $currentStatus !== 'CANCELLED' && $nextStatuses !== [];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>گردش کار تصمیم مدیریتی Go/No-Go</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w9c-wrap">
    <header class="w1c-banner w9c-banner">
        <h1>گردش کار تصمیم مدیریتی Go/No-Go</h1>
        <p>WAVE 9C — Controlled Executive Decision Workflow &amp; Review Control</p>
    </header>

    <section class="w1c-card w9c-warning">
        <strong>Internal executive decision workflow only — not final delivery approval. Not delivery completion. Not legal e-signature. Not payment/accounting. Not production activation.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این صفحه فقط برای به‌روزرسانی کنترل‌شده وضعیت و بازبینی نوع تصمیم مدیریتی است — رکورد تصمیم جدید ایجاد نمی‌شود.</p>
    </section>

    <?php if (($schema['schema_status'] ?? '') === MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave9c_workflow_h(MOGHARE360_EXECUTIVE_GO_NO_GO_BLOCK_MESSAGE) ?></p>
        </section>
    <?php elseif ($decisionId < 1): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه تصمیم نامعتبر است.</p>
        </section>
    <?php elseif ($detail === null || !($detail['ok'] ?? false)): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave9c_workflow_h((string)($detail['message'] ?? 'رکورد تصمیم یافت نشد.')) ?></p>
        </section>
    <?php else: ?>
        <section class="w1c-card w9c-current">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">وضعیت فعلی تصمیم</h2>
            <dl class="w9c-dl">
                <dt>شناسه تصمیم</dt>
                <dd><?= wave9c_workflow_h((string)($record['decision_id'] ?? '')) ?></dd>
                <dt>کد تصمیم</dt>
                <dd><?= wave9c_workflow_h((string)($record['decision_code'] ?? '')) ?></dd>
                <dt>عنوان</dt>
                <dd><?= wave9c_workflow_h((string)($record['decision_title'] ?? '')) ?></dd>
                <dt>نوع تصمیم</dt>
                <dd><?= wave9c_workflow_h(moghare360_executive_go_no_go_decision_type_label($currentType)) ?>
                    (<?= wave9c_workflow_h($currentType) ?>)</dd>
                <dt>وضعیت تصمیم</dt>
                <dd><?= wave9c_workflow_h(moghare360_executive_go_no_go_decision_status_label($currentStatus)) ?>
                    (<?= wave9c_workflow_h($currentStatus) ?>)</dd>
                <dt>وضعیت آمادگی مدیریتی</dt>
                <dd><?= wave9c_workflow_h((string)($record['executive_readiness_status'] ?? '')) ?></dd>
            </dl>
        </section>

        <section class="w1c-card w9c-transitions">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">انتقال‌های مجاز وضعیت تصمیم</h2>
            <?php if ($nextStatuses === []): ?>
                <p style="margin:0;font-size:0.9rem;color:#525252;">
                    وضعیت <?= wave9c_workflow_h($currentStatus) ?> انتقال بعدی مجاز ندارد.
                </p>
            <?php else: ?>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($nextStatuses as $nextStatus): ?>
                        <li>
                            <?= wave9c_workflow_h($currentStatus) ?> →
                            <strong><?= wave9c_workflow_h($nextStatus) ?></strong>
                            (<?= wave9c_workflow_h(moghare360_executive_go_no_go_decision_status_label($nextStatus)) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <?php if ($workflowAllowed): ?>
            <section class="w1c-card w1c-form w9c-form">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">فرم به‌روزرسانی گردش کار و بازبینی مدیریتی</h2>
                <form method="post" action="submit-executive-go-no-go-decision-workflow.php">
                    <input type="hidden" name="decision_id" value="<?= wave9c_workflow_h((string)$decisionId) ?>">

                    <label for="new_decision_status">وضعیت تصمیم جدید <span style="color:#b91c1c;">*</span></label>
                    <select id="new_decision_status" name="new_decision_status" required>
                        <option value="<?= wave9c_workflow_h($currentStatus) ?>" selected>
                            <?= wave9c_workflow_h(moghare360_executive_go_no_go_decision_status_label($currentStatus)) ?>
                            (<?= wave9c_workflow_h($currentStatus) ?>) — بدون تغییر
                        </option>
                        <?php foreach ($nextStatuses as $nextStatus): ?>
                            <option value="<?= wave9c_workflow_h($nextStatus) ?>">
                                <?= wave9c_workflow_h(moghare360_executive_go_no_go_decision_status_label($nextStatus)) ?>
                                (<?= wave9c_workflow_h($nextStatus) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="decision_type">نوع تصمیم (بازبینی — اختیاری)</label>
                    <select id="decision_type" name="decision_type">
                        <option value="">بدون تغییر — <?= wave9c_workflow_h(moghare360_executive_go_no_go_decision_type_label($currentType)) ?></option>
                        <?php foreach ($allowedTypes as $type): ?>
                            <?php if ($type === $currentType) continue; ?>
                            <option value="<?= wave9c_workflow_h($type) ?>">
                                <?= wave9c_workflow_h(moghare360_executive_go_no_go_decision_type_label($type)) ?>
                                (<?= wave9c_workflow_h($type) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="change_reason">دلیل تغییر <span style="color:#b91c1c;">*</span></label>
                    <textarea id="change_reason" name="change_reason" rows="3" maxlength="1000" required
                              placeholder="دلیل کنترل‌شده تغییر وضعیت یا بازبینی نوع"></textarea>

                    <label for="management_review_note">یادداشت بازبینی مدیریتی (اختیاری)</label>
                    <textarea id="management_review_note" name="management_review_note" rows="3" maxlength="1500"
                              placeholder="یادداشت بازبینی — به خلاصه اقدام مورد نیاز افزوده می‌شود"></textarea>

                    <label for="decision_summary">خلاصه تصمیم (اختیاری)</label>
                    <textarea id="decision_summary" name="decision_summary" rows="3" maxlength="1500"><?= wave9c_workflow_h((string)($record['decision_summary'] ?? '')) ?></textarea>

                    <label for="required_action_summary">خلاصه اقدام مورد نیاز (اختیاری)</label>
                    <textarea id="required_action_summary" name="required_action_summary" rows="3" maxlength="1500"><?= wave9c_workflow_h((string)($record['required_action_summary'] ?? '')) ?></textarea>

                    <label for="risk_note">یادداشت ریسک (اختیاری)</label>
                    <textarea id="risk_note" name="risk_note" rows="3" maxlength="1500"><?= wave9c_workflow_h((string)($record['risk_note'] ?? '')) ?></textarea>

                    <button type="submit" class="w1c-btn w9c-btn">اعمال گردش کار تصمیم مدیریتی</button>
                </form>
            </section>
        <?php else: ?>
            <section class="w1c-card w1c-error-box">
                <p style="margin:0;">وضعیت CANCELLED نهایی است — گردش کار مجاز نیست.</p>
            </section>
        <?php endif; ?>

        <section class="w1c-card w9c-review">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">مرجع انتقال‌های کنترل‌شده (Read-only Decision Workflow Review)</h2>
            <div style="overflow-x:auto;">
                <table class="w9c-table">
                    <thead>
                    <tr>
                        <th>از وضعیت</th>
                        <th>به وضعیت</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($statusTransitions as $from => $toList): ?>
                        <?php if ($toList === []) continue; ?>
                        <?php foreach ($toList as $to): ?>
                            <tr>
                                <td><?= wave9c_workflow_h($from) ?></td>
                                <td><?= wave9c_workflow_h($to) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="margin:0.75rem 0 0;font-size:0.85rem;color:#525252;">
                نوع تصمیم می‌تواند بدون تغییر باقی بماند یا در صورت نیاز بازبینی شود — مقادیر مجاز:
                <?= wave9c_workflow_h(implode(', ', $allowedTypes)) ?>
            </p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w9c-nav">
        <?php if ($decisionId > 0): ?>
            <a href="erp-executive-go-no-go-decision-detail.php?decision_id=<?= wave9c_workflow_h((string)$decisionId) ?>">جزئیات تصمیم</a>
        <?php endif; ?>
        <a href="erp-executive-go-no-go-decision-board.php">برد تصمیم‌ها</a>
        <a href="erp-executive-soft-run-readiness-dashboard.php">داشبورد آمادگی مدیریتی</a>
    </nav>
</div>
</body>
</html>
