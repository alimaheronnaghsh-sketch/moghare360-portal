<?php
declare(strict_types=1);

/**
 * ISSUED internal-consumable reversal — opposite stock movement, original preserved.
 */

require_once __DIR__ . '/m360-workshop-internal-consumable-helper.php';
require_once __DIR__ . '/m360-inventory-valuation-helper.php';

const M360_WS_IC_REVERSAL_REASONS = [
    'WRONG_QTY' => 'مقدار اشتباه ثبت شده',
    'WRONG_ITEM' => 'قلم اشتباه انتخاب شده',
    'WRONG_JOBCARD' => 'پرونده تعمیر اشتباه انتخاب شده',
    'NOT_DELIVERED' => 'کالا تحویل نشده',
    'NOT_CONSUMED' => 'مصرف انجام نشده',
    'DUPLICATE' => 'ثبت تکراری',
    'OTHER' => 'سایر با توضیح اجباری',
];

function m360_ws_ic_rev_fetch($conn, int $reversalId): ?array
{
    if ($reversalId < 1 || !customer_core_table_exists($conn, 'erp_workshop_ic_reversals')) {
        return null;
    }
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.erp_workshop_ic_reversals WHERE reversal_id=?', [$reversalId]);
    return $rows[0] ?? null;
}

function m360_ws_ic_item_net_qty(array $item): float
{
    $q = (float)($item['quantity'] ?? 0);
    $rev = (float)($item['quantity_reversed'] ?? 0);
    return max(0.0, $q - $rev);
}

/**
 * @return array{ok:bool,message:string,reversal_id:int}
 */
function m360_ws_ic_reversal_request(
    $conn,
    int $requestItemId,
    float $qty,
    string $reasonCategory,
    string $reasonDetail,
    int $actorId,
    int $actorCompanyId,
    bool $isOwner
): array {
    $reasonCategory = strtoupper(trim($reasonCategory));
    $reasonDetail = trim($reasonDetail);
    if (!isset(M360_WS_IC_REVERSAL_REASONS[$reasonCategory])) {
        return ['ok' => false, 'message' => 'دسته دلیل ابطال نامعتبر است.', 'reversal_id' => 0];
    }
    if ($reasonDetail === '') {
        return ['ok' => false, 'message' => 'توضیح فارسی دلیل ابطال الزامی است.', 'reversal_id' => 0];
    }
    if ($reasonCategory === 'OTHER' && mb_strlen($reasonDetail) < 5) {
        return ['ok' => false, 'message' => 'برای «سایر» توضیح کامل‌تر الزامی است.', 'reversal_id' => 0];
    }
    if ($qty <= 0) {
        return ['ok' => false, 'message' => 'مقدار ابطال باید مثبت باشد.', 'reversal_id' => 0];
    }

    $items = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_workshop_internal_consumable_request_items WHERE request_item_id=?',
        [$requestItemId]
    );
    if ($items === []) {
        return ['ok' => false, 'message' => 'قلم مصرف داخلی یافت نشد.', 'reversal_id' => 0];
    }
    $it = $items[0];
    $requestId = (int)$it['request_id'];
    $req = m360_ws_ic_fetch_request($conn, $requestId);
    if ($req === null) {
        return ['ok' => false, 'message' => 'درخواست مصرف یافت نشد.', 'reversal_id' => 0];
    }
    $st = strtoupper((string)$req['status']);
    if (!in_array($st, ['ISSUED', 'REVERSAL_REQUESTED', 'REVERSAL_REJECTED'], true)) {
        return ['ok' => false, 'message' => 'فقط اقلام صادرشده قابل درخواست ابطال هستند.', 'reversal_id' => 0];
    }
    if ($st === 'REVERSED') {
        return ['ok' => false, 'message' => 'این مصرف کاملاً ابطال شده است.', 'reversal_id' => 0];
    }

    m360_ws_assert_jobcard_object_scope($conn, (int)$req['jobcard_id'], $actorCompanyId, $isOwner);
    $jcCompany = m360_ws_resolve_jobcard_company_id($conn, (int)$req['jobcard_id']);
    $companyId = (int)($req['company_id'] ?? 0);
    if ($companyId < 1) {
        $companyId = (int)($jcCompany ?? 0);
    }
    if (!$isOwner && $companyId > 0 && $companyId !== $actorCompanyId) {
        return ['ok' => false, 'message' => 'خارج از محدوده شرکت.', 'reversal_id' => 0];
    }

    $net = m360_ws_ic_item_net_qty($it);
    if ($net <= 0) {
        return ['ok' => false, 'message' => 'مقدار خالص قابل ابطال صفر است.', 'reversal_id' => 0];
    }
    if ($qty > $net + 0.00001) {
        return ['ok' => false, 'message' => 'مقدار ابطال از مقدار خالص صادرشده بیشتر است.', 'reversal_id' => 0];
    }

    $active = (int)(customer_core_scalar(
        $conn,
        "SELECT TOP 1 reversal_id FROM dbo.erp_workshop_ic_reversals
         WHERE request_item_id=? AND status=N'REVERSAL_REQUESTED'",
        [$requestItemId]
    ) ?? 0);
    if ($active > 0) {
        return ['ok' => false, 'message' => 'درخواست ابطال فعال تکراری مسدود شد.', 'reversal_id' => 0];
    }

    $origMov = (int)($it['inventory_movement_id'] ?? 0);
    $costPending = strtoupper((string)($it['cost_status'] ?? '')) === 'COST_PENDING' ? 1 : 0;

    $ok = customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_workshop_ic_reversals
            (company_id, request_id, request_item_id, jobcard_id, work_item_id, original_movement_id,
             reversal_qty, reason_category, reason_detail, status, requested_by_user_id, cost_was_pending)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, N'REVERSAL_REQUESTED', ?, ?)",
        [
            $companyId > 0 ? $companyId : $actorCompanyId,
            $requestId,
            $requestItemId,
            (int)$req['jobcard_id'],
            isset($req['work_item_id']) && $req['work_item_id'] !== '' ? (int)$req['work_item_id'] : null,
            $origMov > 0 ? $origMov : null,
            $qty,
            $reasonCategory,
            $reasonDetail,
            $actorId,
            $costPending,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت درخواست ابطال ناموفق بود.', 'reversal_id' => 0];
    }
    $rid = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 reversal_id FROM dbo.erp_workshop_ic_reversals
         WHERE request_item_id=? AND requested_by_user_id=? ORDER BY reversal_id DESC',
        [$requestItemId, $actorId]
    ) ?? 0);

    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_internal_consumable_requests
         SET status=N'REVERSAL_REQUESTED', updated_at=SYSUTCDATETIME()
         WHERE request_id=? AND status IN (N'ISSUED', N'REVERSAL_REJECTED')",
        [$requestId]
    );
    m360_ws_ic_history_add(
        $conn,
        $requestId,
        (int)$req['jobcard_id'],
        'IC_REVERSAL_REQUESTED',
        $st,
        'REVERSAL_REQUESTED',
        M360_WS_IC_REVERSAL_REASONS[$reasonCategory] . ' — ' . $reasonDetail . ' | qty=' . $qty,
        $actorId
    );

    return ['ok' => true, 'message' => 'درخواست ابطال ثبت شد (بدون تغییر موجودی تا تأیید).', 'reversal_id' => $rid];
}

