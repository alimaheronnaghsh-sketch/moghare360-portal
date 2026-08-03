<?php
declare(strict_types=1);

/**
 * Internal non-billable consumables — separate from customer-billable part usage.
 */

require_once __DIR__ . '/m360-workshop-access-enforcement.php';

const M360_WS_IC_COST_FIELDS = [
    'internal_unit_cost', 'internal_total_cost', 'unit_cost', 'purchase_price', 'supplier',
    'supplier_payment', 'margin', 'valuation_method', 'cost', 'amount', 'cost_currency',
    'valuation_id', 'cost_source_document_type', 'cost_source_document_id',
];

function m360_ws_ic_fetch_request($conn, int $requestId): ?array
{
    if ($requestId < 1) {
        return null;
    }
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.erp_workshop_internal_consumable_requests WHERE request_id=?', [$requestId]);
    return $rows[0] ?? null;
}

/** @return list<array<string,mixed>> */
function m360_ws_ic_fetch_items($conn, int $requestId): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.erp_workshop_internal_consumable_request_items WHERE request_id=? ORDER BY request_item_id',
        [$requestId]
    );
}

function m360_ws_ic_history_add($conn, int $requestId, int $jobcardId, string $event, ?string $old, ?string $new, string $note, int $actorId): void
{
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_workshop_internal_consumable_history
            (request_id, jobcard_id, event_name, old_status, new_status, event_note, actor_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$requestId, $jobcardId, $event, $old, $new, $note !== '' ? $note : null, $actorId]
    );
}

/**
 * Strip cost fields from array for non-cost_view users.
 *
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function m360_ws_ic_filter_cost_row(array $row, bool $canViewCost): array
{
    if ($canViewCost) {
        return $row;
    }
    foreach (M360_WS_IC_COST_FIELDS as $k) {
        unset($row[$k]);
    }
    return $row;
}

/**
 * @param list<array<string,mixed>> $rows
 * @return list<array<string,mixed>>
 */
function m360_ws_ic_filter_cost_rows(array $rows, bool $canViewCost): array
{
    return array_map(static fn(array $r) => m360_ws_ic_filter_cost_row($r, $canViewCost), $rows);
}

/**
 * @param array<string,mixed> $input
 * @param list<array<string,mixed>> $items
 * @return array{ok:bool,message:string,request_id:int}
 */
function m360_ws_ic_create($conn, int $companyId, int $jobcardId, ?int $workItemId, array $input, array $items, int $actorId): array
{
    m360_ws_reject_injected_prices($input);
    foreach ($items as $it) {
        if (is_array($it)) {
            m360_ws_reject_injected_prices($it);
            if (!empty($it['customer_billable']) || (isset($it['invoice_excluded']) && (int)$it['invoice_excluded'] === 0)) {
                return ['ok' => false, 'message' => 'مصرف داخلی نمی‌تواند قابل‌صورتحساب مشتری باشد.', 'request_id' => 0];
            }
        }
    }
    $reason = trim((string)($input['usage_reason'] ?? ''));
    $unit = trim((string)($input['consuming_unit'] ?? ''));
    if ($reason === '' || $unit === '') {
        return ['ok' => false, 'message' => 'واحد مصرف‌کننده و دلیل مصرف الزامی است.', 'request_id' => 0];
    }
    if ($items === []) {
        return ['ok' => false, 'message' => 'حداقل یک قلم مصرفی لازم است.', 'request_id' => 0];
    }
    m360_ws_assert_jobcard_object_scope($conn, $jobcardId);

    if (!customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_workshop_internal_consumable_requests
            (company_id, jobcard_id, work_item_id, requesting_user_id, consuming_unit, usage_reason, status)
         VALUES (?, ?, ?, ?, ?, ?, N'DRAFT')",
        [$companyId, $jobcardId, $workItemId, $actorId, $unit, $reason]
    )) {
        return ['ok' => false, 'message' => 'ثبت درخواست مصرف داخلی ناموفق بود.', 'request_id' => 0];
    }
    $requestId = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 request_id FROM dbo.erp_workshop_internal_consumable_requests
         WHERE jobcard_id=? AND requesting_user_id=? ORDER BY request_id DESC',
        [$jobcardId, $actorId]
    ) ?? 0);
    if ($requestId < 1) {
        return ['ok' => false, 'message' => 'شناسه درخواست یافت نشد.', 'request_id' => 0];
    }

    foreach ($items as $it) {
        if (!is_array($it)) {
            continue;
        }
        $qty = (float)($it['quantity'] ?? 0);
        if ($qty <= 0) {
            return ['ok' => false, 'message' => 'مقدار باید مثبت باشد.', 'request_id' => $requestId];
        }
        $invId = (int)($it['inventory_item_id'] ?? 0);
        $manual = trim((string)($it['manual_description'] ?? ''));
        $stockManaged = $invId > 0 ? 1 : 0;
        if ($invId < 1 && $manual === '') {
            return ['ok' => false, 'message' => 'قلم انبار یا شرح دستی الزامی است.', 'request_id' => $requestId];
        }
        $uom = trim((string)($it['unit_of_measure'] ?? 'عدد'));
        if ($uom === '') {
            $uom = 'عدد';
        }
        customer_core_execute(
            $conn,
            "INSERT INTO dbo.erp_workshop_internal_consumable_request_items
                (request_id, inventory_item_id, manual_description, quantity, unit_of_measure, stock_managed,
                 customer_billable, invoice_excluded, notes)
             VALUES (?, ?, ?, ?, ?, ?, 0, 1, ?)",
            [
                $requestId,
                $invId > 0 ? $invId : null,
                $manual !== '' ? $manual : null,
                $qty,
                $uom,
                $stockManaged,
                trim((string)($it['notes'] ?? '')) ?: null,
            ]
        );
    }
    m360_ws_ic_history_add($conn, $requestId, $jobcardId, 'IC_CREATED', null, 'DRAFT', 'ثبت مصرف داخلی', $actorId);
    return ['ok' => true, 'message' => 'درخواست مصرف داخلی ثبت شد. این مورد در صورتحساب مشتری درج نمی‌شود.', 'request_id' => $requestId];
}

