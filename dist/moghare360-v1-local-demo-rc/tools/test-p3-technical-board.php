<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p3b_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p3b_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }
function p3b_lint(string $rel): array {
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($path)) return p3b_pass('PHP lint: ' . $rel, false, 'missing');
    $out = []; $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p3b_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$board = p3b_read($public . '/erp-technical-board.php');
$detail = p3b_read($public . '/erp-technical-jobcard-detail.php');
$action = p3b_read($public . '/erp-technical-jobcard-action.php');
$helper = p3b_read($public . '/includes/m360-technical-operation-helper.php');

$results = [];
$results[] = p3b_pass('Technical board exists', is_file($public . '/erp-technical-board.php'));
$results[] = p3b_pass('Detail page exists', is_file($public . '/erp-technical-jobcard-detail.php'));
$results[] = p3b_pass('Action handler exists', is_file($public . '/erp-technical-jobcard-action.php'));
$results[] = p3b_pass('Technical helper exists', is_file($public . '/includes/m360-technical-operation-helper.php'));
$results[] = p3b_pass('Technician workflow helper exists', is_file($public . '/includes/m360-technician-workflow-helper.php'));
$results[] = p3b_pass('Persian RTL board', str_contains($board, 'lang="fa"') && str_contains($board, 'dir="rtl"'));
$results[] = p3b_pass('Persian RTL detail', str_contains($detail, 'lang="fa"') && str_contains($detail, 'dir="rtl"'));
$results[] = p3b_pass('Action POST only', str_contains($action, "!== 'POST'"));
$results[] = p3b_pass('No GET state change board', !preg_match('/\$_GET[\s\S]*UPDATE\s+dbo/i', $board));
$results[] = p3b_pass('No GET state change detail', !preg_match('/\$_GET[\s\S]*UPDATE\s+dbo/i', $detail));
$results[] = p3b_pass('CSRF on action', str_contains($action, 'erp_csrf_require_valid'));
$results[] = p3b_pass('No raw credentials', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $board . $detail . $action . $helper));
$results[] = p3b_lint('erp-technical-board.php');
$results[] = p3b_lint('erp-technical-jobcard-detail.php');
$results[] = p3b_lint('erp-technical-jobcard-action.php');
$results[] = p3b_lint('includes/m360-technical-operation-helper.php');
$results[] = p3b_lint('includes/m360-technician-workflow-helper.php');

$pass = 0; $fail = 0;
echo "# MOGHARE360 P3 Technical Board Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
