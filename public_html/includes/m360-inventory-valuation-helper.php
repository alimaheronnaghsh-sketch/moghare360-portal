<?php
declare(strict_types=1);

/**
 * Authoritative inventory internal valuation for workshop non-billable consumables.
 * Methods: MOVING_AVERAGE | LAST_APPROVED_PURCHASE | STANDARD_INTERNAL_COST
 * Only APPROVED + active (effective_to NULL or future) valuations are consumed.
 */

require_once __DIR__ . '/m360-workshop-access-enforcement.php';

const M360_INV_VAL_METHODS = ['MOVING_AVERAGE', 'LAST_APPROVED_PURCHASE', 'STANDARD_INTERNAL_COST'];
const M360_INV_VAL_STATUSES = ['DRAFT', 'SUBMITTED', 'APPROVED', 'SUPERSEDED', 'VOIDED'];

function m360_inv_val_fetch($conn, int $valuationId): ?array
{
    if ($valuationId < 1 || !customer_core_table_exists($conn, 'erp_inventory_item_valuations')) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_inventory_item_valuations WHERE valuation_id=?',
        [$valuationId]
    );
    return $rows[0] ?? null;
}

/**
 * Resolve active approved valuation for item+company.
 * Priority: MOVING_AVERAGE → LAST_APPROVED_PURCHASE → STANDARD_INTERNAL_COST (most recent effective).
 *
 * @return array{ok:bool,valuation:?array,unit_cost:?float,method:?string,message:string}
 */
function m360_inv_val_resolve_approved($conn, int $inventoryItemId, int $companyId): array
{
    if ($inventoryItemId < 1 || $companyId < 1) {
        return ['ok' => false, 'valuation' => null, 'unit_cost' => null, 'method' => null, 'message' => 'شناسه کالا/شرکت نامعتبر است.'];
    }
    if (!customer_core_table_exists($conn, 'erp_inventory_item_valuations')) {
        return ['ok' => false, 'valuation' => null, 'unit_cost' => null, 'method' => null, 'message' => 'جدول ارزیابی موجود نیست.'];
    }

    $priority = ['MOVING_AVERAGE', 'LAST_APPROVED_PURCHASE', 'STANDARD_INTERNAL_COST'];
    foreach ($priority as $method) {
        $rows = customer_core_fetch_rows(
            $conn,
            "SELECT TOP 1 * FROM dbo.erp_inventory_item_valuations
             WHERE inventory_item_id=? AND company_id=? AND status=N'APPROVED'
               AND valuation_method=?
               AND (effective_to IS NULL OR effective_to > SYSUTCDATETIME())
             ORDER BY effective_from DESC, valuation_id DESC",
            [$inventoryItemId, $companyId, $method]
        );
        if ($rows === []) {
            continue;
        }
        $v = $rows[0];
        $unit = (float)($v['unit_cost'] ?? 0);
        if ($unit <= 0) {
            continue;
        }
        return [
            'ok' => true,
            'valuation' => $v,
            'unit_cost' => $unit,
            'method' => (string)$v['valuation_method'],
            'message' => 'ارزیابی تأییدشده یافت شد.',
        ];
    }

    // Fallback: any approved active with positive unit_cost
    $any = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 * FROM dbo.erp_inventory_item_valuations
         WHERE inventory_item_id=? AND company_id=? AND status=N'APPROVED'
           AND unit_cost > 0
           AND (effective_to IS NULL OR effective_to > SYSUTCDATETIME())
         ORDER BY effective_from DESC, valuation_id DESC",
        [$inventoryItemId, $companyId]
    );
    if ($any !== []) {
        $v = $any[0];
        return [
            'ok' => true,
            'valuation' => $v,
            'unit_cost' => (float)$v['unit_cost'],
            'method' => (string)$v['valuation_method'],
            'message' => 'ارزیابی تأییدشده یافت شد.',
        ];
    }

    return ['ok' => false, 'valuation' => null, 'unit_cost' => null, 'method' => null, 'message' => 'بهای داخلی تأییدشده موجود نیست.'];
}

/**
 * @param array<string,mixed> $input
 * @return array{ok:bool,message:string,valuation_id:int}
 */
