<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 1 Customer Entry Form (no direct write)
 */

require_once __DIR__ . '/includes/erp-customer-core-helper.php';

$connection = false;
$errorMessage = '';

try {
    $connection = customer_core_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    customer_core_require_auth_and_guard($connection, 'customer.core.entry.create');
} catch (Throwable) {
    customer_core_render_error_page('ورود مشتری', 'صفحه ورود مشتری قابل بارگذاری نیست.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

customer_core_render_head('ورود مشتری');

echo '<div class="p1cc-hero">';
echo '<h1>ورود مشتری</h1>';
echo '<p>ثبت کنترل‌شده Intake با بررسی تکراری</p>';
echo '</div>';

echo '<div class="p1cc-card">';
echo '<h2>راهنمای بررسی تکراری</h2>';
echo '<p class="p1cc-hint">سیستم موبایل، کد ملی و پلاک را در جداول Intake و در صورت وجود، جداول Customers_v2 / CustomerPhones_v2 / Vehicles بررسی می‌کند. در صورت یافتن تطابق، وضعیت <span class="p1cc-badge p1cc-badge-duplicate">POSSIBLE_DUPLICATE</span> ثبت می‌شود.</p>';
echo '</div>';

echo '<form class="p1cc-card" method="post" action="submit-customer-entry.php">';
echo erp_csrf_input('customer_core_entry');
echo '<div class="p1cc-form-grid">';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="full_name">نام کامل *</label>';
echo '<input class="p1cc-input" type="text" id="full_name" name="full_name" required maxlength="200"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="mobile">موبایل *</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="text" id="mobile" name="mobile" required maxlength="30"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="national_code">کد ملی</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="text" id="national_code" name="national_code" maxlength="30"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="license_plate">پلاک</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="text" id="license_plate" name="license_plate" maxlength="50"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="intake_channel">کانال ورود *</label>';
echo '<select class="p1cc-select" id="intake_channel" name="intake_channel" required>';
echo '<option value="">انتخاب کنید</option>';
echo '<option value="WALK_IN">حضوری</option>';
echo '<option value="PHONE">تماس</option>';
echo '<option value="WHATSAPP">واتساپ</option>';
echo '<option value="WEBSITE">سایت</option>';
echo '<option value="INSTAGRAM">اینستاگرام</option>';
echo '<option value="REFERRAL">معرفی</option>';
echo '<option value="CORPORATE">سازمانی</option>';
echo '</select></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="intake_type">نوع ورود *</label>';
echo '<select class="p1cc-select" id="intake_type" name="intake_type" required>';
echo '<option value="CUSTOMER">مشتری</option>';
echo '<option value="LEAD">لید</option>';
echo '</select></div>';

echo '<div class="p1cc-form-group full"><label class="p1cc-label" for="source_description">توضیح منبع</label>';
echo '<input class="p1cc-input" type="text" id="source_description" name="source_description" maxlength="300"></div>';

echo '<div class="p1cc-form-group full"><label class="p1cc-label" for="notes">یادداشت</label>';
echo '<textarea class="p1cc-textarea" id="notes" name="notes" maxlength="1000"></textarea></div>';

echo '</div>';
echo '<div class="p1cc-btn-row">';
echo '<button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت ورود مشتری</button>';
echo '<a class="p1cc-btn p1cc-btn-ghost" href="erp-customer-core-dashboard.php">انصراف</a>';
echo '</div>';
echo '</form>';

customer_core_render_foot();
