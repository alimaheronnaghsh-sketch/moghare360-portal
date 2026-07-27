<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-staff-home-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-qc-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-final-invoice-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-delivery-readiness-helper.php';

const M360_FULLJOB_REQUEST_TYPES = [
    'TECHNICAL_ADDITIONAL_WORK',
    'PARTS_MATERIALS_REQUISITION',
    'EXTERNAL_SERVICE_REQUEST',
    'CUSTOMER_CLARIFICATION_REQUEST',
    'WORK_HOLD_SAFETY_STOP',
];

const M360_FULLJOB_PRIORITIES = ['LOW', 'NORMAL', 'HIGH', 'URGENT', 'SAFETY_CRITICAL'];
const M360_FULLJOB_RISKS = ['NO_RISK', 'QUALITY_RISK', 'TIME_RISK', 'COST_RISK', 'SAFETY_RISK', 'LEGAL_RISK'];

function m360_fulljob_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_fulljob_request_type_label_fa(string $type): string
{
    return [
        'TECHNICAL_ADDITIONAL_WORK' => 'درخواست کار فنی اضافه',
        'PARTS_MATERIALS_REQUISITION' => 'درخواست قطعه / مواد',
        'EXTERNAL_SERVICE_REQUEST' => 'درخواست خدمت خارج از مجموعه',
        'CUSTOMER_CLARIFICATION_REQUEST' => 'درخواست شفاف‌سازی از مشتری',
        'WORK_HOLD_SAFETY_STOP' => 'توقف کار / توقف ایمنی',
    ][strtoupper(trim($type))] ?? $type;
}

function m360_fulljob_status_label_fa(string $status): string
{
    return [
        'UNDER_HALL_REVIEW' => 'در بررسی مدیر سالن',
        'NEEDS_MORE_EVIDENCE' => 'نیازمند شواهد بیشتر',
        'SENT_TO_INVENTORY' => 'ارسال‌شده به انبار',
        'SENT_TO_PURCHASE' => 'ارسال‌شده به خرید',
        'SENT_TO_CRM' => 'ارسال‌شده به CRM',
        'SENT_TO_CUSTOMER' => 'در انتظار پاسخ مشتری',
        'EXECUTION_BLOCKED' => 'اجرای کار مسدود است',
        'APPROVED' => 'تأیید شده',
        'REJECTED' => 'رد شده',
        'CLOSED' => 'بسته شده',
        'OPEN' => 'باز',
    ][strtoupper(trim($status))] ?? $status;
}

function m360_fulljob_priority_label_fa(string $priority): string
{
    return [
        'LOW' => 'کم',
        'NORMAL' => 'عادی',
        'HIGH' => 'بالا',
        'URGENT' => 'فوری',
        'SAFETY_CRITICAL' => 'بحرانی / ایمنی',
    ][strtoupper(trim($priority))] ?? $priority;
}

function m360_fulljob_risk_label_fa(string $risk): string
{
    return [
        'NO_RISK' => 'بدون ریسک',
        'QUALITY_RISK' => 'ریسک کیفیت',
        'TIME_RISK' => 'ریسک زمان',
        'COST_RISK' => 'ریسک هزینه',
        'SAFETY_RISK' => 'ریسک ایمنی',
        'LEGAL_RISK' => 'ریسک حقوقی',
    ][strtoupper(trim($risk))] ?? $risk;
}

function m360_fulljob_current_actor($conn): array
{
    erp_auth_context_start();
    $userId = (int)(erp_auth_current_user_id() ?? erp_auth_context_session_user_id() ?? 0);
    $companyId = (int)($_SESSION['erp_company_id'] ?? 1);
    $roleCode = $userId > 0 ? m360_staff_home_resolve_role_code($conn, $userId, $companyId) : 'UNKNOWN';

    return [
        'user_id' => $userId,
        'company_id' => $companyId,
        'role_code' => $roleCode,
        'is_staff' => $userId > 0,
    ];
}

