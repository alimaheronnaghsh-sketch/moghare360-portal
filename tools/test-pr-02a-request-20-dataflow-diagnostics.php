<?php
declare(strict_types=1);

/**
 * PR-02A — Request #20 read-only dataflow diagnostics (local test only).
 *
 * Usage:
 *   php tools/test-pr-02a-request-20-dataflow-diagnostics.php
 *   php tools/test-pr-02a-request-20-dataflow-diagnostics.php --output=docs/audit/request_20_before_fake_completion_snapshot.json
 */

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

const PR02A_REQ20_ID = 20;
const PR02A_REQ20_ODBC_PAYLOAD_READ_LIMIT = 4096;

/** @deprecated Use m360_online_req_read_payload_json_chunked — kept for diagnostic comparison. */
function pr02a_req20_read_payload_json($conn, int $requestId): string
{
    return m360_online_req_read_payload_json_chunked($conn, $requestId);
}

/** @return array<string, mixed>|null */
function pr02a_req20_fetch_request($conn, int $requestId): ?array
{
    return m360_online_req_fetch_by_id($conn, $requestId);
}

/** @return array<string, mixed> */
function pr02a_req20_build_snapshot($conn, int $requestId): array
{
    $request = pr02a_req20_fetch_request($conn, $requestId);
    if ($request === null) {
        return ['exists' => false, 'online_request_id' => $requestId];
    }

    $odbcPayloadLen = strlen((string)(m360_online_req_fetch_by_id($conn, $requestId)['request_payload_json'] ?? ''));
    $fullPayloadLen = strlen((string)($request['request_payload_json'] ?? ''));

    $payloadMeta = m360_rw_decode_payload($request['request_payload_json'] ?? null);
    $payload = m360_rw_intake_payload_for_recovery($payloadMeta['items']);
    $formValues = m360_rw_intake_form_values($payload, $request);
    $state = m360_rw_intake_get_wizard_step_state($payload, $request);
    $resolved = m360_rw_intake_resolve_active_step([], $request, $payload, $formValues);
    $file = m360_rw_build_intake_file($conn, $requestId);
    $gate = is_array($file['gate'] ?? null) ? $file['gate'] : [];
    $history = m360_reception_fetch_history($conn, $requestId);
    $vehicle = m360_rw_intake_vehicle_canonical($payload, $request);
    $photos = m360_rw_intake_reception_photo_status($payload);

    $lastSaveStep = '';
    foreach ($history as $h) {
        $event = (string)($h['event_type'] ?? '');
        if (str_contains($event, 'RECEPTION_INTAKE_SAVE_')) {
            $lastSaveStep = str_replace('RECEPTION_INTAKE_SAVE_', '', $event);
            break;
        }
    }

    $missingByGate = [];
    foreach ($state['steps'] as $stepKey => $meta) {
        if (empty($meta['complete'])) {
            $missingByGate[$stepKey] = $meta['missing_fields'] ?? [];
        }
    }

    $payloadValid = ($payloadMeta['valid'] ?? true) === true;
    $rawJson = (string)($request['request_payload_json'] ?? '');
    if ($rawJson !== '') {
        json_decode($rawJson, true);
        $payloadValid = json_last_error() === JSON_ERROR_NONE;
    }

    return [
        'snapshot_meta' => [
            'generated_at_utc' => gmdate('c'),
            'mission' => 'PR-02A-E2E-DATAFLOW-TEST-REQUEST-20',
            'read_only' => true,
        ],
        'exists' => true,
        'online_request_id' => $requestId,
        'request_row' => [
            'online_request_id' => (string)($request['online_request_id'] ?? ''),
            'request_status' => (string)($request['request_status'] ?? ''),
            'otp_verified' => (string)($request['otp_verified'] ?? ''),
            'customer_id' => (string)($request['customer_id'] ?? ''),
            'vehicle_id' => (string)($request['vehicle_id'] ?? ''),
            'converted_jobcard_id' => (string)($request['converted_jobcard_id'] ?? '0'),
            'mobile' => (string)($request['mobile'] ?? ''),
            'customer_name' => (string)($request['customer_name'] ?? ''),
            'vehicle_plate' => (string)($request['vehicle_plate'] ?? ''),
            'visit_date' => (string)($request['visit_date'] ?? ''),
            'request_type' => (string)($request['request_type'] ?? ''),
            'source_channel' => (string)($request['source_channel'] ?? ''),
            'created_at' => (string)($request['created_at'] ?? ''),
            'updated_at' => (string)($request['updated_at'] ?? ''),
        ],
        'payload_valid_json' => $payloadValid,
        'payload_read_meta' => [
            'odbc_fetch_len' => $odbcPayloadLen,
            'chunked_read_len' => $fullPayloadLen,
            'odbc_truncated' => $fullPayloadLen > PR02A_REQ20_ODBC_PAYLOAD_READ_LIMIT
                && $odbcPayloadLen === PR02A_REQ20_ODBC_PAYLOAD_READ_LIMIT,
        ],
        'payload_meta' => [
            'valid' => (bool)($payloadMeta['valid'] ?? true),
            'raw_warning' => (string)($payloadMeta['raw_warning'] ?? ''),
        ],
        'payload_decoded' => $payload,
        'form_values' => $formValues,
        'wizard_state' => $state,
        'resolved_active_step' => $resolved,
        'gate' => [
            'status' => (string)($gate['status'] ?? ''),
            'label_fa' => (string)($gate['label_fa'] ?? ''),
            'can_show_convert' => !empty($gate['can_show_convert']),
            'reception_mode' => (string)($gate['reception_mode'] ?? ''),
            'missing' => $gate['missing'] ?? [],
            'missing_hard' => $gate['missing_hard'] ?? [],
        ],
        'vehicle_canonical' => $vehicle,
        'photos_status' => $photos,
        'history_count' => count($history),
        'history_recent' => array_slice($history, 0, 15),
        'last_saved_step_from_history' => $lastSaveStep,
        'missing_fields_by_gate' => $missingByGate,
        'labels' => [
            'REQUEST_20_EXISTS' => 'yes',
            'REQUEST_20_OTP_VERIFIED' => m360_online_req_payload_otp_verified($request) ? 'yes' : 'no',
            'REQUEST_20_PAYLOAD_VALID_JSON' => $payloadValid ? 'yes' : 'no',
            'REQUEST_20_CUSTOMER_LINKED' => ((int)($request['customer_id'] ?? 0) > 0) ? 'yes' : 'no',
            'REQUEST_20_VEHICLE_LINKED' => ((int)($request['vehicle_id'] ?? 0) > 0) ? 'yes' : 'no',
            'REQUEST_20_CURRENT_STEP' => $resolved,
            'REQUEST_20_LAST_SAVED_STEP' => $lastSaveStep !== '' ? $lastSaveStep : 'unknown',
            'REQUEST_20_JOBCARD_ID' => (string)((int)($request['converted_jobcard_id'] ?? 0)),
            'REQUEST_20_HISTORY_COUNT' => (string)count($history),
        ],
    ];
}

