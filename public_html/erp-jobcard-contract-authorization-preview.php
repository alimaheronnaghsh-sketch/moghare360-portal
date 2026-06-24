<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Contract Authorization Preview (Wave 3A)
 * Read-only — no DB write
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-helper.php';

function wave3a_preview_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$jobcardIdRaw = trim((string)($_GET['jobcard_id'] ?? ''));
$invalidId = $jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1;
$jobcardId = $invalidId ? 0 : (int)$jobcardIdRaw;
$listResult = $invalidId ? null : moghare360_contract_authorization_list_by_jobcard($jobcardId);
$schema = moghare360_contract_authorization_schema_status();
$typeLabels = moghare360_contract_authorization_type_labels();
$statusLabels = moghare360_contract_authorization_status_labels();
$methodLabels = moghare360_contract_authorization_method_labels();

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>پیش‌نمایش مجوز/قرارداد کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w3a-wrap">
    <header class="w1c-banner w3a-banner">
        <h1>پیش‌نمایش مجوز/قرارداد کارت کار</h1>
        <p>Wave 3A — Read-only authorization review</p>
    </header>

    <section class="w1c-card w3a-warning">
        <strong>This is internal controlled authorization, not final legal e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی مشتری فعال نیست.</p>
    </section>

    <?php if ($invalidId): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه کارت کار نامعتبر است. مقدار عددی مثبت وارد کنید.</p>
        </section>
    <?php elseif (($schema['schema_status'] ?? '') === MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave3a_preview_h(MOGHARE360_CONTRACT_AUTH_BLOCK_MESSAGE) ?></p>
        </section>
    <?php elseif ($listResult !== null && ($listResult['ok'] ?? false) === true && !empty($listResult['records'])): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">مجوزهای کارت کار <?= wave3a_preview_h((string)$jobcardId) ?></h2>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                    <thead>
                    <tr>
                        <th style="text-align:right;padding:0.35rem;">شناسه</th>
                        <th style="text-align:right;padding:0.35rem;">نوع</th>
                        <th style="text-align:right;padding:0.35rem;">وضعیت</th>
                        <th style="text-align:right;padding:0.35rem;">روش</th>
                        <th style="text-align:right;padding:0.35rem;">مشتری</th>
                        <th style="text-align:right;padding:0.35rem;">موبایل</th>
                        <th style="text-align:right;padding:0.35rem;">زمان</th>
                        <th style="text-align:right;padding:0.35rem;">گردش کار</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($listResult['records'] as $row): ?>
                        <?php
                        $typeKey = (string)($row['authorization_type'] ?? '');
                        $statusKey = (string)($row['authorization_status'] ?? '');
                        $methodKey = (string)($row['authorization_method'] ?? '');
                        ?>
                        <tr>
                            <td style="padding:0.35rem;"><?= wave3a_preview_h((string)($row['authorization_id'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave3a_preview_h($typeLabels[$typeKey] ?? $typeKey) ?></td>
                            <td style="padding:0.35rem;"><?= wave3a_preview_h($statusLabels[$statusKey] ?? $statusKey) ?></td>
                            <td style="padding:0.35rem;"><?= wave3a_preview_h($methodLabels[$methodKey] ?? $methodKey) ?></td>
                            <td style="padding:0.35rem;"><?= wave3a_preview_h((string)($row['customer_name'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave3a_preview_h((string)($row['customer_mobile'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave3a_preview_h((string)($row['created_at'] ?? '')) ?></td>
                            <td style="padding:0.35rem;">
                                <a href="erp-jobcard-contract-authorization-workflow.php?authorization_id=<?= wave3a_preview_h((string)($row['authorization_id'] ?? '')) ?>">گردش کار</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php elseif ($listResult !== null && ($listResult['ok'] ?? false) === true): ?>
        <section class="w1c-card w1c-note">
            <p style="margin:0;">رکورد مجوز/قراردادی برای کارت کار <strong><?= wave3a_preview_h((string)$jobcardId) ?></strong> یافت نشد.</p>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave3a_preview_h((string)($listResult['message'] ?? 'خواندن مجوزها ناموفق بود.')) ?></p>
        </section>
    <?php endif; ?>

    <section class="w1c-card w1c-form">
        <form method="get" action="erp-jobcard-contract-authorization-preview.php">
            <label for="jobcard_id_lookup">جستجوی شناسه کارت کار</label>
            <input type="number" id="jobcard_id_lookup" name="jobcard_id" min="1" value="<?= $invalidId ? '' : wave3a_preview_h((string)$jobcardId) ?>" required>
            <button type="submit" class="w1c-btn">نمایش</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-contract-authorization.php">ثبت مجوز/قرارداد جدید</a>
        <a href="erp-jobcard-authorization-gate.php?jobcard_id=<?= wave3a_preview_h($invalidId ? '1' : (string)$jobcardId) ?>">گیت مجوزهای جاب‌کارت</a>
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
    </nav>
</div>
</body>
</html>
