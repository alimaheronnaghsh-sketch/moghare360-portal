<?php
declare(strict_types=1);

/**
 * MOGHARE360 P5 — Work execution after P4 approval.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-parts-finance-gate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-parts-consumption-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-technical-completion-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-technical-operation-helper.php';

const M360_WORK_CSRF = 'work_execution_p5';
const M360_WORK_HISTORY = 'erp_jobcard_change_history';
const M360_WORK_EVENTS = 'erp_work_execution_events';

const M360_WX_APPROVED = 'APPROVED_FOR_WORK';
const M360_WX_QUEUE = 'WORK_QUEUE';
const M360_WX_STARTED = 'WORK_STARTED';
const M360_WX_PARTS_PENDING = 'PARTS_CONSUMPTION_PENDING';
const M360_WX_PARTS_CONSUMED = 'PARTS_CONSUMED';
const M360_WX_SERVICE_PROGRESS = 'SERVICE_IN_PROGRESS';
const M360_WX_SERVICE_DONE = 'SERVICE_COMPLETED';
const M360_WX_TECH_REVIEW = 'TECHNICAL_COMPLETION_REVIEW';
const M360_WX_READY_QC = 'READY_FOR_QC';
const M360_WX_WAIT_PARTS = 'WAITING_FOR_PARTS';
const M360_WX_ON_HOLD = 'ON_HOLD';
const M360_WX_CANCELLED = 'CANCELLED';

/** @var array<string, string> */
const M360_WX_LABELS_FA = [
    M360_WX_APPROVED => 'مجاز برای کار',
    M360_WX_QUEUE => 'صف اجرا',
    M360_WX_STARTED => 'کار شروع شد',
    M360_WX_PARTS_PENDING => 'مصرف قطعه',
    M360_WX_PARTS_CONSUMED => 'قطعه مصرف شد',
    M360_WX_SERVICE_PROGRESS => 'سرویس در جریان',
    M360_WX_SERVICE_DONE => 'سرویس تکمیل',
    M360_WX_TECH_REVIEW => 'بازبینی تکمیل',
    M360_WX_READY_QC => 'آماده QC',
    M360_WX_WAIT_PARTS => 'انتظار قطعه',
    M360_WX_ON_HOLD => 'معلق',
    M360_WX_CANCELLED => 'لغو',
];

