<?php
declare(strict_types=1);

/**
 * Workshop R0B permission enforcement helpers (canonical matrix resolver only).
 * No second permission engine. No numeric Owner ID. No real personnel grants.
 */

require_once __DIR__ . '/m360-access-matrix-guard.php';

/** @var list<string> */
const M360_WS_PRICE_FIELD_KEYS = [
    'unit_price', 'customer_price', 'price', 'price_irr', 'price_irr_input', 'amount', 'line_total',
    'discount', 'discount_amount', 'tax', 'tax_amount', 'vat', 'vat_amount', 'margin',
    'purchase_price', 'supplier_payment', 'settlement_amount', 'invoice_amount', 'financial_amount',
];

/**
 * Resolve active company membership for the actor. Positive company_id required
 * for non-Owner actors. Owner may proceed with company_id from membership or session.
 *
 * @return array{user_id:int,company_id:int,conn:resource,is_owner:bool}
 */
function m360_ws_require_actor_context(): array
{
    if (!function_exists('erp_auth_require_login')) {
        customer_core_require_helper('erp-auth-context.php');
    }
    erp_auth_require_login();
    $uid = (int)(erp_auth_current_user_id() ?? 0);
    $conn = m360_am_db();
    if ($conn === false || $uid < 1) {
        m360_am_forbidden('نشست کاربری معتبر نیست.');
    }

    $isOwner = m360_am_is_owner($conn, $uid);
    $mem = m360_am_one(
        $conn,
        'SELECT TOP 1 company_id FROM dbo.erp_company_users WHERE user_id=? AND is_active=1 AND company_id > 0 ORDER BY company_id',
        [$uid]
    );
    $companyId = (int)($mem['company_id'] ?? 0);
    if ($companyId < 1) {
        $sessionCid = (int)($_SESSION['erp_company_id'] ?? 0);
        if ($sessionCid > 0) {
            $chk = m360_am_one(
                $conn,
                'SELECT TOP 1 company_id FROM dbo.erp_company_users WHERE user_id=? AND company_id=? AND is_active=1',
                [$uid, $sessionCid]
            );
            $companyId = (int)($chk['company_id'] ?? 0);
        }
    }
    if ($companyId < 1 && !$isOwner) {
        m360_am_forbidden('عضویت فعال شرکت برای این کاربر یافت نشد.');
    }
    if ($companyId < 1 && $isOwner) {
        $companyId = (int)($_SESSION['erp_company_id'] ?? 0);
        if ($companyId < 1) {
            $companyId = 1; // Owner operational default for object-scope helpers only — not an auth grant source
        }
    }

    return ['user_id' => $uid, 'company_id' => $companyId, 'conn' => $conn, 'is_owner' => $isOwner];
}

/**
 * Soft can() that does not exit on missing membership (for menu visibility).
 */
function m360_ws_can(string $permissionKey): bool
{
    if (!function_exists('erp_auth_current_user_id')) {
        return false;
    }
    $uid = (int)(erp_auth_current_user_id() ?? 0);
    if ($uid < 1) {
        return false;
    }
    $conn = m360_am_db();
    if ($conn === false) {
        return false;
    }
    if (m360_am_is_owner($conn, $uid)) {
        return true;
    }
    $mem = m360_am_one(
        $conn,
        'SELECT TOP 1 company_id FROM dbo.erp_company_users WHERE user_id=? AND is_active=1 AND company_id > 0 ORDER BY company_id',
        [$uid]
    );
    $cid = (int)($mem['company_id'] ?? 0);
    if ($cid < 1) {
        return false;
    }
    return m360_am_effective_can($conn, $uid, $cid, $permissionKey);
}

function m360_ws_can_any(array $permissionKeys): bool
{
    foreach ($permissionKeys as $k) {
        if (m360_ws_can((string)$k)) {
            return true;
        }
    }
    return false;
}

/**
 * Hard require one permission (+ optional jobcard object existence).
 */
function m360_ws_require(string $permissionKey, ?int $jobcardId = null): void
{
    $ctx = m360_ws_require_actor_context();
    $conn = $ctx['conn'];
    $uid = $ctx['user_id'];
    $cid = $ctx['company_id'];

    $isOwner = m360_am_is_owner($conn, $uid);
    if (!$isOwner && !m360_am_effective_can($conn, $uid, $cid, $permissionKey)) {
        m360_am_forbidden('شما مجوز «' . $permissionKey . '» را ندارید.');
    }

    if ($jobcardId !== null && $jobcardId > 0) {
        m360_ws_assert_jobcard_object_scope($conn, $jobcardId, $cid, $isOwner);
    }
}

