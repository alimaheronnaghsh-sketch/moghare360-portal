<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 2 Submit Service Status Update (controlled write)
 */

require_once __DIR__ . '/includes/erp-operation-engine-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    operation_engine_render_error_page('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('operation_service_update', $_POST['erp_csrf_token'] ?? null);

$operationCaseId = operation_engine_post_int('operation_case_id');
$serviceStepId = operation_engine_post_int('service_step_id');
$stepTitle = operation_engine_post_string('step_title');
$stepType = strtoupper(operation_engine_post_string('step_type'));
$stepDescription = operation_engine_post_string('step_description');
$assignedTechnician = operation_engine_post_string('assigned_technician_text');
$stepStatus = strtoupper(operation_engine_post_string('step_status'));
$progressRaw = operation_engine_post_string('progress_percent');

if ($operationCaseId === null || $operationCaseId < 1) {
    operation_engine_render_error_page('خطای اعتبارسنجی', 'شناسه پرونده عملیاتی الزامی است.');
}

if ($stepType === '') {
    $stepType = 'REPAIR';
}

$progress = 0;

if ($progressRaw !== '') {
    if (!ctype_digit($progressRaw)) {
        operation_engine_render_error_page('خطای اعتبارسنجی', 'درصد پیشرفت باید عدد صحیح بین ۰ تا ۱۰۰ باشد.');
    }

    $progress = (int)$progressRaw;

    if ($progress < 0 || $progress > 100) {
        operation_engine_render_error_page('خطای اعتبارسنجی', 'درصد پیشرفت باید بین ۰ تا ۱۰۰ باشد.');
    }
}

if ($stepStatus !== '' && !operation_engine_validate_step_status($stepStatus)) {
    operation_engine_render_error_page('خطای اعتبارسنجی', 'وضعیت مرحله نامعتبر است.');
}

if (!operation_engine_validate_step_type($stepType)) {
    operation_engine_render_error_page('خطای اعتبارسنجی', 'نوع مرحله نامعتبر است.');
}

$connection = false;