function m360_ws_ic_submit($conn, int $requestId, int $actorId): array
{
    $r = m360_ws_ic_fetch_request($conn, $requestId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'درخواست یافت نشد.'];
    }
    $st = strtoupper((string)$r['status']);
    if (!in_array($st, ['DRAFT', 'RETURNED'], true)) {
        return ['ok' => false, 'message' => 'فقط پیش‌نویس یا برگشتی قابل ارسال است.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_internal_consumable_requests
         SET status=N'SUBMITTED', submitted_at=SYSUTCDATETIME(), updated_at=SYSUTCDATETIME()
         WHERE request_id=?",
        [$requestId]
    );
    m360_ws_ic_history_add($conn, $requestId, (int)$r['jobcard_id'], 'IC_SUBMITTED', $st, 'SUBMITTED', 'ارسال برای تأیید', $actorId);
    return ['ok' => true, 'message' => 'درخواست مصرف داخلی ارسال شد.'];
}

/**
 * Approve + issue stock (non-billable). Duplicate approval blocked.
 */
function m360_ws_ic_approve($conn, int $requestId, int $actorId): array
{
    $r = m360_ws_ic_fetch_request($conn, $requestId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'درخواست یافت نشد.'];
    }
    if (strtoupper((string)$r['status']) !== 'SUBMITTED') {
        return ['ok' => false, 'message' => 'فقط درخواست ارسال‌شده قابل تأیید است.'];
    }
    if ((int)$r['requesting_user_id'] === $actorId) {
        return ['ok' => false, 'message' => 'ایجادکننده نمی‌تواند درخواست خود را تأیید کند.'];
    }
    $items = m360_ws_ic_fetch_items($conn, $requestId);
    if ($items === []) {
        return ['ok' => false, 'message' => 'قلمی برای تأیید وجود ندارد.'];
    }

    // Own a top-level transaction only when none is open (avoid nested COMMIT no-ops under UAT/caller txns).
    $tranCount = (int)(customer_core_scalar($conn, 'SELECT @@TRANCOUNT') ?? 0);
    $ownsTx = $tranCount < 1;
    if ($ownsTx) {
        @odbc_autocommit($conn, false);
    }

    $didStockIssue = false;
    try {
        foreach ($items as $it) {
            $itemId = (int)$it['request_item_id'];
            if (!empty($it['inventory_movement_id'])) {
                throw new RuntimeException('duplicate_movement');
            }
            $stockManaged = (int)($it['stock_managed'] ?? 0) === 1;
            $invItemId = (int)($it['inventory_item_id'] ?? 0);
            $qty = (float)$it['quantity'];
            $movementId = 0;

            if ($stockManaged) {
                if ($invItemId < 1) {
                    throw new RuntimeException('stock_item_required');
                }
                $item = customer_core_fetch_rows(
                    $conn,
                    'SELECT TOP 1 inventory_item_id, item_name, unit_name, is_active FROM dbo.erp_inventory_items WHERE inventory_item_id=?',
                    [$invItemId]
                );
                if ($item === [] || (int)($item[0]['is_active'] ?? 0) !== 1) {
                    throw new RuntimeException('invalid_inventory_item');
                }
                if (customer_core_table_exists($conn, 'erp_stock_balances')) {
                    $bal = customer_core_fetch_rows(
                        $conn,
                        'SELECT TOP 1 stock_balance_id, available_qty, reserved_qty FROM dbo.erp_stock_balances WHERE inventory_item_id=? ORDER BY stock_balance_id',
                        [$invItemId]
                    );
                    $available = (float)($bal[0]['available_qty'] ?? 0);
                    $reserved = (float)($bal[0]['reserved_qty'] ?? 0);
                    $free = $available - $reserved;
                    if ($bal !== [] && $free < $qty) {
                        throw new RuntimeException('insufficient_stock');
                    }
                }
                $note = 'INTERNAL_CONSUMABLE:REQ:' . $requestId . '|ITEM:' . $itemId . '|JC:' . (int)$r['jobcard_id'];
                // Idempotency: refuse if note already exists
                $dup = (int)(customer_core_scalar(
                    $conn,
                    "SELECT TOP 1 stock_movement_id FROM dbo.erp_inventory_stock_movements WHERE movement_note=? ORDER BY stock_movement_id DESC",
                    [$note]
                ) ?? 0);
                if ($dup > 0) {
                    throw new RuntimeException('duplicate_movement');
                }
                $okMov = customer_core_execute(
                    $conn,
                    "INSERT INTO dbo.erp_inventory_stock_movements
                        (inventory_item_id, stock_location_id, operation_case_id, movement_type, movement_qty, movement_status, movement_note, created_by)
                     VALUES (?, NULL, ?, N'INTERNAL_CONSUMABLE', ?, N'RECORDED', ?, ?)",
                    [$invItemId, (int)$r['jobcard_id'], $qty, $note, (string)$actorId]
                );
                if ($okMov === false) {
                    // Fallback movement_type for constrained schemas
                    $okMov = customer_core_execute(
                        $conn,
                        "INSERT INTO dbo.erp_inventory_stock_movements
                            (inventory_item_id, stock_location_id, operation_case_id, movement_type, movement_qty, movement_status, movement_note, created_by)
                         VALUES (?, NULL, ?, N'OUTBOUND', ?, N'RECORDED', ?, ?)",
                        [$invItemId, (int)$r['jobcard_id'], $qty, $note, (string)$actorId]
                    );
                }
                if ($okMov === false) {
                    throw new RuntimeException('movement_failed');
                }
                $movementId = (int)(customer_core_scalar(
                    $conn,
                    'SELECT TOP 1 stock_movement_id FROM dbo.erp_inventory_stock_movements WHERE movement_note=? ORDER BY stock_movement_id DESC',
                    [$note]
                ) ?? 0);
                if ($movementId < 1) {
                    throw new RuntimeException('movement_id_missing');
                }
                $didStockIssue = true;
                if (customer_core_table_exists($conn, 'erp_stock_balances')) {
                    $balId = (int)(customer_core_scalar(
                        $conn,
                        'SELECT TOP 1 stock_balance_id FROM dbo.erp_stock_balances WHERE inventory_item_id=? ORDER BY stock_balance_id',
                        [$invItemId]
                    ) ?? 0);
                    if ($balId > 0) {
                        customer_core_execute(
                            $conn,
                            "UPDATE dbo.erp_stock_balances
                             SET available_qty = CASE WHEN available_qty >= ? THEN available_qty - ? ELSE 0 END,
                                 updated_at = SYSUTCDATETIME(), updated_by = ?
                             WHERE stock_balance_id=?",
                            [$qty, $qty, (string)$actorId, $balId]
                        );
                    }
                }
            } else {
                // Non-stock path: description already required at create; no movement.
                if (trim((string)($it['manual_description'] ?? '')) === '') {
                    throw new RuntimeException('manual_description_required');
                }
            }

            // Authoritative cost snapshot (never invent zero as confirmed cost).
            require_once __DIR__ . '/m360-inventory-valuation-helper.php';
            $companyId = (int)($r['company_id'] ?? 0);
            if ($companyId < 1) {
                $companyId = (int)(m360_ws_resolve_jobcard_company_id($conn, (int)$r['jobcard_id']) ?? 0);
            }
            $costStatus = 'COST_PENDING';
            $unitSnap = null;
            $totalSnap = null;
            $methodSnap = null;
            $valId = null;
            $currency = null;
            $srcType = null;
            $srcId = null;
            if ($invItemId > 0 && $companyId > 0) {
                $resolved = m360_inv_val_resolve_approved($conn, $invItemId, $companyId);
                if (!empty($resolved['ok']) && $resolved['unit_cost'] !== null && (float)$resolved['unit_cost'] > 0) {
                    $unitSnap = (float)$resolved['unit_cost'];
                    $totalSnap = round($unitSnap * $qty, 4);
                    $methodSnap = (string)$resolved['method'];
                    $valId = (int)($resolved['valuation']['valuation_id'] ?? 0);
                    $currency = (string)($resolved['valuation']['currency'] ?? 'IRR');
                    $srcType = trim((string)($resolved['valuation']['source_document_type'] ?? '')) ?: null;
                    $srcId = isset($resolved['valuation']['source_document_id']) && $resolved['valuation']['source_document_id'] !== ''
                        ? (int)$resolved['valuation']['source_document_id'] : null;
                    $costStatus = 'COST_CONFIRMED';
                }
            }
            if ($costStatus === 'COST_PENDING') {
                m360_inv_val_open_pending_task(
                    $conn,
                    max(1, $companyId),
                    $invItemId > 0 ? $invItemId : null,
                    (int)$r['jobcard_id'],
                    $requestId,
                    $itemId,
                    'بهای داخلی این قلم هنوز تأیید نشده است.',
                    $qty
                );
            }

            if ($movementId > 0) {
                $upd = customer_core_execute(
                    $conn,
                    'UPDATE dbo.erp_workshop_internal_consumable_request_items
                     SET inventory_movement_id = COALESCE(NULLIF(inventory_movement_id, 0), ?),
                         customer_billable = 0,
                         invoice_excluded = 1,
                         cost_status = ?,
                         valuation_method = ?,
                         valuation_id = ?,
                         internal_unit_cost = ?,
                         internal_total_cost = ?,
                         cost_currency = ?,
                         cost_source_document_type = ?,
                         cost_source_document_id = ?,
                         cost_snapshot_at = SYSUTCDATETIME()
                     WHERE request_item_id = ?',
                    [$movementId, $costStatus, $methodSnap, $valId, $unitSnap, $totalSnap, $currency, $srcType, $srcId, $itemId]
                );
            } else {
                $upd = customer_core_execute(
                    $conn,
                    'UPDATE dbo.erp_workshop_internal_consumable_request_items
                     SET customer_billable = 0,
                         invoice_excluded = 1,
                         cost_status = ?,
                         valuation_method = ?,
                         valuation_id = ?,
                         internal_unit_cost = ?,
                         internal_total_cost = ?,
                         cost_currency = ?,
                         cost_source_document_type = ?,
                         cost_source_document_id = ?,
                         cost_snapshot_at = SYSUTCDATETIME()
                     WHERE request_item_id = ?',
                    [$costStatus, $methodSnap, $valId, $unitSnap, $totalSnap, $currency, $srcType, $srcId, $itemId]
                );
            }
            if ($upd === false) {
                throw new RuntimeException('item_update_failed');
            }
            if ($movementId > 0) {
                $linked = (int)(customer_core_scalar(
                    $conn,
                    'SELECT inventory_movement_id FROM dbo.erp_workshop_internal_consumable_request_items WHERE request_item_id=?',
                    [$itemId]
                ) ?? 0);
                if ($linked !== (int)$movementId) {
                    throw new RuntimeException('movement_link_failed');
                }
            }
        }

        $statusUpd = customer_core_execute(
            $conn,
            "UPDATE dbo.erp_workshop_internal_consumable_requests
             SET status=N'ISSUED', approved_at=SYSUTCDATETIME(), approved_by_user_id=?, updated_at=SYSUTCDATETIME()
             WHERE request_id=? AND status=N'SUBMITTED'",
            [$actorId, $requestId]
        );
        if ($statusUpd === false) {
            throw new RuntimeException('status_update_failed');
        }
        m360_ws_ic_history_add($conn, $requestId, (int)$r['jobcard_id'], 'IC_APPROVED_ISSUED', 'SUBMITTED', 'ISSUED', 'تأیید و صدور مصرف داخلی غیرقابل‌صورتحساب', $actorId);
        if ($ownsTx) {
            @odbc_commit($conn);
            @odbc_autocommit($conn, true);
        }
        $msg = $didStockIssue
            ? 'مصرف داخلی تأیید و از انبار کسر شد (بدون خط صورتحساب مشتری).'
            : 'مصرف داخلی تأیید شد (غیرانباردار؛ بدون خط صورتحساب مشتری).';
        return ['ok' => true, 'message' => $msg];
    } catch (Throwable $e) {
        if ($ownsTx) {
            @odbc_rollback($conn);
            @odbc_autocommit($conn, true);
        }
        $map = [
            'insufficient_stock' => 'موجودی کافی نیست.',
            'duplicate_movement' => 'حرکت انبار تکراری مسدود شد.',
            'invalid_inventory_item' => 'قلم انبار نامعتبر است.',
            'stock_item_required' => 'برای قلم انباردار، شناسه کالا الزامی است.',
            'manual_description_required' => 'شرح دستی برای مصرف غیرانباردار الزامی است.',
            'movement_failed' => 'ثبت حرکت انبار ناموفق بود.',
            'movement_id_missing' => 'شناسه حرکت انبار دریافت نشد.',
            'item_update_failed' => 'به‌روزرسانی قلم مصرف داخلی ناموفق بود.',
            'movement_link_failed' => 'پیوند حرکت انبار به قلم مصرف برقرار نشد.',
            'status_update_failed' => 'به‌روزرسانی وضعیت درخواست ناموفق بود.',
        ];
        $msg = $map[$e->getMessage()] ?? ('تأیید مصرف داخلی ناموفق بود: ' . $e->getMessage());
        return ['ok' => false, 'message' => $msg];
    }
}

