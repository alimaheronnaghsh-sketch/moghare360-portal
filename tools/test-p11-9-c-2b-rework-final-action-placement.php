<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$detail = (string)file_get_contents($root . '/public_html/erp-reception-online-request-detail.php');
$intake = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');

function rf_act_pass(string $name, bool $ok): array
{
    return ['name' => $name, 'pass' => $ok];
}

$results = [];
$results[] = rf_act_pass('detail primary CTA: تکمیل پرونده پذیرش', str_contains($detail, 'تکمیل پرونده پذیرش'));
$results[] = rf_act_pass('detail guidance: Gate first', str_contains($detail, 'ابتدا پرونده پذیرش را تکمیل و وضعیت Gate را بررسی کنید'));
$results[] = rf_act_pass('detail: controlled section title', str_contains($detail, 'اقدامات پس از بررسی پرونده'));
$results[] = rf_act_pass('detail: no convert submit button', !preg_match('/action\s*=\s*"convert_to_jobcard"/', $detail));
$results[] = rf_act_pass('detail: no reject submit', !preg_match('/action\s*=\s*"reject"/', $detail));
$results[] = rf_act_pass('detail: no accept submit', !preg_match('/action\s*=\s*"accept"/', $detail));
$results[] = rf_act_pass('detail: links to intake for actions', str_contains($detail, 'erp-reception-intake-file.php'));
$results[] = rf_act_pass('intake: reject in temp', str_contains($intake, 'رد درخواست'));
$results[] = rf_act_pass('intake: convert only when gate allows', str_contains($intake, 'can_show_convert') || str_contains($intake, 'convert_to_jobcard'));
$results[] = rf_act_pass('intake: convert form behind gate var', preg_match('/\$canAct\s*&&\s*!\s*empty\s*\(\s*\$gate\s*\[\s*[\'"]can_show_convert[\'"]\s*\]\s*\)/', $intake) === 1);

$pass = 0;
$fail = 0;
echo "# P11.9-C-2B-REWORK-FINAL Action Placement Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
