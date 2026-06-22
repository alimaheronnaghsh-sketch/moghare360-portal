<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 2 Submit Delivery Final Check (controlled write)
 */

require_once __DIR__ . '/includes/erp-operation-engine-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    operation_engine_render_error_page('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('operation_delivery_check', $_POST['erp_csrf_token'] ?? null);

$operationCaseId = operation_engine_post_int('operation_case_id');
$isReady = operation_engine_post_string('is_ready_for_delivery') === '1';
$customerContact = operation_engine_post_string('customer_contact_required') !== '0';
$paymentPreview = operation_engine_post_string('payment_preview_required') !== '0';
$qcPassedRequired = operation_engine_post_string('qc_passed_required') !== '0';
$finalNote = operation_engine_post_string('final_note');
$deliveryOutcome = strtoupper(operation_engine_post_string('delivery_outcome'));

if ($operationCaseId === null || $operationCaseId < 1) {
    operation_engine_render_error_page('خطای اعتبارسنجی', 'شناسه پرونده عملیاتی الزامی است.');
}

if ($deliveryOutcome === '') {
    $deliveryOutcome = $isReady ? 'READY_FOR_DELIVERY' : 'HOLD';
}

if (!in_array($deliveryOutcome, ['READY_FOR_DELIVERY', 'DELIVERED', 'HOLD'], true)) {
    operation_engine_render_error_page('خطای اعتبارسنجی', 'نتیجه تحویل نامعتبر است.');
}

$connection = false;

try {
    $connection = operation_engine_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    operation_engine_require_auth_and_guard($connection, 'operation.engine.delivery.check');

    $case = operation_engine_load_case($connection, $operationCaseId);

    if ($case === null) {
        throw new RuntimeException('پرونده عملیاتی یافت نشد.');
    }

    $latestQc = operation_engine_latest_qc_decision($connection, $operationCaseId);

    if ($isReady && ($latestQc === null || ($latestQc['decision_status'] ?? '') !== 'PASSED')) {
        operation_engine_render_error_page(
            'خطای اعتبارسنجی',
            'برای آماده‌سازی تحویل، آخرین تصمیم QC باید PASSED باشد.'
        );
    }

    $checkedBy = operation_engine_safe_current_user();
    $newStage = $case['current_stage'] ?? 'READY_FOR_DELIVERY';
    $newStatus = $case['current_status'] ?? 'OPEN';

    if ($isReady) {
        if ($deliveryOutcome === 'DELIVERED') {
            $newStage = 'DELIVERED';
            $newStatus = 'DELIVERED';
        } else {
            $newStage = 'READY_FOR_DELIVERY';
            $newStatus = 'READY_FOR_DELIVERY';
        }
    } else {
        $newStatus = 'DELIVERY_HOLD';
    }

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('ثبت بررسی تحویل انجام نشد.');
    }

    $deliveryOk = operation_engine_execute(
        $connection,
        'INSERT INTO dbo.erp_operation_delivery_checks (
            operation_case_id, is_ready_for_delivery, customer_contact_required,
            payment_preview_required, qc_passed_required, final_note,
            checked_by, source_ip, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $operationCaseId,
            $isReady ? 1 : 0,
            $customerContact ? 1 : 0,
            $paymentPreview ? 1 : 0,
            $qcPassedRequired ? 1 : 0,
            $finalNote !== '' ? $finalNote : null,
            $checkedBy,
            operation_engine_client_ip(),
            operation_engine_user_agent(),
        ]
    );

    if ($deliveryOk === false) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت بررسی تحویل انجام نشد.');
    }

    if ($isReady) {
        $caseUpdateOk = operation_engine_execute(
            $connection,
            'UPDATE dbo.erp_operation_cases SET current_stage = ?, current_status = ?,
             updated_at = SYSUTCDATETIME(), updated_by = ? WHERE operation_case_id = ?',
            [$newStage, $newStatus, $checkedBy, $operationCaseId]
        );

        if ($caseUpdateOk === false) {
            @odbc_rollback($connection);
            throw new RuntimeException('به‌روزرسانی مرحله پرونده انجام نشد.');
        }
    }

    operation_engine_insert_history(
        $connection,
        'erp_operation_delivery_checks',
        $operationCaseId,
        'DELIVERY_CHECK',
        'بررسی تحویل — آماده: ' . ($isReady ? 'بله' : 'خیر') . ' — نتیجه: ' . $deliveryOutcome,
        null,
        json_encode(['stage' => $newStage, 'status' => $newStatus], JSON_UNESCAPED_UNICODE)
    );

    if (!@odbc_commit($connection)) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت بررسی تحویل انجام نشد.');
    }

    @odbc_autocommit($connection, true);
    operation_engine_redirect('erp-jobcard-operation-flow.php?operation_case_id=' . $operationCaseId . '&phase2=delivery_ok');
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }

    operation_engine_render_error_page('خطا در ثبت', 'ثبت بررسی تحویل انجام نشد.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}
