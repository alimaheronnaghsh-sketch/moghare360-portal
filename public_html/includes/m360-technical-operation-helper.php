<?php
declare(strict_types=1);

/**
 * MOGHARE360 P3 — Technical operation board and execution helper.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-technician-workflow-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-reception-jobcard-helper.php';

const M360_TECHNICAL_CSRF_PURPOSE = 'technical_operation_p3';
const M360_TECHNICAL_JOBCARD_HISTORY = 'erp_jobcard_change_history';
const M360_TECHNICAL_SERVICE_TABLE = 'erp_service_operations';
const M360_TECHNICAL_SERVICE_HISTORY = 'erp_service_operation_change_history';

const M360_SO_STATUS_CREATED = 'ASSIGNED';
const M360_SO_STATUS_STARTED = 'IN_PROGRESS';
const M360_SO_STATUS_COMPLETED = 'DONE';
const M360_SO_STATUS_CANCELLED = 'CANCELLED';

function m360_technical_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_technical_require_staff(): void
{
    erp_auth_context_start();
    $userId = erp_auth_current_user_id();
    if ($userId === null || $userId <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_technical_p15_gate_available(): bool
{
    return m360_reception_jobcard_p15_gate_available();
}

function m360_technical_contract_filter_codes(): array
{
    return ['ALL', 'SIGNED', 'OVERRIDDEN'];
}

function m360_technical_contract_filter_label(string $code): string
{
    $map = [
        'ALL' => 'همه',
        'SIGNED' => 'امضا شده',
        'OVERRIDDEN' => 'تأیید مدیریتی',
    ];
    return $map[strtoupper($code)] ?? $code;
}

/**
 * @param array<string, mixed> $row
 * @return array{ok:bool,message:string,block_event:?string}
 */
function m360_technical_assert_gates($conn, int $jobcardId, array $row): array
{
    if (!m360_technical_p15_gate_available()) {
        return ['ok' => false, 'message' => 'P1.5 Gate missing — عملیات فنی متوقف شد.', 'block_event' => null];
    }
    if (!m360_technician_workflow_is_p2_ready($row)) {
        return [
            'ok' => false,
            'message' => 'این پرونده هنوز از پذیرش برای عملیات فنی آزاد نشده است.',
            'block_event' => 'JOBCARD_TECHNICAL_ACTION_BLOCKED_NOT_READY',
        ];
    }
    if (!m360_contract_can_continue_to_p2($jobcardId)) {
        return [
            'ok' => false,
            'message' => 'قرارداد پذیرش هنوز امضا یا override معتبر نشده است.',
            'block_event' => 'JOBCARD_TECHNICAL_ACTION_BLOCKED_CONTRACT_GATE',
        ];
    }
    return ['ok' => true, 'message' => '', 'block_event' => null];
}

/**
 * @return list<array<string, mixed>>
 */
function m360_technical_list_jobcards($conn, ?string $statusFilter = null, ?string $contractFilter = null, int $limit = 150): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, 'erp_jobcards')) {
        return [];
    }

    $limit = max(1, min(500, $limit));
    $params = [];
    $where = "(j.jobcard_status = N'READY_FOR_TECHNICAL' OR (j.technical_status IS NOT NULL AND j.technical_status <> N''))";

    $hasTechStatus = customer_core_column_exists($conn, 'erp_jobcards', 'technical_status');

    if ($statusFilter !== null && $statusFilter !== '' && strtoupper($statusFilter) !== 'ALL') {
        $sf = strtoupper(trim($statusFilter));
        if ($sf === M360_TECH_STATUS_READY_FOR_TECHNICAL) {
            $where .= " AND j.jobcard_status = N'READY_FOR_TECHNICAL' AND (j.technical_status IS NULL OR j.technical_status = N'')";
        } elseif ($hasTechStatus) {
            $where .= ' AND j.technical_status = ?';
            $params[] = $sf;
        }
    }

    $contractFilter = strtoupper(trim((string)$contractFilter));
    if ($contractFilter === 'SIGNED') {
        $where .= " AND (j.contract_status = N'SIGNED' OR EXISTS (
            SELECT 1 FROM dbo.erp_intake_contracts ic WHERE ic.jobcard_id = j.jobcard_id AND ic.contract_status = N'SIGNED'
        ))";
    } elseif ($contractFilter === 'OVERRIDDEN') {
        $where .= " AND (j.contract_status = N'OVERRIDDEN' OR EXISTS (
            SELECT 1 FROM dbo.erp_intake_contracts ic
            WHERE ic.jobcard_id = j.jobcard_id AND ic.contract_status = N'OVERRIDDEN' AND ic.manager_override = 1
        ))";
    }

    $sql = 'SELECT TOP ' . $limit . '
            j.jobcard_id, j.jobcard_number, j.jobcard_status, j.technical_status,
            j.created_at, j.ready_for_technical_at, j.contract_status, j.contract_signed_at,
            j.assigned_technician_user_id, j.customer_complaint, j.reception_notes,
            j.initial_inspection_notes, j.technician_notes, j.diagnosis_summary,
            c.full_name AS customer_name, c.primary_mobile AS customer_mobile,
            v.plate_number, v.brand, v.model
        FROM dbo.erp_jobcards j
        LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
        LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
        WHERE ' . $where . '
        ORDER BY j.ready_for_technical_at DESC, j.created_at DESC, j.jobcard_id DESC';

    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return [];
    }

    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $rows[] = m360_technical_enrich_jobcard($conn, $row);
    }
    return $rows;
}

