<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';
$script = $root . '/tools/package-moghare360-v1-local-demo.ps1';

function p11p_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p11p_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$results = [];
$results[] = p11p_pass('erp-local-demo-package.php exists', is_file($public . '/erp-local-demo-package.php'));
$results[] = p11p_pass('m360-local-demo-package-helper exists', is_file($public . '/includes/m360-local-demo-package-helper.php'));
$results[] = p11p_pass('package script exists', is_file($script));
$results[] = p11p_pass('manifest doc exists', is_file($root . '/docs/release/MOGHARE360_V1_LOCAL_DEMO_PACKAGE_MANIFEST.md'));

require_once $public . '/includes/m360-local-demo-package-helper.php';
$pkg = m360_local_package_status();

$results[] = p11p_pass('dist path defined', ($pkg['dist_dir'] ?? '') === 'dist/moghare360-v1-local-demo-rc');
$results[] = p11p_pass('zip path defined', ($pkg['zip_path'] ?? '') === 'dist/moghare360-v1-local-demo-rc.zip');
$results[] = p11p_pass('UI does not build zip', ($pkg['ui_builds_zip'] ?? true) === false);
$results[] = p11p_pass('Package page no ZipArchive', !preg_match('/\bZipArchive\b/i', p11p_read($public . '/erp-local-demo-package.php')));
$results[] = p11p_pass('Package page no Compress-Archive', !str_contains(p11p_read($public . '/erp-local-demo-package.php'), 'Compress-Archive'));
$results[] = p11p_pass('Script has private exclusion', str_contains(p11p_read($script), 'private'));
$results[] = p11p_pass('Script has .env exclusion', str_contains(p11p_read($script), '.env'));
$results[] = p11p_pass('Script has config exclusion', str_contains(p11p_read($script), 'config.php'));
$results[] = p11p_pass('Script has DryRun', str_contains(p11p_read($script), 'DryRun'));
$results[] = p11p_pass('Exclude rules in helper', count($pkg['exclude_rules'] ?? []) >= 10);

$pass = 0; $fail = 0;
echo "# P11 Local Demo Package Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
