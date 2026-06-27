<?php
declare(strict_types=1);

/**
 * MOGHARE360 P6 — QC workflow after P5 READY_FOR_QC.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-work-execution-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-final-inspection-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-delivery-readiness-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-parts-consumption-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-technical-operation-helper.php';

const M360_QC_CSRF = 'qc_inspection_p6';
const M360_QC_HISTORY = 'erp_jobcard_change_history';
const M360_QC_EVENTS_TABLE = 'erp_qc_events';
const M360_QC_CHECKS_TABLE = 'erp_qc_checks';
const M360_QC_MEDIA_TABLE = 'erp_qc_media_events';

const M360_QC_READY = 'READY_FOR_QC';
const M360_QC_IN_PROGRESS = 'QC_IN_PROGRESS';
const M360_QC_FAILED = 'QC_FAILED';
const M360_QC_REWORK_REQUIRED = 'REWORK_REQUIRED';
const M360_QC_REWORK_IN_PROGRESS = 'REWORK_IN_PROGRESS';
const M360_QC_REWORK_COMPLETED = 'REWORK_COMPLETED';
const M360_QC_PASSED = 'QC_PASSED';
const M360_QC_DELIVERY_READY = 'DELIVERY_READY';
const M360_QC_ON_HOLD = 'ON_HOLD';
const M360_QC_CANCELLED = 'CANCELLED';

/** @var array<string, string> */
const M360_QC_LABELS_FA = [
    M360_QC_READY => 'آماده QC',
    M360_QC_IN_PROGRESS => 'QC در جریان',
    M360_QC_FAILED => 'QC رد شد',
    M360_QC_REWORK_REQUIRED => 'نیاز به Rework',
    M360_QC_REWORK_IN_PROGRESS => 'Rework در جریان',
    M360_QC_REWORK_COMPLETED => 'Rework تکمیل',
    M360_QC_PASSED => 'QC قبول',
    M360_QC_DELIVERY_READY => 'آماده تحویل',
    M360_QC_ON_HOLD => 'معلق',
    M360_QC_CANCELLED => 'لغو',
];

