<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
ensureSessionStarted();

function cpTrim($value): string
{
    return trim((string)$value);
}

function cpFieldValue(string $field, array $old, ?array $customer): string
{
    if (array_key_exists($field, $old)) {
        return (string)$old[$field];
    }
    if (is_array($customer) && array_key_exists($field, $customer)) {
        return (string)$customer[$field];
    }
    return '';
}

function cpParseBirthDateFromDb(?array $customer): array
{
    if (!is_array($customer)) {
        return ['', '', ''];
    }

    $raw = cpTrim($customer['birth_date'] ?? '');
    if ($raw === '' && array_key_exists('birth_date_jalali', $customer)) {
        $raw = cpTrim($customer['birth_date_jalali'] ?? '');
    }

    if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $raw, $m)) {
        return [$m[1], (string)((int)$m[2]), (string)((int)$m[3])];
    }
    return ['', '', ''];
}

try {
    $mobile = requireCustomerLogin();
    $customer = getCustomerByMobile($mobile);
    $requests = getServiceRequestsByMobile($mobile);
    $requestCols = getTableColumns('portal_service_requests_staging');

    $old = is_array($_SESSION['customer_profile_old'] ?? null) ? $_SESSION['customer_profile_old'] : [];
    $errors = is_array($_SESSION['customer_profile_errors'] ?? null) ? $_SESSION['customer_profile_errors'] : [];
    unset($_SESSION['customer_profile_old'], $_SESSION['customer_profile_errors']);

    $firstName = cpTrim(cpFieldValue('first_name', $old, $customer));
    $lastName = cpTrim(cpFieldValue('last_name', $old, $customer));
    $fullNameDb = cpTrim((string)($customer['full_name'] ?? ''));
    $fullName = $fullNameDb !== '' ? $fullNameDb : cpTrim($firstName . ' ' . $lastName);
    $nationalCode = cpTrim(cpFieldValue('national_code', $old, $customer));
    $postalAddress = cpFieldValue('postal_address', $old, $customer);
    $jobTitle = cpTrim(cpFieldValue('job_title', $old, $customer));
    $profilePhotoPath = cpTrim(cpFieldValue('profile_photo_path', $old, $customer));

    [$dbYear, $dbMonth, $dbDay] = cpParseBirthDateFromDb($customer);
    $birthYear = cpTrim((string)($old['birth_year'] ?? $dbYear));
    $birthMonth = cpTrim((string)($old['birth_month'] ?? $dbMonth));
    $birthDay = cpTrim((string)($old['birth_day'] ?? $dbDay));
    $birthDateText = ($birthYear !== '' && $birthMonth !== '' && $birthDay !== '')
        ? sprintf('%04d/%02d/%02d', (int)$birthYear, (int)$birthMonth, (int)$birthDay)
        : '';

    $isExisting = is_array($customer);
    $isProfileComplete = $isExisting
        && $firstName !== ''
        && $lastName !== ''
        && $fullName !== ''
        && preg_match('/^[0-9]{10}$/', $nationalCode)
        && cpTrim($postalAddress) !== ''
        && $birthDateText !== '';

    $profileModeTitle = $isProfileComplete ? 'ویرایش اطلاعات' : 'تکمیل ثبت نام';
    $submitButtonText = $isExisting ? 'ذخیره تغییرات' : 'ثبت نام و ورود به پروفایل';

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

    $hasStatusCol = in_array('status', $requestCols, true);
    $activeRequest = null;
    if ($hasStatusCol) {
        foreach ($requests as $request) {
            $status = strtoupper(cpTrim($request['status'] ?? ''));
            if ($status === '' || !in_array($status, ['DELIVERED', 'CANCELLED', 'CLOSED'], true)) {
                $activeRequest = $request;
                break;
            }
        }
    }
    $latestRequest = $requests[0] ?? null;

    renderHeader('پروفایل مشتری', 'حساب کاربری مشتری');
    renderFlashes();
    ?>
    <main class="page-grid">
      <section class="card">
        <div class="welcome-banner">به مجموعه مقاره موتورز خوش آمدید</div>
        <h2>اطلاعات مشتری</h2>
        <div class="profile-box">
          <div class="avatar"><?= e(initialLetter($fullName !== '' ? $fullName : $mobile)) ?></div>
          <div>
            <strong><?= e($fullName !== '' ? $fullName : 'مشتری جدید') ?></strong>
            <p><?= e($mobile) ?></p>
            <p class="muted"><?= e(cpTrim($postalAddress) !== '' ? $postalAddress : 'آدرس ثبت نشده') ?></p>
            <span class="pill"><?= e($profileModeTitle) ?></span>
          </div>
        </div>
      </section>

      <?php if ($isProfileComplete): ?>
        <?php if ($hasStatusCol && $activeRequest): ?>
          <section class="card request-active-card">
            <h2>درخواست فعال شما</h2>
            <p class="muted">
              خودرو: <?= e((string)($activeRequest['vehicle_brand'] ?? '')) ?> <?= e((string)($activeRequest['vehicle_model'] ?? '')) ?>
              | وضعیت: <?= e((string)($activeRequest['status'] ?? 'در حال بررسی')) ?>
            </p>
            <a class="btn primary" href="customer-request-status.php">مشاهده وضعیت درخواست</a>
          </section>
        <?php elseif (!$hasStatusCol && $latestRequest): ?>
          <section class="card request-active-card">
            <h2>آخرین درخواست شما</h2>
            <p class="muted">در این نسخه ستون وضعیت درخواست موجود نیست. آخرین درخواست نمایش داده می‌شود.</p>
            <a class="btn primary" href="customer-request-status.php">مشاهده جزئیات درخواست‌ها</a>
          </section>
        <?php endif; ?>

        <section class="card">
          <h2>داشبورد مشتری</h2>
          <div class="action-row">
            <a class="btn secondary" href="#profileForm">ویرایش اطلاعات</a>
            <a class="btn primary" href="customer-service-request.php">ثبت درخواست پذیرش / درخواست خدمات</a>
            <a class="btn secondary" href="customer-request-status.php">مشاهده درخواست‌های قبلی</a>
            <a class="btn ghost" href="index.php">بازگشت</a>
            <a class="btn danger" href="customer-logout.php">خروج از حساب کاربری</a>
          </div>
        </section>
      <?php endif; ?>

      <section class="card" id="profileForm">
        <h2><?= e($isExisting ? $profileModeTitle : 'ثبت نام مشتری') ?></h2>

        <?php if ($errors): ?>
          <div class="notice bad">
            <?php foreach ($errors as $error): ?>
              <div><?= e((string)$error) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" action="save-customer-profile.php" class="form-grid">
          <?= csrfField() ?>

          <label>نام *
            <input name="first_name" required value="<?= e($firstName) ?>">
          </label>

          <label>نام خانوادگی *
            <input name="last_name" required value="<?= e($lastName) ?>">
          </label>

          <label>کد ملی *
            <input name="national_code" required inputmode="numeric" maxlength="10" value="<?= e($nationalCode) ?>">
          </label>

          <label>شماره موبایل
            <input name="mobile" value="<?= e($mobile) ?>" readonly>
          </label>

          <label>آدرس پستی *
            <textarea name="postal_address" required><?= e($postalAddress) ?></textarea>
          </label>

          <label>شغل
            <input name="job_title" value="<?= e($jobTitle) ?>">
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

          <label>تصویر پروفایل (اختیاری)
            <input name="profile_photo_path" placeholder="مسیر فایل تصویر" value="<?= e($profilePhotoPath) ?>">
          </label>

          <div class="action-row wide">
            <button class="btn primary" type="submit"><?= e($submitButtonText) ?></button>
            <a class="btn ghost" href="index.php">بازگشت</a>
            <a class="btn danger" href="customer-logout.php">خروج از حساب کاربری</a>
          </div>
        </form>
      </section>
    </main>
    <?php
    renderFooter();
} catch (Throwable $e) {
    showErrorPage('خطا در نمایش پروفایل مشتری.', $e->getMessage());
}
