<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p10d_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p10d_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$demoPage = $public . '/erp-demo-package-rc.php';
$demoHelper = $public . '/includes/m360-demo-package-helper.php';

$requiredDocs = [
    'docs/release/MOGHARE360_V1_RC_MANIFEST.md',
    'docs/release/MOGHARE360_V1_DEMO_PACKAGE_RC.md',
    'docs/release/MOGHARE360_V1_RELEASE_READINESS_REPORT.md',
    'docs/release/MOGHARE360_V1_ROUTE_MAP.md',
    'docs/release/MOGHARE360_V1_SECURITY_SCOPE_LOCK.md',
    'docs/demo/MOGHARE360_V1_OWNER_DEMO_RUNBOOK.md',
];

$results = [];
$results[] = p10d_pass('Demo package page exists', is_file($demoPage));
$results[] = p10d_pass('Demo package helper exists', is_file($demoHelper));
foreach ($requiredDocs as $doc) {
    $results[] = p10d_pass('Doc exists: ' . basename($doc), is_file($root . '/' . $doc));
}

require_once $demoHelper;
$manifest = m360_demo_package_manifest();

$results[] = p10d_pass('m360_demo_package_manifest exists', function_exists('m360_demo_package_manifest'));
$results[] = p10d_pass('Manifest has rc_version', ($manifest['rc_version'] ?? '') !== '');
$results[] = p10d_pass('Manifest has owner_demo_order', count($manifest['owner_demo_order'] ?? []) >= 4);
$results[] = p10d_pass('Manifest references P1–P10 migrations', count($manifest['migrations'] ?? []) >= 10);
$results[] = p10d_pass('P10 migration in manifest', in_array('P10_release_hardening_navigation_rc.sql', $manifest['migrations'] ?? [], true));
$results[] = p10d_pass('Manifest lists P10 tests', (bool)array_filter($manifest['tests'] ?? [], static fn(string $t): bool => str_starts_with($t, 'test-p10-')));
$results[] = p10d_pass('No zip without safe tool', empty($manifest['package_zip_available']));
$results[] = p10d_pass('Package build note present', str_contains((string)($manifest['package_build_note'] ?? ''), 'manifest'));
$results[] = p10d_pass('Owner runbook referenced in page', str_contains(p10d_read($demoPage), 'Owner Demo Order') || str_contains(p10d_read($demoPage), 'owner_demo_order'));
$results[] = p10d_pass('Page shows P1–P10 migrations heading', str_contains(p10d_read($demoPage), 'P1–P10'));
$results[] = p10d_pass('No ZipArchive build in demo helper', !preg_match('/\bZipArchive\b/i', p10d_read($demoHelper)));

$pass = 0; $fail = 0;
echo "# P10 Demo Package RC Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