function m360_qc_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_qc_require_staff(): void
{
    erp_auth_context_start();
    if (erp_auth_current_user_id() === null || erp_auth_current_user_id() <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_qc_status_label(string $s): string
{
    return M360_QC_LABELS_FA[strtoupper(trim($s))] ?? $s;
}

/** @return list<string> */
function m360_qc_board_filters(): array
{
    return [
        M360_QC_READY, M360_QC_IN_PROGRESS, M360_QC_FAILED, M360_QC_REWORK_REQUIRED,
        M360_QC_PASSED, M360_QC_DELIVERY_READY, M360_QC_ON_HOLD,
    ];
}

/** @param array<string, mixed> $row */
function m360_qc_effective_status(array $row): string
{
    $qs = strtoupper(trim((string)($row['qc_status'] ?? '')));
    if ($qs !== '') {
        return $qs;
    }
    $wx = strtoupper(trim((string)($row['work_execution_status'] ?? '')));
    if ($wx === M360_WX_READY_QC) {
        return M360_QC_READY;
    }
    $js = strtoupper(trim((string)($row['jobcard_status'] ?? '')));
    if ($js === M360_QC_READY) {
        return M360_QC_READY;
    }
    return '';
}

/** @param array<string, mixed> $row */
function m360_qc_is_p5_ready(array $row): bool
{
    $wx = strtoupper(trim((string)($row['work_execution_status'] ?? '')));
    $js = strtoupper(trim((string)($row['jobcard_status'] ?? '')));
    return $wx === M360_WX_READY_QC || $js === M360_QC_READY
        || in_array(m360_qc_effective_status($row), [
            M360_QC_READY, M360_QC_IN_PROGRESS, M360_QC_FAILED, M360_QC_REWORK_REQUIRED,
            M360_QC_REWORK_IN_PROGRESS, M360_QC_REWORK_COMPLETED, M360_QC_PASSED, M360_QC_DELIVERY_READY,
        ], true);
}

/** @param array<string, mixed> $row */
function m360_qc_is_active_workflow(array $row): bool
{
    return in_array(m360_qc_effective_status($row), [
        M360_QC_IN_PROGRESS, M360_QC_FAILED, M360_QC_REWORK_REQUIRED,
        M360_QC_REWORK_IN_PROGRESS, M360_QC_REWORK_COMPLETED, M360_QC_PASSED,
    ], true);
}

/**
 * @param array<string, mixed> $row
 * @return array{ok:bool,message:string,block_event:?string}
 */
function m360_qc_assert_gates($conn, int $jobcardId, array $row): array
{
    if (!function_exists('m360_contract_can_continue_to_p2')) {
        return ['ok' => false, 'message' => 'P1.5 Gate missing.', 'block_event' => 'JOBCARD_QC_BLOCKED_GATE'];
    }
    if (!m360_qc_is_p5_ready($row) && !m360_qc_is_active_workflow($row)) {
        return ['ok' => false, 'message' => 'پرونده هنوز آماده QC نیست.', 'block_event' => 'JOBCARD_QC_BLOCKED_GATE'];
    }
    if (!m360_contract_can_continue_to_p2($jobcardId)) {
        return ['ok' => false, 'message' => 'قرارداد پذیرش معتبر نیست.', 'block_event' => 'JOBCARD_QC_BLOCKED_GATE'];
    }
    $estStatus = strtoupper(trim((string)($row['estimate_status'] ?? '')));
    if ($estStatus !== M360_EST_STATUS_APPROVED_WORK && $estStatus !== 'APPROVED_FOR_WORK') {
        $est = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
        if ($est === null || strtoupper((string)($est['estimate_status'] ?? '')) !== M360_EST_STATUS_APPROVED_WORK) {
            return ['ok' => false, 'message' => 'تأیید برآورد (P4) معتبر نیست.', 'block_event' => 'JOBCARD_QC_BLOCKED_GATE'];
        }
    }
    $notes = trim((string)($row['technical_completion_notes'] ?? ''));
    if ($notes === '') {
        return ['ok' => false, 'message' => 'یادداشت تکمیل فنی (P5) وجود ندارد.', 'block_event' => 'JOBCARD_QC_BLOCKED_GATE'];
    }
    $wx = strtoupper(trim((string)($row['work_execution_status'] ?? '')));
    $completed = in_array($wx, [M360_WX_READY_QC, M360_QC_PASSED, M360_QC_DELIVERY_READY], true)
        || trim((string)($row['work_completed_at'] ?? '')) !== ''
        || strtoupper((string)($row['technical_status'] ?? '')) === 'TECHNICAL_DONE';
    if (!$completed && !m360_qc_is_active_workflow($row)) {
        return ['ok' => false, 'message' => 'اجرای کار (P5) تکمیل نشده است.', 'block_event' => 'JOBCARD_QC_BLOCKED_GATE'];
    }
    return ['ok' => true, 'message' => '', 'block_event' => null];
}

function m360_qc_write_history($conn, int $jobcardId, string $type, ?string $prev, ?string $new, string $summary, int $userId): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_QC_HISTORY)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_QC_HISTORY . ' (jobcard_id, change_type, previous_status, new_status, change_summary, changed_by_user_id) VALUES (?, ?, ?, ?, ?, ?)',
        [$jobcardId, $type, $prev, $new, $summary, $userId]
    );
}

function m360_qc_write_event($conn, int $jobcardId, string $name, ?string $note, int $userId, ?int $qcCheckId = null): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_QC_EVENTS_TABLE)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_QC_EVENTS_TABLE . ' (qc_check_id, jobcard_id, event_name, event_note, created_by_user_id) VALUES (?, ?, ?, ?, ?)',
        [$qcCheckId, $jobcardId, $name, $note, $userId]
    );
}

/** @return array<string, mixed>|null */
function m360_qc_fetch_jobcard($conn, int $jobcardId): ?array
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

/** @return array<string, mixed>|null */
function m360_qc_fetch_check($conn, int $qcCheckId): ?array
{
    if (!is_resource($conn) || $qcCheckId < 1 || !customer_core_table_exists($conn, M360_QC_CHECKS_TABLE)) {
        return null;
    }
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.' . M360_QC_CHECKS_TABLE . ' WHERE qc_check_id = ?', [$qcCheckId]);
    return $rows[0] ?? null;
}

