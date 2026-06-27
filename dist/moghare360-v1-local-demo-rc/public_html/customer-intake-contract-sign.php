<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-contract-signature-helper.php';

$token = trim((string)($_GET['token'] ?? ''));
$resolved = m360_contract_resolve_token($token);
$error = !$resolved['ok'];
$contract = $resolved['contract'] ?? null;
$signed = is_array($contract) && m360_intake_contract_is_signed($contract);
$contractId = is_array($contract) ? (int)$contract['contract_id'] : 0;
$flash = trim((string)($_GET['msg'] ?? ''));
$flashOk = (string)($_GET['ok'] ?? '') === '1';

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>امضای قرارداد پذیرش</title>
    <link rel="stylesheet" href="assets/css/m360-contract.css">
</head>
<body style="background:#f8fafc;margin:0;padding:1rem;">
<div class="m360-contract-page">
    <?php if ($flash !== ''): ?>
        <div class="m360-contract-flash <?= $flashOk ? 'ok' : 'err' ?>"><?= m360_intake_contract_h($flash) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="m360-contract-flash err"><?= m360_intake_contract_h($resolved['message']) ?></div>
    <?php elseif ($signed): ?>
        <div class="m360-contract-flash ok">قرارداد شما با موفقیت امضا شد.</div>
    <?php else: ?>
        <h1 style="font-size:1.2rem;">امضای دیجیتال قرارداد</h1>
        <p>لطفاً در کادر زیر امضا کنید، تأییدهای الزامی را انتخاب کنید و کد پیامکی را وارد نمایید.</p>
        <canvas id="m360_signature_canvas" class="m360-contract-sign-canvas" aria-label="امضا"></canvas>
        <button type="button" id="m360_signature_clear" class="m360-contract-btn secondary">پاک کردن امضا</button>
        <form id="m360_sign_form" method="post" action="api/customer/contract-sign.php">
            <input type="hidden" name="token" value="<?= m360_intake_contract_h($token) ?>">
            <input type="hidden" name="signature_data" id="m360_signature_data" value="">
            <div class="m360-contract-checklist">
                <label><input type="checkbox" name="confirm_read" value="1" required> قرارداد را کامل مطالعه کردم</label>
                <label><input type="checkbox" name="confirm_info" value="1" required> اطلاعات پذیرش و خودرو را تأیید می‌کنم</label>
                <label><input type="checkbox" name="confirm_otp_terms" value="1" required> شرایط OTP و امضای آنلاین را می‌پذیرم</label>
            </div>
            <div class="m360-contract-otp-row">
                <button type="button" id="m360_send_contract_otp" class="m360-contract-btn secondary">ارسال کد تأیید</button>
                <input type="text" name="otp_code" id="m360_otp_code" inputmode="numeric" maxlength="6" placeholder="کد ۶ رقمی" required>
                <button type="submit" class="m360-contract-btn primary">ثبت نهایی امضا</button>
            </div>
            <p id="m360_otp_status" style="font-size:0.9rem;color:#52525b;"></p>
        </form>
        <script src="assets/js/m360-signature-pad.js?v=p15"></script>
        <script>
        (function () {
            var btn = document.getElementById('m360_send_contract_otp');
            var status = document.getElementById('m360_otp_status');
            if (!btn) return;
            btn.addEventListener('click', function () {
                status.textContent = 'در حال ارسال...';
                fetch('api/customer/contract-send-otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ token: <?= json_encode($token, JSON_UNESCAPED_UNICODE) ?> })
                }).then(function (r) { return r.json(); }).then(function (data) {
                    status.textContent = data.message || (data.ok ? 'کد ارسال شد.' : 'خطا');
                }).catch(function () {
                    status.textContent = 'خطا در ارتباط با سرور.';
                });
            });
        })();
        </script>
    <?php endif; ?>
</div>
</body>
</html>
