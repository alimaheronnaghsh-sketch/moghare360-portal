<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function fixb_plate_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$intakeSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');
$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');

$results[] = fixb_plate_pass('plate part fields in helper', str_contains($helperSrc, 'plate_left_2_digits') && str_contains($helperSrc, 'plate_iran_2_digits'));
$results[] = fixb_plate_pass('plate widget renderer exists', function_exists('m360_rw_intake_render_plate_widget'));
$results[] = fixb_plate_pass('intake uses plate widget', str_contains($intakeSrc, 'm360_rw_intake_render_plate_widget'));
$results[] = fixb_plate_pass('plate letters list non-empty', m360_rw_intake_plate_letters() !== []);

$post = [
    'plate_left_2_digits' => '12',
    'plate_letter' => 'ب',
    'plate_middle_3_digits' => '345',
    'plate_iran_2_digits' => '67',
];
$built = m360_rw_intake_build_plate_from_post($post);
$results[] = fixb_plate_pass('build plate from parts ok', $built['ok']);
$results[] = fixb_plate_pass('normalized plate stored format', $built['plate'] === '12ب345-67');
$results[] = fixb_plate_pass('plate_parts object', ($built['parts']['left_2'] ?? '') === '12' && ($built['parts']['region_2'] ?? '') === '67');

$applied = m360_rw_intake_apply_action([], 'save_vehicle_identity', array_merge($post, [
    'vin' => 'WVWZZZ1JZXW000001',
    'brand' => 'Test',
    'model' => 'X',
    'mileage' => '1000',
    'fuel_level' => 'نصف',
]));
$results[] = fixb_plate_pass('save_vehicle_identity with plate parts', $applied['ok']);
$results[] = fixb_plate_pass('payload vehicle_plate normalized', ($applied['payload']['vehicle_plate'] ?? '') === '12ب345-67');
$results[] = fixb_plate_pass('payload plate_parts stored', !empty($applied['payload']['plate_parts']));

$legacy = m360_rw_intake_apply_action([], 'save_vehicle_identity', [
    'plate' => '99د999-11',
    'vin' => '',
    'brand' => 'Legacy',
    'model' => 'Y',
    'mileage' => '',
    'fuel_level' => '',
]);
$results[] = fixb_plate_pass('old free-form plate still recoverable', $legacy['ok'] && ($legacy['payload']['vehicle_plate'] ?? '') === '99د999-11');

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C-FIX-B Plate UI Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
