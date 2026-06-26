<?php
declare(strict_types=1);

/**
 * MOGHARE360 P2 — Reception JobCard workflow tests.
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p2w_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function p2w_read(string $path): string
{
    return is_file($path) ? (string)file_get_contents($path) : '';
}

function p2w_lint(string $rel): array
{
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($path)) {
        return p2w_pass('PHP lint: ' . $rel, false, 'missing');
    }
    $out = [];
    $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p2w_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$list = p2w_read($public . '/erp-reception-jobcards.php');
$detail = p2w_read($public . '/erp-reception-jobcard-detail.php');
$action = p2w_read($public . '/erp-reception-jobcard-action.php');
$helper = p2w_read($public . '/includes/m360-reception-jobcard-helper.php');
$workflow = p2w_read($public . '/includes/m360-jobcard-workflow-helper.php');

$results = [];
$results[] = p2w_pass('Dashboard exists', is_file($public . '/erp-reception-jobcards.php'));
$results[] = p2w_pass('Detail page exists', is_file($public . '/erp-reception-jobcard-detail.php'));
$results[] = p2w_pass('Action handler exists', is_file($public . '/erp-reception-jobcard-action.php'));
$results[] = p2w_pass('Reception jobcard helper exists', is_file($public . '/includes/m360-reception-jobcard-helper.php'));
$results[] = p2w_pass('Workflow helper exists', is_file($public . '/includes/m360-jobcard-workflow-helper.php'));
$results[] = p2w_pass('Workflow statuses defined', str_contains($workflow, 'M360_JC_STATUS_READY_FOR_TECHNICAL') && str_contains($workflow, 'm360_jobcard_workflow_statuses'));
$results[] = p2w_pass('P15 gate check in helper', str_contains($helper, 'm360_reception_jobcard_p15_gate_available') && str_contains($helper, 'm360_contract_can_continue_to_p2'));
$results[] = p2w_pass('Persian RTL dashboard', str_contains($list, 'lang="fa"') && str_contains($list, 'dir="rtl"'));
$results[] = p2w_pass('Persian RTL detail', str_contains($detail, 'lang="fa"') && str_contains($detail, 'dir="rtl"'));
$results[] = p2w_pass('No GET state change on list', !preg_match('/\$_GET[\s\S]*UPDATE\s+dbo/i', $list));
$results[] = p2w_pass('No GET state change on detail', !preg_match('/\$_GET[\s\S]*UPDATE\s+dbo/i', $detail));
$results[] = p2w_pass('Action POST only', str_contains($action, "!== 'POST'"));
$results[] = p2w_pass('No raw credentials', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $list . $detail . $action . $helper));
$results[] = p2w_pass('Contract gate message on detail', str_contains($detail, 'ادامه عملیات فنی تا زمان امضای قرارداد پذیرش مجاز نیست'));
$results[] = p2w_lint('erp-reception-jobcards.php');
$results[] = p2w_lint('erp-reception-jobcard-detail.php');
$results[] = p2w_lint('erp-reception-jobcard-action.php');
$results[] = p2w_lint('includes/m360-reception-jobcard-helper.php');
$results[] = p2w_lint('includes/m360-jobcard-workflow-helper.php');

$pass = 0;
$fail = 0;
echo "# MOGHARE360 P2 Reception JobCard Workflow Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'];
    if ($r['detail'] !== '') {
        echo ' — ' . $r['detail'];
    }
    echo "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
