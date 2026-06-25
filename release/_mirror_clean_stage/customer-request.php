<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';

$result = null;
$input = [
    'full_name' => '',
    'mobile' => '',
    'national_id' => '',
    'province' => '',
    'city' => '',
    'address' => '',
    'postal_address' => '',
    'extra_contact_info' => '',
    'job_title' => '',
    'birth_date' => '',
    'vehicle_brand' => '',
    'vehicle_class' => '',
    'vehicle_year_pair' => '',
    'plate_left_2_digits' => '',
    'plate_letter' => '',
    'plate_middle_3_digits' => '',
    'plate_region_2_digits' => '',
    'plate_display' => '',
    'vin' => '',
    'odometer_km' => '',
    'request_type' => '',
    'visit_date' => '',
    'request_description' => '',
];

$requestTypes = [
    'diagnostic_inspection' => 'کارشناسی و عیب‌یابی',
    'buy_sell_inspection' => 'کارشناسی خرید/فروش',
    'periodic_service' => 'سرویس‌های دوره‌ای',
    'option_add' => 'افزودن آپشن',
    'other' => 'سایر',
];

$plateLetters = ['ب', 'ج', 'د', 'س', 'ص', 'ط', 'ع', 'ق', 'ل', 'م', 'ن', 'و', 'ه', 'ی', 'ت', 'ک', 'گ', 'پ', 'ژ'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    foreach (array_keys($input) as $key) {
        $input[$key] = trim((string)($_POST[$key] ?? ''));
    }

    if ($input['plate_display'] === '') {
        $l = $input['plate_left_2_digits'];
        $letter = $input['plate_letter'];
        $mid = $input['plate_middle_3_digits'];
        $region = $input['plate_region_2_digits'];
        if ($l !== '' && $letter !== '' && $mid !== '' && $region !== '') {
            $input['plate_display'] = $l . ' ' . $letter . ' ' . $mid . ' ایران ' . $region;
        }
    }

    $payload = [
        'customer_name' => $input['full_name'],
        'full_name' => $input['full_name'],
        'mobile' => $input['mobile'],
        'national_id' => $input['national_id'],
        'province' => $input['province'],
        'city' => $input['city'],
        'vehicle_brand' => $input['vehicle_brand'],
        'brand' => $input['vehicle_brand'],
        'vehicle_class' => $input['vehicle_class'],
        'vehicle_year_pair' => $input['vehicle_year_pair'],
        'plate_left_2_digits' => $input['plate_left_2_digits'],
        'plate_letter' => $input['plate_letter'],
        'plate_middle_3_digits' => $input['plate_middle_3_digits'],
        'plate_region_2_digits' => $input['plate_region_2_digits'],
        'plate_display' => $input['plate_display'],
        'plate_number' => $input['plate_display'],
        'vehicle_plate' => $input['plate_display'],
        'plate_parts' => [
            'left_2' => $input['plate_left_2_digits'],
            'letter' => $input['plate_letter'],
            'middle_3' => $input['plate_middle_3_digits'],
            'region_2' => $input['plate_region_2_digits'],
        ],
        'vin' => $input['vin'],
        'odometer_km' => $input['odometer_km'],
        'request_type' => $input['request_type'],
        'visit_date' => $input['visit_date'],
        'request_description' => $input['request_description'],
        'service_description' => $input['request_description'],
        'address' => $input['address'] !== '' ? $input['address'] : $input['postal_address'],
        'postal_address' => $input['postal_address'],
        'extra_contact_info' => $input['extra_contact_info'],
        'job_title' => $input['job_title'],
        'birth_date' => $input['birth_date'],
        'source' => 'moghareh360.ir',
        'source_channel' => 'PUBLIC_WEB',
    ];

    $result = mirror_api_customer_request($payload);
}

mirror_render_head('ثبت درخواست مشتری', 'customer');
?>
<section class="m360-hero">
    <h2>ثبت درخواست آنلاین</h2>
    <p>فرم زیر را تکمیل کنید تا همکاران ما در اسرع وقت با شما تماس بگیرند.</p>