/**
 * Approve reversal: opposite stock movement + cost reverse from original snapshot.
 *
 * @return array{ok:bool,message:string}
 */
function m360_ws_ic_reversal_approve(
    $conn,
    int $reversalId,
    int $actorId,
    int $actorCompanyId,
    bool $isOwner,
    bool $ownerOverride = false,
    string $overrideReason = ''
): array {
    $rev = m360_ws_ic_rev_fetch($conn, $reversalId);
    if ($rev === null) {
        return ['ok' => false, 'message' => 'درخواست ابطال یافت نشد.'];
    }
    if (strtoupper((string)$rev['status']) !== 'REVERSAL_REQUESTED') {
        return ['ok' => false, 'message' => 'فقط درخواست ابطال در انتظار قابل تأیید است.'];
    }
    if ((int)$rev['requested_by_user_id'] === $actorId && !$ownerOverride) {
        return ['ok' => false, 'message' => 'درخواست‌کننده نمی‌تواند ابطال خود را تأیید کند.'];
    }
    if ($ownerOverride && trim($overrideReason) === '') {
        return ['ok' => false, 'message' => 'دلیل ممیزی عبور مالک الزامی است.'];
    }
    if (!$isOwner && (int)$rev['company_id'] !== $actorCompanyId) {
        return ['ok' => false, 'message' => 'خارج از محدوده شرکت.'];
    }

    $requestId = (int)$rev['request_id'];
    $itemId = (int)$rev['request_item_id'];
    $req = m360_ws_ic_fetch_request($conn, $requestId);
    if ($req === null) {
        return ['ok' => false, 'message' => 'درخواست مصرف یافت نشد.'];
    }
    // Original creator cannot silently approve their own correction
    if ((int)($req['requesting_user_id'] ?? 0) === $actorId && !$ownerOverride) {
        return ['ok' => false, 'message' => 'ایجادکننده مصرف نمی‌تواند ابطال را به‌صورت خاموش تأیید کند.'];
    }

    m360_ws_assert_jobcard_object_scope($conn, (int)$rev['jobcard_id'], $actorCompanyId, $isOwner);

    $items = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_workshop_internal_consumable_request_items WHERE request_item_id=?',
        [$itemId]
    );
    if ($items === []) {
        return ['ok' => false, 'message' => 'قلم مصرف یافت نشد.'];
    }
    $it = $items[0];
    $qty = (float)$rev['reversal_qty'];
    $net = m360_ws_ic_item_net_qty($it);
    if ($qty <= 0 || $qty > $net + 0.00001) {
        return ['ok' => false, 'message' => 'مقدار ابطال از مقدار خالص قابل برگشت بیشتر است.'];
    }
    if (!empty($rev['reversing_movement_id'])) {
        return ['ok' => false, 'message' => 'حرکت برگشتی تکراری مسدود شد.'];
    }

    $tranCount = (int)(customer_core_scalar($conn, 'SELECT @@TRANCOUNT') ?? 0);
    $ownsTx = $tranCount < 1;
    if ($ownsTx) {
        @odbc_autocommit($conn, false);
    }

    try {
        $origMovId = (int)($rev['original_movement_id'] ?? 0);
        if ($origMovId < 1) {
            $origMovId = (int)($it['inventory_movement_id'] ?? 0);
        }
        $invItemId = (int)($it['inventory_item_id'] ?? 0);
        $reversingMovId = null;
        $stockManaged = (int)($it['stock_managed'] ?? 0) === 1 && $invItemId > 0;

        if ($stockManaged) {
            if ($origMovId < 1) {
                throw new RuntimeException('original_movement_missing');
            }
            $orig = customer_core_fetch_rows(
                $conn,
                'SELECT TOP 1 stock_movement_id, inventory_item_id, movement_qty, movement_note
                 FROM dbo.erp_inventory_stock_movements WHERE stock_movement_id=?',
                [$origMovId]
            );
            if ($orig === [] || (int)($orig[0]['inventory_item_id'] ?? 0) !== $invItemId) {
                throw new RuntimeException('movement_mismatch');
            }
            $note = 'INTERNAL_CONSUMABLE_REVERSAL:REQ:' . $reversalId . ':ORIGINAL:' . $origMovId;
            $dup = (int)(customer_core_scalar(
                $conn,
                'SELECT TOP 1 stock_movement_id FROM dbo.erp_inventory_stock_movements WHERE movement_note=? ORDER BY stock_movement_id DESC',
                [$note]
            ) ?? 0);
            if ($dup > 0) {
                throw new RuntimeException('duplicate_reversal_movement');
            }

            $okMov = customer_core_execute(
                $conn,
                "INSERT INTO dbo.erp_inventory_stock_movements
                    (inventory_item_id, stock_location_id, operation_case_id, movement_type, movement_qty, movement_status, movement_note, created_by)
                 VALUES (?, NULL, ?, N'INTERNAL_CONSUMABLE_REVERSAL', ?, N'RECORDED', ?, ?)",
                [$invItemId, (int)$rev['jobcard_id'], $qty, $note, (string)$actorId]
            );
            if ($okMov === false) {
                $okMov = customer_core_execute(
                    $conn,
                    "INSERT INTO dbo.erp_inventory_stock_movements
                        (inventory_item_id, stock_location_id, operation_case_id, movement_type, movement_qty, movement_status, movement_note, created_by)
                     VALUES (?, NULL, ?, N'INBOUND', ?, N'RECORDED', ?, ?)",
                    [$invItemId, (int)$rev['jobcard_id'], $qty, $note, (string)$actorId]
                );
            }
            if ($okMov === false) {
                throw new RuntimeException('reversal_movement_failed');
            }
            $reversingMovId = (int)(customer_core_scalar(
                $conn,
                'SELECT TOP 1 stock_movement_id FROM dbo.erp_inventory_stock_movements WHERE movement_note=? ORDER BY stock_movement_id DESC',
                [$note]
            ) ?? 0);
            if ($reversingMovId < 1) {
                throw new RuntimeException('reversal_movement_id_missing');
            }

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
                         SET available_qty = available_qty + ?,
                             updated_at = SYSUTCDATETIME(), updated_by = ?
                         WHERE stock_balance_id=?",
                        [$qty, (string)$actorId, $balId]
                    );
                }
            }
        }

        // Cost: use original snapshot only — never current valuation
        $costPending = strtoupper((string)($it['cost_status'] ?? '')) === 'COST_PENDING'
            || (int)($rev['cost_was_pending'] ?? 0) === 1;
        $unitSnap = null;
        $revTotal = null;
        if (!$costPending) {
            $unitRaw = trim((string)($it['internal_unit_cost'] ?? ''));
            if ($unitRaw !== '') {
                $unitSnap = (float)$unitRaw;
                $revTotal = round($unitSnap * $qty, 4);
            }
        }

        $newRevQty = (float)($it['quantity_reversed'] ?? 0) + $qty;
        $newRevCost = (float)($it['reversed_total_cost'] ?? 0) + (float)($revTotal ?? 0);
        $issuedQty = (float)$it['quantity'];
        $itemFully = $newRevQty >= $issuedQty - 0.00001;

        customer_core_execute(
            $conn,
            'UPDATE dbo.erp_workshop_internal_consumable_request_items
             SET quantity_reversed = ?,
                 reversed_total_cost = ?,
                 customer_billable = 0,
                 invoice_excluded = 1
             WHERE request_item_id=?',
            [$newRevQty, $revTotal !== null || (float)($it['reversed_total_cost'] ?? 0) > 0 ? $newRevCost : null, $itemId]
        );

        // Pending-cost task adjust
        if ($costPending && customer_core_table_exists($conn, 'erp_inventory_cost_pending_tasks')) {
            $task = customer_core_fetch_rows(
                $conn,
                "SELECT TOP 1 * FROM dbo.erp_inventory_cost_pending_tasks
                 WHERE request_item_id=? AND task_status=N'OPEN' ORDER BY task_id DESC",
                [$itemId]
            );
            if ($task !== []) {
                $pendingQty = (float)($task[0]['pending_qty'] ?? $issuedQty);
                if ($pendingQty <= 0) {
                    $pendingQty = $issuedQty - ((float)($it['quantity_reversed'] ?? 0));
                }
                $left = max(0.0, $pendingQty - $qty);
                if ($itemFully || $left <= 0.00001) {
                    customer_core_execute(
                        $conn,
                        "UPDATE dbo.erp_inventory_cost_pending_tasks
                         SET task_status=N'CLOSED', pending_qty=0, closed_at=SYSUTCDATETIME(), closed_by_user_id=?,
                             task_note = CONCAT(ISNULL(task_note,N''), N' | بسته به‌دلیل ابطال کامل')
                         WHERE task_id=?",
                        [$actorId, (int)$task[0]['task_id']]
                    );
                } else {
                    customer_core_execute(
                        $conn,
                        "UPDATE dbo.erp_inventory_cost_pending_tasks
                         SET pending_qty=?, task_note = CONCAT(ISNULL(task_note,N''), N' | کاهش بابت ابطال جزئی')
                         WHERE task_id=?",
                        [$left, (int)$task[0]['task_id']]
                    );
                }
            }
        }

        $noteExtra = $ownerOverride ? (' | عبور مالک: ' . trim($overrideReason)) : '';
        customer_core_execute(
            $conn,
            "UPDATE dbo.erp_workshop_ic_reversals
             SET status=N'REVERSED', approved_by_user_id=?, approved_at=SYSUTCDATETIME(),
                 reversing_movement_id=?, reversed_unit_cost_snapshot=?, reversed_total_cost=?,
                 updated_at=SYSUTCDATETIME()
             WHERE reversal_id=? AND status=N'REVERSAL_REQUESTED'",
            [$actorId, $reversingMovId, $unitSnap, $revTotal, $reversalId]
        );

        // Prefer PHP itemFully — avoid ODBC stale COUNT/SUM inside open txn.
        if ($itemFully) {
            $newReqStatus = 'REVERSED';
        } else {
            $newReqStatus = 'ISSUED';
        }
        $pendingSiblings = (int)(customer_core_scalar(
            $conn,
            "SELECT COUNT(*) FROM dbo.erp_workshop_ic_reversals
             WHERE request_id=? AND status=N'REVERSAL_REQUESTED' AND reversal_id<>?",
            [$requestId, $reversalId]
        ) ?? 0);
        if ($pendingSiblings > 0) {
            $newReqStatus = 'REVERSAL_REQUESTED';
        }

        $stUpd = customer_core_execute(
            $conn,
            "UPDATE dbo.erp_workshop_internal_consumable_requests
             SET status=?, updated_at=SYSUTCDATETIME() WHERE request_id=?",
            [$newReqStatus, $requestId]
        );
        if ($stUpd === false) {
            throw new RuntimeException('request_status_update_failed');
        }

        m360_ws_ic_history_add(
            $conn,
            $requestId,
            (int)$rev['jobcard_id'],
            'IC_REVERSED',
            'REVERSAL_REQUESTED',
            $newReqStatus,
            'ابطال تأیید شد qty=' . $qty . ' mov=' . (string)($reversingMovId ?? 0) . $noteExtra,
            $actorId
        );

        if ($ownsTx) {
            @odbc_commit($conn);
            @odbc_autocommit($conn, true);
        }
        return ['ok' => true, 'message' => 'ابطال تأیید و حرکت مخالف انبار ثبت شد (حرکت اصلی حفظ شد).'];
    } catch (Throwable $e) {
        if ($ownsTx) {
            @odbc_rollback($conn);
            @odbc_autocommit($conn, true);
        }
        $map = [
            'original_movement_missing' => 'حرکت انبار اصلی یافت نشد.',
            'movement_mismatch' => 'عدم تطابق حرکت انبار با قلم کالا.',
            'duplicate_reversal_movement' => 'حرکت برگشتی تکراری مسدود شد.',
            'reversal_movement_failed' => 'ثبت حرکت برگشتی انبار ناموفق بود.',
            'reversal_movement_id_missing' => 'شناسه حرکت برگشتی دریافت نشد.',
        ];
        return ['ok' => false, 'message' => ($map[$e->getMessage()] ?? ('تأیید ابطال ناموفق: ' . $e->getMessage()))];
    }
}

