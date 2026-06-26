<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$migration = file_get_contents($root . '/database/migrations/P4_estimate_approval_parts_finance_gate.sql') ?: '';
$helper = file_get_contents($root . '/public_html/includes/m360-estimate-helper.php') ?: '';

function p4s_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p4s_pass('Migration file exists', is_file($root . '/database/migrations/P4_estimate_approval_parts_finance_gate.sql'));
$results[] = p4s_pass('No DROP/DELETE/TRUNCATE', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p4s_pass('erp_estimates table', str_contains($migration, 'erp_estimates'));
$results[] = p4s_pass('erp_estimate_items table', str_contains($migration, 'erp_estimate_items'));
$results[] = p4s_pass('erp_estimate_approvals table', str_contains($migration, 'erp_estimate_approvals'));
$results[] = p4s_pass('erp_estimate_events table', str_contains($migration, 'erp_estimate_events'));
$results[] = p4s_pass('Jobcard estimate columns', str_contains($migration, 'estimate_status') && str_contains($migration, 'current_estimate_id'));
$results[] = p4s_pass('secure_token_hash not raw token', str_contains($migration, 'secure_token_hash') && !str_contains($migration, 'secure_token_raw'));

$pass = 0; $fail = 0;
echo "# P4 Estimate Schema Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