/**
 * Hard require any of the listed permissions.
 *
 * @param list<string> $permissionKeys
 */
function m360_ws_require_any(array $permissionKeys, ?int $jobcardId = null): void
{
    $ctx = m360_ws_require_actor_context();
    $conn = $ctx['conn'];
    $uid = $ctx['user_id'];
    $cid = $ctx['company_id'];
    $isOwner = m360_am_is_owner($conn, $uid);

    if (!$isOwner) {
        $ok = false;
        foreach ($permissionKeys as $k) {
            if (m360_am_effective_can($conn, $uid, $cid, (string)$k)) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            m360_am_forbidden('شما مجوز مشاهده یا انجام این عملیات را ندارید.');
        }
    }

    if ($jobcardId !== null && $jobcardId > 0) {
        m360_ws_assert_jobcard_object_scope($conn, $jobcardId, $cid, $isOwner);
    }
}

/**
 * Resolve JobCard company_id from canonical column (preferred) or proven creator/reception membership.
 * Returns null when ambiguous or unresolved — never guesses company 1.
 */
function m360_ws_resolve_jobcard_company_id($conn, int $jobcardId): ?int
{
    if ($jobcardId < 1) {
        return null;
    }
    if (customer_core_column_exists($conn, 'erp_jobcards', 'company_id')) {
        $row = m360_am_one($conn, 'SELECT TOP 1 company_id FROM dbo.erp_jobcards WHERE jobcard_id=?', [$jobcardId]);
        if ($row !== null) {
            $cid = (int)($row['company_id'] ?? 0);
            if ($cid > 0) {
                return $cid;
            }
            // Explicit NULL/0 → ambiguous; do not invent.
            return null;
        }
        return null;
    }
    // Legacy fallback before column exists: unique creator/reception membership only.
    $n = (int)(customer_core_scalar(
        $conn,
        "SELECT COUNT(DISTINCT cu.company_id)
         FROM dbo.erp_jobcards j
         INNER JOIN dbo.erp_company_users cu
           ON cu.is_active=1 AND cu.company_id > 0
          AND cu.user_id IN (j.created_by_user_id, j.reception_user_id)
         WHERE j.jobcard_id=?",
        [$jobcardId]
    ) ?? 0);
    if ($n !== 1) {
        return null;
    }
    $cid = (int)(customer_core_scalar(
        $conn,
        "SELECT MIN(cu.company_id)
         FROM dbo.erp_jobcards j
         INNER JOIN dbo.erp_company_users cu
           ON cu.is_active=1 AND cu.company_id > 0
          AND cu.user_id IN (j.created_by_user_id, j.reception_user_id)
         WHERE j.jobcard_id=?",
        [$jobcardId]
    ) ?? 0);
    return $cid > 0 ? $cid : null;
}

/**
 * SQL predicate + params for JobCard company tenancy.
 * Owner sees all (including ambiguous). Non-Owner: company_id = actor company only.
 *
 * @return array{sql:string,params:list<mixed>}
 */
function m360_ws_jobcard_company_sql(string $alias, int $actorCompanyId, bool $isOwner): array
{
    $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias) ?: 'j';
    if ($isOwner) {
        return ['sql' => '1=1', 'params' => []];
    }
    if ($actorCompanyId < 1) {
        return ['sql' => '1=0', 'params' => []];
    }
    return [
        'sql' => "{$alias}.company_id = ?",
        'params' => [$actorCompanyId],
    ];
}

/**
 * Object + company scope: jobcard must exist and belong to actor company (unless Owner).
 * Ambiguous (NULL company_id) JobCards are blocked for non-Owner.
 */
