<?php
require_once __DIR__ . '/inv360-workflow.php';
require_once __DIR__ . '/inv360-search.php';

function inv360_items_list($conn, int $limit = 100): array
{
    return inv360_rows(
        $conn,
        'SELECT TOP ' . max(1, min(300, $limit)) . ' PartID, WorkshopCode, InternalCode, TechnicalCode, ItemName, ManufacturerBrand, ItemStatus, Quantity, IsActive
         FROM dbo.Parts WHERE ISNULL(IsDeleted,0)=0 ORDER BY PartID DESC',
        []
    );
}

function inv360_items_get($conn, int $id): ?array
{
    return inv360_one($conn, 'SELECT TOP 1 * FROM dbo.Parts WHERE PartID = ?', [$id]);
}
