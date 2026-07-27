<?php
declare(strict_types=1);

/**
 * PR-02A — Controlled local fake completion for online_request_id = 20 only.
 *
 * Usage:
 *   php tools/test-pr-02a-request-20-fake-completion.php --restore --snapshot=docs/audit/request_20_before_fake_completion_snapshot.json --confirm=TEST_PR02A_REQ20_RESTORE
 *   php tools/test-pr-02a-request-20-fake-completion.php --confirm=TEST_PR02A_REQ20_FAKE_COMPLETION --mode=minimal
 *   php tools/test-pr-02a-request-20-fake-completion.php --confirm=TEST_PR02A_REQ20_FAKE_COMPLETION --mode=full
 */

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';
require_once $root . '/tools/test-pr-02a-request-20-dataflow-diagnostics.php';

const PR02A_FAKE_MARKER = 'TEST_PR02A_REQ20_FAKE_COMPLETION';
const PR02A_RESTORE_MARKER = 'TEST_PR02A_REQ20_RESTORE';
const PR02A_FAKE_REQ_ID = 20;
const PR02A_FAKE_MAX_PAYLOAD_BYTES = 3800;

$confirm = '';
$mode = 'minimal';
$restore = false;
$snapshotPath = '';

foreach ($argv as $arg) {
    if (preg_match('/^--confirm=(.+)$/', $arg, $m)) {
        $confirm = $m[1];
    }
    if (preg_match('/^--mode=(.+)$/', $arg, $m)) {
        $mode = strtolower(trim($m[1]));
    }
    if ($arg === '--restore') {
        $restore = true;
    }
    if (preg_match('/^--snapshot=(.+)$/', $arg, $m)) {
        $snapshotPath = $m[1];
    }
}

/** @return array{ok:bool,message:string} */
function pr02a_req20_restore_from_snapshot($conn, string $snapshotPath): array
{
    $fullPath = str_starts_with($snapshotPath, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:/', $snapshotPath)
        ? $snapshotPath
        : dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $snapshotPath);

    if (!is_file($fullPath)) {
        return ['ok' => false, 'message' => 'Snapshot file not found: ' . $fullPath];
    }

    $raw = file_get_contents($fullPath);
    if ($raw === false) {
        return ['ok' => false, 'message' => 'Unable to read snapshot file.'];
    }

    $snapshot = json_decode($raw, true);
    if (!is_array($snapshot) || empty($snapshot['payload_decoded'])) {
        return ['ok' => false, 'message' => 'Snapshot missing payload_decoded.'];
    }

    $payload = $snapshot['payload_decoded'];
    unset($payload['pr02a_fake_completion']);

    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        return ['ok' => false, 'message' => 'Failed to encode snapshot payload.'];
    }

    $sql = 'UPDATE dbo.' . m360_online_req_table() . ' SET request_payload_json = ?';
  $params = [$encoded];
    if (m360_online_req_has_column($conn, 'updated_at') && !empty($snapshot['request_row']['updated_at'])) {
        $sql .= ', updated_at = ?';
        $params[] = (string)$snapshot['request_row']['updated_at'];
    }
    $sql .= ' WHERE online_request_id = ?';
    $params[] = PR02A_FAKE_REQ_ID;

    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return ['ok' => false, 'message' => 'Restore UPDATE failed.'];
    }

    $verify = pr02a_req20_read_payload_json($conn, PR02A_FAKE_REQ_ID);
    $meta = m360_rw_decode_payload($verify);
    if (!$meta['valid']) {
        return ['ok' => false, 'message' => 'Restore verify failed: payload JSON invalid after write.'];
    }

    m360_online_req_write_history(
        $conn,
        PR02A_FAKE_REQ_ID,
        'PR02A_RESTORE_' . PR02A_RESTORE_MARKER,
        (string)($snapshot['request_row']['request_status'] ?? ''),
        (string)($snapshot['request_row']['request_status'] ?? ''),
        PR02A_RESTORE_MARKER,
        1
    );

    return ['ok' => true, 'message' => ''];
}

