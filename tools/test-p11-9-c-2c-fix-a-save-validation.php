<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function fixa_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$saveSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-save.php');

$vtStr = m360_rw_intake_validate_text('hello', 500, 'تست');
$results[] = fixa_pass('validate_text accepts string', $vtStr['ok']);

$noFatalInt = true;
try {
    $vtInt = m360_rw_intake_validate_text(10101010, 500, 'توافق هزینه');
    $results[] = fixa_pass('validate_text accepts int without TypeError', $vtInt['ok']);
} catch (Throwable $e) {
    $noFatalInt = false;
    $results[] = fixa_pass('validate_text accepts int without TypeError', false, $e->getMessage());
}

try {
    $vtFloat = m360_rw_intake_validate_text(12.5, 500, 'تست');
    $results[] = fixa_pass('validate_text accepts float without TypeError', $vtFloat['ok']);
} catch (Throwable $e) {
    $results[] = fixa_pass('validate_text accepts float without TypeError', false, $e->getMessage());
}

try {
    $vtNull = m360_rw_intake_validate_text(null, 500, 'تست');
    $results[] = fixa_pass('validate_text accepts null without TypeError', $vtNull['ok']);
} catch (Throwable $e) {
    $results[] = fixa_pass('validate_text accepts null without TypeError', false, $e->getMessage());
}

$vtArr = m360_rw_intake_validate_text(['x'], 500, 'تست');
$results[] = fixa_pass('validate_text rejects array safely', !$vtArr['ok']);

$vtObj = m360_rw_intake_validate_text(new stdClass(), 500, 'تست');
$results[] = fixa_pass('validate_text rejects object safely', !$vtObj['ok']);

$docPost = [
    'photo_status' => 'ثبت شد',
    'diagnostic_status' => 'ثبت شد',
    'contract_status' => 'در انتظار',
    'cost_agreement' => 10101010,
    'cost_agreement_note' => 'نقد',
];
try {
    $docApplied = m360_rw_intake_apply_action(['customer_name' => 'Ali'], 'save_documents_and_cost', $docPost);
    $results[] = fixa_pass('save_documents_and_cost numeric cost no fatal', $docApplied['ok']);
    $results[] = fixa_pass('cost stored as string', ($docApplied['payload']['cost_agreement'] ?? '') === '10101010');
} catch (Throwable $e) {
    $results[] = fixa_pass('save_documents_and_cost numeric cost no fatal', false, $e->getMessage());
    $results[] = fixa_pass('cost stored as string', false);
}

$vehPost = [
    'plate' => '12ب345-67',
    'vin' => '',
    'brand' => 'BMW',
    'model' => 'X5',
    'mileage' => 85000,
    'fuel_level' => 'نصف',
];
try {
    $vehApplied = m360_rw_intake_apply_action([], 'save_vehicle_identity', $vehPost);
    $results[] = fixa_pass('save_vehicle_identity numeric mileage no fatal', $vehApplied['ok']);
} catch (Throwable $e) {
    $results[] = fixa_pass('save_vehicle_identity numeric mileage no fatal', false, $e->getMessage());
}

$results[] = fixa_pass('endpoint Throwable catch', str_contains($saveSrc, 'catch (Throwable)'));
$results[] = fixa_pass('endpoint no stack trace output', !str_contains($saveSrc, 'getTraceAsString') && !str_contains($saveSrc, 'echo $e'));
$results[] = fixa_pass('no otp_verified write in apply', !preg_match('/\$payload\s*\[\s*[\'"]otp_verified[\'"]\s*\]\s*=\s*1/', $helperSrc));
$results[] = fixa_pass('process save preserves otp', str_contains($helperSrc, '$otpPreserved'));
$results[] = fixa_pass('no automatic jobcard in save', !str_contains($saveSrc, 'convert_to_jobcard'));
$results[] = fixa_pass('broken foreach pattern removed', !str_contains($helperSrc, '$costAgreement => \'توافق هزینه\''));

$childTests = [
    'test-p11-9-c-2c-intake-write-runtime.php',
    'test-p11-9-c-2c-intake-write-security.php',
    'test-p11-9-c-2c-intake-write-process.php',
    'test-p11-9-c-2c-intake-write-payload.php',
    'test-p11-9-c-2c-scope-security.php',
    'test-v1-production-signoff.php',
];
foreach ($childTests as $test) {
    exec('"' . $phpBin . '" ' . escapeshellarg($root . '/tools/' . $test) . ' 2>&1', $out, $code);
    $results[] = fixa_pass($test . ' still passes', $code === 0, $code !== 0 ? implode("\n", array_slice($out, -3)) : '');
}

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C-FIX-A Save Validation Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
