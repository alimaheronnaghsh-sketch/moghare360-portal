<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function fixc_anchor_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$intakeSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');
$saveSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-save.php');
$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');

$anchors = [
    'section-mobile-otp',
    'section-vehicle-identity',
    'section-condition-notes',
    'section-service-classification',
    'section-temporary-reception',
    'section-referral-team',
    'section-camera-photo',
    'section-diagnostic-pdf',
    'section-contract',
    'section-documents-cost',
    'section-reception-confirmation',
    'section-gate-checklist',
];
foreach ($anchors as $a) {
    $results[] = fixc_anchor_pass('anchor id ' . $a, str_contains($intakeSrc, 'id="' . $a . '"'));
}

$results[] = fixc_anchor_pass('edit url includes hash', str_contains($helperSrc, "#' . \$anchor") || str_contains($helperSrc, '#\' . $anchor'));
$results[] = fixc_anchor_pass('return_section helper', function_exists('m360_rw_intake_return_section_hidden'));
$results[] = fixc_anchor_pass('forms include return_section', str_contains($intakeSrc, 'm360_rw_intake_return_section_hidden'));
$results[] = fixc_anchor_pass('redirect uses post for anchor', str_contains($saveSrc, '$_POST') && str_contains($helperSrc, 'return_section'));
$results[] = fixc_anchor_pass('redirect appends hash', str_contains($helperSrc, "#' . \$returnSection"));
$results[] = fixc_anchor_pass('GET edit_section no write in intake', !preg_match('/\$_GET\s*\[\s*[\'"]edit_section[\'"]\s*\][^;]*UPDATE/i', $intakeSrc));

$url = m360_rw_intake_save_redirect_url(18, 'تست', true, ['action_type' => 'save_vehicle_identity']);
$results[] = fixc_anchor_pass('save redirect includes section hash', str_contains($url, '#section-vehicle-identity'));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C-FIX-C Scroll Anchor Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
