<?php
declare(strict_types=1);

/**
 * MOGHARE360 P4 — Estimate helper (ERP board, detail, actions).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-technician-workflow-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-parts-finance-gate-helper.php';

const M360_ESTIMATE_CSRF = 'estimate_p4';
const M360_ESTIMATE_TABLE = 'erp_estimates';
const M360_ESTIMATE_ITEM_TABLE = 'erp_estimate_items';
const M360_ESTIMATE_EVENT_TABLE = 'erp_estimate_events';
const M360_ESTIMATE_HISTORY = 'erp_jobcard_change_history';
const M360_ESTIMATE_TOKEN_TTL = 259200;

const M360_EST_STATUS_DRAFT = 'DRAFT';
const M360_EST_STATUS_INTERNAL_REVIEW = 'INTERNAL_REVIEW';
const M360_EST_STATUS_SENT = 'SENT_TO_CUSTOMER';
const M360_EST_STATUS_VIEWED = 'CUSTOMER_VIEWED';
const M360_EST_STATUS_APPROVED = 'CUSTOMER_APPROVED';
const M360_EST_STATUS_REJECTED = 'CUSTOMER_REJECTED';
const M360_EST_STATUS_REVISION = 'REVISION_REQUIRED';
const M360_EST_STATUS_PARTS_PENDING = 'PARTS_GATE_PENDING';
const M360_EST_STATUS_PARTS_CLEARED = 'PARTS_GATE_CLEARED';
const M360_EST_STATUS_FIN_PENDING = 'FINANCE_GATE_PENDING';
const M360_EST_STATUS_FIN_CLEARED = 'FINANCE_GATE_CLEARED';
const M360_EST_STATUS_APPROVED_WORK = 'APPROVED_FOR_WORK';
const M360_EST_STATUS_CANCELLED = 'CANCELLED';

/** @var array<string, string> */
const M360_EST_STATUS_LABELS_FA = [
    M360_EST_STATUS_DRAFT => 'پیش‌نویس',
    M360_EST_STATUS_INTERNAL_REVIEW => 'بازبینی داخلی',
    M360_EST_STATUS_SENT => 'ارسال به مشتری',
    M360_EST_STATUS_VIEWED => 'مشاهده شده',
    M360_EST_STATUS_APPROVED => 'تأیید مشتری',
    M360_EST_STATUS_REJECTED => 'رد مشتری',
    M360_EST_STATUS_REVISION => 'نیاز به اصلاح',
    M360_EST_STATUS_PARTS_PENDING => 'انتظار قطعه',
    M360_EST_STATUS_PARTS_CLEARED => 'قطعه آماده',
    M360_EST_STATUS_FIN_PENDING => 'انتظار مالی',
    M360_EST_STATUS_FIN_CLEARED => 'مجوز مالی',
    M360_EST_STATUS_APPROVED_WORK => 'مجاز برای ادامه کار',
    M360_EST_STATUS_CANCELLED => 'لغو شده',
];

/** @var list<string> */
const M360_EST_ITEM_TYPES = ['LABOR', 'SERVICE', 'PART', 'OUTSOURCE', 'DIAGNOSIS', 'OTHER'];

