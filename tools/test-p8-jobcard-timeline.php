<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$timelineHelper = is_file($root . '/public_html/includes/m360-jobcard-timeline-helper.php')
    ? (string)file_get_contents($root . '/public_html/includes/m360-jobcard-timeline-helper.php')
    : '';
$timelinePage = is_file($root . '/public_html/erp-jobcard-timeline.php')
    ? (string)file_get_contents($root . '/public_html/erp-jobcard-timeline.php')
    : '';

require_once $root . '/public_html/includes/m360-jobcard-timeline-helper.php';

function p8t_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$emptyConn = m360_timeline_build(false, 1);
$invalidId = m360_timeline_build(false, 0);
$fromMissing = m360_timeline_from_table(false, 'erp_qc_events', 'jobcard_id', 'event_name', 'created_at', 1, 'qc', true);

$results = [];
$results[] = p8t_pass('Timeline page exists', is_file($root . '/public_html/erp-jobcard-timeline.php'));
$results[] = p8t_pass('Timeline helper exists', is_file($root . '/public_html/includes/m360-jobcard-timeline-helper.php'));
$results[] = p8t_pass('Timeline helper loaded', function_exists('m360_timeline_build'));
$results[] = p8t_pass('Build returns jobcard key', array_key_exists('jobcard', $emptyConn));
$results[] = p8t_pass('Build returns events key', array_key_exists('events', $emptyConn) && is_array($emptyConn['events']));
$results[] = p8t_pass('No conn null jobcard', array_key_exists('jobcard', $emptyConn) && $emptyConn['jobcard'] === null);
$results[] = p8t_pass('Invalid id empty events', ($invalidId['events'] ?? null) === []);
$results[] = p8t_pass('table_exists guard in build', str_contains($timelineHelper, 'customer_core_table_exists'));
$results[] = p8t_pass('table_exists guard in from_table', str_contains($timelineHelper, 'customer_core_table_exists($conn, $table)'));
$results[] = p8t_pass('Missing table returns empty', $fromMissing === []);
$results[] = p8t_pass('Read-only page note', str_contains($timelinePage, 'read-only'));
$results[] = p8t_pass('No SQL writes in helper', !preg_match('/\b(INSERT INTO|UPDATE dbo|DELETE FROM)\b/i', $timelineHelper));
$results[] = p8t_pass('Timeline API GET only', str_contains((string)file_get_contents($root . '/public_html/api/management/jobcard-timeline.php'), "!== 'GET'"));

$pass = 0; $fail = 0;
echo "# P8 JobCard Timeline Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
