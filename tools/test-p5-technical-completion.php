<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-technical-completion-helper.php';
$work = file_get_contents($root . '/public_html/includes/m360-work-execution-helper.php') ?: '';
$tech = file_get_contents($root . '/public_html/includes/m360-technical-completion-helper.php') ?: '';
$detail = file_get_contents($root . '/public_html/erp-work-execution-detail.php') ?: '';

function p5t_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p5t_pass('start_work action', str_contains($work, "'start_work'"));
$results[] = p5t_pass('complete_technical_work action', str_contains($work, "'complete_technical_work'"));
$results[] = p5t_pass('ready_for_qc action', str_contains($work, "'ready_for_qc'"));
$results[] = p5t_pass('Service op note required', str_contains($work, 'operation_result_note') || str_contains($work, 'نتیجه عملیات'));
$results[] = p5t_pass('Completion notes validate', str_contains($tech, 'technical_completion_notes'));
$results[] = p5t_pass('ready_for_qc validate', function_exists('m360_technical_ready_for_qc_validate'));
$results[] = p5t_pass('Service ops must be DONE', str_contains($tech, 'M360_SO_STATUS_COMPLETED'));
$results[] = p5t_pass('READY_FOR_QC status', str_contains($work, 'READY_FOR_QC'));
$results[] = p5t_pass('No QC checklist module', !preg_match('/qc_checklist|erp_qc_/i', $work . $tech . $detail));
$results[] = p5t_pass('Detail has completion notes form', str_contains($detail, 'save_completion_notes'));

$pass = 0; $fail = 0;
echo "# P5 Technical Completion Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
