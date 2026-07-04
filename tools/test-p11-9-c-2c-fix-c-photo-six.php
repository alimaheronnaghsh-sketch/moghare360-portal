<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function fixc_photo_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$intakeSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');

$slots = m360_rw_intake_reception_photo_slots();
$results[] = fixc_photo_pass('six slots defined', count($slots) === 6);
$results[] = fixc_photo_pass('front slot label', ($slots['front'] ?? '') === 'نمای جلو خودرو');
$results[] = fixc_photo_pass('photo_min_required constant', M360_RW_INTAKE_PHOTO_MIN_REQUIRED === 6);

$legacyPayload = ['reception_intake' => ['documents' => ['photo_file' => 'reception-intake/1/old.jpg', 'photo_status' => 'ثبت شد']]];
$legacyStatus = m360_rw_intake_reception_photo_status($legacyPayload);
$results[] = fixc_photo_pass('legacy single photo not complete', !$legacyStatus['complete']);
$results[] = fixc_photo_pass('legacy_only flag', $legacyStatus['legacy_only'] === true);

$partialPayload = ['reception_intake' => ['documents' => ['reception_photos' => ['front' => ['file' => 'reception-intake/1/a.jpg', 'label' => 'نمای جلو']], 'photo_count' => 1]]];
$partialStatus = m360_rw_intake_reception_photo_status($partialPayload);
$results[] = fixc_photo_pass('partial count 1', $partialStatus['count'] === 1);
$msg = m360_rw_intake_reception_photo_missing_message($partialStatus);
$results[] = fixc_photo_pass('missing message mentions slots or minimum', str_contains($msg, 'ناقص') || str_contains($msg, '۶'));

$noSlot = m360_rw_intake_apply_action([], 'save_camera_photo', ['online_request_id' => '1', 'camera_image_base64' => '']);
$results[] = fixc_photo_pass('save without photo_slot rejected', !$noSlot['ok']);

$gateFields = m360_rw_recover_intake_fields(['mobile' => '09121111111'], $legacyPayload, null, null, null, null, [], [], []);
$results[] = fixc_photo_pass('gate photo not present for legacy only', empty($gateFields['photo']['present']));

$results[] = fixc_photo_pass('gate label count format in build_gate', str_contains($helperSrc, 'photoCheckLabel'));
$results[] = fixc_photo_pass('photo_slot in save action', str_contains($helperSrc, 'photo_slot'));
$results[] = fixc_photo_pass('six slot UI render helper', function_exists('m360_rw_intake_render_reception_photos_section'));
$results[] = fixc_photo_pass('no generic photo file upload in intake', !preg_match('/type\s*=\s*[\'"]file[\'"].*photo/i', $intakeSrc));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C-FIX-C Photo Six Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