/** @param list<string> $argv */
function pr02a_req20_run_cli(array $argv): void
{
    global $root;

    $outputPath = '';
    foreach ($argv as $arg) {
        if (preg_match('/^--output=(.+)$/', $arg, $m)) {
            $outputPath = $m[1];
        }
    }

    $conn = customer_core_db();
    if (!is_resource($conn)) {
        fwrite(STDERR, "Database connection unavailable.\n");
        exit(1);
    }

    $snapshot = pr02a_req20_build_snapshot($conn, PR02A_REQ20_ID);
    @odbc_close($conn);

    if (empty($snapshot['exists'])) {
        fwrite(STDERR, 'Request #' . PR02A_REQ20_ID . " not found.\n");
        exit(1);
    }

    $rootCause = 'unknown';
    if (($snapshot['wizard_state']['first_incomplete'] ?? '') === 'vehicle') {
        $missing = $snapshot['missing_fields_by_gate']['vehicle'] ?? [];
        $rootCause = 'vehicle_step_incomplete_in_payload: ' . implode(',', $missing);
        if (($snapshot['photos_status']['count'] ?? 0) > 0) {
            $rootCause .= '; photos in payload but vehicle canonical incomplete forces wizard backjump';
        }
    }
    $snapshot['labels']['REQUEST_20_STEP_RETURN_ROOT_CAUSE'] = $rootCause;
    $snapshot['labels']['REQUEST_20_MISSING_FIELDS_BY_GATE'] = json_encode(
        $snapshot['missing_fields_by_gate'],
        JSON_UNESCAPED_UNICODE
    );
    $snapshot['labels']['READ_ONLY_DIAGNOSTIC_DONE'] = 'yes';

    $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) {
        fwrite(STDERR, "Failed to encode snapshot JSON.\n");
        exit(1);
    }

    if ($outputPath !== '') {
        $fullPath = str_starts_with($outputPath, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:/', $outputPath)
            ? $outputPath
            : $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $outputPath);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($fullPath, $json);
        echo "Snapshot written: {$fullPath}\n";
    }

    echo "PR-02A Request #20 dataflow diagnostics (read-only)\n";
    foreach ($snapshot['labels'] as $key => $val) {
        echo "{$key} = {$val}\n";
    }
    echo "REQUEST_20_STEP_RETURN_ROOT_CAUSE = {$rootCause}\n";

    exit(0);
}