function m360_inv_val_create_draft($conn, int $companyId, int $inventoryItemId, array $input, int $actorId): array
{
    m360_ws_reject_injected_prices(array_diff_key($input, array_flip(['unit_cost', 'standard_internal_unit_cost', 'moving_average_unit_cost', 'last_approved_unit_cost'])));
    $method = strtoupper(trim((string)($input['valuation_method'] ?? 'STANDARD_INTERNAL_COST')));
    if (!in_array($method, M360_INV_VAL_METHODS, true)) {
        return ['ok' => false, 'message' => 'روش ارزیابی نامعتبر است.', 'valuation_id' => 0];
    }
    $unit = (float)($input['unit_cost'] ?? 0);
    if ($unit <= 0) {
        return ['ok' => false, 'message' => 'بهای واحد باید مثبت باشد.', 'valuation_id' => 0];
    }
    $item = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 inventory_item_id FROM dbo.erp_inventory_items WHERE inventory_item_id=? AND is_active=1',
        [$inventoryItemId]
    );
    if ($item === []) {
        return ['ok' => false, 'message' => 'قلم انبار یافت نشد.', 'valuation_id' => 0];
    }
    $currency = trim((string)($input['currency'] ?? 'IRR')) ?: 'IRR';
    $ok = customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_inventory_item_valuations
            (inventory_item_id, company_id, valuation_method, standard_internal_unit_cost,
             moving_average_unit_cost, last_approved_unit_cost, unit_cost, currency, status, entered_by_user_id,
             source_document_type, source_document_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, N'DRAFT', ?, ?, ?)",
        [
            $inventoryItemId,
            $companyId,
            $method,
            $method === 'STANDARD_INTERNAL_COST' ? $unit : null,
            $method === 'MOVING_AVERAGE' ? $unit : null,
            $method === 'LAST_APPROVED_PURCHASE' ? $unit : null,
            $unit,
            $currency,
            $actorId,
            trim((string)($input['source_document_type'] ?? '')) ?: null,
            isset($input['source_document_id']) ? (int)$input['source_document_id'] : null,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت پیش‌نویس ارزیابی ناموفق بود.', 'valuation_id' => 0];
    }
    $id = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 valuation_id FROM dbo.erp_inventory_item_valuations
         WHERE inventory_item_id=? AND company_id=? AND entered_by_user_id=? ORDER BY valuation_id DESC',
        [$inventoryItemId, $companyId, $actorId]
    ) ?? 0);
    return ['ok' => true, 'message' => 'پیش‌نویس ارزیابی ثبت شد.', 'valuation_id' => $id];
}

function m360_inv_val_submit($conn, int $valuationId, int $actorId): array
{
    $v = m360_inv_val_fetch($conn, $valuationId);
    if ($v === null) {
        return ['ok' => false, 'message' => 'ارزیابی یافت نشد.'];
    }
    if (strtoupper((string)$v['status']) !== 'DRAFT') {
        return ['ok' => false, 'message' => 'فقط پیش‌نویس قابل ارسال است.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_inventory_item_valuations
         SET status=N'SUBMITTED', submitted_at=SYSUTCDATETIME(), updated_at=SYSUTCDATETIME()
         WHERE valuation_id=?",
        [$valuationId]
    );
    return ['ok' => true, 'message' => 'ارزیابی برای تأیید ارسال شد.'];
}

function m360_inv_val_approve($conn, int $valuationId, int $actorId, bool $ownerOverride = false, string $overrideReason = ''): array
{
    $v = m360_inv_val_fetch($conn, $valuationId);
    if ($v === null) {
        return ['ok' => false, 'message' => 'ارزیابی یافت نشد.'];
    }
    if (strtoupper((string)$v['status']) !== 'SUBMITTED') {
        return ['ok' => false, 'message' => 'فقط ارزیابی ارسال‌شده قابل تأیید است.'];
    }
    if ((int)$v['entered_by_user_id'] === $actorId && !$ownerOverride) {
        return ['ok' => false, 'message' => 'ایجادکننده نمی‌تواند ارزیابی خود را تأیید کند.'];
    }
    if ($ownerOverride && trim($overrideReason) === '') {
        return ['ok' => false, 'message' => 'دلیل ممیزی برای عبور مالک الزامی است.'];
    }

    // Supersede previous approved active rows for same item/company/method
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_inventory_item_valuations
         SET status=N'SUPERSEDED', effective_to=SYSUTCDATETIME(), updated_at=SYSUTCDATETIME()
         WHERE inventory_item_id=? AND company_id=? AND valuation_method=? AND status=N'APPROVED'
           AND valuation_id<>?",
        [(int)$v['inventory_item_id'], (int)$v['company_id'], (string)$v['valuation_method'], $valuationId]
    );

    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_inventory_item_valuations
         SET status=N'APPROVED', approved_by_user_id=?, approved_at=SYSUTCDATETIME(), updated_at=SYSUTCDATETIME()
         WHERE valuation_id=? AND status=N'SUBMITTED'",
        [$actorId, $valuationId]
    );

    // Close related pending cost tasks for this item
    if (customer_core_table_exists($conn, 'erp_inventory_cost_pending_tasks')) {
        customer_core_execute(
            $conn,
            "UPDATE dbo.erp_inventory_cost_pending_tasks
             SET task_status=N'CLOSED', closed_at=SYSUTCDATETIME(), closed_by_user_id=?
             WHERE inventory_item_id=? AND company_id=? AND task_status=N'OPEN'",
            [$actorId, (int)$v['inventory_item_id'], (int)$v['company_id']]
        );
    }

    return ['ok' => true, 'message' => 'ارزیابی تأیید شد.' . ($ownerOverride ? ' (عبور مالک: ' . trim($overrideReason) . ')' : '')];
}

