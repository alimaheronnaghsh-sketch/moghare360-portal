<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

function loadContractOtpRecord(string $mobile, int $requestId, int $contractId): ?array
{
    $stmt = getPdo()->prepare(
        'SELECT * FROM portal_contract_confirmations WHERE id = ? AND service_request_id = ? AND mobile = ? LIMIT 1'
    );
    $stmt->execute([$contractId, $requestId, $mobile]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function loadRequestMini(string $mobile, int $requestId): ?array
{
    $stmt = getPdo()->prepare(
        'SELECT * FROM portal_service_requests_staging WHERE id = ? AND mobile = ? LIMIT 1'
    );
    $stmt->execute([$requestId, $mobile]);
    $row = $stmt->fetch();
    return $row ?: null;
}

try {
    $mobile = requireCustomerLogin();
    $requestId = (int)($_GET['request_id'] ?? 0);
    $contractId = (int)($_GET['contract_id'] ?? 0);

    if ($requestId <= 0 || $contractId <= 0) {
        flash('شناسه تایید قرارداد معتبر نیست.', 'bad');
        redirect('customer-request-status.php');
    }

    $request = loadRequestMini($mobile, $requestId);
    if (!is_array($request)) {
        flash('پرونده خدمت پیدا نشد.', 'bad');
        redirect('customer-request-status.php');
    }

    $record = loadContractOtpRecord($mobile, $requestId, $contractId);
    if (!is_array($record)) {
        flash('رکورد تایید قرارداد پیدا نشد.', 'bad');
        redirect('customer-contract.php?request_id=' . $requestId);
    }

    $status = strtoupper(trim((string)($record['contract_status'] ?? '')));
    if ($status === 'ONLINE_SIGNED') {
        flash('این قرارداد قبلاً نهایی شده است.');
        redirect('customer-request-status.php?request_id=' . $requestId);
    }

    renderHeader('تایید OTP قرارداد', 'تکمیل امضای آنلاین');
    renderFlashes();
    ?>
    <main class="auth-wrap wide-auth">
      <section class="card">
        <h2>تایید نهایی قرارداد آنلاین</h2>
        <p class="muted">
          کد پرونده:
          <strong class="tracking-code"><?= e((string)($request['jobcard_code'] ?? ('REQ-' . $requestId))) ?></strong>
          |
          خودرو:
          <?= e((string)($request['vehicle_brand'] ?? '')) ?>
          <?= e((string)($request['vehicle_model'] ?? '')) ?>
        </p>
        <p class="muted">کد تایید به شماره <strong class="mobile-field"><?= e($mobile) ?></strong> ارسال شده است.</p>
      </section>

      <form class="card form-card compact-form" method="post" action="submit-contract-confirmation.php">
        <?= csrfField() ?>
        <input type="hidden" name="request_id" value="<?= e((string)$requestId) ?>">
        <input type="hidden" name="contract_id" value="<?= e((string)$contractId) ?>">
        <label>کد تایید قرارداد
          <input class="otp-code-input input-number" name="otp_code" inputmode="numeric" maxlength="5" required placeholder="کد 5 رقمی">
        </label>
        <div class="action-row">
          <button class="btn primary" type="submit">ثبت نهایی قرارداد</button>
          <a class="btn ghost" href="customer-contract.php?request_id=<?= e((string)$requestId) ?>">بازگشت</a>
          <a class="btn danger" href="customer-logout.php">خروج از حساب کاربری</a>
        </div>
      </form>

      <form class="card form-card compact-form" method="post" action="send-contract-otp.php">
        <?= csrfField() ?>
        <input type="hidden" name="request_id" value="<?= e((string)$requestId) ?>">
        <input type="hidden" name="resend_contract_id" value="<?= e((string)$contractId) ?>">
        <button class="btn secondary" type="submit">ارسال مجدد کد تایید</button>
      </form>
    </main>
    <?php
    renderFooter();
} catch (Throwable $e) {
    showErrorPage('خطا در نمایش صفحه تایید OTP قرارداد.', $e->getMessage());
}