function m360_fulljob_require_role($conn, array $allowedRoles): array
{
    $actor = m360_fulljob_current_actor($conn);
    if (empty($actor['is_staff'])) {
        header('Location: staff-login.php');
        exit;
    }
    if (!in_array((string)$actor['role_code'], $allowedRoles, true)) {
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>دسترسی مجاز نیست</title><link rel="stylesheet" href="assets/css/m360-staff-home.css"></head>';
        echo '<body class="m360-staff-page"><main class="m360-staff-wrap"><section class="m360-staff-hero">';
        echo '<h1>دسترسی مجاز نیست.</h1><p>این مسیر فقط برای نقش‌های مجاز تعریف شده است.</p>';
        echo '<p><a class="m360-staff-btn" href="erp-staff-home.php">بازگشت به میز کار</a></p>';
        echo '</section></main></body></html>';
        exit;
    }

    return $actor;
}

function m360_fulljob_role_can_hall(string $roleCode): bool
{
    return in_array($roleCode, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER'], true);
}

function m360_fulljob_role_can_technician(string $roleCode): bool
{
    return in_array($roleCode, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN'], true);
}

function m360_fulljob_fetch_jobcard($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 j.*, c.full_name AS customer_name, c.primary_mobile AS customer_mobile,
                v.plate_number, v.brand, v.model
         FROM dbo.erp_jobcards j
         LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
         LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
         WHERE j.jobcard_id = ?',
        [$jobcardId]
    );

    return $rows[0] ?? null;
}

function m360_fulljob_find_jobcard_by_request($conn, int $requestId): ?array
{
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_jobcards WHERE online_request_id = ? ORDER BY jobcard_id DESC',
        [$requestId]
    );

    return $rows[0] ?? null;
}

function m360_fulljob_hall_cartable($conn): array
{
    return customer_core_fetch_rows(
        $conn,
        "SELECT a.*, j.jobcard_number, j.online_request_id, j.customer_id, j.vehicle_id,
                c.full_name AS customer_name, v.plate_number, v.brand, v.model
         FROM dbo.erp_jobcard_assignments a
         INNER JOIN dbo.erp_jobcards j ON j.jobcard_id = a.jobcard_id
         LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
         LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
         WHERE a.assignment_type = N'HALL_INTAKE' AND a.status = N'ACTIVE'
         ORDER BY a.assignment_id DESC"
    );
}

function m360_fulljob_list_assignments($conn, int $jobcardId): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.erp_jobcard_assignments WHERE jobcard_id = ? ORDER BY assignment_id DESC',
        [$jobcardId]
    );
}

function m360_fulljob_list_requests($conn, int $jobcardId): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.erp_jobcard_technical_requests WHERE jobcard_id = ? ORDER BY technical_request_id DESC',
        [$jobcardId]
    );
}

function m360_fulljob_list_requests_by_status($conn, array $statuses): array
{
    if ($statuses === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    return customer_core_fetch_rows(
        $conn,
        "SELECT tr.*, j.jobcard_number
         FROM dbo.erp_jobcard_technical_requests tr
         INNER JOIN dbo.erp_jobcards j ON j.jobcard_id = tr.jobcard_id
         WHERE tr.status IN ($placeholders)
         ORDER BY tr.technical_request_id DESC",
        $statuses
    );
}

function m360_fulljob_fetch_request($conn, int $technicalRequestId): ?array
{
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_jobcard_technical_requests WHERE technical_request_id = ?',
        [$technicalRequestId]
    );

    return $rows[0] ?? null;
}

function m360_fulljob_request_events($conn, int $technicalRequestId): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.erp_jobcard_technical_request_events WHERE technical_request_id = ? ORDER BY event_id DESC',
        [$technicalRequestId]
    );
}

