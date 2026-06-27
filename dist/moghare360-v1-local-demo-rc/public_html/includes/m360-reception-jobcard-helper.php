<?php
declare(strict_types=1);

/**
 * MOGHARE360 P2 — Reception JobCard operational workflow helper.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-jobcard-workflow-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';

const M360_RECEPTION_JOBCARD_CSRF_PURPOSE = 'reception_jobcard_p2';
const M360_RECEPTION_JOBCARD_HISTORY_TABLE = 'erp_jobcard_change_history';

function m360_reception_jobcard_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_reception_jobcard_require_staff(): void
{
    erp_auth_context_start();
    $userId = erp_auth_current_user_id();
    if ($userId === null || $userId <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_reception_jobcard_p15_gate_available(): bool
{
    return function_exists('m360_contract_can_continue_to_p2')
        && function_exists('m360_contract_signed_for_jobcard')
        && function_exists('m360_contract_required_for_jobcard')
        && function_exists('m360_contract_record_event');
}

/** @return array{ok:bool,message:string} */
function m360_reception_jobcard_assert_p15_gate(): array
{
    if (!m360_reception_jobcard_p15_gate_available()) {
        return ['ok' => false, 'message' => 'P1.5 Gate missing — عملیات متوقف شد.'];
    }
    return ['ok' => true, 'message' => ''];
}

function m360_reception_jobcard_contract_filter_codes(): array
{
    return ['ALL', 'SIGNED', 'UNSIGNED', 'OVERRIDDEN'];
}

function m360_reception_jobcard_contract_filter_label(string $code): string
{
    $map = [
        'ALL' => 'همه قراردادها',
        'SIGNED' => 'امضا شده',
        'UNSIGNED' => 'بدون امضا',
        'OVERRIDDEN' => 'تأیید مدیریتی',
    ];
    return $map[strtoupper($code)] ?? $code;
}

/**
 * @return list<array<string, mixed>>
 */
function m360_reception_jobcard_list($conn, ?string $statusFilter = null, ?string $contractFilter = null, int $limit = 150): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, 'erp_jobcards')) {
        return [];
    }

    $limit = max(1, min(500, $limit));
    $params = [];
    $where = '1=1';

    if ($statusFilter !== null && $statusFilter !== '' && strtoupper($statusFilter) !== 'ALL') {
        $where .= ' AND j.jobcard_status = ?';
        $params[] = strtoupper(trim($statusFilter));
    }

    $contractFilter = strtoupper(trim((string)$contractFilter));
    if ($contractFilter === 'SIGNED') {
        $where .= " AND (j.contract_status = N'SIGNED' OR EXISTS (
            SELECT 1 FROM dbo.erp_intake_contracts ic
            WHERE ic.jobcard_id = j.jobcard_id AND ic.contract_status = N'SIGNED'
        ))";
    } elseif ($contractFilter === 'UNSIGNED') {
        $where .= " AND (j.contract_status IS NULL OR j.contract_status NOT IN (N'SIGNED', N'OVERRIDDEN'))
            AND NOT EXISTS (
                SELECT 1 FROM dbo.erp_intake_contracts ic
                WHERE ic.jobcard_id = j.jobcard_id AND ic.contract_status IN (N'SIGNED', N'OVERRIDDEN')
            )";
    } elseif ($contractFilter === 'OVERRIDDEN') {
        $where .= " AND (j.contract_status = N'OVERRIDDEN' OR EXISTS (
            SELECT 1 FROM dbo.erp_intake_contracts ic
            WHERE ic.jobcard_id = j.jobcard_id AND ic.contract_status = N'OVERRIDDEN' AND ic.manager_override = 1
        ))";
    }

    $hasOnlineReq = customer_core_column_exists($conn, 'erp_jobcards', 'online_request_id');
    $onlineCol = $hasOnlineReq ? 'j.online_request_id' : 'NULL AS online_request_id';

    $sql = 'SELECT TOP ' . $limit . '
            j.jobcard_id,
            j.jobcard_number,
            j.jobcard_status,
            j.created_at,
            j.reception_at,
            j.vehicle_arrival_at,
            j.checked_in_at,
            j.contract_status,
            j.contract_signed_at,
            j.intake_contract_id,
            j.customer_complaint,
            j.reception_notes,
            j.initial_inspection_notes,
            ' . $onlineCol . ',
            c.full_name AS customer_name,
            c.primary_mobile AS customer_mobile,
            v.plate_number,
            v.brand,
            v.model
        FROM dbo.erp_jobcards j
        LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
        LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
        WHERE ' . $where . '
        ORDER BY j.created_at DESC, j.jobcard_id DESC';

    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return [];
    }

    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $rows[] = m360_reception_jobcard_enrich_row($conn, $row);
    }
    return $rows;
}

