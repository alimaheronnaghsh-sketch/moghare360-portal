<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 3 Rule Test Console (internal controlled)
 */

require_once __DIR__ . '/includes/erp-rule-engine.php';

$connection = false;
$errorMessage = '';
$results = [];
$ranCheck = false;

$defaults = [
    'operation_case_id' => '',
    'service_step_id' => '',
    'contract_id' => '',
    'customer_id' => '',
    'vehicle_binding_id' => '',
    'requested_amount' => '',
    'service_title' => '',
    'is_out_of_contract' => '0',
    'part_id' => '',
    'part_code' => '',
    'part_name' => '',
    'requested_qty' => '1',
    'run_inventory' => '0',
];

$form = $defaults;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    erp_csrf_require_valid('rule_test_console', $_POST['erp_csrf_token'] ?? null);

    foreach (array_keys($defaults) as $key) {
        $form[$key] = rule_engine_post_string($key);
    }

    $form['is_out_of_contract'] = rule_engine_post_bool('is_out_of_contract') ? '1' : '0';
    $form['run_inventory'] = rule_engine_post_bool('run_inventory') ? '1' : '0';

    try {
        $connection = rule_engine_db();

        if ($connection === false) {
            throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
        }

        rule_engine_require_auth_and_guard($connection, 'rule.engine.console.execute');

        if (!rule_engine_table_exists($connection, 'erp_rule_decisions')) {
            throw new RuntimeException('جدول erp_rule_decisions یافت نشد. ابتدا SQL فاز ۳ را اجرا کنید.');
        }

        $operationCaseId = rule_engine_post_int('operation_case_id');
        $serviceStepId = rule_engine_post_int('service_step_id');
        $contractId = rule_engine_post_int('contract_id');
        $customerId = rule_engine_post_int('customer_id');
        $vehicleBindingId = rule_engine_post_int('vehicle_binding_id');
        $partId = rule_engine_post_int('part_id');
        $requestedAmount = rule_engine_post_float('requested_amount') ?? 0.0;
        $requestedQty = rule_engine_post_float('requested_qty') ?? 1.0;
        $isOutOfContract = rule_engine_post_bool('is_out_of_contract');
        $serviceTitle = rule_engine_post_string('service_title');
        $partCode = rule_engine_post_string('part_code');
        $partName = rule_engine_post_string('part_name');
        $runInventory = rule_engine_post_bool('run_inventory');

        $operationCase = rule_engine_get_operation_case($connection, $operationCaseId);

        if ($contractId === null && $operationCase !== null && ($operationCase['contract_id'] ?? '') !== '') {
            $contractId = (int)$operationCase['contract_id'];
        }

        if ($customerId === null && $operationCase !== null && ($operationCase['customer_id'] ?? '') !== '') {
            $customerId = (int)$operationCase['customer_id'];
        }

        if ($vehicleBindingId === null && $operationCase !== null && ($operationCase['vehicle_binding_id'] ?? '') !== '') {
            $vehicleBindingId = (int)$operationCase['vehicle_binding_id'];
        }

        $context = [
            'operation_case_id' => $operationCaseId,
            'service_step_id' => $serviceStepId,
            'contract_id' => $contractId,
            'customer_id' => $customerId,
            'vehicle_binding_id' => $vehicleBindingId,
            'part_id' => $partId,
        ];

        if (!@odbc_autocommit($connection, false)) {
            throw new RuntimeException('اجرای Rule Check انجام نشد.');
        }

        $contractDecision = rule_engine_check_contract_authorization($connection, $contractId, $requestedAmount);
        $contractDecisionId = rule_engine_create_decision($connection, $contractDecision, $context);
        rule_engine_create_approval_request_if_needed($connection, $contractDecisionId, $contractDecision, $context);

        $results[] = [
            'title' => 'بررسی مجوز قرارداد',
            'payload' => $contractDecision,
            'decision_id' => $contractDecisionId,
        ];

        $serviceDecision = rule_engine_check_service_requires_approval($connection, $isOutOfContract, $requestedAmount, $serviceTitle);
        $serviceDecisionId = rule_engine_create_decision($connection, $serviceDecision, $context);
        rule_engine_create_approval_request_if_needed($connection, $serviceDecisionId, $serviceDecision, $context);

        $results[] = [
            'title' => 'بررسی سرویس خارج از قرارداد',
            'payload' => $serviceDecision,
            'decision_id' => $serviceDecisionId,
        ];

        if ($runInventory || $partId !== null || $partCode !== '') {
            $inventoryDecision = rule_engine_check_inventory_decision($connection, $partId, $partCode, $partName, $requestedQty);
            $inventoryDecisionId = rule_engine_create_decision($connection, $inventoryDecision, $context);
            $inventoryRequestId = rule_engine_create_inventory_request_if_needed($connection, $inventoryDecisionId, $inventoryDecision, $context);

            $results[] = [
                'title' => 'بررسی موجودی قطعه',
                'payload' => $inventoryDecision,
                'decision_id' => $inventoryDecisionId,
                'inventory_request_id' => $inventoryRequestId,
            ];
        }

        $blockRule = rule_engine_get_rule_by_code($connection, 'OPERATION_BLOCK_WITHOUT_RULE_CHECK');

        rule_engine_insert_history(
            $connection,
            'erp_rule_definitions',
            isset($blockRule['rule_id']) ? (int)$blockRule['rule_id'] : null,
            'RULE_CHECK_COMPLETE',
            'اجرای Rule Check از کنسول داخلی — ' . count($results) . ' تصمیم',
            null,
            json_encode(['operation_case_id' => $operationCaseId], JSON_UNESCAPED_UNICODE)
        );

        if (!@odbc_commit($connection)) {
            @odbc_rollback($connection);
            throw new RuntimeException('اجرای Rule Check انجام نشد.');
        }

        @odbc_autocommit($connection, true);
        $ranCheck = true;
    } catch (Throwable) {
        if ($connection !== false) {
            @odbc_rollback($connection);
            @odbc_autocommit($connection, true);
        }

        $errorMessage = 'اجرای Rule Check با خطا مواجه شد.';
    } finally {
        if ($connection !== false) {
            @odbc_close($connection);
        }
    }
}