function m360_estimate_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_estimate_require_staff(): void
{
    erp_auth_context_start();
    if (erp_auth_current_user_id() === null || erp_auth_current_user_id() <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_estimate_status_label(string $s): string
{
    return M360_EST_STATUS_LABELS_FA[strtoupper(trim($s))] ?? $s;
}

/** @return list<string> */
function m360_estimate_board_filters(): array
{
    return [
        'WAITING_FOR_APPROVAL',
        M360_EST_STATUS_DRAFT,
        M360_EST_STATUS_SENT,
        M360_EST_STATUS_APPROVED,
        M360_EST_STATUS_REJECTED,
        M360_EST_STATUS_PARTS_PENDING,
        M360_EST_STATUS_PARTS_CLEARED,
        M360_EST_STATUS_FIN_PENDING,
        M360_EST_STATUS_FIN_CLEARED,
        M360_EST_STATUS_APPROVED_WORK,
    ];
}

function m360_estimate_hash(string $raw): string
{
    return hash('sha256', $raw);
}

/**
 * @param array<string, mixed> $row
 */
function m360_estimate_is_p3_ready(array $row): bool
{
    $tech = strtoupper(trim((string)($row['technical_status'] ?? '')));
    return $tech === M360_TECH_STATUS_WAITING_APPROVAL
        || strtoupper((string)($row['estimate_status'] ?? '')) !== '';
}

/** @return array{ok:bool,message:string} */
function m360_estimate_assert_upstream_gates(int $jobcardId, ?array $jobcardRow = null): array
{
    if (!function_exists('m360_contract_can_continue_to_p2')) {
        return ['ok' => false, 'message' => 'P1.5 Gate missing — عملیات متوقف شد.'];
    }
    if ($jobcardId < 1) {
        return ['ok' => false, 'message' => 'شناسه کارت کار نامعتبر است.'];
    }
    if (!m360_contract_can_continue_to_p2($jobcardId)) {
        return ['ok' => false, 'message' => 'قرارداد پذیرش معتبر نیست.'];
    }
    $conn = customer_core_db();
    if ($jobcardRow === null && $conn !== false) {
        $jobcardRow = m360_estimate_fetch_jobcard($conn, $jobcardId);
    }
    if ($jobcardRow === null) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.'];
    }
    if (!m360_estimate_is_p3_ready($jobcardRow)) {
        return ['ok' => false, 'message' => 'پرونده هنوز در وضعیت انتظار تأیید برآورد (P3) نیست.'];
    }
    return ['ok' => true, 'message' => ''];
}

function m360_estimate_record_event($conn, int $jobcardId, string $eventName, ?int $estimateId = null, ?string $note = null, ?int $userId = null): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_ESTIMATE_EVENT_TABLE)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_ESTIMATE_EVENT_TABLE . ' (estimate_id, jobcard_id, event_name, event_note, created_by_user_id) VALUES (?, ?, ?, ?, ?)',
        [$estimateId, $jobcardId, $eventName, $note, $userId]
    );
}

function m360_estimate_jobcard_history($conn, int $jobcardId, string $type, ?string $prev, ?string $new, string $summary, int $userId): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_ESTIMATE_HISTORY)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_ESTIMATE_HISTORY . ' (jobcard_id, change_type, previous_status, new_status, change_summary, changed_by_user_id) VALUES (?, ?, ?, ?, ?, ?)',
        [$jobcardId, $type, $prev, $new, $summary, $userId]
    );
}

/** @return array<string, mixed>|null */
function m360_estimate_fetch_jobcard($conn, int $jobcardId): ?array
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
function m360_estimate_fetch($conn, int $estimateId): ?array
{
    if (!is_resource($conn) || $estimateId < 1 || !customer_core_table_exists($conn, M360_ESTIMATE_TABLE)) {
        return null;
    }
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.' . M360_ESTIMATE_TABLE . ' WHERE estimate_id = ?', [$estimateId]);
    return $rows[0] ?? null;
}

/** @return array<string, mixed>|null */
function m360_estimate_fetch_active_for_jobcard($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 * FROM dbo." . M360_ESTIMATE_TABLE . " WHERE jobcard_id = ? AND estimate_status <> N'CANCELLED' ORDER BY estimate_id DESC",
        [$jobcardId]
    );
    return $rows[0] ?? null;
}

/** @return list<array<string, mixed>> */
function m360_estimate_list_items($conn, int $estimateId): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_ESTIMATE_ITEM_TABLE)) {
        return [];
    }
    return customer_core_fetch_rows(
        $conn,
        "SELECT * FROM dbo." . M360_ESTIMATE_ITEM_TABLE . " WHERE estimate_id = ? AND item_status <> N'REMOVED' ORDER BY estimate_item_id",
        [$estimateId]
    );
}

/** @return list<array<string, mixed>> */
function m360_estimate_list_events($conn, int $estimateId, int $limit = 50): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_ESTIMATE_EVENT_TABLE)) {
        return [];
    }
    return customer_core_fetch_rows(
        $conn,
        'SELECT TOP ' . max(1, min(100, $limit)) . ' * FROM dbo.' . M360_ESTIMATE_EVENT_TABLE . ' WHERE estimate_id = ? ORDER BY created_at DESC, event_id DESC',
        [$estimateId]
    );
}

/**
 * @return list<array<string, mixed>>
 */
