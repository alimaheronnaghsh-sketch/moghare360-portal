<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function c2c_pl_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$base = [
    'customer_name' => 'Ali',
    'mobile' => '09121234567',
    'service_note' => 'noise',
    'source' => 'test',
    'otp_verified' => 0,
];

$vehiclePost = [
    'plate' => '12ب345-67',
    'vin' => 'VIN123456',
    'brand' => 'BMW',
    'model' => 'X5',
    'mileage' => '85000',
    'fuel_level' => 'نصف',
];
$applied = m360_rw_intake_apply_action($base, 'save_vehicle_identity', $vehiclePost);
$p = $applied['payload'];
$results[] = c2c_pl_pass('vehicle apply ok', $applied['ok']);
$results[] = c2c_pl_pass('preserves customer_name', ($p['customer_name'] ?? '') === 'Ali');
$results[] = c2c_pl_pass('preserves otp_verified', (int)($p['otp_verified'] ?? -1) === 0);
$results[] = c2c_pl_pass('vehicle plate top-level', ($p['vehicle_plate'] ?? '') === '12ب345-67');
$results[] = c2c_pl_pass('nested reception_intake vehicle', ($p['reception_intake']['vehicle']['plate'] ?? '') === '12ب345-67');
$results[] = c2c_pl_pass('mileage stored', ($p['odometer_km'] ?? '') === '85000');

$svcPost = [
    'service_primary' => 'diag',
    'service_diag_sub' => ['engine_transmission', 'electrical_battery'],
    'service_path_clear' => '1',
    'service_path_note' => 'مسیر مشخص',
];
$applied2 = m360_rw_intake_apply_action($p, 'save_service_classification', $svcPost);
$p2 = $applied2['payload'];
$results[] = c2c_pl_pass('service classification ok', $applied2['ok']);
$results[] = c2c_pl_pass('reception_service_primary', ($p2['reception_service_primary'] ?? '') === 'diag');
$results[] = c2c_pl_pass('diag sub array', is_array($p2['reception_service_diag_sub'] ?? null) && count($p2['reception_service_diag_sub']) === 2);
$results[] = c2c_pl_pass('service path clear', ($p2['fault_service_path_clear'] ?? '') === '1');
$results[] = c2c_pl_pass('nested service classification', ($p2['reception_intake']['service_classification']['main'] ?? '') === 'diag');

$condPost = ['vehicle_items' => 'جک', 'visible_damage' => 'خط روی در', 'initial_vehicle_condition' => 'سالم'];
$applied3 = m360_rw_intake_apply_action($p2, 'save_condition_notes', $condPost);
$p3 = $applied3['payload'];
$results[] = c2c_pl_pass('condition notes ok', $applied3['ok']);
$results[] = c2c_pl_pass('belongings stored', ($p3['belongings'] ?? '') === 'جک');

$docPost = [
    'photo_status' => 'ثبت شد',
    'diagnostic_status' => 'ثبت شد',
    'contract_status' => 'در انتظار',
    'cost_agreement' => 'توافق شد',
    'cost_agreement_note' => 'نقد',
];
$applied4 = m360_rw_intake_apply_action($p3, 'save_documents_and_cost', $docPost);
$p4 = $applied4['payload'];
$results[] = c2c_pl_pass('documents ok', $applied4['ok']);
$results[] = c2c_pl_pass('cost agreement stored', ($p4['cost_agreement'] ?? '') === 'توافق شد');

$confirmPost = ['confirmed_by_receptionist' => '1', 'confirmation_note' => 'تأیید پذیرش'];
$applied5 = m360_rw_intake_apply_action($p4, 'save_reception_confirmation', $confirmPost);
$p5 = $applied5['payload'];
$results[] = c2c_pl_pass('confirmation ok', $applied5['ok']);
$results[] = c2c_pl_pass('reception_final_confirmation', ($p5['reception_final_confirmation'] ?? '') === '1');
$results[] = c2c_pl_pass('still no otp fake', (int)($p5['otp_verified'] ?? 0) === 0);

$recovery = m360_rw_intake_payload_for_recovery($p5);
$results[] = c2c_pl_pass('recovery reads nested plate', m360_rw_pick([$recovery], 'vehicle_plate') === '12ب345-67');

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C Intake Write Payload Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