rule_engine_render_head('کنسول تست قوانین', false);

echo '<div class="p3re-hero">';
echo '<h1>کنسول تست قوانین</h1>';
echo '<p>صفحه داخلی پرسنل — اجرای Rule Check بدون ورود مشتری</p>';
echo '</div>';

echo '<p class="p3re-internal-note">هیچ عملیات اضافه‌ای نباید بدون Rule Check جلو برود. این کنسول برای تست و ثبت تصمیم طراحی شده است.</p>';

echo '<div class="p1cc-card"><h2 class="p3re-section-title">فرم Rule Check</h2>';
echo '<form method="post">';
echo erp_csrf_input('rule_test_console');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">Operation Case ID</label><input class="p1cc-input p1cc-input-ltr" type="number" name="operation_case_id" value="' . rule_engine_h($form['operation_case_id']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">Service Step ID</label><input class="p1cc-input p1cc-input-ltr" type="number" name="service_step_id" value="' . rule_engine_h($form['service_step_id']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">Contract ID</label><input class="p1cc-input p1cc-input-ltr" type="number" name="contract_id" value="' . rule_engine_h($form['contract_id']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">Customer ID</label><input class="p1cc-input p1cc-input-ltr" type="number" name="customer_id" value="' . rule_engine_h($form['customer_id']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">Vehicle Binding ID</label><input class="p1cc-input p1cc-input-ltr" type="number" name="vehicle_binding_id" value="' . rule_engine_h($form['vehicle_binding_id']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">مبلغ درخواستی</label><input class="p1cc-input p1cc-input-ltr" type="number" step="0.01" name="requested_amount" value="' . rule_engine_h($form['requested_amount']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">عنوان سرویس</label><input class="p1cc-input" type="text" name="service_title" value="' . rule_engine_h($form['service_title']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">خارج از قرارداد</label><select class="p1cc-select" name="is_out_of_contract"><option value="0"' . ($form['is_out_of_contract'] === '0' ? ' selected' : '') . '>خیر</option><option value="1"' . ($form['is_out_of_contract'] === '1' ? ' selected' : '') . '>بله</option></select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">Part ID</label><input class="p1cc-input p1cc-input-ltr" type="number" name="part_id" value="' . rule_engine_h($form['part_id']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کد قطعه</label><input class="p1cc-input p1cc-input-ltr" type="text" name="part_code" value="' . rule_engine_h($form['part_code']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نام قطعه</label><input class="p1cc-input" type="text" name="part_name" value="' . rule_engine_h($form['part_name']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تعداد درخواست</label><input class="p1cc-input p1cc-input-ltr" type="number" step="0.01" name="requested_qty" value="' . rule_engine_h($form['requested_qty']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">بررسی موجودی</label><select class="p1cc-select" name="run_inventory"><option value="0"' . ($form['run_inventory'] === '0' ? ' selected' : '') . '>خیر</option><option value="1"' . ($form['run_inventory'] === '1' ? ' selected' : '') . '>بله</option></select></div>';
echo '</div>';
echo '<div class="p1cc-btn-row"><button class="p1cc-btn p1cc-btn-primary" type="submit">اجرای Rule Check</button></div>';
echo '</form></div>';

if ($errorMessage !== '') {
    echo '<div class="p1cc-card p1cc-error"><p>' . rule_engine_h($errorMessage) . '</p></div>';
}

if ($ranCheck && $results !== []) {
    echo '<div class="p1cc-card"><h2 class="p3re-section-title">نتایج</h2>';

    foreach ($results as $result) {
        $payload = $result['payload'];
        $status = (string)($payload['decision_status'] ?? '');
        $boxClass = match ($status) {
            'ALLOWED', 'INVENTORY_AVAILABLE' => 'allowed',
            'APPROVAL_REQUIRED', 'PURCHASE_REQUIRED', 'BLOCKED' => 'blocked',
            default => 'review',
        };

        echo '<div class="p3re-result-box ' . $boxClass . '">';
        echo '<h3>' . rule_engine_h($result['title']) . '</h3>';
        echo '<p><strong>' . rule_engine_h(rule_engine_status_label_fa($status)) . '</strong> — ';
        echo rule_engine_h((string)($payload['decision_reason'] ?? '')) . '</p>';
        echo '<p class="p1cc-hint">اقدام بعدی: <span class="m360-ltr">' . rule_engine_h((string)($payload['next_action'] ?? '')) . '</span>';
        if (!empty($result['decision_id'])) {
            echo ' · Decision ID: <span class="m360-num">' . rule_engine_h((string)$result['decision_id']) . '</span>';
        }
        echo '</p></div>';
    }

    echo '<p><a class="p3re-link" href="erp-rule-decision-board.php">مشاهده در تابلوی تصمیم‌ها</a></p>';
    echo '</div>';
}

rule_engine_render_foot();