/** @return array<string, mixed>|null */
function m360_qc_fetch_active_check($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, M360_QC_CHECKS_TABLE)) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_QC_CHECKS_TABLE . ' WHERE jobcard_id = ? AND is_active = 1 ORDER BY qc_check_id DESC',
        [$jobcardId]
    );
    return $rows[0] ?? null;
}

/** @return array{ok:bool,qc_check_id:int,message:string} */
function m360_qc_create_check($conn, int $jobcardId, int $userId): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_QC_CHECKS_TABLE)) {
        return ['ok' => false, 'qc_check_id' => 0, 'message' => 'جدول QC یافت نشد.'];
    }
    $existing = m360_qc_fetch_active_check($conn, $jobcardId);
    if ($existing !== null && in_array(strtoupper((string)($existing['qc_status'] ?? '')), ['PENDING', 'DRAFT', 'IN_PROGRESS'], true)) {
        return ['ok' => true, 'qc_check_id' => (int)$existing['qc_check_id'], 'message' => 'QC قبلاً شروع شده.'];
    }

    $hasLegacyCheckedBy = customer_core_column_exists($conn, M360_QC_CHECKS_TABLE, 'checked_by_user_id');
    $statusVal = $hasLegacyCheckedBy ? 'PENDING' : 'DRAFT';

    if ($hasLegacyCheckedBy) {
        $ok = customer_core_execute(
            $conn,
            'INSERT INTO dbo.' . M360_QC_CHECKS_TABLE . ' (jobcard_id, qc_status, checked_by_user_id, checked_at, is_active) VALUES (?, ?, ?, SYSUTCDATETIME(), 1)',
            [$jobcardId, $statusVal, max(1, $userId)]
        );
    } else {
        $ok = customer_core_execute(
            $conn,
            'INSERT INTO dbo.' . M360_QC_CHECKS_TABLE . ' (jobcard_id, qc_status, created_by_user_id, started_at, is_active) VALUES (?, ?, ?, SYSUTCDATETIME(), 1)',
            [$jobcardId, $statusVal, $userId]
        );
    }
    if ($ok === false) {
        return ['ok' => false, 'qc_check_id' => 0, 'message' => 'ایجاد QC ناموفق بود.'];
    }
    $newId = (int)(customer_core_scalar($conn, 'SELECT TOP 1 qc_check_id FROM dbo.' . M360_QC_CHECKS_TABLE . ' WHERE jobcard_id = ? ORDER BY qc_check_id DESC', [$jobcardId]) ?? 0);
    m360_final_inspection_seed_items($conn, $newId, $jobcardId);
    return ['ok' => true, 'qc_check_id' => $newId, 'message' => 'QC شروع شد.'];
}

function m360_qc_map_check_status(string $p6Status): string
{
    return match (strtoupper(trim($p6Status))) {
        M360_QC_IN_PROGRESS => 'PENDING',
        M360_QC_PASSED => 'PASSED',
        M360_QC_FAILED, M360_QC_REWORK_REQUIRED => 'FAILED',
        M360_QC_REWORK_COMPLETED, M360_QC_READY => 'RECHECK_REQUIRED',
        M360_QC_CANCELLED => 'CANCELLED',
        default => 'PENDING',
    };
}

function m360_qc_update_jobcard($conn, int $jobcardId, array $sets, array $params): void
{
    if ($sets === []) {
        return;
    }
    if (customer_core_column_exists($conn, 'erp_jobcards', 'updated_at')) {
        $sets[] = 'updated_at = SYSUTCDATETIME()';
    }
    $params[] = $jobcardId;
    customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET ' . implode(', ', $sets) . ' WHERE jobcard_id = ?', $params);
}

/**
 * @return list<array<string, mixed>>
 */
