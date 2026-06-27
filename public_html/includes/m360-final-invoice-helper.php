<?php
declare(strict_types=1);

/**
 * MOGHARE360 P7 — Final invoice (no erp_invoices INSERT, no payment gateway, no accounting voucher).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-qc-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-delivery-readiness-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-work-execution-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-parts-consumption-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-technical-operation-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-settlement-helper.php';

const M360_FI_CSRF = 'final_invoice_p7';
const M360_FI_TABLE = 'erp_final_invoices';
const M360_FI_ITEMS = 'erp_final_invoice_items';
const M360_FI_EVENTS = 'erp_delivery_events';
const M360_FI_HISTORY = 'erp_jobcard_change_history';

const M360_FI_DRAFT = 'DRAFT';
const M360_FI_CALCULATED = 'CALCULATED';
const M360_FI_FINALIZED = 'FINALIZED';
const M360_FI_CANCELLED = 'CANCELLED';

const M360_FI_VARIANCE_MAX_PERCENT = 0.10;
const M360_FI_TOKEN_TTL_DAYS = 7;

const M360_FI_VAR_WITHIN = 'WITHIN_LIMIT';
const M360_FI_VAR_EXCEEDS = 'EXCEEDS_LIMIT';
const M360_FI_VAR_OVERRIDE = 'OVERRIDE_APPROVED';

/** @var array<string, string> */
const M360_FI_STATUS_LABELS_FA = [
    M360_FI_DRAFT => 'پیش‌نویس',
    M360_FI_CALCULATED => 'محاسبه‌شده',
    M360_FI_FINALIZED => 'نهایی',
    M360_FI_CANCELLED => 'لغو شده',
];

function m360_fi_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_fi_require_staff(): void
{
    erp_auth_context_start();
    if (erp_auth_current_user_id() === null || erp_auth_current_user_id() <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_fi_hash(string $raw): string
{
    return hash('sha256', $raw);
}

/**
 * @return array{raw:string,hash:string,expires_at:string}
 */
function m360_fi_generate_token(): array
{
    $raw = bin2hex(random_bytes(32));
    $expires = gmdate('Y-m-d H:i:s', time() + (M360_FI_TOKEN_TTL_DAYS * 86400));
    return [
        'raw' => $raw,
        'hash' => m360_fi_hash($raw),
        'expires_at' => $expires,
    ];
}

/** @return array<string, mixed>|null */
function m360_fi_fetch_jobcard($conn, int $jobcardId): ?array
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
function m360_fi_fetch_invoice($conn, int $invoiceId): ?array
{
    if (!is_resource($conn) || $invoiceId < 1 || !customer_core_table_exists($conn, M360_FI_TABLE)) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_FI_TABLE . ' WHERE final_invoice_id = ?',
        [$invoiceId]
    );
    return $rows[0] ?? null;
}

/** @return array<string, mixed>|null */
function m360_fi_fetch_active($conn, $jobcardId): ?array
{
    $jobcardId = (int)$jobcardId;
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, M360_FI_TABLE)) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 * FROM dbo." . M360_FI_TABLE . "
         WHERE jobcard_id = ? AND invoice_status IN (N'DRAFT', N'CALCULATED')
         ORDER BY final_invoice_id DESC",
        [$jobcardId]
    );
    if ($rows !== []) {
        return $rows[0];
    }
    $rows = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 * FROM dbo." . M360_FI_TABLE . "
         WHERE jobcard_id = ? AND invoice_status = N'FINALIZED'
         ORDER BY final_invoice_id DESC",
        [$jobcardId]
    );
    return $rows[0] ?? null;
}

/** @param array<string, mixed> $row */
function m360_p7_is_delivery_ready(array $row): bool
{
    $qc = strtoupper(trim((string)($row['qc_status'] ?? '')));
    if ($qc === M360_QC_DELIVERY_READY) {
        return true;
    }
    $readiness = strtoupper(trim((string)($row['delivery_readiness_status'] ?? '')));
    if ($readiness === M360_DEL_READY_STATUS) {
        return true;
    }
    $js = strtoupper(trim((string)($row['jobcard_status'] ?? '')));
    return $js === 'DELIVERY_READY';
}

/**
 * @param array<string, mixed> $row
 * @return array{ok:bool,message:string,block_event:?string}
 */
function m360_p7_assert_gates($conn, int $jobcardId, array $row): array
{
    $block = 'FINAL_INVOICE_FINALIZE_BLOCKED_GATE';

    if (!m360_p7_is_delivery_ready($row)) {
        return ['ok' => false, 'message' => 'پرونده هنوز آماده تحویل (DELIVERY_READY) نیست.', 'block_event' => $block];
    }

    if (!function_exists('m360_contract_can_continue_to_p2') || !m360_contract_can_continue_to_p2($jobcardId)) {
        return ['ok' => false, 'message' => 'قرارداد پذیرش معتبر نیست.', 'block_event' => $block];
    }

    $estStatus = strtoupper(trim((string)($row['estimate_status'] ?? '')));
    if ($estStatus !== M360_EST_STATUS_APPROVED_WORK) {
        $est = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
        if ($est === null || strtoupper((string)($est['estimate_status'] ?? '')) !== M360_EST_STATUS_APPROVED_WORK) {
            return ['ok' => false, 'message' => 'برآورد (P4) باید APPROVED_FOR_WORK باشد.', 'block_event' => $block];
        }
    }

    if (trim((string)($row['technical_completion_notes'] ?? '')) === '') {
        return ['ok' => false, 'message' => 'یادداشت تکمیل فنی (P5) وجود ندارد.', 'block_event' => $block];
    }

    $qc = strtoupper(trim((string)($row['qc_status'] ?? '')));
    if (!in_array($qc, [M360_QC_PASSED, M360_QC_DELIVERY_READY], true)) {
        return ['ok' => false, 'message' => 'QC باید Pass یا آماده تحویل باشد.', 'block_event' => $block];
    }

    $wx = strtoupper(trim((string)($row['work_execution_status'] ?? '')));
    $workDone = in_array($wx, [M360_WX_READY_QC, M360_QC_PASSED, M360_QC_DELIVERY_READY], true)
        || trim((string)($row['work_completed_at'] ?? '')) !== ''
        || strtoupper((string)($row['technical_status'] ?? '')) === 'TECHNICAL_DONE';
    if (!$workDone) {
        return ['ok' => false, 'message' => 'اجرای کار (P5) تکمیل نشده است.', 'block_event' => $block];
    }

    return ['ok' => true, 'message' => '', 'block_event' => null];
}

