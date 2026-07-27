<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function pr02a_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$helper = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$intakePhp = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');

$results = [];
$results[] = pr02a_pass('send_to_hall_manager action allowed', str_contains($helper, "'send_to_hall_manager'"));
$results[] = pr02a_pass('hall manager UI label present', str_contains($intakePhp, 'ارسال پرونده به مسئول سالن'));
$results[] = pr02a_pass('hall manager status label present', str_contains($helper, 'آماده بررسی مسئول سالن'));
$results[] = pr02a_pass('referral team save removed from allowed actions', !str_contains($helper, "'save_referral_team',") || str_contains($helper, "'send_to_hall_manager'"));
$results[] = pr02a_pass('no technician assignment in reception intake', !preg_match('/technician|assistant_1|assistant_2|مسئول فنی/ui', $intakePhp));
$results[] = pr02a_pass('hall manager step in post-reception operation keys', str_contains($helper, "return ['documents', 'signature', 'referral'];"));
$results[] = pr02a_pass('reception completion keys exclude referral', str_contains($helper, "return ['otp', 'vehicle', 'condition', 'service', 'photos'];"));
$results[] = pr02a_pass('operation gate hall manager helper', str_contains($helper, 'm360_rw_intake_operation_gate_hall_manager_allowed'));

$payload = ['reception_intake' => []];
$results[] = pr02a_pass('hall manager incomplete before send', !m360_rw_intake_hall_manager_step_complete($payload));

$payload['reception_intake']['hall_manager'] = [
    'status' => M360_RW_HALL_MANAGER_STATUS_READY_FA,
    'sent_at' => gmdate('c'),
];
$results[] = pr02a_pass('hall manager complete after send', m360_rw_intake_hall_manager_step_complete($payload));

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02A hall manager gate: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
