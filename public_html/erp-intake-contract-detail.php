<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-contract-signature-helper.php';

m360_intake_contract_require_staff();

$contractId = isset($_GET['contract_id']) ? (int)$_GET['contract_id'] : 0;
$flash = trim((string)($_GET['msg'] ?? ''));
$flashOk = (string)($_GET['ok'] ?? '') === '1';
$conn = customer_core_db();
$row = ($conn !== false && $contractId > 0) ? m360_intake_contract_fetch_by_id($conn, $contractId) : null;
$events = ($conn !== false && $row !== null) ? m360_intake_contract_events($conn, $contractId) : [];
$snapshot = $row !== null ? m360_intake_contract_snapshot_from_row($row) : [];
$html = $row !== null ? m360_contract_render_html($snapshot, true) : '';
$signed = $row !== null && m360_intake_contract_is_signed($row);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات قرارداد #<?= $contractId ?></title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-contract.css">
</head>
<body style="background:#f8fafc;margin:0;padding:1.25rem;">
<div class="w1c-wrap m360-contract-page">
    <header class="w1c-banner">
        <h1>جزئیات قرارداد پذیرش</h1>
        <p>شناسه <?= m360_intake_contract_h((string)$contractId) ?></p>
    </header>
    <?php if ($flash !== ''): ?>
        <div class="m360-contract-flash <?= $flashOk ? 'ok' : 'err' ?>"><?= m360_intake_contract_h($flash) ?></div>
    <?php endif; ?>
    <?php if ($row === null): ?>
        <section class="w1c-card"><p>قرارداد یافت نشد.</p></section>
    <?php else: ?>
        <section class="w1c-card">
            <p><strong>وضعیت:</strong> <?= m360_intake_contract_h(M360_CONTRACT_STATUS_LABELS_FA[$row['contract_status']] ?? $row['contract_status']) ?></p>
            <p><strong>موبایل:</strong> <?= m360_intake_contract_h($row['mobile']) ?></p>
            <p><strong>کارت کار:</strong> <?= m360_intake_contract_h($row['jobcard_id'] ?: '—') ?></p>
            <p><strong>Hash:</strong> <?= m360_intake_contract_h(substr($row['contract_body_hash'], 0, 16)) ?>…</p>
            <?php if (!$signed): ?>
                <div class="m360-contract-actions">
                    <form method="post" action="erp-intake-contract-send.php">
                        <?= erp_csrf_input(M360_CONTRACT_CSRF_PURPOSE) ?>
                        <input type="hidden" name="contract_id" value="<?= $contractId ?>">
                        <button type="submit" class="m360-contract-btn primary">ارسال لینک برای مشتری</button>
                    </form>
                </div>
                <p style="font-size:0.88rem;color:#71717a;">لینک امن فقط پس از تولید/ارسال در پیامک یا کانال امن به مشتری داده می‌شود.</p>
            <?php endif; ?>
        </section>
        <section class="w1c-card"><?= $html ?></section>
        <?php if ($events !== []): ?>
            <section class="w1c-card">
                <h2 style="font-size:1rem;">رویدادها</h2>
                <ul>
                    <?php foreach ($events as $e): ?>
                        <li><?= m360_intake_contract_h((string)$e['created_at'] . ' — ' . $e['event_name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endif; ?>
    <nav class="w1c-card w1c-links"><a href="erp-intake-contracts.php">بازگشت</a></nav>
</div>
</body>
</html>
