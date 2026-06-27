<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p3g_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p3g_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$helper = p3g_read($public . '/includes/m360-technical-operation-helper.php');
$wf = p3g_read($public . '/includes/m360-technician-workflow-helper.php');
$detail = p3g_read($public . '/erp-technical-jobcard-detail.php');

$results = [];
$results[] = p3g_pass('P2 ready check', str_contains($wf, 'm360_technician_workflow_is_p2_ready'));
$results[] = p3g_pass('Contract gate check', str_contains($helper, 'm360_contract_can_continue_to_p2'));
$results[] = p3g_pass('Blocked not ready event', str_contains($helper, 'JOBCARD_TECHNICAL_ACTION_BLOCKED_NOT_READY'));
$results[] = p3g_pass('Blocked contract event', str_contains($helper, 'JOBCARD_TECHNICAL_ACTION_BLOCKED_CONTRACT_GATE'));
$results[] = p3g_pass('Board filters READY_FOR_TECHNICAL', str_contains($helper, "jobcard_status = N'READY_FOR_TECHNICAL'"));
$results[] = p3g_pass('Detail gate messages', str_contains($detail, 'این پرونده هنوز از پذیرش برای عملیات فنی آزاد نشده است') || str_contains($helper, 'این پرونده هنوز از پذیرش برای عملیات فنی آزاد نشده است'));
$results[] = p3g_pass('Contract gate message', str_contains($helper, 'قرارداد پذیرش هنوز امضا یا override معتبر نشده است'));
$results[] = p3g_pass('No gate bypass', str_contains($helper, 'm360_technical_assert_gates'));

require_once $public . '/includes/m360-intake-contract-helper.php';
require_once $public . '/includes/m360-technician-workflow-helper.php';

$rowNotReady = ['jobcard_status' => 'RECEIVED', 'technical_status' => ''];
$results[] = p3g_pass('Runtime not P2 ready', m360_technician_workflow_is_p2_ready($rowNotReady) === false);

$rowReady = ['jobcard_status' => 'READY_FOR_TECHNICAL', 'technical_status' => ''];
$results[] = p3g_pass('Runtime P2 ready', m360_technician_workflow_is_p2_ready($rowReady) === true);

$results[] = p3g_pass('Runtime unsigned contract blocks', m360_contract_can_continue_to_p2(999999) === false);

$pass = 0; $fail = 0;
echo "# MOGHARE360 P3 P2 Gate Enforcement Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