/** @param array<string, mixed> $row */
function m360_technical_enrich_jobcard($conn, array $row): array
{
    $jobcardId = (int)($row['jobcard_id'] ?? 0);
    $effective = m360_technician_workflow_effective_status($row);
    $row['effective_technical_status'] = $effective;
    $row['technical_status_label'] = m360_technician_workflow_status_label($effective);
    $row['reception_status_label'] = m360_jobcard_workflow_status_label((string)($row['jobcard_status'] ?? ''));
    $row['contract_summary'] = m360_reception_jobcard_contract_summary($conn, $jobcardId, $row);
    $vehicle = trim(trim((string)($row['brand'] ?? '')) . ' ' . trim((string)($row['model'] ?? '')));
    $row['vehicle_label'] = $vehicle !== '' ? $vehicle : '-';
    $row['last_technical_action'] = m360_technical_last_history_event($conn, $jobcardId);
    return $row;
}

function m360_technical_last_history_event($conn, int $jobcardId): string
{
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, M360_TECHNICAL_JOBCARD_HISTORY)) {
        return '-';
    }
    $sql = 'SELECT TOP 1 change_type FROM dbo.' . M360_TECHNICAL_JOBCARD_HISTORY . '
            WHERE jobcard_id = ?
            ORDER BY changed_at DESC, history_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return '-';
    }
    $row = odbc_fetch_array($stmt);
    return $row !== false ? (string)($row['change_type'] ?? '-') : '-';
}

/** @return array<string, mixed>|null */
function m360_technical_fetch_jobcard($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return null;
    }
    $sql = 'SELECT TOP 1 j.*, c.full_name AS customer_name, c.primary_mobile AS customer_mobile,
                   v.plate_number, v.brand, v.model, v.vin
            FROM dbo.erp_jobcards j
            LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
            LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
            WHERE j.jobcard_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    if ($row === false) {
        return null;
    }
    return m360_technical_enrich_jobcard($conn, $row);
}

/** @return list<array<string, mixed>> */
function m360_technical_jobcard_history($conn, int $jobcardId, int $limit = 60): array
{
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, M360_TECHNICAL_JOBCARD_HISTORY)) {
        return [];
    }
    $limit = max(1, min(200, $limit));
    $sql = 'SELECT TOP ' . $limit . ' history_id, change_type, previous_status, new_status, change_summary, changed_by_user_id, changed_at
            FROM dbo.' . M360_TECHNICAL_JOBCARD_HISTORY . '
            WHERE jobcard_id = ? ORDER BY changed_at DESC, history_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $rows[] = $row;
    }
    return $rows;
}

/** @return list<array<string, mixed>> */
function m360_technical_list_service_operations($conn, int $jobcardId, int $limit = 30): array
{
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, M360_TECHNICAL_SERVICE_TABLE)) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    $sql = 'SELECT TOP ' . $limit . ' service_operation_id, jobcard_id, service_title, service_description,
                   service_status, assigned_to_user_id, created_at, updated_at
            FROM dbo.' . M360_TECHNICAL_SERVICE_TABLE . '
            WHERE jobcard_id = ? AND is_active = 1
            ORDER BY service_operation_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $rows[] = $row;
    }
    return $rows;
}

