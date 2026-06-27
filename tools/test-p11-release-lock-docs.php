<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function p11d_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p11d_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$docs = [
    'RC final lock' => $root . '/docs/release/MOGHARE360_V1_RC_FINAL_LOCK.md',
    'Security exclusions' => $root . '/docs/release/MOGHARE360_V1_FINAL_SECURITY_EXCLUSIONS.md',
    'Local demo manifest' => $root . '/docs/release/MOGHARE360_V1_LOCAL_DEMO_PACKAGE_MANIFEST.md',
    'Owner presentation lock' => $root . '/docs/release/MOGHARE360_V1_OWNER_PRESENTATION_LOCK.md',
    'RC final audit report' => $root . '/docs/release/MOGHARE360_V1_RC_FINAL_AUDIT_REPORT.md',
];

$results = [];
foreach ($docs as $label => $path) {
    $results[] = p11d_pass($label . ' doc exists', is_file($path));
}

$lockDoc = p11d_read($docs['RC final lock']);
$results[] = p11d_pass('No further feature build rule', str_contains($lockDoc, 'No Further Feature Build') || str_contains($lockDoc, 'no new workflow'));
$results[] = p11d_pass('P11 included in lock', str_contains($lockDoc, 'P11'));
$results[] = p11d_pass('Next allowed actions listed', str_contains($lockDoc, 'Demo') && str_contains($lockDoc, 'Bugfix'));
$results[] = p11d_pass('Security doc forbids destructive SQL', str_contains(p11d_read($docs['Security exclusions']), 'DROP'));
$results[] = p11d_pass('Manifest references package script', str_contains(p11d_read($docs['Local demo manifest']), 'package-moghare360-v1-local-demo.ps1'));

$pass = 0; $fail = 0;
echo "# P11 Release Lock Docs Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
