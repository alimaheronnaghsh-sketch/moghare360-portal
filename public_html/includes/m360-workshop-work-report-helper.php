<?php
declare(strict_types=1);

/**
 * Workshop completion (work) report workflow — create/submit/approve/return with history.
 * Distinct from diagnosis reports. Does not approve customer pricing.
 */

require_once __DIR__ . '/m360-workshop-access-enforcement.php';

function m360_ws_wr_fetch($conn, int $reportId): ?array
{
    if ($reportId < 1) {
        return null;
    }
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.erp_workshop_work_reports WHERE work_report_id=?', [$reportId]);
    return $rows[0] ?? null;
}

/** @return list<array<string,mixed>> */
function m360_ws_wr_list_for_jobcard($conn, int $jobcardId): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.erp_workshop_work_reports WHERE jobcard_id=? ORDER BY revision_no DESC, work_report_id DESC',
        [$jobcardId]
    );
}

function m360_ws_wr_history_add($conn, int $reportId, int $jobcardId, string $event, ?string $old, ?string $new, string $note, int $actorId): void
{
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_workshop_work_report_history
            (work_report_id, jobcard_id, event_name, old_status, new_status, event_note, actor_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$reportId, $jobcardId, $event, $old, $new, $note !== '' ? $note : null, $actorId]
    );
}

/**
 * @param array<string,mixed> $input
 * @return array{ok:bool,message:string,work_report_id:int}
 */
function m360_ws_wr_create_or_update_draft($conn, int $companyId, int $jobcardId, ?int $workItemId, array $input, int $actorId): array
{
    $desc = trim((string)($input['work_description'] ?? ''));
    if ($desc === '') {
        return ['ok' => false, 'message' => 'شرح کار انجام‌شده الزامی است.', 'work_report_id' => 0];
    }
    $reportId = (int)($input['work_report_id'] ?? 0);
    $duration = (int)($input['duration_minutes'] ?? 0);
    $performer = (int)($input['performer_user_id'] ?? $actorId);
    if ($reportId > 0) {
        $existing = m360_ws_wr_fetch($conn, $reportId);
        if ($existing === null || (int)$existing['jobcard_id'] !== $jobcardId) {
            return ['ok' => false, 'message' => 'گزارش انجام کار یافت نشد.', 'work_report_id' => 0];
        }
        $st = strtoupper((string)$existing['status']);
        if (!in_array($st, ['DRAFT', 'RETURNED'], true)) {
            return ['ok' => false, 'message' => 'فقط گزارش پیش‌نویس یا برگشتی قابل ویرایش است.', 'work_report_id' => $reportId];
        }
        customer_core_execute(
            $conn,
            "UPDATE dbo.erp_workshop_work_reports SET
                work_description=?, performer_user_id=?, duration_minutes=?, result_summary=?,
                remaining_fault=?, recommendation=?, status=N'DRAFT', updated_at=SYSUTCDATETIME()
             WHERE work_report_id=?",
            [
                $desc, $performer > 0 ? $performer : null, $duration > 0 ? $duration : null,
                trim((string)($input['result_summary'] ?? '')) ?: null,
                trim((string)($input['remaining_fault'] ?? '')) ?: null,
                trim((string)($input['recommendation'] ?? '')) ?: null,
                $reportId,
            ]
        );
        m360_ws_wr_history_add($conn, $reportId, $jobcardId, 'WORK_REPORT_DRAFT_SAVED', $st, 'DRAFT', 'ذخیره پیش‌نویس', $actorId);
        return ['ok' => true, 'message' => 'پیش‌نویس گزارش انجام کار ذخیره شد.', 'work_report_id' => $reportId];
    }

    $rev = (int)(customer_core_scalar(
        $conn,
        'SELECT ISNULL(MAX(revision_no),0)+1 FROM dbo.erp_workshop_work_reports WHERE jobcard_id=?',
        [$jobcardId]
    ) ?? 1);
    customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_workshop_work_reports
            (company_id, jobcard_id, work_item_id, revision_no, status, work_description, performer_user_id,
             duration_minutes, result_summary, remaining_fault, recommendation, created_by_user_id)
         VALUES (?, ?, ?, ?, N'DRAFT', ?, ?, ?, ?, ?, ?, ?)",
        [
            $companyId, $jobcardId, $workItemId, $rev, $desc, $performer > 0 ? $performer : null,
            $duration > 0 ? $duration : null,
            trim((string)($input['result_summary'] ?? '')) ?: null,
            trim((string)($input['remaining_fault'] ?? '')) ?: null,
            trim((string)($input['recommendation'] ?? '')) ?: null,
            $actorId,
        ]
    );
    $id = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 work_report_id FROM dbo.erp_workshop_work_reports WHERE jobcard_id=? AND revision_no=? ORDER BY work_report_id DESC',
        [$jobcardId, $rev]
    ) ?? 0);
    if ($id > 0) {
        m360_ws_wr_history_add($conn, $id, $jobcardId, 'WORK_REPORT_CREATED', null, 'DRAFT', 'ایجاد گزارش انجام کار', $actorId);
    }
    return ['ok' => $id > 0, 'message' => $id > 0 ? 'گزارش انجام کار ایجاد شد.' : 'ایجاد گزارش ناموفق بود.', 'work_report_id' => $id];
}

