<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-estimate-approval-helper.php';

$conn = customer_core_db();
$token = trim((string)($_GET['token'] ?? ($_POST['token'] ?? '')));
$taskId = (int)($_GET['task_id'] ?? ($_POST['task_id'] ?? 0));
$msg = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$ok = isset($_GET['ok']) && $_GET['ok'] === '1';

$context = is_resource($conn)
    ? m360_estimate_resolve_customer_context($conn, ['token' => $token, 'task_id' => $taskId])
    : ['ok' => false, 'message' => 'سرویس در دسترس نیست.', 'estimate' => null, 'version' => null, 'items' => [], 'task' => null, 'entry_mode' => '', 'raw_token' => $token, 'jobcard' => null];

$est = $context['estimate'] ?? null;
$version = $context['version'] ?? null;
$readOnly = is_array($est) && is_array($version) && m360_estimate_is_customer_approved($est, $version);
$rejected = is_array($version) && strtoupper((string)($version['version_status'] ?? '')) === M360_EST_VERSION_STATUS_REJECTED;
$contentHash = is_array($version) ? (string)($version['content_hash'] ?? '') : '';
$sessionEntry = (string)($context['entry_mode'] ?? '') === M360_EST_APPROVAL_CHANNEL_SESSION;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !$readOnly && !$rejected && $context['ok']) {
    $decision = trim((string)($_POST['decision'] ?? 'approve'));
    $result = m360_estimate_customer_decision($context, [
        'decision' => $decision,
        'confirm_viewed' => $_POST['confirm_viewed'] ?? null,
        'confirm_amount' => $_POST['confirm_amount'] ?? null,
        'confirm_hidden' => $_POST['confirm_hidden'] ?? null,
        'otp_code' => trim((string)($_POST['otp_code'] ?? '')),
        'reject_reason' => trim((string)($_POST['reject_reason'] ?? '')),
        'customer_note' => trim((string)($_POST['reject_reason'] ?? '')),
        'content_hash' => trim((string)($_POST['content_hash'] ?? $contentHash)),
        'erp_csrf_token' => $_POST['erp_csrf_token'] ?? null,
    ]);
    $redirectQuery = ['msg' => $result['message'], 'ok' => $result['ok'] ? '1' : '0'];
    if ($taskId > 0) {
        $redirectQuery['task_id'] = (string)$taskId;
    }
    if ($token !== '') {
        $redirectQuery['token'] = $token;
    }
    header('Location: customer-estimate-approval-sign.php?' . http_build_query($redirectQuery));
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
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <script src="assets/js/m360-estimate-approval.js" defer></script>
    <script>
    (function () {
        var btn = document.getElementById('m360-est-send-otp');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var payload = {
                token: btn.getAttribute('data-token') || '',
                task_id: parseInt(btn.getAttribute('data-task-id') || '0', 10) || 0,
                content_hash: btn.getAttribute('data-content-hash') || ''
            };
            btn.disabled = true;
            fetch('api/customer/estimate-send-otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            }).then(function (r) { return r.json(); }).then(function (j) {
                alert(j.message || (j.ok ? 'ارسال شد' : 'خطا'));
                btn.disabled = false;
            }).catch(function () {
                alert('خطا در ارسال کد');
                btn.disabled = false;
            });
        });
    })();
    </script>
</head>
<body class="m360-est-page m360-est-customer">
<div class="m360-est-wrap">
    <?php if ($msg !== ''): ?><div class="m360-est-flash <?= $ok ? 'ok' : 'err' ?>"><?= m360_estimate_h($msg) ?></div><?php endif; ?>

    <?php if (!$context['ok']): ?>
        <div class="m360-est-flash err"><?= m360_estimate_h((string)$context['message']) ?></div>
    <?php elseif ($readOnly): ?>
        <div class="m360-est-flash ok">برآورد تأیید شده است — فقط مشاهده</div>
    <?php elseif ($rejected): ?>
        <div class="m360-est-flash err">برآورد رد شده است.</div>
    <?php else: ?>
        <h1>تأیید نهایی برآورد</h1>
        <p>نسخه <?= m360_estimate_h((string)($version['version_number'] ?? '')) ?> — مبلغ کل: <?= m360_estimate_h(number_format((float)($version['total_amount'] ?? 0))) ?> تومان</p>
        <p class="m360-lux-warn">این گیت فقط توسط مشتری و با OTP معتبر انجام می‌شود. پرسنل یا مالک سیستم نمی‌توانند به جای مشتری تأیید کنند.</p>
        <form method="post" id="m360-est-approval-form">
            <?php if ($sessionEntry): ?><?= m360_estimate_approval_csrf_input() ?><?php endif; ?>
            <input type="hidden" name="content_hash" value="<?= m360_estimate_h($contentHash) ?>">
            <?php if ($taskId > 0): ?><input type="hidden" name="task_id" value="<?= (int)$taskId ?>"><?php endif; ?>
            <?php if ($token !== ''): ?><input type="hidden" name="token" value="<?= m360_estimate_h($token) ?>"><?php endif; ?>
            <label><input type="checkbox" name="confirm_viewed" required> برآورد را مشاهده کردم</label><br>
            <label><input type="checkbox" name="confirm_amount" required> مبلغ کل را می‌پذیرم</label><br>
            <label><input type="checkbox" name="confirm_hidden" required> شرایط تغییر هزینه در صورت ایرادات پنهان را می‌پذیرم</label>
            <div class="m360-est-otp-block">
                <button type="button" class="m360-est-btn secondary" id="m360-est-send-otp"
                    data-token="<?= m360_estimate_h($token) ?>"
                    data-task-id="<?= (int)$taskId ?>"
                    data-content-hash="<?= m360_estimate_h($contentHash) ?>">ارسال کد تأیید</button>
                <label>کد تأیید</label>
                <input class="m360-est-input" name="otp_code" id="m360-est-otp" inputmode="numeric" autocomplete="one-time-code">
            </div>
            <input type="hidden" name="decision" value="approve">
            <button type="submit" class="m360-est-btn">تأیید برآورد</button>
        </form>
        <form method="post" style="margin-top:1.5rem">
            <?php if ($sessionEntry): ?><?= m360_estimate_approval_csrf_input() ?><?php endif; ?>
            <input type="hidden" name="content_hash" value="<?= m360_estimate_h($contentHash) ?>">
            <?php if ($taskId > 0): ?><input type="hidden" name="task_id" value="<?= (int)$taskId ?>"><?php endif; ?>
            <?php if ($token !== ''): ?><input type="hidden" name="token" value="<?= m360_estimate_h($token) ?>"><?php endif; ?>
            <input type="hidden" name="decision" value="reject">
            <label>یادداشت مشتری (اختیاری)</label>
            <textarea class="m360-est-input" name="reject_reason" maxlength="1000"></textarea>
            <p class="m360-est-legal">رد برآورد ممکن است ادامه فرایند تعمیر را متوقف کند.</p>
            <button type="submit" class="m360-est-btn danger">رد برآورد</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
