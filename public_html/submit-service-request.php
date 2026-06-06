<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

function generateJobCardCode(): string
{
    return 'JOB-' . date('Ymd') . '-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

function validVinOrEmpty(string $vin): bool
{
    if ($vin === '') {
        return true;
    }
    return preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin) === 1;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('customer-profile.php?mode=dashboard');
    }
    checkCsrf();

    $mobile = requireCustomerLogin();
    $brand = trim((string)($_POST['vehicle_brand'] ?? ''));
    $model = trim((string)($_POST['vehicle_model'] ?? ''));
    $type = trim((string)($_POST['vehicle_type'] ?? ''));
    $plate = trim((string)($_POST['plate_number'] ?? ''));
    $vin = strtoupper(trim((string)($_POST['vin'] ?? '')));
    $odometerRaw = trim((string)($_POST['odometer_km'] ?? ''));
    $description = trim((string)($_POST['service_description'] ?? ''));
    $serviceType = trim((string)($_POST['service_type'] ?? ''));
    $priority = trim((string)($_POST['customer_priority'] ?? 'Normal'));

    if ($brand === '' || $model === '' || $type === '' || $plate === '' || $description === '' || $serviceType === '') {
        flash('برند، مدل، نوع خودرو، پلاک، نوع خدمت و شرح خدمات الزامی است.', 'bad');
        redirect('customer-service-request.php');
    }

    if (!validVinOrEmpty($vin)) {
        flash('فرمت VIN معتبر نیست. VIN باید ۱۷ کاراکتر باشد.', 'bad');
        redirect('customer-service-request.php');
    }

    if (preg_match('/^[0-9]+$/', preg_replace('/[^0-9]/', '', $odometerRaw) ?? '') !== 1) {
        flash('کارکرد کیلومتر باید عدد معتبر باشد.', 'bad');
        redirect('customer-service-request.php');
    }
    $odometerValue = (int)preg_replace('/[^0-9]/', '', $odometerRaw);
    if ($odometerValue <= 0) {
        flash('کارکرد کیلومتر باید بیشتر از صفر باشد.', 'bad');
        redirect('customer-service-request.php');
    }

    $priority = in_array($priority, ['Normal', 'High', 'Critical'], true) ? $priority : 'Normal';

    $pdo = getPdo();
    $columns = getTableColumns('portal_service_requests_staging');
    if (!$columns) {
        throw new RuntimeException('جدول portal_service_requests_staging یافت نشد.');
    }

    $jobcardCode = generateJobCardCode();
    $payload = [
        'mobile' => $mobile,
        'vehicle_brand' => $brand,
        'vehicle_model' => $model,
        'vehicle_type' => $type,
        'plate_number' => $plate,
        'vin' => $vin,
        'odometer_km' => $odometerValue,
        'service_description' => $description,
    ];

    if (in_array('service_type', $columns, true)) {
        $payload['service_type'] = $serviceType;
    }
    if (in_array('customer_priority', $columns, true)) {
        $payload['customer_priority'] = $priority;
    }
    if (in_array('jobcard_code', $columns, true)) {
        $payload['jobcard_code'] = $jobcardCode;
    }
    if (in_array('request_status', $columns, true)) {
        $payload['request_status'] = 'CONTRACT_PENDING';
    } elseif (in_array('status', $columns, true)) {
        $payload['status'] = 'CONTRACT_PENDING';
    }
    if (in_array('contract_confirmed', $columns, true)) {
        $payload['contract_confirmed'] = 0;
    }
    if (in_array('intake_channel', $columns, true)) {
        $payload['intake_channel'] = 'CUSTOMER_PORTAL';
    }
    if (in_array('manager_override_needed', $columns, true)) {
        $payload['manager_override_needed'] = 0;
    }
    if (in_array('sync_status', $columns, true)) {
        $payload['sync_status'] = 'Pending';
    }
    if (in_array('sync_error', $columns, true)) {
        $payload['sync_error'] = null;
    }

    $insertColumns = [];
    $insertValues = [];
    $params = [];
    foreach ($payload as $column => $value) {
        $insertColumns[] = "`{$column}`";
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

    $sql = 'INSERT INTO portal_service_requests_staging (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $requestId = (int)$pdo->lastInsertId();
    flash('درخواست پذیرش ثبت شد. لطفاً قرارداد آنلاین را تایید کنید.');
    redirect('customer-contract.php?request_id=' . $requestId);
} catch (Throwable $e) {
    showErrorPage('خطا در ثبت درخواست خدمات.', $e->getMessage());
}
