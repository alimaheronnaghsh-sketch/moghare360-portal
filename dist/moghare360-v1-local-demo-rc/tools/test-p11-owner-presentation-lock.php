<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p11o_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p11o_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$page = $public . '/erp-owner-presentation-lock.php';
$scriptDoc = $root . '/docs/demo/MOGHARE360_V1_OWNER_PRESENTATION_SCRIPT_FINAL.md';
$checklistDoc = $root . '/docs/demo/MOGHARE360_V1_DEMO_DAY_CHECKLIST.md';
$lockDoc = $root . '/docs/release/MOGHARE360_V1_OWNER_PRESENTATION_LOCK.md';

$results = [];
$results[] = p11o_pass('Owner presentation page exists', is_file($page));
$results[] = p11o_pass('Presentation script doc exists', is_file($scriptDoc));
$results[] = p11o_pass('Demo day checklist exists', is_file($checklistDoc));
$results[] = p11o_pass('Owner presentation lock doc exists', is_file($lockDoc));

require_once $public . '/includes/m360-owner-presentation-helper.php';
$report = m360_owner_presentation_lock_report();

$results[] = p11o_pass('Flow has 10 steps', count($report['flow'] ?? []) === 10);
$results[] = p11o_pass('V1 exclusions present', count($report['exclusions'] ?? []) >= 8);
$results[] = p11o_pass('Signoff checklist present', count($report['signoff_checklist'] ?? []) >= 5);

$blob = p11o_read($page) . p11o_read($lockDoc) . p11o_read($scriptDoc);
$results[] = p11o_pass('Excludes official accounting', str_contains($blob, 'حسابداری'));
$results[] = p11o_pass('Excludes payment gateway', str_contains($blob, 'درگاه') || str_contains($blob, 'gateway'));
$results[] = p11o_pass('Excludes bank integration', str_contains($blob, 'بانک') || str_contains($blob, 'bank'));
$results[] = p11o_pass('Excludes official tax', str_contains($blob, 'مالیات') || str_contains($blob, 'tax'));
$results[] = p11o_pass('Excludes SaaS multi-company', str_contains($blob, 'SaaS') || str_contains($blob, 'Multi-company'));
$results[] = p11o_pass('No false promise language', !preg_match('/fully integrated accounting|complete erp accounting/i', $blob));
$results[] = p11o_pass('Read-only page', ($report['read_only'] ?? false) === true);

$pass = 0; $fail = 0;
echo "# P11 Owner Presentation Lock Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
