<?php
declare(strict_types=1);

/**
 * MOGHARE360 P2 — Contract gate integration with P1.5.
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p2g_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function p2g_read(string $path): string
{
    return is_file($path) ? (string)file_get_contents($path) : '';
}

$helper = p2g_read($public . '/includes/m360-reception-jobcard-helper.php');
$intake = p2g_read($public . '/includes/m360-intake-contract-helper.php');

$results = [];
$results[] = p2g_pass('P1.5 intake helper exists', is_file($public . '/includes/m360-intake-contract-helper.php'));
$results[] = p2g_pass('m360_contract_can_continue_to_p2', str_contains($intake, 'function m360_contract_can_continue_to_p2'));
$results[] = p2g_pass('P15 gate availability check', str_contains($helper, 'm360_reception_jobcard_p15_gate_available'));
$results[] = p2g_pass('P15 gate fail closed message', str_contains($helper, 'P1.5 Gate missing'));
$results[] = p2g_pass('ready_for_technical calls gate', str_contains($helper, 'm360_contract_can_continue_to_p2($jobcardId)'));
$results[] = p2g_pass('Blocked event recorded', str_contains($helper, 'JOBCARD_READY_FOR_TECHNICAL_BLOCKED_CONTRACT_UNSIGNED'));
$results[] = p2g_pass('Manager override min reason', str_contains($helper, 'mb_strlen($reason) < 10'));
$results[] = p2g_pass('Manager override audit event', str_contains($helper, 'JOBCARD_CONTRACT_GATE_MANAGER_OVERRIDE'));
$results[] = p2g_pass('Uses m360_intake_contract_apply_manager_override', str_contains($helper, 'm360_intake_contract_apply_manager_override'));
$results[] = p2g_pass('No contract gate bypass', !str_contains($helper, 'can_continue_to_p2') || str_contains($helper, 'if (!m360_contract_can_continue_to_p2'));

require_once $public . '/includes/m360-intake-contract-helper.php';
require_once $public . '/includes/m360-reception-jobcard-helper.php';

$results[] = p2g_pass('Runtime P15 gate available', m360_reception_jobcard_p15_gate_available());
$results[] = p2g_pass('Runtime unsigned blocks continue', m360_contract_can_continue_to_p2(999999) === false);

$pass = 0;
$fail = 0;
echo "# MOGHARE360 P2 Contract Gate Integration Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'];
    if ($r['detail'] !== '') {
        echo ' — ' . $r['detail'];
    }
    echo "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
