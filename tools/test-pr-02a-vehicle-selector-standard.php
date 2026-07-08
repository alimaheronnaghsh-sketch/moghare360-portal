<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function pr02a_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$helper = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$brandJs = (string)file_get_contents($root . '/public_html/assets/js/vehicle-brand-classes.js');
$intakePhp = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');

$approved = m360_rw_intake_approved_vehicle_brands();
$results = [];
$results[] = pr02a_pass('approved brands count is 6', count($approved) === 6);
$results[] = pr02a_pass('approved brands include luxury set', in_array('بنز', $approved, true) && in_array('پورشه', $approved, true) && in_array('فولکس واگن', $approved, true));
$results[] = pr02a_pass('asian brands not in approved set', !in_array('تویوتا', $approved, true) && !in_array('هیوندای', $approved, true));
$results[] = pr02a_pass('vehicle-brand-classes has Porsche Macan', str_contains($brandJs, '"پورشه"') && str_contains($brandJs, 'Macan'));
$results[] = pr02a_pass('brand dependent activation helper exists', str_contains($brandJs, 'm360PopulateVehicleClasses'));
$results[] = pr02a_pass('reception removed free-text brand field', !str_contains($intakePhp, "m360_rw_intake_form_field('برند', 'brand'"));
$results[] = pr02a_pass('reception uses controlled vehicle selector', str_contains($intakePhp, 'm360_rw_intake_render_vehicle_selector'));
$results[] = pr02a_pass('save uses validate_vehicle_selection', str_contains($helper, 'm360_rw_intake_validate_vehicle_selection'));

$porscheOk = m360_rw_intake_validate_vehicle_selection([
    'vehicle_brand' => 'پورشه',
    'vehicle_class' => 'Macan',
    'vehicle_year_pair' => '1403 - 2024',
]);
$results[] = pr02a_pass('Porsche Macan validates', $porscheOk['ok']);

$gap = m360_rw_intake_validate_vehicle_selection([
    'vehicle_brand' => 'بنز',
    'vehicle_class' => 'سایر',
    'vehicle_year_pair' => '1403 - 2024',
]);
$results[] = pr02a_pass('per-brand other requires explanation', !$gap['ok']);

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02A vehicle selector: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
