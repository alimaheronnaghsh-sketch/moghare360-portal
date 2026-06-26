<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p3w_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p3w_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$wf = p3w_read($public . '/includes/m360-technician-workflow-helper.php');
$helper = p3w_read($public . '/includes/m360-technical-operation-helper.php');

$results = [];
$results[] = p3w_pass('Technical statuses defined', str_contains($wf, 'M360_TECH_STATUS_TECHNICAL_QUEUE') && str_contains($wf, 'M360_TECH_STATUS_WAITING_APPROVAL'));
$results[] = p3w_pass('Transition validation', str_contains($wf, 'm360_technician_workflow_validate_action'));
$results[] = p3w_pass('Idempotent queue', str_contains($wf, 'پرونده در صف فنی است'));
$results[] = p3w_pass('Diagnosis requires allowed state', str_contains($wf, 'شروع عیب‌یابی در این وضعیت مجاز نیست'));
$results[] = p3w_pass('Complete diagnosis transition', str_contains($wf, 'complete_diagnosis'));

require_once $public . '/includes/m360-technician-workflow-helper.php';
$bad = m360_technician_workflow_validate_action('READY_FOR_TECHNICAL', 'complete_diagnosis');
$results[] = p3w_pass('Runtime invalid transition rejected', $bad['ok'] === false);

$diag = m360_technician_workflow_validate_action('DIAGNOSIS_STARTED', 'complete_diagnosis');
$results[] = p3w_pass('Runtime complete diagnosis allowed from started', $diag['ok'] === true);

require_once $public . '/includes/m360-technical-operation-helper.php';
$results[] = p3w_pass('Complete diagnosis requires summary in apply', str_contains($helper, "action === 'complete_diagnosis'") && str_contains($helper, 'خلاصه عیب‌یابی الزامی است'));

$pass = 0; $fail = 0;
echo "# MOGHARE360 P3 Technician Workflow Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
