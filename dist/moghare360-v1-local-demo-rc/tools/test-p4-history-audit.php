<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$helper = file_get_contents($root . '/public_html/includes/m360-estimate-helper.php') ?: '';
$approval = file_get_contents($root . '/public_html/includes/m360-estimate-approval-helper.php') ?: '';
$all = $helper . $approval;
$migration = file_get_contents($root . '/database/migrations/P4_estimate_approval_parts_finance_gate.sql') ?: '';

function p4h_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }

$events = ['ESTIMATE_DRAFT_CREATED','ESTIMATE_ITEM_ADDED','ESTIMATE_SENT_TO_CUSTOMER','ESTIMATE_CUSTOMER_APPROVED','ESTIMATE_APPROVED_FOR_WORK_BLOCKED_GATE','JOBCARD_ESTIMATE_CREATED','JOBCARD_APPROVED_FOR_WORK'];

$results = [];
foreach ($events as $ev) {
    $results[] = p4h_pass('Event: ' . $ev, str_contains($all, $ev));
}
$results[] = p4h_pass('No destructive SQL', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p4h_pass('Auth unchanged', !preg_match('/function\s+erp_auth_/', $all));
$results[] = p4h_pass('No credentials', !preg_match('/api_key|password\s*=/i', $all));

$pass = 0; $fail = 0;
echo "# P4 History Audit Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
