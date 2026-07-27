<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function pr02a_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$helper = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$intakeJs = (string)file_get_contents($root . '/public_html/assets/js/m360-reception-intake.js');
$customerPhp = (string)file_get_contents($root . '/public_html/customer-request.php');
$customerJs = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');
$intakePhp = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');

$results = [];
$results[] = pr02a_pass('reception plate uses digit selects', str_contains($helper, 'plate_first_digit_1') && str_contains($helper, 'plate_region_digit_1'));
$results[] = pr02a_pass('reception plate removed free-text parts', !str_contains($helper, 'name="plate_left_2_digits" maxlength="2" inputmode="numeric"'));
$results[] = pr02a_pass('reception plate uses plate_region_2_digits', str_contains($helper, 'plate_region_2_digits'));
$results[] = pr02a_pass('reception plate build rejects empty controlled input', str_contains($helper, 'پلاک باید با انتخاب رقم و حرف تکمیل شود'));
$results[] = pr02a_pass('reception plate build removed free-text fallback', !str_contains($helper, "\$fallback = trim((string)(\$post['plate']"));
$results[] = pr02a_pass('reception intake JS mirrors customer plate display', str_contains($intakeJs, 'ایران') && str_contains($intakeJs, 'plate_region_digit_1'));
$results[] = pr02a_pass('intake page loads mirror.css', str_contains($intakePhp, 'assets/css/mirror.css'));
$results[] = pr02a_pass('customer plate standard source unchanged structure', str_contains($customerPhp, 'plate_first_digit_1') && str_contains($customerJs, 'buildPlateDisplay'));

$built = m360_rw_intake_build_plate_from_post([
    'plate_first_digit_1' => '1',
    'plate_first_digit_2' => '2',
    'plate_letter' => 'ب',
    'plate_middle_digit_1' => '3',
    'plate_middle_digit_2' => '4',
    'plate_middle_digit_3' => '5',
    'plate_region_digit_1' => '6',
    'plate_region_digit_2' => '7',
]);
$results[] = pr02a_pass('plate build from digit fields', $built['ok'] && $built['plate'] === '12ب345-67');
$results[] = pr02a_pass('plate display includes ایران segment', str_contains((string)($built['display'] ?? ''), 'ایران'));

$fail = m360_rw_intake_build_plate_from_post(['plate' => 'free text plate']);
$results[] = pr02a_pass('free-text plate not accepted as normal flow', !$fail['ok']);

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02A plate alignment: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