function m360_inv_val_open_pending_task($conn, int $companyId, ?int $inventoryItemId, int $jobcardId, int $requestId, int $requestItemId, string $note, ?float $qty = null): void
{
    if (!customer_core_table_exists($conn, 'erp_inventory_cost_pending_tasks')) {
        return;
    }
    $exists = (int)(customer_core_scalar(
        $conn,
        "SELECT TOP 1 task_id FROM dbo.erp_inventory_cost_pending_tasks
         WHERE request_item_id=? AND task_status=N'OPEN'",
        [$requestItemId]
    ) ?? 0);
    if ($exists > 0) {
        return;
    }
    $hasPendingQty = customer_core_column_exists($conn, 'erp_inventory_cost_pending_tasks', 'pending_qty');
    if ($hasPendingQty) {
        customer_core_execute(
            $conn,
            "INSERT INTO dbo.erp_inventory_cost_pending_tasks
                (company_id, inventory_item_id, jobcard_id, request_id, request_item_id, task_status, task_note, pending_qty, original_qty)
             VALUES (?, ?, ?, ?, ?, N'OPEN', ?, ?, ?)",
            [$companyId, $inventoryItemId, $jobcardId, $requestId, $requestItemId, $note, $qty, $qty]
        );
    } else {
        customer_core_execute(
            $conn,
            "INSERT INTO dbo.erp_inventory_cost_pending_tasks
                (company_id, inventory_item_id, jobcard_id, request_id, request_item_id, task_status, task_note)
             VALUES (?, ?, ?, ?, ?, N'OPEN', ?)",
            [$companyId, $inventoryItemId, $jobcardId, $requestId, $requestItemId, $note]
        );
    }
}

/**
 * Complete COST_PENDING item using APPROVED valuation (effective-date policy).
 *
 * @return array{ok:bool,message:string}
 */
