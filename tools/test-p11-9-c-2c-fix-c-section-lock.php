<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function fixc_lock_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$intakeSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');

$payload = ['reception_intake' => ['section_status' => ['service_classification' => ['completed' => true]]], 'reception_service_primary' => 'diag', 'reception_service_diag_sub' => ['engine_transmission'], 'fault_service_path_clear' => '1'];
$request = ['mobile' => '09120000000'];
$formValues = m360_rw_intake_form_values($payload, $request);

$closed = m360_rw_intake_section_ui_state('service_classification', $payload, $request, $formValues, '');
$results[] = fixc_lock_pass('completed shows summary', $closed['show_summary'] === true);
$results[] = fixc_lock_pass('completed hides form', $closed['show_form'] === false);

$open = m360_rw_intake_section_ui_state('service_classification', $payload, $request, $formValues, 'service_classification');
$results[] = fixc_lock_pass('edit_section opens form', $open['show_form'] === true);

$sections = ['condition_notes', 'temporary_reception', 'documents_cost', 'reception_confirmation'];
foreach ($sections as $sec) {
    $results[] = fixc_lock_pass($sec . ' uses section_ui_state', str_contains($intakeSrc, "m360_rw_intake_section_ui_state('$sec'") || str_contains($intakeSrc, 'm360_rw_intake_section_ui_state(\'' . $sec . '\''));
}

$results[] = fixc_lock_pass('edit button ویرایش in helper', str_contains((string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php'), 'ویرایش'));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C-FIX-C Section Lock Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
