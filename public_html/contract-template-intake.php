<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

function loadContractRequestForTemplate(string $mobile, int $requestId): ?array
{
    $stmt = getPdo()->prepare(
        'SELECT * FROM portal_service_requests_staging WHERE id = ? AND mobile = ? LIMIT 1'
    );
    $stmt->execute([$requestId, $mobile]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function resolveCustomerNameForTemplate(string $mobile): string
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

try {
    $mobile = requireCustomerLogin();
    $requestId = (int)($_GET['request_id'] ?? 0);
    if ($requestId <= 0) {
        showErrorPage('شناسه پرونده معتبر نیست.');
    }

    $request = loadContractRequestForTemplate($mobile, $requestId);
    if (!is_array($request)) {
        showErrorPage('پرونده قرارداد پیدا نشد.');
    }

    $customerName = resolveCustomerNameForTemplate($mobile);
    $requestCode = trim((string)($request['jobcard_code'] ?? ('REQ-' . $requestId)));
    $serviceType = trim((string)($request['service_type'] ?? 'در حال کارشناسی'));
    $vehicle = trim((string)($request['vehicle_brand'] ?? '') . ' ' . (string)($request['vehicle_model'] ?? ''));
    ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>قرارداد پذیرش خودرو - <?= e($requestCode) ?></title>
  <style>
    body {
      margin: 0;
      padding: 24px;
      background: #f8faf9;
      color: #15201b;
      font-family: "Vazirmatn", "Estedad", "Shabnam", Tahoma, sans-serif;
      line-height: 1.9;
    }
    .sheet {
      max-width: 880px;
      margin: 0 auto;
      background: #ffffff;
      border: 1px solid #d7e6df;
      border-radius: 10px;
      padding: 24px;
      box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
    }
    h1 {
      margin: 0 0 8px;
      font-size: 28px;
      color: #0e3d2f;
    }
    .meta {
      margin: 0;
      color: #3a564b;
      font-size: 14px;
    }
    .block {
      margin-top: 16px;
      padding: 14px;
      border: 1px solid #d7e6df;
      border-radius: 8px;
      background: #f6fbf8;
    }
    .block h2 {
      margin: 0 0 8px;
      font-size: 18px;
      color: #0e3d2f;
    }
    ul {
      margin: 8px 0;
      padding-right: 20px;
    }
    li {
      margin-bottom: 6px;
    }
    .footer-note {
      margin-top: 20px;
      font-size: 13px;
      color: #4f675f;
    }
  </style>
</head>
<body>
  <article class="sheet">
    <h1>قرارداد خدمات و پذیرش خودرو</h1>
    <p class="meta">
      نسخه قرارداد: <strong>MOGHARE360-INTAKE-V1</strong> |
      کد پرونده: <strong><?= e($requestCode) ?></strong> |
      تاریخ مشاهده: <strong><?= e(date('Y-m-d H:i')) ?></strong>
    </p>

    <section class="block">
      <h2>خلاصه اطلاعات پذیرش</h2>
      <p>نام مشتری: <strong><?= e($customerName) ?></strong></p>
      <p>شماره موبایل: <strong><?= e($mobile) ?></strong></p>
      <p>خودرو: <strong><?= e($vehicle !== '' ? $vehicle : '-') ?></strong></p>
      <p>نوع خدمت: <strong><?= e($serviceType) ?></strong></p>
    </section>

    <section class="block">
      <h2>شرایط حقوقی و اجرایی</h2>
      <ul>
        <li>مشتری مسئول صحت اطلاعات ثبت‌شده در پرونده خودرو و پذیرش است.</li>
        <li>کارشناسی فنی و اعلام برآورد اولیه بر اساس بررسی لحظه‌ای انجام می‌شود.</li>
        <li>عیوب پنهان، کامپیوتری یا غیرقابل مشاهده در زمان پذیرش ممکن است در ادامه فرآیند شناسایی شوند.</li>
        <li>هرگونه افزایش هزینه یا تغییر دامنه کار نیازمند هماهنگی با مشتری است، مگر در حالت مجاز ثبت‌شده در سیاست خرید قطعه.</li>
        <li>تحویل خودرو منوط به تسویه، یا ثبت مجوز مدیریتی در سامانه است.</li>
        <li>هرگونه Override تنها با دسترسی مدیر و ثبت اثر در سیستم معتبر خواهد بود.</li>
      </ul>
    </section>

    <section class="block">
      <h2>حریم خصوصی و مستندسازی</h2>
      <ul>
        <li>تمام سوابق پذیرش، خدمات، تغییرات هزینه و تاییدات مشتری در سامانه ثبت می‌شود.</li>
        <li>امضای دیجیتال و OTP تایید مشتری به عنوان تایید حقوقی قرارداد ثبت می‌گردد.</li>
        <li>نسخه قرارداد پس از تایید نهایی مشتری، به عنوان نسخه غیرقابل تغییر نگهداری می‌شود.</li>
      </ul>
    </section>

    <p class="footer-note">
      این قرارداد برای پذیرش خودرو در مجموعه مقاره موتورز تهیه شده است.
      پس از بستن این پنجره، بخش تایید نهایی قرارداد در صفحه اصلی فعال می‌شود.
    </p>
  </article>
</body>
</html>
<?php
} catch (Throwable $e) {
    showErrorPage('خطا در نمایش متن قرارداد.', $e->getMessage());
}