/** @param array<string, mixed> $payload */
function pr02a_req20_fill_vehicle_only(array $payload, array $request): array
{
    $payload = m360_rw_intake_ensure_nested($payload);
    $filled = [];
    $preserved = [];

    $vehicle = is_array($payload['reception_intake']['vehicle'] ?? null)
        ? $payload['reception_intake']['vehicle']
        : [];
    $canonical = m360_rw_intake_vehicle_canonical($payload, $request);

    $setVehicle = static function (string $key, string $value) use (&$vehicle, &$payload, &$filled, &$preserved, $canonical): void {
        $topKeys = [
            'plate' => 'vehicle_plate',
            'brand' => 'brand',
            'model' => 'model',
            'mileage' => 'mileage',
            'fuel_level' => 'fuel_level',
            'vin' => 'vin',
        ];
        $current = trim((string)($vehicle[$key] ?? $canonical[$key] ?? $payload[$topKeys[$key] ?? $key] ?? ''));
        if ($current !== '') {
            $preserved[] = $key;
            return;
        }
        $vehicle[$key] = $value;
        $payload[$topKeys[$key] ?? $key] = $value;
        if ($key === 'brand') {
            $payload['vehicle_brand'] = $value;
        }
        if ($key === 'model') {
            $payload['vehicle_model'] = $value;
        }
        if ($key === 'mileage') {
            $payload['odometer_km'] = $value;
        }
        $filled[] = $key;
    };

    $plate = trim($canonical['plate']);
    if ($plate !== '') {
        $preserved[] = 'plate';
    }
    $vehicle['plate'] = $plate !== '' ? $plate : '39ب498-15';
    $payload['plate'] = $vehicle['plate'];
    $payload['vehicle_plate'] = $vehicle['plate'];

    $setVehicle('brand', 'پورشه');
    $setVehicle('model', 'Macan');
    $setVehicle('mileage', '50000');
    $setVehicle('fuel_level', 'نصف');
    $setVehicle('vin', 'TEST_PR02A_REQ20_VIN');

    $yearOpts = m360_rw_intake_vehicle_year_options();
    $yearVal = trim((string)($vehicle['vehicle_year_pair'] ?? $payload['vehicle_year_pair'] ?? ''));
    if ($yearVal === '' && $yearOpts !== []) {
        $yearVal = (string)($yearOpts[0]['value'] ?? '1403-1404');
        $vehicle['vehicle_year_pair'] = $yearVal;
        $payload['vehicle_year_pair'] = $yearVal;
        $filled[] = 'vehicle_year_pair';
    } else {
        $preserved[] = 'vehicle_year_pair';
    }

    $visitDate = trim((string)($vehicle['visit_date'] ?? $payload['visit_date'] ?? $request['visit_date'] ?? ''));
    if ($visitDate === '') {
        foreach (m360_rw_calendar_next_30_day_window() as $day) {
            if (!empty($day['is_selectable'])) {
                $visitDate = (string)$day['gregorian'];
                break;
            }
        }
        if ($visitDate !== '') {
            $vehicle['visit_date'] = $visitDate;
            $payload['visit_date'] = $visitDate;
            $filled[] = 'visit_date';
        }
    } else {
        $preserved[] = 'visit_date';
    }

    $payload['reception_intake']['vehicle'] = $vehicle;

    if (is_array($payload['form_values'] ?? null)) {
        foreach ([
            'brand' => 'پورشه',
            'model' => 'Macan',
            'mileage' => '50000',
            'fuel_level' => 'نصف',
            'vin' => 'TEST_PR02A_REQ20_VIN',
            'vehicle_year_pair' => (string)($payload['vehicle_year_pair'] ?? ''),
            'visit_date' => (string)($payload['visit_date'] ?? ''),
        ] as $fk => $fv) {
            if (trim((string)($payload['form_values'][$fk] ?? '')) === '' && $fv !== '') {
                $payload['form_values'][$fk] = $fv;
                $filled[] = 'form_values.' . $fk;
            }
        }
    }

    $payload['pr02a_fake_completion'] = [
        'marker' => PR02A_FAKE_MARKER,
        'mode' => 'minimal',
        'applied_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];

    return ['payload' => $payload, 'filled' => $filled, 'preserved' => $preserved];
}

/** @param array<string, mixed> $payload */
function pr02a_req20_fill_full(array $payload, array $request): array
{
    $result = pr02a_req20_fill_missing($payload, $request);
    $result['payload']['pr02a_fake_completion']['mode'] = 'full';
    return $result;
}

/** @param array<string, mixed> $payload */
function pr02a_req20_fill_missing(array $payload, array $request): array
{
    $base = pr02a_req20_fill_vehicle_only($payload, $request);
    $payload = $base['payload'];
    $filled = $base['filled'];
    $preserved = $base['preserved'];
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $testNote = PR02A_FAKE_MARKER;

    $condition = is_array($payload['reception_intake']['condition'] ?? null)
        ? $payload['reception_intake']['condition']
        : [];
    foreach ([
        'vehicle_items' => $testNote . ' belongings',
        'visible_damage' => $testNote . ' no visible damage',
        'initial_vehicle_condition' => $testNote . ' OK',
    ] as $ck => $cv) {
        if (trim((string)($condition[$ck] ?? '')) === '') {
            $condition[$ck] = $cv;
            $filled[] = 'condition.' . $ck;
        } else {
            $preserved[] = 'condition.' . $ck;
        }
    }
    $payload['reception_intake']['condition'] = $condition;

    $svc = is_array($payload['reception_intake']['service_classification'] ?? null)
        ? $payload['reception_intake']['service_classification']
        : [];
    if (trim((string)($svc['route'] ?? '')) === '') {
        $svc['route'] = 'periodic';
        $filled[] = 'service.route';
    } else {
        $preserved[] = 'service.route';
    }
    if (empty($svc['service_path_clear'])) {
        $svc['service_path_clear'] = true;
        $filled[] = 'service.service_path_clear';
    } else {
        $preserved[] = 'service.service_path_clear';
    }
    $payload['reception_intake']['service_classification'] = $svc;
    $payload['service_route'] = (string)($svc['route'] ?? 'periodic');
    $payload['service_path_clear'] = '1';

    if (!m360_rw_intake_photos_complete($payload)) {
        $slots = m360_rw_intake_reception_photo_slots();
        $photoSlots = [];
        foreach (array_keys($slots) as $key) {
            $photoSlots[$key] = [
                'label' => $slots[$key],
                'status' => 'captured',
                'data_key' => 'reception-intake/20/FAKE_' . $key . '.jpg',
                'captured_at' => $now,
                'captured_by' => '1',
            ];
        }
        $canonicalPhotos = m360_rw_intake_photos_recalculate([
            'required_count' => M360_RW_INTAKE_PHOTO_MIN_REQUIRED,
            'completed_count' => 0,
            'is_complete' => false,
            'slots' => $photoSlots,
            'missing_labels' => [],
        ]);
        $payload = m360_rw_intake_photos_sync_to_payload($payload, $canonicalPhotos);
        $filled[] = 'photos.6_slots';
    } else {
        $preserved[] = 'photos';
    }

    $docs = is_array($payload['reception_intake']['documents'] ?? null)
        ? $payload['reception_intake']['documents']
        : [];
    foreach ([
        'diagnostic_status' => $testNote . ' diag',
        'cost_agreement' => $testNote . ' cost',
    ] as $dk => $dv) {
        if (trim((string)($docs[$dk] ?? '')) === '') {
            $docs[$dk] = $dv;
            $filled[] = 'documents.' . $dk;
        } else {
            $preserved[] = 'documents.' . $dk;
        }
    }
    $payload['reception_intake']['documents'] = $docs;
    if (trim((string)($payload['cost_agreement'] ?? '')) === '') {
        $payload['cost_agreement'] = $testNote . ' cost';
        $filled[] = 'cost_agreement';
    }

    if (!m360_rw_intake_contract_customer_accepted($payload)) {
        $payload['reception_intake']['contract'] = array_merge(
            is_array($payload['reception_intake']['contract'] ?? null) ? $payload['reception_intake']['contract'] : [],
            [
                'status' => M360_RW_INTAKE_CONTRACT_STATUS_CUSTOMER_ACCEPTED,
                'accepted_at' => $now,
                'acceptance_method' => $testNote,
            ]
        );
        $payload['reception_intake']['documents']['contract_status'] = M360_RW_INTAKE_DOC_CONTRACT_STATUS_CUSTOMER_ACCEPTED;
        $payload['contract_status'] = M360_RW_INTAKE_CONTRACT_STATUS_CUSTOMER_ACCEPTED;
        $payload['reception_intake']['customer_cartable']['contract_task'] = [
            'status' => M360_RW_INTAKE_CARTABLE_STATUS_CUSTOMER_ACCEPTED,
            'accepted_at' => $now,
            'acceptance_note' => $testNote,
        ];
        $filled[] = 'contract.customer_accepted';
    } else {
        $preserved[] = 'contract';
    }

    $sig = is_array($payload['reception_intake']['customer_signature'] ?? null)
        ? $payload['reception_intake']['customer_signature']
        : [];
    if (trim((string)($sig['status'] ?? '')) !== 'signed') {
        $payload['reception_intake']['customer_signature'] = [
            'status' => 'signed',
            'signed_at' => $now,
            'method' => $testNote,
        ];
        $filled[] = 'customer_signature';
    } else {
        $preserved[] = 'customer_signature';
    }

    $confirmBlock = is_array($payload['reception_intake']['reception_confirmation'] ?? null)
        ? $payload['reception_intake']['reception_confirmation']
        : [];
    if (empty($confirmBlock['confirmed_by_receptionist'])) {
        $payload['reception_intake']['reception_confirmation'] = [
            'confirmed_by_receptionist' => true,
            'confirmation_note' => $testNote,
            'confirmed_at' => $now,
        ];
        $payload['reception_final_confirmation'] = '1';
        $filled[] = 'reception_confirmation';
    } else {
        $preserved[] = 'reception_confirmation';
    }

    if (!m360_rw_intake_is_locked($payload)) {
        $payload['reception_intake']['intake_lock'] = [
            'status' => 'locked',
            'locked_at' => $now,
            'locked_by' => '1',
            'reason' => $testNote,
        ];
        $filled[] = 'intake_lock';
    } else {
        $preserved[] = 'intake_lock';
    }

    if (!m360_rw_intake_hall_manager_step_complete($payload)) {
        $payload['reception_intake']['hall_manager'] = [
            'status' => M360_RW_HALL_MANAGER_STATUS_READY_FA,
            'status_code' => 'ready_for_hall_manager_review',
            'sent_at' => $now,
            'sent_by' => '1',
            'note' => $testNote,
        ];
        $payload['reception_intake']['referral'] = [
            'hall_manager_gate' => true,
            'legacy_referral_team_removed_pr02a' => true,
        ];
        $filled[] = 'hall_manager';
    } else {
        $preserved[] = 'hall_manager';
    }

    if (!isset($payload['otp_verified']) || (int)$payload['otp_verified'] !== 1) {
        $payload['otp_verified'] = 1;
        $filled[] = 'otp_verified_payload';
    } else {
        $preserved[] = 'otp_verified_payload';
    }

    $payload['pr02a_fake_completion'] = [
        'marker' => $testNote,
        'mode' => 'full',
        'applied_at' => $now,
    ];

    return ['payload' => $payload, 'filled' => $filled, 'preserved' => $preserved];
}

/** @return array{ok:bool,message:string,encoded_len:int} */
function pr02a_req20_safe_persist_payload($conn, int $requestId, array $payload, array $columnUpdates): array
{
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        return ['ok' => false, 'message' => 'json_encode failed.', 'encoded_len' => 0];
    }

    $len = strlen($encoded);
    if ($len > PR02A_FAKE_MAX_PAYLOAD_BYTES) {
        return [
            'ok' => false,
            'message' => 'Refused: encoded payload ' . $len . ' bytes exceeds safe limit '
                . PR02A_FAKE_MAX_PAYLOAD_BYTES . ' (ODBC read truncates near 4096). Use --mode=minimal.',
            'encoded_len' => $len,
        ];
    }

    $decodedCheck = json_decode($encoded, true);
    if (!is_array($decodedCheck)) {
        return ['ok' => false, 'message' => 'Round-trip JSON decode failed before persist.', 'encoded_len' => $len];
    }

    $persist = m360_rw_intake_persist_payload($conn, $requestId, $payload, $columnUpdates);
    if (!$persist['ok']) {
        return ['ok' => false, 'message' => $persist['message'], 'encoded_len' => $len];
    }

    $readBack = pr02a_req20_read_payload_json($conn, $requestId);
    $meta = m360_rw_decode_payload($readBack);
    if (!$meta['valid']) {
        return ['ok' => false, 'message' => 'Post-persist chunked read: invalid JSON.', 'encoded_len' => $len];
    }

    return ['ok' => true, 'message' => '', 'encoded_len' => $len];
}