</section>

<?php if ($result !== null): ?>
    <div class="m360-alert <?= ($result['ok'] ?? false) ? 'm360-alert-info' : 'm360-alert-error' ?>">
        <?php if ($result['ok'] ?? false): ?>
            <strong>ثبت موفق.</strong> درخواست شما ثبت شد و پس از بررسی با شما تماس گرفته می‌شود.
        <?php else: ?>
            <?= mirror_h((string)($result['message'] ?? 'ثبت درخواست ناموفق بود. لطفاً دوباره تلاش کنید.')) ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<section class="m360-card m360-form">
    <form method="post" action="customer-request.php" class="m360-customer-form">
        <h3>اطلاعات مشتری</h3>
        <label for="full_name">نام و نام خانوادگی <span class="m360-req">*</span></label>
        <input type="text" id="full_name" name="full_name" maxlength="100" required value="<?= mirror_h($input['full_name']) ?>">

        <label for="mobile">موبایل <span class="m360-req">*</span></label>
        <input type="tel" id="mobile" name="mobile" inputmode="tel" maxlength="15" required value="<?= mirror_h($input['mobile']) ?>">

        <label for="national_id">کد ملی</label>
        <input type="text" id="national_id" name="national_id" maxlength="10" inputmode="numeric" value="<?= mirror_h($input['national_id']) ?>">

        <label for="province">استان <span class="m360-req">*</span></label>
        <select id="province" name="province" required>
            <option value="">انتخاب استان</option>
            <?php if ($input['province'] !== ''): ?>
                <option value="<?= mirror_h($input['province']) ?>" selected><?= mirror_h($input['province']) ?></option>
            <?php endif; ?>
        </select>

        <label for="city">شهر <span class="m360-req">*</span></label>
        <select id="city" name="city" required <?= $input['city'] === '' ? 'disabled' : '' ?>>
            <option value="">انتخاب شهر</option>
            <?php if ($input['city'] !== ''): ?>
                <option value="<?= mirror_h($input['city']) ?>" selected><?= mirror_h($input['city']) ?></option>
            <?php endif; ?>
        </select>

        <label for="address">آدرس</label>
        <input type="text" id="address" name="address" maxlength="200" value="<?= mirror_h($input['address']) ?>">

        <label for="postal_address">آدرس پستی</label>
        <input type="text" id="postal_address" name="postal_address" maxlength="200" value="<?= mirror_h($input['postal_address']) ?>">

        <label for="extra_contact_info">اطلاعات تماس تکمیلی</label>
        <textarea id="extra_contact_info" name="extra_contact_info" maxlength="500"><?= mirror_h($input['extra_contact_info']) ?></textarea>

        <label for="job_title">شغل</label>
        <input type="text" id="job_title" name="job_title" maxlength="100" value="<?= mirror_h($input['job_title']) ?>">

        <label for="birth_date">تاریخ تولد</label>
        <input type="date" id="birth_date" name="birth_date" value="<?= mirror_h($input['birth_date']) ?>">

        <h3 class="m360-section-title">اطلاعات خودرو</h3>

        <label for="vehicle_brand">برند خودرو <span class="m360-req">*</span></label>
        <select id="vehicle_brand" name="vehicle_brand" required>
            <option value="">انتخاب برند</option>
            <?php if ($input['vehicle_brand'] !== ''): ?>
                <option value="<?= mirror_h($input['vehicle_brand']) ?>" selected><?= mirror_h($input['vehicle_brand']) ?></option>
            <?php endif; ?>
        </select>

        <label for="vehicle_class">کلاس / مدل خودرو <span class="m360-req">*</span></label>
        <select id="vehicle_class" name="vehicle_class" required <?= $input['vehicle_class'] === '' ? 'disabled' : '' ?>>
            <option value="">انتخاب کلاس / مدل</option>
            <?php if ($input['vehicle_class'] !== ''): ?>
                <option value="<?= mirror_h($input['vehicle_class']) ?>" selected><?= mirror_h($input['vehicle_class']) ?></option>
            <?php endif; ?>
        </select>

        <label for="vehicle_year_pair">سال تولید <span class="m360-req">*</span></label>
        <select id="vehicle_year_pair" name="vehicle_year_pair" required>
            <option value="">انتخاب سال</option>
            <?php if ($input['vehicle_year_pair'] !== ''): ?>
                <option value="<?= mirror_h($input['vehicle_year_pair']) ?>" selected><?= mirror_h($input['vehicle_year_pair']) ?></option>
            <?php endif; ?>
        </select>

        <label>پلاک خودرو <span class="m360-req">*</span></label>
        <div class="m360-plate-widget" dir="ltr">
            <div class="m360-plate-iran">
                <span class="m360-plate-iran-label">ایران</span>
                <select id="plate_region_2_digits" name="plate_region_2_digits" required aria-label="کد منطقه"></select>
            </div>
            <select id="plate_middle_3_digits" name="plate_middle_3_digits" required aria-label="سه رقم وسط"></select>
            <select id="plate_letter" name="plate_letter" required aria-label="حرف پلاک">
                <option value="">حرف</option>
                <?php foreach ($plateLetters as $letter): ?>
                    <option value="<?= mirror_h($letter) ?>" <?= $input['plate_letter'] === $letter ? 'selected' : '' ?>><?= mirror_h($letter) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="plate_left_2_digits" name="plate_left_2_digits" required aria-label="دو رقم"></select>
            <div class="m360-plate-flag" aria-hidden="true">IR</div>
        </div>
        <input type="hidden" id="plate_display" name="plate_display" value="<?= mirror_h($input['plate_display']) ?>">

        <label for="vin">شماره شاسی (VIN)</label>
        <input type="text" id="vin" name="vin" maxlength="17" value="<?= mirror_h($input['vin']) ?>">

        <label for="odometer_km">کیلومتر خودرو</label>
        <input type="number" id="odometer_km" name="odometer_km" min="0" step="1" value="<?= mirror_h($input['odometer_km']) ?>">

        <h3 class="m360-section-title">درخواست</h3>

        <label for="request_type">نوع درخواست <span class="m360-req">*</span></label>
        <select id="request_type" name="request_type" required>
            <option value="">انتخاب کنید</option>
            <?php foreach ($requestTypes as $code => $label): ?>
                <option value="<?= mirror_h($code) ?>" <?= $input['request_type'] === $code ? 'selected' : '' ?>><?= mirror_h($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="visit_date">تاریخ مراجعه <span class="m360-req">*</span></label>
        <input type="hidden" id="visit_date" name="visit_date" required value="<?= mirror_h($input['visit_date']) ?>">
        <div id="visit_date_display" class="m360-muted"><?= $input['visit_date'] !== '' ? 'تاریخ انتخاب‌شده: ' . mirror_h($input['visit_date']) : 'روز مورد نظر را از تقویم انتخاب کنید.' ?></div>
        <div id="visit_date_grid" class="m360-cal-grid" aria-label="تقویم شمسی"></div>
        <p id="visit_time_hint" class="m360-visit-hint" style="display:none">ساعت حضور الزاما بین 8:30 الی 11:30 می‌باشد.</p>

        <label for="request_description">شرح درخواست <span class="m360-req">*</span></label>
        <textarea id="request_description" name="request_description" required maxlength="1500"><?= mirror_h($input['request_description']) ?></textarea>

        <button type="submit" class="m360-btn">ثبت درخواست</button>
    </form>
</section>

<script src="assets/js/iran-provinces-cities.js"></script>
<script src="assets/js/vehicle-brand-classes.js"></script>
<script src="assets/js/customer-form.js"></script>
<?php mirror_render_foot(); ?>
