<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-final-inspection-helper.php';
$qc = file_get_contents($root . '/public_html/includes/m360-qc-helper.php') ?: '';

function p6c_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$template = m360_final_inspection_checklist_template();
$results = [];
$results[] = p6c_pass('Checklist template', count($template) >= 14);
$results[] = p6c_pass('SERVICE_JOBCARD_MATCH', (bool)array_filter($template, fn($i) => $i['key'] === 'SERVICE_JOBCARD_MATCH'));
$results[] = p6c_pass('DELIVERY_PREP item', (bool)array_filter($template, fn($i) => $i['key'] === 'DELIVERY_PREP_CONFIRM'));
$results[] = p6c_pass('PASS result', defined('M360_QC_ITEM_PASS'));
$results[] = p6c_pass('FAIL result', defined('M360_QC_ITEM_FAIL'));
$results[] = p6c_pass('NOT_APPLICABLE', defined('M360_QC_ITEM_NA'));
$results[] = p6c_pass('validate_pass function', function_exists('m360_final_inspection_validate_pass'));
$results[] = p6c_pass('Empty notes blocked', !m360_final_inspection_validate_pass(false, 1, '')['ok']);
$results[] = p6c_pass('QC pass uses validate', str_contains($qc, 'm360_final_inspection_validate_pass'));
$results[] = p6c_pass('Checklist complete fn', function_exists('m360_final_inspection_checklist_complete'));
$results[] = p6c_pass('Active fail check', function_exists('m360_final_inspection_has_active_fail'));

$pass = 0; $fail = 0;
echo "# P6 Final Inspection Checklist Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
