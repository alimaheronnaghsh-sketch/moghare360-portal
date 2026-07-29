<?php
require_once __DIR__ . '/inv360-db.php';
require_once __DIR__ . '/inv360-audit.php';

function inv360_suppliers_list($conn): array
{
    return inv360_rows($conn, 'SELECT * FROM dbo.Inv360Suppliers WHERE IsActive=1 ORDER BY SupplierID DESC', []);
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
        'INSERT INTO dbo.Inv360Suppliers (SupplierCode, SupplierName, ContactName, ContactPhone, PaymentTerms, CreatedByUserID)
         VALUES (?,?,?,?,?,?)',
        [$code, $name, $d['contact_name'] ?? null, $d['contact_phone'] ?? null, $d['payment_terms'] ?? null, $userId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت تأمین‌کننده ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 SupplierID FROM dbo.Inv360Suppliers WHERE SupplierCode=?', [$code]) ?? 0);
    inv360_audit($conn, 'SUPPLIER', (string)$id, 'CREATED', $name, $userId);
    return ['ok' => true, 'message' => 'تأمین‌کننده ثبت شد.', 'supplier_id' => $id];
}

function inv360_pr_list($conn): array
{
    return inv360_rows($conn, 'SELECT TOP 100 * FROM dbo.Inv360PurchaseRequests ORDER BY PurchaseRequestID DESC', []);
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
        'INSERT INTO dbo.Inv360PurchaseRequests
            (PRNo, RequesterName, DepartmentName, NeededDate, UrgencyCode, ReasonText, PartID, ItemText, Qty, CurrentStockSnapshot, SuggestedSuppliers, AttachmentNote, PRStatus, CreatedByUserID)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,N\'submitted\',?)',
        [
            $no, $d['requester'] ?? null, $d['department'] ?? null, ($d['needed_date'] ?? '') !== '' ? $d['needed_date'] : null,
            $d['urgency'] ?? 'normal', $d['reason'] ?? null, ((int)($d['part_id'] ?? 0)) ?: null, $d['item_text'] ?? null,
            $qty, $d['current_stock'] ?? null, $d['suggested_suppliers'] ?? null, $d['attachment_note'] ?? null, $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت درخواست خرید ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 PurchaseRequestID FROM dbo.Inv360PurchaseRequests WHERE PRNo=?', [$no]) ?? 0);
    inv360_audit($conn, 'PR', (string)$id, 'CREATED', $no, $userId);
    return ['ok' => true, 'message' => 'درخواست خرید ثبت شد.', 'pr_id' => $id, 'pr_no' => $no];
}

function inv360_rfq_list($conn): array
{
    return inv360_rows(
        $conn,
        'SELECT TOP 100 r.*, s.SupplierName FROM dbo.Inv360Rfqs r
         LEFT JOIN dbo.Inv360Suppliers s ON s.SupplierID=r.SupplierID
         ORDER BY r.RfqID DESC',
        []
    );
}

function inv360_rfq_comparison($conn, ?int $prId = null): array
{
    $rows = inv360_rfq_compare($conn, $prId);
    foreach ($rows as &$r) {
        $r['RFQNo'] = $r['RfqNo'] ?? ($r['RFQNo'] ?? '');
        $r['Score'] = $r['TotalScore'] ?? ($r['Score'] ?? 0);
    }
    unset($r);
    return $rows;
}

function inv360_rfq_create($conn, array $d, int $userId): array
{
    $no = 'RFQ-' . gmdate('YmdHis') . '-' . random_int(10, 99);
    $price = (float)($d['unit_price'] ?? 0);
    $qg = (float)($d['quality_grade'] ?? ($d['quality_score'] ?? 7));
    if ($qg <= 10) {
        $qg *= 10;
    }
    $resp = (float)($d['responsiveness'] ?? ($d['response_score'] ?? 70));
    if ($resp <= 10) {
        $resp *= 10;
    }
    $mismatch = (float)($d['mismatch_rate'] ?? 0);
    $retRate = (float)($d['return_rate'] ?? 0);
    $deliveryDays = (int)($d['delivery_days'] ?? 7);
    $ontime = max(0, min(100, 100 - max(0, $deliveryDays - 3) * 5));
    $returnScore = max(0, min(100, 100 - ($mismatch + $retRate) * 5));
    $priceScore = max(0, min(100, 100 - min(90, $price / 1000000)));
    $scores = [
        'price' => (float)($d['price_score'] ?? $priceScore),
        'quality' => (float)($d['quality_score'] ?? $qg),
        'ontime' => (float)($d['ontime_score'] ?? $ontime),
        'payment' => (float)($d['payment_score'] ?? 70),
        'response' => (float)($d['response_score'] ?? $resp),
        'return' => (float)($d['return_score'] ?? $returnScore),
    ];
    $total = $scores['price'] * 0.25 + $scores['quality'] * 0.25 + $scores['ontime'] * 0.20
        + $scores['payment'] * 0.10 + $scores['response'] * 0.10 + $scores['return'] * 0.10;
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360Rfqs
            (RfqNo, PurchaseRequestID, SupplierID, PartID, ItemText, UnitPrice, DiscountPct, TaxPct, FreightAmount, DeliveryDays,
             PaymentTerms, QualityGrade, WarrantyText, CurrencyCode, ValidUntil, PriceScore, QualityScore, OnTimeScore, PaymentScore, ResponseScore, ReturnScore, TotalScore, CreatedByUserID)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,N\'IRR\',?,?,?,?,?,?,?,?,?)',
        [
            $no, ((int)($d['pr_id'] ?? 0)) ?: null, ((int)($d['supplier_id'] ?? 0)) ?: null, ((int)($d['part_id'] ?? 0)) ?: null,
            $d['item_text'] ?? null, $price, (float)($d['discount'] ?? 0), (float)($d['tax'] ?? 0), (float)($d['freight'] ?? 0),
            ((int)($d['delivery_days'] ?? 0)) ?: null, $d['payment_terms'] ?? null, $d['quality_grade'] ?? null, $d['warranty'] ?? null,
            ($d['valid_until'] ?? '') !== '' ? $d['valid_until'] : null,
            $scores['price'], $scores['quality'], $scores['ontime'], $scores['payment'], $scores['response'], $scores['return'], $total, $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت RFQ ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 RfqID FROM dbo.Inv360Rfqs WHERE RfqNo=?', [$no]) ?? 0);
    inv360_audit($conn, 'RFQ', (string)$id, 'CREATED', $no, $userId);
    return ['ok' => true, 'message' => 'RFQ ثبت شد.', 'rfq_id' => $id, 'total_score' => $total];
}

function inv360_rfq_compare($conn, ?int $prId = null): array
{
    if ($prId) {
        return inv360_rows(
            $conn,
            'SELECT r.*, s.SupplierName FROM dbo.Inv360Rfqs r LEFT JOIN dbo.Inv360Suppliers s ON s.SupplierID=r.SupplierID
             WHERE r.PurchaseRequestID=? ORDER BY r.TotalScore DESC',
            [$prId]
        );
    }
    return inv360_rows(
        $conn,
        'SELECT TOP 50 r.*, s.SupplierName FROM dbo.Inv360Rfqs r LEFT JOIN dbo.Inv360Suppliers s ON s.SupplierID=r.SupplierID
         ORDER BY r.TotalScore DESC',
        []
    );
}

function inv360_po_list($conn): array
{
    return inv360_rows(
        $conn,
        'SELECT TOP 100 po.*, s.SupplierName FROM dbo.Inv360PurchaseOrders po
         LEFT JOIN dbo.Inv360Suppliers s ON s.SupplierID=po.SupplierID
         ORDER BY po.PurchaseOrderID DESC',
        []
    );
}

function inv360_po_get($conn, int $poId): ?array
{
    return inv360_one(
        $conn,
        'SELECT po.*, s.SupplierName FROM dbo.Inv360PurchaseOrders po
         LEFT JOIN dbo.Inv360Suppliers s ON s.SupplierID=po.SupplierID
         WHERE po.PurchaseOrderID=?',
        [$poId]
    );
}

function inv360_po_create($conn, array $d, int $userId): array
{
    $no = 'PO-' . gmdate('YmdHis') . '-' . random_int(10, 99);
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360PurchaseOrders
            (PONo, SupplierID, PurchaseRequestID, RfqID, CurrencyCode, ExchangeRate, DeliveryPlace, PaymentTerms, WarrantyText, DelayPenalty, ResponsibleName, POStatus, CreatedByUserID)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,N\'approved\',?)',
        [
            $no, ((int)($d['supplier_id'] ?? 0)) ?: null, ((int)($d['pr_id'] ?? 0)) ?: null, ((int)($d['rfq_id'] ?? 0)) ?: null,
            $d['currency'] ?? 'IRR', (float)($d['exchange_rate'] ?? 1),
            $d['delivery_place'] ?? null, $d['payment_terms'] ?? null, $d['warranty'] ?? null, $d['delay_penalty'] ?? null,
            $d['responsible_person'] ?? ($d['responsible'] ?? null), $userId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت سفارش خرید ناموفق بود.'];
    }
    $id = (int)(inv360_scalar($conn, 'SELECT TOP 1 PurchaseOrderID FROM dbo.Inv360PurchaseOrders WHERE PONo=?', [$no]) ?? 0);
    if (((int)($d['part_id'] ?? 0)) > 0 || trim((string)($d['item_text'] ?? '')) !== '') {
        inv360_po_add_line($conn, $id, $d);
    }
    inv360_audit($conn, 'PO', (string)$id, 'CREATED', $no, $userId);
    return ['ok' => true, 'message' => 'سفارش خرید ثبت شد.', 'po_id' => $id, 'po_no' => $no];
}

function inv360_po_add_line($conn, int $poId, array $d): array
{
    $qty = (float)($d['qty'] ?? 0);
    if ($poId < 1 || $qty <= 0) {
        return ['ok' => false, 'message' => 'قلم سفارش نامعتبر است.'];
    }
    $ok = inv360_exec(
        $conn,
        'INSERT INTO dbo.Inv360PurchaseOrderLines (PurchaseOrderID, PartID, ItemText, OrderedQty, ReceivedQty, UnitPrice, DiscountPct, TaxPct)
         VALUES (?,?,?,?,0,?,?,?)',
        [
            $poId, ((int)($d['part_id'] ?? 0)) ?: null, $d['item_text'] ?? 'قلم سفارش', $qty,
            (float)($d['unit_price'] ?? 0), (float)($d['discount'] ?? 0), (float)($d['tax'] ?? 0),
        ]
    );
    return $ok === false ? ['ok' => false, 'message' => 'افزودن قلم ناموفق بود.'] : ['ok' => true, 'message' => 'قلم اضافه شد.'];
}

function inv360_po_lines($conn, int $poId): array
{
    $rows = inv360_rows(
        $conn,
        'SELECT l.*, p.ItemName FROM dbo.Inv360PurchaseOrderLines l
         LEFT JOIN dbo.Parts p ON p.PartID=l.PartID WHERE l.PurchaseOrderID=?',
        [$poId]
    );
    foreach ($rows as &$r) {
        $r['QtyOrdered'] = $r['OrderedQty'] ?? ($r['QtyOrdered'] ?? 0);
        $r['QtyReceived'] = $r['ReceivedQty'] ?? ($r['QtyReceived'] ?? 0);
    }
    unset($r);
    return $rows;
}

function inv360_po_receive_partial($conn, int $poId, int $partId, float $qty, int $userId = 0): array
{
    $line = inv360_one(
        $conn,
        'SELECT TOP 1 * FROM dbo.Inv360PurchaseOrderLines WHERE PurchaseOrderID=? AND PartID=? ORDER BY POLineID',
        [$poId, $partId]
    );
    if (!$line) {
        return ['ok' => false, 'message' => 'قلم سفارش برای این کالا یافت نشد.'];
    }
    return inv360_po_partial_receive($conn, (int)$line['POLineID'], $qty, $userId);
}

function inv360_po_partial_receive($conn, int $lineId, float $qty, int $userId): array
{
    $line = inv360_one($conn, 'SELECT TOP 1 * FROM dbo.Inv360PurchaseOrderLines WHERE POLineID=?', [$lineId]);
    if (!$line) {
        return ['ok' => false, 'message' => 'قلم سفارش یافت نشد.'];
    }
    $ordered = (float)$line['OrderedQty'];
    $recv = (float)$line['ReceivedQty'];
    $open = $ordered - $recv;
    if ($qty <= 0 || $qty > $open + 0.0001) {
        return ['ok' => false, 'message' => 'مقدار دریافت از مانده باز بیشتر است.'];
    }
    $new = $recv + $qty;
    inv360_exec($conn, 'UPDATE dbo.Inv360PurchaseOrderLines SET ReceivedQty=? WHERE POLineID=?', [$new, $lineId]);
    $openLines = (int)(inv360_scalar(
        $conn,
        'SELECT COUNT(*) FROM dbo.Inv360PurchaseOrderLines WHERE PurchaseOrderID=? AND ReceivedQty < OrderedQty',
        [(int)$line['PurchaseOrderID']]
    ) ?? 0);
    $st = $openLines > 0 ? 'partial' : 'closed';
    inv360_exec($conn, 'UPDATE dbo.Inv360PurchaseOrders SET POStatus=? WHERE PurchaseOrderID=?', [$st, (int)$line['PurchaseOrderID']]);
    inv360_audit($conn, 'PO', (string)$line['PurchaseOrderID'], 'PARTIAL_RECEIVE', 'line=' . $lineId . ';qty=' . $qty, $userId);
    return ['ok' => true, 'message' => 'دریافت جزئی ثبت شد.', 'open_qty' => max(0, $ordered - $new)];
}