function m360_work_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_work_require_staff(): void
{
    erp_auth_context_start();
    if (erp_auth_current_user_id() === null || erp_auth_current_user_id() <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_work_status_label(string $s): string
{
    return M360_WX_LABELS_FA[strtoupper(trim($s))] ?? $s;
}

/** @return list<string> */
function m360_work_board_filters(): array
{
    return [
        M360_WX_APPROVED, M360_WX_QUEUE, M360_WX_STARTED, M360_WX_PARTS_PENDING, M360_WX_PARTS_CONSUMED,
        M360_WX_SERVICE_PROGRESS, M360_WX_SERVICE_DONE, M360_WX_TECH_REVIEW, M360_WX_READY_QC,
        M360_WX_WAIT_PARTS, M360_WX_ON_HOLD,
    ];
}

/** @param array<string, mixed> $row */
function m360_work_effective_status(array $row): string
{
    $wx = strtoupper(trim((string)($row['work_execution_status'] ?? '')));
    if ($wx !== '') {
        return $wx;
    }
    if (strtoupper((string)($row['estimate_status'] ?? '')) === M360_WX_APPROVED) {
        return M360_WX_APPROVED;
    }
    return '';
}

/** @param array<string, mixed> $row */
function m360_work_is_p4_approved(array $row): bool
{
    $est = strtoupper(trim((string)($row['estimate_status'] ?? '')));
    $wx = m360_work_effective_status($row);
    return $est === M360_WX_APPROVED || in_array($wx, [
        M360_WX_APPROVED, M360_WX_QUEUE, M360_WX_STARTED, M360_WX_PARTS_PENDING, M360_WX_PARTS_CONSUMED,
        M360_WX_SERVICE_PROGRESS, M360_WX_SERVICE_DONE, M360_WX_TECH_REVIEW, M360_WX_READY_QC, M360_WX_WAIT_PARTS,
    ], true);
}

/**
 * @param array<string, mixed> $row
 * @return array{ok:bool,message:string,block_event:?string}
 */
function m360_work_assert_gates($conn, int $jobcardId, array $row): array
{
    if (!function_exists('m360_contract_can_continue_to_p2')) {
        return ['ok' => false, 'message' => 'P1.5 Gate missing.', 'block_event' => null];
    }
    if (!m360_work_is_p4_approved($row)) {
        return ['ok' => false, 'message' => 'پرونده هنوز برای اجرای کار تأیید نشده است.', 'block_event' => 'JOBCARD_WORK_EXECUTION_BLOCKED_GATE'];
    }
    if (!m360_contract_can_continue_to_p2($jobcardId)) {
        return ['ok' => false, 'message' => 'قرارداد پذیرش معتبر نیست.', 'block_event' => 'JOBCARD_WORK_EXECUTION_BLOCKED_GATE'];
    }

    $est = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
    if ($est === null) {
        return ['ok' => false, 'message' => 'برآورد تأیید‌شده یافت نشد.', 'block_event' => 'JOBCARD_WORK_EXECUTION_BLOCKED_GATE'];
    }
    $estStatus = strtoupper((string)($est['estimate_status'] ?? ''));
    if ($estStatus === M360_EST_STATUS_APPROVED_WORK) {
        return ['ok' => true, 'message' => '', 'block_event' => null];
    }
    if (!in_array($estStatus, [M360_EST_STATUS_APPROVED, M360_EST_STATUS_PARTS_CLEARED, M360_EST_STATUS_FIN_CLEARED], true)) {
        return ['ok' => false, 'message' => 'تأیید مشتری برآورد معتبر نیست.', 'block_event' => 'JOBCARD_WORK_EXECUTION_BLOCKED_GATE'];
    }

    $parts = strtoupper((string)($est['parts_gate_status'] ?? $row['parts_gate_status'] ?? ''));
    $fin = strtoupper((string)($est['finance_gate_status'] ?? $row['finance_gate_status'] ?? ''));
    if (!in_array($parts, [M360_GATE_PARTS_CLEARED, M360_GATE_PARTS_NOT_REQUIRED], true)) {
        return ['ok' => false, 'message' => 'گیت قطعه باز نشده است.', 'block_event' => 'JOBCARD_WORK_EXECUTION_BLOCKED_GATE'];
    }
    if (!in_array($fin, [M360_GATE_FINANCE_CLEARED, M360_GATE_FINANCE_NOT_REQUIRED], true)) {
        return ['ok' => false, 'message' => 'گیت مالی باز نشده است.', 'block_event' => 'JOBCARD_WORK_EXECUTION_BLOCKED_GATE'];
    }

    return ['ok' => true, 'message' => '', 'block_event' => null];
}

function m360_work_write_history($conn, int $jobcardId, string $type, ?string $prev, ?string $new, string $summary, int $userId): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_WORK_HISTORY)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_WORK_HISTORY . ' (jobcard_id, change_type, previous_status, new_status, change_summary, changed_by_user_id) VALUES (?, ?, ?, ?, ?, ?)',
        [$jobcardId, $type, $prev, $new, $summary, $userId]
    );
}

function m360_work_write_event($conn, int $jobcardId, string $name, ?string $note, int $userId, ?int $soId = null): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_WORK_EVENTS)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_WORK_EVENTS . ' (jobcard_id, service_operation_id, event_name, event_note, created_by_user_id) VALUES (?, ?, ?, ?, ?)',
        [$jobcardId, $soId, $name, $note, $userId]
    );
}

/** @return array<string, mixed>|null */
function m360_work_fetch_jobcard($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return null;
    }
    $sql = 'SELECT TOP 1 j.*, c.full_name AS customer_name, c.primary_mobile AS customer_mobile,
                   v.plate_number, v.brand, v.model
            FROM dbo.erp_jobcards j
            LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
            LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
            WHERE j.jobcard_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    return $row === false ? null : $row;
}

/**
 * @return list<array<string, mixed>>
 */
