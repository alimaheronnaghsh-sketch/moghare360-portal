<?php
require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-audit.php';
require_once __DIR__ . '/inv360-workflow.php';

function inv360_gr_list($conn): array
{
    $rows = inv360_rows($conn, 'SELECT TOP 100 * FROM dbo.inv360_goods_receipts ORDER BY gr_id DESC', []);
    foreach ($rows as &$r) {
        $r['GRNo'] = $r['gr_no'];
        $r['ReceiptType'] = $r['receipt_type'];
        $r['GRStatus'] = $r['gr_status'];
    }
    unset($r);
    return $rows;
}

function inv360_gr_create($conn, array $d, int $userId): array
{
    $no = 'GR-' . gmdate('YmdHis') . '-' . random_int(10, 99);
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.inv360_goods_receipts (gr_no, po_id, warehouse_id, location_id, receipt_type, gr_status, qty_control_note, created_by)
         VALUES (?,?,?,?,?,N\'draft\',?,?)',
        [
            $no, ((int)($d['po_id'] ?? 0)) ?: null, ((int)($d['warehouse_id'] ?? 0)) ?: null,
            ((int)($d['location_id'] ?? 0)) ?: null, $d['receipt_type'] ?? 'from_purchase', $d['notes'] ?? null, $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت رسید ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 gr_id FROM dbo.inv360_goods_receipts WHERE gr_no=?', [$no]) ?? 0);
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
        'INSERT INTO dbo.inv360_goods_receipt_lines (gr_id, po_line_id, item_id, item_text, received_qty, unit_cost)
         VALUES (?,?,?,?,?,?)',
        [
            $grId, ((int)($d['po_line_id'] ?? 0)) ?: null, ((int)($d['part_id'] ?? 0)) ?: null,
            $d['item_text'] ?? 'قلم دریافت', $qty, (float)($d['unit_cost'] ?? 0),
        ]
    );
    return $ok === false ? ['ok' => false, 'message' => 'افزودن قلم دریافت ناموفق بود.'] : ['ok' => true, 'message' => 'قلم دریافت ثبت شد.'];
}

function inv360_qc_list($conn): array
{
    return inv360_rows(
        $conn,
        'SELECT TOP 100 q.qc_event_id AS QCRecordID, q.action_code AS ResultCode, q.created_at AS CreatedAt, i.item_name_fa AS ItemName
         FROM dbo.inv360_qc_events q LEFT JOIN dbo.inv360_items i ON i.item_id=q.item_id ORDER BY q.qc_event_id DESC',
        []
    );
}

function inv360_qc_create($conn, array $d, int $userId): array
{
    return inv360_qc_action(
        $conn,
        (int)($d['gr_id'] ?? 0),
        strtolower((string)($d['result'] ?? 'accept')),
        (float)($d['qty'] ?? 0),
        ((int)($d['part_id'] ?? 0)) ?: null,
        ((int)($d['warehouse_id'] ?? 0)) ?: null,
        ((int)($d['location_id'] ?? 0)) ?: null,
        (string)($d['notes'] ?? ''),
        $userId
    );
}

function inv360_qc_action($conn, int $grId, string $action, float $qty, ?int $itemId, ?int $warehouseId, ?int $locationId, string $note, int $userId): array
{
    $action = strtolower(trim($action));
    if (!in_array($action, ['accept', 'reject', 'quarantine', 'release', 'return_supplier'], true)) {
        return ['ok' => false, 'message' => 'اقدام QC نامعتبر است.'];
    }
    if ($qty <= 0 || !$itemId) {
        return ['ok' => false, 'message' => 'کالا و مقدار الزامی است.'];
    }
    inv360_exec(
        $conn,
        'INSERT INTO dbo.inv360_qc_events (gr_id, item_id, action_code, qty, note_text, created_by) VALUES (?,?,?,?,?,?)',
        [$grId > 0 ? $grId : null, $itemId, $action, $qty, $note, $userId]
    );
    if ($action === 'quarantine') {
        $r = inv360_balance_adjust($conn, $itemId, $warehouseId, $locationId, 0, 0, $qty);
        if (empty($r['ok'])) {
            return $r;
        }
    } elseif ($action === 'release') {
        $r = inv360_balance_adjust($conn, $itemId, $warehouseId, $locationId, 0, 0, -$qty);
        if (empty($r['ok'])) {
            return $r;
        }
    }
    if ($grId > 0) {
        inv360_exec($conn, 'UPDATE dbo.inv360_goods_receipts SET qc_result=?, gr_status=? WHERE gr_id=?', [$action, $action === 'reject' ? 'rejected' : 'posted', $grId]);
    }
    inv360_audit($conn, 'QC', (string)$grId, strtoupper($action), $note, $userId);
    return ['ok' => true, 'message' => 'اقدام کنترل کیفیت ثبت شد.'];
}

function inv360_qc_quarantine($conn, int $itemId, float $qty, ?int $warehouseId, ?int $locationId, int $userId): array
{
    return inv360_qc_action($conn, 0, 'quarantine', $qty, $itemId, $warehouseId, $locationId, 'quarantine', $userId);
}

function inv360_qc_release_quarantine($conn, int $itemId, float $qty, ?int $warehouseId, ?int $locationId, int $userId): array
{
    return inv360_qc_action($conn, 0, 'release', $qty, $itemId, $warehouseId, $locationId, 'release', $userId);
}

function inv360_quarantine_list($conn): array
{
    return inv360_rows(
        $conn,
        'SELECT b.*, i.item_name_fa AS ItemName, i.workshop_code AS WorkshopCode, i.technical_code AS TechnicalCode, b.quarantine_qty AS QuarantineQty
         FROM dbo.inv360_stock_balances b LEFT JOIN dbo.inv360_items i ON i.item_id=b.item_id
         WHERE b.quarantine_qty > 0 ORDER BY b.balance_id DESC',
        []
    );
}