/** @return list<array<string, mixed>> */
function m360_technical_service_operation_history($conn, int $jobcardId, int $limit = 40): array
{
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, M360_TECHNICAL_SERVICE_HISTORY)) {
        return [];
    }
    $limit = max(1, min(150, $limit));
    $sql = 'SELECT TOP ' . $limit . ' history_id, service_operation_id, action_code, old_status, new_status, changed_by_user_id, changed_at, change_note
            FROM dbo.' . M360_TECHNICAL_SERVICE_HISTORY . '
            WHERE jobcard_id = ? ORDER BY changed_at DESC, history_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $rows[] = $row;
    }
    return $rows;
}

function m360_technical_write_jobcard_history(
    $conn,
    int $jobcardId,
    string $changeType,
    ?string $previousStatus,
    ?string $newStatus,
    string $summary,
    int $userId
): void {
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_TECHNICAL_JOBCARD_HISTORY)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_TECHNICAL_JOBCARD_HISTORY . '
            (jobcard_id, change_type, previous_status, new_status, change_summary, changed_by_user_id)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$jobcardId, $changeType, $previousStatus, $newStatus, $summary, $userId]
    );
}

function m360_technical_write_service_history(
    $conn,
    int $serviceOperationId,
    int $jobcardId,
    string $actionCode,
    ?string $oldStatus,
    ?string $newStatus,
    string $note,
    int $userId
): void {
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_TECHNICAL_SERVICE_HISTORY)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_TECHNICAL_SERVICE_HISTORY . '
            (service_operation_id, jobcard_id, action_code, old_status, new_status, changed_by_user_id, change_note)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$serviceOperationId, $jobcardId, $actionCode, $oldStatus, $newStatus, $userId, $note]
    );
}

/**
 * @return array{ok:bool,message:string,service_operation_id:?int}
 */
function m360_technical_create_service_operation($conn, int $jobcardId, string $title, string $description, int $technicianUserId, int $userId): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_TECHNICAL_SERVICE_TABLE)) {
        return ['ok' => false, 'message' => 'جدول عملیات سرویس یافت نشد.', 'service_operation_id' => null];
    }
    $title = trim($title);
    if ($title === '') {
        return ['ok' => false, 'message' => 'عنوان عملیات سرویس الزامی است.', 'service_operation_id' => null];
    }
    $description = trim($description);

    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_TECHNICAL_SERVICE_TABLE . '
            (jobcard_id, service_title, service_description, assigned_to_user_id, service_status, created_by_user_id, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)',
        [
            $jobcardId,
            mb_substr($title, 0, 200),
            $description !== '' ? $description : null,
            $technicianUserId > 0 ? $technicianUserId : null,
            M360_SO_STATUS_CREATED,
            $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت عملیات سرویس ناموفق بود.', 'service_operation_id' => null];
    }

    $soId = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 service_operation_id FROM dbo.' . M360_TECHNICAL_SERVICE_TABLE . '
         WHERE jobcard_id = ? AND service_title = ? AND created_by_user_id = ?
         ORDER BY service_operation_id DESC',
        [$jobcardId, mb_substr($title, 0, 200), $userId]
    ) ?? 0);

    if ($soId < 1) {
        return ['ok' => false, 'message' => 'شناسه عملیات سرویس دریافت نشد.', 'service_operation_id' => null];
    }

    m360_technical_write_service_history(
        $conn,
        $soId,
        $jobcardId,
        'SERVICE_OPERATION_CREATED',
        null,
        M360_SO_STATUS_CREATED,
        'P3 service operation created',
        $userId
    );

    return ['ok' => true, 'message' => 'عملیات سرویس ثبت شد.', 'service_operation_id' => $soId];
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_technical_update_service_operation_status($conn, int $serviceOperationId, int $jobcardId, string $newStatus, string $historyAction, int $userId): array
{
    if (!is_resource($conn) || $serviceOperationId < 1) {
        return ['ok' => false, 'message' => 'عملیات سرویس نامعتبر است.'];
    }

    $row = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 service_operation_id, jobcard_id, service_status FROM dbo.' . M360_TECHNICAL_SERVICE_TABLE . '
         WHERE service_operation_id = ? AND is_active = 1',
        [$serviceOperationId]
    );
    if ($row === []) {
        return ['ok' => false, 'message' => 'عملیات سرویس یافت نشد.'];
    }
    $so = $row[0];
    if ((int)($so['jobcard_id'] ?? 0) !== $jobcardId) {
        return ['ok' => false, 'message' => 'عملیات سرویس به این کارت کار تعلق ندارد.'];
    }

    $oldStatus = (string)($so['service_status'] ?? '');
    if ($oldStatus === $newStatus) {
        return ['ok' => true, 'message' => 'وضعیت عملیات سرویس بدون تغییر است.'];
    }

    $sets = ['service_status = ?'];
    $params = [$newStatus];
    if (customer_core_column_exists($conn, M360_TECHNICAL_SERVICE_TABLE, 'updated_at')) {
        $sets[] = 'updated_at = SYSUTCDATETIME()';
    }
    $params[] = $serviceOperationId;

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_TECHNICAL_SERVICE_TABLE . ' SET ' . implode(', ', $sets) . ' WHERE service_operation_id = ?',
        $params
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'به‌روزرسانی عملیات سرویس ناموفق بود.'];
    }

    m360_technical_write_service_history(
        $conn,
        $serviceOperationId,
        $jobcardId,
        $historyAction,
        $oldStatus,
        $newStatus,
        'P3 service operation status change',
        $userId
    );

    return ['ok' => true, 'message' => 'وضعیت عملیات سرویس به‌روز شد.'];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string}
 */
