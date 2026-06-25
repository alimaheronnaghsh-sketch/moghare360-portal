<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

function loadContractForFinalize(string $mobile, int $requestId, int $contractId): ?array
{
    $stmt = getPdo()->prepare(
        'SELECT * FROM portal_contract_confirmations WHERE id = ? AND service_request_id = ? AND mobile = ? LIMIT 1'
    );
    $stmt->execute([$contractId, $requestId, $mobile]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function updateContractById(int $contractId, array $payload, array $columns): void
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
    $params[] = $contractId;
    $sql = 'UPDATE portal_contract_confirmations SET ' . implode(', ', $set) . ' WHERE id = ?';
    $stmt = getPdo()->prepare($sql);
    $stmt->execute($params);
}

function markServiceRequestIntakeApproved(string $mobile, int $requestId): void
{
    $columns = getTableColumns('portal_service_requests_staging');
    if (!$columns) {
        return;
    }

    $set = [];
    if (in_array('contract_confirmed', $columns, true)) {
        $set[] = '`contract_confirmed` = 1';
    }
    if (in_array('request_status', $columns, true)) {
        $set[] = "`request_status` = 'INTAKE_APPROVED'";
    }
    if (in_array('status', $columns, true)) {
        $set[] = "`status` = 'INTAKE_APPROVED'";
    }
    if (in_array('jobcard_status', $columns, true)) {
        $set[] = "`jobcard_status` = 'INTAKE_APPROVED'";
    }
    if (in_array('sync_status', $columns, true)) {
        $set[] = "`sync_status` = 'Pending'";
    }
    if (in_array('sync_error', $columns, true)) {
        $set[] = '`sync_error` = NULL';
    }
    if (in_array('updated_at', $columns, true)) {
        $set[] = '`updated_at` = NOW()';
    }
    if (!$set) {
        return;
    }

    $sql = 'UPDATE portal_service_requests_staging SET ' . implode(', ', $set) . ' WHERE id = ? AND mobile = ?';
    $stmt = getPdo()->prepare($sql);
    $stmt->execute([$requestId, $mobile]);
}

function sendContractConfirmationSms(string $mobile, string $requestCode): bool
{
    global $useFakeOtp, $ippanelApiKey, $ippanelSender;

    if ((bool)$useFakeOtp) {
        return true;
    }
    if ($ippanelApiKey === '' || $ippanelApiKey === 'CHANGE_ME_IPPANEL_API_KEY') {
        return false;
    }
    if (!function_exists('curl_init')) {
        return false;
    }

    $message = 'قرارداد آنلاین پرونده ' . $requestCode . ' با موفقیت تایید شد. مقاره موتورز';
    $payload = [
        'sending_type' => 'webservice',
        'from_number' => $ippanelSender ?: '100033605070',
        'params' => [
            'recipients' => [toIppanelRecipient($mobile)],
            'message' => $message,
        ],
    ];

    $ch = curl_init('https://edge.ippanel.com/v1/api/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: ' . $ippanelApiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 15,
    ]);

    $responseBody = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;
    $ok = ($status >= 200 && $status < 300);
    if (is_array($decoded) && isset($decoded['meta']['status'])) {
        $ok = $ok && ((bool)$decoded['meta']['status'] === true);
    }
    return $ok;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('customer-profile.php?mode=dashboard');
    }
    checkCsrf();

    $mobile = requireCustomerLogin();
    $requestId = (int)($_POST['request_id'] ?? 0);
    $contractId = (int)($_POST['contract_id'] ?? 0);
    $otpCode = trim((string)($_POST['otp_code'] ?? ''));

    if ($requestId <= 0 || $contractId <= 0) {
        flash('شناسه قرارداد معتبر نیست.', 'bad');
        redirect('customer-request-status.php');
    }
    if (preg_match('/^[0-9]{5}$/', $otpCode) !== 1) {
        flash('کد تایید باید 5 رقم باشد.', 'bad');
        redirect('verify-contract-otp.php?request_id=' . $requestId . '&contract_id=' . $contractId);
    }

    $contract = loadContractForFinalize($mobile, $requestId, $contractId);
    if (!is_array($contract)) {
        flash('رکورد تایید قرارداد پیدا نشد.', 'bad');
        redirect('customer-contract.php?request_id=' . $requestId);
    }

    $columns = getTableColumns('portal_contract_confirmations');
    if (!$columns) {
        throw new RuntimeException('جدول portal_contract_confirmations در دسترس نیست.');
    }

    $status = strtoupper(trim((string)($contract['contract_status'] ?? '')));
    if ($status === 'ONLINE_SIGNED') {
        flash('این قرارداد قبلاً نهایی شده است.');
        redirect('customer-request-status.php?request_id=' . $requestId);
    }

    if (in_array('otp_attempt_count', $columns, true)) {
        $attemptCount = (int)($contract['otp_attempt_count'] ?? 0);
        if ($attemptCount >= 5) {
            flash('تعداد تلاش ناموفق زیاد است. لطفاً کد جدید ارسال کنید.', 'bad');
            redirect('verify-contract-otp.php?request_id=' . $requestId . '&contract_id=' . $contractId);
        }
    }

    if (in_array('otp_expires_at', $columns, true)) {
        $expiresAt = trim((string)($contract['otp_expires_at'] ?? ''));
        if ($expiresAt !== '' && strtotime($expiresAt) < time()) {
            flash('کد تایید قرارداد منقضی شده است. لطفاً ارسال مجدد کد را بزنید.', 'bad');
            redirect('verify-contract-otp.php?request_id=' . $requestId . '&contract_id=' . $contractId);
        }
    }

    $otpHash = trim((string)($contract['otp_hash'] ?? ''));
    if ($otpHash === '' || !password_verify($otpCode, $otpHash)) {
        if (in_array('otp_attempt_count', $columns, true)) {
            $stmt = getPdo()->prepare('UPDATE portal_contract_confirmations SET otp_attempt_count = otp_attempt_count + 1 WHERE id = ?');
            $stmt->execute([$contractId]);
        }
        flash('کد تایید قرارداد نادرست است.', 'bad');
        redirect('verify-contract-otp.php?request_id=' . $requestId . '&contract_id=' . $contractId);
    }

    $payload = [];
    if (in_array('contract_status', $columns, true)) {
        $payload['contract_status'] = 'ONLINE_SIGNED';
    }
    if (in_array('is_accepted', $columns, true)) {
        $payload['is_accepted'] = 1;
    }
    if (in_array('accepted_at', $columns, true)) {
        $payload['accepted_at'] = date('Y-m-d H:i:s');
    }
    if (in_array('otp_verified_at', $columns, true)) {
        $payload['otp_verified_at'] = date('Y-m-d H:i:s');
    }
    if (in_array('otp_hash', $columns, true)) {
        $payload['otp_hash'] = null;
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
    if (in_array('sync_status', $columns, true)) {
        $payload['sync_status'] = 'Pending';
    }
    if (in_array('sync_error', $columns, true)) {
        $payload['sync_error'] = null;
    }
    updateContractById($contractId, $payload, $columns);

    markServiceRequestIntakeApproved($mobile, $requestId);

    $requestCode = trim((string)($contract['service_request_code'] ?? ('REQ-' . $requestId)));
    $smsOk = sendContractConfirmationSms($mobile, $requestCode);

    if ($smsOk) {
        flash('قرارداد آنلاین با موفقیت نهایی شد و پرونده وارد کارتابل پذیرش شد.');
    } else {
        flash('قرارداد آنلاین نهایی شد. ارسال پیامک تایید انجام نشد.', 'bad');
    }

    redirect('customer-request-status.php?request_id=' . $requestId);
} catch (Throwable $e) {
    showErrorPage('خطا در نهایی‌سازی قرارداد.', $e->getMessage());
}
