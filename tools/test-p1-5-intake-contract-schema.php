<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . DIRECTORY_SEPARATOR . 'public_html';
$migration = file_get_contents($root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . 'P1_5_intake_contract_signature.sql') ?: '';

function p15s_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p15s_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$results = [];
$results[] = p15s_pass('Migration file exists', is_file($root . '/database/migrations/P1_5_intake_contract_signature.sql'));
$results[] = p15s_pass('Migration non-destructive', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p15s_pass('erp_intake_contracts table defined', str_contains($migration, 'erp_intake_contracts'));
$results[] = p15s_pass('erp_intake_contract_signatures defined', str_contains($migration, 'erp_intake_contract_signatures'));
$results[] = p15s_pass('erp_intake_contract_events defined', str_contains($migration, 'erp_intake_contract_events'));
$results[] = p15s_pass('JobCard contract columns', str_contains($migration, 'contract_status') && str_contains($migration, 'intake_contract_id'));
$results[] = p15s_pass('Contract version MOGHARE360-INTAKE-V1 in migration default', str_contains($migration, 'MOGHARE360-INTAKE-V1'));

$helper = p15s_read($public . '/includes/m360-intake-contract-helper.php');
$results[] = p15s_pass('Helper defines contract version', str_contains($helper, 'MOGHARE360-INTAKE-V1') || str_contains($helper, 'M360_CONTRACT_VERSION'));

$pass = 0; $fail = 0;
echo "# P1.5 Contract Schema Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
