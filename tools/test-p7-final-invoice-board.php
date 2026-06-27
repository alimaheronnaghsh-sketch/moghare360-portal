<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . '/public_html';

function p7b_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p7b_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }
function p7b_lint(string $rel): array {
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $out = []; $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p7b_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$board = p7b_read($public . '/erp-final-invoice-board.php');
$detail = p7b_read($public . '/erp-final-invoice-detail.php');
$action = p7b_read($public . '/erp-final-invoice-action.php');

$results = [];
$results[] = p7b_pass('Board exists', is_file($public . '/erp-final-invoice-board.php'));
$results[] = p7b_pass('Detail exists', is_file($public . '/erp-final-invoice-detail.php'));
$results[] = p7b_pass('Action exists', is_file($public . '/erp-final-invoice-action.php'));
$results[] = p7b_pass('Final invoice helper', is_file($public . '/includes/m360-final-invoice-helper.php'));
$results[] = p7b_pass('Settlement helper', is_file($public . '/includes/m360-settlement-helper.php'));
$results[] = p7b_pass('Customer delivery helper', is_file($public . '/includes/m360-customer-delivery-helper.php'));
$results[] = p7b_pass('Jobcard close helper', is_file($public . '/includes/m360-jobcard-close-helper.php'));
$results[] = p7b_pass('CSS exists', is_file($public . '/assets/css/m360-final-delivery.css'));
$results[] = p7b_pass('RTL', str_contains($board, 'dir="rtl"'));
$results[] = p7b_pass('Persian title', str_contains($board, 'برد فاکتور نهایی'));
$results[] = p7b_pass('POST only action', str_contains($action, "!== 'POST'"));
$results[] = p7b_pass('No GET mutation', !preg_match('/\$_GET[\s\S]*UPDATE\s+dbo/i', $detail . $board));
$results[] = p7b_pass('CSRF', str_contains($action, 'erp_csrf_require_valid'));
$results[] = p7b_pass('No credentials', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $board . $detail));
$results[] = p7b_lint('erp-final-invoice-board.php');
$results[] = p7b_lint('erp-final-invoice-detail.php');
$results[] = p7b_lint('erp-final-invoice-action.php');
$results[] = p7b_lint('includes/m360-final-invoice-helper.php');
$results[] = p7b_lint('includes/m360-settlement-helper.php');
$results[] = p7b_lint('includes/m360-customer-delivery-helper.php');
$results[] = p7b_lint('includes/m360-jobcard-close-helper.php');

$pass = 0; $fail = 0;
echo "# P7 Final Invoice Board Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
