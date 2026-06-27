<?php
declare(strict_types=1);

/**
 * MOGHARE360 P2 — JobCard action control tests.
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p2a_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function p2a_read(string $path): string
{
    return is_file($path) ? (string)file_get_contents($path) : '';
}

$action = p2a_read($public . '/erp-reception-jobcard-action.php');
$helper = p2a_read($public . '/includes/m360-reception-jobcard-helper.php');
$workflow = p2a_read($public . '/includes/m360-jobcard-workflow-helper.php');
$detail = p2a_read($public . '/erp-reception-jobcard-detail.php');

$results = [];
$results[] = p2a_pass('Action handler POST only', str_contains($action, "!== 'POST'") && str_contains($action, 'erp-reception-jobcards.php'));
$results[] = p2a_pass('CSRF required on action', str_contains($action, 'erp_csrf_require_valid'));
$results[] = p2a_pass('CSRF on detail forms', str_contains($detail, 'erp_csrf_input'));
$results[] = p2a_pass('Invalid transition validation', str_contains($workflow, 'm360_jobcard_workflow_validate_action'));
$results[] = p2a_pass('Idempotent arrived path', str_contains($workflow, 'خودرو قبلاً رسیده است'));
$results[] = p2a_pass('Idempotent check-in path', str_contains($workflow, 'ورود قبلاً ثبت شده است'));
$results[] = p2a_pass('Safe redirect to detail', str_contains($action, 'erp-reception-jobcard-detail.php'));
$results[] = p2a_pass('Persian error messages', str_contains($helper, 'قرارداد پذیرش هنوز توسط مشتری امضا نشده است'));
$results[] = p2a_pass('All P2 actions mapped', str_contains($workflow, 'mark_arrived') && str_contains($workflow, 'ready_for_technical') && str_contains($workflow, 'manager_override_contract_gate'));
$results[] = p2a_pass('jobcard_id required', str_contains($action, '$jobcardId < 1'));

require_once $public . '/includes/m360-jobcard-workflow-helper.php';
$bad = m360_jobcard_workflow_validate_action('RECEIVED', 'ready_for_technical');
$results[] = p2a_pass('Runtime invalid transition rejected', $bad['ok'] === false);

$pass = 0;
$fail = 0;
echo "# MOGHARE360 P2 JobCard Action Control Test\n\n";
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
