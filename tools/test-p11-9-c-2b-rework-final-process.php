<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$wb = (string)file_get_contents($root . '/public_html/erp-reception-workbench.php');
$helper = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$intake = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');
$combined = $wb . $helper . $intake;

function rf_proc_pass(string $name, bool $ok): array
{
    return ['name' => $name, 'pass' => $ok];
}

$results = [];
$results[] = rf_proc_pass('landing: پذیرش', str_contains($wb, 'm360-rw-landing-grid') && str_contains($wb, 'پذیرش'));
$results[] = rf_proc_pass('landing: پروفایل پرسنلی', str_contains($wb, 'پروفایل پرسنلی'));
$results[] = rf_proc_pass('profile: پروفایل پرسنلی من', str_contains($helper, 'پروفایل پرسنلی من'));
$results[] = rf_proc_pass('profile: مرخصی / اضافه‌کاری', str_contains($helper, 'مرخصی / اضافه‌کاری'));
$results[] = rf_proc_pass('profile: مدارک پرسنلی', str_contains($helper, 'مدارک پرسنلی'));
$results[] = rf_proc_pass('profile: فیش حقوقی', str_contains($helper, 'فیش حقوقی'));
$results[] = rf_proc_pass('hub: پذیرش موقت', str_contains($helper, "'title' => 'پذیرش موقت'"));
$results[] = rf_proc_pass('hub: پذیرش', str_contains($helper, "'title' => 'پذیرش'"));
$results[] = rf_proc_pass('hub: کنترل کیفی', str_contains($helper, 'کنترل کیفی'));
$results[] = rf_proc_pass('hub: ترخیص', str_contains($helper, 'ترخیص'));
$results[] = rf_proc_pass('full: پذیرش حضوری', str_contains($helper, 'پذیرش حضوری'));
$results[] = rf_proc_pass('full: پذیرش آنلاین / تکمیل پرونده', str_contains($helper, 'پذیرش آنلاین / تکمیل پرونده'));
$results[] = rf_proc_pass('full: پیگیری پرونده‌های در جریان', str_contains($helper, 'پیگیری پرونده‌های در جریان'));
$results[] = rf_proc_pass('temp: reject on intake', str_contains($intake, 'رد درخواست'));
$results[] = rf_proc_pass('temp: convert blocked message', str_contains($intake, 'تبدیل در پذیرش موقت مجاز نیست') || str_contains($intake, 'تبدیل تا تکمیل'));

require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';
$g = m360_rw_build_gate(
    ['customer_name' => 'T', 'mobile' => '09121234567', 'vehicle_plate' => '12ب34567', 'service_note' => 'x', 'request_status' => 'NEW', 'request_payload_json' => '{"otp_verified":1}'],
    ['otp_verified' => 1, 'service_note' => 'x'],
    null, null, null, [], [], [], null
);
$results[] = rf_proc_pass('temp: convert blocked in gate', ($g['can_show_convert'] ?? true) === false);

$tax = m360_rw_service_classification_taxonomy();
$results[] = rf_proc_pass('taxonomy: کارشناسی و عیب‌یابی', isset($tax['diag']));
$results[] = rf_proc_pass('taxonomy: موتور و گیربکس', str_contains($tax['diag']['subs']['engine_transmission'] ?? '', 'موتور'));
$results[] = rf_proc_pass('taxonomy: سرویس‌های دوره‌ای', isset($tax['periodic']));
$results[] = rf_proc_pass('taxonomy: کارشناسی خرید و فروش', isset($tax['trade']));
$results[] = rf_proc_pass('service: receptionist business text', str_contains($intake, 'M360_RW_SERVICE_CLASS_BUSINESS_PURPOSE_FA') || str_contains($intake, 'گزارش‌گیری مدیریتی'));
$results[] = rf_proc_pass('service: customer request_type note', str_contains($intake, 'M360_RW_CUSTOMER_REQUEST_TYPE_NOTE_FA') || str_contains($intake, 'جایگزین دسته‌بندی خدمات پذیرشگر نیست'));
$results[] = rf_proc_pass('service: write placeholder', str_contains($intake, 'M360_RW_SERVICE_CLASS_WRITE_PLACEHOLDER') || str_contains($intake, 'ثبت دسته‌بندی خدمات در فاز تکمیل عملیات'));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2B-REWORK-FINAL Process Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
