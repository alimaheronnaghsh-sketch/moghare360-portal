<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function fixb_cdc_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$intakeSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');
$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$jsSrc = (string)file_get_contents($root . '/public_html/assets/js/m360-reception-intake.js');

$results[] = fixb_cdc_pass('camera section in intake', str_contains($intakeSrc, 'section-camera-photo') || str_contains($intakeSrc, 'عکس‌های پذیرش'));
$results[] = fixb_cdc_pass('save_camera_photo action', in_array('save_camera_photo', m360_rw_intake_allowed_actions(), true));
$results[] = fixb_cdc_pass('getUserMedia camera JS', str_contains($jsSrc, 'getUserMedia'));
$results[] = fixb_cdc_pass('photo_slot required in save', str_contains($helperSrc, 'photo_slot'));
$results[] = fixb_cdc_pass('camera base64 hidden field not generic file upload', (str_contains($intakeSrc, 'camera_image_base64') || str_contains($intakeSrc, 'm360-rw-slot-base64') || str_contains($helperSrc, 'camera_image_base64')));
$results[] = fixb_cdc_pass('no arbitrary file upload for photo form', !preg_match('/name\s*=\s*[\'"]photo_file[\'"]\s+type\s*=\s*[\'"]file/i', $intakeSrc));

$results[] = fixb_cdc_pass('diagnostic PDF section', str_contains($intakeSrc, 'فایل دیاگ اولیه'));
$results[] = fixb_cdc_pass('save_diagnostic_pdf action', in_array('save_diagnostic_pdf', m360_rw_intake_allowed_actions(), true));
$results[] = fixb_cdc_pass('PDF accept only', str_contains($intakeSrc, 'accept="application/pdf'));
$results[] = fixb_cdc_pass('pdf upload validates extension', str_contains($helperSrc, "if (\$ext !== 'pdf')"));

$results[] = fixb_cdc_pass('contract section exists', str_contains($intakeSrc, 'قرارداد پذیرش و تأیید مشتری'));
$results[] = fixb_cdc_pass('run_intake_contract action', in_array('run_intake_contract', m360_rw_intake_allowed_actions(), true));
$results[] = fixb_cdc_pass('approve_intake_contract action', in_array('approve_intake_contract', m360_rw_intake_allowed_actions(), true));

$run = m360_rw_intake_apply_action(['customer_name' => 'Ali', 'mobile' => '09121111111'], 'run_intake_contract', []);
$results[] = fixb_cdc_pass('contract run creates draft', $run['ok'] && !empty($run['payload']['reception_intake']['contract']['contract_text']));

$noApprove = m360_rw_intake_apply_action($run['payload'], 'approve_intake_contract', []);
$results[] = fixb_cdc_pass('no fake customer approval without checkbox', !$noApprove['ok']);

$approve = m360_rw_intake_apply_action($run['payload'], 'approve_intake_contract', ['customer_contract_approved' => '1']);
$results[] = fixb_cdc_pass('explicit approval required and works', $approve['ok'] && !empty($approve['payload']['reception_intake']['contract']['customer_approved']));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C-FIX-B Contract Diagnostic Camera Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