function m360_fi_write_history(
    $conn,
    int $jobcardId,
    string $type,
    ?string $prev,
    ?string $new,
    string $summary,
    int $userId
): void {
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_FI_HISTORY)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_FI_HISTORY . ' (jobcard_id, change_type, previous_status, new_status, change_summary, changed_by_user_id) VALUES (?, ?, ?, ?, ?, ?)',
        [$jobcardId, $type, $prev, $new, $summary, $userId > 0 ? $userId : null]
    );
}

function m360_fi_write_event(
    $conn,
    int $jobcardId,
    string $name,
    ?string $note,
    int $userId,
    int $invoiceId = 0,
    ?string $ip = null,
    ?string $ua = null
): void {
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_FI_EVENTS)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_FI_EVENTS . ' (jobcard_id, final_invoice_id, event_name, event_note, event_ip, event_user_agent, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [
            $jobcardId,
            $invoiceId > 0 ? $invoiceId : null,
            $name,
            $note,
            $ip ?? customer_core_client_ip(),
            $ua ?? customer_core_user_agent(),
            $userId > 0 ? $userId : null,
        ]
    );
}

/** @return list<string> */
function m360_fi_board_filters(): array
{
    return [
        'DELIVERY_READY',
        M360_FI_DRAFT,
        M360_FI_CALCULATED,
        M360_FI_FINALIZED,
        'SETTLEMENT_PENDING',
        'SETTLED',
        'DELIVERY_SIGNED',
        'DELIVERED',
        'CLOSED',
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function m360_fi_board_list($conn, ?string $filter = null, int $limit = 150): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, 'erp_jobcards')) {
        return [];
    }

    $limit = max(1, min(300, $limit));
    $filter = strtoupper(trim((string)$filter));
    $params = [];
    $where = '1=1';

    if ($filter === 'DELIVERY_READY') {
        $where .= " AND (j.qc_status = N'DELIVERY_READY' OR j.delivery_readiness_status = N'READY' OR j.jobcard_status = N'DELIVERY_READY')";
        $where .= " AND (fi.final_invoice_id IS NULL OR fi.invoice_status IN (N'CANCELLED'))";
    } elseif ($filter === M360_FI_DRAFT || $filter === M360_FI_CALCULATED || $filter === M360_FI_FINALIZED) {
        $where .= ' AND fi.invoice_status = ?';
        $params[] = $filter;
    } elseif ($filter === 'SETTLEMENT_PENDING') {
        $where .= " AND fi.invoice_status = N'FINALIZED' AND (j.settlement_status IN (N'PAYMENT_PENDING', N'PARTIAL_SETTLED') OR j.settlement_status IS NULL)";
    } elseif ($filter === 'SETTLED') {
        $where .= " AND j.settlement_status IN (N'SETTLED', N'MANAGER_RELEASE_APPROVED')";
    } elseif ($filter === 'DELIVERY_SIGNED') {
        $where .= " AND j.customer_delivery_status = N'DELIVERY_SIGNED'";
    } elseif ($filter === 'DELIVERED') {
        $where .= " AND (j.customer_delivery_status = N'VEHICLE_RELEASED' OR j.vehicle_released_at IS NOT NULL)";
    } elseif ($filter === 'CLOSED') {
        $where .= " AND j.jobcard_status = N'CLOSED'";
    } else {
        $where .= " AND (j.qc_status = N'DELIVERY_READY' OR j.delivery_readiness_status = N'READY' OR j.jobcard_status = N'DELIVERY_READY' OR fi.final_invoice_id IS NOT NULL)";
    }

    $fiJoin = customer_core_table_exists($conn, M360_FI_TABLE)
        ? 'LEFT JOIN dbo.' . M360_FI_TABLE . ' fi ON fi.final_invoice_id = j.current_final_invoice_id
           OR (j.current_final_invoice_id IS NULL AND fi.final_invoice_id = (
               SELECT TOP 1 final_invoice_id FROM dbo.' . M360_FI_TABLE . ' fx
               WHERE fx.jobcard_id = j.jobcard_id AND fx.invoice_status <> N\'CANCELLED\'
               ORDER BY fx.final_invoice_id DESC
           ))'
        : 'LEFT JOIN (SELECT NULL AS final_invoice_id, NULL AS invoice_status, NULL AS total_amount, NULL AS jobcard_id) fi ON 1=0';

    $sql = 'SELECT TOP ' . $limit . '
            j.jobcard_id, j.jobcard_number, j.qc_status, j.delivery_readiness_status, j.jobcard_status,
            j.final_invoice_status, j.current_final_invoice_id, j.final_invoice_amount,
            j.settlement_status, j.settlement_amount_paid, j.settlement_remaining_amount,
            j.customer_delivery_status, j.vehicle_released_at,
            fi.final_invoice_id, fi.invoice_status, fi.total_amount AS invoice_total,
            c.full_name AS customer_name, c.primary_mobile AS customer_mobile,
            v.plate_number, v.brand, v.model
        FROM dbo.erp_jobcards j
        ' . $fiJoin . '
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
        $vehicle = trim(trim((string)($row['brand'] ?? '')) . ' ' . trim((string)($row['model'] ?? '')));
        $row['vehicle_label'] = $vehicle !== '' ? $vehicle : '-';
        $invStatus = (string)($row['invoice_status'] ?? $row['final_invoice_status'] ?? '');
        $row['invoice_status_label'] = M360_FI_STATUS_LABELS_FA[strtoupper($invStatus)] ?? ($invStatus !== '' ? $invStatus : '—');
        $rows[] = $row;
    }
    return $rows;
}

