<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . '/public_html';

function p5b_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p5b_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }
function p5b_lint(string $rel): array {
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $out = []; $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p5b_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$board = p5b_read($public . '/erp-work-execution-board.php');
$detail = p5b_read($public . '/erp-work-execution-detail.php');
$action = p5b_read($public . '/erp-work-execution-action.php');

$results = [];
$results[] = p5b_pass('Board exists', is_file($public . '/erp-work-execution-board.php'));
$results[] = p5b_pass('Detail exists', is_file($public . '/erp-work-execution-detail.php'));
$results[] = p5b_pass('Action exists', is_file($public . '/erp-work-execution-action.php'));
$results[] = p5b_pass('Work helper exists', is_file($public . '/includes/m360-work-execution-helper.php'));
$results[] = p5b_pass('Parts helper exists', is_file($public . '/includes/m360-parts-consumption-helper.php'));
$results[] = p5b_pass('Technical completion helper exists', is_file($public . '/includes/m360-technical-completion-helper.php'));
$results[] = p5b_pass('CSS exists', is_file($public . '/assets/css/m360-work-execution.css'));
$results[] = p5b_pass('RTL board', str_contains($board, 'dir="rtl"'));
$results[] = p5b_pass('Persian title', str_contains($board, 'برد اجرای کار'));
$results[] = p5b_pass('POST action only', str_contains($action, "!== 'POST'"));
$results[] = p5b_pass('No GET mutation', !preg_match('/\$_GET[\s\S]*UPDATE\s+dbo/i', $detail . $board));
$results[] = p5b_pass('CSRF', str_contains($action, 'erp_csrf_require_valid'));
$results[] = p5b_pass('No credentials', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $board . $detail . $action));
$results[] = p5b_lint('erp-work-execution-board.php');
$results[] = p5b_lint('erp-work-execution-detail.php');
$results[] = p5b_lint('erp-work-execution-action.php');
$results[] = p5b_lint('includes/m360-work-execution-helper.php');
$results[] = p5b_lint('includes/m360-parts-consumption-helper.php');
$results[] = p5b_lint('includes/m360-technical-completion-helper.php');

$pass = 0; $fail = 0;
echo "# P5 Work Execution Board Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
