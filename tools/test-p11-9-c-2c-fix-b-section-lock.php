<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function fixb_lock_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$intakeSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');
$saveSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-save.php');

$payload = ['reception_intake' => ['section_status' => ['vehicle_identity' => ['completed' => true]]], 'vehicle_plate' => '12ب345-67'];
$request = ['mobile' => '09120000000'];
$formValues = ['plate' => '12ب345-67'];

$closed = m360_rw_intake_section_ui_state('vehicle_identity', $payload, $request, $formValues, '');
$results[] = fixb_lock_pass('completed section show_summary', $closed['show_summary'] === true);
$results[] = fixb_lock_pass('completed section hide form when not editing', $closed['show_form'] === false);
$results[] = fixb_lock_pass('completed status label', $closed['status_label'] === 'تکمیل شده');

$editing = m360_rw_intake_section_ui_state('vehicle_identity', $payload, $request, $formValues, 'vehicle_identity');
$results[] = fixb_lock_pass('edit_section shows form', $editing['show_form'] === true);
$results[] = fixb_lock_pass('edit_section editing label', $editing['status_label'] === 'در حال ویرایش');

$results[] = fixb_lock_pass('edit button in render helper', str_contains((string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php'), 'ویرایش'));
$results[] = fixb_lock_pass('intake uses edit_section GET', str_contains($intakeSrc, 'edit_section'));
$results[] = fixb_lock_pass('GET edit_section does not write in intake', !preg_match('/\$_GET\s*\[\s*[\'"]edit_section[\'"]\s*\][^;]*UPDATE/i', $intakeSrc));
$results[] = fixb_lock_pass('save endpoint POST only', str_contains($saveSrc, '$_SERVER[\'REQUEST_METHOD\']') || str_contains($saveSrc, 'REQUEST_METHOD'));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C-FIX-B Section Lock Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