function m360_fulljob_record_request_event(
    $conn,
    int $technicalRequestId,
    int $jobcardId,
    string $eventName,
    ?string $oldStatus,
    ?string $newStatus,
    ?string $note,
    array $actor,
    array $metadata = []
): void {
    $encoded = $metadata !== [] ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_jobcard_technical_request_events
            (technical_request_id, jobcard_id, event_name, old_status, new_status, event_note, actor_user_id, actor_role, event_metadata_json)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $technicalRequestId,
            $jobcardId,
            $eventName,
            $oldStatus,
            $newStatus,
            $note,
            (int)($actor['user_id'] ?? 0) ?: null,
            (string)($actor['role_code'] ?? ''),
            $encoded,
        ]
    );
}

function m360_fulljob_ensure_hall_cartable($conn, int $jobcardId, int $actorUserId): array
{
    $existing = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 assignment_id FROM dbo.erp_jobcard_assignments
         WHERE jobcard_id = ? AND assignment_type = N'HALL_INTAKE' AND status = N'ACTIVE'
         ORDER BY assignment_id DESC",
        [$jobcardId]
    );
    if ($existing !== []) {
        return ['ok' => true, 'assignment_id' => (int)$existing[0]['assignment_id'], 'created' => false];
    }

    $ok = customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_jobcard_assignments
            (jobcard_id, assignment_type, assigned_by_user_id, priority, assignment_description)
         VALUES (?, N'HALL_INTAKE', ?, N'NORMAL', N'ارجاع به صف مدیر سالن پس از آماده شدن پرونده.')",
        [$jobcardId, $actorUserId]
    );
    if ($ok === false) {
        return ['ok' => false, 'assignment_id' => 0, 'created' => false];
    }
    $assignmentId = (int)(customer_core_scalar($conn, 'SELECT TOP 1 assignment_id FROM dbo.erp_jobcard_assignments WHERE jobcard_id = ? ORDER BY assignment_id DESC', [$jobcardId]) ?? 0);
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_jobcards
         SET jobcard_status = N'HALL_REVIEW',
             technical_status = N'HALL_REVIEW',
             ready_for_technical_at = COALESCE(ready_for_technical_at, SYSUTCDATETIME()),
             updated_at = SYSUTCDATETIME()
         WHERE jobcard_id = ?",
        [$jobcardId]
    );

    return ['ok' => true, 'assignment_id' => $assignmentId, 'created' => true];
}

function m360_fulljob_assign_team($conn, int $jobcardId, string $teamCode, int $actorUserId, string $description = ''): array
{
    $teamCode = strtoupper(trim($teamCode));
    if (!in_array($teamCode, ['MECHANICAL', 'ELECTRICAL'], true)) {
        return ['ok' => false, 'message' => 'نوع تیم نامعتبر است.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_jobcard_assignments
         SET status = N'CLOSED', closed_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME()
         WHERE jobcard_id = ? AND assignment_type = N'TEAM_ASSIGNMENT' AND status = N'ACTIVE'",
        [$jobcardId]
    );
    $ok = customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_jobcard_assignments
            (jobcard_id, assignment_type, team_code, assigned_by_user_id, priority, assignment_description)
         VALUES (?, N'TEAM_ASSIGNMENT', ?, ?, N'HIGH', ?)",
        [$jobcardId, $teamCode, $actorUserId, $description !== '' ? $description : 'Hall manager team assignment']
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت تیم ناموفق بود.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_jobcards
         SET assigned_team_id = CASE WHEN ? = N'MECHANICAL' THEN 10 ELSE 20 END,
             technical_status = N'TEAM_ASSIGNED',
             updated_at = SYSUTCDATETIME()
         WHERE jobcard_id = ?",
        [$teamCode, $jobcardId]
    );

    return ['ok' => true, 'message' => 'تیم ثبت شد.'];
}

