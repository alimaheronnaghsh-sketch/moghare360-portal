<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr02b_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

require_once $root . '/public_html/includes/m360-customer-online-submit-helper.php';

$results = [];
$helper = (string)file_get_contents($root . '/public_html/includes/m360-customer-online-submit-helper.php');
$page = (string)file_get_contents($root . '/public_html/customer-request.php');
$js = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');

$results[] = pr02b_pass('list customer vehicles helper', str_contains($helper, 'm360_pr02b_list_customer_vehicles'));
$results[] = pr02b_pass('resolve vehicle selected id', str_contains($helper, 'selected_vehicle_id'));
$results[] = pr02b_pass('ensure relation on new vehicle', str_contains($helper, 'm360_reception_ensure_relation'));
$results[] = pr02b_pass('vehicle picker UI exists', str_contains($page, 'm360_vehicle_picker'));
$results[] = pr02b_pass('selected_vehicle_id hidden input', str_contains($page, 'id="selected_vehicle_id"'));
$results[] = pr02b_pass('add new vehicle button', str_contains($page, 'm360_vehicle_add_new'));
$results[] = pr02b_pass('vehicle mode hidden', str_contains($page, 'id="vehicle_mode"'));
$results[] = pr02b_pass('JS render vehicle picker', str_contains($js, 'renderVehiclePicker'));
$results[] = pr02b_pass('one mobile one vehicle assumption removed', !str_contains($js, 'last_vehicle') || str_contains($js, 'payload.vehicles'));
$results[] = pr02b_pass('approved brands only', in_array('بنز', m360_pr02b_approved_vehicle_brands(), true));
$results[] = pr02b_pass('toyota brand filter helper', str_contains($helper, 'm360_pr02b_is_supported_vehicle_brand'));
$results[] = pr02b_pass('vehicles out of scope API', str_contains((string)file_get_contents($root . '/public_html/api/customer/profile-status.php'), 'vehicles_out_of_scope'));
$results[] = pr02b_pass('ASIAN_BRANDS_REINTRODUCED no', !in_array('تویوتا', m360_pr02b_approved_vehicle_brands(), true));

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02B multi-vehicle linking: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