function m360_ws_assert_jobcard_object_scope($conn, int $jobcardId, ?int $actorCompanyId = null, ?bool $isOwner = null): void
{
    if ($jobcardId < 1) {
        m360_am_forbidden('شناسه پرونده تعمیر نامعتبر است.');
    }
    $cols = customer_core_column_exists($conn, 'erp_jobcards', 'company_id')
        ? 'jobcard_id, company_id'
        : 'jobcard_id';
    $row = m360_am_one($conn, "SELECT TOP 1 {$cols} FROM dbo.erp_jobcards WHERE jobcard_id=?", [$jobcardId]);
    if ($row === null) {
        m360_am_forbidden('پرونده تعمیر در محدوده مجاز یافت نشد.');
    }

    if ($actorCompanyId === null || $isOwner === null) {
        // Soft path for legacy callers: resolve actor from session if available.
        if (function_exists('erp_auth_current_user_id')) {
            $uid = (int)(erp_auth_current_user_id() ?? 0);
            if ($uid > 0) {
                $isOwner = $isOwner ?? m360_am_is_owner($conn, $uid);
                if ($actorCompanyId === null && !($isOwner ?? false)) {
                    $mem = m360_am_one(
                        $conn,
                        'SELECT TOP 1 company_id FROM dbo.erp_company_users WHERE user_id=? AND is_active=1 AND company_id > 0 ORDER BY company_id',
                        [$uid]
                    );
                    $actorCompanyId = (int)($mem['company_id'] ?? 0);
                }
            }
        }
    }

    if ($isOwner === true) {
        return;
    }

    $jcCompany = m360_ws_resolve_jobcard_company_id($conn, $jobcardId);
    if ($jcCompany === null) {
        m360_am_forbidden('پرونده تعمیر فاقد شرکت مشخص است و برای کاربران عملیاتی مسدود است.');
    }
    if ($actorCompanyId !== null && (int)$actorCompanyId > 0 && $jcCompany !== (int)$actorCompanyId) {
        m360_am_forbidden('پرونده تعمیر خارج از محدوده شرکت شماست.');
    }
}

/**
 * Stamp verified company_id on a new JobCard (no ambiguous fallback).
 */
function m360_ws_stamp_jobcard_company($conn, int $jobcardId, int $companyId): bool
{
    if ($jobcardId < 1 || $companyId < 1 || !customer_core_column_exists($conn, 'erp_jobcards', 'company_id')) {
        return false;
    }
    return customer_core_execute(
        $conn,
        'UPDATE dbo.erp_jobcards SET company_id=? WHERE jobcard_id=? AND (company_id IS NULL OR company_id=0)',
        [$companyId, $jobcardId]
    ) !== false;
}

function m360_ws_assert_assignment_object_scope($conn, int $assignmentId, int $jobcardId = 0): void
{
    if ($assignmentId < 1) {
        m360_am_forbidden('شناسه تخصیص نامعتبر است.');
    }
    $row = m360_am_one(
        $conn,
        'SELECT TOP 1 assignment_id, jobcard_id FROM dbo.erp_jobcard_assignments WHERE assignment_id=?',
        [$assignmentId]
    );
    if ($row === null) {
        m360_am_forbidden('تخصیص در محدوده مجاز یافت نشد.');
    }
    if ($jobcardId > 0 && (int)$row['jobcard_id'] !== $jobcardId) {
        m360_am_forbidden('عدم تطابق تخصیص با پرونده تعمیر.');
    }
}

function m360_ws_assert_request_object_scope($conn, int $technicalRequestId, int $jobcardId = 0): void
{
    if ($technicalRequestId < 1) {
        m360_am_forbidden('شناسه درخواست قطعه نامعتبر است.');
    }
    $row = m360_am_one(
        $conn,
        'SELECT TOP 1 technical_request_id, jobcard_id FROM dbo.erp_jobcard_technical_requests WHERE technical_request_id=?',
        [$technicalRequestId]
    );
    if ($row === null) {
        m360_am_forbidden('درخواست در محدوده مجاز یافت نشد.');
    }
    if ($jobcardId > 0 && (int)$row['jobcard_id'] !== $jobcardId) {
        m360_am_forbidden('عدم تطابق درخواست با پرونده تعمیر.');
    }
}

/**
 * Map specialist unit team_code → assignment permission.
 */
function m360_ws_assign_permission_for_team(string $teamCode): ?string
{
    $teamCode = strtoupper(trim($teamCode));
    return match ($teamCode) {
        'MECHANICAL' => 'workshop.assign.mechanical',
        'ELECTRICAL' => 'workshop.assign.electrical',
        'OPTIONS' => 'workshop.assign.options',
        default => null,
    };
}

/**
 * Distinct specialty assignment permissions — never OR'd with generic mechanical/electrical.
 */
function m360_ws_assign_permission_for_family(string $family, string $specialty = ''): ?string
{
    require_once __DIR__ . '/m360-workshop-work-item-helper.php';
    return m360_ws_wi_assign_permission($family, $specialty);
}