function m360_qc_board_list($conn, ?string $filter = null, int $limit = 150): array
{
    if (!is_resource($conn)) {
        return [];
    }
    $limit = max(1, min(300, $limit));
    $where = "(j.work_execution_status = N'READY_FOR_QC' OR j.qc_status IS NOT NULL OR j.jobcard_status = N'READY_FOR_QC')";
    $params = [];
    if ($filter !== null && $filter !== '' && strtoupper($filter) !== 'ALL') {
        $f = strtoupper(trim($filter));
        if ($f === M360_QC_READY) {
            $where .= " AND (j.qc_status IS NULL OR j.qc_status = N'READY_FOR_QC') AND j.work_execution_status = N'READY_FOR_QC'";
        } else {
            $where .= ' AND j.qc_status = ?';
            $params[] = $f;
        }
    }
    $sql = 'SELECT TOP ' . $limit . ' j.jobcard_id, j.qc_status, j.work_execution_status, j.jobcard_status,
            j.estimate_status, j.ready_for_qc_at, j.delivery_readiness_status, j.delivery_ready_at,
            j.final_technician_user_id, j.technical_completion_notes, c.full_name AS customer_name,
            c.primary_mobile AS customer_mobile, v.plate_number, v.brand, v.model
            FROM dbo.erp_jobcards j
            LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
            LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
            WHERE ' . $where . ' ORDER BY j.ready_for_qc_at DESC, j.jobcard_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $row['qc_status_label'] = m360_qc_status_label(m360_qc_effective_status($row));
        $row['work_status_label'] = m360_work_status_label(m360_work_effective_status($row));
        $vehicle = trim(trim((string)($row['brand'] ?? '')) . ' ' . trim((string)($row['model'] ?? '')));
        $row['vehicle_label'] = $vehicle !== '' ? $vehicle : '-';
        $rows[] = $row;
    }
    return $rows;
}

/** @return array<string, string> */
function m360_qc_history_map(): array
{
    return [
        'start_qc' => 'JOBCARD_QC_STARTED',
        'save_checklist_item' => 'JOBCARD_QC_CHECKLIST_ITEM_SAVED',
        'save_final_inspection_notes' => 'JOBCARD_FINAL_INSPECTION_NOTES_SAVED',
        'qc_failed' => 'JOBCARD_QC_FAILED',
        'rework_required' => 'JOBCARD_REWORK_REQUIRED',
        'rework_completed' => 'JOBCARD_REWORK_COMPLETED',
        'qc_passed' => 'JOBCARD_QC_PASSED',
        'delivery_ready' => 'JOBCARD_DELIVERY_READY',
        'hold' => 'JOBCARD_QC_ON_HOLD',
        'cancel' => 'JOBCARD_QC_CANCELLED',
    ];
}

/** @return array<string, string> */
function m360_qc_event_map(): array
{
    return [
        'start_qc' => 'QC_STARTED',
        'save_checklist_item' => 'QC_CHECKLIST_ITEM_SAVED',
        'save_final_inspection_notes' => 'QC_FINAL_INSPECTION_NOTES_SAVED',
        'qc_failed' => 'QC_FAILED',
        'rework_required' => 'QC_REWORK_REQUIRED',
        'rework_completed' => 'QC_REWORK_COMPLETED',
        'qc_passed' => 'QC_PASSED',
        'delivery_ready' => 'QC_DELIVERY_READY',
        'hold' => 'QC_ON_HOLD',
        'cancel' => 'QC_CANCELLED',
    ];
}

/**
 * @return array{ok:bool,message:string,new_status:?string}
 */
