<?php
declare(strict_types=1);

require_once __DIR__ . '/../public_html/includes/m360-intake-prepayment-gate-helper.php';

function m360_prepay_test_result(string $name, bool $pass, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $pass, 'detail' => $detail];
}

function m360_prepay_gate(array $gate, bool $signed = true): array
{
    return m360_intake_prepayment_gate_evaluate(
        ['reception_intake' => ['prepayment_gate' => $gate]],
        $signed,
        ['payment_backend_available' => true, 'payment_backend_source' => 'test']
    );
}

$results = [];

$contractMissing = m360_prepay_gate([], false);
$results[] = m360_prepay_test_result(
    'Contract not signed blocks Step 8',
    empty($contractMissing['allow_handoff'])
        && $contractMissing['gate_status'] === M360_INTAKE_PREPAYMENT_GATE_CONTRACT_NOT_SIGNED
);

$noPrepay = m360_prepay_gate([]);
$results[] = m360_prepay_test_result(
    'Signed no prepayment and no owner approval blocks Step 8',
    empty($noPrepay['allow_handoff'])
        && $noPrepay['gate_status'] === M360_INTAKE_PREPAYMENT_GATE_PREPAYMENT_REQUIRED
);

$confirmed = m360_prepay_gate([
    'required_amount' => '1000000',
    'paid_amount' => '1000000',
    'payment_status' => 'PREPAYMENT_CONFIRMED',
]);
$results[] = m360_prepay_test_result(
    'Signed prepayment confirmed allows Step 8',
    !empty($confirmed['allow_handoff'])
        && $confirmed['gate_status'] === M360_INTAKE_PREPAYMENT_GATE_PREPAYMENT_CONFIRMED
);

$ownerApproved = m360_prepay_gate([
    'owner_decision_status' => M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVED,
    'requested_by_user_id' => '200',
    'approved_by_user_id' => '10001',
]);
$results[] = m360_prepay_test_result(
    'Signed owner approved start without prepayment allows Step 8',
    !empty($ownerApproved['allow_handoff'])
        && $ownerApproved['gate_status'] === M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVED
);

$ownerRejected = m360_prepay_gate([
    'owner_decision_status' => M360_INTAKE_PREPAYMENT_GATE_OWNER_REJECTED,
    'requested_by_user_id' => '200',
    'approved_by_user_id' => '10001',
]);
$results[] = m360_prepay_test_result(
    'Signed owner rejected start without prepayment blocks Step 8',
    empty($ownerRejected['allow_handoff'])
        && $ownerRejected['gate_status'] === M360_INTAKE_PREPAYMENT_GATE_OWNER_REJECTED
);

$registered = m360_prepay_gate(['payment_status' => 'RECEIVED', 'paid_amount' => '500000']);
$results[] = m360_prepay_test_result(
    'Payment registered but not confirmed blocks Step 8',
    empty($registered['allow_handoff'])
        && $registered['gate_status'] === M360_INTAKE_PREPAYMENT_GATE_PENDING_CONFIRMATION
);

$helper = file_get_contents(__DIR__ . '/../public_html/includes/m360-reception-workbench-helper.php') ?: '';
$results[] = m360_prepay_test_result(
    'Owner approval is audited',
    str_contains($helper, 'PREPAYMENT_GATE_START_WITHOUT_PREPAYMENT_APPROVED')
        && str_contains($helper, 'm360_online_req_write_history')
);
$results[] = m360_prepay_test_result(
    'Reception cannot approve own gate',
    str_contains($helper, '$requestedBy > 0 && $requestedBy === $actorUserId')
        && str_contains($helper, 'درخواست‌کننده نمی‌تواند')
);
$results[] = m360_prepay_test_result(
    'Unauthorized actors cannot approve',
    str_contains($helper, "empty(\$actor['can_approve'])")
        && str_contains($helper, 'فقط مالک/سیستم ادمین')
);

$failed = 0;
foreach ($results as $result) {
    echo ($result['pass'] ? 'PASS' : 'FAIL') . ' - ' . $result['name'];
    if ($result['detail'] !== '') {
        echo ' - ' . $result['detail'];
    }
    echo PHP_EOL;
    if (!$result['pass']) {
        $failed++;
    }
}

exit($failed === 0 ? 0 : 1);
