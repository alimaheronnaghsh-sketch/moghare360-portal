<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p15g_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p15g_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$helper = p15g_read($public . '/includes/m360-intake-contract-helper.php');

$results = [];
$results[] = p15g_pass('Gate helper functions', str_contains($helper, 'm360_contract_can_continue_to_p2') && str_contains($helper, 'm360_contract_signed_for_jobcard'));
$results[] = p15g_pass('Required for jobcard', str_contains($helper, 'm360_contract_required_for_jobcard'));
$results[] = p15g_pass('Manager override needs reason', str_contains($helper, 'm360_intake_contract_apply_manager_override') && preg_match('/reason === \'\'/', $helper) === 1);
$results[] = p15g_pass('Override audit event', str_contains($helper, 'CONTRACT_MANAGER_OVERRIDE'));
$results[] = p15g_pass('Signed status allows continue', str_contains($helper, 'M360_CONTRACT_STATUS_SIGNED'));
$results[] = p15g_pass('Unsigned prevents by default', str_contains($helper, 'return m360_contract_signed_for_jobcard'));

require_once $public . '/includes/m360-intake-contract-helper.php';
$results[] = p15g_pass('Runtime unsigned blocks P2', m360_contract_can_continue_to_p2(999999) === false);

$pass = 0; $fail = 0;
echo "# P1.5 Contract Gate Before P2 Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