/** @return list<array<string, mixed>> */
function m360_fi_list_items($conn, int $invoiceId): array
{
    if (!is_resource($conn) || $invoiceId < 1 || !customer_core_table_exists($conn, M360_FI_ITEMS)) {
        return [];
    }
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.' . M360_FI_ITEMS . ' WHERE final_invoice_id = ? ORDER BY final_invoice_item_id',
        [$invoiceId]
    );
}

function m360_fi_status_label(string $status): string
{
    return M360_FI_STATUS_LABELS_FA[strtoupper(trim($status))] ?? $status;
}

function m360_fi_generate_invoice_no(int $jobcardId): string
{
    return 'FI-' . $jobcardId . '-' . gmdate('YmdHis');
}

/**
 * @return array{subtotal:float,discount:float,tax:float,total:float,estimate_total:float,variance:float,variance_status:string}
 */
function m360_fi_eval_variance(float $estimateTotal, float $total, ?string $overrideReason = null): array
{
    $variance = round($total - $estimateTotal, 2);
    $status = M360_FI_VAR_WITHIN;
    if ($estimateTotal > 0 && $variance > 0) {
        $pct = $variance / $estimateTotal;
        if ($pct > M360_FI_VARIANCE_MAX_PERCENT) {
            $status = trim((string)$overrideReason) !== '' ? M360_FI_VAR_OVERRIDE : M360_FI_VAR_EXCEEDS;
        }
    }
    return [
        'subtotal' => 0.0,
        'discount' => 0.0,
        'tax' => 0.0,
        'total' => $total,
        'estimate_total' => $estimateTotal,
        'variance' => $variance,
        'variance_status' => $status,
    ];
}

function m360_fi_update_jobcard_invoice($conn, int $jobcardId, int $invoiceId, string $status, float $amount): void
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return;
    }
    $sets = [
        'final_invoice_status = ?',
        'current_final_invoice_id = ?',
        'final_invoice_amount = ?',
    ];
    $params = [$status, $invoiceId, $amount];
    if ($status === M360_FI_FINALIZED && customer_core_column_exists($conn, 'erp_jobcards', 'settlement_status')) {
        $currentSettle = strtoupper(trim((string)(customer_core_scalar(
            $conn,
            'SELECT TOP 1 settlement_status FROM dbo.erp_jobcards WHERE jobcard_id = ?',
            [$jobcardId]
        ) ?? '')));
        if ($currentSettle === '') {
            $sets[] = 'settlement_status = ?';
            $params[] = M360_SETTLE_PAYMENT_PENDING;
        }
    }
    if (customer_core_column_exists($conn, 'erp_jobcards', 'updated_at')) {
        $sets[] = 'updated_at = SYSUTCDATETIME()';
    }
    $params[] = $jobcardId;
    customer_core_execute(
        $conn,
        'UPDATE dbo.erp_jobcards SET ' . implode(', ', $sets) . ' WHERE jobcard_id = ?',
        $params
    );
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_fi_recalc_invoice_totals($conn, int $invoiceId): array
{
    if (!is_resource($conn) || $invoiceId < 1) {
        return ['ok' => false, 'message' => 'شناسه فاکتور نامعتبر است.'];
    }
    $items = m360_fi_list_items($conn, $invoiceId);
    $subtotal = 0.0;
    foreach ($items as $item) {
        if (strtoupper((string)($item['source_type'] ?? '')) === 'DISCOUNT') {
            continue;
        }
        $subtotal += (float)($item['line_total'] ?? 0);
    }
    $inv = m360_fi_fetch_invoice($conn, $invoiceId);
    if ($inv === null) {
        return ['ok' => false, 'message' => 'فاکتور یافت نشد.'];
    }
    $discount = max(0.0, (float)($inv['discount_amount'] ?? 0));
    $tax = max(0.0, (float)($inv['tax_amount'] ?? 0));
    $total = max(0.0, round($subtotal - $discount + $tax, 2));
    $estimateTotal = (float)($inv['estimate_total_amount'] ?? 0);
    $varEval = m360_fi_eval_variance($estimateTotal, $total, (string)($inv['variance_override_reason'] ?? ''));

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_FI_TABLE . ' SET subtotal_amount = ?, total_amount = ?, variance_amount = ?, variance_status = ?, updated_at = SYSUTCDATETIME() WHERE final_invoice_id = ?',
        [$subtotal, $total, $varEval['variance'], $varEval['variance_status'], $invoiceId]
    );
    return $ok === false
        ? ['ok' => false, 'message' => 'به‌روزرسانی مبالغ فاکتور ناموفق بود.']
        : ['ok' => true, 'message' => ''];
}

function m360_fi_delete_calculated_items($conn, int $invoiceId): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_FI_ITEMS)) {
        return;
    }
    customer_core_execute(
        $conn,
        "DELETE FROM dbo." . M360_FI_ITEMS . " WHERE final_invoice_id = ? AND source_type IN (N'ESTIMATE_ITEM', N'SERVICE_OPERATION', N'PART_USAGE')",
        [$invoiceId]
    );
}

