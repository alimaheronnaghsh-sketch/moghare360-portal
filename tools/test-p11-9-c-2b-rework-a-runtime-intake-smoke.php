<?php
declare(strict_types=1);

/**
 * P11.9-C-2B-REWORK-A — Runtime smoke (must execute code paths, not string-only).
 */

$root = dirname(__DIR__);

function rework_a_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];

// --- Runtime: bootstrap helper and call intake builder ---
$helper = $root . '/public_html/includes/m360-reception-workbench-helper.php';
$results[] = rework_a_pass('helper file exists', is_file($helper));

require_once $helper;

try {
    $emptyFile = m360_rw_build_intake_file(false, 0);
    $results[] = rework_a_pass(
        'm360_rw_build_intake_file(false, 0) no throw',
        is_array($emptyFile) && isset($emptyFile['gate'])
    );
    $results[] = rework_a_pass(
        'empty intake gate has status key',
        isset($emptyFile['gate']['status'])
    );
} catch (Throwable $e) {
    $results[] = rework_a_pass('m360_rw_build_intake_file(false, 0) no throw', false, $e->getMessage());
}

try {
    $emptyGate = m360_rw_build_gate([], [], null, null, null, [], [], [], null);
    $results[] = rework_a_pass('m360_rw_build_gate 9-arg empty call', is_array($emptyGate) && isset($emptyGate['status']));
} catch (Throwable $e) {
    $results[] = rework_a_pass('m360_rw_build_gate 9-arg empty call', false, $e->getMessage());
}

// Live DB path when available
require_once $root . '/public_html/includes/m360-reception-helper.php';
$dbConn = customer_core_db();
if ($dbConn !== false) {
    foreach ([18, 20] as $reqId) {
        try {
            $file = m360_rw_build_intake_file($dbConn, $reqId);
            $results[] = rework_a_pass(
                "m360_rw_build_intake_file(conn, {$reqId}) runtime",
                is_array($file) && isset($file['gate']['status']),
                'status=' . (string)($file['gate']['status'] ?? '')
            );
        } catch (Throwable $e) {
            $results[] = rework_a_pass("m360_rw_build_intake_file(conn, {$reqId}) runtime", false, $e->getMessage());
        }
    }
} else {
    $results[] = rework_a_pass('DB intake smoke (18/20)', true, 'skipped — no DB connection in test env');
}

// --- Process compliance (gate logic) ---
$gTemp = m360_rw_build_gate(
    ['customer_name' => 'T', 'mobile' => '09121234567', 'vehicle_plate' => '12ب34567', 'service_note' => 'x', 'request_status' => 'NEW', 'request_payload_json' => '{"otp_verified":1}'],
    ['otp_verified' => 1, 'service_note' => 'x'],
    null, null, null, [], [], [], null
);
$results[] = rework_a_pass('temp: convert blocked without service class', ($gTemp['can_show_convert'] ?? true) === false);
$results[] = rework_a_pass('temp: temp actions allowed', ($gTemp['can_show_temp_actions'] ?? false) === true);

$gReady = m360_rw_build_gate(
    ['customer_name' => 'T', 'mobile' => '09121234567', 'vehicle_plate' => '12ب34567', 'service_note' => 'x', 'request_status' => 'NEW', 'request_payload_json' => '{}'],
    [
        'otp_verified' => 1,
        'service_note' => 'x',
        'reception_service_primary' => 'diag',
        'reception_service_diag_sub' => ['engine_transmission'],
        'fault_service_path_clear' => '1',
        'vin' => 'VIN1',
        'brand' => 'B',
        'vehicle_brand' => 'B',
        'model' => 'M',
        'vehicle_model' => 'M',
        'odometer_km' => '1000',
        'fuel_level' => 'half',
        'belongings' => 'none',
        'visible_damage' => 'none',
        'cost_agreement' => 'ok',
        'reception_final_confirmation' => '1',
    ],
    null, null, null, [], [], [], null
);
$results[] = rework_a_pass('full gate: past temp when service registered', !in_array($gReady['status'] ?? '', ['complete_unclear_fault', 'temporary_reception'], true));
$results[] = rework_a_pass('full gate: convert only on ready_convert status', ($gReady['can_show_convert'] ?? false) === (($gReady['status'] ?? '') === 'ready_convert'));

// --- Action placement (detail page demoted) ---
$detail = (string)file_get_contents($root . '/public_html/erp-reception-online-request-detail.php');
$results[] = rework_a_pass('detail: primary CTA تکمیل پرونده', str_contains($detail, 'تکمیل پرونده پذیرش'));
$results[] = rework_a_pass('detail: gate guidance text', str_contains($detail, 'ابتدا پرونده پذیرش را تکمیل و وضعیت Gate را بررسی کنید'));
$results[] = rework_a_pass('detail: no convert submit button', !preg_match('/action\s*=\s*"convert_to_jobcard"/', $detail));
$results[] = rework_a_pass('detail: no accept submit button', !preg_match('/action\s*=\s*"accept"/', $detail));
$results[] = rework_a_pass('detail: no reject submit button', !preg_match('/action\s*=\s*"reject"/', $detail));

$intake = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');
$results[] = rework_a_pass('intake: reject in temp section', str_contains($intake, 'رد درخواست') && str_contains($intake, 'اقدامات پذیرش موقت'));
$results[] = rework_a_pass('intake: service class by receptionist', str_contains($intake, 'ثبت توسط پذیرشگر'));

// --- Helper: no 8-arg build_gate call left ---
$helperSrc = (string)file_get_contents($helper);
$results[] = rework_a_pass('helper: no 8-arg m360_rw_build_gate call', !preg_match('/m360_rw_build_gate\(\[\],\s*\[\],\s*\[\],\s*\[\],\s*\[\],\s*\[\],\s*\[\],\s*\[\]\)/', $helperSrc));

// --- PHP lint intake page via include simulation ---
$intakePhp = $root . '/public_html/erp-reception-intake-file.php';
$lintOut = [];
$lintCode = 0;
exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($intakePhp) . ' 2>&1', $lintOut, $lintCode);
$results[] = rework_a_pass('PHP lint intake-file.php', $lintCode === 0, implode(' ', $lintOut));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2B-REWORK-A Runtime Intake Smoke\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