/** @param array<string, mixed> $row */
function m360_reception_jobcard_enrich_row($conn, array $row): array
{
    $jobcardId = (int)($row['jobcard_id'] ?? 0);
    $row['status_label'] = m360_jobcard_workflow_status_label((string)($row['jobcard_status'] ?? ''));
    $row['contract_summary'] = m360_reception_jobcard_contract_summary($conn, $jobcardId, $row);
    $row['source_label'] = ((int)($row['online_request_id'] ?? 0) > 0) ? 'آنلاین' : 'دستی';
    $vehicle = trim(trim((string)($row['brand'] ?? '')) . ' ' . trim((string)($row['model'] ?? '')));
    $row['vehicle_label'] = $vehicle !== '' ? $vehicle : '-';
    return $row;
}

/**
 * @param array<string, mixed>|null $jobcardRow
 * @return array{code:string,label:string,signed_at:?string,contract_id:?int,can_continue:bool}
 */
function m360_reception_jobcard_contract_summary($conn, int $jobcardId, ?array $jobcardRow = null): array
{
    $default = [
        'code' => 'UNSIGNED',
        'label' => 'بدون امضا',
        'signed_at' => null,
        'contract_id' => null,
        'can_continue' => false,
    ];

    if ($jobcardId < 1) {
        return $default;
    }

    $jcStatus = strtoupper((string)($jobcardRow['contract_status'] ?? ''));
    $signedAt = trim((string)($jobcardRow['contract_signed_at'] ?? ''));
    $contractId = (int)($jobcardRow['intake_contract_id'] ?? 0);

    if (is_resource($conn) && customer_core_table_exists($conn, M360_CONTRACT_TABLE)) {
        $sql = 'SELECT TOP 1 contract_id, contract_status, signed_at, manager_override
                FROM dbo.' . M360_CONTRACT_TABLE . ' WHERE jobcard_id = ? ORDER BY contract_id DESC';
        $stmt = @odbc_prepare($conn, $sql);
        if ($stmt !== false && @odbc_execute($stmt, [$jobcardId])) {
            $cRow = odbc_fetch_array($stmt);
            if ($cRow !== false) {
                $contractId = (int)($cRow['contract_id'] ?? $contractId);
                $jcStatus = strtoupper((string)($cRow['contract_status'] ?? $jcStatus));
                if ($signedAt === '') {
                    $signedAt = trim((string)($cRow['signed_at'] ?? ''));
                }
                if ($jcStatus === M360_CONTRACT_STATUS_OVERRIDDEN && (int)($cRow['manager_override'] ?? 0) === 1) {
                    $default['code'] = 'OVERRIDDEN';
                    $default['label'] = 'تأیید مدیریتی';
                }
            }
        }
    }

    if ($jcStatus === M360_CONTRACT_STATUS_SIGNED || $jcStatus === 'SIGNED') {
        $default['code'] = 'SIGNED';
        $default['label'] = 'امضا شده';
    } elseif ($jcStatus === M360_CONTRACT_STATUS_OVERRIDDEN || $default['code'] === 'OVERRIDDEN') {
        $default['code'] = 'OVERRIDDEN';
        $default['label'] = 'تأیید مدیریتی';
    }

    $default['signed_at'] = $signedAt !== '' ? $signedAt : null;
    $default['contract_id'] = $contractId > 0 ? $contractId : null;

    if (m360_reception_jobcard_p15_gate_available()) {
        $default['can_continue'] = m360_contract_can_continue_to_p2($jobcardId);
    }

    return $default;
}

/** @return array<string, mixed>|null */
function m360_reception_jobcard_fetch($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, 'erp_jobcards')) {
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
    return m360_reception_jobcard_enrich_row($conn, $row);
}

/** @return list<array<string, mixed>> */
function m360_reception_jobcard_history($conn, int $jobcardId, int $limit = 50): array
{
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, M360_RECEPTION_JOBCARD_HISTORY_TABLE)) {
        return [];
    }
    $limit = max(1, min(200, $limit));
    $sql = 'SELECT TOP ' . $limit . ' history_id, change_type, previous_status, new_status, change_summary, changed_by_user_id, changed_at
            FROM dbo.' . M360_RECEPTION_JOBCARD_HISTORY_TABLE . '
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
function m360_reception_jobcard_contract_events($conn, int $contractId, int $limit = 30): array
{
    if (!is_resource($conn) || $contractId < 1 || !customer_core_table_exists($conn, M360_CONTRACT_EVT_TABLE)) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    $sql = 'SELECT TOP ' . $limit . ' event_id, event_name, event_note, created_by_user_id, created_at
            FROM dbo.' . M360_CONTRACT_EVT_TABLE . '
            WHERE contract_id = ? ORDER BY created_at DESC, event_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$contractId])) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $rows[] = $row;
    }
    return $rows;
}

