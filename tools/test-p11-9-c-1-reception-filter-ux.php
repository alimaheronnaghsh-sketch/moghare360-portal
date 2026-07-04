<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$list = $root . '/public_html/erp-reception-online-requests.php';
$helper = $root . '/public_html/includes/m360-reception-helper.php';

function p119c1_filter_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$listSrc = is_file($list) ? (string)file_get_contents($list) : '';
$helperSrc = is_file($helper) ? (string)file_get_contents($helper) : '';

$labels = ['همه', 'جدید', 'در انتظار بررسی', 'در حال بررسی', 'پذیرفته‌شده', 'تبدیل به کارت کار', 'رد شده'];
foreach ($labels as $label) {
    $results[] = p119c1_filter_pass('filter label: ' . $label, str_contains($helperSrc, $label) || str_contains($listSrc, $label));
}

$results[] = p119c1_filter_pass('active filter meta visible', str_contains($listSrc, 'فیلتر فعال'));
$results[] = p119c1_filter_pass('active class on filter links', str_contains($listSrc, "class=\"<?= \$active ? 'active'"));
$results[] = p119c1_filter_pass('aria-current on active filter', str_contains($listSrc, 'aria-current="page"'));
$results[] = p119c1_filter_pass('status counts helper', str_contains($helperSrc, 'm360_reception_status_counts'));
$results[] = p119c1_filter_pass('list uses filter labels helper', str_contains($listSrc, 'm360_reception_list_filter_labels'));
$results[] = p119c1_filter_pass('count badge in filters', str_contains($listSrc, 'p1-req-count'));
$results[] = p119c1_filter_pass('empty state names filter', str_contains($listSrc, 'برای فیلتر'));
$results[] = p119c1_filter_pass('no new invented status codes', !str_contains($listSrc, 'WAITING_CUSTOMER') && !str_contains($helperSrc, 'WAITING_CUSTOMER'));

$pass = 0;
$fail = 0;
echo "# P11.9-C-1 Reception Filter UX Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
