<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function rf_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$helperPath = $root . '/public_html/includes/m360-reception-workbench-helper.php';
$helperSrc = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';

$results[] = rf_pass('helper exists', is_file($helperPath));

require_once $helperPath;

// No 8-arg call pattern
$results[] = rf_pass(
    'no 8-arg m360_rw_build_gate call in helper',
    !preg_match('/m360_rw_build_gate\s*\(\s*\[\s*\]\s*,\s*\[\s*\]\s*,\s*\[\s*\]\s*,\s*\[\s*\]\s*,\s*\[\s*\]\s*,\s*\[\s*\]\s*,\s*\[\s*\]\s*,\s*\[\s*\]\s*\)/', $helperSrc)
);

try {
    $empty = m360_rw_build_empty_gate();
    $results[] = rf_pass('m360_rw_build_empty_gate() works', isset($empty['status']));
} catch (Throwable $e) {
    $results[] = rf_pass('m360_rw_build_empty_gate() works', false, $e->getMessage());
}

try {
    $file = m360_rw_build_intake_file(false, 0);
    $results[] = rf_pass('m360_rw_build_intake_file(false,0) no throw', is_array($file) && isset($file['gate']['status']));
} catch (Throwable $e) {
    $results[] = rf_pass('m360_rw_build_intake_file(false,0) no throw', false, $e->getMessage());
}

require_once $root . '/public_html/includes/m360-reception-helper.php';
$conn = customer_core_db();
if ($conn !== false) {
    foreach ([18, 20] as $id) {
        try {
            $f = m360_rw_build_intake_file($conn, $id);
            $results[] = rf_pass("intake runtime id={$id}", isset($f['gate']['status']), 'status=' . ($f['gate']['status'] ?? ''));
        } catch (Throwable $e) {
            $results[] = rf_pass("intake runtime id={$id}", false, $e->getMessage());
        }
    }
} else {
    $results[] = rf_pass('DB intake runtime 18/20', true, 'SKIP — no DB');
}

$pass = 0;
$fail = 0;
echo "# P11.9-C-2B-REWORK-FINAL Runtime Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