/** @return array<string, mixed>|null */
function m360_reception_jobcard_fetch_contract($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, M360_CONTRACT_TABLE)) {
        return null;
    }
    $sql = 'SELECT TOP 1 * FROM dbo.' . M360_CONTRACT_TABLE . ' WHERE jobcard_id = ? ORDER BY contract_id DESC';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    return $row === false ? null : $row;
}

/**
 * @param array<string, mixed> $row
 * @return list<string>
 */
function m360_reception_jobcard_allowed_actions(array $row): array
{
    $status = m360_jobcard_workflow_normalize_status((string)($row['jobcard_status'] ?? ''));
    $canContinue = (bool)($row['contract_summary']['can_continue'] ?? false);
    $actions = [];

    if (!m360_jobcard_workflow_is_terminal($status)) {
        if (in_array($status, [M360_JC_STATUS_RECEIVED], true)) {
            $actions[] = 'mark_arrived';
        }
        if ($status === M360_JC_STATUS_ARRIVED) {
            $actions[] = 'check_in';
        }
        if (in_array($status, [M360_JC_STATUS_CHECKED_IN, M360_JC_STATUS_INITIAL_REVIEW, M360_JC_STATUS_ON_HOLD], true)) {
            if ($canContinue) {
                $actions[] = 'ready_for_technical';
            }
        }
        if (!in_array($status, [M360_JC_STATUS_CANCELLED, M360_JC_STATUS_READY_FOR_TECHNICAL], true)) {
            $actions[] = 'hold';
            $actions[] = 'cancel';
        }
    }

    $actions[] = 'save_customer_complaint';
    $actions[] = 'save_reception_notes';
    if (in_array($status, [M360_JC_STATUS_CHECKED_IN, M360_JC_STATUS_INITIAL_REVIEW, M360_JC_STATUS_READY_FOR_TECHNICAL, M360_JC_STATUS_IN_PROGRESS, M360_JC_STATUS_ON_HOLD], true)) {
        $actions[] = 'save_initial_inspection';
    }
    if (!$canContinue && !m360_jobcard_workflow_is_terminal($status)) {
        $actions[] = 'manager_override_contract_gate';
    }

    return array_values(array_unique($actions));
}

function m360_reception_jobcard_write_history(
    $conn,
    int $jobcardId,
    string $changeType,
    ?string $previousStatus,
    ?string $newStatus,
    string $summary,
    int $userId
): void {
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, M360_RECEPTION_JOBCARD_HISTORY_TABLE)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_RECEPTION_JOBCARD_HISTORY_TABLE . '
            (jobcard_id, change_type, previous_status, new_status, change_summary, changed_by_user_id)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$jobcardId, $changeType, $previousStatus, $newStatus, $summary, $userId]
    );
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string}
 */
