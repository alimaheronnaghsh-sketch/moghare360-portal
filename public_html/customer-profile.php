<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

function customerFieldMissing($value): bool
{
    return trim((string)$value) === '';
}

function currentCustomerFullName(array $customer): string
{
    $firstName = trim((string)($customer['first_name'] ?? ''));
    $lastName = trim((string)($customer['last_name'] ?? ''));
    $fullName = trim((string)($customer['full_name'] ?? ''));
    if ($fullName === '' && ($firstName !== '' || $lastName !== '')) {
        $fullName = trim($firstName . ' ' . $lastName);
    }
    return $fullName;
}

function detectActiveRequest(array $requests): ?array
{
    foreach ($requests as $request) {
        $status = strtoupper(trim((string)($request['request_status'] ?? $request['status'] ?? '')));
        if ($status === '' || !in_array($status, ['DELIVERED', 'CANCELLED', 'CLOSED', 'DONE'], true)) {
            return $request;
        }
    }
    return null;
}

try {
    $mobile = requireCustomerLogin();
    $customer = getCustomerByMobile($mobile) ?? [];
    $requests = getServiceRequestsByMobile($mobile);
    $activeRequest = detectActiveRequest($requests);
    $fullName = currentCustomerFullName($customer);

    $customerCols = getTableColumns('portal_customers_staging');
    $profilePhotoPath = trim((string)($customer['profile_photo_path'] ?? ''));

    $isProfileComplete = (
        !customerFieldMissing($customer['first_name'] ?? '')
        && !customerFieldMissing($customer['last_name'] ?? '')
        && !customerFieldMissing($fullName)
        && preg_match('/^[0-9]{10}$/', trim((string)($customer['national_code'] ?? ''))) === 1
        && !customerFieldMissing($customer['postal_address'] ?? '')
        && !customerFieldMissing($customer['job_title'] ?? '')
        && (
            !customerFieldMissing($customer['birth_date_jalali'] ?? '')
            || !customerFieldMissing($customer['birth_date'] ?? '')
        )
    );

    $mode = (string)($_GET['mode'] ?? ($isProfileComplete ? 'dashboard' : 'complete'));
    $showForm = (!$isProfileComplete) || in_array($mode, ['complete', 'edit'], true);

    $sessionOld = is_array($_SESSION['customer_profile_old'] ?? null) ? $_SESSION['customer_profile_old'] : [];
    $sessionErrors = is_array($_SESSION['customer_profile_errors'] ?? null) ? $_SESSION['customer_profile_errors'] : [];
    unset($_SESSION['customer_profile_old'], $_SESSION['customer_profile_errors']);

    $pickValue = static function (string $field, string $fallback = '') use ($sessionOld, $customer): string {
        if (array_key_exists($field, $sessionOld)) {
            return trim((string)$sessionOld[$field]);
        }
        return trim((string)($customer[$field] ?? $fallback));
    };

    $firstName = $pickValue('first_name');
    $lastName = $pickValue('last_name');
    $nationalCode = $pickValue('national_code');
    $postalAddress = $pickValue('postal_address');
    $jobTitle = $pickValue('job_title');
    $birthDateJalali = $pickValue('birth_date_jalali', trim((string)($customer['birth_date'] ?? '')));

    $birthYear = '';
    $birthMonth = '';
    $birthDay = '';
    if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $birthDateJalali, $matches)) {
        $birthYear = $matches[1];
        $birthMonth = ltrim($matches[2], '0');
        $birthDay = ltrim($matches[3], '0');
    }

    $monthNames = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند',
    ];

    $currentJalaliYear = (int)date('Y') - 621;
    if ($currentJalaliYear < 1400) {
        $currentJalaliYear = 1405;
    }

    $requestStatusLabel = '';
    $activeRequestNeedsContract = false;
    if (is_array($activeRequest)) {
        $requestStatusCode = strtoupper(trim((string)($activeRequest['request_status'] ?? $activeRequest['status'] ?? '')));
        $requestStatusLabel = trim((string)($activeRequest['request_status'] ?? $activeRequest['status'] ?? 'در حال بررسی پذیرش'));
        if ($requestStatusLabel === '') {
            $requestStatusLabel = 'در حال بررسی پذیرش';
        }
        $hasContractFlag = array_key_exists('contract_confirmed', $activeRequest);
        $activeRequestNeedsContract = in_array($requestStatusCode, ['CONTRACT_PENDING', 'INTAKE_SUBMITTED'], true)
            || ($hasContractFlag && (int)$activeRequest['contract_confirmed'] !== 1);
    }

    $showPhotoReminder = $isProfileComplete && $profilePhotoPath === '';

    renderHeader('پروفایل مشتری', 'حساب کاربری مشتری');
    renderFlashes();
    ?>
    <main class="page-grid">
      <section class="card">
        <div class="welcome-banner">به مجموعه مقاره موتورز خوش آمدید</div>
        <h2>خلاصه پرونده مشتری</h2>
        <div class="profile-box">
          <div class="avatar"><?= e(initialLetter($fullName !== '' ? $fullName : $mobile)) ?></div>
          <div>
            <strong><?= e($fullName !== '' ? $fullName : 'مشتری جدید') ?></strong>
            <p class="mobile-field"><?= e($mobile) ?></p>
            <p class="muted"><?= e(trim((string)($customer['postal_address'] ?? 'آدرس ثبت نشده'))) ?></p>
            <?php if (in_array('sync_status', $customerCols, true)): ?>
              <span class="pill">وضعیت همگام‌سازی: <?= e((string)($customer['sync_status'] ?? 'Pending')) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="card flow-card">
        <h2>مسیر پذیرش خودرو</h2>
        <ol class="flow-steps">
          <li class="<?= $isProfileComplete ? 'done' : 'active' ?>">تکمیل پروفایل</li>
          <li class="<?= is_array($activeRequest) ? 'active' : '' ?>">ثبت پذیرش خودرو</li>
          <li class="<?= (is_array($activeRequest) && !$activeRequestNeedsContract) ? 'active' : '' ?>">تایید قرارداد</li>
          <li>پیگیری JobCard و تحویل</li>
        </ol>
      </section>

      <?php if ($isProfileComplete && !$showForm): ?>
        <section class="card">
          <h2>داشبورد مشتری</h2>
          <?php if (is_array($activeRequest)): ?>
            <div class="request-active-card card compact-card">
              <h3>درخواست فعال شما</h3>
              <p class="muted">
                کد پرونده: <?= e((string)($activeRequest['jobcard_code'] ?? ('REQ-' . (string)$activeRequest['id']))) ?>
                | وضعیت: <?= e($requestStatusLabel) ?>
              </p>
              <div class="action-row">
                <a class="btn primary" href="customer-request-status.php?request_id=<?= e((string)$activeRequest['id']) ?>">ادامه و پیگیری پرونده</a>
                <?php if ($activeRequestNeedsContract): ?>
                  <a class="btn secondary" href="customer-contract.php?request_id=<?= e((string)$activeRequest['id']) ?>">تکمیل قرارداد</a>
                <?php endif; ?>
              </div>
            </div>
          <?php else: ?>
            <p class="muted">در حال حاضر پرونده فعالی ندارید. با یک کلیک پذیرش جدید ثبت کنید.</p>
            <div class="action-row">
              <a class="btn primary" href="customer-service-request.php">شروع پذیرش خودرو</a>
            </div>
          <?php endif; ?>

          <div class="action-row">
            <a class="btn secondary" href="customer-request-status.php">مشاهده درخواست‌های قبلی</a>
            <a class="btn ghost" href="customer-profile.php?mode=edit">ویرایش اطلاعات</a>
            <a class="btn ghost" href="index.php">بازگشت</a>
            <a class="btn danger" href="customer-logout.php">خروج از حساب کاربری</a>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($showForm): ?>
        <section class="card" id="profileForm">
          <h2><?= $isProfileComplete ? 'ویرایش اطلاعات مشتری' : 'تکمیل ثبت‌نام مشتری' ?></h2>
          <?php if (!$isProfileComplete): ?>
            <p class="muted">برای ادامه پذیرش خودرو، تکمیل این فرم الزامی است.</p>
          <?php endif; ?>

          <?php if ($sessionErrors): ?>
            <div class="notice bad">
              <strong>لطفاً موارد زیر را اصلاح کنید:</strong>
              <ul class="error-list">
                <?php foreach ($sessionErrors as $field => $message): ?>
                  <li><?= e((string)$message) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post" action="save-customer-profile.php" class="form-grid" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="return_mode" value="<?= e($mode) ?>">

            <label>نام *
              <input name="first_name" required value="<?= e($firstName) ?>">
            </label>

            <label>نام خانوادگی *
              <input name="last_name" required value="<?= e($lastName) ?>">
            </label>

            <label>کد ملی *
              <input class="national-code-field input-number" name="national_code" required inputmode="numeric" maxlength="10" value="<?= e($nationalCode) ?>">
            </label>

            <label>شماره موبایل
              <input class="mobile-field input-number" name="mobile" value="<?= e($mobile) ?>" readonly>
            </label>

            <label>تصویر پروفایل (اختیاری)
              <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </label>

            <?php if ($profilePhotoPath !== ''): ?>
              <label>تصویر فعلی
                <input value="<?= e($profilePhotoPath) ?>" readonly>
              </label>
            <?php endif; ?>

            <label>شغل *
              <input name="job_title" required value="<?= e($jobTitle) ?>">
            </label>

            <label>سال تولد *
              <select name="birth_year" required>
                <option value="">انتخاب سال</option>
                <?php for ($year = $currentJalaliYear; $year >= 1300; $year--): ?>
                  <option value="<?= e((string)$year) ?>" <?= ((string)$year === $birthYear) ? 'selected' : '' ?>><?= e((string)$year) ?></option>
                <?php endfor; ?>
              </select>
            </label>

            <label>ماه تولد *
              <select name="birth_month" required>
                <option value="">انتخاب ماه</option>
                <?php foreach ($monthNames as $monthIndex => $monthLabel): ?>
                  <option value="<?= e((string)$monthIndex) ?>" <?= ((string)$monthIndex === $birthMonth) ? 'selected' : '' ?>><?= e($monthLabel) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>روز تولد *
              <select name="birth_day" required>
                <option value="">انتخاب روز</option>
                <?php for ($day = 1; $day <= 31; $day++): ?>
                  <option value="<?= e((string)$day) ?>" <?= ((string)$day === $birthDay) ? 'selected' : '' ?>><?= e((string)$day) ?></option>
                <?php endfor; ?>
              </select>
            </label>

            <label class="wide">آدرس پستی *
              <textarea name="postal_address" required><?= e($postalAddress) ?></textarea>
            </label>

            <div class="action-row wide">
              <button class="btn primary" type="submit"><?= $isProfileComplete ? 'ذخیره تغییرات' : 'ثبت نام و ورود به پروفایل' ?></button>
              <?php if ($isProfileComplete): ?>
                <a class="btn ghost" href="customer-profile.php?mode=dashboard">بازگشت به داشبورد</a>
              <?php else: ?>
                <a class="btn ghost" href="index.php">بازگشت</a>
              <?php endif; ?>
              <a class="btn danger" href="customer-logout.php">خروج از حساب کاربری</a>
            </div>
          </form>
        </section>
      <?php endif; ?>
    </main>

    <?php if ($showPhotoReminder): ?>
      <section class="modal-overlay" id="photoReminderModal" aria-hidden="true">
        <div class="modal-card">
          <h3>یادآوری تصویر پروفایل</h3>
          <p>برای شناخت بهتر شما در زمان پذیرش، لطفاً تصویر پروفایل خود را بارگذاری کنید.</p>
          <div class="action-row">
            <a class="btn primary" href="customer-profile.php?mode=edit#profileForm" data-photo-reminder-upload>بارگذاری تصویر</a>
            <button type="button" class="btn ghost" data-photo-reminder-dismiss>فعلاً بعداً</button>
          </div>
        </div>
      </section>
      <script>
        window.MOGHARE360_PHOTO_REMINDER = true;
      </script>
    <?php endif; ?>
    <?php
    renderFooter();
} catch (Throwable $e) {
    showErrorPage('خطا در نمایش پروفایل مشتری.', $e->getMessage());
}
