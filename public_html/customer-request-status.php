<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

function readRequestStatus(array $request): string
{
    $status = trim((string)($request['request_status'] ?? $request['status'] ?? ''));
    if ($status !== '') {
        return strtoupper($status);
    }
    $sync = strtoupper(trim((string)($request['sync_status'] ?? '')));
    return $sync !== '' ? $sync : 'INTAKE_SUBMITTED';
}

function requestStatusFa(string $status): string
{
    $map = [
        'INTAKE_SUBMITTED' => 'پذیرش اولیه ثبت شد',
        'CONTRACT_PENDING' => 'در انتظار تایید قرارداد',
        'RECEPTION_REVIEW' => 'در حال بررسی پذیرش‌گر',
        'WAITING_MANAGER_OVERRIDE' => 'در انتظار تصمیم مدیر',
        'APPROVED_FOR_JOB' => 'تایید نهایی و آماده JobCard',
        'IN_PROGRESS' => 'در حال انجام خدمات',
        'READY_FOR_DELIVERY' => 'آماده تحویل',
        'DELIVERED' => 'تحویل شده',
        'CANCELLED' => 'لغو شده',
        'CLOSED' => 'بسته شده',
        'PENDING' => 'در صف بررسی',
        'SYNCED' => 'ثبت نهایی شد',
    ];
    return $map[$status] ?? $status;
}

function detectActiveRequest(array $requests): ?array
{
    foreach ($requests as $request) {
        $status = readRequestStatus($request);
        if (!in_array($status, ['DELIVERED', 'CANCELLED', 'CLOSED'], true)) {
            return $request;
        }
    }
    return null;
}

try {
    $mobile = requireCustomerLogin();
    $requests = getServiceRequestsByMobile($mobile);
    $requestId = (int)($_GET['request_id'] ?? 0);

    $focusRequest = null;
    if ($requestId > 0) {
        foreach ($requests as $request) {
            if ((int)($request['id'] ?? 0) === $requestId) {
                $focusRequest = $request;
                break;
            }
        }
    }
    if (!is_array($focusRequest)) {
        $focusRequest = detectActiveRequest($requests);
    }

    $focusStatus = is_array($focusRequest) ? readRequestStatus($focusRequest) : '';
    $focusNeedsContract = false;
    if (is_array($focusRequest)) {
        $hasContractFlag = array_key_exists('contract_confirmed', $focusRequest);
        $focusNeedsContract = in_array($focusStatus, ['CONTRACT_PENDING', 'INTAKE_SUBMITTED'], true)
            || ($hasContractFlag && (int)$focusRequest['contract_confirmed'] !== 1);
    }

    renderHeader('وضعیت درخواست‌های مشتری', 'پیگیری مسیر پذیرش تا تحویل');
    renderFlashes();
    ?>
    <main class="page-grid">
      <section class="card">
        <h2>پرونده فعال</h2>
        <?php if ($focusRequest): ?>
          <p>
            کد پرونده:
            <strong class="tracking-code"><?= e((string)($focusRequest['jobcard_code'] ?? ('REQ-' . (string)$focusRequest['id']))) ?></strong>
          </p>
          <p>
            خودرو: <?= e((string)($focusRequest['vehicle_brand'] ?? '')) ?>
            <?= e((string)($focusRequest['vehicle_model'] ?? '')) ?>
            | پلاک: <?= e((string)($focusRequest['plate_number'] ?? '-')) ?>
          </p>
          <p>وضعیت: <strong><?= e(requestStatusFa($focusStatus)) ?></strong></p>
          <div class="request-steps">
            <span class="<?= in_array($focusStatus, ['INTAKE_SUBMITTED', 'CONTRACT_PENDING', 'RECEPTION_REVIEW', 'WAITING_MANAGER_OVERRIDE', 'APPROVED_FOR_JOB', 'IN_PROGRESS', 'READY_FOR_DELIVERY', 'DELIVERED'], true) ? 'on' : '' ?>">پذیرش اولیه</span>
            <span class="<?= in_array($focusStatus, ['RECEPTION_REVIEW', 'WAITING_MANAGER_OVERRIDE', 'APPROVED_FOR_JOB', 'IN_PROGRESS', 'READY_FOR_DELIVERY', 'DELIVERED'], true) ? 'on' : '' ?>">تایید قرارداد</span>
            <span class="<?= in_array($focusStatus, ['APPROVED_FOR_JOB', 'IN_PROGRESS', 'READY_FOR_DELIVERY', 'DELIVERED'], true) ? 'on' : '' ?>">JobCard</span>
            <span class="<?= in_array($focusStatus, ['DELIVERED'], true) ? 'on' : '' ?>">تحویل</span>
          </div>
          <div class="action-row">
            <?php if ($focusNeedsContract): ?>
              <a class="btn primary" href="customer-contract.php?request_id=<?= e((string)$focusRequest['id']) ?>">تکمیل قرارداد آنلاین</a>
            <?php endif; ?>
            <a class="btn secondary" href="customer-profile.php?mode=dashboard">بازگشت به داشبورد</a>
          </div>
        <?php else: ?>
          <p class="muted">پرونده فعال ثبت نشده است.</p>
          <div class="action-row">
            <a class="btn primary" href="customer-service-request.php">ثبت پذیرش جدید</a>
          </div>
        <?php endif; ?>
      </section>

      <section class="card">
        <h2>سوابق درخواست‌ها</h2>
        <?php if (!$requests): ?>
          <p class="muted">هیچ درخواستی ثبت نشده است.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>کد پرونده</th>
                  <th>خودرو</th>
                  <th>نوع خدمت</th>
                  <th>وضعیت</th>
                  <th>تاریخ ثبت</th>
                  <th>عملیات</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($requests as $request): ?>
                  <?php $status = readRequestStatus($request); ?>
                  <tr>
                    <td class="tracking-code"><?= e((string)($request['jobcard_code'] ?? ('REQ-' . (string)$request['id']))) ?></td>
                    <td><?= e((string)($request['vehicle_brand'] ?? '')) ?> <?= e((string)($request['vehicle_model'] ?? '')) ?></td>
                    <td><?= e((string)($request['service_type'] ?? '-')) ?></td>
                    <td><?= e(requestStatusFa($status)) ?></td>
                    <td><?= e((string)($request['created_at'] ?? '-')) ?></td>
                    <td>
                      <a class="btn small" href="customer-request-status.php?request_id=<?= e((string)$request['id']) ?>">جزئیات</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <section class="card">
        <div class="action-row">
          <a class="btn primary" href="customer-service-request.php">ثبت درخواست جدید</a>
          <a class="btn ghost" href="customer-profile.php?mode=dashboard">بازگشت</a>
          <a class="btn danger" href="customer-logout.php">خروج از حساب کاربری</a>
        </div>
      </section>
    </main>
    <?php
    renderFooter();
} catch (Throwable $e) {
    showErrorPage('خطا در نمایش وضعیت درخواست مشتری.', $e->getMessage());
}
