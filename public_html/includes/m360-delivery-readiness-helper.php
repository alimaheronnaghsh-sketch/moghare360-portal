<?php
declare(strict_types=1);

/**
 * MOGHARE360 P6 — Delivery readiness (not actual vehicle release).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-final-inspection-helper.php';

const M360_DEL_READY_STATUS = 'READY';
const M360_DEL_READINESS_TABLE = 'erp_delivery_readiness_checks';
const M360_DEL_CONTROLS_TABLE = 'erp_delivery_controls';

/**
 * @param array<string, mixed> $jobcardRow
 * @param array<string, mixed>|null $qcCheckRow
 * @return array{ok:bool,message:string}
 */
function m360_delivery_readiness_validate($conn, int $jobcardId, array $jobcardRow, ?array $qcCheckRow): array
{
    $qcStatus = strtoupper(trim((string)($jobcardRow['qc_status'] ?? '')));
    if ($qcStatus !== 'QC_PASSED') {
        return ['ok' => false, 'message' => 'ابتدا QC باید Pass شود.'];
    }
    $notes = trim((string)($jobcardRow['final_inspection_notes'] ?? ''));
    if ($notes === '') {
        return ['ok' => false, 'message' => 'یادداشت بازبینی نهایی وجود ندارد.'];
    }
    $wx = strtoupper(trim((string)($jobcardRow['work_execution_status'] ?? '')));
    if (!in_array($wx, ['READY_FOR_QC', 'QC_PASSED', 'DELIVERY_READY'], true)) {
        $completed = trim((string)($jobcardRow['work_completed_at'] ?? '')) !== ''
            || strtoupper((string)($jobcardRow['technical_status'] ?? '')) === 'TECHNICAL_DONE';
        if (!$completed) {
            return ['ok' => false, 'message' => 'اجرای کار (P5) تکمیل نشده است.'];
        }
    }
    if ($qcCheckRow !== null) {
        $qcCheckId = (int)($qcCheckRow['qc_check_id'] ?? 0);
        if ($qcCheckId > 0 && m360_final_inspection_has_active_fail($conn, $qcCheckId)) {
            return ['ok' => false, 'message' => 'آیتم FAIL فعال در چک‌لیست وجود دارد.'];
        }
    }
    return ['ok' => true, 'message' => ''];
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_delivery_readiness_mark($conn, int $jobcardId, int $qcCheckId, int $userId, ?string $note = null): array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات نامعتبر است.'];
    }

    if (customer_core_table_exists($conn, M360_DEL_READINESS_TABLE)) {
        customer_core_execute(
            $conn,
            'INSERT INTO dbo.' . M360_DEL_READINESS_TABLE . ' (jobcard_id, qc_check_id, readiness_status, readiness_note, ready_at, created_by_user_id) VALUES (?, ?, ?, ?, SYSUTCDATETIME(), ?)',
            [$jobcardId, $qcCheckId > 0 ? $qcCheckId : null, M360_DEL_READY_STATUS, $note, $userId]
        );
    }

    if (customer_core_table_exists($conn, M360_DEL_CONTROLS_TABLE)) {
        customer_core_execute(
            $conn,
            "INSERT INTO dbo." . M360_DEL_CONTROLS_TABLE . " (jobcard_id, qc_check_id, delivery_status, delivery_allowed, released_at, created_at, is_active)
             VALUES (?, ?, N'READY', 1, NULL, SYSUTCDATETIME(), 1)",
            [$jobcardId, $qcCheckId > 0 ? $qcCheckId : null]
        );
    }

    return ['ok' => true, 'message' => 'آمادگی تحویل ثبت شد.'];
}
