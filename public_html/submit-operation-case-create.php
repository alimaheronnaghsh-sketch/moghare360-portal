<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 2 Submit Operation Case Create (controlled write)
 */

require_once __DIR__ . '/includes/erp-operation-engine-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    operation_engine_render_error_page('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('operation_case_create', $_POST['erp_csrf_token'] ?? null);

$jobcardId = operation_engine_post_int('jobcard_id');
$intakeId = operation_engine_post_int('intake_id');
$customerId = operation_engine_post_int('customer_id');
$vehicleBindingId = operation_engine_post_int('vehicle_binding_id');
$contractId = operation_engine_post_int('contract_id');
$priorityLevel = operation_engine_post_string('priority_level');
$receptionSummary = operation_engine_post_string('reception_summary');
$internalNotes = operation_engine_post_string('internal_notes');
$stepTitle = operation_engine_post_string('step_title');
$stepType = strtoupper(operation_engine_post_string('step_type'));

if ($priorityLevel === '') {
    $priorityLevel = 'NORMAL';
}

if ($stepType === '') {
    $stepType = 'INSPECTION';
}

if (!operation_engine_validate_step_type($stepType)) {
    operation_engine_render_error_page('خطای اعتبارسنجی', 'نوع مرحله سرویس نامعتبر است.');
}

$connection = false;

try {
    $connection = operation_engine_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    operation_engine_require_auth_and_guard($connection, 'operation.engine.case.create');

    if (!operation_engine_table_exists($connection, 'erp_operation_cases')) {
        throw new RuntimeException('جدول erp_operation_cases یافت نشد. ابتدا SQL فاز ۲ را اجرا کنید.');
    }

    $operationCode = operation_engine_generate_operation_code();
    $createdBy = operation_engine_safe_current_user();

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('ایجاد پرونده عملیاتی انجام نشد.');
    }

    $insertOk = operation_engine_execute(
        $connection,
        'INSERT INTO dbo.erp_operation_cases (
            jobcard_id, intake_id, customer_id, vehicle_binding_id, contract_id,
            operation_code, current_stage, current_status, priority_level,
            reception_summary, internal_notes, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $jobcardId,
            $intakeId,
            $customerId,
            $vehicleBindingId,
            $contractId,
            $operationCode,
            'RECEPTION',
            'OPEN',
            $priorityLevel,
            $receptionSummary !== '' ? $receptionSummary : null,
            $internalNotes !== '' ? $internalNotes : null,
            $createdBy,
        ]
    );

    if ($insertOk === false) {
        @odbc_rollback($connection);
        throw new RuntimeException('ایجاد پرونده عملیاتی انجام نشد.');
    }

    $operationCaseId = operation_engine_scope_identity($connection);

    if ($operationCaseId === null) {
        @odbc_rollback($connection);
        throw new RuntimeException('شناسه پرونده دریافت نشد.');
    }

    if ($stepTitle !== '' && operation_engine_table_exists($connection, 'erp_operation_service_steps')) {
        $stepOk = operation_engine_execute(
            $connection,
            'INSERT INTO dbo.erp_operation_service_steps (
                operation_case_id, step_type, step_title, step_status, progress_percent, created_by
            ) VALUES (?, ?, ?, ?, 0, ?)',
            [$operationCaseId, $stepType, $stepTitle, 'OPEN', $createdBy]
        );

        if ($stepOk === false) {
            @odbc_rollback($connection);
            throw new RuntimeException('ثبت مرحله سرویس اولیه انجام نشد.');
        }
    }

    operation_engine_insert_history(
        $connection,
        'erp_operation_cases',
        $operationCaseId,
        'CASE_CREATE',
        'ایجاد پرونده عملیاتی — کد: ' . $operationCode,
        null,
        json_encode(['operation_code' => $operationCode, 'stage' => 'RECEPTION'], JSON_UNESCAPED_UNICODE)
    );

    if (!@odbc_commit($connection)) {
        @odbc_rollback($connection);
        throw new RuntimeException('ایجاد پرونده عملیاتی انجام نشد.');
    }

    @odbc_autocommit($connection, true);
    operation_engine_redirect('erp-jobcard-operation-flow.php?operation_case_id=' . $operationCaseId . '&phase2=case_ok');
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }

    operation_engine_render_error_page('خطا در ثبت', 'ایجاد پرونده عملیاتی انجام نشد.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}
