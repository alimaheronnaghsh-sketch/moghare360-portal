<?php
declare(strict_types=1);

/**
 * Workshop work-item model + specialty assignment (periodic / inspection).
 * Extends erp_jobcard_assignments — does not replace the JobCard engine.
 */

require_once __DIR__ . '/m360-fulljob-lifecycle-helper.php';
require_once __DIR__ . '/m360-workshop-access-enforcement.php';

const M360_WS_SERVICE_FAMILIES = ['PERIODIC_SERVICE', 'INSPECTION', 'ELECTRICAL_OPTIONS', 'MECHANICAL'];

function m360_ws_wi_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_ws_wi_is_family(string $family): bool
{
    return in_array(strtoupper(trim($family)), M360_WS_SERVICE_FAMILIES, true);
}

function m360_ws_wi_family_label_fa(string $family): string
{
    return match (strtoupper(trim($family))) {
        'PERIODIC_SERVICE' => 'سرویس دوره‌ای',
        'INSPECTION' => 'کارشناسی',
        'ELECTRICAL_OPTIONS' => 'برق و آپشن',
        'MECHANICAL' => 'مکانیکی',
        default => $family,
    };
}

function m360_ws_wi_assign_permission(string $family, string $specialty = ''): ?string
{
    $family = strtoupper(trim($family));
    $specialty = strtoupper(trim($specialty));
    return match (true) {
        $family === 'PERIODIC_SERVICE' => 'workshop.assign.periodic_service',
        $family === 'INSPECTION' && $specialty === 'MECHANICAL' => 'workshop.assign.inspection_mechanical',
        $family === 'INSPECTION' && $specialty === 'ELECTRICAL' => 'workshop.assign.inspection_electrical',
        $family === 'MECHANICAL' => 'workshop.assign.mechanical',
        $family === 'ELECTRICAL_OPTIONS' && $specialty === 'ELECTRICAL' => 'workshop.assign.electrical',
        $family === 'ELECTRICAL_OPTIONS' && $specialty === 'OPTIONS' => 'workshop.assign.options',
        default => null,
    };
}

function m360_ws_wi_unit_for_family(string $family, string $specialty = ''): string
{
    $family = strtoupper(trim($family));
    $specialty = strtoupper(trim($specialty));
    if ($family === 'PERIODIC_SERVICE' || ($family === 'INSPECTION' && $specialty === 'MECHANICAL') || $family === 'MECHANICAL') {
        return 'MECHANICAL';
    }
    if ($family === 'INSPECTION' && $specialty === 'ELECTRICAL') {
        return 'ELECTRICAL';
    }
    if ($family === 'ELECTRICAL_OPTIONS') {
        return $specialty === 'OPTIONS' ? 'OPTIONS' : 'ELECTRICAL';
    }
    return 'MECHANICAL';
}

/**
 * @return array{ok:bool,work_item_id:int,message:string}
 */
function m360_ws_wi_ensure($conn, int $companyId, int $jobcardId, string $family, string $workItemType, string $specialty, int $actorUserId, string $title = ''): array
{
    $family = strtoupper(trim($family));
    $specialty = strtoupper(trim($specialty));
    $workItemType = strtoupper(trim($workItemType));
    if (!m360_ws_wi_is_family($family) || $jobcardId < 1) {
        return ['ok' => false, 'work_item_id' => 0, 'message' => 'آیتم کاری یا خانواده خدمات نامعتبر است.'];
    }
    $unit = m360_ws_wi_unit_for_family($family, $specialty);
    $existing = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 work_item_id FROM dbo.erp_workshop_work_items
         WHERE jobcard_id=? AND service_family=? AND ISNULL(specialty_code,N'')=? AND status IN (N'OPEN', N'ASSIGNED', N'IN_PROGRESS')
         ORDER BY work_item_id DESC",
        [$jobcardId, $family, $specialty]
    );
    if ($existing !== []) {
        return ['ok' => true, 'work_item_id' => (int)$existing[0]['work_item_id'], 'message' => 'آیتم کاری موجود است.'];
    }
    if ($title === '') {
        $title = m360_ws_wi_family_label_fa($family) . ($specialty !== '' ? (' — ' . $specialty) : '');
    }
    $ok = customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_workshop_work_items
            (company_id, jobcard_id, service_family, work_item_type, specialty_code, unit_code, title, status, created_by_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, N'OPEN', ?)",
        [$companyId, $jobcardId, $family, $workItemType !== '' ? $workItemType : $family, $specialty !== '' ? $specialty : null, $unit, $title, $actorUserId]
    );
    if ($ok === false) {
        return ['ok' => false, 'work_item_id' => 0, 'message' => 'ثبت آیتم کاری ناموفق بود.'];
    }
    $id = (int)(customer_core_scalar(
        $conn,
        "SELECT TOP 1 work_item_id FROM dbo.erp_workshop_work_items WHERE jobcard_id=? AND service_family=? AND ISNULL(specialty_code,N'')=? ORDER BY work_item_id DESC",
        [$jobcardId, $family, $specialty]
    ) ?? 0);
    return ['ok' => $id > 0, 'work_item_id' => $id, 'message' => $id > 0 ? 'آیتم کاری ایجاد شد.' : 'شناسه آیتم کاری یافت نشد.'];
}

