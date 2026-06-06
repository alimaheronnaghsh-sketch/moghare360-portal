<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

function profileCompleteAfterOtp(?array $customer): bool
{
    if (!is_array($customer)) {
        return false;
    }
    $first = trim((string)($customer['first_name'] ?? ''));
    $last = trim((string)($customer['last_name'] ?? ''));
    $full = trim((string)($customer['full_name'] ?? ''));
    if ($full === '' && $first !== '' && $last !== '') {
        $full = trim($first . ' ' . $last);
    }
    return $first !== ''
        && $last !== ''
        && $full !== ''
        && preg_match('/^[0-9]{10}$/', trim((string)($customer['national_code'] ?? ''))) === 1
        && trim((string)($customer['postal_address'] ?? '')) !== ''
        && trim((string)($customer['job_title'] ?? '')) !== ''
        && (
            trim((string)($customer['birth_date_jalali'] ?? '')) !== ''
            || trim((string)($customer['birth_date'] ?? '')) !== ''
        );
}

function pickActiveRequestAfterOtp(array $requests): ?array
{
    foreach ($requests as $request) {
        $status = strtoupper(trim((string)($request['request_status'] ?? $request['status'] ?? '')));
        if ($status === '') {
            return $request;
        }
        if (!in_array($status, ['DELIVERED', 'CANCELLED', 'CLOSED', 'DONE'], true)) {
            return $request;
        }
    }
    return null;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('customer-login.php');
    }
    checkCsrf();

    $mobile = normalizeMobile((string)($_POST['mobile'] ?? ''));
    $purpose = (string)($_POST['purpose'] ?? '');
    $otpCode = trim((string)($_POST['otp_code'] ?? ''));

    if (!isValidMobile($mobile) || !validPurpose($purpose) || preg_match('/^[0-9]{5}$/', $otpCode) !== 1) {
        flash('کد یا موبایل معتبر نیست.', 'bad');
        redirect('customer-login.php');
    }

    $pdo = getPdo();
    $stmt = $pdo->prepare('SELECT * FROM otp_verifications WHERE mobile = ? AND purpose = ? AND is_used = 0 ORDER BY id DESC LIMIT 1');
    $stmt->execute([$mobile, $purpose]);
    $otp = $stmt->fetch();
    if (!$otp) {
        flash('کد فعالی برای این شماره پیدا نشد.', 'bad');
        redirect('customer-login.php');
    }

    if ((int)$otp['attempt_count'] >= 5) {
        flash('تعداد تلاش ناموفق زیاد است. دوباره کد بگیرید.', 'bad');
        redirect('customer-login.php');
    }

    if (strtotime((string)$otp['expires_at']) < time()) {
        flash('کد تایید منقضی شده است.', 'bad');
        redirect('customer-login.php');
    }

    if (!hash_equals((string)$otp['otp_code'], $otpCode)) {
        $update = $pdo->prepare('UPDATE otp_verifications SET attempt_count = attempt_count + 1 WHERE id = ?');
        $update->execute([(int)$otp['id']]);
        flash('کد تایید درست نیست.', 'bad');
        redirect('verify-otp.php?mobile=' . urlencode($mobile) . '&purpose=' . urlencode($purpose));
    }

    $used = $pdo->prepare('UPDATE otp_verifications SET is_used = 1, used_at = NOW() WHERE id = ?');
    $used->execute([(int)$otp['id']]);
    setVerifiedCustomerMobile($mobile);

    $customer = getCustomerByMobile($mobile);
    if (!profileCompleteAfterOtp($customer)) {
        redirect('customer-profile.php?mode=complete');
    }

    $requests = getServiceRequestsByMobile($mobile);
    $activeRequest = pickActiveRequestAfterOtp($requests);
    if (is_array($activeRequest)) {
        $requestId = (int)($activeRequest['id'] ?? 0);
        $requestStatus = strtoupper(trim((string)($activeRequest['request_status'] ?? $activeRequest['status'] ?? '')));
        $hasContractFlag = array_key_exists('contract_confirmed', $activeRequest);
        $needsContract = in_array($requestStatus, ['CONTRACT_PENDING', 'INTAKE_SUBMITTED'], true)
            || ($hasContractFlag && (int)$activeRequest['contract_confirmed'] !== 1);
        if ($requestId > 0 && $needsContract) {
            redirect('customer-contract.php?request_id=' . $requestId);
        }
        redirect('customer-request-status.php?request_id=' . $requestId);
    }

    redirect('customer-profile.php?mode=dashboard');
} catch (Throwable $e) {
    showErrorPage('خطا در تایید کد.', $e->getMessage());
}