function m360_estimate_board_list($conn, ?string $filter = null, int $limit = 150): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_ESTIMATE_TABLE)) {
        return [];
    }
    $limit = max(1, min(300, $limit));
    $params = [];
    $where = "(j.technical_status = N'WAITING_FOR_APPROVAL' OR e.estimate_id IS NOT NULL)";

    $filter = strtoupper(trim((string)$filter));
    if ($filter === 'WAITING_FOR_APPROVAL') {
        $where .= " AND j.technical_status = N'WAITING_FOR_APPROVAL' AND (e.estimate_id IS NULL OR e.estimate_status = N'DRAFT')";
    } elseif ($filter !== '' && $filter !== 'ALL') {
        $where .= ' AND e.estimate_status = ?';
        $params[] = $filter;
    }

    $sql = 'SELECT TOP ' . $limit . '
            j.jobcard_id, j.jobcard_number, j.technical_status, j.estimate_status AS jobcard_estimate_status,
            j.parts_gate_status AS jc_parts_gate, j.finance_gate_status AS jc_finance_gate,
            j.diagnosis_summary, j.created_at AS jobcard_created_at,
            e.estimate_id, e.estimate_status, e.total_amount, e.advance_required_amount,
            e.parts_gate_status, e.finance_gate_status, e.approved_at,
            c.full_name AS customer_name, c.primary_mobile AS customer_mobile,
            v.plate_number, v.brand, v.model
        FROM dbo.erp_jobcards j
        LEFT JOIN dbo.erp_estimates e ON e.estimate_id = j.current_estimate_id
            OR (j.current_estimate_id IS NULL AND e.estimate_id = (
                SELECT TOP 1 estimate_id FROM dbo.erp_estimates ex WHERE ex.jobcard_id = j.jobcard_id AND ex.estimate_status <> N\'CANCELLED\' ORDER BY estimate_id DESC
            ))
        LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
        LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
        WHERE ' . $where . '
        ORDER BY j.jobcard_id DESC';

    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $row['estimate_status_label'] = m360_estimate_status_label((string)($row['estimate_status'] ?? 'WAITING_FOR_APPROVAL'));
        $vehicle = trim(trim((string)($row['brand'] ?? '')) . ' ' . trim((string)($row['model'] ?? '')));
        $row['vehicle_label'] = $vehicle !== '' ? $vehicle : '-';
        $rows[] = $row;
    }
    return $rows;
}

/** @return array{ok:bool,message:string,estimate_id:?int} */
function m360_estimate_calculate_totals($conn, int $estimateId): array
{
    $est = m360_estimate_fetch($conn, $estimateId);
    if ($est === null) {
        return ['ok' => false, 'message' => 'برآورد یافت نشد.', 'estimate_id' => null];
    }
    $items = m360_estimate_list_items($conn, $estimateId);
    $subtotal = 0.0;
    $partsRequired = false;
    foreach ($items as $it) {
        $line = (float)($it['line_total'] ?? 0);
        $subtotal += $line;
        if (strtoupper((string)($it['item_type'] ?? '')) === 'PART') {
            $partsRequired = true;
        }
    }
    $discount = (float)($est['discount_amount'] ?? 0);
    $tax = (float)($est['tax_amount'] ?? 0);
    $total = max(0, $subtotal - $discount + $tax);
    $advance = m360_finance_calculate_advance($total);

    $partsEval = m360_parts_gate_evaluate($conn, $estimateId);
    $partsGate = $partsEval['parts_gate_status'];
    if (!$partsRequired) {
        $partsGate = M360_GATE_PARTS_NOT_REQUIRED;
    }

    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET subtotal_amount = ?, total_amount = ?, advance_required_amount = ?, parts_required = ?, parts_gate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?',
        [$subtotal, $total, $advance, $partsRequired ? 1 : 0, $partsGate, $estimateId]
    );

    return ['ok' => true, 'message' => 'جمع‌بندی محاسبه شد.', 'estimate_id' => $estimateId];
}

/** @return array{raw:string,hash:string,expires_at:string} */
function m360_estimate_generate_token(): array
{
    $raw = bin2hex(random_bytes(32));
    return [
        'raw' => $raw,
        'hash' => m360_estimate_hash($raw),
        'expires_at' => gmdate('Y-m-d H:i:s', time() + M360_ESTIMATE_TOKEN_TTL),
    ];
}

function m360_estimate_customer_url(string $rawToken): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $base = ($https ? 'https' : 'http') . '://' . $host;
    return $base . '/customer-estimate-approval.php?token=' . rawurlencode($rawToken);
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string}
 */