function m360_reception_jobcard_apply_action($conn, int $jobcardId, string $action, array $payload, int $userId): array
{
    $gateCheck = m360_reception_jobcard_assert_p15_gate();
    if (!$gateCheck['ok'] && in_array($action, ['ready_for_technical', 'manager_override_contract_gate'], true)) {
        return $gateCheck;
    }

    if (!is_resource($conn) || $jobcardId < 1) {
        return ['ok' => false, 'message' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    $row = m360_reception_jobcard_fetch($conn, $jobcardId);
    if ($row === null) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.'];
    }

    $action = strtolower(trim($action));
    $currentStatus = m360_jobcard_workflow_normalize_status((string)($row['jobcard_status'] ?? ''));
    $validation = m360_jobcard_workflow_validate_action($currentStatus, $action);
    if (!$validation['ok']) {
        return ['ok' => false, 'message' => $validation['message']];
    }

    $newStatus = $validation['new_status'];
    $historyType = m360_jobcard_workflow_history_event($action);
    $summary = 'P2 reception action: ' . $action;

    if ($action === 'ready_for_technical') {
        if (!m360_contract_can_continue_to_p2($jobcardId)) {
            m360_reception_jobcard_write_history(
                $conn,
                $jobcardId,
                'JOBCARD_READY_FOR_TECHNICAL_BLOCKED_CONTRACT_UNSIGNED',
                $currentStatus,
                $currentStatus,
                'Blocked: intake contract not signed',
                $userId
            );
            $contract = m360_reception_jobcard_fetch_contract($conn, $jobcardId);
            if ($contract !== null && isset($contract['contract_id'])) {
                m360_contract_record_event(
                    $conn,
                    (int)$contract['contract_id'],
                    'JOBCARD_READY_FOR_TECHNICAL_BLOCKED_CONTRACT_UNSIGNED',
                    'Reception blocked ready_for_technical',
                    $userId
                );
            }
            return ['ok' => false, 'message' => 'قرارداد پذیرش هنوز توسط مشتری امضا نشده است.'];
        }
    }

    if ($action === 'manager_override_contract_gate') {
        $reason = trim((string)($payload['override_reason'] ?? ''));
        if (mb_strlen($reason) < 10) {
            return ['ok' => false, 'message' => 'دلیل تأیید مدیریتی باید حداقل ۱۰ کاراکتر باشد.'];
        }
        $contract = m360_reception_jobcard_fetch_contract($conn, $jobcardId);
        if ($contract === null) {
            return ['ok' => false, 'message' => 'قرارداد پذیرش برای این کارت کار یافت نشد.'];
        }
        $override = m360_intake_contract_apply_manager_override($conn, (int)$contract['contract_id'], $reason);
        if (!$override['ok']) {
            return $override;
        }
        m360_reception_jobcard_write_history(
            $conn,
            $jobcardId,
            'JOBCARD_CONTRACT_GATE_MANAGER_OVERRIDE',
            $currentStatus,
            $currentStatus,
            $reason,
            $userId
        );
        m360_contract_record_event(
            $conn,
            (int)$contract['contract_id'],
            'JOBCARD_CONTRACT_GATE_MANAGER_OVERRIDE',
            $reason,
            $userId
        );
        return ['ok' => true, 'message' => 'تأیید مدیریتی ثبت شد. اکنون می‌توانید آماده فنی کنید.'];
    }

    $sets = [];
    $params = [];

    if ($newStatus !== null && $newStatus !== $currentStatus) {
        $sets[] = 'jobcard_status = ?';
        $params[] = $newStatus;
    }

    if ($action === 'mark_arrived' && $newStatus === M360_JC_STATUS_ARRIVED && customer_core_column_exists($conn, 'erp_jobcards', 'vehicle_arrival_at')) {
        $sets[] = 'vehicle_arrival_at = SYSUTCDATETIME()';
    }
    if ($action === 'check_in' && $newStatus === M360_JC_STATUS_CHECKED_IN && customer_core_column_exists($conn, 'erp_jobcards', 'checked_in_at')) {
        $sets[] = 'checked_in_at = SYSUTCDATETIME()';
    }
    if ($action === 'ready_for_technical' && customer_core_column_exists($conn, 'erp_jobcards', 'ready_for_technical_at')) {
        $sets[] = 'ready_for_technical_at = SYSUTCDATETIME()';
    }
    if (customer_core_column_exists($conn, 'erp_jobcards', 'assigned_reception_user_id')) {
        $sets[] = 'assigned_reception_user_id = ?';
        $params[] = $userId;
    }
    if (customer_core_column_exists($conn, 'erp_jobcards', 'reception_user_id')) {
        $sets[] = 'reception_user_id = ?';
        $params[] = $userId;
    }

    if ($action === 'save_customer_complaint') {
        $text = trim((string)($payload['customer_complaint'] ?? ''));
        if ($text === '') {
            return ['ok' => false, 'message' => 'متن شکایت مشتری خالی است.'];
        }
        $sets[] = 'customer_complaint = ?';
        $params[] = mb_substr($text, 0, 1000);
    }
    if ($action === 'save_reception_notes') {
        $text = trim((string)($payload['reception_notes'] ?? ''));
        if ($text === '') {
            return ['ok' => false, 'message' => 'یادداشت پذیرش خالی است.'];
        }
        if (customer_core_column_exists($conn, 'erp_jobcards', 'reception_notes')) {
            $sets[] = 'reception_notes = ?';
            $params[] = $text;
        } elseif (customer_core_column_exists($conn, 'erp_jobcards', 'internal_notes')) {
            $sets[] = 'internal_notes = ?';
            $params[] = mb_substr($text, 0, 1000);
        }
    }
    if ($action === 'save_initial_inspection') {
        $text = trim((string)($payload['initial_inspection_notes'] ?? ''));
        if ($text === '') {
            return ['ok' => false, 'message' => 'یادداشت بررسی اولیه خالی است.'];
        }
        if (customer_core_column_exists($conn, 'erp_jobcards', 'initial_inspection_notes')) {
            $sets[] = 'initial_inspection_notes = ?';
            $params[] = $text;
        } elseif (customer_core_column_exists($conn, 'erp_jobcards', 'initial_vehicle_condition')) {
            $sets[] = 'initial_vehicle_condition = ?';
            $params[] = mb_substr($text, 0, 1000);
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

    $effectiveNew = $newStatus ?? $currentStatus;
    if ($action !== 'manager_override_contract_gate') {
        m360_reception_jobcard_write_history(
            $conn,
            $jobcardId,
            $historyType,
            $currentStatus,
            $effectiveNew,
            $summary,
            $userId
        );
    }

    $msg = $validation['message'] !== '' ? $validation['message'] : 'عملیات با موفقیت انجام شد.';
    return ['ok' => true, 'message' => $msg];
}
