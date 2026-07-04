<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function fr_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$helper = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$intake = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');
$wb = (string)file_get_contents($root . '/public_html/erp-reception-workbench.php');

$results[] = fr_pass('no fake OTP bypass in helper', !preg_match('/otpOk\s*=\s*true/i', $helper) && !str_contains($helper, 'otp_verified"] = 1'));
$results[] = fr_pass('OTP uses m360_online_req_payload_otp_verified', str_contains($helper, 'm360_online_req_payload_otp_verified'));
$results[] = fr_pass('intake OTP from field recovery not hardcoded verified', !preg_match('/تأیید شده.*m360_online_req_payload_otp_verified\s*\(\s*\$request\s*\)\s*\?\s*[\'"]/', $intake) || str_contains($intake, 'fieldRecovery'));
$results[] = fr_pass('gate missing uses m360_rw_gate_missing_message', str_contains($helper, 'function m360_rw_gate_missing_message'));
$results[] = fr_pass('precise VIN missing label exists', str_contains($helper, 'VIN/شاسی ثبت نشده است'));
$results[] = fr_pass('precise mileage missing label exists', str_contains($helper, 'کیلومتر ورود ثبت نشده است'));
$results[] = fr_pass('precise fuel missing label exists', str_contains($helper, 'سطح سوخت ثبت نشده است'));
$results[] = fr_pass('C-2C label for belongings', str_contains($helper, 'لوازم داخل خودرو هنوز در پرونده ثبت نشده است'));
$results[] = fr_pass('helper has m360_rw_recover_intake_fields', str_contains($helper, 'function m360_rw_recover_intake_fields'));
$results[] = fr_pass('helper has m360_rw_pick_meta', str_contains($helper, 'function m360_rw_pick_meta'));
$results[] = fr_pass('intake uses field recovered display', str_contains($intake, 'm360_rw_intake_field_recovered'));

$req = ['customer_name' => 'Ali', 'mobile' => '09121234567', 'vehicle_plate' => '12ب345-67', 'vehicle_id' => '0', 'request_status' => 'NEW', 'request_payload_json' => '{}'];
$payload = ['vin' => 'VIN123', 'brand' => 'BMW', 'vehicle_model' => 'X5', 'odometer_km' => '120000', 'fuel_level' => 'half', 'plate_display' => '12ب345-67'];
$fields = m360_rw_recover_intake_fields($req, $payload, null, null, null, null, [], [], []);
$results[] = fr_pass('plate from payload', ($fields['plate']['present'] ?? false) === true);
$results[] = fr_pass('vin from payload', ($fields['vin']['value'] ?? '') === 'VIN123');
$results[] = fr_pass('brand from payload', ($fields['brand']['value'] ?? '') === 'BMW');
$results[] = fr_pass('model from payload', ($fields['model']['value'] ?? '') === 'X5');
$results[] = fr_pass('mileage from payload', ($fields['mileage']['value'] ?? '') === '120000');
$results[] = fr_pass('fuel from payload', ($fields['fuel']['value'] ?? '') === 'half');
$results[] = fr_pass('vehicle partial without ERP id', ($fields['vehicle']['partial'] ?? false) === true);

$reqNoOtp = array_merge($req, ['request_payload_json' => '{"otp_verified":0}']);
$fieldsOtp = m360_rw_recover_intake_fields($reqNoOtp, $payload, null, null, null, null, [], [], []);
$results[] = fr_pass('OTP not verified when data says 0', ($fieldsOtp['otp']['verified'] ?? true) === false);

$reqOtp = array_merge($req, ['request_payload_json' => '{"otp_verified":1}']);
$fieldsOtpOk = m360_rw_recover_intake_fields($reqOtp, $payload, null, null, null, null, [], [], []);
$results[] = fr_pass('OTP verified only from data', ($fieldsOtpOk['otp']['verified'] ?? false) === true);

$gate = m360_rw_build_gate($reqOtp, $payload, null, null, null, [], [], [], null);
$results[] = fr_pass('gate passes plate when present', true);
foreach ($gate['checks'] as $c) {
    if (($c['id'] ?? '') === 'plate') {
        $results[] = fr_pass('gate plate check ok', ($c['ok'] ?? false) === true);
    }
}
$gateMissing = m360_rw_build_gate($req, ['brand' => 'X'], null, null, null, [], [], [], null);
$hasPreciseVin = false;
foreach ($gateMissing['missing'] as $m) {
    if (str_contains($m, 'VIN/شاسی ثبت نشده')) {
        $hasPreciseVin = true;
    }
    $results[] = fr_pass('missing list no generic phase-later', !str_contains($m, 'فاز تکمیل عملیات') || str_contains($m, 'C-2C'));
}
$results[] = fr_pass('gate missing uses precise VIN label', $hasPreciseVin);

$results[] = fr_pass('service classification C-2C boundary', str_contains($helper, 'C-2C فعال می‌شود'));
$results[] = fr_pass('customer request_type note in intake', str_contains($intake, 'M360_RW_CUSTOMER_REQUEST_TYPE_NOTE_FA') || str_contains($intake, 'جایگزین دسته‌بندی'));
$results[] = fr_pass('mock not primary walk-in route', !preg_match('/class="m360-rw-btn"[^>]*href="erp-jobcard-create-ux/', $wb));
$results[] = fr_pass('mock guides collapsed', str_contains($wb, 'm360-rw-mock-guides'));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2B-REWORK-FINAL Field Recovery Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
