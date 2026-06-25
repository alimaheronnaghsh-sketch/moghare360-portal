<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';

$result = null;
$input = [
    'full_name' => '',
    'mobile' => '',
    'national_id' => '',
    'brand' => '',
    'model' => '',
    'vehicle_class' => '',
    'plate_number' => '',
    'vin' => '',
    'request_type' => '',
    'request_description' => '',
    'city' => '',
    'address' => '',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    foreach (array_keys($input) as $key) {
        $input[$key] = trim((string)($_POST[$key] ?? ''));
    }

    $payload = [
        'full_name' => $input['full_name'],
        'mobile' => $input['mobile'],
        'national_id' => $input['national_id'],
        'brand' => $input['brand'],
        'model' => $input['model'],
        'vehicle_class' => $input['vehicle_class'],
        'plate_number' => $input['plate_number'],
        'vin' => $input['vin'],
        'request_type' => $input['request_type'],
        'request_description' => $input['request_description'],
        'city' => $input['city'],
        'address' => $input['address'],
        'source' => 'mirror-moghareh360.ir',
        'mirror_mode' => true,
    ];

    $result = mirror_api_customer_request($payload);
}

$brands = ['پژو', 'ایران‌خودرو', 'سایپا', 'هیوندای', 'کیا', 'تویوتا', 'بنز', 'بی‌ام‌و', 'سایر'];
$classes = ['sedan' => 'سواری', 'suv' => 'شاسی‌بلند', 'hatchback' => 'هاچ‌بک', 'van' => 'ون', 'pickup' => 'وانت', 'other' => 'سایر'];
$requestTypes = [
    'vehicle_intake' => 'پذیرش خودرو',
    'consultation' => 'مشاوره',
    'follow_up' => 'پیگیری',
    'periodic_service' => 'خدمات دوره‌ای',
    'other' => 'سایر',
];

mirror_render_head('ثبت درخواست مشتری — MOGHARE360', 'customer');
?>
<section class="m360-hero">
    <h2>ثبت درخواست آنلاین</h2>
    <p>فیلدها مطابق فرم‌های Customer / Vehicle موجود در نرم‌افزار (erp-customer-create-v2، erp-customer-vehicle-create) هستند. درخواست فقط به Master Server ارسال می‌شود.</p>
</section>

<?php if ($result !== null): ?>
    <div class="m360-alert <?= ($result['ok'] ?? false) ? 'm360-alert-info' : 'm360-alert-error' ?>">
        <?= mirror_h((string)($result['message'] ?? '')) ?>
        <?php if (!($result['ok'] ?? false) && (int)($result['status'] ?? 0) === 0): ?>
            <p style="margin:0.5rem 0 0;font-size:0.85rem">Master API endpoint implementation required on local server</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<section class="m360-card m360-form">
    <form method="post" action="customer-request.php">
        <h3>اطلاعات مشتری</h3>
        <label for="full_name">نام و نام خانوادگی فارسی <span style="color:var(--m360-danger)">*</span></label>
        <input type="text" id="full_name" name="full_name" maxlength="100" required value="<?= mirror_h($input['full_name']) ?>">

        <label for="mobile">موبایل <span style="color:var(--m360-danger)">*</span></label>
        <input type="tel" id="mobile" name="mobile" inputmode="tel" maxlength="15" required value="<?= mirror_h($input['mobile']) ?>">

        <label for="national_id">کد ملی</label>
        <input type="text" id="national_id" name="national_id" maxlength="10" inputmode="numeric" value="<?= mirror_h($input['national_id']) ?>">

        <label for="city">شهر</label>
        <input type="text" id="city" name="city" maxlength="80" value="<?= mirror_h($input['city']) ?>">

        <label for="address">آدرس</label>
        <input type="text" id="address" name="address" maxlength="200" value="<?= mirror_h($input['address']) ?>">

        <h3 style="margin-top:1.25rem">اطلاعات خودرو</h3>
        <label for="brand">برند خودرو <span style="color:var(--m360-danger)">*</span></label>
        <select id="brand" name="brand" required>
            <option value="">انتخاب کنید</option>
            <?php foreach ($brands as $brand): ?>
                <option value="<?= mirror_h($brand) ?>" <?= $input['brand'] === $brand ? 'selected' : '' ?>><?= mirror_h($brand) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="model">مدل خودرو</label>
        <input type="text" id="model" name="model" maxlength="80" value="<?= mirror_h($input['model']) ?>" placeholder="مثال: ۲۰۶، النترا">

        <label for="vehicle_class">کلاس خودرو</label>
        <select id="vehicle_class" name="vehicle_class">
            <option value="">انتخاب کنید</option>
            <?php foreach ($classes as $code => $label): ?>
                <option value="<?= mirror_h($code) ?>" <?= $input['vehicle_class'] === $code ? 'selected' : '' ?>><?= mirror_h($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="plate_number">پلاک</label>
        <input type="text" id="plate_number" name="plate_number" maxlength="30" value="<?= mirror_h($input['plate_number']) ?>" placeholder="مثال: ۱۲ب۳۴۵-۶۷">

        <label for="vin">VIN</label>
        <input type="text" id="vin" name="vin" maxlength="17" value="<?= mirror_h($input['vin']) ?>">

        <h3 style="margin-top:1.25rem">درخواست</h3>
        <label for="request_type">نوع درخواست <span style="color:var(--m360-danger)">*</span></label>
        <select id="request_type" name="request_type" required>
            <option value="">انتخاب کنید</option>
            <?php foreach ($requestTypes as $code => $label): ?>
                <option value="<?= mirror_h($code) ?>" <?= $input['request_type'] === $code ? 'selected' : '' ?>><?= mirror_h($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="request_description">شرح درخواست <span style="color:var(--m360-danger)">*</span></label>
        <textarea id="request_description" name="request_description" required maxlength="1500"><?= mirror_h($input['request_description']) ?></textarea>

        <button type="submit" class="m360-btn">ارسال به Master Server</button>
    </form>
</section>
<?php mirror_render_foot(); ?>
