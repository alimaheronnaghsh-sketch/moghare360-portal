<?php
declare(strict_types=1);

/**
 * P11.9-C-2B ROOT CAUSE — read-only field recovery diagnostic (CLI only).
 * Does not write to DB or filesystem.
 */

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function dbg_line(string $label, string $value): void
{
    echo str_pad($label, 28) . ': ' . $value . PHP_EOL;
}

function dbg_section(string $title): void
{
    echo PHP_EOL . str_repeat('=', 72) . PHP_EOL . $title . PHP_EOL . str_repeat('-', 72) . PHP_EOL;
}

$conn = customer_core_db();
if ($conn === false) {
    echo "DB connection: UNAVAILABLE (controlled stop — no fatal)\n";
    exit(0);
}

echo "DB connection: OK\n";
echo "Helper path: " . realpath($root . '/public_html/includes/m360-reception-workbench-helper.php') . "\n";

foreach ([18, 20] as $requestId) {
    dbg_section("ONLINE REQUEST ID {$requestId}");

    $request = m360_online_req_fetch_by_id($conn, $requestId);
    dbg_line('request row loaded', $request !== null ? 'yes' : 'no');
    if ($request === null) {
        continue;
    }

    $payloadJson = (string)($request['request_payload_json'] ?? '');
    dbg_line('payload exists', $payloadJson !== '' ? 'yes' : 'no');
    dbg_line('payload length', (string)strlen($payloadJson));

    $payloadMeta = m360_rw_decode_payload($payloadJson !== '' ? $payloadJson : null);
    dbg_line('payload JSON valid', ($payloadMeta['valid'] ?? false) ? 'yes' : 'no');
    if (!($payloadMeta['valid'] ?? false) && ($payloadMeta['raw_warning'] ?? '') !== '') {
        dbg_line('payload warning', (string)$payloadMeta['raw_warning']);
    }

    $payload = $payloadMeta['items'] ?? [];
    $keys = array_keys($payload);
    sort($keys);
    dbg_line('payload key count', (string)count($keys));
    dbg_line('payload keys', $keys === [] ? '(empty)' : implode(', ', $keys));

    dbg_line('customer_id', (string)($request['customer_id'] ?? '0'));
    dbg_line('vehicle_id', (string)($request['vehicle_id'] ?? '0'));
    dbg_line('vehicle_plate column', (string)($request['vehicle_plate'] ?? ''));
    dbg_line('mobile column', (string)($request['mobile'] ?? ''));
    dbg_line('request_status', (string)($request['request_status'] ?? ''));
    dbg_line('otp_verified column', (string)($request['otp_verified'] ?? '(absent)'));
    dbg_line('otp in payload', isset($payload['otp_verified']) ? (string)$payload['otp_verified'] : '(absent)');

    $file = m360_rw_build_intake_file($conn, $requestId);
    $recovery = $file['field_recovery'] ?? [];
    $gate = $file['gate'] ?? [];

    $fields = ['plate', 'vin', 'brand', 'model', 'mileage', 'fuel', 'vehicle', 'otp'];
    foreach ($fields as $f) {
        $r = $recovery[$f] ?? [];
        $val = (string)($r['value'] ?? '');
        $src = (string)($r['source_label'] ?? ($r['source_key'] ?? ''));
        $miss = (string)($r['missing_label'] ?? '');
        $present = !empty($r['present']) ? 'yes' : 'no';
        dbg_line("{$f} value", $val !== '' ? $val : '(empty)');
        dbg_line("{$f} present", $present);
        dbg_line("{$f} source", $src !== '' ? $src : '(none)');
        if ($miss !== '') {
            dbg_line("{$f} missing_label", $miss);
        }
        if (!empty($r['partial'])) {
            dbg_line("{$f} partial", 'yes');
            dbg_line("{$f} detail", (string)($r['detail'] ?? ''));
        }
    }

    if (isset($recovery['otp']['verified'])) {
        dbg_line('OTP verified (helper)', ($recovery['otp']['verified'] ?? false) ? 'yes' : 'no');
    }

    dbg_line('gate status', (string)($gate['status'] ?? ''));
    dbg_line('gate label', (string)($gate['label_fa'] ?? ''));
    $missing = $gate['missing'] ?? [];
    dbg_line('gate missing count', (string)count($missing));
    foreach ($missing as $i => $m) {
        dbg_line('gate missing[' . $i . ']', (string)$m);
    }

    $partial = $gate['partial_notes'] ?? [];
    foreach ($partial as $i => $p) {
        dbg_line('partial note[' . $i . ']', (string)$p);
    }

    // Raw column vs payload comparison for plate
    $plateCol = trim((string)($request['vehicle_plate'] ?? ''));
    $platePayload = m360_rw_pick([$payload], 'vehicle_plate', 'plate', 'plate_display', 'plate_number');
    dbg_line('plate column raw', $plateCol !== '' ? $plateCol : '(empty)');
    dbg_line('plate payload raw', $platePayload !== '' ? $platePayload : '(empty)');

    $vehicleRow = ($file['vehicle'] ?? null);
    dbg_line('erp_vehicles row fetched', $vehicleRow !== null ? 'yes' : 'no');
    if ($vehicleRow !== null) {
        dbg_line('erp vehicle plate_number', (string)($vehicleRow['plate_number'] ?? ''));
        dbg_line('erp vehicle vin', (string)($vehicleRow['vin'] ?? ''));
        dbg_line('erp vehicle brand', (string)($vehicleRow['brand'] ?? ''));
        dbg_line('erp vehicle model', (string)($vehicleRow['model'] ?? ''));
    }

    dbg_line('customer_vehicle_binding queried', 'no (not implemented in helper)');
}

echo PHP_EOL;
