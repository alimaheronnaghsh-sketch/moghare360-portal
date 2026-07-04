<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function c2c_pr_pass(string $name, bool $ok): array
{
    return ['name' => $name, 'pass' => $ok];
}

$results = [];
$detail = (string)file_get_contents($root . '/public_html/erp-reception-online-request-detail.php');
$intake = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');

$results[] = c2c_pr_pass('intake service class note', str_contains($intake, 'M360_RW_CUSTOMER_REQUEST_TYPE_NOTE_FA'));
$results[] = c2c_pr_pass('detail no raw convert button', !preg_match('/action\s*=\s*"convert_to_jobcard"/', $detail));

$req = [
    'customer_name' => 'T',
    'mobile' => '09121234567',
    'vehicle_plate' => '12ب34567',
    'service_note' => 'x',
    'request_status' => 'NEW',
    'request_payload_json' => '{"otp_verified":1}',
];
$payloadClassNoPath = [
    'otp_verified' => 1,
    'reception_service_primary' => 'diag',
    'reception_service_diag_sub' => ['engine_transmission'],
    'fault_service_path_clear' => '0',
];
$gTemp = m360_rw_build_gate($req, $payloadClassNoPath, null, null, null, [], [], [], null);
$results[] = c2c_pr_pass('service registered path unclear => temporary', ($gTemp['status'] ?? '') === 'temporary_reception');

$sixPhotos = [];
foreach (array_keys(m360_rw_intake_reception_photo_slots()) as $slotKey) {
    $sixPhotos[$slotKey] = ['file' => 'reception-intake/1/' . $slotKey . '.jpg', 'label' => m360_rw_intake_reception_photo_slots()[$slotKey]];
}
$payloadPathClear = array_merge($payloadClassNoPath, [
    'fault_service_path_clear' => '1',
    'vin' => 'V1', 'brand' => 'B', 'model' => 'M',
    'odometer_km' => '1', 'fuel_level' => 'نصف',
    'belongings' => 'x', 'visible_damage' => 'x',
    'reception_intake' => [
        'documents' => [
            'reception_photos' => $sixPhotos,
            'photo_count' => 6,
            'photo_min_required' => 6,
            'photo_status' => 'ثبت شد',
            'diagnostic_status' => 'ثبت شد',
            'contract_status' => 'ثبت شد',
        ],
    ],
    'diagnostic_status' => 'ثبت شد', 'contract_status' => 'ثبت شد',
    'cost_agreement' => 'ok', 'reception_final_confirmation' => '1',
]);
$gFull = m360_rw_build_gate($req, $payloadPathClear, null, null, null, [], [], [], null);
$results[] = c2c_pr_pass('ready_convert only when all pass + otp', ($gFull['status'] ?? '') === 'ready_convert');
$results[] = c2c_pr_pass('can_show_convert matches ready_convert', ($gFull['can_show_convert'] ?? false) === true);

$reqNoOtp = array_merge($req, ['request_payload_json' => '{"otp_verified":0}']);
$payloadNoOtp = $payloadPathClear;
$payloadNoOtp['otp_verified'] = 0;
$gOtp = m360_rw_build_gate($reqNoOtp, $payloadNoOtp, null, null, null, [], [], [], null);
$results[] = c2c_pr_pass('otp blocks ready_convert', ($gOtp['status'] ?? '') !== 'ready_convert');

$reqRej = array_merge($req, ['request_status' => M360_ONLINE_REQ_STATUS_REJECTED]);
$gRej = m360_rw_build_gate($reqRej, $payloadPathClear, null, null, null, [], [], [], null);
$results[] = c2c_pr_pass('rejected gate state', ($gRej['status'] ?? '') === 'rejected');

$svc = m360_rw_parse_service_classification($payloadClassNoPath, $req);
$results[] = c2c_pr_pass('service registered separate from request_type', $svc['registered'] === true);
$results[] = c2c_pr_pass('fault path not auto true when registered only', $svc['fault_path_clear'] === false);

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C Intake Write Process Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