function m360_ws_ic_return($conn, int $requestId, int $actorId, string $reason): array
{
    $reason = trim($reason);
    if ($reason === '') {
        return ['ok' => false, 'message' => 'ذکر دلیل برگشت به فارسی الزامی است.'];
    }
    $r = m360_ws_ic_fetch_request($conn, $requestId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'درخواست یافت نشد.'];
    }
    if (strtoupper((string)$r['status']) !== 'SUBMITTED') {
        return ['ok' => false, 'message' => 'فقط درخواست ارسال‌شده قابل برگشت عادی است.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_internal_consumable_requests
         SET status=N'RETURNED', returned_at=SYSUTCDATETIME(), returned_by_user_id=?, return_reason=?, updated_at=SYSUTCDATETIME()
         WHERE request_id=?",
        [$actorId, $reason, $requestId]
    );
    m360_ws_ic_history_add($conn, $requestId, (int)$r['jobcard_id'], 'IC_RETURNED', 'SUBMITTED', 'RETURNED', $reason, $actorId);
    return ['ok' => true, 'message' => 'درخواست برای اصلاح برگشت داده شد (بدون حرکت انبار).'];
}

/**
 * JobCard confirmed internal-consumable true cost (excludes COST_PENDING / unknown).
 * Unknown cost is never treated as zero.
 */
function m360_ws_ic_jobcard_true_cost($conn, int $jobcardId): float
{
    require_once __DIR__ . '/m360-inventory-valuation-helper.php';
    $b = m360_inv_val_jobcard_true_cost_breakdown($conn, $jobcardId);
    return (float)$b['confirmed_cost'];
}

/** @return list<array<string,mixed>> */
function m360_ws_ic_list_by_status($conn, array $statuses, int $limit = 100): array
{
    $statuses = array_values(array_filter(array_map(static fn($s) => strtoupper(trim((string)$s)), $statuses)));
    if ($statuses === []) {
        return [];
    }
    $ph = implode(',', array_fill(0, count($statuses), '?'));
    return customer_core_fetch_rows(
        $conn,
        "SELECT TOP {$limit} * FROM dbo.erp_workshop_internal_consumable_requests
         WHERE status IN ($ph) ORDER BY request_id DESC",
        $statuses
    );
}
