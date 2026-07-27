<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function pr02b2_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$helper = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$intakePhp = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');
$js = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');

$results = [];
$results[] = pr02b2_pass('JS OTP send button binding', str_contains($js, "sendBtn.addEventListener('click'") && str_contains($js, 'm360_send_otp'));
$results[] = pr02b2_pass('JS sendOtp fetch api/customer/send-otp.php', str_contains($js, "fetchJson('api/customer/send-otp.php'"));
$results[] = pr02b2_pass('JS sendOtp loading status', str_contains($js, 'در حال ارسال کد تأیید'));
$results[] = pr02b2_pass('JS sendOtp no silent catch', str_contains($js, '.catch(function ()') && str_contains($js, 'ارسال کد تأیید انجام نشد'));
$results[] = pr02b2_pass('JS renderVehiclePicker syntax sane', !preg_match('/picker\.hidden = false;\s+if \(mode\) mode\.value = \'new\';\s+if \(selected\) selected\.value = \'\';\s+newFields\.hidden = false;\s+setVehicleNewFieldsRequired\(true\);\s+return;\s+\}/', $js));
$results[] = pr02b2_pass('reception completion keys defined', str_contains($helper, 'function m360_rw_intake_reception_completion_keys'));
$results[] = pr02b2_pass('complete_reception_intake action allowed', str_contains($helper, "'complete_reception_intake'"));
$results[] = pr02b2_pass('contract not in documents missing fields', !preg_match('/function m360_rw_intake_documents_step_state[\s\S]*missing\[\] = \'contract_status\'/', $helper));
$results[] = pr02b2_pass('referral not in cartable prerequisites loop', !preg_match("/foreach \(\['otp', 'vehicle', 'condition', 'service', 'referral', 'photos'\]/", $helper));
$results[] = pr02b2_pass('hall manager uses operation gate', str_contains($helper, 'm360_rw_intake_operation_gate_hall_manager_allowed'));
$results[] = pr02b2_pass('hall manager not requires intake lock only', !str_contains($helper, 'پرونده باید پس از امضا و قفل پذیرش، برای ارسال به مسئول سالن آماده باشد.'));
$results[] = pr02b2_pass('contract SMS text locked', str_contains($helper, M360_RW_INTAKE_CONTRACT_SMS_TEXT_FA));
$results[] = pr02b2_pass('contract SMS skips cli', str_contains($helper, "PHP_SAPI === 'cli'"));
$results[] = pr02b2_pass('intake complete reception button', str_contains($intakePhp, 'complete_reception_intake'));
$results[] = pr02b2_pass('intake shows reception completed status', str_contains($intakePhp, 'پذیرش ثبت شد'));
$results[] = pr02b2_pass('OTP_PROVIDER_CONFIG_UNTOUCHED', !str_contains($helper, 'm360-otp-config.php'));
$results[] = pr02b2_pass('OTP_BYPASS_CREATED no', !str_contains($helper, 'otp_verified = 1'));

$payload = ['reception_intake' => []];
$docState = m360_rw_intake_documents_step_state($payload, []);
$results[] = pr02b2_pass('documents incomplete without contract only diag/cost', in_array('diagnostic_status', $docState['missing_fields'], true) && !in_array('contract_status', $docState['missing_fields'], true));

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02B UAT repair 2: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