function m360_estimate_apply_action($conn, string $action, int $userId, ?int $estimateId = null, ?int $jobcardId = null, array $payload = []): array
{
    if (!is_resource($conn)) {
        return ['ok' => false, 'message' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    $action = strtolower(trim($action));
    if ($estimateId !== null && $estimateId > 0) {
        $est = m360_estimate_fetch($conn, $estimateId);
        if ($est === null) {
            return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
        }
        $jobcardId = (int)($est['jobcard_id'] ?? 0);
    } elseif ($jobcardId !== null && $jobcardId > 0) {
        $est = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
        $estimateId = $est !== null ? (int)$est['estimate_id'] : 0;
    } else {
        return ['ok' => false, 'message' => 'شناسه برآورد یا کارت کار الزامی است.'];
    }

    $jc = m360_estimate_fetch_jobcard($conn, (int)$jobcardId);
    if ($jc === null) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.'];
    }

    $gate = m360_estimate_assert_upstream_gates((int)$jobcardId, $jc);
    if (!$gate['ok'] && !in_array($action, ['create_draft'], true)) {
        return $gate;
    }

    switch ($action) {
        case 'create_draft':
            if ($est !== null && !in_array(strtoupper((string)$est['estimate_status']), [M360_EST_STATUS_CANCELLED], true)) {
                return ['ok' => true, 'message' => 'برآورد فعال از قبل وجود دارد.',];
            }
            $gate = m360_estimate_assert_upstream_gates((int)$jobcardId, $jc);
            if (!$gate['ok']) {
                return $gate;
            }
            $title = 'برآورد کارت کار ' . (string)($jc['jobcard_number'] ?? $jobcardId);
            $ok = customer_core_execute(
                $conn,
                'INSERT INTO dbo.' . M360_ESTIMATE_TABLE . ' (jobcard_id, customer_id, vehicle_id, estimate_title, estimate_status, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $jobcardId,
                    (int)($jc['customer_id'] ?? 0) ?: null,
                    (int)($jc['vehicle_id'] ?? 0) ?: null,
                    $title,
                    M360_EST_STATUS_DRAFT,
                    $userId,
                ]
            );
            if ($ok === false) {
                return ['ok' => false, 'message' => 'ایجاد برآورد ناموفق بود.'];
            }
            $newId = (int)(customer_core_scalar(
                $conn,
                'SELECT TOP 1 estimate_id FROM dbo.' . M360_ESTIMATE_TABLE . ' WHERE jobcard_id = ? ORDER BY estimate_id DESC',
                [$jobcardId]
            ) ?? 0);
            if (customer_core_column_exists($conn, 'erp_jobcards', 'current_estimate_id')) {
                customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET current_estimate_id = ?, estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE jobcard_id = ?', [$newId, M360_EST_STATUS_DRAFT, $jobcardId]);
            }
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_DRAFT_CREATED', $newId, null, $userId);
            m360_estimate_jobcard_history($conn, $jobcardId, 'JOBCARD_ESTIMATE_CREATED', null, M360_EST_STATUS_DRAFT, 'Draft estimate created', $userId);
            return ['ok' => true, 'message' => 'پیش‌نویس برآورد ایجاد شد.'];

        case 'add_item':
            if ($estimateId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'ابتدا برآورد ایجاد کنید.'];
            }
            if (!in_array(strtoupper((string)$est['estimate_status']), [M360_EST_STATUS_DRAFT, M360_EST_STATUS_INTERNAL_REVIEW, M360_EST_STATUS_REVISION], true)) {
                return ['ok' => false, 'message' => 'پس از ارسال به مشتری، افزودن آیتم مستقیم مجاز نیست.'];
            }
            $type = strtoupper(trim((string)($payload['item_type'] ?? 'SERVICE')));
            if (!in_array($type, M360_EST_ITEM_TYPES, true)) {
                $type = 'SERVICE';
            }
            $title = trim((string)($payload['item_title'] ?? ''));
            if ($title === '') {
                return ['ok' => false, 'message' => 'عنوان آیتم الزامی است.'];
            }
            $qty = max(0.01, (float)($payload['quantity'] ?? 1));
            $price = max(0, (float)($payload['unit_price'] ?? 0));
            $line = round($qty * $price, 2);
            $partReq = $type === 'PART' ? 1 : 0;
            customer_core_execute(
                $conn,
                'INSERT INTO dbo.' . M360_ESTIMATE_ITEM_TABLE . ' (estimate_id, jobcard_id, item_type, item_title, item_description, quantity, unit_price, line_total, part_required) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$estimateId, $jobcardId, $type, mb_substr($title, 0, 300), trim((string)($payload['item_description'] ?? '')) ?: null, $qty, $price, $line, $partReq]
            );
            m360_estimate_calculate_totals($conn, $estimateId);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_ITEM_ADDED', $estimateId, $title, $userId);
            return ['ok' => true, 'message' => 'آیتم اضافه شد.'];

        case 'remove_draft_item':
            $itemId = (int)($payload['estimate_item_id'] ?? 0);
            if ($estimateId < 1 || $itemId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'آیتم نامعتبر است.'];
            }
            if (!in_array(strtoupper((string)$est['estimate_status']), [M360_EST_STATUS_DRAFT, M360_EST_STATUS_INTERNAL_REVIEW], true)) {
                return ['ok' => false, 'message' => 'حذف آیتم پس از ارسال مجاز نیست.'];
            }
            customer_core_execute($conn, "UPDATE dbo." . M360_ESTIMATE_ITEM_TABLE . " SET item_status = N'REMOVED' WHERE estimate_item_id = ? AND estimate_id = ?", [$itemId, $estimateId]);
            m360_estimate_calculate_totals($conn, $estimateId);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_ITEM_REMOVED', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'آیتم حذف شد.'];

        case 'calculate_totals':
            if ($estimateId < 1) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            $r = m360_estimate_calculate_totals($conn, $estimateId);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_TOTALS_CALCULATED', $estimateId, null, $userId);
            return ['ok' => $r['ok'], 'message' => $r['message']];

        case 'internal_review':
            if ($estimateId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_EST_STATUS_INTERNAL_REVIEW, $estimateId]);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_INTERNAL_REVIEW', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'بازبینی داخلی ثبت شد.'];

        case 'send_to_customer':
            if ($estimateId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            $items = m360_estimate_list_items($conn, $estimateId);
            if ($items === []) {
                return ['ok' => false, 'message' => 'حداقل یک آیتم برای ارسال لازم است.'];
            }
            m360_estimate_calculate_totals($conn, $estimateId);
            $tok = m360_estimate_generate_token();
            customer_core_execute(
                $conn,
                'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET estimate_status = ?, secure_token_hash = ?, secure_token_expires_at = ?, sent_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE estimate_id = ?',
                [M360_EST_STATUS_SENT, $tok['hash'], $tok['expires_at'], $estimateId]
            );
            if (customer_core_column_exists($conn, 'erp_jobcards', 'estimate_status')) {
                customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET estimate_status = N\'ESTIMATE_SENT\', updated_at = SYSUTCDATETIME() WHERE jobcard_id = ?', [$jobcardId]);
            }
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_SENT_TO_CUSTOMER', $estimateId, null, $userId);
            m360_estimate_jobcard_history($conn, $jobcardId, 'JOBCARD_ESTIMATE_SENT', null, 'ESTIMATE_SENT', 'Estimate sent to customer', $userId);
            $_SESSION['m360_estimate_last_link_' . $estimateId] = m360_estimate_customer_url($tok['raw']);
            return ['ok' => true, 'message' => 'برآورد برای مشتری ارسال شد. لینک: ' . m360_estimate_customer_url($tok['raw'])];

        case 'mark_parts_required':
            if ($estimateId < 1) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET parts_required = 1, parts_gate_status = ?, estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_GATE_PARTS_PENDING, M360_EST_STATUS_PARTS_PENDING, $estimateId]);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_PARTS_GATE_PENDING', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'نیاز قطعه ثبت شد.'];

        case 'mark_parts_not_required':
            if ($estimateId < 1) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET parts_required = 0, parts_gate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_GATE_PARTS_NOT_REQUIRED, $estimateId]);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_PARTS_GATE_NOT_REQUIRED', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'قطعه لازم نیست.'];

        case 'clear_parts_gate':
            if ($estimateId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            $pe = m360_parts_gate_evaluate($conn, $estimateId);
            if ($pe['parts_gate_status'] === M360_GATE_PARTS_PENDING && (bool)($est['parts_required'] ?? false)) {
                m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_PARTS_GATE_PENDING_PURCHASE_REQUIRED', $estimateId, $pe['message'], $userId);
                return ['ok' => false, 'message' => 'قطعات هنوز آماده نیستند.'];
            }
            $status = $pe['parts_gate_status'];
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET parts_gate_status = ?, estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [$status, M360_EST_STATUS_PARTS_CLEARED, $estimateId]);
            if (customer_core_column_exists($conn, 'erp_jobcards', 'parts_gate_status')) {
                customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET parts_gate_status = ? WHERE jobcard_id = ?', [$status, $jobcardId]);
            }
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_PARTS_GATE_CLEARED', $estimateId, null, $userId);
            m360_estimate_jobcard_history($conn, $jobcardId, 'JOBCARD_PARTS_GATE_CLEARED', null, $status, 'Parts gate cleared', $userId);
            return ['ok' => true, 'message' => 'گیت قطعه باز شد.'];

        case 'mark_finance_required':
            if ($estimateId < 1) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            m360_estimate_calculate_totals($conn, $estimateId);
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET finance_required = 1, finance_gate_status = ?, estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_GATE_FINANCE_PENDING, M360_EST_STATUS_FIN_PENDING, $estimateId]);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_FINANCE_GATE_PENDING', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'نیاز مالی ثبت شد.'];

        case 'mark_finance_not_required':
            if ($estimateId < 1) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET finance_required = 0, finance_gate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_GATE_FINANCE_NOT_REQUIRED, $estimateId]);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_FINANCE_GATE_NOT_REQUIRED', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'علی‌الحساب لازم نیست.'];

        case 'clear_finance_gate':
            if ($estimateId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            $fe = m360_finance_gate_evaluate($conn, $estimateId);
            if ($fe['finance_gate_status'] === M360_GATE_FINANCE_PENDING && (bool)($est['finance_required'] ?? true)) {
                m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_FINANCE_GATE_BLOCKED', $estimateId, $fe['message'], $userId);
                return ['ok' => false, 'message' => 'مجوز مالی هنوز کامل نیست. علی‌الحساب: ' . number_format($fe['advance_required']) . ' تومان'];
            }
            $status = $fe['finance_gate_status'];
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET finance_gate_status = ?, estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [$status, M360_EST_STATUS_FIN_CLEARED, $estimateId]);
            if (customer_core_column_exists($conn, 'erp_jobcards', 'finance_gate_status')) {
                customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET finance_gate_status = ? WHERE jobcard_id = ?', [$status, $jobcardId]);
            }
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_FINANCE_GATE_CLEARED', $estimateId, null, $userId);
            m360_estimate_jobcard_history($conn, $jobcardId, 'JOBCARD_FINANCE_GATE_CLEARED', null, $status, 'Finance gate cleared', $userId);
            return ['ok' => true, 'message' => 'گیت مالی باز شد.'];

        case 'approve_for_work':
            if ($estimateId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            $est = m360_estimate_fetch($conn, $estimateId);
            $can = m360_gates_can_approve_for_work($conn, $est);
            if (!$can['ok']) {
                m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_APPROVED_FOR_WORK_BLOCKED_GATE', $estimateId, 'Gates not cleared', $userId);
                return ['ok' => false, 'message' => 'ادامه کار مجاز نیست — تأیید مشتری، قطعه یا مالی کامل نشده است.'];
            }
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_EST_STATUS_APPROVED_WORK, $estimateId]);
            if (customer_core_column_exists($conn, 'erp_jobcards', 'approved_for_work_at')) {
                customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET estimate_status = N\'APPROVED_FOR_WORK\', approved_for_work_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE jobcard_id = ?', [$jobcardId]);
            }
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_APPROVED_FOR_WORK', $estimateId, null, $userId);
            m360_estimate_jobcard_history($conn, $jobcardId, 'JOBCARD_APPROVED_FOR_WORK', null, 'APPROVED_FOR_WORK', 'Approved for work', $userId);
            return ['ok' => true, 'message' => 'پرونده برای ادامه کار مجاز شد.'];

        case 'cancel':
            if ($estimateId < 1) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_EST_STATUS_CANCELLED, $estimateId]);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_CANCELLED', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'برآورد لغو شد.'];

        default:
            return ['ok' => false, 'message' => 'عملیات نامعتبر است.'];
    }
}
