<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 3 Submit Service Approval Request (controlled write)
 */

require_once __DIR__ . '/includes/erp-rule-engine.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    rule_engine_render_error_page('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('rule_approval_decide', $_POST['erp_csrf_token'] ?? null);

$approvalRequestId = rule_engine_post_int('approval_request_id');
$approvalStatus = strtoupper(rule_engine_post_string('approval_status'));
$internalNote = rule_engine_post_string('internal_note');

if ($approvalRequestId === null || $approvalRequestId < 1) {
    rule_engine_render_error_page('خطای اعتبارسنجی', 'شناسه درخواست تأیید الزامی است.');
}

if (!in_array($approvalStatus, ['APPROVED', 'REJECTED', 'CANCELLED'], true)) {
    rule_engine_render_error_page('خطای اعتبارسنجی', 'وضعیت تأیید نامعتبر است.');
}

$connection = false;

try {
    $connection = rule_engine_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    rule_engine_require_auth_and_guard($connection, 'rule.engine.approval.decide');

    $existing = rule_engine_fetch_rows(
        $connection,
        'SELECT TOP 1 approval_request_id, approval_status, operation_case_id, decision_id
         FROM dbo.erp_service_approval_requests WHERE approval_request_id = ?',
        [$approvalRequestId]
    );

    if ($existing === []) {
        throw new RuntimeException('درخواست تأیید یافت نشد.');
    }

    $row = $existing[0];
    $oldStatus = $row['approval_status'] ?? '';
    $operationCaseId = ($row['operation_case_id'] ?? '') !== '' ? (int)$row['operation_case_id'] : null;
    $decidedBy = rule_engine_safe_current_user();

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('ثبت تصمیم تأیید انجام نشد.');
    }

    $updateOk = rule_engine_execute(
        $connection,
        'UPDATE dbo.erp_service_approval_requests SET
            approval_status = ?,
            internal_note = ?,
            controlled_decision_by = ?,
            controlled_decision_at = SYSUTCDATETIME()
         WHERE approval_request_id = ?',
        [
            $approvalStatus,
            $internalNote !== '' ? $internalNote : null,
            $decidedBy,
            $approvalRequestId,
        ]
    );

    if ($updateOk === false) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت تصمیم تأیید انجام نشد.');
    }

    rule_engine_insert_history(
        $connection,
        'erp_service_approval_requests',
        $approvalRequestId,
        'APPROVAL_DECISION',
        'تصمیم داخلی: ' . $approvalStatus,
        json_encode(['status' => $oldStatus], JSON_UNESCAPED_UNICODE),
        json_encode(['status' => $approvalStatus, 'note' => $internalNote], JSON_UNESCAPED_UNICODE)
    );

    if ($approvalStatus === 'APPROVED' && $operationCaseId !== null && rule_engine_table_exists($connection, 'erp_operation_cases')) {
        $case = rule_engine_get_operation_case($connection, $operationCaseId);

        if ($case !== null) {
            $stage = strtoupper($case['current_stage'] ?? '');
            $status = strtoupper($case['current_status'] ?? '');

            if ($stage === 'WAITING_APPROVAL' || str_contains($status, 'APPROVAL')) {
                rule_engine_execute(
                    $connection,
                    "UPDATE dbo.erp_operation_cases SET current_status = 'APPROVAL_GRANTED',
                     updated_at = SYSUTCDATETIME(), updated_by = ? WHERE operation_case_id = ?",
                    [$decidedBy, $operationCaseId]
                );

                rule_engine_insert_history(
                    $connection,
                    'erp_operation_cases',
                    $operationCaseId,
                    'APPROVAL_GRANTED',
                    'تأیید داخلی — اجازه ادامه عملیات در تاریخچه ثبت شد (بدون تغییر سنگین stage)',
                    null,
                    json_encode(['approval_request_id' => $approvalRequestId], JSON_UNESCAPED_UNICODE)
                );
            }
        }
    }

    if (!@odbc_commit($connection)) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت تصمیم تأیید انجام نشد.');
    }

    @odbc_autocommit($connection, true);
    rule_engine_safe_redirect('erp-service-approval-request.php?phase3=approval_ok');
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }

    rule_engine_render_error_page('خطا در ثبت', 'ثبت تصمیم تأیید انجام نشد.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}