function m360_ws_wr_submit($conn, int $reportId, int $actorId): array
{
    $r = m360_ws_wr_fetch($conn, $reportId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'گزارش یافت نشد.'];
    }
    $st = strtoupper((string)$r['status']);
    if (!in_array($st, ['DRAFT', 'RETURNED'], true)) {
        return ['ok' => false, 'message' => 'فقط گزارش پیش‌نویس یا برگشتی قابل ارسال است.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_work_reports
         SET status=N'SUBMITTED', submitted_at=SYSUTCDATETIME(), submitted_by_user_id=?, updated_at=SYSUTCDATETIME()
         WHERE work_report_id=?",
        [$actorId, $reportId]
    );
    m360_ws_wr_history_add($conn, $reportId, (int)$r['jobcard_id'], 'WORK_REPORT_SUBMITTED', $st, 'SUBMITTED', 'ارسال برای تأیید', $actorId);
    return ['ok' => true, 'message' => 'گزارش انجام کار ارسال شد.'];
}

function m360_ws_wr_approve($conn, int $reportId, int $actorId): array
{
    $r = m360_ws_wr_fetch($conn, $reportId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'گزارش یافت نشد.'];
    }
    if (strtoupper((string)$r['status']) !== 'SUBMITTED') {
        return ['ok' => false, 'message' => 'فقط گزارش ارسال‌شده قابل تأیید است.'];
    }
    $creator = (int)($r['created_by_user_id'] ?? 0);
    $submitter = (int)($r['submitted_by_user_id'] ?? 0);
    if ($actorId === $creator || $actorId === $submitter) {
        return ['ok' => false, 'message' => 'ایجادکننده/ارسال‌کننده نمی‌تواند گزارش خود را تأیید کند (قاعده maker-checker).'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_work_reports
         SET status=N'APPROVED', approved_at=SYSUTCDATETIME(), approved_by_user_id=?, updated_at=SYSUTCDATETIME()
         WHERE work_report_id=?",
        [$actorId, $reportId]
    );
    if (customer_core_column_exists($conn, 'erp_jobcards', 'technical_completion_notes')) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.erp_jobcards SET technical_completion_notes=?, updated_at=SYSUTCDATETIME() WHERE jobcard_id=?',
            [(string)$r['work_description'], (int)$r['jobcard_id']]
        );
    }
    m360_ws_wr_history_add($conn, $reportId, (int)$r['jobcard_id'], 'WORK_REPORT_APPROVED', 'SUBMITTED', 'APPROVED', 'تأیید گزارش انجام کار — بدون تأیید قیمت مشتری', $actorId);
    return ['ok' => true, 'message' => 'گزارش انجام کار تأیید شد (بدون تأیید مالی مشتری).'];
}

function m360_ws_wr_return($conn, int $reportId, int $actorId, string $reason): array
{
    $reason = trim($reason);
    if ($reason === '') {
        return ['ok' => false, 'message' => 'ذکر دلیل برگشت به فارسی الزامی است.'];
    }
    $r = m360_ws_wr_fetch($conn, $reportId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'گزارش یافت نشد.'];
    }
    if (strtoupper((string)$r['status']) !== 'SUBMITTED') {
        return ['ok' => false, 'message' => 'فقط گزارش ارسال‌شده قابل برگشت است.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_work_reports
         SET status=N'RETURNED', returned_at=SYSUTCDATETIME(), returned_by_user_id=?, return_reason=?, updated_at=SYSUTCDATETIME()
         WHERE work_report_id=?",
        [$actorId, $reason, $reportId]
    );
    m360_ws_wr_history_add($conn, $reportId, (int)$r['jobcard_id'], 'WORK_REPORT_RETURNED', 'SUBMITTED', 'RETURNED', $reason, $actorId);

    $rev = (int)(customer_core_scalar(
        $conn,
        'SELECT ISNULL(MAX(revision_no),0)+1 FROM dbo.erp_workshop_work_reports WHERE jobcard_id=?',
        [(int)$r['jobcard_id']]
    ) ?? 1);
    customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_workshop_work_reports
            (company_id, jobcard_id, work_item_id, revision_no, status, work_description, performer_user_id,
             duration_minutes, result_summary, remaining_fault, recommendation, created_by_user_id, supersedes_report_id)
         VALUES (?, ?, ?, ?, N'DRAFT', ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            (int)$r['company_id'], (int)$r['jobcard_id'], $r['work_item_id'], $rev,
            (string)$r['work_description'], $r['performer_user_id'], $r['duration_minutes'],
            $r['result_summary'], $r['remaining_fault'], $r['recommendation'],
            (int)$r['created_by_user_id'], $reportId,
        ]
    );
    $newId = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 work_report_id FROM dbo.erp_workshop_work_reports WHERE jobcard_id=? AND revision_no=? ORDER BY work_report_id DESC',
        [(int)$r['jobcard_id'], $rev]
    ) ?? 0);
    if ($newId > 0) {
        m360_ws_wr_history_add($conn, $newId, (int)$r['jobcard_id'], 'WORK_REPORT_REVISION_OPENED', null, 'DRAFT', 'باز شدن نسخه اصلاحی', $actorId);
    }
    return ['ok' => true, 'message' => 'گزارش انجام کار برای اصلاح برگشت داده شد.', 'new_report_id' => $newId];
}