function m360_work_board_list($conn, ?string $filter = null, int $limit = 150): array
{
    if (!is_resource($conn)) {
        return [];
    }
    $limit = max(1, min(300, $limit));
    $where = "(j.estimate_status = N'APPROVED_FOR_WORK' OR j.work_execution_status IS NOT NULL)";
    $params = [];
    if ($filter !== null && $filter !== '' && strtoupper($filter) !== 'ALL') {
        $f = strtoupper(trim($filter));
        if ($f === M360_WX_APPROVED) {
            $where .= " AND (j.work_execution_status IS NULL OR j.work_execution_status = N'APPROVED_FOR_WORK') AND j.estimate_status = N'APPROVED_FOR_WORK'";
        } else {
            $where .= ' AND j.work_execution_status = ?';
            $params[] = $f;
        }
    }
    $sql = 'SELECT TOP ' . $limit . ' j.jobcard_id, j.estimate_status, j.work_execution_status, j.parts_gate_status,
            j.finance_gate_status, j.parts_consumption_status, j.technical_status, j.assigned_technician_user_id,
            j.approved_for_work_at, j.ready_for_qc_at, c.full_name AS customer_name, c.primary_mobile AS customer_mobile,
            v.plate_number, v.brand, v.model
            FROM dbo.erp_jobcards j
            LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
            LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
            WHERE ' . $where . ' ORDER BY j.approved_for_work_at DESC, j.jobcard_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $row['work_status_label'] = m360_work_status_label(m360_work_effective_status($row));
        $vehicle = trim(trim((string)($row['brand'] ?? '')) . ' ' . trim((string)($row['model'] ?? '')));
        $row['vehicle_label'] = $vehicle !== '' ? $vehicle : '-';
        $rows[] = $row;
    }
    return $rows;
}

/**
 * @return array{ok:bool,message:string,new_status:?string}
 */
function m360_work_validate_action(string $status, string $action): array
{
    $s = strtoupper(trim($status));
    $a = strtolower(trim($action));
    if ($s === M360_WX_CANCELLED && $a !== 'save_completion_notes') {
        return ['ok' => false, 'message' => 'پرونده لغو شده است.', 'new_status' => null];
    }
    if ($s === M360_WX_READY_QC && !in_array($a, ['save_completion_notes'], true)) {
        return ['ok' => true, 'message' => 'پرونده آماده QC است.', 'new_status' => null];
    }

    switch ($a) {
        case 'move_to_work_queue':
            if (in_array($s, [M360_WX_APPROVED, M360_WX_QUEUE], true)) {
                return ['ok' => true, 'message' => $s === M360_WX_QUEUE ? 'در صف اجراست.' : '', 'new_status' => M360_WX_QUEUE];
            }
            return ['ok' => false, 'message' => 'انتقال به صف مجاز نیست.', 'new_status' => null];
        case 'start_work':
            if (in_array($s, [M360_WX_APPROVED, M360_WX_QUEUE, M360_WX_STARTED], true)) {
                return ['ok' => true, 'message' => $s === M360_WX_STARTED ? 'کار قبلاً شروع شده.' : '', 'new_status' => M360_WX_STARTED];
            }
            return ['ok' => false, 'message' => 'شروع کار مجاز نیست.', 'new_status' => null];
        case 'waiting_for_parts':
            return ['ok' => true, 'message' => '', 'new_status' => M360_WX_WAIT_PARTS];
        case 'save_completion_notes':
            return ['ok' => true, 'message' => '', 'new_status' => null];
        case 'complete_technical_work':
            return ['ok' => true, 'message' => '', 'new_status' => M360_WORK_STATUS_TECH_COMPLETED];
        case 'ready_for_qc':
            if ($s === M360_WX_READY_QC) {
                return ['ok' => true, 'message' => 'قبلاً آماده QC است.', 'new_status' => null];
            }
            return ['ok' => true, 'message' => '', 'new_status' => M360_WX_READY_QC];
        case 'hold':
            return ['ok' => true, 'message' => '', 'new_status' => M360_WX_ON_HOLD];
        case 'cancel':
            return ['ok' => true, 'message' => '', 'new_status' => M360_WX_CANCELLED];
        case 'start_service_operation':
        case 'complete_service_operation':
        case 'consume_approved_part':
            if (in_array($s, [M360_WX_STARTED, M360_WX_SERVICE_PROGRESS, M360_WX_PARTS_PENDING, M360_WX_PARTS_CONSUMED, M360_WX_SERVICE_DONE, M360_WX_WAIT_PARTS], true) || $s === M360_WX_APPROVED || $s === M360_WX_QUEUE) {
                return ['ok' => true, 'message' => '', 'new_status' => null];
            }
            return ['ok' => false, 'message' => 'ابتدا کار را شروع کنید.', 'new_status' => null];
        default:
            return ['ok' => false, 'message' => 'عملیات نامعتبر.', 'new_status' => null];
    }
}