try {
    $connection = operation_engine_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    operation_engine_require_auth_and_guard($connection, 'operation.engine.service.update');

    $case = operation_engine_load_case($connection, $operationCaseId);

    if ($case === null) {
        throw new RuntimeException('پرونده عملیاتی یافت نشد.');
    }

    $updatedBy = operation_engine_safe_current_user();

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('به‌روزرسانی وضعیت سرویس انجام نشد.');
    }

    if ($serviceStepId === null) {
        if ($stepTitle === '') {
            @odbc_rollback($connection);
            operation_engine_render_error_page('خطای اعتبارسنجی', 'برای ایجاد مرحله جدید، عنوان مرحله الزامی است.');
        }

        $status = $stepStatus !== '' ? $stepStatus : 'OPEN';
        $insertOk = operation_engine_execute(
            $connection,
            'INSERT INTO dbo.erp_operation_service_steps (
                operation_case_id, step_type, step_title, step_description,
                assigned_technician_text, step_status, progress_percent, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $operationCaseId,
                $stepType,
                $stepTitle,
                $stepDescription !== '' ? $stepDescription : null,
                $assignedTechnician !== '' ? $assignedTechnician : null,
                $status,
                $progress,
                $updatedBy,
            ]
        );

        if ($insertOk === false) {
            @odbc_rollback($connection);
            throw new RuntimeException('ثبت مرحله سرویس انجام نشد.');
        }

        $serviceStepId = operation_engine_scope_identity($connection);
        $actionSummary = 'ایجاد مرحله سرویس: ' . $stepTitle;
    } else {
        $existing = operation_engine_fetch_rows(
            $connection,
            'SELECT TOP 1 step_status, started_at FROM dbo.erp_operation_service_steps
             WHERE service_step_id = ? AND operation_case_id = ?',
            [$serviceStepId, $operationCaseId]
        );

        if ($existing === []) {
            @odbc_rollback($connection);
            throw new RuntimeException('مرحله سرویس یافت نشد.');
        }

        $newStatus = $stepStatus !== '' ? $stepStatus : ($existing[0]['step_status'] ?? 'OPEN');
        $startedAt = $existing[0]['started_at'] ?? '';

        if ($newStatus === 'DONE') {
            $updateSql = 'UPDATE dbo.erp_operation_service_steps SET
                step_status = ?, progress_percent = ?,
                assigned_technician_text = ?,
                started_at = CASE WHEN started_at IS NULL THEN SYSUTCDATETIME() ELSE started_at END,
                completed_at = SYSUTCDATETIME(),
                updated_at = SYSUTCDATETIME(), updated_by = ?
                WHERE service_step_id = ? AND operation_case_id = ?';
        } elseif ($newStatus === 'IN_PROGRESS' && $startedAt === '') {
            $updateSql = 'UPDATE dbo.erp_operation_service_steps SET
                step_status = ?, progress_percent = ?,
                assigned_technician_text = ?,
                started_at = SYSUTCDATETIME(),
                updated_at = SYSUTCDATETIME(), updated_by = ?
                WHERE service_step_id = ? AND operation_case_id = ?';
        } else {
            $updateSql = 'UPDATE dbo.erp_operation_service_steps SET
                step_status = ?, progress_percent = ?,
                assigned_technician_text = ?,
                updated_at = SYSUTCDATETIME(), updated_by = ?
                WHERE service_step_id = ? AND operation_case_id = ?';
        }

        $updateOk = operation_engine_execute(
            $connection,
            $updateSql,
            [
                $newStatus,
                $progress,
                $assignedTechnician !== '' ? $assignedTechnician : null,
                $updatedBy,
                $serviceStepId,
                $operationCaseId,
            ]
        );

        if ($updateOk === false) {
            @odbc_rollback($connection);
            throw new RuntimeException('به‌روزرسانی مرحله سرویس انجام نشد.');
        }

        $actionSummary = 'به‌روزرسانی مرحله سرویس #' . $serviceStepId . ' — وضعیت: ' . $newStatus;

        if ($newStatus === 'IN_PROGRESS' && operation_engine_validate_stage($case['current_stage'] ?? '')) {
            $currentStage = strtoupper($case['current_stage'] ?? '');

            if (in_array($currentStage, ['RECEPTION', 'DIAGNOSIS'], true)) {
                operation_engine_execute(
                    $connection,
                    "UPDATE dbo.erp_operation_cases SET current_stage = 'SERVICE', current_status = 'IN_SERVICE',
                     updated_at = SYSUTCDATETIME(), updated_by = ? WHERE operation_case_id = ?",
                    [$updatedBy, $operationCaseId]
                );
            }
        }
    }

    if (operation_engine_all_steps_done($connection, $operationCaseId)) {
        operation_engine_execute(
            $connection,
            "UPDATE dbo.erp_operation_cases SET current_stage = 'QC', current_status = 'AWAITING_QC',
             updated_at = SYSUTCDATETIME(), updated_by = ? WHERE operation_case_id = ?",
            [$updatedBy, $operationCaseId]
        );
    }

    operation_engine_insert_history(
        $connection,
        'erp_operation_service_steps',
        $serviceStepId,
        'SERVICE_STEP_UPDATE',
        $actionSummary ?? 'به‌روزرسانی سرویس',
        null,
        json_encode(['operation_case_id' => $operationCaseId, 'progress' => $progress], JSON_UNESCAPED_UNICODE)
    );

    if (!@odbc_commit($connection)) {
        @odbc_rollback($connection);
        throw new RuntimeException('به‌روزرسانی وضعیت سرویس انجام نشد.');
    }

    @odbc_autocommit($connection, true);
    operation_engine_redirect('erp-jobcard-operation-flow.php?operation_case_id=' . $operationCaseId . '&phase2=service_ok');
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }

    operation_engine_render_error_page('خطا در ثبت', 'به‌روزرسانی وضعیت سرویس انجام نشد.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}
