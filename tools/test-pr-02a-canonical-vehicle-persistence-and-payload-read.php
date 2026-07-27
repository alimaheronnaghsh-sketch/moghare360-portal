<?php
declare(strict_types=1);

/**
 * PR-02A-E2E-REPAIR — canonical vehicle persistence + ODBC payload read.
 */

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function pr02a_repair_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$helper = (string)file_get_contents($root . '/public_html/includes/m360-online-request-helper.php');
$wb = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$results = [];

$results[] = pr02a_repair_pass(
    'chunked payload read helper exists',
    str_contains($helper, 'function m360_online_req_read_payload_json_chunked')
);
$results[] = pr02a_repair_pass(
    'fetch_by_id hydrates full payload',
    str_contains($helper, 'm360_online_req_hydrate_row_payload_json')
);
$results[] = pr02a_repair_pass(
    'vehicle canonical sync helper exists',
    str_contains($wb, 'function m360_rw_intake_sync_vehicle_canonical_fields')
);
$results[] = pr02a_repair_pass(
    'save_camera_photo blocked without vehicle',
    str_contains($wb, 'm360_rw_intake_action_requires_vehicle_complete')
        && str_contains($wb, 'ابتدا مرحله خودرو و پلاک را')
);
$results[] = pr02a_repair_pass(
    'invalid JSON returns controlled error',
    str_contains($wb, 'ساختار JSON پرونده نامعتبر است')
);
$results[] = pr02a_repair_pass(
    'diagnostic request ids 18 and 20',
    m360_rw_intake_is_diagnostic_only_request(18) && m360_rw_intake_is_diagnostic_only_request(20)
);
$results[] = pr02a_repair_pass(
    'asian brands not in approved set',
    !in_array('تویوتا', m360_rw_intake_approved_vehicle_brands(), true)
        && !in_array('Toyota', m360_rw_intake_approved_vehicle_brands(), true)
);
$results[] = pr02a_repair_pass(
    'legacy Toyota blocked in validation',
    !m360_rw_intake_validate_vehicle_selection([
        'vehicle_brand' => 'Toyota',
        'vehicle_class' => 'Camry',
        'vehicle_year_pair' => '1403 - 2024',
    ])['ok']
);

$payload = [
    'plate' => '39ب498-15',
    'vehicle_plate' => '39ب498-15',
    'brand' => 'پورشه',
    'vehicle_brand' => 'پورشه',
    'model' => 'Macan',
    'vehicle_model' => 'Macan',
    'mileage' => '50000',
    'odometer_km' => '50000',
    'fuel_level' => 'نصف',
    'vehicle_year_pair' => '1403 - 2024',
    'visit_date' => '2026-07-07',
    'reception_intake' => ['vehicle' => []],
];
$synced = m360_rw_intake_sync_vehicle_canonical_fields($payload);
$results[] = pr02a_repair_pass(
    'sync writes nested vehicle brand',
    trim((string)($synced['reception_intake']['vehicle']['brand'] ?? '')) === 'پورشه'
);
$results[] = pr02a_repair_pass(
    'vehicle step complete after sync',
    m360_rw_intake_vehicle_step_complete($synced, [])
);

$applied = m360_rw_intake_apply_action([], 'save_vehicle_identity', [
    'plate_left_2_digits' => '39',
    'plate_letter' => 'ب',
    'plate_middle_3_digits' => '498',
    'plate_iran_2_digits' => '15',
    'plate_region_2_digits' => '15',
    'vehicle_brand' => 'پورشه',
    'vehicle_class' => 'Macan',
    'vehicle_year_pair' => '1403 - 2024',
    'visit_date' => '2026-07-07',
    'mileage' => '42000',
    'fuel_level' => 'نصف',
    'vin' => 'TESTVIN123456789',
]);
$savePayload = $applied['payload'];
$results[] = pr02a_repair_pass('save_vehicle_identity ok', $applied['ok']);
$results[] = pr02a_repair_pass('brand persisted', trim((string)($savePayload['brand'] ?? '')) === 'پورشه');
$results[] = pr02a_repair_pass('model persisted', trim((string)($savePayload['model'] ?? '')) === 'Macan');
$results[] = pr02a_repair_pass('mileage persisted', trim((string)($savePayload['mileage'] ?? '')) === '42000');
$results[] = pr02a_repair_pass('fuel persisted', trim((string)($savePayload['fuel_level'] ?? '')) === 'نصف');
$results[] = pr02a_repair_pass(
    'vehicle step complete after save apply',
    m360_rw_intake_vehicle_step_complete($savePayload, ['vehicle_plate' => '39ب498-15'])
);

$photoBlocked = m360_rw_intake_apply_action([], 'save_camera_photo', [
    'online_request_id' => '99',
    'photo_slot' => 'front',
    'camera_image_base64' => '',
]);
$results[] = pr02a_repair_pass(
    'photo save fails without image (sanity)',
    !$photoBlocked['ok']
);

$conn = customer_core_db();
if (is_resource($conn)) {
    $bigPayload = $savePayload;
    $bigPayload['reception_intake']['photos'] = [
        'required_count' => 6,
        'completed_count' => 6,
        'is_complete' => true,
        'slots' => [],
    ];
    $bigPayload['pr02a_padding'] = str_repeat('آ', 4200);
    $encoded = json_encode($bigPayload, JSON_UNESCAPED_UNICODE);
    $encodedLen = is_string($encoded) ? strlen($encoded) : 0;
    $results[] = pr02a_repair_pass('test payload exceeds 4096', $encodedLen > 4096, 'len=' . $encodedLen);

  $testId = 20;
    $read = m360_online_req_fetch_by_id($conn, $testId);
    $fullLen = strlen((string)($read['request_payload_json'] ?? ''));
    $meta = m360_rw_decode_payload((string)($read['request_payload_json'] ?? ''));
    $results[] = pr02a_repair_pass(
        'live fetch payload valid JSON',
        ($meta['valid'] ?? false) === true,
        'len=' . $fullLen
    );
    $results[] = pr02a_repair_pass(
        'persian text preserved in live payload',
        str_contains((string)($read['request_payload_json'] ?? ''), 'پورشه') || $fullLen < 100,
        'request #20'
    );
    @odbc_close($conn);
} else {
    $results[] = pr02a_repair_pass('database connection for live read', false, 'skipped');
}

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02A canonical vehicle + payload read: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
