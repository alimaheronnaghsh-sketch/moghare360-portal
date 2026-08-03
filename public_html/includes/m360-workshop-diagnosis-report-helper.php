<?php
declare(strict_types=1);

/**
 * Workshop diagnosis report workflow — create/submit/approve/return with history.
 */

require_once __DIR__ . '/m360-workshop-access-enforcement.php';

const M360_WS_DIAG_STATUSES = ['DRAFT', 'SUBMITTED', 'APPROVED', 'RETURNED', 'SUPERSEDED', 'CANCELLED'];

function m360_ws_diag_fetch($conn, int $reportId): ?array
{
    if ($reportId < 1) {
        return null;
    }
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.erp_workshop_diagnosis_reports WHERE diagnosis_report_id=?', [$reportId]);
    return $rows[0] ?? null;
}

/**
 * @return list<array<string,mixed>>
 */
function m360_ws_diag_list_for_jobcard($conn, int $jobcardId): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.erp_workshop_diagnosis_reports WHERE jobcard_id=? ORDER BY revision_no DESC, diagnosis_report_id DESC',
        [$jobcardId]
    );
}

function m360_ws_diag_history_add($conn, int $reportId, int $jobcardId, string $event, ?string $old, ?string $new, string $note, int $actorId): void
{
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_workshop_diagnosis_report_history
            (diagnosis_report_id, jobcard_id, event_name, old_status, new_status, event_note, actor_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$reportId, $jobcardId, $event, $old, $new, $note !== '' ? $note : null, $actorId]
    );
}

/**
 * @param array<string,mixed> $input
 * @return array{ok:bool,message:string,diagnosis_report_id:int}
 */
function m360_ws_diag_create_or_update_draft($conn, int $companyId, int $jobcardId, ?int $workItemId, array $input, int $actorId): array
{
    $summary = trim((string)($input['diagnosis_summary'] ?? ''));
    if ($summary === '') {
        return ['ok' => false, 'message' => 'خلاصه تشخیص الزامی است.', 'diagnosis_report_id' => 0];
    }
    $reportId = (int)($input['diagnosis_report_id'] ?? 0);
    if ($reportId > 0) {
        $existing = m360_ws_diag_fetch($conn, $reportId);
        if ($existing === null || (int)$existing['jobcard_id'] !== $jobcardId) {
            return ['ok' => false, 'message' => 'گزارش تشخیص یافت نشد.', 'diagnosis_report_id' => 0];
        }
        $st = strtoupper((string)$existing['status']);
        if (!in_array($st, ['DRAFT', 'RETURNED'], true)) {
            return ['ok' => false, 'message' => 'فقط گزارش پیش‌نویس یا برگشتی قابل ویرایش است.', 'diagnosis_report_id' => $reportId];
        }
        customer_core_execute(
            $conn,
            "UPDATE dbo.erp_workshop_diagnosis_reports SET
                diagnosis_summary=?, fault_assessment=?, probable_cause=?, proposed_work=?, proposed_parts=?, risk_note=?,
                status=N'DRAFT', updated_at=SYSUTCDATETIME()
             WHERE diagnosis_report_id=?",
            [
                $summary,
                trim((string)($input['fault_assessment'] ?? '')) ?: null,
                trim((string)($input['probable_cause'] ?? '')) ?: null,
                trim((string)($input['proposed_work'] ?? '')) ?: null,
                trim((string)($input['proposed_parts'] ?? '')) ?: null,
                trim((string)($input['risk_note'] ?? '')) ?: null,
                $reportId,
            ]
        );
        m360_ws_diag_history_add($conn, $reportId, $jobcardId, 'DIAGNOSIS_DRAFT_SAVED', $st, 'DRAFT', 'ذخیره پیش‌نویس', $actorId);
        return ['ok' => true, 'message' => 'پیش‌نویس گزارش تشخیص ذخیره شد.', 'diagnosis_report_id' => $reportId];
    }

    $rev = (int)(customer_core_scalar(
        $conn,
        'SELECT ISNULL(MAX(revision_no),0)+1 FROM dbo.erp_workshop_diagnosis_reports WHERE jobcard_id=?',
        [$jobcardId]
    ) ?? 1);
    customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_workshop_diagnosis_reports
            (company_id, jobcard_id, work_item_id, revision_no, status, diagnosis_summary, fault_assessment,
             probable_cause, proposed_work, proposed_parts, risk_note, created_by_user_id)
         VALUES (?, ?, ?, ?, N'DRAFT', ?, ?, ?, ?, ?, ?, ?)",
        [
            $companyId, $jobcardId, $workItemId, $rev, $summary,
            trim((string)($input['fault_assessment'] ?? '')) ?: null,
            trim((string)($input['probable_cause'] ?? '')) ?: null,
            trim((string)($input['proposed_work'] ?? '')) ?: null,
            trim((string)($input['proposed_parts'] ?? '')) ?: null,
            trim((string)($input['risk_note'] ?? '')) ?: null,
            $actorId,
        ]
    );
    $id = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 diagnosis_report_id FROM dbo.erp_workshop_diagnosis_reports WHERE jobcard_id=? AND revision_no=? ORDER BY diagnosis_report_id DESC',
        [$jobcardId, $rev]
    ) ?? 0);
    if ($id > 0) {
        m360_ws_diag_history_add($conn, $id, $jobcardId, 'DIAGNOSIS_CREATED', null, 'DRAFT', 'ایجاد گزارش تشخیص', $actorId);
    }
    return ['ok' => $id > 0, 'message' => $id > 0 ? 'گزارش تشخیص ایجاد شد.' : 'ایجاد گزارش ناموفق بود.', 'diagnosis_report_id' => $id];
}