/** @return array<string, string> */
function m360_work_history_map(): array
{
    return [
        'move_to_work_queue' => 'JOBCARD_WORK_QUEUE',
        'start_work' => 'JOBCARD_WORK_STARTED',
        'waiting_for_parts' => 'JOBCARD_WAITING_FOR_PARTS',
        'consume_approved_part' => 'JOBCARD_PART_CONSUMED',
        'save_completion_notes' => 'JOBCARD_TECHNICAL_COMPLETION_NOTES_SAVED',
        'complete_technical_work' => 'JOBCARD_TECHNICAL_WORK_COMPLETED',
        'ready_for_qc' => 'JOBCARD_READY_FOR_QC',
        'hold' => 'JOBCARD_WORK_EXECUTION_ON_HOLD',
        'cancel' => 'JOBCARD_WORK_EXECUTION_CANCELLED',
        'start_service_operation' => 'JOBCARD_SERVICE_OPERATION_EXECUTION_STARTED',
        'complete_service_operation' => 'JOBCARD_SERVICE_OPERATION_EXECUTION_COMPLETED',
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string}
 */
function m360_work_apply_action($conn, int $jobcardId, string $action, array $payload, int $userId): array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return ['ok' => false, 'message' => 'اتصال برقرار نشد.'];
    }
    $row = m360_work_fetch_jobcard($conn, $jobcardId);
    if ($row === null) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.'];
    }

    $gate = m360_work_assert_gates($conn, $jobcardId, $row);
    if (!$gate['ok']) {
        if ($gate['block_event'] !== null) {
            m360_work_write_history($conn, $jobcardId, $gate['block_event'], m360_work_effective_status($row), m360_work_effective_status($row), $gate['message'], $userId);
            m360_work_write_event($conn, $jobcardId, $gate['block_event'], $gate['message'], $userId);
        }
        return ['ok' => false, 'message' => $gate['message']];
    }

    $action = strtolower(trim($action));
    $current = m360_work_effective_status($row);
    $validation = m360_work_validate_action($current, $action);
    if (!$validation['ok']) {
        return ['ok' => false, 'message' => $validation['message']];
    }

    $newStatus = $validation['new_status'];
    $histMap = m360_work_history_map();
    $histType = $histMap[$action] ?? 'JOBCARD_WORK_ACTION';
    $res = null;

    if ($action === 'complete_service_operation') {
        $note = trim((string)($payload['operation_result_note'] ?? ''));
        if ($note === '') {
            return ['ok' => false, 'message' => 'نتیجه عملیات سرویس الزامی است.'];
        }
    }
    if ($action === 'complete_technical_work') {
        $row = m360_work_fetch_jobcard($conn, $jobcardId);
        $v = m360_technical_completion_validate($conn, $jobcardId, $row ?? []);
        if (!$v['ok']) {
            return $v;
        }
    }
    if ($action === 'ready_for_qc') {
        $row = m360_work_fetch_jobcard($conn, $jobcardId);
        $v = m360_technical_ready_for_qc_validate($conn, $jobcardId, $row ?? []);
        if (!$v['ok']) {
            return $v;
        }
    }

    if ($action === 'start_service_operation') {
        $soId = (int)($payload['service_operation_id'] ?? 0);
        if ($soId < 1) {
            return ['ok' => false, 'message' => 'شناسه عملیات سرویس الزامی است.'];
        }
        $res = m360_technical_update_service_operation_status($conn, $soId, $jobcardId, M360_SO_STATUS_STARTED, 'SERVICE_OPERATION_EXECUTION_STARTED', $userId);
        if (!$res['ok']) {
            m360_work_write_event($conn, $jobcardId, 'SERVICE_OPERATION_EXECUTION_BLOCKED', $res['message'], $userId, $soId);
            return $res;
        }
        $newStatus = M360_WX_SERVICE_PROGRESS;
        m360_work_write_history($conn, $jobcardId, $histType, $current, M360_WX_SERVICE_PROGRESS, 'SO started', $userId);
        m360_work_write_event($conn, $jobcardId, 'SERVICE_OPERATION_EXECUTION_STARTED', null, $userId, $soId);
    } elseif ($action === 'complete_service_operation') {
        $soId = (int)($payload['service_operation_id'] ?? 0);
        $note = trim((string)($payload['operation_result_note'] ?? ''));
        $res = m360_technical_update_service_operation_status($conn, $soId, $jobcardId, M360_SO_STATUS_COMPLETED, 'SERVICE_OPERATION_EXECUTION_COMPLETED', $userId);
        if (!$res['ok']) {
            return $res;
        }
        $newStatus = M360_WX_SERVICE_DONE;
        m360_work_write_history($conn, $jobcardId, $histType, $current, M360_WX_SERVICE_DONE, $note, $userId);
        m360_work_write_event($conn, $jobcardId, 'SERVICE_OPERATION_EXECUTION_COMPLETED', $note, $userId, $soId);
    } elseif ($action === 'consume_approved_part') {
        $itemId = (int)($payload['estimate_item_id'] ?? 0);
        $qty = (float)($payload['quantity'] ?? 0);
        $soId = (int)($payload['service_operation_id'] ?? 0) ?: null;
        $res = m360_parts_consume_approved($conn, $jobcardId, $itemId, $qty, $userId, $soId);
        if (!$res['ok']) {
            if ($res['message'] === 'INSUFFICIENT_STOCK') {
                m360_work_write_history($conn, $jobcardId, 'JOBCARD_PART_CONSUMPTION_BLOCKED_INSUFFICIENT_STOCK', $current, M360_WX_WAIT_PARTS, 'Insufficient stock', $userId);
                m360_work_write_event($conn, $jobcardId, 'PART_USAGE_CONSUMPTION_BLOCKED', 'Insufficient stock', $userId);
                if (customer_core_column_exists($conn, 'erp_jobcards', 'work_execution_status')) {
                    customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET work_execution_status = ?, parts_consumption_status = ? WHERE jobcard_id = ?', [M360_WX_WAIT_PARTS, 'BLOCKED', $jobcardId]);
                }
                return ['ok' => false, 'message' => 'موجودی کافی نیست — پرونده در انتظار قطعه.'];
            }
            return ['ok' => false, 'message' => $res['message']];
        }
        if ($res['consumed']) {
            m360_work_write_history($conn, $jobcardId, 'JOBCARD_PART_CONSUMED', $current, $current, 'Part consumed', $userId);
            m360_work_write_event($conn, $jobcardId, 'PART_USAGE_CONSUMED_FOR_JOBCARD', null, $userId);
            if (m360_parts_consumption_complete($conn, $jobcardId)) {
                $newStatus = M360_WX_PARTS_CONSUMED;
                if (customer_core_column_exists($conn, 'erp_jobcards', 'parts_consumption_status')) {
                    customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET parts_consumption_status = N\'CONSUMED\' WHERE jobcard_id = ?', [$jobcardId]);
                }
            } else {
                $newStatus = M360_WX_PARTS_PENDING;
            }
        }
    } else {
        $sets = [];
        $params = [];
        if ($action === 'start_work' && customer_core_column_exists($conn, 'erp_jobcards', 'work_started_at')) {
            $sets[] = 'work_started_at = SYSUTCDATETIME()';
        }
        if ($action === 'save_completion_notes') {
            $notes = trim((string)($payload['technical_completion_notes'] ?? ''));
            if ($notes === '') {
                return ['ok' => false, 'message' => 'یادداشت تکمیل خالی است.'];
            }
            if (customer_core_column_exists($conn, 'erp_jobcards', 'technical_completion_notes')) {
                $sets[] = 'technical_completion_notes = ?';
                $params[] = $notes;
            }
        }
        if ($action === 'complete_technical_work') {
            if (customer_core_column_exists($conn, 'erp_jobcards', 'work_completed_at')) {
                $sets[] = 'work_completed_at = SYSUTCDATETIME()';
            }
            if (customer_core_column_exists($conn, 'erp_jobcards', 'final_technician_user_id')) {
                $sets[] = 'final_technician_user_id = ?';
                $params[] = $userId;
            }
            if (customer_core_column_exists($conn, 'erp_jobcards', 'technical_status')) {
                $sets[] = "technical_status = N'TECHNICAL_DONE'";
            }
        }
        if ($action === 'ready_for_qc' && customer_core_column_exists($conn, 'erp_jobcards', 'ready_for_qc_at')) {
            $sets[] = 'ready_for_qc_at = SYSUTCDATETIME()';
            if (customer_core_column_exists($conn, 'erp_jobcards', 'jobcard_status')) {
                $sets[] = "jobcard_status = N'READY_FOR_QC'";
            }
        }
        if ($sets !== []) {
            if (customer_core_column_exists($conn, 'erp_jobcards', 'updated_at')) {
                $sets[] = 'updated_at = SYSUTCDATETIME()';
            }
            $params[] = $jobcardId;
            customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET ' . implode(', ', $sets) . ' WHERE jobcard_id = ?', $params);
        }
        $effectiveNew = $newStatus ?? $current;
        m360_work_write_history($conn, $jobcardId, $histType, $current, $effectiveNew, 'P5 work: ' . $action, $userId);
        m360_work_write_event($conn, $jobcardId, $histType, null, $userId);
    }

    if ($newStatus !== null && customer_core_column_exists($conn, 'erp_jobcards', 'work_execution_status')) {
        $sets = ['work_execution_status = ?'];
        $params = [$newStatus];
        if (customer_core_column_exists($conn, 'erp_jobcards', 'updated_at')) {
            $sets[] = 'updated_at = SYSUTCDATETIME()';
        }
        $params[] = $jobcardId;
        customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET ' . implode(', ', $sets) . ' WHERE jobcard_id = ?', $params);
    }

    $msg = $validation['message'] !== '' ? $validation['message'] : 'عملیات انجام شد.';
    if ($action === 'consume_approved_part' && isset($res) && ($res['message'] ?? '') !== '') {
        $msg = (string)$res['message'];
    }
    return ['ok' => true, 'message' => $msg];
}

