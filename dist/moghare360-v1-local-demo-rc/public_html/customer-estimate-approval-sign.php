<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-estimate-approval-helper.php';

$token = trim((string)($_GET['token'] ?? ''));
$msg = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$ok = isset($_GET['ok']) && $_GET['ok'] === '1';
$resolved = m360_estimate_resolve_token($token);
$est = $resolved['estimate'] ?? null;
$readOnly = is_array($est) && m360_estimate_is_customer_approved($est);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !$readOnly) {
    $decision = trim((string)($_POST['decision'] ?? 'approve'));
    $result = m360_estimate_customer_decision(
        is_array($est) ? $est : [],
        $token,
        $decision,
        isset($_POST['confirm_viewed']),
        isset($_POST['confirm_amount']),
        isset($_POST['confirm_hidden']),
        trim((string)($_POST['otp_code'] ?? '')),
        trim((string)($_POST['reject_reason'] ?? ''))
    );
    header('Location: customer-estimate-approval-sign.php?token=' . rawurlencode($token) . '&msg=' . rawurlencode($result['message']) . '&ok=' . ($result['ok'] ? '1' : '0'));
    exit;
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأیید برآورد</title>
    <link rel="stylesheet" href="assets/css/m360-estimate.css">
    <script src="assets/js/m360-estimate-approval.js" defer></script>
</head>
<body class="m360-est-page m360-est-customer">
<div class="m360-est-wrap">
    <?php if ($msg !== ''): ?><div class="m360-est-flash <?= $ok ? 'ok' : 'err' ?>"><?= m360_estimate_h($msg) ?></div><?php endif; ?>

    <?php if (!is_array($est)): ?>
        <div class="m360-est-flash err"><?= m360_estimate_h($resolved['message'] ?: 'لینک نامعتبر است.') ?></div>
    <?php elseif ($readOnly): ?>
        <div class="m360-est-flash ok">برآورد تأیید شده — فقط مشاهده</div>
    <?php else: ?>
        <h1>تأیید نهایی برآورد</h1>
        <p>مبلغ کل: <?= m360_estimate_h(number_format((float)($est['total_amount'] ?? 0))) ?> تومان</p>
        <form method="post" id="m360-est-approval-form">
            <label><input type="checkbox" name="confirm_viewed" required> برآورد را مشاهده کردم</label><br>
            <label><input type="checkbox" name="confirm_amount" required> مبلغ کل و علی‌الحساب را می‌پذیرم</label><br>
            <label><input type="checkbox" name="confirm_hidden" required> شرایط تغییر هزینه در صورت ایرادات پنهان را می‌پذیرم</label>
            <div class="m360-est-otp-block">
                <button type="button" class="m360-est-btn secondary" id="m360-est-send-otp" data-token="<?= m360_estimate_h($token) ?>">ارسال کد تأیید</button>
                <label>کد تأیید</label>
                <input class="m360-est-input" name="otp_code" id="m360-est-otp" inputmode="numeric" autocomplete="one-time-code">
            </div>
            <input type="hidden" name="decision" value="approve">
            <button type="submit" class="m360-est-btn">تأیید برآورد</button>
        </form>
        <form method="post" style="margin-top:1.5rem">
            <input type="hidden" name="decision" value="reject">
            <label>دلیل رد</label>
            <textarea class="m360-est-input" name="reject_reason" required minlength="5"></textarea>
            <button type="submit" class="m360-est-btn danger">رد برآورد</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
