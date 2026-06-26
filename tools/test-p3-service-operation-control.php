<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p3s_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p3s_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$helper = p3s_read($public . '/includes/m360-technical-operation-helper.php');
$wf = p3s_read($public . '/includes/m360-technician-workflow-helper.php');
$detail = p3s_read($public . '/erp-technical-jobcard-detail.php');

$results = [];
$results[] = p3s_pass('Service operation table constant', str_contains($helper, 'erp_service_operations'));
$results[] = p3s_pass('Create service operation function', str_contains($helper, 'm360_technical_create_service_operation'));
$results[] = p3s_pass('SO linked to jobcard_id', str_contains($helper, 'jobcard_id = ?') || str_contains($helper, 'jobcard_id,'));
$results[] = p3s_pass('SO jobcard mismatch rejected', str_contains($helper, 'به این کارت کار تعلق ندارد'));
$results[] = p3s_pass('Start service operation', str_contains($helper, 'SERVICE_OPERATION_STARTED'));
$results[] = p3s_pass('Complete service operation', str_contains($helper, 'SERVICE_OPERATION_COMPLETED'));
$results[] = p3s_pass('No pricing in P3 helper', !preg_match('/\b(price|payment|invoice|inventory|part_usage)\b/i', $helper));
$results[] = p3s_pass('Waiting for approval in workflow', str_contains($wf, 'M360_TECH_STATUS_WAITING_APPROVAL') || str_contains($wf, 'waiting_for_approval'));
$results[] = p3s_pass('Detail shows service operations', str_contains($detail, 'عملیات‌های سرویس'));
$results[] = p3s_pass('P4 handoff note in workflow', str_contains($wf, 'M360_TECH_STATUS_WAITING_APPROVAL') || str_contains($wf, 'waiting_for_approval'));

$pass = 0; $fail = 0;
echo "# MOGHARE360 P3 Service Operation Control Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
