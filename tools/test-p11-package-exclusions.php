<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$script = $root . '/tools/package-moghare360-v1-local-demo.ps1';
$content = is_file($script) ? (string)file_get_contents($script) : '';

function p11e_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$mustExclude = ['private', '.env', 'config.php', '.bak', '.log', '.git', 'uploads', 'cache', 'node_modules', 'vendor'];
$results = [];
$results[] = p11e_pass('Package script exists', is_file($script));

foreach ($mustExclude as $ex) {
    $results[] = p11e_pass('Excludes ' . $ex, str_contains($content, $ex));
}

$results[] = p11e_pass('Credential scan patterns', str_contains($content, 'strictPatterns') || str_contains($content, 'api[_-]?key'));
$results[] = p11e_pass('Real data scan warning', str_contains($content, 'real-like') || str_contains($content, 'DEMO'));
$results[] = p11e_pass('SHA256 output', str_contains($content, 'SHA256') && str_contains($content, 'PACKAGE_SHA256'));
$results[] = p11e_pass('Fail on suspect', str_contains($content, 'exit 1') && str_contains($content, 'suspects'));
$results[] = p11e_pass('DryRun parameter', str_contains($content, 'DryRun'));
$results[] = p11e_pass('Dist output path', str_contains($content, 'moghare360-v1-local-demo-rc'));

$pass = 0; $fail = 0;
echo "# P11 Package Exclusions Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
