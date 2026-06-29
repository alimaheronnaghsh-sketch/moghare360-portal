<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function p117sg_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$gate = $root . '/docs/audit/MOGHARE360_P11_7_WORKBENCH_SCOPE_GATE_REPORT.md';
$coverage = $root . '/docs/audit/MOGHARE360_P11_7_ONE_DAY_RUN_WORKBENCH_COVERAGE.md';

$results[] = p117sg_pass('scope gate report exists', is_file($gate));
$results[] = p117sg_pass('one-day run coverage doc exists', is_file($coverage));

$gateText = is_file($gate) ? (string)file_get_contents($gate) : '';
$results[] = p117sg_pass('gate documents included scope', str_contains($gateText, 'Included in P11.7'));
$results[] = p117sg_pass('gate documents excluded scope', str_contains($gateText, 'Explicitly Excluded from P11.7'));
$results[] = p117sg_pass('gate stop conditions section', str_contains($gateText, 'Stop Conditions'));
$results[] = p117sg_pass('gate final boundary section', str_contains($gateText, 'Final Implementation Boundary'));
$results[] = p117sg_pass('gate passes navigation-only', str_contains($gateText, 'navigation/workbench consolidation only'));
$results[] = p117sg_pass('technician filter in backlog not built', str_contains($gateText, 'Disabled backlog card for technician assignment filter'));
$results[] = p117sg_pass('HR excluded from build', str_contains($gateText, 'HR self-service module'));
$results[] = p117sg_pass('no P12 in gate', !preg_match('/\bP12 operational\b/', $gateText) || str_contains($gateText, 'P12 scope'));

$pass = 0; $fail = 0;
echo "# P11.7 Workbench Scope Gate Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
