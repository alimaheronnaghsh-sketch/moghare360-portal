<?php
declare(strict_types=1);

/**
 * MOGHARE360 P5 — Technical completion and READY_FOR_QC validation.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-parts-consumption-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-technical-operation-helper.php';

const M360_WORK_STATUS_READY_QC = 'READY_FOR_QC';
const M360_WORK_STATUS_TECH_COMPLETED = 'TECHNICAL_COMPLETED';

/**
 * @return array{ok:bool,message:string}
 */
function m360_technical_completion_validate_service_ops($conn, int $jobcardId): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_TECHNICAL_SERVICE_TABLE)) {
        return ['ok' => true, 'message' => ''];
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT service_operation_id, service_status FROM dbo.' . M360_TECHNICAL_SERVICE_TABLE . ' WHERE jobcard_id = ? AND is_active = 1',
        [$jobcardId]
    );
    if ($rows === []) {
        return ['ok' => true, 'message' => ''];
    }
    foreach ($rows as $row) {
        $st = strtoupper((string)($row['service_status'] ?? ''));
        if (!in_array($st, [M360_SO_STATUS_COMPLETED, M360_SO_STATUS_CANCELLED], true)) {
            return ['ok' => false, 'message' => 'همه عملیات سرویس باید تکمیل شوند.'];
        }
    }
    return ['ok' => true, 'message' => ''];
}

/**
 * @param array<string, mixed> $jobcardRow
 * @return array{ok:bool,message:string}
 */
function m360_technical_completion_validate($conn, int $jobcardId, array $jobcardRow): array
{
    $notes = trim((string)($jobcardRow['technical_completion_notes'] ?? ''));
    if ($notes === '') {
        return ['ok' => false, 'message' => 'یادداشت تکمیل فنی الزامی است.'];
    }
    if (!m360_parts_consumption_complete($conn, $jobcardId)) {
        return ['ok' => false, 'message' => 'قطعات تأیید‌شده هنوز کامل مصرف نشده‌اند.'];
    }
    $so = m360_technical_completion_validate_service_ops($conn, $jobcardId);
    if (!$so['ok']) {
        return $so;
    }
    return ['ok' => true, 'message' => ''];
}

/**
 * @param array<string, mixed> $jobcardRow
 * @return array{ok:bool,message:string}
 */
function m360_technical_ready_for_qc_validate($conn, int $jobcardId, array $jobcardRow): array
{
    $workStatus = strtoupper(trim((string)($jobcardRow['work_execution_status'] ?? '')));
    if (!in_array($workStatus, [M360_WORK_STATUS_TECH_COMPLETED, 'SERVICE_COMPLETED', 'TECHNICAL_COMPLETION_REVIEW'], true)) {
        $complete = m360_technical_completion_validate($conn, $jobcardId, $jobcardRow);
        if (!$complete['ok']) {
            return ['ok' => false, 'message' => 'ابتدا تکمیل فنی باید انجام شود.'];
        }
    }
    return ['ok' => true, 'message' => ''];
}
