<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';
$readinessHelper = $public . '/includes/m360-demo-readiness-helper.php';
$readinessPage = $public . '/erp-demo-readiness-report.php';
$api = $public . '/api/soft-run/readiness-summary.php';

require_once $readinessHelper;

function p9r_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p9r_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$results = [];
$results[] = p9r_pass('Readiness helper exists', is_file($readinessHelper));
$results[] = p9r_pass('Readiness report page exists', is_file($readinessPage));
$results[] = p9r_pass('Readiness API exists', is_file($api));
$results[] = p9r_pass('m360_readiness_report exists', function_exists('m360_readiness_report'));
$results[] = p9r_pass('m360_readiness_checklist_items exists', function_exists('m360_readiness_checklist_items'));
$results[] = p9r_pass('m360_readiness_checklist_definitions exists', function_exists('m360_readiness_checklist_definitions'));

$report = m360_readiness_report(false);
$results[] = p9r_pass('Report has readiness_score', array_key_exists('readiness_score', $report));
$results[] = p9r_pass('Report has counts', isset($report['counts']['PASS'], $report['counts']['WARNING'], $report['counts']['BLOCKED']));
$results[] = p9r_pass('Report has recommendation_fa', array_key_exists('recommendation_fa', $report));
$results[] = p9r_pass('Score is numeric 0-100', is_numeric($report['readiness_score']) && (float)$report['readiness_score'] >= 0 && (float)$report['readiness_score'] <= 100);
$results[] = p9r_pass('Checklist items populated', count($report['items'] ?? []) >= 20);
$results[] = p9r_pass('P9 migration flag in report', !empty($report['migrations']['p9']) || is_file($root . '/database/migrations/P9_end_to_end_soft_run.sql'));
$results[] = p9r_pass('P8 migration flag in report', array_key_exists('p8', $report['migrations'] ?? []));
$results[] = p9r_pass('Security scope in report', !empty($report['security_scope']['no_workflow_mutation']));
$results[] = p9r_pass('Readiness page shows score card', str_contains(p9r_read($readinessPage), 'Readiness Score') || str_contains(p9r_read($readinessPage), 'readiness_score'));
$results[] = p9r_pass('API GET only', str_contains(p9r_read($api), "!== 'GET'"));
$results[] = p9r_pass('API returns readiness_score', str_contains(p9r_read($api), "'report' => \$report") || str_contains(p9r_read($api), 'm360_readiness_report'));
$results[] = p9r_pass('Score formula PASS/total', str_contains(p9r_read($readinessHelper), "counts['PASS']"));

$pass = 0; $fail = 0;
echo "# P9 Readiness Report Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
