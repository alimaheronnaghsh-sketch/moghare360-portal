<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 1 Vehicle Binding Form (no direct write)
 */

require_once __DIR__ . '/includes/erp-customer-core-helper.php';

$connection = false;

try {
    $connection = customer_core_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    customer_core_require_auth_and_guard($connection, 'customer.core.vehicle.binding.create');
} catch (Throwable) {
    customer_core_render_error_page('اتصال خودرو', 'صفحه اتصال خودرو قابل بارگذاری نیست.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

$photoLabels = [
    'FRONT' => 'جلو',
    'REAR' => 'عقب',
    'LEFT' => 'چپ',
    'RIGHT' => 'راست',
    'INTERIOR' => 'داخل',
    'ODOMETER' => 'کیلومترشمار',
    'DAMAGE' => 'آسیب',
];

customer_core_render_head('اتصال خودرو به مشتری');

echo '<div class="p1cc-hero">';
echo '<h1>اتصال خودرو به مشتری</h1>';
echo '<p>ثبت Binding و متادیتای placeholder عکس — بدون آپلود فایل</p>';
echo '</div>';

echo '<form class="p1cc-card" method="post" action="submit-vehicle-binding.php">';
echo erp_csrf_input('customer_core_vehicle_binding');
echo '<div class="p1cc-form-grid">';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="customer_id">شناسه مشتری</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="number" id="customer_id" name="customer_id" min="1"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="intake_id">شناسه Intake</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="number" id="intake_id" name="intake_id" min="1"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="vehicle_id">شناسه خودرو (legacy)</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="number" id="vehicle_id" name="vehicle_id" min="1"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="relationship_type">نوع رابطه</label>';
echo '<select class="p1cc-select" id="relationship_type" name="relationship_type">';
echo '<option value="OWNER">OWNER — مالک</option>';
echo '<option value="DRIVER">DRIVER — راننده</option>';
echo '<option value="REPRESENTATIVE">REPRESENTATIVE — نماینده</option>';
echo '<option value="FLEET_CONTACT">FLEET_CONTACT — تماس ناوگان</option>';
echo '<option value="PREVIOUS_OWNER">PREVIOUS_OWNER — مالک قبلی</option>';
echo '</select></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="license_plate">پلاک *</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="text" id="license_plate" name="license_plate" required maxlength="50"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="vin">VIN</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="text" id="vin" name="vin" maxlength="100"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="brand">برند</label>';
echo '<input class="p1cc-input" type="text" id="brand" name="brand" maxlength="100"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="model">مدل</label>';
echo '<input class="p1cc-input" type="text" id="model" name="model" maxlength="100"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="model_year">سال مدل</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="number" id="model_year" name="model_year" min="1900" max="2100"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="color">رنگ</label>';
echo '<input class="p1cc-input" type="text" id="color" name="color" maxlength="100"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="mileage_km">کیلومتر</label>';
echo '<input class="p1cc-input p1cc-input-ltr" type="number" id="mileage_km" name="mileage_km" min="0"></div>';

echo '<div class="p1cc-form-group full"><label class="p1cc-label" for="notes">یادداشت</label>';
echo '<textarea class="p1cc-textarea" id="notes" name="notes" maxlength="1000"></textarea></div>';

echo '<div class="p1cc-form-group full"><span class="p1cc-label">متادیتای عکس (Placeholder)</span>';
echo '<div class="p1cc-photo-grid">';

foreach (ERP_PHASE1_PHOTO_TYPES as $photoType) {
    $label = $photoLabels[$photoType] ?? $photoType;
    echo '<label class="p1cc-photo-item">';
    echo '<input type="checkbox" name="photo_types[]" value="' . customer_core_h($photoType) . '" checked>';
    echo customer_core_h($label) . ' <span class="p1cc-badge p1cc-badge-draft">' . customer_core_h($photoType) . '</span>';
    echo '</label>';
}

echo '</div><p class="p1cc-hint">همه موارد انتخاب‌شده با storage_status = PLACEHOLDER ثبت می‌شوند.</p></div>';

echo '</div>';
echo '<div class="p1cc-btn-row">';
echo '<button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت اتصال خودرو</button>';
echo '<a class="p1cc-btn p1cc-btn-ghost" href="erp-customer-core-dashboard.php">انصراف</a>';
echo '</div>';
echo '</form>';

customer_core_render_foot();
