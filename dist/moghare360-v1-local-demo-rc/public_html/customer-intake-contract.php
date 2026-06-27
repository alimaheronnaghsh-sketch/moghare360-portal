<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-contract-signature-helper.php';

$token = trim((string)($_GET['token'] ?? ''));
$resolved = m360_contract_resolve_token($token);
$error = !$resolved['ok'];
$contract = $resolved['contract'] ?? null;

if (!$error && is_array($contract)) {
    $conn = customer_core_db();
    if ($conn !== false) {
        m360_intake_contract_mark_viewed($conn, (int)$contract['contract_id']);
    }
}

$snapshot = is_array($contract) ? m360_intake_contract_snapshot_from_row($contract) : [];
$html = is_array($contract) ? m360_contract_render_html($snapshot, true) : '';
$signed = is_array($contract) && m360_intake_contract_is_signed($contract);
$signUrl = $token !== '' ? m360_intake_contract_sign_url($token) : '#';

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قرارداد پذیرش خودرو</title>
    <link rel="stylesheet" href="assets/css/m360-contract.css">
    <link rel="stylesheet" href="assets/css/mirror.css">
</head>
<body style="background:#f4f6f5;margin:0;padding:1rem;">
<div class="m360-contract-page">
    <?php if ($error): ?>
        <div class="m360-contract-flash err"><?= m360_intake_contract_h($resolved['message']) ?></div>
    <?php elseif ($signed): ?>
        <div class="m360-contract-flash ok">این قرارداد قبلاً امضا شده است.</div>
        <?= $html ?>
    <?php else: ?>
        <div class="m360-contract-flash ok">لطفاً متن قرارداد را با دقت مطالعه کنید.</div>
        <?= $html ?>
        <div class="m360-contract-actions">
            <a class="m360-contract-btn primary" href="<?= m360_intake_contract_h($signUrl) ?>">قرارداد را مطالعه کردم و تأیید می‌کنم</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
