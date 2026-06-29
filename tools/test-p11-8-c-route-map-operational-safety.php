<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-route-operational-safety-helper.php';

function p118c_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

$results = [];
$page = (string)file_get_contents($pub . '/erp-route-map.php');

$results[] = p118c_pass('route map includes safety helper', str_contains($page, 'm360-route-operational-safety-helper.php'));
$results[] = p118c_pass('operational view tab', str_contains($page, 'view=operational') && str_contains($page, 'نمای عملیاتی'));
$results[] = p118c_pass('technical view tab', str_contains($page, 'view=technical') && str_contains($page, 'نمای فنی'));
$results[] = p118c_pass('no raw File OK badge', !preg_match('/>\s*OK\s*</', $page));
$results[] = p118c_pass('file status label present', str_contains($page, 'ops_file_status_fa') || str_contains($page, 'فایل موجود'));

$rows = m360_route_ops_enrich_audit_rows();
$results[] = p118c_pass('all registry routes enriched', count($rows) === count(m360_nav_registry()));

$opsClickable = 0;
$normalLinkCount = 0;
foreach ($rows as $row) {
    if (!empty($row['ops_link_operational'])) {
        $opsClickable++;
    }
    if (($row['ops_class'] ?? '') === M360_ROUTE_OPS_CLASS_OPERATIONAL && !empty($row['ops_link_operational'])) {
        $normalLinkCount++;
    }
}
$results[] = p118c_pass('not all routes ops-clickable', $opsClickable < count($rows), "$opsClickable/" . count($rows));
$results[] = p118c_pass('ops clickable less than half registry', $opsClickable < (count($rows) / 2));

foreach ($rows as $row) {
    $url = (string)($row['url'] ?? '');
    if (strtoupper((string)($row['expected_method'] ?? '')) === 'POST') {
        $results[] = p118c_pass('POST not ops-clickable: ' . $url, empty($row['ops_link_operational']));
        if (!empty($row['is_api'])) {
            $results[] = p118c_pass('POST API is api class: ' . $url, ($row['ops_class'] ?? '') === M360_ROUTE_OPS_CLASS_API);
        } else {
            $results[] = p118c_pass('POST is action class: ' . $url, ($row['ops_class'] ?? '') === M360_ROUTE_OPS_CLASS_ACTION);
        }
    }
    if (!empty($row['is_api'])) {
        $results[] = p118c_pass('API not ops-clickable: ' . $url, empty($row['ops_link_operational']));
        $results[] = p118c_pass('API class: ' . $url, ($row['ops_class'] ?? '') === M360_ROUTE_OPS_CLASS_API);
    }
    if (str_ends_with($url, '-detail.php')) {
        $results[] = p118c_pass('detail guided: ' . $url, ($row['ops_class'] ?? '') === M360_ROUTE_OPS_CLASS_GUIDED);
        $results[] = p118c_pass('detail not ops-clickable: ' . $url, empty($row['ops_link_operational']));
    }
}

$boardUrls = [
    'erp-reception-jobcards.php',
    'erp-technical-board.php',
    'erp-qc-board.php',
    'erp-estimate-board.php',
];
foreach ($rows as $row) {
    if (in_array((string)($row['url'] ?? ''), $boardUrls, true)) {
        $results[] = p118c_pass('board ops-clickable: ' . $row['url'], !empty($row['ops_link_operational']));
        $results[] = p118c_pass('board operational class: ' . $row['url'], ($row['ops_class'] ?? '') === M360_ROUTE_OPS_CLASS_OPERATIONAL);
    }
}

foreach (M360_ROUTE_OPS_RUNTIME_NOT_READY_URLS as $url) {
    $sample = m360_route_ops_classify(['url' => $url, 'file_exists' => true, 'expected_method' => 'GET', 'is_staff_entry' => true]);
    $results[] = p118c_pass('runtime hold class: ' . $url, ($sample['ops_class'] ?? '') === M360_ROUTE_OPS_CLASS_RUNTIME_HOLD);
    $results[] = p118c_pass('runtime hold not clickable: ' . $url, empty($sample['ops_link_operational']));
}

$fileOkMislabel = true;
foreach ($rows as $row) {
    if (($row['ops_file_status_fa'] ?? '') === 'OK') {
        $fileOkMislabel = false;
        break;
    }
}
$results[] = p118c_pass('file status not labeled OK', $fileOkMislabel);

$customerCount = 0;
foreach ($rows as $row) {
    if (($row['ops_class'] ?? '') === M360_ROUTE_OPS_CLASS_CUSTOMER) {
        $customerCount++;
        $results[] = p118c_pass('customer not ops-clickable: ' . $row['url'], empty($row['ops_link_operational']));
    }
}
$results[] = p118c_pass('customer routes classified', $customerCount >= 6);

$pass = 0;
$fail = 0;
echo "# P11.8-C Route Map Operational Safety Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