$conn = customer_core_db();
if (!is_resource($conn)) {
    fwrite(STDERR, "Database connection unavailable.\n");
    exit(1);
}

if ($restore) {
    if ($confirm !== PR02A_RESTORE_MARKER) {
        fwrite(STDERR, 'Refused: pass --confirm=' . PR02A_RESTORE_MARKER . " with --restore\n");
        exit(1);
    }
    if ($snapshotPath === '') {
        $snapshotPath = 'docs/audit/request_20_before_fake_completion_snapshot.json';
    }
    $restoreResult = pr02a_req20_restore_from_snapshot($conn, $snapshotPath);
    @odbc_close($conn);
    if (!$restoreResult['ok']) {
        fwrite(STDERR, 'Restore failed: ' . $restoreResult['message'] . "\n");
        exit(1);
    }
    echo "RESTORE_APPLIED = yes\n";
    echo "RESTORE_SOURCE = {$snapshotPath}\n";
    exit(0);
}

if ($confirm !== PR02A_FAKE_MARKER) {
    fwrite(STDERR, 'Refused: pass --confirm=' . PR02A_FAKE_MARKER . "\n");
    exit(1);
}

$request = pr02a_req20_fetch_request($conn, PR02A_FAKE_REQ_ID);
if ($request === null) {
    fwrite(STDERR, "Request #20 not found.\n");
    exit(1);
}

