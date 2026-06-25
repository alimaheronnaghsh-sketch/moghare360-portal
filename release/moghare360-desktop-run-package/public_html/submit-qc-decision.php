<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 2 Submit QC Decision (controlled write)
 */

require_once __DIR__ . '/includes/erp-operation-engine-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    operation_engine_render_error_page('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('operation_qc_decision', $_POST['erp_csrf_token'] ?? null);

$operationCaseId = operation_engine_post_int('operation_case_id');
$decisionStatus = strtoupper(operation_engine_post_string('decision_status'));
$decisionNote = operation_engine_post_string('decision_note');

if ($operationCaseId === null || $operationCaseId < 1) {
    operation_engine_render_error_page('خطای اعتبارسنجی', 'شناسه پرونده عملیاتی الزامی است.');
}

if (!operation_engine_validate_qc_decision($decisionStatus)) {
    operation_engine_render_error_page('خطای اعتبارسنجی', 'وضعیت تصمیم QC نامعتبر است.');
}

$connection = false;

try {
    $connection = operation_engine_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    operation_engine_require_auth_and_guard($connection, 'operation.engine.qc.decide');

    $case = operation_engine_load_case($connection, $operationCaseId);

    if ($case === null) {
        throw new RuntimeException('پرونده عملیاتی یافت نشد.');
    }

    $newStage = $case['current_stage'] ?? 'QC';
    $newStatus = $case['current_status'] ?? 'OPEN';
    $returnToStage = null;

    switch ($decisionStatus) {
        case 'PASSED':
            $newStage = 'READY_FOR_DELIVERY';
            $newStatus = 'QC_PASSED';
            break;
        case 'FAILED_RETURN_TO_SERVICE':
            $newStage = 'SERVICE';
            $newStatus = 'RETURNED_FROM_QC';
            $returnToStage = 'SERVICE';
            break;
        case 'FAILED_RETURN_TO_DIAGNOSIS':
            $newStage = 'DIAGNOSIS';
            $newStatus = 'RETURNED_FROM_QC';
            $returnToStage = 'DIAGNOSIS';
            break;
        case 'HOLD':
            $newStatus = 'QC_HOLD';
            break;
    }

    $decidedBy = operation_engine_safe_current_user();

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('ثبت تصمیم QC انجام نشد.');
    }

    $qcOk = operation_engine_execute(
        $connection,
        'INSERT INTO dbo.erp_operation_qc_decisions (
            operation_case_id, decision_status, decision_note, return_to_stage,
            decided_by, source_ip, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [
            $operationCaseId,
            $decisionStatus,
            $decisionNote !== '' ? $decisionNote : null,
            $returnToStage,
            $decidedBy,
            operation_engine_client_ip(),
            operation_engine_user_agent(),
        ]
    );

    if ($qcOk === false) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت تصمیم QC انجام نشد.');
    }

    $caseUpdateOk = operation_engine_execute(
        $connection,
        'UPDATE dbo.erp_operation_cases SET current_stage = ?, current_status = ?,
         updated_at = SYSUTCDATETIME(), updated_by = ? WHERE operation_case_id = ?',
        [$newStage, $newStatus, $decidedBy, $operationCaseId]
    );

    if ($caseUpdateOk === false) {
        @odbc_rollback($connection);
        throw new RuntimeException('به‌روزرسانی مرحله پرونده انجام نشد.');
    }

    operation_engine_insert_history(
        $connection,
        'erp_operation_qc_decisions',
        $operationCaseId,
        'QC_DECISION',
        'تصمیم QC: ' . $decisionStatus,
        json_encode(['stage' => $case['current_stage'] ?? '', 'status' => $case['current_status'] ?? ''], JSON_UNESCAPED_UNICODE),
        json_encode(['stage' => $newStage, 'status' => $newStatus], JSON_UNESCAPED_UNICODE)
    );

    if (!@odbc_commit($connection)) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت تصمیم QC انجام نشد.');
    }

    @odbc_autocommit($connection, true);
    operation_engine_redirect('erp-jobcard-operation-flow.php?operation_case_id=' . $operationCaseId . '&phase2=qc_ok');
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }

    operation_engine_render_error_page('خطا در ثبت', 'ثبت تصمیم QC انجام نشد.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}
