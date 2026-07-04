<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$intake = $root . '/public_html/erp-reception-intake-file.php';
$workbench = $root . '/public_html/erp-reception-workbench.php';
$helper = $root . '/public_html/includes/m360-reception-workbench-helper.php';

function fixa_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$intakeContent = is_file($intake) ? (string)file_get_contents($intake) : '';
$wbContent = is_file($workbench) ? (string)file_get_contents($workbench) : '';
$helperContent = is_file($helper) ? (string)file_get_contents($helper) : '';
$combined = $intakeContent . $helperContent;

$results = [];

$labels = [
    'کارشناسی و عیب‌یابی',
    'موتور و گیربکس',
    'برق و باتری',
    'زیروبند و تعلیق',
    'مبلمان داخلی',
    'خدمات بدنه',
    'آپشن',
    'سرویس‌های دوره‌ای',
    'کارشناسی خرید و فروش',
];
foreach ($labels as $label) {
    $results[] = fixa_pass('service classification label: ' . $label, str_contains($combined, $label));
}

$results[] = fixa_pass('receptionist-filled not customer-filled', str_contains($intakeContent, 'ثبت توسط پذیرشگر') || str_contains($intakeContent, 'توسط پذیرشگر'));
$results[] = fixa_pass('service class write placeholder', str_contains($combined, 'ثبت دسته‌بندی خدمات در فاز تکمیل عملیات پذیرش'));
$results[] = fixa_pass('gate item service classification message', str_contains($helperContent, 'دسته‌بندی خدمات توسط پذیرشگر ثبت نشده است'));
$results[] = fixa_pass('temporary reception in gate', str_contains($helperContent, 'temporary_reception') || str_contains($helperContent, 'پذیرش موقت'));
$results[] = fixa_pass('temporary allows reject on intake', str_contains($intakeContent, 'رد درخواست') && str_contains($intakeContent, 'اقدامات پذیرش موقت'));
$results[] = fixa_pass('temp blocks convert message', str_contains($intakeContent, 'تبدیل در پذیرش موقت مجاز نیست'));
$results[] = fixa_pass('workbench landing پذیرش only two cards', str_contains($wbContent, 'm360-rw-landing-grid') && str_contains($wbContent, 'پروفایل پرسنلی'));
$results[] = fixa_pass('reception hub پذیرش موقت', str_contains($helperContent, 'پذیرش موقت'));
$results[] = fixa_pass('reception hub کنترل کیفی', str_contains($helperContent, 'کنترل کیفی'));
$results[] = fixa_pass('reception hub ترخیص', str_contains($helperContent, 'ترخیص'));

require_once $helper;

$gTemp = m360_rw_build_gate(
    ['customer_name' => 'T', 'mobile' => '09121234567', 'vehicle_plate' => '12ب34567', 'service_note' => 'noise', 'request_status' => 'NEW', 'request_payload_json' => '{"otp_verified":1}'],
    ['otp_verified' => 1, 'service_note' => 'noise'],
    null, null, null, [], [], [], null
);
$results[] = fixa_pass('no service class blocks convert', ($gTemp['can_show_convert'] ?? true) === false);
$results[] = fixa_pass('no service class -> unclear fault or temp', in_array($gTemp['status'] ?? '', ['complete_unclear_fault', 'temporary_reception'], true));
$results[] = fixa_pass('temp actions allowed when not converted', ($gTemp['can_show_temp_actions'] ?? false) === true);

$gReady = m360_rw_build_gate(
    ['customer_name' => 'T', 'mobile' => '09121234567', 'vehicle_plate' => '12ب34567', 'service_note' => 'noise', 'request_status' => 'NEW', 'request_payload_json' => '{"otp_verified":1}'],
    ['otp_verified' => 1, 'service_note' => 'noise', 'reception_service_primary' => 'diag', 'reception_service_diag_sub' => ['engine_transmission'], 'fault_service_path_clear' => '1'],
    null, null, null, [], [], [], null
);
$results[] = fixa_pass('service class registered in gate', ($gReady['service_classification_registered'] ?? false) === true);
$results[] = fixa_pass('with service class not temp/unclear', !in_array($gReady['status'] ?? '', ['complete_unclear_fault', 'temporary_reception'], true));

$sqlRecent = false;
foreach (glob($root . '/database/migrations/*.sql') ?: [] as $sql) {
    if (filemtime($sql) > time() - 300) {
        $sqlRecent = true;
        break;
    }
}
$results[] = fixa_pass('no DB schema migration added', !$sqlRecent);
$results[] = fixa_pass('no customer-facing service form in intake', !str_contains($intakeContent, 'name="service_classification"'));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2B-FIX-A Process Correction Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
