<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

function profileIsCompleteForService(?array $customer): bool
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

function detectActiveRequestForIntake(array $requests): ?array
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
    $customer = getCustomerByMobile($mobile);
    if (!profileIsCompleteForService($customer)) {
        flash('ابتدا ثبت‌نام مشتری را کامل کنید تا امکان ثبت پذیرش فعال شود.', 'bad');
        redirect('customer-profile.php?mode=complete');
    }

    $activeRequest = detectActiveRequestForIntake(getServiceRequestsByMobile($mobile));
    if (is_array($activeRequest)) {
        flash('شما یک پرونده فعال دارید. ابتدا همان پرونده را ادامه دهید.', 'bad');
        redirect('customer-request-status.php?request_id=' . (int)($activeRequest['id'] ?? 0));
    }

    $lookups = getVehicleLookups();
    $brandPriority = ['Mercedes-Benz', 'BMW', 'Porsche', 'Audi', 'Volkswagen', 'Volvo', 'Other'];
    $brandBucket = [];
    foreach ($lookups as $row) {
        $brand = trim((string)($row['brand'] ?? ''));
        $model = trim((string)($row['model'] ?? ''));
        if ($brand === '' || $model === '') {
            continue;
        }
        $brandBucket[$brand][] = $model;
    }
    foreach ($brandBucket as $brand => $models) {
        $brandBucket[$brand] = array_values(array_unique($models));
    }
    uksort($brandBucket, static function (string $a, string $b) use ($brandPriority): int {
        $aIndex = array_search($a, $brandPriority, true);
        $bIndex = array_search($b, $brandPriority, true);
        $aScore = ($aIndex === false) ? 999 : $aIndex;
        $bScore = ($bIndex === false) ? 999 : $bIndex;
        return $aScore <=> $bScore;
    });

    renderHeader('ثبت پذیرش خودرو', 'درخواست خدمات مشتری');
    renderFlashes();
    ?>
    <main class="auth-wrap wide-auth">
      <section class="card flow-card">
        <h2>فرم پذیرش اولیه خودرو</h2>
        <p class="muted">ثبت این فرم به معنی ایجاد پرونده پذیرش است. بعد از ثبت، قرارداد آنلاین تایید می‌شود و پرونده وارد کارتابل پذیرش‌گر خواهد شد.</p>
        <ol class="flow-steps">
          <li class="done">تکمیل پروفایل</li>
          <li class="active">ثبت پذیرش خودرو</li>
          <li>تایید قرارداد</li>
          <li>پیگیری JobCard</li>
        </ol>
      </section>

      <form class="card form-card" method="post" action="submit-service-request.php">
        <?= csrfField() ?>
        <div class="form-grid">
          <label>شماره موبایل
            <input class="mobile-field input-number" name="mobile" value="<?= e($mobile) ?>" readonly>
          </label>

          <label>برند خودرو *
            <select name="vehicle_brand" id="vehicleBrand" required>
              <option value="">انتخاب برند</option>
              <?php foreach (array_keys($brandBucket) as $brand): ?>
                <option value="<?= e($brand) ?>"><?= e($brand) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label>مدل خودرو *
            <select name="vehicle_model" id="vehicleModel" required disabled>
              <option value="">ابتدا برند را انتخاب کنید</option>
              <?php foreach ($brandBucket as $brand => $models): ?>
                <?php foreach ($models as $model): ?>
                  <option value="<?= e($model) ?>" data-brand="<?= e($brand) ?>"><?= e($model) ?></option>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </select>
          </label>

          <label>نوع خودرو *
            <select name="vehicle_type" required>
              <option value="">انتخاب نوع</option>
              <option value="سواری">سواری</option>
              <option value="شاسی بلند">شاسی بلند</option>
              <option value="کوپه">کوپه</option>
              <option value="سدان">سدان</option>
              <option value="هاچ‌بک">هاچ‌بک</option>
              <option value="وانت">وانت</option>
              <option value="سایر">سایر</option>
            </select>
          </label>

          <label>پلاک خودرو *
            <input name="plate_number" required placeholder="مثال: 12 ب 345 ایران 67">
          </label>

          <label>VIN / شماره شاسی
            <input class="input-number" name="vin" maxlength="17" placeholder="17 کاراکتر">
          </label>

          <label>کارکرد کیلومتر *
            <input class="input-number" name="odometer_km" inputmode="numeric" required placeholder="مثال: 82000">
          </label>

          <label>نوع خدمت *
            <select name="service_type" required>
              <option value="">انتخاب نوع خدمت</option>
              <option value="سرویس‌های دوره‌ای">سرویس‌های دوره‌ای</option>
              <option value="آپشن و ارتقا">آپشن و ارتقا</option>
              <option value="کارشناسی خرید/فروش">کارشناسی خرید/فروش</option>
              <option value="کارشناسی و عیب‌یابی">کارشناسی و عیب‌یابی</option>
            </select>
          </label>

          <label>اولویت رسیدگی
            <select name="customer_priority">
              <option value="Normal">عادی</option>
              <option value="High">فوری</option>
              <option value="Critical">خیلی فوری</option>
            </select>
          </label>

          <label class="wide">شرح درخواست خدمات *
            <textarea name="service_description" required placeholder="شرح ایراد، صدای غیرعادی، خطای دیاگ، سابقه تعمیر و هر توضیحی که به کارشناسی کمک می‌کند..."></textarea>
          </label>
        </div>

        <div class="action-row">
          <button class="btn primary" type="submit">ثبت پذیرش و ادامه قرارداد</button>
          <a class="btn secondary" href="customer-request-status.php">مشاهده وضعیت درخواست‌ها</a>
          <a class="btn ghost" href="customer-profile.php?mode=dashboard">بازگشت</a>
          <a class="btn danger" href="customer-logout.php">خروج از حساب کاربری</a>
        </div>
      </form>
    </main>
    <?php
    renderFooter();
} catch (Throwable $e) {
    showErrorPage('خطا در نمایش فرم پذیرش خودرو.', $e->getMessage());
}