function m360_fulljob_assign_technician(
    $conn,
    int $jobcardId,
    int $technicianUserId,
    ?int $assistantUserId,
    int $actorUserId,
    string $priority,
    string $description
): array {
    $priority = in_array($priority, M360_FULLJOB_PRIORITIES, true) ? $priority : 'NORMAL';
    if ($technicianUserId < 1) {
        return ['ok' => false, 'message' => 'تکنسین معتبر نیست.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_jobcard_assignments
         SET status = N'CLOSED', closed_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME()
         WHERE jobcard_id = ? AND assignment_type = N'TECHNICIAN_ASSIGNMENT' AND status = N'ACTIVE'",
        [$jobcardId]
    );
    $ok = customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_jobcard_assignments
            (jobcard_id, assignment_type, assigned_to_user_id, assistant_user_id, assigned_by_user_id, priority, due_at, assignment_description)
         VALUES (?, N'TECHNICIAN_ASSIGNMENT', ?, ?, ?, ?, DATEADD(hour, 6, SYSUTCDATETIME()), ?)",
        [$jobcardId, $technicianUserId, $assistantUserId, $actorUserId, $priority, $description]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت تکنسین ناموفق بود.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_jobcards
         SET assigned_technician_user_id = ?,
             technical_status = N'TECHNICIAN_ASSIGNED',
             updated_at = SYSUTCDATETIME()
         WHERE jobcard_id = ?",
        [$technicianUserId, $jobcardId]
    );

    return ['ok' => true, 'message' => 'تکنسین ثبت شد.'];
}

function m360_fulljob_assigned_technician_jobs($conn, array $actor): array
{
    $roleCode = (string)($actor['role_code'] ?? '');
    if (m360_fulljob_role_can_hall($roleCode)) {
        return customer_core_fetch_rows(
            $conn,
            "SELECT a.*, j.jobcard_number, j.customer_id, j.vehicle_id, j.technical_status, j.work_execution_status,
                    c.full_name AS customer_name, v.plate_number
             FROM dbo.erp_jobcard_assignments a
             INNER JOIN dbo.erp_jobcards j ON j.jobcard_id = a.jobcard_id
             LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
             LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
             WHERE a.assignment_type = N'TECHNICIAN_ASSIGNMENT' AND a.status = N'ACTIVE'
             ORDER BY a.assignment_id DESC"
        );
    }

    return customer_core_fetch_rows(
        $conn,
        "SELECT a.*, j.jobcard_number, j.customer_id, j.vehicle_id, j.technical_status, j.work_execution_status,
                c.full_name AS customer_name, v.plate_number
         FROM dbo.erp_jobcard_assignments a
         INNER JOIN dbo.erp_jobcards j ON j.jobcard_id = a.jobcard_id
         LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
         LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
         WHERE a.assignment_type = N'TECHNICIAN_ASSIGNMENT'
           AND a.status = N'ACTIVE'
           AND (a.assigned_to_user_id = ? OR a.assistant_user_id = ?)
         ORDER BY a.assignment_id DESC",
        [(int)$actor['user_id'], (int)$actor['user_id']]
    );
}

function m360_fulljob_technician_can_open($conn, int $jobcardId, array $actor): bool
{
    if (m360_fulljob_role_can_hall((string)$actor['role_code'])) {
        return true;
    }
    $count = (int)(customer_core_scalar(
        $conn,
        "SELECT COUNT(*) FROM dbo.erp_jobcard_assignments
         WHERE jobcard_id = ? AND assignment_type = N'TECHNICIAN_ASSIGNMENT' AND status = N'ACTIVE'
           AND (assigned_to_user_id = ? OR assistant_user_id = ?)",
        [$jobcardId, (int)$actor['user_id'], (int)$actor['user_id']]
    ) ?? 0);

    return $count > 0;
}

