<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';

m360_intake_contract_require_staff();

$statusFilter = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'ALL';
$conn = customer_core_db();
$contracts = $conn !== false ? m360_intake_contract_list($conn, $statusFilter === 'ALL' ? null : $statusFilter, 150) : [];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قراردادهای پذیرش</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="<?= m360_operational_shell_h(m360_operational_shell_css_href()) ?>">
    <link rel="stylesheet" href="assets/css/m360-contract.css">
</head>
<body style="background:#f8fafc;margin:0;padding:1.25rem;">
<div class="w1c-wrap m360-contract-page">
    <?php m360_operational_shell_render_board('intake_contracts'); ?>
    <header class="w1c-banner">
        <h1>قراردادهای پذیرش</h1>
        <p>تولید، ارسال و پیگیری امضای مشتری</p>
    </header>
    <section class="w1c-card">
        <nav class="m360-contract-actions">
            <?php foreach (['ALL' => 'همه'] + M360_CONTRACT_STATUS_LABELS_FA as $code => $label): ?>
                <a class="m360-contract-btn secondary" href="?status=<?= m360_intake_contract_h($code) ?>"><?= m360_intake_contract_h($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if ($contracts === []): ?>
            <p>قراردادی یافت نشد.</p>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;font-size:0.92rem;">
                <thead>
                <tr style="background:#fafafa;">
                    <th style="padding:0.5rem;text-align:right;">شناسه</th>
                    <th style="padding:0.5rem;text-align:right;">کارت کار</th>
                    <th style="padding:0.5rem;text-align:right;">موبایل</th>
                    <th style="padding:0.5rem;text-align:right;">وضعیت</th>
                    <th style="padding:0.5rem;text-align:right;">ارسال</th>
                    <th style="padding:0.5rem;text-align:right;">امضا</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($contracts as $c): ?>
                    <tr>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;"><?= m360_intake_contract_h((string)$c['contract_id']) ?></td>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;"><?= m360_intake_contract_h((string)($c['jobcard_id'] ?: '-')) ?></td>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;"><?= m360_intake_contract_h((string)$c['mobile']) ?></td>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;"><?= m360_intake_contract_h(M360_CONTRACT_STATUS_LABELS_FA[$c['contract_status']] ?? $c['contract_status']) ?></td>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;"><?= m360_intake_contract_h(substr((string)$c['sent_at'], 0, 16)) ?></td>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;"><?= m360_intake_contract_h(substr((string)$c['signed_at'], 0, 16)) ?></td>
                        <td style="padding:0.5rem;border-top:1px solid #e5e7eb;"><a class="m360-contract-btn primary" href="erp-intake-contract-detail.php?contract_id=<?= (int)$c['contract_id'] ?>">جزئیات</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <nav class="w1c-card w1c-links">
        <a href="erp-reception-online-requests.php">درخواست‌های آنلاین</a>
        <a href="erp-jobcard-command-center.php">مرکز کارت کار</a>
    </nav>
</div>
</body>
</html>
