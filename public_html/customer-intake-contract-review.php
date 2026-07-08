<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';

$rawToken = trim((string)($_GET['t'] ?? $_GET['token'] ?? $_POST['t'] ?? $_POST['token'] ?? ''));
$flashMsg = trim((string)($_GET['msg'] ?? ''));
$flashOk = (string)($_GET['ok'] ?? '') === '1';
$error = '';
$request = null;
$payload = [];
$summary = [];
$contractText = '';
$otpVerified = false;
$accepted = false;
$canAccept = false;
$invalidMessage = 'مأموریت قرارداد مشتری یافت نشد یا منقضی شده است.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rawToken !== '') {
    $conn = customer_core_db();
    if ($conn === false) {
        $error = 'اتصال به پایگاه داده برقرار نشد.';
    } else {
        $result = m360_rw_intake_process_customer_contract_accept($conn, $rawToken, $_POST, $_SERVER);
        header('Location: customer-intake-contract-review.php?t=' . rawurlencode($rawToken)
            . '&msg=' . rawurlencode((string)$result['message'])
            . '&ok=' . ($result['ok'] ? '1' : '0'));
        exit;
    }
}

if ($rawToken !== '') {
    $requestId = m360_rw_intake_contract_parse_review_token($rawToken);
    $conn = customer_core_db();
    if ($conn === false) {
        $error = 'اتصال به پایگاه داده برقرار نشد.';
    } elseif ($requestId < 1) {
        $error = $invalidMessage;
    } else {
        $request = m360_online_req_fetch_by_id($conn, $requestId);
        if ($request === null) {
            $error = $invalidMessage;
        } else {
            $payload = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
            $payload = m360_rw_intake_payload_for_recovery($payload);
            $tokenCheck = m360_rw_intake_contract_validate_review_token($payload, $requestId, $rawToken);
            if (!$tokenCheck['ok']) {
                $error = $tokenCheck['error'];
            } else {
                $summary = m360_rw_intake_contract_review_summary($payload, $request);
                $contractText = trim((string)($payload['reception_intake']['contract']['contract_text'] ?? ''));
                if ($contractText === '') {
                    $contractText = m360_rw_intake_contract_template_text($request, $payload);
                }
                $otpVerified = m360_online_req_payload_otp_verified($request);
                $accepted = m360_rw_intake_contract_customer_accepted($payload);
                $canAccept = $otpVerified && !$accepted && !m360_rw_intake_is_locked($payload);
            }
        }
    }
} else {
    $error = $invalidMessage;
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کارتابل مشتری — مأموریت قرارداد پذیرش</title>
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
</head>
<body class="m360-public-shell m360-rw-page">
<div class="m360-wrap m360-rw-wrap">
    <header class="m360-rw-header">
        <h1 class="m360-rw-title">کارتابل مشتری</h1>
        <p class="m360-rw-subtitle">مأموریت قرارداد پذیرش — تأیید دیجیتال مشتری</p>
        <?php if ($flashMsg !== ''): ?>
            <div class="m360-rw-flash <?= $flashOk ? 'is-ok' : 'is-err' ?>"><?= m360_rw_h($flashMsg) ?></div>
        <?php endif; ?>
    </header>

    <?php if ($error !== ''): ?>
        <section class="m360-rw-alert"><?= m360_rw_h($error) ?></section>
    <?php else: ?>
        <section class="m360-rw-wizard-page-card">
            <h2 class="m360-rw-section-title">مأموریت قرارداد پذیرش</h2>
            <div class="m360-rw-field-grid">
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">مشتری</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['customer_name'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">موبایل</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['mobile'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">پلاک</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['plate'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">خودرو</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['brand_model'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">خدمات</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['service_route'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">عکس‌های پذیرش</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['photos'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">دیاگ</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['diagnostic'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">توافق هزینه</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['cost_agreement'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">احراز هویت OTP</span><span class="m360-rw-field-val"><?= $otpVerified ? 'تأیید شده' : 'تأیید نشده' ?></span></div>
            </div>

            <div class="m360-rw-contract-text"><?= m360_rw_h($contractText) ?></div>

            <?php if ($accepted): ?>
                <p class="m360-rw-flash is-ok">قرارداد پذیرش در کارتابل مشتری تأیید شد.</p>
            <?php elseif (!$otpVerified): ?>
                <p class="m360-rw-warn">ابتدا احراز هویت موبایل مشتری باید توسط OTP تکمیل شود.</p>
            <?php elseif ($canAccept): ?>
                <form class="m360-rw-form" method="post" action="customer-intake-contract-review.php">
                    <input type="hidden" name="t" value="<?= m360_rw_h($rawToken) ?>">
                    <label class="m360-rw-check-label m360-rw-confirm-check">
                        <input type="checkbox" name="customer_accepts_contract" value="1" required>
                        اطلاعات و شرایط قرارداد پذیرش را در کارتابل مشتری مطالعه کردم و می‌پذیرم.
                    </label>
                    <button type="submit" class="m360-rw-btn">تأیید قرارداد در کارتابل مشتری</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