/**
 * @param array<string, mixed> $row
 * @return list<string>
 */
function m360_work_allowed_actions(array $row, bool $gatesOk): array
{
    if (!$gatesOk) {
        return [];
    }
    $s = m360_work_effective_status($row);
    $actions = ['move_to_work_queue', 'start_work', 'start_service_operation', 'complete_service_operation', 'consume_approved_part', 'waiting_for_parts', 'save_completion_notes', 'complete_technical_work', 'ready_for_qc', 'hold', 'cancel'];
    $allowed = [];
    foreach ($actions as $a) {
        if (m360_work_validate_action($s, $a)['ok']) {
            $allowed[] = $a;
        }
    }
    return $allowed;
}

/**
 * @return list<array<string, mixed>>
 */
function m360_work_list_events($conn, int $jobcardId, int $limit = 80): array
{
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, M360_WORK_EVENTS)) {
        return [];
    }
    $limit = max(1, min(200, $limit));
    return customer_core_fetch_rows(
        $conn,
        'SELECT TOP ' . $limit . ' event_id, event_name, event_note, created_at, service_operation_id, created_by_user_id
         FROM dbo.' . M360_WORK_EVENTS . ' WHERE jobcard_id = ? ORDER BY event_id DESC',
        [$jobcardId]
    );
}

/**
 * @return list<array<string, mixed>>
 */
function m360_work_list_history($conn, int $jobcardId, int $limit = 80): array
{
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, M360_WORK_HISTORY)) {
        return [];
    }
    $limit = max(1, min(200, $limit));
    return customer_core_fetch_rows(
        $conn,
        'SELECT TOP ' . $limit . ' change_id, change_type, previous_status, new_status, change_summary, changed_at, changed_by_user_id
         FROM dbo.' . M360_WORK_HISTORY . ' WHERE jobcard_id = ? ORDER BY change_id DESC',
        [$jobcardId]
    );
}