/** @return array{ok:bool,message:string} */
function m360_ws_diag_submit($conn, int $reportId, int $actorId): array
{
    $r = m360_ws_diag_fetch($conn, $reportId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'گزارش یافت نشد.'];
    }
    $st = strtoupper((string)$r['status']);
    if (!in_array($st, ['DRAFT', 'RETURNED'], true)) {
        return ['ok' => false, 'message' => 'فقط گزارش پیش‌نویس یا برگشتی قابل ارسال است.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_diagnosis_reports
         SET status=N'SUBMITTED', submitted_at=SYSUTCDATETIME(), submitted_by_user_id=?, updated_at=SYSUTCDATETIME()
         WHERE diagnosis_report_id=?",
        [$actorId, $reportId]
    );
    m360_ws_diag_history_add($conn, $reportId, (int)$r['jobcard_id'], 'DIAGNOSIS_SUBMITTED', $st, 'SUBMITTED', 'ارسال برای تأیید', $actorId);
    return ['ok' => true, 'message' => 'گزارش تشخیص ارسال شد.'];
}

/** @return array{ok:bool,message:string} */
function m360_ws_diag_approve($conn, int $reportId, int $actorId, bool $isOwner): array
{
    $r = m360_ws_diag_fetch($conn, $reportId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'گزارش یافت نشد.'];
    }
    if (strtoupper((string)$r['status']) !== 'SUBMITTED') {
        return ['ok' => false, 'message' => 'فقط گزارش ارسال‌شده قابل تأیید است.'];
    }
    $creator = (int)($r['created_by_user_id'] ?? 0);
    $submitter = (int)($r['submitted_by_user_id'] ?? 0);
    if ($actorId === $creator || $actorId === $submitter) {
        // No silent Owner bypass — even Owner must not auto-approve own report without separate audited policy row.
        return ['ok' => false, 'message' => 'ایجادکننده/ارسال‌کننده نمی‌تواند گزارش خود را تأیید کند (قاعده maker-checker).'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_diagnosis_reports
         SET status=N'APPROVED', approved_at=SYSUTCDATETIME(), approved_by_user_id=?, updated_at=SYSUTCDATETIME()
         WHERE diagnosis_report_id=?",
        [$actorId, $reportId]
    );
    // Mirror summary onto jobcard for legacy readers (non-destructive).
    if (customer_core_column_exists($conn, 'erp_jobcards', 'diagnosis_summary')) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.erp_jobcards SET diagnosis_summary=?, updated_at=SYSUTCDATETIME() WHERE jobcard_id=?',
            [(string)$r['diagnosis_summary'], (int)$r['jobcard_id']]
        );
    }
    m360_ws_diag_history_add($conn, $reportId, (int)$r['jobcard_id'], 'DIAGNOSIS_APPROVED', 'SUBMITTED', 'APPROVED', 'تأیید گزارش تشخیص', $actorId);
    return ['ok' => true, 'message' => 'گزارش تشخیص تأیید شد.'];
}

