<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-customer-delivery-helper.php';

$token = trim((string)($_GET['token'] ?? ''));
$msg = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$ok = isset($_GET['ok']) && $_GET['ok'] === '1';
$resolved = m360_delivery_resolve_token($token);
$invoice = $resolved['invoice'] ?? null;
$readOnly = false;

if (is_array($invoice)) {
    $conn = customer_core_db();
    if ($conn !== false) {
        $readOnly = m360_delivery_is_confirmed($conn, (int)($invoice['jobcard_id'] ?? 0));
    }
}

function m360_del_sign_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>امضای تحویل خودرو</title>
    <link rel="stylesheet" href="assets/css/m360-estimate.css">
    <link rel="stylesheet" href="assets/css/m360-contract.css">
    <script src="assets/js/m360-signature-pad.js" defer></script>
    <script src="assets/js/m360-customer-delivery-sign.js" defer></script>
</head>
<body class="m360-est-page m360-est-customer" style="background:#f8fafc;margin:0;padding:1rem;">
<div class="m360-est-wrap m360-contract-page">
    <?php if ($msg !== ''): ?><div class="m360-est-flash <?= $ok ? 'ok' : 'err' ?>"><?= m360_del_sign_h($msg) ?></div><?php endif; ?>

    <?php if (!is_array($invoice) || !$resolved['ok']): ?>
        <div class="m360-est-flash err"><?= m360_del_sign_h($resolved['message'] ?: 'لینک نامعتبر است.') ?></div>
    <?php elseif ($readOnly): ?>
        <div class="m360-est-flash ok">تحویل با موفقیت تأیید شده — فقط مشاهده</div>
        <p>مبلغ نهایی: <?= m360_del_sign_h(number_format((float)($invoice['total_amount'] ?? 0))) ?> تومان</p>
    <?php else: ?>
        <h1 style="font-size:1.2rem;">امضای دیجیتال تحویل</h1>
        <p>مبلغ نهایی: <?= m360_del_sign_h(number_format((float)($invoice['total_amount'] ?? 0))) ?> تومان</p>
        <p>لطفاً در کادر زیر امضا کنید، تأییدهای الزامی را انتخاب کنید و کد پیامکی را وارد نمایید.</p>

        <canvas id="m360_signature_canvas" class="m360-contract-sign-canvas" aria-label="امضا"></canvas>
        <button type="button" id="m360_signature_clear" class="m360-contract-btn secondary">پاک کردن امضا</button>

        <form id="m360-delivery-sign-form">
            <input type="hidden" name="token" id="m360-delivery-token" value="<?= m360_del_sign_h($token) ?>">
            <input type="hidden" name="signature_data" id="m360_signature_data" value="">
            <div class="m360-contract-checklist">
                <label><input type="checkbox" name="confirm_vehicle" id="m360-del-c1" required> خودرو را مشاهده کردم</label>
                <label><input type="checkbox" name="confirm_services" id="m360-del-c2" required> خدمات و توضیحات نهایی را مشاهده کردم</label>
                <label><input type="checkbox" name="confirm_finance" id="m360-del-c3" required> وضعیت مالی/تسویه را مشاهده کردم</label>
                <label><input type="checkbox" name="confirm_terms" id="m360-del-c4" required> شرایط تحویل را می‌پذیرم</label>
            </div>
            <p class="m360-est-legal">اینجانب خودرو را پس از انجام خدمات، کنترل نهایی و اعلام وضعیت مالی، مشاهده و تحویل می‌گیرم. موارد باقی‌مانده یا توصیه‌های بعدی در سیستم ثبت شده است.</p>
            <div class="m360-contract-otp-row">
                <button type="button" id="m360-delivery-send-otp" class="m360-contract-btn secondary" data-token="<?= m360_del_sign_h($token) ?>">ارسال کد تأیید</button>
                <input type="text" name="otp_code" id="m360-delivery-otp" inputmode="numeric" maxlength="6" placeholder="کد ۶ رقمی" required>
                <button type="submit" class="m360-contract-btn primary">ثبت نهایی تحویل</button>
            </div>
            <p id="m360-delivery-otp-status" style="font-size:0.9rem;color:#52525b;"></p>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