function m360_technical_apply_action($conn, int $jobcardId, string $action, array $payload, int $userId): array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return ['ok' => false, 'message' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    $row = m360_technical_fetch_jobcard($conn, $jobcardId);
    if ($row === null) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.'];
    }

    $gate = m360_technical_assert_gates($conn, $jobcardId, $row);
    if (!$gate['ok']) {
        if ($gate['block_event'] !== null) {
            $effective = m360_technician_workflow_effective_status($row);
            m360_technical_write_jobcard_history(
                $conn,
                $jobcardId,
                $gate['block_event'],
                $effective,
                $effective,
                $gate['message'],
                $userId
            );
        }
        return ['ok' => false, 'message' => $gate['message']];
    }

    $action = strtolower(trim($action));
    $effective = m360_technician_workflow_effective_status($row);
    $validation = m360_technician_workflow_validate_action($effective, $action);
    if (!$validation['ok']) {
        return ['ok' => false, 'message' => $validation['message']];
    }

    if ($action === 'complete_diagnosis') {
        $summary = trim((string)($payload['diagnosis_summary'] ?? ''));
        if ($summary === '') {
            return ['ok' => false, 'message' => 'خلاصه عیب‌یابی الزامی است.'];
        }
    }

    if ($action === 'assign_technician') {
        $techId = (int)($payload['technician_user_id'] ?? 0);
        if ($techId < 1) {
            $techId = $userId;
        }
    } else {
        $techId = (int)($row['assigned_technician_user_id'] ?? 0);
    }

    $newTechStatus = $validation['new_status'];
    $historyType = m360_technician_workflow_history_event($action);
    $sets = [];
    $params = [];

    if ($newTechStatus !== null && customer_core_column_exists($conn, 'erp_jobcards', 'technical_status')) {
        $sets[] = 'technical_status = ?';
        $params[] = $newTechStatus;
    }

    if ($action === 'assign_technician' && customer_core_column_exists($conn, 'erp_jobcards', 'assigned_technician_user_id')) {
        $sets[] = 'assigned_technician_user_id = ?';
        $params[] = $techId > 0 ? $techId : $userId;
    }

    if ($action === 'start_diagnosis' && customer_core_column_exists($conn, 'erp_jobcards', 'diagnosis_started_at')) {
        $sets[] = 'diagnosis_started_at = SYSUTCDATETIME()';
    }
    if ($action === 'complete_diagnosis') {
        if (customer_core_column_exists($conn, 'erp_jobcards', 'diagnosis_completed_at')) {
            $sets[] = 'diagnosis_completed_at = SYSUTCDATETIME()';
        }
        if (customer_core_column_exists($conn, 'erp_jobcards', 'diagnosis_summary')) {
            $sets[] = 'diagnosis_summary = ?';
            $params[] = trim((string)($payload['diagnosis_summary'] ?? ''));
        }
    }
    if ($action === 'start_service_operation' && customer_core_column_exists($conn, 'erp_jobcards', 'technical_started_at')) {
        $sets[] = 'technical_started_at = SYSUTCDATETIME()';
    }
    if (in_array($action, ['technical_done', 'complete_service_operation'], true) && customer_core_column_exists($conn, 'erp_jobcards', 'technical_completed_at')) {
        if ($action === 'technical_done') {
            $sets[] = 'technical_completed_at = SYSUTCDATETIME()';
        }
    }

    if ($action === 'save_technician_notes') {
        $notes = trim((string)($payload['technician_notes'] ?? ''));
        if ($notes === '') {
            return ['ok' => false, 'message' => 'یادداشت تکنسین خالی است.'];
        }
        if (customer_core_column_exists($conn, 'erp_jobcards', 'technician_notes')) {
            $sets[] = 'technician_notes = ?';
            $params[] = $notes;
        }
    }

    if ($action === 'create_service_operation') {
        $title = trim((string)($payload['operation_title'] ?? ''));
        $desc = trim((string)($payload['operation_description'] ?? ''));
        $assignTech = (int)($payload['technician_user_id'] ?? 0);
        if ($assignTech < 1) {
            $assignTech = (int)($row['assigned_technician_user_id'] ?? 0) ?: $userId;
        }
        $so = m360_technical_create_service_operation($conn, $jobcardId, $title, $desc, $assignTech, $userId);
        if (!$so['ok']) {
            return $so;
        }
    }

    if ($action === 'start_service_operation') {
        $soId = (int)($payload['service_operation_id'] ?? 0);
        if ($soId < 1) {
            return ['ok' => false, 'message' => 'شناسه عملیات سرویس الزامی است.'];
        }
        $soRes = m360_technical_update_service_operation_status(
            $conn,
            $soId,
            $jobcardId,
            M360_SO_STATUS_STARTED,
            'SERVICE_OPERATION_STARTED',
            $userId
        );
        if (!$soRes['ok']) {
            return $soRes;
        }
    }

    if ($action === 'complete_service_operation') {
        $soId = (int)($payload['service_operation_id'] ?? 0);
        if ($soId < 1) {
            return ['ok' => false, 'message' => 'شناسه عملیات سرویس الزامی است.'];
        }
        $soRes = m360_technical_update_service_operation_status(
            $conn,
            $soId,
            $jobcardId,
            M360_SO_STATUS_COMPLETED,
            'SERVICE_OPERATION_COMPLETED',
            $userId
        );
        if (!$soRes['ok']) {
            return $soRes;
        }
        if (customer_core_column_exists($conn, 'erp_jobcards', 'technical_completed_at')) {
            $sets[] = 'technical_completed_at = SYSUTCDATETIME()';
        }
    }

    if ($sets !== []) {
        if (customer_core_column_exists($conn, 'erp_jobcards', 'updated_at')) {
            $sets[] = 'updated_at = SYSUTCDATETIME()';
        }
        $params[] = $jobcardId;
        $ok = customer_core_execute(
            $conn,
            'UPDATE dbo.erp_jobcards SET ' . implode(', ', $sets) . ' WHERE jobcard_id = ?',
            $params
        );
        if ($ok === false) {
            return ['ok' => false, 'message' => 'ذخیره تغییرات ناموفق بود.'];
        }
    }

    $effectiveNew = $newTechStatus ?? $effective;
    if ($action !== 'create_service_operation' || $newTechStatus !== null) {
        m360_technical_write_jobcard_history(
            $conn,
            $jobcardId,
            $historyType,
            $effective,
            $effectiveNew,
            'P3 technical action: ' . $action,
            $userId
        );
    } elseif ($action === 'create_service_operation') {
        m360_technical_write_jobcard_history(
            $conn,
            $jobcardId,
            $historyType,
            $effective,
            $effective,
            'P3 technical action: create_service_operation',
            $userId
        );
    }

    $msg = $validation['message'] !== '' ? $validation['message'] : 'عملیات با موفقیت انجام شد.';
    if ($action === 'create_service_operation' && isset($so) && $so['ok']) {
        $msg = $so['message'];
    }
    return ['ok' => true, 'message' => $msg];
}
