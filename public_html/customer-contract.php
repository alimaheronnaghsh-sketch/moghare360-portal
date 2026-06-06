<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

function loadContractRequest(string $mobile, int $requestId): ?array
{
    $stmt = getPdo()->prepare(
        'SELECT * FROM portal_service_requests_staging WHERE id = ? AND mobile = ? LIMIT 1'
    );
    $stmt->execute([$requestId, $mobile]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function loadLatestContractRecord(string $mobile, int $requestId): ?array
{
    try {
        $columns = getTableColumns('portal_contract_confirmations');
        if (!$columns) {
            return null;
        }
        $stmt = getPdo()->prepare(
            'SELECT * FROM portal_contract_confirmations WHERE service_request_id = ? AND mobile = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$requestId, $mobile]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function customerDisplayName(string $mobile): string
{
    $customer = getCustomerByMobile($mobile);
    if (!is_array($customer)) {
        return 'مشتری محترم';
    }
    $first = trim((string)($customer['first_name'] ?? ''));
    $last = trim((string)($customer['last_name'] ?? ''));
    $full = trim((string)($customer['full_name'] ?? ''));
    if ($full === '' && ($first !== '' || $last !== '')) {
        $full = trim($first . ' ' . $last);
    }
    return $full !== '' ? $full : 'مشتری محترم';
}

function requestCostRangeText(array $request): string
{
    $min = trim((string)($request['estimated_cost_min'] ?? $request['estimated_min_cost'] ?? ''));
    $max = trim((string)($request['estimated_cost_max'] ?? $request['estimated_max_cost'] ?? ''));
    if ($min !== '' || $max !== '') {
        $minText = $min !== '' ? number_format((float)$min) : '-';
        $maxText = $max !== '' ? number_format((float)$max) : '-';
        return $minText . ' تا ' . $maxText . ' تومان';
    }

    $range = trim((string)($request['cost_range'] ?? $request['estimated_cost_range'] ?? ''));
    if ($range !== '') {
        return $range;
    }

    return 'پس از کارشناسی اعلام می‌شود';
}

function requestPrepaymentText(array $request): string
{
    $value = trim((string)($request['prepayment_amount'] ?? $request['down_payment'] ?? $request['deposit_amount'] ?? ''));
    if ($value === '') {
        return 'در انتظار تعیین';
    }
    return number_format((float)$value) . ' تومان';
}

function requestVipStatusText(string $mobile): string
{
    $customer = getCustomerByMobile($mobile);
    if (!is_array($customer)) {
        return 'عادی';
    }
    $vip = trim((string)($customer['vip_status'] ?? $customer['customer_tier'] ?? $customer['customer_type'] ?? ''));
    if ($vip === '') {
        return 'عادی';
    }
    return $vip;
}

function parseAcceptedTerms(?array $record): array
{
    if (!is_array($record)) {
        return [];
    }
    $json = trim((string)($record['accepted_terms_json'] ?? ''));
    if ($json === '') {
        return [];
    }
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}

function pickTermValue(array $terms, string $key, string $fallback = ''): string
{
    $value = $terms[$key] ?? $fallback;
    return trim((string)$value);
}

try {
    $mobile = requireCustomerLogin();
    $requestId = (int)($_GET['request_id'] ?? 0);
    if ($requestId <= 0) {
        flash('ابتدا درخواست پذیرش خودرو را ثبت کنید.', 'bad');
        redirect('customer-service-request.php');
    }

    $request = loadContractRequest($mobile, $requestId);
    if (!is_array($request)) {
        flash('پرونده پذیرش موردنظر پیدا نشد.', 'bad');
        redirect('customer-request-status.php');
    }

    $contractRecord = loadLatestContractRecord($mobile, $requestId);
    $terms = parseAcceptedTerms($contractRecord);

    $requestCode = trim((string)($request['jobcard_code'] ?? ('REQ-' . $requestId)));
    $serviceType = trim((string)($request['service_type'] ?? 'در حال کارشناسی'));
    $vehicleName = trim((string)($request['vehicle_brand'] ?? '') . ' ' . (string)($request['vehicle_model'] ?? ''));
    $customerName = customerDisplayName($mobile);
    $costRange = requestCostRangeText($request);
    $prepayment = requestPrepaymentText($request);
    $vipStatus = requestVipStatusText($mobile);

    $status = strtoupper(trim((string)($contractRecord['contract_status'] ?? '')));
    $alreadySigned = ($status === 'ONLINE_SIGNED');
    $hasOtpPending = ($status === 'OTP_SENT' || $status === 'OTP_PENDING');
    $contractId = (int)($contractRecord['id'] ?? 0);

    $viewedAt = trim((string)($contractRecord['contract_viewed_at'] ?? ''));
    $closedAt = trim((string)($contractRecord['contract_view_closed_at'] ?? ''));
    $canAnswerQuestions = ($closedAt !== '');

renderHeader('قرارداد آنلاین پذیرش خودرو', 'تایید حقوقی مشتری');
renderFlashes();
?>
<main class="auth-wrap wide-auth contract-page contract-flow">
  <section class="card contract-summary-card">
    <h2>قرارداد آنلاین پذیرش خودرو</h2>
    <p class="muted">
      لطفاً ابتدا متن قرارداد را مشاهده کنید. پس از مطالعه و تأیید متن قرارداد، مرحله امضا فعال می‌شود.
    </p>

    <div class="contract-summary-grid">
      <article class="summary-item">
        <span>نام مشتری</span>
        <strong><?= e($customerName) ?></strong>
      </article>

      <article class="summary-item">
        <span>خودرو</span>
        <strong><?= e($vehicleName !== '' ? $vehicleName : '-') ?></strong>
      </article>

      <article class="summary-item">
        <span>کد پرونده</span>
        <strong class="tracking-code"><?= e($requestCode) ?></strong>
      </article>

      <article class="summary-item">
        <span>نوع خدمت</span>
        <strong><?= e($serviceType) ?></strong>
      </article>

      <article class="summary-item">
        <span>محدوده هزینه</span>
        <strong><?= e($costRange) ?></strong>
      </article>

      <article class="summary-item">
        <span>علی‌الحساب</span>
        <strong><?= e($prepayment) ?></strong>
      </article>

      <article class="summary-item">
        <span>وضعیت مشتری</span>
        <strong><?= e($vipStatus) ?></strong>
      </article>

      <article class="summary-item">
        <span>شماره موبایل</span>
        <strong class="mobile-field"><?= e($mobile) ?></strong>
      </article>
    </div>
  </section>

  <?php if ($alreadySigned): ?>
    <section class="card contract-success-card">
      <h2>قرارداد آنلاین نهایی شد</h2>
      <p>این قرارداد قبلاً با موفقیت امضا و تأیید شده است.</p>

      <div class="action-row">
        <a class="btn primary" href="customer-request-status.php?request_id=<?= e((string)$requestId) ?>">مشاهده وضعیت پرونده</a>
        <a class="btn ghost" href="customer-profile.php?mode=dashboard">بازگشت</a>
        <a class="btn danger" href="customer-logout.php">خروج از حساب کاربری</a>
      </div>
    </section>
  <?php else: ?>

    <section class="card contract-viewer-card">
      <h2>متن رسمی قرارداد</h2>
      <p class="muted">
        برای ادامه، متن قرارداد را باز کنید. فقط پس از تیک مطالعه قرارداد و بستن تأییدی، مرحله امضا فعال می‌شود.
      </p>

      <div class="action-row">
        <button type="button" class="btn primary large-btn" id="openContractViewer">
          مشاهده متن قرارداد
        </button>

        <a class="btn secondary" target="_blank" rel="noopener" href="contract-template-intake.php?request_id=<?= e((string)$requestId) ?>">
          نمای رسمی در صفحه جداگانه
        </a>
      </div>

      <p class="muted contract-view-state" id="contractViewState">
        هنوز متن قرارداد با تأیید مطالعه بسته نشده است.
      </p>
    </section>

    <?php if ($hasOtpPending && $contractId > 0): ?>
      <section class="card">
        <h3>کد تأیید قبلاً ارسال شده است</h3>
        <p class="muted">
          در صورت دریافت کد، تأیید نهایی را ادامه دهید. در غیر این صورت کد جدید بگیرید.
        </p>

        <div class="action-row">
          <a class="btn primary" href="verify-contract-otp.php?request_id=<?= e((string)$requestId) ?>&contract_id=<?= e((string)$contractId) ?>">
            ادامه تأیید OTP
          </a>
        </div>
      </section>
    <?php endif; ?>

    <form class="card form-card contract-form" method="post" action="send-contract-otp.php" id="contractAgreementForm">
      <?= csrfField() ?>

      <input type="hidden" name="request_id" value="<?= e((string)$requestId) ?>">
      <input type="hidden" name="contract_viewed_at" id="contractViewedAt" value="">
      <input type="hidden" name="contract_read_confirmed" id="contractReadConfirmed" value="1">
      <input type="hidden" name="contract_read_confirmed_at" id="contractReadConfirmedAt" value="">
      <input type="hidden" name="signature_data" id="signatureData" value="">

      <input type="hidden" name="hidden_fault_accepted" id="hiddenFaultAccepted" value="">
      <input type="hidden" name="insurance_option" id="insuranceOption" value="">
      <input type="hidden" name="insurance_warning_accepted" id="insuranceWarningAccepted" value="0">
      <input type="hidden" name="purchase_limit_option" id="purchaseLimitOption" value="">
      <input type="hidden" name="ui_version" value="contract_ui_v2">

      <section id="signatureBox" class="contract-signature-box hidden">
        <h2>امضا و تأیید اولیه قرارداد</h2>

        <p class="muted">
          متن قرارداد توسط شما مشاهده و تأیید شد. لطفاً اطلاعات امضا را تکمیل کنید.
        </p>

        <label>
          نام و نام خانوادگی جهت امضا
          <input id="typedSignature" name="typed_signature" required value="<?= e($customerName) ?>" placeholder="نام کامل فارسی">
        </label>

        <label>
          کد ملی امضاکننده
          <input id="signedNationalCode" class="national-code-field input-number" name="signed_national_code" inputmode="numeric" maxlength="10" required>
        </label>

        <label>
          امضای دیجیتال
          <canvas id="signaturePad" class="signature-pad" width="650" height="180"></canvas>
        </label>

        <div class="action-row">
          <button class="btn ghost" type="button" id="clearSignatureBtn">پاک کردن امضا</button>
          <span class="muted tiny-text">زمان امضا در لحظه ارسال نهایی ثبت می‌شود.</span>
        </div>

        <div class="action-row">
          <button class="btn primary" type="button" id="startQuestionsBtn">
            ثبت امضا و ادامه تأییدهای نهایی
          </button>

          <a class="btn ghost" href="customer-request-status.php?request_id=<?= e((string)$requestId) ?>">بازگشت</a>
          <a class="btn danger" href="customer-logout.php">خروج از حساب کاربری</a>
        </div>
      </section>
    </form>
  <?php endif; ?>
</main>

<section class="modal-overlay" id="contractViewerModal" aria-hidden="true">
  <div class="modal-card contract-modal-card">
    <div class="contract-modal-header">
      <h3>متن رسمی قرارداد پذیرش</h3>
      <button type="button" class="btn ghost" id="closeContractViewer">
        بستن بدون تأیید
      </button>
    </div>

    <iframe
      src="contract-template-intake.php?request_id=<?= e((string)$requestId) ?>"
      title="قرارداد رسمی پذیرش"
      class="contract-viewer-frame"
      loading="lazy"
    ></iframe>

    <div class="contract-read-confirm-box">
      <label class="checkbox-line">
        <input type="checkbox" id="contractReadConfirm">
        متن کامل قرارداد را مطالعه کردم و با آگاهی درخواست ادامه فرآیند امضا را دارم.
      </label>

      <button type="button" class="btn primary" id="closeAfterReadBtn" disabled>
        متن قرارداد را مطالعه کردم و می‌بندم
      </button>

      <p class="muted tiny-text">
        بدون تأیید مطالعه قرارداد، مرحله امضا فعال نمی‌شود.
      </p>
    </div>
  </div>
</section>

<section class="modal-overlay" id="contractQuestionModal" aria-hidden="true">
  <div class="modal-card contract-modal-card contract-question-modal">
    <div class="contract-modal-header">
      <h3 id="contractQuestionTitle">سؤال تأییدی</h3>
    </div>

    <div id="contractQuestionBody" class="contract-question-body"></div>

    <div id="contractQuestionActions" class="question-actions"></div>
  </div>
</section>

<script src="contract-flow.js"></script>
<?php
