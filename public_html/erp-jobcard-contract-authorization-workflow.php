<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Contract Authorization Workflow (Wave 3B)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-workflow-helper.php';

function wave3b_wf_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$authIdRaw = trim((string)($_GET['authorization_id'] ?? ''));
$invalidId = $authIdRaw === '' || !ctype_digit($authIdRaw) || (int)$authIdRaw < 1;
$authorizationId = $invalidId ? 0 : (int)$authIdRaw;
$recordResult = $invalidId ? null : moghare360_contract_authorization_workflow_get_record($authorizationId);
$historyResult = $invalidId ? null : moghare360_contract_authorization_workflow_history($authorizationId);
$record = ($recordResult['ok'] ?? false) ? ($recordResult['record'] ?? null) : null;
$nextActions = $record !== null
    ? moghare360_contract_authorization_workflow_next_actions((string)($record['authorization_status'] ?? ''))
    : [];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>گردش کار مجوز/قرارداد</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w3b-wrap">
    <header class="w1c-banner w3b-banner">
        <h1>گردش کار مجوز/قرارداد</h1>
        <p>WAVE 3B — Controlled Authorization Workflow</p>
    </header>

    <section class="w1c-card w3b-warning">
        <strong>This is internal controlled workflow, not final legal e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی مشتری فعال نیست — پرداخت فعال نیست.</p>
    </section>

    <?php if ($invalidId): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه مجوز نامعتبر است. مقدار عددی مثبت وارد کنید.</p>
        </section>
    <?php elseif ($record === null): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave3b_wf_h((string)($recordResult['message'] ?? 'رکورد مجوز یافت نشد.')) ?></p>
        </section>
    <?php else: ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">جزئیات مجوز #<?= wave3b_wf_h((string)$authorizationId) ?></h2>
            <dl class="w3b-detail-dl">
                <dt>کارت کار</dt><dd><?= wave3b_wf_h((string)($record['jobcard_id'] ?? '')) ?></dd>
                <dt>نوع</dt><dd><?= wave3b_wf_h((string)($record['authorization_type'] ?? '')) ?></dd>
                <dt>وضعیت فعلی</dt><dd><strong><?= wave3b_wf_h(moghare360_contract_authorization_workflow_status_label((string)($record['authorization_status'] ?? ''))) ?></strong></dd>
                <dt>روش</dt><dd><?= wave3b_wf_h((string)($record['authorization_method'] ?? '')) ?></dd>
                <dt>مشتری</dt><dd><?= wave3b_wf_h((string)($record['customer_name'] ?? '')) ?></dd>
                <dt>موبایل</dt><dd><?= wave3b_wf_h((string)($record['customer_mobile'] ?? '')) ?></dd>
                <dt>یادداشت</dt><dd><?= wave3b_wf_h((string)($record['authorization_note'] ?? '—')) ?></dd>
                <dt>ایجاد</dt><dd><?= wave3b_wf_h((string)($record['created_at'] ?? '')) ?></dd>
            </dl>
        </section>

        <?php if (($historyResult['ok'] ?? false) && !empty($historyResult['rows'])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">تاریخچه گردش کار</h2>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                        <thead>
                        <tr>
                            <th style="text-align:right;padding:0.35rem;">رویداد</th>
                            <th style="text-align:right;padding:0.35rem;">از</th>
                            <th style="text-align:right;padding:0.35rem;">به</th>
                            <th style="text-align:right;padding:0.35rem;">زمان</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($historyResult['rows'] as $row): ?>
                            <tr>
                                <td style="padding:0.35rem;"><?= wave3b_wf_h((string)($row['event_code'] ?? '')) ?></td>
                                <td style="padding:0.35rem;"><?= wave3b_wf_h(moghare360_contract_authorization_workflow_status_label((string)($row['old_status'] ?? ''))) ?></td>
                                <td style="padding:0.35rem;"><?= wave3b_wf_h(moghare360_contract_authorization_workflow_status_label((string)($row['new_status'] ?? ''))) ?></td>
                                <td style="padding:0.35rem;"><?= wave3b_wf_h((string)($row['event_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($nextActions !== []): ?>
            <section class="w1c-card w1c-form">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">اقدام بعدی مجاز</h2>
                <form method="post" action="submit-jobcard-contract-authorization-workflow.php">
                    <input type="hidden" name="authorization_id" value="<?= wave3b_wf_h((string)$authorizationId) ?>">

                    <label for="target_status">وضعیت هدف <span style="color:#b91c1c;">*</span></label>
                    <select id="target_status" name="target_status" required>
                        <?php foreach ($nextActions as $action): ?>
                            <option value="<?= wave3b_wf_h($action) ?>"><?= wave3b_wf_h(moghare360_contract_authorization_workflow_status_label($action)) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="workflow_note">یادداشت گردش کار / دلیل لغو</label>
                    <textarea id="workflow_note" name="workflow_note" rows="3" maxlength="2000" placeholder="برای لغو پس از تأیید/رد، دلیل الزامی است"></textarea>

                    <button type="submit" class="w1c-btn">اعمال انتقال وضعیت</button>
                </form>
            </section>
        <?php else: ?>
            <section class="w1c-card w1c-note">
                <p style="margin:0;">انتقال وضعیت بیشتری برای این مجوز مجاز نیست.</p>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <section class="w1c-card w1c-form">
        <form method="get" action="erp-jobcard-contract-authorization-workflow.php">
            <label for="authorization_id_lookup">جستجوی شناسه مجوز</label>
            <input type="number" id="authorization_id_lookup" name="authorization_id" min="1" value="<?= $invalidId ? '' : wave3b_wf_h((string)$authorizationId) ?>" required>
            <button type="submit" class="w1c-btn">نمایش</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-contract-authorization-preview.php?jobcard_id=<?= wave3b_wf_h((string)($record['jobcard_id'] ?? '1')) ?>">پیش‌نمایش مجوزهای کارت کار</a>
        <a href="erp-jobcard-contract-authorization.php">ثبت مجوز جدید</a>
    </nav>
</div>
</body>
</html>
