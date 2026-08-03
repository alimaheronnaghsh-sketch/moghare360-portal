<?php
require_once __DIR__.'/inv360-db.php';
require_once __DIR__.'/inv360-validation.php';
function inv360_search_items($conn, string $q, int $limit=50): array {
    $q=trim($q); if($q==='') return [];
    $norm=inv360_normalize_search($q);
    $like='%'.str_replace(['%','_'],['\\%','\\_'],$q).'%';
    $likeNorm='%'.str_replace(['%','_'],['\\%','\\_'],$norm).'%';
    $limit=max(1,min(200,$limit));
    $sql="SELECT TOP $limit i.item_id AS PartID, i.workshop_code AS WorkshopCode, i.item_code AS InternalCode, i.technical_code AS TechnicalCode,
        i.item_name_fa AS ItemName, i.item_name_en AS ItemNameEn, i.common_name AS CommonName, i.brand AS ManufacturerBrand,
        i.part_number AS PartNumber, i.oem_code AS OEMCode, i.alternative_codes AS AlternativeCodes, i.barcode AS Barcode,
        i.item_status AS ItemStatus, i.is_active AS IsActive,
        ISNULL((SELECT SUM(b.physical_qty) FROM dbo.inv360_stock_balances b WHERE b.item_id=i.item_id), i.quantity) AS PhysicalQty,
        ISNULL((SELECT SUM(b.reserved_qty) FROM dbo.inv360_stock_balances b WHERE b.item_id=i.item_id),0) AS ReservedQty,
        ISNULL((SELECT SUM(b.quarantine_qty) FROM dbo.inv360_stock_balances b WHERE b.item_id=i.item_id),0) AS QuarantineQty,
        ISNULL((SELECT SUM(b.blocked_qty) FROM dbo.inv360_stock_balances b WHERE b.item_id=i.item_id),0) AS BlockedQty,
        CASE WHEN i.workshop_code=? OR i.item_code=? OR i.technical_code=? OR i.part_number=? OR i.oem_code=? OR i.barcode=? THEN 0
             WHEN i.item_name_fa=? THEN 1 ELSE 2 END AS RankScore
      FROM dbo.inv360_items i
      WHERE i.is_deleted=0 AND (
        i.workshop_code LIKE ? OR i.item_code LIKE ? OR i.technical_code LIKE ?
        OR i.item_name_fa LIKE ? OR i.item_name_en LIKE ? OR i.common_name LIKE ?
        OR i.part_number LIKE ? OR i.oem_code LIKE ? OR i.alternative_codes LIKE ? OR i.barcode LIKE ?
        OR i.search_norm LIKE ?)
      ORDER BY RankScore ASC, i.item_id DESC";
    $params=[$q,$q,$q,$q,$q,$q,$q,$like,$like,$like,$like,$like,$like,$like,$like,$like,$like,$likeNorm];
    $rows=inv360_rows($conn,$sql,$params);
    foreach($rows as &$r){
        $r['AvailableQty']=(float)$r['PhysicalQty']-(float)$r['ReservedQty']-(float)$r['QuarantineQty']-(float)$r['BlockedQty'];
    }
    unset($r); return $rows;
}