function m360_fi_insert_item(
    $conn,
    int $invoiceId,
    int $jobcardId,
    string $sourceType,
    ?int $sourceId,
    string $itemType,
    string $title,
    ?string $description,
    float $qty,
    float $unitPrice
): bool {
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_FI_ITEMS)) {
        return false;
    }
    $lineTotal = round($qty * $unitPrice, 2);
    return customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_FI_ITEMS . ' (final_invoice_id, jobcard_id, source_type, source_id, item_type, item_title, item_description, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$invoiceId, $jobcardId, $sourceType, $sourceId, $itemType, $title, $description, $qty, $unitPrice, $lineTotal]
    ) !== false;
}

function m360_fi_estimate_part_price($conn, int $jobcardId, int $partId, int $estimateItemId = 0): float
{
    if ($estimateItemId > 0) {
        $rows = customer_core_fetch_rows(
            $conn,
            "SELECT TOP 1 unit_price FROM dbo.erp_estimate_items WHERE estimate_item_id = ? AND jobcard_id = ?",
            [$estimateItemId, $jobcardId]
        );
        if ($rows !== []) {
            return (float)($rows[0]['unit_price'] ?? 0);
        }
    }
    if ($partId > 0) {
        $rows = customer_core_fetch_rows(
            $conn,
            "SELECT TOP 1 unit_price FROM dbo.erp_estimate_items WHERE jobcard_id = ? AND item_type = N'PART' AND part_id = ? AND item_status <> N'REMOVED' ORDER BY estimate_item_id DESC",
            [$jobcardId, $partId]
        );
        if ($rows !== []) {
            return (float)($rows[0]['unit_price'] ?? 0);
        }
    }
    return 0.0;
}

function m360_fi_service_operation_price($conn, int $jobcardId, string $serviceTitle): float
{
    $title = trim($serviceTitle);
    if ($title === '') {
        return 0.0;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 unit_price FROM dbo.erp_estimate_items
         WHERE jobcard_id = ? AND item_type IN (N'SERVICE', N'LABOR') AND item_status <> N'REMOVED'
         AND (item_title = ? OR item_description LIKE ?)
         ORDER BY estimate_item_id DESC",
        [$jobcardId, $title, '%' . $title . '%']
    );
    return (float)($rows[0]['unit_price'] ?? 0);
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_fi_calculate($conn, int $invoiceId, int $jobcardId, int $userId): array
{
    if (!is_resource($conn) || $invoiceId < 1 || $jobcardId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات نامعتبر است.'];
    }
    if (!customer_core_table_exists($conn, M360_FI_TABLE) || !customer_core_table_exists($conn, M360_FI_ITEMS)) {
        return ['ok' => false, 'message' => 'جداول فاکتور نهایی یافت نشد.'];
    }

    $inv = m360_fi_fetch_invoice($conn, $invoiceId);
    if ($inv === null || (int)($inv['jobcard_id'] ?? 0) !== $jobcardId) {
        return ['ok' => false, 'message' => 'فاکتور با کارت کار مطابقت ندارد.'];
    }
    $invStatus = strtoupper((string)($inv['invoice_status'] ?? ''));
    if ($invStatus === M360_FI_FINALIZED) {
        return ['ok' => true, 'message' => 'فاکتور قبلاً نهایی شده است.'];
    }
    if ($invStatus === M360_FI_CANCELLED) {
        return ['ok' => false, 'message' => 'فاکتور لغو شده قابل محاسبه نیست.'];
    }

    $est = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
    if ($est === null) {
        return ['ok' => false, 'message' => 'برآورد فعال یافت نشد.'];
    }
    $estimateId = (int)$est['estimate_id'];
    $estimateTotal = (float)($est['total_amount'] ?? 0);

    m360_fi_delete_calculated_items($conn, $invoiceId);

    $estItems = m360_estimate_list_items($conn, $estimateId);
    foreach ($estItems as $item) {
        $itemType = strtoupper((string)($item['item_type'] ?? 'OTHER'));
        if ($itemType === 'PART') {
            continue;
        }
        m360_fi_insert_item(
            $conn,
            $invoiceId,
            $jobcardId,
            'ESTIMATE_ITEM',
            (int)($item['estimate_item_id'] ?? 0),
            $itemType !== '' ? $itemType : 'OTHER',
            (string)($item['item_title'] ?? 'آیتم برآورد'),
            isset($item['item_description']) ? (string)$item['item_description'] : null,
            (float)($item['quantity'] ?? 1),
            (float)($item['unit_price'] ?? 0)
        );
    }

    if (customer_core_table_exists($conn, M360_TECHNICAL_SERVICE_TABLE)) {
        $serviceOps = customer_core_fetch_rows(
            $conn,
            "SELECT service_operation_id, service_title, service_description, service_status
             FROM dbo." . M360_TECHNICAL_SERVICE_TABLE . "
             WHERE jobcard_id = ? AND is_active = 1 AND service_status = N'DONE'
             ORDER BY service_operation_id",
            [$jobcardId]
        );
        foreach ($serviceOps as $so) {
            $soId = (int)($so['service_operation_id'] ?? 0);
            $title = trim((string)($so['service_title'] ?? 'سرویس'));
            $price = m360_fi_service_operation_price($conn, $jobcardId, $title);
            m360_fi_insert_item(
                $conn,
                $invoiceId,
                $jobcardId,
                'SERVICE_OPERATION',
                $soId,
                'SERVICE',
                $title,
                isset($so['service_description']) ? (string)$so['service_description'] : null,
                1.0,
                $price
            );
        }
    }

    if (customer_core_table_exists($conn, M360_PART_USAGE_TABLE)) {
        $usageCols = 'part_usage_id, jobcard_id, part_id, quantity, usage_status';
        if (customer_core_column_exists($conn, M360_PART_USAGE_TABLE, 'estimate_item_id')) {
            $usageCols .= ', estimate_item_id';
        }
        $partUsages = customer_core_fetch_rows(
            $conn,
            'SELECT ' . $usageCols . ' FROM dbo.' . M360_PART_USAGE_TABLE . ' WHERE jobcard_id = ? AND is_active = 1 AND usage_status = N\'USED\' ORDER BY part_usage_id',
            [$jobcardId]
        );
    } else {
        $partUsages = [];
    }
    foreach ($partUsages as $usage) {
        $partId = (int)($usage['part_id'] ?? 0);
        $usageId = (int)($usage['part_usage_id'] ?? 0);
        $estItemId = (int)($usage['estimate_item_id'] ?? 0);
        $qty = (float)($usage['quantity'] ?? 1);
        $unitPrice = m360_fi_estimate_part_price($conn, $jobcardId, $partId, $estItemId);
        $partTitle = 'قطعه #' . $partId;
        if ($partId > 0 && customer_core_table_exists($conn, 'erp_parts')) {
            $prows = customer_core_fetch_rows(
                $conn,
                'SELECT TOP 1 part_name, part_code FROM dbo.erp_parts WHERE part_id = ?',
                [$partId]
            );
            if ($prows !== []) {
                $partTitle = trim((string)($prows[0]['part_name'] ?? $prows[0]['part_code'] ?? $partTitle));
            }
        }
        m360_fi_insert_item(
            $conn,
            $invoiceId,
            $jobcardId,
            'PART_USAGE',
            $usageId,
            'PART',
            $partTitle,
            null,
            $qty,
            $unitPrice
        );
    }

    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_FI_TABLE . ' SET estimate_id = ?, estimate_total_amount = ?, invoice_status = ?, updated_at = SYSUTCDATETIME() WHERE final_invoice_id = ? AND jobcard_id = ?',
        [$estimateId, $estimateTotal, M360_FI_CALCULATED, $invoiceId, $jobcardId]
    );

    $totals = m360_fi_recalc_invoice_totals($conn, $invoiceId);
    if (!$totals['ok']) {
        return $totals;
    }

    m360_fi_update_jobcard_invoice($conn, $jobcardId, $invoiceId, M360_FI_CALCULATED, (float)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 total_amount FROM dbo.' . M360_FI_TABLE . ' WHERE final_invoice_id = ?',
        [$invoiceId]
    ) ?? 0));

    m360_fi_write_history($conn, $jobcardId, 'JOBCARD_FINAL_INVOICE_CALCULATED', M360_FI_DRAFT, M360_FI_CALCULATED, 'P7 final invoice calculated', $userId);
    m360_fi_write_event($conn, $jobcardId, 'FINAL_INVOICE_CALCULATED', null, $userId, $invoiceId);

    return ['ok' => true, 'message' => 'فاکتور نهایی محاسبه شد.'];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string,invoice_id:int,delivery_token:?string}
 */
