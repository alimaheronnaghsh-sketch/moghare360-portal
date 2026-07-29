<?php
require_once __DIR__ . '/inv360-workflow.php';

function inv360_stock_balances_list($conn): array
{
    return inv360_rows(
        $conn,
        'SELECT b.*,
                (b.PhysicalQty - b.ReservedQty - b.QuarantineQty - b.BlockedQty) AS AvailableQty,
                p.ItemName, p.WorkshopCode, p.TechnicalCode
         FROM dbo.Inv360StockBalances b
         LEFT JOIN dbo.Parts p ON p.PartID = b.PartID
         ORDER BY b.BalanceID DESC',
        []
    );
}

function inv360_stock_docs_list($conn): array
{
    return inv360_rows($conn, 'SELECT TOP 100 * FROM dbo.Inv360StockDocuments ORDER BY DocumentID DESC', []);
}

function inv360_stock_doc_get($conn, int $id): ?array
{
    return inv360_one($conn, 'SELECT TOP 1 * FROM dbo.Inv360StockDocuments WHERE DocumentID=?', [$id]);
}

function inv360_stock_doc_lines($conn, int $id): array
{
    return inv360_rows(
        $conn,
        'SELECT l.*, p.ItemName FROM dbo.Inv360StockDocumentLines l
         LEFT JOIN dbo.Parts p ON p.PartID = l.PartID WHERE l.DocumentID=?',
        [$id]
    );
}
