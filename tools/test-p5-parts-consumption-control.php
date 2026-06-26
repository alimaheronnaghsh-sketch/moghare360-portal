<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$parts = file_get_contents($root . '/public_html/includes/m360-parts-consumption-helper.php') ?: '';
$work = file_get_contents($root . '/public_html/includes/m360-work-execution-helper.php') ?: '';

function p5p_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p5p_pass('consume_approved function', str_contains($parts, 'function m360_parts_consume_approved'));
$results[] = p5p_pass('PART item type filter', str_contains($parts, "item_type = N'PART'"));
$results[] = p5p_pass('Over qty blocked', str_contains($parts, 'بیش از مقدار تأیید'));
$results[] = p5p_pass('Insufficient stock code', str_contains($parts, 'INSUFFICIENT_STOCK'));
$results[] = p5p_pass('Idempotent already consumed', str_contains($parts, 'قبلاً مصرف شده'));
$results[] = p5p_pass('Uses erp_jobcard_part_usage', str_contains($parts, 'erp_jobcard_part_usage'));
$results[] = p5p_pass('No duplicate usage table create', !str_contains($parts, 'CREATE TABLE'));
$results[] = p5p_pass('Stock check optional', str_contains($parts, 'm360_parts_stock_available'));
$results[] = p5p_pass('WAITING_FOR_PARTS on block', str_contains($work, 'WAITING_FOR_PARTS'));
$results[] = p5p_pass('No full purchase module', !preg_match('/INSERT INTO dbo\.erp_purchase_orders/i', $parts . $work));
$results[] = p5p_pass('No inventory full module', !preg_match('/INSERT INTO dbo\.erp_inventory_parts/i', $parts));

$pass = 0; $fail = 0;
echo "# P5 Parts Consumption Control Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