/**
 * Map unit work board → view permission.
 */
function m360_ws_view_permission_for_unit(string $unitCode): ?string
{
    $unitCode = strtoupper(trim($unitCode));
    return match ($unitCode) {
        'MECHANICAL' => 'workshop.mechanical.view',
        'ELECTRICAL', 'OPTIONS' => 'workshop.electrical_options.view',
        default => null,
    };
}

/**
 * Strip / reject injected customer pricing fields for no-price service recording.
 *
 * @param array<string,mixed> $input
 * @return array{clean:array<string,mixed>,rejected:list<string>}
 */
function m360_ws_filter_price_fields(array $input): array
{
    $rejected = [];
    $clean = $input;
    foreach (M360_WS_PRICE_FIELD_KEYS as $k) {
        if (array_key_exists($k, $clean)) {
            $val = $clean[$k];
            unset($clean[$k]);
            if ($val !== null && $val !== '' && $val !== 0 && $val !== '0' && $val !== 0.0) {
                $rejected[] = $k;
            }
        }
    }
    return ['clean' => $clean, 'rejected' => $rejected];
}

/**
 * Reject request if non-empty price fields were injected.
 *
 * @param array<string,mixed> $input
 */
function m360_ws_reject_injected_prices(array $input): void
{
    $f = m360_ws_filter_price_fields($input);
    if ($f['rejected'] !== []) {
        m360_am_forbidden('ثبت مبلغ مشتری / مالی با این مجوز مجاز نیست. فیلدهای قیمت رد شدند.');
    }
}

/**
 * Diagnosis action → permission key (null = no matrix key / leave role gate).
 */
function m360_ws_diagnosis_permission_for_action(string $action): ?string
{
    $action = strtolower(trim($action));
    return match ($action) {
        'start_diagnosis', 'complete_diagnosis', 'save_diagnosis', 'submit_diagnosis' => 'workshop.diagnosis_report.create',
        'approve_diagnosis' => 'workshop.diagnosis_report.approve',
        'return_diagnosis', 'reject_diagnosis' => 'workshop.diagnosis_report.return',
        default => null,
    };
}

/**
 * Work-report / completion action → permission.
 */
function m360_ws_work_report_permission_for_action(string $action): ?string
{
    $action = strtolower(trim($action));
    return match ($action) {
        'save_completion_notes', 'complete_technical_work', 'complete_service_operation',
        'start_service_operation', 'start_work', 'move_to_work_queue' => 'workshop.work_report.create',
        'approve_work_report' => 'workshop.work_report.approve',
        'return_work_report' => 'workshop.work_report.return',
        default => null,
    };
}

/**
 * QC action → permission.
 */
function m360_ws_qc_permission_for_action(string $action): ?string
{
    $action = strtolower(trim($action));
    return match ($action) {
        'start_qc', 'save_checklist_item', 'save_final_inspection_notes', 'rework_completed' => 'workshop.qc.result.create',
        'qc_passed', 'qc_failed', 'rework_required' => 'workshop.qc.approve_return',
        default => null,
    };
}

/**
 * Part-request review decision → permission.
 */
function m360_ws_part_request_permission_for_decision(string $decision): string
{
    $decision = strtoupper(trim($decision));
    if (in_array($decision, ['REJECT', 'NEEDS_MORE_EVIDENCE'], true)) {
        return 'workshop.part_request.reject_return';
    }
    // Technical necessity / routing toward inventory (not warehouse stock issue).
    return 'workshop.part_request.technical_approve';
}

/**
 * Active technician must belong to company (via erp_company_users) and be login-enabled.
 */
function m360_ws_assert_technician_eligible($conn, int $technicianUserId, int $companyId): void
{
    if ($technicianUserId < 1) {
        m360_am_forbidden('تکنسین معتبر نیست.');
    }
    $row = m360_am_one(
        $conn,
        "SELECT TOP 1 u.user_id
         FROM dbo.core_users u
         INNER JOIN dbo.erp_company_users cu ON cu.user_id=u.user_id AND cu.company_id=? AND cu.is_active=1
         WHERE u.user_id=? AND u.lifecycle_state=N'ACTIVE' AND u.is_login_enabled=1",
        [$companyId, $technicianUserId]
    );
    if ($row === null) {
        m360_am_forbidden('تکنسین فعال در شرکت مجاز یافت نشد.');
    }
}
