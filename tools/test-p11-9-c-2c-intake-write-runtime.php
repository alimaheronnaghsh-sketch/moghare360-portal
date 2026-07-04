<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';

function c2c_rt_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$files = [
    'public_html/includes/m360-reception-workbench-helper.php',
    'public_html/erp-reception-intake-file.php',
    'public_html/erp-reception-intake-save.php',
];
foreach ($files as $rel) {
    $path = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    $results[] = c2c_rt_pass('PHP lint: ' . basename($rel), $code === 0, implode(' ', $out));
}

require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

$intakeSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');
$saveSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-save.php');

$results[] = c2c_rt_pass('save endpoint POST only', str_contains($saveSrc, "!== 'POST'"));
$results[] = c2c_rt_pass('intake no write on GET', !str_contains($intakeSrc, 'm360_rw_intake_process_save'));
$results[] = c2c_rt_pass('intake forms POST to save', str_contains($intakeSrc, 'erp-reception-intake-save.php'));

try {
    $gate = m360_rw_build_empty_gate();
    $results[] = c2c_rt_pass('build_empty_gate no fatal', is_array($gate));
    $file = m360_rw_build_intake_file(false, 0);
    $results[] = c2c_rt_pass('build_intake_file false conn no fatal', $file['request'] === null);
} catch (Throwable $e) {
    $results[] = c2c_rt_pass('intake render helpers no fatal', false, $e->getMessage());
}

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C Intake Write Runtime Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
