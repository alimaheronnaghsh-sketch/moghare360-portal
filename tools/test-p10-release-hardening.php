<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . '/public_html';

function p10h_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p10h_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$readinessPage = $public . '/erp-release-readiness.php';
$readinessHelper = $public . '/includes/m360-release-readiness-helper.php';
$hardeningHelper = $public . '/includes/m360-release-hardening-helper.php';

$results = [];
$results[] = p10h_pass('Release readiness page exists', is_file($readinessPage));
$results[] = p10h_pass('Release readiness helper exists', is_file($readinessHelper));
$results[] = p10h_pass('Release hardening helper exists', is_file($hardeningHelper));

require_once $hardeningHelper;
require_once $readinessHelper;

$results[] = p10h_pass('m360_release_hardening_audit exists', function_exists('m360_release_hardening_audit'));
$results[] = p10h_pass('m360_release_readiness_report exists', function_exists('m360_release_readiness_report'));
$results[] = p10h_pass('m360_release_readiness_categories exists', function_exists('m360_release_readiness_categories'));

$audit = m360_release_hardening_audit();
$report = m360_release_readiness_report();
$categories = m360_release_readiness_categories();

$results[] = p10h_pass('Audit has readiness_score', array_key_exists('readiness_score', $audit));
$results[] = p10h_pass('Score null-safe numeric', is_numeric($audit['readiness_score'] ?? null), 'score=' . ($audit['readiness_score'] ?? 'null'));
$results[] = p10h_pass('Score in 0-100 range', (float)($audit['readiness_score'] ?? -1) >= 0 && (float)($audit['readiness_score'] ?? 101) <= 100);
$results[] = p10h_pass('Audit has warnings array', is_array($audit['warnings'] ?? null));
$results[] = p10h_pass('Audit has blockers array', is_array($audit['blockers'] ?? null));
$results[] = p10h_pass('Report has categories', count($categories) >= 8, 'count=' . count($categories));
$results[] = p10h_pass('Report counts PASS/WARNING/BLOCKED', isset($report['counts']['PASS'], $report['counts']['WARNING'], $report['counts']['BLOCKED']));
$results[] = p10h_pass('Report score null-safe', is_numeric($report['readiness_score'] ?? null));
$results[] = p10h_pass('Report has recommendation_fa', ($report['recommendation_fa'] ?? '') !== '');
$results[] = p10h_pass('Category security_scope_lock', (bool)array_filter($categories, static fn(array $c): bool => ($c['key'] ?? '') === 'security_scope_lock'));
$results[] = p10h_pass('Category documentation', (bool)array_filter($categories, static fn(array $c): bool => ($c['key'] ?? '') === 'documentation'));
$results[] = p10h_pass('Readiness page shows categories', str_contains(p10h_read($readinessPage), 'categories'));
$results[] = p10h_pass('Readiness page shows blockers section', str_contains(p10h_read($readinessPage), 'blockers'));

$pass = 0; $fail = 0;
echo "# P10 Release Hardening Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