function m360_inv_val_complete_pending_cost(
    $conn,
    int $taskId,
    int $valuationId,
    int $actorId,
    int $actorCompanyId,
    bool $isOwner,
    string $laterValuationReason = ''
): array {
    if (!customer_core_table_exists($conn, 'erp_inventory_cost_pending_tasks')) {
        return ['ok' => false, 'message' => 'جدول وظایف بهای معلق موجود نیست.'];
    }
    $tasks = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.erp_inventory_cost_pending_tasks WHERE task_id=?', [$taskId]);
    if ($tasks === []) {
        return ['ok' => false, 'message' => 'وظیفه یافت نشد.'];
    }
    $task = $tasks[0];
    if (strtoupper((string)$task['task_status']) !== 'OPEN') {
        return ['ok' => false, 'message' => 'وظیفه قبلاً بسته شده است.'];
    }
    if (!$isOwner && (int)$task['company_id'] !== $actorCompanyId) {
        return ['ok' => false, 'message' => 'خارج از محدوده شرکت.'];
    }
    $itemId = (int)$task['request_item_id'];
    $items = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_workshop_internal_consumable_request_items WHERE request_item_id=?',
        [$itemId]
    );
    if ($items === []) {
        return ['ok' => false, 'message' => 'قلم مصرف یافت نشد.'];
    }
    $it = $items[0];
    if (strtoupper((string)($it['cost_status'] ?? '')) !== 'COST_PENDING') {
        return ['ok' => false, 'message' => 'قلم در وضعیت بهای معلق نیست یا قبلاً تکمیل شده است.'];
    }
    if (trim((string)($it['internal_total_cost'] ?? '')) !== '') {
        return ['ok' => false, 'message' => 'تکمیل تکراری بهای معلق مسدود شد.'];
    }

    $val = m360_inv_val_fetch($conn, $valuationId);
    if ($val === null || strtoupper((string)$val['status']) !== 'APPROVED') {
        return ['ok' => false, 'message' => 'ارزیابی تأییدشده معتبر نیست.'];
    }
    if ((int)$val['company_id'] !== (int)$task['company_id']) {
        return ['ok' => false, 'message' => 'عدم تطابق شرکت ارزیابی.'];
    }
    if ((int)($it['inventory_item_id'] ?? 0) > 0 && (int)$val['inventory_item_id'] !== (int)$it['inventory_item_id']) {
        return ['ok' => false, 'message' => 'عدم تطابق قلم ارزیابی با مصرف.'];
    }
    $unit = (float)($val['unit_cost'] ?? 0);
    if ($unit <= 0) {
        return ['ok' => false, 'message' => 'بهای واحد ارزیابی نامعتبر است.'];
    }

    // Effective-date: prefer valuation effective on/before issue (cost_snapshot_at or request approved_at)
    $req = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 approved_at, created_at FROM dbo.erp_workshop_internal_consumable_requests WHERE request_id=?',
        [(int)$task['request_id']]
    );
    $issueAt = (string)($req[0]['approved_at'] ?? $req[0]['created_at'] ?? '');
    $effFrom = (string)($val['effective_from'] ?? '');
    $laterUsed = false;
    if ($issueAt !== '' && $effFrom !== '' && strcmp($effFrom, $issueAt) > 0) {
        $laterUsed = true;
        if (trim($laterValuationReason) === '') {
            return ['ok' => false, 'message' => 'برای ارزیابی دیرتر از تاریخ صدور، ذکر دلیل ممیزی الزامی است.'];
        }
    }

    $netQty = (float)$it['quantity'] - (float)($it['quantity_reversed'] ?? 0);
    if ($netQty <= 0) {
        return ['ok' => false, 'message' => 'مقدار خالص برای تعیین بها صفر است.'];
    }
    $total = round($unit * $netQty, 4);
    $reason = $laterUsed ? trim($laterValuationReason) : null;

    $upd = customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_internal_consumable_request_items
         SET cost_status=N'COST_CONFIRMED',
             valuation_method=?,
             valuation_id=?,
             internal_unit_cost=?,
             internal_total_cost=?,
             cost_currency=?,
             cost_source_document_type=?,
             cost_source_document_id=?,
             cost_snapshot_at=SYSUTCDATETIME(),
             cost_completed_at=SYSUTCDATETIME(),
             cost_completed_by_user_id=?,
             cost_completion_reason=?,
             customer_billable=0,
             invoice_excluded=1
         WHERE request_item_id=? AND cost_status=N'COST_PENDING'",
        [
            (string)$val['valuation_method'],
            $valuationId,
            $unit,
            $total,
            (string)($val['currency'] ?? 'IRR'),
            trim((string)($val['source_document_type'] ?? '')) ?: null,
            isset($val['source_document_id']) && $val['source_document_id'] !== '' ? (int)$val['source_document_id'] : null,
            $actorId,
            $reason,
            $itemId,
        ]
    );
    if ($upd === false) {
        return ['ok' => false, 'message' => 'ثبت بهای معلق ناموفق بود.'];
    }

    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_inventory_cost_pending_tasks
         SET task_status=N'CLOSED', pending_qty=0, closed_at=SYSUTCDATETIME(), closed_by_user_id=?
         WHERE task_id=? AND task_status=N'OPEN'",
        [$actorId, $taskId]
    );

    return ['ok' => true, 'message' => 'بهای معلق تکمیل شد و به هزینه تأییدشده افزوده شد.'];
}

/**
 * JobCard true-cost breakdown for internal consumables (net of reversals).
 *
 * @return array{
 *   gross_issued_cost:float,
 *   reversed_cost:float,
 *   confirmed_cost:float,
 *   pending_item_count:int,
 *   pending_qty:float,
 *   methods:list<string>,
 *   snapshots:list<array<string,mixed>>,
 *   pending_item_count:int,
 *   pending_qty:float
 * }
 */
