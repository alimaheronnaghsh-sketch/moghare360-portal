<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . '/public_html';

function p4b_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p4b_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }
function p4b_lint(string $rel): array {
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $out = []; $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p4b_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$board = p4b_read($public . '/erp-estimate-board.php');
$detail = p4b_read($public . '/erp-estimate-detail.php');
$action = p4b_read($public . '/erp-estimate-action.php');

$results = [];
$results[] = p4b_pass('Board exists', is_file($public . '/erp-estimate-board.php'));
$results[] = p4b_pass('Detail exists', is_file($public . '/erp-estimate-detail.php'));
$results[] = p4b_pass('Action exists', is_file($public . '/erp-estimate-action.php'));
$results[] = p4b_pass('Helpers exist', is_file($public . '/includes/m360-estimate-helper.php'));
$results[] = p4b_pass('RTL board', str_contains($board, 'dir="rtl"'));
$results[] = p4b_pass('POST action only', str_contains($action, "!== 'POST'"));
$results[] = p4b_pass('No GET mutation', !preg_match('/\$_GET[\s\S]*UPDATE\s+dbo/i', $detail));
$results[] = p4b_pass('CSRF', str_contains($action, 'erp_csrf_require_valid'));
$results[] = p4b_pass('No credentials', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $board . $detail));
$results[] = p4b_lint('erp-estimate-board.php');
$results[] = p4b_lint('erp-estimate-detail.php');
$results[] = p4b_lint('erp-estimate-action.php');
$results[] = p4b_lint('includes/m360-estimate-helper.php');

$pass = 0; $fail = 0;
echo "# P4 Estimate Board Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
