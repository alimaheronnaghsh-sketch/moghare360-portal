<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$wb = $root . '/public_html/erp-reception-workbench.php';
$helper = $root . '/public_html/includes/m360-reception-workbench-helper.php';

function c2b_wb_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$results[] = c2b_wb_pass('erp-reception-workbench.php exists', is_file($wb));

$content = is_file($wb) ? (string)file_get_contents($wb) : '';
$helperContent = is_file($helper) ? (string)file_get_contents($helper) : '';
$combined = $content . $helperContent;
$required = [
    'میز کار پذیرش',
    'پذیرش خودرو حضوری',
    'درخواست‌های آنلاین مشتری',
    'تکمیل پرونده پذیرش',
    'پرونده‌های ناقص',
    'آماده تبدیل به کارت کار',
    'پروفایل خودرو',
    'قراردادهای پذیرش',
    'عکس‌ها و مستندات پذیرش',
    'گزارش روزانه پذیرش',
];
foreach ($required as $phrase) {
    $results[] = c2b_wb_pass('workbench contains: ' . $phrase, str_contains($combined, $phrase));
}

$results[] = c2b_wb_pass('Persian RTL html', str_contains($content, 'lang="fa"') && str_contains($content, 'dir="rtl"'));
$results[] = c2b_wb_pass('landing shows پذیرش and پروفایل پرسنلی', str_contains($content, 'm360-rw-landing-grid') && str_contains($content, 'پروفایل پرسنلی'));
$results[] = c2b_wb_pass('uses luxury UI css', str_contains($content, 'moghare360-v1-luxury-ui.css'));
$results[] = c2b_wb_pass('no raw ERP security validation failed', !str_contains($content, 'ERP security validation failed'));
$results[] = c2b_wb_pass('helper exists', is_file($helper));

require_once $helper;
$decoded = m360_rw_decode_payload('{"vin":"TEST","fuel_level":"half","odometer_km":"120000"}');
$results[] = c2b_wb_pass('payload decoder valid json', $decoded['valid'] === true && isset($decoded['items']['vin']));
$bad = m360_rw_decode_payload('{invalid');
$results[] = c2b_wb_pass('payload decoder invalid json warning', $bad['valid'] === false && $bad['raw_warning'] !== '');
$rows = m360_rw_payload_display_rows(['vin' => 'ABC', 'fuel_level' => 'نیم']);
$results[] = c2b_wb_pass('payload persian labels', count($rows) >= 2);

$pass = 0;
$fail = 0;
echo "# P11.9-C-2B Reception Workbench Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