function m360_inv_val_jobcard_true_cost_breakdown($conn, int $jobcardId): array
{
    $gross = 0.0;
    $reversed = 0.0;
    $pendingCount = 0;
    $pendingQty = 0.0;
    $methods = [];
    $snapshots = [];

    if (!customer_core_table_exists($conn, 'erp_workshop_internal_consumable_request_items')) {
        return [
            'gross_issued_cost' => 0.0,
            'reversed_cost' => 0.0,
            'confirmed_cost' => 0.0,
            'pending_item_count' => 0,
            'pending_qty' => 0.0,
            'methods' => [],
            'snapshots' => [],
        ];
    }

    $rows = customer_core_fetch_rows(
        $conn,
        "SELECT i.request_item_id, i.quantity, i.quantity_reversed, i.unit_of_measure, i.manual_description, i.inventory_item_id,
                i.internal_unit_cost, i.internal_total_cost, i.reversed_total_cost, i.cost_status, i.valuation_method,
                i.cost_snapshot_at, i.customer_billable, i.invoice_excluded, r.status AS request_status
         FROM dbo.erp_workshop_internal_consumable_request_items i
         INNER JOIN dbo.erp_workshop_internal_consumable_requests r ON r.request_id=i.request_id
         WHERE r.jobcard_id=? AND r.status IN (N'ISSUED', N'APPROVED', N'REVERSAL_REQUESTED', N'REVERSED', N'REVERSAL_REJECTED')
         ORDER BY i.request_item_id",
        [$jobcardId]
    );

    foreach ($rows as $r) {
        $st = strtoupper(trim((string)($r['cost_status'] ?? '')));
        $totalRaw = trim((string)($r['internal_total_cost'] ?? ''));
        $revCost = (float)($r['reversed_total_cost'] ?? 0);
        $qty = (float)$r['quantity'];
        $qtyRev = (float)($r['quantity_reversed'] ?? 0);
        $netQty = max(0.0, $qty - $qtyRev);
        $snapshots[] = $r;

        if ($st === 'COST_PENDING' || ($st === '' && $totalRaw === '')) {
            if ($netQty > 0.00001) {
                $pendingCount++;
                $pendingQty += $netQty;
            }
            continue;
        }
        if ($st === 'COST_CONFIRMED' || $totalRaw !== '') {
            $gross += (float)$totalRaw;
            $reversed += $revCost;
            $m = trim((string)($r['valuation_method'] ?? ''));
            if ($m !== '' && !in_array($m, $methods, true)) {
                $methods[] = $m;
            }
        }
    }

    $net = max(0.0, $gross - $reversed);
    return [
        'gross_issued_cost' => $gross,
        'reversed_cost' => $reversed,
        'confirmed_cost' => $net,
        'pending_item_count' => $pendingCount,
        'pending_qty' => $pendingQty,
        'methods' => $methods,
        'snapshots' => $snapshots,
    ];
}

/**
 * Focused JobCard true-cost summary sections.
 *
 * @return array<string,mixed>
 */
function m360_ws_jobcard_true_cost_summary($conn, int $jobcardId): array
{
    $ic = m360_inv_val_jobcard_true_cost_breakdown($conn, $jobcardId);
    $billableParts = 0.0;
    if (customer_core_table_exists($conn, 'erp_jobcard_part_usage')) {
        // Best-effort: sum when a cost-like column exists; otherwise 0 (customer-billable list is separate).
        $hasAmt = customer_core_column_exists($conn, 'erp_jobcard_part_usage', 'line_total')
            || customer_core_column_exists($conn, 'erp_jobcard_part_usage', 'unit_price');
        if ($hasAmt && customer_core_column_exists($conn, 'erp_jobcard_part_usage', 'line_total')) {
            $billableParts = (float)(customer_core_scalar(
                $conn,
                'SELECT ISNULL(SUM(line_total),0) FROM dbo.erp_jobcard_part_usage WHERE jobcard_id=?',
                [$jobcardId]
            ) ?? 0);
        }
    }

    return [
        'customer_billable_parts' => $billableParts,
        'internal_consumable_gross' => $ic['gross_issued_cost'],
        'internal_consumable_reversed' => $ic['reversed_cost'],
        'internal_consumable_net' => $ic['confirmed_cost'],
        'internal_consumable_pending_count' => $ic['pending_item_count'],
        'internal_consumable_pending_qty' => $ic['pending_qty'],
        'labor_internal' => null,
        'external_services' => null,
        'confirmed_jobcard_true_cost' => $ic['confirmed_cost'],
        'has_pending_cost' => $ic['pending_item_count'] > 0,
        'methods' => $ic['methods'],
        'snapshots' => $ic['snapshots'],
    ];
}