function m360_ws_ic_reversal_reject($conn, int $reversalId, int $actorId, string $reason): array
{
    $reason = trim($reason);
    if ($reason === '') {
        return ['ok' => false, 'message' => 'دلیل رد ابطال الزامی است.'];
    }
    $rev = m360_ws_ic_rev_fetch($conn, $reversalId);
    if ($rev === null || strtoupper((string)$rev['status']) !== 'REVERSAL_REQUESTED') {
        return ['ok' => false, 'message' => 'درخواست ابطال قابل رد نیست.'];
    }
    if ((int)$rev['requested_by_user_id'] === $actorId) {
        return ['ok' => false, 'message' => 'درخواست‌کننده نمی‌تواند درخواست خود را رد کند.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_ic_reversals
         SET status=N'REVERSAL_REJECTED', rejected_by_user_id=?, rejected_at=SYSUTCDATETIME(),
             reject_reason=?, updated_at=SYSUTCDATETIME()
         WHERE reversal_id=?",
        [$actorId, $reason, $reversalId]
    );
    $other = (int)(customer_core_scalar(
        $conn,
        "SELECT COUNT(*) FROM dbo.erp_workshop_ic_reversals
         WHERE request_id=? AND status=N'REVERSAL_REQUESTED'",
        [(int)$rev['request_id']]
    ) ?? 0);
    $newSt = $other > 0 ? 'REVERSAL_REQUESTED' : 'ISSUED';
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_internal_consumable_requests SET status=?, updated_at=SYSUTCDATETIME() WHERE request_id=?",
        [$newSt, (int)$rev['request_id']]
    );
    m360_ws_ic_history_add($conn, (int)$rev['request_id'], (int)$rev['jobcard_id'], 'IC_REVERSAL_REJECTED', 'REVERSAL_REQUESTED', $newSt, $reason, $actorId);
    return ['ok' => true, 'message' => 'درخواست ابطال رد شد؛ مصرف صادرشده همچنان معتبر است.'];
}

/** @return list<array<string,mixed>> */
function m360_ws_ic_reversal_list($conn, array $statuses, int $companyId, bool $isOwner, int $limit = 100): array
{
    if (!customer_core_table_exists($conn, 'erp_workshop_ic_reversals')) {
        return [];
    }
    $statuses = array_values(array_filter(array_map(static fn($s) => strtoupper(trim((string)$s)), $statuses)));
    if ($statuses === []) {
        return [];
    }
    $ph = implode(',', array_fill(0, count($statuses), '?'));
    $params = $statuses;
    $companySql = '1=1';
    if (!$isOwner) {
        $companySql = 'company_id=?';
        $params[] = $companyId;
    }
    $limit = max(1, min(300, $limit));
    return customer_core_fetch_rows(
        $conn,
        "SELECT TOP {$limit} * FROM dbo.erp_workshop_ic_reversals
         WHERE status IN ($ph) AND ({$companySql})
         ORDER BY reversal_id DESC",
        $params
    );
}