function m360_fulljob_create_request($conn, int $jobcardId, array $input, array $actor): array
{
    if (!m360_fulljob_technician_can_open($conn, $jobcardId, $actor)) {
        return ['ok' => false, 'message' => 'این کاربر مجاز به ثبت درخواست برای این JobCard نیست.', 'technical_request_id' => 0];
    }
    $requestType = strtoupper(trim((string)($input['request_type'] ?? '')));
    if (!in_array($requestType, M360_FULLJOB_REQUEST_TYPES, true)) {
        return ['ok' => false, 'message' => 'نوع درخواست معتبر نیست.', 'technical_request_id' => 0];
    }
    $title = trim((string)($input['title'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    if ($title === '' || $description === '') {
        return ['ok' => false, 'message' => 'عنوان و شرح درخواست الزامی است.', 'technical_request_id' => 0];
    }
    $priority = strtoupper(trim((string)($input['priority'] ?? 'NORMAL')));
    $risk = strtoupper(trim((string)($input['risk_level'] ?? 'NO_RISK')));
    $priority = in_array($priority, M360_FULLJOB_PRIORITIES, true) ? $priority : 'NORMAL';
    $risk = in_array($risk, M360_FULLJOB_RISKS, true) ? $risk : 'NO_RISK';
    $uid = trim((string)($input['request_uid'] ?? ''));
    if ($uid === '') {
        $uid = hash('sha256', $jobcardId . '|' . $requestType . '|' . $title . '|' . (string)$actor['user_id']);
    }
    $existing = customer_core_fetch_rows($conn, 'SELECT TOP 1 technical_request_id FROM dbo.erp_jobcard_technical_requests WHERE request_uid = ?', [$uid]);
    if ($existing !== []) {
        return ['ok' => true, 'message' => 'درخواست قبلاً ثبت شده است.', 'technical_request_id' => (int)$existing[0]['technical_request_id']];
    }
    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_jobcard_technical_requests
            (request_uid, jobcard_id, request_type, requested_by_user_id, requested_by_role, assigned_operation_id,
             title, description, priority, risk_level, evidence_files, estimated_cost_impact, estimated_time_impact_minutes,
             requires_customer_approval, requires_part, requires_external_service, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $uid,
            $jobcardId,
            $requestType,
            (int)$actor['user_id'],
            (string)$actor['role_code'],
            (int)($input['assigned_operation_id'] ?? 0) > 0 ? (int)$input['assigned_operation_id'] : null,
            mb_substr($title, 0, 300),
            $description,
            $priority,
            $risk,
            trim((string)($input['evidence_files'] ?? '')) ?: null,
            (float)($input['estimated_cost_impact'] ?? 0),
            (int)($input['estimated_time_impact_minutes'] ?? 0),
            !empty($input['requires_customer_approval']) ? 1 : 0,
            !empty($input['requires_part']) ? 1 : 0,
            !empty($input['requires_external_service']) ? 1 : 0,
            'UNDER_HALL_REVIEW',
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت درخواست ناموفق بود.', 'technical_request_id' => 0];
    }
    $technicalRequestId = (int)(customer_core_scalar($conn, 'SELECT TOP 1 technical_request_id FROM dbo.erp_jobcard_technical_requests WHERE request_uid = ?', [$uid]) ?? 0);
    m360_fulljob_record_request_event($conn, $technicalRequestId, $jobcardId, 'TECH_REQUEST_SUBMITTED', 'DRAFT', 'UNDER_HALL_REVIEW', $title, $actor);

    return ['ok' => true, 'message' => 'درخواست ثبت شد.', 'technical_request_id' => $technicalRequestId];
}

function m360_fulljob_review_request($conn, int $technicalRequestId, string $decision, string $note, array $actor): array
{
    if (!m360_fulljob_role_can_hall((string)$actor['role_code'])) {
        return ['ok' => false, 'message' => 'این نقش مجاز به بررسی درخواست نیست.'];
    }
    $request = m360_fulljob_fetch_request($conn, $technicalRequestId);
    if ($request === null) {
        return ['ok' => false, 'message' => 'درخواست یافت نشد.'];
    }
    if ((int)$request['requested_by_user_id'] === (int)$actor['user_id'] && (string)$actor['role_code'] === 'TECHNICIAN') {
        return ['ok' => false, 'message' => 'تکنسین نمی‌تواند درخواست خودش را تأیید کند.'];
    }
    $decision = strtoupper(trim($decision));
    $note = trim($note);
    if ($note === '') {
        return ['ok' => false, 'message' => 'یادداشت بررسی درخواست برای این اقدام الزامی است.'];
    }
    $statusMap = [
        'APPROVE_ESTIMATE_REVISION' => 'APPROVED_FOR_ESTIMATE_REVISION',
        'REJECT' => 'REJECTED_BY_HALL',
        'NEEDS_MORE_EVIDENCE' => 'NEEDS_MORE_EVIDENCE',
        'ROUTE_OTHER_TEAM' => 'ROUTED_TO_OTHER_TEAM',
        'SEND_INVENTORY' => 'SENT_TO_INVENTORY',
        'SEND_PURCHASE' => 'SENT_TO_PURCHASE',
        'SEND_CRM' => 'SENT_TO_CRM',
        'SEND_CUSTOMER' => 'SENT_TO_CUSTOMER',
        'ISSUE_HOLD' => 'EXECUTION_BLOCKED',
        'ALLOW_EXECUTION' => 'EXECUTION_ALLOWED',
        'CLOSE' => 'CLOSED',
    ];
    if (!isset($statusMap[$decision])) {
        return ['ok' => false, 'message' => 'تصمیم معتبر نیست.'];
    }
    $oldStatus = (string)($request['status'] ?? '');
    $newStatus = $statusMap[$decision];
    $jobcardId = (int)$request['jobcard_id'];
    customer_core_execute(
        $conn,
        'UPDATE dbo.erp_jobcard_technical_requests
         SET status = ?, reviewed_by_user_id = ?, review_decision = ?, review_note = ?, reviewed_at = SYSUTCDATETIME(),
             closed_at = CASE WHEN ? IN (N\'REJECTED_BY_HALL\', N\'CLOSED\') THEN SYSUTCDATETIME() ELSE closed_at END,
             updated_at = SYSUTCDATETIME()
         WHERE technical_request_id = ?',
        [$newStatus, (int)$actor['user_id'], $decision, $note, $newStatus, $technicalRequestId]
    );
    if ($decision === 'ISSUE_HOLD') {
        customer_core_execute(
            $conn,
            "UPDATE dbo.erp_jobcards
             SET work_execution_status = N'ON_HOLD', technical_status = N'WORK_HOLD', updated_at = SYSUTCDATETIME()
             WHERE jobcard_id = ?",
            [$jobcardId]
        );
    }
    if ($decision === 'ALLOW_EXECUTION') {
        customer_core_execute(
            $conn,
            "UPDATE dbo.erp_jobcards
             SET work_execution_status = N'WORK_QUEUE', updated_at = SYSUTCDATETIME()
             WHERE jobcard_id = ?",
            [$jobcardId]
        );
    }
    m360_fulljob_record_request_event($conn, $technicalRequestId, $jobcardId, 'HALL_REVIEW_' . $decision, $oldStatus, $newStatus, $note, $actor);

    return ['ok' => true, 'message' => 'بررسی درخواست ثبت شد.', 'status' => $newStatus];
}

function m360_fulljob_ensure_service_operation($conn, int $jobcardId, int $technicianUserId, int $actorUserId): int
{
    $existing = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 service_operation_id FROM dbo.erp_service_operations
         WHERE jobcard_id = ? AND is_active = 1
         ORDER BY service_operation_id DESC",
        [$jobcardId]
    );
    if ($existing !== []) {
        return (int)$existing[0]['service_operation_id'];
    }
    customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_service_operations
            (jobcard_id, service_title, service_description, assigned_to_user_id, service_status, created_by_user_id, is_active)
         VALUES (?, N'AUTO-UAT-FULLJOB technical operation', N'Synthetic full job lifecycle operation', ?, N'IN_PROGRESS', ?, 1)",
        [$jobcardId, $technicianUserId, $actorUserId]
    );

    return (int)(customer_core_scalar($conn, 'SELECT TOP 1 service_operation_id FROM dbo.erp_service_operations WHERE jobcard_id = ? ORDER BY service_operation_id DESC', [$jobcardId]) ?? 0);
}

function m360_fulljob_create_external_service($conn, int $jobcardId, int $technicalRequestId, int $actorUserId): int
{
    $existing = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 external_service_request_id FROM dbo.erp_external_service_requests WHERE technical_request_id = ? ORDER BY external_service_request_id DESC',
        [$technicalRequestId]
    );
    if ($existing !== []) {
        return (int)$existing[0]['external_service_request_id'];
    }
    $custody = json_encode([
        'synthetic_case' => 'AUTO-UAT-FULLJOB-20260716',
        'vehicle_or_part_release_requires_audit' => true,
        'handoff' => 'Hall manager approved external service request; physical send-out pending customer approval and dispatch audit',
    ], JSON_UNESCAPED_UNICODE);
    customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_external_service_requests
            (technical_request_id, jobcard_id, vendor_name, service_title, vehicle_component, send_out_at,
             responsible_user_id, expected_return_at, estimated_cost, requires_customer_approval,
             chain_of_custody_json, status, created_by_user_id)
         VALUES (?, ?, N'AUTO-UAT external vendor', N'خدمت خارج از مجموعه', N'AUTO-UAT component',
                 NULL, ?, DATEADD(day, 1, SYSUTCDATETIME()), 2500000, 1, ?, N'APPROVED', ?)",
        [$technicalRequestId, $jobcardId, $actorUserId, $custody, $actorUserId]
    );

    return (int)(customer_core_scalar($conn, 'SELECT TOP 1 external_service_request_id FROM dbo.erp_external_service_requests WHERE technical_request_id = ? ORDER BY external_service_request_id DESC', [$technicalRequestId]) ?? 0);
}

