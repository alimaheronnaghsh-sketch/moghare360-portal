<?php
require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-audit.php';
require_once __DIR__ . '/inv360-workflow.php';

function inv360_gr_list($conn): array
{
    return inv360_rows($conn, 'SELECT TOP 100 * FROM dbo.Inv360GoodsReceipts ORDER BY GoodsReceiptID DESC', []);
}

function inv360_gr_create($conn, array $d, int $userId): array
{
    $no = 'GR-' . gmdate('YmdHis') . '-' . random_int(10, 99);
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360GoodsReceipts
            (GRNo, PurchaseOrderID, WarehouseID, LocationID, GRStatus, QtyControlNote, QualityControlNote, DocumentControlNote, CreatedByUserID)
         VALUES (?,?,?,?,N\'draft\',?,?,?,?)',
        [
            $no, ((int)($d['po_id'] ?? 0)) ?: null, ((int)($d['warehouse_id'] ?? 0)) ?: null, ((int)($d['location_id'] ?? 0)) ?: null,
            $d['qty_note'] ?? ($d['notes'] ?? null), $d['quality_note'] ?? null, $d['doc_note'] ?? null, $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت رسید ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 GoodsReceiptID FROM dbo.Inv360GoodsReceipts WHERE GRNo=?', [$no]) ?? 0);
    if (((int)($d['part_id'] ?? 0)) > 0) {
        inv360_gr_add_line($conn, $id, $d);
    }
    inv360_audit($conn, 'GR', (string)$id, 'CREATED', $no, $userId);
    return ['ok' => true, 'message' => 'رسید کالا ثبت شد.', 'gr_id' => $id, 'gr_no' => $no];
}

function inv360_gr_add_line($conn, int $grId, array $d): array
{
    $qty = (float)($d['qty'] ?? 0);
    if ($grId < 1 || $qty <= 0) {
        return ['ok' => false, 'message' => 'قلم دریافت نامعتبر است.'];
    }
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360GoodsReceiptLines (GoodsReceiptID, POLineID, PartID, ItemText, ReceivedQty, AcceptedQty, RejectedQty, QuarantineQty, UnitCost)
         VALUES (?,?,?,?,?,?,?,?,?)',
        [
            $grId, ((int)($d['po_line_id'] ?? 0)) ?: null, ((int)($d['part_id'] ?? 0)) ?: null, $d['item_text'] ?? 'قلم دریافت',
            $qty, 0, 0, 0, (float)($d['unit_cost'] ?? 0),
        ]
    );
    return $ok === false ? ['ok' => false, 'message' => 'افزودن قلم دریافت ناموفق بود.'] : ['ok' => true, 'message' => 'قلم دریافت ثبت شد.'];
}

function inv360_qc_list($conn): array
{
    return inv360_rows(
        $conn,
        'SELECT TOP 100 q.*, p.ItemName,
                q.QcEventID AS QCRecordID, q.ActionCode AS ResultCode
         FROM dbo.Inv360QcEvents q
         LEFT JOIN dbo.Parts p ON p.PartID=q.PartID
         ORDER BY q.QcEventID DESC',
        []
    );
}

function inv360_qc_create($conn, array $d, int $userId): array
{
    $action = strtolower((string)($d['result'] ?? 'accept'));
    $partId = (int)($d['part_id'] ?? 0);
    $qty = (float)($d['qty'] ?? 0);
    $note = trim(implode(' | ', array_filter([
        (string)($d['notes'] ?? ''),
        !empty($d['qty_ok']) ? 'qty_ok' : 'qty_fail:' . ($d['qty_notes'] ?? ''),
        !empty($d['quality_ok']) ? 'quality_ok' : 'quality_fail:' . ($d['quality_notes'] ?? ''),
        !empty($d['doc_ok']) ? 'doc_ok' : 'doc_fail:' . ($d['doc_notes'] ?? ''),
    ])));
    return inv360_qc_action(
        $conn,
        (int)($d['gr_id'] ?? 0),
        $action,
        $qty,
        $partId > 0 ? $partId : null,
        ((int)($d['warehouse_id'] ?? 0)) ?: null,
        ((int)($d['location_id'] ?? 0)) ?: null,
        $note,
        $userId
    );
}

function inv360_qc_action($conn, int $grId, string $action, float $qty, ?int $partId, ?int $warehouseId, ?int $locationId, string $note, int $userId): array
{
    $action = strtolower(trim($action));
    if (!in_array($action, ['accept', 'reject', 'quarantine', 'release', 'return_supplier'], true)) {
        return ['ok' => false, 'message' => 'اقدام QC نامعتبر است.'];
    }
    if ($qty <= 0 || !$partId) {
        return ['ok' => false, 'message' => 'کالا و مقدار الزامی است.'];
    }
    inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360QcEvents (GoodsReceiptID, PartID, ActionCode, Qty, NoteText, CreatedByUserID) VALUES (?,?,?,?,?,?)',
        [$grId > 0 ? $grId : null, $partId, $action, $qty, $note, $userId]
    );

    if ($action === 'accept') {
        // Accept does not auto-increase stock here when goods already received via GR post.
        if ($grId > 0) {
            inv360_exec($conn, 'UPDATE dbo.Inv360GoodsReceipts SET QCResult=N\'accepted\', GRStatus=N\'posted\', PostedAt=SYSUTCDATETIME() WHERE GoodsReceiptID=?', [$grId]);
        }
    } elseif ($action === 'quarantine') {
        $r = inv360_balance_adjust($conn, $partId, $warehouseId, $locationId, 0, 0, $qty);
        if (empty($r['ok'])) {
            return $r;
        }
        if ($grId > 0) {
            inv360_exec($conn, 'UPDATE dbo.Inv360GoodsReceipts SET QCResult=N\'quarantine\', GRStatus=N\'quarantine\' WHERE GoodsReceiptID=?', [$grId]);
        }
    } elseif ($action === 'release') {
        $r = inv360_balance_adjust($conn, $partId, $warehouseId, $locationId, 0, 0, -$qty);
        if (empty($r['ok'])) {
            return $r;
        }
        if ($grId > 0) {
            inv360_exec($conn, 'UPDATE dbo.Inv360GoodsReceipts SET QCResult=N\'released\', GRStatus=N\'posted\' WHERE GoodsReceiptID=?', [$grId]);
        }
    } elseif ($action === 'reject' || $action === 'return_supplier') {
        if ($grId > 0) {
            inv360_exec($conn, 'UPDATE dbo.Inv360GoodsReceipts SET QCResult=?, GRStatus=N\'rejected\' WHERE GoodsReceiptID=?', [$action, $grId]);
        }
        if ($action === 'return_supplier') {
            $retNo = 'SR-' . gmdate('YmdHis');
            inv360_exec(
                $conn,
                'INSERT INTO dbo.Inv360SupplierReturns (ReturnNo, PartID, Qty, ReasonText, ReturnStatus, CreatedByUserID)
                 VALUES (?,?,?,?,N\'submitted\',?)',
                [$retNo, $partId, $qty, $note !== '' ? $note : 'مرجوعی QC', $userId]
            );
        }
    }
    inv360_audit($conn, 'QC', (string)$grId, strtoupper($action), $note, $userId);
    return ['ok' => true, 'message' => 'اقدام کنترل کیفیت ثبت شد.'];
}

function inv360_qc_quarantine($conn, int $partId, float $qty, ?int $warehouseId, ?int $locationId, int $userId): array
{
    return inv360_qc_action($conn, 0, 'quarantine', $qty, $partId, $warehouseId, $locationId, 'quarantine', $userId);
}

function inv360_qc_release_quarantine($conn, int $partId, float $qty, ?int $warehouseId, ?int $locationId, int $userId): array
{
    return inv360_qc_action($conn, 0, 'release', $qty, $partId, $warehouseId, $locationId, 'release', $userId);
}

function inv360_quarantine_list($conn): array
{
    return inv360_rows(
        $conn,
        'SELECT b.*,
                (b.PhysicalQty - b.ReservedQty - b.QuarantineQty - b.BlockedQty) AS AvailableQty,
                p.ItemName, p.WorkshopCode, p.TechnicalCode
         FROM dbo.Inv360StockBalances b
         LEFT JOIN dbo.Parts p ON p.PartID=b.PartID
         WHERE b.QuarantineQty > 0
         ORDER BY b.BalanceID DESC',
        []
    );
}