function m360_qc_validate_action(string $status, string $action): array
{
    $s = strtoupper(trim($status));
    $a = strtolower(trim($action));
    if ($s === M360_QC_CANCELLED && !in_array($a, ['save_final_inspection_notes', 'save_checklist_item'], true)) {
        return ['ok' => false, 'message' => 'QC لغو شده است.', 'new_status' => null];
    }
    if ($s === M360_QC_DELIVERY_READY && !in_array($a, [], true)) {
        return ['ok' => true, 'message' => 'پرونده آماده تحویل است.', 'new_status' => null];
    }
    switch ($a) {
        case 'start_qc':
            if (in_array($s, [M360_QC_READY, M360_QC_IN_PROGRESS, ''], true) || $s === M360_QC_REWORK_COMPLETED) {
                return ['ok' => true, 'message' => $s === M360_QC_IN_PROGRESS ? 'QC در جریان است.' : '', 'new_status' => M360_QC_IN_PROGRESS];
            }
            return ['ok' => false, 'message' => 'شروع QC مجاز نیست.', 'new_status' => null];
        case 'save_checklist_item':
        case 'save_final_inspection_notes':
            return ['ok' => true, 'message' => '', 'new_status' => null];
        case 'qc_failed':
        case 'rework_required':
            return ['ok' => true, 'message' => '', 'new_status' => M360_QC_REWORK_REQUIRED];
        case 'rework_completed':
            return ['ok' => true, 'message' => '', 'new_status' => M360_QC_READY];
        case 'qc_passed':
            if ($s === M360_QC_PASSED) {
                return ['ok' => true, 'message' => 'QC قبلاً Pass شده.', 'new_status' => null];
            }
            return ['ok' => true, 'message' => '', 'new_status' => M360_QC_PASSED];
        case 'delivery_ready':
            if ($s === M360_QC_DELIVERY_READY) {
                return ['ok' => true, 'message' => 'قبلاً آماده تحویل است.', 'new_status' => null];
            }
            return ['ok' => true, 'message' => '', 'new_status' => M360_QC_DELIVERY_READY];
        case 'hold':
            return ['ok' => true, 'message' => '', 'new_status' => M360_QC_ON_HOLD];
        case 'cancel':
            return ['ok' => true, 'message' => '', 'new_status' => M360_QC_CANCELLED];
        default:
            return ['ok' => false, 'message' => 'عملیات نامعتبر.', 'new_status' => null];
    }
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string}
 */