function m360_ws_wi_fetch($conn, int $workItemId): ?array
{
    if ($workItemId < 1) {
        return null;
    }
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.erp_workshop_work_items WHERE work_item_id=?', [$workItemId]);
    return $rows[0] ?? null;
}

/**
 * @return list<array<string,mixed>>
 */
function m360_ws_wi_list_for_jobcard($conn, int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [];
    }
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.erp_workshop_work_items WHERE jobcard_id=? ORDER BY work_item_id',
        [$jobcardId]
    );
}

/**
 * Assign technician to a specific work item (not whole JobCard).
 *
 * @return array{ok:bool,message:string,assignment_id?:int,http_status?:int}
 */
function m360_ws_wi_assign_technician(
    $conn,
    int $workItemId,
    int $technicianUserId,
    int $actorUserId,
    int $companyId,
    string $priority = 'NORMAL',
    string $description = '',
    string $reassignReason = ''
): array {
    $wi = m360_ws_wi_fetch($conn, $workItemId);
    if ($wi === null) {
        return ['ok' => false, 'message' => 'آیتم کاری یافت نشد.', 'http_status' => 404];
    }
    $jobcardId = (int)$wi['jobcard_id'];
    $family = strtoupper((string)$wi['service_family']);
    $specialty = strtoupper(trim((string)($wi['specialty_code'] ?? '')));
    $perm = m360_ws_wi_assign_permission($family, $specialty);
    if ($perm === null) {
        return ['ok' => false, 'message' => 'مجوز تخصیص برای این خانواده خدمات تعریف نشده است.', 'http_status' => 403];
    }
    // Caller must already have called m360_ws_require($perm); re-check soft for defense-in-depth.
    if (!m360_am_is_owner($conn, $actorUserId) && !m360_am_effective_can($conn, $actorUserId, $companyId, $perm)) {
        return ['ok' => false, 'message' => 'مجوز تخصیص این آیتم کاری را ندارید.', 'http_status' => 403];
    }
    m360_ws_assert_technician_eligible($conn, $technicianUserId, $companyId);

    $unit = m360_ws_wi_unit_for_family($family, $specialty);
    $priority = in_array($priority, M360_FULLJOB_PRIORITIES, true) ? $priority : 'NORMAL';

    if (!m360_fulljob_tx_begin($conn)) {
        return ['ok' => false, 'message' => 'شروع تراکنش ناموفق بود.', 'http_status' => 500];
    }
    try {
        $active = customer_core_fetch_rows(
            $conn,
            "SELECT assignment_id, assigned_to_user_id
             FROM dbo.erp_jobcard_assignments WITH (UPDLOCK, HOLDLOCK)
             WHERE jobcard_id=? AND work_item_id=? AND assignment_type=N'TECHNICIAN_ASSIGNMENT' AND status=N'ACTIVE'",
            [$jobcardId, $workItemId]
        );
        foreach ($active as $row) {
            if ((int)($row['assigned_to_user_id'] ?? 0) === $technicianUserId) {
                m360_fulljob_tx_commit($conn);
                return [
                    'ok' => true,
                    'idempotent' => true,
                    'assignment_id' => (int)$row['assignment_id'],
                    'message' => 'این تکنسین قبلاً به این آیتم کاری تخصیص داده شده است.',
                    'http_status' => 409,
                ];
            }
        }
        if ($active !== []) {
            if (trim($reassignReason) === '') {
                m360_fulljob_tx_commit($conn);
                return [
                    'ok' => false,
                    'conflict' => true,
                    'message' => 'تکنسین فعال دیگری روی این آیتم وجود دارد. برای بازتخصیص، ذکر دلیل الزامی است.',
                    'http_status' => 409,
                ];
            }
            foreach ($active as $row) {
                customer_core_execute(
                    $conn,
                    "UPDATE dbo.erp_jobcard_assignments
                     SET status=N'CLOSED', closed_at=SYSUTCDATETIME(), updated_at=SYSUTCDATETIME(), reassign_reason=?
                     WHERE assignment_id=?",
                    [$reassignReason, (int)$row['assignment_id']]
                );
            }
        }

        $ok = customer_core_execute(
            $conn,
            "INSERT INTO dbo.erp_jobcard_assignments
                (jobcard_id, assignment_type, team_code, assigned_to_user_id, assigned_by_user_id, priority,
                 assignment_description, status, work_item_id, service_family, specialty_code)
             VALUES (?, N'TECHNICIAN_ASSIGNMENT', ?, ?, ?, ?, ?, N'ACTIVE', ?, ?, ?)",
            [
                $jobcardId, $unit, $technicianUserId, $actorUserId, $priority,
                $description, $workItemId, $family, $specialty !== '' ? $specialty : null,
            ]
        );
        if ($ok === false) {
            throw new RuntimeException('assign_insert_failed');
        }
        $assignmentId = (int)(customer_core_scalar(
            $conn,
            "SELECT TOP 1 assignment_id FROM dbo.erp_jobcard_assignments
             WHERE jobcard_id=? AND work_item_id=? AND status=N'ACTIVE' ORDER BY assignment_id DESC",
            [$jobcardId, $workItemId]
        ) ?? 0);
        customer_core_execute(
            $conn,
            "UPDATE dbo.erp_workshop_work_items
             SET status=N'ASSIGNED', assigned_technician_user_id=?, updated_at=SYSUTCDATETIME()
             WHERE work_item_id=?",
            [$technicianUserId, $workItemId]
        );
        m360_fulljob_tx_commit($conn);
        return [
            'ok' => true,
            'assignment_id' => $assignmentId,
            'message' => 'تخصیص آیتم کاری ثبت شد.',
            'http_status' => 200,
        ];
    } catch (Throwable $e) {
        m360_fulljob_tx_rollback($conn);
        return ['ok' => false, 'message' => 'ثبت تخصیص ناموفق بود.', 'http_status' => 500];
    }
}

/**
 * Filter work items for shared boards by permitted families.
 *
 * @param list<string> $allowedFamilies
 * @return list<array<string,mixed>>
 */
function m360_ws_wi_filter_rows(array $rows, array $allowedFamilies): array
{
    $allowed = array_map('strtoupper', $allowedFamilies);
    return array_values(array_filter($rows, static function (array $row) use ($allowed): bool {
        $fam = strtoupper(trim((string)($row['service_family'] ?? '')));
        return $fam === '' || in_array($fam, $allowed, true);
    }));
}

/**
 * Families the current user may view on shared boards.
 *
 * @return list<string>
 */
function m360_ws_wi_viewable_families_for_actor(): array
{
    $out = [];
    if (m360_ws_can('workshop.mechanical.view')) {
        $out[] = 'MECHANICAL';
    }
    if (m360_ws_can('workshop.periodic_service.view')) {
        $out[] = 'PERIODIC_SERVICE';
    }
    if (m360_ws_can('workshop.inspection.view')) {
        $out[] = 'INSPECTION';
    }
    if (m360_ws_can('workshop.electrical_options.view')) {
        $out[] = 'ELECTRICAL_OPTIONS';
    }
    return $out;
}
