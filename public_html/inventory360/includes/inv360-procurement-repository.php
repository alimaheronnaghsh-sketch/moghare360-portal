<?php
require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-audit.php';

function inv360_suppliers_list($conn): array
{
    $rows = inv360_rows($conn, 'SELECT * FROM dbo.inv360_suppliers WHERE is_active=1 ORDER BY supplier_id DESC', []);
    foreach ($rows as &$r) {
        $r['SupplierID'] = $r['supplier_id'];
        $r['SupplierCode'] = $r['supplier_code'];
        $r['SupplierName'] = $r['supplier_name'];
        $r['SupplierStatus'] = $r['supplier_status'];
    }
    unset($r);
    return $rows;
}

function inv360_supplier_create($conn, array $d, int $userId): array
{
    $code = trim((string)($d['supplier_code'] ?? ''));
    $name = trim((string)($d['supplier_name'] ?? ''));
    if ($code === '' || $name === '') {
        return ['ok' => false, 'message' => 'کد و نام تأمین‌کننده الزامی است.'];
    }
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.inv360_suppliers (supplier_code, supplier_name, contact_name, contact_phone, payment_terms, created_by) VALUES (?,?,?,?,?,?)',
        [$code, $name, $d['contact_name'] ?? null, $d['contact_phone'] ?? null, $d['payment_terms'] ?? null, $userId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت تأمین‌کننده ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 supplier_id FROM dbo.inv360_suppliers WHERE supplier_code=?', [$code]) ?? 0);
    inv360_audit($conn, 'SUPPLIER', (string)$id, 'CREATED', $name, $userId);
    return ['ok' => true, 'message' => 'تأمین‌کننده ثبت شد.', 'supplier_id' => $id];
}

function inv360_pr_list($conn): array
{
    $rows = inv360_rows($conn, 'SELECT TOP 100 * FROM dbo.inv360_purchase_requests ORDER BY pr_id DESC', []);
    foreach ($rows as &$r) {
        $r['PRNo'] = $r['pr_no'];
        $r['ItemText'] = $r['item_text'];
        $r['Qty'] = $r['qty'];
        $r['PRStatus'] = $r['pr_status'];
    }
    unset($r);
    return $rows;
}

function inv360_pr_create($conn, array $d, int $userId): array
{
    $no = 'PR-' . gmdate('YmdHis') . '-' . random_int(10, 99);
    $qty = (float)($d['qty'] ?? 0);
    if ($qty <= 0) {
        return ['ok' => false, 'message' => 'تعداد نامعتبر است.'];
    }
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.inv360_purchase_requests
            (pr_no, requester_name, department_name, needed_date, urgency_code, reason_text, item_id, item_text, qty, current_stock_snapshot, suggested_suppliers, pr_status, created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,N\'submitted\',?)',
        [
            $no, $d['requester'] ?? null, $d['department'] ?? null, ($d['needed_date'] ?? '') !== '' ? $d['needed_date'] : null,
            $d['urgency'] ?? 'normal', $d['reason'] ?? null, ((int)($d['part_id'] ?? 0)) ?: null, $d['item_text'] ?? null,
            $qty, $d['current_stock'] ?? null, $d['suggested_suppliers'] ?? null, $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت درخواست خرید ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 pr_id FROM dbo.inv360_purchase_requests WHERE pr_no=?', [$no]) ?? 0);
    inv360_audit($conn, 'PR', (string)$id, 'CREATED', $no, $userId);
    return ['ok' => true, 'message' => 'درخواست خرید ثبت شد.', 'pr_id' => $id, 'pr_no' => $no];
}

function inv360_rfq_list($conn): array
{
    $rows = inv360_rows(
        $conn,
        'SELECT TOP 100 r.*, s.supplier_name AS SupplierName FROM dbo.inv360_rfqs r
         LEFT JOIN dbo.inv360_suppliers s ON s.supplier_id=r.supplier_id ORDER BY r.rfq_id DESC',
        []
    );
    foreach ($rows as &$r) {
        $r['RFQNo'] = $r['rfq_no'];
        $r['RfqNo'] = $r['rfq_no'];
        $r['UnitPrice'] = $r['unit_price'];
        $r['Score'] = $r['total_score'];
        $r['TotalScore'] = $r['total_score'];
        $r['RFQStatus'] = $r['rfq_status'];
    }
    unset($r);
    return $rows;
}

function inv360_rfq_comparison($conn, ?int $prId = null): array
{
    return inv360_rfq_list($conn);
}

function inv360_rfq_compare($conn, ?int $prId = null): array
{
    return inv360_rfq_list($conn);
}

function inv360_rfq_create($conn, array $d, int $userId): array
{
    $no = 'RFQ-' . gmdate('YmdHis') . '-' . random_int(10, 99);
    $price = (float)($d['unit_price'] ?? 0);
    $qg = (float)($d['quality_grade'] ?? 8);
    if ($qg <= 10) {
        $qg *= 10;
    }
    $score = min(100.0, $qg * 0.25 + 70 * 0.75);
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.inv360_rfqs
            (rfq_no, pr_id, supplier_id, item_id, item_text, unit_price, discount_pct, tax_pct, freight_amount, delivery_days,
             payment_terms, quality_grade, warranty_text, currency_code, valid_until, total_score, created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,N\'IRR\',?,?,?)',
        [
            $no, ((int)($d['pr_id'] ?? 0)) ?: null, ((int)($d['supplier_id'] ?? 0)) ?: null, ((int)($d['part_id'] ?? 0)) ?: null,
            $d['item_text'] ?? null, $price, (float)($d['discount'] ?? 0), (float)($d['tax'] ?? 0), (float)($d['freight'] ?? 0),
            ((int)($d['delivery_days'] ?? 0)) ?: null, $d['payment_terms'] ?? null, $d['quality_grade'] ?? null, $d['warranty'] ?? null,
            ($d['valid_until'] ?? '') !== '' ? $d['valid_until'] : null, $score, $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت RFQ ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 rfq_id FROM dbo.inv360_rfqs WHERE rfq_no=?', [$no]) ?? 0);
    inv360_audit($conn, 'RFQ', (string)$id, 'CREATED', $no, $userId);
    return ['ok' => true, 'message' => 'RFQ ثبت شد.', 'rfq_id' => $id, 'total_score' => $score];
}

function inv360_po_list($conn): array
{
    $rows = inv360_rows(
        $conn,
        'SELECT TOP 100 po.*, s.supplier_name AS SupplierName FROM dbo.inv360_purchase_orders po
         LEFT JOIN dbo.inv360_suppliers s ON s.supplier_id=po.supplier_id ORDER BY po.po_id DESC',
        []
    );
    foreach ($rows as &$r) {
        $r['PurchaseOrderID'] = $r['po_id'];
        $r['PONo'] = $r['po_no'];
        $r['POStatus'] = $r['po_status'];
    }
    unset($r);
    return $rows;
}

function inv360_po_get($conn, int $poId): ?array
{
    $r = inv360_one(
        $conn,
        'SELECT po.*, s.supplier_name AS SupplierName FROM dbo.inv360_purchase_orders po
         LEFT JOIN dbo.inv360_suppliers s ON s.supplier_id=po.supplier_id WHERE po.po_id=?',
        [$poId]
    );
    if ($r) {
        $r['PurchaseOrderID'] = $r['po_id'];
        $r['PONo'] = $r['po_no'];
        $r['POStatus'] = $r['po_status'];
    }
    return $r;
}

function inv360_po_add_line($conn, int $poId, array $d): array
{
    $qty = (float)($d['qty'] ?? 0);
    if ($poId < 1 || $qty <= 0) {
        return ['ok' => false, 'message' => 'قلم سفارش نامعتبر است.'];
    }
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.inv360_purchase_order_lines (po_id, item_id, item_text, ordered_qty, received_qty, unit_price, discount_pct, tax_pct)
         VALUES (?,?,?,?,0,?,?,?)',
        [
            $poId, ((int)($d['part_id'] ?? 0)) ?: null, $d['item_text'] ?? 'قلم سفارش', $qty,
            (float)($d['unit_price'] ?? 0), (float)($d['discount'] ?? 0), (float)($d['tax'] ?? 0),
        ]
    );
    return $ok === false ? ['ok' => false, 'message' => 'افزودن قلم ناموفق بود.'] : ['ok' => true, 'message' => 'قلم اضافه شد.'];
}

function inv360_po_create($conn, array $d, int $userId): array
{
    $no = 'PO-' . gmdate('YmdHis') . '-' . random_int(10, 99);
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.inv360_purchase_orders
            (po_no, supplier_id, pr_id, rfq_id, currency_code, exchange_rate, delivery_place, payment_terms, warranty_text, delay_penalty, responsible_name, po_status, created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,N\'approved\',?)',
        [
            $no, ((int)($d['supplier_id'] ?? 0)) ?: null, ((int)($d['pr_id'] ?? 0)) ?: null, ((int)($d['rfq_id'] ?? 0)) ?: null,
            $d['currency'] ?? 'IRR', (float)($d['exchange_rate'] ?? 1), $d['delivery_place'] ?? null, $d['payment_terms'] ?? null,
            $d['warranty'] ?? null, $d['delay_penalty'] ?? null, $d['responsible_person'] ?? null, $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت سفارش خرید ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 po_id FROM dbo.inv360_purchase_orders WHERE po_no=?', [$no]) ?? 0);
    if (((int)($d['part_id'] ?? 0)) > 0) {
        inv360_po_add_line($conn, $id, $d);
    }
    inv360_audit($conn, 'PO', (string)$id, 'CREATED', $no, $userId);
    return ['ok' => true, 'message' => 'سفارش خرید ثبت شد.', 'po_id' => $id, 'po_no' => $no];
}

function inv360_po_lines($conn, int $poId): array
{
    $rows = inv360_rows(
        $conn,
        'SELECT l.*, i.item_name_fa AS ItemName FROM dbo.inv360_purchase_order_lines l
         LEFT JOIN dbo.inv360_items i ON i.item_id=l.item_id WHERE l.po_id=?',
        [$poId]
    );
    foreach ($rows as &$r) {
        $r['POLineID'] = $r['po_line_id'];
        $r['QtyOrdered'] = $r['ordered_qty'];
        $r['QtyReceived'] = $r['received_qty'];
        $r['OrderedQty'] = $r['ordered_qty'];
        $r['ReceivedQty'] = $r['received_qty'];
    }
    unset($r);
    return $rows;
}

function inv360_po_partial_receive($conn, int $lineId, float $qty, int $userId): array
{
    $line = inv360_one($conn, 'SELECT TOP 1 * FROM dbo.inv360_purchase_order_lines WHERE po_line_id=?', [$lineId]);
    if (!$line) {
        return ['ok' => false, 'message' => 'قلم سفارش یافت نشد.'];
    }
    $ordered = (float)$line['ordered_qty'];
    $recv = (float)$line['received_qty'];
    $open = $ordered - $recv;
    if ($qty <= 0 || $qty > $open + 0.0001) {
        return ['ok' => false, 'message' => 'مقدار دریافت از مانده باز بیشتر است.'];
    }
    $new = $recv + $qty;
    inv360_exec($conn, 'UPDATE dbo.inv360_purchase_order_lines SET received_qty=? WHERE po_line_id=?', [$new, $lineId]);
    $openLines = (int)(inv360_scalar($conn, 'SELECT COUNT(*) FROM dbo.inv360_purchase_order_lines WHERE po_id=? AND received_qty < ordered_qty', [(int)$line['po_id']]) ?? 0);
    inv360_exec($conn, 'UPDATE dbo.inv360_purchase_orders SET po_status=? WHERE po_id=?', [$openLines > 0 ? 'partial' : 'closed', (int)$line['po_id']]);
    inv360_audit($conn, 'PO', (string)$line['po_id'], 'PARTIAL_RECEIVE', 'line=' . $lineId . ';qty=' . $qty, $userId);
    return ['ok' => true, 'message' => 'دریافت جزئی ثبت شد.', 'open_qty' => max(0, $ordered - $new)];
}

function inv360_po_receive_partial($conn, int $poId, int $partId, float $qty, int $userId = 0): array
{
    $line = inv360_one($conn, 'SELECT TOP 1 * FROM dbo.inv360_purchase_order_lines WHERE po_id=? AND item_id=? ORDER BY po_line_id', [$poId, $partId]);
    if (!$line) {
        return ['ok' => false, 'message' => 'قلم سفارش برای این کالا یافت نشد.'];
    }
    return inv360_po_partial_receive($conn, (int)$line['po_line_id'], $qty, $userId);
}
