<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function pr02a_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$brandJs = (string)file_get_contents($root . '/public_html/assets/js/vehicle-brand-classes.js');
$helper = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');

$results = [];
$results[] = pr02a_pass('top-level other brand constant', str_contains($helper, "M360_RW_TOP_LEVEL_OTHER_BRAND = 'سایر'"));
$results[] = pr02a_pass('JS top-level other has empty model list', str_contains($brandJs, '"سایر": []'));
$results[] = pr02a_pass('JS manager exception panel helper', str_contains($brandJs, 'm360IsTopLevelOtherBrand'));
$results[] = pr02a_pass('JS model list gap helper', str_contains($brandJs, 'MODEL_LIST_GAP'));

$topOtherNoExplain = m360_rw_intake_validate_vehicle_selection([
    'vehicle_brand' => 'سایر',
    'vehicle_year_pair' => '1403 - 2024',
]);
$results[] = pr02a_pass('top-level other requires explanation', !$topOtherNoExplain['ok']);

$topOther = m360_rw_intake_validate_vehicle_selection([
    'vehicle_brand' => 'سایر',
    'vehicle_year_pair' => '1403 - 2024',
    'brand_other_explanation' => 'برند خارج از محدوده',
]);
$results[] = pr02a_pass('top-level other marks manager exception', $topOther['ok'] && $topOther['brand_status'] === 'MANAGER_EXCEPTION_REVIEW');

$modelGap = m360_rw_intake_validate_vehicle_selection([
    'vehicle_brand' => 'پورشه',
    'vehicle_class' => 'سایر',
    'vehicle_year_pair' => '1403 - 2024',
    'model_other_explanation' => 'مدل جدید',
]);
$results[] = pr02a_pass('per-brand other marks MODEL_LIST_GAP', $modelGap['ok'] && $modelGap['model_status'] === 'MODEL_LIST_GAP');

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02A other brand exception: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
