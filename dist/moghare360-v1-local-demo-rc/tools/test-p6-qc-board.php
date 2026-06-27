<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . '/public_html';

function p6b_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p6b_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }
function p6b_lint(string $rel): array {
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $out = []; $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p6b_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$board = p6b_read($public . '/erp-qc-board.php');
$detail = p6b_read($public . '/erp-qc-detail.php');
$action = p6b_read($public . '/erp-qc-action.php');

$results = [];
$results[] = p6b_pass('Board exists', is_file($public . '/erp-qc-board.php'));
$results[] = p6b_pass('Detail exists', is_file($public . '/erp-qc-detail.php'));
$results[] = p6b_pass('Action exists', is_file($public . '/erp-qc-action.php'));
$results[] = p6b_pass('QC helper', is_file($public . '/includes/m360-qc-helper.php'));
$results[] = p6b_pass('Final inspection helper', is_file($public . '/includes/m360-final-inspection-helper.php'));
$results[] = p6b_pass('Delivery readiness helper', is_file($public . '/includes/m360-delivery-readiness-helper.php'));
$results[] = p6b_pass('CSS exists', is_file($public . '/assets/css/m360-qc.css'));
$results[] = p6b_pass('RTL', str_contains($board, 'dir="rtl"'));
$results[] = p6b_pass('Persian title', str_contains($board, 'برد QC'));
$results[] = p6b_pass('POST only action', str_contains($action, "!== 'POST'"));
$results[] = p6b_pass('No GET mutation', !preg_match('/\$_GET[\s\S]*UPDATE\s+dbo/i', $detail . $board));
$results[] = p6b_pass('CSRF', str_contains($action, 'erp_csrf_require_valid'));
$results[] = p6b_pass('No credentials', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $board . $detail));
$results[] = p6b_lint('erp-qc-board.php');
$results[] = p6b_lint('erp-qc-detail.php');
$results[] = p6b_lint('erp-qc-action.php');
$results[] = p6b_lint('includes/m360-qc-helper.php');
$results[] = p6b_lint('includes/m360-final-inspection-helper.php');
$results[] = p6b_lint('includes/m360-delivery-readiness-helper.php');

$pass = 0; $fail = 0;
echo "# P6 QC Board Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