function m360_fulljob_list_external_services($conn, int $jobcardId): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.erp_external_service_requests WHERE jobcard_id = ? ORDER BY external_service_request_id DESC',
        [$jobcardId]
    );
}

function m360_fulljob_request_counts($conn, int $jobcardId): array
{
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT status, COUNT(*) AS c FROM dbo.erp_jobcard_technical_requests WHERE jobcard_id = ? GROUP BY status',
        [$jobcardId]
    );
    $counts = [];
    foreach ($rows as $row) {
        $counts[(string)$row['status']] = (int)$row['c'];
    }

    return $counts;
}

function m360_fulljob_open_request_count($conn, int $jobcardId): int
{
    return (int)(customer_core_scalar(
        $conn,
        "SELECT COUNT(*) FROM dbo.erp_jobcard_technical_requests
         WHERE jobcard_id = ? AND status NOT IN (N'CLOSED', N'REJECTED_BY_HALL', N'CUSTOMER_REJECTED')",
        [$jobcardId]
    ) ?? 0);
}

function m360_fulljob_customer_approval_blocked($conn, int $jobcardId): array
{
    $estimate = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
    if ($estimate === null) {
        return ['ok' => false, 'message' => 'برآورد فعال یافت نشد.'];
    }
    $status = strtoupper((string)($estimate['estimate_status'] ?? ''));
    if (in_array($status, [M360_EST_STATUS_APPROVED, M360_EST_STATUS_APPROVED_WORK], true)) {
        return ['ok' => true, 'message' => 'برآورد تأیید شده است.'];
    }

    return ['ok' => false, 'message' => 'تا تأیید مشتری، اقدام پرسنل به‌جای مشتری مجاز نیست.'];
}

