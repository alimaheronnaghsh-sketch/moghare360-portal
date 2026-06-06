<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('customer-login.php');
    }
    checkCsrf();

    $mobile = normalizeMobile((string)($_POST['mobile'] ?? ''));
    $purpose = (string)($_POST['purpose'] ?? '');
    if (!isValidMobile($mobile) || !validPurpose($purpose)) {
        flash('شماره موبایل یا نوع درخواست معتبر نیست.', 'bad');
        redirect('customer-login.php');
    }

    $pdo = getPdo();
    $rate = $pdo->prepare(
        'SELECT COUNT(*) FROM otp_verifications WHERE mobile = ? AND purpose = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
    );
    $rate->execute([$mobile, $purpose]);
    if ((int)$rate->fetchColumn() >= 3) {
        flash('برای این شماره بیش از حد مجاز کد ارسال شده است. لطفاً ۱۵ دقیقه بعد دوباره تلاش کنید.', 'bad');
        redirect('customer-login.php');
    }

    $otp = (string)random_int(10000, 99999);
    $expiresAt = (new DateTimeImmutable('+' . (int)$otpExpireMinutes . ' minutes'))->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare(
        'INSERT INTO otp_verifications (mobile, otp_code, purpose, expires_at, ip_address) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$mobile, $otp, $purpose, $expiresAt, currentIp()]);

    if ((bool)$useFakeOtp) {
        renderHeader('کد تایید تستی', 'حالت تست فعال است');
        ?>
        <main class="auth-wrap">
          <section class="card form-card">
            <h2>کد تایید تستی</h2>
            <p class="muted">در حالت تست، پیامک واقعی ارسال نمی‌شود.</p>
            <p class="otp-message">کد تایید تستی شما: <strong class="otp-code"><?= e($otp) ?></strong></p>
            <div class="action-row">
              <a class="btn primary" href="verify-otp.php?mobile=<?= urlencode($mobile) ?>&purpose=<?= urlencode($purpose) ?>">ادامه و ورود کد</a>
              <a class="btn ghost" href="customer-login.php">بازگشت</a>
            </div>
          </section>
        </main>
        <?php
        renderFooter();
        exit;
    }

    $sms = sendIppanelOtp($mobile, $otp);
    if (!($sms['ok'] ?? false)) {
        $message = 'کد تایید در پایگاه داده ثبت شد اما ارسال پیامک با خطا مواجه شد. لطفاً دوباره تلاش کنید.';
        if ($debug) {
            $rawResponse = $sms['decoded_response'] ?? $sms['response_body'] ?? $sms['error'] ?? 'UNKNOWN_ERROR';
            $debugText = is_string($rawResponse)
                ? $rawResponse
                : json_encode($rawResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $message .= ' | پاسخ IPPanel: ' . $debugText;
        }
        flash($message, 'bad');
    } else {
        flash('کد تایید برای شما ارسال شد.');
    }

    redirect('verify-otp.php?mobile=' . urlencode($mobile) . '&purpose=' . urlencode($purpose));
} catch (Throwable $e) {
    showErrorPage('خطا در ارسال کد تایید.', $e->getMessage());
}