function m360_fi_apply_action($conn, string $action, int $userId, int $jobcardId, int $invoiceId, array $payload = []): array
{
    $empty = ['ok' => false, 'message' => 'اطلاعات نامعتبر است.', 'invoice_id' => 0, 'delivery_token' => null];
    if (!is_resource($conn) || $jobcardId < 1) {
        return $empty;
    }
    if (!customer_core_table_exists($conn, M360_FI_TABLE)) {
        return ['ok' => false, 'message' => 'جدول فاکتور نهایی یافت نشد.', 'invoice_id' => 0, 'delivery_token' => null];
    }

    $action = strtolower(trim($action));
    $row = m360_fi_fetch_jobcard($conn, $jobcardId);
    if ($row === null) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.', 'invoice_id' => 0, 'delivery_token' => null];
    }

    if ($invoiceId < 1) {
        $active = m360_fi_fetch_active($conn, $jobcardId);
        $invoiceId = (int)($active['final_invoice_id'] ?? 0);
    }

    switch ($action) {
        case 'create_draft_invoice':
            return m360_fi_action_create_draft($conn, $jobcardId, $userId, $row);

        case 'calculate_invoice':
            if ($invoiceId < 1) {
                return ['ok' => false, 'message' => 'فاکتور پیش‌نویس یافت نشد.', 'invoice_id' => 0, 'delivery_token' => null];
            }
            $calc = m360_fi_calculate($conn, $invoiceId, $jobcardId, $userId);
            return [
                'ok' => $calc['ok'],
                'message' => $calc['message'],
                'invoice_id' => $invoiceId,
                'delivery_token' => null,
            ];

        case 'add_approved_manual_item':
            return m360_fi_action_add_manual_item($conn, $jobcardId, $invoiceId, $userId, $payload);

        case 'apply_discount':
            return m360_fi_action_apply_discount($conn, $jobcardId, $invoiceId, $userId, $payload);

        case 'finalize_invoice':
            return m360_fi_action_finalize($conn, $jobcardId, $invoiceId, $userId, $row, $payload);

        case 'recalculate_settlement':
            return m360_fi_action_recalculate_settlement($conn, $jobcardId, $invoiceId, $userId);

        case 'notify_customer':
            return m360_fi_action_notify_customer($conn, $jobcardId, $invoiceId, $userId);

        case 'cancel_draft_invoice':
            return m360_fi_action_cancel_draft($conn, $jobcardId, $invoiceId, $userId);

        default:
            return ['ok' => false, 'message' => 'عملیات نامعتبر است.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }
}

/**
 * @param array<string, mixed> $row
 * @return array{ok:bool,message:string,invoice_id:int,delivery_token:?string}
 */
function m360_fi_action_create_draft($conn, int $jobcardId, int $userId, array $row): array
{
    if (!m360_p7_is_delivery_ready($row)) {
        return ['ok' => false, 'message' => 'فقط پرونده‌های DELIVERY_READY وارد فاکتور نهایی می‌شوند.', 'invoice_id' => 0, 'delivery_token' => null];
    }

    $existing = m360_fi_fetch_active($conn, $jobcardId);
    if ($existing !== null) {
        $st = strtoupper((string)($existing['invoice_status'] ?? ''));
        if (in_array($st, [M360_FI_DRAFT, M360_FI_CALCULATED], true)) {
            return [
                'ok' => true,
                'message' => 'پیش‌نویس فاکتور از قبل وجود دارد.',
                'invoice_id' => (int)$existing['final_invoice_id'],
                'delivery_token' => null,
            ];
        }
        if ($st === M360_FI_FINALIZED) {
            return [
                'ok' => true,
                'message' => 'فاکتور نهایی از قبل ثبت شده است.',
                'invoice_id' => (int)$existing['final_invoice_id'],
                'delivery_token' => null,
            ];
        }
    }

    $est = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
    if ($est === null) {
        return ['ok' => false, 'message' => 'برآورد فعال یافت نشد.', 'invoice_id' => 0, 'delivery_token' => null];
    }

    $invoiceNo = m360_fi_generate_invoice_no($jobcardId);
    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_FI_TABLE . ' (jobcard_id, estimate_id, customer_id, vehicle_id, invoice_no, invoice_status, estimate_total_amount, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $jobcardId,
            (int)$est['estimate_id'],
            (int)($row['customer_id'] ?? 0) ?: null,
            (int)($row['vehicle_id'] ?? 0) ?: null,
            $invoiceNo,
            M360_FI_DRAFT,
            (float)($est['total_amount'] ?? 0),
            $userId > 0 ? $userId : null,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ایجاد پیش‌نویس فاکتور ناموفق بود.', 'invoice_id' => 0, 'delivery_token' => null];
    }

    $invoiceId = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 final_invoice_id FROM dbo.' . M360_FI_TABLE . ' WHERE jobcard_id = ? ORDER BY final_invoice_id DESC',
        [$jobcardId]
    ) ?? 0);
    if ($invoiceId < 1) {
        return ['ok' => false, 'message' => 'شناسه فاکتور یافت نشد.', 'invoice_id' => 0, 'delivery_token' => null];
    }

    m360_fi_update_jobcard_invoice($conn, $jobcardId, $invoiceId, M360_FI_DRAFT, 0.0);
    m360_fi_write_history($conn, $jobcardId, 'JOBCARD_FINAL_INVOICE_CREATED', null, M360_FI_DRAFT, 'P7 draft final invoice created', $userId);
    m360_fi_write_event($conn, $jobcardId, 'FINAL_INVOICE_CREATED', $invoiceNo, $userId, $invoiceId);

    return ['ok' => true, 'message' => 'پیش‌نویس فاکتور نهایی ایجاد شد.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string,invoice_id:int,delivery_token:?string}
 */
function m360_fi_action_add_manual_item($conn, int $jobcardId, int $invoiceId, int $userId, array $payload): array
{
    if ($invoiceId < 1) {
        return ['ok' => false, 'message' => 'فاکتور یافت نشد.', 'invoice_id' => 0, 'delivery_token' => null];
    }
    $inv = m360_fi_fetch_invoice($conn, $invoiceId);
    if ($inv === null || (int)($inv['jobcard_id'] ?? 0) !== $jobcardId) {
        return ['ok' => false, 'message' => 'فاکتور با کارت کار مطابقت ندارد.', 'invoice_id' => 0, 'delivery_token' => null];
    }
    $st = strtoupper((string)($inv['invoice_status'] ?? ''));
    if (!in_array($st, [M360_FI_DRAFT, M360_FI_CALCULATED], true)) {
        return ['ok' => false, 'message' => 'فقط پیش‌نویس یا محاسبه‌شده قابل ویرایش است.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }

    $title = trim((string)($payload['title'] ?? ''));
    $qty = (float)($payload['qty'] ?? 1);
    $price = (float)($payload['price'] ?? 0);
    if ($title === '') {
        return ['ok' => false, 'message' => 'عنوان آیتم الزامی است.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }
    if ($qty <= 0 || $price < 0) {
        return ['ok' => false, 'message' => 'مقدار یا قیمت نامعتبر است.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }

    if (!m360_fi_insert_item($conn, $invoiceId, $jobcardId, 'MANUAL_APPROVED', null, 'OTHER', $title, null, $qty, $price)) {
        return ['ok' => false, 'message' => 'ثبت آیتم دستی ناموفق بود.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }
    m360_fi_recalc_invoice_totals($conn, $invoiceId);
    if ($st === M360_FI_CALCULATED) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_FI_TABLE . ' SET invoice_status = ?, updated_at = SYSUTCDATETIME() WHERE final_invoice_id = ?',
            [M360_FI_CALCULATED, $invoiceId]
        );
    }
    m360_fi_write_event($conn, $jobcardId, 'FINAL_INVOICE_MANUAL_ITEM_ADDED', $title, $userId, $invoiceId);

    return ['ok' => true, 'message' => 'آیتم دستی تأیید‌شده اضافه شد.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string,invoice_id:int,delivery_token:?string}
 */
function m360_fi_action_apply_discount($conn, int $jobcardId, int $invoiceId, int $userId, array $payload): array
{
    if ($invoiceId < 1) {
        return ['ok' => false, 'message' => 'فاکتور یافت نشد.', 'invoice_id' => 0, 'delivery_token' => null];
    }
    $inv = m360_fi_fetch_invoice($conn, $invoiceId);
    if ($inv === null || (int)($inv['jobcard_id'] ?? 0) !== $jobcardId) {
        return ['ok' => false, 'message' => 'فاکتور با کارت کار مطابقت ندارد.', 'invoice_id' => 0, 'delivery_token' => null];
    }
    $st = strtoupper((string)($inv['invoice_status'] ?? ''));
    if (!in_array($st, [M360_FI_DRAFT, M360_FI_CALCULATED], true)) {
        return ['ok' => false, 'message' => 'تخفیف فقط روی پیش‌نویس مجاز است.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }

    $amount = max(0.0, (float)($payload['amount'] ?? 0));
    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_FI_TABLE . ' SET discount_amount = ?, updated_at = SYSUTCDATETIME() WHERE final_invoice_id = ?',
        [$amount, $invoiceId]
    );
    m360_fi_recalc_invoice_totals($conn, $invoiceId);
    m360_fi_write_event($conn, $jobcardId, 'FINAL_INVOICE_DISCOUNT_APPLIED', (string)$amount, $userId, $invoiceId);

    return ['ok' => true, 'message' => 'تخفیف اعمال شد.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
}

/**
 * @param array<string, mixed> $row
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string,invoice_id:int,delivery_token:?string}
 */
function m360_fi_action_finalize($conn, int $jobcardId, int $invoiceId, int $userId, array $row, array $payload): array
{
    if ($invoiceId < 1) {
        return ['ok' => false, 'message' => 'فاکتور یافت نشد.', 'invoice_id' => 0, 'delivery_token' => null];
    }
    $inv = m360_fi_fetch_invoice($conn, $invoiceId);
    if ($inv === null || (int)($inv['jobcard_id'] ?? 0) !== $jobcardId) {
        return ['ok' => false, 'message' => 'فاکتور با کارت کار مطابقت ندارد.', 'invoice_id' => 0, 'delivery_token' => null];
    }

    $st = strtoupper((string)($inv['invoice_status'] ?? ''));
    if ($st === M360_FI_FINALIZED) {
        return ['ok' => true, 'message' => 'فاکتور قبلاً نهایی شده است.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }
    if (!in_array($st, [M360_FI_DRAFT, M360_FI_CALCULATED], true)) {
        return ['ok' => false, 'message' => 'وضعیت فاکتور برای نهایی‌سازی مجاز نیست.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }

    $gate = m360_p7_assert_gates($conn, $jobcardId, $row);
    if (!$gate['ok']) {
        m360_fi_write_history($conn, $jobcardId, 'JOBCARD_FINAL_INVOICE_FINALIZE_BLOCKED_GATE', $st, $st, $gate['message'], $userId);
        m360_fi_write_event($conn, $jobcardId, 'FINAL_INVOICE_FINALIZE_BLOCKED_GATE', $gate['message'], $userId, $invoiceId);
        return ['ok' => false, 'message' => $gate['message'], 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }

    if ($st === M360_FI_DRAFT) {
        $calc = m360_fi_calculate($conn, $invoiceId, $jobcardId, $userId);
        if (!$calc['ok']) {
            return ['ok' => false, 'message' => $calc['message'], 'invoice_id' => $invoiceId, 'delivery_token' => null];
        }
        $inv = m360_fi_fetch_invoice($conn, $invoiceId);
        if ($inv === null) {
            return ['ok' => false, 'message' => 'فاکتور یافت نشد.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
        }
    }

    $total = (float)($inv['total_amount'] ?? 0);
    if ($total <= 0) {
        return ['ok' => false, 'message' => 'مبلغ فاکتور باید بیشتر از صفر باشد.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }

    $estimateTotal = (float)($inv['estimate_total_amount'] ?? 0);
    $overrideReason = trim((string)($payload['variance_override_reason'] ?? ''));
    $varEval = m360_fi_eval_variance($estimateTotal, $total, $overrideReason);
    if ($varEval['variance_status'] === M360_FI_VAR_EXCEEDS) {
        m360_fi_write_history($conn, $jobcardId, 'JOBCARD_FINAL_INVOICE_FINALIZE_BLOCKED_GATE', M360_FI_CALCULATED, M360_FI_CALCULATED, 'Variance exceeds allowed threshold', $userId);
        m360_fi_write_event($conn, $jobcardId, 'FINAL_INVOICE_FINALIZE_BLOCKED_GATE', 'Variance exceeds threshold', $userId, $invoiceId);
        return [
            'ok' => false,
            'message' => 'اختلاف مبلغ از سقف مجاز (۱۰٪) بیشتر است — دلیل override الزامی است.',
            'invoice_id' => $invoiceId,
            'delivery_token' => null,
        ];
    }

    $overrideSql = $overrideReason !== '' ? $overrideReason : null;
    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_FI_TABLE . ' SET invoice_status = ?, finalized_at = SYSUTCDATETIME(), variance_amount = ?, variance_status = ?, variance_override_reason = ?, updated_at = SYSUTCDATETIME() WHERE final_invoice_id = ? AND jobcard_id = ?',
        [M360_FI_FINALIZED, $varEval['variance'], $varEval['variance_status'], $overrideSql, $invoiceId, $jobcardId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'نهایی‌سازی فاکتور ناموفق بود.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }

    m360_fi_update_jobcard_invoice($conn, $jobcardId, $invoiceId, M360_FI_FINALIZED, $total);
    m360_fi_write_history($conn, $jobcardId, 'JOBCARD_FINAL_INVOICE_FINALIZED', M360_FI_CALCULATED, M360_FI_FINALIZED, 'P7 final invoice finalized', $userId);
    m360_fi_write_event($conn, $jobcardId, 'FINAL_INVOICE_FINALIZED', null, $userId, $invoiceId);

    if (function_exists('m360_settlement_recalculate')) {
        m360_settlement_recalculate($conn, $jobcardId, $invoiceId, $total, $userId);
    }

    return ['ok' => true, 'message' => 'فاکتور نهایی ثبت شد.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
}

/**
 * @return array{ok:bool,message:string,invoice_id:int,delivery_token:?string}
 */
function m360_fi_action_recalculate_settlement($conn, int $jobcardId, int $invoiceId, int $userId): array
{
    if ($invoiceId < 1) {
        return ['ok' => false, 'message' => 'فاکتور یافت نشد.', 'invoice_id' => 0, 'delivery_token' => null];
    }
    $inv = m360_fi_fetch_invoice($conn, $invoiceId);
    if ($inv === null || (int)($inv['jobcard_id'] ?? 0) !== $jobcardId) {
        return ['ok' => false, 'message' => 'فاکتور با کارت کار مطابقت ندارد.', 'invoice_id' => 0, 'delivery_token' => null];
    }
    if (strtoupper((string)($inv['invoice_status'] ?? '')) !== M360_FI_FINALIZED) {
        return ['ok' => false, 'message' => 'تسویه فقط بعد از نهایی‌سازی فاکتور مجاز است.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }

    $total = (float)($inv['total_amount'] ?? 0);
    $res = m360_settlement_recalculate($conn, $jobcardId, $invoiceId, $total, $userId);
    m360_fi_write_history($conn, $jobcardId, 'JOBCARD_SETTLEMENT_RECALCULATED', null, null, $res['message'], $userId);

    return [
        'ok' => $res['ok'],
        'message' => $res['message'],
        'invoice_id' => $invoiceId,
        'delivery_token' => null,
    ];
}

/**
 * @return array{ok:bool,message:string,invoice_id:int,delivery_token:?string}
 */
function m360_fi_action_notify_customer($conn, int $jobcardId, int $invoiceId, int $userId): array
{
    if ($invoiceId < 1) {
        return ['ok' => false, 'message' => 'فاکتور یافت نشد.', 'invoice_id' => 0, 'delivery_token' => null];
    }
    $inv = m360_fi_fetch_invoice($conn, $invoiceId);
    if ($inv === null || (int)($inv['jobcard_id'] ?? 0) !== $jobcardId) {
        return ['ok' => false, 'message' => 'فاکتور با کارت کار مطابقت ندارد.', 'invoice_id' => 0, 'delivery_token' => null];
    }
    if (strtoupper((string)($inv['invoice_status'] ?? '')) !== M360_FI_FINALIZED) {
        return ['ok' => false, 'message' => 'ابتدا فاکتور باید نهایی شود.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }

    $token = m360_fi_generate_token();
    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_FI_TABLE . ' SET delivery_token_hash = ?, delivery_token_expires_at = ?, customer_notified_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE final_invoice_id = ?',
        [$token['hash'], $token['expires_at'], $invoiceId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت لینک تحویل ناموفق بود.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }

    m360_fi_write_history($conn, $jobcardId, 'JOBCARD_DELIVERY_REVIEWED_BY_CUSTOMER', null, null, 'Customer delivery link generated', $userId);
    m360_fi_write_event($conn, $jobcardId, 'CUSTOMER_DELIVERY_REVIEWED', null, $userId, $invoiceId);

    return [
        'ok' => true,
        'message' => 'لینک تحویل برای مشتری آماده شد.',
        'invoice_id' => $invoiceId,
        'delivery_token' => $token['raw'],
    ];
}

/**
 * @return array{ok:bool,message:string,invoice_id:int,delivery_token:?string}
 */
function m360_fi_action_cancel_draft($conn, int $jobcardId, int $invoiceId, int $userId): array
{
    if ($invoiceId < 1) {
        return ['ok' => false, 'message' => 'فاکتور یافت نشد.', 'invoice_id' => 0, 'delivery_token' => null];
    }
    $inv = m360_fi_fetch_invoice($conn, $invoiceId);
    if ($inv === null || (int)($inv['jobcard_id'] ?? 0) !== $jobcardId) {
        return ['ok' => false, 'message' => 'فاکتور با کارت کار مطابقت ندارد.', 'invoice_id' => 0, 'delivery_token' => null];
    }

    $st = strtoupper((string)($inv['invoice_status'] ?? ''));
    if ($st === M360_FI_CANCELLED) {
        return ['ok' => true, 'message' => 'فاکتور قبلاً لغو شده است.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }
    if (!in_array($st, [M360_FI_DRAFT, M360_FI_CALCULATED], true)) {
        return ['ok' => false, 'message' => 'فقط پیش‌نویس قابل لغو است.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }

    $prev = $st;
    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_FI_TABLE . ' SET invoice_status = ?, updated_at = SYSUTCDATETIME() WHERE final_invoice_id = ? AND jobcard_id = ?',
        [M360_FI_CANCELLED, $invoiceId, $jobcardId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'لغو فاکتور ناموفق بود.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
    }

    if ((int)($inv['jobcard_id'] ?? 0) === $jobcardId) {
        $jcInvId = (int)(customer_core_scalar(
            $conn,
            'SELECT TOP 1 current_final_invoice_id FROM dbo.erp_jobcards WHERE jobcard_id = ?',
            [$jobcardId]
        ) ?? 0);
        if ($jcInvId === $invoiceId) {
            customer_core_execute(
                $conn,
                'UPDATE dbo.erp_jobcards SET final_invoice_status = NULL, current_final_invoice_id = NULL, final_invoice_amount = NULL WHERE jobcard_id = ?',
                [$jobcardId]
            );
        }
    }

    m360_fi_write_history($conn, $jobcardId, 'JOBCARD_FINAL_INVOICE_CANCELLED', $prev, M360_FI_CANCELLED, 'Draft final invoice cancelled', $userId);
    m360_fi_write_event($conn, $jobcardId, 'FINAL_INVOICE_CANCELLED', null, $userId, $invoiceId);

    return ['ok' => true, 'message' => 'پیش‌نویس فاکتور لغو شد.', 'invoice_id' => $invoiceId, 'delivery_token' => null];
}