$payloadMeta = m360_rw_decode_payload($request['request_payload_json'] ?? null);
$payload = m360_rw_intake_payload_for_recovery($payloadMeta['items']);
$result = $mode === 'full'
    ? pr02a_req20_fill_full($payload, $request)
    : pr02a_req20_fill_vehicle_only($payload, $request);
$newPayload = $result['payload'];

$columnUpdates = [];
if (trim((string)($request['vehicle_plate'] ?? '')) === '' && trim((string)($newPayload['vehicle_plate'] ?? '')) !== '') {
    $columnUpdates['vehicle_plate'] = (string)$newPayload['vehicle_plate'];
}
$visitDate = trim((string)($newPayload['visit_date'] ?? ''));
if ($visitDate !== '' && trim((string)($request['visit_date'] ?? '')) === '') {
    // visit_date column update only if helper supports via columnUpdates - persist doesn't have visit_date
}

$persist = pr02a_req20_safe_persist_payload($conn, PR02A_FAKE_REQ_ID, $newPayload, $columnUpdates);
if (!$persist['ok']) {
    fwrite(STDERR, 'Persist failed: ' . $persist['message'] . "\n");
    @odbc_close($conn);
    exit(1);
}

$historyWritten = m360_online_req_write_history(
    $conn,
    PR02A_FAKE_REQ_ID,
    'PR02A_FAKE_COMPLETION_' . PR02A_FAKE_MARKER . '_' . strtoupper($mode),
    (string)($request['request_status'] ?? ''),
    (string)($request['request_status'] ?? ''),
    PR02A_FAKE_MARKER . ' mode=' . $mode,
    1
);

@odbc_close($conn);

echo "FAKE_COMPLETION_APPLIED = yes\n";
echo 'FAKE_COMPLETION_MODE = ' . $mode . "\n";
echo 'PAYLOAD_ENCODED_LEN = ' . $persist['encoded_len'] . "\n";
echo 'FIELDS_FILLED = ' . implode(', ', $result['filled']) . "\n";
echo 'FIELDS_PRESERVED = ' . implode(', ', $result['preserved']) . "\n";
echo 'HISTORY_WRITTEN = ' . ($historyWritten ? 'yes' : 'no') . "\n";
echo "JOBCARD_CREATED = no\n";
echo "OTP_SENT = no\n";
echo "C2D_TRIGGERED = no\n";

exit(0);
