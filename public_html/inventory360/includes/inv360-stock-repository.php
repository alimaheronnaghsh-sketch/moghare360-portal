<?php
require_once __DIR__ . '/inv360-workflow.php';

function inv360_stock_doc_map(?array $row): ?array
{
    if (!$row) {
        return null;
    }
    $row['DocumentID'] = $row['document_id'] ?? ($row['DocumentID'] ?? null);
    $row['DocNo'] = $row['doc_no'] ?? ($row['DocNo'] ?? '');
    $row['DocType'] = $row['doc_type'] ?? ($row['DocType'] ?? '');
    $row['DocStatus'] = $row['doc_status'] ?? ($row['DocStatus'] ?? '');
    return $row;
}

function inv360_stock_balances_list($conn): array
{
    return inv360_rows(
        $conn,
        'SELECT b.balance_id AS BalanceID, b.item_id AS PartID, b.warehouse_id AS WarehouseID, b.location_id AS LocationID,
                b.physical_qty AS PhysicalQty, b.reserved_qty AS ReservedQty, b.quarantine_qty AS QuarantineQty,
                b.in_transit_qty AS InTransitQty, b.blocked_qty AS BlockedQty, b.consignment_qty AS ConsignmentQty,
                b.unit_cost AS UnitCost,
                (b.physical_qty - b.reserved_qty - b.quarantine_qty - b.blocked_qty) AS AvailableQty,
                p.item_name_fa AS ItemName, p.workshop_code AS WorkshopCode, p.technical_code AS TechnicalCode
         FROM dbo.inv360_stock_balances b
         LEFT JOIN dbo.inv360_items p ON p.item_id = b.item_id
         ORDER BY b.balance_id DESC',
        []
    );
}

function inv360_stock_docs_list($conn): array
{
    $rows = inv360_rows($conn, 'SELECT TOP 100 * FROM dbo.inv360_stock_documents ORDER BY document_id DESC', []);
    foreach ($rows as &$r) {
        $r = inv360_stock_doc_map($r) ?? $r;
    }
    unset($r);
    return $rows;
}

function inv360_stock_doc_get($conn, int $id): ?array
{
    $row = inv360_one($conn, 'SELECT TOP 1 * FROM dbo.inv360_stock_documents WHERE document_id=?', [$id]);
    return inv360_stock_doc_map($row);
}

function inv360_stock_doc_lines($conn, int $id): array
{
    return inv360_rows(
        $conn,
        'SELECT l.line_id AS LineID, l.document_id AS DocumentID, l.item_id AS PartID, l.qty AS Qty,
                l.unit_cost AS UnitCost, l.line_note AS LineNote, p.item_name_fa AS ItemName
         FROM dbo.inv360_stock_document_lines l
         LEFT JOIN dbo.inv360_items p ON p.item_id = l.item_id WHERE l.document_id=?',
        [$id]
    );
}
