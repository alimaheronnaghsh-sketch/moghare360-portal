<?php
require_once __DIR__ . '/inv360-workflow.php';

function inv360_items_list($conn, int $limit = 100): array
{
    $limit = max(1, min(500, $limit));
    return inv360_rows(
        $conn,
        "SELECT TOP $limit item_id AS PartID, workshop_code AS WorkshopCode, technical_code AS TechnicalCode,
                item_name_fa AS ItemName, brand AS ManufacturerBrand, item_status AS ItemStatus, quantity AS Quantity
         FROM dbo.inv360_items WHERE is_deleted=0 ORDER BY item_id DESC",
        []
    );
}

function inv360_items_get($conn, int $id): ?array
{
    $r = inv360_one($conn, 'SELECT TOP 1 * FROM dbo.inv360_items WHERE item_id=? AND is_deleted=0', [$id]);
    if (!$r) {
        return null;
    }
    $r['PartID'] = $r['item_id'];
    $r['ItemName'] = $r['item_name_fa'];
    $r['WorkshopCode'] = $r['workshop_code'];
    $r['TechnicalCode'] = $r['technical_code'];
    $r['ManufacturerBrand'] = $r['brand'];
    $r['OEMCode'] = $r['oem_code'];
    $r['PartNumber'] = $r['part_number'];
    return $r;
}