/** @return array<string, string> */
function pr02a_req20_post_fake_evaluation(array $snapshot): array
{
    $payload = is_array($snapshot['payload_decoded'] ?? null) ? $snapshot['payload_decoded'] : [];
    $request = is_array($snapshot['request_row'] ?? null) ? $snapshot['request_row'] : [];
    $gate = is_array($snapshot['gate'] ?? null) ? $snapshot['gate'] : [];
    $resolved = (string)($snapshot['resolved_active_step'] ?? '');
    $state = is_array($snapshot['wizard_state'] ?? null) ? $snapshot['wizard_state'] : [];
    $photos = is_array($snapshot['photos_status'] ?? null) ? $snapshot['photos_status'] : [];

    $blockers = [];
    if (empty($gate['can_show_convert'])) {
        foreach (['missing_hard', 'missing'] as $key) {
            if (!empty($gate[$key]) && is_array($gate[$key])) {
                foreach ($gate[$key] as $item) {
                    $blockers[] = (string)$item;
                }
            }
        }
    }

    $odbcTruncated = !empty($snapshot['payload_read_meta']['odbc_truncated']);
    $returnVehicle = $resolved === 'vehicle';
    $returnCause = '';
    if ($returnVehicle) {
        $missing = $snapshot['missing_fields_by_gate']['vehicle'] ?? [];
        $returnCause = 'vehicle_step_incomplete: ' . implode(',', (array)$missing);
    }
    if ($odbcTruncated && !$snapshot['payload_valid_json']) {
        $returnCause = trim($returnCause . '; odbc_payload_truncation_breaks_json_decode');
    }

    return [
        'REQUEST_20_OPENS_AFTER_FAKE' => !empty($snapshot['exists']) ? 'yes' : 'no',
        'REQUEST_20_DEFAULT_STEP_AFTER_FAKE' => $resolved !== '' ? $resolved : 'unknown',
        'PHOTO_GATE_PASSED' => !empty($photos['complete']) ? 'yes' : 'no',
        'CONTRACT_GATE_PASSED' => m360_rw_intake_contract_customer_accepted($payload) ? 'yes' : 'no',
        'HALL_MANAGER_GATE_VISIBLE' => m360_rw_intake_hall_manager_step_complete($payload) ? 'yes' : 'no',
        'READY_CONVERT_ACTIVE' => !empty($gate['can_show_convert']) ? 'yes' : 'no',
        'JOBCARD_CONVERSION_AVAILABLE' => !empty($gate['can_show_convert']) ? 'yes' : 'no',
        'JOBCARD_BLOCKERS_AFTER_FAKE' => implode(' | ', array_slice($blockers, 0, 12)),
        'STILL_RETURNS_TO_VEHICLE_STEP' => $returnVehicle ? 'yes' : 'no',
        'RETURN_TO_VEHICLE_ROOT_CAUSE' => $returnCause !== '' ? $returnCause : 'none',
        'ODBC_PAYLOAD_TRUNCATION_RISK' => $odbcTruncated ? 'yes' : 'no',
        'FIRST_INCOMPLETE_STEP' => (string)($state['first_incomplete'] ?? ''),
    ];
}

if (PHP_SAPI === 'cli' && realpath((string)($argv[0] ?? '')) === realpath(__FILE__)) {
    pr02a_req20_run_cli($argv);
}