function m360_qc_apply_action($conn, int $jobcardId, string $action, array $payload, int $userId, ?int $qcCheckId = null): array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return ['ok' => false, 'message' => 'اتصال برقرار نشد.'];
    }
    $row = m360_qc_fetch_jobcard($conn, $jobcardId);
    if ($row === null) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.'];
    }

    $gate = m360_qc_assert_gates($conn, $jobcardId, $row);
    if (!$gate['ok']) {
        m360_qc_write_history($conn, $jobcardId, 'JOBCARD_QC_BLOCKED_GATE', m360_qc_effective_status($row), m360_qc_effective_status($row), $gate['message'], $userId);
        m360_qc_write_event($conn, $jobcardId, 'QC_BLOCKED_GATE', $gate['message'], $userId, $qcCheckId);
        return ['ok' => false, 'message' => $gate['message']];
    }

    $action = strtolower(trim($action));
    $current = m360_qc_effective_status($row);
    $validation = m360_qc_validate_action($current, $action);
    if (!$validation['ok']) {
        return ['ok' => false, 'message' => $validation['message']];
    }

    $check = $qcCheckId > 0 ? m360_qc_fetch_check($conn, $qcCheckId) : m360_qc_fetch_active_check($conn, $jobcardId);
    if ($check !== null) {
        $qcCheckId = (int)$check['qc_check_id'];
    }

    $histMap = m360_qc_history_map();
    $eventMap = m360_qc_event_map();
    $histType = $histMap[$action] ?? 'JOBCARD_QC_ACTION';
    $eventName = $eventMap[$action] ?? 'QC_ACTION';
    $newStatus = $validation['new_status'];

    if ($action === 'start_qc') {
        $created = m360_qc_create_check($conn, $jobcardId, $userId);
        if (!$created['ok']) {
            return ['ok' => false, 'message' => $created['message']];
        }
        $qcCheckId = $created['qc_check_id'];
        $newStatus = M360_QC_IN_PROGRESS;
    } elseif ($action === 'save_checklist_item') {
        $itemId = (int)($payload['qc_item_id'] ?? 0);
        $result = (string)($payload['item_result'] ?? '');
        $note = trim((string)($payload['item_note'] ?? ''));
        $res = m360_final_inspection_save_item($conn, $itemId, $result, $note !== '' ? $note : null, $userId);
        if (!$res['ok']) {
            return $res;
        }
        m360_qc_write_history($conn, $jobcardId, $histType, $current, $current, 'Checklist item saved', $userId);
        m360_qc_write_event($conn, $jobcardId, $eventName, $note, $userId, $qcCheckId);
        return $res;
    } elseif ($action === 'save_final_inspection_notes') {
        $notes = trim((string)($payload['final_inspection_notes'] ?? ''));
        if ($notes === '') {
            return ['ok' => false, 'message' => 'یادداشت بازبینی نهایی خالی است.'];
        }
        m360_qc_update_jobcard($conn, $jobcardId, ['final_inspection_notes = ?'], [$notes]);
        if ($check !== null && customer_core_column_exists($conn, M360_QC_CHECKS_TABLE, 'final_note')) {
            customer_core_execute($conn, 'UPDATE dbo.' . M360_QC_CHECKS_TABLE . ' SET final_note = ? WHERE qc_check_id = ?', [$notes, $qcCheckId]);
        }
        m360_qc_write_history($conn, $jobcardId, $histType, $current, $current, 'Final notes saved', $userId);
        m360_qc_write_event($conn, $jobcardId, $eventName, null, $userId, $qcCheckId);
        return ['ok' => true, 'message' => 'یادداشت بازبینی نهایی ذخیره شد.'];
    } elseif (in_array($action, ['qc_failed', 'rework_required'], true)) {
        $reason = trim((string)($payload['failure_reason'] ?? ''));
        if ($reason === '') {
            return ['ok' => false, 'message' => 'دلیل رد QC الزامی است.'];
        }
        $newStatus = M360_QC_REWORK_REQUIRED;
        if ($check !== null) {
            $mapped = m360_qc_map_check_status(M360_QC_FAILED);
            customer_core_execute(
                $conn,
                'UPDATE dbo.' . M360_QC_CHECKS_TABLE . ' SET qc_status = ?, failure_reason = ?, failed_at = SYSUTCDATETIME(), completed_at = SYSUTCDATETIME() WHERE qc_check_id = ?',
                [$mapped, $reason, $qcCheckId]
            );
        }
        m360_qc_update_jobcard($conn, $jobcardId, [
            'qc_failure_reason = ?', 'qc_failed_at = SYSUTCDATETIME()',
            'work_execution_status = ?',
        ], [$reason, M360_QC_REWORK_REQUIRED]);
    } elseif ($action === 'rework_completed') {
        $newStatus = M360_QC_READY;
        m360_qc_update_jobcard($conn, $jobcardId, [
            'work_execution_status = ?', 'qc_failure_reason = NULL',
        ], [M360_WX_READY_QC]);
        if ($check !== null) {
            customer_core_execute($conn, 'UPDATE dbo.' . M360_QC_CHECKS_TABLE . ' SET qc_status = ?, is_active = 0 WHERE qc_check_id = ?', [m360_qc_map_check_status(M360_QC_REWORK_COMPLETED), $qcCheckId]);
        }
    } elseif ($action === 'qc_passed') {
        $row = m360_qc_fetch_jobcard($conn, $jobcardId);
        $notes = trim((string)($row['final_inspection_notes'] ?? ''));
        if ($qcCheckId < 1) {
            return ['ok' => false, 'message' => 'QC شروع نشده است.'];
        }
        $passVal = m360_final_inspection_validate_pass($conn, $qcCheckId, $notes);
        if (!$passVal['ok']) {
            return $passVal;
        }
        $newStatus = M360_QC_PASSED;
        if ($check !== null) {
            customer_core_execute(
                $conn,
                'UPDATE dbo.' . M360_QC_CHECKS_TABLE . ' SET qc_status = ?, qc_result = ?, passed_at = SYSUTCDATETIME(), completed_at = SYSUTCDATETIME() WHERE qc_check_id = ?',
                [m360_qc_map_check_status(M360_QC_PASSED), 'PASS', $qcCheckId]
            );
        }
        m360_qc_update_jobcard($conn, $jobcardId, ['qc_passed_at = SYSUTCDATETIME()', 'qc_completed_at = SYSUTCDATETIME()', 'qc_user_id = ?'], [$userId]);
    } elseif ($action === 'delivery_ready') {
        $row = m360_qc_fetch_jobcard($conn, $jobcardId);
        $delVal = m360_delivery_readiness_validate($conn, $jobcardId, $row ?? [], $check);
        if (!$delVal['ok']) {
            return $delVal;
        }
        $newStatus = M360_QC_DELIVERY_READY;
        m360_delivery_readiness_mark($conn, $jobcardId, $qcCheckId, $userId);
        m360_qc_update_jobcard($conn, $jobcardId, [
            'delivery_readiness_status = ?', 'delivery_ready_at = SYSUTCDATETIME()',
        ], [M360_DEL_READY_STATUS]);
        if (customer_core_column_exists($conn, 'erp_jobcards', 'jobcard_status')) {
            m360_qc_update_jobcard($conn, $jobcardId, ["jobcard_status = N'DELIVERY_READY'"], []);
        }
    } elseif ($action === 'hold') {
        $newStatus = M360_QC_ON_HOLD;
    } elseif ($action === 'cancel') {
        $newStatus = M360_QC_CANCELLED;
        if ($check !== null) {
            customer_core_execute($conn, 'UPDATE dbo.' . M360_QC_CHECKS_TABLE . ' SET qc_status = ?, is_active = 0 WHERE qc_check_id = ?', ['CANCELLED', $qcCheckId]);
        }
    }

    if ($newStatus !== null && customer_core_column_exists($conn, 'erp_jobcards', 'qc_status')) {
        $sets = ['qc_status = ?'];
        $params = [$newStatus];
        if ($action === 'start_qc' && customer_core_column_exists($conn, 'erp_jobcards', 'qc_started_at')) {
            $sets[] = 'qc_started_at = SYSUTCDATETIME()';
            $sets[] = 'qc_user_id = ?';
            $params[] = $userId;
        }
        m360_qc_update_jobcard($conn, $jobcardId, $sets, $params);
    }

    if (!in_array($action, ['save_checklist_item', 'save_final_inspection_notes'], true)) {
        $effectiveNew = $newStatus ?? $current;
        m360_qc_write_history($conn, $jobcardId, $histType, $current, $effectiveNew, 'P6 QC: ' . $action, $userId);
        m360_qc_write_event($conn, $jobcardId, $eventName, null, $userId, $qcCheckId);
    }

    $msg = $validation['message'] !== '' ? $validation['message'] : 'عملیات انجام شد.';
    return ['ok' => true, 'message' => $msg];
}

