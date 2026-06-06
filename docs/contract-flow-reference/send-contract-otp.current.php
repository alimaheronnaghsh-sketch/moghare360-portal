<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

function loadRequestForContractOtp(string $mobile, int $requestId): ?array
{
    $stmt = getPdo()->prepare(
        'SELECT * FROM portal_service_requests_staging WHERE id = ? AND mobile = ? LIMIT 1'
    );
    $stmt->execute([$requestId, $mobile]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function loadContractRowByIdForCustomer(string $mobile, int $contractId, int $requestId): ?array
{
    $stmt = getPdo()->prepare(
        'SELECT * FROM portal_contract_confirmations WHERE id = ? AND service_request_id = ? AND mobile = ? LIMIT 1'
    );
    $stmt->execute([$contractId, $requestId, $mobile]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function loadLatestContractRowForCustomer(string $mobile, int $requestId): ?array
{
    $stmt = getPdo()->prepare(
        'SELECT * FROM portal_contract_confirmations WHERE service_request_id = ? AND mobile = ? ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$requestId, $mobile]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function resolveCustomerNameForContractOtp(string $mobile): string
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

function isValidNationalCodeValue(string $value): bool
{
    return preg_match('/^[0-9]{10}$/', $value) === 1;
}

function ensureContractSnapshotDirectory(string $relativeDir): string
{
    $relativeDir = trim($relativeDir, '/');
    $absoluteDir = __DIR__ . '/' . $relativeDir;
    if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0755, true);
    }

    $htaccess = $absoluteDir . '/.htaccess';
    if (!is_file($htaccess)) {
        file_put_contents(
            $htaccess,
            "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\n  Require all denied\n</FilesMatch>\n"
        );
    }
    return $absoluteDir;
}

function createImmutableContractSnapshot(
    array $request,
    string $mobile,
    string $customerName,
    array $terms,
    string $contractVersion
): string {
    $yearDir = date('Y');
    $relativeDir = 'contracts/intake/' . $yearDir;
    $absoluteDir = ensureContractSnapshotDirectory($relativeDir);

    $requestId = (int)($request['id'] ?? 0);
    $requestCode = trim((string)($request['jobcard_code'] ?? ('REQ-' . $requestId)));
    $vehicle = trim((string)($request['vehicle_brand'] ?? '') . ' ' . (string)($request['vehicle_model'] ?? ''));
    $serviceType = trim((string)($request['service_type'] ?? 'در حال کارشناسی'));
    $plate = trim((string)($request['plate_number'] ?? '-'));
    $riskFlag = trim((string)($terms['risk_flag'] ?? 'NONE'));
    $partsLimit = trim((string)($terms['legal_q3_limit_amount'] ?? ''));

    $snapshotHtml = '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Contract Snapshot - ' . e($requestCode) . '</title>'
        . '<style>body{margin:0;padding:24px;background:#f8faf9;color:#112018;font-family:"Vazirmatn","Estedad","Shabnam",Tahoma,sans-serif;line-height:1.9}'
        . '.sheet{max-width:900px;margin:0 auto;background:#fff;border:1px solid #dbe8e2;border-radius:10px;padding:24px}'
        . 'h1{margin:0 0 10px;color:#0e3d2f}.meta{margin:0 0 14px;color:#3e5d52}.box{margin-top:12px;padding:12px;border:1px solid #dbe8e2;border-radius:8px;background:#f7fbf9}'
        . 'ul{margin:8px 0;padding-right:18px}li{margin-bottom:6px}</style></head><body><article class="sheet">'
        . '<h1>نسخه نهایی قرارداد پذیرش خودرو</h1>'
        . '<p class="meta">نسخه قرارداد: <strong>' . e($contractVersion) . '</strong> | زمان ثبت نهایی: <strong>' . e(date('Y-m-d H:i:s')) . '</strong></p>'
        . '<section class="box"><p>کد پرونده: <strong>' . e($requestCode) . '</strong></p>'
        . '<p>نام مشتری: <strong>' . e($customerName) . '</strong></p><p>موبایل: <strong>' . e($mobile) . '</strong></p>'
        . '<p>خودرو: <strong>' . e($vehicle !== '' ? $vehicle : '-') . '</strong></p>'
        . '<p>پلاک: <strong>' . e($plate !== '' ? $plate : '-') . '</strong></p>'
        . '<p>نوع خدمت: <strong>' . e($serviceType) . '</strong></p></section>'
        . '<section class="box"><h2>تاییدات مشتری</h2><ul>'
        . '<li>تایید خرابی‌های پنهان: <strong>' . (trim((string)($terms['legal_q1_accept'] ?? '')) === '1' ? 'بله' : 'خیر') . '</strong></li>'
        . '<li>وضعیت بیمه بدنه: <strong>' . e((string)($terms['legal_q2_insurance_label'] ?? '-')) . '</strong></li>'
        . '<li>سیاست خرید قطعه: <strong>' . e((string)($terms['legal_q3_parts_policy_label'] ?? '-')) . '</strong></li>'
        . '<li>سقف مجاز خرید قطعه: <strong>' . e($partsLimit !== '' ? $partsLimit : '-') . '</strong></li>'
        . '<li>Risk Flag: <strong>' . e($riskFlag) . '</strong></li>'
        . '<li>نام امضاکننده: <strong>' . e((string)($terms['typed_signature'] ?? '-')) . '</strong></li>'
        . '<li>کد ملی امضاکننده: <strong>' . e((string)($terms['signed_national_code'] ?? '-')) . '</strong></li>'
        . '</ul></section></article></body></html>';

    $filename = 'contract-' . date('Ymd-His') . '-req' . $requestId . '-' . bin2hex(random_bytes(4)) . '.html';
    $absoluteFile = $absoluteDir . '/' . $filename;
    file_put_contents($absoluteFile, $snapshotHtml, LOCK_EX);

    return trim($relativeDir, '/') . '/' . $filename;
}

function riskFlagFromInsuranceSelection(string $insurance): string
{
    if ($insurance === 'NOT_ALLOWED') {
        return 'BODY_INSURANCE_NOT_ALLOWED';
    }
    if ($insurance === 'NOT_AVAILABLE') {
        return 'BODY_INSURANCE_NOT_AVAILABLE';
    }
    return 'NONE';
}

function insuranceLabel(string $insurance): string
{
    if ($insurance === 'ALLOW') {
        return 'اجازه استفاده از بیمه بدنه داده شد';
    }
    if ($insurance === 'NOT_ALLOWED') {
        return 'اجازه استفاده از بیمه بدنه داده نشد';
    }
    if ($insurance === 'NOT_AVAILABLE') {
        return 'بیمه بدنه ندارد یا مشتری اطلاع ندارد';
    }
    return '-';
}

function partsPolicyLabel(string $policy): string
{
    if ($policy === 'ALWAYS_CONFIRM') {
        return 'قبل از هر خرید قطعه هماهنگ شود';
    }
    if ($policy === 'ALLOW_LIMIT') {
        return 'مجاز تا سقف مبلغ تعیین‌شده';
    }
    if ($policy === 'URGENT_LIMIT') {
        return 'فقط در موارد فوری تا سقف مبلغ تعیین‌شده';
    }
    return '-';
}

function buildContractPayload(
    array $columns,
    string $mobile,
    int $requestId,
    array $request,
    array $terms,
    string $otpHash,
    string $otpExpiresAt,
    string $contractVersion,
    string $snapshotPath
): array {
    $payload = [];
    if (in_array('mobile', $columns, true)) {
        $payload['mobile'] = $mobile;
    }
    if (in_array('service_request_id', $columns, true)) {
        $payload['service_request_id'] = $requestId;
    }
    if (in_array('accepted_mobile', $columns, true)) {
        $payload['accepted_mobile'] = $mobile;
    }
    if (in_array('accepted_full_name', $columns, true)) {
        $payload['accepted_full_name'] = (string)$terms['typed_signature'];
    }
    if (in_array('accepted_national_code', $columns, true)) {
        $payload['accepted_national_code'] = (string)$terms['signed_national_code'];
    }
    if (in_array('is_accepted', $columns, true)) {
        $payload['is_accepted'] = 0;
    }
    if (in_array('accepted_terms_json', $columns, true)) {
        $payload['accepted_terms_json'] = json_encode($terms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    if (in_array('contract_version', $columns, true)) {
        $payload['contract_version'] = $contractVersion;
    }
    if (in_array('contract_pdf_path', $columns, true)) {
        $payload['contract_pdf_path'] = $snapshotPath;
    }
    if (in_array('contract_status', $columns, true)) {
        $payload['contract_status'] = 'OTP_SENT';
    }
    if (in_array('contract_viewed_at', $columns, true)) {
        $payload['contract_viewed_at'] = (string)$terms['contract_viewed_at'];
    }
    if (in_array('contract_view_closed_at', $columns, true)) {
        $payload['contract_view_closed_at'] = (string)$terms['contract_view_closed_at'];
    }
    if (in_array('otp_hash', $columns, true)) {
        $payload['otp_hash'] = $otpHash;
    }
    if (in_array('otp_expires_at', $columns, true)) {
        $payload['otp_expires_at'] = $otpExpiresAt;
    }
    if (in_array('otp_sent_at', $columns, true)) {
        $payload['otp_sent_at'] = date('Y-m-d H:i:s');
    }
    if (in_array('otp_verified_at', $columns, true)) {
        $payload['otp_verified_at'] = null;
    }
    if (in_array('otp_attempt_count', $columns, true)) {
        $payload['otp_attempt_count'] = 0;
    }
    if (in_array('customer_ip', $columns, true)) {
        $payload['customer_ip'] = currentIp();
    }
    if (in_array('ip_address', $columns, true)) {
        $payload['ip_address'] = currentIp();
    }
    if (in_array('user_agent', $columns, true)) {
        $payload['user_agent'] = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }
    if (in_array('service_request_code', $columns, true)) {
        $payload['service_request_code'] = (string)($request['jobcard_code'] ?? ('REQ-' . $requestId));
    }
    if (in_array('sync_status', $columns, true)) {
        $payload['sync_status'] = 'Pending';
    }
    if (in_array('sync_error', $columns, true)) {
        $payload['sync_error'] = null;
    }
    return $payload;
}

function updateDynamicContractRow(int $id, array $payload, array $columns): void
{
    $set = [];
    $params = [];
    foreach ($payload as $column => $value) {
        if (!in_array($column, $columns, true)) {
            continue;
        }
        $set[] = '`' . $column . '` = ?';
        $params[] = $value;
    }
    if (in_array('updated_at', $columns, true)) {
        $set[] = '`updated_at` = NOW()';
    }
    if (!$set) {
        return;
    }
    $params[] = $id;
    $sql = 'UPDATE portal_contract_confirmations SET ' . implode(', ', $set) . ' WHERE id = ?';
    $stmt = getPdo()->prepare($sql);
    $stmt->execute($params);
}

function insertDynamicContractRow(array $payload, array $columns): int
{
    $insertColumns = [];
    $insertValues = [];
    $params = [];
    foreach ($payload as $column => $value) {
        if (!in_array($column, $columns, true)) {
            continue;
        }
        $insertColumns[] = '`' . $column . '`';
        $insertValues[] = '?';
        $params[] = $value;
    }
    if (in_array('created_at', $columns, true)) {
        $insertColumns[] = '`created_at`';
        $insertValues[] = 'NOW()';
    }
    if (in_array('updated_at', $columns, true)) {
        $insertColumns[] = '`updated_at`';
        $insertValues[] = 'NOW()';
    }
    $sql = 'INSERT INTO portal_contract_confirmations (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
    $stmt = getPdo()->prepare($sql);
    $stmt->execute($params);
    return (int)getPdo()->lastInsertId();
}

function renderFakeContractOtpPage(string $otp, int $requestId, int $contractId): void
{
    renderHeader('کد تایید قرارداد (تستی)', 'حالت تست فعال است');
    ?>
    <main class="auth-wrap">
      <section class="card form-card">
        <h2>کد تایید تستی قرارداد</h2>
        <p class="muted">در حالت تست پیامک واقعی ارسال نمی‌شود.</p>
        <p class="otp-message">کد تایید تستی شما: <strong class="otp-code"><?= e($otp) ?></strong></p>
        <div class="action-row">
          <a class="btn primary" href="verify-contract-otp.php?request_id=<?= e((string)$requestId) ?>&contract_id=<?= e((string)$contractId) ?>">ادامه و ثبت نهایی قرارداد</a>
          <a class="btn ghost" href="customer-contract.php?request_id=<?= e((string)$requestId) ?>">بازگشت</a>
        </div>
      </section>
    </main>
    <?php
    renderFooter();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('customer-profile.php?mode=dashboard');
    }
    checkCsrf();

    $mobile = requireCustomerLogin();
    $requestId = (int)($_POST['request_id'] ?? 0);
    if ($requestId <= 0) {
        flash('شناسه پرونده قرارداد معتبر نیست.', 'bad');
        redirect('customer-request-status.php');
    }

    $request = loadRequestForContractOtp($mobile, $requestId);
    if (!is_array($request)) {
        flash('پرونده پذیرش برای قرارداد پیدا نشد.', 'bad');
        redirect('customer-request-status.php');
    }

    $contractColumns = getTableColumns('portal_contract_confirmations');
    if (!$contractColumns) {
        throw new RuntimeException('جدول portal_contract_confirmations در دسترس نیست.');
    }

    $resendId = (int)($_POST['resend_contract_id'] ?? 0);
    $contractVersion = 'MOGHARE360-INTAKE-V1';
    $customerName = resolveCustomerNameForContractOtp($mobile);

    if ($resendId > 0) {
        $existing = loadContractRowByIdForCustomer($mobile, $resendId, $requestId);
        if (!is_array($existing)) {
            flash('رکورد قرارداد برای ارسال مجدد کد پیدا نشد.', 'bad');
            redirect('customer-contract.php?request_id=' . $requestId);
        }
        $status = strtoupper(trim((string)($existing['contract_status'] ?? '')));
        if ($status === 'ONLINE_SIGNED') {
            flash('این قرارداد قبلاً نهایی شده است.', 'bad');
            redirect('customer-request-status.php?request_id=' . $requestId);
        }

        $otp = (string)random_int(10000, 99999);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);
        $otpExpiresAt = (new DateTimeImmutable('+' . (int)$otpExpireMinutes . ' minutes'))->format('Y-m-d H:i:s');

        $payload = [];
        if (in_array('otp_hash', $contractColumns, true)) {
            $payload['otp_hash'] = $otpHash;
        }
        if (in_array('otp_expires_at', $contractColumns, true)) {
            $payload['otp_expires_at'] = $otpExpiresAt;
        }
        if (in_array('otp_sent_at', $contractColumns, true)) {
            $payload['otp_sent_at'] = date('Y-m-d H:i:s');
        }
        if (in_array('otp_verified_at', $contractColumns, true)) {
            $payload['otp_verified_at'] = null;
        }
        if (in_array('otp_attempt_count', $contractColumns, true)) {
            $payload['otp_attempt_count'] = 0;
        }
        if (in_array('contract_status', $contractColumns, true)) {
            $payload['contract_status'] = 'OTP_SENT';
        }
        updateDynamicContractRow((int)$existing['id'], $payload, $contractColumns);

        if ((bool)$useFakeOtp) {
            renderFakeContractOtpPage($otp, $requestId, (int)$existing['id']);
            exit;
        }

        $sms = sendIppanelOtp($mobile, $otp);
        if (!($sms['ok'] ?? false)) {
            flash('کد تایید ارسال شد ولی ارسال پیامک با خطا مواجه شد. لطفاً دوباره تلاش کنید.', 'bad');
        } else {
            flash('کد تایید قرارداد برای شما ارسال شد.');
        }
        redirect('verify-contract-otp.php?request_id=' . $requestId . '&contract_id=' . (int)$existing['id']);
    }

    $contractViewedAt = trim((string)($_POST['contract_viewed_at'] ?? ''));
    $contractClosedAt = trim((string)($_POST['contract_view_closed_at'] ?? ''));
    $q1Accept = isset($_POST['legal_q1_accept']) ? '1' : '0';
    $q2Insurance = trim((string)($_POST['legal_q2_insurance'] ?? ''));
    $q3PartsPolicy = trim((string)($_POST['legal_q3_parts_policy'] ?? ''));
    $q3LimitAmountRaw = trim((string)($_POST['legal_q3_limit_amount'] ?? ''));
    $finalAgreement = isset($_POST['final_agreement']) ? '1' : '0';
    $typedSignature = trim((string)($_POST['typed_signature'] ?? ''));
    $signedNationalCode = trim((string)($_POST['signed_national_code'] ?? ''));
    $signatureData = trim((string)($_POST['signature_data'] ?? ''));

    if ($contractViewedAt === '' || $contractClosedAt === '') {
        flash('ابتدا متن قرارداد را باز کنید و پس از مطالعه پنجره را ببندید.', 'bad');
        redirect('customer-contract.php?request_id=' . $requestId);
    }
    $viewedTs = strtotime($contractViewedAt);
    $closedTs = strtotime($contractClosedAt);
    if (!$viewedTs || !$closedTs || $closedTs < $viewedTs) {
        flash('ثبت مشاهده قرارداد معتبر نیست. لطفاً قرارداد را دوباره مشاهده کنید.', 'bad');
        redirect('customer-contract.php?request_id=' . $requestId);
    }
    if ($q1Accept !== '1') {
        flash('تایید بند خرابی‌های پنهان الزامی است.', 'bad');
        redirect('customer-contract.php?request_id=' . $requestId);
    }
    if (!in_array($q2Insurance, ['ALLOW', 'NOT_ALLOWED', 'NOT_AVAILABLE'], true)) {
        flash('انتخاب وضعیت بیمه بدنه الزامی است.', 'bad');
        redirect('customer-contract.php?request_id=' . $requestId);
    }
    if (!in_array($q3PartsPolicy, ['ALWAYS_CONFIRM', 'ALLOW_LIMIT', 'URGENT_LIMIT'], true)) {
        flash('انتخاب سیاست خرید قطعه الزامی است.', 'bad');
        redirect('customer-contract.php?request_id=' . $requestId);
    }
    if ($q3PartsPolicy !== 'ALWAYS_CONFIRM') {
        $numeric = preg_replace('/[^0-9]/', '', $q3LimitAmountRaw) ?? '';
        if ($numeric === '' || (int)$numeric <= 0) {
            flash('برای سیاست انتخاب‌شده، تعیین سقف مبلغ قطعه الزامی است.', 'bad');
            redirect('customer-contract.php?request_id=' . $requestId);
        }
        $q3LimitAmountRaw = $numeric;
    } else {
        $q3LimitAmountRaw = '';
    }
    if ($finalAgreement !== '1') {
        flash('تایید نهایی قرارداد الزامی است.', 'bad');
        redirect('customer-contract.php?request_id=' . $requestId);
    }
    if (!isPersianLettersAndSpace($typedSignature)) {
        flash('نام و نام خانوادگی جهت امضا باید فارسی و معتبر باشد.', 'bad');
        redirect('customer-contract.php?request_id=' . $requestId);
    }
    if (!isValidNationalCodeValue($signedNationalCode)) {
        flash('کد ملی امضاکننده باید 10 رقم باشد.', 'bad');
        redirect('customer-contract.php?request_id=' . $requestId);
    }
    if ($signatureData === '' || stripos($signatureData, 'data:image') !== 0) {
        flash('ثبت امضای دیجیتال الزامی است.', 'bad');
        redirect('customer-contract.php?request_id=' . $requestId);
    }

    $riskFlag = riskFlagFromInsuranceSelection($q2Insurance);
    $terms = [
        'contract_viewed_at' => $contractViewedAt,
        'contract_view_closed_at' => $contractClosedAt,
        'legal_q1_accept' => $q1Accept,
        'legal_q2_insurance' => $q2Insurance,
        'legal_q2_insurance_label' => insuranceLabel($q2Insurance),
        'legal_q3_parts_policy' => $q3PartsPolicy,
        'legal_q3_parts_policy_label' => partsPolicyLabel($q3PartsPolicy),
        'legal_q3_limit_amount' => $q3LimitAmountRaw,
        'final_agreement' => $finalAgreement,
        'typed_signature' => $typedSignature,
        'signed_national_code' => $signedNationalCode,
        'signature_data' => $signatureData,
        'risk_flag' => $riskFlag,
        'signature_time' => date('Y-m-d H:i:s'),
    ];

    $snapshotPath = createImmutableContractSnapshot($request, $mobile, $customerName, $terms, $contractVersion);
    $otp = (string)random_int(10000, 99999);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $otpExpiresAt = (new DateTimeImmutable('+' . (int)$otpExpireMinutes . ' minutes'))->format('Y-m-d H:i:s');

    $payload = buildContractPayload(
        $contractColumns,
        $mobile,
        $requestId,
        $request,
        $terms,
        $otpHash,
        $otpExpiresAt,
        $contractVersion,
        $snapshotPath
    );

    $existing = loadLatestContractRowForCustomer($mobile, $requestId);
    $contractId = 0;
    if (is_array($existing) && strtoupper(trim((string)($existing['contract_status'] ?? ''))) !== 'ONLINE_SIGNED') {
        updateDynamicContractRow((int)$existing['id'], $payload, $contractColumns);
        $contractId = (int)$existing['id'];
    } else {
        $contractId = insertDynamicContractRow($payload, $contractColumns);
    }

    if ((bool)$useFakeOtp) {
        renderFakeContractOtpPage($otp, $requestId, $contractId);
        exit;
    }

    $sms = sendIppanelOtp($mobile, $otp);
    if (!($sms['ok'] ?? false)) {
        flash('کد تایید قرارداد ثبت شد اما ارسال پیامک موفق نبود. لطفاً ارسال مجدد کد را بزنید.', 'bad');
    } else {
        flash('کد تایید قرارداد برای شما ارسال شد.');
    }

    redirect('verify-contract-otp.php?request_id=' . $requestId . '&contract_id=' . $contractId);
} catch (Throwable $e) {
    showErrorPage('خطا در ارسال کد تایید قرارداد.', $e->getMessage());
}