/** @return array{ok:bool,message:string,new_report_id?:int} */
function m360_ws_diag_return($conn, int $reportId, int $actorId, string $reason): array
{
    $reason = trim($reason);
    if ($reason === '') {
        return ['ok' => false, 'message' => 'ذکر دلیل برگشت به فارسی الزامی است.'];
    }
    $r = m360_ws_diag_fetch($conn, $reportId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'گزارش یافت نشد.'];
    }
    if (strtoupper((string)$r['status']) !== 'SUBMITTED') {
        return ['ok' => false, 'message' => 'فقط گزارش ارسال‌شده قابل برگشت است.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_diagnosis_reports
         SET status=N'RETURNED', returned_at=SYSUTCDATETIME(), returned_by_user_id=?, return_reason=?, updated_at=SYSUTCDATETIME()
         WHERE diagnosis_report_id=?",
        [$actorId, $reason, $reportId]
    );
    m360_ws_diag_history_add($conn, $reportId, (int)$r['jobcard_id'], 'DIAGNOSIS_RETURNED', 'SUBMITTED', 'RETURNED', $reason, $actorId);

    // Controlled revision: new DRAFT row linked via supersedes; prior SUBMITTED→RETURNED preserved.
    $rev = (int)(customer_core_scalar(
        $conn,
        'SELECT ISNULL(MAX(revision_no),0)+1 FROM dbo.erp_workshop_diagnosis_reports WHERE jobcard_id=?',
        [(int)$r['jobcard_id']]
    ) ?? 1);
    $ins = customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_workshop_diagnosis_reports
            (company_id, jobcard_id, work_item_id, revision_no, status, diagnosis_summary, fault_assessment,
             probable_cause, proposed_work, proposed_parts, risk_note, created_by_user_id, supersedes_report_id)
         VALUES (?, ?, ?, ?, N'DRAFT', ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            (int)$r['company_id'], (int)$r['jobcard_id'], $r['work_item_id'] !== null ? (int)$r['work_item_id'] : null, $rev,
            (string)$r['diagnosis_summary'], $r['fault_assessment'], $r['probable_cause'],
            $r['proposed_work'], $r['proposed_parts'], $r['risk_note'],
            (int)$r['created_by_user_id'], $reportId,
        ]
    );
    $newId = 0;
    if ($ins !== false) {
        $newId = (int)(customer_core_scalar(
            $conn,
            'SELECT TOP 1 diagnosis_report_id FROM dbo.erp_workshop_diagnosis_reports WHERE jobcard_id=? AND revision_no=? ORDER BY diagnosis_report_id DESC',
            [(int)$r['jobcard_id'], $rev]
        ) ?? 0);
        if ($newId > 0) {
            m360_ws_diag_history_add($conn, $newId, (int)$r['jobcard_id'], 'DIAGNOSIS_REVISION_OPENED', null, 'DRAFT', 'باز شدن نسخه اصلاحی پس از برگشت', $actorId);
        }
    }
    return ['ok' => true, 'message' => 'گزارش برای اصلاح برگشت داده شد.', 'new_report_id' => $newId];
}