/**
 * @param array<string, mixed> $row
 * @return list<string>
 */
function m360_qc_allowed_actions(array $row, bool $gatesOk): array
{
    if (!$gatesOk) {
        return [];
    }
    $s = m360_qc_effective_status($row);
    $actions = ['start_qc', 'save_checklist_item', 'save_final_inspection_notes', 'qc_failed', 'rework_required', 'rework_completed', 'qc_passed', 'delivery_ready', 'hold', 'cancel'];
    $allowed = [];
    foreach ($actions as $a) {
        if (m360_qc_validate_action($s, $a)['ok']) {
            $allowed[] = $a;
        }
    }
    return $allowed;
}

/** @return list<array<string, mixed>> */
function m360_qc_list_events($conn, int $jobcardId, int $limit = 80): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_QC_EVENTS_TABLE)) {
        return [];
    }
    $limit = max(1, min(200, $limit));
    return customer_core_fetch_rows(
        $conn,
        'SELECT TOP ' . $limit . ' event_id, event_name, event_note, created_at, qc_check_id FROM dbo.' . M360_QC_EVENTS_TABLE . ' WHERE jobcard_id = ? ORDER BY event_id DESC',
        [$jobcardId]
    );
}

/** @return list<array<string, mixed>> */
function m360_qc_list_history($conn, int $jobcardId, int $limit = 80): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_QC_HISTORY)) {
        return [];
    }
    $limit = max(1, min(200, $limit));
    return customer_core_fetch_rows(
        $conn,
        'SELECT TOP ' . $limit . ' change_id, change_type, previous_status, new_status, change_summary, changed_at FROM dbo.' . M360_QC_HISTORY . ' WHERE jobcard_id = ? ORDER BY change_id DESC',
        [$jobcardId]
    );
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_qc_register_media_event($conn, int $jobcardId, int $userId, string $mediaType, ?string $note, ?int $qcCheckId = null): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_QC_MEDIA_TABLE)) {
        return ['ok' => false, 'message' => 'ثبت مستندات QC در دسترس نیست.'];
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_QC_MEDIA_TABLE . ' (jobcard_id, qc_check_id, media_type, capture_method, media_note, created_by_user_id) VALUES (?, ?, ?, N\'DIRECT_CAMERA\', ?, ?)',
        [$jobcardId, $qcCheckId, strtoupper(trim($mediaType)), $note, $userId]
    );
    return ['ok' => true, 'message' => 'رویداد مستندات QC ثبت شد.'];
}
